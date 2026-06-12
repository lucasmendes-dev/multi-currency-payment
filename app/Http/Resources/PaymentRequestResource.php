<?php

namespace App\Http\Resources;

use App\Models\User;
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
            'approved_by' => User::where('id', $this->approved_by)->value('name'),
            'approved_at' => $this->approved_at,
            'rejected_by' => User::where('id', $this->rejected_by)->value('name'),
            'rejected_at' => $this->rejected_at,
            'rejection_reason' => $this->rejection_reason,
            'expires_at' => $this->expires_at,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'user' => $this->whenLoaded('user', fn () => [
                'name' => $this->user->name,
                'email' => $this->user->email,
                'country' => $this->user->country,
            ]),
        ];
    }
}
