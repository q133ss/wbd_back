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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->unique();
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->boolean('is_configured')->default(false)->comment('Проверка, завершил-ли юзер настройку профиля после регистрации');
            $table->decimal('balance', 10, 2)->default(0)->comment('Баланс пользователя');
            $table->unsignedSmallInteger('redemption_count')->default(0)->comment('Количество выкупов');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone');
            $table->dropColumn('is_configured');
        });
    }
};
