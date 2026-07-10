<x-app-layout>
    {{-- Container Utama --}}
    <div class="max-w-7xl mx-auto" x-data="{
        search: '',
        role: '',
        isLoading: false,
    
        init() {
            this.$watch('search', value => this.fetchData());
            this.$watch('role', value => this.fetchData());
        },
    
        fetchData() {
            this.isLoading = true;
            let params = new URLSearchParams({
                search: this.search,
                role: this.role
            });
    
            fetch(`{{ route('users.index') }}?${params.toString()}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => res.text())
                .then(html => {
                    document.getElementById('ajax-content').innerHTML = html;
                    this.isLoading = false;
                    window.history.pushState(null, '', `{{ route('users.index') }}?${params.toString()}`);
                })
                .catch(err => {
                    console.error(err);
                    this.isLoading = false;
                });
        }
    }">
        {{-- HEADER SECTION --}}
        <div class="flex flex-col md:flex-row md:items-end justify-between mb-6 gap-4" x-data="{ openModal: {{ $errors->any() ? 'true' : 'false' }} }">
            <div>
                <nav
                    class="flex items-center gap-2 text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">
                    <span>AQUAFARM</span>
                    <i class="fa-solid fa-chevron-right text-[8px]"></i>
                    <span class="text-blue-600">Manajemen User</span>
                </nav>
                <h1 class="text-2xl md:text-3xl font-black text-slate-900 tracking-tight leading-none">Manajemen User
                </h1>
                <p class="text-xs md:text-sm text-slate-500 mt-2 font-medium">Monitoring kredensial dan hak akses staf
                    secara realtime.</p>
            </div>

            {{-- TOMBOL REGISTRASI: Hanya Muncul untuk Admin Utama --}}
            @if (Auth::user()->isAdmin())
                <button @click="openModal = true"
                    class="w-full md:w-auto bg-blue-600 hover:bg-blue-700 px-6 py-3.5 rounded-2xl text-[11px] font-black text-white shadow-xl shadow-blue-600/20 active:scale-95 transition-all uppercase tracking-widest flex items-center justify-center gap-2">
                    <i class="fa-solid fa-user-plus text-xs"></i> Registrasi User
                </button>

                {{-- MODAL REGISTRASI --}}
                <div x-show="openModal"
                    class="fixed inset-0 z-[150] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md"
                    x-cloak x-transition>
                    <div @click.away="openModal = false"
                        class="bg-white w-full max-w-md rounded-[2.5rem] p-10 shadow-2xl">
                        <h2 class="text-xl font-black text-slate-800 mb-6">Registrasi User Baru</h2>

                        @if ($errors->any())
                            <div class="mb-5 p-3.5 rounded-2xl bg-rose-50 border border-rose-100 text-rose-600 text-[11px] font-bold space-y-1">
                                <div class="flex items-center gap-2 mb-1 text-rose-500">
                                    <i class="fa-solid fa-circle-exclamation"></i>
                                    <span class="uppercase tracking-widest">Registrasi gagal:</span>
                                </div>
                                <ul class="list-disc list-inside space-y-0.5 pl-1">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form action="{{ route('users.store') }}" method="POST" class="space-y-5" autocomplete="off">
                            @csrf
                            <div>
                                <label
                                    class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">Nama</label>
                                <input type="text" name="name" value="{{ old('name') }}" required autocomplete="none"
                                    class="w-full mt-2 bg-slate-50 border-slate-100 rounded-2xl px-5 py-3.5 text-sm focus:ring-2 focus:ring-blue-500 font-semibold transition-all"
                                    placeholder="Masukkan nama lengkap">
                            </div>
                            <div>
                                <label
                                    class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">Email</label>
                                <input type="email" name="email" value="{{ old('email') }}" required autocomplete="off"
                                    class="w-full mt-2 bg-slate-50 border-slate-100 rounded-2xl px-5 py-3.5 text-sm focus:ring-2 focus:ring-blue-500 font-semibold transition-all"
                                    placeholder="nama@email.com">
                            </div>
                            <div x-data="{ show: false }">
                                <label
                                    class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">Password</label>
                                <div class="relative mt-2">
                                    <input :type="show ? 'text' : 'password'" name="password" required
                                        autocomplete="new-password"
                                        class="w-full bg-slate-50 border-slate-100 rounded-2xl px-5 py-3.5 text-sm focus:ring-2 focus:ring-blue-500 transition-all"
                                        placeholder="••••••••">
                                    <button type="button" @click="show = !show"
                                        class="absolute right-5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-blue-600 transition-colors">
                                        <i class="fa-solid" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">Role
                                    Akses</label>
                                <select name="role"
                                    class="w-full mt-2 bg-slate-50 border-slate-100 rounded-2xl px-5 py-3.5 text-sm focus:ring-2 focus:ring-blue-500 font-bold transition-all">
                                    <option value="customer" {{ old('role') == 'customer' ? 'selected' : '' }}>Customer</option>
                                    <option value="operator" {{ old('role') == 'operator' ? 'selected' : '' }}>Operator Tambak</option>
                                    <option value="pemilik" {{ old('role') == 'pemilik' ? 'selected' : '' }}>Pemilik Tambak</option>
                                    <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin System</option>
                                </select>
                            </div>
                            <div class="flex gap-3 pt-4">
                                <button type="button" @click="openModal = false"
                                    class="flex-1 py-4 bg-slate-100 text-slate-500 rounded-2xl font-bold text-xs uppercase tracking-widest">Batal</button>
                                <button type="submit"
                                    class="flex-1 py-4 bg-blue-600 text-white rounded-2xl font-bold text-xs uppercase tracking-widest shadow-lg shadow-blue-600/20 transition-all hover:bg-blue-700">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </div>

        @if (session('success'))
            <div
                class="mb-6 p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 rounded-2xl text-sm font-bold shadow-sm">
                <i class="fa-solid fa-circle-check mr-2"></i> {{ session('success') }}
            </div>
        @endif

        {{-- MAIN TABLE CARD --}}
        <div
            class="bg-white rounded-[2.5rem] shadow-2xl shadow-slate-200/60 border border-slate-100 overflow-hidden relative">

            {{-- LOADING OVERLAY --}}
            <div x-show="isLoading"
                class="absolute inset-0 bg-white/60 backdrop-blur-[2px] z-[10] flex items-center justify-center"
                x-transition x-cloak>
                <div class="flex flex-col items-center gap-3">
                    <i class="fa-solid fa-circle-notch fa-spin text-blue-600 text-3xl"></i>
                    <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Sinkronisasi
                        Data...</span>
                </div>
            </div>

            {{-- FILTER BAR --}}
            <div class="px-6 md:px-8 py-6 bg-slate-50/50 border-b border-slate-100">
                <div class="flex flex-col lg:flex-row gap-4">
                    <div class="relative flex-1 group">
                        <i class="fa-solid fa-magnifying-glass absolute left-5 top-1/2 -translate-y-1/2 text-slate-400 transition-colors"
                            :class="search.length > 0 ? 'text-blue-600' : ''"></i>
                        <input type="text" x-model.debounce.300ms="search"
                            placeholder="Ketik nama atau email untuk mencari..."
                            class="w-full pl-12 pr-5 py-3.5 bg-white border-slate-200 rounded-xl md:rounded-2xl text-sm focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-medium placeholder:text-slate-300">
                    </div>
                    <div class="w-full lg:w-64">
                        <select x-model="role"
                            class="w-full py-3.5 bg-white border-slate-200 rounded-xl md:rounded-2xl text-sm font-extrabold text-slate-600 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all cursor-pointer shadow-sm">
                            <option value="">Semua Level Akses</option>
                            <option value="admin">Admin</option>
                            <option value="pemilik">Pemilik</option>
                            <option value="operator">Operator</option>
                            <option value="customer">Customer</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- AJAX CONTENT AREA --}}
            <div id="ajax-content">
                @include('users.partials.table')
            </div>
        </div>
    </div>

    {{-- SCRIPT UNTUK PAGINATION TANPA REFRESH --}}
    <script>
        document.addEventListener('click', function(e) {
            if (e.target.closest('.ajax-pagination a')) {
                e.preventDefault();
                let url = e.target.closest('.ajax-pagination a').href;
                const alpineElement = document.querySelector('[x-data]');
                const alpineData = Alpine.$data(alpineElement);
                alpineData.isLoading = true;

                fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(res => res.text())
                    .then(html => {
                        document.getElementById('ajax-content').innerHTML = html;
                        alpineData.isLoading = false;
                        window.history.pushState(null, '', url);
                    });
            }
        });
    </script>

    <style>
        [x-cloak] {
            display: none !important;
        }

        .custom-scrollbar::-webkit-scrollbar {
            height: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
    </style>
</x-app-layout>