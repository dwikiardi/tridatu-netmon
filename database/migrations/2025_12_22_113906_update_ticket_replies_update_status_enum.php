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
        // Update enum untuk update_status dengan menambahkan 'on_progress'
        \DB::statement("ALTER TABLE `ticket_replies` MODIFY `update_status` ENUM('need_visit', 'on_progress', 'remote_done', 'done') DEFAULT 'need_visit'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback ke enum yang lama
        \DB::statement("ALTER TABLE `ticket_replies` MODIFY `update_status` ENUM('need_visit', 'remote_done', 'done') DEFAULT 'need_visit'");
    }
};
