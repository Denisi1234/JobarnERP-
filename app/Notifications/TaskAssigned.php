<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskAssigned extends Notification
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
            'kind' => 'assigned',
            'task_id' => $this->task->id,
            'title' => 'New Task Assigned',
            'message' => $this->task->title,
            'due' => $this->task->due_at?->format('d M Y'),
        ];
    }
}
