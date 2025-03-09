<?php

namespace App\Repository;

use App\Models\TrendingProduct;
use Illuminate\Support\Facades\DB;
use App\Repository\ITrendingProductRepository;

class TrendingProductRepository implements ITrendingProductRepository
{
    public function getTrendingProducts($limit = 10)
    {
        return TrendingProduct::with('product')
            ->orderByDesc('sales')
            ->orderByDesc('views')
            ->limit($limit)
            ->get();
    }

    public function incrementSalesCount($product, $quantity)
    {
        $trendingProduct = TrendingProduct::where('product_id', $product->id)->first();

        if(!$trendingProduct) {
            TrendingProduct::create([
                'product_id' => $product->id,                    
                'views' => 0,
                'sales' => 1,
            ]);
        } else {
            $trendingProduct->increment('sales');
        }
    }

    public function incrementViewCount($productId)
    {
        $trendingProduct = TrendingProduct::where('product_id', $productId)->first();

        if (!$trendingProduct) {
            TrendingProduct::create([
                'product_id' => $productId,
                'views' => 1,
                'sales' => 0,
            ]);
        } else {
            $trendingProduct->increment('views');
        }
    }

}
