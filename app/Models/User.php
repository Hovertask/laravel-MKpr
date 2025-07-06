<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Task;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Laratrust\Traits\HasRolesAndPermissions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRolesAndPermissions, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'fname',
        'lname',
        'username',
        'phone',
        'how_you_want_to_use',
        'country',
        'currency',
        'balance',
        'referred_by',
        'account_status',
        'referral_code',
        'avatar',
        'email',
        'password',
        'is_member',
    ];

    public function task(){
        return $this->hasMany(Task::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
    public function carts()
    {
        return $this->hasMany(Cart::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }
    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function fundrecord()
    {
        return $this->hasMany(FundsRecord::class);
    }

    public function withdrawal()
    {
        return $this->hasMany(Withdrawal::class);
    }

    // Users this user is following
    public function following()
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'following_id');
    }

    // Users following this user
    public function followers()
    {
        return $this->belongsToMany(User::class, 'follows', 'following_id', 'follower_id');
    }
    
    // Check if a user is following another user
    public function isFollowing(User $user)
    {
        return $this->following()->where('following_id', $user->id)->exists();
    }

    public function contactLists()
    {
        return $this->hasMany(ContactList::class, 'user_id');
    }
    
    public function addedContactLists()
    {
        return $this->hasMany(ContactList::class, 'added_user_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function advertise()
    {
        return $this->hasMany(Advertise::class);
    }

    public function manualSocialAccountLinkings()
    {
        return $this->hasOne(ManualSocialAccountLinking::class);
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
