<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskDeclined extends Notification
{
    use Queueable;

    public function __construct(public Task $task, public string $byName, public ?string $reason = null) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'kind' => 'declined',
            'task_id' => $this->task->id,
            'title' => 'Task Declined',
            'message' => "{$this->byName} declined \"{$this->task->title}\"" . ($this->reason ? ": {$this->reason}" : '.'),
            'due' => $this->task->due_at?->format('d M Y'),
        ];
    }
}
