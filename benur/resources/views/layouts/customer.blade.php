<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AQUAFARM MARKET | Solusi Benur Udang</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        :root { --font-sans: 'Inter', -apple-system, sans-serif; }

        body {
            font-family: var(--font-sans);
            background-color: #EEF4FF;
            color: #0F172A;
            letter-spacing: -0.01em;
        }

        [x-cloak] { display: none !important; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(5px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in { animation: fadeIn 0.3s ease-in-out; }

        ::-webkit-scrollbar       { width: 4px; height: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 99px; }

        .hide-scrollbar                    { -ms-overflow-style: none; scrollbar-width: none; }
        .hide-scrollbar::-webkit-scrollbar { display: none; }
    </style>
</head>

<body class="min-h-screen">

    @php
        $cartCount = \Illuminate\Support\Facades\Schema::hasTable('keranjang_sementara')
            ? \Illuminate\Support\Facades\DB::table('keranjang_sementara')->where('user_id', Auth::id())->count()
            : 0;
    @endphp

    {{-- ── TOP NAVIGATION BAR ── --}}
    <header class="sticky top-0 z-50 bg-white border-b border-slate-200 shadow-sm">
        <div class="max-w-4xl mx-auto px-4 sm:px-6">
            <div class="flex items-center justify-between h-14">

                {{-- Brand --}}
                <a href="{{ route('customer.katalog') }}" class="flex items-center gap-2.5 shrink-0">
                    <div class="w-8 h-8 bg-blue-600 rounded-xl flex items-center justify-center shadow shadow-blue-500/30">
                        <i class="fa-solid fa-fish text-white text-sm"></i>
                    </div>
                    <span class="font-black text-slate-900 text-sm tracking-tight hidden sm:block">
                        AQUAFARM <span class="text-blue-600">MARKET</span>
                    </span>
                </a>

                {{-- Desktop nav --}}
                <nav class="hidden md:flex items-center gap-1">
                    <a href="{{ route('customer.katalog') }}"
                       class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-bold transition-colors
                              {{ request()->routeIs('customer.katalog*') ? 'bg-blue-50 text-blue-600' : 'text-slate-500 hover:text-slate-800 hover:bg-slate-50' }}">
                        <i class="fa-solid fa-store text-xs"></i> Katalog
                    </a>

                    <a href="{{ route('customer.keranjang') }}"
                       class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-bold transition-colors
                              {{ request()->routeIs('customer.keranjang') ? 'bg-blue-50 text-blue-600' : 'text-slate-500 hover:text-slate-800 hover:bg-slate-50' }}">
                        <i class="fa-solid fa-cart-shopping text-xs"></i> Keranjang
                        @if($cartCount > 0)
                            <span class="bg-rose-500 text-white text-[9px] min-w-[16px] h-4 px-1 flex items-center justify-center rounded-full font-black">
                                {{ $cartCount }}
                            </span>
                        @endif
                    </a>

                    <a href="{{ route('customer.pesanan.index') }}"
                       class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-bold transition-colors
                              {{ request()->routeIs('customer.pesanan.*') ? 'bg-blue-50 text-blue-600' : 'text-slate-500 hover:text-slate-800 hover:bg-slate-50' }}">
                        <i class="fa-solid fa-clipboard-list text-xs"></i> Riwayat
                    </a>
                </nav>

                {{-- User menu (desktop) --}}
                <div x-data="{ open: false }" class="relative hidden md:block">
                    <button @click="open = !open"
                        class="flex items-center gap-2 pl-2 pr-3 py-1.5 rounded-xl hover:bg-slate-50 transition-colors focus:outline-none">
                        <div class="w-7 h-7 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 shrink-0">
                            <i class="fa-solid fa-user text-xs"></i>
                        </div>
                        <span class="text-xs font-black text-slate-700 hidden sm:block max-w-[120px] truncate">
                            {{ Auth::user()->name }}
                        </span>
                        <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 hidden sm:block transition-transform"
                           :class="open ? 'rotate-180' : ''"></i>
                    </button>

                    <div x-show="open" @click.away="open = false"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 top-full mt-2 w-52 bg-white border border-slate-100 shadow-xl rounded-2xl p-2 z-50 origin-top-right"
                         x-cloak>
                        <div class="px-3 py-2.5 border-b border-slate-50 mb-1">
                            <p class="text-xs font-black text-slate-800 truncate">{{ Auth::user()->name }}</p>
                            <p class="text-[10px] font-medium text-slate-400 truncate mt-0.5">{{ Auth::user()->email }}</p>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="w-full text-left px-3 py-2.5 rounded-xl text-xs font-black text-rose-600 hover:bg-rose-50 transition-colors flex items-center gap-2">
                                <i class="fa-solid fa-right-from-bracket"></i> Keluar Aplikasi
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Mobile: user icon only --}}
                <div class="md:hidden w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center">
                    <i class="fa-solid fa-circle-user text-slate-400 text-lg"></i>
                </div>

            </div>
        </div>
    </header>

    {{-- ── MAIN CONTENT ── --}}
    <main class="max-w-4xl mx-auto px-4 sm:px-6 py-5 pb-24 md:pb-8 min-h-[calc(100vh-56px)]">
        {{ $slot }}
    </main>

    {{-- ── BOTTOM NAV (mobile only) ── --}}
    <nav class="md:hidden fixed bottom-0 left-0 right-0 z-50
                bg-white border-t border-slate-100 h-[60px]
                grid grid-cols-4 items-center
                shadow-[0_-4px_12px_rgba(0,0,0,0.06)] select-none">

        <a href="{{ route('customer.katalog') }}"
           class="flex flex-col items-center justify-center gap-1 h-full transition-colors
                  {{ request()->routeIs('customer.katalog*') ? 'text-blue-600' : 'text-slate-400' }}">
            <i class="fa-solid fa-store text-[18px]"></i>
            <span class="text-[9px] font-bold">Katalog</span>
        </a>

        <a href="{{ route('customer.keranjang') }}"
           class="flex flex-col items-center justify-center gap-1 h-full transition-colors relative
                  {{ request()->routeIs('customer.keranjang') ? 'text-blue-600' : 'text-slate-400' }}">
            <div class="relative">
                <i class="fa-solid fa-cart-shopping text-[18px]"></i>
                @if($cartCount > 0)
                    <span class="absolute -top-1.5 -right-2.5
                                 bg-rose-500 text-white text-[9px] font-black
                                 min-w-[15px] h-[15px] px-0.5
                                 flex items-center justify-center
                                 rounded-full border-2 border-white">
                        {{ $cartCount }}
                    </span>
                @endif
            </div>
            <span class="text-[9px] font-bold">Keranjang</span>
        </a>

        <a href="{{ route('customer.pesanan.index') }}"
           class="flex flex-col items-center justify-center gap-1 h-full transition-colors
                  {{ request()->routeIs('customer.pesanan.*') ? 'text-blue-600' : 'text-slate-400' }}">
            <i class="fa-solid fa-clipboard-list text-[18px]"></i>
            <span class="text-[9px] font-bold">Riwayat</span>
        </a>

        {{-- Mobile profile popup --}}
        <div x-data="{ openMobile: false }" class="relative flex flex-col items-center justify-center h-full">
            <button @click="openMobile = !openMobile"
                class="flex flex-col items-center justify-center gap-1 w-full h-full text-slate-400 focus:outline-none">
                <i class="fa-solid fa-circle-user text-[18px]"></i>
                <span class="text-[9px] font-bold">Profil</span>
            </button>

            <div x-show="openMobile" @click.away="openMobile = false"
                 x-transition.opacity
                 class="absolute bottom-[64px] right-1 w-48 bg-white border border-slate-100 shadow-xl rounded-2xl p-2 z-50"
                 x-cloak>
                <div class="p-3 border-b border-slate-50 flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 shrink-0">
                        <i class="fa-solid fa-user text-sm"></i>
                    </div>
                    <div class="overflow-hidden">
                        <p class="text-xs font-black text-slate-800 truncate">{{ Auth::user()->name }}</p>
                        <p class="text-[9px] font-bold text-slate-400 truncate">{{ Auth::user()->email }}</p>
                    </div>
                </div>
                <div class="p-1 mt-1">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                            class="w-full text-left px-3 py-2.5 rounded-xl text-xs font-black text-rose-600 hover:bg-rose-50 transition-colors flex items-center gap-2">
                            <i class="fa-solid fa-right-from-bracket"></i> Keluar Aplikasi
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </nav>

</body>
</html>