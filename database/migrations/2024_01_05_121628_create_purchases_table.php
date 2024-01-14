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
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_no');
            $table->integer('branch_id');
            $table->integer('supplier_id');
            $table->integer('payment_id')->nullable();
            $table->integer('total_quantity');
            $table->integer('amount');
            $table->integer('total_amount');
            $table->decimal('tax_percentage', 5, 2)->nullable();
            $table->integer('tax_amount');
            $table->decimal('discount_percentage', 5, 2)->nullable();
            $table->integer('discount_amount');
            $table->integer('pay_amount')->default(0);
            $table->integer('remain_amount');
            $table->enum('payment_status', ['PARTIALLY_PAID', 'FULLY_PAID', 'UN_PAID']);
            $table->string('remark');
            $table->timestamp('purchase_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
