<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Product;
use App\Models\User;

class ResellerConversion extends Model
{
    protected $fillable = [
        'product_id',
        'reseller_id',
        'reseller_code',
        'visitor_cookie',
        'ip',
        'user_agent',
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function reseller()
    {
        return $this->belongsTo(User::class, 'reseller_id');
    }
}
