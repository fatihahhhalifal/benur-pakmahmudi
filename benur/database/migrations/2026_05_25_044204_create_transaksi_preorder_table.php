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
        // 1. TABEL UTAMA PESANAN / INVOICE
        Schema::create('pesanan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // ID Pelanggan / Customer
            $table->string('nomor_invoice')->unique(); // Contoh: INV-20260524-001
            
            // Alur State Transaksi Preorder Tambak
            $table->enum('status', ['pending', 'proses', 'siap_ambil', 'selesai', 'batal'])->default('pending');
            
            // Sistem Penguncian Harga via DP (Down Payment) Kontrak
            $table->integer('nominal_dp_dibayar')->default(0);
            $table->boolean('is_harga_dikunci')->default(false); // True jika sudah DP, aman dari fluktuasi DOC
            
            // Keuangan Final Nota
            $table->bigInteger('total_pembayaran_final')->default(0); // Nominal BERSIH setelah diskon bulat (Contoh: 10.200.000)
            $table->string('bukti_transfer_dp')->nullable();
            $table->string('bukti_transfer_pelunasan')->nullable();
            $table->text('catatan_internal_admin')->nullable();
            $table->timestamps();

            // Relasi ke tabel users
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // 2. TABEL DETAIL PESANAN (AKOMODASI MATEMATIKA SAK & KANTONG)
        Schema::create('detail_pesanan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pesanan_id');
            $table->unsignedBigInteger('siklus_id'); // Mengikat ke hulu produksi (Kolam Aktif)
            
            // VARIABEL LOGISTIK FISIK (Sesuai Nota Lapangan Anda)
            $table->integer('kapasitas_kantong_per_sak')->default(45); // Standar 45 kantong dalam 1 sak
            $table->integer('jumlah_sak_dipesan')->default(0); // Contoh: 29 sak
            $table->integer('kantong_eceran_dipesan')->default(0); // Contoh: 24 kantong
            $table->integer('total_kantong_hitung')->default(0); // Hasil rumus otomatis internal (Contoh: 1329)
            $table->integer('total_kantong_riil_muat')->default(0); // Koreksi fisik lapangan saat muat/sortasi (Contoh: 1327)
            $table->integer('konversi_per_kantong')->default(0); // Nilai isi kerapatan benur (Contoh: 1550 atau 1700)
            
            // VARIABEL KEUANGAN NOTA
            $table->integer('harga_per_ekor_kontrak'); // Harga acuan awal saat booking/checkout
            $table->integer('harga_per_ekor_aktual'); // Harga riil berbasis DOC saat jemput (jika harga tidak dikunci DP)
            $table->bigInteger('subtotal_kotor')->default(0); // Hasil murni (Total Kantong Riil x Konversi x Harga Aktual)
            $table->integer('diskon_pembulatan_manual')->default(0); // Contoh: Rp 84.250 untuk membulatkan harga
            $table->timestamps();

            // Relasi Integritas Data
            $table->foreign('pesanan_id')->references('id')->on('pesanan')->onDelete('cascade');
            // Catatan: Relasi ke siklus menggunakan foreign jika tabel siklus/bop_kolam sudah menggunakan bigint id
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_pesanan');
        Schema::dropIfExists('pesanan');
    }
};