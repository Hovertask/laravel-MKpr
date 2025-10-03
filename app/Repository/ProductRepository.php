<?php
namespace App\Repository;

use App\Models\Product;
//use Cloudinary\Cloudinary;
use Illuminate\Support\Str;
use App\Models\ResellerLink;
use Illuminate\Http\Request;
use App\Models\ProductImages;
use App\Services\FileUploadService;
use Illuminate\Support\Facades\URL;
use App\Repository\IProductRepository;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Collection;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;


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

        // $uploadedFile = Cloudinary::upload($data['file_path']->getRealPath(), [
        //     'folder' => 'lambogini',  // Optional: specify a folder in Cloudinary
        //     'public_id' => 'prosper',  // Optional: specify the public ID for the file
        // ]);
        
        // // Get the secure URL of the uploaded file
        // $filePath = $uploadedFile->getSecurePath();
        // dd($filePath);

        if ($request->hasFile('file_path')) {
            //dd($request->file('file_path'));
            $files = $request->file('file_path');
        
            // Normalize to array (even if it's one file)
            $files = is_array($files) ? $files : [$files];
        
            foreach ($files as $file) {
                if ($file->isValid()) {
                    $uploadedFile = Cloudinary::upload($file->getRealPath(), [
                        'folder' => 'product_images'
                    ]);
        
                    $product->productImages()->create([
                        'file_path' => $uploadedFile->getSecurePath(),
                        'public_id' => $uploadedFile->getPublicId()
                    ]);
                }
            }
        }
        
        
        if ($request->hasFile('video_path')) {
            $videos = $request->file('video_path');
            $videos = is_array($videos) ? $videos : [$videos];
        
            foreach ($videos as $video) {
                if ($video->isValid()) {
                    $uploadedFile = Cloudinary::upload($video->getRealPath(), [
                        'folder' => 'product_videos',
                        'resource_type' => 'video'
                    ]);
        
                    $product->productImages()->create([
                        'video_path' => $uploadedFile->getSecurePath(),
                        'public_id' => $uploadedFile->getPublicId()
                    ]);
                }
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

        if ($request->hasFile('file_path')) {
            //dd($request->file('file_path'));
            $files = $request->file('file_path');
        
            // Normalize to array (even if it's one file)
            $files = is_array($files) ? $files : [$files];
        
            foreach ($files as $file) {
                if ($file->isValid()) {
                    $uploadedFile = Cloudinary::upload($file->getRealPath(), [
                        'folder' => 'product_images'
                    ]);
        
                    $product->productImages()->updateorCreate([
                        'product_id' => $product->id,
                    ],
                        [
                        'file_path' => $uploadedFile->getSecurePath(),
                        'public_id' => $uploadedFile->getPublicId()
                    ]);
                }
            }
        }
        
        
        if ($request->hasFile('video_path')) {
            $videos = $request->file('video_path');
            $videos = is_array($videos) ? $videos : [$videos];
        
            foreach ($videos as $video) {
                if ($video->isValid()) {
                    $uploadedFile = Cloudinary::upload($video->getRealPath(), [
                        'folder' => 'product_videos',
                        'resource_type' => 'video'
                    ]);
        
                    $product->productImages()->updareorCreate([
                        'product_id' => $product->id
                    ],
                    [
                        'video_path' => $uploadedFile->getSecurePath(),
                        'public_id' => $uploadedFile->getPublicId()
                    ]);
                }
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
        return Product::with('productImages')->latest()->get();	
    }


    public function show(?int $productId, ?string $resellerId = null)
{
    $product = Product::with('productImages')->where('id', $productId)->firstOrFail();

    if (!is_null($resellerId)) {
        $product->reseller = $resellerId;
    }

    return $product;
}


    public function authUserProducts(){
        return Product::where('user_id', auth()->user()->id)->latest()->get();
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
    $userId = auth()->id();

    // Check if link already exists for this user + product
    $existingLink = ResellerLink::where('user_id', $userId)
        ->where('product_id', $product->id)
        ->first();

    if ($existingLink) {
        $resellerIdentifier = $existingLink->unique_link;
        $commission = $existingLink->commission_rate;
    } else {
        // Generate only if it doesn’t exist
        $resellerIdentifier = $this->generateUniqueLink(); 
        $commission = 10.0;

        $existingLink = ResellerLink::create([
            'user_id' => $userId,
            'product_id' => $product->id,
            'unique_link' => $resellerIdentifier,
            'commission_rate' => $commission,
        ]);
    }

    // Generate URL with reseller parameter
    $resellerUrl = URL::route('product.show', [
        'id' => $product->id,
        'reseller' => $resellerIdentifier,
    ]);

    return [
        'product' => $product,
        'reseller_url' => $resellerUrl,
    ];
}


/**
 * Generate unique reseller code
 */
private function generateUniqueLink(): string
{
    return substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 10);
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

    public function contactSeller($id)
    {
        $product = Product::with('user')->findOrFail($id);
    
        return $product;
    }
  
}