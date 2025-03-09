<?php

namespace App\Repository;

use App\Models\Product;

interface ITrendingProductRepository
{    
    public function getTrendingProducts($limit = 10);
    public function incrementSalesCount($product);
    public function incrementViewCount($product);
}
