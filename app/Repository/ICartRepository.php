<?php

namespace App\Repository;

use App\Models\Cart;
use App\Models\Product;

interface ICartRepository
{
    public function addToCart(Product $product, int $userId, int $quantity = 1);
    public function removeFromCart(Product $product, int $userId);
}