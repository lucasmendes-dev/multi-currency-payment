<?php

namespace Database\Seeders;

use App\Models\PaymentRequest;
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
        $financeUser = User::factory()->create([
            'name' => 'Finance User',
            'email' => 'finance@example.com',
            'password' => Hash::make('Finance@123'),
            'role' => 'finance'
        ]);

        $employees = User::factory(5)->create();

        // Combine all users to select from for payment requests
        $allUsers = collect([$financeUser])->concat($employees);

        // Seed 20 payment requests using only the seeded users:
        // - 3 approved
        // - 2 rejected
        // - 2 expired (pending but expires_at 48h before now)
        // - 13 pending

        // 3 Approved
        for ($i = 0; $i < 3; $i++) {
            $user = $allUsers->random();
            PaymentRequest::factory()
                ->forCurrency($user->local_currency)
                ->approved($financeUser)
                ->create(['user_id' => $user->id]);
        }

        // 2 Rejected
        for ($i = 0; $i < 2; $i++) {
            $user = $allUsers->random();
            PaymentRequest::factory()
                ->forCurrency($user->local_currency)
                ->rejected($financeUser)
                ->create(['user_id' => $user->id]);
        }

        // 2 Expired (pending, expires_at 48h before now)
        for ($i = 0; $i < 2; $i++) {
            $user = $allUsers->random();
            PaymentRequest::factory()
                ->forCurrency($user->local_currency)
                ->expired48h()
                ->create(['user_id' => $user->id]);
        }

        // 13 Pending
        for ($i = 0; $i < 13; $i++) {
            $user = $allUsers->random();
            PaymentRequest::factory()
                ->forCurrency($user->local_currency)
                ->create(['user_id' => $user->id]);
        }
    }
}
