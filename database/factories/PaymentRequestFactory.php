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

    private const APPROXIMATE_RATES = [
        'BRL' => 0.18,
        'USD' => 0.93,
        'GBP' => 1.17,
        'JPY' => 0.0062,
        'CHF' => 1.05,
        'EUR' => 1.0,
        'CAD' => 0.69,
        'AUD' => 0.61,
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $currency = fake()->randomElement(array_keys(self::APPROXIMATE_RATES));
        $localAmount = fake()->randomFloat(2, 10, 10000);
        $exchangeRate = self::APPROXIMATE_RATES[$currency] ?? 1.0;

        return [
            'user_id' => User::factory(),
            'local_currency' => $currency,
            'local_amount' => $localAmount,
            'target_currency' => 'EUR',
            'converted_amount' => round($localAmount * $exchangeRate, 2),
            'exchange_rate' => $exchangeRate,
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
    public function approved(?User $financeUser = null): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => PaymentStatusEnum::APPROVED,
            'approved_by' => $financeUser ? $financeUser->id : User::factory()->state(['role' => 'finance']),
            'approved_at' => now(),
        ]);
    }

    /**
     * Set the payment request as rejected.
     */
    public function rejected(?User $financeUser = null): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => PaymentStatusEnum::REJECTED,
            'rejected_by' => $financeUser ? $financeUser->id : User::factory()->state(['role' => 'finance']),
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

    /**
     * Set the payment request as pending and expired 48 hours ago.
     */
    public function expired48h(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => PaymentStatusEnum::PENDING,
            'expires_at' => now()->subHours(48),
        ]);
    }

    public function forCurrency(string $currency): static
    {
        $localAmount = fake()->randomFloat(2, 10, 10000);
        $exchangeRate = self::APPROXIMATE_RATES[$currency] ?? 1.0;

        return $this->state([
            'local_currency' => $currency,
            'local_amount' => $localAmount,
            'converted_amount' => round($localAmount * $exchangeRate, 2),
            'exchange_rate' => $exchangeRate,
        ]);
    }
}
