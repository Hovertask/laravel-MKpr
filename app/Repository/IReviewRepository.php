<?php
namespace App\Repository;

use App\Models\Review;
use App\Models\Product;
use App\Models\User;
use App\Repository\IReviewRepository;

interface IReviewRepository
{
    public function createReview(array $data);
    public function getReviewsByProduct($productId);
    public function getUserReview($userId, $productId);
}