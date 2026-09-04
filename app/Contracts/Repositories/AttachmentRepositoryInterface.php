<?php

namespace App\Contracts\Repositories;

use App\Models\Ticket\Attachment;

interface AttachmentRepositoryInterface
{
    public function create(array $data): Attachment;

    public function delete(Attachment $attachment): void;

    public function findById(int $id): ?Attachment;

    public function findByTicket(int $ticketId): \Illuminate\Database\Eloquent\Collection;
}
