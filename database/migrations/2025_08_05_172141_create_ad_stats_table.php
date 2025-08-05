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
        Schema::create('ad_stats', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ad_id')->constrained()->onDelete('cascade');

            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade'); // для авторизованных
            $table->ipAddress('ip_address')->nullable(); // для гостей

            $table->enum('type', ['view', 'click']);

            $table->timestamp('created_at')->index(); // аналитика по датам

            $table->unique(['ad_id', 'user_id', 'type']); // 1 юзер = 1 просмотр / клик / заказ
            $table->unique(['ad_id', 'ip_address', 'type']); // для гостей аналогично
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_stats');
    }
};
