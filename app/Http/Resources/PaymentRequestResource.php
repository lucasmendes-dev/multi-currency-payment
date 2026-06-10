<?php

namespace App\Http\Resources;

use App\ValueObjects\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'local_currency' => $this->local_currency,
            'local_amount' => $this->local_amount instanceof Money ? $this->local_amount->getAmount() : $this->local_amount,
            'target_currency' => $this->target_currency,
            'converted_amount' => $this->converted_amount instanceof Money ? $this->converted_amount->getAmount() : $this->converted_amount,
            'exchange_rate' => $this->exchange_rate,
            'exchange_rate_source' => $this->exchange_rate_source,
            'exchange_rate_fetched_at' => $this->exchange_rate_fetched_at,
            'description' => $this->description,
            'status' => $this->status,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at,
            'rejected_by' => $this->rejected_by,
            'rejected_at' => $this->rejected_at,
            'expires_at' => $this->expires_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
