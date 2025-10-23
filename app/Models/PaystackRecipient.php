<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaystackRecipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'recipient_code', 'name', 'account_number', 'bank_code', 'meta'
    ];

    protected $casts = [
        'meta' => 'array',
    ];
}
