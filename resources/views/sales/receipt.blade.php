<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #{{ $invoice->receipt_number ?? ('RCP-' . $invoice->id) }} — JOBARN POS</title>
    <style>
        @page {
            margin: 0;
            size: auto;
        }
        body {
            font-family: 'Courier New', Courier, monospace, -apple-system, BlinkMacSystemFont, sans-serif;
            font-size: 13px;
            line-height: 1.35;
            color: #111;
            background: #f4f4f5;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
        }
        .receipt-container {
            background: #fff;
            width: 80mm;
            max-width: 100%;
            padding: 16px 14px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border-radius: 4px;
            box-sizing: border-box;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .divider {
            border-top: 1px dashed #444;
            margin: 10px 0;
        }
        .double-divider {
            border-top: 2px double #222;
            margin: 10px 0;
        }
        .item-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            margin: 8px 0;
        }
        .item-table th {
            text-align: left;
            border-bottom: 1px dashed #444;
            padding-bottom: 4px;
            font-weight: bold;
        }
        .item-table td {
            padding: 3px 0;
            vertical-align: top;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 2px 0;
            font-size: 12px;
        }
        .grand-total {
            font-size: 15px;
            font-weight: 800;
            padding: 4px 0;
        }
        .actions-bar {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: #18181b;
            color: #fff;
            padding: 10px 20px;
            border-radius: 9999px;
            display: flex;
            gap: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.25);
            z-index: 100;
        }
        .btn-print {
            background: #10b981;
            color: #fff;
            border: none;
            padding: 6px 14px;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            font-family: sans-serif;
            font-size: 13px;
        }
        .btn-close {
            background: #3f3f46;
            color: #fff;
            border: none;
            padding: 6px 14px;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            font-family: sans-serif;
            font-size: 13px;
        }
        @media print {
            body {
                background: #fff;
                padding: 0;
                margin: 0;
            }
            .receipt-container {
                box-shadow: none;
                width: 100%;
                padding: 4mm;
            }
            .actions-bar {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<div class="receipt-container">
    {{-- Header --}}
    <div class="text-center">
        <div style="font-size: 18px; font-weight: 900; letter-spacing: 1px;">JOBARN SYSTEMS</div>
        <div style="font-size: 11px; color: #444; margin-top: 2px;">FrontDesk Point of Sale & IT Care</div>
        <div style="font-size: 11px; color: #444;">Ali Hassan Mwinyi Rd, Dar es Salaam</div>
        <div style="font-size: 11px; color: #444;">Tel: +255 700 000 000 | TIN: 100-245-889</div>
        <div class="divider"></div>
        <div class="font-bold" style="font-size: 13px; letter-spacing: 0.5px;">OFFICIAL FISCAL RECEIPT</div>
    </div>

    {{-- Meta --}}
    <div style="font-size: 11px; margin-top: 8px; line-height: 1.5;">
        <div><strong>Receipt #:</strong> {{ $invoice->receipt_number ?? ('RCP-' . str_pad($invoice->id, 5, '0', STR_PAD_LEFT)) }}</div>
        <div><strong>Date:</strong> {{ $invoice->paid_at ? $invoice->paid_at->format('d/m/Y h:i A') : ($invoice->created_at ? $invoice->created_at->format('d/m/Y h:i A') : now()->format('d/m/Y h:i A')) }}</div>
        <div><strong>Cashier:</strong> {{ $invoice->owner?->name ?? (auth()->user()?->name ?? 'FrontDesk Cashier') }}</div>
        <div><strong>Customer:</strong> {{ $invoice->customer_name ?: 'Walk-in Customer' }}</div>
        @if($invoice->customer_phone)
        <div><strong>Phone:</strong> {{ $invoice->customer_phone }}</div>
        @endif
        @if($invoice->it_ticket_id)
        <div><strong>IT Ticket:</strong> #IT-{{ $invoice->it_ticket_id }}</div>
        @endif
    </div>

    <div class="divider"></div>

    {{-- Items Table --}}
    <table class="item-table">
        <thead>
            <tr>
                <th style="width: 55%;">ITEM</th>
                <th class="text-center" style="width: 15%;">QTY</th>
                <th class="text-right" style="width: 30%;">AMOUNT</th>
            </tr>
        </thead>
        <tbody>
            @php
                $items = $invoice->items_json ?: [];
            @endphp
            @if(!empty($items) && is_array($items))
                @foreach($items as $it)
                <tr>
                    <td>
                        <div class="font-bold">{{ $it['name'] ?? $it['item_name'] ?? 'Product/Service' }}</div>
                        @if(!empty($it['price']))
                        <div style="font-size: 10px; color: #555;">@ {{ number_format($it['price']) }}</div>
                        @endif
                    </td>
                    <td class="text-center">{{ $it['qty'] ?? 1 }}</td>
                    <td class="text-right font-bold">{{ number_format(($it['price'] ?? 0) * ($it['qty'] ?? 1)) }}</td>
                </tr>
                @endforeach
            @else
                <tr>
                    <td>
                        <div class="font-bold">{{ $invoice->service ?: 'General Sales / Service' }}</div>
                    </td>
                    <td class="text-center">1</td>
                    <td class="text-right font-bold">{{ number_format($invoice->amount) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="divider"></div>

    {{-- Totals Calculation --}}
    <div style="margin-top: 6px;">
        @php
            $sub = $invoice->subtotal > 0 ? $invoice->subtotal : $invoice->amount;
            $tax = $invoice->tax_amount > 0 ? $invoice->tax_amount : 0;
            $disc = $invoice->discount_amount > 0 ? $invoice->discount_amount : 0;
            $tot = $invoice->amount > 0 ? $invoice->amount : ($sub + $tax - $disc);
        @endphp
        <div class="total-row">
            <span>Subtotal:</span>
            <span>TZS {{ number_format($sub) }}</span>
        </div>
        @if($disc > 0)
        <div class="total-row">
            <span>Discount:</span>
            <span>- TZS {{ number_format($disc) }}</span>
        </div>
        @endif
        @if($tax > 0)
        <div class="total-row">
            <span>VAT (18%):</span>
            <span>TZS {{ number_format($tax) }}</span>
        </div>
        @endif
        <div class="double-divider"></div>
        <div class="total-row grand-total">
            <span>TOTAL PAID:</span>
            <span>TZS {{ number_format($tot) }}</span>
        </div>
        <div class="divider"></div>

        <div class="total-row" style="font-size: 11px;">
            <span>Payment Method:</span>
            <span class="font-bold">{{ strtoupper($invoice->payment_method ?? 'CASH') }}</span>
        </div>
        @if($invoice->payment_reference)
        <div class="total-row" style="font-size: 11px;">
            <span>Reference / Ref:</span>
            <span>{{ $invoice->payment_reference }}</span>
        </div>
        @endif
        @if($invoice->tendered_amount > 0)
        <div class="total-row" style="font-size: 11px;">
            <span>Cash Tendered:</span>
            <span>TZS {{ number_format($invoice->tendered_amount) }}</span>
        </div>
        <div class="total-row" style="font-size: 11px;">
            <span>Change Due:</span>
            <span>TZS {{ number_format($invoice->change_amount ?? 0) }}</span>
        </div>
        @endif
    </div>

    {{-- Footer --}}
    <div class="divider"></div>
    <div class="text-center" style="font-size: 11px; margin-top: 10px; color: #333;">
        <div>*** THANK YOU FOR YOUR BUSINESS ***</div>
        <div style="margin-top: 4px;">Items sold are subject to warranty terms.</div>
        <div style="margin-top: 2px;">Keep this receipt for support or inquiries.</div>
        <div style="margin-top: 6px; font-size: 10px; color: #777;">Powered by FrontDesk OS — POS</div>
    </div>
</div>

<div class="actions-bar">
    <button class="btn-print" onclick="window.print()">🖨️ Print Receipt</button>
    <button class="btn-close" onclick="window.close(); if(window.opener){window.opener.focus();}">Close Window</button>
</div>

<script>
    // Auto-trigger print dialog if opened in popup
    window.addEventListener('load', () => {
        if (window.location.search.includes('autoprint=1')) {
            setTimeout(() => {
                window.print();
            }, 350);
        }
    });
</script>

</body>
</html>
