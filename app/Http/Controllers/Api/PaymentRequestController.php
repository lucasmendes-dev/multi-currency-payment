<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Filters\PaymentRequestFilter;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Resources\PaymentRequestResource;
use App\Models\PaymentRequest;
use App\Services\PaymentRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PaymentRequestController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private PaymentRequestService $paymentRequestService) {}

    public function index(PaymentRequestFilter $filter, Request $request): JsonResponse
    {
        $this->authorize('viewAny', PaymentRequest::class);

        $query = $request->user()->isFinance()
            ? PaymentRequest::with('user')
            : PaymentRequest::with('user')->where('user_id', $request->user()->id);

        $paymentRequests = $filter->apply($query)->paginate(10);

        return response()->json([
            'data' => PaymentRequestResource::collection($paymentRequests),
            'meta' => [
                'total' => $paymentRequests->total(),
                'per_page' => $paymentRequests->perPage(),
                'current_page' => $paymentRequests->currentPage(),
                'last_page' => $paymentRequests->lastPage(),
            ]
        ], Response::HTTP_OK);
    }

    public function store(StorePaymentRequest $request): JsonResponse
    {
        $this->authorize('create', PaymentRequest::class);

        $validatedInputData = $request->validated();
        $paymentRequest = $this->paymentRequestService->createPaymentRequest($validatedInputData, $request);

        return response()->json([
            'message' => 'Payment request created successfully.',
            'data' => new PaymentRequestResource($paymentRequest),
        ], Response::HTTP_CREATED);
    }

    public function show(int $paymentId): JsonResponse
    {
        $paymentRequest = PaymentRequest::with('user')->findOrFail($paymentId);

        $this->authorize('view', $paymentRequest);

        return response()->json([
            'data' => new PaymentRequestResource($paymentRequest),
        ], Response::HTTP_OK);
    }

    public function approve(int $paymentId)
    {
        $this->authorize('approve', PaymentRequest::class);

        $paymentRequest = $this->paymentRequestService->approvePaymentRequest($paymentId)->load('user');

        return response()->json([
            'message' => 'Payment request approved successfully.',
            'data' => new PaymentRequestResource($paymentRequest),
        ], Response::HTTP_OK);
    }

    public function reject(int $paymentId, Request $request): JsonResponse
    {
        $this->authorize('reject', PaymentRequest::class);

        $rejectionReason = $request->input('rejection_reason') ?? '';
        $paymentRequest = $this->paymentRequestService->rejectPaymentRequest($paymentId, $rejectionReason)->load('user');

        return response()->json([
            'message' => 'Payment request rejected successfully.',
            'data' => new PaymentRequestResource($paymentRequest),
        ], Response::HTTP_OK);
    }
}
