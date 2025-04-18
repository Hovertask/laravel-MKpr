<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactList extends Model
{

    use HasFactory;

    protected $table = 'contact_lists';

    protected $fillable = [
        'user_id',
        'added_user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function addedUser()
    {
        return $this->belongsTo(User::class, 'added_user_id');
    }
}
