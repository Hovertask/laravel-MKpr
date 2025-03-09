<?php

namespace App\Providers;

use App\Services\PaymentService;
use App\Repository\CartRepository;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;
use App\Repository\ICartRepository;
use App\Repository\ITaskRepository;
use App\Repository\IUserRepository;
use App\Repository\OrderRepository;
use App\Services\FileUploadService;
use App\Repository\FollowRepository;
use App\Repository\IOrderRepository;
use App\Repository\ReviewRepository;
use App\Repository\WalletRepository;
use App\Repository\IFollowRepository;
use App\Repository\IReviewRepository;
use App\Repository\IWalletRepository;
use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use App\Repository\IProductRepository;
use App\Repository\WishlistRepository;
use App\Repository\ICategoryRepository;
use App\Repository\IWishlistRepository;
use Illuminate\Support\ServiceProvider;
use App\Repository\TrendingProductRepository;
use App\Repository\ITrendingProductRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(IUserRepository::class, UserRepository::class);
        $this->app->bind(ITaskRepository::class, TaskRepository::class);
        $this->app->bind(IProductRepository::class, ProductRepository::class);
        $this->app->bind(ICategoryRepository::class, CategoryRepository::class);
        $this->app->bind(IWishlistRepository::class, WishlistRepository::class);
        $this->app->bind(ICartRepository::class, CartRepository::class);
        $this->app->bind(IWalletRepository::class, WalletRepository::class);
        $this->app->bind(IOrderRepository::class, OrderRepository::class);
        $this->app->bind(ITrendingProductRepository::class, TrendingProductRepository::class);
        $this->app->bind(IFollowRepository::class, FollowRepository::class);


        $this->app->bind(IReviewRepository::class, ReviewRepository::class);
        $this->app->bind(PaymentService::class, function ($app) {
            return new PaymentService();
        });
        $this->app->bind(FileUploadService::class, function ($app) {
            return new FileUploadService();
        });
        
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
