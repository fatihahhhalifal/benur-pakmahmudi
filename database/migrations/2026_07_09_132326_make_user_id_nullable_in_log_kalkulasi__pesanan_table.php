<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom user_id perlu nullable karena webhook Midtrans (notificationHandler)
     * memproses pembayaran tanpa ada user yang login — jadi user_id memang harus
     * bisa null untuk baris log yang dibuat otomatis oleh sistem/webhook.
     */
    public function up(): void
    {
        Schema::table('log_kalkulasi_pesanan', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('log_kalkulasi_pesanan', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
};
