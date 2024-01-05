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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_no');
            $table->integer('purchase_id');
            $table->integer('pay_amount');
            $table->timestamp('payment_date');
        });
    }

//   id integer [primary key]
//   purchase_id integer
//   user_id integer
//   payment_method_id integer
//   pay_amount integer
//   payment_date integer

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
