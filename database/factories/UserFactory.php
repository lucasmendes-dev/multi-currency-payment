<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    private const COUNTRIES_CURRENCIES = [
        ['country' => 'Brazil', 'currency' => 'BRL'],
        ['country' => 'United States', 'currency' => 'USD'],
        ['country' => 'United Kingdom', 'currency' => 'GBP'],
        ['country' => 'Japan', 'currency' => 'JPY'],
        ['country' => 'Switzerland', 'currency' => 'CHF'],
        ['country' => 'Portugal', 'currency' => 'EUR'],
        ['country' => 'Germany', 'currency' => 'EUR'],
        ['country' => 'Canada', 'currency' => 'CAD'],
        ['country' => 'Australia', 'currency' => 'AUD'],
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $pair = fake()->randomElement(self::COUNTRIES_CURRENCIES);

        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => 'employee',
            'country' => $pair['country'],
            'local_currency' => $pair['currency'],
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
