<x-guest-layout>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        html, body, .min-h-screen {
            background-color: #0B111E !important;
            background-image:
                radial-gradient(at 0% 0%, rgba(30, 64, 175, 0.4) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(6, 182, 212, 0.15) 0px, transparent 50%) !important;
            font-family: 'Inter', -apple-system, sans-serif !important;
        }
        .min-h-screen>div {
            background-color: transparent !important;
            box-shadow: none !important;
            border: none !important;
        }
        .glass-login-card {
            background: rgba(22, 30, 49, 0.85) !important;
            backdrop-filter: blur(25px) !important;
            -webkit-backdrop-filter: blur(25px) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-radius: 2.5rem !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6) !important;
            color: white !important;
        }
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

    <div class="w-full sm:max-w-md mx-auto px-10 py-12 glass-login-card relative">

        {{-- Logo & Judul --}}
        <div class="flex flex-col items-center justify-center mb-10 text-center select-none">
            <div class="w-16 h-16 bg-gradient-to-tr from-blue-600 to-cyan-500 rounded-3xl flex items-center justify-center shadow-2xl shadow-blue-500/40 mb-4 glow-shrimp">
                <i class="fa-solid fa-shrimp text-3xl text-white"></i>
            </div>
            <h2 class="text-2xl font-black tracking-tight leading-none uppercase italic text-white">
                AQUA<span class="text-blue-400 font-normal">FARM</span>
            </h2>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mt-3">Masuk ke Akun Anda</p>
        </div>

        <x-auth-session-status class="mb-6 text-xs font-bold text-emerald-400 text-center" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}" autocomplete="off">
            @csrf

            {{-- Email --}}
            <div class="space-y-2">
                <label for="email" class="text-[11px] font-black text-slate-300 uppercase tracking-widest ml-1">Alamat Email</label>
                <div class="relative">
                    <span class="absolute left-5 top-1/2 -translate-y-1/2 text-slate-500 text-sm">
                        <i class="fa-solid fa-envelope"></i>
                    </span>
                    <input id="email" type="email" name="email" :value="old('email')" required autofocus
                        class="w-full glass-input pl-12 pr-4 py-4 text-sm focus:outline-none"
                        placeholder="contoh@email.com">
                </div>
                <x-input-error :messages="$errors->get('email')" class="mt-1 text-xs font-bold text-rose-400 ml-1" />
            </div>

            {{-- Password --}}
            <div class="mt-6 space-y-2" x-data="{ showPass: false }">
                <div class="flex items-center justify-between px-1">
                    <label for="password" class="text-[11px] font-black text-slate-300 uppercase tracking-widest">Kata Sandi</label>
                    @if (Route::has('password.request'))
                        <a class="text-[10px] font-black uppercase text-blue-400 hover:text-blue-300 transition-colors"
                            href="{{ route('password.request') }}">
                            Lupa Kata Sandi?
                        </a>
                    @endif
                </div>
                <div class="relative">
                    <span class="absolute left-5 top-1/2 -translate-y-1/2 text-slate-500 text-sm">
                        <i class="fa-solid fa-lock"></i>
                    </span>
                    <input id="password" :type="showPass ? 'text' : 'password'" name="password" required
                        class="w-full glass-input pl-12 pr-14 py-4 text-sm focus:outline-none" placeholder="••••••••">
                    <button type="button" @click="showPass = !showPass"
                        class="absolute right-5 top-1/2 -translate-y-1/2 text-slate-500 hover:text-blue-400 transition-colors p-1">
                        <i class="fa-solid" :class="showPass ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-1 text-xs font-bold text-rose-400 ml-1" />
            </div>

            {{-- Ingat Saya --}}
            <div class="flex items-center justify-between mt-6 px-1 select-none">
                <label for="remember_me" class="inline-flex items-center cursor-pointer group">
                    <input id="remember_me" type="checkbox" name="remember"
                        class="rounded bg-slate-900 border-slate-700 text-blue-600 shadow-sm focus:ring-0 focus:ring-offset-0 w-4 h-4 transition-colors cursor-pointer group-hover:border-slate-500">
                    <span class="ms-2 text-[10px] font-black text-slate-400 uppercase tracking-wider group-hover:text-slate-300 transition-colors">Ingat Saya</span>
                </label>
            </div>

            {{-- Tombol Masuk --}}
            <div class="mt-8">
                <button type="submit"
                    class="w-full py-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-2xl text-xs font-black uppercase tracking-[0.2em] shadow-xl shadow-blue-900/50 active:scale-[0.98] transition-all flex items-center justify-center gap-3">
                    <i class="fa-solid fa-right-to-bracket text-sm"></i> Masuk
                </button>
            </div>

            {{-- Link Daftar --}}
            <div class="mt-8 text-center border-t border-white/10 pt-6">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">
                    Belum punya akun?
                    <a href="{{ route('register') }}"
                        class="text-blue-400 font-black hover:text-blue-300 transition-colors ml-1 uppercase tracking-widest border-b border-blue-400/30 pb-0.5">
                        Daftar Sekarang
                    </a>
                </p>
            </div>
        </form>
    </div>
</x-guest-layout>