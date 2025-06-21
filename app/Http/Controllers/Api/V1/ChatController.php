<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Repository\IChatRepository;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

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
       // dd($request->all());
        $validate = Validator::make($request->all(), [
            'recipient_id' => 'required|exists:users,id',
            'product_id' => 'required|exists:products,id',
            'content' => 'required|string'
        ]);
        // $data = $request->validate([
        //     'recipient_id' => 'required|exists:users,id',
        //     'content' => 'required|string'
        // ]);

        if(!$validate->passes()) {
            return response()->json([
                'errors' => $validate->errors()
            ], 422);
        }

        $data = $request->all();
       
        //dd($data);
        $conversation = $this->chatRepository->findOrCreateConversation(
            $request->user()->id,
            $data['recipient_id'],
            $data['product_id']
        );

        $message = $this->chatRepository->sendMessage([
            'conversation_id' => $conversation->id,
            'user_id' => $request->user()->id,
            'content' => $data['content'],
            'product_id' => $data['product_id']
        ]);

        return response()->json([
            'message' => $message,
            'conversation' => $conversation
        ], 201);
    }
}
