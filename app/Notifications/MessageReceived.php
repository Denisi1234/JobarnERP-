<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MessageReceived extends Notification
{
    use Queueable;
    public function __construct(public Message $message) {}
    public function via($n): array { return ['database']; }
    public function toDatabase($n): array {
        $s = $this->message->sender;
        return [
            'kind' => 'message',
            'title' => 'New message from ' . ($s?->name ?? 'Reception'),
            'message' => \Str::limit($this->message->body, 120),
            'body' => $this->message->body,
            'message_id' => $this->message->id,
            'sender_id' => $this->message->sender_id,
            'sender_name' => $s?->name ?? 'Reception',
            'url' => route('messages.index'),
        ];
    }
}
