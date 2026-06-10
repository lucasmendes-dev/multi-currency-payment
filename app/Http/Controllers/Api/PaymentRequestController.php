<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Models\PaymentRequest;
use App\Services\PaymentRequestService;
use Illuminate\Http\JsonResponse;

class PaymentRequestController extends Controller
{
    public function __construct(private PaymentRequestService $paymentRequestService) {}

    public function index(): JsonResponse
    {
        $payments = PaymentRequest::all();

        return response()->json([
            'data' => $payments,
        ]);
    }

    public function store(StorePaymentRequest $request)
    {
        $validatedInputData = $request->validated();
        $paymentRequest = $this->paymentRequestService->createPaymentRequest($validatedInputData, $request);

        return response()->json([
            'message' => 'Payment request created successfully',
            'data' => $paymentRequest,
        ], 201);
    }
}
