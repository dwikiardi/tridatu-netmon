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
        Schema::table('snmp_data', function (Blueprint $table) {
            // Change column types to dateTime and make them nullable
            $table->dateTime('lastonline')->nullable()->change();
            $table->dateTime('lastoffline')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('snmp_data', function (Blueprint $table) {
            // Revert back to time (note: data loss might occur converting datetime to time)
            $table->time('lastonline')->change();
            $table->time('lastoffline')->change();
        });
    }
};
