<?php

namespace App\Services;

use App\Enums\PaymentStatusEnum;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Resources\PaymentRequestResource;
use App\Interfaces\ExchangerateInterface;
use App\Models\PaymentRequest;

class PaymentRequestService
{
    public function __construct(
        private ExchangerateInterface $exchangeRateService
    ) {}

    public function createPaymentRequest(array $data, StorePaymentRequest $request): PaymentRequestResource
    {
        $data['user_id'] = $request->user()->id;
        $data['target_currency'] = config('app.default_convertion_currency');

        $data['exchange_rate'] = $this->exchangeRateService->getExchangeRate($data['local_currency']);
        $data['exchange_rate_source'] = config('app.exchange_rate_source');
        $data['exchange_rate_fetched_at'] = now();

        $data['converted_amount'] = $this->exchangeRateService->convert($data['local_amount'], $data['local_currency']);
        $data['status'] = PaymentStatusEnum::PENDING->value;
        $data['expires_at'] = now()->addHours(48);

        $paymentRequest = PaymentRequest::create($data);
        return new PaymentRequestResource($paymentRequest);
    }
}
