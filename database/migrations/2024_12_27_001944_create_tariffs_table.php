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
        Schema::create('tariffs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('price');
            $table->integer('buybacks_count')->comment('Кол-во выкупов');
            $table->json('advantages')->comment('Преимущества');
            $table->unsignedInteger('redemption_price')->comment('Цена 1 выкупа');
            $table->date('expiration_date')->nullable()->comment('Срок действия');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tariffs');
    }
};
