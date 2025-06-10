<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'price',
        'category_id',
        'stock',
        'currency',
        'discount',
        'payment_method',
        'meet_up_preference',
        'delivery_fee',
        'estimated_delivery_date',
        'phone_number',
        'email',
        'social_media_link',
        'image',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_items')->withPivot('quantity', 'price');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function trending()
    {
        return $this->hasMany(TrendingProduct::class);
    }
    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function carts()
    {
        return $this->hasMany(Cart::class);
    }

    public function productImages()
    {
        return $this->hasMany(ProductImages::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function participants()
    {
        return $this->hasMany(Participant::class);
    }   
}
