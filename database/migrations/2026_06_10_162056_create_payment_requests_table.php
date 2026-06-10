<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->decimal('local_amount', 10, 2);
            $table->string('local_currency');

            $table->decimal('exchange_rate', 10, 2);
            $table->string('exchange_rate_source');
            $table->timestamp('exchange_rate_fetched_at');

            $table->decimal('eur_amount', 10, 2);

            $table->string('description');

            $table->enum('status', ['pending', 'approved', 'rejected', 'expired']);

            $table->foreignId('approved_by')->nullable()->constrained('users')->cascadeOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->foreignId('rejected_by')->nullable()->constrained('users')->cascadeOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->string('rejection_reason')->nullable();

            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index('status');
            $table->index('user_id');
            $table->index(['user_id','status']);
            $table->index(['status', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_requests');
    }
};
