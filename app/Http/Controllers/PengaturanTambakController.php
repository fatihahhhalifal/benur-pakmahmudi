<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PengaturanTambakController extends Controller
{
    /**
     * Tampilan Utama: Menangani 3 Tab Data (Jalur Folder FIXED: admin/pengaturan/index)
     */
    public function index()
    {
        if (!in_array(Auth::user()->role, ['admin', 'pemilik'])) {
            abort(403, 'Akses Ditolak.');
        }

        // KOREKSI JALUR VIEW: Diarahkan ke folder 'admin.pengaturan.index' sesuai direktori Anda
        return view('admin.pengaturan.index', [
            'profil' => DB::table('profil_tambak')->first(),
            'jenis'  => DB::table('jenis_benur')->get(),
            'ukuran' => DB::table('ukuran_benur')->get(),
            'grade'  => DB::table('grade_benur')->get(),
            'harga'  => DB::table('master_harga')
                ->join('jenis_benur', 'master_harga.jenis_id', '=', 'jenis_benur.id')
                ->join('ukuran_benur', 'master_harga.ukuran_id', '=', 'ukuran_benur.id')
                ->join('grade_benur', 'master_harga.grade_id', '=', 'grade_benur.id')
                ->select('master_harga.*', 'jenis_benur.nama as nama_jenis', 'ukuran_benur.ukuran', 'grade_benur.nama_grade')
                ->latest()->get()
        ]);
    }

    /**
     * PROFIL & REKENING (Tab 1) - Sinkron 100% Kolam Database Riil Anda
     */
    public function saveProfil(Request $request)
    {
        if (Auth::user()->role !== 'admin') abort(403);

        $validated = $request->validate([
            'nama_tambak'    => 'required|string|max:255',
            'npwp_nib'       => 'nullable|string|max:255', // Menyediakan validasi kolom sesuai skema database riil Anda
            'nomor_whatsapp' => 'required|string|max:50',
            'email'          => 'required|email|max:255',
            'alamat'         => 'required|string',
            'nama_bank'      => 'required|string|max:100',
            'nomor_rekening' => 'required|string|max:100',
            'atas_nama'      => 'required|string|max:255',
            'nominal_dp'     => 'required|integer|min:0|max:100',
        ]);

        $validated['updated_at'] = now();
        $profil = DB::table('profil_tambak')->first();

        if ($profil) {
            DB::table('profil_tambak')->where('id', $profil->id)->update($validated);
        } else {
            $validated['created_at'] = now();
            DB::table('profil_tambak')->insert($validated);
        }

        return redirect()->back()->with('success', 'Profil operasional tambak berhasil diperbarui.')->with('last_tab', 'profil');
    }

    /**
     * KRITERIA BENUR (Tab 2) - PROTEKSI INTEGRITAS DATA
     */
    private function cekRelasi(string $tabel, int $id): bool
    {
        return DB::table('master_harga')->where($tabel, $id)->exists() ||
               DB::table('siklus_kolam')->where($tabel, $id)->exists();
    }

    public function storeJenis(Request $request)
    {
        $request->validate(['nama' => 'required|unique:jenis_benur,nama']);
        DB::table('jenis_benur')->insert(['nama' => $request->nama, 'kode' => $request->kode, 'deskripsi' => $request->deskripsi, 'created_at' => now(), 'updated_at' => now()]);
        return redirect()->back()->with('success', 'Kriteria varietas jenis benur baru berhasil ditambahkan.')->with(['last_tab' => 'kriteria', 'last_sub_tab' => 'jenis']);
    }

    public function updateJenis(Request $request, int $id)
    {
        $request->validate(['nama' => 'required|unique:jenis_benur,nama,'.$id]);
        DB::table('jenis_benur')->where('id', $id)->update(['nama' => $request->nama, 'kode' => $request->kode, 'deskripsi' => $request->deskripsi, 'updated_at' => now()]);
        return redirect()->back()->with('success', 'Kriteria varietas jenis benur berhasil diperbarui.')->with(['last_tab' => 'kriteria', 'last_sub_tab' => 'jenis']);
    }

    public function destroyJenis(int $id)
    {
        if ($this->cekRelasi('jenis_id', $id)) return redirect()->back()->withErrors(['gagal' => 'Data sedang digunakan di Harga/Siklus hulu tambak.']);
        DB::table('jenis_benur')->where('id', $id)->delete();
        return redirect()->back()->with('success', 'Kriteria jenis benur berhasil dihapus.')->with(['last_tab' => 'kriteria', 'last_sub_tab' => 'jenis']);
    }

    /**
     * REVISI: storeUkuran sekarang menerima upload opsional 'foto_skala' —
     * foto referensi ukuran (mis. PL berdampingan dengan penggaris/koin) yang
     * berlaku untuk SEMUA kolam/siklus dengan ukuran ini (bukan per-siklus).
     */
    public function storeUkuran(Request $request)
    {
        $request->validate([
            'ukuran'     => 'required|unique:ukuran_benur,ukuran',
            'foto_skala' => 'nullable|image|mimes:jpeg,png,jpg|max:3072',
        ]);

        $pathFotoSkala = null;
        if ($request->hasFile('foto_skala')) {
            $pathFotoSkala = $request->file('foto_skala')->store('skala_ukuran', 'public');
        }

        DB::table('ukuran_benur')->insert([
            'ukuran'     => $request->ukuran,
            'kode'       => $request->kode,
            'deskripsi'  => $request->deskripsi,
            'foto_skala' => $pathFotoSkala,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return redirect()->back()->with('success', 'Kategori ukuran benur baru berhasil ditambahkan.')->with(['last_tab' => 'kriteria', 'last_sub_tab' => 'ukuran']);
    }

    /**
     * REVISI: updateUkuran mendukung ganti foto skala. Foto lama dihapus dari
     * storage saat diganti agar tidak menumpuk file yatim (orphan).
     */
    public function updateUkuran(Request $request, int $id)
    {
        $request->validate([
            'ukuran'     => 'required|unique:ukuran_benur,ukuran,'.$id,
            'foto_skala' => 'nullable|image|mimes:jpeg,png,jpg|max:3072',
        ]);

        $ukuran = DB::table('ukuran_benur')->where('id', $id)->first();
        if (!$ukuran) return redirect()->back()->withErrors(['gagal' => 'Kategori ukuran tidak ditemukan.']);

        $pathFotoSkala = $ukuran->foto_skala;
        if ($request->hasFile('foto_skala')) {
            if ($pathFotoSkala) {
                Storage::disk('public')->delete($pathFotoSkala);
            }
            $pathFotoSkala = $request->file('foto_skala')->store('skala_ukuran', 'public');
        }

        DB::table('ukuran_benur')->where('id', $id)->update([
            'ukuran'     => $request->ukuran,
            'kode'       => $request->kode,
            'deskripsi'  => $request->deskripsi,
            'foto_skala' => $pathFotoSkala,
            'updated_at' => now(),
        ]);
        return redirect()->back()->with('success', 'Kategori ukuran benur berhasil diperbarui.')->with(['last_tab' => 'kriteria', 'last_sub_tab' => 'ukuran']);
    }

    /**
     * REVISI: hapus juga file foto skala dari storage saat kategori ukuran dihapus,
     * supaya tidak menyisakan file yatim di disk.
     */
    public function destroyUkuran(int $id)
    {
        if ($this->cekRelasi('ukuran_id', $id)) return redirect()->back()->withErrors(['gagal' => 'Data ukuran sedang digunakan pada matriks aktif hulu.']);

        $ukuran = DB::table('ukuran_benur')->where('id', $id)->first();
        if ($ukuran && $ukuran->foto_skala) {
            Storage::disk('public')->delete($ukuran->foto_skala);
        }

        DB::table('ukuran_benur')->where('id', $id)->delete();
        return redirect()->back()->with('success', 'Kategori ukuran benur berhasil dihapus.')->with(['last_tab' => 'kriteria', 'last_sub_tab' => 'ukuran']);
    }

    /**
     * Hapus khusus foto skala tanpa menghapus kategori ukurannya (dipakai
     * tombol "Hapus Foto" di form edit ukuran, opsional untuk admin).
     */
    public function destroyFotoSkalaUkuran(int $id)
    {
        $ukuran = DB::table('ukuran_benur')->where('id', $id)->first();
        if (!$ukuran) return redirect()->back()->withErrors(['gagal' => 'Kategori ukuran tidak ditemukan.']);

        if ($ukuran->foto_skala) {
            Storage::disk('public')->delete($ukuran->foto_skala);
            DB::table('ukuran_benur')->where('id', $id)->update(['foto_skala' => null, 'updated_at' => now()]);
        }

        return redirect()->back()->with('success', 'Foto skala ukuran berhasil dihapus.')->with(['last_tab' => 'kriteria', 'last_sub_tab' => 'ukuran']);
    }

    public function storeGrade(Request $request)
    {
        $request->validate(['nama_grade' => 'required|unique:grade_benur,nama_grade']);
        DB::table('grade_benur')->insert(['nama_grade' => $request->nama_grade, 'kode' => $request->kode, 'deskripsi' => $request->deskripsi, 'created_at' => now(), 'updated_at' => now()]);
        return redirect()->back()->with('success', 'Tingkatan grade benur baru berhasil didaftarkan.')->with(['last_tab' => 'kriteria', 'last_sub_tab' => 'grade']);
    }

    public function updateGrade(Request $request, int $id)
    {
        $request->validate(['nama_grade' => 'required|unique:grade_benur,nama_grade,'.$id]);
        DB::table('grade_benur')->where('id', $id)->update(['nama_grade' => $request->nama_grade, 'kode' => $request->kode, 'deskripsi' => $request->deskripsi, 'updated_at' => now()]);
        return redirect()->back()->with('success', 'Tingkatan grade benur berhasil diperbarui.')->with(['last_tab' => 'kriteria', 'last_sub_tab' => 'grade']);
    }

    public function destroyGrade(int $id)
    {
        if ($this->cekRelasi('grade_id', $id)) return redirect()->back()->withErrors(['gagal' => 'Data grade sedang digunakan dalam sistem operasional.']);
        DB::table('grade_benur')->where('id', $id)->delete();
        return redirect()->back()->with('success', 'Tingkatan grade benur berhasil dihapus.')->with(['last_tab' => 'kriteria', 'last_sub_tab' => 'grade']);
    }

    /**
     * KONTRAK HARGA (Tab 3) - HARGA ACUAN TETAP PASAR HILIR
     */
    public function storeHarga(Request $request)
    {
        $request->validate(['jenis_id' => 'required', 'ukuran_id' => 'required', 'grade_id' => 'required', 'harga_jual' => 'required|integer']);

        if (DB::table('master_harga')->where(['jenis_id' => $request->jenis_id, 'ukuran_id' => $request->ukuran_id, 'grade_id' => $request->grade_id])->exists()) {
            return redirect()->back()->withErrors(['gagal' => 'Kombinasi matriks harga komoditas tersebut sudah ada!'])->with('last_tab', 'harga');
        }

        DB::table('master_harga')->insert([
            'jenis_id' => $request->jenis_id, 'ukuran_id' => $request->ukuran_id, 'grade_id' => $request->grade_id,
            'harga_jual' => $request->harga_jual, 'created_at' => now(), 'updated_at' => now()
        ]);
        return redirect()->back()->with('success', 'Kontrak harga jual acuan pasar berhasil diterbitkan.')->with('last_tab', 'harga');
    }

    public function updateHarga(Request $request, int $id)
    {
        $request->validate(['harga_jual' => 'required|integer']);

        DB::table('master_harga')->where('id', $id)->update([
            'harga_jual' => $request->harga_jual,
            'updated_at' => now()
        ]);
        return redirect()->back()->with('success', 'Kontrak nominal acuan harga jual berhasil diperbarui.')->with('last_tab', 'harga');
    }

    public function destroyHarga(int $id)
    {
        DB::table('master_harga')->where('id', $id)->delete();
        return redirect()->back()->with('success', 'Kontrak acuan harga pasar berhasil dihapus dari sistem.')->with('last_tab', 'harga');
    }
}