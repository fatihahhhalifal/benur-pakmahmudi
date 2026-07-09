<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\View\View;

class BiayaOperasionalController extends Controller
{
    public function index(): View
    {
        if (!in_array(Auth::user()->role, ['admin', 'pemilik', 'operator'])) {
            abort(403);
        }

        // =========================================================================
        // DATA KONSOLIDASI UNTUK TRANSAKSI YANG SUDAH SAH (DISETUJUI / VERIFIKASI)
        // =========================================================================
        // A. Arus Keluar Sah (BOP yang sudah di-ACC Admin)
        $pengeluaran_aktif = DB::table('bop_kolam')
            ->join('siklus_kolam', 'bop_kolam.siklus_id', '=', 'siklus_kolam.id')
            ->join('master_kolam', 'siklus_kolam.kolam_id', '=', 'master_kolam.id')
            ->where('siklus_kolam.status', 'aktif')
            ->where('bop_kolam.status', 'disetujui')
            ->select(
                'bop_kolam.id',
                'bop_kolam.created_at as tanggal',
                'bop_kolam.waktu_pencatatan',
                'master_kolam.nama_kolam',
                'siklus_kolam.waktu_tabur',
                DB::raw("NULL as waktu_kuras"),
                DB::raw("'PENGELUARAN' as kategori_akun"),
                DB::raw("SUBSTRING_INDEX(bop_kolam.keterangan_biaya, ' - ', 1) as kategori_bop"),
                DB::raw("CASE WHEN bop_kolam.keterangan_biaya LIKE '% - %' THEN SUBSTRING_INDEX(bop_kolam.keterangan_biaya, ' - ', -1) ELSE bop_kolam.keterangan_biaya END as deskripsi"),
                'bop_kolam.nominal_biaya',
                DB::raw("'KELUAR' as jenis_arus"),
                'bop_kolam.siklus_id',
                DB::raw("'disetujui' as status_bop")
            );

        // B. Arus Masuk Sah (Omzet Preorder)
        $pendapatan_aktif = DB::table('pesanan')
            ->join('detail_pesanan', 'pesanan.id', '=', 'detail_pesanan.pesanan_id')
            ->join('siklus_kolam', 'detail_pesanan.siklus_id', '=', 'siklus_kolam.id')
            ->join('master_kolam', 'siklus_kolam.kolam_id', '=', 'master_kolam.id')
            ->where('siklus_kolam.status', 'aktif')
            ->whereIn('pesanan.status', ['proses', 'menunggu_kalkulasi', 'menunggu_pelunasan', 'siap_ambil', 'selesai'])
            ->select(
                'pesanan.id',
                DB::raw("CASE WHEN pesanan.status = 'selesai' THEN pesanan.waktu_pelunasan_final ELSE pesanan.waktu_kunci_dp END as tanggal"),
                DB::raw("NULL as waktu_pencatatan"),
                'master_kolam.nama_kolam',
                'siklus_kolam.waktu_tabur',
                DB::raw("NULL as waktu_kuras"),
                DB::raw("'PENDAPATAN' as kategori_akun"),
                DB::raw("CASE WHEN pesanan.status = 'selesai' THEN 'Pelunasan Nota Preorder' ELSE 'Uang Muka (DP) Preorder' END as kategori_bop"),
                DB::raw("CONCAT('PREORDER INV ', pesanan.nomor_invoice) as deskripsi"),
                DB::raw("CASE WHEN pesanan.status = 'selesai' THEN pesanan.total_pembayaran_final ELSE pesanan.nominal_dp_dibayar END as nominal_biaya"),
                DB::raw("'MASUK' as jenis_arus"),
                'detail_pesanan.siklus_id',
                DB::raw("'disetujui' as status_bop")
            );

        $bop_aktif = $pengeluaran_aktif->unionAll($pendapatan_aktif)
            ->orderBy('tanggal', 'desc')
            ->get();

        // =========================================================================
        // DATA ANTREAN VALIDASI FINANSIAL OPERATOR (STATUS PENDING)
        // =========================================================================
        $bop_pending = DB::table('bop_kolam')
            ->join('siklus_kolam', 'bop_kolam.siklus_id', '=', 'siklus_kolam.id')
            ->join('master_kolam', 'siklus_kolam.kolam_id', '=', 'master_kolam.id')
            ->where('bop_kolam.status', 'pending')
            ->select(
                'bop_kolam.id',
                'bop_kolam.created_at as tanggal',
                'bop_kolam.waktu_pencatatan',
                'master_kolam.nama_kolam',
                'siklus_kolam.waktu_tabur',
                DB::raw("SUBSTRING_INDEX(bop_kolam.keterangan_biaya, ' - ', 1) as kategori_bop"),
                DB::raw("CASE WHEN bop_kolam.keterangan_biaya LIKE '% - %' THEN SUBSTRING_INDEX(bop_kolam.keterangan_biaya, ' - ', -1) ELSE bop_kolam.keterangan_biaya END as deskripsi"),
                'bop_kolam.nominal_biaya',
                'bop_kolam.siklus_id'
            )
            ->orderBy('tanggal', 'desc')
            ->get();

        // =========================================================================
        // DATA ARSIP SEJARAH MASA LALU (SIKLUS SELESAI / SUDAH DIKURAS)
        // =========================================================================
        $pengeluaran_arsip = DB::table('bop_kolam')
            ->join('siklus_kolam', 'bop_kolam.siklus_id', '=', 'siklus_kolam.id')
            ->join('master_kolam', 'siklus_kolam.kolam_id', '=', 'master_kolam.id')
            ->where('siklus_kolam.status', 'selesai')
            ->select(
                'bop_kolam.id',
                'bop_kolam.created_at as tanggal',
                'master_kolam.nama_kolam',
                'siklus_kolam.waktu_tabur',
                'siklus_kolam.waktu_kuras',
                DB::raw("'PENGELUARAN' as kategori_akun"),
                DB::raw("SUBSTRING_INDEX(bop_kolam.keterangan_biaya, ' - ', 1) as kategori_bop"),
                DB::raw("CASE WHEN bop_kolam.keterangan_biaya LIKE '% - %' THEN SUBSTRING_INDEX(bop_kolam.keterangan_biaya, ' - ', -1) ELSE bop_kolam.keterangan_biaya END as deskripsi"),
                'bop_kolam.nominal_biaya',
                DB::raw("'KELUAR' as jenis_arus"),
                'bop_kolam.siklus_id'
            );

        $pendapatan_arsip = DB::table('pesanan')
            ->join('detail_pesanan', 'pesanan.id', '=', 'detail_pesanan.pesanan_id')
            ->join('siklus_kolam', 'detail_pesanan.siklus_id', '=', 'siklus_kolam.id')
            ->join('master_kolam', 'siklus_kolam.kolam_id', '=', 'master_kolam.id')
            ->where('siklus_kolam.status', 'selesai')
            ->where('pesanan.status', 'selesai')
            ->select(
                'pesanan.id',
                'pesanan.waktu_pelunasan_final as tanggal',
                'master_kolam.nama_kolam',
                'siklus_kolam.waktu_tabur',
                'siklus_kolam.waktu_kuras',
                DB::raw("'PENDAPATAN' as kategori_akun"),
                DB::raw("'Omzet Jual Benur' as kategori_bop"),
                DB::raw("CONCAT('ARSIP OMZET INV ', pesanan.nomor_invoice) as deskripsi"),
                'pesanan.total_pembayaran_final as nominal_biaya',
                DB::raw("'MASUK' as jenis_arus"),
                'detail_pesanan.siklus_id'
            );

        $bop_arsip = $pengeluaran_arsip->unionAll($pendapatan_arsip)
            ->orderBy('waktu_kuras', 'desc')
            ->get();

        $kolam_aktif = DB::table('master_kolam')
            ->join('siklus_kolam', 'master_kolam.id', '=', 'siklus_kolam.kolam_id')
            ->where('siklus_kolam.status', 'aktif')
            ->select('master_kolam.nama_kolam', 'siklus_kolam.id as siklus_id', 'siklus_kolam.waktu_tabur')
            ->get();

        $totalPengeluaran = $bop_aktif->where('jenis_arus', 'KELUAR')->sum('nominal_biaya');
        $totalPendapatan = $bop_aktif->where('jenis_arus', 'MASUK')->sum('nominal_biaya');
        $saldoBersih = $totalPendapatan - $totalPengeluaran;

        return view('admin.biaya.index', compact('bop_aktif', 'bop_pending', 'bop_arsip', 'kolam_aktif', 'totalPengeluaran', 'totalPendapatan', 'saldoBersih'));
    }
}