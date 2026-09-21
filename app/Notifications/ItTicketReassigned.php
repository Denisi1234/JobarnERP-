<?php

namespace App\Notifications;

use App\Models\ItTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ItTicketReassigned extends Notification
{
    use Queueable;

    public function __construct(public ItTicket $ticket, public ?string $handoverNotes = null) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'kind' => 'it_ticket_reassigned',
            'ticket_id' => $this->ticket->id,
            'title' => 'IT Ticket Reassigned to You',
            'message' => "Ticket #{$this->ticket->ticket_code} reassigned" . ($this->handoverNotes ? ": {$this->handoverNotes}" : ""),
            'url' => route('it.tickets.show', $this->ticket->id),
            'created_at' => now()->toIso8601String(),
        ];
    }
}
