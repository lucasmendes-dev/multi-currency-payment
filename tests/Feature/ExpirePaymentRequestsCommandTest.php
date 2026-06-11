<?php

namespace Tests\Feature;

use App\Enums\PaymentStatusEnum;
use App\Models\PaymentRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ExpirePaymentRequestsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_expires_pending_payments_past_48_hours(): void
    {
        // Create a payment that expired 1 hour ago
        $payment = PaymentRequest::factory()->pendingAndExpired()->create();

        $this->artisan('payments:expire')
            ->expectsOutputToContain('1 payment requests expired.')
            ->assertExitCode(0);

        $payment->refresh();
        $this->assertEquals(PaymentStatusEnum::EXPIRED, $payment->status);
    }

    public function test_command_does_not_expire_pending_payments_within_48_hours(): void
    {
        // Create a payment that won't expire for another 24 hours
        $payment = PaymentRequest::factory()->create([
            'status' => PaymentStatusEnum::PENDING,
            'expires_at' => now()->addHours(24),
        ]);

        $this->artisan('payments:expire')
            ->expectsOutputToContain('0 payment requests expired.')
            ->assertExitCode(0);

        $payment->refresh();
        $this->assertEquals(PaymentStatusEnum::PENDING, $payment->status);
    }

    public function test_command_does_not_affect_approved_payments(): void
    {
        $payment = PaymentRequest::factory()->approved()->create([
            'expires_at' => now()->subHours(72),
        ]);

        $this->artisan('payments:expire')
            ->expectsOutputToContain('0 payment requests expired.')
            ->assertExitCode(0);

        $payment->refresh();
        $this->assertEquals(PaymentStatusEnum::APPROVED, $payment->status);
    }

    public function test_command_does_not_affect_rejected_payments(): void
    {
        $payment = PaymentRequest::factory()->rejected()->create([
            'expires_at' => now()->subHours(72),
        ]);

        $this->artisan('payments:expire')
            ->expectsOutputToContain('0 payment requests expired.')
            ->assertExitCode(0);

        $payment->refresh();
        $this->assertEquals(PaymentStatusEnum::REJECTED, $payment->status);
    }

    public function test_command_does_not_affect_already_expired_payments(): void
    {
        $payment = PaymentRequest::factory()->expired()->create();

        $this->artisan('payments:expire')
            ->expectsOutputToContain('0 payment requests expired.')
            ->assertExitCode(0);
    }

    public function test_command_expires_multiple_pending_payments_at_once(): void
    {
        // Create 5 pending payments that have all passed their expiration
        PaymentRequest::factory()->count(5)->pendingAndExpired()->create();

        // And 2 that are still valid
        PaymentRequest::factory()->count(2)->create([
            'status' => PaymentStatusEnum::PENDING,
            'expires_at' => now()->addHours(24),
        ]);

        $this->artisan('payments:expire')
            ->expectsOutputToContain('5 payment requests expired.')
            ->assertExitCode(0);

        $this->assertEquals(5, PaymentRequest::where('status', PaymentStatusEnum::EXPIRED)->count());
        $this->assertEquals(2, PaymentRequest::where('status', PaymentStatusEnum::PENDING)->count());
    }

    public function test_command_handles_zero_payments_gracefully(): void
    {
        $this->artisan('payments:expire')
            ->expectsOutputToContain('0 payment requests expired.')
            ->assertExitCode(0);
    }

    public function test_command_expires_payment_exactly_at_boundary(): void
    {
        Carbon::setTestNow('2026-01-15 12:00:00');

        // Payment that expires exactly now
        $payment = PaymentRequest::factory()->create([
            'status' => PaymentStatusEnum::PENDING,
            'expires_at' => Carbon::parse('2026-01-15 12:00:00'),
        ]);

        $this->artisan('payments:expire')
            ->expectsOutputToContain('1 payment requests expired.')
            ->assertExitCode(0);

        $payment->refresh();
        $this->assertEquals(PaymentStatusEnum::EXPIRED, $payment->status);
    }
}
