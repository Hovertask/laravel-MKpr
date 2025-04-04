<?php

namespace App\Repository;

use App\Models\Product;

interface ITrendingProductRepository
{    
    public function getTrendingProducts($limit = 10);
    public function incrementSalesCount($product, $quantity);
    public function incrementViewCount($product, $quantity);
}
