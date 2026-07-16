<x-customer-layout>
    @php
        $grouped      = $pesanan->groupBy('id');
        $countSemua   = $grouped->filter(fn($g) => $g->first()->status != 'batal')->count();
        $countPending = $grouped->filter(fn($g) => $g->first()->status == 'pending')->count();
        $countProses  = $grouped->filter(fn($g) => in_array($g->first()->status, ['proses','menunggu_kalkulasi']))->count();
        $countTagihan = $grouped->filter(fn($g) => $g->first()->status == 'menunggu_pelunasan')->count();
        $countSelesai = $grouped->filter(fn($g) => $g->first()->status == 'selesai')->count();
        $countBatal   = $grouped->filter(fn($g) => $g->first()->status == 'batal')->count();
    @endphp

    <div x-data="{
        activeTab: 'semua',
        searchQuery: '',
        sortBy: 'terbaru',
        get filteredOrders() {
            let orders = Object.values(window._allOrders || {});
            if (this.activeTab === 'semua') {
                orders = orders.filter(o => o.status !== 'batal');
            } else {
                orders = orders.filter(o => {
                    if (this.activeTab === 'proses') return ['proses','menunggu_kalkulasi'].includes(o.status);
                    return o.status === this.activeTab;
                });
            }
            if (this.searchQuery.trim()) {
                const q = this.searchQuery.toLowerCase();
                orders = orders.filter(o =>
                    (o.nomor_invoice||'').toLowerCase().includes(q) ||
                    (o.nama_jenis||'').toLowerCase().includes(q)
                );
            }
            if (this.sortBy === 'terbaru') orders.sort((a,b)=>new Date(b.created_at)-new Date(a.created_at));
            else orders.sort((a,b)=>new Date(a.created_at)-new Date(b.created_at));
            return orders;
        }
    }" class="font-sans text-slate-800">

        {{-- HEADER --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-black text-slate-900 tracking-tight mb-0.5">Riwayat Pesanan</h1>
                <p class="text-[11px] text-slate-500 font-medium">Pantau status, volume, dan nota semua pesanan benur Anda.</p>
            </div>
            <a href="{{ route('customer.katalog') }}"
               class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-2xl font-black text-xs transition-colors shadow-sm shadow-blue-500/20 shrink-0">
                <i class="fa-solid fa-plus text-[10px]"></i> Pesan Lagi
            </a>
        </div>

        @if(session('success'))
            <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-[11px] font-black flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-circle-check text-emerald-500"></i> {{ session('success') }}
            </div>
        @endif

        {{-- TAB FILTER --}}
        @php
            $tabs = [
                ['id'=>'semua',              'label'=>'Semua',                 'count'=>$countSemua,   'active'=>'bg-blue-600',    'badge'=>'bg-blue-700',    'icon'=>'fa-list'],
                ['id'=>'pending',            'label'=>'Belum Bayar DP',        'count'=>$countPending, 'active'=>'bg-amber-500',   'badge'=>'bg-amber-500',   'icon'=>'fa-clock-rotate-left'],
                ['id'=>'proses',             'label'=>'Diproses / Muat',       'count'=>$countProses,  'active'=>'bg-blue-600',    'badge'=>'bg-blue-500',    'icon'=>'fa-truck-ramp-box'],
                ['id'=>'menunggu_pelunasan', 'label'=>'Menunggu Pelunasan',    'count'=>$countTagihan, 'active'=>'bg-rose-500',    'badge'=>'bg-rose-500',    'icon'=>'fa-file-invoice-dollar'],
                ['id'=>'selesai',            'label'=>'Selesai',               'count'=>$countSelesai, 'active'=>'bg-emerald-600', 'badge'=>'bg-emerald-500', 'icon'=>'fa-circle-check'],
                ['id'=>'batal',              'label'=>'Ditolak / Dibatalkan',  'count'=>$countBatal,   'active'=>'bg-slate-600',   'badge'=>'bg-slate-500',   'icon'=>'fa-ban'],
            ];
        @endphp
        <div class="flex gap-2 mb-5 overflow-x-auto pt-3 pb-2 hide-scrollbar">
            @foreach($tabs as $tab)
                <button @click="activeTab='{{ $tab['id'] }}'"
                    :class="activeTab==='{{ $tab['id'] }}' ? '{{ $tab['active'] }} text-white shadow-md' : 'bg-white text-slate-600 border border-slate-200'"
                    class="relative flex items-center gap-2 px-4 py-2.5 rounded-2xl font-bold text-xs transition-all whitespace-nowrap shrink-0 shadow-sm">
                    <i class="fa-solid {{ $tab['icon'] }} text-xs"></i>
                    {{ $tab['label'] }}
                    <span class="absolute -top-2.5 -right-1.5 min-w-[20px] h-5 px-1 text-[9px] font-black rounded-full flex items-center justify-center border-2 border-[#F1F5F9] shadow-sm
                        {{ $tab['count'] > 0 ? $tab['badge'].' text-white' : 'bg-slate-200 text-slate-500' }}">{{ $tab['count'] }}</span>
                </button>
            @endforeach
        </div>

        {{-- SEARCH + SORT --}}
        <div class="flex gap-2.5 mb-5">
            <div class="relative flex-1">
                <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                    <i class="fa-solid fa-magnifying-glass text-sm"></i>
                </span>
                <input type="text" x-model="searchQuery" placeholder="Cari nomor invoice atau jenis benur..."
                    class="w-full bg-white border border-slate-200 text-slate-800 rounded-2xl pl-11 pr-4 py-2.5 text-xs font-medium focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all placeholder:text-slate-400 shadow-sm">
            </div>
            <select x-model="sortBy"
                class="bg-white border border-slate-200 rounded-2xl px-4 py-2.5 text-xs font-bold text-slate-700 focus:outline-none focus:border-blue-400 shadow-sm shrink-0">
                <option value="terbaru">Terbaru</option>
                <option value="terlama">Terlama</option>
            </select>
        </div>

        {{-- INJECT DATA --}}
        <script>
        window._allOrders = {
            @foreach($grouped as $pesananId => $orderGroup)
            @php $fi = $orderGroup->first(); @endphp
            "{{ $pesananId }}": {
                id: {{ $pesananId }},
                status: "{{ $fi->status }}",
                nomor_invoice: "{{ addslashes($fi->nomor_invoice ?? '') }}",
                nama_kolam: "{{ addslashes($fi->nama_kolam ?? '') }}",
                nama_jenis: "{{ addslashes($fi->nama_jenis ?? '') }}",
                created_at: "{{ $fi->created_at }}"
            },
            @endforeach
        };
        </script>

        {{-- EMPTY --}}
        <div x-show="filteredOrders.length === 0"
             class="text-center py-16 flex flex-col items-center justify-center bg-white rounded-3xl border border-slate-100 shadow-sm">
            <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-3 text-slate-300 border border-slate-100">
                <i class="fa-solid fa-clipboard-list text-3xl"></i>
            </div>
            <p class="font-black text-slate-600">Tidak ada pesanan ditemukan</p>
            <p class="text-xs text-slate-500 mt-1">Coba ubah filter atau kata kunci pencarian</p>
        </div>

        {{-- LIST PESANAN --}}
        <div class="space-y-4">
            @forelse($grouped as $pesananId => $orderGroup)
                @php
                    $fi            = $orderGroup->first();
                    $status        = $fi->status;
                    $isRiil        = in_array($status, ['menunggu_pelunasan','selesai','menunggu_kalkulasi']);

                    // ✅ FIX DOC: ambil waktu_tabur dari siklus_kolam, hitung integer diffInDays
                    $siklusDoc = \Illuminate\Support\Facades\DB::table('siklus_kolam')
                        ->where('id', $fi->siklus_id ?? 0)
                        ->value('waktu_tabur');
                    $docHari = $siklusDoc
                        ? (int)\Carbon\Carbon::parse($siklusDoc)->startOfDay()->diffInDays(now()->startOfDay())
                        : '-';

                    $foto = \Illuminate\Support\Facades\DB::table('riwayat_sampling')
                        ->where('siklus_id', $fi->siklus_id ?? 0)
                        ->whereNotNull('path_foto')
                        ->orderBy('tanggal_sampling', 'desc')
                        ->value('path_foto');

                    $sakDipesan    = $fi->jumlah_sak_dipesan ?? 0;
                    $kantongEceran = $fi->kantong_eceran_dipesan ?? 0;
                    $kantongDraf   = $fi->total_kantong_hitung ?? ($sakDipesan * 45 + $kantongEceran);
                    $konversi      = $fi->konversi_per_kantong ?? 1700;
                    $ekorDraf      = $kantongDraf * $konversi;

                    $kantongRiil   = $fi->total_kantong_riil_muat ?? 0;
                    $sakRiil       = $kantongRiil > 0 ? floor($kantongRiil / 45) : 0;
                    $ecerRiil      = $kantongRiil > 0 ? ($kantongRiil % 45) : 0;
                    $ekorRiil      = $kantongRiil * $konversi;

                    $harga         = $fi->harga_per_ekor_kontrak ?? 0;
                    $dpDibayar     = $fi->nominal_dp_dibayar ?? 0;
                    $diskon        = $fi->diskon_pembulatan ?? 0;
                    $totalTagihan  = $fi->total_pembayaran_final ?? 0;
                    $estimasi      = $ekorDraf * $harga;
                    $kewajibanDp   = $estimasi * 0.2;
                    $subtotalRiil  = $ekorRiil * $harga;
                    $sisaBayar     = $totalTagihan > 0 ? $totalTagihan - $dpDibayar : 0;

                    // ✅ REVISI: label status disesuaikan dengan alur Midtrans
                    $badgeMap = [
                        'pending'            => ['amber',   'fa-clock-rotate-left',    'Menunggu Pembayaran DP',  false],
                        'proses'             => ['blue',    'fa-truck-ramp-box',        'DP Dibayar / Siap Muat',  false],
                        'menunggu_kalkulasi' => ['purple',  'fa-calculator',            'Sedang Dihitung Admin',   false],
                        'menunggu_pelunasan' => ['rose',    'fa-file-invoice-dollar',   'Menunggu Pelunasan',      true],
                        'selesai'            => ['emerald', 'fa-check-double',          'Selesai',                 false],
                        'batal'              => ['slate',   'fa-ban',                   'Ditolak / Dibatalkan',    false],
                    ];
                    [$bc, $bico, $blabel, $bpulse] = $badgeMap[$status] ?? $badgeMap['batal'];

                    $stepIndex = match($status) {
                        'pending'            => 1,
                        'proses'             => 2,
                        'menunggu_kalkulasi' => 3,
                        'menunggu_pelunasan' => 4,
                        'selesai'            => 5,
                        default              => 0,
                    };
                @endphp

                <div x-data="{ expanded: false }"
                     x-show="filteredOrders.some(o => o.id == {{ $pesananId }})"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     style="display:none"
                     class="bg-white rounded-[20px] border border-slate-100 shadow-[0_2px_15px_rgba(0,0,0,0.03)] overflow-hidden hover:shadow-lg hover:-translate-y-0.5 transition-all">

                    {{-- Header kartu: invoice + status badge --}}
                    <div class="px-4 pt-4 pb-3 border-b border-slate-100 flex items-center justify-between gap-3 bg-white">
                        <div class="min-w-0">
                            <p class="text-xs font-black text-blue-600 truncate">{{ $fi->nomor_invoice ?? '(Menunggu Invoice)' }}</p>
                            <p class="text-[9px] text-slate-400 font-medium mt-0.5">{{ \Carbon\Carbon::parse($fi->created_at)->translatedFormat('d M Y, H:i') }}</p>
                        </div>

                        {{-- Status badge --}}
                        <span class="inline-flex items-center gap-1.5 text-[9px] font-black px-2.5 py-1.5 rounded-xl border shrink-0 shadow-sm
                            {{ $status !== 'batal'
                                ? 'bg-'.$bc.'-50 text-'.$bc.'-700 border-'.$bc.'-200 '.($bpulse ? 'animate-pulse' : '')
                                : 'bg-slate-50 text-slate-500 border-slate-200' }}">
                            <i class="fa-solid {{ $bico }} text-[8px]"></i>
                            {{ $blabel }}
                        </span>
                    </div>

                    {{-- ✅ Mini stepper — step 1 label diganti "Bayar DP" --}}
                    @if($status !== 'batal' && $stepIndex > 0)
                    <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-100">
                        <div class="flex items-center gap-1">
                            @php
                                $miniSteps = [
                                    ['Bayar DP',  1],  // ✅ sebelumnya "Verif. DP"
                                    ['Siap Muat', 2],
                                    ['Dihitung',  3],
                                    ['Pelunasan', 4],
                                    ['Selesai',   5],
                                ];
                            @endphp
                            @foreach($miniSteps as [$slabel, $sindex])
                                <div class="flex-1 flex flex-col items-center gap-0.5">
                                    <div class="w-4 h-4 rounded-full flex items-center justify-center text-[7px] border
                                        @if($sindex < $stepIndex)  bg-blue-600 border-blue-600 text-white
                                        @elseif($sindex === $stepIndex) bg-white border-blue-600 text-blue-600 ring-2 ring-blue-100
                                        @else bg-white border-slate-200 text-slate-300
                                        @endif">
                                        @if($sindex < $stepIndex)
                                            <i class="fa-solid fa-check"></i>
                                        @else
                                            {{ $sindex }}
                                        @endif
                                    </div>
                                    <p class="text-[7px] font-bold leading-none
                                        @if($sindex < $stepIndex) text-blue-500
                                        @elseif($sindex === $stepIndex) text-slate-800
                                        @else text-slate-300
                                        @endif">{{ $slabel }}</p>
                                </div>
                                @if(!$loop->last)
                                    <div class="h-px flex-1 mb-3 {{ $sindex < $stepIndex ? 'bg-blue-400' : 'bg-slate-200' }}"></div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Layout: Foto & Ringkasan --}}
                    <div class="p-4 flex flex-col sm:flex-row gap-4 bg-white">

                        {{-- FOTO --}}
                        <div class="w-full sm:w-[130px] h-[160px] sm:h-[120px] bg-slate-100 rounded-xl shrink-0 relative overflow-hidden border border-slate-200 shadow-sm">
                            @if($foto)
                                <img src="{{ asset('storage/'.$foto) }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full bg-gradient-to-br from-slate-100 to-blue-50 flex items-center justify-center">
                                    <i class="fa-solid fa-shrimp text-4xl text-blue-200"></i>
                                </div>
                            @endif
                            {{-- ✅ FIX DOC badge: pakai $docHari yang sudah dihitung integer --}}
                            <span class="absolute bottom-2 left-2 bg-blue-600/80 text-white text-[8px] font-black px-2 py-1 rounded-md backdrop-blur-sm shadow-sm">
                                DOC {{ $docHari }}
                            </span>
                        </div>

                        {{-- INFO RINGKAS --}}
                        <div class="flex-1 flex flex-col min-w-0">
                            <p class="text-sm font-black text-slate-900 truncate mb-3">Benur {{ $fi->nama_jenis ?? 'Vaname' }}</p>

                            {{-- Highlight Volume & Total --}}
                            <div class="flex flex-wrap items-center gap-4 mb-4">
                                @if($isRiil && $kantongRiil > 0)
                                    <div>
                                        <p class="text-[9px] text-slate-500 font-bold mb-0.5">Vol. Muat Riil</p>
                                        <p class="text-[13px] font-black text-emerald-600">{{ number_format($ekorRiil,0,',','.') }} <span class="text-[9px] text-emerald-500 font-medium">Ekor</span></p>
                                    </div>
                                @else
                                    <div>
                                        <p class="text-[9px] text-slate-500 font-bold mb-0.5">Vol. Draf Order</p>
                                        <p class="text-[13px] font-black text-blue-600">{{ number_format($ekorDraf,0,',','.') }} <span class="text-[9px] text-blue-400 font-medium">Ekor</span></p>
                                    </div>
                                @endif

                                <div class="w-px h-6 bg-slate-200 hidden sm:block"></div>

                                <div>
                                    <p class="text-[9px] text-slate-500 font-bold mb-0.5">{{ $isRiil ? 'Total Tagihan' : 'Est. Total Harga' }}</p>
                                    <p class="text-[13px] font-black text-slate-800">Rp{{ number_format($totalTagihan > 0 ? $totalTagihan : $estimasi,0,',','.') }}</p>
                                </div>
                            </div>

                            {{-- Aksi --}}
                            <div class="flex flex-wrap items-center gap-2 mt-auto">
                                @if($status == 'menunggu_pelunasan')
                                    <a href="{{ route('customer.pesanan.detail',$pesananId) }}"
                                       class="flex items-center justify-center gap-1.5 text-[10px] font-black bg-rose-500 text-white px-4 py-2.5 rounded-xl hover:bg-rose-600 transition-colors shadow-sm shadow-rose-500/20 animate-pulse">
                                        <i class="fa-solid fa-money-bill-wave text-[9px]"></i> Bayar Sekarang
                                    </a>
                                @elseif($status == 'pending')
                                    {{-- ✅ Tombol bayar DP langsung dari list --}}
                                    <a href="{{ route('customer.pesanan.detail',$pesananId) }}"
                                       class="flex items-center justify-center gap-1.5 text-[10px] font-black bg-amber-500 text-white px-4 py-2.5 rounded-xl hover:bg-amber-600 transition-colors shadow-sm shadow-amber-500/20">
                                        <i class="fa-solid fa-bolt text-[9px]"></i> Bayar DP
                                    </a>
                                @else
                                    <a href="{{ route('customer.pesanan.detail',$pesananId) }}"
                                       class="flex items-center gap-1.5 text-[10px] font-black bg-blue-50 text-blue-600 px-4 py-2.5 rounded-xl hover:bg-blue-100 transition-colors">
                                        <i class="fa-solid fa-eye text-[9px]"></i> Lihat Detail
                                    </a>
                                @endif

                                <button @click="expanded = !expanded" type="button"
                                    class="ml-auto flex items-center gap-1.5 text-[10px] font-bold text-slate-500 bg-white border border-slate-200 px-3 py-2.5 rounded-xl hover:bg-slate-50 transition-colors shadow-sm">
                                    <span x-text="expanded ? 'Tutup Rincian' : 'Lihat Rincian'"></span>
                                    <i class="fa-solid fa-chevron-down transition-transform duration-300" :class="expanded ? 'rotate-180' : ''"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- DROPDOWN RINCIAN --}}
                    <div x-show="expanded"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-2"
                         class="border-t border-slate-100 bg-slate-50/50 p-4" style="display: none;">

                        <div class="flex flex-col sm:flex-row gap-5">

                            {{-- Kolom Kiri: Detail Volume --}}
                            <div class="flex-1 space-y-3 min-w-0">
                                <div class="bg-white rounded-xl p-3 border border-slate-100 shadow-sm">
                                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Draf Pesanan Awal</p>
                                    <div class="flex flex-wrap items-center gap-1 mb-1">
                                        @if($sakDipesan > 0)
                                            <span class="bg-slate-50 text-slate-700 font-black text-[9px] px-1.5 py-0.5 rounded-md border border-slate-200">{{ $sakDipesan }} Sak</span>
                                            <span class="text-slate-400 text-[8px]">×45</span>
                                            @if($kantongEceran > 0)<span class="text-slate-300">+</span>@else<span class="text-slate-300">=</span>@endif
                                        @endif
                                        @if($kantongEceran > 0)
                                            <span class="bg-slate-50 text-slate-700 font-black text-[9px] px-1.5 py-0.5 rounded-md border border-slate-200">{{ $kantongEceran }} Ktg</span>
                                            <span class="text-slate-300">=</span>
                                        @endif
                                        <span class="bg-blue-50 text-blue-700 font-black text-[9px] px-1.5 py-0.5 rounded-md border border-blue-200">{{ number_format($kantongDraf,0,',','.') }} Ktg</span>
                                    </div>
                                    <p class="text-[9px] text-slate-400">= <span class="font-black text-blue-700">{{ number_format($ekorDraf,0,',','.') }} Ekor</span></p>
                                </div>

                                @if($isRiil && $kantongRiil > 0)
                                <div class="bg-emerald-50/50 rounded-xl p-3 border border-emerald-100 shadow-sm">
                                    <p class="text-[9px] font-black text-emerald-600 uppercase tracking-widest mb-1.5">Fisik Riil Muat</p>
                                    <div class="flex flex-wrap items-center gap-1 mb-1">
                                        @if($sakRiil > 0)
                                            <span class="bg-white text-slate-700 font-black text-[9px] px-1.5 py-0.5 rounded-md border border-slate-200">{{ $sakRiil }} Sak</span>
                                            <span class="text-slate-400 text-[8px]">×45</span>
                                            @if($ecerRiil > 0)<span class="text-slate-300">+</span>@else<span class="text-slate-300">=</span>@endif
                                        @endif
                                        @if($ecerRiil > 0)
                                            <span class="bg-white text-slate-700 font-black text-[9px] px-1.5 py-0.5 rounded-md border border-slate-200">{{ $ecerRiil }} Ktg</span>
                                            <span class="text-slate-300">=</span>
                                        @endif
                                        <span class="bg-emerald-100 text-emerald-700 font-black text-[9px] px-1.5 py-0.5 rounded-md border border-emerald-200">{{ number_format($kantongRiil,0,',','.') }} Ktg</span>
                                    </div>
                                    <p class="text-[9px] text-slate-400">= <span class="font-black text-emerald-700">{{ number_format($ekorRiil,0,',','.') }} Ekor</span></p>
                                </div>
                                @elseif($isRiil)
                                <p class="text-[9px] text-slate-400 italic px-2">Menunggu data muat riil...</p>
                                @endif
                            </div>

                            {{-- Kolom Kanan: Rincian Keuangan & Nota --}}
                            <div class="flex-1 min-w-0 flex flex-col justify-between">
                                <div>
                                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2.5">Rincian Pembayaran</p>
                                    <div class="space-y-1.5 text-[10px] bg-white p-3 rounded-xl border border-slate-100 shadow-sm">
                                        @if($isRiil && $subtotalRiil > 0)
                                            <div class="flex justify-between gap-2">
                                                <span class="text-slate-500">Vol. Riil</span>
                                                <span class="font-black text-slate-700 text-right">{{ number_format($ekorRiil,0,',','.') }} Ekor</span>
                                            </div>
                                            <div class="flex justify-between gap-2">
                                                <span class="text-slate-500">Harga/Ekor</span>
                                                <span class="font-black text-slate-700">Rp{{ number_format($harga,0,',','.') }}</span>
                                            </div>
                                            <div class="flex justify-between gap-2">
                                                <span class="text-slate-500">Subtotal</span>
                                                <span class="font-black text-slate-700">Rp{{ number_format($subtotalRiil,0,',','.') }}</span>
                                            </div>
                                            @if($diskon > 0)
                                            <div class="flex justify-between gap-2">
                                                <span class="text-rose-500">Diskon</span>
                                                <span class="font-black text-rose-500">-Rp{{ number_format($diskon,0,',','.') }}</span>
                                            </div>
                                            @endif
                                            @if($dpDibayar > 0)
                                            <div class="flex justify-between gap-2 border-t border-slate-100 pt-1.5">
                                                <span class="text-emerald-600 font-bold">DP Terbayar</span>
                                                <span class="font-black text-emerald-600">Rp{{ number_format($dpDibayar,0,',','.') }}</span>
                                            </div>
                                            @endif
                                            @if($sisaBayar > 0 && $status == 'menunggu_pelunasan')
                                            <div class="flex justify-between gap-2 bg-rose-50 rounded-lg px-2 py-1.5 border border-rose-100 mt-1">
                                                <span class="font-black text-rose-600">Sisa Bayar</span>
                                                <span class="font-black text-rose-600">Rp{{ number_format($sisaBayar,0,',','.') }}</span>
                                            </div>
                                            @endif
                                        @else
                                            <div class="flex justify-between gap-2">
                                                <span class="text-slate-500">Est. Ekor</span>
                                                <span class="font-black text-slate-700">{{ number_format($ekorDraf,0,',','.') }}</span>
                                            </div>
                                            <div class="flex justify-between gap-2">
                                                <span class="text-slate-500">Harga/Ekor</span>
                                                <span class="font-black text-slate-700">Rp{{ number_format($harga,0,',','.') }}</span>
                                            </div>
                                            <div class="flex justify-between gap-2 border-t border-slate-100 pt-1.5">
                                                <span class="font-bold text-slate-600">~Est. Total</span>
                                                <span class="font-black text-slate-700">Rp{{ number_format($estimasi,0,',','.') }}</span>
                                            </div>
                                            <div class="flex justify-between gap-2">
                                                <span class="text-amber-600 font-bold">DP 20%</span>
                                                <span class="font-black text-amber-600">Rp{{ number_format($kewajibanDp,0,',','.') }}</span>
                                            </div>
                                            @if($dpDibayar > 0)
                                            <div class="flex justify-between gap-2 border-t border-slate-100 pt-1.5">
                                                <span class="text-emerald-600 font-bold">DP Terbayar</span>
                                                <span class="font-black text-emerald-600">Rp{{ number_format($dpDibayar,0,',','.') }}</span>
                                            </div>
                                            @endif
                                        @endif
                                    </div>
                                </div>

                                {{-- Aksi Nota --}}
                                <div class="flex items-center gap-2 mt-3">
                                    @if(in_array($status,['proses','menunggu_kalkulasi','menunggu_pelunasan','selesai']) && $dpDibayar > 0)
                                        <a href="{{ route('customer.pesanan.invoice',['id'=>$pesananId,'type'=>'dp']) }}" target="_blank"
                                           class="flex items-center justify-center gap-1.5 text-[9px] font-black bg-white text-slate-600 border border-slate-200 px-3 py-2 rounded-xl hover:bg-slate-50 transition-colors shadow-sm flex-1">
                                            <i class="fa-solid fa-download"></i> Unduh Nota DP
                                        </a>
                                    @endif
                                    @if($status == 'selesai')
                                        <a href="{{ route('customer.pesanan.invoice',['id'=>$pesananId,'type'=>'pelunasan']) }}" target="_blank"
                                           class="flex items-center justify-center gap-1.5 text-[9px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200 px-3 py-2 rounded-xl hover:bg-emerald-100 transition-colors shadow-sm flex-1">
                                            <i class="fa-solid fa-download"></i> Unduh Nota Lunas
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-16 flex flex-col items-center justify-center bg-white rounded-3xl border border-slate-100 shadow-sm">
                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-3 text-slate-300 border border-slate-100">
                        <i class="fa-solid fa-clipboard-list text-3xl"></i>
                    </div>
                    <p class="font-black text-slate-600">Belum Ada Pesanan</p>
                    <a href="{{ route('customer.katalog') }}" class="mt-4 inline-flex items-center gap-2 bg-blue-600 text-white px-5 py-2.5 rounded-2xl font-black text-xs hover:bg-blue-700 transition-colors shadow-sm">
                        <i class="fa-solid fa-store"></i> Ke Katalog
                    </a>
                </div>
            @endforelse
        </div>

    </div>

    <style>
    .hide-scrollbar::-webkit-scrollbar { display: none; }
    .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</x-customer-layout>