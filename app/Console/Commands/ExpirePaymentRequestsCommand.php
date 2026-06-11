<?php

namespace App\Console\Commands;

use App\Enums\PaymentStatusEnum;
use App\Models\PaymentRequest;
use Illuminate\Console\Command;

class ExpirePaymentRequestsCommand extends Command
{
    protected $signature = 'payments:expire';

    protected $description = 'Expire pending payment requests after 48 hours';

    public function handle(): int
    {
        $expiredCount = PaymentRequest::query()
            ->where('status', PaymentStatusEnum::PENDING)
            ->where('expires_at', '<=', now())
            ->update([
                'status' => PaymentStatusEnum::EXPIRED,
            ]);

        $this->info("{$expiredCount} payment requests expired.");

        return self::SUCCESS;
    }
}
