<?php

namespace App\Http\Controllers\Api\V1;

use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\PaymentService;
use App\Models\InitializeDeposit;
use Illuminate\Support\Facades\DB;
use App\Repository\OrderRepository;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Repository\TrendingProductRepository;
use App\Repository\ITrendingProductRepository;

class OrderController extends Controller
{
    protected $orderRepository;
    protected $paymentService;
    protected $trendingRepository;


    public function __construct(OrderRepository $orderRepository, PaymentService $paymentService, TrendingProductRepository $trendingRepository)
    {
        $this->orderRepository = $orderRepository;
        $this->paymentService = $paymentService;
        $this->trendingRepository = $trendingRepository;
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

            //dd($request->all());
            
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

            $transaction = InitializeDeposit::create([
                'user_id' => $userId,
                'reference' => $authorizationUrl['data']['reference'],
                'amount' => $totalAmount,
                'status' => 'pending',
                'trx' => InitializeDeposit::generateTrx(10),
            ]);


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
            if (InitializeDeposit::where('reference', $reference)->where('status', 'successful')->exists()) {
                throw new Exception("Transaction already processed.");
            }else{
            $responseData = $this->paymentService->verifyPayment($reference, $this->trendingRepository);

            InitializeDeposit::where('reference', $reference)->update([
                'status' => 'successful',
                'reference' => $responseData['data']['reference'],
                'token' => $responseData['data']['authorization']['authorization_code'],
                'method' => $responseData['data']['channel'],
                'currency' => $responseData['data']['currency'],
                'amount' => $responseData['data']['amount'],
            ]);
        

            return response()->json([
                'success' => true,
                'message' => 'Payment verified successfully.',
                'data' => $responseData,
            ]);
        }

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}