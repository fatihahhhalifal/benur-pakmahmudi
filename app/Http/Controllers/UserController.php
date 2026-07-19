<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    private function proteksiAksesUserKetat()
    {
        $user = Auth::user();
        
        if ($user && in_array($user->role, ['operator', 'customer'])) {
            abort(403, 'Akses Ditolak. Menu kredensial hak user dikunci ketat khusus jajaran otoritas tinggi tambak.');
        }
    }

    private function wajibAdminUtama()
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'admin') {
            abort(403, 'Aksi Ditolak. Pemilik hanya diizinkan memantau daftar user, eksekusi CRUD murni hak Admin Utama.');
        }
    }

    private function tolakJikaCustomer(User $target)
    {
        if ($target->role === 'customer') {
            return back()->with('error', 'Akun customer bersifat permanen dan dilindungi. Admin tidak dapat mengedit, mereset password, mengubah role, atau menghapus akun customer.');
        }

        return null;
    }

    public function index(Request $request)
    {
        $this->proteksiAksesUserKetat();

        $query = User::query();

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->latest()->paginate(10)->withQueryString();

        if ($request->ajax()) {
            return view('users.partials.table', compact('users'))->render();
        }

        return view('users.index', compact('users'));
    }

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

    public function updateRole(Request $request, User $user)
    {
        $this->proteksiAksesUserKetat();
        $this->wajibAdminUtama();

        $request->validate([
            'role' => 'required|in:admin,operator,pemilik,customer',
        ]);

        if ($user->getKey() === Auth::id()) {
            return back()->with('error', 'Anda tidak diizinkan mendowngrade tingkat status role akun Anda sendiri.');
        }

        if ($blokir = $this->tolakJikaCustomer($user)) {
            return $blokir;
        }

        $user->update(['role' => $request->role]);

        return back()->with('success', 'Struktur hak akses tingkat role ' . $user->name . ' resmi diganti.');
    }

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

    public function destroy(User $user)
    {
        $this->proteksiAksesUserKetat();
        $this->wajibAdminUtama();

        if ($blokir = $this->tolakJikaCustomer($user)) {
            return $blokir;
        }

        if ($user->getKey() === Auth::id()) {
            return back()->with('error', 'Aksi ditolak. Anda dilarang menghapus akun yang sedang Anda gunakan saat ini.');
        }

        $user->delete();
        
        return back()->with('success', 'Rekam akun user berhasil dihapus dari sistem.');
    }
}