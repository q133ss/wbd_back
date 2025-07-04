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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('wb_id')->index()->comment('ИД товара из WB');
            $table->string('name');
            $table->decimal('price', 8, 2);
            $table->string('brand')->nullable();
            $table->decimal('discount', 5, 2)->unsigned()->default(0);
            $table->decimal('rating', 3, 2)->unsigned()->default(0);
            $table->unsignedInteger('quantity_available')->default(0);
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->text('description')->nullable();
            $table->decimal('supplier_rating', 3, 2)->unsigned()->default(0);
            $table->boolean('is_archived')->default(false);
            $table->foreignId('shop_id')->constrained('shops')->onDelete('cascade');
            $table->json('images');
            $table->json('colors')->nullable();
            $table->json('sizes')->nullable();
            $table->boolean('status')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
