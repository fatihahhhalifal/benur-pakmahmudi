<?php

namespace App\Http\Controllers\SatuanPasar;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class KatalogController extends Controller
{
    public function index(): View
    {
        $userId = Auth::id();
        
        $total_pesanan = DB::table('pesanan')->where('user_id', $userId)->count();

        $stats = [
            'total_pesanan' => $total_pesanan,
        ];

        $katalog = $this->getKatalogData();

        return view('customer.katalog.index', compact('katalog', 'stats'));
    }

    public function adminPreview(): View
    {
        $stats = ['total_pesanan' => 0];
        $katalog = $this->getKatalogData();
        return view('customer.katalog.index', compact('katalog', 'stats'));
    }

    private function getKatalogData()
    {
        return DB::table('siklus_kolam')
            ->join('master_kolam', 'siklus_kolam.kolam_id', '=', 'master_kolam.id')
            ->leftJoin('jenis_benur', 'siklus_kolam.jenis_id', '=', 'jenis_benur.id')
            ->leftJoin('ukuran_benur', 'siklus_kolam.ukuran_id', '=', 'ukuran_benur.id')
            ->leftJoin('grade_benur', 'siklus_kolam.grade_id', '=', 'grade_benur.id')
            ->leftJoin('master_harga', function($join) {
                $join->on('siklus_kolam.jenis_id', '=', 'master_harga.jenis_id')
                     ->on('siklus_kolam.ukuran_id', '=', 'master_harga.ukuran_id')
                     ->on('siklus_kolam.grade_id', '=', 'master_harga.grade_id');
            })
            ->where('siklus_kolam.status', 'aktif')
            ->where('siklus_kolam.stok_tersedia', '>', 0)
            ->select(
                'siklus_kolam.id as siklus_id',
                'master_kolam.id as kolam_id',
                'master_kolam.nama_kolam',
                'jenis_benur.nama as nama_jenis',
                'ukuran_benur.ukuran as label_ukuran',
                'grade_benur.nama_grade as nama_grade',
                'siklus_kolam.stok_tersedia',
                'siklus_kolam.waktu_tabur',
                'master_harga.harga_jual as harga_saat_ini'
            )
            ->get()
            ->map(function ($item) {
                $item->doc = (int) \Carbon\Carbon::parse($item->waktu_tabur)->startOfDay()->diffInDays(now()->startOfDay());
                
                if (!$item->harga_saat_ini) {
                    $item->harga_saat_ini = 0; 
                }

                $item->path_foto = DB::table('riwayat_sampling')
                    ->where('siklus_id', $item->siklus_id)
                    ->whereNotNull('path_foto')
                    ->orderBy('tanggal_sampling', 'desc')
                    ->value('path_foto');

                return $item;
            });
    }

    public function show(int $siklus_id): View
    {
        $produk = DB::table('siklus_kolam')
            ->join('master_kolam', 'siklus_kolam.kolam_id', '=', 'master_kolam.id')
            ->leftJoin('jenis_benur', 'siklus_kolam.jenis_id', '=', 'jenis_benur.id')
            ->leftJoin('ukuran_benur', 'siklus_kolam.ukuran_id', '=', 'ukuran_benur.id')
            ->leftJoin('grade_benur', 'siklus_kolam.grade_id', '=', 'grade_benur.id')
            ->leftJoin('master_harga', function($join) {
                $join->on('siklus_kolam.jenis_id', '=', 'master_harga.jenis_id')
                     ->on('siklus_kolam.ukuran_id', '=', 'master_harga.ukuran_id')
                     ->on('siklus_kolam.grade_id', '=', 'master_harga.grade_id');
            })
            ->where('siklus_kolam.id', $siklus_id)
            ->select(
                'siklus_kolam.*', 
                'master_kolam.id as kolam_id', 
                'master_kolam.nama_kolam', 
                'jenis_benur.nama as nama_jenis', 
                'jenis_benur.deskripsi as deskripsi_jenis', 
                'ukuran_benur.ukuran as label_ukuran', 
                'ukuran_benur.deskripsi as deskripsi_ukuran', 
                'grade_benur.nama_grade as nama_grade',
                'grade_benur.deskripsi as deskripsi_grade',
                'master_harga.harga_jual as harga_saat_ini'
            )
            ->first();

        if (!$produk) abort(404);

        $produk->doc = (int) Carbon::parse($produk->waktu_tabur)->startOfDay()->diffInDays(now()->startOfDay());
        $produk->harga_saat_ini = $produk->harga_saat_ini ?? 0;

        $galeri = DB::table('riwayat_sampling')
            ->where('siklus_id', $siklus_id)
            ->orderBy('tanggal_sampling', 'desc')
            ->get();

        $cartItems = DB::table('keranjang_sementara')
            ->join('master_kolam', 'keranjang_sementara.kolam_id', '=', 'master_kolam.id')
            ->join('siklus_kolam', 'master_kolam.id', '=', 'siklus_kolam.kolam_id')
            ->leftJoin('jenis_benur', 'siklus_kolam.jenis_id', '=', 'jenis_benur.id')
            ->leftJoin('ukuran_benur', 'siklus_kolam.ukuran_id', '=', 'ukuran_benur.id')
            ->leftJoin('grade_benur', 'siklus_kolam.grade_id', '=', 'grade_benur.id')
            ->leftJoin('master_harga', function($join) {
                $join->on('siklus_kolam.jenis_id', '=', 'master_harga.jenis_id')
                     ->on('siklus_kolam.ukuran_id', '=', 'master_harga.ukuran_id')
                     ->on('siklus_kolam.grade_id', '=', 'master_harga.grade_id');
            })
            ->where('keranjang_sementara.user_id', Auth::id())
            ->where('siklus_kolam.status', 'aktif')
            ->select(
                'keranjang_sementara.*', 
                'master_kolam.nama_kolam', 
                'jenis_benur.nama as nama_jenis', 
                'ukuran_benur.ukuran as label_ukuran', 
                'siklus_kolam.waktu_tabur', 
                'siklus_kolam.id as siklus_id',
                'master_harga.harga_jual'
            )
            ->get()
            ->map(function ($cItem) {
                $cItem->harga_per_ekor = $cItem->harga_jual ?? 0;
                $cItem->total_kantong = ($cItem->jumlah_sak * 45) + $cItem->kantong_eceran;
                $cItem->total_ekor = $cItem->total_kantong * 1700;
                $cItem->subtotal_harga = $cItem->total_ekor * $cItem->harga_per_ekor;
                return $cItem;
            });
            
        $cartTotal = $cartItems->sum('subtotal_harga');

        return view('customer.katalog.show', compact('produk', 'galeri', 'cartItems', 'cartTotal'));
    }

    public function checkout(Request $request): RedirectResponse
    {
        $request->validate([
            'kolam_id' => 'required',
            'jumlah_sak' => 'required|integer|min:0',
            'kantong_eceran' => 'required|integer|min:0',
        ]);

        DB::table('keranjang_sementara')->where('user_id', Auth::id())->delete();
        DB::table('keranjang_sementara')->insert([
            'user_id' => Auth::id(),
            'kolam_id' => $request->kolam_id,
            'jumlah_sak' => $request->jumlah_sak,
            'kantong_eceran' => $request->kantong_eceran,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return redirect()->route('customer.checkout.index');
    }

    public function riwayatPesanan(): View
    {
        $pesanan = DB::table('pesanan')
            ->join('detail_pesanan', 'pesanan.id', '=', 'detail_pesanan.pesanan_id')
            ->join('siklus_kolam', 'detail_pesanan.siklus_id', '=', 'siklus_kolam.id')
            ->join('master_kolam', 'siklus_kolam.kolam_id', '=', 'master_kolam.id')
            ->leftJoin('jenis_benur', 'siklus_kolam.jenis_id', '=', 'jenis_benur.id')
            ->leftJoin('ukuran_benur', 'siklus_kolam.ukuran_id', '=', 'ukuran_benur.id')
            ->leftJoin('grade_benur', 'siklus_kolam.grade_id', '=', 'grade_benur.id')
            ->leftJoin('master_harga', function($join) {
                $join->on('siklus_kolam.jenis_id', '=', 'master_harga.jenis_id')
                     ->on('siklus_kolam.ukuran_id', '=', 'master_harga.ukuran_id')
                     ->on('siklus_kolam.grade_id', '=', 'master_harga.grade_id');
            })
            ->where('pesanan.user_id', Auth::id())
            ->select(
                'pesanan.*',
                'master_kolam.nama_kolam',
                'jenis_benur.nama as nama_jenis',
                'ukuran_benur.ukuran as label_ukuran',
                'grade_benur.nama_grade',
                'siklus_kolam.waktu_tabur',
                'siklus_kolam.id as siklus_id',
                'detail_pesanan.total_kantong_hitung',
                'detail_pesanan.total_kantong_riil_muat',
                'detail_pesanan.konversi_per_kantong',
                'detail_pesanan.harga_per_ekor_kontrak',
                'master_harga.harga_jual as harga_live'
            )
            ->orderBy('pesanan.created_at', 'desc')
            ->get()
            ->map(function($p) {
                $p->total_ekor = $p->total_kantong_hitung * $p->konversi_per_kantong;
                
                $hargaAktif = $p->is_harga_dikunci ? $p->harga_per_ekor_kontrak : ($p->harga_live ?? $p->harga_per_ekor_kontrak ?? 0);
                $p->subtotal_kotor = $p->total_ekor * $hargaAktif;
                
                $p->doc = (int) \Carbon\Carbon::parse($p->waktu_tabur)->startOfDay()->diffInDays(now()->startOfDay());
                $p->path_foto = DB::table('riwayat_sampling')
                    ->where('siklus_id', $p->siklus_id)
                    ->whereNotNull('path_foto')
                    ->orderBy('tanggal_sampling', 'desc')
                    ->value('path_foto');
                    
                return $p;
            });

        return view('customer.pesanan.index', compact('pesanan'));
    }

    public function detailPesanan(int $id): View
    {
        $pesanan = DB::table('pesanan')->where('id', $id)->where('user_id', Auth::id())->first();
        if (!$pesanan) abort(404);

        $items = DB::table('detail_pesanan')
            ->join('siklus_kolam', 'detail_pesanan.siklus_id', '=', 'siklus_kolam.id')
            ->join('master_kolam', 'siklus_kolam.kolam_id', '=', 'master_kolam.id')
            ->leftJoin('jenis_benur', 'siklus_kolam.jenis_id', '=', 'jenis_benur.id')
            ->leftJoin('ukuran_benur', 'siklus_kolam.ukuran_id', '=', 'ukuran_benur.id')
            ->leftJoin('grade_benur', 'siklus_kolam.grade_id', '=', 'grade_benur.id')
            ->leftJoin('master_harga', function($join) {
                $join->on('siklus_kolam.jenis_id', '=', 'master_harga.jenis_id')
                     ->on('siklus_kolam.ukuran_id', '=', 'master_harga.ukuran_id')
                     ->on('siklus_kolam.grade_id', '=', 'master_harga.grade_id');
            })
            ->where('detail_pesanan.pesanan_id', $id)
            ->select(
                'detail_pesanan.*', 
                'master_kolam.nama_kolam', 
                'jenis_benur.nama as nama_jenis', 
                'ukuran_benur.ukuran as label_ukuran',
                'master_harga.harga_jual as harga_live'
            )
            ->get()
            ->map(function($item) use ($pesanan) {
                $hargaAktif = $pesanan->is_harga_dikunci ? $item->harga_per_ekor_kontrak : ($item->harga_live ?? $item->harga_per_ekor_kontrak ?? 0);
                $item->subtotal_kotor = ($item->total_kantong_hitung * $item->konversi_per_kantong) * $hargaAktif;
                return $item;
            });
            
        $grandTotal = $items->sum('subtotal_kotor');
        $profilTambak = DB::table('profil_tambak')->first();

        // REVISI 3 & 6: Ambil riwayat sampling untuk setiap siklus dalam pesanan
        $riwayatSampling = collect();
        foreach ($items as $item) {
            $samplings = DB::table('riwayat_sampling')
                ->join('grade_benur', 'riwayat_sampling.grade_id', '=', 'grade_benur.id')
                ->where('riwayat_sampling.siklus_id', $item->siklus_id)
                ->select('riwayat_sampling.*', 'grade_benur.nama_grade')
                ->orderBy('tanggal_sampling', 'desc')
                ->get();
            $riwayatSampling = $riwayatSampling->merge($samplings);
        }
        // Sampling terbaru (untuk notifikasi perubahan)
        $samplingTerbaru = $riwayatSampling->sortByDesc('tanggal_sampling')->first();

        return view('customer.pesanan.show', compact('pesanan', 'items', 'grandTotal', 'profilTambak', 'riwayatSampling', 'samplingTerbaru'));
    }

    public function batalkanOlehCustomer(Request $request, int $id): RedirectResponse
    {
        DB::table('pesanan')
            ->where('id', $id)
            ->where('user_id', Auth::id())
            ->where('status', 'pending')
            ->update([
                'status' => 'batal',
                'keterangan_batal' => 'Dibatalkan oleh Pembeli',
                'updated_at' => now()
            ]);
        
        return redirect()->back()->with('success', 'Pesanan berhasil dibatalkan.');
    }
}