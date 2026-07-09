<x-app-layout>
    <div class="max-w-7xl mx-auto" x-data="{
        openModalTambah: {{ $errors->any() ? 'true' : 'false' }},
        openModalEdit: false,
        openModalRiwayat: false,
        openModalBiaya: false,
        selectedStok: { samplings: [], jenis: {}, ukuran: {}, grade: {}, foto: null },
    
        search: '{{ request('search') }}',
        isLoading: false,
    
        formatRupiah(val) {
            if (!val) return '0';
            let value = val.toString().replace(/\D/g, '');
            return new Intl.NumberFormat('id-ID').format(value);
        },
    
        fetchTable() {
            this.isLoading = true;
            let url = new URL(window.location.href);
            url.searchParams.set('search', this.search);
    
            fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newTable = doc.getElementById('container-tabel').innerHTML;
                    document.getElementById('container-tabel').innerHTML = newTable;
                    this.isLoading = false;
                    window.history.pushState({}, '', url);
                });
        },
    
        tungguKetik: null,
        liveSearch() {
            clearTimeout(this.tungguKetik);
            this.tungguKetik = setTimeout(() => {
                this.fetchTable();
            }, 400);
        }
    }">

        {{-- HEADER SECTION --}}
        <div class="flex flex-col md:flex-row md:items-end justify-between mb-8 gap-6">
            <div class="text-center md:text-left">
                <nav
                    class="flex items-center justify-center md:justify-start gap-2 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">
                    <span>AQUAFARM</span>
                    <i class="fa-solid fa-chevron-right text-[8px]"></i>
                    <span class="text-blue-600">Produksi & Stok</span>
                </nav>
                <h1 class="text-2xl md:text-4xl font-black text-slate-900 tracking-tight leading-none">Monitoring Stok
                    Benur</h1>
            </div>

            {{-- AKSES TABUR BIBIT BARU: Hanya Admin dan Operator --}}
            @if (Auth::user()->isAdmin() || Auth::user()->isOperator())
                <button @click="openModalTambah = true"
                    class="w-full md:w-auto bg-blue-600 hover:bg-blue-700 px-8 py-4 rounded-2xl text-[11px] font-black text-white shadow-xl shadow-blue-600/20 active:scale-95 transition-all uppercase tracking-widest flex items-center justify-center gap-3">
                    <i class="fa-solid fa-plus text-xs"></i> TABUR BIBIT BARU
                </button>
            @endif
        </div>

        {{-- ALERT --}}
        @if (session('success'))
            <div
                class="mb-6 p-5 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 rounded-2xl text-xs font-black uppercase tracking-widest shadow-sm flex items-center animate-pulse">
                <i class="fa-solid fa-circle-check mr-3 text-lg"></i> {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 p-5 bg-rose-50 border-l-4 border-rose-500 text-rose-700 rounded-2xl text-xs font-black uppercase tracking-widest shadow-sm flex items-center gap-3">
                <i class="fa-solid fa-circle-exclamation text-lg"></i>
                <ul class="inline">
                    @foreach ($errors->all() as $error)
                        <li class="inline">{{ $error }}{{ !$loop->last ? ' — ' : '' }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- SEARCH --}}
        <div class="flex flex-col md:flex-row gap-4 mb-6">
            <div class="relative flex-1">
                <i
                    class="fa-solid fa-magnifying-glass absolute left-5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" x-model="search" @input="liveSearch()" placeholder="Cari kolam atau supplier..."
                    class="w-full pl-12 pr-6 py-4 bg-white border-none rounded-2xl text-[11px] font-bold shadow-sm focus:ring-2 focus:ring-blue-500/20 transition-all uppercase tracking-widest placeholder-slate-300">
                <div x-show="isLoading" class="absolute right-5 top-1/2 -translate-y-1/2">
                    <i class="fa-solid fa-spinner fa-spin text-blue-500"></i>
                </div>
            </div>
        </div>

        {{-- MAIN TABLE --}}
        <div id="container-tabel"
            class="bg-white rounded-[2.5rem] shadow-2xl shadow-slate-200/60 border border-slate-100 overflow-hidden relative">
            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left border-collapse min-w-[1450px]">
                    <thead>
                        <tr
                            class="text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-50 bg-white">
                            <th class="px-8 py-6 text-center w-16">No</th>
                            <th class="px-4 py-6 text-center">Visual & Lokasi</th>
                            <th class="px-4 py-6">Informasi Benur</th>
                            <th class="px-4 py-6 text-center w-48">Estimasi Kualitas</th>
                            <th class="px-4 py-6 text-center">Manajemen Populasi Stok</th>
                            <th class="px-4 py-6 text-center">Analisis Modal (HPP)</th>
                            <th class="px-4 py-6 text-center">Umur & Catatan</th>
                            <th class="px-8 py-6 text-right w-44">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($stok as $index => $s)
                            <tr class="hover:bg-slate-50/50 transition-all group" x-data="{
                                modalSampling: false,
                                dataSampling: { gradeA: '', gradeB: '', gradeC: '', target: 100, date: '{{ date('Y-m-d\TH:i') }}' },
                                resetFormSampling() {
                                    this.dataSampling = { gradeA: '', gradeB: '', gradeC: '', target: 100, date: '{{ date('Y-m-d\TH:i') }}' };
                                    this.modalSampling = false;
                                }
                            }">
                                <td class="px-8 py-7 text-center font-bold text-slate-300 text-xs">
                                    {{ ($stok->currentPage() - 1) * $stok->perPage() + $index + 1 }}
                                </td>

                                {{-- VISUAL & LOKASI --}}
                                <td class="px-4 py-7 text-center">
                                    <div class="flex flex-col items-center gap-2">
                                        <div
                                            class="w-12 h-12 rounded-xl bg-slate-100 overflow-hidden border border-slate-200 shadow-sm mb-1">
                                            @if ($s->foto)
                                                <img src="{{ asset('storage/' . $s->foto) }}"
                                                    class="w-full h-full object-cover">
                                            @else
                                                <div
                                                    class="w-full h-full flex items-center justify-center bg-slate-50 text-slate-200">
                                                    <i class="fa-solid fa-camera text-lg"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <span
                                            class="px-3 py-1.5 bg-slate-100 rounded-lg group-hover:bg-blue-600 group-hover:text-white transition-all font-black text-[10px] uppercase">{{ $s->nama_kolam }}</span>
                                        @if ($s->status == 'aktif')
                                            <span
                                                class="px-2 py-0.5 bg-emerald-100 text-emerald-600 rounded text-[8px] font-black uppercase tracking-widest">Running</span>
                                        @elseif($s->status == 'panen')
                                            <span
                                                class="px-2 py-0.5 bg-blue-100 text-blue-600 rounded text-[8px] font-black uppercase tracking-widest">Selesai</span>
                                        @else
                                            <span
                                                class="px-2 py-0.5 bg-rose-100 text-rose-600 rounded text-[8px] font-black uppercase tracking-widest">Gagal</span>
                                        @endif
                                    </div>
                                </td>

                                {{-- INFORMASI BENUR --}}
                                <td class="px-4 py-7">
                                    <div class="text-sm font-extrabold text-slate-800 uppercase italic">
                                        {{ $s->jenis->nama ?? 'BENUR UDANG' }}
                                    </div>
                                    <div class="text-[10px] font-bold text-slate-400 mt-0.5 uppercase">
                                        {{ $s->ukuran->ukuran ?? 'PL' }} • {{ $s->grade->nama_grade ?? 'Grade A' }}
                                    </div>
                                </td>

                                {{-- ESTIMASI KUALITAS --}}
                                <td class="px-4 py-7">
                                    @php
                                        $lastSampling = $s->samplings()->latest()->first();
                                        $totalSampel = $lastSampling
                                            ? $lastSampling->sampel_grade_a +
                                                $lastSampling->sampel_grade_b +
                                                $lastSampling->sampel_grade_c
                                            : 0;
                                        $pA =
                                            $totalSampel > 0 ? ($lastSampling->sampel_grade_a / $totalSampel) * 100 : 0;
                                        $pB =
                                            $totalSampel > 0 ? ($lastSampling->sampel_grade_b / $totalSampel) * 100 : 0;
                                        $pC =
                                            $totalSampel > 0 ? ($lastSampling->sampel_grade_c / $totalSampel) * 100 : 0;
                                    @endphp
                                    @if ($s->samplings->count() > 0)
                                        <div class="h-2 w-full bg-slate-100 rounded-full flex overflow-hidden mb-1">
                                            <div class="bg-emerald-500" style="width: {{ $pA }}%"></div>
                                            <div class="bg-amber-500" style="width: {{ $pB }}%"></div>
                                            <div class="bg-rose-500" style="width: {{ $pC }}%"></div>
                                        </div>
                                        <div
                                            class="flex justify-between text-[9px] font-black uppercase text-slate-400">
                                            <span>A:{{ round($pA) }}%</span>
                                            <span>B:{{ round($pB) }}%</span>
                                            <span>C:{{ round($pC) }}%</span>
                                        </div>
                                    @else
                                        <span
                                            class="text-[9px] text-slate-300 italic uppercase tracking-tighter flex justify-center">Belum
                                            Sampling</span>
                                    @endif
                                </td>

                                {{-- POPULASI & SR TRACKER --}}
                                <td class="px-4 py-7">
                                    <div class="flex flex-col items-center">
                                        <div
                                            class="flex items-center gap-3 bg-slate-50/80 p-2.5 rounded-xl border border-slate-100">
                                            <div class="flex flex-col items-center">
                                                <span
                                                    class="text-[8px] font-black text-slate-400 uppercase leading-none mb-1">Tebar
                                                    Awal</span>
                                                <span
                                                    class="text-xs font-bold text-slate-600">{{ number_format($s->jumlah_ekor, 0, ',', '.') }}</span>
                                            </div>
                                            <div class="h-6 w-[1px] bg-slate-200"></div>
                                            <div class="flex flex-col items-center">
                                                <span
                                                    class="text-[8px] font-black text-indigo-500 uppercase leading-none mb-1">Estimasi
                                                    Fisik</span>
                                                <span
                                                    class="text-xs font-extrabold text-indigo-600">{{ number_format($s->populasi_estimasi, 0, ',', '.') }}</span>
                                            </div>
                                        </div>
                                        @php $currentSr = $lastSampling ? $lastSampling->estimasi_sr : 100; @endphp
                                        <div
                                            class="mt-2 px-2 py-0.5 {{ $currentSr < 85 ? 'bg-rose-50 text-rose-600' : 'bg-emerald-50 text-emerald-600' }} rounded-md border border-current/10">
                                            <span class="text-[9px] font-black uppercase italic">Survival Rate (SR):
                                                {{ number_format($currentSr, 2) }}%</span>
                                        </div>
                                    </div>
                                </td>

                                {{-- ANALISIS MODAL / HPP --}}
                                <td class="px-4 py-7 text-center">
                                    <div class="flex flex-col items-center">
                                        <span
                                            class="text-xs font-black text-slate-800 tracking-tight">Rp{{ number_format($s->harga_beli, 0, ',', '.') }}</span>
                                        @if ($s->ekor_per_kantong > 0)
                                            <div
                                                class="mt-1 px-2 py-0.5 bg-indigo-50 text-indigo-600 rounded text-[9px] font-bold border border-indigo-100 whitespace-nowrap">
                                                HPP:
                                                Rp{{ number_format($s->harga_beli / $s->ekor_per_kantong, 2, ',', '.') }}
                                                / ekor
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                {{-- UMUR & CATATAN --}}
                                <td class="px-4 py-7 text-center">
                                    <div class="flex flex-col items-center">
                                        @php $diff = \Carbon\Carbon::parse($s->tanggal_tabur)->diff(\Carbon\Carbon::now()); @endphp
                                        <span class="text-sm font-black text-blue-600 leading-none">{{ $diff->days }}
                                            Hari</span>
                                        <span
                                            class="text-[8px] font-bold text-slate-400 italic mb-2">{{ $diff->h }}J
                                            {{ $diff->i }}M</span>
                                        <span
                                            class="text-[9px] font-bold text-slate-500 uppercase italic border-t border-slate-100 pt-1 w-full">{{ $s->catatan ?? '-' }}</span>
                                    </div>
                                </td>

                                {{-- AKSI MANAGEMENT KONDISIONAL BERDASARKAN ROLE USER --}}
                                <td class="px-8 py-7 text-right">
                                    <div class="flex justify-end gap-1.5">
                                        {{-- 1. Tombol Sampling (Bisa diakses Admin & Operator) --}}
                                        @if (Auth::user()->isAdmin() || Auth::user()->isOperator())
                                            <button title="Sampling" @click="modalSampling = true"
                                                class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white transition-all flex items-center justify-center">
                                                <i class="fa-solid fa-vial text-xs"></i>
                                            </button>
                                        @endif

                                        {{-- 2. Tombol Riwayat (Bisa diakses Semua Staff Internal) --}}
                                        <button title="Riwayat"
                                            @click="selectedStok = {{ $s->load('samplings') }}; openModalRiwayat = true"
                                            class="w-8 h-8 rounded-lg bg-slate-50 text-slate-400 hover:bg-slate-600 hover:text-white transition-all flex items-center justify-center">
                                            <i class="fa-solid fa-clock-rotate-left text-xs"></i>
                                        </button>

                                        {{-- 3. Tombol Input Biaya Spesifik Kolam (Bisa diakses Admin & Operator) --}}
                                        @if (Auth::user()->isAdmin() || Auth::user()->isOperator())
                                            <button title="Input Biaya Spesifik"
                                                @click="selectedStok = {{ $s }}; openModalBiaya = true"
                                                class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white transition-all flex items-center justify-center">
                                                <i class="fa-solid fa-money-bill-transfer text-xs"></i>
                                            </button>
                                        @endif

                                        {{-- 4. Tombol Edit Kargo Data Kolam (Bisa diakses Admin & Operator) --}}
                                        @if (Auth::user()->isAdmin() || Auth::user()->isOperator())
                                            <button title="Edit Data"
                                                @click="selectedStok = JSON.parse(JSON.stringify({{ $s }})); openModalEdit = true"
                                                class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-600 hover:text-white transition-all flex items-center justify-center">
                                                <i class="fa-solid fa-pen-to-square text-xs"></i>
                                            </button>
                                        @endif

                                        {{-- 5. Tombol Hapus Permanen Aset: Hanya Admin Utama --}}
                                        @if (Auth::user()->isAdmin())
                                            <form action="{{ route('stok.destroy', $s->id) }}" method="POST"
                                                onsubmit="return confirm('Hapus permanen kolam ini beserta seluruh riwayat produksinya?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" title="Hapus"
                                                    class="w-8 h-8 rounded-lg bg-rose-50 text-rose-500 hover:bg-rose-600 hover:text-white transition-all flex items-center justify-center">
                                                    <i class="fa-solid fa-trash-can text-xs"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>

                                    {{-- MODAL INPUT SAMPLING HARIAN --}}
                                    <template x-teleport="body">
                                        <div x-show="modalSampling"
                                            class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md"
                                            x-cloak x-transition>
                                            <div @click.away="resetFormSampling()"
                                                class="bg-white w-full max-w-md rounded-[2.5rem] p-10 shadow-2xl border border-slate-100">
                                                <h2 class="text-2xl font-black text-slate-800 uppercase mb-2">Sampling
                                                    & SR</h2>
                                                <p class="text-xs font-bold text-slate-400 uppercase mb-6 italic">
                                                    Kolam: {{ $s->nama_kolam }}</p>

                                                <form action="{{ route('stok.sampling', $s->id) }}" method="POST"
                                                    class="space-y-4" x-data="{
                                                        get autoSR() {
                                                            let total = parseInt(dataSampling.gradeA || 0) + parseInt(dataSampling.gradeB || 0) + parseInt(dataSampling.gradeC || 0);
                                                            let targetVal = parseInt(dataSampling.target || 1);
                                                            let hasil = (total / targetVal) * 100;
                                                            return hasil > 100 ? 100.00 : hasil.toFixed(2);
                                                        }
                                                    }">
                                                    @csrf
                                                    <div class="grid grid-cols-2 gap-4">
                                                        <div class="col-span-2">
                                                            <label
                                                                class="text-[10px] font-black text-blue-600 ml-1 uppercase">Waktu
                                                                Sampling</label>
                                                            <input type="datetime-local" name="created_at"
                                                                x-model="dataSampling.date" required
                                                                class="w-full mt-1 bg-slate-50 border-none rounded-xl px-4 py-3 font-bold text-sm">
                                                        </div>
                                                        <div class="bg-indigo-50 p-4 rounded-2xl">
                                                            <label
                                                                class="text-[10px] font-black text-indigo-700 ml-1 uppercase">Target
                                                                Serok</label>
                                                            <input type="number" name="target_serokan"
                                                                x-model="dataSampling.target" required
                                                                class="w-full mt-1 bg-white border-none rounded-xl px-4 py-3 font-black text-indigo-700 text-lg">
                                                        </div>
                                                        <div
                                                            class="bg-emerald-50 p-4 rounded-2xl flex flex-col justify-center items-center border-2 border-emerald-200 border-dashed">
                                                            <span
                                                                class="text-[9px] font-black uppercase text-emerald-600">SR
                                                                Otomatis</span>
                                                            <div class="flex items-baseline">
                                                                <span class="text-2xl font-black text-emerald-700"
                                                                    x-text="autoSR"></span>
                                                                <span
                                                                    class="text-xs font-black text-emerald-700 ml-1">%</span>
                                                            </div>
                                                            <input type="hidden" name="estimasi_sr"
                                                                :value="autoSR">
                                                        </div>
                                                    </div>
                                                    <div class="grid grid-cols-3 gap-3">
                                                        <div>
                                                            <label
                                                                class="text-[9px] font-bold text-slate-400 block text-center mb-1">GRADE
                                                                A</label>
                                                            <input type="number" name="sampel_grade_a"
                                                                x-model.number="dataSampling.gradeA" placeholder="0"
                                                                required
                                                                class="w-full bg-slate-50 border-none rounded-xl py-3 text-center font-black text-emerald-600">
                                                        </div>
                                                        <div>
                                                            <label
                                                                class="text-[9px] font-bold text-slate-400 block text-center mb-1">GRADE
                                                                B</label>
                                                            <input type="number" name="sampel_grade_b"
                                                                x-model.number="dataSampling.gradeB" placeholder="0"
                                                                required
                                                                class="w-full bg-slate-50 border-none rounded-xl py-3 text-center font-black text-amber-600">
                                                        </div>
                                                        <div>
                                                            <label
                                                                class="text-[9px] font-bold text-slate-400 block text-center mb-1">GRADE
                                                                C</label>
                                                            <input type="number" name="sampel_grade_c"
                                                                x-model.number="dataSampling.gradeC" placeholder="0"
                                                                required
                                                                class="w-full bg-slate-50 border-none rounded-xl py-3 text-center font-black text-rose-600">
                                                        </div>
                                                    </div>
                                                    <div class="flex gap-3 pt-2">
                                                        <button type="button" @click="resetFormSampling()"
                                                            class="flex-1 py-4 bg-slate-100 text-slate-500 rounded-2xl font-black uppercase text-[11px]">Batal</button>
                                                        <button type="submit"
                                                            class="flex-[2] py-4 bg-blue-600 text-white rounded-2xl font-black uppercase text-[11px] shadow-lg shadow-blue-600/20">Simpan
                                                            Update</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </template>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-24 text-center font-black text-slate-300 uppercase">Tidak
                                    ada data ditemukan</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-8 py-6 border-t border-slate-50 bg-slate-50/30">{{ $stok->links() }}</div>
        </div>

        {{-- MODAL RIWAYAT SEROKAN --}}
        <div x-show="openModalRiwayat"
            class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md" x-cloak
            x-transition>
            <div @click.away="openModalRiwayat = false"
                class="bg-white w-full max-w-5xl rounded-[3rem] p-10 shadow-2xl border border-slate-100">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 class="text-2xl font-black text-slate-800 uppercase italic">Riwayat Serokan</h2>
                        <p class="text-xs font-bold text-blue-600 uppercase tracking-widest"
                            x-text="selectedStok.nama_kolam"></p>
                    </div>
                    <button @click="openModalRiwayat = false"
                        class="text-slate-300 hover:text-rose-500 transition-colors"><i
                            class="fa-solid fa-circle-xmark text-2xl"></i></button>
                </div>
                <div class="max-h-[450px] overflow-y-auto custom-scrollbar">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[10px] font-black text-slate-400 uppercase border-b border-slate-50">
                                <th class="py-4">No</th>
                                <th class="py-4">Waktu</th>
                                <th class="py-4 text-center bg-slate-50 rounded-t-lg">Target</th>
                                <th class="py-4 text-center">SR (%)</th>
                                <th class="py-4 text-center text-emerald-600">GR A</th>
                                <th class="py-4 text-center text-amber-600">GR B</th>
                                <th class="py-4 text-center text-rose-600">GR C</th>
                                @if (Auth::user()->isAdmin() || Auth::user()->isOperator())
                                    <th class="py-4 text-right">Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <template x-for="(sm, i) in selectedStok.samplings" :key="sm.id">
                                <tr class="text-xs font-bold text-slate-600 group" x-data="{ editing: false }">
                                    <td class="py-4" x-text="i+1"></td>
                                    <td class="py-4 text-slate-400 font-medium"
                                        x-text="new Date(sm.created_at).toLocaleString('id-ID', {day:'numeric', month:'short', hour:'2-digit', minute:'2-digit'})">
                                    </td>

                                    <template x-if="!editing">
                                        <td class="text-center py-4 bg-slate-50/50 font-black text-indigo-600"
                                            x-text="sm.target_serokan || 100"></td>
                                    </template>
                                    <template x-if="!editing">
                                        <td class="text-center py-4" x-text="sm.estimasi_sr + '%'"></td>
                                    </template>
                                    <template x-if="!editing">
                                        <td class="text-center py-4 text-emerald-600" x-text="sm.sampel_grade_a"></td>
                                    </template>
                                    <template x-if="!editing">
                                        <td class="text-center py-4 text-amber-600" x-text="sm.sampel_grade_b"></td>
                                    </template>
                                    <template x-if="!editing">
                                        <td class="text-center py-4 text-rose-600" x-text="sm.sampel_grade_c"></td>
                                    </template>

                                    {{-- INLINE EDIT RIWAYAT SAMPLING --}}
                                    <template x-if="editing">
                                        <td colspan="6" class="py-2">
                                            <form :action="'{{ url('sampling') }}/' + sm.id" method="POST"
                                                class="flex gap-2 items-center bg-slate-50 p-2 rounded-xl"
                                                x-data="{
                                                    t: sm.target_serokan,
                                                    a: sm.sampel_grade_a,
                                                    b: sm.sampel_grade_b,
                                                    c: sm.sampel_grade_c,
                                                    get cSR() {
                                                        let res = (((parseInt(this.a) + parseInt(this.b) + parseInt(this.c)) / (parseInt(this.t) || 1)) * 100);
                                                        return res > 100 ? 100.00 : res.toFixed(2);
                                                    }
                                                }">
                                                @csrf @method('PUT')
                                                <input type="number" name="target_serokan" x-model.number="t"
                                                    class="w-16 bg-white border-none rounded-lg text-[11px] font-black text-center p-2 text-indigo-600">
                                                <input type="number" step="0.01" name="estimasi_sr"
                                                    :value="cSR" readonly
                                                    class="w-20 bg-slate-100 border-none rounded-lg text-[11px] font-black text-center p-2 text-slate-500">
                                                <input type="number" name="sampel_grade_a" x-model.number="a"
                                                    class="w-14 bg-white border-none rounded-lg text-[11px] font-black text-center p-2 text-emerald-600">
                                                <input type="number" name="sampel_grade_b" x-model.number="b"
                                                    class="w-14 bg-white border-none rounded-lg text-[11px] font-black text-center p-2 text-amber-600">
                                                <input type="number" name="sampel_grade_c" x-model.number="c"
                                                    class="w-14 bg-white border-none rounded-lg text-[11px] font-black text-center p-2 text-rose-600">
                                                <button type="submit"
                                                    class="bg-blue-600 text-white px-3 py-2 rounded-lg hover:bg-blue-700"><i
                                                        class="fa-solid fa-check"></i></button>
                                                <button type="button" @click="editing = false"
                                                    class="bg-slate-200 text-slate-500 px-3 py-2 rounded-lg"><i
                                                        class="fa-solid fa-xmark"></i></button>
                                            </form>
                                        </td>
                                    </template>

                                    @if (Auth::user()->isAdmin() || Auth::user()->isOperator())
                                        <td class="py-4 text-right" x-show="!editing">
                                            <button @click="editing = true"
                                                class="w-7 h-7 rounded-lg bg-slate-100 text-slate-400 hover:bg-blue-600 hover:text-white transition-all"><i
                                                    class="fa-solid fa-pen text-[10px]"></i></button>
                                        </td>
                                    @endif
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- MODAL TAMBAH BIBIT (DIPROTEKSI BLADE DARI PEMILIK) --}}
        @if (Auth::user()->isAdmin() || Auth::user()->isOperator())
            <div x-show="openModalTambah"
                class="fixed inset-0 z-[150] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md"
                x-cloak x-transition x-data="{
                    form: { kolam: '', karung: '', kantong: '', ekor: '', harga: '', displayHpp: '', rawHpp: '', tgl: '{{ date('Y-m-d') }}', catatan: '' },
                    resetFormTambah() {
                        this.form = { kolam: '', karung: '', kantong: '', ekor: '', harga: '', displayHpp: '', rawHpp: '', tgl: '{{ date('Y-m-d') }}', catatan: '' };
                        this.openModalTambah = false;
                    },
                    formatInputRupiah(val) {
                        let value = val.toString().replace(/\D/g, '');
                        this.form.rawHpp = value;
                        this.form.displayHpp = new Intl.NumberFormat('id-ID').format(value);
                    }
                }">
                <div @click.away="resetFormTambah()"
                    class="bg-white w-full max-w-2xl rounded-[3rem] p-10 shadow-2xl overflow-y-auto max-h-[90vh]">
                    <h2 class="text-3xl font-black text-slate-800 mb-6 uppercase tracking-tight">Tabur Bibit Baru</h2>
                    <form action="{{ route('stok.store') }}" method="POST" enctype="multipart/form-data"
                        class="grid grid-cols-1 md:grid-cols-2 gap-6" autocomplete="off">
                        @csrf
                        <div class="md:col-span-2">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">Nama
                                Kolam / Lokasi</label>
                            <input type="text" name="nama_kolam" x-model="form.kolam" required
                                class="w-full mt-2 bg-slate-50 border-none rounded-2xl px-6 py-4 text-sm font-bold shadow-inner"
                                placeholder="Kolam A-1">
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">Foto
                                Produk / Kolam</label>
                            <input type="file" name="foto"
                                class="w-full mt-2 bg-slate-50 border-none rounded-2xl px-6 py-4 text-xs font-bold shadow-inner">
                        </div>
                        <div
                            class="md:col-span-2 grid grid-cols-3 gap-4 bg-slate-50 p-6 rounded-[2.5rem] border border-slate-100">
                            <div>
                                <label class="text-[10px] font-black text-blue-600 uppercase">Jml Karung</label>
                                <input type="number" name="jml_karung" x-model.number="form.karung" required
                                    class="w-full mt-1 bg-blue-100 border-none rounded-xl px-4 py-3.5 text-sm font-black text-blue-700">
                            </div>
                            <div>
                                <label class="text-[10px] font-black text-indigo-600 uppercase">Ktg/Karung</label>
                                <input type="number" name="kantong_per_karung" x-model.number="form.kantong"
                                    required
                                    class="w-full mt-1 bg-indigo-100 border-none rounded-xl px-4 py-3.5 text-sm font-black text-indigo-700">
                            </div>
                            <div>
                                <label class="text-[10px] font-black text-teal-600 uppercase">Ekor/Ktg</label>
                                <input type="number" name="ekor_per_kantong" x-model.number="form.ekor" required
                                    class="w-full mt-1 bg-teal-100 border-none rounded-xl px-4 py-3.5 text-sm font-black text-teal-700">
                            </div>
                            <div
                                class="col-span-3 bg-slate-900 rounded-2xl p-5 flex justify-between items-center text-white mt-2">
                                <span class="text-[10px] font-black uppercase text-slate-500 italic">Estimasi
                                    Populasi:</span>
                                <span class="text-2xl font-black"
                                    x-text="new Intl.NumberFormat('id-ID').format(form.karung * form.kantong * form.ekor || 0)"></span>
                            </div>
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 ml-2">Jenis Benur</label>
                            <select name="jenis_id" required
                                class="w-full mt-2 bg-slate-50 border-none rounded-2xl px-6 py-4 text-sm font-bold">
                                @foreach ($jenis as $j)
                                    <option value="{{ $j->id }}">{{ $j->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 ml-2">Ukuran (PL)</label>
                            <select name="ukuran_id" required
                                class="w-full mt-2 bg-slate-50 border-none rounded-2xl px-6 py-4 text-sm font-bold">
                                @foreach ($ukuran as $u)
                                    <option value="{{ $u->id }}">{{ $u->ukuran }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 ml-2">Grade Awal</label>
                            <select name="grade_id" required
                                class="w-full mt-2 bg-slate-50 border-none rounded-2xl px-6 py-4 text-sm font-bold">
                                @foreach ($grade as $g)
                                    <option value="{{ $g->id }}">{{ $g->nama_grade }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-emerald-600 uppercase italic ml-2">Harga Beli
                                Total (Rp)</label>
                            <input type="text" x-model="form.displayHpp"
                                @input="formatInputRupiah($event.target.value)" required
                                class="w-full mt-2 bg-emerald-50 border-none rounded-2xl px-6 py-4 text-sm font-black text-emerald-700"
                                placeholder="0">
                            <input type="hidden" name="harga_beli" :value="form.rawHpp">
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 ml-2 uppercase">Tanggal Tabur</label>
                            <input type="date" name="tanggal_tabur" x-model="form.tgl" required
                                class="w-full mt-2 bg-slate-50 border-none rounded-2xl px-6 py-4 text-sm font-bold">
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 ml-2 uppercase">Catatan /
                                Supplier</label>
                            <input type="text" name="catatan" x-model="form.catatan"
                                placeholder="Nama Supplier..."
                                class="w-full mt-2 bg-slate-50 border-none rounded-2xl px-6 py-4 text-sm font-bold">
                        </div>
                        <div class="md:col-span-2 flex gap-4 pt-6">
                            <button type="button" @click="resetFormTambah()"
                                class="flex-1 py-5 bg-slate-100 rounded-2xl font-black text-[11px] uppercase">Batal</button>
                            <button type="submit"
                                class="flex-1 py-5 bg-blue-600 text-white rounded-2xl font-black text-[11px] uppercase shadow-xl shadow-blue-600/30 transition-all hover:bg-blue-700">Simpan
                                Data</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        {{-- MODAL EDIT STOK (DIPROTEKSI BLADE DARI PEMILIK) --}}
        @if (Auth::user()->isAdmin() || Auth::user()->isOperator())
            <div x-show="openModalEdit"
                class="fixed inset-0 z-[150] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md"
                x-cloak x-transition>
                <div @click.away="openModalEdit = false"
                    class="bg-white w-full max-w-2xl rounded-[3rem] p-10 shadow-2xl overflow-y-auto max-h-[90vh]"
                    x-init="$watch('openModalEdit', value => { if (value && selectedStok.harga_beli) { selectedStok.harga_beli = parseInt(selectedStok.harga_beli).toString(); } })">
                    <h2 class="text-3xl font-black text-slate-800 mb-6 uppercase tracking-tight italic text-amber-600">
                        Edit Data Stok</h2>
                    <form :action="'{{ url('stok') }}/' + selectedStok.id" method="POST"
                        enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-6"
                        autocomplete="off">
                        @csrf @method('PUT')
                        <div class="md:col-span-2">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">Ubah
                                Status Kolam</label>
                            <select name="status" x-model="selectedStok.status" required
                                class="w-full mt-2 bg-slate-50 border-none rounded-2xl px-6 py-4 text-sm font-bold shadow-inner">
                                <option value="aktif">Aktif (Running)</option>
                                <option value="panen">Panen (Selesai)</option>
                                <option value="mati">Gagal Panen (Mati)</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">Nama
                                Kolam / Lokasi</label>
                            <input type="text" name="nama_kolam" x-model="selectedStok.nama_kolam" required
                                class="w-full mt-2 bg-slate-50 border-none rounded-2xl px-6 py-4 text-sm font-bold shadow-inner">
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">Foto
                                Produk / Kolam (Opsional)</label>
                            <input type="file" name="foto"
                                class="w-full mt-2 bg-slate-50 border-none rounded-2xl px-6 py-4 text-xs font-bold shadow-inner">
                        </div>
                        <div
                            class="md:col-span-2 grid grid-cols-3 gap-4 bg-amber-50/30 p-6 rounded-[2.5rem] border border-amber-100">
                            <div>
                                <label class="text-[10px] font-black text-blue-600 uppercase">Jml Karung</label>
                                <input type="number" name="jml_karung" x-model.number="selectedStok.jml_karung"
                                    required
                                    class="w-full mt-1 bg-blue-100 border-none rounded-xl px-4 py-3.5 text-sm font-black text-blue-700">
                            </div>
                            <div>
                                <label class="text-[10px] font-black text-indigo-600 uppercase">Ktg/Karung</label>
                                <input type="number" name="kantong_per_karung"
                                    x-model.number="selectedStok.kantong_per_karung" required
                                    class="w-full mt-1 bg-indigo-100 border-none rounded-xl px-4 py-3.5 text-sm font-black text-indigo-700">
                            </div>
                            <div>
                                <label class="text-[10px] font-black text-teal-600 uppercase">Ekor/Ktg</label>
                                <input type="number" name="ekor_per_kantong"
                                    x-model.number="selectedStok.ekor_per_kantong" required
                                    class="w-full mt-1 bg-teal-100 border-none rounded-xl px-4 py-3.5 text-sm font-black text-teal-700">
                            </div>
                            <div
                                class="col-span-3 bg-slate-900 rounded-2xl p-4 text-center mt-2 flex justify-between items-center px-6">
                                <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Kalkulasi
                                    Baru: </span>
                                <span class="text-xl font-black text-white"
                                    x-text="new Intl.NumberFormat('id-ID').format(selectedStok.jml_karung * selectedStok.kantong_per_karung * selectedStok.ekor_per_kantong || 0)"></span>
                            </div>
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">Jenis
                                Benur</label>
                            <select name="jenis_id" x-model="selectedStok.jenis_id"
                                class="w-full mt-2 bg-slate-50 border-none rounded-2xl px-6 py-4 text-sm font-bold">
                                @foreach ($jenis as $j)
                                    <option value="{{ $j->id }}">{{ $j->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">Ukuran
                                (PL)</label>
                            <select name="ukuran_id" x-model="selectedStok.ukuran_id"
                                class="w-full mt-2 bg-slate-50 border-none rounded-2xl px-6 py-4 text-sm font-bold">
                                @foreach ($ukuran as $u)
                                    <option value="{{ $u->id }}">{{ $u->ukuran }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">Grade
                                Awal</label>
                            <select name="grade_id" x-model="selectedStok.grade_id"
                                class="w-full mt-2 bg-slate-50 border-none rounded-2xl px-6 py-4 text-sm font-bold">
                                @foreach ($grade as $g)
                                    <option value="{{ $g->id }}">{{ $g->nama_grade }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div x-data="{
                            get formattedHarga() { return new Intl.NumberFormat('id-ID').format(selectedStok.harga_beli || 0); },
                            set formattedHarga(value) { selectedStok.harga_beli = value.toString().replace(/\D/g, ''); }
                        }">
                            <label class="text-[10px] font-black text-emerald-600 uppercase italic ml-2">Harga Beli
                                (Rp)</label>
                            <input type="text" x-model="formattedHarga" required
                                class="w-full mt-2 bg-emerald-50 border-none rounded-2xl px-6 py-4 text-sm font-black text-emerald-700">
                            <input type="hidden" name="harga_beli" :value="selectedStok.harga_beli">
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 ml-2">Tanggal Tabur</label>
                            <input type="date" name="tanggal_tabur" x-model="selectedStok.tanggal_tabur" required
                                class="w-full mt-2 bg-slate-50 border-none rounded-2xl px-6 py-4 text-sm font-bold">
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 ml-2">Catatan / Supplier</label>
                            <input type="text" name="catatan" x-model="selectedStok.catatan"
                                class="w-full mt-2 bg-slate-50 border-none rounded-2xl px-6 py-4 text-sm font-bold shadow-inner">
                        </div>
                        <div class="md:col-span-2 flex gap-4 pt-6">
                            <button type="button" @click="openModalEdit = false"
                                class="flex-1 py-5 bg-slate-100 rounded-2xl font-black text-[11px] uppercase">Batal</button>
                            <button type="submit"
                                class="flex-1 py-5 bg-amber-600 text-white rounded-2xl font-black text-[11px] uppercase shadow-xl shadow-amber-600/30 transition-all hover:bg-amber-700">Simpan
                                Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        {{-- MODAL BIAYA SPESIFIK KOLAM (DIPROTEKSI BLADE DARI PEMILIK) --}}
        @if (Auth::user()->isAdmin() || Auth::user()->isOperator())
            <div x-show="openModalBiaya"
                class="fixed inset-0 z-[150] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md"
                x-cloak x-transition>
                <div @click.away="openModalBiaya = false"
                    class="bg-white w-full max-w-lg rounded-[3rem] p-10 shadow-2xl">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h2 class="text-2xl font-black text-slate-800 uppercase italic">Input Biaya</h2>
                            <p class="text-xs font-bold text-blue-600 uppercase tracking-widest">Kolam: <span
                                    x-text="selectedStok.nama_kolam"></span></p>
                        </div>
                        <button type="button" @click="openModalBiaya = false"
                            class="text-slate-300 hover:text-rose-500 transition-colors"><i
                                class="fa-solid fa-circle-xmark text-2xl"></i></button>
                    </div>

                    <form action="{{ route('biaya.store') }}" method="POST" class="space-y-4"
                        x-data="{ isLainnya: false }">
                        @csrf
                        <input type="hidden" name="stok_id" :value="selectedStok.id">

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase ml-2">Kategori</label>
                                <select name="kategori" required
                                    x-on:change="isLainnya = ($event.target.value === 'Lainnya')"
                                    class="w-full mt-1 bg-slate-50 border-none rounded-2xl px-4 py-3 text-sm font-bold">
                                    <option value="">Pilih...</option>
                                    <option value="Pakan">Pakan & Artemia</option>
                                    <option value="Obat">Obat & Vitamin</option>
                                    <option value="Listrik">Listrik & Air</option>
                                    <option value="BBM">BBM & Solar</option>
                                    <option value="Gaji">Gaji Pegawai</option>
                                    <option value="Bibit">Bibit (Benur)</option>
                                    <option value="Lainnya">Lainnya...</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase ml-2">Tanggal</label>
                                <input type="date" name="tanggal_biaya" value="{{ date('Y-m-d') }}" required
                                    class="w-full mt-1 bg-slate-50 border-none rounded-2xl px-4 py-3 text-sm font-bold">
                            </div>
                        </div>

                        <div x-show="isLainnya" x-cloak class="bg-indigo-50 p-4 rounded-2xl">
                            <label class="text-[10px] font-black text-indigo-600 uppercase ml-2">Sebutkan
                                Kategori</label>
                            <input type="text" name="custom_kategori"
                                class="w-full mt-1 bg-white border-none rounded-xl px-4 py-3 text-sm font-bold">
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-2">Nama Item</label>
                            <input type="text" name="nama_item" required placeholder="Cth: Pelet Bintang 2 Sak"
                                class="w-full mt-1 bg-slate-50 border-none rounded-2xl px-4 py-3 text-sm font-bold">
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-2">Nominal (Rp)</label>
                            <input type="number" name="nominal" required placeholder="500000"
                                class="w-full mt-1 bg-slate-50 border-none rounded-2xl px-4 py-3 text-sm font-black text-slate-700">
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-2">Catatan /
                                Keterangan</label>
                            <input type="text" name="catatan" placeholder="Keterangan tambahan..."
                                class="w-full mt-1 bg-slate-50 border-none rounded-2xl px-4 py-3 text-sm font-bold">
                        </div>

                        <button type="submit"
                            class="w-full py-4 mt-2 bg-indigo-600 text-white rounded-2xl font-black text-[11px] uppercase shadow-xl shadow-indigo-600/30 hover:bg-indigo-700 transition-all">Simpan
                            Biaya</button>
                    </form>
                </div>
            </div>
        @endif

    </div>
</x-app-layout>