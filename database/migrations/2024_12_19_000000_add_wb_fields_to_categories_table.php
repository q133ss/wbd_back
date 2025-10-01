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
        Schema::table('categories', function (Blueprint $table) {
            $table->string('url')->nullable()->after('name');
            $table->string('shard_key')->nullable()->after('url');
            $table->string('raw_query')->nullable()->after('shard_key');
            $table->string('query')->nullable()->after('raw_query');
            $table->boolean('children_only')->default(false)->after('query');
            $table->json('nodes')->nullable()->after('children_only');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn([
                'url',
                'shard_key',
                'raw_query',
                'query',
                'children_only',
                'nodes',
            ]);
        });
    }
};
