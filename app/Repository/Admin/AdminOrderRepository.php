<?php

namespace App\Repository\Admin;

use App\Models\Order;
use App\Repository\Admin\IAdminOrderRepository;

class AdminOrderRepository implements IAdminOrderRepository
{
    public function getAllOrders()
    {
        return Order::with(['user', 'products'])->paginate(20);
    }

    public function getOrderById($id)
    {
        return Order::with(['user', 'products', 'orderItems'])->findOrFail($id);
    }

    public function updateOrder($id, array $data)
    {
        $order = Order::findOrFail($id);
        $order->update($data);
        return $order;
    }

    public function deleteOrder($id)
    {
        $order = Order::findOrFail($id);
        $order->delete();
        return true;
    }

    public function getOrdersByStatus($status)
    {
        return Order::where('status', $status)->with(['user', 'products'])->paginate(20);
    }

    public function getOrdersByUser($userId)
    {
        return Order::where('user_id', $userId)->with(['user', 'products'])->paginate(20);
    }
}