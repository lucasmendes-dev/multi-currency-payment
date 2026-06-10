<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\PaymentStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentRequest extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'local_currency',
        'local_amount',
        'target_currency',
        'converted_amount',
        'exchange_rate',
        'exchange_rate_source',
        'exchange_rate_fetched_at',
        'description',
        'status',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatusEnum::class,
            'local_amount' => MoneyCast::class,
            'converted_amount' => MoneyCast::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }
}
