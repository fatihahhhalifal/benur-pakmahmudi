<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up() {
    Schema::create('riwayat_sampling', function (Blueprint $table) {
        $table->id();
        $table->foreignId('siklus_id')->constrained('siklus_kolam');
        $table->foreignId('grade_id')->constrained('grade_benur');
        $table->integer('jumlah_ekor');
        $table->decimal('sr_persen', 5, 2); // Survival Rate
        $table->string('keterangan')->nullable();
        $table->timestamp('tanggal_sampling');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('riwayat_sampling');
    }
};
