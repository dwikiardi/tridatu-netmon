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
        Schema::table('tickets', function (Blueprint $table) {
            // Make these columns nullable since they will be filled during update
            $table->string('priority')->nullable()->change();
            $table->string('metode_penanganan')->nullable()->change();
            $table->date('tanggal_kunjungan')->nullable()->change();
            $table->string('pic_it_lokasi')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('priority')->nullable(false)->change();
            $table->string('metode_penanganan')->nullable(false)->change();
            $table->date('tanggal_kunjungan')->nullable(false)->change();
            $table->string('pic_it_lokasi')->nullable(false)->change();
        });
    }
};
