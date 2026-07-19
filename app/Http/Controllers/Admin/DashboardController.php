<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (in_array($user->role, ['admin', 'pemilik', 'operator'])) {

            $totalKolam  = DB::table('master_kolam')->count();
            $kolamAktif  = DB::table('siklus_kolam')->where('status', 'aktif')->count();
            $totalBenurLive = DB::table('siklus_kolam')->where('status', 'aktif')->sum('stok_tersedia') ?? 0;

            $totalModalBenih  = DB::table('siklus_kolam')->where('status', 'aktif')->sum('modal_awal_rupiah') ?? 0;
            $totalBopBerjalan = DB::table('bop_kolam')
                ->join('siklus_kolam', 'bop_kolam.siklus_id', '=', 'siklus_kolam.id')
                ->where('siklus_kolam.status', 'aktif')
                ->where('bop_kolam.status', 'disetujui')
                ->sum('bop_kolam.nominal_biaya') ?? 0;
            $totalPengeluaran = $totalModalBenih + $totalBopBerjalan;

            $totalPendapatan = DB::table('pesanan')
                ->whereIn('status', ['proses','menunggu_kalkulasi','menunggu_pelunasan','siap_ambil','selesai'])
                ->sum(DB::raw("CASE WHEN status='selesai' THEN total_pembayaran_final ELSE nominal_dp_dibayar END")) ?? 0;

            $antreanPreorder = DB::table('pesanan')
                ->whereIn('status', ['pending','menunggu_konfirmasi_dp'])
                ->count();

            $pesananDiproses = DB::table('pesanan')
                ->whereIn('status', ['proses','menunggu_kalkulasi','menunggu_pelunasan','siap_ambil'])
                ->count();

            $totalOrderValid = DB::table('pesanan')
                ->whereNotIn('status', ['batal'])
                ->count();

            $pesananSelesaiBulanIni = DB::table('pesanan')
                ->where('status', 'selesai')
                ->whereMonth('updated_at', now()->month)
                ->whereYear('updated_at', now()->year)
                ->count();

            $statsByStatus = DB::table('pesanan')
                ->selectRaw("status, COUNT(*) as jumlah")
                ->whereNotIn('status', ['batal'])
                ->groupBy('status')
                ->pluck('jumlah', 'status');

            $recentLogs = DB::table('bop_kolam')
                ->join('siklus_kolam', 'bop_kolam.siklus_id', '=', 'siklus_kolam.id')
                ->join('master_kolam', 'siklus_kolam.kolam_id', '=', 'master_kolam.id')
                ->select('bop_kolam.*', 'master_kolam.nama_kolam')
                ->orderBy('bop_kolam.created_at', 'desc')
                ->limit(5)
                ->get();

            return view('dashboard', compact(
                'totalKolam','kolamAktif','totalBenurLive',
                'totalPengeluaran','totalPendapatan',
                'antreanPreorder','pesananDiproses','recentLogs',
                'pesananSelesaiBulanIni','totalOrderValid','statsByStatus'
            ));

        } else {
            $myPreordersCount = DB::table('pesanan')
                ->where('user_id', $user->id)
                ->whereIn('status', ['pending','menunggu_konfirmasi_dp'])
                ->count();

            $myActiveOrdersCount = DB::table('pesanan')
                ->where('user_id', $user->id)
                ->whereIn('status', ['proses','menunggu_kalkulasi','menunggu_pelunasan','siap_ambil'])
                ->count();

            $mySelesaiCount = DB::table('pesanan')
                ->where('user_id', $user->id)
                ->where('status', 'selesai')
                ->count();

            $myTotalInvoiced = DB::table('pesanan')
                ->where('user_id', $user->id)
                ->where('status', 'selesai')
                ->sum('total_pembayaran_final') ?? 0;

            $myTotalOrder = DB::table('pesanan')
                ->where('user_id', $user->id)
                ->whereNotIn('status', ['batal'])
                ->count();

            $myNeedActionCount = DB::table('pesanan')
                ->where('user_id', $user->id)
                ->where('status', 'menunggu_pelunasan')
                ->count();

            $myRecentOrders = DB::table('pesanan')
                ->join('detail_pesanan', 'pesanan.id', '=', 'detail_pesanan.pesanan_id')
                ->join('siklus_kolam', 'detail_pesanan.siklus_id', '=', 'siklus_kolam.id')
                ->join('master_kolam', 'siklus_kolam.kolam_id', '=', 'master_kolam.id')
                ->join('jenis_benur', 'siklus_kolam.jenis_id', '=', 'jenis_benur.id')
                ->where('pesanan.user_id', $user->id)
                ->whereNotIn('pesanan.status', ['batal'])
                ->select(
                    'pesanan.*',
                    'detail_pesanan.jumlah_sak_dipesan',
                    'detail_pesanan.kantong_eceran_dipesan',
                    'detail_pesanan.total_kantong_hitung',
                    'detail_pesanan.total_kantong_riil_muat',
                    'detail_pesanan.konversi_per_kantong',
                    'detail_pesanan.harga_per_ekor_kontrak',
                    'master_kolam.nama_kolam',
                    'jenis_benur.nama as nama_jenis'
                )
                ->orderBy('pesanan.created_at', 'desc')
                ->limit(5)
                ->get();

            $myStatsByStatus = DB::table('pesanan')
                ->where('user_id', $user->id)
                ->whereNotIn('status', ['batal'])
                ->selectRaw("status, COUNT(*) as jumlah")
                ->groupBy('status')
                ->pluck('jumlah', 'status');

            return view('dashboard', compact(
                'myPreordersCount','myActiveOrdersCount','mySelesaiCount',
                'myTotalInvoiced','myTotalOrder','myNeedActionCount',
                'myRecentOrders','myStatsByStatus'
            ));
        }
    }
}
