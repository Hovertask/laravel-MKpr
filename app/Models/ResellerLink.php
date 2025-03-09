<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class ResellerLink extends Model
{
    protected $table = 'reseller_links';
    protected $fillable = ['user_id', 'product_id', 'unique_link', 'commission_rate'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    function generateUniqueLink()
    {
        do{
            $uniqueLink = Str::random(20);
        }while(ResellerLink::where('unique_link', $uniqueLink)->exists());

        return $uniqueLink;
    }
    
}
