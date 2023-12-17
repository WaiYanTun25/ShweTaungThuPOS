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
        Schema::create('unit_convert_details', function (Blueprint $table) {
            $table->id();
            $table->integer('unit_convert_id');
            $table->integer('from_unit_id');
            $table->integer('from_qty');
            $table->integer('to_unit_id');
            $table->integer('to_qty');
            // $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit_convert_details');
    }
};
