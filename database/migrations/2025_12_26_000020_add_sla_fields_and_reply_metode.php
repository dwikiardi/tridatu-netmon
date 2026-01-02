<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->unsignedInteger('sla_remote_minutes')->nullable()->after('status');
            $table->unsignedInteger('sla_onsite_minutes')->nullable()->after('sla_remote_minutes');
            $table->unsignedInteger('sla_total_minutes')->nullable()->after('sla_onsite_minutes');
        });

        Schema::table('ticket_replies', function (Blueprint $table) {
            $table->string('metode_penanganan')->nullable()->after('update_status');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_replies', function (Blueprint $table) {
            $table->dropColumn('metode_penanganan');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['sla_remote_minutes', 'sla_onsite_minutes', 'sla_total_minutes']);
        });
    }
};
