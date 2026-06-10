<?php

namespace App\Interfaces;

use App\ValueObjects\Money;

interface ExchangerateInterface
{
    public function fetchApiData();
    public function getExchangeRate(string $from): float;
    public function convert(Money $money): Money;
}
