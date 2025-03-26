<?php
namespace App\Repository;

use App\Models\Product;
use Illuminate\Support\Str;
use App\Models\ResellerLink;
use Illuminate\Http\Request;
use App\Models\ProductImages;
use App\Services\FileUploadService;
use Illuminate\Support\Facades\URL;
use App\Repository\IProductRepository;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository implements IProductRepository
{
    protected $fileUploadService;

    // Inject FileUploadService in the constructor
    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }
    
    public function create(array $data, Request $request): Product
    {
        // Create the product
        $product = Product::create([
            'name' => $data['name'],
            'user_id' => $data['user_id'],
            'category_id' => $data['category_id'],
            'description' => $data['description'],
            'stock' => $data['stock'],
            'price' => $data['price'],
            'currency' => $data['currency'],
            'discount' => $data['discount'],
            'payment_method' => $data['payment_method'],
            'meet_up_preference' => $data['meet_up_preference'],
            'delivery_fee' => $data['delivery_fee'],
            'estimated_delivery_date' => $data['estimated_delivery_date'],
            'phone_number' => $data['phone_number'],
            'email' => $data['email'],
            'social_media_link' => $data['social_media_link'],
        ]);

        // Handle single file upload
        if ($request->hasFile('file_path')) {
            $image = $request->file('file_path');
            $path = $image->store('product_images', 'public');
            $product->productImages()->create(['file_path' => $path]);
        }

        // Handle multiple file uploads
        if ($request->hasFile('file_paths')) {
            foreach ($request->file('file_paths') as $image) {
                $path = $image->store('product_images', 'public');
                $product->productImages()->create(['file_path' => $path]);
            }
        }

        return $product;
    }

    public function update(array $data, Request $request, int $id): Product
    {
        $product = Product::findOrFail($id);

        // Update the product details
        $product->update([
            'name' => $data['name'],
            'user_id' => $data['user_id'],
            'category_id' => $data['category_id'],
            'description' => $data['description'],
            'stock' => $data['stock'],
            'price' => $data['price'],
            'currency' => $data['currency'],
            'discount' => $data['discount'],
            'payment_method' => $data['payment_method'],
            'meet_up_preference' => $data['meet_up_preference'],
            'delivery_fee' => $data['delivery_fee'],
            'estimated_delivery_date' => $data['estimated_delivery_date'],
            'phone_number' => $data['phone_number'],
            'email' => $data['email'],
            'social_media_link' => $data['social_media_link'],
        ]);

        // Handle single file upload
        if ($request->hasFile('file_path')) {
            $image = $request->file('file_path');
            $path = $image->store('product_images', 'public');
            $product->productImages()->updateOrCreate(['file_path' => $path]);
        }

        // Handle multiple file uploads
        if ($request->hasFile('file_paths')) {
            foreach ($request->file('file_paths') as $image) {
                $path = $image->store('product_images', 'public');
                $product->productImages()->updateOrCreate(['file_path' => $path]);
            }
        }

        return $product;
    }

    public function delete(int $id): bool
    {
        $product = Product::findOrFail($id);
        foreach ($product->productImages as $image) {
            Storage::disk('public')->delete($image->file_path);
            $image->delete();
        }
        $product->delete();

        return true;
    }

    public function showAll(): Collection
    {
        return Product::with('productImages')->get();	
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