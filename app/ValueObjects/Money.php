<?php

namespace App\ValueObjects;

use InvalidArgumentException;
use JsonSerializable;

class Money implements JsonSerializable
{
    private float $amount;
    private string $currency;

    public function __construct(float $amount, string $currency)
    {
        $this->amount = round($amount, 2);
        $this->currency = strtoupper($currency);
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function add(Money $other): Money
    {
        $this->assertSameCurrency($other);
        return new self($this->amount + $other->getAmount(), $this->currency);
    }

    public function subtract(Money $other): Money
    {
        $this->assertSameCurrency($other);
        return new self($this->amount - $other->getAmount(), $this->currency);
    }

    public function multiply(float $multiplier): Money
    {
        return new self($this->amount * $multiplier, $this->currency);
    }

    public function format(): string
    {
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }

    public function equals(Money $other): bool
    {
        return $this->amount === $other->getAmount() && $this->currency === $other->getCurrency();
    }

    public function jsonSerialize(): float
    {
        return $this->amount;
    }

    public function __toString(): string
    {
        return $this->format();
    }

    private function assertSameCurrency(Money $other): void
    {
        if ($this->currency !== $other->getCurrency()) {
            throw new InvalidArgumentException('Currencies must match for this operation.');
        }
    }
}
