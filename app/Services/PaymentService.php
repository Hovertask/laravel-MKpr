<?php
namespace App\Services;

use DB;
use Exception;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Repository\ITrendingProductRepository;

class PaymentService
{
    protected $paystackSecretKey;

    public function __construct()
    {
        $this->paystackSecretKey = config('services.paystack.secret_key');
    }

    public function initializePayment($email, $amount, $metadata = [])
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->paystackSecretKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.paystack.co/transaction/initialize', [
            'email' => $email,
            'amount' => $amount * 100,
            'metadata' => $metadata,
        ]);

        $responseData = $response->json();

        if (!$response->successful() || !$responseData['status']) {
            throw new Exception("Failed to initialize payment: " . ($responseData['message'] ?? 'Unknown error'));
        }

        return $responseData['data']['authorization_url'];
    }

       /**
     * Verify payment for a product.
     *
     * @param string $reference The payment reference from Paystack.
     * @return array The verified payment data.
     * @throws Exception If payment verification fails or payment is not successful.
     */
    public function verifyPayment(string $reference, ITrendingProductRepository $trendingRepository)
    {
        DB::beginTransaction();
        try {
            // Verify payment with Paystack
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->paystackSecretKey,
                'Content-Type' => 'application/json',
            ])->get("https://api.paystack.co/transaction/verify/{$reference}");

            $responseData = $response->json();

            if (!$response->successful() || !$responseData['status']) {
                throw new Exception("Failed to verify payment: " . ($responseData['message'] ?? 'Unknown error'));
            }
            if ($responseData['data']['status'] !== 'success') {
                throw new Exception("Payment not successful: " . $responseData['data']['gateway_response']);
            }

            // Extract metadata
            $metadata = $responseData['data']['metadata'];
            $userId = $metadata['user_id'];
            $orderId = $metadata['order_id'] ?? null;

            // Update the order status to 'paid' if order_id is present
            if ($orderId) {
                $order = Order::find($orderId);
                if ($order) {
                    $order->update(['status' => 'paid']);

                    // bullshit the cart
                    Cart::where('user_id', $userId)->where('status', 'pending')->delete();

                    // Update the stock lets get a clean record for HoverTask users😘
                    foreach ($order->items as $item) {
                        $product = Product::find($item->product_id);
                        if ($product) {
                            $product->stock -= $item->quantity;
                            $product->save();

                            $trendingRepository->incrementViewCount($product->id, $item->quantity);
                        }
                    }
                }
            }

            DB::commit();

            return $responseData;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Payment verification failed: " . $e->getMessage());
            throw new Exception("Failed to verify payment: " . $e->getMessage());
        }
    }
    
}