<?php
namespace App\Repository;

use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;

interface IOrderRepository
{
   public function getCartItem(int $userId);
   public function createOrder(int $userId, float $totalAmount): Order;
   public function createOrderItems(Order $order, Collection $cartItems): void;
}