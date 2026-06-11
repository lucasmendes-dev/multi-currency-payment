<?php

namespace Tests\Unit;

use App\Enums\PaymentStatusEnum;
use App\Http\Requests\StorePaymentRequest;
use App\Interfaces\ExchangerateInterface;
use App\Models\PaymentRequest;
use App\Models\User;
use App\Services\PaymentRequestService;
use App\ValueObjects\Money;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Mockery;
use Tests\TestCase;

class PaymentRequestServiceTest extends TestCase
{
    use RefreshDatabase;

    private PaymentRequestService $service;
    private ExchangerateInterface $exchangeRateMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->exchangeRateMock = Mockery::mock(ExchangerateInterface::class);
        $this->service = new PaymentRequestService($this->exchangeRateMock);

        config([
            'app.default_convertion_currency' => 'EUR',
            'app.exchange_rate_source' => 'https://v6.exchangerate-api.com',
        ]);
    }

    // ── createPaymentRequest ────────────────────────────────────────────

    public function test_create_payment_request_stores_record_with_correct_data(): void
    {
        Carbon::setTestNow(now());

        $user = User::factory()->create(['local_currency' => 'BRL']);

        $this->exchangeRateMock
            ->shouldReceive('getExchangeRate')
            ->with('BRL')
            ->once()
            ->andReturn(0.18);

        $this->exchangeRateMock
            ->shouldReceive('convert')
            ->once()
            ->andReturn(new Money(18.00, 'EUR'));

        // Build a mock StorePaymentRequest that returns the user
        $request = Mockery::mock(StorePaymentRequest::class);
        $request->shouldReceive('user')->andReturn($user);

        $data = [
            'local_amount' => 100.00,
            'local_currency' => 'BRL',
            'description' => 'Office supplies',
        ];

        $paymentRequest = $this->service->createPaymentRequest($data, $request);

        $this->assertInstanceOf(PaymentRequest::class, $paymentRequest);
        $this->assertEquals($user->id, $paymentRequest->user_id);
        $this->assertEquals('EUR', $paymentRequest->target_currency);
        $this->assertEquals(0.18, $paymentRequest->exchange_rate);
        $this->assertEquals('https://v6.exchangerate-api.com', $paymentRequest->exchange_rate_source);
        $this->assertEquals(PaymentStatusEnum::PENDING, $paymentRequest->status);
        $this->assertEquals('Office supplies', $paymentRequest->description);
        $this->assertNotNull($paymentRequest->expires_at);

        $this->assertDatabaseHas('payment_requests', [
            'id' => $paymentRequest->id,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
    }

    public function test_create_payment_request_sets_expiration_to_48_hours(): void
    {
        Carbon::setTestNow('2026-01-15 10:00:00');

        $user = User::factory()->create();

        $this->exchangeRateMock->shouldReceive('getExchangeRate')->andReturn(1.0);
        $this->exchangeRateMock->shouldReceive('convert')->andReturn(new Money(100.00, 'EUR'));

        $request = Mockery::mock(StorePaymentRequest::class);
        $request->shouldReceive('user')->andReturn($user);

        $paymentRequest = $this->service->createPaymentRequest([
            'local_amount' => 100.00,
            'local_currency' => 'EUR',
            'description' => 'Test',
        ], $request);

        $this->assertEquals(
            '2026-01-17 10:00:00',
            Carbon::parse($paymentRequest->expires_at)->format('Y-m-d H:i:s')
        );
    }

    public function test_create_payment_request_sets_status_to_pending(): void
    {
        $user = User::factory()->create();

        $this->exchangeRateMock->shouldReceive('getExchangeRate')->andReturn(1.0);
        $this->exchangeRateMock->shouldReceive('convert')->andReturn(new Money(50.00, 'EUR'));

        $request = Mockery::mock(StorePaymentRequest::class);
        $request->shouldReceive('user')->andReturn($user);

        $paymentRequest = $this->service->createPaymentRequest([
            'local_amount' => 50.00,
            'local_currency' => 'EUR',
            'description' => 'Test',
        ], $request);

        $this->assertEquals(PaymentStatusEnum::PENDING, $paymentRequest->status);
    }

    // ── approvePaymentRequest ───────────────────────────────────────────

    public function test_approve_payment_request_changes_status_to_approved(): void
    {
        $financeUser = User::factory()->create(['role' => 'finance']);
        $this->actingAs($financeUser);

        $paymentRequest = PaymentRequest::factory()->create([
            'status' => PaymentStatusEnum::PENDING,
        ]);

        $result = $this->service->approvePaymentRequest($paymentRequest->id);

        $this->assertEquals(PaymentStatusEnum::APPROVED, $result->status);
        $this->assertEquals($financeUser->id, $result->approved_by);
        $this->assertNotNull($result->approved_at);
    }

    public function test_approve_throws_exception_if_payment_is_not_pending(): void
    {
        $financeUser = User::factory()->create(['role' => 'finance']);
        $this->actingAs($financeUser);

        $paymentRequest = PaymentRequest::factory()->create([
            'status' => PaymentStatusEnum::APPROVED,
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Payment request should be in 'pending' state to be approved.");

        $this->service->approvePaymentRequest($paymentRequest->id);
    }

    public function test_approve_throws_exception_for_rejected_payment(): void
    {
        $financeUser = User::factory()->create(['role' => 'finance']);
        $this->actingAs($financeUser);

        $paymentRequest = PaymentRequest::factory()->rejected()->create();

        $this->expectException(Exception::class);

        $this->service->approvePaymentRequest($paymentRequest->id);
    }

    public function test_approve_throws_exception_for_expired_payment(): void
    {
        $financeUser = User::factory()->create(['role' => 'finance']);
        $this->actingAs($financeUser);

        $paymentRequest = PaymentRequest::factory()->expired()->create();

        $this->expectException(Exception::class);

        $this->service->approvePaymentRequest($paymentRequest->id);
    }

    // ── rejectPaymentRequest ────────────────────────────────────────────

    public function test_reject_payment_request_changes_status_to_rejected(): void
    {
        $financeUser = User::factory()->create(['role' => 'finance']);
        $this->actingAs($financeUser);

        $paymentRequest = PaymentRequest::factory()->create([
            'status' => PaymentStatusEnum::PENDING,
        ]);

        $result = $this->service->rejectPaymentRequest($paymentRequest->id, 'Budget exceeded');

        $this->assertEquals(PaymentStatusEnum::REJECTED, $result->status);
        $this->assertEquals($financeUser->id, $result->rejected_by);
        $this->assertNotNull($result->rejected_at);
        $this->assertEquals('Budget exceeded', $result->rejection_reason);
    }

    public function test_reject_throws_exception_if_payment_is_not_pending(): void
    {
        $financeUser = User::factory()->create(['role' => 'finance']);
        $this->actingAs($financeUser);

        $paymentRequest = PaymentRequest::factory()->approved()->create();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Payment request should be in 'pending' state to be rejected.");

        $this->service->rejectPaymentRequest($paymentRequest->id, 'Reason');
    }

    public function test_reject_throws_exception_for_expired_payment(): void
    {
        $financeUser = User::factory()->create(['role' => 'finance']);
        $this->actingAs($financeUser);

        $paymentRequest = PaymentRequest::factory()->expired()->create();

        $this->expectException(Exception::class);

        $this->service->rejectPaymentRequest($paymentRequest->id, 'Too late');
    }

    public function test_reject_stores_empty_reason_when_provided(): void
    {
        $financeUser = User::factory()->create(['role' => 'finance']);
        $this->actingAs($financeUser);

        $paymentRequest = PaymentRequest::factory()->create([
            'status' => PaymentStatusEnum::PENDING,
        ]);

        $result = $this->service->rejectPaymentRequest($paymentRequest->id, '');

        $this->assertEquals(PaymentStatusEnum::REJECTED, $result->status);
        $this->assertEquals('', $result->rejection_reason);
    }
}
