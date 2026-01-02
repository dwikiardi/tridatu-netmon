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
        Schema::create('customer_logs', function (Blueprint $table) {
            $table->id();
            $table->string('customer_cid');
            $table->string('action'); // created, updated, deleted
            $table->string('field_changed')->nullable(); // field yang berubah
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->string('changed_by'); // nama user yang mengubah
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->foreign('customer_cid')->references('cid')->on('customers')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_logs');
    }
};
