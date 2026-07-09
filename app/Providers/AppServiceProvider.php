<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
// 1. IMPORT PAGINATOR UNTUK TAMPILAN TAILWIND
use Illuminate\Pagination\Paginator;
// 2. IMPORT BLADE UNTUK MENDAFTARKAN KOMPONEN CUSTOM
use Illuminate\Support\Facades\Blade;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 3. PERINTAHKAN LARAVEL UNTUK MENGGUNAKAN TAILWIND PADA PAGINATION
        Paginator::useTailwind();

        // 4. DAFTARKAN KOMPONEN LAYOUT CUSTOMER (MOBILE-FIRST)
        // Sekarang tag <x-customer-layout> dapat dikenali sistem
        Blade::component('layouts.customer', 'customer-layout');
    }
}