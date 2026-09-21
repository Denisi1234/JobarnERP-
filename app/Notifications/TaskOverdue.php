<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskOverdue extends Notification
{
    use Queueable;

    public function __construct(public Task $task) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'kind' => 'overdue',
            'task_id' => $this->task->id,
            'title' => 'Task Overdue',
            'message' => "\"{$this->task->title}\" is past its due date.",
            'due' => $this->task->due_at?->format('d M Y'),
        ];
    }
}
