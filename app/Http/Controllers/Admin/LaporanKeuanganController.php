<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class LaporanKeuanganController extends Controller
{
    /**
     * Tampilan Utama Laporan Keuangan Neraca Konsolidasi
     */
    public function index(Request $request)
    {
        if (!in_array(Auth::user()->role, ['admin', 'pemilik'])) {
            abort(403, 'Akses Ditolak. Menu neraca akuntansi ini dikunci rapat dari Operator lapangan.');
        }

        $arusKas = $this->getKombinasiArusDana($request);

        $totalPendapatan = $arusKas->where('jenis_arus', 'MASUK')->sum('nominal');
        $totalPengeluaran = $arusKas->where('jenis_arus', 'KELUAR')->sum('nominal');
        $keuntunganBersih = $totalPendapatan - $totalPengeluaran;

        return view('admin.laporan.index', compact('arusKas', 'totalPendapatan', 'totalPengeluaran', 'keuntunganBersih'));
    }

    /**
     * Cetak Semua Laporan Arus Kas Jurnal Terpadu
     */
    public function cetakSemua(Request $request)
    {
        if (!in_array(Auth::user()->role, ['admin', 'pemilik'])) {
            abort(403);
        }

        $arusKas = $this->getKombinasiArusDana($request);
        $judul = "Laporan Rekonsiliasi Arus Kas Global";
        $totalPendapatan = $arusKas->where('jenis_arus', 'MASUK')->sum('nominal');
        $totalPengeluaran = $arusKas->where('jenis_arus', 'KELUAR')->sum('nominal');

        return view('admin.laporan.cetak', compact('arusKas', 'judul', 'totalPendapatan', 'totalPengeluaran'));
    }

    /**
     * Cetak Buku Kas Pembantu Pendapatan (Omzet Jual Benur)
     */
    public function cetakMasuk(Request $request)
    {
        if (!in_array(Auth::user()->role, ['admin', 'pemilik'])) {
            abort(403);
        }

        $arusKas = $this->getKombinasiArusDana($request)->where('jenis_arus', 'MASUK');
        $judul = "Buku Kas Pembantu Pendapatan (Omzet Jual)";
        $totalPendapatan = $arusKas->sum('nominal');
        $totalPengeluaran = 0;

        return view('admin.laporan.cetak', compact('arusKas', 'judul', 'totalPendapatan', 'totalPengeluaran'));
    }

    /**
     * Cetak Buku Kas Pembantu Pengeluaran (BOP Budidaya Hulu)
     */
    public function cetakKeluar(Request $request)
    {
        if (!in_array(Auth::user()->role, ['admin', 'pemilik'])) {
            abort(403);
        }

        $arusKas = $this->getKombinasiArusDana($request)->where('jenis_arus', 'KELUAR');
        $judul = "Buku Kas Pembantu Pengeluaran (BOP Lapangan)";
        $totalPendapatan = 0;
        $totalPengeluaran = $arusKas->sum('nominal');

        return view('admin.laporan.cetak', compact('arusKas', 'judul', 'totalPendapatan', 'totalPengeluaran'));
    }

    /**
     * Query Engine: Menggabungkan pengeluaran bop hulu dan omzet riil hilir via SQL UNION
     * Dilengkapi dengan penyaringan koleksi data berdasarkan parameter kueri peladen
     */
    private function getKombinasiArusDana(Request $request = null)
    {
        $pengeluaran = DB::table('bop_kolam')
            ->join('siklus_kolam', 'bop_kolam.siklus_id', '=', 'siklus_kolam.id')
            ->join('master_kolam', 'siklus_kolam.kolam_id', '=', 'master_kolam.id')
            ->where('bop_kolam.status', 'disetujui')
            ->select(
                'bop_kolam.id',
                'bop_kolam.created_at as tanggal',
                'master_kolam.nama_kolam',
                DB::raw("SUBSTRING_INDEX(bop_kolam.keterangan_biaya, ' - ', 1) as pos_akun"),
                DB::raw("CASE WHEN bop_kolam.keterangan_biaya LIKE '% - %' THEN SUBSTRING_INDEX(bop_kolam.keterangan_biaya, ' - ', -1) ELSE bop_kolam.keterangan_biaya END as rincian"),
                'bop_kolam.nominal_biaya as nominal',
                DB::raw("'KELUAR' as jenis_arus")
            );

        // Modal awal pembelian benih disimpan di siklus_kolam. Satukan sebagai
        // jurnal virtual agar laporan keuangan konsisten dengan jurnal BOP.
        $modalBenih = DB::table('siklus_kolam')
            ->join('master_kolam', 'siklus_kolam.kolam_id', '=', 'master_kolam.id')
            ->where('siklus_kolam.modal_awal_rupiah', '>', 0)
            ->select(
                DB::raw("CONCAT('modal-benih-', siklus_kolam.id) as id"),
                'siklus_kolam.waktu_tabur as tanggal',
                'master_kolam.nama_kolam',
                DB::raw("'Modal Benih' as pos_akun"),
                DB::raw("'Pembelian benih awal siklus' as rincian"),
                'siklus_kolam.modal_awal_rupiah as nominal',
                DB::raw("'KELUAR' as jenis_arus")
            );

        $pendapatan = DB::table('pesanan')
            ->join('detail_pesanan', 'pesanan.id', '=', 'detail_pesanan.pesanan_id')
            ->join('siklus_kolam', 'detail_pesanan.siklus_id', '=', 'siklus_kolam.id')
            ->join('master_kolam', 'siklus_kolam.kolam_id', '=', 'master_kolam.id')
            ->whereIn('pesanan.status', ['proses', 'menunggu_kalkulasi', 'menunggu_pelunasan', 'siap_ambil', 'selesai'])
            ->select(
                'pesanan.id',
                DB::raw("COALESCE(CASE WHEN pesanan.status = 'selesai' THEN pesanan.waktu_pelunasan_final ELSE pesanan.waktu_kunci_dp END, pesanan.created_at) as tanggal"),
                'master_kolam.nama_kolam',
                DB::raw("'Omzet Jual Benur' as pos_akun"),
                DB::raw("CONCAT('PREORDER CUST INV ', pesanan.nomor_invoice) as rincian"),
                DB::raw("CASE WHEN pesanan.status = 'selesai' THEN pesanan.total_pembayaran_final ELSE pesanan.nominal_dp_dibayar END as nominal"),
                DB::raw("'MASUK' as jenis_arus")
            );

        $koleksiData = $pengeluaran
            ->unionAll($modalBenih)
            ->unionAll($pendapatan)
            ->orderBy('tanggal', 'desc')
            ->get();

        if ($request) {
            if ($request->filled('start')) {
                $koleksiData = $koleksiData->where('tanggal', '>=', $request->start . ' 00:00:00');
            }
            
            if ($request->filled('end')) {
                $koleksiData = $koleksiData->where('tanggal', '<=', $request->end . ' 23:59:59');
            }

            if ($request->filled('bulan') && $request->bulan !== 'semua') {
                $koleksiData = $koleksiData->filter(function ($item) use ($request) {
                    return Carbon::parse($item->tanggal)->format('Y-m') === $request->bulan;
                });
            }

            if ($request->filled('kategori') && $request->kategori !== 'semua') {
                $koleksiData = $koleksiData->where('pos_akun', $request->kategori);
            }

            if ($request->filled('kolam') && $request->kolam !== 'semua') {
                $koleksiData = $koleksiData->where('nama_kolam', $request->kolam);
            }
        }

        return $koleksiData->values();
    }
}