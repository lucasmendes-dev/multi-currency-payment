<?php

namespace Database\Factories;

use App\Enums\PaymentStatusEnum;
use App\Models\PaymentRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentRequest>
 */
class PaymentRequestFactory extends Factory
{
    protected $model = PaymentRequest::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'local_currency' => fake()->randomElement(['BRL', 'USD', 'GBP', 'JPY', 'CHF']),
            'local_amount' => fake()->randomFloat(2, 10, 10000),
            'target_currency' => 'EUR',
            'converted_amount' => fake()->randomFloat(2, 5, 8000),
            'exchange_rate' => fake()->randomFloat(8, 0.01, 10),
            'exchange_rate_source' => 'https://v6.exchangerate-api.com',
            'exchange_rate_fetched_at' => now(),
            'description' => fake()->sentence(),
            'status' => PaymentStatusEnum::PENDING,
            'approved_by' => null,
            'approved_at' => null,
            'rejected_by' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
            'expires_at' => now()->addHours(48),
        ];
    }

    /**
     * Set the payment request as approved.
     */
    public function approved(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => PaymentStatusEnum::APPROVED,
            'approved_by' => User::factory()->state(['role' => 'finance']),
            'approved_at' => now(),
        ]);
    }

    /**
     * Set the payment request as rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => PaymentStatusEnum::REJECTED,
            'rejected_by' => User::factory()->state(['role' => 'finance']),
            'rejected_at' => now(),
            'rejection_reason' => fake()->sentence(),
        ]);
    }

    /**
     * Set the payment request as expired.
     */
    public function expired(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => PaymentStatusEnum::EXPIRED,
            'expires_at' => now()->subHour(),
        ]);
    }

    /**
     * Set the payment request as already past its expiration time but still pending.
     * Useful for testing the expire command.
     */
    public function pendingAndExpired(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => PaymentStatusEnum::PENDING,
            'expires_at' => now()->subHour(),
        ]);
    }
}
