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
        'payment_source',
        'platform_reference',
        'gateway_reference',
        'parent_reference',
    ];

    protected $casts = [
        'amount' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Transaction belongs to a user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Parent transaction (if this is a distributed credit/debit).
     */
    public function parent()
    {
        return $this->belongsTo(Transaction::class, 'parent_reference', 'platform_reference');
    }

    /**
     * Children transactions (all credits/debits linked to this parent).
     */
    public function children()
    {
        return $this->hasMany(Transaction::class, 'parent_reference', 'platform_reference');
    }

    /**
     * Helper to resolve transaction type.
     */
    public static function resolveTransactionType($type)
    {
        return $type === 'deposit' ? 'credit' : 'debit';
    }
}
