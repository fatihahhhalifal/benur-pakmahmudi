<x-customer-layout>
    @php
        foreach ($katalog as $prod) {
            $totalBooking = \Illuminate\Support\Facades\DB::table('pesanan')
                ->join('detail_pesanan', 'pesanan.id', '=', 'detail_pesanan.pesanan_id')
                ->where('detail_pesanan.siklus_id', $prod->siklus_id)
                ->whereIn('pesanan.status', ['pending', 'proses'])
                ->sum(DB::raw('((detail_pesanan.jumlah_sak_dipesan * 45) + detail_pesanan.kantong_eceran_dipesan) * COALESCE(detail_pesanan.konversi_per_kantong, 1700)'));
            $stokAktual = $prod->stok_tersedia - $totalBooking;
            $prod->stok_tersedia = $stokAktual < 0 ? 0 : $stokAktual;

            $prod->foto_terbaru = \Illuminate\Support\Facades\DB::table('riwayat_sampling')
                ->where('siklus_id', $prod->siklus_id)
                ->whereNotNull('path_foto')
                ->orderBy('tanggal_sampling', 'desc')
                ->value('path_foto');

            $prod->tgl_foto = \Illuminate\Support\Facades\DB::table('riwayat_sampling')
                ->where('siklus_id', $prod->siklus_id)
                ->whereNotNull('path_foto')
                ->orderBy('tanggal_sampling', 'desc')
                ->value('tanggal_sampling');
        }
    @endphp

    <div x-data="{ searchQuery: '', showFilter: false }" class="font-sans">

        <div class="max-w-4xl mx-auto">

            {{-- Flash success --}}
            @if(session('success'))
                <div class="mb-4 flex items-center gap-2.5 px-4 py-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl text-xs font-bold">
                    <i class="fa-solid fa-circle-check text-emerald-500 shrink-0"></i>
                    {{ session('success') }}
                </div>
            @endif

            {{-- ── GREETING ── --}}
            <div class="mb-5">
                <div class="flex items-center gap-2">
                    <span class="text-xl" style="filter: drop-shadow(0 0 4px rgba(249,115,22,0.9)) drop-shadow(0 0 12px rgba(234,88,12,0.6));">🔥</span>
                    {{-- Warna: hitam pekat --}}
                    <h1 class="text-[19px] font-black text-slate-900 tracking-tight">
                        Halo, {{ Auth::user()->name }}!
                    </h1>
                </div>
                {{-- Warna: abu-abu --}}
                <p class="text-[12px] text-slate-500 font-medium mt-0.5 ml-8">
                    Dapatkan benur berkualitas terbaik hari ini.
                </p>
            </div>

            {{-- ── BANNER ── --}}
            <div class="relative overflow-hidden rounded-2xl bg-[#2A64F6] mb-6
                        shadow-[0_8px_24px_rgba(42,100,246,0.22)]">

                <div class="absolute -top-10 -left-10 w-44 h-44 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
                <div class="absolute -bottom-8 right-10 w-32 h-32 bg-white/5 rounded-full blur-xl pointer-events-none"></div>

                <div class="relative z-10 grid grid-cols-2 gap-3 p-5">

                    {{-- Kiri: teks --}}
                    <div class="flex flex-col justify-between">
                        <div>
                            <div class="w-8 h-8 bg-white/20 rounded-xl flex items-center justify-center mb-3">
                                <i class="fa-solid fa-shield-halved text-white text-sm"></i>
                            </div>
                            {{-- Warna: biru muda/putih pudar --}}
                            <p class="text-[9px] font-black uppercase tracking-[0.16em] text-blue-200 mb-1.5">
                                Kualitas Terjamin
                            </p>
                            {{-- Warna: putih --}}
                            <h2 class="text-[15px] font-black text-white leading-snug">
                                Hasil panen maksimal,<br>keuntungan meningkat.
                            </h2>
                        </div>
                        <div class="mt-4">
                            {{-- Warna: putih --}}
                            <span class="inline-flex items-center gap-1.5 bg-white/20 border border-white/20
                                         px-3 py-1.5 rounded-xl text-[10px] font-black text-white">
                                <i class="fa-solid fa-lock text-[9px]"></i>
                                TOTAL PESANAN: {{ number_format($stats['total_pesanan'], 0, ',', '.') }}
                            </span>
                        </div>
                    </div>

                    {{-- Kanan: fitur card --}}
                    <div class="bg-white/10 border border-white/15 rounded-[14px] p-3 flex flex-col justify-center gap-2.5">
                        @foreach([
                            ['fa-shield-virus', 'Benur Sehat',    'Bebas penyakit'],
                            ['fa-dna',          'Genetik Unggul', 'Pertumbuhan optimal'],
                            ['fa-box-open',     'Proses Terjaga', 'Panen & packing higienis'],
                        ] as [$ico, $title, $sub])
                        <div class="flex items-center gap-2">
                            <div class="w-5 h-5 bg-white/20 rounded-full flex items-center justify-center shrink-0">
                                <i class="fa-solid {{ $ico }} text-[8px] text-white"></i>
                            </div>
                            <div>
                                {{-- Warna: putih --}}
                                <p class="text-[9px] font-black text-white leading-none">{{ $title }}</p>
                                {{-- Warna: biru muda --}}
                                <p class="text-[8px] text-blue-200 font-medium leading-none mt-0.5">{{ $sub }}</p>
                            </div>
                        </div>
                        @if(!$loop->last)
                            <div class="border-t border-white/10"></div>
                        @endif
                        @endforeach
                    </div>

                </div>
            </div>

            {{-- ── HEADING + SEARCH + FILTER ── --}}
            <div class="mb-4">
                <div class="flex items-center justify-between mb-3">
                    {{-- Warna: "Katalog" hitam, "Benur Terbaru" biru --}}
                    <h2 class="text-[15px] font-black text-slate-900">
                        Katalog <span class="text-blue-600">Benur Terbaru</span>
                    </h2>
                    {{-- Warna: biru --}}
                    <a href="#" class="text-[11px] font-bold text-blue-600 flex items-center gap-1">
                        Lihat Semua <i class="fa-solid fa-chevron-right text-[9px]"></i>
                    </a>
                </div>

                <div class="flex gap-2">
                    <div class="relative flex-1">
                        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                        <input
                            type="text"
                            x-model="searchQuery"
                            placeholder="Cari kolam, jenis, ukuran, atau grade..."
                            class="w-full bg-white border border-slate-200 rounded-2xl pl-9 pr-4 py-2.5
                                   text-[12px] font-medium text-slate-700 placeholder:text-slate-400
                                   focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500
                                   transition-all shadow-sm">
                    </div>
                    <button @click="showFilter = !showFilter"
                        class="flex items-center gap-1.5 px-3.5 py-2.5 bg-white border border-slate-200
                               rounded-2xl shadow-sm text-[12px] font-bold text-slate-600
                               hover:bg-slate-50 transition-colors shrink-0">
                        <i class="fa-solid fa-sliders text-blue-500 text-xs"></i>
                        Filter
                    </button>
                </div>
            </div>

            {{-- ── GRID PRODUK ── --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">

                @forelse($katalog as $prod)
                    @php $habis = $prod->stok_tersedia <= 0 || $prod->harga_saat_ini <= 0; @endphp

                    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden flex flex-col
                                transition-transform active:scale-[0.98]"
                         x-show="searchQuery === ''
                               || '{{ strtolower($prod->nama_jenis) }}'.includes(searchQuery.toLowerCase())
                               || '{{ strtolower($prod->nama_kolam) }}'.includes(searchQuery.toLowerCase())">

                        {{-- Foto --}}
                        <div class="relative w-full aspect-[4/3] bg-slate-100 overflow-hidden">
                            @if($prod->foto_terbaru)
                                <img src="{{ asset('storage/'.$prod->foto_terbaru) }}"
                                     alt="{{ $prod->nama_kolam }}"
                                     class="w-full h-full object-cover {{ $habis ? 'opacity-50 grayscale' : '' }}">
                                <div class="absolute inset-x-0 bottom-0 h-10
                                            bg-gradient-to-t from-black/50 to-transparent
                                            flex items-end px-2 pb-1.5">
                                    <span class="text-[8px] font-bold text-white/90 flex items-center gap-1">
                                        <i class="fa-solid fa-camera text-[7px]"></i>
                                        {{ $prod->tgl_foto ? \Carbon\Carbon::parse($prod->tgl_foto)->diffForHumans() : 'Foto Aktual' }}
                                    </span>
                                </div>
                            @else
                                <div class="w-full h-full bg-gradient-to-br from-slate-100 to-blue-50
                                            flex items-center justify-center">
                                    <i class="fa-solid fa-shrimp text-4xl text-blue-200/60"></i>
                                </div>
                            @endif

                            {{-- DOC badge: bg biru, teks putih --}}
                            <span class="absolute top-2 left-2 bg-blue-600 text-white
                                         text-[8px] font-black px-2 py-0.5 rounded-lg shadow-sm">
                                DOC {{ $prod->doc }}
                            </span>

                            {{-- Wishlist --}}
                            <button class="absolute top-2 right-2 w-7 h-7 bg-white rounded-full shadow-md
                                           flex items-center justify-center">
                                <i class="fa-regular fa-heart text-slate-400 text-[11px]"></i>
                            </button>

                            @if($habis)
                                <div class="absolute inset-0 flex items-center justify-center bg-black/20">
                                    <span class="bg-slate-800/80 text-white text-[9px] font-black px-2.5 py-1 rounded-lg backdrop-blur-sm">
                                        Stok Habis
                                    </span>
                                </div>
                            @endif
                        </div>

                        {{-- Body --}}
                        <div class="p-3 flex flex-col flex-1 gap-2">

                            {{-- Nama: hitam pekat | Jenis: abu-abu --}}
                            <div>
                                <p class="text-[13px] font-black text-slate-900 leading-tight truncate">{{ $prod->nama_kolam }}</p>
                                <p class="text-[10px] text-slate-400 font-medium truncate mt-0.5">{{ $prod->nama_jenis ?? 'Vaname' }}</p>
                            </div>

                            {{-- Grade: amber | PL: biru --}}
                            <div class="flex items-center gap-1.5">
                                <span class="inline-flex items-center gap-1 text-[9px] font-black text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded-md">
                                    <i class="fa-solid fa-star text-amber-400 text-[7px]"></i>
                                    {{ $prod->nama_grade ?? 'Grade Unggulan' }}
                                </span>
                                <span class="text-[9px] font-black text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded-md">
                                    PL {{ $prod->label_ukuran ?? '5' }}
                                </span>
                            </div>

                            {{-- Stok + Harga --}}
                            <div class="grid grid-cols-2 gap-1 pt-2 border-t border-slate-100">
                                <div>
                                    {{-- Label: abu-abu --}}
                                    <p class="text-[8px] text-slate-400 font-semibold mb-0.5">Sisa Kuota Bebas</p>
                                    {{-- Angka stok:
                                         - habis  → merah
                                         - besar (≥ 1jt) → hijau (seperti "2.000.000" di gambar)
                                         - normal → hitam slate-800
                                         "ekor" → abu-abu kecil
                                    --}}
                                    @if($habis)
                                        <p class="text-[11px] font-black text-rose-500 leading-none">
                                            {{ number_format($prod->stok_tersedia, 0, ',', '.') }}
                                            <span class="text-[8px] font-medium text-slate-400">ekor</span>
                                        </p>
                                    @elseif($prod->stok_tersedia >= 1000000)
                                        <p class="text-[11px] font-black text-emerald-600 leading-none">
                                            {{ number_format($prod->stok_tersedia, 0, ',', '.') }}
                                            <span class="text-[8px] font-medium text-slate-400">ekor</span>
                                        </p>
                                    @else
                                        <p class="text-[11px] font-black text-slate-800 leading-none">
                                            {{ number_format($prod->stok_tersedia, 0, ',', '.') }}
                                            <span class="text-[8px] font-medium text-slate-400">ekor</span>
                                        </p>
                                    @endif
                                </div>
                                <div>
                                    {{-- Label: abu-abu --}}
                                    <p class="text-[8px] text-slate-400 font-semibold mb-0.5">Harga / Ekor</p>
                                    {{-- Harga: hitam pekat --}}
                                    @if($prod->harga_saat_ini > 0)
                                        <p class="text-[13px] font-black text-slate-900 leading-none">
                                            Rp {{ number_format($prod->harga_saat_ini, 0, ',', '.') }}
                                        </p>
                                    @else
                                        <p class="text-[10px] font-black text-rose-400 leading-none">N/A</p>
                                    @endif
                                </div>
                            </div>

                            {{-- CTA --}}
                            <div class="flex gap-1.5 mt-auto pt-1">
                                <form action="{{ route('customer.keranjang.add') }}" method="POST" class="shrink-0">
                                    @csrf
                                    <input type="hidden" name="kolam_id"       value="{{ $prod->kolam_id }}">
                                    <input type="hidden" name="jumlah_sak"     value="1">
                                    <input type="hidden" name="kantong_eceran" value="0">
                                    {{-- Ikon keranjang: biru di bg biru muda --}}
                                    <button type="submit"
                                        class="w-9 h-9 rounded-xl flex items-center justify-center transition-colors
                                               {{ !$habis
                                                    ? 'bg-blue-50 border border-blue-100 text-blue-600 hover:bg-blue-100'
                                                    : 'bg-slate-50 border border-slate-200 text-slate-300 cursor-not-allowed' }}"
                                        {{ $habis ? 'disabled' : '' }}>
                                        <i class="fa-solid fa-cart-shopping text-sm"></i>
                                    </button>
                                </form>
                                {{-- Tombol: bg biru, teks putih --}}
                                <a href="{{ route('customer.katalog.show', $prod->siklus_id) }}"
                                   class="flex-1 h-9 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-xl
                                          flex items-center justify-center gap-1.5
                                          text-[10px] font-black transition-colors">
                                    Lihat Detail <i class="fa-solid fa-arrow-right text-[8px]"></i>
                                </a>
                            </div>

                        </div>
                    </div>

                @empty
                    <div class="col-span-full py-16 flex flex-col items-center gap-3
                                bg-white rounded-2xl border border-slate-100">
                        <i class="fa-solid fa-box-open text-4xl text-slate-200"></i>
                        <p class="text-sm font-black text-slate-600">Katalog Kosong</p>
                        <p class="text-xs text-slate-400">Belum ada benur tersedia saat ini.</p>
                    </div>
                @endforelse

            </div>

        </div>
    </div>
</x-customer-layout>