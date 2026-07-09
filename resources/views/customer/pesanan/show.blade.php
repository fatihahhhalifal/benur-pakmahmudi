<x-customer-layout>
<div x-data="{ isLightboxOpen: false, activeImage: '' }" class="font-sans text-slate-800 min-h-full bg-[#F8FAFC]">

    {{-- LIGHTBOX --}}
    <div x-show="isLightboxOpen" x-transition.opacity.duration.300ms
         class="fixed inset-0 z-[100] bg-black/95 flex items-center justify-center" x-cloak>
        <button @click="isLightboxOpen = false"
            class="absolute top-6 right-6 w-10 h-10 bg-white/10 hover:bg-white/20 rounded-full flex items-center justify-center text-white transition-colors z-[110]">
            <i class="fa-solid fa-xmark text-xl"></i>
        </button>
        <img :src="activeImage" class="max-w-full max-h-screen object-contain p-4" @click.away="isLightboxOpen = false">
    </div>

    {{-- PAGE HEADER --}}
    <div class="px-6 md:px-8 pt-6 pb-4 bg-[#F8FAFC] border-b border-slate-200 sticky top-0 z-30 flex items-center gap-3">
        <a href="{{ route('customer.pesanan.index') }}"
           class="w-9 h-9 flex items-center justify-center rounded-xl text-slate-600 bg-white hover:bg-slate-50 transition-colors shadow-sm border border-slate-200 shrink-0">
            <i class="fa-solid fa-chevron-left text-sm"></i>
        </a>
        <div>
            <h1 class="text-lg font-black text-slate-900 tracking-tight">Detail Pesanan</h1>
            <p class="text-xs text-slate-400 mt-0.5">{{ $pesanan->nomor_invoice ?? 'Menunggu nomor invoice...' }}</p>
        </div>
    </div>

    <div class="p-6 md:p-8">

        {{-- ALERT --}}
        @if(session('success'))
            <div class="mb-5 p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs font-black flex items-center gap-2 animate-fade-in">
                <i class="fa-solid fa-circle-check text-emerald-500 text-base"></i> {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-5 p-3 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-xs font-black flex items-center gap-2 animate-fade-in">
                <i class="fa-solid fa-circle-xmark text-rose-500 text-base"></i> {{ session('error') }}
            </div>
        @endif

        {{-- STATUS TRACKER --}}
        @php
            $allSteps = [
                [
                    'key'   => 'pending',
                    'label' => 'Menunggu Pembayaran DP',
                    'icon'  => 'fa-clock-rotate-left',
                    'desc'  => 'Pesanan diterima. Selesaikan pembayaran DP agar pesanan segera diproses.',
                ],
                [
                    'key'   => 'proses',
                    'label' => 'DP Dibayar / Siap Muat',
                    'icon'  => 'fa-truck-ramp-box',
                    'desc'  => 'DP telah diterima. Benur sedang disiapkan dan dimuat ke kendaraan Anda.',
                ],
                [
                    'key'   => 'menunggu_kalkulasi',
                    'label' => 'Sedang Dihitung Admin',
                    'icon'  => 'fa-calculator',
                    'desc'  => 'Muat selesai. Admin sedang menghitung total tagihan akhir berdasarkan fisik riil.',
                ],
                [
                    'key'   => 'menunggu_pelunasan',
                    'label' => 'Menunggu Pelunasan',
                    'icon'  => 'fa-file-invoice-dollar',
                    'desc'  => 'Tagihan akhir sudah diterbitkan. Silakan lunasi sisa pembayaran agar surat jalan diterbitkan.',
                ],
                [
                    'key'   => 'selesai',
                    'label' => 'Selesai',
                    'icon'  => 'fa-check-double',
                    'desc'  => 'Transaksi selesai. Surat jalan dan nota lunas telah diterbitkan. Terima kasih!',
                ],
            ];

            $isBatal    = $pesanan->status === 'batal';
            $statusOrder = ['pending','proses','menunggu_kalkulasi','menunggu_pelunasan','selesai'];
            $currentIdx  = array_search($pesanan->status, $statusOrder);
            if ($currentIdx === false) $currentIdx = -1;

            $sudahUploadPelunasan = !empty($pesanan->bukti_transfer_pelunasan);
        @endphp

        @if($isBatal)
            <div class="bg-white rounded-2xl border border-rose-200 shadow-sm flex items-center gap-4 relative overflow-hidden mb-6">
                <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-rose-500 rounded-l-2xl"></div>
                <div class="pl-6 pr-4 py-4 flex items-center gap-4 w-full">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 bg-rose-50 text-rose-500">
                        <i class="fa-solid fa-ban text-xl"></i>
                    </div>
                    <div>
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-0.5">Status Pesanan</p>
                        <h2 class="text-sm font-black text-rose-600 mb-0.5">Ditolak / Dibatalkan</h2>
                        <p class="text-[10px] text-slate-500 font-medium leading-relaxed">
                            {{ $pesanan->keterangan_batal ?? 'Pesanan ini telah dibatalkan.' }}
                        </p>
                    </div>
                </div>
            </div>
        @else
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 mb-6">
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-4">Status Pesanan</p>

                <div class="relative">
                    <div class="absolute top-5 left-5 right-5 h-0.5 bg-slate-100 z-0 hidden sm:block"></div>
                    <div class="absolute top-5 left-5 h-0.5 bg-blue-500 z-0 hidden sm:block transition-all duration-500"
                         style="width: calc({{ $currentIdx > 0 ? ($currentIdx / (count($allSteps)-1)) * 100 : 0 }}% * (100% - 2.5rem) / 100%)">
                    </div>

                    <div class="flex flex-col sm:flex-row gap-4 sm:gap-0 sm:justify-between relative z-10">
                        @foreach($allSteps as $i => $step)
                            @php
                                $isDone    = $i < $currentIdx;
                                $isActive  = $i === $currentIdx;
                                $isPending = $i > $currentIdx;
                            @endphp
                            <div class="flex sm:flex-col items-center sm:items-center gap-3 sm:gap-2 flex-1">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 border-2 transition-all duration-300
                                    @if($isDone)   bg-blue-600 border-blue-600 text-white shadow-md shadow-blue-500/20
                                    @elseif($isActive) bg-white border-blue-600 text-blue-600 shadow-lg shadow-blue-500/25 ring-4 ring-blue-100
                                    @else          bg-white border-slate-200 text-slate-300
                                    @endif">
                                    @if($isDone)
                                        <i class="fa-solid fa-check text-xs"></i>
                                    @else
                                        <i class="fa-solid {{ $step['icon'] }} text-sm"></i>
                                    @endif
                                </div>
                                <div class="sm:text-center flex-1 sm:flex-none">
                                    <p class="text-[10px] font-black leading-tight
                                        @if($isDone)   text-blue-600
                                        @elseif($isActive) text-slate-900
                                        @else          text-slate-400
                                        @endif">
                                        {{ $step['label'] }}
                                    </p>
                                    @if($isActive)
                                        <p class="text-[9px] text-slate-500 font-medium mt-0.5 leading-snug hidden sm:block max-w-[120px]">
                                            {{ $step['desc'] }}
                                        </p>
                                    @endif
                                </div>
                                @if(!$loop->last)
                                    <div class="w-0.5 h-4 sm:hidden
                                        {{ $isDone ? 'bg-blue-500' : 'bg-slate-200' }} ml-5 shrink-0"></div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                @if(isset($allSteps[$currentIdx]))
                <div class="mt-4 pt-4 border-t border-slate-100 sm:hidden">
                    <p class="text-[10px] text-slate-500 font-medium leading-relaxed">
                        <i class="fa-solid fa-circle-info text-blue-400 mr-1"></i>
                        {{ $allSteps[$currentIdx]['desc'] }}
                    </p>
                </div>
                @endif

                @php
                    $activeBannerColor = match($pesanan->status) {
                        'pending'            => ['bg' => 'bg-amber-50',  'border' => 'border-amber-200',  'text' => 'text-amber-700',  'icon_bg' => 'bg-amber-100',  'icon_text' => 'text-amber-500'],
                        'proses'             => ['bg' => 'bg-blue-50',   'border' => 'border-blue-200',   'text' => 'text-blue-700',   'icon_bg' => 'bg-blue-100',   'icon_text' => 'text-blue-500'],
                        'menunggu_kalkulasi' => ['bg' => 'bg-purple-50', 'border' => 'border-purple-200', 'text' => 'text-purple-700', 'icon_bg' => 'bg-purple-100', 'icon_text' => 'text-purple-500'],
                        'menunggu_pelunasan' => ['bg' => 'bg-rose-50',   'border' => 'border-rose-200',   'text' => 'text-rose-700',   'icon_bg' => 'bg-rose-100',   'icon_text' => 'text-rose-500'],
                        'selesai'            => ['bg' => 'bg-emerald-50','border' => 'border-emerald-200','text' => 'text-emerald-700','icon_bg' => 'bg-emerald-100','icon_text' => 'text-emerald-500'],
                        default              => ['bg' => 'bg-slate-50',  'border' => 'border-slate-200',  'text' => 'text-slate-700',  'icon_bg' => 'bg-slate-100',  'icon_text' => 'text-slate-400'],
                    };
                    $activeStep = $allSteps[$currentIdx] ?? null;
                @endphp

                @if($activeStep)
                <div class="mt-4 {{ $activeBannerColor['bg'] }} {{ $activeBannerColor['border'] }} border rounded-xl p-3 flex items-center gap-3">
                    <div class="w-8 h-8 {{ $activeBannerColor['icon_bg'] }} {{ $activeBannerColor['icon_text'] }} rounded-lg flex items-center justify-center shrink-0">
                        <i class="fa-solid {{ $activeStep['icon'] }} text-sm"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-black {{ $activeBannerColor['text'] }}">
                            @if($pesanan->status === 'menunggu_pelunasan' && $sudahUploadPelunasan)
                                Pelunasan Terkirim — Menunggu Verifikasi Otomatis
                            @else
                                {{ $activeStep['label'] }}
                            @endif
                        </p>
                        <p class="text-[9px] text-slate-500 font-medium mt-0.5 leading-snug">
                            @if($pesanan->status === 'menunggu_pelunasan' && $sudahUploadPelunasan)
                                Pembayaran pelunasan Anda sedang diverifikasi otomatis oleh sistem Midtrans. Surat jalan akan diterbitkan begitu status terverifikasi.
                            @else
                                {{ $activeStep['desc'] }}
                            @endif
                        </p>
                    </div>
                </div>
                @endif
            </div>
        @endif

        {{-- MAIN GRID --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">

            {{-- KOLOM KIRI (2/3) --}}
            <div class="lg:col-span-2 space-y-5">

                {{-- ITEM PESANAN --}}
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                    <div class="flex justify-between items-center mb-4 pb-3 border-b border-slate-100">
                        <h3 class="text-xs font-black text-slate-500 uppercase tracking-wider">
                            <i class="fa-solid fa-box-open text-blue-500 mr-1.5"></i> Item Pesanan
                        </h3>
                        <span class="text-[10px] font-black text-slate-600 bg-slate-50 border border-slate-200 px-2.5 py-1 rounded-lg">
                            {{ $pesanan->nomor_invoice ?? '—' }}
                        </span>
                    </div>

                    <div class="space-y-5">
                        @foreach($items as $item)
                            @php
                                $siklusInfo = \Illuminate\Support\Facades\DB::table('siklus_kolam')
                                    ->leftJoin('grade_benur','siklus_kolam.grade_id','=','grade_benur.id')
                                    ->where('siklus_kolam.id', $item->siklus_id)
                                    ->select('siklus_kolam.waktu_tabur','grade_benur.nama_grade')
                                    ->first();
                                $doc = $siklusInfo
                                    ? (int)\Carbon\Carbon::parse($siklusInfo->waktu_tabur)->startOfDay()->diffInDays(now()->startOfDay())
                                    : '-';
                                $foto = \Illuminate\Support\Facades\DB::table('riwayat_sampling')
                                    ->where('siklus_id', $item->siklus_id)
                                    ->whereNotNull('path_foto')
                                    ->orderBy('tanggal_sampling','desc')
                                    ->value('path_foto');
                                $isKalkulasiAkhir = in_array($pesanan->status, ['menunggu_kalkulasi','menunggu_pelunasan','selesai']);
                                $hargaSatuan = $isKalkulasiAkhir
                                    ? ($item->harga_per_ekor_aktual ?? $item->harga_per_ekor_kontrak)
                                    : ($pesanan->is_harga_dikunci ? $item->harga_per_ekor_kontrak : ($item->harga_live ?? $item->harga_per_ekor_kontrak ?? 0));
                                $volume = $isKalkulasiAkhir
                                    ? $item->total_kantong_riil_muat * $item->konversi_per_kantong
                                    : $item->total_kantong_hitung * $item->konversi_per_kantong;
                                $subtotalBaris = $volume * $hargaSatuan;
                                $ekorDraf = $item->total_kantong_hitung * $item->konversi_per_kantong;
                                $ekorRiil = ($item->total_kantong_riil_muat ?? 0) * $item->konversi_per_kantong;
                                $selisihEkor = $ekorRiil - $ekorDraf;
                                $adaPerubahan = $isKalkulasiAkhir && ($item->total_kantong_riil_muat ?? 0) > 0 && $ekorRiil != $ekorDraf;
                            @endphp

                            <div class="flex gap-4">
                                <div @click="activeImage='{{ $foto ? asset('storage/'.$foto) : '' }}'; if(activeImage) isLightboxOpen=true"
                                     class="w-20 h-20 bg-slate-100 rounded-xl relative overflow-hidden shrink-0 border border-slate-200 cursor-zoom-in">
                                    @if($foto)
                                        <img src="{{ asset('storage/'.$foto) }}" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-blue-200 bg-gradient-to-br from-slate-50 to-slate-100">
                                            <i class="fa-solid fa-shrimp text-3xl"></i>
                                        </div>
                                    @endif
                                    <span class="absolute top-1 left-1 bg-blue-600/90 text-white text-[8px] font-black px-1.5 py-0.5 rounded">DOC {{ $doc }}</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-sm font-black text-slate-900 truncate">{{ $item->nama_kolam ?? 'Kolam Standar' }}</h3>
                                    <p class="text-[10px] text-slate-500 font-medium mt-0.5 truncate">Benur {{ $item->nama_jenis ?? 'Vannamei' }}</p>
                                    <div class="flex flex-wrap gap-1.5 mt-2">
                                        <span class="text-[9px] font-black text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-100 flex items-center gap-1">
                                            <i class="fa-solid fa-star text-amber-400 text-[8px]"></i> Grade {{ $siklusInfo->nama_grade ?? 'A' }}
                                        </span>
                                        <span class="text-[9px] font-black text-slate-600 bg-slate-50 px-1.5 py-0.5 rounded border border-slate-200">
                                            PL {{ $item->label_ukuran ?? '-' }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-slate-50 rounded-xl p-4 border border-slate-100 space-y-3">
                                <div>
                                    <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-2">
                                        {{ $isKalkulasiAkhir ? 'Volume Draf (Booking Awal)' : 'Estimasi Volume Pesanan' }}
                                    </p>
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        @if(($item->jumlah_sak_dipesan ?? 0) > 0)
                                            <span class="text-[10px] font-black bg-white text-slate-700 border border-slate-200 px-2 py-1 rounded-lg">{{ $item->jumlah_sak_dipesan }} Sak</span>
                                            @if(($item->kantong_eceran_dipesan ?? 0) > 0)
                                                <span class="text-slate-400 font-black text-xs">+</span>
                                            @else
                                                <span class="text-slate-400 text-xs">=</span>
                                            @endif
                                        @endif
                                        @if(($item->kantong_eceran_dipesan ?? 0) > 0)
                                            <span class="text-[10px] font-black bg-white text-slate-700 border border-slate-200 px-2 py-1 rounded-lg">{{ $item->kantong_eceran_dipesan }} Kantong</span>
                                            <span class="text-slate-400 text-xs">=</span>
                                        @endif
                                        <span class="text-[10px] font-black bg-blue-50 text-blue-700 border border-blue-200 px-2 py-1 rounded-lg">{{ number_format($item->total_kantong_hitung,0,',','.') }} Kantong</span>
                                        <span class="text-slate-400 text-xs">× {{ number_format($item->konversi_per_kantong,0,',','.') }}/ktg</span>
                                        <span class="text-slate-400 font-black text-xs">→</span>
                                        <span class="text-sm font-black text-blue-700">{{ number_format($ekorDraf,0,',','.') }} Ekor</span>
                                    </div>
                                </div>

                                @if($isKalkulasiAkhir && ($item->total_kantong_riil_muat ?? 0) > 0)
                                <div class="pt-3 border-t border-dashed border-slate-200">
                                    <p class="text-[8px] font-black text-emerald-600 uppercase tracking-widest mb-2">Fisik Riil (Aktual Muat)</p>
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200 px-2 py-1 rounded-lg">{{ number_format($item->total_kantong_riil_muat,0,',','.') }} Kantong</span>
                                        <span class="text-slate-400 text-xs">× {{ number_format($item->konversi_per_kantong,0,',','.') }}/ktg</span>
                                        <span class="text-slate-400 font-black text-xs">→</span>
                                        <span class="text-sm font-black text-emerald-700">{{ number_format($ekorRiil,0,',','.') }} Ekor</span>
                                    </div>
                                </div>
                                @endif

                                @if($adaPerubahan)
                                <div class="pt-3 border-t border-dashed border-slate-200">
                                    <div class="rounded-xl border p-3 space-y-2
                                        {{ $selisihEkor > 0 ? 'bg-blue-50 border-blue-200' : 'bg-amber-50 border-amber-200' }}">
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 rounded-full flex items-center justify-center shrink-0
                                                {{ $selisihEkor > 0 ? 'bg-blue-100 text-blue-600' : 'bg-amber-100 text-amber-600' }}">
                                                <i class="fa-solid {{ $selisihEkor > 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' }} text-[10px]"></i>
                                            </div>
                                            <p class="text-[10px] font-black {{ $selisihEkor > 0 ? 'text-blue-700' : 'text-amber-700' }}">
                                                Volume berubah saat penimbangan lapangan
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-2 text-[10px]">
                                            <div class="flex-1 bg-white rounded-lg p-2 border border-slate-200 text-center">
                                                <p class="text-slate-400 font-bold text-[8px] mb-0.5">Booking Awal</p>
                                                <p class="font-black text-slate-700">{{ number_format($ekorDraf,0,',','.') }}</p>
                                                <p class="text-slate-400 text-[8px]">ekor</p>
                                            </div>
                                            <div class="shrink-0 text-slate-400"><i class="fa-solid fa-arrow-right text-xs"></i></div>
                                            <div class="flex-1 bg-white rounded-lg p-2 border {{ $selisihEkor > 0 ? 'border-blue-200' : 'border-amber-200' }} text-center">
                                                <p class="{{ $selisihEkor > 0 ? 'text-blue-500' : 'text-amber-500' }} font-bold text-[8px] mb-0.5">Aktual Muat</p>
                                                <p class="font-black {{ $selisihEkor > 0 ? 'text-blue-700' : 'text-amber-700' }}">{{ number_format($ekorRiil,0,',','.') }}</p>
                                                <p class="{{ $selisihEkor > 0 ? 'text-blue-400' : 'text-amber-400' }} text-[8px]">ekor</p>
                                            </div>
                                            <div class="shrink-0 text-slate-400">=</div>
                                            <div class="flex-1 bg-white rounded-lg p-2 border {{ $selisihEkor > 0 ? 'border-blue-200' : 'border-amber-200' }} text-center">
                                                <p class="{{ $selisihEkor > 0 ? 'text-blue-500' : 'text-amber-500' }} font-bold text-[8px] mb-0.5">Selisih</p>
                                                <p class="font-black {{ $selisihEkor > 0 ? 'text-blue-700' : 'text-amber-700' }}">
                                                    {{ $selisihEkor > 0 ? '+' : '' }}{{ number_format($selisihEkor,0,',','.') }}
                                                </p>
                                                <p class="{{ $selisihEkor > 0 ? 'text-blue-400' : 'text-amber-400' }} text-[8px]">ekor</p>
                                            </div>
                                        </div>
                                        <div class="bg-white/70 rounded-lg p-2.5 border border-slate-200/70 space-y-1">
                                            <p class="text-[9px] font-black text-slate-600 flex items-center gap-1">
                                                <i class="fa-solid fa-circle-info text-slate-400 text-[8px]"></i>
                                                Mengapa volume bisa berbeda?
                                            </p>
                                            <ul class="text-[9px] text-slate-500 font-medium space-y-0.5 pl-3">
                                                <li>· Penimbangan fisik kantong dilakukan langsung di lapangan oleh operator</li>
                                                <li>· Jumlah ekor per kantong disesuaikan dengan hasil sampling aktual benur</li>
                                                <li>· Selisih ini wajar dan merupakan bagian dari proses standar pembenihan</li>
                                            </ul>
                                        </div>
                                        @php $dampakRupiah = abs($selisihEkor) * $hargaSatuan; @endphp
                                        <div class="flex items-center justify-between bg-white rounded-lg px-3 py-2 border {{ $selisihEkor > 0 ? 'border-blue-200' : 'border-amber-200' }}">
                                            <p class="text-[9px] font-black text-slate-600">Dampak ke tagihan:</p>
                                            <p class="text-[10px] font-black {{ $selisihEkor > 0 ? 'text-blue-600' : 'text-amber-600' }}">
                                                {{ $selisihEkor > 0 ? '+' : '-' }} Rp{{ number_format($dampakRupiah,0,',','.') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                <div class="pt-3 border-t border-dashed border-slate-200 flex justify-between items-center">
                                    <div>
                                        <p class="text-[9px] text-slate-400 font-medium mb-0.5">Harga Satuan</p>
                                        <p class="text-sm font-black text-blue-600">Rp{{ number_format($hargaSatuan,0,',','.') }} / Ekor</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-[9px] text-slate-400 font-medium mb-0.5">Subtotal</p>
                                        <p class="text-sm font-black text-slate-900">Rp{{ number_format($subtotalBaris,0,',','.') }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- RINGKASAN KEUANGAN --}}
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                    <h3 class="text-xs font-black text-slate-500 uppercase tracking-wider mb-4 pb-3 border-b border-slate-100">
                        <i class="fa-solid fa-receipt text-blue-500 mr-1.5"></i> Ringkasan Biaya
                    </h3>

                    @if(in_array($pesanan->status, ['menunggu_pelunasan','selesai']))
                        @php
                            $subtotalKotorRiil = 0;
                            foreach($items as $itm) {
                                $subtotalKotorRiil += $itm->total_kantong_riil_muat * $itm->konversi_per_kantong
                                    * ($itm->harga_per_ekor_aktual ?? $itm->harga_per_ekor_kontrak);
                            }
                            $diskonManual      = $items->sum('diskon_pembulatan_manual');
                            $totalTagihanAkhir = $pesanan->total_pembayaran_final;
                            $dpDibayar         = $pesanan->nominal_dp_dibayar;
                            $sisaPelunasan     = $totalTagihanAkhir - $dpDibayar;
                        @endphp
                        <div class="space-y-3 mb-4">
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-slate-500">Subtotal Kotor (Fisik Riil)</span>
                                <span class="text-xs font-black text-slate-900">Rp{{ number_format($subtotalKotorRiil,0,',','.') }}</span>
                            </div>
                            @if($diskonManual > 0)
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-slate-500">Diskon Pembulatan</span>
                                <span class="text-xs font-black text-rose-500">-Rp{{ number_format($diskonManual,0,',','.') }}</span>
                            </div>
                            @endif
                            <div class="flex justify-between items-center pt-3 border-t border-dashed border-slate-200">
                                <span class="text-sm font-black text-slate-900">Total Tagihan Akhir</span>
                                <span class="text-sm font-black text-slate-900">Rp{{ number_format($totalTagihanAkhir,0,',','.') }}</span>
                            </div>
                        </div>
                        <div class="bg-slate-50 rounded-xl p-4 border border-slate-100 space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-slate-500">Telah Dibayar (DP)</span>
                                <span class="text-xs font-black text-emerald-600">Rp{{ number_format($dpDibayar,0,',','.') }}</span>
                            </div>
                            <div class="flex justify-between items-center pt-3 border-t border-dashed border-slate-200">
                                <span class="text-sm font-black text-rose-600">Sisa Wajib Dilunasi</span>
                                <span class="text-base font-black text-rose-600">Rp{{ number_format($sisaPelunasan,0,',','.') }}</span>
                            </div>
                        </div>

                    @else
                        @php
                            $estimasiGrandTotal = 0;
                            foreach($items as $itm) {
                                $h = $pesanan->is_harga_dikunci
                                    ? $itm->harga_per_ekor_kontrak
                                    : ($itm->harga_live ?? $itm->harga_per_ekor_kontrak ?? 0);
                                $estimasiGrandTotal += $itm->total_kantong_hitung * $itm->konversi_per_kantong * $h;
                            }
                            $nilaiDP = $estimasiGrandTotal * 0.2;
                        @endphp
                        <div class="flex justify-between items-center mb-4">
                            <span class="text-xs font-black text-slate-700">Total Nilai Kontrak Estimasi</span>
                            <span class="text-sm font-black text-slate-900">Rp{{ number_format($estimasiGrandTotal,0,',','.') }}</span>
                        </div>
                        <div class="bg-slate-50 rounded-xl p-4 border border-slate-100 space-y-3">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-xs font-black text-blue-600">DP Uang Muka (20%)</p>
                                    <p class="text-[9px] {{ $pesanan->status=='pending' ? 'text-amber-500' : 'text-emerald-500' }} font-bold mt-0.5">
                                        {{ $pesanan->status=='pending' ? 'Menunggu pembayaran.' : 'Telah dibayar.' }}
                                    </p>
                                </div>
                                <span class="text-sm font-black text-blue-600">
                                    Rp{{ $pesanan->status=='pending' ? number_format($nilaiDP,0,',','.') : number_format($pesanan->nominal_dp_dibayar,0,',','.') }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center pt-3 border-t border-dashed border-slate-200">
                                <div>
                                    <p class="text-xs font-black text-slate-700">Estimasi Sisa Pelunasan</p>
                                    <p class="text-[9px] text-slate-400 font-medium mt-0.5">Menyesuaikan fisik riil muatan.</p>
                                </div>
                                <span class="text-sm font-black text-slate-700">
                                    Rp{{ $pesanan->status=='pending'
                                        ? number_format($estimasiGrandTotal - $nilaiDP,0,',','.')
                                        : number_format($estimasiGrandTotal - $pesanan->nominal_dp_dibayar,0,',','.') }}
                                </span>
                            </div>
                        </div>
                    @endif
                </div>

                        {{-- TOMBOL BAYAR DP VIA MIDTRANS --}}
                @if($pesanan->status == 'pending')
                @php
                    $estimasiDpMidtrans = 0;
                    foreach ($items as $itmDp) {
                        $hDp = $pesanan->is_harga_dikunci ? $itmDp->harga_per_ekor_kontrak : ($itmDp->harga_live ?? $itmDp->harga_per_ekor_kontrak ?? 0);
                        $estimasiDpMidtrans += $itmDp->total_kantong_hitung * $itmDp->konversi_per_kantong * $hDp;
                    }
                    $estimasiDpMidtrans *= 0.2;
                @endphp
                <div class="bg-white rounded-2xl border border-blue-200 shadow-sm p-5"
                     x-data="{
                         loading: false,
                         errorMsg: '',
                         bayarMidtrans() {
                             this.loading = true;
                             this.errorMsg = '';
                             fetch('{{ route('customer.pesanan.midtrans.token', $pesanan->id) }}', {
                                 method: 'POST',
                                 headers: {
                                     'Content-Type': 'application/json',
                                     'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                                 }
                             })
                             .then(r => r.json())
                             .then(data => {
                                 this.loading = false;
                                 if (data.error) {
                                     this.errorMsg = data.error;
                                     return;
                                 }
                                 window.snap.pay(data.snap_token, {
                                     onSuccess: (result) => {
                                         fetch('{{ route('customer.pesanan.midtrans.cekstatus', $pesanan->id) }}', {
                                             method: 'POST',
                                             headers: {
                                                 'Content-Type': 'application/json',
                                                 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                                             },
                                             body: JSON.stringify({ order_id: result.order_id })
                                         })
                                         .then(r => r.json())
                                         .then(res => {
                                             if (res.redirect) window.location.href = res.redirect;
                                             else window.location.reload();
                                         });
                                     },
                                     onPending: (result) => {
                                         window.location.reload();
                                     },
                                     onError: (result) => {
                                         this.errorMsg = 'Pembayaran gagal. Silakan coba lagi.';
                                     },
                                     onClose: () => {
                                         this.loading = false;
                                     }
                                 });
                             })
                             .catch(() => {
                                 this.loading = false;
                                 this.errorMsg = 'Gagal menghubungi server. Coba lagi.';
                             });
                         }
                     }">
                    <h3 class="text-xs font-black text-blue-700 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                        <i class="fa-solid fa-credit-card"></i> Bayar DP Sekarang
                        <span class="ml-auto bg-blue-100 text-blue-700 text-[8px] font-black px-2 py-0.5 rounded-full">Otomatis</span>
                    </h3>
                    <p class="text-[10px] text-slate-500 mb-4 leading-relaxed">
                        Bayar uang muka <strong class="text-blue-600">Rp{{ number_format($estimasiDpMidtrans, 0, ',', '.') }}</strong>
                        langsung via transfer bank, QRIS, atau metode lainnya — tanpa perlu upload bukti manual.
                    </p>
                    <div x-show="errorMsg" class="mb-3 p-2.5 bg-rose-50 border border-rose-200 rounded-xl text-[10px] text-rose-700 font-bold" x-text="errorMsg"></div>
                    <button @click="bayarMidtrans()" :disabled="loading"
                        class="w-full bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white font-black text-xs py-3.5 rounded-xl shadow-lg shadow-blue-500/20 transition-all active:scale-95 flex items-center justify-center gap-2">
                        <span x-show="!loading"><i class="fa-solid fa-bolt text-xs"></i> Bayar DP Sekarang</span>
                        <span x-show="loading"><i class="fa-solid fa-spinner fa-spin text-xs"></i> Memproses...</span>
                    </button>
                </div>
                @endif

                {{-- FORM UPLOAD / BAYAR PELUNASAN --}}
                @if($pesanan->status == 'menunggu_pelunasan')
                    @if($sudahUploadPelunasan)
                        <div class="bg-blue-50 border border-blue-200 rounded-2xl p-5 flex items-start gap-4">
                            <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center shrink-0 mt-0.5">
                                <i class="fa-solid fa-clock-rotate-left text-blue-500 text-base"></i>
                            </div>
                            <div>
                                <p class="text-xs font-black text-blue-700 mb-1">Pelunasan sudah diterima</p>
                                <p class="text-[10px] text-slate-500 font-medium leading-relaxed">
                                    Pembayaran pelunasan Anda sudah kami terima dan sedang diverifikasi otomatis oleh sistem.
                                    Surat jalan dan nota lunas akan diterbitkan setelah konfirmasi selesai.
                                </p>
                                <p class="text-[9px] text-blue-500 font-black mt-2 flex items-center gap-1">
                                    <i class="fa-solid fa-circle-info"></i> Tidak perlu upload ulang — cukup tunggu konfirmasi Admin.
                                </p>
                            </div>
                        </div>
                    @else
                        @php $sisaPelunasan = ($pesanan->total_pembayaran_final ?? 0) - ($pesanan->nominal_dp_dibayar ?? 0); @endphp

                        <div class="bg-white rounded-2xl border border-blue-200 shadow-sm p-5"
                             x-data="{
                                 loading: false,
                                 errorMsg: '',
                                 bayarPelunasan() {
                                     this.loading = true;
                                     this.errorMsg = '';
                                     fetch('{{ route('customer.pesanan.midtrans.token.pelunasan', $pesanan->id) }}', {
                                         method: 'POST',
                                         headers: {
                                             'Content-Type': 'application/json',
                                             'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                                         }
                                     })
                                     .then(r => r.json())
                                     .then(data => {
                                         this.loading = false;
                                         if (data.error) { this.errorMsg = data.error; return; }
                                         window.snap.pay(data.snap_token, {
                                             onSuccess: (result) => {
                                                 fetch('{{ route('customer.pesanan.midtrans.cekstatus.pelunasan', $pesanan->id) }}', {
                                                     method: 'POST',
                                                     headers: {
                                                         'Content-Type': 'application/json',
                                                         'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                                                     },
                                                     body: JSON.stringify({ order_id: result.order_id })
                                                 })
                                                 .then(r => r.json())
                                                 .then(res => {
                                                     if (res.redirect) window.location.href = res.redirect;
                                                     else window.location.reload();
                                                 });
                                             },
                                             onPending: () => { window.location.reload(); },
                                             onError: () => { this.errorMsg = 'Pembayaran gagal. Silakan coba lagi.'; },
                                             onClose: () => { this.loading = false; }
                                         });
                                     })
                                     .catch(() => {
                                         this.loading = false;
                                         this.errorMsg = 'Gagal menghubungi server. Coba lagi.';
                                     });
                                 }
                             }">
                            <h3 class="text-xs font-black text-blue-700 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                                <i class="fa-solid fa-bolt"></i> Lunasi Sekarang
                                <span class="ml-auto bg-blue-100 text-blue-700 text-[8px] font-black px-2 py-0.5 rounded-full">Direkomendasikan</span>
                            </h3>
                            <p class="text-[10px] text-slate-500 mb-4 leading-relaxed">
                                Bayar sisa tagihan <strong class="text-blue-600">Rp{{ number_format($sisaPelunasan, 0, ',', '.') }}</strong>
                                langsung via transfer bank, QRIS, atau e-wallet. Status pesanan berubah otomatis setelah pembayaran.
                            </p>
                            <div x-show="errorMsg" class="mb-3 p-2.5 bg-rose-50 border border-rose-200 rounded-xl text-[10px] text-rose-700 font-bold" x-text="errorMsg"></div>
                            <button @click="bayarPelunasan()" :disabled="loading"
                                class="w-full bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white font-black text-xs py-3.5 rounded-xl shadow-lg shadow-blue-500/20 transition-all active:scale-95 flex items-center justify-center gap-2">
                                <span x-show="!loading"><i class="fa-solid fa-bolt text-xs"></i> Lunasi Sekarang — Rp{{ number_format($sisaPelunasan, 0, ',', '.') }}</span>
                                <span x-show="loading"><i class="fa-solid fa-spinner fa-spin text-xs"></i> Memproses...</span>
                            </button>
                        </div>

                    @endif
                @endif

                {{-- UNDUH DOKUMEN --}}
                @if(in_array($pesanan->status, ['proses','menunggu_kalkulasi','menunggu_pelunasan','selesai']))
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                    <h3 class="text-xs font-black text-slate-500 uppercase tracking-wider mb-4">
                        <i class="fa-solid fa-file-arrow-down text-blue-500 mr-1.5"></i> Unduh Dokumen
                    </h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        @if($pesanan->nominal_dp_dibayar > 0)
                        <a href="{{ route('customer.pesanan.invoice', ['id'=>$pesanan->id,'type'=>'dp']) }}" target="_blank"
                           class="flex flex-col items-center gap-2 p-4 bg-blue-50 border border-blue-200 rounded-xl text-blue-700 hover:bg-blue-100 transition-colors">
                            <i class="fa-solid fa-file-invoice text-xl"></i>
                            <span class="text-[9px] font-black uppercase tracking-wider">Nota DP</span>
                        </a>
                        @endif
                        @if($pesanan->status == 'selesai')
                        <a href="{{ route('customer.pesanan.invoice', ['id'=>$pesanan->id,'type'=>'pelunasan']) }}" target="_blank"
                           class="flex flex-col items-center gap-2 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-700 hover:bg-emerald-100 transition-colors">
                            <i class="fa-solid fa-file-invoice-dollar text-xl"></i>
                            <span class="text-[9px] font-black uppercase tracking-wider">Nota Lunas</span>
                        </a>
                        @endif
                    </div>
                </div>
                @endif

                {{-- RIWAYAT SAMPLING --}}
                @if(isset($riwayatSampling) && $riwayatSampling->count() > 0)
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                    <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                        <h3 class="text-xs font-black text-slate-500 uppercase tracking-wider">
                            <i class="fa-solid fa-microscope text-teal-500 mr-1.5"></i> Riwayat Hasil Sampling
                        </h3>
                        @if(isset($samplingTerbaru) && $samplingTerbaru)
                            <span class="text-[9px] font-black bg-teal-50 text-teal-600 border border-teal-200 px-2 py-1 rounded-full">
                                Update {{ \Carbon\Carbon::parse($samplingTerbaru->tanggal_sampling)->diffForHumans() }}
                            </span>
                        @endif
                    </div>
                    <div class="space-y-2.5">
                        @foreach($riwayatSampling->sortByDesc('tanggal_sampling')->take(5) as $sampling)
                        <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl border border-slate-200">
                            <div class="w-8 h-8 bg-teal-100 rounded-lg flex items-center justify-center text-teal-600 shrink-0">
                                <i class="fa-solid fa-flask text-xs"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-1.5 mb-0.5 flex-wrap">
                                    <span class="text-[10px] font-black text-slate-900">{{ number_format($sampling->jumlah_ekor,0,',','.') }} Ekor</span>
                                    <span class="text-[9px] font-bold text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-100">Grade {{ $sampling->nama_grade ?? '-' }}</span>
                                    <span class="text-[9px] font-bold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-100">SR {{ $sampling->sr_persen }}%</span>
                                </div>
                                @if($sampling->keterangan)
                                    <p class="text-[9px] text-slate-500 font-medium leading-tight truncate">{{ $sampling->keterangan }}</p>
                                @endif
                                <p class="text-[9px] text-slate-400 font-bold mt-0.5">{{ \Carbon\Carbon::parse($sampling->tanggal_sampling)->translatedFormat('d M Y, H:i') }}</p>
                            </div>
                            @if($sampling->path_foto)
                            <div @click="activeImage='{{ asset('storage/'.$sampling->path_foto) }}'; isLightboxOpen=true"
                                 class="w-10 h-10 rounded-lg overflow-hidden border border-slate-200 cursor-zoom-in shrink-0">
                                <img src="{{ asset('storage/'.$sampling->path_foto) }}" class="w-full h-full object-cover">
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

            </div>{{-- end lg:col-span-2 --}}

            {{-- SIDEBAR KANAN (1/3) --}}
            <div class="space-y-4">

                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                    <h3 class="text-xs font-black text-slate-500 uppercase tracking-wider mb-4 pb-3 border-b border-slate-100">
                        <i class="fa-solid fa-circle-info text-blue-500 mr-1.5"></i> Info Pesanan
                    </h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-start gap-2">
                            <span class="text-xs text-slate-400 shrink-0">No. Invoice</span>
                            <span class="text-xs font-black text-slate-800 text-right">{{ $pesanan->nomor_invoice ?? '—' }}</span>
                        </div>
                        <div class="flex justify-between items-start gap-2">
                            <span class="text-xs text-slate-400 shrink-0">Tgl. Pesan</span>
                            <span class="text-xs font-bold text-slate-700">{{ \Carbon\Carbon::parse($pesanan->created_at)->translatedFormat('d M Y') }}</span>
                        </div>
                        @if($pesanan->nominal_dp_dibayar > 0)
                        <div class="flex justify-between items-start gap-2">
                            <span class="text-xs text-slate-400 shrink-0">DP Dibayar</span>
                            <span class="text-xs font-black text-blue-700">Rp{{ number_format($pesanan->nominal_dp_dibayar,0,',','.') }}</span>
                        </div>
                        @endif
                        @if($pesanan->total_pembayaran_final > 0)
                        <div class="pt-3 border-t border-slate-100 space-y-2">
                            <div class="flex justify-between items-start gap-2">
                                <span class="text-xs text-slate-400 shrink-0">Total Tagihan</span>
                                <span class="text-sm font-black text-slate-900">Rp{{ number_format($pesanan->total_pembayaran_final,0,',','.') }}</span>
                            </div>
                            @if($pesanan->nominal_dp_dibayar > 0)
                            <div class="flex justify-between items-start gap-2">
                                <span class="text-xs text-slate-400 shrink-0">Sisa Bayar</span>
                                <span class="text-sm font-black text-rose-600">Rp{{ number_format($pesanan->total_pembayaran_final - $pesanan->nominal_dp_dibayar,0,',','.') }}</span>
                            </div>
                            @endif
                        </div>
                        @endif
                    </div>
                </div>

                @php
                    $profilTambak = \Illuminate\Support\Facades\DB::table('profil_tambak')->first();
                    $wa = $profilTambak ? preg_replace('/^0/','62',$profilTambak->nomor_whatsapp) : '';
                @endphp
                @if($wa)
                <a href="https://wa.me/{{ $wa }}?text=Halo, saya ingin konfirmasi pesanan {{ $pesanan->nomor_invoice ?? '' }}"
                   target="_blank"
                   class="flex items-center justify-center gap-2.5 w-full py-3.5 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl font-bold text-sm transition-colors shadow-sm shadow-emerald-500/20">
                    <i class="fa-brands fa-whatsapp text-lg"></i> Hubungi Admin
                </a>
                @endif

            </div>
        </div>
    </div>
</div>
@if(in_array($pesanan->status, ['pending', 'menunggu_pelunasan']))
<script src="{{ config('midtrans.is_production')
    ? 'https://app.midtrans.com/snap/snap.js'
    : 'https://app.sandbox.midtrans.com/snap/snap.js' }}"
    data-client-key="{{ config('midtrans.client_key') }}">
</script>
@endif
</x-customer-layout>