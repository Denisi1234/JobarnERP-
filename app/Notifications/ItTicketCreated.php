<?php

namespace App\Notifications;

use App\Models\ItTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ItTicketCreated extends Notification
{
    use Queueable;

    public function __construct(public ItTicket $ticket) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'kind' => 'it_ticket_created',
            'ticket_id' => $this->ticket->id,
            'title' => 'New IT Ticket Forwarded',
            'message' => "#{$this->ticket->ticket_code} — {$this->ticket->title} ({$this->ticket->visitor_name})",
            'url' => route('it.tickets.show', $this->ticket->id),
            'created_at' => now()->toIso8601String(),
        ];
    }
}
