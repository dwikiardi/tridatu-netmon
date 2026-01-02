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
            $table->unsignedBigInteger('calon_customer_id')->nullable()->after('cid');
            $table->foreign('calon_customer_id')->references('id')->on('calon_customers')->onDelete('set null');

            $table->string('cid')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['calon_customer_id']);
            $table->dropColumn('calon_customer_id');

            $table->string('cid')->nullable(false)->change();
        });
    }
};
