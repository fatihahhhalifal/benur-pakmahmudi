<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jenis_benur', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique();
            $table->string('kode')->nullable(); // Sesuai kolom yang kamu minta
            $table->text('deskripsi')->nullable(); // Sesuai kolom yang kamu minta
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jenis_benur');
    }
};