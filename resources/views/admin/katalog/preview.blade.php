<x-app-layout>
    <div class="max-w-7xl mx-auto font-sans text-slate-800 antialiased animate-fade-in">

        {{-- BANNER WARNING MODE PRATINJAU (QUALITY CONTROL ACTION) --}}
        <div
            class="mb-6 p-4 bg-gradient-to-r from-amber-500 to-orange-600 text-white rounded-2xl shadow-md flex flex-col sm:flex-row items-center justify-between gap-3 select-none">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center text-lg backdrop-blur-md">
                    <i class="fa-solid fa-magnifying-glass-chart"></i>
                </div>
                <div>
                    <h2 class="text-sm font-black uppercase tracking-wider">Mode Pratinjau Etalase (QC Mode)</h2>
                    <p class="text-[11px] text-amber-100 font-medium">Halaman pratinjau visual live market sisi
                        pelanggan. Simulasi tombol checkout dinonaktifkan demi keamanan kas.</p>
                </div>
            </div>
            <a href="{{ route('dashboard') }}"
                class="bg-white text-orange-700 px-4 py-1.5 rounded-xl text-xs font-black shadow-sm uppercase tracking-wider transition-all hover:bg-slate-50 active:scale-95">
                <i class="fa-solid fa-circle-arrow-left mr-0.5"></i> KEMBALI KONTROL
            </a>
        </div>

        {{-- HEADER ETALASE PASAR --}}
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <nav
                    class="flex items-center gap-1.5 text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">
                    <span>AQUAFARM MARKETPLACE</span>
                    <i class="fa-solid fa-chevron-right text-[6px] text-slate-300"></i>
                    <span class="text-blue-600">Katalog Benur Udang Vaname</span>
                </nav>
                <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Etalase Komoditas Live Pembeli
                </h1>
            </div>
            <div class="text-xs font-bold text-slate-400 bg-white border px-4 py-2 rounded-xl shadow-2xs">
                🟢 Total Produk Tayang: <span class="text-slate-800 font-black">{{ $produk_live->count() }}
                    Varietas</span>
            </div>
        </div>

        {{-- STRUKTUR GRID KARTU PRODUK ETALASE MARKETPLACE --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($produk_live as $prod)
                <div
                    class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden flex flex-col hover:shadow-md transition-all duration-300 group">

                    {{-- Visual Top Card: Efek Air & Badges --}}
                    <div
                        class="p-6 bg-gradient-to-br from-blue-50 to-indigo-50/40 relative overflow-hidden border-b border-slate-100/60">
                        {{-- Hiasan Shrimp Ikon Transparan Belakang Card --}}
                        <i
                            class="fa-solid fa-shrimp absolute -right-4 -bottom-4 text-7xl text-blue-600/5 rotate-12 pointer-events-none group-hover:scale-110 transition-transform duration-500"></i>

                        <div class="flex justify-between items-start mb-4 relative z-10">
                            <span
                                class="bg-blue-600 text-white text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full shadow-xs shadow-blue-500/20 border border-white/10">
                                LOC: {{ $prod->nama_kolam }}
                            </span>
                            <span
                                class="bg-emerald-50 text-emerald-700 border border-emerald-200 text-[9px] font-black uppercase tracking-wider px-2.5 py-1 rounded-md">
                                {{ $prod->nama_grade ?? 'Grade A' }}
                            </span>
                        </div>

                        <h3
                            class="text-lg font-black text-slate-900 tracking-tight group-hover:text-blue-600 transition-colors uppercase leading-none mt-2 relative z-10">
                            {{ $prod->nama_jenis }} Super PL-{{ $prod->label_ukuran }}
                        </h3>
                        <p class="text-[11px] text-slate-400 font-bold mt-1.5 relative z-10 uppercase tracking-wide">
                            Siklus Budidaya Hulu • Umur DOC {{ $prod->doc }} Hari
                        </p>
                    </div>

                    {{-- Spesifikasi & Detail Nilai Stok Ekor --}}
                    <div class="p-6 flex-1 flex flex-col justify-between space-y-5">
                        <div
                            class="grid grid-cols-2 gap-4 divide-x divide-slate-100 text-xs font-bold text-slate-500 select-none">
                            <div class="space-y-0.5">
                                <span
                                    class="text-[10px] text-slate-400 font-bold uppercase tracking-wide block">Kapasitas
                                    Lapangan</span>
                                <span
                                    class="text-slate-800 font-black text-sm block">{{ number_format($prod->stok_tersedia, 0, ',', '.') }}
                                    <span class="text-[10px] font-bold text-slate-400">ekor</span></span>
                            </div>
                            <div class="space-y-0.5 pl-4">
                                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wide block">Harga
                                    Kontrak Jual</span>
                                {{-- Gunakan pengecekan data agar tidak error jika property kosong --}}
                                <span class="text-blue-600 font-black text-sm block">
                                    Rp {{ number_format($prod->harga_per_ekor ?? 0, 0, ',', '.') }}
                                    <span class="text-[10px] font-bold text-slate-400">/ ekor</span>
                                </span>
                            </div>
                        </div>

                        {{-- Tombol Aksi Simulasi Checkout Belanja --}}
                        <div class="pt-2">
                            <button type="button" disabled
                                class="w-full flex items-center justify-center gap-2 py-3 bg-slate-100 border border-slate-200 text-slate-400 rounded-2xl font-black text-xs uppercase tracking-widest cursor-not-allowed select-none transition-all">
                                <i class="fa-solid fa-cart-plus text-sm"></i> SIMULASI CHECKOUT LOCKED
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div
                    class="col-span-full bg-white border rounded-3xl p-12 text-center text-slate-400 italic font-medium shadow-2xs">
                    Etalase kosong. Belum ada benur udang dari siklus kolam aktif hulu yang didaftarkan tayang pasar,
                    Partner.
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
