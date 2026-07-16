<x-customer-layout>
    @php
        $profilTambak = \Illuminate\Support\Facades\DB::table('profil_tambak')->first();
        $namaTambak   = $profilTambak ? $profilTambak->nama_tambak : 'Aquafarm Official';

        $hargaReal = \Illuminate\Support\Facades\DB::table('master_harga')
            ->where('jenis_id',  $produk->jenis_id)
            ->where('ukuran_id', $produk->ukuran_id)
            ->where('grade_id',  $produk->grade_id)
            ->value('harga_jual') ?? $produk->harga_saat_ini;

        $galeri      = \Illuminate\Support\Facades\DB::table('riwayat_sampling')
            ->where('siklus_id', $produk->id)
            ->orderBy('tanggal_sampling', 'desc')
            ->get();
        $fotoTerbaru = $galeri->whereNotNull('path_foto')->first();

        $deskripsiJenis  = $produk->deskripsi_jenis  ?? 'Benih Vaname unggul dengan SR tinggi dan adaptif.';
        $deskripsiUkuran = $produk->deskripsi_ukuran ?? 'Post Larva ukuran ekspor, siap tebar dengan organ sempurna.';
        $deskripsiGrade  = $produk->deskripsi_grade  ?? 'Kualitas Super, lincah, aktif, dan teruji bebas penyakit.';
    @endphp

    <div x-data="productDetail()"
         x-init="@if(session('success')) isCartOpen = true @endif"
         class="font-sans text-slate-800 pb-40 md:pb-10">

        {{-- ── LIGHTBOX ── --}}
        <div x-show="isLightboxOpen"
             x-transition.opacity
             class="fixed inset-0 z-[100] bg-black/90 flex items-center justify-center p-4"
             x-cloak>
            <button @click="isLightboxOpen = false"
                    class="absolute top-4 right-4 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20
                           flex items-center justify-center text-white transition-colors">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
            <img :src="activeImage"
                 class="max-w-full max-h-[88vh] object-contain rounded-xl"
                 @click.away="isLightboxOpen = false">
        </div>

        {{-- ── PAGE WRAPPER ── --}}
        <div class="max-w-4xl mx-auto px-4 md:px-0 pt-4 md:pt-6">

            <form method="POST" id="productForm">
                @csrf
                <input type="hidden" name="kolam_id"       value="{{ $produk->kolam_id }}">
                <input type="hidden" name="siklus_id"      value="{{ $produk->id }}">
                <input type="hidden" name="harga_kontrak"  value="{{ $hargaReal }}">
                <input type="hidden" name="jumlah_sak"     :value="sak">
                <input type="hidden" name="kantong_eceran" :value="ecer">

                {{-- ── GRID DUA KOLOM (desktop) / SATU KOLOM (mobile) ── --}}
                <div class="flex flex-col md:grid md:grid-cols-2 gap-6 md:items-start">

                    {{-- ════════════════════════════════════
                         KOLOM KIRI — Foto + Galeri + Jaminan
                         ════════════════════════════════════ --}}
                    <div>

                        {{-- ── HERO FOTO ── --}}
                        <div class="relative w-full aspect-[4/3] rounded-2xl overflow-hidden bg-slate-100 mb-3">
                            <div @click="if(activeImage) isLightboxOpen = true" class="w-full h-full cursor-zoom-in">
                                <template x-if="activeImage">
                                    <img :src="activeImage" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!activeImage">
                                    <div class="w-full h-full bg-gradient-to-br from-slate-100 to-blue-50
                                                flex items-center justify-center">
                                        <i class="fa-solid fa-shrimp text-6xl text-blue-200/50"></i>
                                    </div>
                                </template>
                            </div>

                            {{-- badges atas --}}
                            <div class="absolute top-3 left-3 flex gap-1.5">
                                <span class="bg-blue-600 text-white text-[9px] font-black px-2 py-1 rounded-lg shadow-sm">
                                    DOC {{ $produk->doc }} Hari
                                </span>
                            </div>
                            @if($fotoTerbaru?->tanggal_sampling)
                            <div class="absolute top-3 right-3
                                        bg-teal-500/85 backdrop-blur-sm text-white text-[8px] font-black
                                        px-2 py-1 rounded-lg flex items-center gap-1">
                                <i class="fa-solid fa-camera text-[7px]"></i>
                                Foto {{ \Carbon\Carbon::parse($fotoTerbaru->tanggal_sampling)->diffForHumans() }}
                            </div>
                            @endif

                            {{-- zoom hint --}}
                            <div class="absolute bottom-3 right-3
                                        bg-black/40 backdrop-blur-sm text-white text-[8px] font-bold
                                        px-2 py-1 rounded-lg flex items-center gap-1">
                                <i class="fa-solid fa-magnifying-glass-plus text-[7px]"></i> Zoom
                            </div>
                        </div>

                        {{-- ── THUMBNAIL GALERI ── --}}
                        @if($galeri->whereNotNull('path_foto')->count() > 1)
                        <div class="flex gap-2 mb-4 overflow-x-auto hide-scrollbar pb-0.5">
                            @foreach($galeri->whereNotNull('path_foto') as $g)
                            <button type="button"
                                    @click="activeImage = '{{ asset('storage/'.$g->path_foto) }}'"
                                    :class="activeImage === '{{ asset('storage/'.$g->path_foto) }}'
                                                ? 'border-blue-600 opacity-100'
                                                : 'border-transparent opacity-50 hover:opacity-75'"
                                    class="relative w-14 h-14 rounded-xl border-2 overflow-hidden shrink-0 transition-all bg-slate-100">
                                <img src="{{ asset('storage/'.$g->path_foto) }}" class="w-full h-full object-cover">
                                @if($loop->first)
                                <span class="absolute bottom-0 inset-x-0 bg-blue-600/80 text-white
                                             text-[6px] font-black text-center py-0.5">
                                    TERBARU
                                </span>
                                @endif
                            </button>
                            @endforeach
                        </div>
                        @endif

                        {{-- ── JAMINAN ── --}}
                        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 mb-3 md:mb-0">
                            <div class="grid grid-cols-3 gap-3 text-center">
                                @foreach([
                                    ['fa-shield-virus', 'Bebas Virus',  'Sertifikasi SPF'],
                                    ['fa-box-open',     'Kemasan Aman', 'Standar Oksigen'],
                                    ['fa-percent',      'Lock Price',   'Harga Terkunci'],
                                ] as [$ico, $title, $sub])
                                <div class="flex flex-col items-center gap-2">
                                    <div class="w-9 h-9 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center">
                                        <i class="fa-solid {{ $ico }} text-sm"></i>
                                    </div>
                                    <div>
                                        <p class="text-[9px] font-black text-slate-800 leading-none mb-0.5">{{ $title }}</p>
                                        <p class="text-[8px] text-slate-400 font-medium">{{ $sub }}</p>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>

                    </div>{{-- /kolom kiri --}}

                    {{-- ════════════════════════════════════
                         KOLOM KANAN — Info + Order + Komoditas
                         ════════════════════════════════════ --}}
                    <div class="space-y-4 md:space-y-3">

                        {{-- ── INFO PRODUK ── --}}
                        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
                            <div class="flex items-start justify-between gap-2 mb-3">
                                <h1 class="text-[17px] font-black text-slate-900 tracking-tight leading-tight">
                                    Benur {{ $produk->nama_jenis ?? 'Vaname' }}
                                </h1>
                                <span class="shrink-0 flex items-center gap-1 bg-emerald-50 text-emerald-600
                                             text-[8px] font-black px-2 py-1 rounded-lg">
                                    <i class="fa-solid fa-circle text-[5px] animate-pulse"></i> Ready
                                </span>
                            </div>

                            <div class="flex items-center gap-1.5 mb-4 pb-4 border-b border-slate-100">
                                <span class="bg-amber-50 text-amber-600 text-[9px] font-black px-2 py-1 rounded-lg flex items-center gap-1">
                                    <i class="fa-solid fa-star text-amber-400 text-[7px]"></i>
                                    Grade {{ $produk->nama_grade ?? 'Premium' }}
                                </span>
                                <span class="bg-blue-50 text-blue-600 text-[9px] font-black px-2 py-1 rounded-lg">
                                    PL {{ $produk->label_ukuran }}
                                </span>
                            </div>

                            <div class="flex items-end justify-between">
                                <div>
                                    <p class="text-[9px] font-bold text-slate-400 mb-1">Harga Kontrak / Ekor</p>
                                    <p class="text-[26px] font-black text-blue-600 leading-none tracking-tight">
                                        Rp{{ number_format($hargaReal, 0, ',', '.') }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-[9px] font-bold text-slate-400 mb-1">Stok Tersedia</p>
                                    <p class="text-[14px] font-black text-slate-800 leading-none">
                                        {{ number_format($produk->stok_tersedia, 0, ',', '.') }}
                                        <span class="text-[10px] font-medium text-slate-400">Ekor</span>
                                    </p>
                                    <p class="text-[9px] font-bold text-slate-400 leading-none mt-1">
                                        ≈ {{ floor(($produk->stok_tersedia ?? 0) / 1700 / 45) }} Sak +
                                        {{ floor((($produk->stok_tersedia ?? 0) / 1700)) % 45 }} Kantong
                                    </p>
                                </div>
                            </div>
                        </div>

                        {{-- ── KUANTITAS ── --}}
                        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-[13px] font-black text-slate-900">Kuantitas Order</h3>
                                <div class="flex flex-col items-end gap-0.5">
                                    <span class="text-[8px] font-black text-blue-600 bg-blue-50 px-2 py-0.5 rounded-md">
                                        1 Sak = 45 Kantong
                                    </span>
                                    <span class="text-[8px] font-black text-teal-600 bg-teal-50 px-2 py-0.5 rounded-md">
                                        1 Kantong = 1.700 Ekor
                                    </span>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3 mb-4 pb-4 border-b border-slate-100">
                                @foreach([['sak','Jumlah Sak','×45 Kantong','kurangSak','tambahSak'],['ecer','Jumlah Kantong','','kurangEcer','tambahEcer']] as [$var,$label,$sub,$minus,$plus])
                                <div>
                                    <p class="text-[9px] font-bold text-slate-500 mb-2">
                                        {{ $label }} @if($sub)<span class="text-slate-400">{{ $sub }}</span>@endif
                                    </p>
                                    <div class="flex items-center bg-slate-50 border border-slate-200 rounded-xl overflow-hidden">
                                        <button type="button" @click="{{ $minus }}"
                                                class="w-9 h-9 flex items-center justify-center text-blue-600 hover:bg-blue-50
                                                       active:bg-blue-100 transition-colors shrink-0">
                                            <i class="fa-solid fa-minus text-[10px]"></i>
                                        </button>
                                        <input type="number" x-model.number="{{ $var }}" min="0"
                                               class="flex-1 text-center bg-transparent border-none text-[15px] font-black
                                                      text-slate-800 focus:ring-0 p-0 w-full">
                                        <button type="button" @click="{{ $plus }}"
                                                class="w-9 h-9 flex items-center justify-center text-blue-600 hover:bg-blue-50
                                                       active:bg-blue-100 transition-colors shrink-0">
                                            <i class="fa-solid fa-plus text-[10px]"></i>
                                        </button>
                                    </div>
                                </div>
                                @endforeach
                            </div>

                            {{-- Preview volume --}}
                            <div class="bg-slate-50 rounded-xl border border-slate-100 p-3 space-y-2">
                                <div class="flex flex-wrap items-center gap-1.5 text-[10px]">
                                    <span class="text-slate-400 font-bold">Volume:</span>
                                    <span class="bg-white border border-slate-200 px-2 py-0.5 rounded-md font-black text-slate-700"
                                          x-text="sak + ' Sak'"></span>
                                    <template x-if="ecer > 0">
                                        <span class="text-slate-400">+</span>
                                    </template>
                                    <template x-if="ecer > 0">
                                        <span class="bg-white border border-slate-200 px-2 py-0.5 rounded-md font-black text-slate-700"
                                              x-text="ecer + ' Kantong'"></span>
                                    </template>
                                    <span class="text-slate-300">=</span>
                                    <span class="bg-blue-50 border border-blue-100 px-2 py-0.5 rounded-md font-black text-blue-600"
                                          x-text="(sak*45+ecer) + ' Kantong'"></span>
                                    <span class="text-slate-300">→</span>
                                    <span class="font-black text-blue-700" x-text="formatRupiah(totalEkor) + ' Ekor'"></span>
                                </div>
                                <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                                    <span class="text-[9px] text-slate-500 font-medium">Subtotal</span>
                                    <span class="text-[16px] font-black text-blue-600" x-text="'Rp' + formatRupiah(totalHarga)"></span>
                                </div>
                            </div>

                            {{-- ── CTA BUTTONS (SINKRON MOBILE & DESKTOP) ── --}}
                            <div class="fixed bottom-[70px] left-0 right-0 md:static md:bottom-auto md:left-auto md:right-auto
                                        bg-white/95 md:bg-transparent backdrop-blur md:backdrop-blur-none
                                        border-t border-slate-100 md:border-none
                                        px-4 py-3 md:px-0 md:py-0 md:mt-4
                                        z-40 flex gap-2.5
                                        shadow-[0_-6px_20px_rgba(0,0,0,0.06)] md:shadow-none">
                                <button type="button"
                                        @click="submitForm('{{ route('customer.keranjang.add') }}')"
                                        class="flex-1 h-12 bg-white border-2 border-blue-600 text-blue-600
                                               rounded-xl md:rounded-2xl flex items-center justify-center gap-2
                                               text-[13px] font-black hover:bg-blue-50 active:bg-blue-100 transition-colors">
                                    <i class="fa-solid fa-cart-plus"></i> Keranjang
                                </button>
                                <button type="button"
                                        @click="submitForm('{{ route('customer.katalog.checkout') }}')"
                                        class="flex-1 h-12 bg-blue-600 hover:bg-blue-700 text-white
                                               rounded-xl md:rounded-2xl flex items-center justify-center
                                               text-[13px] font-black shadow-lg shadow-blue-600/25
                                               active:scale-[0.98] transition-all">
                                    Beli Langsung
                                </button>
                            </div>

                        </div>

                        {{-- ── INFO KOMODITAS + SAMPLING ── --}}
                        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">

                            <h3 class="text-[13px] font-black text-slate-900 mb-3">Informasi Komoditas</h3>
                            <div class="space-y-3 mb-4 pb-4 border-b border-slate-100">
                                @foreach([
                                    [$produk->nama_jenis ?? 'Vaname',             $deskripsiJenis,  'fa-shrimp'],
                                    ['PL '.$produk->label_ukuran,                 $deskripsiUkuran, 'fa-ruler'],
                                    ['Grade '.($produk->nama_grade ?? 'Premium'), $deskripsiGrade,  'fa-award'],
                                ] as [$label, $desc, $ico])
                                <div class="flex gap-3">
                                    <div class="w-7 h-7 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center shrink-0 mt-0.5">
                                        <i class="fa-solid {{ $ico }} text-[10px]"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-[11px] font-black text-slate-800 mb-0.5">{{ $label }}</p>
                                        <p class="text-[10px] text-slate-500 font-medium leading-relaxed">{{ $desc }}</p>
                                    </div>
                                </div>
                                @endforeach
                            </div>

                            <h3 class="text-[13px] font-black text-slate-900 mb-3">Riwayat Uji Sampling</h3>
                            @if($galeri->count() > 0)
                                <div class="space-y-2">
                                    @foreach($galeri->take(5) as $log)
                                    <div class="flex items-center justify-between bg-slate-50 border border-slate-100 rounded-xl px-3 py-2">
                                        <div class="flex items-center gap-2 text-slate-500">
                                            <i class="fa-solid fa-flask-vial text-xs text-blue-400"></i>
                                            <span class="text-[10px] font-bold">
                                                {{ \Carbon\Carbon::parse($log->tanggal_sampling)->format('d M Y') }}
                                            </span>
                                        </div>
                                        <span class="text-[10px] font-black text-emerald-500">
                                            SR: {{ round($log->sr_persen) }}%
                                        </span>
                                    </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-[10px] text-slate-400 italic">Belum ada riwayat sampling.</p>
                            @endif

                        </div>

                    </div>{{-- /kolom kanan --}}

                </div>{{-- /grid --}}

            </form>

        </div>{{-- /page wrapper --}}

        {{-- ── NOTIF ── --}}
        <div x-show="showNotif"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-2"
             class="fixed bottom-40 left-4 right-4 md:left-auto md:right-6 md:w-80 md:bottom-10 z-50
                    rounded-2xl px-4 py-3 shadow-xl flex items-center gap-3"
             :class="notifType==='error' ? 'bg-rose-600' : 'bg-blue-600'"
             x-cloak>
            <i class="fa-solid text-white shrink-0"
               :class="notifType==='error' ? 'fa-circle-exclamation' : 'fa-circle-info'"></i>
            <p class="text-xs font-bold text-white flex-1" x-text="notifMsg"></p>
            <button @click="showNotif = false" class="text-white/70 hover:text-white transition-colors">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('productDetail', () => ({
                isLightboxOpen: false,
                activeImage:    '{{ $fotoTerbaru && $fotoTerbaru->path_foto ? asset('storage/'.$fotoTerbaru->path_foto) : '' }}',
                sak:   1,
                ecer:  0,
                harga:        {{ $hargaReal }},
                konversi:     1700,
                stokTersedia: {{ $produk->stok_tersedia ?? 0 }},
                showNotif:    false,
                notifMsg:     '',
                notifType:    'error',

                showError(msg) { this.notifMsg = msg; this.notifType = 'error'; this.showNotif = true; },
                showInfo(msg)  { this.notifMsg = msg; this.notifType = 'info';  this.showNotif = true; },

                get totalEkor()  { return ((parseInt(this.sak)||0)*45 + (parseInt(this.ecer)||0)) * this.konversi; },
                get totalHarga() { return this.totalEkor * this.harga; },

                formatRupiah(n) { return new Intl.NumberFormat('id-ID').format(n); },

                tambahSak()  {
                    if (((this.sak+1)*45+this.ecer)*this.konversi <= this.stokTersedia) this.sak++;
                    else this.showError('Melebihi stok tersedia (' + this.formatRupiah(this.stokTersedia) + ' Ekor)');
                },
                kurangSak()  { if (this.sak  > 0) this.sak--;  },
                tambahEcer() {
                    if ((this.sak*45+this.ecer+1)*this.konversi <= this.stokTersedia) this.ecer++;
                    else this.showError('Melebihi stok tersedia (' + this.formatRupiah(this.stokTersedia) + ' Ekor)');
                },
                kurangEcer() { if (this.ecer > 0) this.ecer--; },

                submitForm(url) {
                    if (!this.sak && !this.ecer) { this.showInfo('Tentukan kuantitas terlebih dahulu!'); return; }
                    if (this.totalEkor > this.stokTersedia) { this.showError('Melebihi stok tersedia!'); return; }
                    const f = document.getElementById('productForm');
                    f.action = url;
                    f.submit();
                },
            }));
        });
    </script>

    <style>
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</x-customer-layout>