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
        // Satu produk katalog dapat berasal dari beberapa kolam. Kolam hanya
        // dipakai sebagai sumber stok internal; customer berbelanja berdasarkan
        // jenis, ukuran, grade, dan harga.
        $katalog = DB::table('siklus_kolam')
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
                DB::raw('MIN(siklus_kolam.id) as siklus_id'),
                DB::raw('MIN(siklus_kolam.kolam_id) as kolam_id'),
                'jenis_benur.nama as nama_jenis',
                'ukuran_benur.ukuran as label_ukuran',
                'grade_benur.nama_grade as nama_grade',
                DB::raw('SUM(siklus_kolam.stok_tersedia) as stok_tersedia'),
                DB::raw('MIN(siklus_kolam.waktu_tabur) as waktu_tabur'),
                'master_harga.harga_jual as harga_saat_ini',
                'siklus_kolam.jenis_id',
                'siklus_kolam.ukuran_id',
                'siklus_kolam.grade_id'
            )
            ->groupBy(
                'siklus_kolam.jenis_id',
                'siklus_kolam.ukuran_id',
                'siklus_kolam.grade_id',
                'jenis_benur.nama',
                'ukuran_benur.ukuran',
                'grade_benur.nama_grade',
                'master_harga.harga_jual'
            )
            ->get();

        // Kuota pesanan yang masih berjalan harus dikurangi dari stok gabungan,
        // bukan hanya dari salah satu kolam perwakilan.
        $booking = DB::table('detail_pesanan')
            ->join('pesanan', 'detail_pesanan.pesanan_id', '=', 'pesanan.id')
            ->join('siklus_kolam', 'detail_pesanan.siklus_id', '=', 'siklus_kolam.id')
            ->whereIn('pesanan.status', ['pending', 'proses'])
            ->select(
                'siklus_kolam.jenis_id',
                'siklus_kolam.ukuran_id',
                'siklus_kolam.grade_id',
                DB::raw('SUM(detail_pesanan.total_kantong_hitung * COALESCE(detail_pesanan.konversi_per_kantong, 1700)) as total_booking')
            )
            ->groupBy('siklus_kolam.jenis_id', 'siklus_kolam.ukuran_id', 'siklus_kolam.grade_id')
            ->get()
            ->keyBy(fn ($item) => "{$item->jenis_id}:{$item->ukuran_id}:{$item->grade_id}");

        return $katalog
            ->map(function ($item) use ($booking) {
                $key = "{$item->jenis_id}:{$item->ukuran_id}:{$item->grade_id}";
                $item->stok_tersedia = max(0, $item->stok_tersedia - ($booking[$key]->total_booking ?? 0));
                $item->doc = (int) \Carbon\Carbon::parse($item->waktu_tabur)->startOfDay()->diffInDays(now()->startOfDay());
                
                if (!$item->harga_saat_ini) {
                    $item->harga_saat_ini = 0; 
                }

                $samplingTerbaru = DB::table('riwayat_sampling')
                    ->where('siklus_id', $item->siklus_id)
                    ->whereNotNull('path_foto')
                    ->orderBy('tanggal_sampling', 'desc')
                    ->first(['path_foto', 'tanggal_sampling']);

                $item->foto_terbaru = $samplingTerbaru->path_foto ?? null;
                $item->tgl_foto = $samplingTerbaru->tanggal_sampling ?? null;

                return $item;
            });
    }

    public function show(int $siklus_id): View
    {
        // URL detail membawa satu siklus perwakilan, tetapi produk yang dilihat
        // customer adalah SKU gabungan seluruh kolam aktif dengan jenis, ukuran,
        // dan grade yang sama.
        $siklusAcuan = DB::table('siklus_kolam')
            ->where('id', $siklus_id)
            ->where('status', 'aktif')
            ->first();

        if (!$siklusAcuan) {
            abort(404);
        }

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
            ->where('siklus_kolam.status', 'aktif')
            ->where('siklus_kolam.jenis_id', $siklusAcuan->jenis_id)
            ->where('siklus_kolam.ukuran_id', $siklusAcuan->ukuran_id)
            ->where('siklus_kolam.grade_id', $siklusAcuan->grade_id)
            ->select(
                'siklus_kolam.jenis_id',
                'siklus_kolam.ukuran_id',
                'siklus_kolam.grade_id',
                DB::raw('SUM(siklus_kolam.stok_tersedia) as stok_tersedia'),
                DB::raw('MIN(siklus_kolam.waktu_tabur) as waktu_tabur'),
                'jenis_benur.nama as nama_jenis',
                'jenis_benur.deskripsi as deskripsi_jenis',
                'ukuran_benur.ukuran as label_ukuran',
                'ukuran_benur.deskripsi as deskripsi_ukuran',
                'grade_benur.nama_grade as nama_grade',
                'grade_benur.deskripsi as deskripsi_grade',
                'master_harga.harga_jual as harga_saat_ini'
            )
            ->groupBy(
                'siklus_kolam.jenis_id',
                'siklus_kolam.ukuran_id',
                'siklus_kolam.grade_id',
                'jenis_benur.nama',
                'jenis_benur.deskripsi',
                'ukuran_benur.ukuran',
                'ukuran_benur.deskripsi',
                'grade_benur.nama_grade',
                'grade_benur.deskripsi',
                'master_harga.harga_jual'
            )
            ->first();

        if (!$produk) abort(404);

        // Tetap gunakan satu kolam/siklus perwakilan untuk form dan galeri;
        // pemecahan pesanan ke kolam-kolam lain dilakukan otomatis saat checkout.
        $produk->id = $siklusAcuan->id;
        $produk->kolam_id = $siklusAcuan->kolam_id;

        $totalBooking = DB::table('detail_pesanan')
            ->join('pesanan', 'detail_pesanan.pesanan_id', '=', 'pesanan.id')
            ->join('siklus_kolam', 'detail_pesanan.siklus_id', '=', 'siklus_kolam.id')
            ->whereIn('pesanan.status', ['pending', 'proses'])
            ->where('siklus_kolam.jenis_id', $produk->jenis_id)
            ->where('siklus_kolam.ukuran_id', $produk->ukuran_id)
            ->where('siklus_kolam.grade_id', $produk->grade_id)
            ->sum(DB::raw('detail_pesanan.total_kantong_hitung * COALESCE(detail_pesanan.konversi_per_kantong, 1700)'));

        $produk->stok_tersedia = max(0, $produk->stok_tersedia - $totalBooking);
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
                DB::raw('MIN(detail_pesanan.id) as id'),
                DB::raw('MIN(detail_pesanan.siklus_id) as siklus_id'),
                DB::raw('SUM(detail_pesanan.jumlah_sak_dipesan) as jumlah_sak_dipesan'),
                DB::raw('SUM(detail_pesanan.kantong_eceran_dipesan) as kantong_eceran_dipesan'),
                DB::raw('SUM(detail_pesanan.total_kantong_hitung) as total_kantong_hitung'),
                DB::raw('SUM(COALESCE(detail_pesanan.total_kantong_riil_muat, 0)) as total_kantong_riil_muat'),
                DB::raw('MIN(detail_pesanan.konversi_per_kantong) as konversi_per_kantong'),
                DB::raw('MIN(detail_pesanan.konversi_per_kantong_aktual) as konversi_per_kantong_aktual'),
                DB::raw('MIN(detail_pesanan.harga_per_ekor_kontrak) as harga_per_ekor_kontrak'),
                DB::raw('MIN(detail_pesanan.harga_per_ekor_aktual) as harga_per_ekor_aktual'),
                DB::raw('SUM(COALESCE(detail_pesanan.diskon_pembulatan_manual, 0)) as diskon_pembulatan_manual'),
                'siklus_kolam.jenis_id',
                'siklus_kolam.ukuran_id',
                'siklus_kolam.grade_id',
                'jenis_benur.nama as nama_jenis',
                'ukuran_benur.ukuran as label_ukuran',
                DB::raw('MAX(master_harga.harga_jual) as harga_live')
            )
            // Satu item customer = satu SKU. Bila alokasi internal tersebar
            // ke beberapa kolam, volume tetap tampil sebagai satu pesanan utuh.
            ->groupBy(
                'siklus_kolam.jenis_id',
                'siklus_kolam.ukuran_id',
                'siklus_kolam.grade_id',
                'jenis_benur.nama',
                'ukuran_benur.ukuran'
            )
            ->get()
            ->map(function($item) use ($pesanan) {
                $hargaAktif = $pesanan->is_harga_dikunci ? $item->harga_per_ekor_kontrak : ($item->harga_live ?? $item->harga_per_ekor_kontrak ?? 0);
                $item->subtotal_kotor = ($item->total_kantong_hitung * $item->konversi_per_kantong) * $hargaAktif;

                // FIX: total_kantong_hitung adalah SUM langsung dari seluruh baris split
                // per-kolam, jadi nilainya sudah pasti benar (dan ekor/rupiah ikut benar).
                // Tapi jumlah_sak_dipesan & kantong_eceran_dipesan sebelumnya di-SUM()
                // secara terpisah per baris (hasil intdiv/modulo masing-masing kolam),
                // sehingga bisa TIDAK konsisten bila sisa "kantong eceran" antar kolam
                // totalnya melebihi 45 (butuh carry ke sak, tapi SUM tidak melakukan itu).
                // Solusi: normalisasi ulang breakdown sak/eceran dari total_kantong_hitung
                // gabungan, bukan dari SUM masing-masing kolom hasil split.
                $item->jumlah_sak_dipesan     = intdiv($item->total_kantong_hitung, 45);
                $item->kantong_eceran_dipesan = $item->total_kantong_hitung % 45;

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