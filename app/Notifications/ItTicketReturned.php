<?php

namespace App\Notifications;

use App\Models\ItTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ItTicketReturned extends Notification
{
    use Queueable;

    public function __construct(public ItTicket $ticket, public string $reason) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'kind' => 'it_ticket_returned',
            'ticket_id' => $this->ticket->id,
            'title' => 'IT Ticket Returned to Reception',
            'message' => "Ticket #{$this->ticket->ticket_code} returned: {$this->reason}",
            'url' => route('reception.visitors'),
            'created_at' => now()->toIso8601String(),
        ];
    }
}
