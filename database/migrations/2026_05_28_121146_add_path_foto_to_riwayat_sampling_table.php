<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('riwayat_sampling', function (Blueprint $table) {
            $table->string('path_foto')->nullable()->after('keterangan');
        });
    }

    public function down()
    {
        Schema::table('riwayat_sampling', function (Blueprint $table) {
            $table->dropColumn('path_foto');
        });
    }
};
