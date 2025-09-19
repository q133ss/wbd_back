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
        Schema::create('autopost_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_enabled')->default(false);
            $table->boolean('show_price')->default(true);
            $table->boolean('show_cashback')->default(true);
            $table->boolean('show_conditions')->default(false);
            $table->boolean('show_photo')->default(true);
            $table->boolean('show_link')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('autopost_settings');
    }
};
