<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskVerified extends Notification
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
            'kind' => 'verified',
            'task_id' => $this->task->id,
            'title' => 'Task Verified',
            'message' => "Your task \"{$this->task->title}\" was verified and closed.",
            'due' => null,
        ];
    }
}
