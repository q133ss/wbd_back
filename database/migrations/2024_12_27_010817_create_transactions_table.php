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
            $table->decimal('amount', 10, 2);
            $table->enum('transaction_type', ['deposit', 'withdraw']);
            $table->enum('currency_type', ['cash', 'buyback']);
            $table->string('description');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('ads_id')->nullable()->constrained('ads')->onDelete('cascade');
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');

            $table->string('transaction_id')->unique()->nullable(); // ID транзакции на стороне платежной системы
            $table->timestamp('date_time')->nullable(); // 2025-07-01 14:51:38
            $table->ipAddress('ip_address')->nullable(); // 45.130.213.28
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
