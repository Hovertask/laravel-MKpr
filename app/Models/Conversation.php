<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{

    use HasFactory;

    protected $fillable = [];

    public function participants()
    {
        return $this->hasMany(Participant::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    // Helper method to get the other participant
    public function otherParticipant($userId)
    {
        return $this->participants()->where('user_id', '!=', $userId)->first()->user;
    }
}
