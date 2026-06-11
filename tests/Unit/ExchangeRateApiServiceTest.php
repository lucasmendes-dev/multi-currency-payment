<?php

namespace Tests\Unit;

use App\Interfaces\ExchangerateInterface;
use App\Services\ExchangeRateApiService;
use App\ValueObjects\Money;
use Exception;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExchangeRateApiServiceTest extends TestCase
{
    private ExchangeRateApiService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Set config values for the service
        config([
            'app.exchange_rate_api_token' => 'test-api-key',
            'app.default_convertion_currency' => 'EUR',
            'app.exchange_rate_source' => 'https://v6.exchangerate-api.com',
        ]);

        $this->service = new ExchangeRateApiService();
    }

    // ── Interface Contract ──────────────────────────────────────────────

    public function test_service_implements_exchangerate_interface(): void
    {
        $this->assertInstanceOf(ExchangerateInterface::class, $this->service);
    }

    // ── getExchangeRate ─────────────────────────────────────────────────

    public function test_get_exchange_rate_returns_1_when_currencies_are_the_same(): void
    {
        // No HTTP call should be made when source == target
        Http::fake();

        $rate = $this->service->getExchangeRate('EUR');

        $this->assertEquals(1.0, $rate);

        Http::assertNothingSent();
    }

    public function test_get_exchange_rate_returns_inverse_for_local_to_eur(): void
    {
        Http::fake([
            'v6.exchangerate-api.com/*' => Http::response([
                'result' => 'success',
                'conversion_rates' => [
                    'EUR' => 1,
                    'BRL' => 5.5,
                    'USD' => 1.1,
                ],
            ], 200),
        ]);

        // BRL → EUR: the service returns 1 / rates[BRL] since EUR is the base and target
        $rate = $this->service->getExchangeRate('BRL');

        $this->assertEqualsWithDelta(1 / 5.5, $rate, 0.00001);
    }

    public function test_get_exchange_rate_handles_uppercase_conversion(): void
    {
        Http::fake([
            'v6.exchangerate-api.com/*' => Http::response([
                'result' => 'success',
                'conversion_rates' => [
                    'EUR' => 1,
                    'USD' => 1.1,
                ],
            ], 200),
        ]);

        // Passing lowercase should still work
        $rate = $this->service->getExchangeRate('usd');

        $this->assertEqualsWithDelta(1 / 1.1, $rate, 0.00001);
    }

    public function test_get_exchange_rate_throws_when_api_fails(): void
    {
        Http::fake([
            'v6.exchangerate-api.com/*' => Http::response([], 500),
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to retrieve exchange rates.');

        $this->service->getExchangeRate('BRL');
    }

    public function test_get_exchange_rate_throws_when_currency_not_found(): void
    {
        Http::fake([
            'v6.exchangerate-api.com/*' => Http::response([
                'result' => 'success',
                'conversion_rates' => [
                    'EUR' => 1,
                    'USD' => 1.1,
                ],
            ], 200),
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Currency XYZ not found.');

        $this->service->getExchangeRate('XYZ');
    }

    public function test_get_exchange_rate_throws_when_rates_are_empty(): void
    {
        Http::fake([
            'v6.exchangerate-api.com/*' => Http::response([
                'result' => 'success',
                'conversion_rates' => [],
            ], 200),
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Exchange rates not found.');

        $this->service->getExchangeRate('BRL');
    }

    // ── convert ─────────────────────────────────────────────────────────

    public function test_convert_returns_money_in_target_currency(): void
    {
        Http::fake([
            'v6.exchangerate-api.com/*' => Http::response([
                'result' => 'success',
                'conversion_rates' => [
                    'EUR' => 1,
                    'BRL' => 5.5,
                ],
            ], 200),
        ]);

        $localMoney = new Money(550.00, 'BRL');
        $converted = $this->service->convert($localMoney);

        $this->assertEquals('EUR', $converted->getCurrency());
        // 550 * (1/5.5) = 100
        $this->assertEqualsWithDelta(100.00, $converted->getAmount(), 0.01);
    }

    public function test_convert_same_currency_returns_same_amount(): void
    {
        Http::fake();

        $money = new Money(200.00, 'EUR');
        $converted = $this->service->convert($money);

        $this->assertEquals('EUR', $converted->getCurrency());
        $this->assertEquals(200.00, $converted->getAmount());
    }

    // ── fetchApiData ────────────────────────────────────────────────────

    public function test_fetch_api_data_returns_json_on_success(): void
    {
        $expectedData = [
            'result' => 'success',
            'conversion_rates' => ['EUR' => 1, 'USD' => 1.1],
        ];

        Http::fake([
            'v6.exchangerate-api.com/*' => Http::response($expectedData, 200),
        ]);

        $data = $this->service->fetchApiData();

        $this->assertEquals($expectedData, $data);
    }

    public function test_fetch_api_data_throws_on_failure(): void
    {
        Http::fake([
            'v6.exchangerate-api.com/*' => Http::response([], 503),
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to retrieve exchange rates.');

        $this->service->fetchApiData();
    }
}
