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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('cid');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->date('tanggal_kunjungan');
            $table->string('pic_it_lokasi');
            $table->string('pic_teknisi');
            $table->time('jam');
            $table->string('hari');
            $table->text('kendala');
            $table->text('solusi')->nullable();
            $table->text('hasil')->nullable();
            $table->enum('status', ['open', 'on progress', 'pending', 'selesai'])->default('open');
            $table->timestamps();

            $table->foreign('cid')->references('cid')->on('customers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
