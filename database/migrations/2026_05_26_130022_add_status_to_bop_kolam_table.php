<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bop_kolam', function (Blueprint $table) {
            // Menambahkan kolom status dengan default 'disetujui' agar data simulasi sebelumnya tidak rusak
            $table->enum('status', ['pending', 'disetujui'])->default('disetujui')->after('nominal_biaya');
        });
    }

    public function down(): void
    {
        Schema::table('bop_kolam', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};