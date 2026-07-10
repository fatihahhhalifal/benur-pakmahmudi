<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class UpdateLastSeen
{
    public function handle(Request $request, Closure $next): Response
    {
        // Pengaman mutlak: Hanya eksekusi database jika user terbukti sudah login
        if (Auth::check()) {
            DB::table('users')
                ->where('id', Auth::id())
                ->update(['last_seen' => now()]);
        }

        return $next($request);
    }
}