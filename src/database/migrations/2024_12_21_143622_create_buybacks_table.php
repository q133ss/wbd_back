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
        Schema::create('buybacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ads_id')->constrained('ads')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', [
                'cancelled',       // Отменен
                'order_expired',   // Покупатель не успел сделать заказ в установленный срок
                'pending',         // Ожидание заказа
                'awaiting_receipt', // Ожидание получения
                'on_confirmation', // Подтверждение
                'cashback_received', // Кешбек получен
                'completed',       // Завершено
                'archive',          // Архив
            ])->default('pending');
            $table->double('price');
            $table->boolean('is_archived')->default(false);
            $table->boolean('has_review_by_seller')->default(false);
            $table->boolean('has_review_by_buyer')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buybacks');
    }
};
