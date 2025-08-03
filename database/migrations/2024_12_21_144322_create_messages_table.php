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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->text('text')->nullable();
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('buyback_id')->constrained('buybacks')->onDelete('cascade');
            $table->enum('type', ['text', 'image', 'system', 'comment'])->default('text');
            $table->enum('system_type',
                ['cancel', 'send_photo', 'review', 'confirm', 'completed', 'success', 'error', 'info']
                // Заказ отменен, покупатель отправил фото, покупатель оставил отзыв, продавец подтвердил, Успех (зеленый фон), Ошибка (красный фон), информация (синий фон)
            )->nullable();
            $table->boolean('is_read')->default(false);
            $table->string('color')->default('#7F56D9')->comment('Цвет сообщения');
            $table->enum('hide_for', ['seller', 'user'])->nullable()->comment('Скрыть для селлера или юзера');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
