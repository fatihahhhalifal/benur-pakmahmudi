<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_kalkulasi_pesanan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pesanan_id');
            $table->unsignedBigInteger('user_id'); // admin/operator yang melakukan perubahan
            $table->string('aksi'); // 'input_muat', 'kalkulasi_final', 'verifikasi_dp', 'validasi_pelunasan'
            $table->json('data_sebelum')->nullable(); // snapshot data lama
            $table->json('data_sesudah')->nullable(); // snapshot data baru
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->foreign('pesanan_id')->references('id')->on('pesanan')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_kalkulasi_pesanan');
    }
};
