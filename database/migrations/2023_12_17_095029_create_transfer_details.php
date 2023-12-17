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
        Schema::create('transfer_details', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_no');
            $table->integer('item_id');
            $table->integer('unit_id');
            $table->integer('quantity');
            $table->text('remark');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer_details');
    }
};
