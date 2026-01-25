<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Admin::create([
            'name' => 'Sumon Ahmed',
            'email' => 'bdsumon4u@gmail.com',
            'password' => bcrypt('password'), // You should use a secure password here
        ]);

        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Sumon Ahmed',
            'email' => 'bdsumon4u@gmail.com',
        ]);
    }
}
