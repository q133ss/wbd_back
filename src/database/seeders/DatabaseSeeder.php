<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        User::factory()->create([
            'name'     => 'Алексей',
            'email'    => 'alexey@email.net',
            'phone'    => '+7(951)867-70-86',
            'password' => bcrypt('password'),
        ]);

        Artisan::call('categories:import');
    }
}
