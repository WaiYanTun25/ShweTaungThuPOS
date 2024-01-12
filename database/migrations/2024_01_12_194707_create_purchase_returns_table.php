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
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->integer('voucher_no');
            $table->integer('purchase_id');
            $table->integer('branch_id');
            $table->integer('supplier_id');
            $table->integer('total_quantity');
            $table->integer('amount');
            $table->integer('total_amount');
            $table->decimal('tax_percentage', 5, 2)->nullable();
            $table->integer('tax_amount');
            $table->decimal('discount_percentage', 5, 2)->nullable();
            $table->integer('discount_amount');
            $table->integer('pay_amount')->default(0);
            $table->string('remark');
            $table->timestamp('purchase_return_date');
        });
    }

//     id integer [primary key]
//   purchase_id integer
//   voucher_no string
//   supplier_id integer
//   total_quantity integer
//   total_amount integer
//   pay_amount integer
//   tax_percentage integer
//   tax_amount integer
//   discount_percentage integer
//   discount_amount integer
//   remark string
//   purhcase_return_date date
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_returns');
    }
};
