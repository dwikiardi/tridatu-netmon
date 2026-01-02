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
        // Modify enum to add 'need visit' status
        DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('open', 'need visit', 'on progress', 'pending', 'selesai') NOT NULL DEFAULT 'open'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum (warning: this will reset 'need visit' to 'open')
        DB::statement("UPDATE tickets SET status = 'open' WHERE status = 'need visit'");
        DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('open', 'on progress', 'pending', 'selesai') NOT NULL DEFAULT 'open'");
    }
};
