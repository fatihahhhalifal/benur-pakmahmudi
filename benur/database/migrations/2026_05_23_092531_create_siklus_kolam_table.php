<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('siklus_kolam', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kolam_id')->constrained('master_kolam')->onDelete('cascade');
            $table->foreignId('jenis_id')->constrained('jenis_benur')->onDelete('cascade');
            $table->foreignId('ukuran_id')->constrained('ukuran_benur')->onDelete('cascade'); // Target awal tebar, misal PL-5
            $table->foreignId('grade_id')->constrained('grade_benur')->onDelete('cascade'); // Di-update dinamis dari grade dominan hasil sampling
            
            // Parameter Keuangan & Volume Lapangan
            $table->integer('modal_awal_rupiah'); // Contoh: 4000000 (Modal beli telur/benih)
            $table->integer('jumlah_tebar_awal'); // Contoh: 2000000 ekor
            $table->integer('stok_tersedia');     // Berkurang dinamis saat terjual / kuras habis
            
            // Diskon Pelunasan Inputan Manual Oleh Admin (Sesuai Instruksimu)
            $table->integer('potongan_harga_manual')->default(0); // Nilai Rp potongan per ekor jika dinegosiasikan manual
            
            // Waktu Pencatatan Operasional
            $table->timestamp('waktu_tabur'); 
            $table->enum('status', ['aktif', 'selesai'])->default('aktif'); // 'selesai' artinya kolam sudah dikuras/panen habis
            $table->timestamp('waktu_kuras')->nullable(); // Terisi otomatis saat tombol kuras ditekan
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('siklus_kolam');
    }
};