<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('telegram_sellers', function (Blueprint $table) {
            $table->id();
            $table->string('group_slug')->index();
            $table->string('message_id');
            $table->string('author')->nullable();
            $table->text('message_text')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->string('message_link')->nullable();
            $table->timestamps();

            $table->unique(['group_slug', 'message_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_sellers');
    }
};
