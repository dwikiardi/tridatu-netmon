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
        Schema::table('calon_customers', function (Blueprint $table) {
            // Add tipe_survey column to differentiate between normal and project surveys
            $table->enum('tipe_survey', ['normal', 'project'])->default('normal')->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calon_customers', function (Blueprint $table) {
            $table->dropColumn('tipe_survey');
        });
    }
};
