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
        Schema::create('frozen_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Продавец
            $table->foreignId('ad_id')->constrained('ads')->onDelete('cascade'); // Продавец
            $table->decimal('amount', 8, 2); // Сумма, замороженная на аккаунте
            $table->string('reason'); // Причина заморозки (например, создание объявления)
            $table->enum('status', ['reserved', 'debited', 'returned'])->comment('Зарезервирована | Списана | Возвращаена')->default('reserved'); // Статус заморозки
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('frozen_balances');
    }
};
