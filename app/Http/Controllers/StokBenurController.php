<?php

namespace App\Http\Controllers;

use App\Models\StokBenur;
use App\Models\JenisBenur;
use App\Models\UkuranBenur;
use App\Models\GradeBenur;
use App\Models\SamplingStok;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class StokBenurController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->isCustomer()) {
            abort(403, 'Akses Ditolak. Halaman ini hanya diperuntukkan bagi internal staff tambak.');
        }

        $search = $request->input('search');
        $filterJenis = $request->input('filter_jenis');
        $filterUkuran = $request->input('filter_ukuran');

        $query = StokBenur::with(['jenis', 'ukuran', 'grade', 'samplings', 'biaya']);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nama_kolam', 'like', "%{$search}%")
                  ->orWhere('catatan', 'like', "%{$search}%");
            });
        }

        if ($filterJenis) { $query->where('jenis_id', $filterJenis); }
        if ($filterUkuran) { $query->where('ukuran_id', $filterUkuran); }

        $stok = $query->oldest()->paginate(10)->withQueryString();

        $stok->getCollection()->transform(function ($item) {
            $now = Carbon::now();
            $waktuInput = $item->created_at;

            $item->days = (int) $waktuInput->diffInDays($now);
            $item->display_hours = (int) $waktuInput->diffInHours($now) % 24;
            $item->display_minutes = (int) $waktuInput->diffInMinutes($now) % 60;

            $samplings = $item->samplings;
            
            if ($samplings->count() > 0) {
                $tanggalTerakhir = Carbon::parse($samplings->sortByDesc('created_at')->first()->created_at)->toDateString();
                $serokanHariTerakhir = $samplings->filter(fn($s) => Carbon::parse($s->created_at)->toDateString() == $tanggalTerakhir);
                $item->current_sr = round($serokanHariTerakhir->avg('estimasi_sr'), 2);
            } else {
                $item->current_sr = 100.00;
            }

            $item->populasi_estimasi = ($item->jumlah_ekor * $item->current_sr) / 100;
            $item->total_modal = $item->net_cost; 
            $item->hpp_per_ekor = $item->hpp_per_ekor;

            $total_sampel = $item->samplings->sum(fn($s) => $s->sampel_grade_a + $s->sampel_grade_b + $s->sampel_grade_c);
            if ($total_sampel > 0) {
                $item->persen_a = ($item->samplings->sum('sampel_grade_a') / $total_sampel) * 100;
                $item->persen_b = ($item->samplings->sum('sampel_grade_b') / $total_sampel) * 100;
                $item->persen_c = ($item->samplings->sum('sampel_grade_c') / $total_sampel) * 100;
            } else {
                $item->persen_a = $item->persen_b = $item->persen_c = 0;
            }

            return $item;
        });

        $jenis = JenisBenur::all();
        $ukuran = UkuranBenur::all();
        $grade = GradeBenur::all();

        return view('stok.index', compact('stok', 'jenis', 'ukuran', 'grade'));
    }

    public function store(Request $request)
    {
        if (Auth::user()->isPemilik() || Auth::user()->isCustomer()) {
            abort(403, 'Aksi Ditolak. Otoritas Anda hanya sebatas memantau kargo logistik.');
        }

        $request->validate([
            'jenis_id' => 'required', 'ukuran_id' => 'required', 'grade_id' => 'required',
            'nama_kolam' => 'required', 'jml_karung' => 'required|numeric',
            'kantong_per_karung' => 'required|numeric', 'ekor_per_kantong' => 'required|numeric',
            'tanggal_tabur' => 'required|date', 'harga_beli' => 'required|numeric',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = $request->all();
        $data['jumlah_ekor'] = (int)$request->jml_karung * (int)$request->kantong_per_karung * (int)$request->ekor_per_kantong;
        $data['status'] = 'aktif';

        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('stok', 'public');
        }

        StokBenur::create($data);

        return back()->with('success', 'Bibit berhasil ditabur! Total: ' . number_format($data['jumlah_ekor'], 0, ',', '.') . ' ekor.');
    }

    public function update(Request $request, $id)
    {
        if (Auth::user()->isPemilik() || Auth::user()->isCustomer()) {
            abort(403, 'Aksi Ditolak. Anda tidak mempunyai hak akses merubah data fisik kolam.');
        }

        $stok = StokBenur::findOrFail($id);
        $request->validate([
            'jenis_id' => 'required', 'ukuran_id' => 'required', 'grade_id' => 'required',
            'nama_kolam' => 'required', 'jml_karung' => 'required|numeric',
            'kantong_per_karung' => 'required|numeric', 'ekor_per_kantong' => 'required|numeric',
            'tanggal_tabur' => 'required|date', 'harga_beli' => 'required|numeric',
            'status' => 'required|in:aktif,panen,mati',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = $request->all();
        $data['jumlah_ekor'] = (int)$request->jml_karung * (int)$request->kantong_per_karung * (int)$request->ekor_per_kantong;

        if ($request->hasFile('foto')) {
            if ($stok->foto) {
                Storage::disk('public')->delete($stok->foto);
            }
            $data['foto'] = $request->file('foto')->store('stok', 'public');
        }

        $stok->update($data);

        return back()->with('success', "Data Kolam $stok->nama_kolam telah diperbarui.");
    }

    public function destroy(StokBenur $stok)
    {
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Akses Ilegal. Penghapusan data aset/kolam produksi wajib melalui persetujuan Admin Utama.');
        }

        if ($stok->foto) {
            Storage::disk('public')->delete($stok->foto);
        }
        
        $stok->delete();
        return back()->with('success', 'Data stok dan file foto berhasil dihapus.');
    }

    public function storeSampling(Request $request, $id)
    {
        if (Auth::user()->isPemilik() || Auth::user()->isCustomer()) {
            abort(403, 'Aksi Ditolak. Input sampling berkala hanya bisa dieksekusi tim lapangan.');
        }

        $request->validate([
            'sampel_grade_a' => 'required|numeric|min:0',
            'sampel_grade_b' => 'required|numeric|min:0',
            'sampel_grade_c' => 'required|numeric|min:0',
            'target_serokan' => 'required|numeric|min:1', 
            'created_at' => 'required|date',
        ]);

        $totalDidapat = $request->sampel_grade_a + $request->sampel_grade_b + $request->sampel_grade_c;
        $hitungSr = ($totalDidapat / $request->target_serokan) * 100;
        $srFinal = $hitungSr > 100 ? 100 : $hitungSr;

        SamplingStok::create([
            'stok_id' => $id,
            'serokan_ke' => SamplingStok::where('stok_id', $id)->count() + 1,
            'target_serokan' => $request->target_serokan,
            'estimasi_sr' => $srFinal,
            'sampel_grade_a' => $request->sampel_grade_a,
            'sampel_grade_b' => $request->sampel_grade_b,
            'sampel_grade_c' => $request->sampel_grade_c,
            'catatan' => $request->catatan,
            'created_at' => $request->created_at,
        ]);

        return back()->with('success', "Sampling berhasil disimpan! SR: " . number_format($srFinal, 2) . "%");
    }

    public function updateSampling(Request $request, $id)
    {
        if (Auth::user()->isPemilik() || Auth::user()->isCustomer()) {
            abort(403, 'Akses Ditolak.');
        }

        $request->validate([
            'target_serokan' => 'required|numeric|min:1',
            'sampel_grade_a' => 'required|numeric|min:0',
            'sampel_grade_b' => 'required|numeric|min:0',
            'sampel_grade_c' => 'required|numeric|min:0',
        ]);

        $sampling = SamplingStok::findOrFail($id);
        $total = $request->sampel_grade_a + $request->sampel_grade_b + $request->sampel_grade_c;
        $newSr = ($total / $request->target_serokan) * 100;
        $srFinal = $newSr > 100 ? 100 : $newSr;

        $sampling->update(array_merge($request->all(), ['estimasi_sr' => $srFinal]));

        return back()->with('success', 'Riwayat serokan berhasil diperbarui!');
    }
}