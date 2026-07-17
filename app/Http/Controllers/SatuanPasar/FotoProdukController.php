<?php

namespace App\Http\Controllers\SatuanPasar;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class FotoProdukController extends Controller
{
    /**
     * Upload foto produk per kategori
     */
    public function upload(Request $request, int $siklusId)
    {
        $request->validate([
            'foto'       => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
            'kategori'   => 'required|in:skala_besar,skala_kecil,ukuran',
            'keterangan' => 'nullable|string|max:200',
        ], [
            'foto.required' => 'Pilih file foto terlebih dahulu.',
            'foto.image'    => 'File harus berupa gambar.',
            'foto.max'      => 'Ukuran foto maksimal 5MB.',
            'kategori.in'   => 'Kategori foto tidak valid.',
        ]);

        $siklus = DB::table('siklus_kolam')->where('id', $siklusId)->first();
        if (!$siklus) abort(404);

        // Hapus foto lama dengan kategori yang sama (1 kategori = 1 foto)
        $fotoLama = DB::table('foto_produk_siklus')
            ->where('siklus_id', $siklusId)
            ->where('kategori', $request->kategori)
            ->value('path_foto');

        if ($fotoLama) {
            Storage::disk('public')->delete($fotoLama);
        }

        DB::table('foto_produk_siklus')
            ->where('siklus_id', $siklusId)
            ->where('kategori', $request->kategori)
            ->delete();

        // Upload foto baru
        $path = $request->file('foto')->store('foto_produk', 'public');

        DB::table('foto_produk_siklus')->insert([
            'siklus_id'   => $siklusId,
            'kategori'    => $request->kategori,
            'path_foto'   => $path,
            'keterangan'  => $request->keterangan,
            'uploaded_by' => Auth::id(),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        return redirect()->back()->with('success', 'Foto berhasil diupload.');
    }

    /**
     * Hapus foto produk
     */
    public function hapus(int $id)
    {
        $foto = DB::table('foto_produk_siklus')->where('id', $id)->first();
        if (!$foto) abort(404);

        Storage::disk('public')->delete($foto->path_foto);
        DB::table('foto_produk_siklus')->where('id', $id)->delete();

        return redirect()->back()->with('success', 'Foto berhasil dihapus.');
    }
}