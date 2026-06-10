<?php

namespace App\Services;

use App\Interfaces\ExchangerateInterface;
use App\ValueObjects\Money;
use Exception;
use Illuminate\Support\Facades\Http;

class ExchangeRateApiService implements ExchangerateInterface
{
    private string $apiUrl;
    private string $apiKey;
    private string $defaultSystemCurrency;

    public function __construct()
    {
        $this->apiKey = config('app.exchange_rate_api_token');
        $this->defaultSystemCurrency = config('app.default_convertion_currency');

        $this->apiUrl = sprintf(
            'https://v6.exchangerate-api.com/v6/%s/latest/%s',
            $this->apiKey,
            $this->defaultSystemCurrency
        );
    }

    public function fetchApiData()
    {
        $response = Http::timeout(10)->get($this->apiUrl);

        if (!$response->successful()) {
            throw new Exception('Failed to retrieve exchange rates.');
        }

        return $response->json();
    }

    public function getExchangeRate(string $from): float
    {
        $from = strtoupper($from);
        $to = $this->defaultSystemCurrency;

        if ($from === $to) {
            return 1.0;
        }

        $apiData = $this->fetchApiData();
        $rates = $apiData['conversion_rates'] ?? [];

        if (empty($rates)) {
            throw new Exception('Exchange rates not found.');
        }

        // If the source currency is the base currency (EUR)
        if ($from === $this->defaultSystemCurrency) {
            return $rates[$to]
                ?? throw new Exception("Currency {$to} not found.");
        }

        // If the destination currency is the base currency (EUR)
        if ($to === $this->defaultSystemCurrency) {
            return 1 / (
                $rates[$from]
                ?? throw new Exception("Currency {$from} not found.")
            );
        }

        // If currency pair not found
        if (!isset($rates[$from]) || !isset($rates[$to])) {
            throw new Exception("Currency pair {$from}/{$to} not found.");
        }

        return $rates[$to] / $rates[$from];
    }

    public function convert(Money $money): Money
    {
        $rate = $this->getExchangeRate($money->getCurrency());
        return new Money($money->getAmount() * $rate, $this->defaultSystemCurrency);
    }
}
