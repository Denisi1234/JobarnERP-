<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NextSmsService
{
    /**
     * Normalize TZ phone to E.164 255XXXXXXXXX format required by NextSMS/Beem.
     */
    public static function normalizePhone(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }
        // Strip all non-digits
        $digits = preg_replace('/\D+/', '', $phone);
        if (empty($digits)) {
            return null;
        }

        // Already 255...
        if (str_starts_with($digits, '255') && strlen($digits) === 12) {
            return '+' . $digits;
        }
        // 0XXXXXXXXX -> 255XXXXXXXXX
        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            return '+255' . substr($digits, 1);
        }
        // 255 without + or 9 digits
        if (strlen($digits) === 9) {
            return '+255' . $digits;
        }
        if (strlen($digits) === 12 && str_starts_with($digits, '255')) {
            return '+' . $digits;
        }
        // Fallback: add + if missing
        if (!str_starts_with($phone, '+')) {
            return '+' . $digits;
        }
        return $phone;
    }

    /**
     * Send single SMS via NextSMS / Beem API.
     * Returns ['success'=>bool, 'response'=>mixed, 'error'=>?string]
     */
    public function send(string $to, string $message, ?string $senderId = null): array
    {
        $config = config('nextsms');

        if (!$config['enabled']) {
            Log::info('[NextSMS] disabled by config, skipping send', ['to' => substr($to,0,6).'***']);
            return ['success' => false, 'response' => 'disabled', 'error' => 'NextSMS disabled'];
        }

        $normalized = self::normalizePhone($to);
        if (!$normalized) {
            $fallback = $config['test_phone'] ? self::normalizePhone($config['test_phone']) : null;
            if ($fallback) {
                Log::warning('[NextSMS] invalid phone, using test_phone fallback', ['orig' => $to, 'fallback' => $fallback]);
                $normalized = $fallback;
            } else {
                Log::warning('[NextSMS] invalid phone, no fallback, skipping', ['to' => $to]);
                return ['success' => false, 'response' => null, 'error' => 'Invalid phone number'];
            }
        }

        $senderId = $senderId ?: $config['sender_id'] ?: 'FrontDesk';
        $apiUrl = $config['api_url'];
        $logOnly = (bool) $config['log_only'];

        // Log-only mode for local dev without spending credits
        if ($logOnly) {
            Log::info('[NextSMS] LOG_ONLY — not calling API', [
                'to' => substr($normalized,0,6).'***',
                'from' => $senderId,
                'text' => \Str::limit($message, 30).'...',
                'api_url' => $apiUrl,
            ]);
            return ['success' => false, 'response' => 'log_only', 'error' => 'LOG_ONLY — no credit used'];
        }

        // Build payload — supports both NextSMS and Beem shapes
        // NextSMS shape: { "from": "...", "to": "...", "text": "..." }
        // Beem shape:    { "source_addr": "...", "encoding":0, "schedule_time":"", "message":"...", "recipients":[{"recipient_id":1,"dest_addr":"255..."}] }
        $isBeem = str_contains($apiUrl, 'beem.africa');

        if ($isBeem) {
            $payload = [
                'source_addr' => $senderId,
                'encoding' => 0,
                'schedule_time' => '',
                'message' => $message,
                'recipients' => [
                    ['recipient_id' => 1, 'dest_addr' => ltrim($normalized, '+')],
                ],
            ];
        } else {
            $payload = [
                'from' => $senderId,
                'to' => ltrim($normalized, '+'), // many APIs want without +, some with — we send without
                'text' => $message,
            ];
            // Also support alternative key names
            // NextSMS docs sometimes use 'destination' or 'recipient'
        }

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        // Auth detection
        $apiKey = $config['api_key'];
        $username = $config['username'];
        $password = $config['password'];

        $request = Http::timeout(15)->withHeaders($headers);

        if (!empty($apiKey)) {
            // NextSMS often uses "Authorization: Bearer <key>" or "x-api-key"
            // We send both to be compatible
            $request = $request->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'x-api-key' => $apiKey,
            ]);
        } elseif (!empty($username) && !empty($password)) {
            $request = $request->withBasicAuth($username, $password);
        } else {
            Log::warning('[NextSMS] no credentials — aborting', ['api_url' => $apiUrl]);
            return ['success' => false, 'response' => null, 'error' => 'Missing NEXTSMS_API_KEY or USERNAME/PASSWORD'];
        }

        try {
            Log::info('[NextSMS] sending', ['to' => substr($normalized,0,6).'***', 'from' => $senderId, 'api_url' => $apiUrl, 'is_beem' => $isBeem]);

            $response = $request->retry(2, 200)->post($apiUrl, $payload);

            $body = $response->json() ?? $response->body();
            $success = $response->successful();

            if ($success) {
                Log::info('[NextSMS] sent OK', ['to' => substr($normalized,0,6).'***', 'response' => is_string($body) ? \Str::limit($body, 100) : $body]);
            } else {
                Log::error('[NextSMS] send failed', ['to' => substr($normalized,0,6).'***', 'status' => $response->status(), 'body' => is_string($body) ? \Str::limit($body, 200) : $body, 'payload' => ['to'=>substr($normalized,0,6).'***','from'=>$senderId]]);
            }

            return [
                'success' => $success,
                'response' => $body,
                'error' => $success ? null : ('HTTP ' . $response->status() . ': ' . $response->body()),
            ];
        } catch (\Throwable $e) {
            Log::error('[NextSMS] exception', ['to' => substr($normalized,0,6).'***', 'error' => $e->getMessage()]);
            return ['success' => false, 'response' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send welcome SMS — picks first_visit vs returning template automatically.
     */
    public function sendWelcomeSms(string $phone, string $visitorName, string $host, string $badge, bool $isFirstVisit, ?string $company = null): array
    {
        $templateKey = $isFirstVisit ? 'first_visit' : 'returning';
        $template = config('nextsms.templates.' . $templateKey);

        $message = str_replace(
            ['{name}', '{company}', '{host}', '{badge}'],
            [$visitorName, $company ?? '', $host, $badge],
            $template
        );

        return $this->send($phone, $message);
    }

    /**
     * Send checkout SMS — called when guest leaves.
     */
    public function sendCheckoutSms(string $phone, string $visitorName, string $badge, ?string $time = null): array
    {
        $template = config('nextsms.templates.checkout');
        $time = $time ?: now()->format('H:i');
        $message = str_replace(
            ['{name}', '{badge}', '{time}'],
            [$visitorName, $badge, $time],
            $template
        );
        return $this->send($phone, $message);
    }

    /**
     * Quick test helper — call via tinker: app(\App\Services\NextSmsService::class)->send('+255...','Test')
     */
    public function sendTest(string $to): array
    {
        return $this->send($to, 'FrontDesk NextSMS test — ukiwona ujumbe huu, API inafanya kazi sawasawa. ' . now()->format('Y-m-d H:i:s'));
    }
}
