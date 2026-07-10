<?php

namespace App\Http\Controllers\SatuanPasar;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminPesananController extends Controller
{
    /**
     * Menampilkan daftar seluruh antrean preorder masuk untuk Admin & Operator
     */
    public function index(): View
    {
        // Otomatis batalkan pesanan pending yang sudah lewat dari 1 jam
        $this->autoCancelExpiredOrders();

        $user = Auth::user();

        $query = DB::table('pesanan')
            ->join('users', 'pesanan.user_id', '=', 'users.id')
            ->join('detail_pesanan', 'pesanan.id', '=', 'detail_pesanan.pesanan_id')
            ->join('siklus_kolam', 'detail_pesanan.siklus_id', '=', 'siklus_kolam.id')
            ->join('master_kolam', 'siklus_kolam.kolam_id', '=', 'master_kolam.id')
            ->join('jenis_benur', 'siklus_kolam.jenis_id', '=', 'jenis_benur.id')
            ->join('ukuran_benur', 'siklus_kolam.ukuran_id', '=', 'ukuran_benur.id')
            ->select(
                'pesanan.*',
                'users.name as nama_customer',
                'detail_pesanan.id as detail_id',
                'detail_pesanan.jumlah_sak_dipesan',
                'detail_pesanan.kantong_eceran_dipesan',
                'detail_pesanan.total_kantong_hitung',
                'detail_pesanan.total_kantong_riil_muat',
                'detail_pesanan.konversi_per_kantong',
                'detail_pesanan.konversi_per_kantong_aktual',
                'detail_pesanan.waktu_timbang_muat',
                'detail_pesanan.waktu_kalkulasi_final',
                'detail_pesanan.harga_per_ekor_kontrak',
                'detail_pesanan.subtotal_kotor',
                'detail_pesanan.diskon_pembulatan_manual as diskon_pembulatan',
                'master_kolam.nama_kolam',
                'siklus_kolam.id as siklus_id',
                'siklus_kolam.waktu_tabur',
                'jenis_benur.nama as nama_jenis',
                'ukuran_benur.ukuran as label_ukuran',
                DB::raw('DATEDIFF(NOW(), siklus_kolam.waktu_tabur) as live_doc'),
            );

        // Operator hanya lihat status proses + riwayat yang sudah dikerjakannya
        if ($user->role === 'operator') {
            $query->whereIn('pesanan.status', [
                'proses',
                'menunggu_kalkulasi',
                'menunggu_pelunasan',
                'selesai',
            ]);
        }

        $pesanan = $query
            ->orderByRaw("FIELD(pesanan.status,
                'pending','proses','menunggu_kalkulasi','menunggu_pelunasan','selesai','batal'
            )")
            ->orderBy('pesanan.created_at', 'desc')
            ->get();

        // Ambil semua log sekaligus — TIDAK N+1
        $pesananIds = $pesanan->pluck('id')->toArray();

        // log_kalkulasi: semua aksi KECUALI timbang_muat (untuk modal kalkulasi admin)
        $logKalkulasi = DB::table('log_kalkulasi_pesanan')
            ->join('users', 'log_kalkulasi_pesanan.user_id', '=', 'users.id')
            ->whereIn('log_kalkulasi_pesanan.pesanan_id', $pesananIds)
            ->where('log_kalkulasi_pesanan.aksi', '!=', 'timbang_muat')
            ->select(
                'log_kalkulasi_pesanan.pesanan_id',
                'log_kalkulasi_pesanan.aksi',
                'log_kalkulasi_pesanan.catatan',
                'log_kalkulasi_pesanan.created_at',
                'users.name as nama_operator'
            )
            ->orderBy('log_kalkulasi_pesanan.created_at', 'desc')
            ->get()
            ->groupBy('pesanan_id');

        // log_timbang_muat: HANYA aksi timbang_muat (untuk kolom Detail Pesanan)
        $logTimbang = DB::table('log_kalkulasi_pesanan')
            ->join('users', 'log_kalkulasi_pesanan.user_id', '=', 'users.id')
            ->whereIn('log_kalkulasi_pesanan.pesanan_id', $pesananIds)
            ->where('log_kalkulasi_pesanan.aksi', 'timbang_muat')
            ->select(
                'log_kalkulasi_pesanan.pesanan_id',
                'log_kalkulasi_pesanan.data_sebelum',
                'log_kalkulasi_pesanan.data_sesudah',
                'log_kalkulasi_pesanan.created_at',
                'users.name as nama_operator'
            )
            ->orderBy('log_kalkulasi_pesanan.created_at', 'asc')
            ->get()
            ->groupBy('pesanan_id');

        // Inject log ke tiap pesanan
        $pesanan->each(function ($p) use ($logKalkulasi, $logTimbang) {
            $p->log_kalkulasi        = $logKalkulasi->get($p->id, collect());
            $p->log_timbang_muat_raw = $logTimbang->get($p->id, collect());
        });

        // Statistik akurat per status
        $stats = DB::table('pesanan')
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status NOT IN ('batal') THEN 1 ELSE 0 END) as total_aktif,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'proses' THEN 1 ELSE 0 END) as proses,
                SUM(CASE WHEN status = 'menunggu_kalkulasi' THEN 1 ELSE 0 END) as menunggu_kalkulasi,
                SUM(CASE WHEN status = 'menunggu_pelunasan' THEN 1 ELSE 0 END) as menunggu_pelunasan,
                SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai,
                SUM(CASE WHEN status = 'batal' THEN 1 ELSE 0 END) as batal
            ")
            ->first();

        return view('admin.pesanan.index', compact('pesanan', 'stats'));
    }

    /**
     * VERIFIKASI DP (NOTA 1): Admin mengunci nominal DP masuk & mengunci harga kontrak awal
     */
    public function verifikasiDp(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'nominal_dp' => 'required|numeric|min:1'
        ]);

        DB::table('pesanan')->where('id', $id)->update([
            'status'             => 'proses',
            'nominal_dp_dibayar' => $request->nominal_dp,
            'is_harga_dikunci'   => true,
            'waktu_kunci_dp'     => now(),
            'updated_at'         => now()
        ]);

        DB::table('log_kalkulasi_pesanan')->insert([
            'pesanan_id'   => $id,
            'user_id'      => Auth::id(),
            'aksi'         => 'verifikasi_dp',
            'data_sesudah' => json_encode(['nominal_dp' => $request->nominal_dp, 'status' => 'proses']),
            'catatan'      => 'DP Rp ' . number_format($request->nominal_dp, 0, ',', '.') . ' diverifikasi oleh ' . Auth::user()->name,
            'created_at'   => now(),
            'updated_at'   => now()
        ]);

        return redirect()->back()->with('success', 'Uang muka berhasil diverifikasi! Pesanan dilanjutkan ke Proses Muat.');
    }

    /**
     * TAHAP 1 (OPERATOR / ADMIN): Input aktual fisik yang dimuat ke truk
     * aksi log = 'timbang_muat' agar terbaca di kolom Detail Pesanan
     *
     * PENTING:
     * - 'konversi_per_kantong' adalah snapshot konversi BOOKING AWAL (saat checkout)
     *   dan TIDAK PERNAH diubah lagi setelah pesanan dibuat.
     * - 'konversi_per_kantong_aktual' adalah konversi yang berlaku untuk FISIK RIIL
     *   hasil timbang muat, dan boleh berbeda dari konversi booking awal.
     */
    public function inputMuat(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'total_kantong_riil_muat'     => 'required|integer|min:1',
            'konversi_per_kantong_aktual' => 'required|integer|min:1',
            'detail_id'                   => 'required'
        ]);

        return DB::transaction(function () use ($request, $id) {

            // Ambil data lama sebelum diubah
            $detailLama = DB::table('detail_pesanan')->where('id', $request->detail_id)->first();

            // Konversi booking awal TIDAK BOLEH disentuh
            $konversiAwal = $detailLama->konversi_per_kantong;

            $konversiAktualLama = $detailLama->konversi_per_kantong_aktual ?? $konversiAwal;
            $kantongLama        = $detailLama->total_kantong_riil_muat ?? 0;
            $ekorLama           = $kantongLama * $konversiAktualLama;

            $kantongBaru        = (int) $request->total_kantong_riil_muat;
            $konversiAktualBaru = (int) $request->konversi_per_kantong_aktual;
            $ekorBaru           = $kantongBaru * $konversiAktualBaru;
            $adaPerubahan       = ($konversiAktualLama != $konversiAktualBaru) || ($kantongLama != $kantongBaru);

            // 1. Update fisik muatan. 'konversi_per_kantong' (booking awal) tidak ikut diupdate.
            DB::table('detail_pesanan')->where('id', $request->detail_id)->update([
                'total_kantong_riil_muat'     => $kantongBaru,
                'konversi_per_kantong_aktual' => $konversiAktualBaru,
                'waktu_timbang_muat'          => now(),
                'updated_at'                  => now()
            ]);

            // 2. Lempar ke tahap kalkulasi Admin HANYA jika SEMUA kolam pada pesanan ini
            //    sudah ditimbang muat. Jika pesanan berisi >1 kolam dan masih ada yang
            //    belum ditimbang, status tetap 'proses' sampai kolam terakhir selesai.
            $belumTimbang = DB::table('detail_pesanan')
                ->where('pesanan_id', $id)
                ->whereNull('waktu_timbang_muat')
                ->count();

            if ($belumTimbang === 0) {
                DB::table('pesanan')->where('id', $id)->update([
                    'status'     => 'menunggu_kalkulasi',
                    'updated_at' => now()
                ]);
            }

            // 3. Log dengan aksi 'timbang_muat' — penting agar muncul di riwayat muat
            DB::table('log_kalkulasi_pesanan')->insert([
                'pesanan_id'   => $id,
                'user_id'      => Auth::id(),
                'aksi'         => 'timbang_muat',
                'data_sebelum' => json_encode([
                    'total_kantong_riil_muat'     => $kantongLama,
                    'konversi_per_kantong_aktual' => $konversiAktualLama,
                    'total_ekor'                  => $ekorLama,
                ]),
                'data_sesudah' => json_encode([
                    'total_kantong_riil_muat'     => $kantongBaru,
                    'konversi_per_kantong_aktual' => $konversiAktualBaru,
                    'total_ekor'                  => $ekorBaru,
                ]),
                'catatan'      => $adaPerubahan && $kantongLama > 0
                    ? "Diubah: {$kantongLama} ktg @{$konversiAktualLama}/ktg ({$ekorLama} ekor) → {$kantongBaru} ktg @{$konversiAktualBaru}/ktg ({$ekorBaru} ekor) oleh " . Auth::user()->name
                    : "{$kantongBaru} kantong riil @{$konversiAktualBaru} ekor/ktg = {$ekorBaru} ekor — diinput oleh " . Auth::user()->name,
                'created_at'   => now(),
                'updated_at'   => now()
            ]);

            $pesan = $belumTimbang === 0
                ? 'Data timbang muat berhasil disubmit. Semua kolam sudah ditimbang, menunggu admin memproses kalkulasi tagihan.'
                : "Data timbang muat kolam ini berhasil disubmit. Masih ada {$belumTimbang} kolam lain pada pesanan ini yang belum ditimbang.";

            return redirect()->back()->with('success', $pesan);
        });
    }

    /**
     * TAHAP 2 (ADMIN): Kalkulasi tagihan SEMUA KOLAM dalam 1 pesanan sekaligus,
     * tentukan diskon gabungan, terbitkan invoice & POTONG STOK tiap kolam.
     *
     * Aman dilakukan sekaligus (bukan per-kolam) karena syarat status pesanan
     * naik ke 'menunggu_kalkulasi' adalah SEMUA kolam sudah ditimbang muat —
     * jadi saat method ini dipanggil, data fisik semua kolam sudah lengkap.
     */
    public function kalkulasiFinal(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'diskon_pembulatan' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($request, $id) {
            $semuaDetail = DB::table('detail_pesanan')->where('pesanan_id', $id)->get();

            $totalSubtotalSemuaKolam = 0;
            $totalEkorSemuaKolam     = 0;
            $rincianPerKolam         = [];
            $isBarisPertama          = true;

            foreach ($semuaDetail as $detailPesanan) {
                // Gunakan konversi aktual hasil timbang muat (fallback ke konversi booking awal jika belum diisi)
                $konversiAktual    = $detailPesanan->konversi_per_kantong_aktual ?? $detailPesanan->konversi_per_kantong;
                $totalEkorAktual   = $detailPesanan->total_kantong_riil_muat * $konversiAktual;
                $hargaKontrak      = $detailPesanan->harga_per_ekor_kontrak;
                $subtotalKotorBaru = $totalEkorAktual * $hargaKontrak;

                // Diskon gabungan dari admin dicatat pada baris pertama saja, supaya SUM()
                // di seluruh pesanan tetap benar (tidak dobel) dan tidak perlu kolom baru.
                $diskonUntukBarisIni = $isBarisPertama ? $request->diskon_pembulatan : 0;

                DB::table('detail_pesanan')->where('id', $detailPesanan->id)->update([
                    'harga_per_ekor_aktual'    => $hargaKontrak,
                    'subtotal_kotor'           => $subtotalKotorBaru,
                    'diskon_pembulatan_manual' => $diskonUntukBarisIni,
                    'waktu_kalkulasi_final'    => now(),
                    'updated_at'               => now()
                ]);

                DB::table('siklus_kolam')
                    ->where('id', $detailPesanan->siklus_id)
                    ->decrement('stok_tersedia', $totalEkorAktual);

                $totalSubtotalSemuaKolam += $subtotalKotorBaru;
                $totalEkorSemuaKolam     += $totalEkorAktual;
                $rincianPerKolam[] = [
                    'kantong_riil'   => $detailPesanan->total_kantong_riil_muat,
                    'konversi'       => $konversiAktual,
                    'total_ekor'     => $totalEkorAktual,
                    'harga_per_ekor' => $hargaKontrak,
                    'subtotal_kotor' => $subtotalKotorBaru,
                ];

                $isBarisPertama = false;
            }

            $totalFinalUang = $totalSubtotalSemuaKolam - $request->diskon_pembulatan;

            DB::table('pesanan')->where('id', $id)->update([
                'total_pembayaran_final' => $totalFinalUang,
                'status'                 => 'menunggu_pelunasan',
                'updated_at'             => now()
            ]);

            DB::table('log_kalkulasi_pesanan')->insert([
                'pesanan_id'   => $id,
                'user_id'      => Auth::id(),
                'aksi'         => 'kalkulasi_final',
                'data_sesudah' => json_encode([
                    'jumlah_kolam'   => count($rincianPerKolam),
                    'rincian_kolam'  => $rincianPerKolam,
                    'total_ekor'     => $totalEkorSemuaKolam,
                    'subtotal_kotor' => $totalSubtotalSemuaKolam,
                    'diskon'         => $request->diskon_pembulatan,
                    'total_final'    => $totalFinalUang
                ]),
                'catatan'      => count($rincianPerKolam) . ' kolam, total ' . number_format($totalEkorSemuaKolam, 0, ',', '.') . ' ekor'
                                  . ' = Rp ' . number_format($totalFinalUang, 0, ',', '.')
                                  . ' (diskon Rp ' . number_format($request->diskon_pembulatan, 0, ',', '.') . ') oleh ' . Auth::user()->name,
                'created_at'   => now(),
                'updated_at'   => now()
            ]);

            return redirect()->back()->with('success', 'Kalkulasi selesai untuk seluruh kolam, stok telah dikurangi! Tagihan diterbitkan & menunggu pelunasan dari customer.');
        });
    }

    /**
     * TAHAP 3 (ADMIN): Validasi bukti pelunasan dan Tutup Pesanan (NOTA 2)
     */
    public function validasiPelunasan(Request $request, int $id): RedirectResponse
    {
        DB::table('pesanan')->where('id', $id)->update([
            'status'                => 'selesai',
            'waktu_pelunasan_final' => now(),
            'updated_at'            => now()
        ]);

        return redirect()->back()->with('with_invoice_id', $id)->with('success', 'Pelunasan diverifikasi! SURAT JALAN & NOTA LUNAS resmi diterbitkan.');
    }

    /**
     * Batalkan Pesanan oleh Admin dengan Alasan Rasional
     */
    public function batalkanOlehAdmin(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'alasan_batal' => 'required|string|max:255'
        ]);

        DB::table('pesanan')->where('id', $id)->update([
            'status'            => 'batal',
            'keterangan_batal'  => 'Dibatalkan Admin: ' . $request->alasan_batal,
            'updated_at'        => now()
        ]);

        return redirect()->back()->with('success', 'Pesanan dibatalkan. Kuota draf air dilepas kembali.');
    }

    /**
     * Proteksi Otomatis Pembersih Draf Hangus > 1 Jam Tanpa DP
     */
    private function autoCancelExpiredOrders(): void
    {
        DB::table('pesanan')
            ->where('status', '=', 'pending')
            ->where('created_at', '<=', Carbon::now()->subHour())
            ->update([
                'status'            => 'batal',
                'keterangan_batal'  => 'Sistem Otomatis: Batas waktu transfer DP kedaluwarsa (1 Jam)',
                'updated_at'        => now()
            ]);
    }
}
