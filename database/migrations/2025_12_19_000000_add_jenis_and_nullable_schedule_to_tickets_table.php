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
            $table->enum('jenis', ['maintenance', 'komplain', 'survey', 'installasi'])
                ->default('maintenance')
                ->after('cid');
            $table->enum('metode_penanganan', ['onsite', 'remote'])
                ->nullable()
                ->after('jenis');

            $table->date('tanggal_kunjungan')->nullable()->change();
            $table->time('jam')->nullable()->change();
            $table->string('hari')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['jenis', 'metode_penanganan']);

            $table->date('tanggal_kunjungan')->nullable(false)->change();
            $table->time('jam')->nullable(false)->change();
            $table->string('hari')->nullable(false)->change();
        });
    }
};
