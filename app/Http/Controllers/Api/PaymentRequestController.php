<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Models\PaymentRequest;
use Illuminate\Http\JsonResponse;

class PaymentRequestController extends Controller
{
    public function index(): JsonResponse
    {
        $payments = PaymentRequest::all();

        return response()->json([
            'data' => $payments,
        ]);
    }

    public function store(StorePaymentRequest $request)
    {
        $validatedData = $request->validated();

        // $payment = $paymentRequestService->handlePaymentRequest($validatedData);

        // return response()->json([
        //     'data' => $payment,
        // ], 201);
    }
}
