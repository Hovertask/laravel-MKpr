<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
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
        // Accept reseller from query or body (GET or POST). Use get() which checks both.
        $resellerCode = $request->get('reseller');

        // Explicitly reject empty or whitespace-only reseller codes, and common 'null' placeholders
        $raw = trim((string) $resellerCode);
        if (is_null($resellerCode) || $raw === '' || in_array(strtolower($raw), ['null', 'undefined', 'false'])) {
            Log::info('ResellerConversionController: missing or invalid reseller', ['reseller_raw' => $resellerCode, 'product_id' => $productId, 'ip' => $request->ip()]);
            return response()->json([
                'error' => 'reseller code missing'
            ], 400);
        }

        // Enforce a basic reseller code format (alphanumeric, underscores, dashes, 2-50 chars)
        if (!preg_match('/^[A-Za-z0-9_-]{2,50}$/', $raw)) {
            Log::warning('ResellerConversionController: reseller code failed format check', ['reseller' => $raw, 'product_id' => $productId]);
            return response()->json(['error' => 'invalid reseller code'], 400);
        }

        // Normalized reseller code to use
        $resellerCode = $raw;

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

            Log::info('ResellerConversionController: conversion created', ['reseller' => $resellerCode, 'product_id' => $productId, 'visitor' => $visitorCookie]);
        } else {
            Log::debug('ResellerConversionController: conversion already exists', ['reseller' => $resellerCode, 'visitor' => $visitorCookie]);
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
