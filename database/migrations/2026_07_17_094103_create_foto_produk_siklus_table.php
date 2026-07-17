<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('foto_produk_siklus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siklus_id')->constrained('siklus_kolam')->onDelete('cascade');
            $table->enum('kategori', ['skala_besar', 'skala_kecil', 'ukuran'])
                  ->comment('skala_besar=foto keseluruhan, skala_kecil=close-up benur, ukuran=foto penggaris/skala PL');
            $table->string('path_foto');
            $table->string('keterangan')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('foto_produk_siklus');
    }
};