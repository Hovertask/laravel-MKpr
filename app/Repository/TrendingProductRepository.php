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

    public function incrementSalesCount($productId, $quantity)
    {
        $trendingProduct = TrendingProduct::firstOrNew(['product_id' => $productId]);

        if (!$trendingProduct->exists) {
            $trendingProduct->views = 0;
            $trendingProduct->sales = $quantity;
        } else {
            $trendingProduct->sales += $quantity;
        }

        $trendingProduct->save();
    }


    // public function incrementSalesCount($product, $quantity)
    // {
    //     $trendingProduct = TrendingProduct::where('product_id', $product->id)->first();

    //     if(!$trendingProduct) {
    //         TrendingProduct::create([
    //             'product_id' => $product->id,                    
    //             'views' => 0,
    //             'sales' => $quantity,
    //         ]);
    //     } else {
    //         $trendingProduct->increment('sales');
    //     }
    // }

    public function incrementViewCount($productId)
    {
        $trendingProduct = TrendingProduct::firstOrCreate(
            ['product_id' => $productId],
            ['views' => 0, 'sales' => 0]
        );

        $trendingProduct->increment('views');
    }


    // public function incrementViewCount($productId)
    // {
    //     $trendingProduct = TrendingProduct::where('product_id', $productId)->first();

    //     if (!$trendingProduct) {
    //         TrendingProduct::create([
    //             'product_id' => $productId,
    //             'views' => 1,
    //             'sales' => 0,
    //         ]);
    //     } else {
    //         $trendingProduct->increment('views');
    //     }
    // }

}
