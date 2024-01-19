<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        \App\Models\Admin::create([
            'username' => 'admin',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('abc123')
        ]);
        \App\Models\Client::create([
            'username' => 'client',
            'email' => 'client@gmail.com',
            'password' => bcrypt('abc123')
        ]);
        \App\Models\Freelancer::create([
            'username' => 'freelancer',
            'email' => 'freelancer@gmail.com',
            'password' => bcrypt('abc123')
        ]);
    }
}
