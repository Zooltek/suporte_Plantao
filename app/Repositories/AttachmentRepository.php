<?php

namespace App\Repositories;

use App\Contracts\Repositories\AttachmentRepositoryInterface;
use App\Models\Ticket\Attachment;
use Illuminate\Database\Eloquent\Collection;

class AttachmentRepository implements AttachmentRepositoryInterface
{
    public function create(array $data): Attachment
    {
        return Attachment::create($data);
    }

    public function delete(Attachment $attachment): void
    {
        $attachment->delete();
    }

    public function findById(int $id): ?Attachment
    {
        return Attachment::find($id);
    }

    public function findByTicket(int $ticketId): Collection
    {
        return Attachment::where('ticket_id', $ticketId)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
