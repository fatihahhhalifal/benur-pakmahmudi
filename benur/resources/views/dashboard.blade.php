<x-app-layout>
    <div class="max-w-7xl mx-auto font-sans text-slate-800 antialiased animate-fade-in">

        {{-- WELCOME BANNER --}}
        <div
            class="relative overflow-hidden bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 md:p-8 text-white shadow-xl mb-6 select-none border border-slate-800">
            <div class="absolute inset-0 opacity-10 mix-blend-overlay pointer-events-none">
                <svg viewBox="0 0 400 200" xmlns="http://www.w3.org/2000/svg" class="w-full h-full scale-110">
                    <path fill="#FFFFFF"
                        d="M0,64L120,96C240,128,480,192,720,192C960,192,1200,128,1320,96L1440,64L1440,200L1320,200C1200,200,960,200,720,200C480,200,240,200,120,200L0,200Z">
                    </path>
                </svg>
            </div>
            <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <span class="text-[10px] font-black text-blue-400 uppercase tracking-[0.2em] block mb-1">Pusat
                        Kendali Operasional</span>
                    <h1 class="text-xl md:text-2xl font-black tracking-tight">Selamat Datang Kembali, <span
                            class="text-blue-400">{{ Auth::user()->name }}</span>!</h1>
                    <p class="text-slate-400 text-xs mt-1 max-w-xl">Sistem Informasi Manajemen Benur Udang Terpadu CV
                        Aquafarm Indonesia. Hak Akses Anda dikunci sebagai <span
                            class="text-white font-bold uppercase tracking-wider">[{{ Auth::user()->role }}]</span>.</p>
                </div>
                <div
                    class="bg-white/10 backdrop-blur-md border border-white/10 px-4 py-2.5 rounded-2xl text-right hidden sm:block">
                    <span class="text-[9px] font-bold text-slate-400 block uppercase">Sesi Kerja Berjalan</span>
                    <span class="text-xs font-black text-blue-300 block mt-0.5">{{ date('d F Y') }}</span>
                </div>
            </div>
        </div>

        @if (in_array(Auth::user()->role, ['admin', 'pemilik', 'operator']))

            {{-- 3 KARTU RINGKASAN --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">

                {{-- Card Jumlah Benur Hidup --}}
                <div
                    class="relative overflow-hidden bg-gradient-to-br from-blue-500 via-indigo-600 to-blue-700 text-white rounded-3xl p-6 shadow-xl shadow-blue-950/10 flex items-center justify-between group hover:scale-[1.01] transition-all duration-300">
                    <div class="absolute inset-0 opacity-15 mix-blend-overlay pointer-events-none">
                        <svg viewBox="0 0 400 200" xmlns="http://www.w3.org/2000/svg" class="w-full h-full scale-125">
                            <path fill="#FFFFFF"
                                d="M0,128L80,117.3C160,107,320,85,480,96C640,107,800,149,960,160C1120,171,1280,149,1360,138.7L1440,128L1440,200L1360,200C1280,200,1120,200,960,200C800,200,640,200,480,200C320,200,160,200,80,200L0,200Z">
                            </path>
                        </svg>
                    </div>
                    <div class="absolute inset-0 bg-white/[0.06] backdrop-blur-md pointer-events-none"></div>
                    <div class="space-y-1 relative z-10">
                        <span class="text-[10px] font-black text-blue-100 uppercase tracking-wider block">Jumlah Benur Hidup</span>
                        <span class="text-2xl font-black tracking-tight block">{{ number_format($totalBenurLive, 0, ',', '.') }}
                            <span class="text-xs font-bold text-blue-200">Ekor</span></span>
                    </div>
                    <div
                        class="w-12 h-12 bg-white/15 text-white backdrop-blur-md rounded-2xl flex items-center justify-center text-lg shadow-lg border border-white/10 rotate-3 group-hover:rotate-0 transition-all duration-300 relative z-10">
                        <i class="fa-solid fa-shrimp"></i>
                    </div>
                </div>

                {{-- Card Total Pemasukan --}}
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
                        <span class="text-[10px] font-black text-emerald-100 uppercase tracking-wider block">Total Pemasukan</span>
                        <span class="text-2xl font-black tracking-tight block">Rp
                            {{ number_format($totalPendapatan, 0, ',', '.') }}</span>
                    </div>
                    <div
                        class="w-12 h-12 bg-white/15 text-white backdrop-blur-md rounded-2xl flex items-center justify-center text-lg shadow-lg border border-white/10 -rotate-3 group-hover:rotate-0 transition-all duration-300 relative z-10">
                        <i class="fa-solid fa-money-bill-trend-up"></i>
                    </div>
                </div>

                {{-- Card Total Pengeluaran --}}
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
                        <span class="text-[10px] font-black text-rose-100 uppercase tracking-wider block">Total Pengeluaran</span>
                        <span class="text-2xl font-black tracking-tight block">Rp
                            {{ number_format($totalPengeluaran, 0, ',', '.') }}</span>
                    </div>
                    <div
                        class="w-12 h-12 bg-white/15 text-white backdrop-blur-md rounded-2xl flex items-center justify-center text-lg shadow-lg border border-white/10 group-hover:scale-105 transition-all duration-300 relative z-10">
                        <i class="fa-solid fa-vault"></i>
                    </div>
                </div>
            </div>

            {{-- PANEL OPERASIONAL --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Kolom Kiri: Status Kolam --}}
                <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-2xs space-y-4">
                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-wider">
                        <i class="fa-solid fa-chart-pie text-blue-500 mr-1.5"></i>Status Kolam
                    </h3>
                    <div class="flex items-center gap-4 bg-slate-50 p-4 rounded-2xl border">
                        <div
                            class="w-14 h-14 rounded-full border-4 border-blue-600 flex items-center justify-center font-black text-sm text-blue-600 bg-white shadow-xs">
                            {{ $totalKolam > 0 ? round(($kolamAktif / $totalKolam) * 100) : 0 }}%
                        </div>
                        <div>
                            <span class="text-lg font-black text-slate-900 block">{{ $kolamAktif }}
                                <span class="text-xs font-bold text-slate-400">Kolam Aktif</span></span>
                            <p class="text-[11px] text-slate-400 font-medium">Dari total {{ $totalKolam }} kolam terdaftar.</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3 pt-1">
                        <div class="p-3 bg-amber-50/50 rounded-xl border border-amber-100">
                            <span class="text-[9px] font-black text-amber-800 uppercase block">Pesanan Masuk</span>
                            <span class="text-base font-black text-slate-900 mt-0.5 block">{{ $antreanPreorder }} Transaksi</span>
                        </div>
                        <div class="p-3 bg-blue-50/50 rounded-xl border border-blue-100">
                            <span class="text-[9px] font-black text-blue-800 uppercase block">Sedang Diproses</span>
                            <span class="text-base font-black text-slate-900 mt-0.5 block">{{ $pesananDiproses }} Pesanan</span>
                        </div>
                    </div>
                </div>

                {{-- Kolom Kanan: Catatan Pengeluaran Terbaru --}}
                <div
                    class="bg-white rounded-3xl p-5 border border-slate-100 shadow-2xs lg:col-span-2 flex flex-col justify-between">
                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-wider mb-3">
                        <i class="fa-solid fa-list-check text-rose-500 mr-1.5"></i>Catatan Pengeluaran Terbaru
                    </h3>
                    <div class="flex-1 space-y-2.5">
                        @forelse($recentLogs as $log)
                            @php
                                $segmenBop = explode(' - ', $log->keterangan_biaya);
                                $kategoriBaku = $segmenBop[0] ?? 'Biaya Produksi';
                            @endphp
                            <div
                                class="flex items-center justify-between p-3 bg-slate-50 rounded-xl border border-slate-200/60 hover:bg-slate-50/80 transition-colors">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-8 h-8 bg-rose-50 border border-rose-100 rounded-lg flex items-center justify-center text-rose-600 text-xs">
                                        <i class="fa-solid fa-wallet"></i>
                                    </div>
                                    <div>
                                        <span class="text-xs font-black text-slate-900 uppercase block">{{ $kategoriBaku }}</span>
                                        <span class="text-[10px] text-slate-400 font-bold block mt-0.5">Kolam:
                                            {{ $log->nama_kolam }} |
                                            {{ \Carbon\Carbon::parse($log->created_at)->diffForHumans() }}</span>
                                    </div>
                                </div>
                                <span class="text-xs font-black text-rose-600">- Rp
                                    {{ number_format($log->nominal_biaya, 0, ',', '.') }}</span>
                            </div>
                        @empty
                            <p class="text-center text-slate-400 italic text-xs py-8">Belum ada catatan pengeluaran.</p>
                        @endforelse
                    </div>
                </div>
            </div>

        @else

            {{-- 3 KARTU CUSTOMER --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">

                {{-- Card Pesanan Menunggu DP --}}
                <div
                    class="relative overflow-hidden bg-gradient-to-br from-amber-500 via-orange-500 to-amber-600 text-white rounded-3xl p-6 shadow-xl shadow-amber-950/10 flex items-center justify-between group hover:scale-[1.01] transition-all duration-300">
                    <div class="absolute inset-0 opacity-15 mix-blend-overlay pointer-events-none">
                        <svg viewBox="0 0 400 200" xmlns="http://www.w3.org/2000/svg" class="w-full h-full scale-125">
                            <path fill="#FFFFFF"
                                d="M0,128L80,117.3C160,107,320,85,480,96C640,107,800,149,960,160C1120,171,1280,149,1360,138.7L1440,128L1440,200L1360,200C1280,200,1120,200,960,200C800,200,640,200,480,200C320,200,160,200,80,200L0,200Z">
                            </path>
                        </svg>
                    </div>
                    <div class="absolute inset-0 bg-white/[0.06] backdrop-blur-md pointer-events-none"></div>
                    <div class="space-y-1 relative z-10">
                        <span class="text-[10px] font-black text-amber-100 uppercase tracking-wider block">Pesanan Saya (Menunggu DP)</span>
                        <span class="text-2xl font-black tracking-tight block">{{ $myPreordersCount }}
                            <span class="text-xs font-bold text-amber-200">Nota</span></span>
                    </div>
                    <div
                        class="w-12 h-12 bg-white/15 text-white backdrop-blur-md rounded-2xl flex items-center justify-center text-lg shadow-lg border border-white/10 rotate-3 group-hover:rotate-0 transition-all duration-300 relative z-10">
                        <i class="fa-solid fa-receipt"></i>
                    </div>
                </div>

                {{-- Card Sedang Diproses --}}
                <div
                    class="relative overflow-hidden bg-gradient-to-br from-blue-500 via-indigo-600 to-blue-700 text-white rounded-3xl p-6 shadow-xl shadow-blue-950/10 flex items-center justify-between group hover:scale-[1.01] transition-all duration-300">
                    <div class="absolute inset-0 opacity-15 mix-blend-overlay pointer-events-none">
                        <svg viewBox="0 0 400 200" xmlns="http://www.w3.org/2000/svg" class="w-full h-full scale-125">
                            <path fill="#FFFFFF"
                                d="M0,128L80,117.3C160,107,320,85,480,96C640,107,800,149,960,160C1120,171,1280,149,1360,138.7L1440,128L1440,200L1360,200C1280,200,1120,200,960,200C800,200,640,200,480,200C320,200,160,200,80,200L0,200Z">
                            </path>
                        </svg>
                    </div>
                    <div class="absolute inset-0 bg-white/[0.06] backdrop-blur-md pointer-events-none"></div>
                    <div class="space-y-1 relative z-10">
                        <span class="text-[10px] font-black text-blue-100 uppercase tracking-wider block">Sedang Diproses</span>
                        <span class="text-2xl font-black tracking-tight block">{{ $myActiveOrdersCount }}
                            <span class="text-xs font-bold text-blue-200">Pesanan</span></span>
                    </div>
                    <div
                        class="w-12 h-12 bg-white/15 text-white backdrop-blur-md rounded-2xl flex items-center justify-center text-lg shadow-lg border border-white/10 -rotate-3 group-hover:rotate-0 transition-all duration-300 relative z-10">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                </div>

                {{-- Card Pesanan Selesai --}}
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
                        <span class="text-[10px] font-black text-emerald-100 uppercase tracking-wider block">Pesanan Selesai</span>
                        <span class="text-2xl font-black tracking-tight block">Rp
                            {{ number_format($myTotalInvoiced, 0, ',', '.') }}</span>
                    </div>
                    <div
                        class="w-12 h-12 bg-white/15 text-white backdrop-blur-md rounded-2xl flex items-center justify-center text-lg shadow-lg border border-white/10 group-hover:scale-105 transition-all duration-300 relative z-10">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                </div>
            </div>

            {{-- TABEL RIWAYAT PESANAN TERAKHIR --}}
            <div class="bg-white rounded-3xl border border-slate-100 p-5 shadow-2xs">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-wider">
                        <i class="fa-solid fa-list-ul text-blue-500 mr-1.5"></i>3 Pesanan Terakhir
                    </h3>
                    <a href="{{ route('customer.pesanan.index') }}"
                        class="text-[10px] bg-blue-50 hover:bg-blue-600 text-blue-700 hover:text-white px-3 py-1 rounded-xl transition-all font-black uppercase tracking-wider">Lihat Semua</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-[10px] font-black text-slate-400 uppercase tracking-wider border-b pb-2">
                                <th class="pb-2">No. Invoice</th>
                                <th class="pb-2">Waktu Pesan</th>
                                <th class="pb-2 text-right">Uang Muka (DP)</th>
                                <th class="pb-2 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y text-xs font-semibold text-slate-700">
                            @forelse($myRecentOrders as $mo)
                                <tr class="hover:bg-slate-50/60 transition-colors">
                                    <td class="py-3 font-black text-slate-900 uppercase">INV/{{ $mo->nomor_invoice }}</td>
                                    <td class="py-3 text-slate-400 font-medium">
                                        {{ \Carbon\Carbon::parse($mo->created_at)->translatedFormat('d M Y, H:i') }} WIB
                                    </td>
                                    <td class="py-3 text-right font-black text-slate-900">Rp
                                        {{ number_format($mo->nominal_dp_dibayar, 0, ',', '.') }}</td>
                                    <td class="py-3 text-center">
                                        <span
                                            class="px-2.5 py-0.5 rounded text-[9px] font-black uppercase tracking-wide inline-block bg-blue-50 text-blue-700 border border-blue-200">
                                            {{ str_replace('_', ' ', $mo->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-8 text-center text-slate-400 italic">Belum ada riwayat
                                        pesanan. Silakan jelajahi menu Katalog.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        @endif

    </div>

    <style>
        .animate-fade-in {
            animation: fIn 0.25s ease-out forwards;
        }
        @keyframes fIn {
            from { opacity: 0; transform: scale(0.995); }
            to { opacity: 1; transform: scale(1); }
        }
    </style>
</x-app-layout>