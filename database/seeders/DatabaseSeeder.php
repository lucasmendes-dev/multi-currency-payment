<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Creating a default finance user to approve or reject the payment requests
        User::factory()->create([
            'name' => 'Finance User',
            'email' => 'finance@example.com',
            'password' => Hash::make('Finance@123'),
            'role' => 'finance'
        ]);

        User::factory(5)->create();
    }
}
