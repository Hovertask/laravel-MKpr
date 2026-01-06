<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Repository\Admin\AdminOrderRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminOrderController extends Controller
{
    protected $orderRepo;

    public function __construct(AdminOrderRepository $orderRepo)
    {
        $this->middleware(['auth:sanctum', 'role:superadministrator']);
        $this->orderRepo = $orderRepo;
    }

    public function index()
    {
        $orders = $this->orderRepo->getAllOrders();
        return response()->json($orders);
    }

    public function show($id)
    {
        $order = $this->orderRepo->getOrderById($id);
        return response()->json($order);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|string',
            'total_amount' => 'sometimes|numeric',
            'isReviewed' => 'sometimes|boolean',
            'isOutForDelivery' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $order = $this->orderRepo->updateOrder($id, $request->all());
        return response()->json($order);
    }

    public function destroy($id)
    {
        $this->orderRepo->deleteOrder($id);
        return response()->json(['message' => 'Order deleted']);
    }

    public function getByStatus($status)
    {
        $orders = $this->orderRepo->getOrdersByStatus($status);
        return response()->json($orders);
    }

    public function getByUser($userId)
    {
        $orders = $this->orderRepo->getOrdersByUser($userId);
        return response()->json($orders);
    }
}