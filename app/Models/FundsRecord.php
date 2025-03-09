<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FundsRecord extends Model
{
    protected $table = 'funds_records';

    protected $fillable = ['user_id', 'task_id', 'product_id', 'earned', 'spent', 'pending', 'status'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
