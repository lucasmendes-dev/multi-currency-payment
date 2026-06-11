<?php

namespace App\Http\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PaymentRequestFilter
{
    public function __construct(protected Request $request) {}

    public function apply(Builder $query): Builder
    {
        return $query
            ->when(
                $this->request->filled('user_id'),
                fn (Builder $q) =>
                    $q->where('user_id', $this->request->user_id)
            )
            ->when(
                $this->request->filled('local_currency'),
                fn (Builder $q) =>
                    $q->where('local_currency', $this->request->local_currency)
            )
            ->when(
                $this->request->filled('status'),
                fn (Builder $q) =>
                    $q->where('status', $this->request->status)
            );
    }
}
