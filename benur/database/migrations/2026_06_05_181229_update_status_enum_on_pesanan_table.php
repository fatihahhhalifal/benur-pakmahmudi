<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `pesanan` MODIFY COLUMN `status` ENUM(
            'pending',
            'proses',
            'menunggu_kalkulasi',
            'menunggu_pelunasan',
            'siap_ambil',
            'selesai',
            'batal'
        ) NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `pesanan` MODIFY COLUMN `status` ENUM(
            'pending',
            'proses',
            'siap_ambil',
            'selesai',
            'batal'
        ) NOT NULL DEFAULT 'pending'");
    }
};