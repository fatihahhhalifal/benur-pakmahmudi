<x-app-layout>
    <div class="max-w-7xl mx-auto font-sans text-slate-800 antialiased" x-data="{
        currentFilter: 'semua',
        startDate: '',
        endDate: '',
        filterKategori: 'semua',
        filterBulan: 'semua',
        openPreview: false,
        previewUrl: '',
        previewTitle: '',
        buildQueryString() {
            let params = new URLSearchParams();
            if (this.startDate) params.append('start', this.startDate);
            if (this.endDate) params.append('end', this.endDate);
            if (this.filterBulan !== 'semua') params.append('bulan', this.filterBulan);
            if (this.filterKategori !== 'semua') params.append('kategori', this.filterKategori);
            if (this.currentFilter !== 'semua') params.append('jenis', this.currentFilter);
            let qs = params.toString();
            return qs ? '?' + qs : '';
        }
    }">

        <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4 border-b border-slate-100 pb-5">
            <div>
                <nav class="flex items-center gap-1.5 text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">
                    <span>AQUAFARM</span>
                    <i class="fa-solid fa-chevron-right text-[6px] text-slate-300"></i>
                    <span class="text-blue-600">Laporan Keuangan</span>
                </nav>
                <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight leading-none">Laporan Keuangan</h1>
            </div>

            <div class="flex flex-wrap gap-2">
                <button
                    @click="openPreview = true; previewUrl = '{{ route('laporan.cetak.masuk') }}' + buildQueryString(); previewTitle = 'Laporan Pemasukan';"
                    class="bg-white hover:bg-slate-50 text-slate-700 text-[10px] font-black uppercase tracking-wider px-3 py-2 rounded-xl border transition-all flex items-center gap-1.5 shadow-2xs focus:outline-none">
                    <i class="fa-solid fa-print text-emerald-600"></i> Cetak Pemasukan
                </button>
                <button
                    @click="openPreview = true; previewUrl = '{{ route('laporan.cetak.keluar') }}' + buildQueryString(); previewTitle = 'Laporan Pengeluaran';"
                    class="bg-white hover:bg-slate-50 text-slate-700 text-[10px] font-black uppercase tracking-wider px-3 py-2 rounded-xl border transition-all flex items-center gap-1.5 shadow-2xs focus:outline-none">
                    <i class="fa-solid fa-print text-rose-600"></i> Cetak Pengeluaran
                </button>
                <button
                    @click="openPreview = true; previewUrl = '{{ route('laporan.cetak.semua') }}' + buildQueryString(); previewTitle = 'Laporan Keuangan Lengkap';"
                    class="bg-slate-900 hover:bg-slate-800 text-white text-[10px] font-black uppercase tracking-widest px-4 py-2 rounded-xl border border-slate-950 transition-all flex items-center gap-1.5 shadow-sm focus:outline-none">
                    <i class="fa-solid fa-file-pdf"></i> Cetak Semua
                </button>
            </div>
        </div>

        {{-- 3 KARTU RINGKASAN --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="relative overflow-hidden bg-gradient-to-br from-emerald-500 via-teal-600 to-emerald-700 text-white rounded-3xl p-6 shadow-xl shadow-emerald-950/10 flex items-center justify-between group hover:scale-[1.01] transition-all duration-300">
                <div class="absolute inset-0 opacity-15 mix-blend-overlay pointer-events-none">
                    <svg viewBox="0 0 400 200" xmlns="http://www.w3.org/2000/svg" class="w-full h-full scale-125">
                        <path fill="#FFFFFF" d="M0,128L80,117.3C160,107,320,85,480,96C640,107,800,149,960,160C1120,171,1280,149,1360,138.7L1440,128L1440,200L1360,200C1280,200,1120,200,960,200C800,200,640,200,480,200C320,200,160,200,80,200L0,200Z"></path>
                    </svg>
                </div>
                <div class="absolute inset-0 bg-white/[0.06] backdrop-blur-md pointer-events-none"></div>
                <div class="space-y-1 relative z-10">
                    <span class="text-[10px] font-black text-emerald-100 uppercase tracking-wider block">Total Pemasukan</span>
                    <span class="text-2xl font-black tracking-tight block">Rp {{ number_format($totalPendapatan, 0, ',', '.') }}</span>
                </div>
                <div class="w-12 h-12 bg-white/15 text-white backdrop-blur-md rounded-2xl flex items-center justify-center text-lg shadow-lg border border-white/10 rotate-3 group-hover:rotate-0 transition-all duration-300 relative z-10">
                    <i class="fa-solid fa-money-bill-trend-up"></i>
                </div>
            </div>

            <div class="relative overflow-hidden bg-gradient-to-br from-orange-500 via-rose-500 to-pink-600 text-white rounded-3xl p-6 shadow-xl shadow-rose-950/10 flex items-center justify-between group hover:scale-[1.01] transition-all duration-300">
                <div class="absolute inset-0 opacity-15 mix-blend-overlay pointer-events-none">
                    <svg viewBox="0 0 400 200" xmlns="http://www.w3.org/2000/svg" class="w-full h-full scale-125">
                        <path fill="#FFFFFF" d="M0,128L80,117.3C160,107,320,85,480,96C640,107,800,149,960,160C1120,171,1280,149,1360,138.7L1440,128L1440,200L1360,200C1280,200,1120,200,960,200C800,200,640,200,480,200C320,200,160,200,80,200L0,200Z"></path>
                    </svg>
                </div>
                <div class="absolute inset-0 bg-white/[0.06] backdrop-blur-md pointer-events-none"></div>
                <div class="space-y-1 relative z-10">
                    <span class="text-[10px] font-black text-rose-100 uppercase tracking-wider block">Total Pengeluaran</span>
                    <span class="text-2xl font-black tracking-tight block">Rp {{ number_format($totalPengeluaran, 0, ',', '.') }}</span>
                </div>
                <div class="w-12 h-12 bg-white/15 text-white backdrop-blur-md rounded-2xl flex items-center justify-center text-lg shadow-lg border border-white/10 -rotate-3 group-hover:rotate-0 transition-all duration-300 relative z-10">
                    <i class="fa-solid fa-hand-holding-dollar"></i>
                </div>
            </div>

            <div class="relative overflow-hidden bg-gradient-to-br {{ $keuntunganBersih >= 0 ? 'from-blue-600 via-indigo-600 to-purple-700' : 'from-slate-900 via-purple-950 to-rose-950' }} text-white rounded-3xl p-6 shadow-xl shadow-indigo-950/20 flex items-center justify-between group hover:scale-[1.01] transition-all duration-300">
                <div class="absolute inset-0 opacity-15 mix-blend-overlay pointer-events-none">
                    <svg viewBox="0 0 400 200" xmlns="http://www.w3.org/2000/svg" class="w-full h-full scale-125">
                        <path fill="#FFFFFF" d="M0,128L80,117.3C160,107,320,85,480,96C640,107,800,149,960,160C1120,171,1280,149,1360,138.7L1440,128L1440,200L1360,200C1280,200,1120,200,960,200C800,200,640,200,480,200C320,200,160,200,80,200L0,200Z"></path>
                    </svg>
                </div>
                <div class="absolute inset-0 bg-white/[0.06] backdrop-blur-md pointer-events-none"></div>
                <div class="space-y-1 relative z-10">
                    <span class="text-[10px] font-black text-indigo-100 uppercase tracking-wider block">
                        {{ $keuntunganBersih >= 0 ? 'Keuntungan Bersih' : 'Kerugian Bersih' }}
                    </span>
                    <span class="text-2xl font-black tracking-tight block">Rp {{ number_format(abs($keuntunganBersih), 0, ',', '.') }}</span>
                </div>
                <div class="w-12 h-12 bg-white/15 text-white backdrop-blur-md rounded-2xl flex items-center justify-center text-lg shadow-lg border border-white/10 group-hover:scale-105 transition-all duration-300 relative z-10">
                    <i class="fa-solid fa-scale-balanced"></i>
                </div>
            </div>
        </div>

        {{-- BARIS FILTER --}}
        <div class="flex flex-col md:flex-row justify-between items-end gap-4 mb-5">
            <div class="flex border border-slate-200 bg-white rounded-xl p-1.5 shadow-xs gap-1 w-full md:w-auto select-none">
                <button @click="currentFilter = 'semua'"
                    :class="currentFilter === 'semua' ? 'bg-slate-900 text-white font-black' : 'text-slate-500 font-bold hover:bg-slate-50'"
                    class="flex-1 md:px-6 text-center text-xs py-2 rounded-lg transition-all focus:outline-none">Semua</button>
                <button @click="currentFilter = 'MASUK'"
                    :class="currentFilter === 'MASUK' ? 'bg-emerald-600 text-white font-black' : 'text-slate-500 font-bold hover:bg-slate-50'"
                    class="flex-1 md:px-6 text-center text-xs py-2 rounded-lg transition-all focus:outline-none">Pemasukan</button>
                <button @click="currentFilter = 'KELUAR'"
                    :class="currentFilter === 'KELUAR' ? 'bg-rose-600 text-white font-black' : 'text-slate-500 font-bold hover:bg-slate-50'"
                    class="flex-1 md:px-6 text-center text-xs py-2 rounded-lg transition-all focus:outline-none">Pengeluaran</button>
            </div>

            <div class="flex flex-wrap items-end gap-2 w-full md:w-auto">
                <div class="flex flex-col">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1">Kategori</label>
                    <select x-model="filterKategori"
                        class="text-xs border border-slate-200 rounded-lg p-2.5 text-slate-700 bg-white shadow-sm outline-none focus:border-slate-400 focus:ring-0 w-40">
                        <option value="semua">Semua Kategori</option>
                        @foreach($arusKas->pluck('pos_akun')->filter()->unique()->sort()->values() as $kat)
                            <option value="{{ $kat }}">{{ $kat }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex flex-col">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1">Bulan</label>
                    <select x-model="filterBulan"
                        class="text-xs border border-slate-200 rounded-lg p-2.5 text-slate-700 bg-white shadow-sm outline-none focus:border-slate-400 focus:ring-0 w-40">
                        <option value="semua">Semua Bulan</option>
                        @foreach($arusKas->filter(fn($r) => $r->tanggal)->map(fn($r) => \Carbon\Carbon::parse($r->tanggal)->format('Y-m'))->unique()->sort()->values() as $bln)
                            <option value="{{ $bln }}">{{ \Carbon\Carbon::createFromFormat('Y-m', $bln)->translatedFormat('F Y') }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex flex-col">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1">Dari Tanggal</label>
                    <input type="date" x-model="startDate"
                        class="text-xs border border-slate-200 rounded-lg p-2.5 text-slate-700 bg-white shadow-sm outline-none focus:border-slate-400 focus:ring-0 w-40">
                </div>
                <div class="flex flex-col">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1">Sampai Tanggal</label>
                    <input type="date" x-model="endDate"
                        class="text-xs border border-slate-200 rounded-lg p-2.5 text-slate-700 bg-white shadow-sm outline-none focus:border-slate-400 focus:ring-0 w-40">
                </div>

                <div class="flex flex-col justify-end pb-0.5">
                    <button
                        @click="startDate = ''; endDate = ''; filterKategori = 'semua'; filterBulan = 'semua'; currentFilter = 'semua';"
                        class="bg-slate-100 hover:bg-slate-200 text-slate-600 p-2.5 rounded-lg transition-colors shadow-sm focus:outline-none"
                        title="Reset Semua Filter">
                        <i class="fa-solid fa-rotate-right"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- TABEL DATA --}}
        <div class="bg-white rounded-2xl shadow-xs border border-slate-100 overflow-hidden p-5 md:p-6">
            <div class="overflow-x-auto min-w-full">
                <table class="w-full text-left border-collapse min-w-[900px]">
                    <thead>
                        <tr class="text-[10px] font-black text-slate-400 uppercase tracking-wider border-b border-slate-100 pb-2">
                            <th class="p-3 text-center w-12">No</th>
                            <th class="p-3 w-48">Tanggal</th>
                            <th class="p-3 w-40">Kolam</th>
                            <th class="p-3 w-48">Kategori</th>
                            <th class="p-3">Keterangan</th>
                            <th class="p-3 text-right w-40">Nominal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 text-xs text-slate-700 font-semibold">
                        @forelse($arusKas as $index => $row)
                            <tr class="hover:bg-slate-50/40 transition-colors animate-fade-in"
                                x-data="{ rowDate: '{{ $row->tanggal ? \Carbon\Carbon::parse($row->tanggal)->format('Y-m-d') : '' }}', rowBulan: '{{ $row->tanggal ? \Carbon\Carbon::parse($row->tanggal)->format('Y-m') : '' }}' }"
                                x-show="
                                    (currentFilter === 'semua' || currentFilter === '{{ $row->jenis_arus }}') &&
                                    (!startDate || rowDate >= startDate) &&
                                    (!endDate || rowDate <= endDate) &&
                                    (filterKategori === 'semua' || filterKategori === '{{ addslashes($row->pos_akun) }}') &&
                                    (filterBulan === 'semua' || filterBulan === rowBulan)
                                "
                                x-cloak>
                                <td class="p-3 text-center text-slate-400 font-medium">{{ $index + 1 }}</td>
                                <td class="p-3 text-slate-400 font-medium">
                                    {{ \Carbon\Carbon::parse($row->tanggal)->translatedFormat('d M Y, H:i') }} WIB
                                </td>
                                <td class="p-3 font-black text-slate-900 uppercase">{{ $row->nama_kolam }}</td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 rounded-xl text-[9px] font-black border uppercase tracking-wide block w-fit shadow-2xs {{ $row->jenis_arus === 'MASUK' ? 'bg-emerald-50 text-emerald-700 border-emerald-200/60' : 'bg-slate-50 text-slate-600 border-slate-200' }}">
                                        {{ $row->pos_akun }}
                                    </span>
                                </td>
                                <td class="p-3 uppercase text-slate-800 tracking-tight font-extrabold break-words whitespace-normal">
                                    {{ $row->rincian }}
                                </td>
                                <td class="p-3 text-right font-black text-sm {{ $row->jenis_arus === 'MASUK' ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ $row->jenis_arus === 'MASUK' ? '+' : '-' }} Rp
                                    {{ number_format($row->nominal, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-8 text-center text-slate-400 italic">Belum ada data keuangan yang tercatat.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4 pt-4 border-t border-slate-50 flex justify-center text-[10px] text-slate-400 font-black tracking-widest uppercase select-none">
                <span>Menampilkan data keuangan yang tercatat di sistem sesuai rentang saringan.</span>
            </div>
        </div>

        {{-- MODAL PREVIEW CETAK --}}
        <div x-show="openPreview"
            class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md"
            x-cloak x-transition>
            <div @click.away="openPreview = false"
                class="bg-white w-full max-w-5xl h-[88vh] rounded-3xl shadow-2xl flex flex-col overflow-hidden border border-slate-100 animate-fade-in">
                <div class="bg-slate-900 px-6 py-4 flex items-center justify-between text-white select-none">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-white/10 rounded-xl flex items-center justify-center">
                            <i class="fa-solid fa-file-invoice-dollar text-sm text-blue-400"></i>
                        </div>
                        <div>
                            <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">Pratinjau Dokumen</span>
                            <h3 class="text-xs font-black tracking-tight uppercase" x-text="previewTitle"></h3>
                        </div>
                    </div>
                    <div class="flex items-center gap-2.5">
                        <button onclick="document.getElementById('frameReport').contentWindow.print();"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-black text-[10px] uppercase tracking-widest px-4 py-2 rounded-xl shadow-md transition-all flex items-center gap-1.5 focus:outline-none">
                            <i class="fa-solid fa-print"></i> Cetak / Unduh PDF
                        </button>
                        <button @click="openPreview = false"
                            class="bg-white/10 hover:bg-white/20 text-slate-300 hover:text-white p-2 rounded-xl transition-all focus:outline-none">
                            <i class="fa-solid fa-xmark text-sm px-0.5"></i>
                        </button>
                    </div>
                </div>
                <div class="flex-1 bg-slate-100 p-4">
                    <iframe id="frameReport" :src="previewUrl"
                        class="w-full h-full rounded-2xl border border-slate-200/60 shadow-inner bg-white"></iframe>
                </div>
            </div>
        </div>
    </div>

    <style>
        [x-cloak] { display: none !important; }
        .animate-fade-in { animation: fIn 0.2s ease-out forwards; }
        @keyframes fIn {
            from { opacity: 0; transform: translateY(2px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</x-app-layout>