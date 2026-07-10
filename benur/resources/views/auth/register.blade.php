<x-guest-layout>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        /* 1. Reset Background & Hilangkan kartu putih bawaan Breeze */
        html,
        body,
        .min-h-screen {
            background-color: #0B111E !important;
            background-image:
                radial-gradient(at 0% 0%, rgba(30, 64, 175, 0.4) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(6, 182, 212, 0.15) 0px, transparent 50%) !important;
            font-family: 'Inter', -apple-system, sans-serif !important;
        }

        /* Mematikan background putih dan shadow dari wrapper guest layout */
        .min-h-screen>div {
            background-color: transparent !important;
            box-shadow: none !important;
            border: none !important;
        }

        /* 2. Kartu Glassmorphism Premium */
        .glass-register-card {
            background: rgba(22, 30, 49, 0.85) !important;
            backdrop-filter: blur(25px) !important;
            -webkit-backdrop-filter: blur(25px) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-radius: 2.5rem !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6) !important;
            color: white !important;
        }

        /* 3. Style Input Kontras Tinggi */
        .glass-input {
            background-color: rgba(10, 15, 30, 0.95) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            color: #FFFFFF !important;
            border-radius: 1.25rem !important;
            transition: all 0.3s ease !important;
            font-weight: 600 !important;
        }

        .glass-input:focus {
            background-color: #0f172a !important;
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.25) !important;
            outline: none !important;
        }

        .glass-input::placeholder {
            color: #475569 !important;
            font-weight: 400 !important;
        }

        .glow-shrimp {
            filter: drop-shadow(0 0 8px rgba(59, 130, 246, 0.5));
        }
    </style>

    <div class="w-full sm:max-w-md mx-auto px-10 py-10 glass-register-card relative my-6">

        {{-- BRANDING IDENTITY LOGO --}}
        <div class="flex flex-col items-center justify-center mb-6 text-center select-none">
            <div
                class="w-14 h-14 bg-gradient-to-tr from-blue-600 to-cyan-500 rounded-2xl flex items-center justify-center shadow-2xl shadow-blue-500/40 mb-3 glow-shrimp">
                <i class="fa-solid fa-shrimp text-2xl text-white"></i>
            </div>
            <h2 class="text-xl font-black tracking-tight leading-none uppercase italic text-white">
                AQUA<span class="text-blue-400 font-normal">FARM</span>
            </h2>
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] mt-2">Registrasi Akun Kemitraan
                Baru</p>
        </div>

        <form method="POST" action="{{ route('register') }}" autocomplete="off">
            @csrf

            <div class="space-y-1.5">
                <label for="name" class="text-[10px] font-black text-slate-300 uppercase tracking-widest ml-1">Nama
                    Lengkap</label>
                <div class="relative">
                    <span class="absolute left-5 top-1/2 -translate-y-1/2 text-slate-500 text-xs">
                        <i class="fa-solid fa-user"></i>
                    </span>
                    <input id="name" type="text" name="name" :value="old('name')" required autofocus
                        autocomplete="name" class="w-full glass-input pl-12 pr-4 py-3.5 text-xs focus:outline-none"
                        placeholder="Masukkan nama lengkap Anda">
                </div>
                <x-input-error :messages="$errors->get('name')" class="mt-1 text-[11px] font-bold text-rose-400 ml-1" />
            </div>

            <div class="mt-4 space-y-1.5">
                <label for="email" class="text-[10px] font-black text-slate-300 uppercase tracking-widest ml-1">Email
                    Kredensial</label>
                <div class="relative">
                    <span class="absolute left-5 top-1/2 -translate-y-1/2 text-slate-500 text-xs">
                        <i class="fa-solid fa-envelope"></i>
                    </span>
                    <input id="email" type="email" name="email" :value="old('email')" required
                        autocomplete="username" class="w-full glass-input pl-12 pr-4 py-3.5 text-xs focus:outline-none"
                        placeholder="nama@email.com">
                </div>
                <x-input-error :messages="$errors->get('email')" class="mt-1 text-[11px] font-bold text-rose-400 ml-1" />
            </div>

            <div class="mt-4 space-y-1.5" x-data="{ showPass: false }">
                <label for="password" class="text-[10px] font-black text-slate-300 uppercase tracking-widest ml-1">Kata
                    Sandi</label>
                <div class="relative">
                    <span class="absolute left-5 top-1/2 -translate-y-1/2 text-slate-500 text-xs">
                        <i class="fa-solid fa-lock"></i>
                    </span>
                    <input id="password" :type="showPass ? 'text' : 'password'" name="password" required
                        autocomplete="new-password"
                        class="w-full glass-input pl-12 pr-14 py-3.5 text-xs focus:outline-none" placeholder="••••••••">

                    <button type="button" @click="showPass = !showPass"
                        class="absolute right-5 top-1/2 -translate-y-1/2 text-slate-500 hover:text-blue-400 transition-colors p-1">
                        <i class="fa-solid" :class="showPass ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-1 text-[11px] font-bold text-rose-400 ml-1" />
            </div>

            <div class="mt-4 space-y-1.5" x-data="{ showConfirmPass: false }">
                <label for="password_confirmation"
                    class="text-[10px] font-black text-slate-300 uppercase tracking-widest ml-1">Konfirmasi Kata
                    Sandi</label>
                <div class="relative">
                    <span class="absolute left-5 top-1/2 -translate-y-1/2 text-slate-500 text-xs">
                        <i class="fa-solid fa-shield-check"></i>
                    </span>
                    <input id="password_confirmation" :type="showConfirmPass ? 'text' : 'password'"
                        name="password_confirmation" required autocomplete="new-password"
                        class="w-full glass-input pl-12 pr-14 py-3.5 text-xs focus:outline-none" placeholder="••••••••">

                    <button type="button" @click="showConfirmPass = !showConfirmPass"
                        class="absolute right-5 top-1/2 -translate-y-1/2 text-slate-500 hover:text-blue-400 transition-colors p-1">
                        <i class="fa-solid" :class="showConfirmPass ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                </div>
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1 text-[11px] font-bold text-rose-400 ml-1" />
            </div>

            {{-- ACTION REGISTER BUTTON --}}
            <div class="mt-6">
                <button type="submit"
                    class="w-full py-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-2xl text-xs font-black uppercase tracking-[0.2em] shadow-xl shadow-blue-900/50 active:scale-[0.98] transition-all flex items-center justify-center gap-3">
                    <i class="fa-solid fa-user-plus text-xs"></i> DAFTAR SEKARANG
                </button>
            </div>

            {{-- REDIRECT BACK TO LOGIN LINK --}}
            <div class="mt-6 text-center border-t border-white/10 pt-4">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">
                    Sudah memiliki akun?
                    <a href="{{ route('login') }}"
                        class="text-blue-400 font-black hover:text-blue-300 transition-colors ml-1 uppercase tracking-widest border-b border-blue-400/30 pb-0.5">
                        Masuk Aplikasi
                    </a>
                </p>
            </div>
        </form>
    </div>
</x-guest-layout>
