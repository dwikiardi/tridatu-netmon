<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tambah opsi 'existing' ke enum tipe_survey di calon_customers
        DB::statement("ALTER TABLE `calon_customers` MODIFY `tipe_survey` ENUM('normal', 'project', 'existing') DEFAULT 'normal'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert ke enum lama tanpa 'existing'
        DB::statement("ALTER TABLE `calon_customers` MODIFY `tipe_survey` ENUM('normal', 'project') DEFAULT 'normal'");
    }
};
