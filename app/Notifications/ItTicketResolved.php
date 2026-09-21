<?php

namespace App\Notifications;

use App\Models\ItTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ItTicketResolved extends Notification
{
    use Queueable;

    public function __construct(public ItTicket $ticket, public int $price = 0) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'kind' => 'it_ticket_resolved',
            'ticket_id' => $this->ticket->id,
            'title' => 'Repair Completed — Ready for Billing',
            'message' => "#{$this->ticket->ticket_code} ({$this->ticket->visitor_name}) resolved — Bill: TZS " . number_format($this->price),
            'url' => route('sales.index'),
            'created_at' => now()->toIso8601String(),
        ];
    }
}
