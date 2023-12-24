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
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_no');
            $table->integer('from_branch_id');
            $table->integer('to_branch_id');
            $table->integer('total_quantity');
            $table->enum('status', ['sent', 'received']);
            $table->timestamp('transaction_date');
            $table->timestamp('receive_date')->nullable();
            // $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};