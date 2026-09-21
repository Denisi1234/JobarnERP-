<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskReturned extends Notification
{
    use Queueable;

    public function __construct(public Task $task, public ?string $comment = null) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'kind' => 'returned',
            'task_id' => $this->task->id,
            'title' => 'Task Returned',
            'message' => "Manager returned \"{$this->task->title}\"" . ($this->comment ? ": {$this->comment}" : '.'),
            'due' => $this->task->due_at?->format('d M Y'),
        ];
    }
}
