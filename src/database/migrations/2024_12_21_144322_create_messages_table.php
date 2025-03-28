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
            $table->enum('type', ['text', 'image', 'system'])->default('text');
            $table->enum('system_type',
                ['cancel', 'send_photo', 'review', 'confirm', 'completed']
                // Заказ отменен, покупатель отправил фото, покупатель оставил отзыв, продавец подтвердил
            )->nullable();
            $table->boolean('is_read')->default(false);
            $table->string('color')->default('#7F56D9')->comment('Цвет сообщения');
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
