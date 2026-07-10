<div id="user-table-container">
    <div class="overflow-x-auto custom-scrollbar">
        <table class="w-full text-left border-collapse min-w-[800px]">
            <thead>
                <tr
                    class="text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-50 bg-white">
                    <th class="px-6 md:px-10 py-6">Profil Pengguna</th>
                    <th class="px-6 py-6 text-center">Status</th>
                    <th class="px-6 py-6 text-center">Hak Akses</th>
                    <th class="px-6 md:px-10 py-6 text-right">Tindakan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse ($users as $user)
                    <tr class="hover:bg-slate-50/50 transition-all duration-300 group" x-data="{ editModal: false, resetModal: false }">
                        <td class="px-6 md:px-10 py-6 md:py-7">
                            <div class="flex items-center gap-4 md:gap-5">
                                <div
                                    class="w-10 h-10 md:w-12 md:h-12 rounded-xl md:rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400 shadow-inner group-hover:bg-blue-600 group-hover:text-white transition-all duration-500">
                                    <i class="fa-solid fa-user text-lg md:text-xl"></i>
                                </div>
                                <div class="max-w-[150px] md:max-w-none">
                                    <div class="flex items-center gap-2 md:gap-3">
                                        <p class="text-sm font-extrabold text-slate-800 truncate">{{ $user->name }}
                                        </p>
                                        @if ($user->id === auth()->id())
                                            <span
                                                class="text-[8px] bg-blue-600 text-white px-2 py-0.5 rounded-lg font-black uppercase tracking-tighter shadow-md">Anda</span>
                                        @endif
                                    </div>
                                    <p class="text-[10px] md:text-[11px] text-slate-400 mt-1 font-semibold truncate">
                                        {{ $user->email }}</p>
                                </div>
                            </div>
                        </td>

                        {{-- STATUS ONLINE / OFFLINE --}}
                        <td class="px-6 py-7 text-center">
                            @if ($user->isOnline())
                                <div class="flex items-center justify-center gap-2">
                                    <span class="relative flex h-2 w-2">
                                        <span
                                            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                    </span>
                                    <span
                                        class="text-[10px] font-black text-emerald-600 uppercase tracking-widest">Online</span>
                                </div>
                            @else
                                <span
                                    class="text-[10px] font-black text-slate-300 uppercase tracking-widest italic">Offline</span>
                            @endif
                        </td>

                        {{-- SELEKSI LEVEL HAK AKSES ROLE --}}
                        <td class="px-6 py-7 text-center">
                            @if (Auth::user()->isAdmin() && $user->role !== 'customer')
                                {{-- Jika Admin & bukan akun customer: Render Interaksi Form Mengubah Role secara Live --}}
                                <form action="{{ route('users.updateRole', $user->id) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <select name="role" onchange="this.form.submit()"
                                        class="min-w-[130px] text-[10px] font-black uppercase tracking-widest rounded-xl py-2.5 px-3 border-0 transition-all cursor-pointer text-center
                                        {{ $user->role == 'admin' ? 'bg-blue-50 text-blue-700 ring-1 ring-blue-100' : '' }}
                                        {{ $user->role == 'pemilik' ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-100' : '' }}
                                        {{ $user->role == 'operator' ? 'bg-amber-50 text-amber-700 ring-1 ring-amber-100' : '' }}">
                                        <option value="operator" {{ $user->role == 'operator' ? 'selected' : '' }}>
                                            Operator</option>
                                        <option value="pemilik" {{ $user->role == 'pemilik' ? 'selected' : '' }}>Pemilik
                                        </option>
                                        <option value="admin" {{ $user->role == 'admin' ? 'selected' : '' }}>Admin
                                        </option>
                                    </select>
                                </form>
                            @else
                                {{-- Akun Customer (permanen, tidak bisa diubah Admin) ATAU dilihat oleh Pemilik: Badge Read-Only --}}
                                <div
                                    class="min-w-[130px] inline-block text-[10px] font-black uppercase tracking-widest rounded-xl py-2.5 px-4 text-center border-none
                                    {{ $user->role == 'admin' ? 'bg-blue-50 text-blue-700 ring-1 ring-blue-100' : '' }}
                                    {{ $user->role == 'pemilik' ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-100' : '' }}
                                    {{ $user->role == 'operator' ? 'bg-amber-50 text-amber-700 ring-1 ring-amber-100' : '' }}
                                    {{ $user->role == 'customer' ? 'bg-slate-100 text-slate-600 ring-1 ring-slate-200' : '' }}">
                                    {{ $user->role == 'admin' ? 'Admin System' : ($user->role == 'pemilik' ? 'Pemilik Tambak' : ($user->role == 'operator' ? 'Operator Tambak' : 'Customer')) }}
                                </div>
                            @endif
                        </td>

                        {{-- SEKTOR KENDALI TINDAKAN --}}
                        <td class="px-6 md:px-10 py-7">
                            <div class="flex items-center justify-end gap-2 md:gap-3">
                                @if (Auth::user()->isAdmin() && $user->role !== 'customer')
                                    {{-- Panel Tombol Manajemen Modifikasi Data: Khusus Admin, dan bukan akun customer --}}
                                    <button @click="editModal = true" title="Edit Profil"
                                        class="w-9 h-9 md:w-10 md:h-10 rounded-xl bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition-all shadow-sm flex items-center justify-center">
                                        <i class="fa-solid fa-pen-to-square text-sm"></i>
                                    </button>
                                    <button @click="resetModal = true" title="Reset Keamanan Password"
                                        class="w-9 h-9 md:w-10 md:h-10 rounded-xl bg-amber-50 text-amber-600 hover:bg-amber-500 hover:text-white transition-all shadow-sm flex items-center justify-center">
                                        <i class="fa-solid fa-key text-sm"></i>
                                    </button>
                                    @if ($user->id !== auth()->id())
                                        <form action="{{ route('users.destroy', $user->id) }}" method="POST"
                                            onsubmit="return confirm('Hapus user ini?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" title="Hapus Akun"
                                                class="w-9 h-9 md:w-10 md:h-10 rounded-xl bg-rose-50 text-rose-500 hover:bg-rose-600 hover:text-white transition-all shadow-sm flex items-center justify-center">
                                                <i class="fa-solid fa-trash-can text-sm"></i>
                                            </button>
                                        </form>
                                    @endif
                                @else
                                    {{-- Tampilan untuk Pemilik Tambak, ATAU akun Customer (dilindungi permanen) --}}
                                    <span
                                        class="text-[9px] font-black text-slate-400 bg-slate-50 border px-3 py-1.5 rounded-lg uppercase tracking-wider select-none">
                                        <i class="fa-solid fa-shield text-[8px] mr-0.5 text-slate-300"></i> Terproteksi
                                    </span>
                                @endif
                            </div>

                            {{-- MODAL PREORDER POP-UP PORTAL - HANYA TERMUAT UNTUK PERAN ADMIN & BUKAN AKUN CUSTOMER --}}
                            @if (Auth::user()->isAdmin() && $user->role !== 'customer')
                                <template x-teleport="body">
                                    <div x-show="editModal || resetModal"
                                        class="fixed inset-0 z-[150] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md"
                                        x-cloak x-transition>

                                        {{-- 1. FORM EDIT DATA PROFILE --}}
                                        <div x-show="editModal" @click.away="editModal = false"
                                            class="bg-white w-full max-w-md rounded-[2rem] p-8 md:p-10 shadow-2xl">
                                            <h2 class="text-xl font-black text-slate-800 mb-6 tracking-tight">Edit Data
                                                User</h2>
                                            <form action="{{ route('users.update', $user->id) }}" method="POST"
                                                class="space-y-5 text-left">
                                                @csrf @method('PUT')
                                                <div>
                                                    <label
                                                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">Nama</label>
                                                    <input type="text" name="name" value="{{ $user->name }}"
                                                        required
                                                        class="w-full mt-2 bg-slate-50 border-slate-100 rounded-2xl px-5 py-3 text-sm focus:ring-2 focus:ring-blue-500 font-bold transition-all">
                                                </div>
                                                <div>
                                                    <label
                                                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">Email</label>
                                                    <input type="email" name="email" value="{{ $user->email }}"
                                                        required
                                                        class="w-full mt-2 bg-slate-50 border-slate-100 rounded-2xl px-5 py-3 text-sm focus:ring-2 focus:ring-blue-500 font-bold transition-all">
                                                </div>
                                                <div class="flex gap-3 pt-6">
                                                    <button type="button" @click="editModal = false"
                                                        class="flex-1 py-4 bg-slate-100 text-slate-500 rounded-2xl font-bold text-[10px] uppercase tracking-widest transition-colors hover:bg-slate-200">Batal</button>
                                                    <button type="submit"
                                                        class="flex-1 py-4 bg-blue-600 text-white rounded-2xl font-bold text-[10px] uppercase tracking-widest shadow-lg shadow-blue-600/20 transition-all hover:bg-blue-700">Update</button>
                                                </div>
                                            </form>
                                        </div>

                                        {{-- 2. FORM RESET PASSWORD FORCE BYPASS --}}
                                        <div x-show="resetModal" @click.away="resetModal = false"
                                            class="bg-white w-full max-w-md rounded-[2rem] p-8 md:p-10 shadow-2xl">
                                            <h2 class="text-xl font-black text-slate-800 mb-2">Reset Password</h2>
                                            <p class="text-[11px] text-slate-400 mb-6 font-medium italic">User:
                                                {{ $user->name }}</p>
                                            <form action="{{ route('users.resetPassword', $user->id) }}" method="POST"
                                                class="space-y-5 text-left">
                                                @csrf @method('PATCH')
                                                <div>
                                                    <label
                                                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">Password
                                                        Baru</label>
                                                    <input type="password" name="password" required
                                                        class="w-full mt-2 bg-slate-50 border-slate-100 rounded-2xl px-5 py-3 text-sm focus:ring-2 focus:ring-amber-500 font-bold transition-all">
                                                </div>
                                                <div>
                                                    <label
                                                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">Konfirmasi</label>
                                                    <input type="password" name="password_confirmation" required
                                                        class="w-full mt-2 bg-slate-50 border-slate-100 rounded-2xl px-5 py-3 text-sm focus:ring-2 focus:ring-amber-500 font-bold transition-all">
                                                </div>
                                                <div class="flex gap-3 pt-6">
                                                    <button type="button" @click="resetModal = false"
                                                        class="flex-1 py-4 bg-slate-100 text-slate-500 rounded-2xl font-bold text-[10px] uppercase tracking-widest transition-colors hover:bg-slate-200">Batal</button>
                                                    <button type="submit"
                                                        class="flex-1 py-4 bg-amber-500 text-white rounded-2xl font-bold text-[10px] uppercase tracking-widest shadow-lg shadow-amber-500/20 transition-all hover:bg-amber-600">Reset</button>
                                                </div>
                                            </form>
                                        </div>

                                    </div>
                                </template>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-8 py-32 text-center bg-slate-50/20">
                            <div class="flex flex-col items-center">
                                <i class="fa-solid fa-users-slash text-4xl text-slate-200 mb-5"></i>
                                <h3 class="text-lg font-bold text-slate-800">Tidak ada user ditemukan</h3>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($users->hasPages())
        <div class="px-6 md:px-10 py-8 bg-slate-50/50 border-t border-slate-100 ajax-pagination">
            {{ $users->links() }}
        </div>
    @endif
</div>
