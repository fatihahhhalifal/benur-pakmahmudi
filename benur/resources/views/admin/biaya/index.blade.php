<x-app-layout>
    {{-- Pengunci State Jurnal Terintegrasi Hulu-Hilir --}}
    <div class="max-w-7xl mx-auto font-sans text-slate-800 antialiased" x-data="{
        tab: '{{ request()->query('tab', 'disetujui') }}',
        openModalTambahBOP: false,
        openModalEditBOP: false,
        bopKategori: '',
        searchQuery: '',
        editData: { id: '', siklus_id: '', kategori_bop: '', deskripsi: '', nominal: '' }
    }">

        {{-- HEADER SECTION --}}
        <div class="mb-6">
            <nav class="flex items-center gap-1.5 text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">
                <span>AQUAFARM CENTRUM</span>
                <i class="fa-solid fa-chevron-right text-[6px] text-slate-300"></i>
                <span class="text-blue-600">Jurnal Finansial Terpadu</span>
            </nav>
            <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Jurnal Finansial & BOP</h1>
        </div>

        {{-- PREMIUM ACCENT CHROMATIC GRADIENT PATTERN WIDGET CARDS --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            {{-- Card 1: Omzet Masuk --}}
            <div
                class="relative overflow-hidden bg-gradient-to-br from-emerald-500 via-teal-600 to-emerald-700 text-white rounded-3xl p-6 shadow-xl shadow-emerald-950/10 flex items-center justify-between group hover:scale-[1.01] transition-all duration-300">
                <div class="absolute inset-0 opacity-15 mix-blend-overlay pointer-events-none">
                    <svg viewBox="0 0 400 200" xmlns="http://www.w3.org/2000/svg" class="w-full h-full scale-125">
                        <path fill="#FFFFFF"
                            d="M0,128L80,117.3C160,107,320,85,480,96C640,107,800,149,960,160C1120,171,1280,149,1360,138.7L1440,128L1440,200L1360,200C1280,200,1120,200,960,200C800,200,640,200,480,200C320,200,160,200,80,200L0,200Z">
                        </path>
                    </svg>
                </div>
                <div class="absolute inset-0 bg-white/[0.06] backdrop-blur-md pointer-events-none"></div>
                <div class="space-y-1 relative z-10">
                    <span class="text-[10px] font-black text-emerald-100 uppercase tracking-wider block">Omzet Berjalan
                        (Masuk)</span>
                    <span class="text-2xl font-black tracking-tight block">Rp
                        {{ number_format($totalPendapatan, 0, ',', '.') }}</span>
                </div>
                <div
                    class="w-12 h-12 bg-white/15 text-white backdrop-blur-md rounded-2xl flex items-center justify-center text-lg shadow-lg border border-white/10 rotate-3 group-hover:rotate-0 transition-all duration-300 relative z-10">
                    <i class="fa-solid fa-money-bill-trend-up"></i>
                </div>
            </div>

            {{-- Card 2: Biaya Keluar --}}
            <div
                class="relative overflow-hidden bg-gradient-to-br from-orange-500 via-rose-500 to-pink-600 text-white rounded-3xl p-6 shadow-xl shadow-rose-950/10 flex items-center justify-between group hover:scale-[1.01] transition-all duration-300">
                <div class="absolute inset-0 opacity-15 mix-blend-overlay pointer-events-none">
                    <svg viewBox="0 0 400 200" xmlns="http://www.w3.org/2000/svg" class="w-full h-full scale-125">
                        <path fill="#FFFFFF"
                            d="M0,128L80,117.3C160,107,320,85,480,96C640,107,800,149,960,160C1120,171,1280,149,1360,138.7L1440,128L1440,200L1360,200C1280,200,1120,200,960,200C800,200,640,200,480,200C320,200,160,200,80,200L0,200Z">
                        </path>
                    </svg>
                </div>
                <div class="absolute inset-0 bg-white/[0.06] backdrop-blur-md pointer-events-none"></div>
                <div class="space-y-1 relative z-10">
                    <span class="text-[10px] font-black text-rose-100 uppercase tracking-wider block">Biaya Produksi
                        (Keluar)</span>
                    <span class="text-2xl font-black tracking-tight block">Rp
                        {{ number_format($totalPengeluaran, 0, ',', '.') }}</span>
                </div>
                <div
                    class="w-12 h-12 bg-white/15 text-white backdrop-blur-md rounded-2xl flex items-center justify-center text-lg shadow-lg border border-white/10 -rotate-3 group-hover:rotate-0 transition-all duration-300 relative z-10">
                    <i class="fa-solid fa-hand-holding-dollar"></i>
                </div>
            </div>

            {{-- Card 3: Laba Bersih Global --}}
            <div
                class="relative overflow-hidden bg-gradient-to-br {{ $saldoBersih >= 0 ? 'from-blue-600 via-indigo-600 to-purple-700' : 'from-slate-900 via-purple-950 to-rose-950' }} text-white rounded-3xl p-6 shadow-xl {{ $saldoBersih >= 0 ? 'shadow-indigo-950/20' : 'shadow-rose-950/30' }} flex items-center justify-between group hover:scale-[1.01] transition-all duration-300">
                <div class="absolute inset-0 opacity-15 mix-blend-overlay pointer-events-none">
                    <svg viewBox="0 0 400 200" xmlns="http://www.w3.org/2000/svg" class="w-full h-full scale-125">
                        <path fill="#FFFFFF"
                            d="M0,128L80,117.3C160,107,320,85,480,96C640,107,800,149,960,160C1120,171,1280,149,1360,138.7L1440,128L1440,200L1360,200C1280,200,1120,200,960,200C800,200,640,200,480,200C320,200,160,200,80,200L0,200Z">
                        </path>
                    </svg>
                </div>
                <div class="absolute inset-0 bg-white/[0.06] backdrop-blur-md pointer-events-none"></div>
                <div class="space-y-1 relative z-10">
                    <span
                        class="text-[10px] font-black {{ $saldoBersih >= 0 ? 'text-indigo-100' : 'text-rose-200' }} uppercase tracking-wider block">
                        {{ $saldoBersih >= 0 ? 'Laba Bersih Siklus Aktif' : 'Defisit Siklus Aktif' }}
                    </span>
                    <span class="text-2xl font-black tracking-tight block">Rp
                        {{ number_format($saldoBersih, 0, ',', '.') }}</span>
                </div>
                <div
                    class="w-12 h-12 bg-white/15 text-white backdrop-blur-md rounded-2xl flex items-center justify-center text-lg shadow-lg border border-white/10 group-hover:scale-105 transition-all duration-300 relative z-10">
                    <i class="fa-solid {{ $saldoBersih >= 0 ? 'fa-scale-balanced' : 'fa-circle-exclamation' }}"></i>
                </div>
            </div>
        </div>

        {{-- TAB NAVIGATION UTAMA --}}
        <div class="flex p-1 bg-slate-200/60 border border-slate-200/20 rounded-2xl mb-5 gap-1 max-w-2xl select-none">
            <button @click="tab = 'disetujui'"
                :class="tab === 'disetujui' ? 'bg-white text-blue-600 shadow-xs font-black' :
                    'text-slate-500 hover:text-slate-800 font-bold'"
                class="flex-1 py-2 text-[11px] uppercase tracking-wide rounded-xl transition-all duration-200 focus:outline-none">
                <i class="fa-solid fa-folder-open mr-1"></i> Disetujui
            </button>
            <button @click="tab = 'validasi'"
                :class="tab === 'validasi' ? 'bg-white text-blue-600 shadow-xs font-black' :
                    'text-slate-500 hover:text-slate-800 font-bold'"
                class="flex-1 py-2 text-[11px] uppercase tracking-wide rounded-xl transition-all duration-200 focus:outline-none relative">
                <i class="fa-solid fa-hourglass-half mr-1"></i> Validasi / Antrean
                @if (count($bop_pending) > 0)
                    <span
                        class="absolute top-2 right-3 w-4 h-4 bg-rose-500 text-white text-[9px] flex items-center justify-center font-black rounded-full border border-white">{{ count($bop_pending) }}</span>
                @endif
            </button>
            <button @click="tab = 'arsip'"
                :class="tab === 'arsip' ? 'bg-white text-blue-600 shadow-xs font-black' :
                    'text-slate-500 hover:text-slate-800 font-bold'"
                class="flex-1 py-2 text-[11px] uppercase tracking-wide rounded-xl transition-all duration-200 focus:outline-none">
                <i class="fa-solid fa-box-archive mr-1"></i> Arsip
            </button>
        </div>

        {{-- TOOLBAR CONTROL SECTION --}}
        <div class="mb-5 flex flex-col sm:flex-row gap-3 items-center justify-between">
            <div class="relative w-full sm:w-80">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400"><i
                        class="fa-solid fa-magnifying-glass text-xs"></i></span>
                <input type="text" x-model="searchQuery" placeholder="Cari deskripsi, kategori, atau nama kolam..."
                    class="w-full bg-white border border-slate-200 text-slate-800 rounded-xl pl-9 pr-4 py-2 text-xs font-medium focus:border-blue-500 focus:ring-1 focus:ring-blue-500 placeholder-slate-400 shadow-xs transition-colors">
            </div>

            @if (in_array(Auth::user()->role, ['admin', 'operator']))
                <button @click="openModalTambahBOP = true"
                    class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-xs font-black shadow-xs flex items-center justify-center gap-1.5 transition-all active:scale-98 tracking-wider">
                    <i class="fa-solid fa-square-plus text-[11px]"></i> AJUKAN BOP LAPANGAN BARU
                </button>
            @endif
        </div>

        {{-- MAIN CONTENT CARD --}}
        <div class="bg-white rounded-2xl shadow-xs border border-slate-100 overflow-hidden p-5 md:p-6">

            {{-- TAB 1: DATA DISETUJUI (SAH) --}}
            <div x-show="tab === 'disetujui'" x-transition class="animate-fade-in">
                <div class="overflow-x-auto min-w-full">
                    <table class="w-full text-left border-collapse min-w-[950px]">
                        <thead>
                            <tr
                                class="text-[10px] font-black text-slate-400 uppercase tracking-wider border-b border-slate-100 pb-2">
                                <th class="p-3 text-center w-12">No</th>
                                <th class="p-3 w-44">Alokasi Kolam</th>
                                <th class="p-3 w-40">Kategori Akun</th>
                                <th class="p-3 w-52">Kategori BOP</th>
                                <th class="p-3">Detail Deskripsi Komponen</th>
                                <th class="p-3 text-right w-36">Nominal</th>
                                <th class="p-3 text-center w-24">Operasional</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 text-xs text-slate-700 font-semibold">
                            @forelse($bop_aktif as $index => $ba)
                                <tr class="hover:bg-slate-50/40 transition-colors"
                                    x-show="searchQuery === '' || '{{ strtolower($ba->deskripsi) }}'.includes(searchQuery.toLowerCase()) || '{{ strtolower($ba->nama_kolam) }}'.includes(searchQuery.toLowerCase()) || '{{ strtolower($ba->kategori_bop) }}'.includes(searchQuery.toLowerCase())">
                                    <td class="p-3 text-center text-slate-400 font-medium">{{ $index + 1 }}</td>
                                    <td class="p-3">
                                        <div class="flex flex-col">
                                            <span
                                                class="text-slate-900 font-black text-sm uppercase">{{ $ba->nama_kolam ?? 'ALOKASI GLOBAL' }}</span>
                                            <span class="text-[9px] text-slate-400 font-bold mt-0.5">
                                                @if ($ba->waktu_tabur)
                                                    Tabur:
                                                    {{ \Carbon\Carbon::parse($ba->waktu_tabur)->format('d/m/y') }}
                                                @else
                                                    Tercatat:
                                                    {{ \Carbon\Carbon::parse($ba->waktu_pencatatan)->format('d/m/y') }}
                                                @endif
                                            </span>
                                        </div>
                                    </td>
                                    <td class="p-3">
                                        <span
                                            class="px-2 py-0.5 rounded text-[10px] font-black border uppercase {{ $ba->jenis_arus === 'MASUK' ? 'bg-emerald-50 text-emerald-700 border-emerald-200/60' : 'bg-blue-50 text-blue-700 border-blue-200/60' }}">
                                            {{ $ba->kategori_akun }}
                                        </span>
                                    </td>
                                    <td class="p-3"><span
                                            class="text-slate-700 font-black uppercase text-[11px]">{{ $ba->kategori_bop }}</span>
                                    </td>
                                    <td
                                        class="p-3 uppercase text-slate-800 tracking-tight font-extrabold break-words whitespace-normal">
                                        {{ $ba->deskripsi }}</td>
                                    <td
                                        class="p-3 text-right font-black text-sm {{ $ba->jenis_arus === 'MASUK' ? 'text-emerald-600' : 'text-rose-600' }}">
                                        {{ $ba->jenis_arus === 'MASUK' ? '+' : '-' }} Rp
                                        {{ number_format($ba->nominal_biaya, 0, ',', '.') }}
                                    </td>
                                    <td class="p-3 text-center">
                                        @if ($ba->jenis_arus === 'KELUAR' && Auth::user()->role === 'admin')
                                            <div class="flex items-center justify-center gap-3">
                                                <button
                                                    @click="openModalEditBOP = true; editData = { id: '{{ $ba->id }}', siklus_id: '{{ $ba->siklus_id }}', kategori_bop: '{{ $ba->kategori_bop }}', deskripsi: '{{ $ba->deskripsi }}', nominal: '{{ $ba->nominal_biaya }}' }"
                                                    class="text-slate-400 hover:text-blue-600 transition-colors focus:outline-none"
                                                    title="Koreksi Data"><i
                                                        class="fa-solid fa-pen-to-square text-sm"></i></button>
                                                <form action="{{ route('kolam.bop.destroy', $ba->id) }}"
                                                    method="POST"
                                                    onsubmit="return confirm('Hapus pengeluaran jurnal sah ini?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit"
                                                        class="text-slate-300 hover:text-rose-600 transition-colors focus:outline-none"
                                                        title="Hapus Item"><i
                                                            class="fa-solid fa-trash-can text-sm"></i></button>
                                                </form>
                                            </div>
                                        @else
                                            <i class="fa-solid fa-circle-check text-[11px] text-emerald-500"
                                                title="Transaksi Sah & Terkunci"></i>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="p-8 text-center text-slate-400 italic">Tidak ada
                                        transaksi operasional berjalan yang berstatus disetujui.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- TAB 2: DATA VALIDASI / ANTREAN (PENDING PENGURUSAN OPERATOR) --}}
            <div x-show="tab === 'validasi'" x-transition x-cloak class="animate-fade-in">
                <div class="overflow-x-auto min-w-full">
                    <table class="w-full text-left border-collapse min-w-[950px]">
                        <thead>
                            <tr
                                class="text-[10px] font-black text-slate-400 uppercase tracking-wider border-b border-slate-100 pb-2">
                                <th class="p-3 text-center w-12">No</th>
                                <th class="p-3 w-44">Target Sasaran</th>
                                <th class="p-3 w-52">Kategori Anggaran</th>
                                <th class="p-3">Rincian Deskripsi Pengajuan</th>
                                <th class="p-3 text-right w-36">Nominal Pengajuan</th>
                                <th class="p-3 text-center w-40">Opsi Tindakan Admin</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 text-xs text-slate-700 font-semibold">
                            @forelse($bop_pending as $index => $bp)
                                <tr class="bg-amber-50/10 hover:bg-amber-50/30 transition-colors">
                                    <td class="p-3 text-center text-amber-500 font-medium">{{ $index + 1 }}</td>
                                    <td class="p-3 uppercase font-black text-slate-900">{{ $bp->nama_kolam }}</td>
                                    <td class="p-3 uppercase text-slate-700 font-extrabold">{{ $bp->kategori_bop }}
                                    </td>
                                    <td class="p-3 uppercase text-slate-600 font-medium break-words whitespace-normal">
                                        {{ $bp->deskripsi }}</td>
                                    <td class="p-3 text-right font-black text-sm text-amber-600">Rp
                                        {{ number_format($bp->nominal_biaya, 0, ',', '.') }}</td>
                                    <td class="p-3 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            @if (Auth::user()->role === 'admin')
                                                <a href="{{ route('kolam.bop.acc', $bp->id) }}"
                                                    class="inline-flex items-center bg-emerald-600 hover:bg-emerald-700 text-white px-2.5 py-1 rounded-md text-[10px] font-black uppercase tracking-wider transition-all"><i
                                                        class="fa-solid fa-check mr-0.5"></i> SETUJUI</a>
                                                <button
                                                    @click="openModalEditBOP = true; editData = { id: '{{ $bp->id }}', siklus_id: '{{ $bp->siklus_id }}', kategori_bop: '{{ $bp->kategori_bop }}', deskripsi: '{{ $bp->deskripsi }}', nominal: '{{ $bp->nominal_biaya }}' }"
                                                    class="bg-amber-500 hover:bg-amber-600 text-white px-2.5 py-1 rounded-md text-[10px] font-black uppercase tracking-wider transition-all"><i
                                                        class="fa-solid fa-pen-to-square mr-0.5"></i> EDIT</button>
                                            @else
                                                <span
                                                    class="bg-amber-50 text-amber-800 text-[8px] font-black px-2 py-0.5 rounded border border-amber-200 uppercase select-none">⏳
                                                    Menunggu Admin</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-8 text-center text-slate-400 italic">Bersih! Tidak ada
                                        antrean pengajuan log biaya tertahan saat ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- TAB 3: DATA ARSIP SEJARAH LAMA (TUTUP BUKU SIKLUS) --}}
            <div x-show="tab === 'arsip'" x-transition x-cloak class="animate-fade-in">
                <div class="overflow-x-auto min-w-full">
                    <table class="w-full text-left border-collapse min-w-[950px]">
                        <thead>
                            <tr
                                class="text-[10px] font-black text-slate-400 uppercase tracking-wider border-b border-slate-100 pb-2">
                                <th class="p-3 text-center w-12">No</th>
                                <th class="p-3 w-44">Arsip Riwayat Siklus</th>
                                <th class="p-3 w-40">Kategori Akun</th>
                                <th class="p-3 w-52">Kategori BOP</th>
                                <th class="p-3">Detail Deskripsi Kas Sejarah</th>
                                <th class="p-3 text-right w-36">Nominal</th>
                                <th class="p-3 text-center w-24">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 text-xs text-slate-700 font-semibold">
                            @forelse($bop_arsip as $index => $bar)
                                <tr class="bg-slate-50/20 hover:bg-slate-50/60 transition-colors"
                                    x-show="searchQuery === '' || '{{ strtolower($bar->deskripsi) }}'.includes(searchQuery.toLowerCase()) || '{{ strtolower($bar->nama_kolam) }}'.includes(searchQuery.toLowerCase()) || '{{ strtolower($bar->kategori_bop) }}'.includes(searchQuery.toLowerCase())">
                                    <td class="p-3 text-center text-slate-400 font-medium">{{ $index + 1 }}</td>
                                    <td class="p-3">
                                        <div class="flex flex-col">
                                            <span
                                                class="text-slate-500 font-extrabold text-sm uppercase">{{ $bar->nama_kolam }}</span>
                                            <span class="text-[9px] text-slate-400 font-bold mt-0.5">Siklus:
                                                {{ \Carbon\Carbon::parse($bar->waktu_tabur)->format('d/m/y') }} -
                                                {{ \Carbon\Carbon::parse($bar->waktu_kuras)->format('d/m/y') }}</span>
                                        </div>
                                    </td>
                                    <td class="p-3">
                                        <span
                                            class="px-2 py-0.5 rounded text-[10px] font-black border uppercase border-slate-200 bg-slate-100 text-slate-500">{{ $bar->kategori_akun }}</span>
                                    </td>
                                    <td class="p-3 text-slate-500 font-black uppercase text-[11px]">
                                        {{ $bar->kategori_bop }}</td>
                                    <td
                                        class="p-3 uppercase text-slate-500 font-medium tracking-tight break-words whitespace-normal">
                                        {{ $bar->deskripsi }}</td>
                                    <td
                                        class="p-3 text-right font-black text-sm {{ $bar->jenis_arus === 'MASUK' ? 'text-emerald-700/70' : 'text-rose-700/70' }}">
                                        {{ $bar->jenis_arus === 'MASUK' ? '+' : '-' }} Rp
                                        {{ number_format($bar->nominal_biaya, 0, ',', '.') }}
                                    </td>
                                    <td class="p-3 text-center">
                                        <span
                                            class="bg-slate-100 text-slate-400 px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-wider border border-slate-200/50 select-none"><i
                                                class="fa-solid fa-lock text-[8px] mr-0.5"></i> Locked</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="p-8 text-center text-slate-400 italic">Belum ada riwayat
                                        siklus kolam masa lalu yang tersimpan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- MODAL 1: AJUKAN REKAM DATA BOP BARU --}}
        @if (in_array(Auth::user()->role, ['admin', 'operator']))
            <div x-show="openModalTambahBOP"
                class="fixed inset-0 z-[150] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
                x-cloak x-transition>
                <div @click.away="openModalTambahBOP = false"
                    class="bg-white w-full max-w-sm rounded-2xl p-5 shadow-xl">
                    <h2 class="text-base font-extrabold text-slate-800 mb-3 uppercase tracking-tight">Form Input
                        Kebutuhan BOP</h2>
                    <form action="{{ route('kolam.bop') }}" method="POST" class="space-y-3">
                        @csrf
                        <div>
                            <label class="text-[9px] font-bold text-slate-400 uppercase">Pilih Alokasi Sasaran Kolam
                                <span class="text-rose-500">*</span></label>
                            <select name="siklus_id" required
                                class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-black text-slate-800 focus:outline-none">
                                <option value="global" selected>🌐 ALOKASIKAN KE SELURUH KOLAM AKTIF</option>
                                @foreach ($kolam_aktif as $ka)
                                    <option value="{{ $ka->siklus_id }}">Kolam Spesifik: {{ $ka->nama_kolam }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-[9px] font-bold text-slate-400 uppercase">Pilih Kategori BOP <span
                                    class="text-rose-500">*</span></label>
                            <select name="keterangan" x-model="bopKategori" required
                                class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-bold text-slate-800 focus:outline-none">
                                <option value="" disabled selected>-- Pilih Kategori BOP --</option>
                                <option value="Pakan">Pakan</option>
                                <option value="Vitamin & Suplemen Imun">Vitamin & Suplemen Imun</option>
                                <option value="Obat & Desinfektan Kolam">Obat & Desinfektan Kolam</option>
                                <option value="Bahan Kimia & Pengkondisi Air">Bahan Kimia & Pengkondisi Air</option>
                                <option value="Energi Listrik & Pompa">Energi Listrik & Pompa</option>
                                <option value="Gaji Tenaga Kerja Lapangan">Gaji Tenaga Kerja Lapangan</option>
                                <option value="Lain-lain">Lain-lain</option>
                            </select>
                        </div>
                        <div x-show="bopKategori !== ''" x-transition>
                            <label class="text-[9px] font-bold text-slate-400 uppercase">Detail Deskripsi Komponen
                                <span class="text-rose-500">*</span></label>
                            <input type="text" name="keterangan_lain" :required="bopKategori !== ''"
                                placeholder="Contoh: Pembelian Merk Artemia A"
                                class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-3 py-1.5 text-xs font-bold text-slate-800 focus:outline-none">
                        </div>
                        <div>
                            <label class="text-[9px] font-bold text-slate-400 uppercase">Nominal Nilai Anggaran (Rp)
                                <span class="text-rose-500">*</span></label>
                            <input type="number" name="nominal" required placeholder="Contoh: 1500000"
                                class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-3 py-1.5 text-xs font-black text-slate-800 focus:outline-none">
                        </div>

                        <div class="flex gap-2 pt-1.5">
                            <button type="button" @click="openModalTambahBOP = false"
                                class="flex-1 py-2 bg-slate-100 text-slate-500 rounded-lg font-bold text-xs">Batal</button>
                            <button type="submit"
                                class="flex-1 py-2 bg-blue-600 text-white rounded-lg font-black text-xs shadow-sm uppercase tracking-wider">
                                {{ Auth::user()->role === 'admin' ? 'Terbitkan Sah' : 'Ajukan ke Admin' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        {{-- MODAL 2: EDIT KOREKSI REKAM LOG DATA BOP (Khusus Akses Admin Otoritas) --}}
        @if (Auth::user()->role === 'admin')
            <div x-show="openModalEditBOP"
                class="fixed inset-0 z-[150] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
                x-cloak x-transition>
                <div @click.away="openModalEditBOP = false"
                    class="bg-white w-full max-w-sm rounded-2xl p-5 shadow-xl">
                    <h2 class="text-base font-extrabold text-slate-800 mb-3 uppercase tracking-tight">Koreksi Log
                        Jurnal BOP</h2>
                    <form
                        :action="'{{ route('kolam.bop.update', ['id' => 'PLACEHOLDER_ID']) }}'.replace('PLACEHOLDER_ID',
                            editData.id)"
                        method="POST" class="space-y-3">
                        @csrf @method('PUT')
                        <div>
                            <label class="text-[9px] font-bold text-slate-400 uppercase">Kolam Sasaran Terkunci</label>
                            <select name="siklus_id" required x-model="editData.siklus_id"
                                class="w-full mt-1 bg-slate-100 border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-black text-slate-500 pointer-events-none focus:outline-none">
                                @foreach ($kolam_aktif as $ka)
                                    <option value="{{ $ka->siklus_id }}">Kelompok: {{ $ka->nama_kolam }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-[9px] font-bold text-slate-400 uppercase">Kategori BOP <span
                                    class="text-rose-500">*</span></label>
                            <select name="keterangan" x-model="editData.kategori_bop" required
                                class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-bold text-slate-800 focus:outline-none">
                                <option value="Pakan">Pakan</option>
                                <option value="Vitamin & Suplemen Imun">Vitamin & Suplemen Imun</option>
                                <option value="Obat & Desinfektan Kolam">Obat & Desinfektan Kolam</option>
                                <option value="Bahan Kimia & Pengkondisi Air">Bahan Kimia & Pengkondisi Air</option>
                                <option value="Energi Listrik & Pompa">Energi Listrik & Pompa</option>
                                <option value="Gaji Tenaga Kerja Lapangan">Gaji Tenaga Kerja Lapangan</option>
                                <option value="Lain-lain">Lain-lain</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[9px] font-bold text-slate-400 uppercase">Koreksi Rincian Deskripsi
                                <span class="text-rose-500">*</span></label>
                            <input type="text" name="keterangan_lain" required x-model="editData.deskripsi"
                                class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-3 py-1.5 text-xs font-bold text-slate-800 focus:outline-none">
                        </div>
                        <div>
                            <label class="text-[9px] font-bold text-slate-400 uppercase">Koreksi Nominal Dana Keluar
                                (Rp) <span class="text-rose-500">*</span></label>
                            <input type="number" name="nominal" required x-model="editData.nominal"
                                class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-3 py-1.5 text-xs font-black text-slate-800 focus:outline-none">
                        </div>
                        <div class="flex gap-2 pt-1.5">
                            <button type="button" @click="openModalEditBOP = false"
                                class="flex-1 py-2 bg-slate-100 text-slate-500 rounded-lg font-bold text-xs">Batal</button>
                            <button type="submit"
                                class="flex-1 py-2 bg-blue-600 text-white rounded-lg font-black text-xs shadow-sm uppercase tracking-wider">Simpan
                                & Setujui Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

    </div>

    <style>
        [x-cloak] {
            display: none !important;
        }

        .animate-fade-in {
            animation: fIn 0.25s ease-out forwards;
        }

        @keyframes fIn {
            from {
                opacity: 0;
                transform: translateY(3px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</x-app-layout>
