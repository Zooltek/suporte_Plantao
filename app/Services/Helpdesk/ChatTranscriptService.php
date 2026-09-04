<?php

namespace App\Services\Helpdesk;

use App\Models\Helpdesk\Chat\Conversation;
use App\Models\Helpdesk\Chat\Message;
use App\Models\Ticket\Ticket;

class ChatTranscriptService
{
    public function mirrorAgentComment(Ticket $ticket, int $userId, string $content): ?Message
    {
        $conversation = Conversation::query()
            ->where('ticket_id', $ticket->id)
            ->where('status_id', '!=', 4)
            ->latest('id')
            ->first();

        if (! $conversation) {
            return null;
        }

        $plainContent = trim(html_entity_decode(strip_tags($content), ENT_QUOTES, 'UTF-8'));

        if ($plainContent === '') {
            return null;
        }

        $message = Message::query()->create([
            'chat_id' => $conversation->id,
            'user_id' => $userId,
            'content' => $plainContent,
        ]);

        $conversation->update([
            'status_id' => 2,
            'expire_at' => now()->addHours(max(1, (int) config('helpdesk.chat.expire_hours', 12))),
        ]);

        return $message;
    }
}
