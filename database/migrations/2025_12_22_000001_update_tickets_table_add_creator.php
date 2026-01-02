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
            // Tambah kolom untuk creator (user_id) dan jenis creator (role)
            $table->unsignedBigInteger('created_by')->nullable()->after('cid');
            $table->enum('created_by_role', ['sales', 'teknisi', 'admin'])->default('admin')->after('created_by');

            // Tambah foreign key
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\User::class, 'created_by');
            $table->dropColumn(['created_by', 'created_by_role']);
        });
    }
};
