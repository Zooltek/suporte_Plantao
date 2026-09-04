<?php

namespace App\Notifications\Helpdesk\Ticketit;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Helpdesk\Ticketit\Setting;

class TicketUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected $ticket,
        protected $owner,
        protected $subject,
        protected $type,
        protected $extraData = []
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $template = match($this->type) {
            'comment' => 'ticketit::emails.comment',
            'status'  => 'ticketit::emails.status',
            'agent', 'transfer' => 'ticketit::emails.transfer',
            'assigned' => 'ticketit::emails.assigned',
            default => 'ticketit::emails.comment'
        };

        $message = (new MailMessage)
            ->subject($this->subject)
            ->replyTo($this->owner->email, $this->owner->name)
            ->view($template, array_merge([
                'ticket' => $this->ticket,
                'notification_owner' => $this->owner
            ], $this->extraData));

        // Se o Redis/Queue estiver ativo nas configurações
        if (Setting::grab('queue_emails') === 'yes') {
            $this->queue = 'emails';
        }

        return $message;
    }
}
