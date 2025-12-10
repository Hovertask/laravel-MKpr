<?php

namespace App\Repository;

use App\Models\ProductFeedback;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\LengthAwarePaginator;


class ProductFeedbackRepository
{
    public function create(array $data): ProductFeedback
    {
        return ProductFeedback::create($data);
    }

     /**
     * Get feedback for a product with pagination and caching
     */
    public function getByProductId(int $productId, int $perPage = 5, int $page = 1): LengthAwarePaginator
    {
        $cacheKey = "product_feedback_{$productId}_page_{$page}";

        return Cache::remember(
            $cacheKey,
            now()->addMinutes(10),
            function () use ($productId, $perPage) {
                return ProductFeedback::where('product_id', $productId)
                    ->with('user:id,fname,lname,avatar')
                    ->latest()
                    ->paginate($perPage);
            }
        );
    }
}
