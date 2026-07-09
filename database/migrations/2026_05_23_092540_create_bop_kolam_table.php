<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bop_kolam', function (Blueprint $table) {
            $table->id();
            // Menghubungkan ke ID SIKLUS, bukan ID KOLAM, agar saat dikuras data BOP lama tidak hilang/ikut ter-reset
            $table->foreignId('siklus_id')->constrained('siklus_kolam')->onDelete('cascade');
            $table->string('keterangan_biaya'); // Contoh: Pembelian Artemia, Listrik Kincir, Vitamin Air
            $table->integer('nominal_biaya');    // Nominal rupiah pengeluaran
            $table->timestamp('waktu_pencatatan');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bop_kolam');
    }
};