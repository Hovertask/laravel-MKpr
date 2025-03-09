<?php

namespace App\Repository;

use App\Models\Product;
use App\Models\ResellerLink;

interface IProductRepository
{
    public function create(array $data);
    public function update($id, array $data);
    public function delete(Product $product);
    public function showAll();
    public function show(?int $productId, ?string $resellerId = null);
    //public function submitProduct(array $product, $id);
    public function approveProduct($id, $status);
    public function resellerLink($id);
    public function productByLocation($location);
    public function findResellerLink($productId, $resellerIdentifier): ?ResellerLink;
}