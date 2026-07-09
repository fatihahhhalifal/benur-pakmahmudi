<x-app-layout>
    @php
        // 1. Kelompokkan baris per PESANAN (1 pesanan bisa berisi banyak kolam/detail_pesanan)
        $grouped = [];

        foreach ($pesanan as $p) {

            // Sinkronisasi Harga Akurat dari master_harga (per kolam, karena tiap kolam bisa beda jenis/ukuran/grade)
            $hargaReal =
                \Illuminate\Support\Facades\DB::table('master_harga')
                    ->where('jenis_id', function ($q) use ($p) {
                        $q->select('jenis_id')->from('siklus_kolam')->where('id', $p->siklus_id);
                    })
                    ->where('ukuran_id', function ($q) use ($p) {
                        $q->select('ukuran_id')->from('siklus_kolam')->where('id', $p->siklus_id);
                    })
                    ->where('grade_id', function ($q) use ($p) {
                        $q->select('grade_id')->from('siklus_kolam')->where('id', $p->siklus_id);
                    })
                    ->value('harga_jual') ??
                ($p->harga_per_ekor_kontrak ?? 0);

            $konversi = $p->konversi_per_kantong ?? 1700;
            $totalKantongDraf = ($p->jumlah_sak_dipesan * 45) + $p->kantong_eceran_dipesan;
            $totalEkorDraf = $totalKantongDraf * $konversi;

            $totalKantongRiil = $p->total_kantong_riil_muat ?? 0;
            $sakRiil = floor($totalKantongRiil / 45);
            $totalEkorRiil = $totalKantongRiil * $konversi;

            $subtotalKotorDraf = $totalEkorDraf * $hargaReal;

            // Data 1 baris kolam (detail_pesanan) di dalam pesanan ini
            $kolamItem = [
                'detail_id' => $p->detail_id ?? '',
                'siklus_id' => $p->siklus_id ?? '',
                'nomor_invoice' => $p->nomor_invoice,
                'dp_terbayar' => $p->nominal_dp_dibayar ?? 0,
                'nama_jenis' => $p->nama_jenis ?? 'Vaname',
                'nama_kolam' => $p->nama_kolam,
                'live_doc' => $p->live_doc ?? '-',

                'jumlah_sak' => $p->jumlah_sak_dipesan,
                'kantong_eceran' => $p->kantong_eceran_dipesan,
                'total_kantong_draf' => $totalKantongDraf,
                'total_ekor_draf' => $totalEkorDraf,

                'sak_riil' => $sakRiil,
                'total_kantong_riil_muat' => $totalKantongRiil,
                'total_ekor_riil' => $totalEkorRiil,

                'konversi_per_kantong' => $konversi,
                'harga_kontrak' => $hargaReal,
                'subtotal_kotor_draf' => $subtotalKotorDraf,
                'subtotal_kotor' => $p->subtotal_kotor ?? 0,
                'diskon_pembulatan' => $p->diskon_pembulatan ?? 0,
                'total_kantong_hitung' => $p->total_kantong_hitung ?? 0,

                'sudah_timbang' => !empty($p->waktu_timbang_muat),
                'sudah_kalkulasi' => !empty($p->waktu_kalkulasi_final),

                'url_input_muat' => url('/admin/pesanan/' . $p->id . '/input-muat'),
                'url_kalkulasi' => url('/admin/pesanan/' . $p->id . '/kalkulasi-final'),

                'log_kalkulasi' => isset($p->log_kalkulasi)
                    ? $p->log_kalkulasi->map(function($l) {
                        return [
                            'aksi'          => $l->aksi,
                            'catatan'       => $l->catatan,
                            'nama_operator' => $l->nama_operator,
                            'waktu'         => \Carbon\Carbon::parse($l->created_at)->translatedFormat('d M Y H:i'),
                        ];
                    })->values()->toArray()
                    : [],

                'log_timbang_muat' => isset($p->log_timbang_muat_raw)
                    ? $p->log_timbang_muat_raw->map(function($l) {
                        $sebelum = json_decode($l->data_sebelum, true);
                        $sesudah = json_decode($l->data_sesudah, true);
                        return [
                            'konversi_lama' => $sebelum['konversi_per_kantong'] ?? null,
                            'konversi_baru' => $sesudah['konversi_per_kantong'] ?? null,
                            'nama_operator' => $l->nama_operator,
                            'waktu'         => \Carbon\Carbon::parse($l->created_at)->translatedFormat('d M Y H:i'),
                        ];
                    })->values()->toArray()
                    : [],
            ];

            // Kalau pesanan ini belum ada di $grouped, buat entri barunya (bagian pesanan-level)
            if (!isset($grouped[$p->id])) {
                $grouped[$p->id] = [
                    'id' => $p->id,
                    'nomor_invoice' => $p->nomor_invoice,
                    'nama_customer' => $p->nama_customer,
                    'created_at' => \Carbon\Carbon::parse($p->created_at)->format('d M Y, H:i'),
                    'created_raw' => \Carbon\Carbon::parse($p->created_at)->format('Y-m-d'),
                    'dp_terbayar' => $p->nominal_dp_dibayar ?? 0,
                    'pelunasan' => $p->total_pembayaran_final ?? 0,
                    'pelunasan_dibayar' => !empty($p->bukti_transfer_pelunasan),
                    'status' => $p->status,

                    'url_nota_dp' => route('admin.pesanan.invoice', ['id' => $p->id, 'type' => 'dp']),
                    'url_nota_lunas' => route('admin.pesanan.invoice', ['id' => $p->id, 'type' => 'pelunasan']),
                    'url_nota_dp_admin' => route('admin.pesanan.invoice', ['id' => $p->id, 'type' => 'dp']),
                    'url_nota_lunas_admin' => route('admin.pesanan.invoice', ['id' => $p->id, 'type' => 'pelunasan']),
                    'url_surat_jalan' => route('admin.pesanan.suratjalan', $p->id),
                    'url_batal' => url('/admin/pesanan/' . $p->id . '/batal'),
                    'url_kalkulasi' => url('/admin/pesanan/' . $p->id . '/kalkulasi-final'),
                    'url_validasi_lunas' => url('/admin/pesanan/' . $p->id . '/validasi-pelunasan'),

                    'kolams' => [],
                ];
            }

            $grouped[$p->id]['kolams'][] = $kolamItem;
        }

        // 2. Hitung agregat (jumlah semua kolam) per pesanan — dipakai kolom Rincian Keuangan
        foreach ($grouped as &$o) {
            $o['total_ekor_draf'] = array_sum(array_column($o['kolams'], 'total_ekor_draf'));
            $o['subtotal_kotor_draf'] = array_sum(array_column($o['kolams'], 'subtotal_kotor_draf'));
            $o['kewajiban_dp'] = $o['subtotal_kotor_draf'] * 0.2;
            $o['total_ekor_riil'] = array_sum(array_column($o['kolams'], 'total_ekor_riil'));
            $o['subtotal_kotor'] = array_sum(array_column($o['kolams'], 'subtotal_kotor'));
            $o['diskon_pembulatan'] = array_sum(array_column($o['kolams'], 'diskon_pembulatan'));
            $o['jumlah_kolam'] = count($o['kolams']);
            $o['jumlah_kolam_belum_timbang'] = count(array_filter($o['kolams'], fn($k) => !$k['sudah_timbang']));
            $o['jumlah_kolam_belum_kalkulasi'] = count(array_filter($o['kolams'], fn($k) => !$k['sudah_kalkulasi']));
        }
        unset($o);

        $ordersData = array_values($grouped);
    @endphp

    <div class="max-w-7xl mx-auto font-sans text-slate-800 antialiased" x-data="adminOrderManager({{ json_encode($ordersData) }})">

        {{-- NOTIFIKASI ALERT FLOATING TOAST --}}
        @if (session('success') || session('error') || $errors->any())
            <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)" x-show="show"
                x-transition:enter="transform ease-out duration-300 transition"
                x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
                x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed top-5 right-5 z-[100] max-w-sm w-full bg-white rounded-2xl shadow-2xl border flex overflow-hidden {{ session('success') ? 'border-emerald-200' : 'border-rose-200' }}"
                x-cloak>
                <div class="p-4 flex items-start gap-3 w-full">
                    <div class="shrink-0">
                        @if (session('success'))
                            <i class="fa-solid fa-circle-check text-xl text-emerald-500"></i>
                        @else
                            <i class="fa-solid fa-circle-exclamation text-xl text-rose-500"></i>
                        @endif
                    </div>
                    <div class="flex-1">
                        <p class="text-xs font-black text-slate-900 uppercase tracking-wide">Notifikasi Sistem</p>
                        <p class="text-[11px] font-bold text-slate-500 mt-0.5 leading-relaxed">
                            {{ session('success') ?? (session('error') ?? $errors->first()) }}</p>
                    </div>
                    <button @click="show = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </button>
                </div>
            </div>
        @endif

        {{-- HEADER DASHBOARD --}}
        <div class="mb-6 flex items-center gap-3">
            <div class="w-12 h-12 bg-blue-600 text-white rounded-[14px] flex items-center justify-center shadow-lg shadow-blue-500/30">
                <i class="fa-solid fa-server text-xl"></i>
            </div>
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-none mb-1">Manajemen Preorder</h1>
                @if (Auth::user()->role === 'admin')
                    <p class="text-[12px] text-slate-500 font-bold">Pusat kendali pemantauan pembayaran DP otomatis dan eksekusi timbang muat lapangan.</p>
                @else
                    <p class="text-[12px] text-slate-500 font-bold">Pusat komando eksekusi timbang muat benur di lapangan.</p>
                @endif
            </div>
        </div>

        {{-- CARD STATISTIK --}}
        @if (Auth::user()->role === 'admin')
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                {{-- Total Order --}}
                <div class="bg-gradient-to-br from-blue-600 to-indigo-800 backdrop-blur-xl border border-white/20 rounded-3xl p-5 shadow-[0_8px_30px_rgba(37,99,235,0.25)] relative overflow-hidden flex items-center gap-4 text-white">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-white/10 rounded-full blur-2xl -mt-6 -mr-6"></div>
                    <div class="w-12 h-12 rounded-2xl bg-white/20 flex items-center justify-center text-xl shrink-0 backdrop-blur-md border border-white/10">
                        <i class="fa-solid fa-boxes-stacked"></i></div>
                    <div class="relative z-10">
                        <p class="text-[10px] font-black uppercase tracking-widest mb-1 text-blue-100">Total Order</p>
                        <p class="text-3xl font-black leading-none">{{ $stats->total_aktif }}</p>
                    </div>
                </div>
                {{-- Menunggu Bayar DP --}}
                <div class="bg-gradient-to-br from-amber-500 to-orange-600 backdrop-blur-xl border border-white/20 rounded-3xl p-5 shadow-[0_8px_30px_rgba(245,158,11,0.25)] relative overflow-hidden flex items-center gap-4 text-white">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-white/10 rounded-full blur-2xl -mt-6 -mr-6"></div>
                    <div class="w-12 h-12 rounded-2xl bg-white/20 flex items-center justify-center text-xl shrink-0 backdrop-blur-md border border-white/10">
                        <i class="fa-solid fa-clock-rotate-left"></i></div>
                    <div class="relative z-10">
                        <p class="text-[10px] font-black uppercase tracking-widest mb-1 text-amber-100">Menunggu Bayar DP</p>
                        <p class="text-3xl font-black leading-none">{{ $stats->pending }}</p>
                    </div>
                </div>
                {{-- Siap Muat --}}
                <div class="bg-gradient-to-br from-emerald-500 to-teal-600 backdrop-blur-xl border border-white/20 rounded-3xl p-5 shadow-[0_8px_30px_rgba(16,185,129,0.25)] relative overflow-hidden flex items-center gap-4 text-white">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-white/10 rounded-full blur-2xl -mt-6 -mr-6"></div>
                    <div class="w-12 h-12 rounded-2xl bg-white/20 flex items-center justify-center text-xl shrink-0 backdrop-blur-md border border-white/10">
                        <i class="fa-solid fa-truck-ramp-box"></i></div>
                    <div class="relative z-10">
                        <p class="text-[10px] font-black uppercase tracking-widest mb-1 text-emerald-100">Siap Muat</p>
                        <p class="text-3xl font-black leading-none">{{ $stats->proses }}</p>
                    </div>
                </div>
                {{-- Dibatalkan --}}
                <div class="bg-gradient-to-br from-rose-500 to-red-600 backdrop-blur-xl border border-white/20 rounded-3xl p-5 shadow-[0_8px_30px_rgba(244,63,94,0.25)] relative overflow-hidden flex items-center gap-4 text-white">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-white/10 rounded-full blur-2xl -mt-6 -mr-6"></div>
                    <div class="w-12 h-12 rounded-2xl bg-white/20 flex items-center justify-center text-xl shrink-0 backdrop-blur-md border border-white/10">
                        <i class="fa-solid fa-ban"></i></div>
                    <div class="relative z-10">
                        <p class="text-[10px] font-black uppercase tracking-widest mb-1 text-rose-100">Dibatalkan</p>
                        <p class="text-3xl font-black leading-none">{{ $stats->batal }}</p>
                    </div>
                </div>
            </div>
        @else
            <div class="mb-8 w-full md:w-1/3">
                <div class="bg-gradient-to-br from-emerald-500 to-teal-600 backdrop-blur-xl border border-white/20 rounded-3xl p-5 shadow-[0_8px_30px_rgba(16,185,129,0.25)] relative overflow-hidden flex items-center gap-4 text-white">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-white/10 rounded-full blur-2xl -mt-6 -mr-6"></div>
                    <div class="w-12 h-12 rounded-2xl bg-white/20 flex items-center justify-center text-xl shrink-0 backdrop-blur-md border border-white/10">
                        <i class="fa-solid fa-truck-ramp-box"></i></div>
                    <div class="relative z-10">
                        <p class="text-[10px] font-black uppercase tracking-widest mb-1 text-emerald-100">Antrean Siap Muat</p>
                        <p class="text-3xl font-black leading-none">{{ $stats->proses }}</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- SEARCH & FILTER BAR --}}
        <div class="bg-white p-3 rounded-2xl shadow-sm border border-slate-200 mb-6 flex flex-col md:flex-row gap-3 items-center justify-between">
            <div class="relative w-full md:w-[400px]">
                <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="text" x-model="searchQuery" placeholder="Cari invoice, pembeli, atau komoditas..."
                    class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl pl-11 pr-4 py-2.5 text-sm font-medium focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
            </div>
            <div class="flex items-center gap-3 w-full md:w-auto">
                <div class="relative w-full md:w-auto">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400"><i class="fa-regular fa-calendar"></i></span>
                    <input type="date" x-model="filterDate"
                        class="w-full bg-slate-50 border border-slate-200 text-slate-600 rounded-xl pl-10 pr-3 py-2.5 text-xs font-black focus:ring-blue-500 focus:border-blue-500">
                </div>
                <button @click="searchQuery = ''; filterDate = ''"
                    class="bg-slate-100 hover:bg-slate-200 text-slate-600 px-4 py-2.5 rounded-xl text-xs font-black transition-colors shrink-0">Reset</button>
            </div>
        </div>

        {{-- TAB NAVIGASI STATUS --}}
        @if (Auth::user()->role === 'admin')
            <div class="flex mb-4 bg-transparent gap-8 overflow-x-auto hide-scrollbar select-none border-b border-slate-200">
                <button @click="currentTab = 'semua'; currentPage = 1"
                    :class="currentTab === 'semua' ? 'text-blue-600 font-black border-b-2 border-blue-600' : 'text-slate-500 hover:text-slate-700 font-bold border-b-2 border-transparent'"
                    class="min-w-max pb-3 text-[13px] transition-all focus:outline-none flex items-center gap-2">
                    Semua Order
                    <span :class="currentTab === 'semua' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-500'"
                        class="text-[10px] px-2 py-0.5 rounded-full font-black">{{ $stats->total_aktif }}</span>
                </button>
                <button @click="currentTab = 'pending'; currentPage = 1"
                    :class="currentTab === 'pending' ? 'text-amber-500 font-black border-b-2 border-amber-500' : 'text-slate-500 hover:text-slate-700 font-bold border-b-2 border-transparent'"
                    class="min-w-max pb-3 text-[13px] transition-all focus:outline-none flex items-center gap-2">
                    Menunggu Bayar DP
                    <span :class="currentTab === 'pending' ? 'bg-amber-500 text-white' : '{{ $stats->pending > 0 ? 'bg-amber-100 text-amber-600' : 'bg-slate-100 text-slate-500' }}'"
                        class="text-[10px] px-2 py-0.5 rounded-full font-black">{{ $stats->pending }}</span>
                </button>
                <button @click="currentTab = 'proses'; currentPage = 1"
                    :class="currentTab === 'proses' ? 'text-blue-600 font-black border-b-2 border-blue-600' : 'text-slate-500 hover:text-slate-700 font-bold border-b-2 border-transparent'"
                    class="min-w-max pb-3 text-[13px] transition-all focus:outline-none flex items-center gap-2">
                    Proses Muat
                    <span :class="currentTab === 'proses' ? 'bg-blue-600 text-white' : '{{ $stats->proses > 0 ? 'bg-blue-100 text-blue-600' : 'bg-slate-100 text-slate-500' }}'"
                        class="text-[10px] px-2 py-0.5 rounded-full font-black">{{ $stats->proses }}</span>
                </button>
                <button @click="currentTab = 'menunggu_kalkulasi'; currentPage = 1"
                    :class="currentTab === 'menunggu_kalkulasi' ? 'text-purple-600 font-black border-b-2 border-purple-600' : 'text-slate-500 hover:text-slate-700 font-bold border-b-2 border-transparent'"
                    class="min-w-max pb-3 text-[13px] transition-all focus:outline-none flex items-center gap-2">
                    Kalkulasi Diskon
                    <span :class="currentTab === 'menunggu_kalkulasi' ? 'bg-purple-600 text-white' : '{{ $stats->menunggu_kalkulasi > 0 ? 'bg-purple-100 text-purple-600' : 'bg-slate-100 text-slate-500' }}'"
                        class="text-[10px] px-2 py-0.5 rounded-full font-black">{{ $stats->menunggu_kalkulasi }}</span>
                </button>
                <button @click="currentTab = 'menunggu_pelunasan'; currentPage = 1"
                    :class="currentTab === 'menunggu_pelunasan' ? 'text-indigo-600 font-black border-b-2 border-indigo-600' : 'text-slate-500 hover:text-slate-700 font-bold border-b-2 border-transparent'"
                    class="min-w-max pb-3 text-[13px] transition-all focus:outline-none flex items-center gap-2">
                    Menunggu Pelunasan
                    <span :class="currentTab === 'menunggu_pelunasan' ? 'bg-indigo-600 text-white' : '{{ $stats->menunggu_pelunasan > 0 ? 'bg-indigo-100 text-indigo-600' : 'bg-slate-100 text-slate-500' }}'"
                        class="text-[10px] px-2 py-0.5 rounded-full font-black">{{ $stats->menunggu_pelunasan }}</span>
                </button>
                <button @click="currentTab = 'selesai'; currentPage = 1"
                    :class="currentTab === 'selesai' ? 'text-emerald-600 font-black border-b-2 border-emerald-600' : 'text-slate-500 hover:text-slate-700 font-bold border-b-2 border-transparent'"
                    class="min-w-max pb-3 text-[13px] transition-all focus:outline-none flex items-center gap-2">
                    Selesai Lunas
                    <span :class="currentTab === 'selesai' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-500'"
                        class="text-[10px] px-2 py-0.5 rounded-full font-black">{{ $stats->selesai }}</span>
                </button>
                <button @click="currentTab = 'batal'; currentPage = 1"
                    :class="currentTab === 'batal' ? 'text-rose-600 font-black border-b-2 border-rose-600' : 'text-slate-500 hover:text-slate-700 font-bold border-b-2 border-transparent'"
                    class="min-w-max pb-3 text-[13px] transition-all focus:outline-none flex items-center gap-2">
                    Dibatalkan
                    <span :class="currentTab === 'batal' ? 'bg-rose-600 text-white' : 'bg-slate-100 text-slate-500'"
                        class="text-[10px] px-2 py-0.5 rounded-full font-black">{{ $stats->batal }}</span>
                </button>
            </div>
        @else
            <div class="flex mb-4 bg-transparent gap-8 overflow-x-auto border-b border-slate-200">
                <button @click="currentTab = 'proses'; currentPage = 1"
                    :class="currentTab === 'proses' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-slate-500 border-b-2 border-transparent'"
                    class="font-black min-w-max pb-3 text-[13px] flex items-center gap-2">
                    Proses Muat
                    <span class="bg-blue-600 text-white text-[10px] px-2 py-0.5 rounded-full">{{ $stats->proses }}</span>
                </button>
                <button @click="currentTab = 'riwayat_operator'; currentPage = 1"
                    :class="currentTab === 'riwayat_operator' ? 'text-slate-800 border-b-2 border-slate-800' : 'text-slate-500 hover:text-slate-700 border-b-2 border-transparent'"
                    class="font-black min-w-max pb-3 text-[13px] flex items-center gap-2 transition-all">
                    Riwayat Anda
                    <template x-if="countRiwayatOperator() > 0">
                        <span :class="currentTab === 'riwayat_operator' ? 'bg-slate-800 text-white' : 'bg-slate-200 text-slate-600'"
                            class="text-[10px] px-2 py-0.5 rounded-full font-black" x-text="countRiwayatOperator()"></span>
                    </template>
                </button>
            </div>
        @endif

        {{-- MAIN TABLE CARD --}}
        <div class="bg-white border border-slate-200 rounded-[20px] shadow-[0_4px_20px_rgba(0,0,0,0.03)] overflow-hidden">
            <div class="overflow-x-auto w-full inline-block align-middle">
                <table class="w-full text-left border-collapse table-auto">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-black text-slate-500 capitalize whitespace-nowrap">
                            <th class="p-4 min-w-[200px]">Invoice / Pembeli</th>
                            <th class="p-4 min-w-[150px]">Komoditas Hulu</th>
                            <th class="p-4 min-w-[220px]">Formulasi Draf</th>
                            @if (Auth::user()->role === 'admin')
                                <th class="p-4 min-w-[230px]">Estimasi Keuangan <i class="fa-solid fa-circle-info text-blue-400 ml-1" title="Harga akurat dari Master Harga"></i></th>
                            @endif
                            <th class="p-4 min-w-[120px]">Status</th>
                            <th class="p-4 text-center min-w-[180px]">Aksi Operasional</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs font-semibold">

                        <template x-if="paginatedOrders.length === 0">
                            <tr>
                                <td colspan="{{ Auth::user()->role === 'admin' ? '6' : '5' }}"
                                    class="p-16 text-center text-slate-400">
                                    <i class="fa-solid fa-folder-open text-4xl mb-3 text-slate-200"></i>
                                    <p class="text-sm font-black text-slate-600">Data Tidak Ditemukan</p>
                                    <p class="text-xs font-medium mt-1">Belum ada tugas operasional untuk Anda saat ini.</p>
                                </td>
                            </tr>
                        </template>

                        <template x-for="p in paginatedOrders" :key="p.id">
                            <tr class="hover:bg-blue-50/30 transition-colors">

                                {{-- Invoice / Pembeli --}}
                                <td class="p-4 whitespace-nowrap align-top">
                                    <span class="text-blue-700 font-black tracking-tight block text-[13px] mb-1" x-text="p.nomor_invoice"></span>
                                    <span class="text-slate-900 font-black block" x-text="p.nama_customer"></span>
                                    <span class="text-[10px] text-slate-400 block font-medium" x-text="p.created_at"></span>
                                </td>

                                {{-- Komoditas — daftar semua kolam dalam pesanan ini --}}
                                <td class="p-4 align-top">
                                    <div class="space-y-1.5">
                                        <template x-for="k in p.kolams" :key="k.detail_id">
                                            <div>
                                                <span class="text-slate-900 font-black uppercase block" x-text="'Benur ' + k.nama_jenis"></span>
                                                <span class="text-[11px] text-slate-500 font-medium block flex items-center gap-1.5">
                                                    <span x-text="k.nama_kolam"></span>
                                                    <span class="bg-slate-100 border border-slate-200 px-1.5 py-0.5 rounded text-[9px] font-black" x-text="'DOC ' + k.live_doc"></span>
                                                </span>
                                            </div>
                                        </template>
                                        <template x-if="p.jumlah_kolam > 1">
                                            <span class="inline-block text-[9px] font-black text-blue-600 bg-blue-50 border border-blue-100 px-2 py-0.5 rounded-md mt-1" x-text="p.jumlah_kolam + ' Kolam'"></span>
                                        </template>
                                    </div>
                                </td>

                                {{-- Detail Pesanan — loop per kolam, wrapper tunggal wajib agar Alpine x-if siblings tidak konflik --}}
                                <td class="p-4 align-top" style="min-width:220px">
                                    <template x-for="k in p.kolams" :key="k.detail_id">
                                        <div class="space-y-2 mb-2 pb-2 border-b border-dashed border-slate-100 last:border-0 last:mb-0 last:pb-0">

                                            {{-- Draf Pesanan (kolam ini belum ditimbang) --}}
                                            <template x-if="!k.sudah_timbang">
                                                <div class="bg-slate-50 border border-slate-100 rounded-xl p-2.5 space-y-1">
                                                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                                                        <span x-text="'Draf — ' + k.nama_kolam"></span>
                                                    </p>
                                                    <div class="flex justify-between items-center">
                                                        <span class="text-[10px] text-slate-500 font-bold">Sak:</span>
                                                        <span class="text-[11px] font-black text-slate-800" x-text="formatRupiah(k.jumlah_sak) + ' Sak'"></span>
                                                    </div>
                                                    <div class="flex justify-between items-center">
                                                        <span class="text-[10px] text-slate-500 font-bold">Kantong:</span>
                                                        <span class="text-[11px] font-black text-slate-800" x-text="formatRupiah(k.total_kantong_draf) + ' Ktg'"></span>
                                                    </div>
                                                    <div class="flex justify-between items-center border-t border-slate-200 pt-1 mt-1">
                                                        <span class="text-[10px] text-blue-600 font-bold">Est. Ekor:</span>
                                                        <span class="text-[11px] font-black text-blue-600" x-text="formatRupiah(k.total_ekor_draf) + ' Ekor'"></span>
                                                    </div>
                                                    <p class="text-[8px] text-slate-400 font-medium" x-text="'(' + k.jumlah_sak + ' sak × 45 + ' + k.kantong_eceran + ' ecer) × ' + formatRupiah(k.konversi_per_kantong) + '/ktg'"></p>
                                                </div>
                                            </template>

                                            {{-- Fisik Riil (kolam ini sudah ditimbang) --}}
                                            <template x-if="k.sudah_timbang">
                                                <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-2.5 space-y-1">
                                                    <p class="text-[9px] font-black text-emerald-600 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                                                        <span x-text="'Riil — ' + k.nama_kolam"></span>
                                                        <i class="fa-solid fa-circle-check"></i>
                                                    </p>
                                                    <div class="flex justify-between items-center">
                                                        <span class="text-[10px] text-slate-500 font-bold">Sak:</span>
                                                        <span class="text-[11px] font-black text-slate-800" x-text="formatRupiah(k.sak_riil) + ' Sak'"></span>
                                                    </div>
                                                    <div class="flex justify-between items-center">
                                                        <span class="text-[10px] text-slate-500 font-bold">Kantong:</span>
                                                        <span class="text-[11px] font-black text-slate-800" x-text="formatRupiah(k.total_kantong_riil_muat) + ' Ktg'"></span>
                                                    </div>
                                                    <div class="flex justify-between items-center border-t border-emerald-200 pt-1 mt-1">
                                                        <span class="text-[10px] text-emerald-700 font-bold">Total Ekor:</span>
                                                        <span class="text-[11px] font-black text-emerald-700" x-text="formatRupiah(k.total_ekor_riil) + ' Ekor'"></span>
                                                    </div>
                                                    <p class="text-[8px] text-emerald-500 font-medium" x-text="k.total_kantong_riil_muat + ' kantong × ' + formatRupiah(k.konversi_per_kantong) + '/ktg'"></p>
                                                </div>
                                            </template>

                                            {{-- Riwayat Timbang Muat kolam ini --}}
                                            <div x-show="k.log_timbang_muat && k.log_timbang_muat.length > 0" class="space-y-1 mt-1">
                                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-wider flex items-center gap-1 whitespace-nowrap">
                                                    <i class="fa-solid fa-clock-rotate-left text-slate-300"></i> Riwayat Muat
                                                </p>
                                                <template x-for="(log, idx) in k.log_timbang_muat" :key="idx">
                                                    <div class="flex gap-2 items-start bg-slate-50 border border-slate-100 rounded-lg p-2">
                                                        <div class="w-5 h-5 rounded-full bg-blue-100 flex items-center justify-center shrink-0 mt-0.5">
                                                            <i class="fa-solid fa-scale-balanced text-blue-500" style="font-size:7px"></i>
                                                        </div>
                                                        <div>
                                                            <p class="text-[10px] font-black text-slate-700 leading-snug whitespace-nowrap">
                                                                Konversi:&nbsp;
                                                                <span class="text-rose-400 line-through" x-text="formatRupiah(log.konversi_lama)"></span>
                                                                <i class="fa-solid fa-arrow-right text-slate-300 mx-0.5" style="font-size:8px"></i>
                                                                <span class="text-emerald-600" x-text="formatRupiah(log.konversi_baru)"></span>
                                                            </p>
                                                            <p class="text-[8px] text-slate-400 font-bold mt-0.5 whitespace-nowrap" x-text="log.nama_operator + ' • ' + log.waktu"></p>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>

                                        </div>
                                    </template>
                                </td>

                                {{-- Rincian Keuangan (ADMIN) — angka gabungan semua kolam dalam pesanan --}}
                                @if (Auth::user()->role === 'admin')
                                    <td class="p-4 whitespace-nowrap align-top">
                                        <div class="bg-slate-50 border border-slate-100 p-2.5 rounded-xl space-y-1">
                                            <template x-if="p.status == 'pending' || p.status == 'proses'">
                                                <div class="space-y-1">
                                                    <div class="flex justify-between items-center">
                                                        <span class="text-[10px] font-bold text-slate-500">Est. Volume:</span>
                                                        <span class="text-[11px] font-black text-slate-900" x-text="formatRupiah(p.total_ekor_draf) + ' Ekor'"></span>
                                                    </div>
                                                    <div class="flex justify-between items-center border-t border-slate-200 pt-1">
                                                        <span class="text-[10px] font-bold text-slate-500">Nilai Kontrak:</span>
                                                        <span class="text-[11px] font-black text-slate-900" x-text="'Rp ' + formatRupiah(p.subtotal_kotor_draf)"></span>
                                                    </div>
                                                    <template x-if="p.dp_terbayar > 0">
                                                        <div class="flex justify-between items-center">
                                                            <span class="text-[10px] font-bold text-emerald-600">DP Terbayar:</span>
                                                            <span class="text-[11px] font-black text-emerald-600" x-text="'Rp ' + formatRupiah(p.dp_terbayar)"></span>
                                                        </div>
                                                    </template>
                                                    <template x-if="p.dp_terbayar == 0">
                                                        <div class="flex justify-between items-center">
                                                            <span class="text-[10px] font-bold text-amber-600">Wajib DP 20%:</span>
                                                            <span class="text-[11px] font-black text-amber-600" x-text="'Rp ' + formatRupiah(p.kewajiban_dp)"></span>
                                                        </div>
                                                    </template>
                                                </div>
                                            </template>
                                            <template x-if="p.status == 'menunggu_kalkulasi' || p.status == 'menunggu_pelunasan' || p.status == 'selesai'">
                                                <div class="space-y-1">
                                                    <div class="flex justify-between items-center">
                                                        <span class="text-[10px] font-bold text-slate-500">Vol. Riil:</span>
                                                        <span class="text-[11px] font-black text-slate-900" x-text="formatRupiah(p.total_ekor_riil) + ' Ekor'"></span>
                                                    </div>
                                                    <div class="flex justify-between items-center">
                                                        <span class="text-[10px] font-bold text-slate-500">Total Tagihan:</span>
                                                        <span class="text-[11px] font-black text-slate-900" x-text="'Rp ' + formatRupiah(p.pelunasan)"></span>
                                                    </div>
                                                    <template x-if="p.diskon_pembulatan > 0">
                                                        <div class="flex justify-between items-center">
                                                            <span class="text-[10px] font-bold text-rose-500">Diskon:</span>
                                                            <span class="text-[11px] font-black text-rose-500" x-text="'- Rp ' + formatRupiah(p.diskon_pembulatan)"></span>
                                                        </div>
                                                    </template>
                                                    <div class="flex justify-between items-center">
                                                        <span class="text-[10px] font-bold text-emerald-600">DP Terbayar:</span>
                                                        <span class="text-[11px] font-black text-emerald-600" x-text="'Rp ' + formatRupiah(p.dp_terbayar)"></span>
                                                    </div>
                                                    <div class="flex justify-between items-center border-t border-slate-200 pt-1">
                                                        <span class="text-[10px] font-black text-indigo-600">Sisa Tagihan:</span>
                                                        <span class="text-[11px] font-black text-indigo-600"
                                                            x-text="'Rp ' + formatRupiah(p.pelunasan - p.dp_terbayar)"></span>
                                                    </div>
                                                </div>
                                            </template>
                                            <template x-if="p.status == 'batal'">
                                                <div class="space-y-1">
                                                    <div class="flex justify-between items-center">
                                                        <span class="text-[10px] font-bold text-slate-500">Nilai Kontrak:</span>
                                                        <span class="text-[11px] font-black text-slate-400 line-through" x-text="'Rp ' + formatRupiah(p.subtotal_kotor_draf)"></span>
                                                    </div>
                                                    <span class="text-[10px] text-rose-400 font-bold italic">Order dibatalkan</span>
                                                </div>
                                            </template>
                                        </div>
                                    </td>
                                @endif

                                {{-- Status --}}
                                <td class="p-4 whitespace-nowrap align-top">
                                    <template x-if="p.status == 'pending'">
                                        <span class="bg-amber-50 text-amber-600 border border-amber-200 px-2.5 py-1 rounded-md text-[10px] font-black uppercase shadow-sm">Menunggu Bayar DP</span>
                                    </template>
                                    <template x-if="p.status == 'proses'">
                                        <span class="bg-blue-50 text-blue-600 border border-blue-200 px-2.5 py-1 rounded-md text-[10px] font-black uppercase shadow-sm">Proses Muat</span>
                                    </template>
                                    <template x-if="p.status == 'menunggu_kalkulasi'">
                                        <span class="bg-purple-50 text-purple-600 border border-purple-200 px-2.5 py-1 rounded-md text-[10px] font-black uppercase shadow-sm">Kalkulasi Admin</span>
                                    </template>
                                    <template x-if="p.status == 'menunggu_pelunasan'">
                                        <span class="bg-indigo-50 text-indigo-600 border border-indigo-200 px-2.5 py-1 rounded-md text-[10px] font-black uppercase shadow-sm">Tunggu Customer</span>
                                    </template>
                                    <template x-if="p.status == 'selesai'">
                                        <span class="bg-emerald-50 text-emerald-600 border border-emerald-200 px-2.5 py-1 rounded-md text-[10px] font-black uppercase shadow-sm">Selesai</span>
                                    </template>
                                    <template x-if="p.status == 'batal'">
                                        <span class="bg-rose-50 text-rose-600 border border-rose-200 px-2.5 py-1 rounded-md text-[10px] font-black uppercase shadow-sm">Batal</span>
                                    </template>
                                </td>

                                {{-- Aksi Operasional --}}
                                <td class="p-4 text-center whitespace-nowrap align-top">
                                    <div class="flex items-center justify-center gap-2">
                                        @if (Auth::user()->role === 'admin')
                                            {{-- Status pending: DP dibayar customer via Midtrans, sistem verifikasi otomatis.
                                                 Admin tidak perlu (dan tidak bisa) verifikasi manual di sini — cukup pantau
                                                 atau batalkan kalau pesanan memang mau digugurkan. --}}
                                            <template x-if="p.status == 'pending'">
                                                <div class="flex items-center gap-2 w-full justify-center">
                                                    <span class="text-[10px] text-slate-400 italic">Menunggu customer bayar DP...</span>
                                                    <button @click="openBatal = true; selectedOrder = p"
                                                        title="Batalkan Pesanan"
                                                        class="bg-rose-50 hover:bg-rose-600 text-rose-500 hover:text-white text-[12px] w-8 h-8 flex items-center justify-center rounded-lg transition-all border border-rose-200 hover:border-rose-600 shadow-sm shrink-0">
                                                        <i class="fa-solid fa-ban"></i>
                                                    </button>
                                                </div>
                                            </template>
                                            <template x-if="p.status == 'proses'">
                                                <span class="text-[10px] text-slate-400 italic font-medium">Tunggu Operator...</span>
                                            </template>
                                            {{-- Kalkulasi Tagihan — 1 tombol untuk seluruh pesanan (semua kolam sekaligus) --}}
                                            <template x-if="p.status == 'menunggu_kalkulasi'">
                                                <button @click="initKalkulasiData(p)"
                                                    class="bg-purple-600 hover:bg-purple-700 text-white text-[10px] uppercase px-3 py-1.5 rounded-lg font-black transition-all shadow-sm w-full">Kalkulasi Tagihan</button>
                                            </template>
                                            <template x-if="p.status == 'menunggu_pelunasan'">
                                                <div class="flex flex-col gap-1 w-full">
                                                    <template x-if="p.pelunasan_dibayar">
                                                        <form :action="p.url_validasi_lunas" method="POST">
                                                            @csrf
                                                            <button type="submit"
                                                                class="bg-indigo-600 hover:bg-indigo-700 text-white text-[10px] uppercase px-3 py-1.5 w-full rounded-lg font-black transition-all shadow-sm">Validasi & Selesai</button>
                                                        </form>
                                                    </template>
                                                    <template x-if="!p.pelunasan_dibayar">
                                                        <span class="text-[10px] text-slate-400 italic">Menunggu Pembayaran</span>
                                                    </template>
                                                </div>
                                            </template>
                                            <template x-if="p.status == 'selesai'">
                                                <div class="flex gap-2">
                                                    <a :href="p.url_nota_lunas_admin" target="_blank"
                                                        class="bg-slate-800 hover:bg-slate-900 text-white text-[10px] uppercase px-3 py-1.5 rounded-lg font-black transition-all shadow-sm">Nota Lunas</a>
                                                    <a :href="p.url_surat_jalan" target="_blank" title="Cetak Surat Jalan"
                                                        class="bg-slate-50 hover:bg-slate-100 text-blue-600 text-[12px] w-8 h-8 flex items-center justify-center rounded-lg font-black transition-all border border-slate-200 shadow-sm"><i class="fa-solid fa-truck"></i></a>
                                                </div>
                                            </template>
                                            <template x-if="p.status == 'batal'">
                                                <span class="text-[10px] text-slate-400 italic bg-slate-50 px-3 py-1.5 rounded-lg">No Action</span>
                                            </template>
                                        @else
                                            {{-- Timbang Muat (OPERATOR) — 1 tombol saja, buka daftar kolam di modal --}}
                                            <template x-if="p.status == 'proses'">
                                                <button @click="openMuatList = true; selectedOrder = p"
                                                    class="bg-blue-600 hover:bg-blue-700 text-white text-[10px] uppercase px-3 py-1.5 rounded-lg font-black shadow-sm transition-all w-full">Timbang Muat</button>
                                            </template>
                                            <template x-if="p.status != 'proses'">
                                                <span class="text-[10px] text-slate-400 italic bg-slate-50 px-3 py-1.5 rounded-lg">Diproses Admin</span>
                                            </template>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- LIVE PAGINATION CONTROLS --}}
            <div class="border-t border-slate-100 p-4 bg-[#F8FAFC] flex flex-col md:flex-row items-center justify-between text-xs text-slate-500 gap-3">
                <span x-text="'Menampilkan ' + (filteredOrders.length > 0 ? ((currentPage - 1) * itemsPerPage + 1) : 0) + ' - ' + Math.min(currentPage * itemsPerPage, filteredOrders.length) + ' dari ' + filteredOrders.length + ' data'"></span>
                <div class="flex gap-2 items-center">
                    <button @click="prevPage()" :disabled="currentPage === 1"
                        class="w-8 h-8 rounded-lg bg-white border border-slate-200 text-slate-600 flex items-center justify-center hover:bg-slate-50 disabled:opacity-50 transition-colors shadow-sm"><i class="fa-solid fa-chevron-left text-[10px]"></i></button>
                    <span class="font-black text-slate-800 px-2" x-text="currentPage + ' / ' + totalPages"></span>
                    <button @click="nextPage()" :disabled="currentPage === totalPages"
                        class="w-8 h-8 rounded-lg bg-white border border-slate-200 text-slate-600 flex items-center justify-center hover:bg-slate-50 disabled:opacity-50 transition-colors shadow-sm"><i class="fa-solid fa-chevron-right text-[10px]"></i></button>
                </div>
            </div>
        </div>

        {{-- ==================== MODAL PANELS ==================== --}}
        @if (Auth::user()->role === 'admin')
            {{-- MODAL BATAL --}}
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm"
                x-show="openBatal" x-transition.opacity x-cloak>
                <div class="bg-white rounded-[24px] max-w-sm w-full p-6 shadow-2xl border border-slate-100" @click.away="openBatal = false">
                    <div class="flex items-center gap-3 text-rose-500 mb-3">
                        <div class="w-10 h-10 rounded-full bg-rose-50 flex items-center justify-center"><i class="fa-solid fa-triangle-exclamation text-xl"></i></div>
                        <h3 class="text-base font-black text-slate-900">Pembatalan Order</h3>
                    </div>
                    <form :action="selectedOrder.url_batal" method="POST">
                        @csrf
                        <p class="text-[11px] text-slate-500 font-medium mb-3">Tindakan membatalkan Invoice <strong x-text="selectedOrder.nomor_invoice"></strong> tidak dapat dipulihkan. Stok akan kembali.</p>
                        <textarea name="alasan_batal" required
                            class="w-full border border-slate-200 rounded-xl p-3 text-[12px] focus:ring-rose-500 focus:border-rose-500 mb-5 bg-slate-50 placeholder:text-slate-400 shadow-inner"
                            rows="3" placeholder="Sertakan alasan pembatalan..."></textarea>
                        <div class="flex gap-3">
                            <button type="button" @click="openBatal = false" class="flex-1 bg-slate-100 py-3 text-[12px] rounded-xl font-black text-slate-600 hover:bg-slate-200 transition-colors shadow-sm">Tutup</button>
                            <button type="submit" class="flex-1 bg-rose-600 py-3 text-[12px] rounded-xl font-black text-white shadow-lg shadow-rose-600/30 hover:bg-rose-700 transition-transform active:scale-95">Eksekusi Batal</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- MODAL KALKULASI TAGIHAN FINAL --}}
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm"
                x-show="openKalkulasi" x-transition.opacity x-cloak>
                <div class="bg-white rounded-[24px] max-w-md w-full p-6 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto" @click.away="openKalkulasi = false">
                    <div class="mb-4">
                        <span class="text-[10px] font-black text-purple-600 bg-purple-50 border border-purple-200 px-2.5 py-1 rounded-md uppercase" x-text="dataKalkulasi.invoice"></span>
                        <h3 class="text-base font-black text-slate-900 mt-2">Kalkulasi Tagihan Final</h3>
                        <p class="text-[11px] text-slate-500 font-medium">Rincian per kolam di bawah, diskon pembulatan berlaku untuk total keseluruhan pesanan.</p>
                    </div>
                    <form :action="dataKalkulasi.url_kalkulasi" method="POST">
                        @csrf
                        {{-- Rincian per kolam --}}
                        <div class="space-y-2 mb-3">
                            <template x-for="(k, ki) in dataKalkulasi.kolams" :key="ki">
                                <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 text-[11px] space-y-1">
                                    <p class="font-black text-slate-600 mb-1" x-text="k.nama_kolam"></p>
                                    <div class="flex justify-between"><span class="text-slate-500">Kantong Riil:</span><span class="font-black text-slate-800" x-text="formatRupiah(k.kantong_riil) + ' Ktg'"></span></div>
                                    <div class="flex justify-between"><span class="text-slate-500">Volume Fisik:</span><span class="font-black text-slate-800" x-text="formatRupiah(k.volume_ekor) + ' Ekor'"></span></div>
                                    <div class="flex justify-between"><span class="text-slate-500">Harga Per Ekor:</span><span class="font-black text-slate-800" x-text="'Rp ' + formatRupiah(k.harga)"></span></div>
                                    <div class="flex justify-between border-t border-slate-200 pt-1 mt-1">
                                        <span class="font-black text-blue-600">Subtotal:</span>
                                        <span class="font-black text-blue-600" x-text="'Rp ' + formatRupiah(k.volume_ekor * k.harga)"></span>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="bg-indigo-50 border border-indigo-100 p-3 rounded-xl mb-4 text-[11px] space-y-1">
                            <div class="flex justify-between">
                                <span class="font-black text-indigo-700">Total Subtotal (semua kolam):</span>
                                <span class="font-black text-indigo-700" x-text="'Rp ' + formatRupiah(dataKalkulasi.total_subtotal)"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-bold text-emerald-600">DP Terbayar:</span>
                                <span class="font-black text-emerald-600" x-text="'Rp ' + formatRupiah(dataKalkulasi.dp)"></span>
                            </div>
                        </div>
                        <div class="mb-5">
                            <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider block mb-1.5">Potongan / Diskon Total (Rp)</label>
                            <input type="number" name="diskon_pembulatan" x-model.number="dataKalkulasi.diskon" required
                                class="w-full border border-slate-200 rounded-xl p-2.5 text-sm font-black text-slate-800 focus:ring-purple-500 shadow-inner">
                        </div>
                        <div class="flex justify-between items-center text-sm mb-5 pt-3 border-t border-slate-100">
                            <span class="font-black text-rose-600 uppercase text-[11px]">Sisa Pelunasan:</span>
                            <span class="font-black text-rose-600 text-lg tracking-tight"
                                x-text="'Rp ' + formatRupiah(dataKalkulasi.total_subtotal - dataKalkulasi.dp - (parseInt(dataKalkulasi.diskon) || 0))"></span>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" @click="openKalkulasi = false" class="flex-1 bg-slate-100 py-2.5 text-[12px] rounded-xl font-bold text-slate-500 hover:bg-slate-200 transition-colors">Tutup</button>
                            <button type="submit" class="flex-1 bg-purple-600 py-2.5 text-[12px] rounded-xl font-black text-white hover:bg-purple-700 shadow-lg shadow-purple-500/30 transition-transform active:scale-95">Terbitkan Tagihan</button>
                        </div>
                    </form>
                    {{-- Histori Kalkulasi --}}
                    <template x-if="dataKalkulasi.log_kalkulasi && dataKalkulasi.log_kalkulasi.length > 0">
                        <div class="mt-4 pt-4 border-t border-slate-100">
                            <h4 class="text-[10px] font-black text-slate-500 uppercase tracking-wider mb-2.5">
                                <i class="fa-solid fa-clock-rotate-left text-purple-400 mr-1"></i> Histori Perubahan
                            </h4>
                            <div class="space-y-2 max-h-36 overflow-y-auto">
                                <template x-for="(log, idx) in dataKalkulasi.log_kalkulasi" :key="idx">
                                    <div class="flex gap-2.5 items-start p-2 bg-slate-50 rounded-lg border border-slate-100">
                                        <div class="w-6 h-6 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 text-[9px] shrink-0 mt-0.5">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </div>
                                        <div>
                                            <p class="text-[10px] font-black text-slate-800 leading-snug" x-text="log.catatan"></p>
                                            <p class="text-[8px] text-slate-400 font-bold mt-0.5" x-text="log.nama_operator + ' • ' + log.waktu"></p>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        @endif

        {{-- MODAL DAFTAR KOLAM — TIMBANG MUAT (OPERATOR) --}}
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm"
            x-show="openMuatList" x-transition.opacity x-cloak>
            <div class="bg-white rounded-[24px] max-w-sm w-full p-6 shadow-2xl border border-slate-100" @click.away="openMuatList = false">
                <div class="mb-4">
                    <span class="text-[10px] font-black text-blue-600 bg-blue-50 border border-blue-200 px-2.5 py-1 rounded-md uppercase" x-text="selectedOrder.nomor_invoice"></span>
                    <h3 class="text-base font-black text-slate-900 mt-2">Pilih Kolam untuk Ditimbang</h3>
                    <p class="text-[11px] text-slate-500 font-medium">Pesanan ini berisi beberapa kolam, pilih salah satu untuk diinput muatannya.</p>
                </div>
                <div class="space-y-2 mb-4">
                    <template x-for="k in (selectedOrder.kolams || [])" :key="k.detail_id">
                        <div class="flex items-center justify-between bg-slate-50 border border-slate-200 rounded-xl p-3">
                            <div>
                                <p class="text-[12px] font-black text-slate-800" x-text="k.nama_kolam"></p>
                                <p class="text-[10px] text-slate-400 font-bold" x-text="'DOC ' + k.live_doc"></p>
                            </div>
                            <template x-if="!k.sudah_timbang">
                                <button @click="openMuatList = false; initMuatData(k)"
                                    class="bg-blue-600 hover:bg-blue-700 text-white text-[10px] uppercase px-3 py-1.5 rounded-lg font-black transition-all shadow-sm">Timbang</button>
                            </template>
                            <template x-if="k.sudah_timbang">
                                <span class="text-[10px] text-emerald-600 font-black italic flex items-center gap-1">
                                    <i class="fa-solid fa-circle-check"></i> Selesai
                                </span>
                            </template>
                        </div>
                    </template>
                </div>
                <button type="button" @click="openMuatList = false" class="w-full bg-slate-100 py-3 text-[12px] rounded-xl font-black text-slate-600 hover:bg-slate-200 transition-colors">Tutup</button>
            </div>
        </div>

        {{-- MODAL EKSEKUSI MUAT FINAL (OPERATOR) --}}
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm"
            x-show="openMuat" x-transition.opacity x-cloak>
            <div class="bg-white rounded-[24px] max-w-md w-full p-6 shadow-2xl border border-slate-100" @click.away="openMuat = false">
                <div class="mb-5 flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="text-base font-black text-slate-900 leading-none mb-1">Timbang Muat Lapangan</h3>
                        <p class="text-[10px] text-slate-500 font-medium uppercase tracking-wider">Hitung muatan fisik truk</p>
                    </div>
                    <span class="text-[10px] font-black text-blue-600 bg-blue-50 px-2.5 py-1 rounded-md uppercase border border-blue-100" x-text="dataMuat.invoice"></span>
                </div>
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 mb-4 text-[11px] space-y-1">
                    <p class="font-black text-slate-500 uppercase text-[9px] tracking-wider mb-1">Draf Pesanan Customer</p>
                    <div class="flex justify-between"><span class="text-slate-500">Sak Draf:</span><span class="font-black" x-text="dataMuat.sak_draf + ' Sak'"></span></div>
                    <div class="flex justify-between"><span class="text-slate-500">Ecer Draf:</span><span class="font-black" x-text="dataMuat.ecer_draf + ' Kantong'"></span></div>
                    <div class="flex justify-between border-t border-slate-200 pt-1"><span class="text-slate-500">Total Kantong Draf:</span><span class="font-black text-blue-600" x-text="formatRupiah(dataMuat.total_ktg_draf) + ' Ktg'"></span></div>
                </div>
                <form :action="dataMuat.url_input_muat" method="POST">
                    @csrf
                    <input type="hidden" name="detail_id" :value="dataMuat.detail_id">
                    <input type="hidden" name="total_kantong_riil_muat"
                        :value="(parseInt(dataMuat.sak) || 0) * 45 + (parseInt(dataMuat.ecer) || 0)">
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider block mb-1.5">Sak Riil <span class="text-slate-400 normal-case">(× 45 ktg)</span></label>
                            <input type="number" x-model.number="dataMuat.sak" required
                                class="w-full border border-slate-200 rounded-xl p-2.5 text-sm font-black text-slate-800 focus:ring-blue-500 bg-slate-50 shadow-inner">
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider block mb-1.5">Ecer Riil <span class="text-slate-400 normal-case">(kantong)</span></label>
                            <input type="number" x-model.number="dataMuat.ecer" required
                                class="w-full border border-slate-200 rounded-xl p-2.5 text-sm font-black text-slate-800 focus:ring-blue-500 bg-slate-50 shadow-inner">
                        </div>
                    </div>
                    <div class="bg-blue-50 border border-blue-100 rounded-xl p-3 mb-4 text-[11px] space-y-1">
                        <p class="font-black text-blue-600 text-[9px] uppercase tracking-wider mb-1">Preview Riil Muat</p>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Total Kantong:</span>
                            <span class="font-black text-slate-800" x-text="formatRupiah((parseInt(dataMuat.sak) || 0) * 45 + (parseInt(dataMuat.ecer) || 0)) + ' Ktg'"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Est. Total Ekor:</span>
                            <span class="font-black text-blue-700" x-text="formatRupiah(((parseInt(dataMuat.sak) || 0) * 45 + (parseInt(dataMuat.ecer) || 0)) * dataMuat.konversi) + ' Ekor'"></span>
                        </div>
                    </div>
                    <div class="mb-6">
                        <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider block mb-1.5">Kepadatan (Ekor/Kantong)</label>
                        <input type="number" name="konversi_per_kantong_aktual" x-model.number="dataMuat.konversi" required
                            class="w-full border border-slate-200 rounded-xl p-2.5 text-sm font-black text-slate-800 focus:ring-blue-500 bg-slate-50 shadow-inner">
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="openMuat = false" class="flex-1 bg-slate-100 py-3 text-[12px] rounded-xl font-black text-slate-600 hover:bg-slate-200 transition-colors shadow-sm">Batal</button>
                        <button type="submit" class="flex-[1.5] bg-blue-600 py-3 text-[12px] rounded-xl font-black text-white shadow-lg shadow-blue-500/30 hover:bg-blue-700 transition-transform active:scale-95">Ajukan Ke Admin</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    {{-- SCRIPT ALPINE REAKTIF --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('adminOrderManager', (initialOrders) => ({
                orders: initialOrders,
                searchQuery: '',
                filterDate: '',
                currentTab: @json(Auth::user()->role === 'operator' ? 'proses' : 'semua'),
                currentPage: 1,
                itemsPerPage: 10,

                openBatal: false,
                openMuat: false,
                openMuatList: false,
                openKalkulasi: false,
                selectedOrder: {},

                dataMuat: {
                    sak: 0,
                    ecer: 0,
                    konversi: 1700,
                    sak_draf: 0,
                    ecer_draf: 0,
                    total_ktg_draf: 0,
                },
                dataKalkulasi: {
                    diskon: 0,
                    volume_ekor: 0,
                    kantong_riil: 0,
                    harga: 0,
                    dp: 0,
                    log_kalkulasi: []
                },

                init() {
                    this.$watch('currentTab', () => { this.currentPage = 1; });
                    this.$watch('searchQuery', () => { this.currentPage = 1; });
                    this.$watch('filterDate', () => { this.currentPage = 1; });
                },

                countByStatus(status) {
                    return this.orders.filter(o => o.status === status).length;
                },

                countRiwayatOperator() {
                    return this.orders.filter(o => ['menunggu_kalkulasi', 'menunggu_pelunasan', 'selesai'].includes(o.status)).length;
                },

                initMuatData(p) {
                    this.dataMuat = {
                        id: p.id,
                        detail_id: p.detail_id,
                        url_input_muat: p.url_input_muat,
                        invoice: p.nomor_invoice,
                        sak: p.jumlah_sak,
                        ecer: p.kantong_eceran,
                        konversi: p.konversi_per_kantong || 1700,
                        sak_draf: p.jumlah_sak,
                        ecer_draf: p.kantong_eceran,
                        total_ktg_draf: p.total_kantong_draf,
                    };
                    this.openMuat = true;
                },

                initKalkulasiData(p) {
                    const kolamsSiapDihitung = (p.kolams || []).map(k => ({
                        nama_kolam: k.nama_kolam,
                        kantong_riil: k.total_kantong_riil_muat,
                        volume_ekor: k.total_ekor_riil,
                        harga: k.harga_kontrak,
                    }));
                    const totalSubtotal = kolamsSiapDihitung.reduce((sum, k) => sum + (k.volume_ekor * k.harga), 0);

                    this.dataKalkulasi = {
                        invoice: p.nomor_invoice,
                        url_kalkulasi: p.url_kalkulasi,
                        kolams: kolamsSiapDihitung,
                        total_subtotal: totalSubtotal,
                        dp: p.dp_terbayar,
                        diskon: 0,
                        log_kalkulasi: p.log_kalkulasi || []
                    };
                    this.openKalkulasi = true;
                },

                get filteredOrders() {
                    let result = this.orders;
                    const role = '{!! Auth::user()->role !!}';

                    if (role === 'operator') {
                        if (this.currentTab === 'proses') {
                            result = result.filter(o => o.status === 'proses');
                        } else if (this.currentTab === 'riwayat_operator') {
                            result = result.filter(o => ['menunggu_kalkulasi', 'menunggu_pelunasan', 'selesai'].includes(o.status));
                        } else {
                            result = result.filter(o => o.status === 'proses');
                        }
                    } else {
                        if (this.currentTab !== 'semua') {
                            result = result.filter(o => o.status === this.currentTab);
                        }
                    }

                    if (this.searchQuery.trim() !== '') {
                        const q = this.searchQuery.toLowerCase();
                        result = result.filter(o =>
                            o.nomor_invoice.toLowerCase().includes(q) ||
                            o.nama_customer.toLowerCase().includes(q) ||
                            (o.kolams || []).some(k => k.nama_kolam.toLowerCase().includes(q))
                        );
                    }
                    if (this.filterDate !== '') {
                        result = result.filter(o => o.created_raw.startsWith(this.filterDate));
                    }
                    return result;
                },

                get paginatedOrders() {
                    const start = (this.currentPage - 1) * this.itemsPerPage;
                    return this.filteredOrders.slice(start, start + this.itemsPerPage);
                },

                get totalPages() {
                    return Math.ceil(this.filteredOrders.length / this.itemsPerPage) || 1;
                },

                formatRupiah(angka) {
                    return new Intl.NumberFormat('id-ID').format(angka);
                },

                prevPage() { if (this.currentPage > 1) this.currentPage--; },
                nextPage() { if (this.currentPage < this.totalPages) this.currentPage++; }
            }))
        })
    </script>

    <style>
        [x-cloak] { display: none !important; }
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</x-app-layout>