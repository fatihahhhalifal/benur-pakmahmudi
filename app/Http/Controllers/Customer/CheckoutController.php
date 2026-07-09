<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Carbon\Carbon;

class CheckoutController extends Controller
{
    public function initCheckout(Request $request): RedirectResponse
    {
        $cartItems = DB::table('keranjang_sementara')->where('user_id', Auth::id())->get();
        if ($cartItems->isEmpty()) {
            return redirect()->route('customer.keranjang')->with('error', 'Keranjang Anda kosong!');
        }
        return redirect()->route('customer.checkout.index');
    }

    public function index(): View
    {
        $userId = Auth::id();
        $items = DB::table('keranjang_sementara')
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
            ->select(
                'keranjang_sementara.*', 
                'master_kolam.nama_kolam', 
                'jenis_benur.nama as nama_jenis', 
                'ukuran_benur.ukuran as label_ukuran',
                'siklus_kolam.id as siklus_id',
                'siklus_kolam.waktu_tabur',
                'master_harga.harga_jual'
            )
            ->where('keranjang_sementara.user_id', $userId)
            ->where('siklus_kolam.status', 'aktif')
            ->get();

        if ($items->isEmpty()) {
            abort(404, 'Tidak ada item checkout valid.');
        }

        $grandTotal = 0;
        foreach ($items as $item) {
            $item->harga_per_ekor = $item->harga_jual ?? 0;
            $item->total_kantong = ($item->jumlah_sak * 45) + $item->kantong_eceran;
            $item->total_ekor = $item->total_kantong * 1700;
            $item->subtotal_harga = $item->total_ekor * $item->harga_per_ekor;
            $grandTotal += $item->subtotal_harga;
        }

        $profilTambak = DB::table('profil_tambak')->first();

        return view('customer.checkout.index', compact('items', 'grandTotal', 'profilTambak'));
    }

    /**
     * Membuat pesanan dari keranjang lalu langsung mengembalikan JSON (bukan redirect),
     * karena halaman checkout memanggil endpoint ini via fetch/AJAX dan butuh pesanan_id
     * untuk langsung meminta Snap Token Midtrans di halaman yang sama — tanpa reload/redirect.
     * Tidak ada lagi upload bukti transfer di form checkout — DP sepenuhnya dibayar via
     * Midtrans, jadi validasi bukti_transfer sudah tidak relevan lagi di titik ini.
     */
    public function processPayment(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $cartItems = DB::table('keranjang_sementara')->where('user_id', $userId)->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['error' => 'Keranjang kosong, tidak bisa checkout.'], 400);
        }

        return DB::transaction(function () use ($userId, $cartItems) {
            $invPrefix = 'INV-AQF-' . date('Ymd') . '-';
            $randomNumber = mt_rand(1000, 9999);
            $invoiceNumber = $invPrefix . $randomNumber;

            $pesananId = DB::table('pesanan')->insertGetId([
                'user_id' => $userId,
                'nomor_invoice' => $invoiceNumber,
                'status' => 'pending', 
                'nominal_dp_dibayar' => 0, 
                'is_harga_dikunci' => false,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            foreach ($cartItems as $item) {
                $siklus = DB::table('siklus_kolam')->where('kolam_id', $item->kolam_id)->where('status', 'aktif')->first();
                
                $hargaAcuan = DB::table('master_harga')
                    ->where('jenis_id', $siklus->jenis_id)
                    ->where('ukuran_id', $siklus->ukuran_id)
                    ->where('grade_id', $siklus->grade_id)
                    ->value('harga_jual') ?? 0;

                $totalKantongHitung = ($item->jumlah_sak * 45) + $item->kantong_eceran;

                DB::table('detail_pesanan')->insert([
                    'pesanan_id' => $pesananId,
                    'siklus_id' => $siklus->id,
                    'jumlah_sak_dipesan' => $item->jumlah_sak,
                    'kantong_eceran_dipesan' => $item->kantong_eceran,
                    'total_kantong_hitung' => $totalKantongHitung,
                    'konversi_per_kantong' => 1700, 
                    'harga_per_ekor_kontrak' => $hargaAcuan,
                    'harga_per_ekor_aktual' => $hargaAcuan, 
                    'subtotal_kotor' => ($totalKantongHitung * 1700) * $hargaAcuan,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            DB::table('keranjang_sementara')->where('user_id', $userId)->delete();

            return response()->json([
                'success'    => true,
                'pesanan_id' => $pesananId,
                'redirect'   => route('customer.pesanan.detail', $pesananId),
            ]);
        });
    }
}
