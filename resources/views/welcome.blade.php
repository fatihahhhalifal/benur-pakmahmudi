<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AQUAFARM | Solusi Benur Terintegrasi</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root { --font-sans: 'Inter', -apple-system, sans-serif; }
        body { font-family: var(--font-sans); background-color: #0B111E; color: #F8FAFC; overflow-x: hidden; }
        .ocean-mesh {
            background-image:
                radial-gradient(at 0% 0%, rgba(30, 64, 175, 0.4) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(6, 182, 212, 0.15) 0px, transparent 50%),
                radial-gradient(at 50% 50%, rgba(15, 23, 42, 0.95) 0px, transparent 100%);
        }
        .glass-card {
            background: rgba(30, 41, 59, 0.4);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
    </style>
</head>

<body class="ocean-mesh min-h-screen flex flex-col justify-between antialiased">

    {{-- NAVBAR --}}
    <header class="w-full max-w-7xl mx-auto px-6 h-24 flex items-center justify-between z-50">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-gradient-to-tr from-blue-600 to-cyan-500 rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/20">
                <i class="fa-solid fa-shrimp text-lg text-white"></i>
            </div>
            <div class="flex flex-col">
                <span class="text-base font-black tracking-tight leading-none uppercase italic">AQUA<span class="text-blue-400 font-normal">FARM</span></span>
                <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest mt-1">Solusi Benur Terintegrasi</span>
            </div>
        </div>

        @if (Route::has('login'))
            <nav class="flex items-center gap-2">
                @auth
                    <a href="{{ url('/dashboard') }}"
                        class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-black uppercase tracking-widest transition-all shadow-lg shadow-blue-600/20 active:scale-95">
                        Masuk Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}"
                        class="px-5 py-2.5 text-slate-300 hover:text-white rounded-xl text-xs font-black uppercase tracking-widest transition-all">
                        Masuk
                    </a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}"
                            class="px-5 py-2.5 bg-white/5 hover:bg-white/10 text-white border border-white/10 rounded-xl text-xs font-black uppercase tracking-widest transition-all">
                            Daftar
                        </a>
                    @endif
                @endauth
            </nav>
        @endif
    </header>

    {{-- HERO --}}
    <main class="w-full max-w-7xl mx-auto px-6 py-12 flex-1 flex flex-col lg:flex-row items-center justify-center gap-12 lg:gap-6 relative">

        {{-- KIRI: Teks & Tombol --}}
        <div class="flex-1 text-center lg:text-left space-y-6 z-10 max-w-2xl">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-blue-500/10 border border-blue-500/20 rounded-full text-blue-400 text-[10px] font-black uppercase tracking-widest">
                <i class="fa-solid fa-circle-nodes animate-pulse"></i> AQUAFARM V1.0
            </div>
            <h1 class="text-4xl md:text-6xl font-black text-white tracking-tight leading-[1.05] uppercase">
                Sistem Informasi <br>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 via-cyan-400 to-indigo-400">Penjualan Benur</span>
            </h1>
            <p class="text-sm md:text-base text-slate-400 font-medium leading-relaxed">
                AQUAFARM membantu petambak memesan benur udang berkualitas secara online langsung dari tempat pembenihan.
                Proses pemesanan mudah, harga transparan, dan pengiriman terjadwal.
            </p>

            <div class="pt-4 flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4">
                @auth
                    <a href="{{ url('/dashboard') }}"
                        class="w-full sm:w-auto px-8 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-2xl text-xs font-black uppercase tracking-widest shadow-xl shadow-blue-600/20 transition-all flex items-center justify-center gap-3 group">
                        Kembali Ke Dashboard <i class="fa-solid fa-arrow-right transition-transform group-hover:translate-x-1"></i>
                    </a>
                @else
                    <a href="{{ route('login') }}"
                        class="w-full sm:w-auto px-8 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-2xl text-xs font-black uppercase tracking-widest shadow-xl shadow-blue-600/20 transition-all flex items-center justify-center gap-3 group">
                        Lihat Katalog Benur <i class="fa-solid fa-right-to-bracket transition-transform group-hover:translate-x-1"></i>
                    </a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}"
                            class="w-full sm:w-auto px-8 py-4 glass-card text-white rounded-2xl text-xs font-black uppercase tracking-widest transition-all flex items-center justify-center gap-2">
                            Daftar Sebagai Pembeli
                        </a>
                    @endif
                @endauth
            </div>
        </div>

        {{-- KANAN: 4 Kartu Fitur --}}
        <div class="flex-1 w-full max-w-md lg:max-w-none grid grid-cols-1 sm:grid-cols-2 gap-4 z-10">

            {{-- Kartu 1 --}}
            <div class="glass-card p-6 rounded-[2rem] flex flex-col justify-between h-48 transition-all hover:-translate-y-1 hover:border-blue-500/30 group">
                <div class="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-400 flex items-center justify-center">
                    <i class="fa-solid fa-water-ladder text-base"></i>
                </div>
                <div>
                    <h3 class="text-xs font-black text-white uppercase tracking-wider mb-1">Pantau Stok Kolam</h3>
                    <p class="text-[11px] text-slate-400 font-medium leading-normal">Lihat jumlah benur yang tersedia di setiap kolam secara langsung, lengkap dengan data usia dan tingkat kelangsungan hidup.</p>
                </div>
            </div>

            {{-- Kartu 2 --}}
            <div class="glass-card p-6 rounded-[2rem] flex flex-col justify-between h-48 transition-all hover:-translate-y-1 hover:border-cyan-500/30 group">
                <div class="w-10 h-10 rounded-xl bg-cyan-500/10 text-cyan-400 flex items-center justify-center">
                    <i class="fa-solid fa-file-invoice text-base"></i>
                </div>
                <div>
                    <h3 class="text-xs font-black text-white uppercase tracking-wider mb-1">Pesan Benur Mudah</h3>
                    <p class="text-[11px] text-slate-400 font-medium leading-normal">Pesan benur langsung dari katalog, bayar uang muka, dan pantau status pengiriman sampai benur tiba di tambak Anda.</p>
                </div>
            </div>

            {{-- Kartu 3 --}}
            <div class="glass-card p-6 rounded-[2rem] flex flex-col justify-between h-48 transition-all hover:-translate-y-1 hover:border-indigo-500/30 group">
                <div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center">
                    <i class="fa-solid fa-money-bill-trend-up text-base"></i>
                </div>
                <div>
                    <h3 class="text-xs font-black text-white uppercase tracking-wider mb-1">Catat Biaya Operasional</h3>
                    <p class="text-[11px] text-slate-400 font-medium leading-normal">Rekam pengeluaran harian tambak seperti pakan, obat, dan bahan bakar agar keuangan tetap terkontrol.</p>
                </div>
            </div>

            {{-- Kartu 4 --}}
            <div class="glass-card p-6 rounded-[2rem] flex flex-col justify-between h-48 transition-all hover:-translate-y-1 hover:border-purple-500/30 group">
                <div class="w-10 h-10 rounded-xl bg-purple-500/10 text-purple-400 flex items-center justify-center">
                    <i class="fa-solid fa-scale-balanced text-base"></i>
                </div>
                <div>
                    <h3 class="text-xs font-black text-white uppercase tracking-wider mb-1">Laporan Keuangan Otomatis</h3>
                    <p class="text-[11px] text-slate-400 font-medium leading-normal">Lihat ringkasan pemasukan dan pengeluaran secara otomatis, sehingga Anda tahu untung atau rugi tanpa hitung manual.</p>
                </div>
            </div>

        </div>
    </main>

    {{-- FOOTER --}}
    <footer class="w-full max-w-7xl mx-auto px-6 h-16 flex items-center justify-between border-t border-white/5 text-[10px] font-bold text-slate-500 uppercase tracking-widest">
        <span>&copy; {{ date('Y') }} AQUAFARM Hatchery Management System.</span>
        <span class="hidden sm:inline-block">All Rights Reserved.</span>
    </footer>

</body>
</html>