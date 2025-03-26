<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacebookUser extends Model
{

    use HasFactory;
    protected $fillable = ['facebook_id', 'name', 'followers_count', 'post_count'];

    public function posts()
    {
        return $this->hasMany(FacebookPost::class);
    }
}
