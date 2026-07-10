<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pesanan', function (Blueprint $table) {
            $table->timestamp('waktu_kunci_dp')->nullable()->after('nominal_dp_dibayar');
            $table->timestamp('waktu_pelunasan_final')->nullable()->after('total_pembayaran_final');
        });
    }

    public function down(): void
    {
        Schema::table('pesanan', function (Blueprint $table) {
            $table->dropColumn(['waktu_kunci_dp', 'waktu_pelunasan_final']);
        });
    }
};