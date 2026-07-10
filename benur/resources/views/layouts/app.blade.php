<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AQUAFARM | Solusi Benur Terintegrasi</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link class="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --font-sans: 'Inter', -apple-system, sans-serif;
        }

        body {
            font-family: var(--font-sans);
            background-color: #F1F5F9;
            color: #1C2434;
            letter-spacing: -0.01em;
        }

        .sidebar-mesh {
            background-color: #1C2434;
            background-image:
                radial-gradient(at 0% 0%, rgba(37, 99, 235, 0.25) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(29, 78, 216, 0.15) 0px, transparent 50%);
        }

        .nav-link {
            color: #8A99AF;
            font-weight: 600;
            transition: all 0.3s ease;
            border-radius: 12px;
        }

        .nav-link:hover {
            color: #FFFFFF;
            background: rgba(255, 255, 255, 0.08);
        }

        .nav-link.active {
            color: #FFFFFF;
            background: #3C50E0;
            box-shadow: 0 10px 20px -5px rgba(60, 80, 224, 0.4);
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }

        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body x-data="{ sidebarOpen: false }">
    <div class="flex h-screen overflow-hidden">

        {{-- Backdrop Mobile --}}
        <div x-show="sidebarOpen" x-transition:enter="transition opacity-ease-out duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition opacity-ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" @click="sidebarOpen = false"
            class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-sm md:hidden" x-cloak>
        </div>

        {{-- SIDEBAR CONTAINER --}}
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-50 w-72 sidebar-mesh transform transition-transform duration-300 md:relative md:translate-x-0 flex flex-col shadow-2xl">

            <div class="p-8 flex items-center gap-4">
                <div
                    class="w-12 h-12 bg-gradient-to-tr from-blue-600 to-blue-800 rounded-2xl flex items-center justify-center shadow-lg shadow-blue-500/40 flex-shrink-0 border border-white/10">
                    <i class="fa-solid fa-shrimp text-2xl text-white"></i>
                </div>
                <div class="flex flex-col">
                    <h1 class="text-xl font-extrabold text-white tracking-tight leading-none uppercase italic">
                        AQUA<span class="text-blue-400 font-medium">FARM</span>
                    </h1>
                    <span class="text-[9px] uppercase tracking-[0.1em] text-slate-400 font-bold mt-2">Solusi Benur
                        Terintegrasi</span>
                </div>
            </div>

            <nav class="flex-1 px-6 space-y-1.5 overflow-y-auto custom-scrollbar font-sans text-xs">

                {{-- KELOMPOK 1: MAIN CONTROL --}}
                <p class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-2 mt-2 px-3 opacity-50">
                    Main Control</p>
                <a href="{{ route('dashboard') }}"
                    class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }} flex items-center p-3.5 group">
                    <i class="fa-solid fa-chart-line text-sm mr-3 w-4 inline-flex justify-center"></i> Dashboard Utama
                </a>

                {{-- KELOMPOK 2: MARKETPLACE SISI CUSTOMER --}}
                @if (Auth::user()->role === 'customer')
                    <p
                        class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-2 px-3 opacity-50 pt-4">
                        Marketplace</p>

                    <a href="{{ route('customer.katalog') }}"
                        class="nav-link {{ request()->routeIs('customer.katalog') ? 'active' : '' }} flex items-center p-3.5 group">
                        <i class="fa-solid fa-store text-sm mr-3 w-4 inline-flex justify-center"></i> Katalog Benur Udang
                    </a>

                    <a href="{{ route('customer.keranjang') }}"
                        class="nav-link {{ request()->routeIs('customer.keranjang') ? 'active' : '' }} flex items-center p-3.5 group">
                        @php
                            $dbCartCount = 0;
                            if (Schema::hasTable('keranjang_sementara')) {
                                $dbCartCount = DB::table('keranjang_sementara')
                                    ->where('user_id', Auth::id())
                                    ->count();
                            }
                        @endphp
                        <span class="relative text-sm mr-3 w-4 flex items-center justify-center flex-shrink-0">
                            <i class="fa-solid fa-cart-shopping"></i>
                            @if ($dbCartCount > 0)
                                <span class="absolute -top-2 -right-2.5 w-4 h-4 bg-emerald-500 text-white text-[8px] flex items-center justify-center rounded-full border-2 border-[#1C2434] font-black">
                                    {{ $dbCartCount }}
                                </span>
                            @endif
                        </span>
                        Keranjang Belanja
                    </a>

                    <a href="{{ route('customer.pesanan.index') }}"
                        class="nav-link {{ request()->routeIs('customer.pesanan.*') ? 'active' : '' }} flex items-center p-3.5 group">
                        <i class="fa-solid fa-clock-rotate-left text-sm mr-3 w-4 inline-flex justify-center"></i> Riwayat Pesanan Saya
                    </a>
                @endif

                {{-- KELOMPOK 3: OTORITAS KONFIGURASI MASTER --}}
                @if (in_array(Auth::user()->role, ['admin', 'pemilik']))
                    <p
                        class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-2 px-3 opacity-50 pt-4">
                        Sistem Konfigurasi</p>

                    <a href="{{ route('users.index') }}"
                        class="nav-link {{ request()->routeIs('users.index') ? 'active' : '' }} flex items-center p-3.5 group">
                        <i class="fa-solid fa-users-gear text-sm mr-3 w-4 inline-flex justify-center"></i> Manajemen Hak User
                    </a>

                    <a href="{{ route('pengaturan.index') }}"
                        class="nav-link {{ request()->routeIs('pengaturan.index') ? 'active' : '' }} flex items-center p-3.5 group">
                        <i class="fa-solid fa-sliders text-sm mr-3 w-4 inline-flex justify-center"></i> Pengaturan Tambak
                    </a>

                    {{-- MENYUNTIKKAN MENU BARU: ADMIN PREVIEW ETALASE MODE PASAR HILIR --}}
                    <a href="{{ route('admin.katalog.preview') }}"
                        class="nav-link {{ request()->routeIs('admin.katalog.preview') ? 'active' : '' }} flex items-center p-3.5 group">
                        <i class="fa-solid fa-magnifying-glass-chart text-sm mr-3 w-4 inline-flex justify-center"></i> Pratinjau Etalase
                    </a>
                @endif

                {{-- KELOMPOK 4: MANAJEMEN PRODUKSI & EVALUASI BIAYA --}}
                @if (in_array(Auth::user()->role, ['admin', 'pemilik', 'operator']))
                    <p
                        class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-2 px-3 opacity-50 pt-4">
                        Siklus Produksi</p>

                    <a href="{{ route('kolam.index') }}"
                        class="nav-link {{ request()->routeIs('kolam.index') ? 'active' : '' }} flex items-center p-3.5 group">
                        <i class="fa-solid fa-boxes-stacked text-sm mr-3 w-4 inline-flex justify-center"></i> Monitoring Stok Kolam
                    </a>

                    <a href="{{ route('biaya.index') }}"
                        class="nav-link {{ request()->routeIs('biaya.index') ? 'active' : '' }} flex items-center p-3.5 group">
                        <i class="fa-solid fa-money-bill-trend-up text-sm mr-3 w-4 inline-flex justify-center"></i> Jurnal Biaya Operasional
                    </a>
                @endif

                {{-- KELOMPOK 5: GERBANG DISTRIBUSI TRANSAKSI & PREORDER --}}
                @if (in_array(Auth::user()->role, ['admin', 'pemilik', 'operator']))
                    <p
                        class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-2 px-3 opacity-50 pt-4">
                        Transaksi Penjualan</p>

                    <a href="{{ route('admin.pesanan.index') }}"
                        class="nav-link {{ request()->routeIs('admin.pesanan.*') ? 'active' : '' }} flex items-center p-3.5 group">
                        <i class="fa-solid fa-file-invoice text-sm mr-3 w-4 inline-flex justify-center"></i> Antrean Preorder Benur
                    </a>

                    @if (in_array(Auth::user()->role, ['admin', 'pemilik']))
                        <a href="{{ route('laporan.index') }}"
                            class="nav-link {{ request()->routeIs('laporan.*') ? 'active' : '' }} flex items-center p-3.5 group">
                            <i class="fa-solid fa-scale-balanced text-sm mr-3 w-4 inline-flex justify-center"></i> Laporan Keuangan
                        </a>
                    @endif
                @endif
            </nav>

            <div class="p-6 border-t border-white/5">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="w-full flex items-center justify-center gap-3 py-3.5 bg-gradient-to-r from-rose-500 to-red-600 hover:from-red-600 hover:to-rose-500 text-white rounded-2xl font-bold text-[11px] uppercase tracking-widest shadow-lg shadow-red-900/30 transition-all duration-300 active:scale-95 group">
                        LOG OUT
                    </button>
                </form>
            </div>
        </aside>

        {{-- MAIN SECTION CONTENT --}}
        <div class="flex-1 flex flex-col overflow-hidden">
            <header
                class="h-20 bg-white border-b border-slate-200 flex items-center justify-between px-8 z-10 shadow-sm">
                <div class="flex items-center">
                    <button @click="sidebarOpen = true"
                        class="md:hidden p-2.5 bg-slate-50 rounded-xl text-slate-600 border border-slate-200 transition-colors hover:bg-slate-100">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                </div>

                <div class="flex items-center gap-5 group cursor-default">
                    <div class="text-right hidden sm:block">
                        <h2 class="text-sm font-bold text-slate-800 leading-none">Selamat Datang, <span
                                class="text-blue-600">{{ Auth::user()->name }}!</span></h2>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.15em] mt-2">ROLE:
                            {{ strtoupper(Auth::user()->role) }}</p>
                    </div>
                    <div
                        class="flex items-center justify-center text-slate-300 group-hover:text-blue-600 transition-all duration-300 transform group-hover:scale-110">
                        <i class="fa-solid fa-circle-user text-[42px]"></i>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-8 md:p-12 bg-[#F8FAFC]">
                {{ $slot }}
            </main>
        </div>
    </div>
</body>

</html>