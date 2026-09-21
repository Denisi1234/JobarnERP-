<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>{{ ($mode ?? 'invoice') === 'quotation' ? 'Quotation' : 'Invoice' }} {{ $refNo ?? '' }} — Jobarn General Trading</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
  @page { size: A4; margin: 12mm 12mm 12mm 12mm; }
  * { box-sizing: border-box; }
  html, body { margin:0; padding:0; }
  body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 11px;
    line-height: 1.45;
    color: #111;
    background: #fff;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
  .no-print { }
  @media print {
    .no-print { display: none !important; }
    body { background: #fff !important; }
    a { text-decoration: none !important; }
  }
  .page { width: 210mm; min-height: 297mm; margin: 0 auto; background:#fff; position: relative; padding: 12mm 14mm 10mm 14mm; overflow:hidden; }
  @media screen {
    .page { box-shadow: 0 8px 32px rgba(0,0,0,0.08); margin: 16px auto; }
  }
  /* watermark — shield only, no word JOBARN */
  .watermark {
    position: absolute;
    left: 50%; top: 46%;
    transform: translate(-50%, -50%);
    width: 520px; height: 520px;
    background: url('{{ asset('images/jobarn-shield.svg') }}') center center / contain no-repeat;
    opacity: 0.07;
    pointer-events: none;
    z-index: 0;
  }
  .watermark-2 {
    position: absolute;
    left: 50%; top: 52%;
    transform: translate(-50%, -50%);
    width: 560px; height: 560px;
    background: url('{{ asset('images/jobarn-shield.svg') }}') center center / contain no-repeat;
    opacity: 0.065;
    pointer-events: none;
    z-index: 0;
  }
  .content { position: relative; z-index: 1; }
  /* header */
  .header { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }
  .header-left { display:flex; align-items:flex-start; gap:10px; }
  .logo { width: 88px; height: auto; display:block; }
  .company { font-size: 10.5px; line-height:1.5; color:#111; }
  .company strong { font-size: 11.5px; }
  .inv-title { text-align: right; }
  .inv-title h1 { margin:0; font-size: 26px; font-weight:800; color:#2b2f7a; letter-spacing:0.2px; line-height:1; }
  .inv-title .ref { margin-top:6px; font-size:13px; font-weight:800; color:#111; }
  .rule { height:1px; background:#d1d5db; margin:14px 0 14px 0; }
  /* bill to row */
  .bill-row { display:flex; justify-content: space-between; gap:16px; }
  .bill-to-label { font-size:13px; font-weight:800; color:#9aa0a6; letter-spacing:0.2px; }
  .bill-to-name { font-size:11px; font-weight:800; text-transform:uppercase; margin-top:2px; color:#111; }
  .bill-to-lines { font-size:10.5px; color:#111; line-height:1.5; }
  .meta-right { text-align:right; font-size:10.5px; line-height:1.6; color:#111; white-space:nowrap; }
  /* table */
  table.items { width:100%; border-collapse:collapse; margin-top:16px; table-layout:fixed; }
  table.items th, table.items td { border:1px solid #d1d5db; padding:7px 8px; vertical-align:top; }
  table.items th { background:#fff; font-size:10px; font-weight:700; color:#9aa0a6; text-align:left; }
  table.items th.qty, table.items td.qty { text-align:center; }
  table.items th.rate, table.items td.rate, table.items th.amount, table.items td.amount { text-align:right; }
  table.items td { font-size:10.5px; }
  table.items td.desc { text-transform:uppercase; font-weight:600; }
  table.items tr { height: 56px; }
  /* totals */
  .totals-wrap { display:flex; justify-content:flex-end; margin-top:14px; }
  .totals { width: 42%; min-width: 240px; border-collapse:collapse; }
  .totals td { padding:6px 8px; font-size:10.5px; }
  .totals td:first-child { text-align:left; color:#111; }
  .totals td:last-child { text-align:right; font-variant-numeric: tabular-nums; }
  .totals tr.total td { font-weight:800; border-top:1px solid #111; }
  .pay-status td { border-top:1px solid #d1d5db; padding-top:10px !important; }
  .pill { display:inline-block; border:1px solid #9aa0a6; border-radius:9999px; padding:2px 10px; font-size:10px; font-weight:700; color:#6b7280; letter-spacing:0.3px; }
  /* signature */
  .sign-row { display:flex; justify-content:space-between; gap:24px; margin-top:28px; }
  .sign-left, .sign-right { flex:1; }
  .sign-img { height:46px; display:block; margin-bottom:2px; }
  .sign-line { border-top:1px solid #111; margin-top:6px; padding-top:6px; font-size:10.5px; }
  .sign-note { font-size:10px; font-style:italic; color:#111; margin-top:2px; }
  .stamp-box { width: 220px; height: 92px; border:1px solid #d1d5db; display:flex; align-items:center; justify-content:center; overflow:hidden; background:#fff; }
  .stamp-box img { max-width:100%; max-height:100%; object-fit:contain; }
  .stamp-label { text-align:right; font-size:10.5px; margin-bottom:6px; }
  /* footer contact */
  .footer-contact { text-align:center; margin-top:18px; font-size:10.5px; line-height:1.6; }
  .footer-contact a { color:#2b2f7a; font-weight:700; text-decoration:none; }
  /* page 2 */
  .page2-grid { display:flex; gap:28px; margin-top:8px; }
  .page2-col { flex:1; font-size:10.5px; line-height:1.6; }
  .page2-col h3 { margin:0 0 8px 0; font-size:12px; font-weight:800; color:#111; }
  .page-break { page-break-before: always; }
  /* toolbar */
  .toolbar { max-width:210mm; margin:12px auto; display:flex; align-items:center; justify-content:space-between; gap:12px; background:#fff; border:1px solid #e5e7eb; padding:10px 14px; }
  .btn { display:inline-flex; align-items:center; gap:6px; padding:7px 14px; font-size:12px; font-weight:700; border-radius:4px; border:1px solid #d1d5db; background:#fff; color:#111; cursor:pointer; text-decoration:none; }
  .btn-primary { background:#111827; color:#fff; border-color:#111827; }
</style>
</head>
<body>

{{-- Toolbar (no-print) --}}
<div class="no-print toolbar">
  <a href="{{ ($mode ?? 'invoice') === 'quotation' ? route('sales.index', ['tab'=>'quotes']) : route('sales.index', ['tab'=>'register']) }}" class="btn">← Back</a>
  <div style="font-size:11px; color:#6b7280;">Exact replica • A4 • Watermark enabled • Print backgrounds ON</div>
  <button onclick="window.print()" class="btn btn-primary">Print / Save PDF</button>
</div>

{{-- Page 1 --}}
<div class="page">
  <div class="watermark"></div>
  <div class="content">
    <div class="header">
      <div class="header-left">
        <img src="{{ asset('images/jobarn-logo-clean.png') }}" alt="Jobarn General Trading" class="logo" onerror="this.style.display='none'">
        <div class="company">
          <strong>Jobarn General Trading Company</strong><br>
          P.O Box<br>
          Survey, Mliman City<br>
          Dar es Salaam<br>
          Phone :+255784837946<br>
          Tel :+255745912000<br>
          info@jobarn.co.tz<br>
          www.jobarn.co.tz<br>
          TIN : 163-331-055
        </div>
      </div>
      <div class="inv-title">
        <h1>{{ ($mode ?? 'invoice') === 'quotation' ? 'Quotation' : 'Invoice' }}</h1>
        <div class="ref">Ref No.: {{ $refNo ?? 'INV-0121' }}</div>
      </div>
    </div>

    <div class="rule"></div>

    <div class="bill-row">
      <div>
        <div class="bill-to-label">Bill To:</div>
        <div class="bill-to-name">{{ $billTo['name'] ?? 'THE NATIONAL ASSEMBLY FUND' }}</div>
        <div class="bill-to-lines">
          {{ $billTo['address1'] ?? 'P.O Box 941' }}<br>
          {{ $billTo['address2'] ?? 'Dodoma' }}<br>
          Client Code: {{ $billTo['client_code'] ?? '0114' }}
        </div>
      </div>
      <div class="meta-right">
        Currency: {{ $currency ?? 'TZS' }}<br>
        {{ ($mode ?? 'invoice') === 'quotation' ? 'Quotation' : 'Invoice' }} Date: {{ $invoiceDate ?? '16-Sep-2026' }}<br>
        Due Date: {{ $dueDate ?? '16-Sep-2026' }}<br>
        Payment Terms: {{ $paymentTerms ?? 'Due Upon Receipt' }}
      </div>
    </div>

    <table class="items">
      <thead>
        <tr>
          <th style="width:6%">#</th>
          <th style="width:18%">Item</th>
          <th style="width:30%">Description</th>
          <th class="qty" style="width:10%">Qty</th>
          <th class="rate" style="width:16%">Rate</th>
          <th class="amount" style="width:20%">Amount Net Tax</th>
        </tr>
      </thead>
      <tbody>
        @forelse($lines as $idx => $line)
        <tr>
          <td>{{ $idx+1 }}</td>
          <td>{{ $line['item'] ?? 'non stock items' }}</td>
          <td class="desc">{{ $line['description'] ?? '' }}</td>
          <td class="qty">{{ $line['qty'] ?? '' }}</td>
          <td class="rate">{{ isset($line['rate']) ? number_format((float)$line['rate'],2) : '' }}</td>
          <td class="amount">{{ isset($line['amount']) ? number_format((float)$line['amount'],2) : '' }}</td>
        </tr>
        @empty
        <tr><td colspan="6" style="text-align:center; color:#9aa0a6;">No items</td></tr>
        @endforelse
      </tbody>
    </table>

    <div class="totals-wrap">
      <table class="totals">
        <tr><td>Sub Total</td><td>{{ number_format((float)($subTotal ?? 0),2) }}</td></tr>
        <tr><td>Tax: VAT:18%</td><td>{{ number_format((float)($taxAmount ?? 0),2) }}</td></tr>
        <tr class="total"><td>Total Invoice</td><td>{{ number_format((float)($totalAmount ?? 0),2) }}</td></tr>
        <tr class="pay-status"><td>Pay Status</td><td><span class="pill">{{ strtoupper($payStatus ?? 'PENDING') }}</span></td></tr>
      </table>
    </div>

    <div class="sign-row">
      <div class="sign-left">
        {{-- Signature image if exists --}}
        @if(file_exists(public_path('images/jobarn-signature.png')))
          <img src="{{ asset('images/jobarn-signature.png') }}" alt="Signature" class="sign-img">
        @else
          <div style="height:46px; display:flex; align-items:end; font-family:'Brush Script MT', cursive; font-size:20px; color:#1e3a8a;">J. Nahar</div>
        @endif
        <div class="sign-line">Signature ............................</div>
        <div class="sign-note">Please quote above invoice/proforma number on all documents.</div>
      </div>
      <div class="sign-right" style="text-align:right;">
        <div class="stamp-label">Stamp</div>
        <div class="stamp-box" style="margin-left:auto; border:1px dashed #d1d5db; background:#fff;">
          {{-- Physical stamp only — left empty for manual stamping --}}
        </div>
      </div>
    </div>

    <div class="footer-contact">
      If you have any query about this document please contact us<br>
      <a href="tel:+255784837946">Phone: +255784837946</a> | <a href="mailto:info@jobarn.co.tz">Email: info@jobarn.co.tz</a>
    </div>
  </div>
</div>

{{-- Page 2 --}}
<div class="page page-break">
  <div class="watermark-2"></div>
  <div class="content">
    <div class="page2-grid">
      <div class="page2-col">
        <div style="font-size:10.5px; color:#111; margin-bottom:4px;">Payment note:</div>
        <div style="font-style:italic; font-weight:700; margin-bottom:6px;">Payments should be made through</div>
        <strong>Bank name :</strong> CRDB BANK<br>
        <strong>SWIFT Code:</strong> CORUTZTZ<br>
        <strong>Branch Code:</strong> 3383<br>
        <strong>Branch:</strong> Tower Branch<br>
        <strong>Account Name:</strong> Jobarn General Trading<br>
        <strong>Account Number:</strong> 0150931664300<br>
        Or<br>
        <strong>Bank name :</strong> ECO BANK<br>
        <strong>Account Name :</strong> JOBARN GENERAL TRADING.<br>
        <strong>Account Number :</strong> 7035000764<br>
        <strong>SWIFT code :</strong> ECOCTZTZ<br>
        <strong>Branch name :</strong> Kariakoo Branch
      </div>
      <div class="page2-col">
        <h3>Terms and Conditions</h3>
        <strong>Payment Terms:</strong> 100% payment upon confirmation of the order<br>
        <strong>Availability of Goods:</strong> Delivery 14-16 working days<br>
        <strong>Warranty:</strong> 1 years<br>
        <strong>Quote Validity:</strong> 14 days from the date of quotation
      </div>
    </div>
  </div>
</div>

</body>
</html>
