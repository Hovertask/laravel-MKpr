<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Advertise extends Model
{

    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'platforms' => 'array',
        'religion' => 'array',
        'location' => 'array',
    ];
    

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function advertiseImages()
    {
        return $this->hasMany(AdvertiseImages::class);
    }

    public function completedTasks()
    {
        return $this->hasMany(CompletedTask::class, 'advert_id');
    }

    public function task()
{
    return $this->hasOne(Task::class, 'advert_id');
}


}
