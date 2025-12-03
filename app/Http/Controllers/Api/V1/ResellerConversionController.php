<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Repository\ResellerConversionRepository;
use App\Models\Product;
use App\Models\ResellerLink; // <-- REQUIRED for DB validation

class ResellerConversionController extends Controller
{
    protected $repo;

    public function __construct(ResellerConversionRepository $repo)
    {
        $this->repo = $repo;
    }

    public function track(Request $request, $productId)
{
    $resellerCodeRaw = trim((string) $request->get('reseller'));

    // -----------------------------------------
    // 1. NO RESELLER → JUST RETURN WHATSAPP
    // -----------------------------------------
    if ($resellerCodeRaw === '' || in_array(strtolower($resellerCodeRaw), ['null', 'undefined', 'false'])) {
        Log::info('Conversion: no reseller provided', [
            'product_id' => $productId,
            'ip' => $request->ip()
        ]);

        return $this->sendWhatsAppResponse($productId);
    }

    // -----------------------------------------
    // 2. FORMAT VALIDATION (SQL Injection protection)
    // -----------------------------------------
    if (!preg_match('/^[A-Za-z0-9_-]{2,50}$/', $resellerCodeRaw)) {
        Log::warning('Conversion: reseller failed format check', [
            'reseller' => $resellerCodeRaw
        ]);

        return $this->sendWhatsAppResponse($productId);
    }

    $resellerCode = $resellerCodeRaw;

    // -----------------------------------------
    // 3. VALIDATE RESELLER IN DATABASE
    // -----------------------------------------
    $resellerRecord = ResellerLink::where('unique_link', $resellerCode)->first();

    if (!$resellerRecord) {
        Log::warning('Conversion: reseller NOT FOUND', [
            'reseller' => $resellerCode
        ]);

        return $this->sendWhatsAppResponse($productId);
    }

    // -----------------------------------------
    // 4. GET PRODUCT + CHECK BUDGET
    // -----------------------------------------
    $product = Product::lockForUpdate()->find($productId);  // prevents race condition
    
    if (!$product) {
        return $this->sendWhatsAppResponse($productId);
    }

    // If product does NOT have ₦500 → DO NOT track conversion
    if ($product->resell_budget < 500) {

        Log::info("Conversion skipped — insufficient budget", [
            'product_id' => $productId,
            'budget_remaining' => $product->resell_budget
        ]);

        return $this->sendWhatsAppResponse($productId);
    }

    // -----------------------------------------
    // 5. COOKIE + prevent duplicates
    // -----------------------------------------
    $cookieName = "conversion_" . $resellerCode;
    $visitor = $request->cookie($cookieName) ?? uniqid('v_', true);

    if (!$this->repo->exists($resellerCode, $visitor)) {

        // -----------------------------------------
        // 6. DEDUCT ₦500 (safe + atomic)
        // -----------------------------------------
        $product->resell_budget -= 500;
        $product->save();

        Log::info("Budget deducted from product", [
            "product_id" => $product->id,
            "new_budget" => $product->resell_budget
        ]);

        // -----------------------------------------
        // 7. STORE CONVERSION
        // -----------------------------------------
        $this->repo->create([
            'product_id'     => $productId,
            'reseller_id'    => $resellerRecord->user_id,
            'reseller_code'  => $resellerCode,
            'visitor_cookie' => $visitor,
            'ip'             => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);
    }

    $cookie = cookie($cookieName, $visitor, 60 * 24 * 365 * 5);

    return $this->sendWhatsAppResponse($productId)->cookie($cookie);
}


    // -----------------------------------------
    // CLEAN METHOD: ALWAYS RETURNS WHATSAPP URL
    // -----------------------------------------
    private function sendWhatsAppResponse($productId)
    {
        $product = Product::findOrFail($productId);

        $sellerPhone = preg_replace('/\D/', '', $product->phone_number);

        if (str_starts_with($sellerPhone, '0')) {
            $sellerPhone = '234' . substr($sellerPhone, 1);
        }

        $url = "https://app.hovertask.com/marketplace/p/{$product->id}";

        $msg = urlencode(
            "Hello, I'm interested in your product: {$product->name}\n\n" .
            "Price: NGN " . number_format($product->price, 2) . "\n" .
            "Preview: {$url}\n\n" .
            "Please share more information."
        );

        return response()->json([
            'message' => 'ready',
            'whatsapp_url' => "https://wa.me/{$sellerPhone}?text={$msg}"
        ]);
    }


    public function getConversionsForReseller(Request $request)
    {
        $reseller = $request->user();

        $conversions = $this->repo->getConversionsForReseller($reseller->id);

        return response()->json([
            'status' => 'success',
            'data' => $conversions
        ]);
    }
}
