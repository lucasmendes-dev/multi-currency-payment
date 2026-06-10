<?php

namespace App\Casts;

use App\ValueObjects\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class MoneyCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Money
    {
        if ($value === null) {
            return null;
        }

        $currencyKey = $this->getCurrencyKey($key);
        $currency = $attributes[$currencyKey] ?? $model->getAttribute($currencyKey) ?? 'USD';

        return new Money((float)$value, $currency);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null) {
            return [
                $key => null,
            ];
        }

        $currencyKey = $this->getCurrencyKey($key);

        if ($value instanceof Money) {
            return [
                $key => $value->getAmount(),
                $currencyKey => $value->getCurrency(),
            ];
        }

        $currency = $attributes[$currencyKey] ?? $model->getAttribute($currencyKey) ?? 'USD';

        return [
            $key => (float)$value,
            $currencyKey => $currency,
        ];
    }

    private function getCurrencyKey(string $key): string
    {
        if ($key === 'converted_amount') {
            return 'target_currency';
        }

        return str_replace('_amount', '_currency', $key);
    }
}
