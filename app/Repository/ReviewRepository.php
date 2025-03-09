<?php
namespace App\Repository;

use App\Models\Order;
use App\Models\Review;
use App\Repository\IReviewRepository;

class ReviewRepository implements IReviewRepository
{
    public function createReview(array $data)
    {
        $order = Order::where('user_id', $data['user_id'])
            ->whereHas('items', function ($query) use ($data) {
                $query->where('product_id', $data['product_id']);
            })
            ->where('status', 'paid')
            ->where('isReviewed', 0)
            ->first();

        if (!$order) {
            return ['error' => 'You can only review products you have purchased.'];
        }
        $review = Review::create($data);
        $order->update(['isReviewed' => 1]);

        return $review;
    }

    public function getReviewsByProduct($productId)
    {
        return Review::where('product_id', $productId)->with('user')->get();
    }

    public function getUserReview($userId, $productId)
    {
        return Review::where('user_id', $userId)->where('product_id', $productId)->first();
    }
}