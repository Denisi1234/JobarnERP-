<?php

namespace App\Jobs;

use App\Services\NextSmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendNextSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300, 900];

    public function __construct(public string $to, public string $message, public ?string $senderId = null) {}

    public function handle(NextSmsService $sms): void
    {
        $res = $sms->send($this->to, $this->message, $this->senderId);
        if (!$res['success'] && ($res['response'] ?? null) !== 'log_only' && ($res['response'] ?? null) !== 'disabled') {
            Log::warning('[SendNextSmsJob] failed, will retry if tries left', ['to'=>substr($this->to,0,6).'***','error'=>$res['error'] ?? 'unknown', 'tries'=>$this->attempts()]);
            if ($this->attempts() < $this->tries) throw new \Exception($res['error'] ?? 'SMS send failed');
        }
    }

    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(30);
    }
}
