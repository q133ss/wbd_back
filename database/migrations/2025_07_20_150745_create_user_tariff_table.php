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
        Schema::create('user_tariff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('tariff_id')->constrained('tariffs');
            $table->timestamp('end_date')->comment('Дата окончания');
            $table->unsignedBigInteger('products_count')->comment('Кол-во товаров');
            $table->boolean('status')->default(true)->comment('Статус, проверить на просрочку и тд');
            $table->string('variant_name')->comment('Название варианта (например, "3 месяца")');
            $table->integer('duration_days')->comment('Кол-во дней (например, 90)');
            $table->decimal('price_paid', 10, 2)->comment('Цена, которую реально заплатил. На всякий случай!');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_tariff');
    }
};
