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
        Schema::create('ads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('name');
            $table->decimal('cashback_percentage', 5, 2)->comment('Процент кешбека');
            $table->decimal('price_with_cashback', 10, 2)->comment('Цена с учетом кешбека');
            $table->text('order_conditions')->comment('Условия заказа');
            $table->text('redemption_instructions')->comment('Инструкции выкупа для покупателя');
            $table->text('review_criteria')->comment('Критерии отзыва');
            $table->unsignedInteger('redemption_count');
            $table->unsignedInteger('views_count')->default(0);
            $table->boolean('one_per_user')->default(false)->comment('Один товар для одного покупателя!');
            $table->boolean('is_archived')->default(false);
            $table->boolean('status')->default(false);
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ads');
    }
};
