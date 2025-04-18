<?php

namespace App\Repository;

interface IChatRepository
{
    public function getUserConversations($userId);
    public function getConversationMessages($conversationId);
    public function findOrCreateConversation($user1Id, $user2Id);
    public function sendMessage(array $data);
}