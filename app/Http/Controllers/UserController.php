<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Helper Internal: Memastikan gerbang hanya bisa ditembus Admin & Pemilik
     */
    private function proteksiAksesUserKetat()
    {
        $user = Auth::user();
        
        // Proteksi Akurat: Memeriksa string properti role secara langsung untuk menghindari bug metode model
        if ($user && in_array($user->role, ['operator', 'customer'])) {
            abort(403, 'Akses Ditolak. Menu kredensial hak user dikunci ketat khusus jajaran otoritas tinggi tambak.');
        }
    }

    /**
     * Helper Internal: Proteksi mutlak tindakan manipulasi data (Hanya untuk Admin Utama)
     */
    private function wajibAdminUtama()
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'admin') {
            abort(403, 'Aksi Ditolak. Pemilik hanya diizinkan memantau daftar user, eksekusi CRUD murni hak Admin Utama.');
        }
    }

    /**
     * Helper Internal: Akun yang mendaftar sebagai customer bersifat permanen.
     * Admin tidak diizinkan mengedit profil, reset password, ubah role, atau menghapus akun customer —
     * akun customer hanya dikelola oleh pemiliknya sendiri lewat halaman profil customer.
     */
    private function tolakJikaCustomer(User $target)
    {
        if ($target->role === 'customer') {
            return back()->with('error', 'Akun customer bersifat permanen dan dilindungi. Admin tidak dapat mengedit, mereset password, mengubah role, atau menghapus akun customer.');
        }

        return null;
    }

    /**
     * Menampilkan daftar user dengan fitur Search, Filter, dan Pagination (AJAX Support).
     */
    public function index(Request $request)
    {
        $this->proteksiAksesUserKetat();

        $query = User::query();

        // Fitur Pencarian (berdasarkan Nama atau Email)
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        // Fitur Filter berdasarkan Role
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Pagination 10 data + mempertahankan query string
        $users = $query->latest()->paginate(10)->withQueryString();

        // LOGIKA AJAX: Jika request via AJAX, kirim partial table saja
        if ($request->ajax()) {
            return view('users.partials.table', compact('users'))->render();
        }

        return view('users.index', compact('users'));
    }

    /**
     * Mendaftarkan Akun Staf/User Baru
     */
    public function store(Request $request)
    {
        $this->proteksiAksesUserKetat();
        $this->wajibAdminUtama();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,operator,pemilik,customer',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return back()->with('success', 'User baru berhasil didaftarkan ke sistem keamanan tambak.');
    }

    /**
     * Memperbarui Data Profil Dasar User
     */
    public function update(Request $request, User $user)
    {
        $this->proteksiAksesUserKetat();
        $this->wajibAdminUtama();

        if ($blokir = $this->tolakJikaCustomer($user)) {
            return $blokir;
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->getKey(),
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        return back()->with('success', 'Data profil user ' . $user->name . ' berhasil diperbarui.');
    }

    /**
     * Mengubah Tingkatan Otoritas / Penugasan Role User
     */
    public function updateRole(Request $request, User $user)
    {
        $this->proteksiAksesUserKetat();
        $this->wajibAdminUtama();

        $request->validate([
            'role' => 'required|in:admin,operator,pemilik,customer',
        ]);

        // FIX SINKRONISASI: Menggunakan Auth::id() untuk membungkam warning merah VS Code
        if ($user->getKey() === Auth::id()) {
            return back()->with('error', 'Anda tidak diizinkan mendowngrade tingkat status role akun Anda sendiri.');
        }

        if ($blokir = $this->tolakJikaCustomer($user)) {
            return $blokir;
        }

        $user->update(['role' => $request->role]);

        return back()->with('success', 'Struktur hak akses tingkat role ' . $user->name . ' resmi diganti.');
    }

    /**
     * Force Bypass Reset Password oleh Admin Utama
     */
    public function resetPassword(Request $request, User $user)
    {
        $this->proteksiAksesUserKetat();
        $this->wajibAdminUtama();

        if ($blokir = $this->tolakJikaCustomer($user)) {
            return $blokir;
        }

        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Kredensial keamanan password user ' . $user->name . ' berhasil direset ulang.');
    }

    /**
     * Menghapus Akun User dari Basis Data Tambak
     */
    public function destroy(User $user)
    {
        $this->proteksiAksesUserKetat();
        $this->wajibAdminUtama();

        if ($blokir = $this->tolakJikaCustomer($user)) {
            return $blokir;
        }

        // FIX SINKRONISASI: Menggunakan Auth::id() menjamin pembacaan tanpa peringatan Intelephense
        if ($user->getKey() === Auth::id()) {
            return back()->with('error', 'Aksi ditolak. Anda dilarang menghapus akun yang sedang Anda gunakan saat ini.');
        }

        $user->delete();
        
        return back()->with('success', 'Rekam akun user berhasil dihapus dari sistem.');
    }
}