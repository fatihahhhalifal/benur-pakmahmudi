<?php

namespace App\Http\Controllers\SatuanPasar;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PesananController extends Controller
{
    /**
     * INDEX: Tampilkan daftar pesanan untuk Admin & Operator
     */
    public function index(): View
    {
        $user = Auth::user();

        $query = DB::table('pesanan')
            ->join('users', 'pesanan.user_id', '=', 'users.id')
            ->join('detail_pesanan', 'pesanan.id', '=', 'detail_pesanan.pesanan_id')
            ->join('siklus_kolam', 'detail_pesanan.siklus_id', '=', 'siklus_kolam.id')
            ->join('master_kolam', 'siklus_kolam.kolam_id', '=', 'master_kolam.id')
            ->leftJoin('jenis_benur', 'siklus_kolam.jenis_id', '=', 'jenis_benur.id')
            ->leftJoin('ukuran_benur', 'siklus_kolam.ukuran_id', '=', 'ukuran_benur.id')
            ->leftJoin('grade_benur', 'siklus_kolam.grade_id', '=', 'grade_benur.id')
            ->select(
                'pesanan.id',
                'pesanan.nomor_invoice',
                'pesanan.status',
                'pesanan.nominal_dp_dibayar',
                'pesanan.total_pembayaran_final',
                'pesanan.bukti_transfer_dp',
                'pesanan.bukti_transfer_pelunasan',
                'pesanan.created_at',
                'users.name as nama_customer',
                'detail_pesanan.id as detail_id',
                'detail_pesanan.siklus_id',
                'detail_pesanan.jumlah_sak_dipesan',
                'detail_pesanan.kantong_eceran_dipesan',
                'detail_pesanan.total_kantong_hitung',
                'detail_pesanan.total_kantong_riil_muat',
                'detail_pesanan.konversi_per_kantong',
                'detail_pesanan.harga_per_ekor_kontrak',
                'detail_pesanan.diskon_pembulatan_manual as diskon_pembulatan',
                'detail_pesanan.sak_riil_muat',
                'detail_pesanan.ekor_riil_muat',
                'master_kolam.nama_kolam',
                'master_kolam.id as kolam_id',
                'jenis_benur.nama as nama_jenis',
                'ukuran_benur.ukuran as label_ukuran',
                'grade_benur.nama_grade as nama_grade',
                DB::raw('DATEDIFF(NOW(), siklus_kolam.waktu_tabur) as live_doc'),
                'siklus_kolam.stok_tersedia',
            );

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

        $pesananIds = $pesanan->pluck('id')->toArray();

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
            ->orderBy('log_kalkulasi_pesanan.created_at', 'asc')
            ->get()
            ->groupBy('pesanan_id');

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

        $pesanan->each(function ($p) use ($logKalkulasi, $logTimbang) {
            $p->log_kalkulasi        = $logKalkulasi->get($p->id, collect());
            $p->log_timbang_muat_raw = $logTimbang->get($p->id, collect());
        });

        $sc = DB::table('pesanan')
            ->selectRaw("
                COUNT(*) as total,
                SUM(status = 'pending') as pending,
                SUM(status = 'proses') as proses,
                SUM(status = 'menunggu_kalkulasi') as menunggu_kalkulasi,
                SUM(status = 'menunggu_pelunasan') as menunggu_pelunasan,
                SUM(status = 'selesai') as selesai,
                SUM(status = 'batal') as batal
            ")
            ->first();

        $stats = (object) [
            'total_aktif'        => ($sc->total ?? 0) - ($sc->batal ?? 0),
            'pending'            => (int) ($sc->pending ?? 0),
            'proses'             => (int) ($sc->proses ?? 0),
            'menunggu_kalkulasi' => (int) ($sc->menunggu_kalkulasi ?? 0),
            'menunggu_pelunasan' => (int) ($sc->menunggu_pelunasan ?? 0),
            'selesai'            => (int) ($sc->selesai ?? 0),
            'batal'              => (int) ($sc->batal ?? 0),
        ];

        return view('admin.pesanan.index', compact('pesanan', 'stats'));
    }

    /**
     * Menampilkan daftar seluruh preorder masuk di Dashboard Admin (Fallback Redirect)
     */
    public function dashboardAdmin(): RedirectResponse
    {
        return redirect()->route('admin.pesanan.index');
    }

    /**
     * VERIFIKASI DP (NOTA 1)
     */
    public function konfirmasiDP(Request $request, int $id): RedirectResponse
    {
        $detailOrder = DB::table('detail_pesanan')
            ->where('pesanan_id', $id)
            ->first();

        if (!$detailOrder) {
            return redirect()->back()->with('error', 'Detail draf kontrak pesanan tidak ditemukan.');
        }

        $nominalDpOtomatis = $detailOrder->subtotal_kotor * 0.2;

        DB::table('pesanan')->where('id', $id)->update([
            'status'           => 'proses',
            'nominal_dp_dibayar' => $nominalDpOtomatis,
            'is_harga_dikunci' => true,
            'waktu_kunci_dp'   => now(),
            'updated_at'       => now()
        ]);

        return redirect()->back()->with('success', 'Uang muka sebesar Rp ' . number_format($nominalDpOtomatis, 0, ',', '.') . ' berhasil diverifikasi otomatis! NOTA DP Resmi diterbitkan & Harga Kontrak dikunci.');
    }

    /**
     * TAHAP 1 (OPERATOR / ADMIN): Input muatan fisik riil dari lapangan
     */
    public function inputMuat(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'total_kantong_riil_muat' => 'required|integer|min:1',
            'konversi_per_kantong'    => 'required|integer|min:1',
            'detail_id'               => 'required'
        ]);

        return DB::transaction(function () use ($request, $id) {

            $detailLama = DB::table('detail_pesanan')
                ->where('id', $request->detail_id)
                ->select('konversi_per_kantong')
                ->first();

            $konversiLama = $detailLama?->konversi_per_kantong ?? null;
            $konversiBaru = (int) $request->konversi_per_kantong;

            DB::table('detail_pesanan')->where('id', $request->detail_id)->update([
                'total_kantong_riil_muat' => $request->total_kantong_riil_muat,
                'konversi_per_kantong'    => $konversiBaru,
                'updated_at'              => now()
            ]);

            DB::table('log_kalkulasi_pesanan')->insert([
                'pesanan_id'   => $id,
                'user_id'      => Auth::id(),
                'aksi'         => 'timbang_muat',
                'data_sebelum' => json_encode(['konversi_per_kantong' => $konversiLama]),
                'data_sesudah' => json_encode(['konversi_per_kantong' => $konversiBaru]),
                'catatan'      => 'Timbang muat oleh ' . Auth::user()->name
                                  . '. Konversi: ' . ($konversiLama ?? '-') . ' → ' . $konversiBaru
                                  . '. Total kantong: ' . $request->total_kantong_riil_muat,
                'created_at'   => now(),
                'updated_at'   => now()
            ]);

            DB::table('pesanan')->where('id', $id)->update([
                'status'     => 'menunggu_kalkulasi',
                'updated_at' => now()
            ]);

            return redirect()->back()->with('success', 'Data fisik muatan berhasil diajukan. Menunggu Admin menghitung tagihan.');
        });
    }

    /**
     * TAHAP 2 (ADMIN): Kalkulasi tagihan final
     */
    public function kalkulasiFinal(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'diskon_pembulatan' => 'required|numeric|min:0'
        ]);

        return DB::transaction(function () use ($request, $id) {

            $order = DB::table('pesanan')
                ->join('detail_pesanan', 'pesanan.id', '=', 'detail_pesanan.pesanan_id')
                ->join('siklus_kolam', 'detail_pesanan.siklus_id', '=', 'siklus_kolam.id')
                ->where('pesanan.id', $id)
                ->select('pesanan.*', 'detail_pesanan.*', 'siklus_kolam.waktu_tabur', 'siklus_kolam.id as siklus_id_riil')
                ->first();

            if (!$order) {
                return redirect()->back()->with('error', 'Data preorder tidak ditemukan.');
            }

            $docAktif = (int) Carbon::parse($order->waktu_tabur)->startOfDay()->diffInDays(now()->startOfDay());
            $hargaFinalPerEkor = $order->harga_per_ekor_kontrak;

            if (!$order->is_harga_dikunci) {
                $hargaFinalPerEkor = 6;
                if ($docAktif > 5 && $docAktif <= 8) {
                    $hargaFinalPerEkor = 5;
                } elseif ($docAktif > 8) {
                    $hargaFinalPerEkor = 4;
                }
            }

            $volumePengali        = $order->total_kantong_riil_muat * $order->konversi_per_kantong;
            $subtotalKotor        = $volumePengali * $hargaFinalPerEkor;
            $totalPembayaranFinal = $subtotalKotor - $request->diskon_pembulatan;

            DB::table('pesanan')->where('id', $id)->update([
                'status'                 => 'menunggu_pelunasan',
                'total_pembayaran_final' => $totalPembayaranFinal,
                'updated_at'             => now()
            ]);

            DB::table('detail_pesanan')->where('pesanan_id', $id)->update([
                'harga_per_ekor_aktual'    => $hargaFinalPerEkor,
                'subtotal_kotor'           => $subtotalKotor,
                'diskon_pembulatan_manual' => $request->diskon_pembulatan,
                'updated_at'              => now()
            ]);

            DB::table('siklus_kolam')
                ->where('id', $order->siklus_id_riil)
                ->decrement('stok_tersedia', $volumePengali);

            DB::table('log_kalkulasi_pesanan')->insert([
                'pesanan_id'   => $id,
                'user_id'      => Auth::id(),
                'aksi'         => 'kalkulasi_final',
                'data_sebelum' => json_encode([]),
                'data_sesudah' => json_encode([
                    'subtotal_kotor'         => $subtotalKotor,
                    'diskon_pembulatan'      => $request->diskon_pembulatan,
                    'total_pembayaran_final' => $totalPembayaranFinal,
                ]),
                'catatan'      => 'Kalkulasi final oleh ' . Auth::user()->name
                                  . '. Subtotal: Rp ' . number_format($subtotalKotor, 0, ',', '.')
                                  . ', Diskon: Rp ' . number_format($request->diskon_pembulatan, 0, ',', '.'),
                'created_at'   => now(),
                'updated_at'   => now()
            ]);

            return redirect()->back()->with('success', 'Kalkulasi berhasil! Stok telah terpotong dan tagihan diterbitkan.');
        });
    }

    /**
     * TAHAP 3 (ADMIN): Verifikasi pelunasan
     */
    public function validasiPelunasan(Request $request, int $id): RedirectResponse
    {
        DB::table('pesanan')->where('id', $id)->update([
            'status'                => 'selesai',
            'waktu_pelunasan_final' => now(),
            'updated_at'            => now()
        ]);

        return redirect()->back()->with('with_invoice_id', $id)->with('success', 'Pembayaran diverifikasi! NOTA PELUNASAN resmi diterbitkan.');
    }

    /**
     * ✅ FIX: FITUR INVOICE GENERATOR — ambil SEMUA item detail pesanan, bukan first()
     */
    public function cetakInvoice(Request $request, int $id): View
    {
        $type = $request->query('type', 'dp');

        // Data header pesanan saja (tanpa join detail)
        $pesanan = DB::table('pesanan')
            ->join('users', 'pesanan.user_id', '=', 'users.id')
            ->select(
                'pesanan.*',
                'users.name as nama_customer',
                'users.email as email_customer',
            )
            ->where('pesanan.id', $id)
            ->first();

        if (!$pesanan) {
            abort(404, 'Data transaksi tidak ditemukan.');
        }

        // ✅ Ambil SEMUA item (semua kolam) dalam pesanan ini
        $items = DB::table('detail_pesanan')
            ->join('siklus_kolam', 'detail_pesanan.siklus_id', '=', 'siklus_kolam.id')
            ->join('master_kolam', 'siklus_kolam.kolam_id', '=', 'master_kolam.id')
            ->leftJoin('jenis_benur', 'siklus_kolam.jenis_id', '=', 'jenis_benur.id')
            ->leftJoin('ukuran_benur', 'siklus_kolam.ukuran_id', '=', 'ukuran_benur.id')
            ->leftJoin('grade_benur', 'siklus_kolam.grade_id', '=', 'grade_benur.id')
            ->where('detail_pesanan.pesanan_id', $id)
            ->select(
                'detail_pesanan.*',
                'master_kolam.nama_kolam',
                'jenis_benur.nama as nama_jenis',
                'ukuran_benur.ukuran as label_ukuran',
                'grade_benur.nama_grade as nama_grade',
                'siklus_kolam.jenis_id',
                'siklus_kolam.ukuran_id',
                'siklus_kolam.grade_id',
            )
            ->get();

        if ($items->isEmpty()) {
            abort(404, 'Detail pesanan tidak ditemukan.');
        }

        // Hitung harga aktual dari master_harga per item, inject ke tiap item
        foreach ($items as $item) {
            $hargaMaster = DB::table('master_harga')
                ->where('jenis_id', $item->jenis_id)
                ->where('ukuran_id', $item->ukuran_id)
                ->where('grade_id', $item->grade_id)
                ->value('harga_jual');

            $item->harga_real = $hargaMaster
                ?? ($type === 'dp' ? $item->harga_per_ekor_kontrak : $item->harga_per_ekor_aktual)
                ?? 0;

            $item->total_ekor_booking = $item->total_kantong_hitung * $item->konversi_per_kantong;
            $item->total_ekor_aktual  = ($item->total_kantong_riil_muat ?? 0) * $item->konversi_per_kantong;

            $item->subtotal_item = $type === 'dp'
                ? $item->total_ekor_booking * $item->harga_real
                : $item->total_ekor_aktual * $item->harga_real;
        }

        // Agregat untuk ringkasan finansial
        $subtotalKotor   = $items->sum('subtotal_item');
        $diskonTotal     = $items->sum('diskon_pembulatan_manual') ?? 0;
        $dpDibayar       = $pesanan->nominal_dp_dibayar ?? 0;
        $totalTagihan    = $pesanan->total_pembayaran_final ?? ($subtotalKotor - $diskonTotal);
        $sisaTagihan     = $type === 'dp'
            ? $subtotalKotor - $dpDibayar
            : $totalTagihan - $dpDibayar;

        // Nama kolam pertama untuk "Asal Pengambilan" di header nota
        $namaKolamPertama = $items->first()->nama_kolam ?? '-';

        return view('admin.pesanan.cetak_nota', compact(
            'pesanan', 'items', 'type',
            'subtotalKotor', 'diskonTotal', 'dpDibayar', 'totalTagihan', 'sisaTagihan',
            'namaKolamPertama'
        ));
    }

    /**
     * FITUR SURAT JALAN LOGISTIK
     */
    public function cetakSuratJalan(int $id): View
    {
        $pesanan = DB::table('pesanan')
            ->join('users', 'pesanan.user_id', '=', 'users.id')
            ->join('detail_pesanan', 'pesanan.id', '=', 'detail_pesanan.pesanan_id')
            ->join('siklus_kolam', 'detail_pesanan.siklus_id', '=', 'siklus_kolam.id')
            ->join('master_kolam', 'siklus_kolam.kolam_id', '=', 'master_kolam.id')
            ->leftJoin('jenis_benur', 'siklus_kolam.jenis_id', '=', 'jenis_benur.id')
            ->leftJoin('ukuran_benur', 'siklus_kolam.ukuran_id', '=', 'ukuran_benur.id')
            ->select(
                'pesanan.*',
                'users.name as nama_customer',
                'detail_pesanan.total_kantong_riil_muat',
                'master_kolam.nama_kolam',
                'jenis_benur.nama as nama_jenis',
                'ukuran_benur.ukuran as label_ukuran'
            )
            ->where('pesanan.id', $id)
            ->first();

        if (!$pesanan || $pesanan->status !== 'selesai') {
            abort(403, 'Surat jalan logistik muat hanya diterbitkan jika status transaksi sudah Selesai Lunas.');
        }

        return view('admin.pesanan.cetak_surat_jalan', compact('pesanan'));
    }
}