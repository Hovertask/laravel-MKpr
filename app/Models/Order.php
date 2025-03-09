<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';

    protected $fillable = [
        'user_id',
        'status',
        'total_amount',
        'isReviewed',
        'isOutForDelivery',

    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'order_items')->withPivot('quantity', 'price');
    }

    // public function orderItems()
    // {
    //     return $this->hasMany(OrderItem::class);
    // } 
    
    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }
}
