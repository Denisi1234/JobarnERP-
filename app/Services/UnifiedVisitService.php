<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\ItTicket;
use App\Models\SaleInvoice;
use App\Models\Task;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitService;
use App\Models\VisitTimeline;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UnifiedVisitService
{
    /**
     * Create a progressive Customer Ticket & Live Visit
     */
    public function createCustomerTicket(array $data, ?NextSmsService $sms = null): array
    {
        $tx = DB::transaction(function () use ($data, $sms) {
            $customerType = $data['customer_type'] ?? 'individual';
            
            if ($customerType === 'company') {
                $customerName = trim($data['organization_name'] ?? ($data['company'] ?? 'Unnamed Company'));
                $company = $customerName;
                $contactPerson = $data['contact_person'] ?? null;
                $phone = $data['visitor_phone'] ?? ($data['contact_phone'] ?? null);
                $email = $data['visitor_email'] ?? ($data['contact_email'] ?? null);
            } else {
                $customerName = trim($data['visitor'] ?? ($data['full_name'] ?? 'Walk-in Customer'));
                $company = null;
                $contactPerson = null;
                $phone = $data['visitor_phone'] ?? null;
                $email = $data['visitor_email'] ?? null;
            }

            $normalizedPhone = $phone ? NextSmsService::normalizePhone($phone) : null;
            $purpose = $data['purpose'] ?? ($data['visit_purpose'] ?? 'General Inquiry');
            $customerStatement = $data['customer_statement'] ?? null;
            $forwardTo = $data['forward_to_department'] ?? ($data['department'] ?? 'IT Support');
            $handoverNote = $data['handover_note'] ?? null;
            $priority = strtolower($data['priority'] ?? 'normal');
            $employeeId = !empty($data['assigned_to']) && is_numeric($data['assigned_to']) ? (int)$data['assigned_to'] : ($data['employee_id'] ?? null);

            // 1. Generate unique ticket code
            $prefix = 'TKT-' . now()->format('Ymd') . '-';
            $latest = Visit::where('ticket_code', 'like', $prefix . '%')
                ->latest('id')
                ->value('ticket_code');
            $nextSeq = 1;
            if ($latest && preg_match('/-(\d+)$/', $latest, $m)) {
                $nextSeq = (int)$m[1] + 1;
            }
            do {
                $ticketCode = $prefix . str_pad((string)$nextSeq, 4, '0', STR_PAD_LEFT);
                $nextSeq++;
            } while (Visit::where('ticket_code', $ticketCode)->exists());

            // 2. Map department code
            $deptCode = match(true) {
                str_contains(strtolower($forwardTo), 'it') || str_contains(strtolower($forwardTo), 'tech') => 'IT',
                str_contains(strtolower($forwardTo), 'sale') || str_contains(strtolower($forwardTo), 'showroom') => 'SALES',
                str_contains(strtolower($forwardTo), 'manage') || str_contains(strtolower($forwardTo), 'admin') => 'MANAGEMENT',
                str_contains(strtolower($forwardTo), 'finan') || str_contains(strtolower($forwardTo), 'account') => 'FINANCE',
                str_contains(strtolower($forwardTo), 'hr') || str_contains(strtolower($forwardTo), 'human') => 'HR',
                str_contains(strtolower($forwardTo), 'operat') => 'OPERATIONS',
                str_contains(strtolower($forwardTo), 'store') || str_contains(strtolower($forwardTo), 'inventor') => 'STORE',
                default => 'SUPPORT',
            };

            // 3. Create Master Visit
            $visit = Visit::create([
                'uuid' => (string) Str::uuid(),
                'ticket_code' => $ticketCode,
                'customer_type' => $customerType,
                'visitor' => $customerName,
                'company' => $company,
                'organization_name' => $customerType === 'company' ? $customerName : null,
                'organization_type' => $data['organization_type'] ?? null,
                'industry' => $data['industry'] ?? null,
                'tin_number' => $data['tin_number'] ?? null,
                'contact_person' => $contactPerson,
                'contact_position' => $data['contact_position'] ?? null,
                'region' => $data['region'] ?? 'Dar es Salaam',
                'district' => $data['district'] ?? null,
                'physical_address' => $data['physical_address'] ?? null,
                'visitor_phone' => $normalizedPhone ?: $phone,
                'visitor_email' => $email,
                'purpose' => $purpose,
                'customer_statement' => $customerStatement,
                'forward_to_department' => $forwardTo,
                'handover_note' => $handoverNote,
                'priority' => $priority,
                'employee_id' => $employeeId,
                'arrival' => now(),
                'status' => 'in_progress',
                'current_department' => strtolower($deptCode),
            ]);

            // 4. Create Visit Service
            $service = $visit->services()->create([
                'uuid' => (string) Str::uuid(),
                'department' => $deptCode,
                'service_name' => $purpose,
                'request_description' => $customerStatement ?: $purpose,
                'resolution_notes' => $handoverNote ? "Handover: {$handoverNote}" : null,
                'price' => 0,
                'quantity' => 1,
                'status' => 'pending',
                'assigned_to' => $employeeId,
            ]);

            // 5. If IT department, create linked ItTicket and Task
            $itTicket = null;
            if ($deptCode === 'IT') {
                $reporterId = auth()->id() ?? User::where('role', 'reception')->first()?->id;
                
                $itTicket = ItTicket::create([
                    'uuid' => (string) Str::uuid(),
                    'ticket_code' => $ticketCode,
                    'customer_type' => $customerType,
                    'visit_id' => $visit->id,
                    'reported_by' => $reporterId,
                    'assigned_to' => $employeeId,
                    'visitor_name' => $customerName,
                    'visitor_company' => $company,
                    'contact_person' => $contactPerson,
                    'contact_position' => $data['contact_position'] ?? null,
                    'organization_type' => $data['organization_type'] ?? null,
                    'industry' => $data['industry'] ?? null,
                    'region' => $data['region'] ?? 'Dar es Salaam',
                    'visitor_phone' => $normalizedPhone ?: $phone,
                    'title' => $purpose,
                    'description' => "Customer Request:\n" . ($customerStatement ?: $purpose) . "\n\nHandover Note:\n" . ($handoverNote ?: 'Referred by Reception'),
                    'customer_statement' => $customerStatement,
                    'handover_note' => $handoverNote,
                    'category' => $purpose,
                    'priority' => $priority,
                    'status' => 'pending',
                    'location' => !empty($data['region']) ? ($data['region'] . (!empty($data['district']) ? ', ' . $data['district'] : '')) : 'Lobby 1',
                ]);

                // Auto-create Task for IT Team — HARDENED creator
                $task = Task::create([
                    'uuid' => (string) Str::uuid(),
                    'title' => "[{$ticketCode}] {$purpose} for {$customerName}",
                    'description' => $customerStatement ?: $handoverNote,
                    'priority' => $priority,
                    'status' => 'pending',
                    'assigned_to' => $employeeId,
                    'it_ticket_id' => $itTicket->id,
                    'created_by' => $reporterId,
                ]);

                $itTicket->update(['task_id' => $task->id]);
            }

            // 6. Log Timeline
            $visit->logTimeline(
                eventType: 'check_in',
                title: "Ticket {$ticketCode} Created & Forwarded to {$forwardTo}",
                description: $handoverNote ?: "Customer referred to {$forwardTo} for {$purpose}",
                department: strtolower($deptCode),
                metadata: [
                    'ticket_code' => $ticketCode,
                    'customer_type' => $customerType,
                    'priority' => $priority,
                    'service_id' => $service->id,
                ]
            );

            return [
                'visit' => $visit,
                'ticketCode' => $ticketCode,
                'customerName' => $customerName,
                'company' => $company,
                'contactPerson' => $contactPerson,
                'phone' => $phone,
                'normalizedPhone' => $normalizedPhone,
                'employeeId' => $employeeId,
                'forwardTo' => $forwardTo,
                'deptCode' => $deptCode,
                'handoverNote' => $handoverNote,
                'purpose' => $purpose,
                'customerStatement' => $customerStatement,
                'priority' => $priority,
                'customerType' => $customerType,
                'email' => $email,
            ];
        });

        // 7. Auto-send SMS outside transaction — never aborts visit creation
        $smsSent = false;
        $smsResult = null;
        $visit = $tx['visit'];
        $ticketCode = $tx['ticketCode'];
        $customerName = $tx['customerName'];
        $company = $tx['company'];
        $contactPerson = $tx['contactPerson'];
        $phone = $tx['phone'];
        $normalizedPhone = $tx['normalizedPhone'];
        $employeeId = $tx['employeeId'];
        $forwardTo = $tx['forwardTo'];
        if ($sms && $normalizedPhone) {
            $targetPhone = $normalizedPhone;
            try {
                $isFirstVisit = !Visit::where(function($q) use ($targetPhone, $customerName, $phone, $visit){
                    if($targetPhone) $q->where('visitor_phone', $targetPhone);
                    if($phone && $phone !== $targetPhone) $q->orWhere('visitor_phone', $phone);
                    if($customerName) $q->orWhere('visitor', $customerName);
                })->where('id','!=',$visit->id)->exists();
                $hostName = $employeeId ? (Employee::find($employeeId)?->name ?? $forwardTo) : $forwardTo;
                $templateKey = $isFirstVisit ? 'first_visit' : 'returning';
                $template = config('nextsms.templates.'.$templateKey);
                $welcomeBody = str_replace(['{name}','{company}','{host}','{badge}'], [$contactPerson ? "{$contactPerson} ({$customerName})" : $customerName, $company ?? '', $hostName, $ticketCode], $template);
                if (config('queue.default') !== 'sync') {
                    \App\Jobs\SendNextSmsJob::dispatch($targetPhone, $welcomeBody, null);
                    $smsSent = true;
                    $smsResult = ['success'=>true,'response'=>'queued'];
                } else {
                    $smsResult = $sms->send($targetPhone, $welcomeBody);
                    $smsSent = (bool)($smsResult['success'] ?? false);
                }
            } catch (\Throwable $e) {
                Log::warning('[UnifiedVisitService] SMS error (outside tx): ' . $e->getMessage());
            }
        }

        return [
            'success' => true,
            'message' => "Ticket {$ticketCode} created and forwarded to {$forwardTo}.",
            'ticket' => [
                'ticket_code' => $ticketCode,
                'customer' => $customerName,
                'customer_type' => $tx['customerType'],
                'contact_person' => $contactPerson,
                'contact_position' => $data['contact_position'] ?? null,
                'phone' => $phone,
                'email' => $tx['email'],
                'purpose' => $tx['purpose'],
                'customer_statement' => $tx['customerStatement'],
                'forward_to' => $forwardTo,
                'dept_code' => $tx['deptCode'],
                'handover_note' => $tx['handoverNote'],
                'priority' => ucfirst($tx['priority']),
                'status' => 'New',
                'created_at' => now()->format('d M Y, H:i'),
            ],
            'visit' => $visit->fresh(['services', 'timelines']),
            'sms_sent' => $smsSent,
        ];
    }

    /**
     * Create a new Live Visit (compatibility wrapper)
     */
    public function createVisit(array $data): Visit
    {
        $res = $this->createCustomerTicket($data);
        return $res['visit'];
    }

    /**
     * Add a service / task request to an existing Live Visit
     */
    public function addServiceToVisit(Visit $visit, array $serviceData): VisitService
    {
        return DB::transaction(function () use ($visit, $serviceData) {
            $service = $visit->services()->create([
                'uuid' => (string) Str::uuid(),
                'department' => strtoupper($serviceData['department']),
                'service_name' => $serviceData['service_name'] ?? 'General Service',
                'request_description' => $serviceData['request_description'] ?? null,
                'price' => $serviceData['price'] ?? 0,
                'quantity' => $serviceData['quantity'] ?? 1,
                'status' => $serviceData['status'] ?? 'pending',
                'assigned_to' => $serviceData['assigned_to'] ?? null,
            ]);

            $visit->update([
                'current_department' => strtolower($serviceData['department']),
                'status' => 'in_progress',
            ]);

            $visit->logTimeline(
                eventType: 'handoff',
                title: "Forwarded to {$service->department}",
                description: "Service: {$service->service_name}. Note: " . ($service->request_description ?: 'None'),
                department: strtolower($service->department)
            );

            $visit->recalculateTotal();

            return $service;
        });
    }

    /**
     * Accept a service task (e.g. IT clicks [ ACCEPT TICKET ])
     */
    public function acceptService(int|string $serviceId, ?int $userId = null): VisitService
    {
        return DB::transaction(function () use ($serviceId, $userId) {
            $service = VisitService::where('id', $serviceId)->orWhere('uuid', $serviceId)->firstOrFail();
            $user = $userId ? User::find($userId) : auth()->user();

            $service->update([
                'status' => 'in_progress',
                'assigned_to' => $user?->id ?? $service->assigned_to,
                'started_at' => now(),
            ]);

            // Sync with ItTicket if exists
            ItTicket::where('visit_id', $service->visit_id)->update([
                'status' => 'assigned',
                'assigned_to' => $user?->id,
            ]);

            Task::where('it_ticket_id', function ($q) use ($service) {
                $q->select('id')->from('it_tickets')->where('visit_id', $service->visit_id);
            })->update([
                'status' => 'in_progress',
                'assigned_to' => $user?->id,
            ]);

            $userName = $user ? $user->name : 'Department Staff';

            $service->visit->logTimeline(
                eventType: 'accepted',
                title: "Ticket Accepted by {$userName} ({$service->department})",
                description: "{$userName} started working on: {$service->service_name}",
                department: strtolower($service->department)
            );

            return $service;
        });
    }

    /**
     * Complete a service task & add price / billable amount
     */
    public function completeService(int|string $serviceId, int $price, ?string $resolutionNotes = null, ?int $userId = null): VisitService
    {
        return DB::transaction(function () use ($serviceId, $price, $resolutionNotes, $userId) {
            $service = VisitService::where('id', $serviceId)->orWhere('uuid', $serviceId)->firstOrFail();
            $user = $userId ? User::find($userId) : auth()->user();

            $service->update([
                'status' => 'completed',
                'price' => $price,
                'resolution_notes' => $resolutionNotes,
                'completed_by' => $user?->id,
                'completed_at' => now(),
            ]);

            $visit = $service->visit;
            $newTotal = $visit->recalculateTotal();

            // Sync with ItTicket and Task
            ItTicket::where('visit_id', $visit->id)->update([
                'status' => 'resolved',
                'price' => $price,
                'resolved_at' => now(),
            ]);

            Task::where('it_ticket_id', function ($q) use ($visit) {
                $q->select('id')->from('it_tickets')->where('visit_id', $visit->id);
            })->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            $userName = $user ? $user->name : 'Staff';

            // Also create or sync sale invoice so Sales portal can take money
            SaleInvoice::create([
                'uuid' => (string) Str::uuid(),
                'it_ticket_id' => ItTicket::where('visit_id', $visit->id)->first()?->id,
                'customer_name' => $visit->visitor,
                'company' => $visit->company,
                'service' => $service->service_name,
                'amount' => $price * ($service->quantity ?: 1),
                'currency' => 'TZS',
                'status' => 'pending_payment',
                'owner_id' => $user?->id,
            ]);

            $formattedPrice = number_format($price);
            $visit->logTimeline(
                eventType: 'completed',
                title: "{$service->department} Task Fixed by {$userName}",
                description: "Resolution: " . ($resolutionNotes ?: 'Fixed') . " | Added Charge: TZS {$formattedPrice}",
                department: strtolower($service->department),
                metadata: ['price' => $price, 'total_now' => $newTotal]
            );

            // If all services are completed, mark visit ready for billing/checkout
            $allDone = $visit->services()->whereIn('status', ['pending', 'in_progress'])->count() === 0;
            if ($allDone) {
                $visit->update(['status' => 'ready_for_billing', 'current_department' => 'sales']);
                $visit->logTimeline(
                    eventType: 'ready_for_billing',
                    title: "Ready for Billing & Checkout",
                    description: "Total Services: TZS " . number_format($newTotal),
                    department: 'reception'
                );
            }

            return $service;
        });
    }

    /**
     * Reception or Sales records payment and checks out the customer
     */
    public function checkoutVisit(int|string $visitId, array $checkoutData): Visit
    {
        return DB::transaction(function () use ($visitId, $checkoutData) {
            $visit = Visit::where('id', $visitId)->orWhere('uuid', $visitId)->firstOrFail();

            $paymentStatus = $checkoutData['payment_status'] ?? 'paid'; // paid, pending, credit
            $notes = $checkoutData['notes'] ?? null;

            $visit->update([
                'departure' => now(),
                'status' => 'checked_out',
                'payment_status' => $paymentStatus,
                'current_department' => 'completed',
                'checkout_notes' => $notes,
            ]);

            // Mark any pending sale invoices for this customer as paid if checkout marked paid
            if ($paymentStatus === 'paid') {
                SaleInvoice::where('customer_name', $visit->visitor)
                    ->where('status', 'pending_payment')
                    ->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                    ]);
            }

            $durationMinutes = $visit->arrival ? $visit->arrival->diffInMinutes(now()) : 0;
            $hours = floor($durationMinutes / 60);
            $mins = $durationMinutes % 60;
            $durationStr = $hours > 0 ? "{$hours}h {$mins}m" : "{$mins}m";

            $visit->logTimeline(
                eventType: 'checkout',
                title: "Customer Checked Out (Total: TZS " . number_format($visit->total_amount) . ")",
                description: "Payment Status: " . strtoupper($paymentStatus) . " | Visit Duration: {$durationStr}" . ($notes ? " | Note: {$notes}" : ""),
                department: 'reception',
                metadata: [
                    'duration_minutes' => $durationMinutes,
                    'total_amount' => $visit->total_amount,
                    'payment_status' => $paymentStatus,
                ]
            );

            return $visit;
        });
    }

    /**
     * Return live visits data for polling / WebSocket payloads
     */
    public function getLiveState(): array
    {
        $activeVisits = Visit::with(['services.assignee', 'services.completedBy', 'timelines.user'])
            ->whereNull('departure')
            ->latest('arrival')
            ->get();

        $completedToday = Visit::with(['services', 'timelines'])
            ->whereNotNull('departure')
            ->whereDate('departure', today())
            ->latest('departure')
            ->get();

        $pendingInvoices = SaleInvoice::where('status', 'pending_payment')->get();

        // ── IT Activity Feed: last 25 IT-department timeline events ──
        $itActivity = VisitTimeline::with(['user', 'itTicket'])
            ->where('department', 'it')
            ->latest('created_at')
            ->limit(25)
            ->get()
            ->map(function (VisitTimeline $tl) {
                return [
                    'id'          => $tl->id,
                    'event_type'  => $tl->event_type,
                    'title'       => $tl->title,
                    'description' => $tl->description,
                    'technician'  => $tl->user?->name ?? 'IT Team',
                    'ticket_code' => $tl->itTicket?->ticket_code ?? null,
                    'ticket_id'   => $tl->it_ticket_id,
                    'visit_id'    => $tl->visit_id,
                    'time'        => $tl->created_at?->format('H:i'),
                    'date'        => $tl->created_at?->format('d M Y'),
                    'full_time'   => $tl->created_at?->format('d M Y, H:i'),
                    'ts'          => $tl->created_at?->timestamp ?? 0,
                ];
            })
            ->values()
            ->toArray();

        return [
            'active_visits'    => $activeVisits,
            'completed_today'  => $completedToday,
            'pending_invoices' => $pendingInvoices,
            'it_activity'      => $itActivity,
            'stats' => [
                'active_count'          => $activeVisits->count(),
                'in_it'                 => $activeVisits->where('current_department', 'it')->count(),
                'in_sales'              => $activeVisits->where('current_department', 'sales')->count(),
                'completed_today_count' => $completedToday->count(),
                'total_revenue_today'   => $completedToday->sum('total_amount'),
            ],
            'server_time' => now()->toIso8601String(),
        ];
    }
}
