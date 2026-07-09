<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan penanda waktu selesai per-tahap untuk tiap baris detail_pesanan (per kolam).
     * Dipakai untuk mengecek apakah SEMUA kolam dalam 1 pesanan sudah selesai diproses,
     * sebelum status pesanan (level induk) boleh naik ke tahap berikutnya.
     */
    public function up(): void
    {
        Schema::table('detail_pesanan', function (Blueprint $table) {
            $table->timestamp('waktu_timbang_muat')->nullable()->after('konversi_per_kantong_aktual');
            $table->timestamp('waktu_kalkulasi_final')->nullable()->after('diskon_pembulatan_manual');
        });
    }

    public function down(): void
    {
        Schema::table('detail_pesanan', function (Blueprint $table) {
            $table->dropColumn(['waktu_timbang_muat', 'waktu_kalkulasi_final']);
        });
    }
};
