<?php

namespace App\Repository;

use App\Models\Cart;
use App\Models\Product;
use App\Repository\ICartRepository;

class CartRepository implements ICartRepository
{public function addToCart(Product $product, int $userId, int $quantity = 1)
    {
        try {
            $cart = Cart::updateOrCreate(
                [
                    'user_id' => $userId,
                    'product_id' => $product->id,
                ],
                [
                    'quantity' => $quantity,
                    'updated_at' => now(),
                ]
            );

            return $cart;
        } catch (Exception $e) {
            throw new Exception("Failed to add product to cart: " . $e->getMessage());
        }
    }
    public function removeFromCart(Product $product, int $userId)
    {
        try {
            $deleted = Cart::where('user_id', $userId)
                            ->where('product_id', $product->id)
                            ->delete();

            return $deleted > 0; // Return true if something was deleted
        } catch (Exception $e) {
            throw new Exception("Failed to remove product from cart: " . $e->getMessage());
        }
    }
}