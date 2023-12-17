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
        Schema::create('item_unit_details', function (Blueprint $table) {
            $table->id();
            $table->integer('item_id');
            $table->integer('unit_id');
            $table->integer('rate');
            $table->integer('vip_price');
            $table->integer('retail_price');
            $table->integer('wholesale_price');
            $table->integer('reorder_level');
            $table->integer('reorder_period');
            // $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_unit_details');
    }
};
