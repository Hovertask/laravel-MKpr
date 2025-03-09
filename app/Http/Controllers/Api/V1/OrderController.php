<?php

namespace App\Http\Controllers\Api\V1;

use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\PaymentService;
use Illuminate\Support\Facades\DB;
use App\Repository\OrderRepository;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    protected $orderRepository;
    protected $paymentService;

    public function __construct(OrderRepository $orderRepository, PaymentService $paymentService)
    {
        $this->orderRepository = $orderRepository;
        $this->paymentService = $paymentService;
    }

    public function createOrder(Request $request)
    {
        $userId = Auth::id();

        // Fetch cart items
        $cartItems = Cart::where('user_id', $userId)
            ->where('status', 'pending')
            ->with('product')
            ->get(); // This returns an Eloquent Collection

        // Calculate total amount
        $totalAmount = $cartItems->sum(function ($item) {
            return $item->quantity * $item->product->price;
        });

        // Create the order
        $order = $this->orderRepository->createOrder($userId, $totalAmount);

        // Create order items
        $this->orderRepository->createOrderItems($order, $cartItems);

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully.',
            'data' => $order,
        ]);
    }

    public function pay(Request $request)
    {
        $userId = Auth::id();

        // Validate the request data
        // $request->validate([
        //     'email' => 'required|email',
        //     'amount' => 'required|numeric|min:100',
        //     'metadata' => 'nullable|array',
        // ]);


        DB::beginTransaction();
        try {
            
            //$metadata = $request->input('metadata', []);

            // Fetch cart items
            $cartItems = $this->orderRepository->getCartItem($userId);

            if ($cartItems->isEmpty()) {
                throw new Exception("Cart is empty.");
            }

            // Calculate total amount
            $totalAmount = $cartItems->sum(fn($item) => $item->product->price * $item->quantity);

            // Create order
            $order = $this->orderRepository->createOrder($userId, $totalAmount);

            // Create order items
            $this->orderRepository->createOrderItems($order, $cartItems);
            $user = User::findOrFail($userId);
            $authorizationUrl = $this->paymentService->initializePayment(
                $user->email,
                $totalAmount,
                ['user_id' => $userId, 'order_id' => $order->id]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'authorization_url' => $authorizationUrl,
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function verify($reference)
    {
        try {
            // Verify the payment
            $responseData = $this->paymentService->verifyPayment($reference);

            return response()->json([
                'success' => true,
                'message' => 'Payment verified successfully.',
                'data' => $responseData,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}