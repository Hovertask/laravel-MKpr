<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\StoreFeedbackRequest;
use Illuminate\Http\Request;
use App\Repository\ProductFeedbackRepository;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductFeedbackController extends Controller
{
    public function __construct(
        private ProductFeedbackRepository $repo
    ) {}

    /**
     * Store feedback for a product
     */
    public function store(StoreFeedbackRequest $request, $productId): JsonResponse
    {
        try {
            $key = 'feedback:' . $request->ip();

            if (RateLimiter::tooManyAttempts($key, 5)) {
                return response()->json([
                    'message' => 'Too many feedback attempts. Try again later.'
                ], 429);
            }

            RateLimiter::hit($key, 60);

            $data = $request->validated();
            $data['product_id'] = $productId;
            $data['user_id'] = Auth::id();

            // FIXED: Use $this->repo — not $repo
            $feedback = $this->repo->create($data);

            return response()->json([
                'message' => 'Feedback submitted successfully!',
                'data' => $feedback
            ]);
        } catch (Throwable $e) {
            // Log full error details for debugging
            Log::error('Feedback store failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'product_id' => $productId,
                'user_id' => Auth::id(),
                'payload' => $request->all(),
            ]);

            return response()->json([
                'message' => 'An error occurred while submitting feedback.'
            ], 500);
        }
    }

    /**
     * List feedback for a product
     */
    public function list(Request $request, int $productId): JsonResponse
    {
        try {
            $perPage = (int) $request->query('per_page', 5);
            $page = (int) $request->query('page', 1);

            $feedback = $this->repo->getByProductId($productId, $perPage, $page);

            return response()->json([
                'data' => $feedback->items(),
                'current_page' => $feedback->currentPage(),
                'last_page' => $feedback->lastPage(),
                'per_page' => $feedback->perPage(),
                'total' => $feedback->total(),
            ]);
        } catch (Throwable $e) {
            Log::error('Feedback list failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'product_id' => $productId,
                'query' => $request->query(),
            ]);

            return response()->json([
                'message' => 'An error occurred while fetching feedback.'
            ], 500);
        }
    }
}
