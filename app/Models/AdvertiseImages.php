<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdvertiseImages extends Model
{

    use HasFactory;
    protected $guarded = [];

    public function advertise()
    {
        return $this->belongsTo(Advertise::class);
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
