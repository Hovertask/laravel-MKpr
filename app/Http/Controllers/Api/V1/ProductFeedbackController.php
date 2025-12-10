<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\StoreFeedbackRequest;
use App\Repository\ProductFeedbackRepository;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\RateLimiter;

class ProductFeedbackController extends Controller
{
    public function __construct(
        private ProductFeedbackRepository $repo
    ) {}

    public function store(StoreFeedbackRequest $request, $productId): JsonResponse
    {
        $key = 'feedback:' . ($request->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'message' => 'Too many feedback attempts. Try again later.'
            ], 429);
        }

        RateLimiter::hit($key, 60); // 1 minute decay

        $data = $request->validated();

        $data['product_id'] = $productId;
        $data['user_id'] = Auth::id();

        $feedback = $repo->create($data);

        return response()->json([
            'message' => 'Feedback submitted successfully!',
            'data' => $feedback
        ]);
    }

     /**
     * List feedback for a product
     */
    public function list(Request $request, int $productId): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 5); // optional, default 5 per page
        $page = (int) $request->query('page', 1);

        $feedback = $this->repo->getByProductId($productId, $perPage, $page);

        return response()->json([
            'data' => $feedback->items(),
            'current_page' => $feedback->currentPage(),
            'last_page' => $feedback->lastPage(),
            'per_page' => $feedback->perPage(),
            'total' => $feedback->total(),
        ]);
    }
}
