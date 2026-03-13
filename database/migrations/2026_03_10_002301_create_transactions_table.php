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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained();
            $table->string('client_email');
            $table->string('gateway_id')->constrained();
            $table->string('external_id')->nullable();
            $table->string('status');
            $table->integer('amount');
            $table->string('card_last_numbers');
            $table->foreignId('product_id')->constrained();
            $table->integer('quantity');
            $table->string('idempotency_hash')->unique()->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
