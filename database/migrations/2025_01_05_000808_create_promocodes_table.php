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
        Schema::create('promocodes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('promocode')->unique();
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('buybacks_count')->comment('Количество выкупов, которое промокод дает юзеру');
            $table->integer('max_usage')->comment('Максимальное кол-во использований');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promocodes');
    }
};
