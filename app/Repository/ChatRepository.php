<?php
namespace App\Repository;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Participant;
use App\Events\NewMessageSent;

class ChatRepository implements IChatRepository
{
    public function getUserConversations($userId)
    {
        return Conversation::whereHas('participants', function($query) use ($userId) {
            $query->where('user_id', $userId);
        })->with(['participants.user', 'messages' => function($q) {
            $q->latest()->limit(1);
        }])->get();
    }

    public function getConversationMessages($conversationId)
    {
        return Message::where('conversation_id', $conversationId)
            ->with('user')
            ->latest()
            ->get();
    }

    public function findOrCreateConversation($user1Id, $user2Id)
    {
        $conversation = Conversation::whereHas('participants', function($q) use ($user1Id) {
            $q->where('user_id', $user1Id);
        })->whereHas('participants', function($q) use ($user2Id) {
            $q->where('user_id', $user2Id);
        })->first();

        if (!$conversation) {
            $conversation = Conversation::create();
            $conversation->participants()->createMany([
                ['user_id' => $user1Id],
                ['user_id' => $user2Id]
            ]);
        }

        return $conversation->load('participants.user');
    }

    public function sendMessage(array $data)
    {
        $message = Message::create([
            'conversation_id' => $data['conversation_id'],
            'user_id' => $data['user_id'],
            'content' => $data['content']
        ]);

        broadcast(new NewMessageSent($message))->toOthers();

        return $message->load('user');
    }
}