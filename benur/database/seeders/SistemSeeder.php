<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SistemSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Membuat User Admin untuk Login
        DB::table('users')->insert([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('admin123'), // Password kamu nanti: admin123
            'email_verified_at' => now(),
        ]);

        // 2. Membuat Contoh Stok Benur agar Dashboard tidak kosong
        DB::table('stok_benur')->insert([
            [
                'jenis_benur' => 'Vaname',
                'ukuran' => 'PL-10',
                'grade' => 'Super',
                'total_stok' => 100000,
                'harga_jual' => 45.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'jenis_benur' => 'Windu',
                'ukuran' => 'PL-12',
                'grade' => 'A',
                'total_stok' => 50000,
                'harga_jual' => 60.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}