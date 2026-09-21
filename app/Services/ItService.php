<?php

namespace App\Services;

use App\Models\ItTicket;
use App\Models\SaleInvoice;
use App\Models\Task;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitTimeline;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ItService
{
    private string $file = 'it_portal.json';

    /**
     * Fallback file loader for offline/DB failure resilience
     */
    private function loadJson(): array
    {
        if (!Storage::exists($this->file)) {
            return ['tickets' => [], 'invoices' => [], 'tasks' => []];
        }
        $data = json_decode(Storage::get($this->file), true);
        return is_array($data) ? $data : ['tickets' => [], 'invoices' => [], 'tasks' => []];
    }

    private function saveJson(array $data): void
    {
        Storage::put($this->file, json_encode($data, JSON_PRETTY_PRINT));
    }

    public function allTickets(): array
    {
        try {
            $tickets = ItTicket::with(['assignee', 'reporter', 'task', 'invoice', 'visit'])
                ->latest('id')
                ->get();

            return $tickets->map(function (ItTicket $t) {
                return $this->formatTicketArray($t);
            })->toArray();
        } catch (\Throwable $e) {
            Log::warning('[ItService] DB error in allTickets, using fallback: ' . $e->getMessage());
            return $this->loadJson()['tickets'];
        }
    }

    public function findTicketModel(int $id): ?ItTicket
    {
        return ItTicket::with(['assignee', 'reporter', 'task', 'invoice', 'visit.timelines.user', 'timelines.user'])->find($id);
    }

    public function findTicket(int $id): ?array
    {
        try {
            $t = $this->findTicketModel($id);
            if ($t) {
                return $this->formatTicketArray($t);
            }
        } catch (\Throwable $e) {
            Log::warning('[ItService] DB error in findTicket: ' . $e->getMessage());
        }

        foreach ($this->loadJson()['tickets'] as $t) {
            if ($t['id'] === $id) return $t;
        }
        return null;
    }

    public function formatTicketArray(ItTicket $t): array
    {
        return [
            'id' => $t->id,
            'uuid' => $t->uuid,
            'ticket_code' => $t->ticket_code ?? ('TKT-' . ($t->created_at ? $t->created_at->format('Ymd') : now()->format('Ymd')) . '-' . str_pad((string)$t->id, 4, '0', STR_PAD_LEFT)),
            'customer_type' => $t->customer_type ?? 'individual',
            'visitor_name' => $t->visitor_name,
            'visitor_company' => $t->visitor_company ?? ($t->visit?->organization_name ?? ''),
            'contact_person' => $t->contact_person ?? ($t->visit?->contact_person ?? null),
            'contact_position' => $t->contact_position ?? ($t->visit?->contact_position ?? null),
            'organization_type' => $t->organization_type ?? ($t->visit?->organization_type ?? null),
            'industry' => $t->industry ?? ($t->visit?->industry ?? null),
            'region' => $t->region ?? ($t->visit?->region ?? 'Dar es Salaam'),
            'district' => $t->visit?->district ?? null,
            'physical_address' => $t->visit?->physical_address ?? null,
            'visitor_phone' => $t->visitor_phone ?? ($t->visit?->visitor_phone ?? ''),
            'visitor_email' => $t->visit?->visitor_email ?? null,
            'title' => $t->title,
            'description' => $t->description ?? '',
            'customer_statement' => $t->customer_statement ?? ($t->visit?->customer_statement ?? $t->description),
            'handover_note' => $t->handover_note ?? ($t->visit?->handover_note ?? 'Referred by Reception'),
            'category' => $t->category ?? 'Technical Support',
            'priority' => $t->priority ?? 'normal',
            'status' => $t->status ?? 'pending',
            'price' => $t->price,
            'location' => $t->location ?? ($t->visit?->location ?? 'Lobby 1'),
            'technician_notes' => $t->technician_notes,
            'diagnosis' => $t->diagnosis,
            'action_taken' => $t->action_taken,
            'resolution_summary' => $t->resolution_summary,
            'work_completed' => $t->work_completed,
            'customer_followup' => $t->customer_followup ?? 'none',
            'attachments' => is_array($t->attachments) ? $t->attachments : (json_decode((string)$t->attachments, true) ?: []),
            'qa_checklist' => is_array($t->qa_checklist) ? $t->qa_checklist : (json_decode((string)$t->qa_checklist, true) ?: []),
            'spare_parts' => is_array($t->spare_parts) ? $t->spare_parts : (json_decode((string)$t->spare_parts, true) ?: []),
            'device_specs' => is_array($t->device_specs) ? $t->device_specs : (json_decode((string)$t->device_specs, true) ?: []),
            'handover_notes' => $t->handover_notes,
            'reassigned_at' => $t->reassigned_at?->format('d M Y, H:i'),
            'aging_hours' => $t->aging_hours ?? 0,
            'is_aging' => $t->is_aging ?? false,
            'is_critical_overdue' => $t->is_critical_overdue ?? false,
            'accepted_at' => $t->accepted_at?->format('d M Y, H:i'),
            'started_at' => $t->started_at?->format('d M Y, H:i'),
            'resolved_at' => $t->resolved_at?->format('d M Y, H:i'),
            'return_reason' => $t->return_reason,
            'reported_by' => $t->reporter?->name ?? 'Reception',
            'assigned_to' => $t->assignee?->name ?? null,
            'assigned_user_id' => $t->assigned_to,
            'task_id' => $t->task_id,
            'visit_id' => $t->visit_id,
            'created_at' => $t->created_at?->format('d M Y, H:i') ?? now()->format('d M Y, H:i'),
            'created_at_time' => $t->created_at?->format('h:i A') ?? now()->format('h:i A'),
            'timelines' => $t->getAllTimelines()->map(function ($tl) {
                return [
                    'id' => $tl->id,
                    'event_type' => $tl->event_type,
                    'title' => $tl->title,
                    'description' => $tl->description,
                    'user_name' => $tl->user?->name ?? 'System',
                    'time' => $tl->created_at ? $tl->created_at->format('h:i A') : '',
                    'date' => $tl->created_at ? $tl->created_at->format('d M Y') : '',
                    'full_time' => $tl->created_at ? $tl->created_at->format('d M Y, H:i') : '',
                ];
            })->toArray(),
        ];
    }

    public function allInvoices(): array
    {
        try {
            $invoices = SaleInvoice::with(['itTicket', 'owner'])
                ->latest('id')
                ->get();

            return $invoices->map(function (SaleInvoice $inv) {
                return [
                    'id' => $inv->id,
                    'uuid' => $inv->uuid,
                    'it_ticket_id' => $inv->it_ticket_id,
                    'customer_name' => $inv->customer_name,
                    'company' => $inv->company ?? '',
                    'service' => $inv->service,
                    'amount' => $inv->amount,
                    'currency' => $inv->currency ?? 'TZS',
                    'status' => $inv->status,
                    'owner' => $inv->owner?->name ?? 'Sales Team',
                    'created_at' => $inv->created_at?->format('Y-m-d H:i') ?? now()->format('Y-m-d H:i'),
                    'paid_at' => $inv->paid_at?->format('Y-m-d H:i'),
                ];
            })->toArray();
        } catch (\Throwable $e) {
            Log::warning('[ItService] DB error in allInvoices, using fallback: ' . $e->getMessage());
            return $this->loadJson()['invoices'];
        }
    }

    public function allTasks(): array
    {
        try {
            $tasks = Task::with(['assignee', 'itTicket'])
                ->latest('id')
                ->get();

            return $tasks->map(function (Task $task) {
                return [
                    'id' => $task->id,
                    'uuid' => $task->uuid,
                    'title' => $task->title,
                    'description' => $task->description ?? '',
                    'priority' => $task->priority,
                    'status' => $task->status,
                    'assigned_to' => $task->assignee?->name ?? 'ICT Team',
                    'it_ticket_id' => $task->it_ticket_id,
                    'created_at' => $task->created_at?->format('Y-m-d H:i') ?? now()->format('Y-m-d H:i'),
                    'completed_at' => $task->completed_at?->format('Y-m-d H:i'),
                ];
            })->toArray();
        } catch (\Throwable $e) {
            Log::warning('[ItService] DB error in allTasks, using fallback: ' . $e->getMessage());
            return $this->loadJson()['tasks'];
        }
    }

    public function createTicket(array $data): array
    {
        try {
            $reporterId = auth()->id();
            if (!$reporterId) {
                $reporter = User::where('role', 'reception')->first();
                $reporterId = $reporter?->id;
            }

            $visitId = !empty($data['visit_id']) && is_numeric($data['visit_id']) ? (int)$data['visit_id'] : null;
            $customerType = $data['customer_type'] ?? 'individual';
            $todayCount = ItTicket::whereDate('created_at', today())->count() + 1;
            $ticketCode = 'TKT-' . now()->format('Ymd') . '-' . str_pad((string)$todayCount, 4, '0', STR_PAD_LEFT);

            $ticket = ItTicket::create([
                'uuid' => (string) Str::uuid(),
                'ticket_code' => $ticketCode,
                'customer_type' => $customerType,
                'visit_id' => $visitId,
                'reported_by' => $reporterId,
                'assigned_to' => !empty($data['assigned_to']) ? (int)$data['assigned_to'] : null,
                'visitor_name' => $data['visitor_name'] ?? 'Walk-in Guest',
                'visitor_company' => $data['visitor_company'] ?? null,
                'contact_person' => $data['contact_person'] ?? null,
                'contact_position' => $data['contact_position'] ?? null,
                'visitor_phone' => $data['visitor_phone'] ?? null,
                'title' => $data['title'] ?? 'PC Maintenance',
                'description' => $data['description'] ?? null,
                'customer_statement' => $data['customer_statement'] ?? ($data['description'] ?? null),
                'handover_note' => $data['handover_note'] ?? 'Logged directly in IT Service Portal',
                'category' => $data['category'] ?? 'Technical Support',
                'priority' => $data['priority'] ?? 'normal',
                'status' => 'pending',
                'price' => null,
                'location' => $data['location'] ?? 'Lobby 1',
            ]);

            // Auto-create linked Task — HARDENED creator
            $task = Task::create([
                'uuid' => (string) Str::uuid(),
                'title' => "[{$ticketCode}] {$ticket->title} for {$ticket->visitor_name}",
                'description' => $ticket->customer_statement ?: $ticket->handover_note,
                'priority' => $ticket->priority,
                'status' => 'pending',
                'assigned_to' => $ticket->assigned_to,
                'it_ticket_id' => $ticket->id,
                'created_by' => auth()->id() ?? $reporterId,
            ]);

            $ticket->update(['task_id' => $task->id]);

            $reporterName = auth()->user()?->name ?? 'Reception';
            $ticket->logActivity(
                eventType: 'ticket_created',
                title: "Ticket {$ticketCode} Created",
                description: "Ticket created and forwarded to IT Service Desk by {$reporterName}",
                department: 'reception',
                userId: $reporterId
            );

            // Send real database notification to IT Officers
            try {
                $itUsers = User::where('role', 'it')->orWhere('role', 'admin')->get();
                foreach ($itUsers as $itUser) {
                    if ($itUser->id !== $reporterId) {
                        $itUser->notify(new \App\Notifications\ItTicketCreated($ticket));
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('[ItService] Failed sending notification: ' . $e->getMessage());
            }

            return [
                'id' => $ticket->id,
                'uuid' => $ticket->uuid,
                'ticket_code' => $ticketCode,
                'visitor_name' => $ticket->visitor_name,
                'title' => $ticket->title,
                'status' => $ticket->status,
                'task_id' => $task->id,
            ];
        } catch (\Throwable $e) {
            Log::warning('[ItService] DB error in createTicket, writing to fallback JSON: ' . $e->getMessage());
            return $this->createTicketJson($data);
        }
    }

    /**
     * Accept ticket: transitions NEW -> ACCEPTED
     */
    public function acceptTicket(int $id, int|string|null $assignee = null, ?string $note = null): ?array
    {
        return DB::transaction(function () use ($id, $assignee, $note) {
            $ticket = ItTicket::find($id);
            if (!$ticket) {
                return $this->assignTicketJson($id, (string)$assignee);
            }

            $userId = null;
            if (is_numeric($assignee) && (int)$assignee > 0) {
                $userId = (int) $assignee;
            } elseif (auth()->check()) {
                $userId = auth()->id();
            } else {
                $itUser = User::where('role', 'it')->first();
                $userId = $itUser?->id;
            }

            $ticket->update([
                'assigned_to' => $userId,
                'status' => 'accepted',
                'accepted_at' => now(),
            ]);

            $technician = $userId ? User::find($userId) : auth()->user();
            $techName = $technician ? "{$technician->name} — IT Technician" : 'IT Technician';

            // Sync Task
            Task::where('it_ticket_id', $ticket->id)
                ->orWhere('id', $ticket->task_id)
                ->update([
                    'assigned_to' => $userId,
                    'status' => 'in_progress',
                ]);

            // Sync Visit
            if ($ticket->visit_id) {
                $visit = Visit::find($ticket->visit_id);
                if ($visit) {
                    $visit->update(['current_department' => 'it', 'status' => 'in_progress']);
                    $visit->services()->where('department', 'IT')->update([
                        'status' => 'accepted',
                        'assigned_to' => $userId,
                    ]);
                    $visit->logTimeline(
                        eventType: 'accepted',
                        title: "Ticket accepted",
                        description: "Accepted by {$techName}" . ($note ? " — {$note}" : ""),
                        department: 'it',
                        userId: $userId
                    );
                }
            }

            $ticket->logActivity(
                eventType: 'accepted',
                title: "Ticket accepted",
                description: "Accepted by {$techName}" . ($note ? " — {$note}" : ""),
                department: 'it',
                userId: $userId
            );

            return $ticket->fresh(['assignee', 'task'])->toArray();
        });
    }

    /**
     * Start work: transitions ACCEPTED -> IN PROGRESS
     */
    public function startWork(int $id, ?int $userId = null): ?array
    {
        return DB::transaction(function () use ($id, $userId) {
            $ticket = ItTicket::find($id);
            if (!$ticket) return null;

            $user = $userId ? User::find($userId) : auth()->user();
            $techName = $user ? "{$user->name} — IT Technician" : 'IT Technician';

            $ticket->update([
                'status' => 'in_progress',
                'started_at' => now(),
                'assigned_to' => $ticket->assigned_to ?: $user?->id,
            ]);

            Task::where('it_ticket_id', $ticket->id)
                ->orWhere('id', $ticket->task_id)
                ->update([
                    'status' => 'in_progress',
                    'assigned_to' => $ticket->assigned_to ?: $user?->id,
                ]);

            if ($ticket->visit_id) {
                $visit = Visit::find($ticket->visit_id);
                if ($visit) {
                    $visit->services()->where('department', 'IT')->update([
                        'status' => 'in_progress',
                        'started_at' => now(),
                    ]);
                    $visit->logTimeline(
                        eventType: 'in_progress',
                        title: "Work started",
                        description: "{$techName} commenced active diagnostics and maintenance",
                        department: 'it',
                        userId: $user?->id
                    );
                }
            }

            $ticket->logActivity(
                eventType: 'in_progress',
                title: "Work started",
                description: "{$techName} commenced active diagnostics and maintenance",
                department: 'it',
                userId: $user?->id
            );

            return $ticket->fresh()->toArray();
        });
    }

    /**
     * Update IT Workspace (Technician notes, diagnosis, action taken, attachments)
     */
    public function updateWorkspace(int $id, array $data, ?int $userId = null): ?array
    {
        return DB::transaction(function () use ($id, $data, $userId) {
            $ticket = ItTicket::find($id);
            if (!$ticket) return null;

            $user = $userId ? User::find($userId) : auth()->user();
            $techName = $user ? "{$user->name} — IT Technician" : 'IT Technician';

            $updatePayload = [];

            if (isset($data['status']) && in_array($data['status'], ['accepted', 'in_progress', 'waiting', 'resolved', 'closed', 'returned'])) {
                $oldStatus = $ticket->status;
                $updatePayload['status'] = $data['status'];

                if ($data['status'] === 'in_progress' && !$ticket->started_at) {
                    $updatePayload['started_at'] = now();
                }

                if ($oldStatus !== $data['status']) {
                    $ticket->logActivity(
                        eventType: 'status_changed',
                        title: "Status changed to " . strtoupper(str_replace('_', ' ', $data['status'])),
                        description: "Status updated by {$techName}",
                        department: 'it',
                        userId: $user?->id
                    );
                }
            }

            if (isset($data['technician_notes'])) {
                $updatePayload['technician_notes'] = $data['technician_notes'];
            }
            if (isset($data['diagnosis'])) {
                $updatePayload['diagnosis'] = $data['diagnosis'];
            }
            if (isset($data['action_taken'])) {
                $updatePayload['action_taken'] = $data['action_taken'];
            }
            if (isset($data['resolution_summary'])) {
                $updatePayload['resolution_summary'] = $data['resolution_summary'];
            }
            if (isset($data['work_completed'])) {
                $updatePayload['work_completed'] = $data['work_completed'];
            }
            if (isset($data['customer_followup'])) {
                $updatePayload['customer_followup'] = $data['customer_followup'];
            }
            if (isset($data['price']) && is_numeric($data['price'])) {
                $updatePayload['price'] = (int)$data['price'];
            }
            if (isset($data['assigned_to']) && is_numeric($data['assigned_to'])) {
                $updatePayload['assigned_to'] = (int)$data['assigned_to'];
            }
            if (isset($data['attachments'])) {
                $existing = is_array($ticket->attachments) ? $ticket->attachments : [];
                $newAttachments = is_array($data['attachments']) ? $data['attachments'] : [$data['attachments']];
                $updatePayload['attachments'] = array_values(array_merge($existing, $newAttachments));
            }
            if (isset($data['qa_checklist']) && is_array($data['qa_checklist'])) {
                $updatePayload['qa_checklist'] = $data['qa_checklist'];
            }
            if (isset($data['spare_parts']) && is_array($data['spare_parts'])) {
                $updatePayload['spare_parts'] = $data['spare_parts'];
            }
            if (isset($data['device_specs']) && is_array($data['device_specs'])) {
                $updatePayload['device_specs'] = $data['device_specs'];
            }

            $ticket->update($updatePayload);

            $ticket->logActivity(
                eventType: 'workspace_saved',
                title: "Workspace details updated",
                description: "Technician notes & work order updated by {$techName}",
                department: 'it',
                userId: $user?->id
            );

            return $ticket->fresh()->toArray();
        });
    }

    /**
     * Resolve Ticket with resolution modal data and auto-bill dispatch
     */
    public function resolveTicket(int $id, array|int $dataOrPrice, string $notes = ''): ?array
    {
        return DB::transaction(function () use ($id, $dataOrPrice, $notes) {
            $ticket = ItTicket::find($id);
            if (!$ticket) {
                $price = is_array($dataOrPrice) ? (int)($dataOrPrice['price'] ?? 0) : (int)$dataOrPrice;
                return $this->resolveTicketJson($id, $price, $notes);
            }

            $price = is_array($dataOrPrice) ? (int)($dataOrPrice['price'] ?? 0) : (int)$dataOrPrice;
            $resolutionSummary = is_array($dataOrPrice) ? ($dataOrPrice['resolution_summary'] ?? ($notes ?: 'PC issue resolved')) : ($notes ?: 'PC issue resolved');
            $workCompleted = is_array($dataOrPrice) ? ($dataOrPrice['work_completed'] ?? $notes) : $notes;
            $customerFollowup = is_array($dataOrPrice) ? ($dataOrPrice['customer_followup'] ?? 'none') : 'none';
            $techNotes = is_array($dataOrPrice) ? ($dataOrPrice['technician_notes'] ?? $ticket->technician_notes) : $ticket->technician_notes;
            $diagnosis = is_array($dataOrPrice) ? ($dataOrPrice['diagnosis'] ?? $ticket->diagnosis) : $ticket->diagnosis;
            $actionTaken = is_array($dataOrPrice) ? ($dataOrPrice['action_taken'] ?? $ticket->action_taken) : $ticket->action_taken;

            $user = auth()->user();
            $techName = $user ? "{$user->name} — IT Technician" : 'IT Technician';

            $ticket->update([
                'status' => 'resolved',
                'price' => $price,
                'resolution_summary' => $resolutionSummary,
                'work_completed' => $workCompleted,
                'customer_followup' => $customerFollowup,
                'technician_notes' => $techNotes,
                'diagnosis' => $diagnosis,
                'action_taken' => $actionTaken,
                'resolved_at' => now(),
            ]);

            // Auto complete linked task
            Task::where('it_ticket_id', $ticket->id)
                ->orWhere('id', $ticket->task_id)
                ->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

            // Auto-create Sale Invoice for Sales team
            $salesUser = User::where('role', 'sales')->first();
            SaleInvoice::create([
                'uuid' => (string) Str::uuid(),
                'it_ticket_id' => $ticket->id,
                'customer_name' => $ticket->visitor_name,
                'company' => $ticket->visitor_company,
                'service' => $ticket->title . ' — ' . $ticket->category,
                'amount' => $price,
                'currency' => 'TZS',
                'status' => 'pending_payment',
                'owner_id' => $salesUser?->id,
            ]);

            // If spare parts were used in this work order, deduct inventory stock count
            if (!empty($ticket->spare_parts) && !empty($ticket->spare_parts['product_id'])) {
                $productId = (int) $ticket->spare_parts['product_id'];
                $qty = max(1, (int) ($ticket->spare_parts['qty'] ?? 1));
                $product = \App\Models\PosProduct::find($productId);
                if ($product && !$product->is_service) {
                    $product->decrement('stock_quantity', min($qty, (int)$product->stock_quantity));
                }
            }

            // If linked to a Live Visit, update visit charges & timeline
            if ($ticket->visit_id) {
                $visit = Visit::find($ticket->visit_id);
                if ($visit) {
                    $visit->services()->where('department', 'IT')->update([
                        'status' => 'completed',
                        'resolution_notes' => "{$resolutionSummary} | Work: {$workCompleted}",
                        'price' => $price,
                        'completed_by' => auth()->id(),
                        'completed_at' => now(),
                    ]);
                    $visit->recalculateTotal();
                    $visit->logTimeline(
                        eventType: 'completed',
                        title: "Ticket resolved",
                        description: "Resolved by {$techName}. Summary: {$resolutionSummary}" . ($price > 0 ? " | Added Charge: TZS " . number_format($price) : ""),
                        department: 'it',
                        userId: auth()->id(),
                        metadata: ['price' => $price, 'ticket_id' => $ticket->id]
                    );

                    // Check if ready for billing
                    $allDone = $visit->services()->whereIn('status', ['pending', 'accepted', 'in_progress'])->count() === 0;
                    if ($allDone) {
                        $visit->update(['status' => 'ready_for_billing', 'current_department' => 'sales']);
                    }
                }
            }

            $ticket->logActivity(
                eventType: 'resolved',
                title: "Ticket resolved",
                description: "Resolved by {$techName}. Summary: {$resolutionSummary}" . ($price > 0 ? " | Billable: TZS " . number_format($price) : ""),
                department: 'it',
                userId: auth()->id(),
                metadata: ['price' => $price, 'followup' => $customerFollowup]
            );

            // Send real database notification to Sales Officers and Admins
            try {
                $recipients = User::whereIn('role', ['sales', 'admin'])->get();
                foreach ($recipients as $recipient) {
                    if ($recipient->id !== auth()->id()) {
                        $recipient->notify(new \App\Notifications\ItTicketResolved($ticket, (int)$price));
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('[ItService] Failed sending resolve notification: ' . $e->getMessage());
            }

            return $ticket->fresh()->toArray();
        });
    }

    /**
     * Reject / Return ticket back to Reception
     */
    public function returnTicket(int $id, string $returnReason, ?int $userId = null): ?array
    {
        return DB::transaction(function () use ($id, $returnReason, $userId) {
            $ticket = ItTicket::find($id);
            if (!$ticket) return null;

            $user = $userId ? User::find($userId) : auth()->user();
            $techName = $user ? "{$user->name} — IT Technician" : 'IT Technician';

            $ticket->update([
                'status' => 'returned',
                'return_reason' => $returnReason,
            ]);

            Task::where('it_ticket_id', $ticket->id)
                ->orWhere('id', $ticket->task_id)
                ->update([
                    'status' => 'returned',
                ]);

            if ($ticket->visit_id) {
                $visit = Visit::find($ticket->visit_id);
                if ($visit) {
                    $visit->update(['current_department' => 'reception', 'status' => 'checked_in']);
                    $visit->logTimeline(
                        eventType: 'returned',
                        title: "Ticket returned to Reception",
                        description: "Returned by {$techName} — Reason: {$returnReason}",
                        department: 'reception',
                        userId: $user?->id
                    );
                }
            }

            $ticket->logActivity(
                eventType: 'returned',
                title: "Ticket returned to Reception",
                description: "Returned by {$techName} — Reason: {$returnReason}",
                department: 'it',
                userId: $user?->id
            );

            // Send real database notification to Reception Officers and Admins
            try {
                $recipients = User::whereIn('role', ['reception', 'admin'])->get();
                foreach ($recipients as $recipient) {
                    if ($recipient->id !== ($user?->id)) {
                        $recipient->notify(new \App\Notifications\ItTicketReturned($ticket, $returnReason));
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('[ItService] Failed sending return notification: ' . $e->getMessage());
            }

            return $ticket->fresh()->toArray();
        });
    }

    /**
     * Reassign ticket to another technician with handover note and timeline audit
     */
    public function reassignTicket(int $id, int $newAssigneeId, ?string $handoverNotes = null, ?int $actorId = null): ?array
    {
        return DB::transaction(function () use ($id, $newAssigneeId, $handoverNotes, $actorId) {
            $ticket = ItTicket::find($id);
            if (!$ticket) return null;

            $newAssignee = User::find($newAssigneeId);
            $actor = $actorId ? User::find($actorId) : auth()->user();
            $actorName = $actor ? $actor->name : 'System';
            $targetName = $newAssignee ? $newAssignee->name : "Technician #{$newAssigneeId}";

            $ticket->update([
                'assigned_to' => $newAssigneeId,
                'handover_notes' => $handoverNotes,
                'reassigned_at' => now(),
            ]);

            Task::where('it_ticket_id', $ticket->id)
                ->orWhere('id', $ticket->task_id)
                ->update(['assigned_to' => $newAssigneeId]);

            $desc = "Reassigned from {$ticket->assignee?->name} to {$targetName} by {$actorName}";
            if ($handoverNotes) {
                $desc .= " — Handover Note: {$handoverNotes}";
            }

            $ticket->logActivity(
                eventType: 'reassigned',
                title: "Ticket Reassigned to {$targetName}",
                description: $desc,
                department: 'it',
                userId: $actor?->id,
                metadata: ['new_assignee_id' => $newAssigneeId, 'handover_notes' => $handoverNotes]
            );

            // Send real database notification to newly assigned technician
            try {
                if ($newAssignee && $newAssignee->id !== ($actor?->id)) {
                    $newAssignee->notify(new \App\Notifications\ItTicketReassigned($ticket, $handoverNotes));
                }
            } catch (\Throwable $e) {
                Log::warning('[ItService] Failed sending reassignment notification: ' . $e->getMessage());
            }

            return $ticket->fresh(['assignee', 'task'])->toArray();
        });
    }

    /**
     * Helper to assign ticket (backward-compatible wrapper)
     */
    public function assignTicket(int $id, string|int $assignee): ?array
    {
        return $this->acceptTicket($id, $assignee);
    }

    public function payInvoice(int $id): ?array
    {
        try {
            $invoice = SaleInvoice::find($id);
            if (!$invoice) {
                return $this->payInvoiceJson($id);
            }

            $invoice->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            return $invoice->fresh()->toArray();
        } catch (\Throwable $e) {
            Log::warning('[ItService] DB error in payInvoice, fallback: ' . $e->getMessage());
            return $this->payInvoiceJson($id);
        }
    }

    public function stats(): array
    {
        try {
            $ticketsTotal = ItTicket::count();
            $ticketsPending = ItTicket::whereIn('status', ['pending', 'new', 'assigned', 'accepted', 'in_progress', 'waiting'])->count();
            $ticketsResolved = ItTicket::where('status', 'resolved')->count();

            // SLA Aging Calculations (>24h and >48h)
            $agingThreshold24 = now()->subHours(24);
            $agingThreshold48 = now()->subHours(48);

            $agingCount = ItTicket::whereIn('status', ['pending', 'new', 'assigned', 'accepted', 'in_progress', 'waiting'])
                ->where('created_at', '<=', $agingThreshold24)
                ->count();

            $criticalOverdueCount = ItTicket::whereIn('status', ['pending', 'new', 'assigned', 'accepted', 'in_progress', 'waiting'])
                ->where('created_at', '<=', $agingThreshold48)
                ->count();

            $invoicesPending = SaleInvoice::where('status', 'pending_payment')->count();
            $invoicesPaid = SaleInvoice::where('status', 'paid')->count();

            $revenuePending = (int) SaleInvoice::where('status', 'pending_payment')->sum('amount');
            $revenuePaid = (int) SaleInvoice::where('status', 'paid')->sum('amount');

            $tasksPending = Task::where('status', '!=', 'completed')->count();
            $tasksCompleted = Task::where('status', 'completed')->count();

            return [
                'tickets_total' => $ticketsTotal,
                'tickets_pending' => $ticketsPending,
                'tickets_resolved' => $ticketsResolved,
                'aging_count' => $agingCount,
                'critical_overdue_count' => $criticalOverdueCount,
                'invoices_pending' => $invoicesPending,
                'invoices_paid' => $invoicesPaid,
                'revenue_pending' => $revenuePending,
                'revenue_paid' => $revenuePaid,
                'tasks_pending' => $tasksPending,
                'tasks_completed' => $tasksCompleted,
            ];
        } catch (\Throwable $e) {
            Log::warning('[ItService] DB error in stats, using fallback: ' . $e->getMessage());
            $data = $this->loadJson();
            $tickets = $data['tickets'];
            $invoices = $data['invoices'];
            $tasks = $data['tasks'];
            return [
                'tickets_total' => count($tickets),
                'tickets_pending' => count(array_filter($tickets, fn($t) => in_array($t['status'], ['pending', 'assigned', 'accepted', 'in_progress']))),
                'tickets_resolved' => count(array_filter($tickets, fn($t) => $t['status'] === 'resolved')),
                'invoices_pending' => count(array_filter($invoices, fn($i) => $i['status'] === 'pending_payment')),
                'invoices_paid' => count(array_filter($invoices, fn($i) => $i['status'] === 'paid')),
                'revenue_pending' => array_sum(array_map(fn($i) => $i['status'] === 'pending_payment' ? $i['amount'] : 0, $invoices)),
                'revenue_paid' => array_sum(array_map(fn($i) => $i['status'] === 'paid' ? $i['amount'] : 0, $invoices)),
                'tasks_pending' => count(array_filter($tasks, fn($t) => $t['status'] !== 'completed')),
                'tasks_completed' => count(array_filter($tasks, fn($t) => $t['status'] === 'completed')),
            ];
        }
    }

    /* -------------------------------------------------------------
     * JSON Fallback implementations for offline mode
     * ------------------------------------------------------------- */
    private function createTicketJson(array $data): array
    {
        $store = $this->loadJson();
        $id = count($store['tickets']) + 1;
        $ticketCode = 'TKT-' . now()->format('Ymd') . '-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
        $ticket = [
            'id' => $id,
            'uuid' => (string) Str::uuid(),
            'ticket_code' => $ticketCode,
            'customer_type' => $data['customer_type'] ?? 'individual',
            'visit_id' => $data['visit_id'] ?? null,
            'visitor_name' => $data['visitor_name'] ?? 'Walk-in Guest',
            'visitor_company' => $data['visitor_company'] ?? '',
            'contact_person' => $data['contact_person'] ?? '',
            'visitor_phone' => $data['visitor_phone'] ?? '',
            'title' => $data['title'] ?? 'PC Maintenance',
            'description' => $data['description'] ?? '',
            'customer_statement' => $data['customer_statement'] ?? $data['description'] ?? '',
            'handover_note' => $data['handover_note'] ?? 'Logged directly in IT Portal',
            'category' => $data['category'] ?? 'Technical Support',
            'priority' => $data['priority'] ?? 'normal',
            'status' => 'pending',
            'price' => null,
            'location' => $data['location'] ?? 'Lobby 1',
            'reported_by' => 'Reception — Aisha Mwinyi',
            'assigned_to' => null,
            'created_at' => now()->format('d M Y, H:i'),
            'resolved_at' => null,
            'timelines' => [],
        ];
        $store['tickets'][] = $ticket;

        $task = [
            'id' => count($store['tasks']) + 1,
            'uuid' => (string) Str::uuid(),
            'title' => "[{$ticketCode}] " . $ticket['title'] . ' for ' . $ticket['visitor_name'],
            'description' => $ticket['description'],
            'priority' => $ticket['priority'],
            'status' => 'pending',
            'assigned_to' => 'ICT Team',
            'it_ticket_id' => $ticket['id'],
            'created_at' => now()->format('Y-m-d H:i'),
            'completed_at' => null,
        ];
        $store['tasks'][] = $task;
        $store['tickets'][array_key_last($store['tickets'])]['task_id'] = $task['id'];

        $this->saveJson($store);
        return $ticket;
    }

    private function assignTicketJson(int $id, string $assignee): ?array
    {
        $store = $this->loadJson();
        foreach ($store['tickets'] as &$t) {
            if ($t['id'] === $id) {
                $t['assigned_to'] = $assignee;
                $t['status'] = 'accepted';
                $t['accepted_at'] = now()->format('d M Y, H:i');
                break;
            }
        }
        foreach ($store['tasks'] as &$task) {
            if (($task['it_ticket_id'] ?? null) === $id) {
                $task['assigned_to'] = $assignee;
                $task['status'] = 'in_progress';
            }
        }
        $this->saveJson($store);
        return $this->findTicket($id);
    }

    private function resolveTicketJson(int $id, int $price, string $notes = ''): ?array
    {
        $store = $this->loadJson();
        $ticket = null;
        foreach ($store['tickets'] as &$t) {
            if ($t['id'] === $id) {
                $t['price'] = $price;
                $t['status'] = 'resolved';
                $t['resolved_at'] = now()->format('d M Y, H:i');
                $t['resolution_summary'] = $notes;
                if ($notes) {
                    $t['description'] = trim(($t['description'] ?? '') . "\n\n[Resolution Note]: " . $notes);
                }
                $ticket = $t;
                break;
            }
        }
        if (!$ticket) return null;

        foreach ($store['tasks'] as &$task) {
            if (($task['it_ticket_id'] ?? null) === $id) {
                $task['status'] = 'completed';
                $task['completed_at'] = now()->format('Y-m-d H:i');
            }
        }

        $invoice = [
            'id' => count($store['invoices']) + 1,
            'uuid' => (string) Str::uuid(),
            'it_ticket_id' => $id,
            'customer_name' => $ticket['visitor_name'],
            'company' => $ticket['visitor_company'],
            'service' => $ticket['title'] . ' — ' . $ticket['category'],
            'amount' => $price,
            'currency' => 'TZS',
            'status' => 'pending_payment',
            'owner' => 'Sales Team',
            'created_at' => now()->format('Y-m-d H:i'),
            'paid_at' => null,
        ];
        $store['invoices'][] = $invoice;
        $this->saveJson($store);
        return $ticket;
    }

    private function payInvoiceJson(int $id): ?array
    {
        $store = $this->loadJson();
        foreach ($store['invoices'] as &$inv) {
            if ($inv['id'] === $id) {
                $inv['status'] = 'paid';
                $inv['paid_at'] = now()->format('Y-m-d H:i');
                break;
            }
        }
        $this->saveJson($store);
        return $store['invoices'][array_key_last($store['invoices'])] ?? null;
    }
}
