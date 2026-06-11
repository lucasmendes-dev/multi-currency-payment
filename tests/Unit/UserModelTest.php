<?php

namespace Tests\Unit;

use App\Enums\UserRoleEnum;
use App\Models\PaymentRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_is_created_with_fillable_attributes(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'country' => 'Brazil',
            'local_currency' => 'BRL',
        ]);

        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertEquals('Brazil', $user->country);
        $this->assertEquals('BRL', $user->local_currency);
    }

    public function test_password_is_hashed_via_cast(): void
    {
        $user = User::factory()->create([
            'password' => 'PlainText123',
        ]);

        $this->assertNotEquals('PlainText123', $user->password);
        $this->assertTrue(password_verify('PlainText123', $user->password));
    }

    public function test_role_is_cast_to_enum(): void
    {
        $user = User::factory()->create(['role' => 'finance']);

        $this->assertInstanceOf(UserRoleEnum::class, $user->role);
        $this->assertSame(UserRoleEnum::FINANCE, $user->role);
    }

    public function test_default_role_is_employee(): void
    {
        $user = User::factory()->create();

        $this->assertSame(UserRoleEnum::EMPLOYEE, $user->role);
    }

    public function test_is_finance_returns_true_for_finance_user(): void
    {
        $user = User::factory()->create(['role' => 'finance']);

        $this->assertTrue($user->isFinance());
    }

    public function test_is_finance_returns_false_for_employee(): void
    {
        $user = User::factory()->create(['role' => 'employee']);

        $this->assertFalse($user->isFinance());
    }

    public function test_user_has_many_payment_requests(): void
    {
        $user = User::factory()->create();
        PaymentRequest::factory()->count(3)->create(['user_id' => $user->id]);

        $this->assertCount(3, $user->paymentRequests);
        $this->assertInstanceOf(PaymentRequest::class, $user->paymentRequests->first());
    }

    public function test_password_is_hidden_in_serialization(): void
    {
        $user = User::factory()->create();
        $array = $user->toArray();

        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('remember_token', $array);
    }

    public function test_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'unique@example.com']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        User::factory()->create(['email' => 'unique@example.com']);
    }
}
