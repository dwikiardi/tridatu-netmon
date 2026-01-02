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
            $table->string('status')->default('prospek')->after('sales_id');
            $table->string('converted_to_cid')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calon_customers', function (Blueprint $table) {
            $table->dropColumn(['status', 'converted_to_cid']);
        });
    }
};
