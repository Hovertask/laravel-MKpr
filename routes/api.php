<?php
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\KYCController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\ChatController;
use App\Http\Controllers\Api\V1\TaskController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\V1\FollowController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\WalletController;
use App\Http\Controllers\Api\V1\AddMeUpController;
use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\WishlistController;
use App\Http\Controllers\Api\V1\AdvertiseController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\SocialConnectController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
//Route::post('/send-reset-link', [AuthController::class, 'resetPasswordRequest'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');
Route::get('/roles', [AuthController::class, 'roles']);


Route::get('/reset-password/{token}', [AuthController::class, 'showResetForm']);
Route::post('/password/reset', [AuthController::class, 'resetPasswordPost']);
//Route::get('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');

Route::get('/email/verify', function () {
    return response()->json(['message' => 'Please verify your email address.'], 403);
})->middleware('auth:sanctum')->name('verification.notice');


// ✅ Send verification email after registration
Route::post('/email/resend', function (Request $request) {
    if ($request->user()->hasVerifiedEmail()) {
        return response()->json(['message' => 'Email already verified.'], 200);
    }

    $request->user()->sendEmailVerificationNotification();
    return response()->json(['message' => 'Verification email resent.']);
})->middleware(['auth:sanctum']);

// ✅ Verify email when user clicks the link
Route::get('/email/verify/{id}/{hash}', function (Request $request, $id, $hash) {
    $user = User::find($id);

    if (!$user) {
        return response()->json(['message' => 'User not found.'], 404);
    }

    if (!hash_equals((string) $user->getKey(), (string) $id)) {
        return response()->json(['message' => 'Invalid verification ID.'], 403);
    }

    if (!hash_equals(sha1($user->getEmailForVerification()), $hash)) {
        return response()->json(['message' => 'Invalid verification hash.'], 403);
    }

    if ($user->hasVerifiedEmail()) {
        return response()->json(['message' => 'Email already verified.'], 200);
    }

    $user->markEmailAsVerified();
    event(new Verified($user));

    return response()->json(['message' => 'Email verified successfully.']);
})->middleware(['signed'])->name('verification.verify');



// ✅ Check if user has verified their email
Route::get('/email/check', function (Request $request) {
    return response()->json(['verified' => $request->user()->hasVerifiedEmail()]);
})->middleware(['auth:sanctum']);

Route::get('/wallet/verify-payment/{reference}', [WalletController::class, 'verifyPayment'])->name('wallet.verify');
    Route::get('/payment/verify-payment/{reference}', [OrderController::class, 'verify']);
//Dashboard Routes
Route::prefix('v1')->group(function () {
    
    Route::prefix('dashboard')->middleware('auth:sanctum')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');
        Route::get('/user', [DashboardController::class, 'userData'])->name('user.data');
        Route::post('/change-password', [AuthController::class, 'changePassword'])->name('change.password');
        Route::post('/bank', [AuthController::class, 'banks'])->name('change.bank');
    });
});

//protected routes TASK
Route::prefix('v1')->group(function () {
    Route::prefix('tasks')->middleware('auth:sanctum', 'verified')->group(function () {
        Route::post('/create-task', [TaskController::class, 'createTask'])->name('create.task');
        Route::post('/update-task/{id}', [TaskController::class, 'updateTask'])->name('update.task');
        Route::get('/show-all-task', [TaskController::class, 'showAll'])->name('show.all');
        Route::get('/show-task/{id}', [TaskController::class, 'show'])->name('show.task');
        Route::post('/submit-task/{id}', [TaskController::class, 'submitTask'])->name('submit.task');
        Route::post('/approve-task/{id}', [TaskController::class, 'approveTask'])->name('approve.task');
        Route::post('/approve-completed-task/{id}', [TaskController::class, 'approveCompletedTask'])->name('approve.completed.task');
        Route::get('/pending-task', [TaskController::class, 'pendingTask'])->name('pending.task');
        Route::get('/completed-task', [TaskController::class, 'completedTask'])->name('completed.task');
        Route::get('/rejected-task', [TaskController::class, 'rejectTask'])->name('reject.task');
        Route::get('/task-history', [TaskController::class, 'taskHistory'])->name('task.history');
        Route::delete('/delete-task/{id}', [TaskController::class, 'deleteTask'])->name('delete.task');
    });
});

//product routes
Route::prefix('v1')->group(function () {
    Route::prefix('products')->middleware('auth:sanctum', 'verified')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('product.index');
        Route::post('/create-product', [ProductController::class, 'store'])->name('product.store');
        Route::get('/auth-user-product', [ProductController::class, 'authUserProducts'])->name('product.authUserProduct');
        Route::post('/update-product/{id}', [ProductController::class, 'update'])->name('product.update');
        Route::post('/approve-product/{id}', [ProductController::class, 'approveProduct'])->name('product.approve');
        Route::get('/show-product/{id}', [ProductController::class, 'show'])->name('product.show');
        Route::get('/show-all-product', [ProductController::class, 'showAll'])->name('product.showAll');
        Route::get('/location/{location}', [ProductController::class, 'productByLocation'])->name('product.location');
        //generate link
        Route::post('/reseller-link/{id}', [ProductController::class, 'resellerLink'])->name('product.resellerLink');
        Route::post('/contact-seller/{id}', [ProductController::class, 'contactSeller'])->name('product.contactSeller');
    });

    Route::prefix('wishlists')->middleware('auth:sanctum', 'verified')->group(function () {
        Route::post('/add/{product}', [WishlistController::class, 'add'])->name('wishlist.add');
        Route::delete('/remove/{product}', [WishlistController::class, 'remove'])->name('wishlist.remove');
        Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    });
    Route::prefix('cart')->middleware('auth:sanctum', 'verified')->group(function () {
        Route::post('/add/{product}', [CartController::class, 'addToCart'])->name('cart.add');
        Route::delete('/remove/{product}', [CartController::class, 'removeFromCart'])->name('cart.remove');
        Route::get('/cartitems', [CartController::class, 'index'])->name('cart.index');
    });
    Route::prefix('wallet')->middleware('auth:sanctum')->group(function () {
        Route::post('/initialize-payment', [WalletController::class, 'initializePayment'])->name('wallet.initialize');
        //Route::get('/verify-payment', [WalletController::class, 'verifyPayment'])->name('wallet.verify');
        Route::get('/balance', [WalletController::class, 'getBalance'])->name('wallet.balance');
    });

    Route::prefix('payment')->middleware('auth:sanctum')->group(function () {
        Route::post('/initialize-payment', [OrderController::class, 'pay']);
    });

    Route::prefix('reviews')->middleware('auth:sanctum', 'verified')->group(function () {
        Route::post('/reviews', [ReviewController::class, 'store']);
        Route::get('/reviews/{productId}', [ReviewController::class, 'getReviews']);
    });

    Route::prefix('follow')->middleware('auth:sanctum', 'verified')->group(function () {
        Route::post('/follow/{user}', [FollowController::class, 'follow'])->name('follow');
        Route::post('/unfollow/{user}', [FollowController::class, 'unfollow'])->name('unfollow');
    });

    Route::prefix('socials')->middleware('auth:sanctum', 'verified')->group(function () {
        //Route::get('/facebook-data', [SocialConnectController::class, 'getFacebookData']);
        Route::get('/facebook', [SocialConnectController::class, 'redirectToFacebook']);
        Route::get('/auth/facebook/callback', [SocialConnectController::class, 'handleFacebookCallback']);
        
        // TikTok routes
        Route::get('/auth/tiktok/url', [SocialConnectController::class, 'getTikTokAuthUrl']);
        Route::post('/auth/tiktok/callback', [SocialConnectController::class, 'handleTikTokCallback']);

        Route::get('/auth/tiktok', [SocialConnectController::class, 'connectTikTok'])->name('tiktok.connect');
        Route::get('/auth/tiktok/callback', [SocialConnectController::class, 'tiktokCallback'])->name('tiktok.callback');
        Route::get('/tiktok/profile', [SocialConnectController::class, 'getTikTokProfile'])->name('tiktok.profile');
        Route::get('/tiktok/videos', [SocialConnectController::class, 'getTikTokVideos'])->name('tiktok.videos');
        Route::post('/tiktok/disconnect', [SocialConnectController::class, 'disconnectTikTok'])->name('tiktok.disconnect');
        
    });
    
    //Route::get('/get-product/{id}', [ProductController::class, 'show'])->name('product.show');
    Route::prefix('contact')->middleware('auth:sanctum', 'verified')->group(function () {
        Route::post('/create-contact', [ContactController::class, 'createContact'])->name('contact.create');
        Route::post('/create-group', [ContactController::class, 'createGroup'])->name('group.create');
    });

    Route::prefix('chat')->middleware('auth:sanctum', 'verified')->group(function () {
        Route::get('/conversations', [ChatController::class, 'index']);
        Route::get('/conversations/{recipientId}/messages', [ChatController::class, 'getMessages']);
        Route::post('/messages', [ChatController::class, 'sendMessage']);
    });

    Route::prefix('addmeup')->middleware('auth:sanctum', 'verified')->group(function () {
        Route::get('/all', [AddMeUpController::class, 'index'])->name('addmeup.index');
        Route::post('/create', [AddMeUpController::class, 'create'])->name('addmeup.create');
        Route::get('/mylist', [AddMeUpController::class, 'myList'])->name('addmeup.list');
        Route::post('/listcontact', [AddMeUpController::class, 'listContact'])->name('addmeup.listcontact');
        Route::post('/listgroup', [AddMeUpController::class, 'listGroup'])->name('addmeup.listgroup');
        Route::post('/addmeup/{added_user_id}', [AddMeUpController::class, 'addMeUp'])->name('addmeup.addmeup');

    });

    Route::prefix('advertise')->middleware('auth:sanctum', 'verified')->group(function () {
        Route::get('/all', [AdvertiseController::class, 'index'])->name('advertise.index');
        Route::get('/show/{id}', [AdvertiseController::class, 'show'])->name('advertise.show');
        Route::post('/create', [AdvertiseController::class, 'create'])->name('advertise.create');
        Route::put('/update/{id}', [AdvertiseController::class, 'updateAds'])->name('advertise.update');
        Route::get('/authuserads', [AdvertiseController::class, 'authUserAds'])->name('advertise.authUserAds');
        Route::put('/approveads/{id}', [AdvertiseController::class, 'approveAds'])->name('advertise.approveAds');
        Route::delete('/deleteAds/{id}', [AdvertiseController::class, 'destroy'])->name('advertise.deleteAds');
    });

        Route::prefix('notification')->group(function () {
            Route::get('/notifications', [NotificationController::class, 'index']);
            Route::get('/notifications/{id}', [NotificationController::class, 'show']);
            Route::post('/notifications/read/{id}', [NotificationController::class, 'viewNotification']);
        });


    Route::prefix('kyc')->middleware('auth:sanctum', 'verified')->group(function () {
        Route::post('/create', [KYCController::class, 'submit'])->name('kyc.create');
        Route::get('/show/{id}', [KYCController::class, 'show'])->name('kyc.show');
        Route::put('/update/{id}', [KYCController::class, 'update'])->name('kyc.update');
        Route::put('/approve/{id}', [KYCController::class, 'approve'])->name('kyc.approve');
        Route::put('/reject/{id}', [KYCController::class, 'reject'])->name('kyc.reject');
    });
});




// Route::middleware('auth:sanctum')->prefix('api/v1')->group(function () {
//     Route::get('/products/show-product/{id}', [ProductController::class, 'show']);
// });


//Categories routes
Route::prefix('v1')->group(function () {
    Route::prefix('categories')->middleware('auth:sanctum', 'verified')->group(function () {
        Route::post('/create', [CategoryController::class, 'create'])->name('category.create');
        Route::get('/all-categories', [CategoryController::class, 'index'])->name('category.index');
        //Route::post('/create-product', [CategoryController::class, 'store'])->name('product.store');
    });
});







//product routes

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});