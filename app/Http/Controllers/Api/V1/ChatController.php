<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repository\IChatRepository;

class ChatController extends Controller
{
    protected $chatRepository;

    public function __construct(IChatRepository $chatRepository)
    {
        $this->chatRepository = $chatRepository;
    }

    public function index(Request $request)
    {
        return response()->json([
            'conversations' => $this->chatRepository->getUserConversations($request->user()->id)
        ]);
    }

    public function getMessages(Request $request, $recipientId)
    {
        $conversation = $this->chatRepository->findOrCreateConversation(
            $request->user()->id,
            $recipientId
        );

        return response()->json([
            'messages' => $this->chatRepository->getConversationMessages($conversation->id),
            'conversation' => $conversation
        ]);
    }

    public function sendMessage(Request $request)
    {
        $data = $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'content' => 'required|string'
        ]);

        $conversation = $this->chatRepository->findOrCreateConversation(
            $request->user()->id,
            $data['recipient_id']
        );

        $message = $this->chatRepository->sendMessage([
            'conversation_id' => $conversation->id,
            'user_id' => $request->user()->id,
            'content' => $data['content']
        ]);

        return response()->json([
            'message' => $message,
            'conversation' => $conversation
        ], 201);
    }
}
