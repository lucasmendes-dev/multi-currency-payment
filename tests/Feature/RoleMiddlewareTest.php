<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_user_passes_finance_middleware(): void
    {
        $financeUser = User::factory()->create(['role' => 'finance']);
        Sanctum::actingAs($financeUser);

        // The approve endpoint uses the finance middleware
        // We don't care about the payment result, just that middleware doesn't block
        $response = $this->patchJson('/api/payments/1/approve');

        // Should NOT be 403 from middleware (could be 404 because payment doesn't exist)
        $this->assertNotEquals(403, $response->status());
    }

    public function test_employee_is_blocked_by_finance_middleware(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        Sanctum::actingAs($employee);

        $response = $this->patchJson('/api/payments/1/approve');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_gets_401_before_finance_middleware(): void
    {
        $response = $this->patchJson('/api/payments/1/approve');

        $response->assertStatus(401);
    }

    public function test_finance_middleware_on_reject_route_allows_finance(): void
    {
        $financeUser = User::factory()->create(['role' => 'finance']);
        Sanctum::actingAs($financeUser);

        $response = $this->patchJson('/api/payments/1/reject', [
            'rejection_reason' => 'Test',
        ]);

        $this->assertNotEquals(403, $response->status());
    }

    public function test_finance_middleware_on_reject_route_blocks_employee(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        Sanctum::actingAs($employee);

        $response = $this->patchJson('/api/payments/1/reject', [
            'rejection_reason' => 'Test',
        ]);

        $response->assertStatus(403);
    }
}
