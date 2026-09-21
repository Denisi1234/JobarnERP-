<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Visit;
use App\Services\NextSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use App\Services\UnifiedVisitService;

class VisitorCheckinController extends Controller
{
    public function __construct(private readonly NextSmsService $sms) {}

    /**
     * POST /reception/api/tickets
     * Create Customer Ticket with progressive disclosure and cross-portal routing.
     */
    public function storeTicket(Request $request, UnifiedVisitService $visitService)
    {
        $validated = $request->validate([
            'customer_type'        => ['required', 'string', 'in:individual,company'],
            'full_name'            => ['nullable', 'string', 'max:255'],
            'visitor'              => ['nullable', 'string', 'max:255'],
            'visitor_phone'        => ['nullable', 'string', 'max:50'],
            'visitor_email'        => ['nullable', 'email', 'max:255'],
            
            // Organization fields
            'organization_name'    => ['nullable', 'string', 'max:255'],
            'organization_type'    => ['nullable', 'string', 'max:100'],
            'industry'             => ['nullable', 'string', 'max:100'],
            'tin_number'           => ['nullable', 'string', 'max:50'],
            'contact_person'       => ['nullable', 'string', 'max:255'],
            'contact_position'     => ['nullable', 'string', 'max:100'],
            'region'               => ['nullable', 'string', 'max:100'],
            'district'             => ['nullable', 'string', 'max:100'],
            'physical_address'     => ['nullable', 'string'],

            // Visit fields
            'visit_purpose'        => ['required', 'string', 'max:255'],
            'purpose'              => ['nullable', 'string', 'max:255'],
            'customer_statement'   => ['required', 'string'],
            
            // Routing fields
            'forward_to_department'=> ['required', 'string', 'max:100'],
            'assigned_to'          => ['nullable'],
            'handover_note'        => ['nullable', 'string'],
            'priority'             => ['nullable', 'string', 'in:low,normal,high,urgent'],
        ]);

        if (empty($validated['purpose'])) {
            $validated['purpose'] = $validated['visit_purpose'];
        }
        if (empty($validated['visitor'])) {
            $validated['visitor'] = $validated['customer_type'] === 'company' 
                ? ($validated['organization_name'] ?? 'Company') 
                : ($validated['full_name'] ?? 'Individual');
        }

        $result = $visitService->createCustomerTicket($validated, $this->sms);

        return response()->json($result);
    }

    /**
     * POST /reception/api/checkin
     * Handles visitor check-in from dashboard & visitors pages.
     * Saves to visits table and auto-sends NextSMS.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'visitor'        => ['required', 'string', 'max:255'],
            'visitor_phone'  => ['nullable', 'string', 'max:30'],
            'visitor_email'  => ['nullable', 'email', 'max:255'],
            'visitor_company'=> ['nullable', 'string', 'max:255'],
            'host'           => ['required', 'string', 'max:255'], // free text host name from input
            'employee_id'    => ['nullable', 'exists:employees,id'],
            'purpose'        => ['nullable', 'string', 'max:500'],
            'zone'           => ['nullable', 'string', 'max:100'],
            'vip'            => ['nullable', 'boolean'],
            'nda_signed'     => ['nullable', 'boolean'],
        ]);

        // Resolve employee_id if host text matches an employee name but no id given
        $employeeId = $validated['employee_id'] ?? null;
        $hostName = trim($validated['host']);
        if (!$employeeId && $hostName) {
            try {
                $employee = Employee::where('name', 'LIKE', '%' . Str::substr($hostName, 0, 30) . '%')->first();
                if ($employee) {
                    $employeeId = $employee->id;
                    $hostName = $employee->name;
                }
            } catch (\Throwable $e) {
                Log::warning('[Checkin] employee lookup failed (DB offline?) — continuing without employee_id', ['error' => $e->getMessage()]);
            }
        }

        $phone = $validated['visitor_phone'] ?? null;
        $normalizedPhone = $phone ? NextSmsService::normalizePhone($phone) : null;

        // First-time detection: any previous visit with same normalized phone or same name+phone
        $isFirstVisit = true;
        try {
            if ($normalizedPhone) {
                $digits = preg_replace('/\D+/', '', $normalizedPhone);
                $exists = Visit::where(function ($q) use ($digits, $normalizedPhone, $phone) {
                    $q->where('visitor_phone', 'LIKE', '%' . substr($digits, -9) . '%')
                      ->orWhere('visitor_phone', $normalizedPhone)
                      ->orWhere('visitor_phone', $phone);
                })->exists();
                $isFirstVisit = !$exists;
            } else {
                $exists = Visit::where('visitor', $validated['visitor'])->exists();
                $isFirstVisit = !$exists;
            }
        } catch (\Throwable $e) {
            Log::warning('[Checkin] first-visit check failed (DB offline?) — assuming first visit', ['error' => $e->getMessage()]);
            $isFirstVisit = true;
        }

        // Generate badge code
        try {
            $badge = 'JOB-' . now()->format('Ymd') . '-' . str_pad((string) (Visit::whereDate('created_at', today())->count() + 1), 3, '0', STR_PAD_LEFT);
        } catch (\Throwable $e) {
            $badge = 'JOB-' . now()->format('Ymd') . '-' . str_pad((string) random_int(100, 999), 3, '0', STR_PAD_LEFT);
        }

        $visit = null;
        try {
            $visit = Visit::create([
                'uuid' => (string) Str::uuid(),
                'employee_id' => $employeeId,
                'visitor' => $validated['visitor'],
                'visitor_phone' => $normalizedPhone ?: $phone,
                'visitor_email' => $validated['visitor_email'] ?? null,
                'purpose' => trim(($validated['purpose'] ?? '') . ' ' . ($validated['zone'] ?? '') . ' ' . ($validated['visitor_company'] ?? '')),
                'arrival' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('[Checkin] DB create failed (will still send SMS)', ['error' => $e->getMessage(), 'data' => $validated]);
            // Don't hard-fail — still send SMS and return badge so front desk not blocked when DB offline
            $visit = [
                'uuid' => (string) Str::uuid(),
                'visitor' => $validated['visitor'],
                'visitor_phone' => $normalizedPhone ?: $phone,
                'purpose' => $validated['purpose'] ?? '',
                'arrival' => now()->toDateTimeString(),
                '_db_error' => $e->getMessage(),
            ];
        }

        // Auto-send SMS if phone available and NEXT SMS configured
        $smsResult = null;
        $smsSent = false;
        if ($normalizedPhone) {
            $targetPhone = $normalizedPhone;
            try {
                $smsResult = $this->sms->sendWelcomeSms(
                    phone: $targetPhone,
                    visitorName: $validated['visitor'],
                    host: $hostName,
                    badge: $badge,
                    isFirstVisit: $isFirstVisit,
                    company: $validated['visitor_company'] ?? null
                );
                $smsSent = (bool) ($smsResult['success'] ?? false);
            } catch (\Throwable $e) {
                Log::error('[Checkin] SMS exception', ['error' => $e->getMessage()]);
                $smsResult = ['success' => false, 'error' => $e->getMessage()];
            }
        } else {
            Log::info('[Checkin] No phone, skipping SMS', ['visitor' => $validated['visitor']]);
            $smsResult = ['success' => false, 'error' => 'No phone number provided — SMS skipped'];
        }

        return response()->json([
            'success' => true,
            'message' => $isFirstVisit ? 'Karibu! First visit saved — welcome SMS ' . ($smsSent ? 'sent' : 'queued') . '.' : 'Welcome back — check-in saved' . ($smsSent ? ' + SMS sent' : ''),
            'is_first_visit' => $isFirstVisit,
            'badge' => $badge,
            'visit' => $visit,
            'sms' => [
                'sent' => $smsSent,
                'result' => $smsResult,
            ],
        ]);
    }

    /**
     * POST /reception/api/checkout
     * Handles checkout — sets departure + auto-sends NextSMS checkout.
     */
    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'visitor'       => ['nullable', 'string', 'max:255'],
            'visitor_phone' => ['nullable', 'string', 'max:30'],
            'badge'         => ['nullable', 'string', 'max:50'],
            'visit_uuid'    => ['nullable', 'string'],
        ]);

        $phone = $validated['visitor_phone'] ?? null;
        $normalizedPhone = $phone ? NextSmsService::normalizePhone($phone) : null;
        $name = $validated['visitor'] ?? 'Mteja';
        $badge = $validated['badge'] ?? 'JOB';
        $time = now()->format('H:i');

        // Try to mark latest open visit as departed
        $visit = null;
        try {
            if (!empty($validated['visit_uuid'])) {
                $visit = Visit::where('uuid', $validated['visit_uuid'])->first();
            }
            if (!$visit && $normalizedPhone) {
                $digits = preg_replace('/\D+/', '', $normalizedPhone);
                $visit = Visit::where('visitor_phone', 'LIKE', '%' . substr($digits, -9) . '%')
                    ->whereNull('departure')
                    ->latest('arrival')->first();
            }
            if (!$visit && $name) {
                $visit = Visit::where('visitor', $name)->whereNull('departure')->latest('arrival')->first();
            }
            if ($visit) {
                $visit->update(['departure' => now()]);
                $name = $visit->visitor;
                $phone = $visit->visitor_phone ?: $phone;
                $normalizedPhone = NextSmsService::normalizePhone($phone);
            }
        } catch (\Throwable $e) {
            Log::warning('[Checkout] DB update failed (will still send SMS)', ['error' => $e->getMessage()]);
        }

        // Send checkout SMS
        $smsResult = null;
        $smsSent = false;
        $targetPhone = $normalizedPhone;
        if ($targetPhone) {
            try {
                $smsResult = $this->sms->sendCheckoutSms($targetPhone, $name, $badge, $time);
                $smsSent = (bool) ($smsResult['success'] ?? false);
            } catch (\Throwable $e) {
                Log::error('[Checkout] SMS exception', ['error' => $e->getMessage()]);
                $smsResult = ['success' => false, 'error' => $e->getMessage()];
            }
        } else {
            $smsResult = ['success' => false, 'error' => 'No phone — SMS skipped'];
        }

        return response()->json([
            'success' => true,
            'message' => $smsSent ? "Asante! Checkout SMS sent to $name ✅" : 'Checked out — ' . ($smsResult['error'] ?? 'no SMS'),
            'visit' => $visit,
            'sms' => ['sent' => $smsSent, 'result' => $smsResult],
        ]);
    }

    /**
     * POST /reception/api/sms/send
     * Direct SMS dispatch from Reception Drawer / Customer Details.
     */
    public function sendCustomSms(Request $request)
    {
        $validated = $request->validate([
            'phone'   => ['required', 'string', 'max:30'],
            'message' => ['required', 'string', 'max:500'],
            'visitor' => ['nullable', 'string', 'max:255'],
        ]);

        $phone = $validated['phone'];
        $message = trim($validated['message']);
        $normalized = NextSmsService::normalizePhone($phone);

        if (!$normalized) {
            return response()->json([
                'success' => false,
                'error'   => 'Namba ya simu si sahihi (Invalid phone number)',
            ], 422);
        }

        try {
            $result = $this->sms->send($normalized, $message);
            $success = (bool) ($result['success'] ?? false);

            return response()->json([
                'success' => $success,
                'message' => $success ? 'SMS imetumwa kikamilifu! (SMS sent successfully)' : ($result['error'] ?? 'SMS dispatch failed'),
                'result'  => $result,
            ]);
        } catch (\Throwable $e) {
            Log::error('[DirectSMS] Exception', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /reception/api/checkin/test?phone=+255...
     * Quick manual test of NextSMS without creating a visit.
     */
    public function testSms(Request $request, NextSmsService $sms)
    {
        $request->validate(['phone' => ['required', 'string']]);
        $result = $sms->sendTest($request->input('phone'));
        return response()->json($result);
    }
}
