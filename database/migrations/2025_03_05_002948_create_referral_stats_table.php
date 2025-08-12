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
        Schema::create('referral_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('clicks_count')->default(0)->comment('Колво переходов по реферальной ссылке');
            $table->unsignedBigInteger('registrations_count')->default(0)->comment('Колво зарегестрированных пользователей');
            $table->unsignedBigInteger('topup_count')->default(0)->comment('Колво раз пополнений баланса');
            $table->unsignedBigInteger('earnings')->default(0)->comment('Заработок');
            $table->enum('type', ['site', 'telegram'])->default('site')->comment('Тип: сайт или тг');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referral_stats');
    }
};
