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
        Schema::create('sales_order_details', function (Blueprint $table) {
            $table->id();
            $table->integer('sale_order_id');
            $table->integer('item_id');
            $table->integer('unit_id');
            $table->integer('item_price');
            $table->integer('discount_amount');
            $table->integer('quantity');
            $table->integer('amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_order_details');
    }
};
