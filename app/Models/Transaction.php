<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{

    use HasFactory;
    protected $table = 'transactions';
    protected $fillable = [
        'user_id',
        'amount',
        'type',
        'status',
        'description',
        'reference',
        'category',
        'payment_source'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Helper to resolve transaction type

    public static function resolveTransactionType($type)
    {
    return $type === 'deposit' ? 'credit' : 'debit';
    }


}
