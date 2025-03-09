<?php
namespace App\Repository;

use App\Models\Product;
use Illuminate\Support\Str;
use App\Models\ResellerLink;
use Illuminate\Support\Facades\URL;
use App\Repository\IProductRepository;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository implements IProductRepository
{
    public function create(array $data): Product
    {
        return Product::create($data);
    }

    public function update($id, array $data): Product
    {
        $product = Product::findOrFail($id);
        $product->update($data);
        return $product;
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }

    public function showAll(): Collection
    {
        return Product::all();
    }

    public function show(?int $productId, ?string $resellerId = null)
    {
        $product = Product::where('id', $productId)->firstOrFail();
        if (!is_null($resellerId)) {
            $product->reseller = $resellerId;
        }

        return $product;
    }

    // public function submitProduct(array $product, $id): Product
    // {
    //     return Product::findOrFail($id)->update($product);
    // }

    public function approveProduct($id, $status)
    {
        $product = Product::findOrFail($id);
        if (!$product) {
            return null;
        }

        $product->update(['status' => 'price product1']);
        return $product;
        
    }  

    public function resellerLink($id): array
    {
        $product = Product::findOrFail($id);
        $resellerIdentifier = generateUniqueLink();
        $commission = 10.0;

        ResellerLink::create([
            'user_id' => auth()->user()->id,
            'product_id' => $product->id,
            'unique_link' => $resellerIdentifier,
            'commission_rate' => $commission,
        ]);

        $resellerUrl = URL::route('product.show', [
            'id' => $product->id,
            'reseller' => $resellerIdentifier,
        ]);

        return [
            'product' => $product,
            'reseller_url' => $resellerUrl,
        ];
    }

    public function findResellerLink($productId, $resellerIdentifier): ?ResellerLink
    {
        return ResellerLink::where('unique_link', $resellerIdentifier)
            ->where('product_id', $productId)
            ->first();
    }

    public function productByLocation($location)
    {
        return Product::where('location', $location)->get();
    }
  
}