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
        Schema::create('autopost_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_id')->nullable()->constrained('ads')->nullOnDelete();
            $table->string('chat_id')->index();
            $table->boolean('is_success')->default(false);
            $table->text('message')->nullable();
            $table->text('error_message')->nullable();
            $table->json('response_payload')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('autopost_logs');
    }
};
