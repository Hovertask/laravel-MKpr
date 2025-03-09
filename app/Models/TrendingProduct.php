<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrendingProduct extends Model
{
    protected $table = 'trending_products';
    protected $fillable = ['product_id', 'views', 'sales'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
