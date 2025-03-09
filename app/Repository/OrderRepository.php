<?php

namespace App\Repository;

use App\Models\Cart;
use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;

class OrderRepository implements IOrderRepository
{
    public function getCartItem(int $userId)
    {
        return Cart::where('user_id', $userId)
        ->where('status', 'pending', 0)
        ->with('product')
        ->get();
    }

    public function createOrder(int $userId, float $totalAmount): Order
    {
        return Order::create([
            'user_id' => $userId,
            'total_amount' => $totalAmount,
            'status' => 'pending',
        ]);
    }

    public function createOrderItems(Order $order, Collection $cartItems): void
    {
        foreach ($cartItems as $cartItem) {
            $order->orderItems()->create([
                'order_id' => $order->id,
                'product_id' => $cartItem->product_id,
                'quantity' => $cartItem->quantity,
                'price' => $cartItem->product->price,
            ]);
        }
    }
    
}