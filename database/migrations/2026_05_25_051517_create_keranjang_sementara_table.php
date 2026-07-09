<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('keranjang_sementara', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Mengikat ke ID Pembeli (Customer)
            $table->unsignedBigInteger('kolam_id'); // Mengikat ke ID Master Fisik Kolam Hulu
            
            // Variabel Logistik Kemasan Sesuai Matematika Nota Tambak Anda
            $table->integer('jumlah_sak')->default(0); 
            $table->integer('kantong_eceran')->default(0);
            
            $table->timestamps();

            // Relasi Integritas Data Cegah Data Yatim Piatu
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('kolam_id')->references('id')->on('master_kolam')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('keranjang_sementara');
    }
};