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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->string('sbp')->nullable();
            $table->string('sbp_comment')->nullable();
            $table->string('sber')->nullable();
            $table->string('tbank')->nullable();
            $table->string('ozon')->nullable();
            $table->string('alfa')->nullable();
            $table->string('vtb')->nullable();
            $table->string('raiffeisen')->nullable();
            $table->string('gazprombank')->nullable();

            $table->enum('active', [
                'sbp',
                'sber',
                'tbank',
                'ozon',
                'alfa',
                'vtb',
                'raiffeisen',
                'gazprombank'
            ])->nullable(); // выбранный по умолчанию метод
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
