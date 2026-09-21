@extends('reception.layout')

@section('content')
@php
  $status = strtolower($ticket['status'] ?? 'pending');
  $stNorm = match($status) {
    'pending','new' => 'new',
    'assigned' => 'accepted',
    default => $status,
  };

  $stLabel = match($stNorm) {
    'new' => 'New', 'accepted' => 'Accepted',
    'in_progress' => 'In Progress', 'waiting' => 'Waiting',
    'resolved' => 'Resolved', 'closed' => 'Closed',
    'returned' => 'Returned', 'rejected' => 'Rejected',
    default => ucfirst($stNorm),
  };

  $pri = strtolower($ticket['priority'] ?? 'normal');
  $priLabel = strtoupper($pri);

  $isNew      = in_array($stNorm, ['new','pending']);
  $isEditable = in_array($stNorm, ['accepted','in_progress','waiting']);
  $isTerminal = in_array($stNorm, ['resolved','closed','returned','rejected']);

  $qaChecklist = is_array($ticket['qa_checklist']) ? $ticket['qa_checklist'] : [];
  $deviceSpecs = is_array($ticket['device_specs']) ? $ticket['device_specs'] : [];
  $spareParts  = is_array($ticket['spare_parts']) ? $ticket['spare_parts'] : [];
@endphp

<div class="w-full bg-[#f4f4f4] -m-6 p-6 lg:p-8">
<div class="mx-auto max-w-[1480px]">
<div class="bg-white border border-[#e0e0e0] border-t-[3px] border-t-[#0f62fe] p-5 mb-6">
  {{-- ── Breadcrumb & Top Bar — IBM ── --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
    <div class="flex items-center gap-2 text-xs text-[#525252] font-medium">
      <a href="{{ route('it.index') }}" class="font-semibold text-[#0f62fe] hover:underline flex items-center gap-1">
        <span class="material-symbols-outlined text-[16px]">arrow_back</span>
        IT Service Desk
      </a>
      <span class="text-[#8d8d8d]">/</span>
      <span class="text-[#525252]">Work Order</span>
      <span class="text-[#8d8d8d]">/</span>
      <span class="font-mono font-bold text-[#161616]">{{ $ticket['ticket_code'] }}</span>
      <span class="ml-2 mono text-[11px] bg-[#edf5ff] border border-[#d0e2ff] text-[#0f62fe] px-2 py-0.5">Live</span>
    </div>

    {{-- Action Buttons --}}
    <div class="flex items-center gap-2 flex-wrap">
      @if($isNew)
        <form method="POST" action="{{ route('it.tickets.accept', $ticket['id']) }}" class="m-0">
          @csrf
          <button type="submit" class="border border-slate-900 bg-slate-900 px-3 py-1.5 text-xs font-bold text-white hover:bg-slate-800 flex items-center gap-1">
            <span class="material-symbols-outlined text-[16px]">how_to_reg</span>
            Accept Ticket
          </button>
        </form>
        <button onclick="openReturnModal()" class="border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50 flex items-center gap-1">
          <span class="material-symbols-outlined text-[16px]">assignment_return</span>
          Return to Reception
        </button>

      @elseif($stNorm === 'accepted')
        <form method="POST" action="{{ route('it.tickets.start', $ticket['id']) }}" class="m-0">
          @csrf
          <button type="submit" class="border border-slate-900 bg-slate-900 px-3 py-1.5 text-xs font-bold text-white hover:bg-slate-800 flex items-center gap-1">
            <span class="material-symbols-outlined text-[16px]">play_arrow</span>
            Start Diagnosis
          </button>
        </form>
        <button onclick="openReassignModal()" class="border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50 flex items-center gap-1">
          <span class="material-symbols-outlined text-[16px]">swap_horiz</span>
          Reassign Tech
        </button>
        <button onclick="openResolveModal()" class="border border-slate-900 bg-slate-900 px-3 py-1.5 text-xs font-bold text-white hover:bg-slate-800 flex items-center gap-1">
          <span class="material-symbols-outlined text-[16px]">task_alt</span>
          Mark Solved
        </button>
        <button onclick="openReturnModal()" class="border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50 flex items-center gap-1">
          <span class="material-symbols-outlined text-[16px]">undo</span>
          Return
        </button>

      @elseif($isEditable)
        <button onclick="openReassignModal()" class="border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50 flex items-center gap-1">
          <span class="material-symbols-outlined text-[16px]">swap_horiz</span>
          Reassign Tech
        </button>
        <button onclick="openResolveModal()" class="border border-slate-900 bg-slate-900 px-3 py-1.5 text-xs font-bold text-white hover:bg-slate-800 flex items-center gap-1">
          <span class="material-symbols-outlined text-[16px]">task_alt</span>
          Resolve &amp; Bill
        </button>
        <button onclick="openReturnModal()" class="border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50 flex items-center gap-1">
          <span class="material-symbols-outlined text-[16px]">undo</span>
          Return
        </button>

      @elseif($isTerminal && $ticket['price'])
        <div class="border border-slate-300 bg-slate-50 px-3 py-1 text-right">
          <span class="text-[10px] uppercase font-bold text-slate-500">Service Fee</span>
          <div class="font-mono text-xs font-bold text-slate-900">TZS {{ number_format($ticket['price']) }}</div>
        </div>
      @endif
    </div>
  </div>

  @if(session('success'))
  <x-success-popup :message="session('success')" />
  @endif

  @if($errors->any())
  <div class="mb-5 border border-slate-400 bg-white px-4 py-3 text-sm text-slate-900">
    <div class="font-bold mb-1">Errors encountered:</div>
    <ul class="list-disc pl-5 text-xs">
      @foreach($errors->all() as $err)
        <li>{{ $err }}</li>
      @endforeach
    </ul>
  </div>
  @endif

  {{-- ── Work Order Summary Banner ── --}}
  <div class="border border-slate-300 bg-white p-5 mb-6">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2 flex-wrap mb-1">
          <span class="font-mono text-sm font-bold text-slate-900">{{ $ticket['ticket_code'] }}</span>
          <span class="border border-slate-300 bg-slate-50 px-2 py-0.5 text-[10px] font-bold uppercase text-slate-700">
            {{ $stLabel }}
          </span>
          <span class="border border-slate-900 bg-slate-900 px-2 py-0.5 text-[10px] font-bold uppercase text-white">
            {{ $priLabel }}
          </span>
          <span class="border border-slate-300 bg-white px-2 py-0.5 text-[10px] font-medium text-slate-700">
            {{ $ticket['category'] }}
          </span>
        </div>

        <h1 class="text-xl font-bold tracking-tight text-slate-900">
          {{ $ticket['title'] }}
        </h1>

        <div class="flex items-center gap-3 text-xs text-slate-500 mt-2 flex-wrap font-mono">
          <span>Opened: {{ $ticket['created_at'] }}</span>
          <span>·</span>
          <span>Technician: <strong class="text-slate-900 font-sans">{{ $ticket['assigned_to'] ?: 'Unassigned' }}</strong></span>
          @if($ticket['accepted_at'])
            <span>·</span>
            <span>Accepted: {{ $ticket['accepted_at'] }}</span>
          @endif
          @if($ticket['resolved_at'])
            <span>·</span>
            <span class="font-bold text-slate-900 font-sans">✓ Resolved: {{ $ticket['resolved_at'] }}</span>
          @endif
        </div>
      </div>
    </div>
  </div>

  {{-- ── Main Two Column Grid ── --}}
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

    {{-- ══════ Left Column (8 cols) ══════ --}}
    <div class="lg:col-span-8 flex flex-col gap-6">

      {{-- Customer Information Panel --}}
      <div class="border border-slate-300 bg-white">
        <div class="px-4 py-3 border-b border-slate-300 bg-slate-50 flex items-center justify-between">
          <h2 class="text-xs font-bold uppercase tracking-wider text-slate-900">Customer &amp; Account Information</h2>
          <span class="border border-slate-300 bg-white px-2 py-0.5 text-[10px] font-bold uppercase text-slate-700">
            {{ ($ticket['customer_type'] ?? 'individual') === 'company' ? 'Corporate' : 'Individual' }}
          </span>
        </div>

        <div class="p-4 text-xs">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <span class="text-slate-500 block mb-0.5">Customer / Company</span>
              <span class="font-bold text-slate-900 text-sm">
                {{ ($ticket['customer_type'] ?? 'individual') === 'company' ? ($ticket['visitor_company'] ?: $ticket['visitor_name']) : $ticket['visitor_name'] }}
              </span>
              @if(!empty($ticket['contact_person']))
                <div class="text-slate-500 mt-0.5">Contact: {{ $ticket['contact_person'] }}</div>
              @endif
            </div>

            <div>
              <span class="text-slate-500 block mb-0.5">Phone Contact</span>
              <span class="font-mono font-bold text-slate-900">{{ $ticket['visitor_phone'] ?: '—' }}</span>
              <div class="text-slate-500 mt-0.5">Location: {{ $ticket['location'] ?: 'Showroom Desk' }}</div>
            </div>
          </div>
        </div>
      </div>

      {{-- Incoming Request & Handover --}}
      <div class="border border-slate-300 bg-white">
        <div class="px-4 py-3 border-b border-slate-300 bg-slate-50">
          <h2 class="text-xs font-bold uppercase tracking-wider text-slate-900">Incoming Issue &amp; Reception Handover</h2>
        </div>

        <div class="p-4 space-y-3 text-xs">
          <div>
            <span class="font-bold uppercase tracking-wider text-slate-500 text-[10px] block mb-1">Customer Statement</span>
            <div class="border-l-2 border-slate-900 pl-3 py-1 italic text-slate-800 bg-slate-50 text-sm">
              "{{ $ticket['customer_statement'] ?: $ticket['description'] ?: $ticket['title'] }}"
            </div>
          </div>

          @if($ticket['handover_note'])
          <div class="pt-2 border-t border-slate-200">
            <span class="font-bold uppercase tracking-wider text-slate-500 text-[10px] block mb-1">Reception Intake Note</span>
            <p class="text-slate-700">{{ $ticket['handover_note'] }}</p>
          </div>
          @endif
        </div>
      </div>

      {{-- ── WORKSPACE FORM (Editable Work Order) ── --}}
      @if($isEditable)
      <form method="POST" action="{{ route('it.tickets.update', $ticket['id']) }}" enctype="multipart/form-data" class="border border-slate-300 bg-white">
        @csrf

        {{-- Form Header --}}
        <div class="px-4 py-3 border-b border-slate-300 bg-slate-50 flex items-center justify-between">
          <h2 class="text-xs font-bold uppercase tracking-wider text-slate-900">Technician Workspace &amp; Diagnostic Tools</h2>
          <span class="text-[11px] font-mono font-bold text-slate-600">ID #{{ $ticket['id'] }}</span>
        </div>

        <div class="p-5 space-y-5">

          {{-- 1. Hardware Specs & Accessories Checklist --}}
          <div class="border border-slate-300 bg-slate-50 p-4">
            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-3">1. Device Hardware Specs &amp; Intake</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
              <div>
                <label class="block font-bold text-slate-700 mb-1">Brand &amp; Model</label>
                <input type="text" name="device_specs[model]" value="{{ $deviceSpecs['model'] ?? '' }}" placeholder="e.g. Dell Latitude 5420 / HP ProBook" class="w-full border border-slate-300 bg-white p-2 text-xs focus:border-slate-900 focus:outline-none">
              </div>
              <div>
                <label class="block font-bold text-slate-700 mb-1">Serial / Service Tag</label>
                <input type="text" name="device_specs[serial]" value="{{ $deviceSpecs['serial'] ?? '' }}" placeholder="e.g. 8XYZ123" class="w-full border border-slate-300 bg-white p-2 text-xs focus:border-slate-900 focus:outline-none">
              </div>
              <div>
                <label class="block font-bold text-slate-700 mb-1">Passcode / PIN</label>
                <input type="text" name="device_specs[passcode]" value="{{ $deviceSpecs['passcode'] ?? '' }}" placeholder="Device unlock code" class="w-full border border-slate-300 bg-white p-2 text-xs focus:border-slate-900 focus:outline-none">
              </div>
            </div>

            <div class="mt-3 pt-3 border-t border-slate-200">
              <span class="block text-[11px] font-bold text-slate-700 mb-1.5">Accessories Left With Unit:</span>
              <div class="flex items-center gap-4 flex-wrap text-xs text-slate-800">
                <label class="inline-flex items-center gap-1.5 cursor-pointer">
                  <input type="checkbox" name="device_specs[acc_charger]" value="1" @checked(!empty($deviceSpecs['acc_charger'])) class="rounded-none border-slate-300 text-slate-900 focus:ring-0">
                  <span>Power Adapter / Charger</span>
                </label>
                <label class="inline-flex items-center gap-1.5 cursor-pointer">
                  <input type="checkbox" name="device_specs[acc_bag]" value="1" @checked(!empty($deviceSpecs['acc_bag'])) class="rounded-none border-slate-300 text-slate-900 focus:ring-0">
                  <span>Laptop Bag / Sleeve</span>
                </label>
                <label class="inline-flex items-center gap-1.5 cursor-pointer">
                  <input type="checkbox" name="device_specs[acc_mouse]" value="1" @checked(!empty($deviceSpecs['acc_mouse'])) class="rounded-none border-slate-300 text-slate-900 focus:ring-0">
                  <span>Mouse / Dongle</span>
                </label>
              </div>
            </div>
          </div>

          {{-- 2. Pre/Post-Repair QA Diagnostic Checklist --}}
          <div class="border border-slate-300 bg-white p-4">
            <div class="flex items-center justify-between mb-3">
              <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">2. Diagnostic &amp; QA Checklist</h3>
              <span class="text-[11px] text-slate-500">Standard 6-point verification</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-xs text-slate-800">
              <label class="flex items-center gap-2 p-2 border border-slate-200 bg-slate-50 hover:bg-slate-100 cursor-pointer">
                <input type="checkbox" name="qa_checklist[power]" value="1" @checked(!empty($qaChecklist['power'])) class="rounded-none border-slate-400 text-slate-900 focus:ring-0">
                <span class="font-semibold">Power &amp; Battery Charging Test</span>
              </label>

              <label class="flex items-center gap-2 p-2 border border-slate-200 bg-slate-50 hover:bg-slate-100 cursor-pointer">
                <input type="checkbox" name="qa_checklist[display]" value="1" @checked(!empty($qaChecklist['display'])) class="rounded-none border-slate-400 text-slate-900 focus:ring-0">
                <span class="font-semibold">Display / Screen Backlight Clean</span>
              </label>

              <label class="flex items-center gap-2 p-2 border border-slate-200 bg-slate-50 hover:bg-slate-100 cursor-pointer">
                <input type="checkbox" name="qa_checklist[keyboard_ports]" value="1" @checked(!empty($qaChecklist['keyboard_ports'])) class="rounded-none border-slate-400 text-slate-900 focus:ring-0">
                <span class="font-semibold">Keyboard, Trackpad &amp; USB/HDMI Ports</span>
              </label>

              <label class="flex items-center gap-2 p-2 border border-slate-200 bg-slate-50 hover:bg-slate-100 cursor-pointer">
                <input type="checkbox" name="qa_checklist[wireless_audio]" value="1" @checked(!empty($qaChecklist['wireless_audio'])) class="rounded-none border-slate-400 text-slate-900 focus:ring-0">
                <span class="font-semibold">Wi-Fi, Bluetooth &amp; Audio / Mic</span>
              </label>

              <label class="flex items-center gap-2 p-2 border border-slate-200 bg-slate-50 hover:bg-slate-100 cursor-pointer">
                <input type="checkbox" name="qa_checklist[storage_ram]" value="1" @checked(!empty($qaChecklist['storage_ram'])) class="rounded-none border-slate-400 text-slate-900 focus:ring-0">
                <span class="font-semibold">Storage SMART Health &amp; RAM Stability</span>
              </label>

              <label class="flex items-center gap-2 p-2 border border-slate-200 bg-slate-50 hover:bg-slate-100 cursor-pointer">
                <input type="checkbox" name="qa_checklist[boot_os]" value="1" @checked(!empty($qaChecklist['boot_os'])) class="rounded-none border-slate-400 text-slate-900 focus:ring-0">
                <span class="font-semibold">Clean OS Boot &amp; Thermals Normal</span>
              </label>
            </div>
          </div>

          {{-- 3. Diagnostic Notes, Root Cause & Work Performed --}}
          <div class="space-y-3 text-xs">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Technician Inspection Notes</label>
              <textarea name="technician_notes" rows="2" placeholder="Initial inspection findings, customer-reported symptoms, bench observation..." class="w-full border border-slate-300 bg-white p-2.5 text-xs focus:border-slate-900 focus:outline-none">{{ $ticket['technician_notes'] }}</textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block font-bold text-slate-700 mb-1">Confirmed Diagnosis <span class="text-slate-400 font-normal">(Root Cause)</span></label>
                <textarea name="diagnosis" rows="2" placeholder="e.g. Bad RAM module, corrupt boot partition, dried thermal paste" class="w-full border border-slate-300 bg-white p-2.5 text-xs focus:border-slate-900 focus:outline-none">{{ $ticket['diagnosis'] }}</textarea>
              </div>

              <div>
                <label class="block font-bold text-slate-700 mb-1">Action Taken <span class="text-slate-400 font-normal">(Steps Performed)</span></label>
                <textarea name="action_taken" rows="2" placeholder="e.g. Reseated RAM, rebuilt MBR, cleaned heatsink and applied Arctic MX-4" class="w-full border border-slate-300 bg-white p-2.5 text-xs focus:border-slate-900 focus:outline-none">{{ $ticket['action_taken'] }}</textarea>
              </div>
            </div>
          </div>

          {{-- 4. Spare Parts Used (Showroom Inventory) --}}
          <div class="border border-slate-300 bg-slate-50 p-4">
            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-2">4. Showroom Spare Parts Used</h3>
            <p class="text-[11px] text-slate-500 mb-3">If replacement parts were installed from inventory, log them here.</p>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
              <div class="sm:col-span-2">
                <label class="block font-bold text-slate-700 mb-1">Inventory Product / Part</label>
                <select name="spare_parts[product_id]" class="w-full border border-slate-300 bg-white p-2 text-xs focus:border-slate-900 focus:outline-none">
                  <option value="">-- No spare parts used (Labor only) --</option>
                  @if(!empty($products))
                    @foreach($products as $prod)
                      <option value="{{ $prod->id }}" @selected(($spareParts['product_id'] ?? '') == $prod->id)>
                        {{ $prod->name }} — TZS {{ number_format($prod->price) }} (Stock: {{ $prod->stock_quantity }})
                      </option>
                    @endforeach
                  @endif
                </select>
              </div>
              <div>
                <label class="block font-bold text-slate-700 mb-1">Quantity</label>
                <input type="number" min="1" name="spare_parts[qty]" value="{{ $spareParts['qty'] ?? 1 }}" class="w-full border border-slate-300 bg-white p-2 text-xs focus:border-slate-900 focus:outline-none">
              </div>
            </div>
          </div>

          {{-- 5. Attachments / Photos --}}
          <div class="border border-slate-300 bg-white p-4">
            <div class="flex items-center justify-between mb-2">
              <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">5. Attachments &amp; Hardware Photos</h3>
              <div class="flex items-center gap-2">
                <label class="border border-slate-300 bg-slate-50 hover:bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700 cursor-pointer">
                  + Add Photos
                  <input type="file" name="photos[]" accept="image/*" multiple class="hidden" onchange="previewFiles(this,'preview-files')">
                </label>
              </div>
            </div>
            <div id="preview-files" class="text-xs font-mono text-slate-600 mb-2"></div>
            @if(!empty($ticket['attachments']))
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mt-2">
              @foreach($ticket['attachments'] as $att)
                @if(is_array($att))
                <a href="{{ $att['path'] ?? '#' }}" target="_blank" class="p-2 border border-slate-200 bg-slate-50 hover:bg-slate-100 text-xs truncate block font-mono">
                  📎 {{ $att['name'] ?? 'File' }}
                </a>
                @endif
              @endforeach
            </div>
            @endif
          </div>

        </div>

        {{-- Form Footer --}}
        <div class="px-5 py-3 border-t border-slate-300 bg-slate-50 flex items-center justify-between">
          <button type="button" onclick="openReturnModal()" class="text-xs font-bold text-slate-600 hover:text-slate-900">
            Return to Reception
          </button>
          <div class="flex items-center gap-2">
            <button type="submit" class="border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-800 hover:bg-slate-100">
              Save Workspace
            </button>
            <button type="button" onclick="openResolveModal()" class="border border-slate-900 bg-slate-900 px-4 py-2 text-xs font-bold text-white hover:bg-slate-800">
              Mark Resolved &amp; Bill
            </button>
          </div>
        </div>
      </form>
      @endif

      {{-- Resolution / Terminal Summary --}}
      @if($isTerminal)
      <div class="border border-slate-300 bg-white">
        <div class="px-4 py-3 border-b border-slate-300 bg-slate-50 flex items-center justify-between">
          <h2 class="text-xs font-bold uppercase tracking-wider text-slate-900">Resolution &amp; Service Summary</h2>
          <span class="border border-slate-900 bg-slate-900 text-white px-2 py-0.5 text-[10px] font-bold uppercase">
            {{ $stLabel }}
          </span>
        </div>
        <div class="p-4 space-y-3 text-xs">
          @if($ticket['resolution_summary'])
          <div>
            <span class="text-slate-500 font-bold block mb-0.5">Resolution Summary</span>
            <p class="text-slate-900 font-medium">{{ $ticket['resolution_summary'] }}</p>
          </div>
          @endif
          @if($ticket['work_completed'])
          <div>
            <span class="text-slate-500 font-bold block mb-0.5">Work Completed</span>
            <p class="text-slate-900">{{ $ticket['work_completed'] }}</p>
          </div>
          @endif
          @if($ticket['diagnosis'])
          <div>
            <span class="text-slate-500 font-bold block mb-0.5">Diagnosis</span>
            <p class="text-slate-900">{{ $ticket['diagnosis'] }}</p>
          </div>
          @endif
          @if($ticket['return_reason'])
          <div class="border-l-2 border-amber-600 pl-3 py-1 bg-amber-50">
            <span class="text-amber-900 font-bold block mb-0.5">Return Reason</span>
            <p class="text-amber-800">{{ $ticket['return_reason'] }}</p>
          </div>
          @endif
        </div>
      </div>
      @endif

    </div>

    {{-- ══════ Right Column (4 cols) ══════ --}}
    <div class="lg:col-span-4 flex flex-col gap-6">

      {{-- Work Order Metadata Card --}}
      <div class="border border-slate-300 bg-white">
        <div class="px-4 py-3 border-b border-slate-300 bg-slate-50">
          <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">Ticket Details</h3>
        </div>
        <div class="divide-y divide-slate-200 text-xs">
          <div class="p-3 flex justify-between">
            <span class="text-slate-500">Ticket Code</span>
            <span class="font-mono font-bold text-slate-900">{{ $ticket['ticket_code'] }}</span>
          </div>
          <div class="p-3 flex justify-between">
            <span class="text-slate-500">Category</span>
            <span class="font-medium text-slate-900">{{ $ticket['category'] }}</span>
          </div>
          <div class="p-3 flex justify-between">
            <span class="text-slate-500">Assigned Tech</span>
            <span class="font-bold text-slate-900">{{ $ticket['assigned_to'] ?: 'Unassigned' }}</span>
          </div>
          @if($ticket['reassigned_at'])
          <div class="p-3 flex justify-between font-mono text-[11px] bg-slate-50">
            <span class="text-slate-500 font-sans">Reassigned</span>
            <span class="text-slate-700">{{ $ticket['reassigned_at'] }}</span>
          </div>
          @endif
          <div class="p-3 flex justify-between font-mono text-[11px]">
            <span class="text-slate-500 font-sans">Created</span>
            <span class="text-slate-700">{{ $ticket['created_at'] }}</span>
          </div>
          @if($ticket['resolved_at'])
          <div class="p-3 flex justify-between font-mono text-[11px] bg-slate-50">
            <span class="text-slate-900 font-sans font-bold">Resolved</span>
            <span class="font-bold text-slate-900">{{ $ticket['resolved_at'] }}</span>
          </div>
          @endif
          @if($ticket['price'])
          <div class="p-3 flex justify-between bg-slate-100">
            <span class="font-bold text-slate-900">Service Charge</span>
            <span class="font-mono font-bold text-slate-900">TZS {{ number_format($ticket['price']) }}</span>
          </div>
          @endif
        </div>
      </div>

      {{-- Shift Handover Notes Card (if present) --}}
      @if(!empty($ticket['handover_notes']))
      <div class="border border-slate-300 bg-white">
        <div class="px-4 py-3 border-b border-slate-300 bg-slate-50">
          <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">Technician Handover Note</h3>
        </div>
        <div class="p-3 text-xs text-slate-700">
          <p>{{ $ticket['handover_notes'] }}</p>
        </div>
      </div>
      @endif

      {{-- Activity Timeline --}}
      <div class="border border-slate-300 bg-white">
        <div class="px-4 py-3 border-b border-slate-300 bg-slate-50 flex items-center justify-between">
          <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">Activity Timeline</h3>
          <span class="border border-slate-300 bg-white px-2 py-0.5 text-[10px] font-bold text-slate-700">
            {{ count($ticket['timelines'] ?? []) }}
          </span>
        </div>
        <div class="p-4 max-h-[480px] overflow-y-auto space-y-3 text-xs">
          @forelse($ticket['timelines'] as $tl)
          <div class="border-b border-slate-100 pb-3 last:border-0 last:pb-0">
            <div class="flex items-center justify-between gap-2">
              <span class="font-bold text-slate-900">{{ $tl['title'] }}</span>
              <span class="font-mono text-[10px] text-slate-400">{{ $tl['time'] ?: $tl['full_time'] }}</span>
            </div>
            <div class="text-[11px] text-slate-500 mt-0.5">{{ $tl['user_name'] }}</div>
            @if(!empty($tl['description']))
              <div class="mt-1 p-2 bg-slate-50 border border-slate-200 text-slate-700 text-[11px] leading-relaxed">
                {{ $tl['description'] }}
              </div>
            @endif
          </div>
          @empty
          <div class="py-6 text-center text-slate-400">
            No activity recorded yet.
          </div>
          @endforelse
        </div>
      </div>

    </div>
  </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════
     MODAL: REASSIGN TECHNICIAN & SHIFT HANDOVER
═══════════════════════════════════════════════════════════════ --}}
<div id="modal-reassign" class="fixed inset-0 z-50 bg-slate-900/50 hidden items-center justify-center p-4">
  <div class="bg-white border border-slate-300 rounded-none shadow-xl w-full max-w-md max-h-[90vh] flex flex-col overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-300 bg-slate-50 flex items-center justify-between">
      <div>
        <h3 class="text-sm font-bold text-slate-900">Reassign Technician &amp; Shift Handover</h3>
        <p class="text-xs text-slate-500 mt-0.5">{{ $ticket['ticket_code'] }} · {{ $ticket['title'] }}</p>
      </div>
      <button type="button" onclick="closeReassignModal()" class="border border-slate-300 bg-white h-7 w-7 flex items-center justify-center text-slate-600 hover:text-slate-900">
        <span class="material-symbols-outlined text-[16px]">close</span>
      </button>
    </div>

    <form method="POST" action="{{ route('it.tickets.reassign', $ticket['id']) }}" class="flex flex-col flex-1 overflow-hidden">
      @csrf
      <div class="p-6 space-y-4 text-xs">
        <div>
          <label class="block font-bold text-slate-700 mb-1">New Assignee *</label>
          <select required name="assigned_to" class="w-full border border-slate-300 bg-white p-2.5 text-xs focus:border-slate-900 focus:outline-none">
            @foreach($itOfficers as $officer)
              <option value="{{ $officer->id }}" @selected($ticket['assigned_user_id'] == $officer->id)>
                {{ $officer->name }} ({{ strtoupper($officer->role ?? 'IT') }})
              </option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="block font-bold text-slate-700 mb-1">Shift Handover / Escalation Note</label>
          <textarea name="handover_notes" rows="3" placeholder="Describe diagnostic state, parts waiting, or specific work completed before shift handover..." class="w-full border border-slate-300 bg-white p-2.5 text-xs focus:border-slate-900 focus:outline-none"></textarea>
        </div>
      </div>

      <div class="px-6 py-3 border-t border-slate-300 bg-slate-50 flex items-center justify-between">
        <button type="button" onclick="closeReassignModal()" class="border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-100">Cancel</button>
        <button type="submit" class="border border-slate-900 bg-slate-900 px-4 py-2 text-xs font-bold text-white hover:bg-slate-800">
          Save Handover
        </button>
      </div>
    </form>
  </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════
     MODAL: RESOLVE TICKET — Mature Legacy
═══════════════════════════════════════════════════════════════ --}}
<div id="modal-resolve" class="fixed inset-0 z-50 bg-slate-900/50 hidden items-center justify-center p-4">
  <div class="bg-white border border-slate-300 rounded-none shadow-xl w-full max-w-2xl max-h-[92vh] flex flex-col overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-300 bg-slate-50 flex items-center justify-between">
      <div>
        <h3 class="text-sm font-bold text-slate-900">Resolve &amp; Close — Showroom Handoff</h3>
        <p class="text-xs text-slate-500 mt-0.5">{{ $ticket['ticket_code'] }} · {{ $ticket['visitor_name'] }}</p>
      </div>
      <button type="button" onclick="closeResolveModal()" class="border border-slate-300 bg-white h-7 w-7 flex items-center justify-center text-slate-600 hover:text-slate-900">
        <span class="material-symbols-outlined text-[16px]">close</span>
      </button>
    </div>

    <form method="POST" action="{{ route('it.tickets.resolve', $ticket['id']) }}" class="flex-1 flex flex-col overflow-hidden">
      @csrf
      <div class="flex-1 overflow-y-auto px-6 py-5 space-y-4">
        <div class="border border-slate-300 bg-slate-50 p-3 text-xs">
          <span class="font-bold text-slate-900 block">Task solved — ready for Sales</span>
          <span class="text-slate-500">Resolving will close the work order and generate a pending service bill for collection at the sales desk.</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div class="sm:col-span-2">
            <label class="block text-xs font-bold text-slate-700 mb-1">Diagnosis</label>
            <input type="text" name="diagnosis" value="{{ $ticket['diagnosis'] }}" placeholder="Root cause" class="w-full border border-slate-300 bg-white p-2.5 text-xs focus:border-slate-900 focus:outline-none">
          </div>
          <div class="sm:col-span-2">
            <label class="block text-xs font-bold text-slate-700 mb-1">Action taken</label>
            <input type="text" name="action_taken" value="{{ $ticket['action_taken'] }}" placeholder="Steps performed" class="w-full border border-slate-300 bg-white p-2.5 text-xs focus:border-slate-900 focus:outline-none">
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Resolution summary *</label>
          <textarea name="resolution_summary" required rows="2" placeholder="Customer-facing summary..." class="w-full border border-slate-300 bg-white p-2.5 text-xs focus:border-slate-900 focus:outline-none">{{ $ticket['resolution_summary'] }}</textarea>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Work completed *</label>
          <textarea name="work_completed" required rows="2" placeholder="Technical detail for audit..." class="w-full border border-slate-300 bg-white p-2.5 text-xs focus:border-slate-900 focus:outline-none">{{ $ticket['work_completed'] ?: $ticket['action_taken'] }}</textarea>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Service charge (TZS)</label>
            <div class="relative">
              <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">TZS</span>
              <input type="number" name="price" min="0" step="500" value="{{ $ticket['price'] ?? 50000 }}" class="w-full border border-slate-300 bg-white pl-11 pr-3 py-2 text-sm font-bold text-slate-900 focus:border-slate-900 focus:outline-none">
            </div>
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Follow-up</label>
            <select name="customer_followup" class="w-full border border-slate-300 bg-white p-2.5 text-xs focus:border-slate-900 focus:outline-none">
              <option value="none" selected>No follow-up</option>
              <option value="monitor_pc">Customer monitors PC</option>
              <option value="contact_customer">Contact customer</option>
            </select>
          </div>
        </div>
      </div>

      <div class="shrink-0 bg-slate-50 border-t border-slate-300 px-6 py-3 flex items-center justify-between">
        <button type="button" onclick="closeResolveModal()" class="border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-100">Cancel</button>
        <button type="submit" class="border border-slate-900 bg-slate-900 px-4 py-2 text-xs font-bold text-white hover:bg-slate-800">
          Confirm Solved &amp; Bill
        </button>
      </div>
    </form>
  </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════
     MODAL: RETURN TICKET
═══════════════════════════════════════════════════════════════ --}}
<div id="modal-return" class="fixed inset-0 z-50 bg-slate-900/50 hidden items-center justify-center p-4">
  <div class="bg-white border border-slate-300 rounded-none shadow-xl w-full max-w-md max-h-[90vh] flex flex-col overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-300 bg-slate-50 flex items-center justify-between">
      <div>
        <h3 class="text-sm font-bold text-slate-900">Return to Reception</h3>
        <p class="text-xs text-slate-500 mt-0.5">{{ $ticket['ticket_code'] }} · {{ $ticket['visitor_name'] }}</p>
      </div>
      <button type="button" onclick="closeReturnModal()" class="border border-slate-300 bg-white h-7 w-7 flex items-center justify-center text-slate-600 hover:text-slate-900">
        <span class="material-symbols-outlined text-[16px]">close</span>
      </button>
    </div>

    <form method="POST" action="{{ route('it.tickets.return', $ticket['id']) }}" class="flex flex-col flex-1 overflow-hidden">
      @csrf
      <div class="p-6 space-y-4 text-xs">
        <div class="border border-slate-300 bg-slate-50 p-3 text-slate-700">
          This ticket will be returned to Reception. Reception will be notified to follow up or re-assign.
        </div>
        <div>
          <label class="block font-bold text-slate-700 mb-1">Reason for Return *</label>
          <textarea name="return_reason" required rows="3" placeholder="e.g. Hardware parts unavailable / customer cancelled service." class="w-full border border-slate-300 bg-white p-2.5 text-xs focus:border-slate-900 focus:outline-none"></textarea>
        </div>
      </div>
      <div class="px-6 py-3 border-t border-slate-300 bg-slate-50 flex items-center justify-between">
        <button type="button" onclick="closeReturnModal()" class="border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-100">Cancel</button>
        <button type="submit" class="border border-slate-900 bg-slate-900 px-4 py-2 text-xs font-bold text-white hover:bg-slate-800">
          Confirm Return
        </button>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
function openResolveModal() {
  const modal = document.getElementById('modal-resolve');
  modal.classList.remove('hidden');
  modal.classList.add('flex');
}

function closeResolveModal() {
  const modal = document.getElementById('modal-resolve');
  modal.classList.remove('flex');
  modal.classList.add('hidden');
}

function openReturnModal() {
  const modal = document.getElementById('modal-return');
  modal.classList.remove('hidden');
  modal.classList.add('flex');
}

function closeReturnModal() {
  const modal = document.getElementById('modal-return');
  modal.classList.remove('flex');
  modal.classList.add('hidden');
}

function openReassignModal() {
  const modal = document.getElementById('modal-reassign');
  modal.classList.remove('hidden');
  modal.classList.add('flex');
}

function closeReassignModal() {
  const modal = document.getElementById('modal-reassign');
  modal.classList.remove('flex');
  modal.classList.add('hidden');
}

// Close modals when clicking backdrop
['modal-resolve', 'modal-return', 'modal-reassign'].forEach(id => {
  const modal = document.getElementById(id);
  if (modal) {
    modal.addEventListener('click', function(e) {
      if (e.target === this) {
        this.classList.remove('flex');
        this.classList.add('hidden');
      }
    });
  }
});

function previewFiles(input, containerId) {
  const el = document.getElementById(containerId);
  el.textContent = input.files.length ? Array.from(input.files).map(f => f.name).join(', ') : '';
}
</script>
@endpush
@endsection
