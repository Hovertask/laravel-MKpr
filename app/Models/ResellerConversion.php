<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResellerConversion extends Model
{
    protected $fillable = [
        'product_id',
        'reseller_code',
        'visitor_cookie',
        'ip',
        'user_agent',
    ];
}
