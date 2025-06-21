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
        })->with(['participants.user', 'participants.product', 'messages' => function($q) {
            $q->latest()->limit(1);
        }])->get();
    }

    public function getConversationMessages($conversationId)
    {
        return Message::where('conversation_id', $conversationId)
            ->with('user', 'product')
            ->latest()
            ->get();
    }

    // public function findOrCreateConversation($user1Id, $user2Id)
    // {
    //     $conversation = Conversation::whereHas('participants', function($q) use ($user1Id) {
    //         $q->where('user_id', $user1Id);
    //     })->whereHas('participants', function($q) use ($user2Id) {
    //         $q->where('user_id', $user2Id);
    //     })->first();

    //     if (!$conversation) {
    //         $conversation = Conversation::create();
    //         $conversation->participants()->createMany([
    //             ['user_id' => $user1Id],
    //             ['user_id' => $user2Id]
    //         ]);
    //     }

    //     return $conversation->load('participants.user');
    // }

    public function findOrCreateConversation($user1Id, $user2Id, $productId = null)
    {
        $query = Conversation::whereHas('participants', function($q) use ($user1Id, $productId) {
            $q->where('user_id', $user1Id);
            if ($productId) {
                $q->where('product_id', $productId);
            } else {
                $q->whereNull('product_id');
            }
        })->whereHas('participants', function($q) use ($user2Id, $productId) {
            $q->where('user_id', $user2Id);
            if ($productId) {
                $q->where('product_id', $productId);
            } else {
                $q->whereNull('product_id');
            }
        });

        $conversation = $query->first();

        if (!$conversation) {
            $conversation = Conversation::create();
            $participants = [
                ['user_id' => $user1Id, 'product_id' => $productId],
                ['user_id' => $user2Id, 'product_id' => $productId]
            ];
            $conversation->participants()->createMany($participants);
        }

        return $conversation->load('participants.user', 'participants.product');
    }

    public function sendMessage(array $data)
    {
        //dd($data);
        $message = Message::create([
            'conversation_id' => $data['conversation_id'],
            'user_id' => $data['user_id'],
            'content' => $data['content'],
            'product_id' => $data['product_id']
        ]);

        broadcast(new NewMessageSent($message))->toOthers();

        return $message->load('user', 'product');
    }
}