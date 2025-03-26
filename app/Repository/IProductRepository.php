<?php

namespace App\Repository;

use App\Models\Product;
use App\Models\ResellerLink;
use Illuminate\Http\Request;

interface IProductRepository
{
    public function create(array $data, Request $request): Product;
    public function update(array $data, Request $request, int $id): Product;
    public function elete(int $id): bool;
    public function showAll();
    public function show(?int $productId, ?string $resellerId = null);
    //public function submitProduct(array $product, $id);
    public function approveProduct($id, $status);
    public function resellerLink($id);
    public function productByLocation($location);
    public function findResellerLink($productId, $resellerIdentifier): ?ResellerLink;
}