<?php

namespace App\Repository\Admin;

interface IAdminOrderRepository
{
    public function getAllOrders();
    public function getOrderById($id);
    public function updateOrder($id, array $data);
    public function deleteOrder($id);
    public function getOrdersByStatus($status);
    public function getOrdersByUser($userId);
}