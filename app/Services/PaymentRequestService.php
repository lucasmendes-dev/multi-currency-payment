<?php

namespace App\Services;

use App\Enums\PaymentStatusEnum;
use App\Http\Requests\StorePaymentRequest;
use App\Interfaces\ExchangerateInterface;
use App\Models\PaymentRequest;
use App\ValueObjects\Money;
use Illuminate\Http\Request;

class PaymentRequestService
{
    public function __construct(
        private ExchangerateInterface $exchangeRateService
    ) {}

    public function createPaymentRequest(array $data, StorePaymentRequest $request): PaymentRequest
    {
        $data['user_id'] = $request->user()->id;
        $data['target_currency'] = config('app.default_convertion_currency');

        $data['exchange_rate'] = $this->exchangeRateService->getExchangeRate($data['local_currency']);
        $data['exchange_rate_source'] = config('app.exchange_rate_source');
        $data['exchange_rate_fetched_at'] = now();

        $localMoney = new Money((float)$data['local_amount'], $data['local_currency']);
        $convertedMoney = $this->exchangeRateService->convert($localMoney);

        $data['local_amount'] = $localMoney;
        $data['converted_amount'] = $convertedMoney;

        $data['status'] = PaymentStatusEnum::PENDING->value;
        $data['expires_at'] = now()->addHours(48);

        return PaymentRequest::create($data);
    }

    public function approvePaymentRequest(int $paymentId): PaymentRequest
    {
        $paymentRequest = PaymentRequest::findOrFail($paymentId);

        if ($paymentRequest->status->value !== PaymentStatusEnum::PENDING->value) {
            throw new \Exception("Payment request should be in 'pending' state to be approved. Current state is: '{$paymentRequest->status->value}'");
        }

        $paymentRequest->status = PaymentStatusEnum::APPROVED->value;

        $paymentRequest->approved_at = now();
        $paymentRequest->approved_by = auth()->user()->id;

        $paymentRequest->save();

        return $paymentRequest;
    }

    public function rejectPaymentRequest(int $paymentId, string $rejectionReason): PaymentRequest
    {
        $paymentRequest = PaymentRequest::findOrFail($paymentId);

        if ($paymentRequest->status->value !== PaymentStatusEnum::PENDING->value) {
            throw new \Exception("Payment request should be in 'pending' state to be rejected. Current state is: '{$paymentRequest->status->value}'");
        }

        $paymentRequest->status = PaymentStatusEnum::REJECTED->value;

        $paymentRequest->rejected_at = now();
        $paymentRequest->rejected_by = auth()->user()->id;
        $paymentRequest->rejection_reason = $rejectionReason;
        $paymentRequest->save();

        return $paymentRequest;
    }
}
