<?php

namespace Tests\Feature;

use App\Models\PaymentRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentRequestPolicyTest extends TestCase
{
    use RefreshDatabase;

    // ═══════════════════════════════════════════════════════════════════
    //  viewAny — Any authenticated user can list payments
    // ═══════════════════════════════════════════════════════════════════

    public function test_employee_can_access_payment_list(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        Sanctum::actingAs($employee);

        $response = $this->getJson('/api/payments');

        $response->assertStatus(200);
    }

    public function test_finance_user_can_access_payment_list(): void
    {
        $financeUser = User::factory()->create(['role' => 'finance']);
        Sanctum::actingAs($financeUser);

        $response = $this->getJson('/api/payments');

        $response->assertStatus(200);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  view — Employees see own, finance sees all
    // ═══════════════════════════════════════════════════════════════════

    public function test_employee_can_view_own_payment(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $payment = PaymentRequest::factory()->create(['user_id' => $employee->id]);

        Sanctum::actingAs($employee);

        $response = $this->getJson("/api/payments/{$payment->id}");

        $response->assertStatus(200);
    }

    public function test_employee_cannot_view_others_payment(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $otherPayment = PaymentRequest::factory()->create();

        Sanctum::actingAs($employee);

        $response = $this->getJson("/api/payments/{$otherPayment->id}");

        $response->assertStatus(403);
    }

    public function test_finance_can_view_any_users_payment(): void
    {
        $financeUser = User::factory()->create(['role' => 'finance']);
        $employeePayment = PaymentRequest::factory()->create();

        Sanctum::actingAs($financeUser);

        $response = $this->getJson("/api/payments/{$employeePayment->id}");

        $response->assertStatus(200);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  create — Any authenticated user can create
    // ═══════════════════════════════════════════════════════════════════

    // (Covered in PaymentRequestControllerTest — both employee and finance can create)

    // ═══════════════════════════════════════════════════════════════════
    //  approve — Only finance
    // ═══════════════════════════════════════════════════════════════════

    public function test_finance_can_access_approve_endpoint(): void
    {
        $financeUser = User::factory()->create(['role' => 'finance']);
        $payment = PaymentRequest::factory()->create();

        Sanctum::actingAs($financeUser);

        $response = $this->patchJson("/api/payments/{$payment->id}/approve");

        // Should not be 403 (it's either 200 or 500 depending on state)
        $this->assertNotEquals(403, $response->status());
    }

    public function test_employee_is_denied_approve_endpoint(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $payment = PaymentRequest::factory()->create();

        Sanctum::actingAs($employee);

        $response = $this->patchJson("/api/payments/{$payment->id}/approve");

        $response->assertStatus(403);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  reject — Only finance
    // ═══════════════════════════════════════════════════════════════════

    public function test_finance_can_access_reject_endpoint(): void
    {
        $financeUser = User::factory()->create(['role' => 'finance']);
        $payment = PaymentRequest::factory()->create();

        Sanctum::actingAs($financeUser);

        $response = $this->patchJson("/api/payments/{$payment->id}/reject", [
            'rejection_reason' => 'Over budget',
        ]);

        // Should not be 403
        $this->assertNotEquals(403, $response->status());
    }

    public function test_employee_is_denied_reject_endpoint(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $payment = PaymentRequest::factory()->create();

        Sanctum::actingAs($employee);

        $response = $this->patchJson("/api/payments/{$payment->id}/reject", [
            'rejection_reason' => 'Trying to reject',
        ]);

        $response->assertStatus(403);
    }
}
