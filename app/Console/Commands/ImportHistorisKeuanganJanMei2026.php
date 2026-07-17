<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Import pembukuan manual (kertas) Januari - Mei 2026 ke dalam sistem.
 *
 * CATATAN PENTING SEBELUM MENJALANKAN:
 * - Total BOP tiap bulan sudah di-cross-check manual dan MATCH persis dengan
 *   catatan asli (Jan 23.900.000 / Feb 26.415.000 / Mar 14.020.000 /
 *   Apr 22.025.000 / Mei 15.930.000). Lihat komentar di $dataBulanan.
 * - "Nopli Vanami" TIDAK dimasukkan sebagai baris bop_kolam biasa, tapi jadi
 *   `modal_awal_rupiah` di siklus_kolam (mengikuti cara LaporanKeuanganController
 *   menghitung pos "Modal Benih" sebagai jurnal virtual).
 * - Command ini bersifat SPLIT RATA: total BOP & Penjualan bulanan dibagi rata
 *   ke N kolam yang aktif bulan itu (dikonfirmasi user). N per bulan:
 *   Jan=10, Feb=10, Mar=6, Apr=8, Mei=6.
 * - Kolam yang dipakai = N kolam PERTAMA berdasarkan urutan id di master_kolam
 *   (asumsi, karena data historis tidak tercatat per-kolam). Edit
 *   $kolamAktifOverride di bawah kalau kenyataan beda.
 * - Ekor benur bulan April diambil dari rumus konsisten (N x 2.000.000), BUKAN
 *   dari angka "18.500" di catatan asli yang meragukan (OCR).
 * - Baris "(Kosong)" di Maret (3.050.000) dikategorikan sebagai
 *   "(Global) Lain-lain - Tidak Berketerangan (perlu ditinjau)" agar tidak
 *   hilang dari BOP, tapi WAJIB ditinjau manual oleh pemilik.
 * - "Penjualan" historis dicatat lewat 1 pesanan dummy PER KOLAM per bulan
 *   (bukan 1 pesanan gabungan), atas nama user internal "Rekap Historis
 *   Pra-Sistem" (email histori@internal.local) — bukan customer asli manapun.
 *
 * Jalankan dengan --dry-run dulu untuk lihat preview tanpa insert ke DB.
 */
class ImportHistorisKeuanganJanMei2026 extends Command
{
    protected $signature = 'import:historis-keuangan {--dry-run : Tampilkan preview tanpa insert ke database}';

    protected $description = 'Import pembukuan manual Januari-Mei 2026 (BOP, Modal Benih, Penjualan) ke sistem';

    /**
     * N kolam pertama (berdasarkan id ASC di master_kolam) yang dipakai tiap bulan.
     * Ubah array ini kalau kamu tahu persis kolam mana saja yang aktif per bulan.
     */
    private array $jumlahKolamPerBulan = [
        '2026-01' => 10,
        '2026-02' => 10,
        '2026-03' => 6,
        '2026-04' => 8,
        '2026-05' => 6,
    ];

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');

        // Guard: cegah double-import kalau command ini pernah dijalankan sebelumnya
        $sudahAda = DB::table('pesanan')->where('nomor_invoice', 'like', 'HIST-%')->exists();
        if ($sudahAda && !$isDryRun) {
            $this->error('Terdeteksi data dengan invoice "HIST-%" sudah ada di tabel pesanan. Import dibatalkan supaya tidak dobel. Hapus data lama dulu kalau memang mau re-import.');
            return self::FAILURE;
        }

        $kolamAktif = DB::table('master_kolam')->orderBy('id')->pluck('id')->all();
        if (count($kolamAktif) < max($this->jumlahKolamPerBulan)) {
            $this->error('Jumlah master_kolam di database (' . count($kolamAktif) . ') lebih sedikit dari kebutuhan maksimal (' . max($this->jumlahKolamPerBulan) . '). Cek data master_kolam dulu.');
            return self::FAILURE;
        }

        $dataBulanan = $this->getDataBulanan();

        if ($isDryRun) {
            $this->tampilkanPreview($dataBulanan, $kolamAktif);
            return self::SUCCESS;
        }

        if (!$this->confirm('Ini akan INSERT data ke siklus_kolam, bop_kolam, dan pesanan/detail_pesanan. Lanjutkan?')) {
            $this->info('Dibatalkan.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($dataBulanan, $kolamAktif) {
            $userHistorisId = $this->getOrCreateUserHistoris();

            foreach ($dataBulanan as $bulanKey => $bulan) {
                $n = $this->jumlahKolamPerBulan[$bulanKey];
                $kolamBulanIni = array_slice($kolamAktif, 0, $n);

                $modalPerKolam = $this->bagiRataDenganSisa($bulan['nopli_nominal'], $n);
                $ekorPerKolam = $this->bagiRataDenganSisa($bulan['nopli_ekor'], $n);
                $penjualanPerKolam = $this->bagiRataDenganSisa($bulan['penjualan_total'], $n);

                foreach ($kolamBulanIni as $index => $kolamId) {
                    // 1. siklus_kolam
                    $siklusId = DB::table('siklus_kolam')->insertGetId([
                        'kolam_id' => $kolamId,
                        'jenis_id' => 1,
                        'ukuran_id' => 1,
                        'grade_id' => 1,
                        'modal_awal_rupiah' => $modalPerKolam[$index],
                        'jumlah_tebar_awal' => $ekorPerKolam[$index],
                        'stok_tersedia' => 0, // asumsi: siklus historis sudah tuntas terjual/panen
                        'potongan_harga_manual' => 0,
                        'waktu_tabur' => $bulan['waktu_tabur'],
                        'status' => 'selesai',
                        'waktu_kuras' => $bulan['tanggal_tercatat'],
                        'created_at' => $bulan['waktu_tabur'],
                        'updated_at' => $bulan['tanggal_tercatat'],
                    ]);

                    // 2. bop_kolam (biaya operasional, tidak termasuk Nopli Vanami)
                    foreach ($bulan['biaya'] as $item) {
                        $nominalPerKolam = $this->bagiRataDenganSisa($item['nominal'], $n);
                        DB::table('bop_kolam')->insert([
                            'siklus_id' => $siklusId,
                            'keterangan_biaya' => $item['kategori'],
                            'nominal_biaya' => $nominalPerKolam[$index],
                            'status' => 'disetujui',
                            'waktu_pencatatan' => $bulan['tanggal_tercatat'],
                            'created_at' => $bulan['waktu_tabur'],
                            'updated_at' => $bulan['tanggal_tercatat'],
                        ]);
                    }

                    // 3. pesanan dummy (representasi penjualan historis per kolam)
                    $noInvoice = 'HIST-' . str_replace('-', '', $bulanKey) . '-K' . $kolamId;
                    $pesananId = DB::table('pesanan')->insertGetId([
                        'user_id' => $userHistorisId,
                        'nomor_invoice' => $noInvoice,
                        'status' => 'selesai',
                        'nominal_dp_dibayar' => 0,
                        'waktu_kunci_dp' => $bulan['waktu_tabur'],
                        'is_harga_dikunci' => 1,
                        'total_pembayaran_final' => $penjualanPerKolam[$index],
                        'waktu_pelunasan_final' => $bulan['tanggal_tercatat'],
                        'catatan_internal_admin' => 'Import otomatis dari pembukuan manual ' . $bulan['label'] . ' (bagi rata ' . $n . ' kolam).',
                        'created_at' => $bulan['waktu_tabur'],
                        'updated_at' => $bulan['tanggal_tercatat'],
                    ]);

                    // 4. detail_pesanan (nilai estimasi, karena rincian sak/kantong asli tidak tercatat)
                    DB::table('detail_pesanan')->insert([
                        'pesanan_id' => $pesananId,
                        'siklus_id' => $siklusId,
                        'kapasitas_kantong_per_sak' => 45,
                        'jumlah_sak_dipesan' => 0,
                        'kantong_eceran_dipesan' => 0,
                        'total_kantong_hitung' => 0,
                        'total_kantong_riil_muat' => 0,
                        'konversi_per_kantong' => 0,
                        'konversi_per_kantong_aktual' => null,
                        'waktu_timbang_muat' => $bulan['tanggal_tercatat'],
                        'harga_per_ekor_kontrak' => 0,
                        'harga_per_ekor_aktual' => 0,
                        'subtotal_kotor' => $penjualanPerKolam[$index],
                        'diskon_pembulatan_manual' => 0,
                        'waktu_kalkulasi_final' => $bulan['tanggal_tercatat'],
                        'created_at' => $bulan['waktu_tabur'],
                        'updated_at' => $bulan['tanggal_tercatat'],
                    ]);
                }

                $this->info("✓ {$bulan['label']}: {$n} kolam diimport (siklus, BOP, modal benih, penjualan).");
            }
        });

        $this->info('Import selesai. Silakan cek Laporan Keuangan untuk verifikasi total per bulan.');
        return self::SUCCESS;
    }

    private function getOrCreateUserHistoris(): int
    {
        $existing = DB::table('users')->where('email', 'histori@internal.local')->first();
        if ($existing) {
            return $existing->id;
        }

        return DB::table('users')->insertGetId([
            'name' => 'Rekap Historis Pra-Sistem',
            'email' => 'histori@internal.local',
            'password' => Hash::make(Str::random(40)),
            'role' => 'customer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Bagi nominal ke N bagian serata mungkin, sisa pembulatan ditaruh di elemen terakhir
     * supaya total tetap PERSIS sama dengan angka aslinya (penting untuk laporan keuangan).
     */
    private function bagiRataDenganSisa(int $total, int $n): array
    {
        $bagian = intdiv($total, $n);
        $hasil = array_fill(0, $n, $bagian);
        $sisa = $total - ($bagian * $n);
        $hasil[$n - 1] += $sisa;
        return $hasil;
    }

    private function tampilkanPreview(array $dataBulanan, array $kolamAktif): void
    {
        foreach ($dataBulanan as $bulanKey => $bulan) {
            $n = $this->jumlahKolamPerBulan[$bulanKey];
            $totalBiaya = collect($bulan['biaya'])->sum('nominal');
            $totalBop = $totalBiaya + $bulan['nopli_nominal'];

            $this->line('');
            $this->info("=== {$bulan['label']} ({$n} kolam: " . implode(',', array_slice($kolamAktif, 0, $n)) . ') ===');
            $this->line("Modal Benih (Nopli Vanami) : Rp " . number_format($bulan['nopli_nominal'], 0, ',', '.') . " (Rp " . number_format(intdiv($bulan['nopli_nominal'], $n), 0, ',', '.') . "/kolam)");
            $this->line("Biaya Operasional (BOP)    : Rp " . number_format($totalBiaya, 0, ',', '.'));
            $this->line("Total BOP (+Modal Benih)   : Rp " . number_format($totalBop, 0, ',', '.'));
            $this->line("Penjualan                  : Rp " . number_format($bulan['penjualan_total'], 0, ',', '.') . " (Rp " . number_format(intdiv($bulan['penjualan_total'], $n), 0, ',', '.') . "/kolam)");
            $this->line("Hasil Bersih (estimasi)    : Rp " . number_format($bulan['penjualan_total'] - $totalBop, 0, ',', '.'));
        }
    }

    private function getDataBulanan(): array
    {
        return [
            '2026-01' => [
                'label' => 'Januari 2026',
                'waktu_tabur' => Carbon::parse('2026-01-01 06:00:00'),
                'tanggal_tercatat' => Carbon::parse('2026-02-01 00:00:00'),
                'nopli_nominal' => 9_900_000,
                'nopli_ekor' => 20_000_000,
                'penjualan_total' => 31_950_000,
                // Total biaya (tanpa Nopli) harus = 14.000.000 -> + Nopli 9.900.000 = BOP 23.900.000 (match)
                'biaya' => [
                    ['kategori' => '(Global) Gaji Tenaga Kerja Lapangan - Uang Makan', 'nominal' => 2_700_000],
                    ['kategori' => '(Global) Energi Listrik & Pompa - Listrik/ Pulsa', 'nominal' => 1_200_000],
                    ['kategori' => '(Global) Energi Listrik & Pompa - Solar, Bensin', 'nominal' => 300_000],
                    ['kategori' => '(Global) Lain-lain - Plastik Bak', 'nominal' => 300_000],
                    ['kategori' => '(Global) Lain-lain - Lampu 3/ Tedeng Lampu + Motoran 4', 'nominal' => 200_000],
                    ['kategori' => '(Global) Obat & Desinfektan Kolam - Pupuk, Aqua', 'nominal' => 100_000],
                    ['kategori' => '(Global) Pakan - Plastik 2500, Sak 54, Karet 2', 'nominal' => 1_500_000],
                    ['kategori' => '(Global) Gaji Tenaga Kerja Lapangan - Konsumsi Panen', 'nominal' => 200_000],
                    ['kategori' => '(Global) Gaji Tenaga Kerja Lapangan - Upah Panen', 'nominal' => 1_750_000],
                    ['kategori' => '(Global) Bahan Kimia & Pengkondisi Air - PLEK', 'nominal' => 900_000],
                    ['kategori' => '(Global) Pakan - Pakan Imam', 'nominal' => 4_850_000],
                ],
            ],
            '2026-02' => [
                'label' => 'Februari 2026',
                'waktu_tabur' => Carbon::parse('2026-02-01 06:00:00'),
                'tanggal_tercatat' => Carbon::parse('2026-03-01 00:00:00'),
                'nopli_nominal' => 11_000_000,
                'nopli_ekor' => 20_000_000,
                'penjualan_total' => 50_350_000,
                // Total biaya (tanpa Nopli) = 15.415.000 -> + Nopli 11.000.000 = BOP 26.415.000 (match)
                'biaya' => [
                    ['kategori' => '(Global) Gaji Tenaga Kerja Lapangan - Uang Makan', 'nominal' => 2_700_000],
                    ['kategori' => '(Global) Energi Listrik & Pompa - Listrik/ Pulsa', 'nominal' => 1_300_000],
                    ['kategori' => '(Global) Energi Listrik & Pompa - Solar, Bensin', 'nominal' => 350_000],
                    ['kategori' => '(Global) Obat & Desinfektan Kolam - Pupuk, Aqua', 'nominal' => 150_000],
                    ['kategori' => '(Global) Lain-lain - Kantong Plangton + Ucek', 'nominal' => 150_000],
                    ['kategori' => '(Global) Vitamin & Suplemen Imun - Vanbel, Termometer', 'nominal' => 100_000],
                    ['kategori' => '(Global) Pakan - Plastik 4000, Sak 40, Karet 3', 'nominal' => 2_350_000],
                    ['kategori' => '(Global) Gaji Tenaga Kerja Lapangan - Upah Panen', 'nominal' => 3_100_000],
                    ['kategori' => '(Global) Pakan - Pakan Imam', 'nominal' => 5_215_000],
                ],
            ],
            '2026-03' => [
                'label' => 'Maret 2026',
                'waktu_tabur' => Carbon::parse('2026-03-01 06:00:00'),
                'tanggal_tercatat' => Carbon::parse('2026-04-01 00:00:00'),
                'nopli_nominal' => 5_500_000,
                'nopli_ekor' => 12_000_000,
                'penjualan_total' => 12_650_000,
                // Total biaya (tanpa Nopli) = 8.520.000 -> + Nopli 5.500.000 = BOP 14.020.000 (match)
                // Hasil bersih bulan ini MINUS (sesuai catatan asli: -1.370.000)
                'biaya' => [
                    ['kategori' => '(Global) Gaji Tenaga Kerja Lapangan - Uang Makan', 'nominal' => 1_500_000],
                    ['kategori' => '(Global) Energi Listrik & Pompa - Listrik/ Pulsa', 'nominal' => 1_200_000],
                    ['kategori' => '(Global) Energi Listrik & Pompa - Solar, Bensin', 'nominal' => 200_000],
                    ['kategori' => '(Global) Lain-lain - 2 Tabung Oksigen', 'nominal' => 320_000],
                    ['kategori' => '(Global) Lain-lain - Waring, Tutup Bethek, Ucek', 'nominal' => 300_000],
                    ['kategori' => '(Global) Obat & Desinfektan Kolam - Pupuk, Aqua', 'nominal' => 150_000],
                    ['kategori' => '(Global) Pakan - Plastik 1300, Sak 30, Karet 2', 'nominal' => 800_000],
                    ['kategori' => '(Global) Gaji Tenaga Kerja Lapangan - Upah Panen', 'nominal' => 1_000_000],
                    // Baris tanpa keterangan di catatan asli - PERLU DITINJAU MANUAL
                    ['kategori' => '(Global) Lain-lain - Tidak Berketerangan (perlu ditinjau)', 'nominal' => 3_050_000],
                ],
            ],
            '2026-04' => [
                'label' => 'April 2026',
                'waktu_tabur' => Carbon::parse('2026-04-01 06:00:00'),
                'tanggal_tercatat' => Carbon::parse('2026-04-24 00:00:00'), // sesuai "Jum'at legi, 24 April 2026" di catatan asli
                'nopli_nominal' => 8_500_000,
                // Ekor "18.500" di catatan asli ambigu (OCR) -> pakai rumus konsisten 8 kolam x 2jt
                'nopli_ekor' => 16_000_000,
                'penjualan_total' => 19_050_000,
                // Total biaya (tanpa Nopli) = 13.525.000 -> + Nopli 8.500.000 = BOP 22.025.000 (match)
                // Hasil bersih bulan ini MINUS (sesuai catatan asli: -2.975.000)
                'biaya' => [
                    ['kategori' => '(Global) Gaji Tenaga Kerja Lapangan - Uang Makan', 'nominal' => 2_700_000],
                    ['kategori' => '(Global) Energi Listrik & Pompa - Listrik/ Pulsa', 'nominal' => 1_200_000],
                    ['kategori' => '(Global) Energi Listrik & Pompa - Solar, Bensin', 'nominal' => 300_000],
                    ['kategori' => '(Global) Vitamin & Suplemen Imun - Vanbel, Kapas', 'nominal' => 200_000],
                    ['kategori' => '(Global) Obat & Desinfektan Kolam - Pupuk, Rinso, Aqua, Ravia', 'nominal' => 200_000],
                    ['kategori' => "(Global) Lain-lain - Servis P'mulik", 'nominal' => 200_000],
                    ['kategori' => '(Global) Lain-lain - Baut + Ring', 'nominal' => 150_000],
                    ['kategori' => '(Global) Lain-lain - 1 Tabung Oksigen', 'nominal' => 160_000],
                    ['kategori' => '(Global) Pakan - Plastik 2300', 'nominal' => 1_500_000],
                    ['kategori' => '(Global) Pakan - 60 Sak, Karet 2', 'nominal' => 210_000],
                    ['kategori' => '(Global) Gaji Tenaga Kerja Lapangan - Upah Panen', 'nominal' => 1_550_000],
                    ['kategori' => '(Global) Gaji Tenaga Kerja Lapangan - Konsumsi Panen', 'nominal' => 250_000],
                    ['kategori' => '(Global) Obat & Desinfektan Kolam - Flok Top 5 Kg', 'nominal' => 900_000],
                    ['kategori' => '(Global) Obat & Desinfektan Kolam - BP Yuhy 1 Kg', 'nominal' => 440_000],
                    ['kategori' => '(Global) Bahan Kimia & Pengkondisi Air - Eri, Groper, P2, Elbarin, DTA, BP Yuhy, Artemia 2 Kg', 'nominal' => 3_565_000],
                ],
            ],
            '2026-05' => [
                'label' => 'Mei 2026',
                'waktu_tabur' => Carbon::parse('2026-05-01 06:00:00'),
                'tanggal_tercatat' => Carbon::parse('2026-05-31 00:00:00'), // tanggal tercoret/tidak jelas di catatan asli
                'nopli_nominal' => 5_500_000,
                'nopli_ekor' => 12_000_000,
                'penjualan_total' => 15_250_000,
                // Total biaya (tanpa Nopli) = 10.430.000 -> + Nopli 5.500.000 = BOP 15.930.000 (match)
                // Hasil bersih bulan ini MINUS (sesuai catatan asli: -680.000)
                'biaya' => [
                    ['kategori' => '(Global) Gaji Tenaga Kerja Lapangan - Uang Makan', 'nominal' => 1_500_000],
                    ['kategori' => '(Global) Energi Listrik & Pompa - Listrik/ Pulsa', 'nominal' => 1_100_000],
                    ['kategori' => '(Global) Energi Listrik & Pompa - Solar, Bensin', 'nominal' => 200_000],
                    ['kategori' => '(Global) Gaji Tenaga Kerja Lapangan - Konsumsi Panen', 'nominal' => 100_000],
                    ['kategori' => '(Global) Obat & Desinfektan Kolam - Pupuk, Aqua', 'nominal' => 100_000],
                    ['kategori' => '(Global) Pakan - Plastik 2000, Karet 2, Sak 50', 'nominal' => 1_500_000],
                    ['kategori' => '(Global) Gaji Tenaga Kerja Lapangan - Upah Panen', 'nominal' => 1_450_000],
                    ['kategori' => '(Global) Bahan Kimia & Pengkondisi Air - BP, Spkeng, P1, Elbarin, Kaporit, Artemia, Groper, Flok Top', 'nominal' => 4_480_000],
                ],
            ],
        ];
    }
}