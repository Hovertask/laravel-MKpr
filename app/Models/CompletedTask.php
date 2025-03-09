<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompletedTask extends Model
{
    protected $table = 'completed_tasks';
    protected $fillable = ['user_id', 'task_id', 'screenshot', 'instagram_url', 'twitter_url', 'tiktok_url', 'youtube_url', 'twitch_url', 'discord_url', 'website_url', 'facebook_url', 'telegram_url', 'other_url', 'status'];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * The user who completed this task.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
