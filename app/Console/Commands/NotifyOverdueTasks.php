<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Notifications\TaskOverdue;
use Illuminate\Console\Command;

class NotifyOverdueTasks extends Command
{
    protected $signature = 'tasks:notify-overdue';
    protected $description = 'Notify assignees of overdue tasks that have not been notified yet';

    public function handle(): int
    {
        $tasks = Task::with(['assignee', 'collaborators'])
            ->whereNotNull('due_at')
            ->where('due_at', '<', now()->startOfDay())
            ->whereNotIn('status', ['completed', 'verified', 'closed'])
            ->whereNull('overdue_notified_at')
            ->get();

        $sent = 0;
        foreach ($tasks as $task) {
            $recipients = collect([$task->assignee])->merge($task->collaborators ?? collect())
                ->filter()
                ->unique('id');

            if ($recipients->isEmpty()) {
                continue;
            }

            foreach ($recipients as $user) {
                $user->notify(new TaskOverdue($task));
            }

            $task->update(['overdue_notified_at' => now()]);
            $sent++;
        }

        $this->info("Notified {$sent} overdue task(s).");

        return self::SUCCESS;
    }
}
