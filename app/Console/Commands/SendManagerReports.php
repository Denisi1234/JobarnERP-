<?php

namespace App\Console\Commands;

use App\Models\ApprovalRequest;
use App\Models\Debit;
use App\Models\ManagerReportSchedule;
use App\Models\PosProduct;
use App\Models\Task;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendManagerReports extends Command
{
    protected $signature = 'manager:send-reports';
    protected $description = 'Deliver manager reports that are due';

    public function handle(): int
    {
        $schedules = ManagerReportSchedule::where('active', true)->where('next_run_at', '<=', now())->get();
        foreach ($schedules as $schedule) {
            $sections = $schedule->sections ?: [];
            $lines = ['Manager report: '.$schedule->name, 'Generated: '.now()->format('d M Y H:i')];
            if (in_array('finance', $sections)) $lines[] = 'Finance — receivables: TZS '.number_format(Debit::where('type','customer')->whereIn('status',['pending','overdue'])->sum('amount'),2).'; payables: TZS '.number_format(Debit::where('type','supplier')->whereIn('status',['pending','overdue'])->sum('amount'),2);
            if (in_array('inventory', $sections)) $lines[] = 'Inventory — low-stock items: '.PosProduct::active()->where('is_service',false)->whereColumn('stock_quantity','<=','min_stock_alert')->count();
            if (in_array('tasks', $sections)) $lines[] = 'Tasks — overdue: '.Task::whereNotNull('due_at')->where('due_at','<',now())->whereNotIn('status',['verified','closed','cancelled'])->count();
            if (in_array('approvals', $sections)) $lines[] = 'Approvals — pending: '.ApprovalRequest::where('status','pending')->count();
            Mail::raw(implode("\n\n", $lines), fn ($message) => $message->to($schedule->recipient_email)->subject('ERP Manager Report — '.$schedule->name));
            $schedule->update(['last_sent_at'=>now(), 'next_run_at'=>$this->nextRun($schedule->frequency)]);
        }
        $this->info("Delivered {$schedules->count()} manager report(s).");
        return self::SUCCESS;
    }

    private function nextRun(string $frequency)
    {
        return match ($frequency) { 'daily'=>now()->addDay()->startOfDay(), 'weekly'=>now()->next('Monday')->startOfDay(), default=>now()->addMonth()->startOfMonth() };
    }
}
