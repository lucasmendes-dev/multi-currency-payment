<?php

namespace App\Services;

use App\Enums\PaymentStatusEnum;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Resources\PaymentRequestResource;
use App\Interfaces\ExchangerateInterface;
use App\Models\PaymentRequest;
use App\ValueObjects\Money;

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

        $localMoney = new Money((float)$data['local_amount'], $data['local_currency']);
        $convertedMoney = $this->exchangeRateService->convert($localMoney);

        $data['local_amount'] = $localMoney;
        $data['converted_amount'] = $convertedMoney;

        $data['status'] = PaymentStatusEnum::PENDING->value;
        $data['expires_at'] = now()->addHours(48);

        $paymentRequest = PaymentRequest::create($data);
        return new PaymentRequestResource($paymentRequest);
    }
}
