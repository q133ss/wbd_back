<?php

use App\Models\Promocode;
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
        Schema::table('promocodes', function (Blueprint $table) {
            $table->dropUnique('promocodes_name_unique');
            $table->json('data')->nullable()->after('max_usage')->comment('Данные, а именно type, value...');
            $table->dropColumn('buybacks_count');
        });

        Promocode::create([
            'name' => 'Superstar 1 месяц',
            'promocode' => 'FIRST10',
            'start_date' => now(),
            'end_date' => now()->endOfYear(),
            'max_usage' => 99999,
            'data' => json_encode([
                "type" => "free_tariff",
                "tariff_name" => "Superstar",
                "variant_name" => "1 месяц"
            ])
        ]);

        Promocode::create([
            'name' => 'Superstar 1 месяц',
            'promocode' => 'WBD390N83JK0',
            'start_date' => now(),
            'end_date' => now()->endOfYear(),
            'max_usage' => 99999,
            'data' => json_encode([
                "type" => "free_tariff",
                "tariff_name" => "Superstar",
                "variant_name" => "1 месяц"
            ])
        ]);

        Schema::table('user_tariff', function (Blueprint $table) {
            $table->unsignedBigInteger('products_count')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promocodes', function (Blueprint $table) {
            $table->dropColumn('data');
            $table->dropColumn('redemption_count'); // Удаляем поле redemption_count, оно устарело
            $table->integer('buybacks_count')->comment('Количество выкупов, которое промокод дает юзеру');
            Promocode::where('promocode', 'FIRST10')->delete();
            Promocode::where('promocode', 'WBD390N83JK0')->delete();
        });

        Schema::table('user_tariff', function (Blueprint $table) {
            $table->unsignedBigInteger('products_count')->nullable(false)->change();
        });
    }
};
