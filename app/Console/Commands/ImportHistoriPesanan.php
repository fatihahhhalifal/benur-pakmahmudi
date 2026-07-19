<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ImportHistoriPesanan extends Command
{
    protected $signature = 'pesanan:import-histori {file} {--dry-run} {--admin-id=1}';
    protected $description = 'Import bulk data transaksi historis (tanggal tabur, preorder, pelunasan terpisah)';

    private function parseAngka(string $raw): float
    {
        $str = trim($raw);
        $str = str_replace(['Rp', ' '], '', $str);
        $str = str_replace('.', '', $str);  
        $str = str_replace(',', '.', $str);  
        return $str === '' ? 0.0 : (float) $str;
    }

    private function parseTanggal(string $raw): Carbon
    {
        return Carbon::createFromFormat('d/m/Y', trim($raw))->startOfDay()->addHours(12);
    }

    public function handle(): int
    {
        $path = $this->argument('file');
        $dryRun = $this->option('dry-run');
        $adminId = (int) $this->option('admin-id');

        if (!file_exists($path)) {
            $this->error("File tidak ditemukan: $path");
            return self::FAILURE;
        }

        $rows = array_map('str_getcsv', file($path));
        $header = array_map('trim', array_shift($rows));

        $this->info('Total baris terbaca: ' . count($rows));
        if ($dryRun) {
            $this->warn('MODE DRY-RUN: tidak ada data yang benar-benar disimpan.');
        }

        $sukses = 0;
        $gagal = 0;

        foreach ($rows as $i => $row) {
            $lineNum = $i + 2;

            if (count($row) !== count($header)) {
                $this->error("Baris $lineNum jumlah kolom tidak sesuai, dilewati.");
                $gagal++;
                continue;
            }

            $data = array_combine($header, array_map('trim', $row));

            try {
                $tglTabur     = $this->parseTanggal($data['tgl_tabur']);
                $tglPreorder  = $this->parseTanggal($data['tgl_preorder']);
                $tglPelunasan = $this->parseTanggal($data['tgl_pelunasan']);
            } catch (\Exception $e) {
                $this->error("Baris $lineNum format tanggal salah (harus dd/mm/yyyy), dilewati.");
                $gagal++;
                continue;
            }

            $jumlahEkor   = $this->parseAngka($data['jumlah_ekor']);
            $hargaPerEkor = $this->parseAngka($data['harga_per_ekor']);
            $diskon       = $this->parseAngka($data['diskon']);

            $hargaTotal   = round($jumlahEkor * $hargaPerEkor);
            $hargaFinal   = $hargaTotal - $diskon;
            $dp           = round($hargaTotal * 0.2);
            $pelunasan    = $hargaFinal - $dp;

            $nomorInvoice = $data['nomor_invoice'] ?? ('INV-HIST-' . str_pad($data['no'] ?? $lineNum, 4, '0', STR_PAD_LEFT));

            if ($dryRun) {
                $this->line("[PREVIEW] $nomorInvoice | {$data['nama_customer']} | {$data['jenis']} {$data['ukuran']} {$data['grade']} | Ekor: $jumlahEkor | Total: Rp" . number_format($hargaTotal, 0, ',', '.') . " | DP: Rp" . number_format($dp, 0, ',', '.') . " | Pelunasan: Rp" . number_format($pelunasan, 0, ',', '.'));
                continue;
            }

            DB::beginTransaction();
            try {
                $emailSlug = strtolower(preg_replace('/\s+/', '', $data['nama_customer'])) . '@histori.local';
                $userId = DB::table('users')->where('email', $emailSlug)->value('id');
                if (!$userId) {
                    $userId = DB::table('users')->insertGetId([
                        'name'       => $data['nama_customer'],
                        'email'      => $emailSlug,
                        'password'   => bcrypt(uniqid()),
                        'role'       => 'customer',
                        'created_at' => $tglPreorder,
                        'updated_at' => $tglPreorder,
                    ]);
                }

                $jenisId  = DB::table('jenis_benur')->where('nama', $data['jenis'])->value('id')
                            ?? DB::table('jenis_benur')->insertGetId(['nama' => $data['jenis'], 'created_at' => $tglPreorder, 'updated_at' => $tglPreorder]);
                $ukuranId = DB::table('ukuran_benur')->where('ukuran', $data['ukuran'])->value('id')
                            ?? DB::table('ukuran_benur')->insertGetId(['ukuran' => $data['ukuran'], 'created_at' => $tglPreorder, 'updated_at' => $tglPreorder]);
                $gradeId  = DB::table('grade_benur')->where('nama_grade', $data['grade'])->value('id')
                            ?? DB::table('grade_benur')->insertGetId(['nama_grade' => $data['grade'], 'created_at' => $tglPreorder, 'updated_at' => $tglPreorder]);

                $kolamArsipId = DB::table('master_kolam')->where('nama_kolam', 'ARSIP HISTORIS')->value('id');
                if (!$kolamArsipId) {
                    $kolamArsipId = DB::table('master_kolam')->insertGetId([
                        'nama_kolam'          => 'ARSIP HISTORIS',
                        'kapasitas_maksimal'  => 0,
                        'created_at'          => $tglPreorder,
                        'updated_at'          => $tglPreorder,
                    ]);
                }

                $siklusId = DB::table('siklus_kolam')->insertGetId([
                    'kolam_id'              => $kolamArsipId,
                    'jenis_id'              => $jenisId,
                    'ukuran_id'             => $ukuranId,
                    'grade_id'              => $gradeId,
                    'modal_awal_rupiah'     => 0,
                    'jumlah_tebar_awal'     => $jumlahEkor,
                    'stok_tersedia'         => 0,
                    'potongan_harga_manual' => 0,
                    'waktu_tabur'           => $tglTabur,
                    'status'                => 'selesai',
                    'waktu_kuras'           => $tglPelunasan,
                    'created_at'            => $tglTabur,
                    'updated_at'            => $tglTabur,
                ]);

                $pesananId = DB::table('pesanan')->insertGetId([
                    'user_id'                 => $userId,
                    'nomor_invoice'            => $nomorInvoice,
                    'status'                   => 'selesai',
                    'nominal_dp_dibayar'       => $dp,
                    'total_pembayaran_final'   => $hargaFinal,
                    'is_harga_dikunci'         => true,
                    'waktu_kunci_dp'           => $tglPreorder,
                    'waktu_pelunasan_final'    => $tglPelunasan,
                    'created_at'               => $tglPreorder,
                    'updated_at'               => $tglPelunasan,
                ]);

                DB::table('detail_pesanan')->insert([
                    'pesanan_id'                => $pesananId,
                    'siklus_id'                 => $siklusId,
                    'jumlah_sak_dipesan'        => $jumlahEkor,
                    'kantong_eceran_dipesan'    => 0,
                    'total_kantong_hitung'      => $jumlahEkor,
                    'total_kantong_riil_muat'   => $jumlahEkor,
                    'konversi_per_kantong'      => 1,
                    'harga_per_ekor_kontrak'    => $hargaPerEkor,
                    'harga_per_ekor_aktual'     => $hargaPerEkor,
                    'subtotal_kotor'            => $hargaTotal,
                    'diskon_pembulatan_manual'  => $diskon,
                    'created_at'                => $tglPreorder,
                    'updated_at'                => $tglPelunasan,
                ]);

                DB::table('log_kalkulasi_pesanan')->insert([
                    'pesanan_id'   => $pesananId,
                    'user_id'      => $adminId,
                    'aksi'         => 'import_histori',
                    'data_sebelum' => json_encode([]),
                    'data_sesudah' => json_encode([
                        'harga_total' => $hargaTotal,
                        'dp'          => $dp,
                        'harga_final' => $hargaFinal,
                        'diskon'      => $diskon,
                        'sumber_file' => $data['nama_file'] ?? null,
                    ]),
                    'catatan'      => 'Data diimpor dari histori (tabur ' . $tglTabur->format('d/m/Y') . ', pelunasan ' . $tglPelunasan->format('d/m/Y') . ') via command import-histori. Sumber: ' . ($data['nama_file'] ?? '-'),
                    'created_at'   => $tglPelunasan,
                    'updated_at'   => $tglPelunasan,
                ]);

                DB::commit();
                $sukses++;
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("Baris $lineNum gagal: " . $e->getMessage());
                $gagal++;
            }
        }

        $this->info("Selesai. Sukses: $sukses, Gagal: $gagal");
        return self::SUCCESS;
    }
}