<?php

namespace App\Repository;

use App\Models\Wallet;
use App\Models\Task;
use App\Models\CompletedTask;
use App\Models\User;
use App\Models\Product;
use App\Models\order;
use App\Models\Cart;
use App\Models\ResellerLink;

class DashboardRepository implements IDashboardRepository
{
    public function getDashboardData() {
        $wallet = Wallet::where('user_id', auth()->id())->first();
        $tasks = Task::where('user_id', auth()->id())->get();
        $completedTasks = CompletedTask::where('user_id', auth()->id())->get();
        $orders = Order::where('user_id', auth()->id())->get();
        $resellers = ResellerLink::where('user_id', auth()->id())->get();
        $products = Product::where('user_id', auth()->id())->get(); 
        $totalCompletedTasks = count($completedTasks);
        $totalOrders = count($orders); 
        return compact('wallet', 'tasks', 'totalCompletedTasks', 'totalOrders', 'resellers', 'products');
    }

    public function getUserData()
{
    $user = User::withCount(['advertise', 'tasks'])
        ->where('id', auth()->id())
        ->first();

    return $user;
}

}