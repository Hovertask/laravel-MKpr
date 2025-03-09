<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'currency',
        'method',
        'trx',
        'approved_by',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
