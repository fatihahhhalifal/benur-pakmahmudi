<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kolom foto referensi skala ukuran (mis. PL difoto berdampingan
     * dengan penggaris/koin) pada tabel master ukuran_benur. Satu foto berlaku
     * untuk SEMUA kolam/siklus yang memakai ukuran tersebut (bukan per-siklus).
     */
    public function up(): void
    {
        Schema::table('ukuran_benur', function (Blueprint $table) {
            $table->string('foto_skala')->nullable()->after('deskripsi');
        });
    }

    public function down(): void
    {
        Schema::table('ukuran_benur', function (Blueprint $table) {
            $table->dropColumn('foto_skala');
        });
    }
};