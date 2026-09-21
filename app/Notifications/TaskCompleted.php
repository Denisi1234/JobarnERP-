<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskCompleted extends Notification
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
            'kind' => 'completed',
            'task_id' => $this->task->id,
            'title' => 'Task Completed',
            'message' => "{$this->byName} completed \"{$this->task->title}\".",
            'due' => null,
        ];
    }
}
