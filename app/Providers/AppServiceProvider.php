<?php

namespace App\Providers;

use SocialConnectRepository;
use App\Services\PaymentService;
use App\Repository\CartRepository;
use App\Repository\ChatRepository;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;
use App\Repository\ICartRepository;
use App\Repository\IChatRepository;
use App\Repository\ITaskRepository;
use App\Repository\IUserRepository;
use App\Repository\OrderRepository;
use App\Services\FileUploadService;
use App\Repository\FollowRepository;
use App\Repository\IOrderRepository;
use App\Repository\ReviewRepository;
use App\Repository\WalletRepository;
use App\Repository\AddMeUpRepository;
use App\Repository\ContactRepository;
use App\Repository\IFollowRepository;
use App\Repository\IReviewRepository;
use App\Repository\IWalletRepository;
use App\Repository\ProductRepository;
use Illuminate\Support\Facades\Event;
use App\Repository\CategoryRepository;
use App\Repository\IAddMeUpRepository;
use App\Repository\IContactRepository;
use App\Repository\IProductRepository;
use App\Repository\WishlistRepository;
use App\Repository\AdvertiseRepository;
use App\Repository\ICategoryRepository;
use App\Repository\IWishlistRepository;
use Illuminate\Support\ServiceProvider;
use App\Repository\IAdvertiseRepository;
use App\Repository\TrendingProductRepository;
use App\Repository\ITrendingProductRepository;
use App\Repositories\Interfaces\ISocialConnectRepository;

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
        $this->app->bind(ISocialConnectRepository::class, SocialConnectRepository::class);
        $this->app->bind(IContactRepository::class, ContactRepository::class);
        $this->app->bind(IChatRepository::class, ChatRepository::class);
        $this->app->bind(IAddMeUpRepository::class, AddMeUpRepository::class);
        $this->app->bind(IAdvertiseRepository::class, AdvertiseRepository::class);



        $this->app->bind(IReviewRepository::class, ReviewRepository::class);
        $this->app->bind(PaymentService::class, function ($app) {
            return new PaymentService();
        });
        $this->app->bind(FileUploadService::class, function ($app) {
            return new FileUploadService();
        });

          // Register Socialite drivers
          $this->app->extend(SocialiteFactory::class, function ($service, $app) {
            $socialite = $service;
            
            // Register TikTok driver
            $socialite->extend('tiktok', function ($app) use ($socialite) {
                $config = $app['config']['services.tiktok'];
                return $socialite->buildProvider(
                    \SocialiteProviders\TikTok\Provider::class,
                    $config
                );
            });
            
            return $socialite;
        });
        
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
            $event->extendSocialite('tiktok', \SocialiteProviders\TikTok\Provider::class);
        });
    }
}
