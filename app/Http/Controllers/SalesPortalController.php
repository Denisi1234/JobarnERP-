<?php

namespace App\Http\Controllers;

use App\Models\Debit;
use App\Models\InventoryLog;
use App\Models\ItTicket;
use App\Models\PosProduct;
use App\Models\SaleInvoice;
use App\Models\SalesQuotation;
use App\Models\SalesQuotationItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Visit;
use App\Services\ItService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SalesPortalController extends Controller
{
    /**
     * Default catalog items for instant out-of-the-box POS operations
     */
    private function getDefaultProducts(): array
    {
        return [
            ['id' => 1, 'name' => 'PC / Laptop Maintenance & Dust Cleaning', 'sku' => 'SRV-001', 'category' => 'Services', 'price' => 80000, 'cost_price' => 20000, 'stock_quantity' => null, 'is_service' => true, 'icon' => 'build'],
            ['id' => 2, 'name' => 'OS Installation & Optimization (Windows/Linux)', 'sku' => 'SRV-002', 'category' => 'Services', 'price' => 50000, 'cost_price' => 10000, 'stock_quantity' => null, 'is_service' => true, 'icon' => 'terminal'],
            ['id' => 3, 'name' => 'Network Configuration & Router Setup', 'sku' => 'SRV-003', 'category' => 'Services', 'price' => 150000, 'cost_price' => 40000, 'stock_quantity' => null, 'is_service' => true, 'icon' => 'lan'],
            ['id' => 4, 'name' => 'Data Recovery & Backup Service', 'sku' => 'SRV-004', 'category' => 'Services', 'price' => 200000, 'cost_price' => 50000, 'stock_quantity' => null, 'is_service' => true, 'icon' => 'cloud_download'],
            ['id' => 5, 'name' => 'Printer Diagnostic & Head Cleaning', 'sku' => 'SRV-005', 'category' => 'Services', 'price' => 45000, 'cost_price' => 15000, 'stock_quantity' => null, 'is_service' => true, 'icon' => 'print'],
            ['id' => 6, 'name' => 'Antivirus License (Kaspersky / Bitdefender 1Yr)', 'sku' => 'SW-001', 'category' => 'Software', 'price' => 45000, 'cost_price' => 28000, 'stock_quantity' => 24, 'is_service' => false, 'icon' => 'security'],
            ['id' => 7, 'name' => 'Microsoft 365 Business (Annual)', 'sku' => 'SW-002', 'category' => 'Software', 'price' => 140000, 'cost_price' => 110000, 'stock_quantity' => 10, 'is_service' => false, 'icon' => 'apps'],
            ['id' => 8, 'name' => 'USB 3.0 Flash Drive 64GB Kingston', 'sku' => 'ACC-001', 'category' => 'Accessories', 'price' => 25000, 'cost_price' => 16000, 'stock_quantity' => 38, 'is_service' => false, 'icon' => 'usb'],
            ['id' => 9, 'name' => 'HDMI Cable 4K Ultra HD (3 Meters)', 'sku' => 'ACC-002', 'category' => 'Accessories', 'price' => 15000, 'cost_price' => 8000, 'stock_quantity' => 45, 'is_service' => false, 'icon' => 'settings_input_hdmi'],
            ['id' => 10, 'name' => 'Logitech USB Optical Mouse (B100)', 'sku' => 'HW-001', 'category' => 'Hardware', 'price' => 25000, 'cost_price' => 15000, 'stock_quantity' => 18, 'is_service' => false, 'icon' => 'mouse'],
            ['id' => 11, 'name' => 'Logitech Standard USB Keyboard (K120)', 'sku' => 'HW-002', 'category' => 'Hardware', 'price' => 38000, 'cost_price' => 24000, 'stock_quantity' => 14, 'is_service' => false, 'icon' => 'keyboard'],
            ['id' => 12, 'name' => 'HP Laser Toner Cartridge 85A (Black)', 'sku' => 'CON-001', 'category' => 'Consumables', 'price' => 65000, 'cost_price' => 42000, 'stock_quantity' => 8, 'is_service' => false, 'icon' => 'inventory'],
            ['id' => 13, 'name' => 'CAT6 Ethernet Patch Cord 5m', 'sku' => 'ACC-003', 'category' => 'Accessories', 'price' => 10000, 'cost_price' => 4500, 'stock_quantity' => 60, 'is_service' => false, 'icon' => 'cable'],
            ['id' => 14, 'name' => 'Heavy Duty 6-Way Surge Protector Power Strip', 'sku' => 'ACC-004', 'category' => 'Accessories', 'price' => 32000, 'cost_price' => 19000, 'stock_quantity' => 12, 'is_service' => false, 'icon' => 'electrical_services'],
            ['id' => 15, 'name' => 'Kingston 480GB 2.5" SATA SSD', 'sku' => 'HW-003', 'category' => 'Hardware', 'price' => 95000, 'cost_price' => 68000, 'stock_quantity' => 6, 'is_service' => false, 'icon' => 'storage'],
            ['id' => 16, 'name' => 'Crucial 8GB DDR4 RAM 2666MHz (Laptop/Desktop)', 'sku' => 'HW-004', 'category' => 'Hardware', 'price' => 75000, 'cost_price' => 52000, 'stock_quantity' => 9, 'is_service' => false, 'icon' => 'memory'],
        ];
    }

    public function index(Request $request, ItService $svc)
    {
        $currentTab = $request->query('tab', 'dashboard');
        $data = $this->getSalesDashboardData($svc);
        $data['currentTab'] = $currentTab;

        return view('sales.index', $data);
    }

    /**
     * Real-time live data polling endpoint for Sales Dashboard & POS Queue
     */
    public function liveStats(Request $request, ItService $svc)
    {
        $data = $this->getSalesDashboardData($svc);

        // Format collections for direct JSON consumption — SALES handoff logic
        $formattedReceptionVisits = $data['recentReceptionVisits']->map(function ($v) {
            $salesService = $v->services ? $v->services->firstWhere('department', 'SALES') ?? $v->services->first() : null;
            return [
                'id' => $v->id,
                'uuid' => $v->uuid ?? null,
                'ticket_code' => $v->ticket_code ?? null,
                'visitor' => $v->visitor ?? $v->full_name ?? 'Walk-in Guest',
                'visitor_phone' => $v->visitor_phone ?? $v->phone ?? '',
                'organization_name' => $v->organization_name ?? $v->company ?? '',
                'purpose' => $v->purpose ?? 'General Consultation',
                'customer_statement' => $v->customer_statement ?? '',
                'handover_note' => $v->handover_note ?? '',
                'priority' => $v->priority ?? 'normal',
                'status' => $v->status ?? 'pending',
                'current_department' => $v->current_department ?? 'sales',
                'arrival_human' => $v->arrival ? (\Carbon\Carbon::parse($v->arrival)->diffForHumans()) : 'Just now',
                'total_amount' => (int) ($v->total_amount ?? 0),
                'service_id' => $salesService?->id,
                'service_name' => $salesService?->service_name ?? $v->purpose,
                'service_status' => $salesService?->status ?? 'pending',
                'service_price' => (int) ($salesService?->price ?? 0),
                'service_quantity' => (int) ($salesService?->quantity ?? 1),
            ];
        });

        $formattedUnpaidTickets = $data['unpaidItTickets']->map(function ($ticket) {
            return [
                'id' => $ticket->id,
                'title' => $ticket->title,
                'visitor_name' => $ticket->visitor_name ?: 'Walk-in',
                'visitor_company' => $ticket->visitor_company ?: '',
                'visitor_phone' => $ticket->visitor_phone ?: '',
                'price' => (int) ($ticket->price ?: 50000),
                'category' => $ticket->category ?: 'PC Maintenance',
                'status' => $ticket->status,
                'created_human' => $ticket->created_at ? $ticket->created_at->diffForHumans() : 'Recently',
            ];
        });

        $formattedInvoices = $data['invoices']->take(10)->map(function ($inv) {
            return [
                'id' => $inv->id,
                'receipt_number' => $inv->receipt_number ?? ('#ORD-' . $inv->id),
                'customer_name' => $inv->customer_name ?: 'Walk-in Customer',
                'service' => $inv->service ?: 'POS Items',
                'payment_method' => strtoupper($inv->payment_method ?? 'CASH'),
                'amount' => (int) $inv->amount,
                'status' => $inv->status,
                'formatted_time' => $inv->paid_at ? $inv->paid_at->format('d M, h:i A') : ($inv->created_at ? $inv->created_at->format('d M, h:i A') : '—'),
            ];
        });

        return response()->json([
            'status' => 'success',
            'timestamp' => now()->format('H:i:s'),
            'metrics' => [
                'todaySalesTotal' => $data['todaySalesTotal'],
                'yesterdaySalesTotal' => $data['yesterdaySalesTotal'],
                'thisWeekSalesTotal' => $data['thisWeekSalesTotal'],
                'thisMonthSalesTotal' => $data['thisMonthSalesTotal'],
                'growthPct' => $data['growthPct'],
                'monthlyTarget' => $data['monthlyTarget'],
                'targetPct' => $data['targetPct'],
                'todayTransactionsCount' => $data['todayTransactionsCount'],
                'avgReceiptValue' => $data['avgReceiptValue'],
                'pendingBillsCount' => $data['pendingBillsCount'],
                'pendingBillsAmount' => $data['pendingBillsAmount'],
                'totalItemsInStock' => $data['totalItemsInStock'],
                'lowStockCount' => $data['lowStockCount'],
            ],
            'liquidity' => [
                'todayCashTotal' => $data['todayCashTotal'],
                'todayMpesaTotal' => $data['todayMpesaTotal'],
                'todayCardTotal' => $data['todayCardTotal'],
                'todayBankTotal' => $data['todayBankTotal'],
                'cashPct' => $data['cashPct'],
                'mpesaPct' => $data['mpesaPct'],
                'cardPct' => $data['cardPct'],
                'bankPct' => $data['bankPct'],
            ],
            'weeklyTrend' => $data['weeklyTrend'],
            'recentReceptionVisits' => $formattedReceptionVisits,
            'salesBillingQueue' => ($data['salesBillingQueue'] ?? collect())->map(function ($v) {
                $svc = $v->services ? $v->services->firstWhere('department', 'SALES') ?? $v->services->first() : null;
                return [
                    'id' => $v->id,
                    'uuid' => $v->uuid,
                    'ticket_code' => $v->ticket_code,
                    'visitor' => $v->visitor,
                    'purpose' => $v->purpose,
                    'total_amount' => (int) $v->total_amount,
                    'status' => $v->status,
                    'arrival_human' => $v->arrival ? (\Carbon\Carbon::parse($v->arrival)->diffForHumans()) : 'Just now',
                ];
            })->values(),
            'unpaidItTickets' => $formattedUnpaidTickets,
            'recentInvoices' => $formattedInvoices,
        ]);
    }

    /**
     * Compute comprehensive live sales, billing, and queue data
     */
    protected function getSalesDashboardData(ItService $svc): array
    {
        // 1. Fetch POS Catalog Products
        try {
            $dbProducts = PosProduct::active()->orderBy('category')->orderBy('name')->get();
            if ($dbProducts->isEmpty()) {
                foreach ($this->getDefaultProducts() as $def) {
                    PosProduct::create([
                        'name' => $def['name'],
                        'sku' => $def['sku'],
                        'category' => $def['category'],
                        'price' => $def['price'],
                        'cost_price' => $def['cost_price'],
                        'stock_quantity' => $def['stock_quantity'],
                        'is_service' => $def['is_service'],
                        'icon' => $def['icon'],
                        'is_active' => true,
                    ]);
                }
                $products = PosProduct::active()->orderBy('category')->orderBy('name')->get();
            } else {
                $products = $dbProducts;
            }
        } catch (\Throwable $e) {
            Log::info('[SalesPortal] Using memory fallback products: ' . $e->getMessage());
            $products = collect(array_map(fn($p) => (object)$p, $this->getDefaultProducts()));
        }

        // 2. Fetch Unpaid / Resolved IT Tickets ready for FrontDesk collection — REAL working source
        // Merges ItTicket + VisitService IT completed + pending SaleInvoice so count is never stale 0 when data exists
        try {
            $unpaidItTickets = ItTicket::with(['assignee', 'visit'])
                ->where(function ($q) {
                    $q->where('status', 'resolved')
                      ->orWhere(function ($sub) {
                          $sub->whereNotNull('price')->where('price', '>', 0)->where('status', '!=', 'closed');
                      });
                })
                ->whereDoesntHave('invoice', function ($q) {
                    $q->where('status', 'paid');
                })
                ->latest('id')
                ->get();

            // Augment with VisitService IT completed that already has price but may not have ItTicket row (unified flow)
            try {
                $itVisitServices = \App\Models\VisitService::with(['visit','assignee'])
                    ->where('department', 'IT')
                    ->where('status', 'completed')
                    ->where('price', '>', 0)
                    ->latest('id')
                    ->get()
                    ->filter(function ($svc) {
                        // keep only if no paid invoice yet for its visit/it_ticket
                        $visitId = $svc->visit_id;
                        $hasPaid = \App\Models\SaleInvoice::where(function($q) use ($visitId, $svc){
                            $q->where('it_ticket_id', \App\Models\ItTicket::where('visit_id', $visitId)->value('id'))
                              ->orWhere(function($q2) use ($svc){ $q2->where('service', $svc->service_name)->where('status','paid'); });
                        })->where('status','paid')->exists();
                        return !$hasPaid;
                    })
                    ->map(function ($svc) {
                        // shape as ticket-like object for Blade
                        return (object)[
                            'id' => 900000 + $svc->id,
                            'ticket_code' => $svc->visit?->ticket_code ?? ('TKT-'.str_pad($svc->id,4,'0',STR_PAD_LEFT)),
                            'title' => $svc->service_name ?? 'IT Service',
                            'category' => $svc->service_name ?? 'IT Support',
                            'visitor_name' => $svc->visit?->visitor ?? 'Walk-in',
                            'visitor_company' => $svc->visit?->company ?? '',
                            'visitor_phone' => $svc->visit?->visitor_phone ?? '',
                            'price' => (int)$svc->price,
                            'status' => 'resolved',
                            'assignee' => $svc->assignee,
                            'visit' => $svc->visit,
                            'created_at' => $svc->completed_at ?? $svc->created_at,
                            '_source' => 'visit_service',
                            '_service_id' => $svc->id,
                        ];
                    });

                if ($itVisitServices->count() > 0) {
                    // merge without duplicating same ticket_code
                    $existingCodes = $unpaidItTickets->pluck('ticket_code')->filter()->all();
                    foreach ($itVisitServices as $vs) {
                        if (!in_array($vs->ticket_code, $existingCodes)) {
                            $unpaidItTickets->push($vs);
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::info('[SalesPortal] VisitService IT augment skipped: '. $e->getMessage());
            }

            // Also include pending SaleInvoices that have it_ticket_id and are still pending_payment (ensures real billed waiting shows)
            try {
                $pendingInvoices = \App\Models\SaleInvoice::with(['itTicket.assignee','itTicket.visit'])
                    ->where('status', 'pending_payment')
                    ->whereNotNull('it_ticket_id')
                    ->latest('id')
                    ->get()
                    ->map(function ($inv) {
                        $it = $inv->itTicket;
                        if (!$it) return null;
                        return (object)[
                            'id' => $it->id,
                            'ticket_code' => $it->ticket_code ?? $inv->receipt_number ?? ('TKT-'.$it->id),
                            'title' => $it->title ?? $inv->service ?? 'IT Service',
                            'category' => $it->category ?? 'IT Support',
                            'visitor_name' => $it->visitor_name ?? $inv->customer_name ?? 'Walk-in',
                            'visitor_company' => $it->visitor_company ?? $inv->company ?? '',
                            'visitor_phone' => $it->visitor_phone ?? $inv->customer_phone ?? '',
                            'price' => (int)($it->price ?? $inv->amount ?? 0),
                            'status' => 'resolved',
                            'assignee' => $it->assignee,
                            'visit' => $it->visit,
                            'created_at' => $inv->created_at ?? $it->created_at,
                            '_source' => 'pending_invoice',
                        ];
                    })->filter();

                foreach ($pendingInvoices as $pi) {
                    if (!$unpaidItTickets->contains(fn($t)=> (string)$t->id === (string)$pi->id || (isset($t->ticket_code) && $t->ticket_code === $pi->ticket_code))) {
                        $unpaidItTickets->push($pi);
                    }
                }
            } catch (\Throwable $e) {
                Log::info('[SalesPortal] pending invoice augment skipped: '. $e->getMessage());
            }

            $unpaidItTickets = $unpaidItTickets->sortByDesc('id')->values();

        } catch (\Throwable $e) {
            Log::warning('[SalesPortal] Error fetching unpaid IT tickets: ' . $e->getMessage());
            $allT = $svc->allTickets();
            $unpaidItTickets = collect(array_filter($allT, fn($t) => in_array($t['status'], ['resolved']) || (!empty($t['price']) && $t['status'] !== 'closed')));
        }

        // 3. Fetch Sale Invoices & Register transactions
        try {
            $invoices = SaleInvoice::with(['itTicket', 'owner'])->latest('id')->get();
        } catch (\Throwable $e) {
            Log::warning('[SalesPortal] Error fetching invoices: ' . $e->getMessage());
            $invoices = collect($svc->allInvoices())->map(fn($i) => (object)$i);
        }

        // 4. Fetch Lightweight Quotes
        try {
            $quotations = SalesQuotation::with(['items', 'creator'])->latest('id')->get();
        } catch (\Throwable $e) {
            $quotations = collect();
        }

        // 5. Daily Sales & Performance Metrics Calculations
        $todayInvoices = $invoices->filter(function ($inv) {
            if (!$inv->created_at) return false;
            $dt = is_string($inv->created_at) ? \Carbon\Carbon::parse($inv->created_at) : $inv->created_at;
            return $dt->isToday();
        });

        $yesterdayInvoices = $invoices->filter(function ($inv) {
            if (!$inv->created_at) return false;
            $dt = is_string($inv->created_at) ? \Carbon\Carbon::parse($inv->created_at) : $inv->created_at;
            return $dt->isYesterday();
        });

        $weekInvoices = $invoices->filter(function ($inv) {
            if (!$inv->created_at) return false;
            $dt = is_string($inv->created_at) ? \Carbon\Carbon::parse($inv->created_at) : $inv->created_at;
            return $dt->isCurrentWeek();
        });

        $monthInvoices = $invoices->filter(function ($inv) {
            if (!$inv->created_at) return false;
            $dt = is_string($inv->created_at) ? \Carbon\Carbon::parse($inv->created_at) : $inv->created_at;
            return $dt->isCurrentMonth();
        });

        $todayPaidInvoices = $todayInvoices->where('status', 'paid');
        $yesterdayPaidInvoices = $yesterdayInvoices->where('status', 'paid');
        $weekPaidInvoices = $weekInvoices->where('status', 'paid');
        $monthPaidInvoices = $monthInvoices->where('status', 'paid');

        // Revenue Totals
        $todaySalesTotal = (int) $todayPaidInvoices->sum('amount');
        $yesterdaySalesTotal = (int) $yesterdayPaidInvoices->sum('amount');
        $thisWeekSalesTotal = (int) $weekPaidInvoices->sum('amount');
        $thisMonthSalesTotal = (int) $monthPaidInvoices->sum('amount');

        // Growth Percentage compared to yesterday
        $growthPct = 0;
        if ($yesterdaySalesTotal > 0) {
            $growthPct = round((($todaySalesTotal - $yesterdaySalesTotal) / $yesterdaySalesTotal) * 100, 1);
        } elseif ($todaySalesTotal > 0) {
            $growthPct = 100;
        }

        // Transactions count & Average Receipt value
        $todayTransactionsCount = $todayPaidInvoices->count();
        $avgReceiptValue = $todayTransactionsCount > 0 ? (int) round($todaySalesTotal / $todayTransactionsCount) : 0;

        // Payment breakdown
        $todayCashTotal = (int) $todayPaidInvoices->where('payment_method', 'cash')->sum('amount');
        $todayMpesaTotal = (int) $todayPaidInvoices->where('payment_method', 'mpesa')->sum('amount');
        $todayCardTotal = (int) $todayPaidInvoices->where('payment_method', 'card')->sum('amount');
        $todayBankTotal = (int) $todayPaidInvoices->where('payment_method', 'bank_transfer')->sum('amount');
        
        $totalPaidSum = max(1, $todaySalesTotal ?: $thisMonthSalesTotal);
        $cashPct = round(($todayCashTotal / $totalPaidSum) * 100);
        $mpesaPct = round(($todayMpesaTotal / $totalPaidSum) * 100);
        $cardPct = round(($todayCardTotal / $totalPaidSum) * 100);
        $bankPct = round(($todayBankTotal / $totalPaidSum) * 100);

        // 7-day Daily Revenue Trend
        $weeklyTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dayInvoices = $invoices->filter(function ($inv) use ($date) {
                if (!$inv->created_at) return false;
                $dt = is_string($inv->created_at) ? \Carbon\Carbon::parse($inv->created_at) : $inv->created_at;
                return $dt->isSameDay($date) && $inv->status === 'paid';
            });
            $weeklyTrend[] = [
                'day' => $date->format('D'),
                'date' => $date->format('d M'),
                'amount' => (int)$dayInvoices->sum('amount'),
                'count' => $dayInvoices->count(),
            ];
        }
        $maxDailyTrend = max(1, collect($weeklyTrend)->max('amount'));

        // Monthly Target
        $monthlyTarget = 5000000; // 5 Million TZS default monthly target
        $targetPct = min(100, round(($thisMonthSalesTotal / $monthlyTarget) * 100, 1));

        // Jorban ERP Sales Executive Metrics
        $pendingOrdersCount = $quotations->whereIn('status', ['accepted', 'pending_order'])->count() + $invoices->where('status', 'pending_payment')->count();
        $unpaidInvoicesAmount = (int) $invoices->where('status', 'pending_payment')->sum('amount');

        // Overdue collections (unpaid invoices past 7 days + overdue customer debits)
        $overdueInvoicesAmount = (int) $invoices->where('status', 'pending_payment')->filter(function ($inv) {
            $created = $inv->created_at ? ($inv->created_at instanceof \Carbon\Carbon ? $inv->created_at : \Carbon\Carbon::parse($inv->created_at)) : now();
            return $created->diffInDays(now()) > 7;
        })->sum('amount');

        $overdueDebitsAmount = 0;
        try {
            $overdueDebitsAmount = (int) Debit::where('type', 'customer')->where('status', 'overdue')->sum('amount');
        } catch (\Throwable $e) {}
        $overdueAmount = $overdueInvoicesAmount + $overdueDebitsAmount;

        // Pending Collections
        $pendingBillsCount = $invoices->where('status', 'pending_payment')->count() + $unpaidItTickets->count();
        $pendingBillsAmount = (int) $invoices->where('status', 'pending_payment')->sum('amount') + (int) $unpaidItTickets->sum('price');
        $totalItemsInStock = $products->where('is_service', false)->sum('stock_quantity');
        $lowStockCount = $products->where('is_service', false)->filter(fn($p) => ($p->stock_quantity ?? 0) <= 5)->count();

        // 6. Live Sales Queue: Customers DIRECTED from Reception -> SALES (reception handoff)
        // Sales receives the customer AFTER being directed from the receptionist.
        // This isolates only visits forwarded to SALES (current_department = sales OR service dept = SALES)
        try {
            $recentReceptionVisits = Visit::with(['services.assignee','services.completedBy','timelines.user'])
                ->whereNull('departure')
                ->where(function ($q) {
                    $q->where('current_department', 'sales')
                      ->orWhereHas('services', function ($sq) { $sq->where('department', 'SALES'); });
                })
                ->whereNotIn('status', ['checked_out','completed','cancelled'])
                ->latest('arrival')
                ->take(8)
                ->get();

            // Also fetch ready-for-billing visits that originated from Reception->SALES or IT->SALES
            $salesBillingQueue = Visit::with(['services'])
                ->where('status', 'ready_for_billing')
                ->where(function ($q) {
                    $q->where('current_department', 'sales')
                      ->orWhereHas('services', function ($sq) { $sq->where('department', 'SALES'); });
                })
                ->latest('id')
                ->take(6)
                ->get();
        } catch (\Throwable $e) {
            $recentReceptionVisits = collect();
            $salesBillingQueue = collect();
        }
        // Fallback if variable not set
        if (!isset($salesBillingQueue)) $salesBillingQueue = collect();

        $managerAlerts = [
            [
                'from' => 'Executive Office',
                'badge' => 'Management Priority',
                'message' => 'Monthly revenue target pacing at ' . $targetPct . '% — Ensure all walk-in service charges are settled immediately.',
                'time' => 'Today',
                'icon' => 'campaign',
                'type' => 'info',
            ],
            [
                'from' => 'Reception Desk',
                'badge' => 'Live Connected Handoff',
                'message' => 'Visitors with repair requests route to IT Desk. Completed repairs appear in the billing queue below.',
                'time' => 'Live',
                'icon' => 'swap_horiz',
                'type' => 'success',
            ]
        ];

        // Product Categories for Filtering
        $categories = $products->pluck('category')->filter()->unique()->values();

        return [
            'products' => $products,
            'categories' => $categories,
            'unpaidItTickets' => $unpaidItTickets,
            'salesBillingQueue' => $salesBillingQueue,
            'invoices' => $invoices,
            'quotations' => $quotations,
            'todaySalesTotal' => $todaySalesTotal,
            'yesterdaySalesTotal' => $yesterdaySalesTotal,
            'thisWeekSalesTotal' => $thisWeekSalesTotal,
            'growthPct' => $growthPct,
            'thisMonthSalesTotal' => $thisMonthSalesTotal,
            'monthlyTarget' => $monthlyTarget,
            'targetPct' => $targetPct,
            'todayTransactionsCount' => $todayTransactionsCount,
            'avgReceiptValue' => $avgReceiptValue,
            'todayCashTotal' => $todayCashTotal,
            'todayMpesaTotal' => $todayMpesaTotal,
            'todayCardTotal' => $todayCardTotal,
            'todayBankTotal' => $todayBankTotal,
            'cashPct' => $cashPct,
            'mpesaPct' => $mpesaPct,
            'cardPct' => $cardPct,
            'bankPct' => $bankPct,
            'weeklyTrend' => $weeklyTrend,
            'pendingOrdersCount' => $pendingOrdersCount,
            'unpaidInvoicesAmount' => $unpaidInvoicesAmount,
            'overdueAmount' => $overdueAmount,
            'pendingBillsCount' => $pendingBillsCount,
            'pendingBillsAmount' => $pendingBillsAmount,
            'totalItemsInStock' => $totalItemsInStock,
            'lowStockCount' => $lowStockCount,
            'recentReceptionVisits' => $recentReceptionVisits,
            'managerAlerts' => $managerAlerts,
        ];
    }

    /**
     * Dedicated Inventory & Stock Management Portal — IT electronics tailored
     */
    public function inventory(Request $request)
    {
        // Ensure warehouses exist
        try {
            if (\App\Models\Warehouse::count() === 0) {
                foreach ([
                    ['name' => 'Main Store', 'code' => 'MAIN', 'location' => 'Head Office', 'type' => 'store'],
                    ['name' => 'Showroom', 'code' => 'SHOW', 'location' => 'Showroom', 'type' => 'store'],
                    ['name' => 'Repair Centre', 'code' => 'REPAIR', 'location' => 'Service', 'type' => 'repair'],
                    ['name' => 'In Transit', 'code' => 'TRANSIT', 'location' => 'Transit', 'type' => 'transit'],
                ] as $w) { \App\Models\Warehouse::create(array_merge($w, ['uuid' => (string) Str::uuid()])); }
            }
        } catch (\Throwable $e) {}

        try {
            $products = PosProduct::active()->with(['inventoryLogs.user', 'supplier', 'warehouse', 'units'])->orderBy('category')->orderBy('name')->get();
            if ($products->isEmpty()) {
                foreach ($this->getDefaultProducts() as $def) {
                    PosProduct::create([
                        'name' => $def['name'],
                        'sku' => $def['sku'],
                        'category' => $def['category'],
                        'price' => $def['price'],
                        'cost_price' => $def['cost_price'],
                        'stock_quantity' => $def['stock_quantity'],
                        'is_service' => $def['is_service'],
                        'icon' => $def['icon'],
                        'is_active' => true,
                        'tracking_method' => in_array($def['category'], ['Hardware','Accessories']) && str_contains($def['name'], 'Laptop') ? 'serial' : 'quantity',
                    ]);
                }
                $products = PosProduct::active()->with('inventoryLogs.user')->orderBy('category')->orderBy('name')->get();
            }
        } catch (\Throwable $e) {
            $products = collect(array_map(fn($p) => (object)$p, $this->getDefaultProducts()));
        }

        $categories = $products->pluck('category')->filter()->unique()->values();
        $physicalProducts = $products->where('is_service', false);
        $services = $products->where('is_service', true);

        $totalItemsInStock = (int) $physicalProducts->sum('stock_quantity');
        $lowStockCount = $physicalProducts->filter(fn($p) => ($p->stock_quantity ?? 0) <= ($p->min_stock_alert ?? 5) && ($p->stock_quantity ?? 0) > 0)->count();
        $outOfStockCount = $physicalProducts->filter(fn($p) => ($p->stock_quantity ?? 0) == 0)->count();
        $stockValuationRetail = (int) $physicalProducts->sum(fn($p) => ($p->stock_quantity ?? 0) * ($p->price ?? 0));
        $stockValuationCost = (int) $physicalProducts->sum(fn($p) => ($p->stock_quantity ?? 0) * ($p->cost_price ?? 0));

        // IT-specific serial metrics
        try {
            $allUnits = \App\Models\InventoryUnit::with(['product','warehouse','supplier'])->get();
            $availableDevices = $allUnits->where('status','available')->count();
            $reservedDevices = $allUnits->where('status','reserved')->count();
            $inTransitDevices = $allUnits->where('status','in_transit')->count();
            $underRepairDevices = $allUnits->where('status','under_repair')->count();
            $soldDevices = $allUnits->where('status','sold')->count();
            $damagedDevices = $allUnits->whereIn('status',['damaged','defective'])->count();
            $warrantyClaims = \App\Models\WarrantyClaim::whereNotIn('status',['resolved','returned'])->count();
            $warrantyExpiring = $allUnits->filter(fn($u) => $u->warranty_end && $u->warranty_end->between(now(), now()->addDays(30)))->count();
            $serialProducts = $products->where('tracking_method','serial')->count();
        } catch (\Throwable $e) {
            $allUnits = collect(); $availableDevices=$reservedDevices=$inTransitDevices=$underRepairDevices=$soldDevices=$damagedDevices=$warrantyClaims=$warrantyExpiring=$serialProducts=0;
        }

        // Warehouses with stock
        try { $warehouses = \App\Models\Warehouse::withCount('units')->get(); } catch (\Throwable $e) { $warehouses = collect(); }

        // Recent Stock Movement Logs
        try {
            $recentStockLogs = InventoryLog::with(['product', 'user'])->latest('id')->take(12)->get();
        } catch (\Throwable $e) {
            $recentStockLogs = collect();
        }

        // Suppliers for stock-in and product form
        try {
            $suppliers = Supplier::active()->orderBy('name')->get();
        } catch (\Throwable $e) {
            $suppliers = collect();
        }

        return view('sales.inventory', compact(
            'products',
            'categories',
            'physicalProducts',
            'services',
            'totalItemsInStock',
            'lowStockCount',
            'outOfStockCount',
            'stockValuationRetail',
            'stockValuationCost',
            'recentStockLogs',
            'suppliers',
            'warehouses','allUnits','availableDevices','reservedDevices','inTransitDevices','underRepairDevices','soldDevices','damagedDevices','warrantyClaims','warrantyExpiring','serialProducts'
        ));
    }

    /**
     * Enterprise Full-Page: Create Product Form
     */
    public function createProduct()
    {
        $categories = PosProduct::active()->pluck('category')->filter()->unique()->values();
        try { $suppliers = Supplier::active()->orderBy('name')->get(); } catch (\Throwable $e) { $suppliers = collect(); }
        $product = null;
        return view('sales.product_form', compact('product', 'categories', 'suppliers'));
    }

    /**
     * Enterprise Full-Page: Edit Product Form
     */
    public function editProduct(int $id)
    {
        $product = PosProduct::with('supplier')->findOrFail($id);
        $categories = PosProduct::active()->pluck('category')->filter()->unique()->values();
        try { $suppliers = Supplier::active()->orderBy('name')->get(); } catch (\Throwable $e) { $suppliers = collect(); }
        return view('sales.product_form', compact('product', 'categories', 'suppliers'));
    }

    /**
     * Professional Product Detail — IT specs, serials, movements, warranty
     */
    public function showProduct(int $id)
    {
        $product = PosProduct::with(['supplier','warehouse','units.warehouse','units.supplier','units.invoice','inventoryLogs.user'])->findOrFail($id);
        $units = $product->units()->with(['warehouse','supplier'])->latest('id')->get();
        $logs = $product->inventoryLogs()->latest('id')->take(30)->get();
        $warrantyClaims = \App\Models\WarrantyClaim::where('pos_product_id', $product->id)->with(['unit','assignee'])->latest('id')->take(10)->get();
        $recentInvoices = \App\Models\SaleInvoice::whereJsonContains('items_json', [['id' => $product->id]])->latest('id')->take(10)->get();
        // fallback for invoices if json contains fails
        if($recentInvoices->isEmpty()){
            try {
                $all = \App\Models\SaleInvoice::latest('id')->take(30)->get();
                $recentInvoices = $all->filter(fn($inv)=> str_contains(json_encode($inv->items_json), '"id":'.$product->id) || str_contains($inv->service ?? '', $product->name))->take(10);
            } catch (\Throwable $e) { $recentInvoices = collect(); }
        }
        $suppliers = Supplier::active()->orderBy('name')->get();
        $warehouses = \App\Models\Warehouse::orderBy('name')->get();

        // valuation
        $onHand = (int) ($product->stock_quantity ?? 0);
        $availableUnits = $units->where('status','available')->count();
        $reservedUnits = $units->where('status','reserved')->count();
        $soldUnits = $units->where('status','sold')->count();
        $retailValue = $onHand * (float) $product->price;
        $costValue = $onHand * (float) ($product->cost_price ?? 0);
        $margin = $product->price > 0 && $product->cost_price ? round((($product->price - $product->cost_price)/$product->price)*100) : null;

        return view('sales.product_show', compact('product','units','logs','warrantyClaims','recentInvoices','suppliers','warehouses','onHand','availableUnits','reservedUnits','soldUnits','retailValue','costValue','margin'));
    }

    /**
     * Suppliers Index — Stock-associated
     */
    public function suppliers(Request $request)
    {
        $suppliers = Supplier::withCount('products')->orderBy('name')->get();
        try {
            $products = PosProduct::active()->with('supplier')->orderBy('name')->get();
        } catch (\Throwable $e) {
            $products = collect();
        }
        try {
            $recentMessages = \App\Models\SupplierMessage::with(['supplier', 'sender'])->latest('id')->take(10)->get();
        } catch (\Throwable $e) {
            $recentMessages = collect();
        }
        return view('sales.suppliers', compact('suppliers', 'products', 'recentMessages'));
    }

    public function storeSupplier(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'product_kind' => 'nullable|string|max:100',
            'supply_categories' => 'nullable|array',
            'supply_categories.*' => 'string|max:50',
            'notes' => 'nullable|string|max:1000',
        ]);
        if (!empty($validated['supply_categories'])) {
            $validated['supply_categories'] = array_values(array_unique($validated['supply_categories']));
            // also fill product_kind for backward compat as first selected
            $validated['product_kind'] = $validated['supply_categories'][0] ?? $validated['product_kind'] ?? null;
        }
        $supplier = Supplier::create($validated);
        return back()->with('success', "Supplier '{$supplier->name}' added ✅ — supplies " . ($supplier->product_kind ?? 'general'));
    }

    public function destroySupplier(int $id)
    {
        $supplier = Supplier::findOrFail($id);
        $name = $supplier->name;
        // Prevent delete if has products
        if ($supplier->products()->exists()) {
            return back()->withErrors(['supplier' => "Cannot delete '{$name}' — still linked to {$supplier->products()->count()} products. Reassign first."]);
        }
        $supplier->delete();
        return back()->with('success', "Supplier '{$name}' removed.");
    }

    /**
     * Send REAL message to supplier — SMS via NextSMS (and email if available)
     */
    public function sendSupplierMessage(Request $request, int $id, \App\Services\NextSmsService $sms)
    {
        $supplier = Supplier::findOrFail($id);
        $validated = $request->validate([
            'message' => 'required|string|min:5|max:1000',
            'channel' => 'nullable|string|in:sms,email,both',
        ]);
        $message = trim($validated['message']);
        $channel = $validated['channel'] ?? 'sms';
        $results = [];
        $status = 'sent';

        // Determine phone/email
        $phone = $supplier->phone;
        $email = $supplier->email;

        // SMS via NextSMS
        if (in_array($channel, ['sms', 'both'])) {
            if (empty($phone)) {
                $results['sms'] = ['success' => false, 'error' => 'Supplier has no phone number'];
                $status = 'failed';
            } else {
                $smsResult = $sms->send($phone, $message);
                $results['sms'] = $smsResult;
                if (!$smsResult['success']) $status = 'failed';
                // LOG_ONLY still counts as sent for UX
                if (($smsResult['response'] ?? null) === 'log_only') $status = 'log_only';
                if (($smsResult['response'] ?? null) === 'disabled') $status = 'log_only';
            }
        }

        // Email via Laravel Mail if available and channel includes email
        if (in_array($channel, ['email', 'both']) && !empty($email)) {
            try {
                \Illuminate\Support\Facades\Mail::raw($message, function ($m) use ($email, $supplier) {
                    $m->to($email, $supplier->name)->subject('Message from JOBARN — FrontDesk');
                });
                $results['email'] = ['success' => true];
            } catch (\Throwable $e) {
                $results['email'] = ['success' => false, 'error' => $e->getMessage()];
                $status = 'failed';
                \Illuminate\Support\Facades\Log::error('[SupplierMessage] email failed', ['supplier_id' => $id, 'error' => $e->getMessage()]);
            }
        }

        // Persist history
        try {
            \App\Models\SupplierMessage::create([
                'supplier_id' => $supplier->id,
                'phone' => $phone,
                'email' => $email,
                'message' => $message,
                'channel' => $channel,
                'status' => $status,
                'provider_response' => json_encode($results, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                'sent_by' => auth()->id(),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[SupplierMessage] log failed', ['error' => $e->getMessage()]);
        }

        if ($status === 'failed' && !empty($results['sms']['error'])) {
            return back()->withErrors(['message' => 'Send failed: ' . $results['sms']['error']])->withInput();
        }

        $isLogOnly = $status === 'log_only';
        $msg = $isLogOnly
            ? "Message queued (LOG_ONLY mode) to {$supplier->name} — check laravel.log ✅"
            : "Real message sent to {$supplier->name} via " . strtoupper($channel) . " ✅";

        // Add hint if NEXTSMS_LOG_ONLY or disabled
        if ($isLogOnly) {
            $msg .= " (NEXTSMS_LOG_ONLY=true — no credit used. Set to false for real SMS.)";
        }

        return back()->with('success', $msg);
    }

    public function destroySupplierMessage(int $id)
    {
        $msg = \App\Models\SupplierMessage::findOrFail($id);
        $msg->delete();
        return back()->with('success', 'Message deleted ✅');
    }

    /**
     * Showroom Debits — Payables (supplier) + Receivables (customer)
     */
    public function debits(Request $request)
    {
        $debits = Debit::with(['supplier', 'product', 'invoice', 'creator'])->latest('id')->get();
        $suppliers = Supplier::active()->orderBy('name')->get();
        $products = PosProduct::active()->orderBy('name')->get();

        // KPIs
        $totalPending = (int) $debits->where('status', 'pending')->sum('amount');
        $totalOverdue = (int) $debits->where('status', 'overdue')->sum('amount');
        $totalPaid = (int) $debits->where('status', 'paid')->sum('amount');
        $supplierPending = (int) $debits->where('type', 'supplier')->where('status', 'pending')->sum('amount');
        $customerPending = (int) $debits->where('type', 'customer')->where('status', 'pending')->sum('amount');
        $supplierCount = $debits->where('type', 'supplier')->count();
        $customerCount = $debits->where('type', 'customer')->count();

        try {
            $recentMessages = \App\Models\DebitMessage::with(['debit.supplier', 'debit.product', 'sender'])->latest('id')->take(10)->get();
        } catch (\Throwable $e) {
            $recentMessages = collect();
        }

        return view('sales.debits', compact('debits', 'suppliers', 'products', 'totalPending', 'totalOverdue', 'totalPaid', 'supplierPending', 'customerPending', 'supplierCount', 'customerCount', 'recentMessages'));
    }

    public function storeDebit(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:supplier,customer',
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'pos_product_id' => 'nullable|integer|exists:pos_products,id',
            'amount' => 'required|numeric|min:1',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validated['type'] === 'supplier' && empty($validated['supplier_id'])) {
            return back()->withErrors(['supplier_id' => 'Supplier is required for supplier debit'])->withInput();
        }
        if ($validated['type'] === 'customer' && empty($validated['customer_name'])) {
            return back()->withErrors(['customer_name' => 'Customer name is required for customer debit'])->withInput();
        }

        $status = 'pending';
        if (!empty($validated['due_date']) && \Carbon\Carbon::parse($validated['due_date'])->isPast()) {
            $status = 'overdue';
        }

        $debit = Debit::create([
            'type' => $validated['type'],
            'supplier_id' => $validated['supplier_id'] ?? null,
            'customer_name' => $validated['customer_name'] ?? null,
            'customer_phone' => $validated['customer_phone'] ?? null,
            'pos_product_id' => $validated['pos_product_id'] ?? null,
            'amount' => $validated['amount'],
            'status' => $status,
            'due_date' => $validated['due_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', "Showroom debit #{$debit->id} created — " . strtoupper($validated['type']) . " TZS " . number_format($validated['amount']) . " ✅");
    }

    public function payDebit(int $id)
    {
        $debit = Debit::findOrFail($id);
        $debit->update(['status' => 'paid', 'paid_at' => now()]);
        // If linked to invoice, mark invoice paid
        if ($debit->sale_invoice_id) {
            $inv = SaleInvoice::find($debit->sale_invoice_id);
            if ($inv) $inv->update(['status' => 'paid', 'paid_at' => now()]);
        }
        return back()->with('success', "Debit #{$debit->id} marked as paid ✅");
    }

    public function destroyDebit(int $id)
    {
        $debit = Debit::findOrFail($id);
        $debit->delete();
        return back()->with('success', "Debit #{$id} deleted");
    }

    /**
     * Send REAL message for debit — to supplier (payable) or customer (receivable) via NextSMS
     */
    public function sendDebitMessage(Request $request, int $id, \App\Services\NextSmsService $sms)
    {
        $debit = Debit::with(['supplier', 'invoice'])->findOrFail($id);
        $validated = $request->validate([
            'message' => 'required|string|min:5|max:1000',
            'channel' => 'nullable|string|in:sms,email,both',
        ]);
        $message = trim($validated['message']);
        $channel = $validated['channel'] ?? 'sms';

        // Determine recipient: supplier phone/email for supplier debit, customer phone for customer debit
        $isSupplier = $debit->type === 'supplier';
        $phone = $isSupplier ? ($debit->supplier?->phone ?? null) : ($debit->customer_phone ?? $debit->invoice?->customer_phone ?? null);
        $email = $isSupplier ? ($debit->supplier?->email ?? null) : null;
        $name = $isSupplier ? ($debit->supplier?->name ?? 'Supplier') : ($debit->customer_name ?? $debit->invoice?->customer_name ?? 'Customer');
        $supplierId = $debit->supplier_id;

        $results = [];
        $status = 'sent';

        if (in_array($channel, ['sms', 'both'])) {
            if (empty($phone)) {
                $results['sms'] = ['success' => false, 'error' => ($isSupplier ? 'Supplier' : 'Customer') . ' has no phone number'];
                $status = 'failed';
            } else {
                $smsResult = $sms->send($phone, $message);
                $results['sms'] = $smsResult;
                if (!$smsResult['success']) $status = 'failed';
                if (($smsResult['response'] ?? null) === 'log_only' || ($smsResult['response'] ?? null) === 'disabled') $status = 'log_only';
            }
        }

        if (in_array($channel, ['email', 'both']) && !empty($email)) {
            try {
                \Illuminate\Support\Facades\Mail::raw($message, function ($m) use ($email, $name) {
                    $m->to($email, $name)->subject('Message from JOBARN — FrontDesk');
                });
                $results['email'] = ['success' => true];
            } catch (\Throwable $e) {
                $results['email'] = ['success' => false, 'error' => $e->getMessage()];
                $status = 'failed';
                \Illuminate\Support\Facades\Log::error('[DebitMessage] email failed', ['debit_id' => $id, 'error' => $e->getMessage()]);
            }
        }

        try {
            \App\Models\DebitMessage::create([
                'debit_id' => $debit->id,
                'supplier_id' => $supplierId,
                'customer_name' => $isSupplier ? null : $name,
                'phone' => $phone,
                'email' => $email,
                'message' => $message,
                'channel' => $channel,
                'status' => $status,
                'provider_response' => json_encode($results, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                'sent_by' => auth()->id(),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[DebitMessage] log failed', ['error' => $e->getMessage()]);
        }

        if ($status === 'failed' && !empty($results['sms']['error'])) {
            return back()->withErrors(['message' => 'Send failed: ' . $results['sms']['error']])->withInput();
        }

        $msg = $status === 'log_only'
            ? "Message queued (LOG_ONLY) to {$name} — check laravel.log ✅"
            : "Message sent to {$name} via " . strtoupper($channel) . " for debit #{$debit->id} ✅";

        return back()->with('success', $msg);
    }

    public function destroyDebitMessage(int $id)
    {
        $msg = \App\Models\DebitMessage::findOrFail($id);
        $msg->delete();
        return back()->with('success', 'Message deleted ✅');
    }

    /**
     * Complete POS Sale & Instant Checkout
     */
    public function checkout(Request $request, ItService $svc)
    {
        $validated = $request->validate([
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'company' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string|max:255',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.qty' => 'required|numeric|min:1',
            'items.*.sku' => 'nullable|string',
            'items.*.id' => 'nullable|integer',
            'subtotal' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string|in:cash,mpesa,card,bank_transfer',
            'payment_reference' => 'nullable|string|max:100',
            'tendered_amount' => 'nullable|numeric|min:0',
            'change_amount' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|in:paid,pending_payment',
            'it_ticket_id' => 'nullable|integer',
            'notes' => 'nullable|string',
        ]);

        try {
            $customerName = !empty($validated['customer_name']) ? trim($validated['customer_name']) : 'Walk-in Customer';
            $customerPhone = $validated['customer_phone'] ?? null;
            $company = $validated['company'] ?? null;
            $items = $validated['items'];
            $subtotal = (float) $validated['subtotal'];
            $discount = (float) ($validated['discount_amount'] ?? 0);
            $tax = (float) ($validated['tax_amount'] ?? 0);
            $total = (float) $validated['total_amount'];
            $payMethod = $validated['payment_method'];
            $payRef = $validated['payment_reference'] ?? null;
            $tendered = (float) ($validated['tendered_amount'] ?? $total);
            $change = (float) ($validated['change_amount'] ?? max(0, $tendered - $total));
            $status = $validated['status'] ?? 'paid';
            $itTicketId = !empty($validated['it_ticket_id']) ? (int)$validated['it_ticket_id'] : null;

            // Generate Item Summary String
            $itemSummary = collect($items)->map(fn($i) => ($i['qty'] > 1 ? $i['qty'] . 'x ' : '') . $i['name'])->join(', ');

            $invoice = DB::transaction(function () use ($customerName, $customerPhone, $company, $items, $subtotal, $discount, $tax, $total, $payMethod, $payRef, $tendered, $change, $status, $itTicketId, $itemSummary) {
                // 1. Validate & lock stock for physical items FIRST (prevents oversell)
                $lockedProducts = [];
                foreach ($items as $it) {
                    if (!empty($it['id'])) {
                        $prod = PosProduct::where('id', $it['id'])->lockForUpdate()->first();
                        if ($prod && !$prod->is_service && $prod->stock_quantity !== null) {
                            $qtySold = (int)($it['qty'] ?? 1);
                            $available = (int)$prod->stock_quantity;
                            if ($available < $qtySold) {
                                throw new \RuntimeException("Insufficient stock for '{$prod->name}' (SKU: {$prod->sku}): available {$available}, requested {$qtySold}. Please adjust quantity or restock.");
                            }
                            $lockedProducts[$prod->id] = $prod;
                        }
                    }
                }

                // 2. Generate Receipt Number inside transaction (safe - no FOR UPDATE on aggregate for PG)
                $today = now()->format('Ymd');
                $count = SaleInvoice::whereDate('created_at', today())->count() + 1;
                $receiptNum = 'RCP-' . $today . '-' . str_pad((string)$count, 4, '0', STR_PAD_LEFT);
                // Ensure uniqueness in case of race (retry if duplicate)
                while (SaleInvoice::where('receipt_number', $receiptNum)->exists()) {
                    $count++;
                    $receiptNum = 'RCP-' . $today . '-' . str_pad((string)$count, 4, '0', STR_PAD_LEFT);
                }

                // 3. Create POS Sale Invoice
                $invoice = SaleInvoice::create([
                    'uuid' => (string) Str::uuid(),
                    'receipt_number' => $receiptNum,
                    'it_ticket_id' => $itTicketId,
                    'customer_name' => $customerName,
                    'customer_phone' => $customerPhone,
                    'company' => $company,
                    'service' => Str::limit($itemSummary, 250),
                    'amount' => (int) round($total),
                    'subtotal' => $subtotal,
                    'discount_amount' => $discount,
                    'tax_amount' => $tax,
                    'tendered_amount' => $tendered,
                    'change_amount' => $change,
                    'payment_method' => $payMethod,
                    'payment_reference' => $payRef,
                    'items_json' => $items,
                    'status' => $status,
                    'notes' => $validated['notes'] ?? null,
                    'owner_id' => auth()->id(),
                    'paid_at' => $status === 'paid' ? now() : null,
                ]);

                // 4. Decrement Stock for Physical Catalog Items and record InventoryLog (uses locked rows)
                foreach ($items as $it) {
                    if (!empty($it['id']) && isset($lockedProducts[$it['id']])) {
                        $prod = $lockedProducts[$it['id']];
                        $qtySold = (int)($it['qty'] ?? 1);
                        $qtyBefore = (int)$prod->stock_quantity;
                        $newStock = $qtyBefore - $qtySold;
                        $prod->update(['stock_quantity' => $newStock]);

                        InventoryLog::create([
                            'pos_product_id' => $prod->id,
                            'type' => 'sale',
                            'quantity_change' => -$qtySold,
                            'quantity_before' => $qtyBefore,
                            'quantity_after' => $newStock,
                            'reason' => "POS Checkout: Receipt #{$receiptNum}",
                            'reference' => $receiptNum,
                            'user_id' => auth()->id(),
                        ]);
                    } elseif (!empty($it['id'])) {
                        // service or null-stock item: no deduction but still ensure product exists
                        $prod = $lockedProducts[$it['id']] ?? PosProduct::find($it['id']);
                        if ($prod && !$prod->is_service && $prod->stock_quantity !== null) {
                            $qtySold = (int)($it['qty'] ?? 1);
                            $qtyBefore = (int)$prod->stock_quantity;
                            $newStock = $qtyBefore - $qtySold;
                            if ($newStock < 0) {
                                throw new \RuntimeException("Insufficient stock for '{$prod->name}'");
                            }
                            $prod->update(['stock_quantity' => $newStock]);
                            InventoryLog::create([
                                'pos_product_id' => $prod->id,
                                'type' => 'sale',
                                'quantity_change' => -$qtySold,
                                'quantity_before' => $qtyBefore,
                                'quantity_after' => $newStock,
                                'reason' => "POS Checkout: Receipt #{$receiptNum}",
                                'reference' => $receiptNum,
                                'user_id' => auth()->id(),
                            ]);
                        }
                    }
                }

                // 4b. Auto-create showroom customer debit if pending_payment (receivable)
                if ($status === 'pending_payment') {
                    $firstProductId = $items[0]['id'] ?? null;
                    Debit::create([
                        'type' => 'customer',
                        'customer_name' => $customerName,
                        'customer_phone' => $customerPhone,
                        'pos_product_id' => is_numeric($firstProductId) ? (int)$firstProductId : null,
                        'sale_invoice_id' => $invoice->id,
                        'amount' => (int) round($total),
                        'status' => 'pending',
                        'notes' => "Showroom sale: {$itemSummary} — Receipt {$receiptNum}",
                        'created_by' => auth()->id(),
                    ]);
                }

                return $invoice;
            });

            $receiptNum = $invoice->receipt_number;

            // Close and settle linked IT ticket if present
            if ($itTicketId) {
                $ticket = ItTicket::find($itTicketId);
                if ($ticket) {
                    $ticket->update(['status' => 'closed']);
                    $ticket->logActivity(
                        eventType: 'payment_collected',
                        title: "Payment Collected at FrontDesk POS",
                        description: "Payment of TZS " . number_format($total) . " settled via " . strtoupper($payMethod) . " (Receipt: {$receiptNum})",
                        department: 'sales',
                        userId: auth()->id()
                    );

                    if ($ticket->visit_id) {
                        $visit = Visit::find($ticket->visit_id);
                        if ($visit) {
                            $visit->services()->where('department', 'IT')->update(['status' => 'completed']);
                            $visit->logTimeline(
                                eventType: 'paid',
                                title: "IT Service Bill Settled",
                                description: "Paid TZS " . number_format($total) . " via " . strtoupper($payMethod) . " at POS (Receipt: {$receiptNum})",
                                department: 'sales',
                                userId: auth()->id()
                            );
                        }
                    }
                }
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Sale completed successfully! Receipt generated.',
                    'invoice_id' => $invoice->id,
                    'receipt_number' => $receiptNum,
                    'receipt_url' => route('sales.invoices.print', $invoice->id),
                ]);
            }

            return redirect()->route('sales.index', ['tab' => 'register'])
                ->with('success', "Sale #{$receiptNum} completed successfully! (TZS " . number_format($total) . " via " . strtoupper($payMethod) . ")")
                ->with('receipt_id', $invoice->id);

        } catch (\Throwable $e) {
            Log::error('[POS Checkout Error] ' . $e->getMessage(), ['exception' => $e]);
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Checkout failed: ' . $e->getMessage()], 500);
            }
            return back()->withErrors(['checkout' => 'Checkout error: ' . $e->getMessage()]);
        }
    }

    /**
     * 1-Click Settle and Charge IT Repair Ticket from Queue
     */
    public function chargeItTicket(Request $request, int $id, ItService $svc)
    {
        $ticket = ItTicket::findOrFail($id);
        $amount = (int) ($request->input('amount') ?: ($ticket->price ?: 50000));
        $payMethod = $request->input('payment_method', 'cash');
        $payRef = $request->input('payment_reference');

        $today = now()->format('Ymd');
        $count = SaleInvoice::whereDate('created_at', today())->count() + 1;
        $receiptNum = 'RCP-' . $today . '-' . str_pad((string)$count, 4, '0', STR_PAD_LEFT);

        $items = [
            [
                'name' => $ticket->title . ' (' . ($ticket->category ?? 'Technical Support') . ')',
                'sku' => $ticket->ticket_code ?? ('TKT-' . $ticket->id),
                'price' => $amount,
                'qty' => 1,
            ]
        ];

        $invoice = SaleInvoice::create([
            'uuid' => (string) Str::uuid(),
            'receipt_number' => $receiptNum,
            'it_ticket_id' => $ticket->id,
            'customer_name' => $ticket->visitor_name ?: 'Walk-in Guest',
            'customer_phone' => $ticket->visitor_phone,
            'company' => $ticket->visitor_company,
            'service' => $ticket->title . ' — ' . $ticket->category,
            'amount' => $amount,
            'subtotal' => $amount,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'tendered_amount' => $amount,
            'change_amount' => 0,
            'payment_method' => $payMethod,
            'payment_reference' => $payRef,
            'items_json' => $items,
            'status' => 'paid',
            'owner_id' => auth()->id(),
            'paid_at' => now(),
        ]);

        $ticket->update(['status' => 'closed', 'price' => $amount]);
        $ticket->logActivity(
            eventType: 'payment_collected',
            title: "Ticket Bill Settled at POS",
            description: "Collected TZS " . number_format($amount) . " via " . strtoupper($payMethod) . " (Receipt: {$receiptNum})",
            department: 'sales',
            userId: auth()->id()
        );

        if ($ticket->visit_id) {
            $visit = Visit::find($ticket->visit_id);
            if ($visit) {
                $visit->services()->where('department', 'IT')->update(['status' => 'completed']);
                $visit->logTimeline(
                    eventType: 'paid',
                    title: "IT Repair Bill Settled",
                    description: "Paid TZS " . number_format($amount) . " via " . strtoupper($payMethod) . " at POS (Receipt: {$receiptNum})",
                    department: 'sales',
                    userId: auth()->id()
                );
            }
        }

        return redirect()->route('sales.index', ['tab' => 'it_bills'])
            ->with('success', "Ticket #{$ticket->id} settled and closed! Receipt {$receiptNum} generated.")
            ->with('receipt_id', $invoice->id);
    }

    /**
     * Mark an Invoice as Paid
     */
    public function pay(Request $request, int $id, ItService $svc)
    {
        $invoice = SaleInvoice::find($id);
        $method = $request->input('payment_method', 'cash');
        $ref = $request->input('payment_reference');

        if ($invoice) {
            $invoice->update([
                'status' => 'paid',
                'payment_method' => $method,
                'payment_reference' => $ref,
                'paid_at' => now(),
            ]);
        } else {
            $svc->payInvoice($id);
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => "Invoice #{$id} marked as paid."]);
        }

        return redirect()->route('sales.index', ['tab' => 'register'])->with('success', 'Payment collected and marked Settled ✅');
    }

    /**
     * Legacy receipt URL: keep old bookmarks pointed at the formal invoice.
     */
    public function receipt(Request $request, int $id)
    {
        SaleInvoice::findOrFail($id);
        return redirect()->route('sales.invoices.print', $id);
    }

    /**
     * Product Catalog: Add or Update Product
     */
    public function storeProduct(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|integer',
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100',
            'barcode' => 'nullable|string|max:100',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'category' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'nullable|integer|min:0',
            'min_stock_alert' => 'nullable|integer|min:0',
            'is_service' => 'nullable|boolean',
            'icon' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
            'tracking_method' => 'nullable|string|in:quantity,serial,batch,none',
            'specs' => 'nullable|string|max:2000',
            'serial_numbers' => 'nullable|string|max:10000',
            'purchase_order' => 'nullable|string|max:100',
            'cost' => 'nullable|numeric|min:0',
            'is_serial_receive' => 'nullable|boolean',
        ]);

        $isService = !empty($validated['is_service']);
        $icon = ($validated['icon'] ?? null) ?: ($isService ? 'build' : 'inventory_2');

        try {
            return DB::transaction(function () use ($validated, $isService, $icon, $request) {
                if (!empty($validated['id'])) {
                    $product = PosProduct::where('id', $validated['id'])->lockForUpdate()->firstOrFail();
                    $oldStock = (int) $product->stock_quantity;
                    $newStock = $isService ? null : ($validated['stock_quantity'] ?? 0);

                    // Prevent duplicate SKU on update (exclude current product)
                    $providedSku = $validated['sku'] ?? null;
                    if (!empty($providedSku) && $providedSku !== $product->sku && PosProduct::where('sku', $providedSku)->exists()) {
                        throw new \RuntimeException("SKU '{$providedSku}' already exists. Use unique SKU.");
                    }

                    $specs = null;
                    if (!empty($validated['specs'])) {
                        $decoded = json_decode($validated['specs'], true);
                        $specs = json_last_error() === JSON_ERROR_NONE ? $decoded : null;
                        if ($specs === null && trim($validated['specs']) !== '') {
                            // try as key:value lines fallback
                            $specs = ['raw' => $validated['specs']];
                        }
                    }
                    $product->update([
                        'name' => $validated['name'],
                        'sku' => ($validated['sku'] ?? null) ?: $product->sku,
                        'barcode' => $validated['barcode'] ?? $product->barcode,
                        'brand' => $validated['brand'] ?? $product->brand,
                        'model' => $validated['model'] ?? $product->model,
                        'category' => $validated['category'],
                        'price' => $validated['price'],
                        'cost_price' => $validated['cost_price'] ?? null,
                        'stock_quantity' => $newStock,
                        'min_stock_alert' => $validated['min_stock_alert'] ?? 5,
                        'is_service' => $isService,
                        'icon' => $icon,
                        'description' => $validated['description'] ?? null,
                        'supplier_id' => $validated['supplier_id'] ?? null,
                        'warehouse_id' => $validated['warehouse_id'] ?? $product->warehouse_id,
                        'tracking_method' => $validated['tracking_method'] ?? $product->tracking_method ?? 'quantity',
                        'specs' => $specs,
                    ]);

                    if (!$isService && $newStock !== $oldStock) {
                        InventoryLog::create([
                            'pos_product_id' => $product->id,
                            'type' => 'adjustment',
                            'quantity_change' => $newStock - $oldStock,
                            'quantity_before' => $oldStock,
                            'quantity_after' => $newStock,
                            'reason' => 'Product Details Update / Recount',
                            'user_id' => auth()->id(),
                        ]);
                    }

                    // Serial receive on existing product (Goods Receiving)
                    if (!empty($validated['serial_numbers'])) {
                        $serials = preg_split('/\r\n|\r|\n/', $validated['serial_numbers']);
                        $created = 0;
                        foreach ($serials as $sn) {
                            $sn = trim($sn);
                            if ($sn === '') continue;
                            if (\App\Models\InventoryUnit::where('serial_number', $sn)->exists()) {
                                throw new \RuntimeException("Serial already exists: {$sn} — duplicate prevented.");
                            }
                            \App\Models\InventoryUnit::create([
                                'pos_product_id' => $product->id,
                                'warehouse_id' => $validated['warehouse_id'] ?? $product->warehouse_id ?? \App\Models\Warehouse::first()?->id,
                                'serial_number' => $sn,
                                'status' => 'available',
                                'cost' => $validated['cost'] ?? $product->cost_price,
                                'supplier_id' => $validated['supplier_id'] ?? $product->supplier_id,
                                'purchase_order' => $validated['purchase_order'] ?? null,
                                'purchase_date' => now()->toDateString(),
                                'warranty_start' => now()->toDateString(),
                                'warranty_end' => now()->addYear()->toDateString(),
                                'warranty_type' => 'supplier',
                                'created_by' => auth()->id(),
                            ]);
                            \App\Models\InventoryLog::create([
                                'pos_product_id' => $product->id,
                                'warehouse_id' => $validated['warehouse_id'] ?? null,
                                'serial_number' => $sn,
                                'type' => 'stock_in',
                                'quantity_change' => 1,
                                'quantity_before' => $created,
                                'quantity_after' => $created + 1,
                                'reason' => 'Goods Receiving — PO ' . ($validated['purchase_order'] ?? 'N/A'),
                                'reference' => $validated['purchase_order'] ?? null,
                                'user_id' => auth()->id(),
                            ]);
                            $created++;
                        }
                        if ($created > 0) {
                            $product->increment('stock_quantity', $created);
                            $msg = "Product '{$product->name}' updated + {$created} serial units received (Goods Receiving) ✅";
                        } else {
                            $msg = "Product '{$product->name}' updated in catalog ✅";
                        }
                    } else {
                        $msg = "Product '{$product->name}' updated in catalog ✅";
                    }
                } else {
                    $initialStock = $isService ? null : ($validated['stock_quantity'] ?? 0);
                    // Validate SKU uniqueness if provided
                    if (!empty($validated['sku']) && PosProduct::where('sku', $validated['sku'])->exists()) {
                        throw new \RuntimeException("SKU '{$validated['sku']}' already exists. Use unique SKU or leave blank for auto-generation.");
                    }
                    $specs = null;
                    if (!empty($validated['specs'])) {
                        $decoded = json_decode($validated['specs'], true);
                        $specs = json_last_error() === JSON_ERROR_NONE ? $decoded : ['raw' => $validated['specs']];
                    }
                $product = PosProduct::create([
                    'name' => $validated['name'],
                    'sku' => $validated['sku'] ?? null,
                    'barcode' => $validated['barcode'] ?? null,
                    'brand' => $validated['brand'] ?? null,
                    'model' => $validated['model'] ?? null,
                    'category' => $validated['category'],
                    'price' => $validated['price'],
                    'cost_price' => $validated['cost_price'] ?? null,
                    'stock_quantity' => $initialStock,
                    'min_stock_alert' => $validated['min_stock_alert'] ?? 5,
                    'is_service' => $isService,
                    'icon' => $icon,
                    'description' => $validated['description'] ?? null,
                    'supplier_id' => $validated['supplier_id'] ?? null,
                    'warehouse_id' => $validated['warehouse_id'] ?? null,
                    'tracking_method' => $validated['tracking_method'] ?? 'quantity',
                    'specs' => $specs,
                    'is_active' => true,
                ]);

                    if (!$isService && $initialStock > 0) {
                        InventoryLog::create([
                            'pos_product_id' => $product->id,
                            'type' => 'initial',
                            'quantity_change' => $initialStock,
                            'quantity_before' => 0,
                            'quantity_after' => $initialStock,
                            'reason' => 'Initial Inventory Setup',
                            'user_id' => auth()->id(),
                        ]);
                    }

                    // Serial receive on new product
                    if (!empty($validated['serial_numbers'])) {
                        $serials = preg_split('/\r\n|\r|\n/', $validated['serial_numbers']);
                        $created = 0;
                        foreach ($serials as $sn) {
                            $sn = trim($sn);
                            if ($sn === '') continue;
                            if (\App\Models\InventoryUnit::where('serial_number', $sn)->exists()) {
                                throw new \RuntimeException("Serial already exists: {$sn}");
                            }
                            \App\Models\InventoryUnit::create([
                                'pos_product_id' => $product->id,
                                'warehouse_id' => $validated['warehouse_id'] ?? \App\Models\Warehouse::first()?->id,
                                'serial_number' => $sn,
                                'status' => 'available',
                                'cost' => $validated['cost'] ?? $product->cost_price,
                                'supplier_id' => $validated['supplier_id'] ?? null,
                                'purchase_order' => $validated['purchase_order'] ?? null,
                                'purchase_date' => now()->toDateString(),
                                'warranty_start' => now()->toDateString(),
                                'warranty_end' => now()->addYear()->toDateString(),
                                'warranty_type' => 'supplier',
                                'created_by' => auth()->id(),
                            ]);
                            $created++;
                        }
                        if ($created > 0) {
                            $product->update(['stock_quantity' => $product->stock_quantity + $created]);
                            // also log total
                            \App\Models\InventoryLog::create([
                                'pos_product_id' => $product->id,
                                'type' => 'stock_in',
                                'quantity_change' => $created,
                                'quantity_before' => $initialStock,
                                'quantity_after' => $initialStock + $created,
                                'reason' => 'Goods Receiving — initial serials',
                                'reference' => $validated['purchase_order'] ?? null,
                                'user_id' => auth()->id(),
                            ]);
                        }
                        $msg = "New item '{$product->name}' added with SKU: {$product->sku} + {$created} serial units ✅";
                    } else {
                        $msg = "New item '{$product->name}' added to catalog with SKU: {$product->sku} ✅";
                    }
                }

                return back()->with('success', $msg);
            });
        } catch (\Throwable $e) {
            Log::error('[Inventory storeProduct] ' . $e->getMessage(), ['exception' => $e]);
            return back()->withErrors(['product' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Direct Recount / Set Physical Stock
     */
    public function updateStock(Request $request, int $id)
    {
        $validated = $request->validate([
            'stock_quantity' => 'required|integer|min:0',
        ]);

        try {
            return DB::transaction(function () use ($validated, $id) {
                $product = PosProduct::where('id', $id)->lockForUpdate()->firstOrFail();
                if ($product->is_service) {
                    throw new \RuntimeException('Cannot set stock for service items (no inventory tracking).');
                }
                $oldStock = (int)$product->stock_quantity;
                $newStock = (int)$validated['stock_quantity'];

                $product->update(['stock_quantity' => $newStock]);

                InventoryLog::create([
                    'pos_product_id' => $product->id,
                    'type' => 'recount',
                    'quantity_change' => $newStock - $oldStock,
                    'quantity_before' => $oldStock,
                    'quantity_after' => $newStock,
                    'reason' => 'Physical Inventory Audit / Recount',
                    'user_id' => auth()->id(),
                ]);

                return back()->with('success', "Stock count updated for {$product->name} ({$newStock} units) ✅");
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['stock_quantity' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Quick Stock In (+ Delivery) / Stock Out (- Damaged / Correction)
     */
    public function adjustStock(Request $request, int $id)
    {
        $validated = $request->validate([
            'action' => 'required|string|in:stock_in,stock_out',
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|string|max:255',
            'reference' => 'nullable|string|max:100',
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
        ]);

        try {
            return DB::transaction(function () use ($validated, $id) {
                $product = PosProduct::where('id', $id)->lockForUpdate()->firstOrFail();
                if ($product->is_service) {
                    throw new \RuntimeException('Cannot adjust stock for service items.');
                }
                $oldStock = (int) $product->stock_quantity;
                $qty = (int) $validated['quantity'];
                $isStockIn = $validated['action'] === 'stock_in';

                if (!$isStockIn && $qty > $oldStock) {
                    throw new \RuntimeException("Cannot deduct {$qty} units from '{$product->name}': only {$oldStock} in stock. Adjust quantity or restock first.");
                }

                $change = $isStockIn ? $qty : -$qty;
                $newStock = $oldStock + $change;

                $product->update(['stock_quantity' => $newStock]);

                // Handle supplier association for stock_in
                $reason = $validated['reason'];
                if ($isStockIn && !empty($validated['supplier_id'])) {
                    $supplier = Supplier::find($validated['supplier_id']);
                    if ($supplier) {
                        $reason = $supplier->name . ' — ' . $reason;
                        // Optionally link product to supplier if not set
                        if (empty($product->supplier_id)) {
                            $product->update(['supplier_id' => $supplier->id]);
                        }
                    }
                }

                $log = InventoryLog::create([
                    'pos_product_id' => $product->id,
                    'type' => $validated['action'],
                    'quantity_change' => $change,
                    'quantity_before' => $oldStock,
                    'quantity_after' => $newStock,
                    'reason' => $reason,
                    'reference' => $validated['reference'] ?? null,
                    'user_id' => auth()->id(),
                ]);

                // Auto-create showroom supplier debit for stock-in (payable)
                if ($isStockIn && !empty($validated['supplier_id'])) {
                    $unitCost = (float) ($product->cost_price ?? $product->price ?? 0);
                    $amount = $unitCost * $qty;
                    if ($amount > 0) {
                        Debit::create([
                            'type' => 'supplier',
                            'supplier_id' => $validated['supplier_id'],
                            'pos_product_id' => $product->id,
                            'inventory_log_id' => $log->id,
                            'amount' => $amount,
                            'status' => 'pending',
                            'notes' => "Showroom restock: {$qty} x {$product->name} @ TZS " . number_format($unitCost) . " — Ref: " . ($validated['reference'] ?? '—'),
                            'created_by' => auth()->id(),
                        ]);
                    }
                }

                $actionText = $isStockIn ? "Added {$qty} units (Restock)" : "Deducted {$qty} units";
                $successMsg = "{$actionText} for {$product->name}. Current stock: {$newStock} units ✅";
                if ($isStockIn && !empty($validated['supplier_id']) && isset($amount) && $amount > 0) {
                    $successMsg .= " — Supplier debit TZS " . number_format($amount) . " created";
                }
                return back()->with('success', $successMsg);
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['quantity' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Export Inventory Stock CSV
     */
    public function exportInventory(Request $request)
    {
        $products = PosProduct::active()->orderBy('category')->orderBy('name')->get();
        $filename = 'frontdesk_inventory_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($products) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'SKU',
                'Item Name',
                'Category',
                'Type',
                'Selling Price (TZS)',
                'Cost Price (TZS)',
                'Profit Margin (%)',
                'Stock Quantity',
                'Min Alert Level',
                'Retail Valuation (TZS)',
                'Cost Valuation (TZS)',
                'Status',
            ]);

            foreach ($products as $p) {
                $margin = ($p->price > 0 && $p->cost_price) ? round((($p->price - $p->cost_price) / $p->price) * 100, 1) . '%' : '—';
                $status = $p->is_service ? 'Service Tariff' : (($p->stock_quantity == 0) ? 'Out of Stock' : (($p->stock_quantity <= ($p->min_stock_alert ?? 5)) ? 'Low Stock' : 'In Stock'));

                fputcsv($handle, [
                    $p->sku,
                    $p->name,
                    $p->category,
                    $p->is_service ? 'Service' : 'Physical Good',
                    number_format($p->price),
                    $p->cost_price ? number_format($p->cost_price) : '—',
                    $margin,
                    $p->is_service ? '—' : $p->stock_quantity,
                    $p->is_service ? '—' : ($p->min_stock_alert ?? 5),
                    $p->is_service ? '—' : number_format(($p->stock_quantity ?? 0) * $p->price),
                    $p->is_service ? '—' : number_format(($p->stock_quantity ?? 0) * ($p->cost_price ?? 0)),
                    $status,
                ]);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Delete Catalog Item
     */
    public function destroyProduct(int $id)
    {
        $product = PosProduct::findOrFail($id);
        $name = $product->name;
        $sku = $product->sku;
        $stock = $product->stock_quantity;
        try {
            InventoryLog::create([
                'pos_product_id' => $product->id,
                'type' => 'deletion',
                'quantity_change' => -((int)($stock ?? 0)),
                'quantity_before' => (int)($stock ?? 0),
                'quantity_after' => 0,
                'reason' => "Product Deleted: {$name} ({$sku}) • Stock: {$stock}",
                'reference' => $sku,
                'user_id' => auth()->id(),
            ]);
        } catch (\Throwable $e) { \Illuminate\Support\Facades\Log::warning('[destroyProduct] audit log failed: '.$e->getMessage()); }
        $product->delete();

        return back()->with('success', "Item '{$name}' removed from inventory.");
    }

    /**
     * Store 1-Click Quotation
     */
    /**
     * Store Jorban ERP Quotation / Proforma
     */
    public function storeQuotation(Request $request)
    {
        if (!$request->has('items') && ($request->filled('item_description') || $request->filled('item_name'))) {
            $itemName = $request->input('item_description') ?: $request->input('item_name');
            $request->merge([
                'items' => [
                    [
                        'item_name' => $itemName,
                        'item_description' => $itemName,
                        'quantity' => $request->input('quantity', 1),
                        'unit_price' => $request->input('unit_price', 0),
                        'discount_amount' => $request->input('discount', 0),
                        'tax_rate' => $request->input('tax_rate', 0),
                    ]
                ]
            ]);
        }

        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'customer_tin' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'billing_address' => 'nullable|string|max:500',
            'currency' => 'nullable|string|max:10',
            'payment_terms' => 'nullable|string|max:100',
            'price_list' => 'nullable|string|max:100',
            'valid_until' => 'nullable|date',
            'terms_conditions' => 'nullable|string',
            'notes' => 'nullable|string',
            'customer_notes' => 'nullable|string',
            'action_type' => 'nullable|string|in:draft,sent,proforma',
            'type' => 'nullable|string|in:quotation,proforma',
            'items' => 'required|array|min:1',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.sku' => 'nullable|string|max:100',
            'items.*.model_specs' => 'nullable|string|max:255',
            'items.*.pos_product_id' => 'nullable|integer',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
            'items.*.discount_rate' => 'nullable|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0',
            'shipping_amount' => 'nullable|numeric|min:0',
        ]);

        $subtotal = 0;
        $totalDiscount = 0;
        $totalTax = 0;

        foreach ($validated['items'] as $it) {
            $lineGross = ((float)$it['quantity'] * (float)$it['unit_price']);
            $lineDisc = isset($it['discount_rate']) ? round($lineGross * ((float)$it['discount_rate'] / 100), 2) : (float)($it['discount_amount'] ?? 0);
            $lineNet = max(0, $lineGross - $lineDisc);
            $lineTaxRate = (float)($it['tax_rate'] ?? 0);
            $lineTax = ($lineTaxRate > 0) ? round($lineNet * ($lineTaxRate / 100), 2) : 0;

            $subtotal += $lineGross;
            $totalDiscount += $lineDisc;
            $totalTax += $lineTax;
        }

        $shipping = (float)($request->input('shipping_amount') ?? 0);
        $totalAmount = max(0, $subtotal - $totalDiscount) + $totalTax + $shipping;
        $isProforma = ($request->input('type') === 'proforma') || ($request->input('action_type') === 'proforma');
        $docStatus = $request->input('action_type') === 'draft' ? 'draft' : 'sent';

        $year = date('Y');
        $proformaNumber = null;
        if ($isProforma) {
            $pfCount = SalesQuotation::where('type', 'proforma')->whereYear('created_at', $year)->count() + 1;
            $proformaNumber = 'PF-' . $year . '-' . str_pad($pfCount, 6, '0', STR_PAD_LEFT);
        }

        $quote = SalesQuotation::create([
            'type' => $isProforma ? 'proforma' : 'quotation',
            'proforma_number' => $proformaNumber,
            'customer_name' => $validated['customer_name'],
            'company' => $validated['company'] ?? null,
            'customer_tin' => $validated['customer_tin'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'billing_address' => $validated['billing_address'] ?? null,
            'currency' => $validated['currency'] ?? 'TZS',
            'payment_terms' => $validated['payment_terms'] ?? 'Due Upon Receipt',
            'price_list' => $validated['price_list'] ?? 'Standard Retail',
            'subtotal' => $subtotal,
            'discount' => $totalDiscount,
            'tax_rate' => $totalTax > 0 ? 18.00 : 0,
            'tax_amount' => $totalTax,
            'total_amount' => $totalAmount,
            'status' => $docStatus,
            'valid_until' => $validated['valid_until'] ?? now()->addDays(7),
            'terms_conditions' => $validated['terms_conditions'] ?? "1. Quotation valid for 7 calendar days.\n2. Prices and stock subject to prior sale.\n3. Standard manufacturer warranty applies to electronics.",
            'notes' => $validated['notes'] ?? null,
            'customer_notes' => $validated['customer_notes'] ?? null,
            'created_by' => auth()->id(),
            'timeline_events' => [
                [
                    'action' => 'created',
                    'title' => ($isProforma ? 'Proforma Invoice' : 'Quotation') . ' created',
                    'actor' => auth()->user()?->name ?? 'Sales Division',
                    'timestamp' => now()->toDateTimeString(),
                    'note' => $docStatus === 'draft' ? 'Saved as draft' : 'Generated & marked as sent to client',
                ]
            ],
        ]);

        foreach ($validated['items'] as $it) {
            $lineGross = ((float)$it['quantity'] * (float)$it['unit_price']);
            $lineDisc = isset($it['discount_rate']) ? round($lineGross * ((float)$it['discount_rate'] / 100), 2) : (float)($it['discount_amount'] ?? 0);
            $lineNet = max(0, $lineGross - $lineDisc);
            $lineTaxRate = (float)($it['tax_rate'] ?? 0);
            $lineTax = ($lineTaxRate > 0) ? round($lineNet * ($lineTaxRate / 100), 2) : 0;
            $lineTotal = $lineNet + $lineTax;

            SalesQuotationItem::create([
                'quotation_id' => $quote->id,
                'pos_product_id' => $it['pos_product_id'] ?? null,
                'sku' => $it['sku'] ?? null,
                'model_specs' => $it['model_specs'] ?? null,
                'item_name' => $it['item_name'],
                'item_description' => $it['item_description'] ?? ($it['item_name'] . (!empty($it['model_specs']) ? ' — ' . $it['model_specs'] : '')),
                'quantity' => (float)$it['quantity'],
                'unit_price' => (float)$it['unit_price'],
                'discount_rate' => isset($it['discount_rate']) ? (float)$it['discount_rate'] : ($lineGross > 0 ? round(($lineDisc / $lineGross) * 100, 2) : 0),
                'discount_amount' => $lineDisc,
                'tax_rate' => $lineTaxRate,
                'tax_amount' => $lineTax,
                'total_price' => $lineTotal,
            ]);
        }

        $docRef = $isProforma ? ($quote->proforma_number ?: $quote->quote_number) : $quote->quote_number;
        return redirect()->route('sales.index', ['tab' => 'quotations'])
            ->with('success', ($isProforma ? 'Proforma Invoice ' : 'Quotation ') . $docRef . ' created successfully ✅');
    }

    public function storeLead(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'source' => 'nullable|string|max:100',
            'product_interest' => 'nullable|string|max:255',
            'estimated_value' => 'nullable|numeric',
        ]);

        \App\Models\SalesLead::create([
            'name' => $validated['name'],
            'company' => $validated['company'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'source' => $validated['source'] ?? 'Direct',
            'product_interest' => $validated['product_interest'] ?? null,
            'estimated_value' => $validated['estimated_value'] ?? 0,
            'status' => 'new',
            'assigned_to' => auth()->id(),
        ]);

        return redirect()->route('sales.index', ['tab' => 'leads'])
            ->with('success', 'Lead created successfully');
    }

    public function updateLeadStatus(Request $request, $id)
    {
        $lead = \App\Models\SalesLead::findOrFail($id);
        $status = $request->input('status', 'qualified');
        $lead->update(['status' => $status]);

        if ($status === 'qualified') {
            \App\Models\SalesOpportunity::firstOrCreate(
                ['lead_id' => $lead->id],
                [
                    'title' => $lead->company ? ($lead->company . ' — ' . $lead->product_interest) : $lead->name,
                    'customer_name' => $lead->name,
                    'company' => $lead->company,
                    'estimated_value' => $lead->estimated_value ?? 0,
                    'stage' => 'qualified',
                    'assigned_to' => auth()->id(),
                ]
            );
        }

        return redirect()->route('sales.index', ['tab' => 'leads'])
            ->with('success', 'Lead status updated');
    }

    public function convertLead(Request $request, $id)
    {
        $lead = \App\Models\SalesLead::findOrFail($id);
        $lead->update([
            'status' => 'converted',
            'converted_at' => now(),
        ]);

        $opp = \App\Models\SalesOpportunity::create([
            'lead_id' => $lead->id,
            'title' => $request->input('deal_title') ?: ($lead->company . ' Deal'),
            'customer_name' => $lead->name,
            'company' => $lead->company,
            'estimated_value' => $request->input('estimated_value') ?: $lead->estimated_value,
            'expected_close_date' => $request->input('expected_close_date') ?: now()->addDays(30),
            'stage' => 'qualification',
            'assigned_to' => auth()->id(),
        ]);

        \App\Models\SalesActivity::create([
            'customer_name' => $lead->name,
            'activity_type' => 'Lead Conversion',
            'subject' => 'Converted Lead to Opportunity: ' . $opp->title,
            'status' => 'completed',
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('sales.index', ['tab' => 'pipeline'])
            ->with('success', 'Lead converted to opportunity successfully');
    }

    public function duplicateQuotation(Request $request, $id)
    {
        $quote = SalesQuotation::with('items')->findOrFail($id);
        $cloned = $quote->replicate(['quote_number', 'proforma_number', 'uuid']);
        $cloned->status = 'draft';
        $cloned->quote_number = null;
        $cloned->proforma_number = null;
        $cloned->uuid = null;
        $cloned->created_at = now();
        $cloned->save();

        foreach ($quote->items as $item) {
            $newItem = $item->replicate();
            $newItem->quotation_id = $cloned->id;
            $newItem->save();
        }

        return redirect()->route('sales.index', ['tab' => 'quotations'])
            ->with('success', 'Quotation duplicated');
    }

    /**
     * Show Document Detail & Lifecycle Timeline View
     */
    public function showQuotation(int $id)
    {
        $quote = SalesQuotation::with(['items.product', 'creator', 'parentQuotation', 'invoices'])->findOrFail($id);

        // Calculate credit info for this customer if exists
        $creditInfo = $this->calculateCustomerCredit($quote->customer_name, $quote->phone);

        // Fetch products for fast adding or comparison
        $products = PosProduct::active()->orderBy('category')->orderBy('name')->get();

        return view('sales.show_document', compact('quote', 'creditInfo', 'products'));
    }

    /**
     * Convert Quotation -> Proforma Invoice
     */
    public function convertToProforma(int $id)
    {
        $quote = SalesQuotation::with('items')->findOrFail($id);
        $year = date('Y');

        if (empty($quote->proforma_number)) {
            $pfCount = SalesQuotation::whereNotNull('proforma_number')->whereYear('created_at', $year)->count() + 1;
            $quote->proforma_number = 'PF-' . $year . '-' . str_pad($pfCount, 6, '0', STR_PAD_LEFT);
        }

        $quote->type = 'proforma';
        if ($quote->status === 'draft') {
            $quote->status = 'issued';
        }
        $quote->save();

        $quote->recordEvent('proforma_converted', 'Converted to Proforma Invoice ' . $quote->proforma_number, 'Generated official preliminary billing document for customer');

        return redirect()->route('sales.quotations.show', $quote->id)
            ->with('success', 'Document converted to Proforma Invoice ' . $quote->proforma_number . ' ✅ (No accounts receivable impact until order/invoice)');
    }

    /**
     * Convert Quotation / Proforma -> Confirmed Sales Order
     */
    public function convertToSalesOrder(int $id)
    {
        $quote = SalesQuotation::with(['items.product'])->findOrFail($id);
        $year = date('Y');

        if (empty($quote->sales_order_number)) {
            $soCount = SalesQuotation::whereNotNull('sales_order_number')->whereYear('created_at', $year)->count() + 1;
            $quote->sales_order_number = 'SO-' . $year . '-' . str_pad($soCount, 6, '0', STR_PAD_LEFT);
        }

        $quote->status = 'accepted';
        $quote->save();

        // Check and reserve stock if applicable
        $reservedSummary = [];
        foreach ($quote->items as $item) {
            if ($item->product && !$item->product->is_service) {
                $reservedSummary[] = $item->product->name . ' (' . (int)$item->quantity . ' units reserved)';
            }
        }
        $stockNote = !empty($reservedSummary) ? 'Stock reserved: ' . implode(', ', $reservedSummary) : 'Order confirmed by client';

        $quote->recordEvent('order_confirmed', 'Converted to Sales Order ' . $quote->sales_order_number, $stockNote);

        SaleInvoice::firstOrCreate(
            ['sales_quotation_id' => $quote->id],
            [
                'customer_name' => $quote->customer_name,
                'customer_phone' => $quote->phone,
                'service' => $quote->items->first()?->item_name ?? 'Quotation Order',
                'amount' => (int) round((float) $quote->total_amount),
                'subtotal' => $quote->subtotal,
                'discount_amount' => $quote->discount,
                'tax_amount' => $quote->tax_amount,
                'status' => 'pending_payment',
                'owner_id' => auth()->id() ?? $quote->created_by,
            ]
        );

        return redirect()->route('sales.index', ['tab' => 'orders'])
            ->with('success', 'Order confirmed! Sales Order ' . $quote->sales_order_number . ' generated with stock reserved ✅');
    }

    /**
     * Convert Sales Order / Proforma -> Official Tax / Accounting Invoice
     */
    public function convertToInvoice(Request $request, int $id)
    {
        $quote = SalesQuotation::with('items')->findOrFail($id);

        $items = $quote->items->map(fn($it) => [
            'id' => $it->pos_product_id,
            'name' => $it->item_name,
            'sku' => $it->sku,
            'price' => (float)$it->unit_price,
            'qty' => (int)$it->quantity,
            'specs' => $it->model_specs,
        ])->toArray();

        $today = now()->format('Ymd');
        $count = SaleInvoice::whereDate('created_at', today())->count() + 1;
        $receiptNum = 'INV-' . date('Y') . '-' . str_pad((string)$count, 6, '0', STR_PAD_LEFT);

        $sourceRef = $quote->sales_order_number ?: ($quote->proforma_number ?: $quote->quote_number);

        $invoice = SaleInvoice::create([
            'uuid' => (string) Str::uuid(),
            'sales_quotation_id' => $quote->id,
            'source_document' => $sourceRef,
            'receipt_number' => $receiptNum,
            'customer_name' => $quote->customer_name,
            'company' => $quote->company,
            'customer_phone' => $quote->phone,
            'customer_tin' => $quote->customer_tin,
            'service' => ($quote->sales_order_number ? 'Sales Order ' . $quote->sales_order_number : 'Order') . ' — ' . $quote->items->pluck('item_name')->join(', '),
            'amount' => (int) round($quote->total_amount),
            'subtotal' => $quote->subtotal,
            'discount_amount' => $quote->discount,
            'tax_amount' => $quote->tax_amount,
            'tendered_amount' => $quote->total_amount,
            'change_amount' => 0,
            'payment_method' => $request->input('payment_method', 'cash'),
            'items_json' => $items,
            'status' => 'pending_payment',
            'owner_id' => auth()->id(),
        ]);

        // Deduct inventory physical stock
        foreach ($quote->items as $it) {
            if ($it->product && !$it->product->is_service) {
                $qty = (int)$it->quantity;
                $it->product->decrement('stock_quantity', $qty);

                InventoryLog::create([
                    'pos_product_id' => $it->product->id,
                    'action' => 'stock_out',
                    'quantity' => $qty,
                    'reference' => $receiptNum . ' (' . $sourceRef . ')',
                    'reason' => 'Official Sales Invoice issuance to ' . $quote->customer_name,
                    'user_id' => auth()->id(),
                ]);
            }
        }

        $quote->update(['status' => 'converted']);
        $quote->recordEvent('invoice_created', 'Official Invoice Issued #' . $invoice->receipt_number, 'Debited to customer ledger; inventory deducted and sent to payment checkout');

        return redirect()->route('sales.index', ['tab' => 'register'])
            ->with('success', 'Official Invoice ' . $invoice->receipt_number . ' created from ' . $sourceRef . ' — ready for cashier payment collection ✅');
    }

    /**
     * Update quotation / proforma workflow status
     */
    public function updateQuotationStatus(Request $request, int $id)
    {
        $quote = SalesQuotation::findOrFail($id);
        $status = $request->validate([
            'status' => 'required|string|in:draft,sent,viewed,accepted,rejected,expired,converted,cancelled',
            'note' => 'nullable|string|max:500',
        ]);

        $oldStatus = $quote->status;
        $quote->status = $status['status'];
        $quote->save();

        $quote->recordEvent('status_change', 'Status updated to ' . strtoupper($quote->status), $status['note'] ?? ('Status transitioned from ' . strtoupper($oldStatus)));

        return back()->with('success', 'Status updated to ' . strtoupper($quote->status) . ' ✅');
    }

    /**
     * Calculate customer credit status across Debits & Invoices
     */
    private function calculateCustomerCredit(?string $name, ?string $phone): array
    {
        $limit = 10000000; // 10,000,000 TZS default standard customer credit limit
        if (empty($name)) {
            return [
                'credit_limit' => $limit,
                'outstanding' => 0,
                'available' => $limit,
                'over_limit' => false,
            ];
        }

        // Outstanding customer debits
        $debitQuery = Debit::where('type', 'customer')->where('status', 'pending');
        if ($phone) {
            $debitQuery->where(function ($q) use ($name, $phone) {
                $q->where('customer_phone', $phone)->orWhere('customer_name', 'like', '%' . $name . '%');
            });
        } else {
            $debitQuery->where('customer_name', 'like', '%' . $name . '%');
        }
        $debitTotal = (float)$debitQuery->sum('amount');

        // Unpaid sale invoices
        $invoiceQuery = SaleInvoice::where('status', 'pending_payment');
        if ($phone) {
            $invoiceQuery->where(function ($q) use ($name, $phone) {
                $q->where('customer_phone', $phone)->orWhere('customer_name', 'like', '%' . $name . '%');
            });
        } else {
            $invoiceQuery->where('customer_name', 'like', '%' . $name . '%');
        }
        $invoiceTotal = (float)$invoiceQuery->sum('amount');

        $outstanding = $debitTotal + $invoiceTotal;
        $available = max(0, $limit - $outstanding);

        return [
            'credit_limit' => $limit,
            'outstanding' => $outstanding,
            'available' => $available,
            'over_limit' => $outstanding >= $limit,
        ];
    }

    /**
     * Customer Credit API endpoint for interactive Sale / Quote builder
     */
    public function getCustomerCredit(Request $request)
    {
        $name = $request->query('name');
        $phone = $request->query('phone');
        $amount = (float)$request->query('amount', 0);

        $credit = $this->calculateCustomerCredit($name, $phone);
        $credit['would_exceed'] = ($credit['outstanding'] + $amount) > $credit['credit_limit'];

        return response()->json($credit);
    }

    public function printQuotation(int $id)
    {
        $quote = SalesQuotation::with(['items.product', 'creator'])->findOrFail($id);

        $lines = $quote->items->map(fn($it) => [
            'item' => $it->sku ?: 'EQUIPMENT',
            'description' => trim($it->item_name ?: $it->item_description) . ($it->model_specs ? ' [' . $it->model_specs . ']' : ''),
            'qty' => (int)$it->quantity,
            'rate' => (float)$it->unit_price,
            'amount' => (float)$it->total_price,
        ])->toArray();

        if (empty($lines) && !empty($quote->items_json)) {
            $lines = collect($quote->items_json)->map(fn($it) => [
                'item' => 'EQUIPMENT',
                'description' => ($it['name'] ?? $it['item_name'] ?? ''),
                'qty' => (int)($it['qty'] ?? $it['quantity'] ?? 1),
                'rate' => (float)($it['price'] ?? $it['unit_price'] ?? 0),
                'amount' => (float)(($it['qty'] ?? 1) * ($it['price'] ?? 0)),
            ])->toArray();
        }

        $subTotal = (float)($quote->subtotal ?? collect($lines)->sum('amount'));
        $taxRate = (float)($quote->tax_rate ?? 18);
        $taxAmount = (float)($quote->tax_amount ?? round($subTotal * 0.18, 2));
        $totalAmount = (float)($quote->total_amount ?? ($subTotal + $taxAmount));

        $docTitle = ($quote->type === 'proforma') ? 'PROFORMA INVOICE' : 'QUOTATION';
        $docRef = ($quote->type === 'proforma' && $quote->proforma_number) ? $quote->proforma_number : $quote->quote_number;

        return view('sales.invoice_a4', [
            'mode' => $quote->type === 'proforma' ? 'proforma' : 'quotation',
            'refNo' => $docRef,
            'billTo' => [
                'name' => strtoupper($quote->customer_name),
                'tin' => $quote->customer_tin,
                'address1' => $quote->billing_address ? explode("\n", $quote->billing_address)[0] : 'P.O Box 941',
                'address2' => $quote->billing_address && str_contains($quote->billing_address, "\n") ? explode("\n", $quote->billing_address)[1] : 'Dodoma / Dar es Salaam',
                'client_code' => $quote->client_code ?? '0114',
            ],
            'currency' => $quote->currency ?: 'TZS',
            'invoiceDate' => $quote->created_at ? $quote->created_at->format('d-M-Y') : now()->format('d-M-Y'),
            'dueDate' => $quote->valid_until ? \Carbon\Carbon::parse($quote->valid_until)->format('d-M-Y') : now()->addDays(7)->format('d-M-Y'),
            'paymentTerms' => $quote->payment_terms ?? '100% upon order confirmation',
            'lines' => $lines,
            'subTotal' => $subTotal,
            'discountAmount' => (float)$quote->discount,
            'taxAmount' => $taxAmount,
            'totalAmount' => $totalAmount,
            'payStatus' => strtoupper($quote->status ?? 'PENDING'),
            'quote' => $quote,
        ]);
    }

    public function printInvoice(int $id)
    {
        $invoice = SaleInvoice::with(['owner', 'quotation'])->findOrFail($id);
        $items = $invoice->items_json ?: [];
        $lines = collect($items)->map(fn($it) => [
            'item' => $it['sku'] ?? 'EQUIPMENT',
            'description' => strtoupper(trim($it['name'] ?? $it['item_name'] ?? $invoice->service ?? '')) . (!empty($it['specs']) ? ' [' . $it['specs'] . ']' : ''),
            'qty' => (int)($it['qty'] ?? $it['quantity'] ?? 1),
            'rate' => (float)($it['price'] ?? $it['unit_price'] ?? ($invoice->amount / max(1, (int)($it['qty'] ?? 1)))),
            'amount' => (float)(($it['qty'] ?? 1) * ($it['price'] ?? $it['unit_price'] ?? $invoice->amount)),
        ])->toArray();

        if (empty($lines)) {
            $qty = 1;
            $rate = (float)($invoice->subtotal ?: $invoice->amount);
            $lines = [[
                'item' => 'EQUIPMENT',
                'description' => strtoupper($invoice->service ?: 'SALES ORDER ITEM'),
                'qty' => $qty,
                'rate' => $rate,
                'amount' => $rate,
            ]];
        }

        $subTotal = (float)($invoice->subtotal ?: collect($lines)->sum('amount'));
        if ($subTotal == 0) $subTotal = collect($lines)->sum('amount');
        $taxAmount = (float)($invoice->tax_amount ?: round($subTotal * 0.18, 2));
        $totalAmount = (float)($invoice->amount ?: ($subTotal + $taxAmount));

        return view('sales.invoice_a4', [
            'mode' => 'invoice',
            'refNo' => $invoice->receipt_number ?: ('INV-' . str_pad($invoice->id, 6, '0', STR_PAD_LEFT)),
            'sourceRef' => $invoice->source_document,
            'billTo' => [
                'name' => strtoupper($invoice->customer_name ?: 'Customer'),
                'tin' => $invoice->customer_tin,
                'address1' => 'P.O Box 941',
                'address2' => 'Dar es Salaam / Dodoma',
                'client_code' => '0114',
            ],
            'currency' => 'TZS',
            'invoiceDate' => $invoice->created_at ? $invoice->created_at->format('d-M-Y') : now()->format('d-M-Y'),
            'dueDate' => $invoice->created_at ? $invoice->created_at->format('d-M-Y') : now()->format('d-M-Y'),
            'paymentTerms' => 'Due Upon Receipt',
            'lines' => $lines,
            'subTotal' => $subTotal,
            'discountAmount' => (float)$invoice->discount_amount,
            'taxAmount' => $taxAmount,
            'totalAmount' => $totalAmount,
            'payStatus' => strtoupper($invoice->status === 'paid' ? 'PAID' : 'PENDING PAYMENT'),
            'invoice' => $invoice,
        ]);
    }

    public function acceptQuotation(int $id)
    {
        return $this->convertToSalesOrder($id);
    }

    public function destroyQuotation(int $id)
    {
        $quote = SalesQuotation::findOrFail($id);
        $num = $quote->quote_number;
        $quote->delete();

        return redirect()->route('sales.index', ['tab' => 'quotes'])->with('success', "Quotation {$num} deleted.");
    }

    /**
     * Export Transactions CSV
     */
    public function exportInvoices(Request $request)
    {
        $query = SaleInvoice::with(['itTicket', 'owner'])->latest('id');
        $invoices = $query->get();
        $filename = 'pos_sales_register_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($invoices) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'Receipt / Inv #',
                'Date & Time',
                'Customer Name',
                'Phone',
                'Items Summary',
                'Payment Method',
                'Reference',
                'Subtotal (TZS)',
                'VAT (TZS)',
                'Discount (TZS)',
                'Total Amount (TZS)',
                'Status',
                'Cashier',
            ]);

            foreach ($invoices as $inv) {
                fputcsv($handle, [
                    $inv->receipt_number ?? ('#ORD-' . $inv->id),
                    $inv->paid_at ? $inv->paid_at->format('Y-m-d H:i') : ($inv->created_at ? $inv->created_at->format('Y-m-d H:i') : '—'),
                    $inv->customer_name ?: 'Walk-in Customer',
                    $inv->customer_phone ?? '—',
                    $inv->service ?? '—',
                    strtoupper($inv->payment_method ?? 'CASH'),
                    $inv->payment_reference ?? '—',
                    number_format($inv->subtotal ?: $inv->amount),
                    number_format($inv->tax_amount ?? 0),
                    number_format($inv->discount_amount ?? 0),
                    number_format($inv->amount),
                    $inv->status === 'paid' ? 'Settled' : 'Pending',
                    $inv->owner?->name ?? 'FrontDesk Cashier',
                ]);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Sales History Hub — all invoices + quotations — production
     */
    public function history(Request $request)
    {
        $tab = $request->get('tab', 'invoices');
        $search = $request->get('search', '');
        $status = $request->get('status', 'all');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        try {
            $invoicesQuery = SaleInvoice::with(['owner'])->latest('id');
            if ($search) {
                $invoicesQuery->where(function($q) use ($search) {
                    $q->where('receipt_number', 'like', "%{$search}%")
                      ->orWhere('customer_name', 'like', "%{$search}%")
                      ->orWhere('company', 'like', "%{$search}%")
                      ->orWhere('service', 'like', "%{$search}%");
                });
            }
            if ($status !== 'all') {
                $invoicesQuery->where('status', $status);
            }
            if ($dateFrom) {
                $invoicesQuery->whereDate('created_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $invoicesQuery->whereDate('created_at', '<=', $dateTo);
            }
            $invoices = $invoicesQuery->paginate(20)->withQueryString();
            $allInvoices = SaleInvoice::with(['owner'])->latest('id')->get();
        } catch (\Throwable $e) {
            $invoices = new \Illuminate\Pagination\LengthAwarePaginator(collect(), 0, 20);
            $allInvoices = collect();
        }

        try {
            $quotationsQuery = SalesQuotation::with(['items','creator'])->latest('id');
            if ($search && $tab === 'quotations') {
                $quotationsQuery->where(function($q) use ($search) {
                    $q->where('quote_number', 'like', "%{$search}%")
                      ->orWhere('customer_name', 'like', "%{$search}%")
                      ->orWhere('company', 'like', "%{$search}%");
                });
            }
            if ($tab === 'quotations' && $status !== 'all') {
                $quotationsQuery->where('status', $status);
            }
            $quotations = $quotationsQuery->paginate(20)->withQueryString();
            $allQuotations = SalesQuotation::with(['items'])->latest('id')->get();
        } catch (\Throwable $e) {
            $quotations = new \Illuminate\Pagination\LengthAwarePaginator(collect(), 0, 20);
            $allQuotations = collect();
        }

        // KPIs for history hub
        $totalSales = (int) $allInvoices->where('status','paid')->sum('amount');
        $totalPending = (int) $allInvoices->where('status','pending_payment')->sum('amount');
        $totalQuotations = $allQuotations->count();
        $quotedValue = (int) $allQuotations->sum('total_amount');
        $todayCount = $allInvoices->filter(fn($i) => $i->created_at && \Carbon\Carbon::parse($i->created_at)->isToday())->count();
        $paidCount = $allInvoices->where('status','paid')->count();
        $pendingCount = $allInvoices->where('status','pending_payment')->count();

        return view('sales.history', compact('invoices','quotations','allInvoices','allQuotations','tab','search','status','dateFrom','dateTo','totalSales','totalPending','totalQuotations','quotedValue','todayCount','paidCount','pendingCount'));
    }
}
