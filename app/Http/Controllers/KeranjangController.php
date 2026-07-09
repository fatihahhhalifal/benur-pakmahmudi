<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class KeranjangController extends Controller
{
    public function index(): View
    {
        $items = DB::table('keranjang_sementara')
            ->join('master_kolam', 'keranjang_sementara.kolam_id', '=', 'master_kolam.id')
            ->join('siklus_kolam', function($join) {
                $join->on('master_kolam.id', '=', 'siklus_kolam.kolam_id')
                     ->where('siklus_kolam.status', '=', 'aktif');
            })
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
                'grade_benur.nama_grade as nama_grade',
                'siklus_kolam.waktu_tabur',
                'siklus_kolam.id as siklus_id',
                'master_harga.harga_jual'
            )
            ->where('keranjang_sementara.user_id', Auth::id())
            ->get()
            ->map(function ($cItem) {
                $cItem->harga_per_ekor = $cItem->harga_jual ?? 0;
                $cItem->total_kantong = ($cItem->jumlah_sak * 45) + $cItem->kantong_eceran;
                $cItem->total_ekor = $cItem->total_kantong * 1700;
                $cItem->subtotal_harga = $cItem->total_ekor * $cItem->harga_per_ekor;
                
                return $cItem;
            });

        $grandTotal = $items->sum('subtotal_harga');

        return view('customer.keranjang.index', compact('items', 'grandTotal'));
    }

    public function addToCart(Request $request): RedirectResponse
    {
        $request->validate([
            'kolam_id' => 'required|exists:master_kolam,id',
            'jumlah_sak' => 'required|integer|min:0',
            'kantong_eceran' => 'required|integer|min:0',
        ]);

        if ($request->jumlah_sak == 0 && $request->kantong_eceran == 0) {
            return redirect()->back()->with('error', 'Jumlah komponen kemasan tidak boleh kosong!');
        }

        $cekKeranjang = DB::table('keranjang_sementara')
            ->where('user_id', Auth::id())
            ->where('kolam_id', $request->kolam_id)
            ->first();

        if ($cekKeranjang) {
            DB::table('keranjang_sementara')
                ->where('id', $cekKeranjang->id)
                ->update([
                    'jumlah_sak' => $cekKeranjang->jumlah_sak + $request->jumlah_sak,
                    'kantong_eceran' => $cekKeranjang->kantong_eceran + $request->kantong_eceran,
                    'updated_at' => now()
                ]);
        } else {
            DB::table('keranjang_sementara')->insert([
                'user_id' => Auth::id(),
                'kolam_id' => $request->kolam_id,
                'jumlah_sak' => $request->jumlah_sak,
                'kantong_eceran' => $request->kantong_eceran,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        return redirect()->back()->with('success', 'Berhasil dimasukkan ke dalam draf keranjang!');
    }

    public function updateQuantity(Request $request): RedirectResponse
    {
        $request->validate([
            'id' => 'required',
            'jumlah_sak' => 'required|integer|min:0',
            'kantong_eceran' => 'required|integer|min:0',
        ]);

        DB::table('keranjang_sementara')
            ->where('id', $request->id)
            ->where('user_id', Auth::id())
            ->update([
                'jumlah_sak' => $request->jumlah_sak,
                'kantong_eceran' => $request->kantong_eceran,
                'updated_at' => now()
            ]);

        return redirect()->route('customer.keranjang')->with('success', 'Kuantitas draf berhasil diperbarui!');
    }

    public function destroy(int $id): RedirectResponse
    {
        DB::table('keranjang_sementara')
            ->where('id', $id)
            ->where('user_id', Auth::id())
            ->delete();

        return redirect()->back()->with('success', 'Item berhasil dikeluarkan dari keranjang.');
    }
}