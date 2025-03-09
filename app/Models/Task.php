<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $table = 'tasks';
    protected $fillable = ['user_id', 'title', 'description', 'platforms', 'task_amount', 'task_type', 'task_count_total', 'task_count_remaining', 'priority', 'start_date', 'due_date', 'gender', 'location', 'no_of_participants', 'payment_per_task', 'religion', 'social_media_url', 'type_of_comment', 'status', 'completed'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
