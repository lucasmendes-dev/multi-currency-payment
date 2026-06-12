<?php

namespace Tests\Feature;

use App\Enums\PaymentStatusEnum;
use App\Interfaces\ExchangerateInterface;
use App\Models\PaymentRequest;
use App\Models\User;
use App\ValueObjects\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class PaymentRequestControllerTest extends TestCase
{
    use RefreshDatabase;

    private $exchangeRateMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the exchange rate service globally so tests don't hit external APIs
        $this->exchangeRateMock = Mockery::mock(ExchangerateInterface::class);
        $this->app->instance(ExchangerateInterface::class, $this->exchangeRateMock);

        config([
            'app.default_convertion_currency' => 'EUR',
            'app.exchange_rate_source' => 'https://v6.exchangerate-api.com',
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  CREATE PAYMENT REQUEST (POST /api/payments)
    // ═══════════════════════════════════════════════════════════════════

    public function test_authenticated_user_can_create_payment_request(): void
    {
        $user = User::factory()->create(['local_currency' => 'BRL']);
        Sanctum::actingAs($user);

        $this->exchangeRateMock
            ->shouldReceive('getExchangeRate')
            ->with('BRL')
            ->once()
            ->andReturn(0.18);

        $this->exchangeRateMock
            ->shouldReceive('convert')
            ->once()
            ->andReturn(new Money(18.00, 'EUR'));

        $payload = [
            'local_amount' => 100.00,
            'local_currency' => 'BRL',
            'description' => 'Office supplies',
        ];

        $response = $this->postJson('/api/payments', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'user_id',
                    'local_currency',
                    'local_amount',
                    'target_currency',
                    'converted_amount',
                    'exchange_rate',
                    'exchange_rate_source',
                    'exchange_rate_fetched_at',
                    'description',
                    'status',
                    'expires_at',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.local_currency', 'BRL')
            ->assertJsonPath('data.target_currency', 'EUR')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.description', 'Office supplies');

        $this->assertDatabaseHas('payment_requests', [
            'user_id' => $user->id,
            'local_currency' => 'BRL',
            'status' => 'pending',
        ]);
    }

    public function test_unauthenticated_user_cannot_create_payment_request(): void
    {
        $response = $this->postJson('/api/payments', [
            'local_amount' => 100.00,
            'local_currency' => 'BRL',
            'description' => 'Test',
        ]);

        $response->assertStatus(401);
    }

    public function test_create_payment_request_requires_local_amount(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/payments', [
            'local_currency' => 'BRL',
            'description' => 'Test',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['local_amount']);
    }

    public function test_create_payment_request_requires_local_currency(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/payments', [
            'local_amount' => 100.00,
            'description' => 'Test',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['local_currency']);
    }

    public function test_create_payment_request_validates_amount_is_numeric(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/payments', [
            'local_amount' => 'not-a-number',
            'local_currency' => 'BRL',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['local_amount']);
    }

    public function test_create_payment_request_validates_amount_minimum(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/payments', [
            'local_amount' => 0,
            'local_currency' => 'BRL',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['local_amount']);
    }

    public function test_create_payment_request_validates_currency_length(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/payments', [
            'local_amount' => 100.00,
            'local_currency' => 'ABCD', // too long
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['local_currency']);
    }

    public function test_create_payment_request_allows_nullable_description(): void
    {
        $user = User::factory()->create(['local_currency' => 'USD']);
        Sanctum::actingAs($user);

        $this->exchangeRateMock->shouldReceive('getExchangeRate')->andReturn(1.1);
        $this->exchangeRateMock->shouldReceive('convert')->andReturn(new Money(110.00, 'EUR'));

        $response = $this->postJson('/api/payments', [
            'local_amount' => 100.00,
            'local_currency' => 'USD',
        ]);

        $response->assertStatus(201);
    }

    public function test_finance_user_can_also_create_payment_request(): void
    {
        $financeUser = User::factory()->create(['role' => 'finance', 'local_currency' => 'EUR']);
        Sanctum::actingAs($financeUser);

        $this->exchangeRateMock->shouldReceive('getExchangeRate')->andReturn(1.0);
        $this->exchangeRateMock->shouldReceive('convert')->andReturn(new Money(200.00, 'EUR'));

        $response = $this->postJson('/api/payments', [
            'local_amount' => 200.00,
            'local_currency' => 'EUR',
            'description' => 'Finance expense',
        ]);

        $response->assertStatus(201);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  LIST PAYMENT REQUESTS (GET /api/payments)
    // ═══════════════════════════════════════════════════════════════════

    public function test_employee_sees_only_own_payment_requests(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $otherEmployee = User::factory()->create(['role' => 'employee']);

        PaymentRequest::factory()->count(3)->create(['user_id' => $employee->id]);
        PaymentRequest::factory()->count(2)->create(['user_id' => $otherEmployee->id]);

        Sanctum::actingAs($employee);

        $response = $this->getJson('/api/payments');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'user_id', 'status']],
                'meta' => ['total', 'per_page', 'current_page', 'last_page'],
            ]);
    }

    public function test_finance_user_sees_all_payment_requests(): void
    {
        $financeUser = User::factory()->create(['role' => 'finance']);

        // Create payments for different employees
        PaymentRequest::factory()->count(3)->create();
        PaymentRequest::factory()->count(2)->create();

        Sanctum::actingAs($financeUser);

        $response = $this->getJson('/api/payments');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 5);
    }

    public function test_list_payment_requests_can_filter_by_status(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);

        PaymentRequest::factory()->count(2)->create([
            'user_id' => $employee->id,
            'status' => PaymentStatusEnum::PENDING,
        ]);
        PaymentRequest::factory()->create([
            'user_id' => $employee->id,
            'status' => PaymentStatusEnum::APPROVED,
        ]);

        Sanctum::actingAs($employee);

        $response = $this->getJson('/api/payments?status=pending');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_list_payment_requests_can_filter_by_currency(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);

        PaymentRequest::factory()->count(2)->create([
            'user_id' => $employee->id,
            'local_currency' => 'BRL',
        ]);
        PaymentRequest::factory()->create([
            'user_id' => $employee->id,
            'local_currency' => 'USD',
        ]);

        Sanctum::actingAs($employee);

        $response = $this->getJson('/api/payments?local_currency=BRL');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_list_returns_paginated_results(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);

        // Create 15 payment requests (paginated at 10)
        PaymentRequest::factory()->count(15)->create([
            'user_id' => $employee->id,
        ]);

        Sanctum::actingAs($employee);

        $response = $this->getJson('/api/payments');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 15)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonCount(10, 'data');

        // Page 2
        $response2 = $this->getJson('/api/payments?page=2');

        $response2->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.current_page', 2);
    }

    public function test_unauthenticated_user_cannot_list_payments(): void
    {
        $response = $this->getJson('/api/payments');

        $response->assertStatus(401);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  SHOW PAYMENT REQUEST (GET /api/payments/{id})
    // ═══════════════════════════════════════════════════════════════════

    public function test_employee_can_view_own_payment_request(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $payment = PaymentRequest::factory()->create(['user_id' => $employee->id]);

        Sanctum::actingAs($employee);

        $response = $this->getJson("/api/payments/{$payment->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id', 'user_id', 'local_currency', 'local_amount',
                    'target_currency', 'converted_amount', 'exchange_rate',
                    'exchange_rate_source', 'description', 'status',
                ],
            ])
            ->assertJsonPath('data.id', $payment->id);
    }

    public function test_employee_cannot_view_another_users_payment_request(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $otherEmployee = User::factory()->create(['role' => 'employee']);
        $payment = PaymentRequest::factory()->create(['user_id' => $otherEmployee->id]);

        Sanctum::actingAs($employee);

        $response = $this->getJson("/api/payments/{$payment->id}");

        $response->assertStatus(403);
    }

    public function test_finance_user_can_view_any_payment_request(): void
    {
        $financeUser = User::factory()->create(['role' => 'finance']);
        $employee = User::factory()->create(['role' => 'employee']);
        $payment = PaymentRequest::factory()->create(['user_id' => $employee->id]);

        Sanctum::actingAs($financeUser);

        $response = $this->getJson("/api/payments/{$payment->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $payment->id);
    }

    public function test_show_returns_404_for_nonexistent_payment(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/payments/99999');

        $response->assertStatus(404);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  APPROVE PAYMENT REQUEST (PATCH /api/payments/{id}/approve)
    // ═══════════════════════════════════════════════════════════════════

    public function test_finance_user_can_approve_pending_payment(): void
    {
        $financeUser = User::factory()->create(['role' => 'finance']);
        $payment = PaymentRequest::factory()->create([
            'status' => PaymentStatusEnum::PENDING,
        ]);

        Sanctum::actingAs($financeUser);

        $response = $this->patchJson("/api/payments/{$payment->id}/approve");

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Payment request approved successfully.')
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.approved_by', $financeUser->name);

        $this->assertDatabaseHas('payment_requests', [
            'id' => $payment->id,
            'status' => 'approved',
            'approved_by' => $financeUser->id,
        ]);
    }

    public function test_employee_cannot_approve_payment(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $payment = PaymentRequest::factory()->create([
            'status' => PaymentStatusEnum::PENDING,
        ]);

        Sanctum::actingAs($employee);

        $response = $this->patchJson("/api/payments/{$payment->id}/approve");

        $response->assertStatus(403);
    }

    public function test_cannot_approve_already_approved_payment(): void
    {
        $financeUser = User::factory()->create(['role' => 'finance']);
        $payment = PaymentRequest::factory()->approved()->create();

        Sanctum::actingAs($financeUser);

        $response = $this->patchJson("/api/payments/{$payment->id}/approve");

        $response->assertStatus(500);
    }

    public function test_cannot_approve_rejected_payment(): void
    {
        $financeUser = User::factory()->create(['role' => 'finance']);
        $payment = PaymentRequest::factory()->rejected()->create();

        Sanctum::actingAs($financeUser);

        $response = $this->patchJson("/api/payments/{$payment->id}/approve");

        $response->assertStatus(500);
    }

    public function test_cannot_approve_expired_payment(): void
    {
        $financeUser = User::factory()->create(['role' => 'finance']);
        $payment = PaymentRequest::factory()->expired()->create();

        Sanctum::actingAs($financeUser);

        $response = $this->patchJson("/api/payments/{$payment->id}/approve");

        $response->assertStatus(500);
    }

    public function test_unauthenticated_user_cannot_approve_payment(): void
    {
        $payment = PaymentRequest::factory()->create();

        $response = $this->patchJson("/api/payments/{$payment->id}/approve");

        $response->assertStatus(401);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  REJECT PAYMENT REQUEST (PATCH /api/payments/{id}/reject)
    // ═══════════════════════════════════════════════════════════════════

    public function test_finance_user_can_reject_pending_payment(): void
    {
        $financeUser = User::factory()->create(['role' => 'finance']);
        $payment = PaymentRequest::factory()->create([
            'status' => PaymentStatusEnum::PENDING,
        ]);

        Sanctum::actingAs($financeUser);

        $response = $this->patchJson("/api/payments/{$payment->id}/reject", [
            'rejection_reason' => 'Budget exceeded',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Payment request rejected successfully.')
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.rejected_by', $financeUser->name)
            ->assertJsonPath('data.rejection_reason', 'Budget exceeded');

        $this->assertDatabaseHas('payment_requests', [
            'id' => $payment->id,
            'status' => 'rejected',
            'rejected_by' => $financeUser->id,
            'rejection_reason' => 'Budget exceeded',
        ]);
    }

    public function test_employee_cannot_reject_payment(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $payment = PaymentRequest::factory()->create([
            'status' => PaymentStatusEnum::PENDING,
        ]);

        Sanctum::actingAs($employee);

        $response = $this->patchJson("/api/payments/{$payment->id}/reject", [
            'rejection_reason' => 'Not allowed',
        ]);

        $response->assertStatus(403);
    }

    public function test_cannot_reject_already_approved_payment(): void
    {
        $financeUser = User::factory()->create(['role' => 'finance']);
        $payment = PaymentRequest::factory()->approved()->create();

        Sanctum::actingAs($financeUser);

        $response = $this->patchJson("/api/payments/{$payment->id}/reject", [
            'rejection_reason' => 'Too late',
        ]);

        $response->assertStatus(500);
    }

    public function test_cannot_reject_already_rejected_payment(): void
    {
        $financeUser = User::factory()->create(['role' => 'finance']);
        $payment = PaymentRequest::factory()->rejected()->create();

        Sanctum::actingAs($financeUser);

        $response = $this->patchJson("/api/payments/{$payment->id}/reject", [
            'rejection_reason' => 'Again',
        ]);

        $response->assertStatus(500);
    }

    public function test_cannot_reject_expired_payment(): void
    {
        $financeUser = User::factory()->create(['role' => 'finance']);
        $payment = PaymentRequest::factory()->expired()->create();

        Sanctum::actingAs($financeUser);

        $response = $this->patchJson("/api/payments/{$payment->id}/reject", [
            'rejection_reason' => 'Too late',
        ]);

        $response->assertStatus(500);
    }

    public function test_reject_without_reason_still_works(): void
    {
        $financeUser = User::factory()->create(['role' => 'finance']);
        $payment = PaymentRequest::factory()->create([
            'status' => PaymentStatusEnum::PENDING,
        ]);

        Sanctum::actingAs($financeUser);

        $response = $this->patchJson("/api/payments/{$payment->id}/reject");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'rejected');
    }

    public function test_unauthenticated_user_cannot_reject_payment(): void
    {
        $payment = PaymentRequest::factory()->create();

        $response = $this->patchJson("/api/payments/{$payment->id}/reject", [
            'rejection_reason' => 'No access',
        ]);

        $response->assertStatus(401);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  EXCHANGE RATE IMMUTABILITY
    // ═══════════════════════════════════════════════════════════════════

    public function test_exchange_rate_is_stored_at_creation_time(): void
    {
        $user = User::factory()->create(['local_currency' => 'BRL']);
        Sanctum::actingAs($user);

        $this->exchangeRateMock
            ->shouldReceive('getExchangeRate')
            ->with('BRL')
            ->once()
            ->andReturn(0.18);

        $this->exchangeRateMock
            ->shouldReceive('convert')
            ->once()
            ->andReturn(new Money(18.00, 'EUR'));

        $response = $this->postJson('/api/payments', [
            'local_amount' => 100.00,
            'local_currency' => 'BRL',
            'description' => 'Test',
        ]);

        $response->assertStatus(201);

        $paymentId = $response->json('data.id');

        // Verify the rate and source are persisted
        $this->assertDatabaseHas('payment_requests', [
            'id' => $paymentId,
            'exchange_rate' => 0.18,
            'exchange_rate_source' => 'https://v6.exchangerate-api.com',
        ]);

        // Verify exchange_rate_fetched_at is not null
        $payment = PaymentRequest::find($paymentId);
        $this->assertNotNull($payment->exchange_rate_fetched_at);
    }

    // ── Additional Validation Tests ──────────────────────────────────────────

    public function test_create_payment_request_validates_currency_min_length(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/payments', [
            'local_amount' => 100.00,
            'local_currency' => 'US', // too short, must be 3
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['local_currency']);
    }

    public function test_create_payment_request_validates_description_max_length(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/payments', [
            'local_amount' => 100.00,
            'local_currency' => 'USD',
            'description' => str_repeat('a', 256), // too long, max 255
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['description']);
    }

    // ── User ID Filtering Tests ──────────────────────────────────────────────

    public function test_list_payment_requests_can_filter_by_user_id_for_finance(): void
    {
        $financeUser = User::factory()->create(['role' => 'finance']);
        $employeeA = User::factory()->create(['role' => 'employee']);
        $employeeB = User::factory()->create(['role' => 'employee']);

        PaymentRequest::factory()->count(3)->create(['user_id' => $employeeA->id]);
        PaymentRequest::factory()->count(2)->create(['user_id' => $employeeB->id]);

        Sanctum::actingAs($financeUser);

        // Filter for Employee A
        $response = $this->getJson("/api/payments?user_id={$employeeA->id}");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');

        // Filter for Employee B
        $responseB = $this->getJson("/api/payments?user_id={$employeeB->id}");

        $responseB->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_list_payment_requests_restricts_user_id_filtering_for_employee(): void
    {
        $employeeA = User::factory()->create(['role' => 'employee']);
        $employeeB = User::factory()->create(['role' => 'employee']);

        PaymentRequest::factory()->count(3)->create(['user_id' => $employeeA->id]);
        PaymentRequest::factory()->count(2)->create(['user_id' => $employeeB->id]);

        Sanctum::actingAs($employeeA);

        // Employee A tries to filter for Employee B's requests - should get 0 results because their query is scoped to Employee A only
        $responseOther = $this->getJson("/api/payments?user_id={$employeeB->id}");

        $responseOther->assertStatus(200)
            ->assertJsonCount(0, 'data');

        // Employee A filters by their own user_id - should get their own 3 requests
        $responseSelf = $this->getJson("/api/payments?user_id={$employeeA->id}");

        $responseSelf->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }
}

