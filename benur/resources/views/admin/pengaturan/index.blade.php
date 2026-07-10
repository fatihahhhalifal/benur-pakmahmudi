<x-app-layout>
    {{-- Pengunci State Tab Terakhir Via Session Flash Controller & Browser Memory --}}
    <div class="max-w-7xl mx-auto font-sans text-slate-800 antialiased" x-data="{
        tab: '{{ session('last_tab') ?? 'profil' }}',
        kriteriaSub: '{{ session('last_sub_tab') ?? 'jenis' }}',
        openModalJenis: {{ $errors->any() && session('last_tab') === 'kriteria' && session('last_sub_tab') === 'jenis' ? 'true' : 'false' }},
        openModalUkuran: {{ $errors->any() && session('last_tab') === 'kriteria' && session('last_sub_tab') === 'ukuran' ? 'true' : 'false' }},
        openModalGrade: {{ $errors->any() && session('last_tab') === 'kriteria' && session('last_sub_tab') === 'grade' ? 'true' : 'false' }},
        openModalHarga: {{ $errors->any() && session('last_tab') === 'harga' ? 'true' : 'false' }},
        searchQuery: ''
    }">

        {{-- HEADER SECTION --}}
        <div class="mb-6">
            <nav class="flex items-center gap-1.5 text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">
                <span>AQUAFARM CENTRUM</span>
                <i class="fa-solid fa-chevron-right text-[6px] text-slate-300"></i>
                <span class="text-blue-600">Konfigurasi Master Tambak</span>
            </nav>
            <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Pengaturan Tambak</h1>
        </div>

        {{-- TAB NAVIGATION (DESAIN PREMIUM) --}}
        <div class="flex p-1 bg-slate-200/60 border border-slate-200/20 rounded-2xl mb-6 gap-1 max-w-lg select-none">
            <button @click="tab = 'profil'"
                :class="tab === 'profil' ? 'bg-white text-blue-600 shadow-sm font-black' :
                    'text-slate-500 font-bold hover:text-slate-800'"
                class="flex-1 py-3 text-[11px] uppercase tracking-wider rounded-xl transition-all">
                <i class="fa-solid fa-building-shield mr-1.5"></i> Profil Perusahaan
            </button>
            <button @click="tab = 'kriteria'"
                :class="tab === 'kriteria' ? 'bg-white text-blue-600 shadow-sm font-black' :
                    'text-slate-500 font-bold hover:text-slate-800'"
                class="flex-1 py-3 text-[11px] uppercase tracking-wider rounded-xl transition-all">
                <i class="fa-solid fa-layer-group mr-1.5"></i> Kriteria Benur
            </button>
            <button @click="tab = 'harga'"
                :class="tab === 'harga' ? 'bg-white text-blue-600 shadow-sm font-black' :
                    'text-slate-500 font-bold hover:text-slate-800'"
                class="flex-1 py-3 text-[11px] uppercase tracking-wider rounded-xl transition-all">
                <i class="fa-solid fa-tags mr-1.5"></i> Kontrak Harga
            </button>
        </div>

        {{-- NOTIFIKASI FEEDBACK --}}
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-transition.opacity
                class="mb-5 p-3.5 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 rounded-xl text-xs font-semibold shadow-xs flex items-center justify-between gap-2">
                <div><i class="fa-solid fa-circle-check text-emerald-500 mr-1.5"></i> {{ session('success') }}</div>
                <button @click="show = false" class="text-emerald-500 hover:text-emerald-700 focus:outline-none"><i
                        class="fa-solid fa-xmark"></i></button>
            </div>
        @endif
        @if ($errors->any())
            <div x-data="{ show: true }" x-show="show" x-transition.opacity
                class="mb-5 p-3.5 bg-rose-50 border-l-4 border-rose-500 text-rose-800 rounded-xl text-xs font-semibold shadow-xs flex items-center justify-between gap-2">
                <div><i class="fa-solid fa-circle-exclamation text-rose-500 mr-1.5"></i> {{ $errors->first() }}</div>
                <button @click="show = false" class="text-rose-500 hover:text-rose-700 focus:outline-none"><i
                        class="fa-solid fa-xmark"></i></button>
            </div>
        @endif

        {{-- TOOLBAR CONTROL SECTION --}}
        <div class="mb-5 flex flex-col sm:flex-row gap-3 items-center justify-between" x-show="tab !== 'profil'">
            <div class="relative w-full sm:w-72">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </span>
                <input type="text" x-model="searchQuery" placeholder="Cari parameter..."
                    class="w-full bg-white border border-slate-200 text-slate-800 rounded-xl pl-9 pr-4 py-2 text-xs font-medium focus:border-blue-500 focus:ring-1 focus:ring-blue-500 placeholder-slate-400 shadow-xs transition-colors">
            </div>

            @if (Auth::user()->role === 'admin')
                <div class="w-full sm:w-auto flex justify-end">
                    <div x-show="tab === 'kriteria'" class="w-full sm:w-auto">
                        <button
                            @click="kriteriaSub === 'jenis' ? openModalJenis = true : (kriteriaSub === 'ukuran' ? openModalUkuran = true : openModalGrade = true)"
                            class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-xs font-bold shadow-xs flex items-center justify-center gap-1.5 transition-all active:scale-98">
                            <i class="fa-solid fa-plus text-[10px]"></i> TAMBAH <span
                                x-text="kriteriaSub.toUpperCase()"></span>
                        </button>
                    </div>
                    <div x-show="tab === 'harga'" class="w-full sm:w-auto">
                        <button @click="openModalHarga = true"
                            class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-xs font-bold shadow-xs flex items-center justify-center gap-1.5 transition-all active:scale-98">
                            <i class="fa-solid fa-file-circle-plus"></i> TERBITKAN HARGA KONTRAK
                        </button>
                    </div>
                </div>
            @endif
        </div>

        {{-- MAIN BODY CARD KONTEN --}}
        <div class="bg-white rounded-2xl shadow-xs border border-slate-100 overflow-hidden p-5 md:p-6">

            {{-- TAB 1: PROFIL & REKENING LEGALITAS --}}
            <div x-show="tab === 'profil'" x-transition x-cloak>
                <form action="{{ route('pengaturan.saveProfil') }}" method="POST" class="space-y-5">
                    @csrf
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                        <div class="lg:col-span-7 space-y-3.5">
                            <h3
                                class="text-xs font-bold text-blue-600 uppercase tracking-wider border-b pb-1.5 flex items-center gap-1.5">
                                <i class="fa-solid fa-id-card"></i> Legalitas & Kontak Tambak
                            </h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <div>
                                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Nama
                                        Tambak <span class="text-rose-500">*</span></label>
                                    <input type="text" name="nama_tambak" value="{{ $profil->nama_tambak ?? '' }}"
                                        placeholder="Nama tambak" required
                                        {{ Auth::user()->role !== 'admin' ? 'disabled' : '' }}
                                        class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-800 focus:bg-white focus:border-blue-500 transition-all">
                                </div>
                                <div>
                                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">NPWP /
                                        NIB <span class="text-slate-400 text-[8px]">(Opsional)</span></label>
                                    <input type="text" name="npwp_nib" value="{{ $profil->npwp_nib ?? '' }}"
                                        placeholder="Legalitas" {{ Auth::user()->role !== 'admin' ? 'disabled' : '' }}
                                        class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-800 focus:bg-white focus:border-blue-500 transition-all">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <div>
                                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">No.
                                        WhatsApp <span class="text-rose-500">*</span></label>
                                    <input type="text" name="nomor_whatsapp"
                                        value="{{ $profil->nomor_whatsapp ?? '' }}" placeholder="08xxxxxxxxxx" required
                                        {{ Auth::user()->role !== 'admin' ? 'disabled' : '' }}
                                        class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-800 focus:bg-white focus:border-blue-500 transition-all">
                                </div>
                                <div>
                                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Email
                                        <span class="text-rose-500">*</span></label>
                                    <input type="email" name="email" value="{{ $profil->email ?? '' }}"
                                        placeholder="Email operasional" required
                                        {{ Auth::user()->role !== 'admin' ? 'disabled' : '' }}
                                        class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-800 focus:bg-white focus:border-blue-500 transition-all">
                                </div>
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Alamat
                                    Operasional <span class="text-rose-500">*</span></label>
                                <textarea name="alamat" required placeholder="Alamat lengkap tambak"
                                    {{ Auth::user()->role !== 'admin' ? 'disabled' : '' }}
                                    class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 focus:bg-white h-16 resize-none">{{ $profil->alamat ?? '' }}</textarea>
                            </div>
                        </div>

                        <div class="lg:col-span-5 flex flex-col justify-between space-y-4 w-full">
                            <div>
                                <h3
                                    class="text-xs font-bold text-blue-600 uppercase tracking-wider border-b pb-1.5 mb-4 flex items-center gap-1.5">
                                    <i class="fa-solid fa-credit-card"></i> Virtual Rekening Penampungan
                                </h3>
                                <div
                                    class="w-full rounded-2xl bg-gradient-to-br from-slate-900 via-slate-850 to-blue-950 px-5 py-6 text-white shadow-xl relative border border-white/10 overflow-hidden sidebar-mesh select-none transition-all duration-300">
                                    <div class="flex justify-between items-start mb-6 gap-2">
                                        <div>
                                            <p
                                                class="text-[8px] uppercase tracking-widest text-slate-400 font-extrabold leading-none">
                                                BANK/E-WALLET TRANSAKSI</p>
                                            <p class="text-xs font-black tracking-wide uppercase mt-1 text-blue-100"
                                                x-text="'{{ $profil->nama_bank ?? '' }}' || 'BELUM DIATUR'"></p>
                                        </div>
                                        <i class="fa-solid fa-shrimp text-lg text-blue-400/40"></i>
                                    </div>
                                    <div class="mb-5">
                                        <p
                                            class="text-[7px] uppercase tracking-widest text-slate-400 font-bold leading-none">
                                            NOMOR REKENING UTAMA</p>
                                        <p class="text-base sm:text-lg font-mono tracking-widest font-black mt-1 text-slate-100"
                                            x-text="'{{ $profil->nomor_rekening ?? '' }}' ? '{{ $profil->nomor_rekening ?? '' }}'.replace(/(\d{4})/g, '$1 ').trim() : '•••• •••• •••• ••••'">
                                        </p>
                                    </div>
                                    <div class="flex justify-between items-end gap-3 pt-1">
                                        <div class="min-w-0 flex-1">
                                            <p
                                                class="text-[7px] uppercase tracking-widest text-slate-400 font-bold leading-none">
                                                ATAS NAMA</p>
                                            <p class="text-xs font-extrabold uppercase mt-1 truncate text-slate-200"
                                                x-text="'{{ $profil->atas_nama ?? '' }}' || 'NAMA PEMILIK ACCOUNT'">
                                            </p>
                                        </div>
                                        <div
                                            class="text-right bg-white/5 border border-white/10 px-2.5 py-1 rounded-xl backdrop-blur-xs">
                                            <p
                                                class="text-[6px] font-black text-slate-400 uppercase tracking-widest leading-none">
                                                DP WAJIB</p>
                                            <p class="text-xs font-black text-emerald-400 mt-0.5"
                                                x-text="'{{ $profil->nominal_dp ?? '0' }}%'"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if (Auth::user()->role === 'admin')
                                <div class="grid grid-cols-2 gap-2.5 pt-1">
                                    <div>
                                        <label class="text-[9px] font-bold text-slate-400 uppercase tracking-wide">Nama
                                            Bank/E-Wallet <span class="text-rose-500">*</span></label>
                                        <input type="text" name="nama_bank"
                                            value="{{ $profil->nama_bank ?? '' }}" placeholder="Contoh: BCA" required
                                            class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-bold text-slate-800">
                                    </div>
                                    <div>
                                        <label class="text-[9px] font-bold text-slate-400 uppercase tracking-wide">No.
                                            Rekening <span class="text-rose-500">*</span></label>
                                        <input type="text" name="nomor_rekening"
                                            value="{{ $profil->nomor_rekening ?? '' }}" placeholder="Angka murni"
                                            required
                                            class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-bold text-slate-800">
                                    </div>
                                    <div class="col-span-2 grid grid-cols-3 gap-2">
                                        <div class="col-span-2">
                                            <label
                                                class="text-[9px] font-bold text-slate-400 uppercase tracking-wide">Atas
                                                Nama <span class="text-rose-500">*</span></label>
                                            <input type="text" name="atas_nama"
                                                value="{{ $profil->atas_nama ?? '' }}"
                                                placeholder="Sesuai buku tabungan" required
                                                class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-bold text-slate-800">
                                        </div>
                                        <div>
                                            <label
                                                class="text-[9px] font-bold text-slate-400 uppercase tracking-wide">DP
                                                (%) <span class="text-rose-500">*</span></label>
                                            <input type="number" name="nominal_dp"
                                                value="{{ $profil->nominal_dp ?? '' }}" min="0"
                                                max="100" placeholder="20" required
                                                class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-bold text-slate-800">
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    @if (Auth::user()->role === 'admin')
                        <div class="flex justify-end pt-3.5 border-t border-slate-100">
                            <button type="submit"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-xs font-bold shadow-md uppercase tracking-wider transition-all">
                                <i class="fa-solid fa-floppy-disk mr-1"></i> Simpan Perubahan Profil
                            </button>
                        </div>
                    @endif
                </form>
            </div>

            {{-- TAB 2: KRITERIA KOMODITAS BENUR --}}
            <div x-show="tab === 'kriteria'" x-transition x-cloak>
                <div
                    class="flex p-1 bg-slate-100 rounded-xl mb-5 gap-1 max-w-sm border border-slate-200/40 select-none">
                    <button type="button" @click="kriteriaSub = 'jenis'"
                        :class="kriteriaSub === 'jenis' ? 'bg-white text-blue-600 shadow-xs font-bold' :
                            'text-slate-400 hover:text-slate-600'"
                        class="flex-1 py-1.5 text-[10px] uppercase tracking-wider rounded-lg transition-all">Jenis</button>
                    <button type="button" @click="kriteriaSub = 'ukuran'"
                        :class="kriteriaSub === 'ukuran' ? 'bg-white text-blue-600 shadow-xs font-bold' :
                            'text-slate-400 hover:text-slate-600'"
                        class="flex-1 py-1.5 text-[10px] uppercase tracking-wider rounded-lg transition-all">Ukuran</button>
                    <button type="button" @click="kriteriaSub = 'grade'"
                        :class="kriteriaSub === 'grade' ? 'bg-white text-blue-600 shadow-xs font-bold' :
                            'text-slate-400 hover:text-slate-600'"
                        class="flex-1 py-1.5 text-[10px] uppercase tracking-wider rounded-lg transition-all">Grade</button>
                </div>

                {{-- Sub-Tabel: Jenis --}}
                <div x-show="kriteriaSub === 'jenis'">
                    <table class="w-full text-left border-collapse min-w-[500px]">
                        <thead>
                            <tr class="text-[10px] font-black text-slate-400 uppercase border-b border-slate-100 pb-2">
                                <th class="py-2.5 text-center w-12">No</th>
                                <th class="py-2.5 w-1/4">Nama Jenis</th>
                                <th class="py-2.5 w-2/5">Deskripsi</th>
                                <th class="py-2.5 text-center w-20">Kode</th>
                                @if (Auth::user()->role === 'admin')
                                    <th class="py-2.5 text-right pr-3 w-24">Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 text-xs text-slate-700 font-semibold">
                            @forelse ($jenis as $index => $j)
                                <tr class="hover:bg-slate-50/40 transition-colors" x-data="{ openEditJenis: false }"
                                    x-show="searchQuery === '' || '{{ addslashes(strtolower($j->nama)) }}'.includes(searchQuery.toLowerCase())">
                                    <td class="py-3 text-center text-slate-400 font-medium">{{ $index + 1 }}</td>
                                    <td class="py-3 text-slate-900 font-bold text-sm">{{ $j->nama }}</td>
                                    <td class="py-3 text-slate-500 font-medium">{{ $j->deskripsi ?? '-' }}</td>
                                    <td class="py-3 text-center"><span
                                            class="bg-slate-100 text-slate-600 px-2 py-0.5 rounded text-[10px] font-mono font-bold uppercase">{{ $j->kode ?? '-' }}</span>
                                    </td>
                                    @if (Auth::user()->role === 'admin')
                                        <td class="py-3 text-right pr-3">
                                            <div class="flex justify-end gap-2.5">
                                                <button @click="openEditJenis = true"
                                                    class="text-blue-600 hover:text-blue-800 transition-colors"><i
                                                        class="fa-solid fa-pen-to-square"></i></button>
                                                <form action="{{ route('pengaturan.destroyJenis', $j->id) }}"
                                                    method="POST"
                                                    onsubmit="return confirm('Hapus kriteria jenis ini?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit"
                                                        class="text-rose-500 hover:text-rose-700 transition-colors"><i
                                                            class="fa-solid fa-trash-can"></i></button>
                                                </form>
                                            </div>

                                            {{-- MODAL EDIT JENIS --}}
                                            <div x-show="openEditJenis"
                                                class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs text-left"
                                                x-cloak x-transition>
                                                <div @click.away="openEditJenis = false"
                                                    class="bg-white w-full max-w-sm rounded-2xl p-5 shadow-xl">
                                                    <h2
                                                        class="text-base font-extrabold text-slate-800 mb-3 uppercase tracking-tight">
                                                        Koreksi Kriteria Jenis</h2>

                                                    {{-- PERBAIKAN ROUTE ACTION --}}
                                                    <form action="{{ route('pengaturan.updateJenis', $j->id) }}"
                                                        method="POST" class="space-y-3">
                                                        @csrf @method('PUT')
                                                        <div>
                                                            <label
                                                                class="text-[9px] font-bold text-slate-400 uppercase">Nama
                                                                Jenis <span class="text-rose-500">*</span></label>
                                                            <input type="text" name="nama"
                                                                value="{{ $j->nama }}" required
                                                                class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs font-bold text-slate-800">
                                                        </div>
                                                        <div>
                                                            <label
                                                                class="text-[9px] font-bold text-slate-400 uppercase">Kode</label>
                                                            <input type="text" name="kode"
                                                                value="{{ $j->kode }}"
                                                                class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs font-bold uppercase text-slate-800">
                                                        </div>
                                                        <div>
                                                            <label
                                                                class="text-[9px] font-bold text-slate-400 uppercase">Deskripsi</label>
                                                            <textarea name="deskripsi"
                                                                class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs font-medium text-slate-800 h-14 resize-none">{{ $j->deskripsi }}</textarea>
                                                        </div>
                                                        <div class="flex gap-2 pt-1.5">
                                                            <button type="button" @click="openEditJenis = false"
                                                                class="flex-1 py-2 bg-slate-100 text-slate-500 rounded-lg font-bold text-xs">Batal</button>
                                                            <button type="submit"
                                                                class="flex-1 py-2 bg-blue-600 text-white rounded-lg font-bold text-xs">Simpan
                                                                Perubahan</button>
                                                        </div>
                                                    </form>

                                                </div>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-6 text-center text-slate-400 italic">Belum ada data
                                        kriteria jenis.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Sub-Tabel: Ukuran --}}
                <div x-show="kriteriaSub === 'ukuran'">
                    <table class="w-full text-left border-collapse min-w-[500px]">
                        <thead>
                            <tr class="text-[10px] font-black text-slate-400 uppercase border-b border-slate-100 pb-2">
                                <th class="py-2.5 text-center w-12">No</th>
                                <th class="py-2.5 w-1/4">Kategori Ukuran</th>
                                <th class="py-2.5 w-2/5">Deskripsi</th>
                                <th class="py-2.5 text-center w-20">Kode</th>
                                @if (Auth::user()->role === 'admin')
                                    <th class="py-2.5 text-right pr-3 w-24">Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 text-xs text-slate-700 font-semibold">
                            @forelse ($ukuran as $index => $u)
                                <tr class="hover:bg-slate-50/40 transition-colors" x-data="{ openEditUkuran: false }"
                                    x-show="searchQuery === '' || '{{ addslashes(strtolower($u->ukuran)) }}'.includes(searchQuery.toLowerCase())">
                                    <td class="py-3 text-center text-slate-400 font-medium">{{ $index + 1 }}</td>
                                    <td class="py-3 text-slate-900 font-bold text-sm">PL - {{ $u->ukuran }}</td>
                                    <td class="py-3 text-slate-500 font-medium">{{ $u->deskripsi ?? '-' }}</td>
                                    <td class="py-3 text-center"><span
                                            class="bg-slate-100 text-slate-600 px-2 py-0.5 rounded text-[10px] font-mono font-bold uppercase">{{ $u->kode ?? '-' }}</span>
                                    </td>
                                    @if (Auth::user()->role === 'admin')
                                        <td class="py-3 text-right pr-3">
                                            <div class="flex justify-end gap-2.5">
                                                <button @click="openEditUkuran = true"
                                                    class="text-blue-600 hover:text-blue-800 transition-colors"><i
                                                        class="fa-solid fa-pen-to-square"></i></button>
                                                <form action="{{ route('pengaturan.destroyUkuran', $u->id) }}"
                                                    method="POST"
                                                    onsubmit="return confirm('Hapus kriteria ukuran ini?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit"
                                                        class="text-rose-500 hover:text-rose-700 transition-colors"><i
                                                            class="fa-solid fa-trash-can"></i></button>
                                                </form>
                                            </div>

                                            {{-- MODAL EDIT UKURAN --}}
                                            <div x-show="openEditUkuran"
                                                class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs text-left"
                                                x-cloak x-transition>
                                                <div @click.away="openEditUkuran = false"
                                                    class="bg-white w-full max-w-sm rounded-2xl p-5 shadow-xl">
                                                    <h2
                                                        class="text-base font-extrabold text-slate-800 mb-3 uppercase tracking-tight">
                                                        Koreksi Kriteria Ukuran</h2>

                                                    {{-- PERBAIKAN ROUTE ACTION --}}
                                                    <form action="{{ route('pengaturan.updateUkuran', $u->id) }}"
                                                        method="POST" class="space-y-3">
                                                        @csrf @method('PUT')
                                                        <div>
                                                            <label
                                                                class="text-[9px] font-bold text-slate-400 uppercase">Label
                                                                Ukuran (PL) <span
                                                                    class="text-rose-500">*</span></label>
                                                            <input type="text" name="ukuran"
                                                                value="{{ $u->ukuran }}" required
                                                                class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs font-bold text-slate-800">
                                                        </div>
                                                        <div>
                                                            <label
                                                                class="text-[9px] font-bold text-slate-400 uppercase">Kode</label>
                                                            <input type="text" name="kode"
                                                                value="{{ $u->kode }}"
                                                                class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs font-bold uppercase text-slate-800">
                                                        </div>
                                                        <div>
                                                            <label
                                                                class="text-[9px] font-bold text-slate-400 uppercase">Deskripsi</label>
                                                            <textarea name="deskripsi"
                                                                class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs font-medium text-slate-800 h-14 resize-none">{{ $u->deskripsi }}</textarea>
                                                        </div>
                                                        <div class="flex gap-2 pt-1.5">
                                                            <button type="button" @click="openEditUkuran = false"
                                                                class="flex-1 py-2 bg-slate-100 text-slate-500 rounded-lg font-bold text-xs">Batal</button>
                                                            <button type="submit"
                                                                class="flex-1 py-2 bg-blue-600 text-white rounded-lg font-bold text-xs">Simpan
                                                                Perubahan</button>
                                                        </div>
                                                    </form>

                                                </div>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-6 text-center text-slate-400 italic">Belum ada data
                                        kriteria ukuran.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Sub-Tabel: Grade --}}
                <div x-show="kriteriaSub === 'grade'">
                    <table class="w-full text-left border-collapse min-w-[500px]">
                        <thead>
                            <tr class="text-[10px] font-black text-slate-400 uppercase border-b border-slate-100 pb-2">
                                <th class="py-2.5 text-center w-12">No</th>
                                <th class="py-2.5 w-1/4">Tingkatan Grade</th>
                                <th class="py-2.5 w-2/5">Deskripsi</th>
                                <th class="py-2.5 text-center w-20">Kode</th>
                                @if (Auth::user()->role === 'admin')
                                    <th class="py-2.5 text-right pr-3 w-24">Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 text-xs text-slate-700 font-semibold">
                            @forelse ($grade as $index => $g)
                                <tr class="hover:bg-slate-50/40 transition-colors" x-data="{ openEditGrade: false }"
                                    x-show="searchQuery === '' || '{{ addslashes(strtolower($g->nama_grade)) }}'.includes(searchQuery.toLowerCase())">
                                    <td class="py-3 text-center text-slate-400 font-medium">{{ $index + 1 }}</td>
                                    <td class="py-3 text-slate-900 font-bold text-sm uppercase">{{ $g->nama_grade }}
                                    </td>
                                    <td class="py-3 text-slate-500 font-medium">{{ $g->deskripsi ?? '-' }}</td>
                                    <td class="py-3 text-center"><span
                                            class="bg-slate-100 text-slate-600 px-2 py-0.5 rounded text-[10px] font-mono font-bold uppercase">{{ $g->kode ?? '-' }}</span>
                                    </td>
                                    @if (Auth::user()->role === 'admin')
                                        <td class="py-3 text-right pr-3">
                                            <div class="flex justify-end gap-2.5">
                                                <button @click="openEditGrade = true"
                                                    class="text-blue-600 hover:text-blue-800 transition-colors"><i
                                                        class="fa-solid fa-pen-to-square"></i></button>
                                                <form action="{{ route('pengaturan.destroyGrade', $g->id) }}"
                                                    method="POST"
                                                    onsubmit="return confirm('Hapus kriteria grade ini?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit"
                                                        class="text-rose-500 hover:text-rose-700 transition-colors"><i
                                                            class="fa-solid fa-trash-can"></i></button>
                                                </form>
                                            </div>

                                            {{-- MODAL EDIT GRADE --}}
                                            <div x-show="openEditGrade"
                                                class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs text-left"
                                                x-cloak x-transition>
                                                <div @click.away="openEditGrade = false"
                                                    class="bg-white w-full max-w-sm rounded-2xl p-5 shadow-xl">
                                                    <h2
                                                        class="text-base font-extrabold text-slate-800 mb-3 uppercase tracking-tight">
                                                        Koreksi Kriteria Grade</h2>

                                                    {{-- PERBAIKAN ROUTE ACTION --}}
                                                    <form action="{{ route('pengaturan.updateGrade', $g->id) }}"
                                                        method="POST" class="space-y-3">
                                                        @csrf @method('PUT')
                                                        <div>
                                                            <label
                                                                class="text-[9px] font-bold text-slate-400 uppercase">Nama
                                                                Grade <span class="text-rose-500">*</span></label>
                                                            <input type="text" name="nama_grade"
                                                                value="{{ $g->nama_grade }}" required
                                                                class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs font-bold uppercase text-slate-800">
                                                        </div>
                                                        <div>
                                                            <label
                                                                class="text-[9px] font-bold text-slate-400 uppercase">Kode</label>
                                                            <input type="text" name="kode"
                                                                value="{{ $g->kode }}"
                                                                class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs font-bold uppercase text-slate-800">
                                                        </div>
                                                        <div>
                                                            <label
                                                                class="text-[9px] font-bold text-slate-400 uppercase">Deskripsi</label>
                                                            <textarea name="deskripsi"
                                                                class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs font-medium text-slate-800 h-14 resize-none">{{ $g->deskripsi }}</textarea>
                                                        </div>
                                                        <div class="flex gap-2 pt-1.5">
                                                            <button type="button" @click="openEditGrade = false"
                                                                class="flex-1 py-2 bg-slate-100 text-slate-500 rounded-lg font-bold text-xs">Batal</button>
                                                            <button type="submit"
                                                                class="flex-1 py-2 bg-blue-600 text-white rounded-lg font-bold text-xs">Simpan
                                                                Perubahan</button>
                                                        </div>
                                                    </form>

                                                </div>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-6 text-center text-slate-400 italic">Belum ada data
                                        kriteria grade.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- TAB 3: KONTRAK ACUAN HARGA JUAL --}}
            <div x-show="tab === 'harga'" x-transition x-cloak>
                <table class="w-full text-left border-collapse min-w-[550px]">
                    <thead>
                        <tr class="text-[10px] font-black text-slate-400 uppercase border-b border-slate-100 pb-2">
                            <th class="py-2.5 text-center w-12">No</th>
                            <th class="py-2.5 w-1/2">Ikatan Matriks Kriteria Komoditas</th>
                            <th class="py-2.5 text-right pr-6 w-1/4">Harga Jual Acuan</th>
                            @if (Auth::user()->role === 'admin')
                                <th class="py-2.5 text-center w-20">Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 text-xs text-slate-700 font-semibold">
                        @forelse ($harga as $index => $h)
                            <tr class="hover:bg-slate-50/40 transition-colors" x-data="{ openEditHarga: false }"
                                x-show="searchQuery === '' || '{{ addslashes(strtolower($h->nama_jenis)) }}'.includes(searchQuery.toLowerCase())">
                                <td class="py-3.5 text-center text-slate-400 font-medium">{{ $index + 1 }}</td>
                                <td class="py-3.5">
                                    <div class="flex flex-wrap items-center gap-1">
                                        <span class="text-slate-900 font-bold text-sm">{{ $h->nama_jenis }}</span>
                                        <span class="text-slate-300 font-normal text-[10px]">/</span>
                                        <span class="italic text-slate-600 font-medium">PL-{{ $h->ukuran }}</span>
                                        <span class="text-slate-300 font-normal text-[10px]">/</span>
                                        <span
                                            class="bg-blue-50 text-blue-600 border border-blue-100 px-1.5 py-0.5 rounded text-[9px] uppercase font-bold tracking-tight">{{ $h->nama_grade }}</span>
                                    </div>
                                </td>
                                <td class="py-3.5 text-right pr-6 font-black text-emerald-600 text-sm">Rp
                                    {{ number_format($h->harga_jual, 0, ',', '.') }}<span
                                        class="text-[9px] text-slate-400 font-medium">/ekor</span></td>
                                @if (Auth::user()->role === 'admin')
                                    <td class="py-3.5 text-center">
                                        <div class="flex justify-center gap-3">
                                            <button @click="openEditHarga = true"
                                                class="text-blue-600 hover:text-blue-800 transition-colors"><i
                                                    class="fa-solid fa-pen-to-square"></i></button>
                                            <form action="{{ route('pengaturan.destroyHarga', $h->id) }}"
                                                method="POST"
                                                onsubmit="return confirm('Hapus ikatan kontrak acuan harga pasar ini?')">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                    class="text-rose-500 hover:text-rose-700 transition-colors"><i
                                                        class="fa-solid fa-trash-can"></i></button>
                                            </form>
                                        </div>

                                        {{-- MODAL EDIT HARGA --}}
                                        <div x-show="openEditHarga"
                                            class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs text-left"
                                            x-cloak x-transition>
                                            <div @click.away="openEditHarga = false"
                                                class="bg-white w-full max-w-sm rounded-2xl p-5 shadow-xl">
                                                <h2
                                                    class="text-base font-extrabold text-slate-800 mb-4 uppercase tracking-tight">
                                                    Koreksi Anggaran Kontrak Harga</h2>

                                                {{-- PERBAIKAN ROUTE ACTION --}}
                                                <form action="{{ route('pengaturan.updateHarga', $h->id) }}"
                                                    method="POST" class="space-y-3">
                                                    @csrf @method('PUT')
                                                    <div>
                                                        <label
                                                            class="text-[9px] font-bold text-slate-400 uppercase">Harga
                                                            Jual Acuan (Per Ekor) <span
                                                                class="text-rose-500">*</span></label>
                                                        <div class="relative flex items-center">
                                                            <span
                                                                class="absolute left-3 font-bold text-slate-400 text-xs">Rp</span>
                                                            <input type="number" name="harga_jual"
                                                                value="{{ $h->harga_jual }}" min="1" required
                                                                class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg pl-8 pr-4 py-2 text-xs font-black text-emerald-600">
                                                        </div>
                                                    </div>
                                                    <div class="flex gap-2 pt-1.5">
                                                        <button type="button" @click="openEditHarga = false"
                                                            class="flex-1 py-2 bg-slate-100 text-slate-500 rounded-lg font-bold text-xs">Batal</button>
                                                        <button type="submit"
                                                            class="flex-1 py-2 bg-blue-600 text-white rounded-lg font-bold text-xs">Simpan
                                                            Perubahan</button>
                                                    </div>
                                                </form>

                                            </div>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-8 text-center text-slate-400 italic">Belum ada kontrak
                                    harga jual acuan pasar yang diterbitkan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- =========================================================================
             BAGIAN MODAL REGISTER DATA DATA BARU (PRODUK STRUKTUR FORMAL ADMIN)
             ========================================================================= --}}
        @if (Auth::user()->role === 'admin')
            {{-- MODAL TAMBAH JENIS --}}
            <div x-show="openModalJenis"
                class="fixed inset-0 z-[150] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
                x-cloak x-transition>
                <div @click.away="openModalJenis = false" class="bg-white w-full max-w-sm rounded-2xl p-5 shadow-xl">
                    <h2 class="text-base font-extrabold text-slate-800 mb-3 uppercase tracking-tight">Daftarkan Jenis
                        Baru</h2>
                    <form action="{{ route('pengaturan.storeJenis') }}" method="POST" class="space-y-3">
                        @csrf
                        <div>
                            <label class="text-[9px] font-bold text-slate-400 uppercase">Nama Jenis <span
                                    class="text-rose-500">*</span></label>
                            <input type="text" name="nama" required placeholder="Contoh: Vaname"
                                value="{{ old('nama') }}"
                                class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs font-bold text-slate-800">
                        </div>
                        <div>
                            <label class="text-[9px] font-bold text-slate-400 uppercase">Kode <span
                                    class="text-slate-400 text-[8px]">(Opsional)</span></label>
                            <input type="text" name="kode" placeholder="Contoh: VNM"
                                class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs font-bold uppercase text-slate-800">
                        </div>
                        <div>
                            <label class="text-[9px] font-bold text-slate-400 uppercase">Deskripsi</label>
                            <textarea name="deskripsi" placeholder="Keterangan penjelasan..."
                                class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs font-medium text-slate-800 h-14 resize-none"></textarea>
                        </div>
                        <div class="flex gap-2 pt-1.5">
                            <button type="button" @click="openModalJenis = false"
                                class="flex-1 py-2 bg-slate-100 text-slate-500 rounded-lg font-bold text-xs">Batal</button>
                            <button type="submit"
                                class="flex-1 py-2 bg-blue-600 text-white rounded-lg font-bold text-xs">Simpan
                                Data</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- MODAL TAMBAH UKURAN --}}
            <div x-show="openModalUkuran"
                class="fixed inset-0 z-[150] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
                x-cloak x-transition>
                <div @click.away="openModalUkuran = false" class="bg-white w-full max-w-sm rounded-2xl p-5 shadow-xl">
                    <h2 class="text-base font-extrabold text-slate-800 mb-3 uppercase tracking-tight">Daftarkan Ukuran
                        Baru</h2>
                    <form action="{{ route('pengaturan.storeUkuran') }}" method="POST" class="space-y-3">
                        @csrf
                        <div>
                            <label class="text-[9px] font-bold text-slate-400 uppercase">Label Ukuran (PL) <span
                                    class="text-rose-500">*</span></label>
                            <input type="text" name="ukuran" required placeholder="Contoh: 10"
                                value="{{ old('ukuran') }}"
                                class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs font-bold text-slate-800">
                        </div>
                        <div>
                            <label class="text-[9px] font-bold text-slate-400 uppercase">Kode <span
                                    class="text-slate-400 text-[8px]">(Opsional)</span></label>
                            <input type="text" name="kode" placeholder="Contoh: PL10"
                                class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs font-bold uppercase text-slate-800">
                        </div>
                        <div>
                            <label class="text-[9px] font-bold text-slate-400 uppercase">Deskripsi</label>
                            <textarea name="deskripsi" placeholder="Standar klasifikasi..."
                                class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs font-medium text-slate-800 h-14 resize-none"></textarea>
                        </div>
                        <div class="flex gap-2 pt-1.5">
                            <button type="button" @click="openModalUkuran = false"
                                class="flex-1 py-2 bg-slate-100 text-slate-500 rounded-lg font-bold text-xs">Batal</button>
                            <button type="submit"
                                class="flex-1 py-2 bg-blue-600 text-white rounded-lg font-bold text-xs">Simpan
                                Data</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- MODAL TAMBAH GRADE --}}
            <div x-show="openModalGrade"
                class="fixed inset-0 z-[150] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
                x-cloak x-transition>
                <div @click.away="openModalGrade = false" class="bg-white w-full max-w-sm rounded-2xl p-5 shadow-xl">
                    <h2 class="text-base font-extrabold text-slate-800 mb-3 uppercase tracking-tight">Daftarkan Grade
                        Baru</h2>
                    <form action="{{ route('pengaturan.storeGrade') }}" method="POST" class="space-y-3">
                        @csrf
                        <div>
                            <label class="text-[9px] font-bold text-slate-400 uppercase">Nama Kategori Grade <span
                                    class="text-rose-500">*</span></label>
                            <input type="text" name="nama_grade" required placeholder="Contoh: Premium"
                                value="{{ old('nama_grade') }}"
                                class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs font-bold uppercase text-slate-800">
                        </div>
                        <div>
                            <label class="text-[9px] font-bold text-slate-400 uppercase">Kode <span
                                    class="text-slate-400 text-[8px]">(Opsional)</span></label>
                            <input type="text" name="kode" placeholder="Contoh: PRM"
                                class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs font-bold uppercase text-slate-800">
                        </div>
                        <div>
                            <label class="text-[9px] font-bold text-slate-400 uppercase">Deskripsi</label>
                            <textarea name="deskripsi" placeholder="Kriteria parameter..."
                                class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs font-medium text-slate-800 h-14 resize-none"></textarea>
                        </div>
                        <div class="flex gap-2 pt-1.5">
                            <button type="button" @click="openModalGrade = false"
                                class="flex-1 py-2 bg-slate-100 text-slate-500 rounded-lg font-bold text-xs">Batal</button>
                            <button type="submit"
                                class="flex-1 py-2 bg-blue-600 text-white rounded-lg font-bold text-xs">Simpan
                                Data</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- MODAL TAMBAH KONTRAK HARGA --}}
            <div x-show="openModalHarga"
                class="fixed inset-0 z-[150] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
                x-cloak x-transition>
                <div @click.away="openModalHarga = false" class="bg-white w-full max-w-sm rounded-2xl p-5 shadow-xl">
                    <h2 class="text-base font-extrabold text-slate-800 mb-4 uppercase tracking-tight">Terbitkan Matriks
                        Harga Jual</h2>
                    <form action="{{ route('pengaturan.storeHarga') }}" method="POST" class="space-y-3">
                        @csrf
                        <div>
                            <label class="text-[9px] font-bold text-slate-400 uppercase">Pilih Jenis <span
                                    class="text-rose-500">*</span></label>
                            <select name="jenis_id" required
                                class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none">
                                <option value="" disabled selected>-- Pilih Jenis --</option>
                                @foreach ($jenis as $j)
                                    <option value="{{ $j->id }}">{{ $j->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-[9px] font-bold text-slate-400 uppercase">Pilih Ukuran (PL) <span
                                    class="text-rose-500">*</span></label>
                            <select name="ukuran_id" required
                                class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none">
                                <option value="" disabled selected>-- Pilih Ukuran --</option>
                                @foreach ($ukuran as $u)
                                    <option value="{{ $u->id }}">PL-{{ $u->ukuran }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-[9px] font-bold text-slate-400 uppercase">Pilih Grade Kualitas <span
                                    class="text-rose-500">*</span></label>
                            <select name="grade_id" required
                                class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none">
                                <option value="" disabled selected>-- Pilih Grade --</option>
                                @foreach ($grade as $g)
                                    <option value="{{ $g->id }}">{{ $g->nama_grade }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-[9px] font-bold text-slate-400 uppercase">Harga Jual Kontrak (Per Ekor)
                                <span class="text-rose-500">*</span></label>
                            <div class="relative flex items-center">
                                <span class="absolute left-3 font-bold text-slate-400 text-xs">Rp</span>
                                <input type="number" name="harga_jual" min="1" required placeholder="6"
                                    value="{{ old('harga_jual') }}"
                                    class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg pl-8 pr-4 py-2 text-xs font-black text-emerald-600 focus:outline-none">
                            </div>
                        </div>
                        <div class="flex gap-2 pt-2">
                            <button type="button" @click="openModalHarga = false"
                                class="flex-1 py-2.5 bg-slate-100 text-slate-500 rounded-lg font-bold text-xs">Batal</button>
                            <button type="submit"
                                class="flex-1 py-2.5 bg-blue-600 text-white rounded-lg font-bold text-xs shadow-sm">Terbitkan
                                Sah</button>
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
    </style>
</x-app-layout>