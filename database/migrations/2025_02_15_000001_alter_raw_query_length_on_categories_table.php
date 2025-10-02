<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `categories` MODIFY `raw_query` TEXT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `categories` MODIFY `raw_query` VARCHAR(255) NULL");
    }
};
