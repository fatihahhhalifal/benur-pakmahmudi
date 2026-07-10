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
        // Hapus dulu jika sudah ada untuk menghindari bentrok struktur
        Schema::dropIfExists('master_harga');

        Schema::create('master_harga', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('jenis_id');
            $table->unsignedBigInteger('ukuran_id');
            $table->unsignedBigInteger('grade_id'); // Menggantikan string grade lama menjadi relasi ID
            $table->integer('harga_jual'); // Menggunakan integer agar ringkas sesuai rancangan Tab 3
            $table->timestamps();

            // Deklarasi Foreign Key Baru Menuju Tabel Tanpa Awalan m_
            $table->foreign('jenis_id')->references('id')->on('jenis_benur')->onDelete('cascade');
            $table->foreign('ukuran_id')->references('id')->on('ukuran_benur')->onDelete('cascade');
            $table->foreign('grade_id')->references('id')->on('grade_benur')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_harga');
    }
};