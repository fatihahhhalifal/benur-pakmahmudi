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
        // Hapus tabel settings lama agar bersih, lalu buat tabel profil_tambak
        Schema::dropIfExists('settings');

        Schema::create('profil_tambak', function (Blueprint $table) {
            $table->id();
            $table->string('nama_tambak');
            $table->string('npwp_nib')->nullable(); // Sesuai permintaan: Bersifat Opsional
            $table->string('nomor_whatsapp');
            $table->string('email');
            $table->text('alamat');
            $table->string('nama_bank');
            $table->string('nomor_rekening');
            $table->string('atas_nama');
            $table->integer('nominal_dp')->default(30); // Menyimpan angka persen (Contoh: 30 untuk 30%)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profil_tambak');
    }
};