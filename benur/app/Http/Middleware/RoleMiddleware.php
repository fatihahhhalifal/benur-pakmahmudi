<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$roles
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // 1. Pastikan user sudah login
        if (!Auth::check()) {
            return redirect('login');
        }

        // 2. Ambil role user yang sedang login
        $userRole = Auth::user()->role;

        // 3. Periksa apakah role user ada dalam daftar role yang diizinkan
        if (in_array($userRole, $roles)) {
            return $next($request);
        }

        // 4. Jika tidak memiliki akses, lempar error 403 (Forbidden)
        abort(403, 'Maaf, Anda tidak memiliki akses untuk masuk ke halaman ini.');
    }
}