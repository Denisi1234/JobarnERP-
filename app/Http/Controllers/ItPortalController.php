<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\ItTicket;
use App\Models\User;
use App\Services\ItService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ItPortalController extends Controller
{
    public function index(ItService $svc)
    {
        $tickets = $svc->allTickets();
        $allInvoices = $svc->allInvoices();
        // Only show Service Invoices that originated from IT and were marked paid by Sales
        $invoices = array_values(array_filter($allInvoices, function ($inv) {
            return !empty($inv['it_ticket_id']) && ($inv['status'] ?? '') === 'paid';
        }));
        $tasks = $svc->allTasks();
        $stats = $svc->stats();
        // Override revenue/pending counts to IT-originated paid invoices only for IT view
        $itPaidCount = count($invoices);
        $itRevenuePaid = array_sum(array_map(fn($i) => $i['amount'] ?? 0, $invoices));
        $stats['invoices_paid'] = $itPaidCount;
        $stats['revenue_paid'] = $itRevenuePaid;
        // Keep pending as IT-originated pending for awareness, but invoices list stays paid-only per request
        $stats['invoices_pending'] = count(array_filter($allInvoices, fn($i) => !empty($i['it_ticket_id']) && ($i['status'] ?? '') === 'pending_payment'));
        $stats['revenue_pending'] = array_sum(array_map(fn($i) => !empty($i['it_ticket_id']) && ($i['status'] ?? '') === 'pending_payment' ? $i['amount'] : 0, $allInvoices));
        $itOfficers = User::where('role', 'it')->orWhere('role', 'admin')->get();
        if ($itOfficers->isEmpty()) {
            $itOfficers = User::all();
        }

        return view('it.index', compact('tickets', 'invoices', 'tasks', 'stats', 'itOfficers'));
    }

    public function show(int $id, ItService $svc)
    {
        $ticket = $svc->findTicket($id);
        if (!$ticket) {
            abort(404, 'IT Ticket not found');
        }

        $itOfficers = User::where('role', 'it')->orWhere('role', 'admin')->get();
        if ($itOfficers->isEmpty()) {
            $itOfficers = User::all();
        }

        $products = \App\Models\PosProduct::active()->inStock()->orderBy('name')->get(['id', 'name', 'sku', 'price', 'stock_quantity']);

        return view('it.show', compact('ticket', 'itOfficers', 'products'));
    }

    public function store(Request $request, ItService $svc)
    {
        $data = $request->validate([
            'visit_id' => 'nullable|integer',
            'visitor_name' => 'required|string|max:255',
            'visitor_company' => 'nullable|string|max:255',
            'visitor_phone' => 'nullable|string|max:50',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'customer_statement' => 'nullable|string',
            'handover_note' => 'nullable|string',
            'category' => 'required|string',
            'priority' => 'required|string',
            'location' => 'nullable|string|max:100',
            'assigned_to' => 'nullable|integer',
        ]);

        $ticket = $svc->createTicket($data);
        return redirect()->route('it.tickets.show', $ticket['id'])->with('success', 'IT Ticket #' . $ticket['id'] . ' created — Task auto-created & Work Order ready ✅');
    }

    public function accept(Request $request, int $id, ItService $svc)
    {
        $assignee = $request->input('assigned_to') ?? ($request->input('assignee') ?? auth()->id());
        $note = $request->input('note');
        
        $svc->acceptTicket($id, $assignee, $note);
        return redirect()->route('it.tickets.show', $id)->with('success', 'Ticket accepted ✅ — Work Order is now active');
    }

    public function start(Request $request, int $id, ItService $svc)
    {
        $svc->startWork($id);
        return redirect()->route('it.tickets.show', $id)->with('success', 'Work started on ticket ✅');
    }

    public function reassign(Request $request, int $id, ItService $svc)
    {
        $data = $request->validate([
            'assigned_to' => 'required|integer|exists:users,id',
            'handover_notes' => 'nullable|string|max:1000',
        ]);

        $svc->reassignTicket($id, (int)$data['assigned_to'], $data['handover_notes'] ?? null);

        return redirect()->route('it.tickets.show', $id)->with('success', 'Ticket reassigned and shift handover recorded ✅');
    }

    public function updateWorkspace(Request $request, int $id, ItService $svc)
    {
        $data = $request->validate([
            'status' => 'nullable|string|in:accepted,in_progress,waiting,resolved,closed,returned',
            'technician_notes' => 'nullable|string',
            'diagnosis' => 'nullable|string',
            'action_taken' => 'nullable|string',
            'resolution_summary' => 'nullable|string',
            'work_completed' => 'nullable|string',
            'customer_followup' => 'nullable|string',
            'price' => 'nullable|integer|min:0',
            'assigned_to' => 'nullable|integer',
            'qa_checklist' => 'nullable|array',
            'spare_parts' => 'nullable|array',
            'device_specs' => 'nullable|array',
            'photos.*' => 'nullable|file|image|max:10240',
            'files.*' => 'nullable|file|max:20480',
        ]);

        // Process file uploads if any
        $uploadedAttachments = [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                if ($photo->isValid()) {
                    $path = $photo->store('it_attachments', 'public');
                    $uploadedAttachments[] = [
                        'name' => $photo->getClientOriginalName(),
                        'path' => '/storage/' . $path,
                        'type' => 'image',
                        'size' => $photo->getSize(),
                        'uploaded_at' => now()->format('d M Y, H:i'),
                    ];
                }
            }
        }
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                if ($file->isValid()) {
                    $path = $file->store('it_attachments', 'public');
                    $uploadedAttachments[] = [
                        'name' => $file->getClientOriginalName(),
                        'path' => '/storage/' . $path,
                        'type' => 'file',
                        'size' => $file->getSize(),
                        'uploaded_at' => now()->format('d M Y, H:i'),
                    ];
                }
            }
        }

        if (!empty($uploadedAttachments)) {
            $data['attachments'] = $uploadedAttachments;
        }

        $svc->updateWorkspace($id, $data);

        return redirect()->route('it.tickets.show', $id)->with('success', 'Work Order workspace saved ✅');
    }

    public function resolve(Request $request, int $id, ItService $svc)
    {
        $data = $request->validate([
            'resolution_summary' => 'required|string|max:1000',
            'work_completed' => 'required|string|max:2000',
            'customer_followup' => 'nullable|string|in:none,monitor_pc,contact_customer',
            'price' => 'nullable|integer|min:0',
            'technician_notes' => 'nullable|string',
            'diagnosis' => 'nullable|string',
            'action_taken' => 'nullable|string',
        ]);

        $price = (int)($data['price'] ?? 0);
        $svc->resolveTicket($id, $data);

        $priceFormatted = $price > 0 ? " & Sales invoice TZS " . number_format($price) . " dispatched to Sales" : "";
        return redirect()->route('it.tickets.show', $id)->with('success', "Ticket resolved{$priceFormatted} ✅");
    }

    public function returnTicket(Request $request, int $id, ItService $svc)
    {
        $data = $request->validate([
            'return_reason' => 'required|string|max:1000',
        ]);

        $svc->returnTicket($id, $data['return_reason']);

        return redirect()->route('it.index')->with('success', "Ticket returned to Reception ✅");
    }

    public function assign(Request $request, int $id, ItService $svc)
    {
        return $this->accept($request, $id, $svc);
    }

    public function exportTickets(Request $request)
    {
        $status = $request->query('status');
        $query = ItTicket::with(['assignee', 'reporter', 'task'])->latest('id');

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $tickets = $query->get();
        $filename = 'it_tickets_export_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($tickets) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'Ticket Code',
                'Customer Type',
                'Customer Name',
                'Company',
                'Contact Person',
                'Phone',
                'Issue Title',
                'Category',
                'Priority',
                'Status',
                'Assigned Technician',
                'Reported By',
                'Technician Notes',
                'Diagnosis',
                'Action Taken',
                'Resolution Summary',
                'Price (TZS)',
                'Created Date',
                'Resolved Date',
            ]);

            foreach ($tickets as $t) {
                fputcsv($handle, [
                    $t->ticket_code ?? ('#IT-' . $t->id),
                    ucfirst($t->customer_type ?? 'individual'),
                    $t->visitor_name ?? '—',
                    $t->visitor_company ?? '—',
                    $t->contact_person ?? '—',
                    $t->visitor_phone ?? '—',
                    $t->title ?? '—',
                    $t->category ?? '—',
                    strtoupper($t->priority ?? 'normal'),
                    strtoupper($t->status ?? 'pending'),
                    $t->assignee?->name ?? 'Unassigned',
                    $t->reporter?->name ?? 'Reception',
                    $t->technician_notes ?? '',
                    $t->diagnosis ?? '',
                    $t->action_taken ?? '',
                    $t->resolution_summary ?? '',
                    $t->price ? number_format($t->price) : '0',
                    $t->created_at ? $t->created_at->format('Y-m-d H:i') : '—',
                    $t->resolved_at ? $t->resolved_at->format('Y-m-d H:i') : '—',
                ]);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function pay(int $id, ItService $svc)
    {
        $svc->payInvoice($id);
        return redirect()->route('sales.index')->with('success', 'Invoice #' . $id . ' marked paid — Take Money done ✅');
    }
}
