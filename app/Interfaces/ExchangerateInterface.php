<?php

namespace App\Interfaces;

interface ExchangerateInterface
{
    public function fetchApiData();
    public function getExchangeRate(string $from): float;
    public function convert(float $amount, string $from): float;
}
