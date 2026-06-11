<?php

namespace App\Policies;

use App\Models\PaymentRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PaymentRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, PaymentRequest $paymentRequest): Response
    {
        if ($user->isFinance()) {
            return Response::allow();
        }

        return $paymentRequest->user_id === $user->id ? Response::allow() : Response::deny('You are not authorized to view this payment request.');
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function approve(User $user): bool
    {
        return $user->isFinance();
    }

    public function reject(User $user): bool
    {
        return $user->isFinance();
    }
}
