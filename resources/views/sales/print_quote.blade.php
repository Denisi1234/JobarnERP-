<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation {{ $quote->quote_number }} — {{ $quote->customer_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; font-size: 12pt; }
            .print-card { border: none !important; box-shadow: none !important; }
        }
    </style>
</head>
<body class="bg-surface text-on-surface antialiased p-4 sm:p-8">

    {{-- Top Action Toolbar (Hidden in print) --}}
    <div class="max-w-4xl mx-auto mb-6 flex items-center justify-between no-print bg-surface-container-lowest p-4 rounded-xl shadow-sm border border-outline-variant/30">
        <div class="flex items-center gap-3">
            <a href="{{ route('sales.index', ['tab' => 'quotes']) }}" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-outline-variant/40 text-on-surface-variant hover:text-on-surface text-sm font-medium">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                <span>Back to Quotes</span>
            </a>
            <span class="text-xs text-on-surface-variant">SuiteCRM Enterprise Document Engine</span>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="flex items-center gap-1.5 px-4 py-2 bg-primary text-on-primary rounded-lg text-sm font-semibold shadow-sm hover:opacity-90">
                <span class="material-symbols-outlined text-[18px]">print</span>
                <span>Print / Save PDF</span>
            </button>
        </div>
    </div>

    {{-- Printable Quotation Document --}}
    <div class="max-w-4xl mx-auto bg-surface-container-lowest rounded-2xl p-8 sm:p-12 shadow-md border border-outline-variant/30 print-card">
        
        {{-- Header Strip --}}
        <div class="flex flex-col sm:flex-row justify-between items-start gap-6 border-b border-outline-variant/40 pb-8">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span class="p-2 rounded-xl bg-primary text-on-primary">
                        <span class="material-symbols-outlined text-[24px]">business</span>
                    </span>
                    <span class="text-xl font-bold tracking-tight text-on-surface">WORKSHOP ENTERPRISE CRM</span>
                </div>
                <p class="text-xs text-on-surface-variant leading-relaxed">
                    Ali Hassan Mwinyi Road, Dar es Salaam, Tanzania<br>
                    Email: sales@workshop.co.tz &nbsp;·&nbsp; Phone: +255 700 000 000<br>
                    TIN: 104-982-341 &nbsp;·&nbsp; VRN: 40-029104-M
                </p>
            </div>
            <div class="text-left sm:text-right">
                <span class="inline-block px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-primary/10 text-primary mb-2">
                    Commercial Quotation
                </span>
                <div class="text-2xl font-mono font-bold text-on-surface">{{ $quote->quote_number }}</div>
                <p class="text-xs text-on-surface-variant mt-1">
                    Date: <strong>{{ $quote->created_at ? $quote->created_at->format('d M Y') : now()->format('d M Y') }}</strong><br>
                    Valid Until: <strong>{{ $quote->valid_until ? $quote->valid_until->format('d M Y') : '14 Days from issuance' }}</strong><br>
                    Payment Terms: <strong>{{ $quote->payment_terms ?? 'Net 30 Days' }}</strong>
                </p>
            </div>
        </div>

        {{-- Recipient & Billing Info (SuiteCRM 2-Column Overview) --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 my-8 p-4 bg-surface-container/30 rounded-xl border border-outline-variant/30">
            <div>
                <div class="text-xs uppercase tracking-wider font-bold text-on-surface-variant mb-1">Quote To (Client Account):</div>
                <div class="text-base font-bold text-on-surface">{{ $quote->customer_name }}</div>
                @if($quote->company)
                    <div class="text-sm font-medium text-primary">{{ $quote->company }}</div>
                @endif
                <div class="text-xs text-on-surface-variant mt-1">
                    @if($quote->phone) <div>Phone: {{ $quote->phone }}</div> @endif
                    @if($quote->email) <div>Email: {{ $quote->email }}</div> @endif
                    @if($quote->billing_address) <div class="mt-1">Billing: {{ $quote->billing_address }}</div> @endif
                </div>
            </div>
            <div>
                <div class="text-xs uppercase tracking-wider font-bold text-on-surface-variant mb-1">Sales Account Executive:</div>
                <div class="text-base font-bold text-on-surface">{{ $quote->creator?->name ?? 'Sales Division' }}</div>
                <div class="text-xs text-on-surface-variant mt-1">
                    <div>Status: <span class="capitalize font-semibold text-primary">{{ $quote->status }}</span></div>
                    @if($quote->opportunity)
                        <div>Linked Deal: #{{ $quote->opportunity->id }} - {{ $quote->opportunity->title }}</div>
                    @endif
                    @if($quote->shipping_address)
                        <div class="mt-1">Shipping: {{ $quote->shipping_address }}</div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Line Items Table (SuiteCRM AOS_Quotes Multi-Line) --}}
        <div class="overflow-x-auto rounded-xl border border-outline-variant/40 mb-8">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container text-[11px] font-bold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/40">
                        <th class="py-3 px-4 w-12 text-center">#</th>
                        <th class="py-3 px-4">Item &amp; Description</th>
                        <th class="py-3 px-4 text-center w-20">Qty</th>
                        <th class="py-3 px-4 text-right w-32">Unit Price</th>
                        <th class="py-3 px-4 text-right w-24">Discount</th>
                        <th class="py-3 px-4 text-right w-36">Total (TZS)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20 text-xs">
                    @forelse($quote->items as $idx => $item)
                    <tr>
                        <td class="py-3 px-4 text-center font-mono text-on-surface-variant">{{ $idx + 1 }}</td>
                        <td class="py-3 px-4">
                            <div class="font-bold text-on-surface text-sm">{{ $item->item_name ?: $item->item_description }}</div>
                            @if($item->item_name && $item->item_description && $item->item_name !== $item->item_description)
                                <div class="text-xs text-on-surface-variant mt-0.5">{{ $item->item_description }}</div>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-center font-semibold">{{ $item->quantity }}</td>
                        <td class="py-3 px-4 text-right font-mono">{{ number_format($item->unit_price) }}</td>
                        <td class="py-3 px-4 text-right font-mono text-on-surface-variant">
                            {{ $item->discount_rate > 0 ? $item->discount_rate . '%' : '—' }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-on-surface">{{ number_format($item->total_price) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-4 px-4 text-center text-on-surface-variant">Standard Service Line Item</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Calculation Breakdown --}}
        <div class="flex flex-col sm:flex-row justify-between items-start gap-8 border-t border-outline-variant/40 pt-6">
            <div class="flex-1 text-xs text-on-surface-variant leading-relaxed">
                <div class="font-bold uppercase tracking-wider text-on-surface mb-2">Terms &amp; Conditions</div>
                <div class="whitespace-pre-line bg-surface-container/20 p-3 rounded-lg border border-outline-variant/20">
                    {{ $quote->terms_conditions ?: "1. Quotation valid for 14 calendar days from date of issue.\n2. Payment terms: " . ($quote->payment_terms ?? 'Net 30 Days') . ".\n3. Goods remain the property of Workshop Enterprise until settled in full." }}
                </div>
            </div>
            <div class="w-full sm:w-80 flex flex-col gap-2 text-xs">
                <div class="flex justify-between py-1 border-b border-outline-variant/20">
                    <span class="text-on-surface-variant font-medium">Subtotal</span>
                    <span class="font-mono font-bold text-on-surface">TZS {{ number_format($quote->subtotal) }}</span>
                </div>
                @if($quote->discount > 0)
                <div class="flex justify-between py-1 border-b border-outline-variant/20 text-emerald-600">
                    <span class="font-medium">Total Discount</span>
                    <span class="font-mono font-bold">- TZS {{ number_format($quote->discount) }}</span>
                </div>
                @endif
                <div class="flex justify-between py-1 border-b border-outline-variant/20">
                    <span class="text-on-surface-variant font-medium">VAT ({{ $quote->tax_rate ?? 18 }}%)</span>
                    <span class="font-mono font-bold text-on-surface">TZS {{ number_format($quote->tax_amount) }}</span>
                </div>
                @if($quote->shipping_amount > 0)
                <div class="flex justify-between py-1 border-b border-outline-variant/20">
                    <span class="text-on-surface-variant font-medium">Shipping / Logistics</span>
                    <span class="font-mono font-bold text-on-surface">TZS {{ number_format($quote->shipping_amount) }}</span>
                </div>
                @endif
                <div class="flex justify-between py-3 border-t-2 border-on-surface text-base">
                    <span class="font-bold text-on-surface">Grand Total</span>
                    <span class="font-mono font-bold text-primary">TZS {{ number_format($quote->total_amount) }}</span>
                </div>
            </div>
        </div>

        {{-- Signature Sign-off --}}
        <div class="grid grid-cols-2 gap-12 mt-16 pt-8 border-t border-outline-variant/30 text-xs">
            <div>
                <div class="border-b border-outline-variant/60 pb-12"></div>
                <div class="mt-2 font-bold text-on-surface">Authorized Signatory (Workshop CRM)</div>
                <div class="text-on-surface-variant text-[11px]">Date &amp; Official Stamp</div>
            </div>
            <div>
                <div class="border-b border-outline-variant/60 pb-12"></div>
                <div class="mt-2 font-bold text-on-surface">Client Acceptance Signatory</div>
                <div class="text-on-surface-variant text-[11px]">Name, Signature &amp; Date</div>
            </div>
        </div>

    </div>

</body>
</html>
