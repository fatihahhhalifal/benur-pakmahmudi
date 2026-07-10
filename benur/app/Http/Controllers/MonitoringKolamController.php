<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MonitoringKolamController extends Controller
{
    /**
     * TAMPILAN UTAMA MENU MONITORING STOK KOLAM
     */
    public function index(): View
    {
        if (!in_array(Auth::user()->role, ['admin', 'pemilik', 'operator'])) {
            abort(403);
        }

        $kolam = DB::table('master_kolam')
            ->leftJoin('siklus_kolam', function($join) {
                $join->on('master_kolam.id', '=', 'siklus_kolam.kolam_id')
                     ->where('siklus_kolam.status', '=', 'aktif');
            })
            ->leftJoin('jenis_benur', 'siklus_kolam.jenis_id', '=', 'jenis_benur.id')
            ->leftJoin('ukuran_benur', 'siklus_kolam.ukuran_id', '=', 'ukuran_benur.id')
            ->leftJoin('grade_benur', 'siklus_kolam.grade_id', '=', 'grade_benur.id')
            ->select(
                'master_kolam.*',
                'siklus_kolam.id as siklus_id',
                'siklus_kolam.jenis_id',
                'siklus_kolam.ukuran_id',
                'siklus_kolam.grade_id',
                'siklus_kolam.jumlah_tebar_awal',
                'siklus_kolam.stok_tersedia',
                'siklus_kolam.modal_awal_rupiah',
                'siklus_kolam.waktu_tabur',
                'siklus_kolam.potongan_harga_manual',
                'jenis_benur.nama as nama_jenis',
                'ukuran_benur.ukuran as label_ukuran',
                'grade_benur.nama_grade as nama_grade'
            )
            ->get()
            ->map(function ($item) {
                if ($item->siklus_id) {
                    $item->doc = (int) Carbon::parse($item->waktu_tabur)->startOfDay()->diffInDays(now()->startOfDay());

                    $bookedOrders = DB::table('detail_pesanan')
                        ->join('pesanan', 'detail_pesanan.pesanan_id', '=', 'pesanan.id')
                        ->where('detail_pesanan.siklus_id', $item->siklus_id)
                        ->whereIn('pesanan.status', ['pending', 'menunggu_konfirmasi_dp', 'proses', 'siap_ambil'])
                        ->select('detail_pesanan.total_kantong_hitung', 'detail_pesanan.konversi_per_kantong')
                        ->get();

                    $totalBookedEkor = 0;
                    foreach ($bookedOrders as $bo) {
                        $totalBookedEkor += ($bo->total_kantong_hitung * $bo->konversi_per_kantong);
                    }

                    $item->total_booked_ekor = $totalBookedEkor;
                    $item->sisa_kuota_bebas = $item->stok_tersedia - $totalBookedEkor;

                    // FIX PENTING: Hanya jumlahkan BOP yang statusnya SUDAH DISETUJUI ADMIN
                    $item->total_bop_keluar = DB::table('bop_kolam')
                        ->where('siklus_id', $item->siklus_id)
                        ->where('status', 'disetujui') 
                        ->sum('nominal_biaya') ?? 0;
                } else {
                    $item->doc = 0;
                    $item->total_booked_ekor = 0;
                    $item->sisa_kuota_bebas = 0;
                    $item->total_bop_keluar = 0;
                }
                return $item;
            });

        // FIX PENTING: Saat menarik log daftar BOP untuk pop-up modal Kelola BOP di monitoring kolam, 
        // hanya tampilkan log yang sudah 'disetujui' agar tabel tidak tercampur data gantung.
        $bop_list = DB::table('bop_kolam')
            ->where('status', 'disetujui')
            ->get();

        $sampling_list = DB::table('riwayat_sampling')
            ->join('grade_benur', 'riwayat_sampling.grade_id', '=', 'grade_benur.id')
            ->select('riwayat_sampling.*', 'grade_benur.nama_grade')
            ->orderBy('riwayat_sampling.tanggal_sampling', 'desc')
            ->get();

        $all_bookings = DB::table('detail_pesanan')
            ->join('pesanan', 'detail_pesanan.pesanan_id', '=', 'pesanan.id')
            ->join('users', 'pesanan.user_id', '=', 'users.id')
            ->whereIn('pesanan.status', ['pending', 'menunggu_konfirmasi_dp', 'proses', 'siap_ambil'])
            ->select('detail_pesanan.*', 'pesanan.nomor_invoice', 'pesanan.status as order_status', 'users.name as cust_name')
            ->get()
            ->map(function ($b) {
                $b->total_ekor_riil = $b->total_kantong_hitung * $b->konversi_per_kantong;
                return $b;
            });

        $list_jenis = DB::table('jenis_benur')->get();
        $list_ukuran = DB::table('ukuran_benur')->get();
        $list_grade = DB::table('grade_benur')->get();

        return view('admin.kolam.index', compact('kolam', 'bop_list', 'sampling_list', 'all_bookings', 'list_jenis', 'list_ukuran', 'list_grade'));
    }

    /**
     * CRUD MASTER FISIK KOLAM
     */
    public function storeKolam(Request $request): RedirectResponse
    {
        $request->validate([
            'nama_kolam' => 'required|unique:master_kolam,nama_kolam',
            'kapasitas'  => 'required|numeric|min:1',
        ], [
            'nama_kolam.unique'   => 'Nama kolam sudah terdaftar, gunakan nama lain.',
            'nama_kolam.required' => 'Nama kolam wajib diisi.',
            'kapasitas.required'  => 'Kapasitas maksimal wajib diisi.',
            'kapasitas.numeric'   => 'Kapasitas harus berupa angka.',
            'kapasitas.min'       => 'Kapasitas minimal 1.',
        ]);
        DB::table('master_kolam')->insert([
            'nama_kolam' => $request->nama_kolam,
            'kapasitas_maksimal' => $request->kapasitas,
            'created_at' => now(), 'updated_at' => now()
        ]);
        return redirect()->back()->with('success', 'Master infrastruktur fisik kolam baru berhasil didaftarkan.');
    }

    public function updateKolam(Request $request, int $id): RedirectResponse
    {
        DB::table('master_kolam')->where('id', $id)->update([
            'nama_kolam' => $request->nama_kolam,
            'kapasitas_maksimal' => $request->kapasitas,
            'updated_at' => now()
        ]);
        return redirect()->back()->with('success', 'Infrastruktur fisik kolam berhasil diperbarui.');
    }

    public function destroyKolam(int $id): RedirectResponse
    {
        $adaSiklusAktif = DB::table('siklus_kolam')->where('kolam_id', $id)->where('status', 'aktif')->exists();
        if ($adaSiklusAktif) {
            return redirect()->back()->withErrors(['gagal' => 'Kolam tidak boleh dihapus karena masih memiliki Siklus Aktif berjalan!']);
        }
        DB::table('master_kolam')->where('id', $id)->delete();
        return redirect()->back()->with('success', 'Master fisik kolam berhasil dihapus dari sistem.');
    }

    /**
     * OPERASIONAL SIKLUS (TEBAR BARU)
     */
    public function storeTebar(Request $request): RedirectResponse
    {
        $masterKolam = DB::table('master_kolam')->where('id', $request->kolam_id)->first();
        if (!$masterKolam) return redirect()->back()->withErrors(['gagal' => 'Data hulu tidak ditemukan.']);

        if ($request->jumlah > $masterKolam->kapasitas_maksimal) {
            return redirect()->back()->withErrors([
                'gagal' => 'Kuantitas penaburan melebihi daya tampung maksimal kolam!'
            ]);
        }

        DB::table('siklus_kolam')->insert([
            'kolam_id' => $request->kolam_id, 'jenis_id' => $request->jenis_id, 'ukuran_id' => $request->ukuran_id, 'grade_id' => $request->grade_id,
            'modal_awal_rupiah' => $request->modal, 'jumlah_tebar_awal' => $request->jumlah, 'stok_tersedia' => $request->jumlah,
            'waktu_tabur' => $request->waktu_tabur, 'status' => 'aktif', 'created_at' => now(), 'updated_at' => now()
        ]);
        return redirect()->back()->with('success', 'Proses penaburan telur angkatan baru berhasil dicatat.');
    }

    public function siklusUpdate(Request $request, int $id): RedirectResponse
    {
        $siklus = DB::table('siklus_kolam')->where('id', $id)->first();
        if ($siklus && $siklus->status === 'selesai') {
            return redirect()->back()->withErrors(['gagal' => 'Aksi ditolak! Parameter siklus selesai telah dikunci.']);
        }

        DB::table('siklus_kolam')->where('id', $id)->update([
            'jenis_id' => $request->jenis_id, 'ukuran_id' => $request->ukuran_id, 'grade_id' => $request->grade_id,
            'modal_awal_rupiah' => $request->modal, 'jumlah_tebar_awal' => $request->jumlah, 'stok_tersedia' => $request->jumlah, 
            'waktu_tabur' => $request->waktu_tabur, 'updated_at' => now()
        ]);
        return redirect()->back()->with('success', 'Parameter penaburan siklus kolam berhasil diperbarui.');
    }

    /**
     * MANAJEMEN FINANSIAL BOP FIELD
     */
    public function storeBOP(Request $request): RedirectResponse
    {
        $request->validate(['siklus_id' => 'required', 'keterangan' => 'required', 'nominal' => 'required|numeric']);
        $keteranganFinal = $request->keterangan . ' - ' . $request->keterangan_lain;
        
        // Logika Aturan Otorisasi
        $statusAwal = (Auth::user()->role === 'operator') ? 'pending' : 'disetujui';

        if ($request->siklus_id === 'global') {
            $siklusAktifList = DB::table('siklus_kolam')->where('status', 'aktif')->get();
            if ($siklusAktifList->count() == 0) return redirect()->back()->withErrors(['gagal' => 'Tidak ada kolam aktif.']);
            
            $nominalPerKolam = (int) ($request->nominal / $siklusAktifList->count());
            foreach ($siklusAktifList as $sk) {
                DB::table('bop_kolam')->insert([
                    'siklus_id' => $sk->id, 'keterangan_biaya' => '(Global) '.$keteranganFinal, 'nominal_biaya' => $nominalPerKolam,
                    'status' => $statusAwal, 'waktu_pencatatan' => now(), 'created_at' => now(), 'updated_at' => now()
                ]);
            }
            
            // Redirect diarahkan tetap ke halaman yang sama (Monitoring Kolam) tapi bawa pesan sukses
            $pesan = ($statusAwal === 'pending') ? 'Pengajuan logistik Global masuk ke antrean persetujuan Admin.' : 'BOP Global berhasil diterapkan ke semua kolam aktif.';
            return redirect()->back()->with('success', $pesan);
        } else {
            DB::table('bop_kolam')->insert([
                'siklus_id' => $request->siklus_id, 'keterangan_biaya' => $keteranganFinal, 'nominal_biaya' => $request->nominal,
                'status' => $statusAwal, 'waktu_pencatatan' => now(), 'created_at' => now(), 'updated_at' => now()
            ]);
            
            // Redirect diarahkan tetap ke halaman yang sama (Monitoring Kolam) tapi bawa pesan sukses
            $pesan = ($statusAwal === 'pending') ? 'Pengajuan logistik kolam masuk ke antrean persetujuan Admin.' : 'BOP Kolam berhasil diterapkan.';
            return redirect()->back()->with('success', $pesan);
        }
    }

    public function updateBOP(Request $request, int $id): RedirectResponse
    {
        $keteranganFinal = $request->keterangan . ' - ' . $request->keterangan_lain;
        DB::table('bop_kolam')->where('id', $id)->update([
            'keterangan_biaya' => $keteranganFinal, 'nominal_biaya' => $request->nominal, 'status' => 'disetujui', 'updated_at' => now()
        ]);
        return redirect()->back()->with('success', 'Catatan pengeluaran BOP kolam berhasil dikoreksi.');
    }

    public function destroyBOP(int $id): RedirectResponse
    {
        DB::table('bop_kolam')->where('id', $id)->delete();
        return redirect()->back()->with('success', 'Catatan pengeluaran BOP berhasil dihapus.');
    }

    // Fungsi ACC ini dipanggil dari menu Jurnal Biaya, jadi redirectnya tetap ke Jurnal Validasi
    public function accBOP(int $id): RedirectResponse
    {
        DB::table('bop_kolam')->where('id', $id)->update(['status' => 'disetujui', 'updated_at' => now()]);
        return redirect('/biaya?tab=validasi')->with('success', 'Pengajuan BOP lapangan berhasil disetujui & resmi tercatat di neraca berjalan.');
    }

    /**
     * SAMPLING SEROKAN LAPANGAN (MUTASI UPLOAD REAL-TIME)
     */
    public function storeSampling(Request $request): RedirectResponse
    {
        if (!in_array(Auth::user()->role, ['admin', 'operator'])) return redirect()->back()->withErrors(['gagal' => 'Akses ditolak.']);

        $request->validate([
            'siklus_id' => 'required', 'grade_id_dominan' => 'required',
            'survival_rate_estimasi' => 'required|numeric|min:0|max:100', 'tanggal_sampling' => 'required',
            'jumlah_ekor_sampling' => 'nullable|integer|min:1',
            'foto_sampling' => 'nullable|image|mimes:jpeg,png,jpg|max:3072'
        ]);

        $pathFileFoto = null;
        if ($request->hasFile('foto_sampling')) {
            $pathFileFoto = $request->file('foto_sampling')->store('sampling_realtime', 'public');
        }

        // REVISI 2: Gunakan jumlah_ekor dari input, bukan hardcode 50
        $jumlahEkor = $request->jumlah_ekor_sampling ?? 50;

        DB::table('riwayat_sampling')->insert([
            'siklus_id' => $request->siklus_id, 'grade_id' => $request->grade_id_dominan,
            'jumlah_ekor' => $jumlahEkor,
            'sr_persen' => $request->survival_rate_estimasi, 'keterangan' => $request->keterangan ?? 'Sampling Berkala',
            'path_foto' => $pathFileFoto, 'tanggal_sampling' => $request->tanggal_sampling, 'created_at' => now(), 'updated_at' => now()
        ]);

        $siklus = DB::table('siklus_kolam')->where('id', $request->siklus_id)->first();
        DB::table('siklus_kolam')->where('id', $request->siklus_id)->update([
            'grade_id' => $request->grade_id_dominan,
            'waktu_tabur' => $siklus->waktu_tabur, 
            'updated_at' => now()
        ]);

        return redirect()->back()->with('success', 'Data log sampling baru berhasil dicatat.');
    }

    public function updateSampling(Request $request, int $id): RedirectResponse
    {
        if (!in_array(Auth::user()->role, ['admin', 'operator'])) return redirect()->back()->withErrors(['gagal' => 'Akses ditolak.']);

        $request->validate([
            'grade_id_dominan' => 'required', 'survival_rate_estimasi' => 'required|numeric|min:0|max:100',
            'tanggal_sampling' => 'required', 'foto_sampling' => 'nullable|image|mimes:jpeg,png,jpg|max:3072'
        ]);

        $log = DB::table('riwayat_sampling')->where('id', $id)->first();
        if (!$log) return redirect()->back()->withErrors(['gagal' => 'Log tidak ditemukan.']);

        $pathFileFoto = $log->path_foto;
        if ($request->hasFile('foto_sampling')) {
            if ($log->path_foto) Storage::disk('public')->delete($log->path_foto);
            $pathFileFoto = $request->file('foto_sampling')->store('sampling_realtime', 'public');
        }

        DB::table('riwayat_sampling')->where('id', $id)->update([
            'grade_id' => $request->grade_id_dominan, 'sr_persen' => $request->survival_rate_estimasi,
            'keterangan' => $request->keterangan ?? $log->keterangan, 'path_foto' => $pathFileFoto,
            'tanggal_sampling' => $request->tanggal_sampling, 'updated_at' => now()
        ]);

        $logTerbaru = DB::table('riwayat_sampling')->where('siklus_id', $log->siklus_id)->orderBy('tanggal_sampling', 'desc')->first();
        if ($logTerbaru && $logTerbaru->id == $id) {
            $siklus = DB::table('siklus_kolam')->where('id', $log->siklus_id)->first();
            DB::table('siklus_kolam')->where('id', $log->siklus_id)->update([
                'grade_id' => $request->grade_id_dominan, 
                'waktu_tabur' => $siklus->waktu_tabur,
                'updated_at' => now()
            ]);
        }

        return redirect()->back()->with('success', 'Data log riwayat sampling berhasil direvisi.');
    }

    public function destroySampling(int $id): RedirectResponse
    {
        if (!in_array(Auth::user()->role, ['admin', 'operator'])) return redirect()->back()->withErrors(['gagal' => 'Akses ditolak.']);

        $log = DB::table('riwayat_sampling')->where('id', $id)->first();
        if (!$log) return redirect()->back()->withErrors(['gagal' => 'Log tidak ditemukan.']);

        if ($log->path_foto) Storage::disk('public')->delete($log->path_foto);
        DB::table('riwayat_sampling')->where('id', $id)->delete();

        $logSisaTerbaru = DB::table('riwayat_sampling')->where('siklus_id', $log->siklus_id)->orderBy('tanggal_sampling', 'desc')->first();
        
        $siklus = DB::table('siklus_kolam')->where('id', $log->siklus_id)->first();
        DB::table('siklus_kolam')->where('id', $log->siklus_id)->update([
            'grade_id' => $logSisaTerbaru ? $logSisaTerbaru->grade_id : null,
            'waktu_tabur' => $siklus->waktu_tabur,
            'updated_at' => now()
        ]);

        return redirect()->back()->with('success', 'Item rekam log sampling berhasil dihapus.');
    }

    public function kurasKolam(int $id): RedirectResponse
    {
        DB::table('siklus_kolam')->where('id', $id)->update(['status' => 'selesai', 'waktu_kuras' => now(), 'updated_at' => now()]);
        return redirect()->back()->with('success', 'Kolam dikuras. Data diarsipkan.');
    }
}