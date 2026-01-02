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
        Schema::create('customers', function (Blueprint $table) {
            $table->string('cid')->primary();
            $table->string('nama');
            $table->string('email');
            $table->string('sales');
            $table->string('packet');
            $table->text('alamat');
            $table->string('pic_it')->nullable();
            $table->string('pic_finance')->nullable();
            $table->string('no_it');
            $table->string('no_finance');
            $table->string('coordinate_maps');
            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
