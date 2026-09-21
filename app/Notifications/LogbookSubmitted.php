<?php

namespace App\Notifications;

use App\Models\WorkReport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LogbookSubmitted extends Notification
{
    use Queueable;

    public function __construct(public WorkReport $report) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'kind' => 'logbook_submitted',
            'work_report_id' => $this->report->id,
            'title' => 'Daily Logbook Submitted',
            'message' => "{$this->report->user?->name} ({$this->report->department?->name}) submitted daily logbook for " . $this->report->report_date->format('d M Y'),
            'url' => route('manager.logbooks'),
            'created_at' => now()->toIso8601String(),
        ];
    }
}
