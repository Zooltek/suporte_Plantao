<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\WhatsAppTicketRepositoryInterface;
use App\Models\Company;
use App\Models\Notification as SystemNotification;
use App\Models\Ticket\Attachment;
use App\Models\Ticket\Ticket;
use App\Models\User;

class WhatsAppTicketRepository implements WhatsAppTicketRepositoryInterface
{
    public function saveTicket(Ticket $ticket): void
    {
        $ticket->save();
    }

    public function findUser(int $userId): ?User
    {
        return User::find($userId);
    }

    public function firstAdminUserId(): int
    {
        return (int) (User::where('ticketit_admin', true)
            ->where('active', true)
            ->value('id') ?? 1);
    }

    public function findCompanyIdByName(string $name): ?int
    {
        $id = Company::where('name', 'like', '%' . $name . '%')
            ->orWhere('trade_name', 'like', '%' . $name . '%')
            ->value('id');

        return $id ? (int) $id : null;
    }

    public function createSystemNotification(array $data): SystemNotification
    {
        return SystemNotification::create($data);
    }

    public function createAttachment(array $data): Attachment
    {
        return Attachment::create($data);
    }
}
