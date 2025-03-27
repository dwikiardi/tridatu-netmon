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
      Schema::create('snmp_data', function (Blueprint $table) {
        $table->id();
        $table->string('host');
        $table->integer('pon_id');
        $table->integer('onu_index');
        $table->decimal('rx_power', 8, 2);
        $table->decimal('tx_power', 8, 2);
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('snmp_data');
    }
};
