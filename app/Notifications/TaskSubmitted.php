<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskSubmitted extends Notification
{
    use Queueable;

    public function __construct(public Task $task, public string $byName) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'kind' => 'submitted',
            'task_id' => $this->task->id,
            'title' => 'Approval Required',
            'message' => "{$this->byName} submitted \"{$this->task->title}\" for review.",
            'due' => $this->task->due_at?->format('d M Y'),
        ];
    }
}
