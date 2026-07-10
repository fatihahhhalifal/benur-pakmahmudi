<x-app-layout>
    {{-- MENARIK DATA BOOKING OTOMATIS (Tanpa sentuh Controller) --}}
    @php
        // Mengambil semua data pesanan yang berstatus pending atau proses
        $all_bookings_raw = \Illuminate\Support\Facades\DB::table('pesanan')
            ->join('detail_pesanan', 'pesanan.id', '=', 'detail_pesanan.pesanan_id')
            ->join('users', 'pesanan.user_id', '=', 'users.id')
            ->whereIn('pesanan.status', ['pending', 'proses'])
            ->select(
                'pesanan.nomor_invoice',
                'pesanan.status as order_status',
                'users.name as cust_name',
                'detail_pesanan.siklus_id',
                'detail_pesanan.jumlah_sak_dipesan',
                'detail_pesanan.kantong_eceran_dipesan',
                'detail_pesanan.konversi_per_kantong',
            )
            ->get()
            ->map(function ($book) {
                // Kalkulasi Volume Ekor Booking
                $book->total_ekor_riil =
                    ($book->jumlah_sak_dipesan * 45 + $book->kantong_eceran_dipesan) *
                    ($book->konversi_per_kantong ?? 1700);
                return $book;
            });
    @endphp

    {{-- Komando Pusat Pengatur State Operasional, Pop-up Multi-Fungsi, & Otomatisasi Kalkulator --}}
    <div class="max-w-7xl mx-auto font-sans text-slate-800 antialiased" x-data="{
        openModalKolam: {{ $errors->any() ? 'true' : 'false' }},
        openModalTebar: false,
        openModalBOP: false,
        openModalSampling: false,
        openModalEditTabur: false,
        openModalBooking: false,
        openModalEditBOPForm: false,
        openModalEditFisikKolam: false,
        openEditRowSampling: false,
        selectedSiklusId: '',
        selectedKolamId: '',
        selectedKolamNama: '',
        searchQuery: '',
    
        // Data Booking Dinamis dari DB
        all_bookings: {{ json_encode($all_bookings_raw) }},
    
        // State Data Transaksi Finansial BOP Lapangan
        bopMasterList: {{ json_encode($bop_list ?? []) }},
        bopKategori: '',
    
        // Binder Data Operasional Edit BOP
        editBopData: { id: '', siklus_id: '', kategori: '', keterangan_lain: '', nominal: '' },
    
        // Binder Data Form Edit Tabur
        editSiklusData: { id: '', jenis_id: '', ukuran_id: '', grade_id: '', modal: '', jumlah: '', waktu_tabur: '' },
    
        // Binder Data Form Edit Fisik Kolam
        editFisikData: { id: '', nama_kolam: '', kapasitas: '' },
    
        // Binder Data Form Edit Sampling Row
        editRowData: { id: '', grade_id: '', sr: '', tanggal: '', keterangan: '' },
    
        // Binder Array Sejarah Sampling Global dari Backend
        samplingMasterList: {{ json_encode($sampling_list ?? []) }},
    
        // KALKULATOR SAMPLING OTOMATIS (100% AUTOMATED ENGINE)
        samplingInput: {
            total_serokan: '',
            alokasi_grade: {},
            tanggal_hari_ini: '{{ \Carbon\Carbon::now()->format('Y-m-d\TH:i') }}',
    
            get totalTerklasifikasi() {
                return Object.values(this.alokasi_grade).reduce((a, b) => parseInt(a || 0) + parseInt(b || 0), 0);
            },
            get hitungSR() {
                if (!this.total_serokan) return 0;
                let hasil = (this.totalTerklasifikasi / this.total_serokan) * 100;
                return hasil > 100 ? 100 : Math.round(hasil);
            },
            get gradeIdDominan() {
                let maxEkor = -1;
                let idDominan = '';
                Object.keys(this.alokasi_grade).forEach(id => {
                    let ekor = parseInt(this.alokasi_grade[id] || 0);
                    if (ekor > maxEkor) {
                        maxEkor = ekor;
                        idDominan = id;
                    }
                });
                return idDominan;
            }
        }
    }">

        {{-- HEADER SECTION --}}
        <div class="mb-6">
            <nav class="flex items-center gap-1.5 text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">
                <span>AQUAFARM HULU</span>
                <i class="fa-solid fa-chevron-right text-[6px] text-slate-300"></i>
                <span class="text-blue-600">Monitoring Siklus Produksi</span>
            </nav>
            <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Manajemen Infrastruktur & Stok Kolam
            </h1>
        </div>

        {{-- PREMIUM CARD CARDS WIDGET --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div
                class="relative overflow-hidden bg-gradient-to-br from-emerald-500 via-teal-600 to-emerald-700 text-white rounded-3xl p-6 shadow-xl shadow-emerald-950/10 flex items-center justify-between group">
                <div class="space-y-1 relative z-10">
                    <span class="text-[10px] font-black text-blue-100 uppercase tracking-wider block">Total Kapasitas
                        Terisi</span>
                    <span class="text-2xl font-black block">
                        {{ number_format($kolam->where('siklus_id', '!=', null)->sum('stok_tersedia'), 0, ',', '.') }}
                        <span class="text-xs font-bold text-blue-200">Ekor Live</span>
                    </span>
                </div>
                <div
                    class="w-12 h-12 bg-white/15 backdrop-blur-md rounded-2xl flex items-center justify-center text-lg border border-white/10 relative z-10">
                    <i class="fa-solid fa-water"></i>
                </div>
            </div>

            <div
                class="relative overflow-hidden bg-gradient-to-br from-orange-500 via-rose-500 to-pink-600 text-white rounded-3xl p-6 shadow-xl shadow-rose-950/10 flex items-center justify-between group">
                <div class="space-y-1 relative z-10">
                    <span class="text-[10px] font-black text-rose-100 uppercase tracking-wider block">Total Modal Benih
                        & BOP</span>
                    <span class="text-2xl font-black block">
                        Rp
                        {{ number_format($kolam->sum('modal_awal_rupiah') + $kolam->sum('total_bop_keluar'), 0, ',', '.') }}
                    </span>
                </div>
                <div
                    class="w-12 h-12 bg-white/15 backdrop-blur-md rounded-2xl flex items-center justify-center text-lg border border-white/10 relative z-10">
                    <i class="fa-solid fa-vault"></i>
                </div>
            </div>

            <div
                class="relative overflow-hidden bg-gradient-to-br from-violet-600 via-indigo-600 to-purple-700 text-white rounded-3xl p-6 shadow-xl shadow-indigo-950/20 flex items-center justify-between group">
                <div class="space-y-1 relative z-10">
                    <span class="text-[10px] font-black text-indigo-100 uppercase tracking-wider block">Utilisasi
                        Infrastruktur</span>
                    <span class="text-2xl font-black block">
                        {{ $kolam->where('siklus_id', '!=', null)->count() }} <span
                            class="text-xs text-indigo-200 font-bold">dari {{ $kolam->count() }} Kolam</span>
                    </span>
                </div>
                <div
                    class="w-12 h-12 bg-white/15 backdrop-blur-md rounded-2xl flex items-center justify-center text-lg border border-white/10 relative z-10">
                    <i class="fa-solid fa-circle-nodes"></i>
                </div>
            </div>
        </div>

        {{-- ALERTS --}}
        @if (session('success'))
            <div
                class="mb-5 p-3.5 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 rounded-xl text-xs font-semibold flex items-center gap-2 shadow-xs">
                <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
            </div>
        @endif
        @if ($errors->any())
            <div
                class="mb-5 p-3.5 bg-rose-50 border-l-4 border-rose-500 text-rose-800 rounded-xl text-xs font-semibold flex items-center gap-2 shadow-xs">
                <i class="fa-solid fa-circle-exclamation"></i> {{ $errors->first() }}
            </div>
        @endif

        {{-- CONTROL TOOLBAR --}}
        <div class="mb-5 flex flex-col sm:flex-row gap-3 items-center justify-between">
            <div class="relative w-full sm:w-80">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </span>
                <input type="text" x-model="searchQuery" placeholder="Cari nama kolam atau komoditas..."
                    class="w-full bg-white border border-slate-200 text-slate-800 rounded-xl pl-9 pr-4 py-2 text-xs font-medium focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-xs transition-colors">
            </div>

            @if (Auth::user()->role === 'admin')
                <button @click="openModalKolam = true"
                    class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-xs font-black shadow-xs flex items-center justify-center gap-1.5 uppercase tracking-wider">
                    <i class="fa-solid fa-circle-plus"></i> REGISTRASI MASTER KOLAM
                </button>
            @endif
        </div>

        {{-- DATA TABLE --}}
        <div class="bg-white rounded-2xl shadow-xs border border-slate-100 overflow-hidden p-5 md:p-6">
            <div class="overflow-x-auto min-w-full">
                <table class="w-full text-left border-collapse min-w-[1050px]">
                    <thead>
                        <tr
                            class="text-[10px] font-black text-slate-400 uppercase tracking-wider border-b border-slate-100 pb-2">
                            <th class="py-2.5 text-center w-12">No</th>
                            <th class="py-2.5 w-44">Identitas Kolam</th>
                            <th class="py-2.5 w-52">Komoditas & DOC</th>
                            <th class="py-2.5 text-right w-44">Kapasitas / Booking</th>
                            <th class="py-2.5 text-center w-52">Biaya Produksi</th>
                            <th class="py-2.5 text-center w-48">Tindakan Field</th>
                            @if (Auth::user()->role === 'admin')
                                <th class="py-2.5 text-right pr-3 w-20">Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 text-xs text-slate-700 font-semibold">
                        @forelse ($kolam as $index => $k)
                            @php
                                $totalDays = $k->siklus_id ? (int) $k->doc : 0;
                                $sisaKuotaBebas = $k->siklus_id ? (int) $k->sisa_kuota_bebas : 0;
                                $totalBooking = $k->siklus_id ? (int) $k->total_booked_ekor : 0;
                                $totalAkumulasiBop = $k->siklus_id ? $k->modal_awal_rupiah + $k->total_bop_keluar : 0;
                            @endphp
                            <tr class="hover:bg-slate-50/40 transition-colors"
                                x-show="searchQuery === '' || '{{ strtolower($k->nama_kolam) }}'.includes(searchQuery.toLowerCase()) || '{{ strtolower($k->nama_jenis) }}'.includes(searchQuery.toLowerCase())">
                                <td class="py-3.5 text-center text-slate-400 font-medium">{{ $index + 1 }}</td>
                                <td class="py-3.5">
                                    <div class="flex flex-col">
                                        <span
                                            class="text-slate-900 font-black text-sm uppercase">{{ $k->nama_kolam }}</span>
                                        <span class="text-[9px] mt-0.5 font-black uppercase tracking-tight"
                                            :class="'{{ $k->siklus_id }}' ? 'text-blue-600' : 'text-slate-400'">
                                            <i class="fa-solid fa-circle-dot text-[7px] mr-1"
                                                :class="'{{ $k->siklus_id }}' ? 'animate-pulse' : ''"></i>
                                            <span x-text="'{{ $k->siklus_id }}' ? 'SIKLUS AKTIF' : 'RESTING'"></span>
                                        </span>
                                    </div>
                                </td>
                                <td class="py-3.5">
                                    @if ($k->siklus_id)
                                        <div class="flex flex-col space-y-0.5">
                                            <div class="flex items-center gap-1">
                                                <span
                                                    class="text-slate-900 font-black uppercase">{{ $k->nama_jenis }}</span>
                                                <span
                                                    class="italic text-slate-600 font-bold">PL-{{ $k->label_ukuran }}</span>
                                            </div>
                                            <span
                                                class="text-[10px] text-blue-600 font-black uppercase tracking-wide">{{ $k->nama_grade ?? 'Belum Sampling' }}</span>

                                            {{-- FIX: HANYA MENAMPILKAN DOC MURNI, OVER-DAY DIHAPUS --}}
                                            <div class="flex items-center gap-1.5 mt-0.5">
                                                <span class="text-[10px] text-slate-500 font-black">DOC:
                                                    {{ $totalDays }} Hari</span>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-slate-300 font-medium italic">-</span>
                                    @endif
                                </td>
                                <td class="py-3.5 text-right">
                                    <div class="flex flex-col">
                                        <span class="text-slate-400 font-bold text-[9px] uppercase">CAP:
                                            {{ number_format($k->kapasitas_maksimal, 0, ',', '.') }}</span>
                                        @if ($k->siklus_id)
                                            <span class="text-sm font-black text-slate-900 mt-0.5">Sisa:
                                                {{ number_format($sisaKuotaBebas, 0, ',', '.') }} <span
                                                    class="text-[9px] font-bold text-slate-400">ekor</span></span>
                                            <div class="mt-0.5 text-[9px] text-slate-400 font-bold">
                                                <span>Booked: {{ number_format($totalBooking, 0, ',', '.') }}
                                                    ekor</span>
                                                <button
                                                    @click="openModalBooking = true; selectedSiklusId = '{{ $k->siklus_id }}'; selectedKolamNama = '{{ $k->nama_kolam }}'"
                                                    class="text-blue-600 hover:text-blue-800 underline font-black focus:outline-none ml-0.5">Lihat</button>
                                            </div>
                                        @else
                                            <span class="text-sm font-black text-slate-300 mt-0.5">0 ekor</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-3.5 text-center">
                                    @if ($k->siklus_id)
                                        <div class="flex flex-col items-center justify-center space-y-1.5">
                                            <div>
                                                <span class="text-rose-600 text-sm font-black block">Rp
                                                    {{ number_format($totalAkumulasiBop, 0, ',', '.') }}</span>
                                                <span
                                                    class="text-[9px] text-slate-400 font-bold block mt-0.5 bg-slate-50 border px-1.5 py-0.5 rounded">Benih:
                                                    {{ number_format($k->modal_awal_rupiah, 0, ',', '.') }} | BOP:
                                                    {{ number_format($k->total_bop_keluar, 0, ',', '.') }}</span>
                                            </div>
                                            @if (in_array(Auth::user()->role, ['admin', 'operator', 'pemilik']))
                                                <button
                                                    @click="openModalBOP = true; selectedSiklusId = '{{ $k->siklus_id }}'; selectedKolamNama = '{{ $k->nama_kolam }}'; editSiklusData.modal = '{{ $k->modal_awal_rupiah }}'; bopKategori = ''"
                                                    class="text-[9px] bg-blue-50 text-blue-700 px-2.5 py-1 rounded-md border border-blue-200/60 font-black uppercase focus:outline-none shadow-2xs">
                                                    <i class="fa-solid fa-wallet mr-1"></i> Kelola BOP Kolam
                                                </button>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-slate-300 font-medium italic">-</span>
                                    @endif
                                </td>
                                <td class="py-3.5 text-center">
                                    @if ($k->siklus_id)
                                        <div class="flex flex-col gap-1.5 max-w-[140px] mx-auto">
                                            @if (in_array(Auth::user()->role, ['admin', 'operator']))
                                                <button
                                                    @click="openModalEditTabur = true; editSiklusData = { id: '{{ $k->siklus_id }}', jenis_id: '{{ $k->jenis_id }}', ukuran_id: '{{ $k->ukuran_id }}', grade_id: '{{ $k->grade_id }}', modal: '{{ $k->modal_awal_rupiah }}', jumlah: '{{ $k->jumlah_tebar_awal }}', waktu_tabur: '{{ \Carbon\Carbon::parse($k->waktu_tabur)->format('Y-m-d\TH:i') }}' }; selectedKolamNama = '{{ $k->nama_kolam }}'"
                                                    class="w-full inline-flex items-center justify-center gap-1.5 bg-amber-50 text-amber-700 px-2 py-1 rounded-md font-black text-[9px] uppercase border border-amber-200/40">
                                                    <i class="fa-solid fa-sliders text-[8px]"></i> Parameter Tabur
                                                </button>
                                                <button
                                                    @click="openModalSampling = true; selectedSiklusId = '{{ $k->siklus_id }}'; selectedKolamNama = '{{ $k->nama_kolam }}'; samplingInput.alokasi_grade = {}; samplingInput.total_serokan = ''"
                                                    class="w-full inline-flex items-center justify-center gap-1.5 bg-emerald-50 text-emerald-700 px-2 py-1 rounded-md font-black text-[9px] uppercase border border-emerald-200/40">
                                                    <i class="fa-solid fa-vial text-[8px]"></i> Serokan Sampling
                                                </button>
                                            @endif
                                            @if (Auth::user()->role === 'admin')
                                                <form action="{{ route('kolam.kuras', $k->siklus_id) }}" method="POST"
                                                    onsubmit="return confirm('Kuras total kolam ini?')">
                                                    @csrf
                                                    <button type="submit"
                                                        class="w-full inline-flex items-center justify-center gap-1.5 bg-rose-50 text-rose-600 px-2 py-1 rounded-md font-black text-[9px] uppercase border border-rose-200/40"><i
                                                            class="fa-solid fa-soap text-[8px]"></i> Kuras
                                                        Selesai</button>
                                                </form>
                                            @endif
                                        </div>
                                    @else
                                        @if (in_array(Auth::user()->role, ['admin', 'operator']))
                                            <button
                                                @click="openModalTebar = true; selectedKolamId = '{{ $k->id }}'; selectedKolamNama = '{{ $k->nama_kolam }}'"
                                                class="inline-flex items-center gap-1 bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-xl font-black text-[10px] uppercase tracking-widest shadow-sm">
                                                <i class="fa-solid fa-fish text-[9px]"></i> TEBAR BARU
                                            </button>
                                        @else
                                            <span
                                                class="text-slate-400 font-bold text-[9px] uppercase bg-slate-50 px-2 py-1 rounded border">Menunggu
                                                Tebar</span>
                                        @endif
                                    @endif
                                </td>
                                @if (Auth::user()->role === 'admin')
                                    <td class="py-3.5 text-right pr-3">
                                        <div class="flex justify-end gap-3 items-center">
                                            <button
                                                @click="openModalEditFisikKolam = true; editFisikData = { id: '{{ $k->id }}', nama_kolam: '{{ $k->nama_kolam }}', kapasitas: '{{ $k->kapasitas_maksimal }}' }"
                                                class="text-slate-400 hover:text-blue-600 focus:outline-none"><i
                                                    class="fa-solid fa-pen-to-square text-xs"></i></button>
                                            <form action="{{ route('kolam.destroy', $k->id) }}" method="POST"
                                                onsubmit="return confirm('Hapus permanen master fisik kolam ini?')">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                    class="text-slate-300 hover:text-rose-600 focus:outline-none"><i
                                                        class="fa-solid fa-trash-can text-xs"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-slate-400 italic">Belum ada
                                    infrastruktur kolam fisik terdaftar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- =========================================================================
             MODAL CONTAINERS
             ========================================================================= --}}
        @if (in_array(Auth::user()->role, ['admin', 'operator', 'pemilik']))

            {{-- 1. REGISTRASI MASTER FISIK KOLAM --}}
            <div x-show="openModalKolam"
                class="fixed inset-0 z-[150] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
                x-cloak x-transition>
                <div @click.away="openModalKolam = false" class="bg-white w-full max-w-sm rounded-2xl p-5 shadow-xl">
                    <h2 class="text-base font-extrabold text-slate-800 mb-3 uppercase">Registrasi Kolam Baru</h2>
                    <form action="{{ route('kolam.store') }}" method="POST" class="space-y-3">
                        @csrf
                        <div>
                            <label class="text-[9px] font-bold text-slate-400 uppercase">Nama Kolam / Blok <span
                                    class="text-rose-500">*</span></label>
                            <input type="text" name="nama_kolam" required placeholder="Contoh: Kolam Blok Alfa-1"
                                class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none">
                        </div>
                        <div>
                            <label class="text-[9px] font-bold text-slate-400 uppercase">Kapasitas Maksimal (Ekor)
                                <span class="text-rose-500">*</span></label>
                            <input type="number" name="kapasitas" required placeholder="Contoh: 250000"
                                class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none">
                        </div>
                        <div class="flex gap-2 pt-1.5">
                            <button type="button" @click="openModalKolam = false"
                                class="flex-1 py-2 bg-slate-100 text-slate-500 rounded-lg font-bold text-xs">Batal</button>
                            <button type="submit"
                                class="flex-1 py-2 bg-blue-600 text-white rounded-lg font-bold text-xs">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- 2. MODAL TEBAR SIKLUS BARU --}}
            <div x-show="openModalTebar"
                class="fixed inset-0 z-[150] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
                x-cloak x-transition>
                <div @click.away="openModalTebar = false" class="bg-white w-full max-w-sm rounded-2xl p-5 shadow-xl">
                    <h2 class="text-base font-extrabold text-slate-800 mb-1 uppercase">Mulai Siklus Tebar</h2>
                    <p class="text-[11px] font-black text-blue-600 uppercase mb-3" x-text="selectedKolamNama"></p>
                    <form action="{{ route('kolam.tebar') }}" method="POST" class="space-y-3">
                        @csrf
                        <input type="hidden" name="kolam_id" :value="selectedKolamId">
                        <div>
                            <label class="text-[9px] font-bold text-slate-400 uppercase">Pilih Jenis Benur <span
                                    class="text-rose-500">*</span></label>
                            <select name="jenis_id" required
                                class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-bold text-slate-800 focus:outline-none">
                                <option value="" disabled selected>-- Pilih Jenis --</option>
                                @foreach ($list_jenis as $j)
                                    <option value="{{ $j->id }}">{{ $j->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="text-[9px] font-bold text-slate-400 uppercase">Setting Ukuran <span
                                        class="text-rose-500">*</span></label>
                                <select name="ukuran_id" required
                                    class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-bold text-slate-800 focus:outline-none">
                                    <option value="" disabled selected>-- Pilih Ukuran --</option>
                                    @foreach ($list_ukuran as $u)
                                        <option value="{{ $u->id }}">PL-{{ $u->ukuran }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="text-[9px] font-bold text-slate-400 uppercase">Initial Grade <span
                                        class="text-rose-500">*</span></label>
                                <select name="grade_id" required
                                    class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-bold text-slate-800 focus:outline-none">
                                    <option value="" disabled selected>-- Pilih Grade --</option>
                                    @foreach ($list_grade as $g)
                                        <option value="{{ $g->id }}">{{ $g->nama_grade }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="text-[9px] font-bold text-slate-400 uppercase">Modal Benih (Rp) <span
                                        class="text-rose-500">*</span></label>
                                <input type="number" name="modal" required
                                    class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-bold text-slate-800 focus:outline-none">
                            </div>
                            <div>
                                <label class="text-[9px] font-bold text-slate-400 uppercase">Jumlah Tebar (Ekor) <span
                                        class="text-rose-500">*</span></label>
                                <input type="number" name="jumlah" required
                                    class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-bold text-slate-800 focus:outline-none">
                            </div>
                        </div>
                        <div>
                            <label class="text-[9px] font-bold text-slate-400 uppercase">Waktu Penaburan <span
                                    class="text-rose-500">*</span></label>
                            <input type="datetime-local" name="waktu_tabur" required
                                class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-bold text-slate-800 focus:outline-none">
                        </div>
                        <div class="flex gap-2 pt-1.5">
                            <button type="button" @click="openModalTebar = false"
                                class="flex-1 py-2 bg-slate-100 text-slate-500 rounded-lg font-bold text-xs">Batal</button>
                            <button type="submit"
                                class="flex-1 py-2 bg-blue-600 text-white rounded-lg font-bold text-xs">Mulai
                                Tebar</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- 3. EDIT SIKLUS AKTIF --}}
            <div x-show="openModalEditTabur"
                class="fixed inset-0 z-[150] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
                x-cloak x-transition>
                <div @click.away="openModalEditTabur = false"
                    class="bg-white w-full max-w-sm rounded-2xl p-5 shadow-xl">
                    <h2 class="text-base font-extrabold text-slate-800 mb-1 uppercase">Edit Parameter Tabur</h2>
                    <p class="text-[11px] font-black text-amber-600 uppercase mb-3" x-text="selectedKolamNama"></p>
                    <form
                        :action="'{{ route('kolam.siklusUpdate', ['id' => 'PLACEHOLDER_ID']) }}'.replace('PLACEHOLDER_ID',
                            editSiklusData.id)"
                        method="POST" class="space-y-3">
                        @csrf @method('PUT')
                        <div>
                            <label class="text-[9px] font-bold text-slate-400 uppercase">Jenis Benur <span
                                    class="text-rose-500">*</span></label>
                            <select name="jenis_id" x-model="editSiklusData.jenis_id" required
                                class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-bold text-slate-800 focus:outline-none">
                                @foreach ($list_jenis as $j)
                                    <option value="{{ $j->id }}">{{ $j->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="text-[9px] font-bold text-slate-400 uppercase">Ukuran Benur <span
                                        class="text-rose-500">*</span></label>
                                <select name="ukuran_id" x-model="editSiklusData.ukuran_id" required
                                    class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-bold text-slate-800 focus:outline-none">
                                    @foreach ($list_ukuran as $u)
                                        <option value="{{ $u->id }}">PL-{{ $u->ukuran }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="text-[9px] font-bold text-slate-400 uppercase">Grade Awal <span
                                        class="text-rose-500">*</span></label>
                                <select name="grade_id" x-model="editSiklusData.grade_id" required
                                    class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-bold text-slate-800 focus:outline-none">
                                    @foreach ($list_grade as $g)
                                        <option value="{{ $g->id }}">{{ $g->nama_grade }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="text-[9px] font-bold text-slate-400 uppercase">Modal Benih (Rp) <span
                                        class="text-rose-500">*</span></label>
                                <input type="number" name="modal" x-model="editSiklusData.modal" required
                                    class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-bold text-slate-800 focus:outline-none">
                            </div>
                            <div>
                                <label class="text-[9px] font-bold text-slate-400 uppercase">Jumlah Tebar (Ekor) <span
                                        class="text-rose-500">*</span></label>
                                <input type="number" name="jumlah" x-model="editSiklusData.jumlah" required
                                    class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-bold text-slate-800 focus:outline-none">
                            </div>
                        </div>
                        <div>
                            <label class="text-[9px] font-bold text-slate-400 uppercase">Waktu Penaburan <span
                                    class="text-rose-500">*</span></label>
                            <input type="datetime-local" name="waktu_tabur" x-model="editSiklusData.waktu_tabur"
                                required
                                class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-bold text-slate-800 focus:outline-none">
                        </div>
                        <div class="flex gap-2 pt-1.5">
                            <button type="button" @click="openModalEditTabur = false"
                                class="flex-1 py-2 bg-slate-100 text-slate-500 rounded-lg font-bold text-xs">Batal</button>
                            <button type="submit"
                                class="flex-1 py-2 bg-blue-600 text-white rounded-lg font-bold text-xs">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- 4. KELOLA BOP KOLAM --}}
            <div x-show="openModalBOP"
                class="fixed inset-0 z-[140] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
                x-cloak x-transition>
                <div @click.away="openModalBOP = false"
                    class="bg-white w-full max-w-lg rounded-2xl p-5 shadow-xl max-h-[90vh] flex flex-col">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <h2 class="text-base font-extrabold text-slate-800 uppercase">Rincian Anggaran Biaya
                                Operasional (BOP)</h2>
                            <p class="text-[11px] font-black text-blue-600 uppercase" x-text="selectedKolamNama"></p>
                        </div>
                        <button @click="openModalBOP = false"
                            class="text-slate-400 hover:text-slate-600 focus:outline-none"><i
                                class="fa-solid fa-xmark text-base"></i></button>
                    </div>

                    <div class="flex-1 overflow-y-auto mb-4 border border-slate-100 rounded-xl max-h-[220px]">
                        <table class="w-full text-left text-[11px] border-collapse">
                            <thead class="bg-slate-50 text-slate-400 uppercase font-black border-b sticky top-0 z-10">
                                <tr>
                                    <th class="p-2 w-12 text-center">No</th>
                                    <th class="p-2">Kategori Log Pengeluaran</th>
                                    <th class="p-2 text-right">Nominal</th>
                                    @if (Auth::user()->role === 'admin')
                                        <th class="p-2 text-center w-24">Tindakan</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y text-slate-700 font-bold">
                                <tr class="bg-blue-50/20">
                                    <td class="p-2 text-center text-slate-400">1</td>
                                    <td class="p-2 text-slate-900 uppercase">Biaya Pembelian Benih (Awal Siklus)</td>
                                    <td class="p-2 text-right text-slate-900"
                                        x-text="'Rp ' + parseInt(editSiklusData.modal || 0).toLocaleString('id-ID')">
                                    </td>
                                    @if (Auth::user()->role === 'admin')
                                        <td class="p-2 text-center text-[9px] text-slate-400 italic font-black">Locked
                                        </td>
                                    @endif
                                </tr>
                                <template
                                    x-for="(item, idx) in bopMasterList.filter(b => b.siklus_id == selectedSiklusId)"
                                    :key="item.id">
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="p-2 text-center text-slate-400" x-text="idx + 2"></td>
                                        <td class="p-2 text-slate-900 uppercase" x-text="item.keterangan_biaya"></td>
                                        <td class="p-2 text-right text-rose-600"
                                            x-text="'Rp ' + parseInt(item.nominal_biaya).toLocaleString('id-ID')"></td>
                                        @if (Auth::user()->role === 'admin')
                                            <td
                                                class="p-2 text-center flex items-center justify-center gap-2.5 pt-2.5">
                                                <button
                                                    @click="openModalEditBOPForm = true; editBopData = { id: item.id, siklus_id: item.siklus_id, kategori: item.keterangan, keterangan_lain: item.keterangan_biaya.includes(' - ') ? item.keterangan_biaya.split(' - ')[1] : item.keterangan_biaya, nominal: item.nominal_biaya }"
                                                    class="text-slate-400 hover:text-blue-600 focus:outline-none"><i
                                                        class="fa-solid fa-pen-to-square"></i></button>
                                                <form :action="'/kolam/bop/' + item.id" method="POST"
                                                    onsubmit="return confirm('Hapus item catatan pengeluaran biaya produksi kolam aktif ini?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit"
                                                        class="text-slate-300 hover:text-rose-500 focus:outline-none"><i
                                                            class="fa-solid fa-trash-can"></i></button>
                                                </form>
                                            </td>
                                        @endif
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    @if (in_array(Auth::user()->role, ['admin', 'operator']))
                        <form action="{{ route('kolam.bop') }}" method="POST"
                            class="space-y-3 pt-3 border-t border-slate-100">
                            @csrf
                            <input type="hidden" name="siklus_id" :value="selectedSiklusId">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="text-[9px] font-bold text-slate-400 uppercase">Kategori Akun <span
                                            class="text-rose-500">*</span></label>
                                    <select name="keterangan" x-model="bopKategori" required
                                        class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-2 py-1.5 text-xs font-bold text-slate-800 focus:outline-none">
                                        <option value="" disabled selected>-- Pilih Kategori BOP --</option>
                                        <option value="Pakan">Pakan</option>
                                        <option value="Vitamin & Suplemen Imun">Vitamin & Suplemen Imun</option>
                                        <option value="Obat & Desinfektan Kolam">Obat & Desinfektan Kolam</option>
                                        <option value="Bahan Kimia & Pengkondisi Air">Bahan Kimia & Pengkondisi Air
                                        </option>
                                        <option value="Energi Listrik & Pompa">Energi Listrik & Pompa</option>
                                        <option value="Gaji Tenaga Kerja Lapangan">Gaji Tenaga Kerja Lapangan</option>
                                        <option value="Lain-lain">Lain-lain</option>
                                    </select>
                                </div>
                                <div x-show="bopKategori !== ''" x-transition>
                                    <label class="text-[9px] font-bold text-slate-400 uppercase">Detail Deskripsi
                                        Komponen <span class="text-rose-500">*</span></label>
                                    <input type="text" name="keterangan_lain"
                                        :required="bopKategori !== ''"
                                        placeholder="Contoh: Pembelian Merk Artemia A"
                                        class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-3 py-1.5 text-xs font-bold text-slate-800 focus:outline-none">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="text-[9px] font-bold text-slate-400 uppercase">Nominal Nilai Biaya
                                        <span class="text-rose-500">*</span></label>
                                    <input type="number" name="nominal" required placeholder="Contoh: 450000"
                                        class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-3 py-1.5 text-xs font-bold text-slate-800 focus:outline-none">
                                </div>
                            </div>
                            <button type="submit"
                                class="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-black text-xs flex items-center justify-center gap-1 uppercase tracking-wider">
                                <i class="fa-solid fa-square-plus"></i> Kirim Alokasi BOP Lapangan
                            </button>
                        </form>
                    @else
                        <div
                            class="p-3 bg-slate-50 border rounded-xl text-center text-[11px] font-medium text-slate-400 italic">
                            Mode Pemantauan (Read-Only). Akun Pemilik hanya diizinkan mengaudit rekap.</div>
                    @endif
                </div>
            </div>

            {{-- 4B. MODAL SUB-EDIT LINE BOP (Hanya Terbuka untuk Admin) --}}
            @if (Auth::user()->role === 'admin')
                <div x-show="openModalEditBOPForm"
                    class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm"
                    x-cloak x-transition>
                    <div @click.away="openModalEditBOPForm = false"
                        class="bg-white w-full max-w-sm rounded-2xl p-5 shadow-2xl">
                        <h2 class="text-base font-extrabold text-slate-800 mb-3 uppercase">Koreksi Log Pengeluaran BOP
                        </h2>
                        <form :action="'/monitoring-kolam/bop-update/' + editBopData.id" method="POST"
                            class="space-y-3">
                            @csrf @method('PUT')
                            <div>
                                <label class="text-[9px] font-bold text-slate-400 uppercase">Kategori Akun <span
                                        class="text-rose-500">*</span></label>
                                <select name="keterangan" x-model="editBopData.kategori" required
                                    class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-bold text-slate-800 focus:outline-none">
                                    <option value="Pakan">Pakan</option>
                                    <option value="Vitamin & Suplemen Imun">Vitamin & Suplemen Imun</option>
                                    <option value="Obat & Desinfektan Kolam">Obat & Desinfektan Kolam</option>
                                    <option value="Bahan Kimia & Pengkondisi Air">Bahan Kimia & Pengkondisi Air
                                    </option>
                                    <option value="Energi Listrik & Pompa">Energi Listrik & Pompa</option>
                                    <option value="Gaji Tenaga Kerja Lapangan">Gaji Tenaga Kerja Lapangan</option>
                                    <option value="Lain-lain">Lain-lain</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-[9px] font-bold text-slate-400 uppercase">Koreksi Deskripsi Komponen
                                    <span class="text-rose-500">*</span></label>
                                <input type="text" name="keterangan_lain" required
                                    x-model="editBopData.keterangan_lain"
                                    class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-3 py-1.5 text-xs font-bold text-slate-800 focus:outline-none">
                            </div>
                            <div>
                                <label class="text-[9px] font-bold text-slate-400 uppercase">Koreksi Nominal Dana
                                    Keluar (Rp) <span class="text-rose-500">*</span></label>
                                <input type="number" name="nominal" required x-model="editBopData.nominal"
                                    class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-3 py-1.5 text-xs font-black text-slate-800 focus:outline-none">
                            </div>
                            <div class="flex gap-2 pt-1.5">
                                <button type="button" @click="openModalEditBOPForm = false"
                                    class="flex-1 py-2 bg-slate-100 text-slate-500 rounded-lg font-bold text-xs">Batal</button>
                                <button type="submit"
                                    class="flex-1 py-2 bg-blue-600 text-white rounded-lg font-black text-xs uppercase tracking-wider">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            {{-- 5. MODAL POP-UP SAMPLING INTEGRASI KOREKSI & UNGGAH FILE MEDIA KAMERA HP --}}
            <div x-show="openModalSampling"
                class="fixed inset-0 z-[150] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
                x-cloak x-transition>
                <div @click.away="openModalSampling = false"
                    class="bg-white w-full max-w-xl rounded-2xl p-5 shadow-xl max-h-[92vh] flex flex-col">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <h2 class="text-base font-extrabold text-slate-800 uppercase">Serokan Sampling Kualitas Log
                                Visual</h2>
                            <p class="text-[11px] font-black text-blue-600 uppercase" x-text="selectedKolamNama"></p>
                        </div>
                        <button @click="openModalSampling = false"
                            class="text-slate-400 hover:text-slate-600 focus:outline-none"><i
                                class="fa-solid fa-xmark text-base"></i></button>
                    </div>

                    {{-- Form Entri Data Baru --}}
                    @if (in_array(Auth::user()->role, ['admin', 'operator']))
                        <form action="{{ route('kolam.sampling') }}" method="POST" enctype="multipart/form-data"
                            class="space-y-3 pb-3 border-b border-slate-100 flex-shrink-0">
                            @csrf
                            <input type="hidden" name="siklus_id" :value="selectedSiklusId">

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="text-[9px] font-bold text-slate-400 uppercase">Target Serokan (Ekor)
                                        <span class="text-rose-500">*</span></label>
                                    <input type="number" name="target_serokan"
                                        x-model.number="samplingInput.total_serokan" min="1" required
                                        placeholder="Contoh: 100"
                                        class="w-full mt-0.5 bg-white border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-black text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="text-[9px] font-bold text-slate-400 uppercase">Tanggal Sampling <span
                                            class="text-rose-500">*</span></label>
                                    <input type="datetime-local" name="tanggal_sampling"
                                        :value="samplingInput.tanggal_hari_ini" required
                                        class="w-full mt-0.5 bg-white border border-slate-200 rounded-lg px-2 py-1 text-xs font-bold text-slate-800 focus:outline-none">
                                </div>
                                <div>
                                    <label class="text-[9px] font-bold text-slate-400 uppercase">Dokumentasi Foto <span
                                            class="text-blue-500">(Kamera)</span></label>
                                    <input type="file" name="foto_sampling" accept="image/*"
                                        class="w-full mt-0.5 text-[10px] text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded-md file:border-0 file:bg-blue-50 file:text-blue-700">
                                </div>
                            </div>

                            <div class="bg-slate-50 rounded-xl p-3 border border-slate-100">
                                <span class="text-[9px] font-black text-slate-400 block uppercase mb-2">Klasifikasi
                                    Hasil Jala Komoditas:</span>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                    @foreach ($list_grade as $g)
                                        <div
                                            class="flex items-center justify-between bg-white px-2 py-1 rounded-lg border border-slate-200/60">
                                            <span
                                                class="text-[10px] font-bold text-slate-700 uppercase">{{ $g->nama_grade }}</span>
                                            <div class="relative flex items-center w-16">
                                                <input type="number"
                                                    x-model.number="samplingInput.alokasi_grade['{{ $g->id }}']"
                                                    placeholder="0"
                                                    class="w-full bg-slate-50 border border-slate-200 rounded px-1 text-right text-[11px] font-black text-slate-800 focus:outline-none">
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <div
                                    class="pt-2 mt-2 border-t border-dashed flex justify-between items-center text-[9px] font-black uppercase">
                                    <span class="text-slate-400">Total Akumulasi: <span
                                            :class="(samplingInput.total_serokan > 0 && samplingInput.totalTerklasifikasi <=
                                                samplingInput.total_serokan) ? 'text-emerald-600' : 'text-rose-500'"
                                            x-text="samplingInput.totalTerklasifikasi + ' / ' + (samplingInput.total_serokan || 0) + ' ekor'"></span></span>
                                    <span class="text-slate-400">SR Estimasi: <span
                                            class="text-blue-600 text-xs font-black"
                                            x-text="samplingInput.hitungSR + '%'"></span></span>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 items-end">
                                <div class="sm:col-span-2">
                                    <input type="text" name="keterangan"
                                        placeholder="Catatan kondisi tambahan lapangan..."
                                        class="w-full bg-white border border-slate-200 rounded-lg px-3 py-1.5 text-xs font-medium text-slate-700 focus:outline-none">
                                </div>
                                <input type="hidden" name="grade_id_dominan" :value="samplingInput.gradeIdDominan">
                                <input type="hidden" name="survival_rate_estimasi" :value="samplingInput.hitungSR">
                                {{-- REVISI 2: Kirim jumlah ekor sampling (total_serokan sebagai jumlah sampel) --}}
                                <input type="hidden" name="jumlah_ekor_sampling" :value="samplingInput.total_serokan">
                                <button type="submit"
                                    :disabled="!samplingInput.total_serokan || samplingInput.total_serokan <= 0 || samplingInput
                                        .totalTerklasifikasi > samplingInput.total_serokan"
                                    :class="(!samplingInput.total_serokan || samplingInput.total_serokan <= 0 || samplingInput
                                        .totalTerklasifikasi > samplingInput.total_serokan) ?
                                    'bg-slate-300 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700'"
                                    class="w-full py-2 text-white rounded-lg font-black text-xs uppercase tracking-wider transition-all">
                                    <i class="fa-solid fa-square-plus mr-0.5"></i> Simpan
                                </button>
                            </div>
                        </form>
                    @endif

                    {{-- Arsip Sejarah Log Sampling --}}
                    <div class="flex-1 overflow-y-auto mt-3 border border-slate-100 rounded-xl min-h-[160px]">
                        <table class="w-full text-left text-[11px] border-collapse">
                            <thead
                                class="bg-slate-50 text-slate-400 uppercase font-black tracking-wider border-b sticky top-0 z-10">
                                <tr>
                                    <th class="p-2 text-center w-10">No</th>
                                    <th class="p-2 w-32">Waktu Sampling</th>
                                    <th class="p-2 text-center w-20">Media</th>
                                    <th class="p-2 text-center">Grade</th>
                                    <th class="p-2 text-right">SR</th>
                                    @if (in_array(Auth::user()->role, ['admin', 'operator']))
                                        <th class="p-2 text-center w-14">Aksi</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y text-slate-700 font-bold">
                                <template
                                    x-for="(log, idx) in samplingMasterList.filter(s => s.siklus_id == selectedSiklusId)"
                                    :key="log.id">
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="p-2 text-center text-slate-400" x-text="idx + 1"></td>
                                        <td class="p-2 text-slate-600 font-medium"
                                            x-text="new Date(log.tanggal_sampling).toLocaleDateString('id-ID', {day: '2-digit', month: 'short', year: 'numeric'})">
                                        </td>
                                        <td class="p-2 text-center">
                                            <template x-if="log.path_foto">
                                                <a :href="'/storage/' + log.path_foto" target="_blank"
                                                    class="inline-flex items-center gap-1 bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded text-[9px] hover:bg-blue-100 transition-colors">
                                                    <i class="fa-solid fa-image"></i> Lihat
                                                </a>
                                            </template>
                                            <template x-if="!log.path_foto">
                                                <span class="text-slate-300 text-[10px] font-normal italic">No
                                                    media</span>
                                            </template>
                                        </td>
                                        <td class="p-2 text-center uppercase text-blue-600" x-text="log.nama_grade">
                                        </td>
                                        <td class="p-2 text-right text-emerald-600"
                                            x-text="Math.round(log.sr_percent || log.sr_persen) + '%'"></td>

                                        @if (in_array(Auth::user()->role, ['admin', 'operator']))
                                            <td class="p-2 text-center flex items-center justify-center gap-3">
                                                <button type="button"
                                                    @click="openEditRowSampling = true; editRowData = { id: log.id, grade_id: log.grade_id, sr: Math.round(log.sr_percent || log.sr_persen), tanggal: log.tanggal_sampling.substring(0, 16), keterangan: log.keterangan || '' }"
                                                    class="text-blue-600 hover:text-blue-800 focus:outline-none">
                                                    <i class="fa-solid fa-pen-to-square text-xs"></i>
                                                </button>
                                                <form :action="'/monitoring-kolam/sampling-destroy/' + log.id"
                                                    method="POST"
                                                    onsubmit="return confirm('Hapus log sampling ini? Sistem otomatis hitung mundur.')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit"
                                                        class="text-rose-500 hover:text-rose-700 focus:outline-none">
                                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        @endif
                                    </tr>
                                </template>
                                <tr
                                    x-show="samplingMasterList.filter(s => s.siklus_id == selectedSiklusId).length === 0">
                                    <td :colspan="in_array(Auth::user()->role, ['admin', 'operator']) ? 6 : 5"
                                        class="p-6 text-center text-slate-400 italic font-medium">Belum terbit rekam
                                        sejarah log serokan pada siklus kolam ini.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- POP-UP SUB-MODAL LAYER INTERNAL EDIT SAMPLING --}}
                    <div x-show="openEditRowSampling"
                        class="fixed inset-0 z-[250] flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs text-left shadow-2xl"
                        x-cloak x-transition>
                        <div @click.away="openEditRowSampling = false"
                            class="bg-white w-full max-w-sm rounded-2xl p-5 border shadow-xl">
                            <h3 class="text-sm font-black text-slate-900 uppercase tracking-tight mb-3">Revisi Log
                                Sampling Visual</h3>
                            <form :action="'/monitoring-kolam/sampling-update/' + editRowData.id" method="POST"
                                enctype="multipart/form-data" class="space-y-3">
                                @csrf @method('PUT')
                                <div>
                                    <label class="text-[9px] font-bold text-slate-400 uppercase">Grade Kualitas Benur
                                        <span class="text-rose-500">*</span></label>
                                    <select name="grade_id_dominan" x-model="editRowData.grade_id" required
                                        class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-bold text-slate-800 focus:outline-none">
                                        @foreach ($list_grade as $g)
                                            <option value="{{ $g->id }}">{{ $g->nama_grade }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="text-[9px] font-bold text-slate-400 uppercase">Survival Rate (SR)
                                            <span class="text-rose-500">*</span></label>
                                        <input type="number" name="survival_rate_estimasi" x-model="editRowData.sr"
                                            min="0" max="100" required
                                            class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-black text-emerald-600 focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="text-[9px] font-bold text-slate-400 uppercase">Waktu Log Baru
                                            <span class="text-rose-500">*</span></label>
                                        <input type="datetime-local" name="tanggal_sampling"
                                            x-model="editRowData.tanggal" required
                                            class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-2 py-1 text-xs font-bold text-slate-800 focus:outline-none">
                                    </div>
                                </div>
                                <div>
                                    <label class="text-[9px] font-bold text-slate-400 uppercase">Perbarui Foto Media
                                        <span class="text-slate-400">(Opsional)</span></label>
                                    <input type="file" name="foto_sampling" accept="image/*"
                                        class="w-full mt-1 text-[10px] text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded-md file:border-0 file:bg-blue-50 file:text-blue-700">
                                </div>
                                <div>
                                    <label class="text-[9px] font-bold text-slate-400 uppercase">Keterangan
                                        Tambahan</label>
                                    <input type="text" name="keterangan" x-model="editRowData.keterangan"
                                        class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-medium text-slate-700 focus:outline-none">
                                </div>
                                <div class="flex gap-2 pt-1.5">
                                    <button type="button" @click="openEditRowSampling = false"
                                        class="flex-1 py-2 bg-slate-100 text-slate-500 rounded-lg font-bold text-xs text-center">Batal</button>
                                    <button type="submit"
                                        class="flex-1 py-2 bg-blue-600 text-white rounded-lg font-black text-xs text-center uppercase tracking-wider">Simpan
                                        Perubahan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 6. MODAL DAFTAR SLOT PREORDER AKTIF CUSTOMER --}}
            <div x-show="openModalBooking"
                class="fixed inset-0 z-[150] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
                x-cloak x-transition>
                <div @click.away="openModalBooking = false"
                    class="bg-white w-full max-w-md rounded-2xl p-5 shadow-xl max-h-[80vh] flex flex-col">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <h2 class="text-base font-extrabold text-slate-800 mb-1 uppercase">Daftar Slot Preorder
                                Aktif</h2>
                            <p class="text-[11px] font-black text-blue-600 uppercase" x-text="selectedKolamNama"></p>
                        </div>
                        <button @click="openModalBooking = false"
                            class="text-slate-400 hover:text-slate-600 text-sm focus:outline-none"><i
                                class="fa-solid fa-xmark text-base"></i></button>
                    </div>

                    <div class="flex-1 overflow-y-auto mb-2 border border-slate-100 rounded-xl">
                        <table class="w-full text-left text-[11px] border-collapse">
                            <thead
                                class="bg-slate-50 text-slate-400 uppercase font-black tracking-wider border-b sticky top-0">
                                <tr>
                                    <th class="p-2 text-center">No</th>
                                    <th class="p-2">No. Invoice / Pembeli</th>
                                    <th class="p-2 text-right">Volume Booking</th>
                                    <th class="p-2 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y text-slate-700 font-bold">
                                <template
                                    x-for="(book, index) in all_bookings.filter(b => b.siklus_id == selectedSiklusId)"
                                    :key="book.id">
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="p-2 text-center text-slate-400" x-text="index + 1"></td>
                                        <td class="p-2">
                                            <div class="flex flex-col">
                                                <span class="text-slate-900 font-extrabold"
                                                    x-text="book.nomor_invoice"></span>
                                                <span class="text-[9px] text-slate-400"
                                                    x-text="book.cust_name"></span>
                                            </div>
                                        </td>
                                        <td class="p-2 text-right text-blue-600"
                                            x-text="parseInt(book.total_ekor_riil).toLocaleString('id-ID') + ' ekor'">
                                        </td>
                                        <td class="p-2 text-center">
                                            <span
                                                class="text-[8px] px-1.5 py-0.5 rounded font-black uppercase text-amber-700 bg-amber-50"
                                                x-text="book.order_status"></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- 7. MODAL MASTER FISIK EDIT INFRASTRUKTUR KOLAM (Hanya Muncul Jika Admin Utama) --}}
            @if (Auth::user()->role === 'admin')
                <div x-show="openModalEditFisikKolam"
                    class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs"
                    x-cloak x-transition>
                    <div @click.away="openModalEditFisikKolam = false"
                        class="bg-white w-full max-w-sm rounded-2xl p-5 shadow-xl">
                        <h2 class="text-base font-extrabold text-slate-800 mb-3 uppercase">Ubah Master Fisik Kolam</h2>
                        <form
                            :action="'{{ route('kolam.update', ['id' => 'PLACEHOLDER_ID']) }}'.replace('PLACEHOLDER_ID',
                                editFisikData.id)"
                            method="POST" class="space-y-3">
                            @csrf @method('PUT')
                            <div>
                                <label class="text-[9px] font-bold text-slate-400 uppercase">Nama Kolam / Blok <span
                                        class="text-rose-500">*</span></label>
                                <input type="text" name="nama_kolam" x-model="editFisikData.nama_kolam" required
                                    class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none">
                            </div>
                            <div>
                                <label class="text-[9px] font-bold text-slate-400 uppercase">Kapasitas Maksimal (Ekor)
                                    <span class="text-rose-500">*</span></label>
                                <input type="number" name="kapasitas" x-model="editFisikData.kapasitas" required
                                    placeholder="Volume kolam"
                                    class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none">
                            </div>
                            <div class="flex gap-2 pt-1.5">
                                <button type="button" @click="openModalEditFisikKolam = false"
                                    class="flex-1 py-2 bg-slate-100 text-slate-500 rounded-lg font-bold text-xs">Batal</button>
                                <button type="submit"
                                    class="flex-1 py-2 bg-blue-600 text-white rounded-lg font-black text-xs uppercase tracking-wider">Simpan
                                    Perubahan</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
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
                transform: scale(0.99);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }
    </style>
</x-app-layout>