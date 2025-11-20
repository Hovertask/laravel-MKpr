<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Repository\ResellerConversionRepository;
use App\Models\Product;

class ResellerConversionController extends Controller
{
    protected $repo;

    public function __construct(ResellerConversionRepository $repo)
    {
        $this->repo = $repo;
    }

    public function track(Request $request, $productId)
    {
        $resellerCode = $request->query('reseller');

        if (!$resellerCode) {
            return response()->json([
                'error' => 'reseller code missing'
            ], 400);
        }

        // Generate or retrieve visitor cookie
        $cookieName = "conversion_" . $resellerCode;
        $visitorCookie = $request->cookie($cookieName);

        if (!$visitorCookie) {
            $visitorCookie = uniqid('v_', true); // create new visitor ID
        }

        // Prevent duplicate conversions
        if (!$this->repo->exists($resellerCode, $visitorCookie)) {

            $this->repo->create([
                'product_id'     => $productId,
                'reseller_code'  => $resellerCode,
                'visitor_cookie' => $visitorCookie,
                'ip'             => $request->ip(),
                'user_agent'     => $request->userAgent(),
            ]);
        }

        // Set cookie for 5 years
        $cookie = cookie(
            $cookieName,
            $visitorCookie,
            60 * 24 * 365 * 5
        );

        // Create WhatsApp URL
        $whatsappUrl = $this->buildWhatsAppLink($productId);

        return response()
            ->json([
                'message' => 'conversion tracked',
                'whatsapp_url' => $whatsappUrl
            ])
            ->cookie($cookie);
    }

    /**
     * Build the WhatsApp redirect link for the seller.
     */
    private function buildWhatsAppLink($productId)
{
    $product = Product::findOrFail($productId);

    // Normalize seller phone
    $sellerPhone = preg_replace('/\D/', '', $product->phone_number);

    if (str_starts_with($sellerPhone, '0')) {
        $sellerPhone = '234' . substr($sellerPhone, 1);
    }

    // Product Preview Link
    $productPreviewUrl = "https://app.hovertask.com/marketplace/p/{$product->id}";

    // WhatsApp Message
    $msg = urlencode(
    "Hello, I'm interested in your product: {$product->name}\n\n" .
    "Product Details:\n" .
    "Price: NGN " . number_format($product->price, 2) . "\n" .
    "Preview Link:\n{$productPreviewUrl}\n\n" .
    "Please give me more information about this item."
);


    return "https://wa.me/{$sellerPhone}?text={$msg}";
}

}
