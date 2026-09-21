@extends('reception.layout')

@section('content')
<div class="w-full max-w-7xl mx-auto" x-data="{ createOpen: false, decisionId: null, decision: 'approved', decisionTitle: '', decisionOpen: false }">
    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-8">
        <div>
            <div class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Manager Portal / Controls</div>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Approval Center</h1>
            <p class="mt-1 text-sm text-slate-500">Review commercial, inventory, finance, and exception decisions in one queue.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('manager.index') }}" class="border border-slate-300 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Dashboard</a>
            <button @click="createOpen = !createOpen" class="bg-slate-900 px-4 py-2.5 text-xs font-semibold text-white hover:bg-slate-700">Register Request</button>
        </div>
    </div>

    @if(session('success'))
    <x-success-popup :message="session('success')" />
    @endif
    @if($errors->any())<div class="mb-5 border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $errors->first() }}</div>@endif

    <div x-show="createOpen" x-cloak class="mb-6 border border-slate-300 bg-white p-6">
        <h2 class="text-sm font-bold text-slate-900">Register an approval request</h2>
        <p class="mt-1 text-xs text-slate-500">Use this for exceptions requiring a recorded manager decision.</p>
        <form action="{{ route('manager.approvals.store') }}" method="POST" class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
            @csrf
            <div><label class="mb-1 block text-xs font-semibold text-slate-700">Control type *</label><select name="request_type" required class="w-full border border-slate-300 px-3 py-2.5 text-sm"><option value="quotation">Quotation approval</option><option value="discount">Discount exception</option><option value="credit_limit">Customer credit limit</option><option value="purchase">Purchase request</option><option value="stock_adjustment">Stock adjustment</option><option value="write_off">Inventory write-off</option><option value="invoice_cancellation">Invoice cancellation</option><option value="refund">Refund</option><option value="debit_note">Debit / credit note</option><option value="budget_exception">Budget exception</option></select></div>
            <div><label class="mb-1 block text-xs font-semibold text-slate-700">Department</label><select name="department_id" class="w-full border border-slate-300 px-3 py-2.5 text-sm"><option value="">Not specified</option>@foreach($departments as $department)<option value="{{ $department->id }}">{{ $department->name }}</option>@endforeach</select></div>
            <div class="md:col-span-2"><label class="mb-1 block text-xs font-semibold text-slate-700">Decision title *</label><input required name="title" value="{{ old('title') }}" placeholder="Approve a 10% discount for ABC Ltd" class="w-full border border-slate-300 px-3 py-2.5 text-sm"></div>
            <div><label class="mb-1 block text-xs font-semibold text-slate-700">Reference</label><input name="reference" value="{{ old('reference') }}" placeholder="QT-2026-000123" class="w-full border border-slate-300 px-3 py-2.5 text-sm"></div>
            <div><label class="mb-1 block text-xs font-semibold text-slate-700">Amount (TZS)</label><input type="number" min="0" step="0.01" name="amount" value="{{ old('amount') }}" class="w-full border border-slate-300 px-3 py-2.5 text-sm"></div>
            <div class="md:col-span-2"><label class="mb-1 block text-xs font-semibold text-slate-700">Business justification *</label><textarea required name="description" rows="3" class="w-full border border-slate-300 px-3 py-2.5 text-sm">{{ old('description') }}</textarea></div>
            <div class="md:col-span-2 flex justify-end gap-2"><button type="button" @click="createOpen=false" class="border border-slate-300 px-4 py-2.5 text-xs font-semibold text-slate-700">Cancel</button><button class="bg-slate-900 px-4 py-2.5 text-xs font-semibold text-white">Submit for approval</button></div>
        </form>
    </div>

    <div class="mb-5 flex flex-wrap gap-2 border-b border-slate-200">
        @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'returned' => 'Returned', 'rejected' => 'Rejected'] as $key => $label)
        <a href="{{ route('manager.approvals', ['status' => $key]) }}" class="border-b-2 px-3 py-3 text-xs font-semibold {{ $status === $key ? 'border-slate-900 text-slate-900' : 'border-transparent text-slate-500 hover:text-slate-900' }}">{{ $label }} <span class="ml-1 font-mono">{{ $counts[$key] ?? 0 }}</span></a>
        @endforeach
    </div>

    <div class="overflow-hidden border border-slate-300 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[860px] text-left text-xs">
                <thead class="border-b border-slate-300 bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Request</th><th class="px-5 py-3">Department</th><th class="px-5 py-3">Amount</th><th class="px-5 py-3">Requested by</th><th class="px-5 py-3">Submitted</th><th class="px-5 py-3">Status</th><th class="px-5 py-3 text-right">Decision</th></tr></thead>
                <tbody class="divide-y divide-slate-200">
                @forelse($requests as $item)
                    <tr class="align-top hover:bg-slate-50">
                        <td class="px-5 py-4"><div class="font-bold text-slate-900">{{ $item->title }}</div><div class="mt-1 uppercase tracking-wide text-[10px] text-slate-500">{{ str_replace('_', ' ', $item->request_type) }}@if($item->reference) · {{ $item->reference }}@endif</div><div class="mt-2 max-w-md text-slate-600">{{ $item->description }}</div>@if($item->actions->count())<div class="mt-2 border-l-2 border-slate-300 pl-2 text-[11px] text-slate-500">{{ strtoupper($item->actions->first()->action) }} by {{ $item->actions->first()->actor?->name ?? 'System' }}@if($item->actions->first()->comment): {{ $item->actions->first()->comment }}@endif</div>@endif</td>
                        <td class="px-5 py-4 text-slate-600">{{ $item->department?->name ?? '—' }}</td><td class="px-5 py-4 font-mono font-semibold text-slate-900">{{ $item->amount !== null ? 'TZS '.number_format($item->amount, 2) : '—' }}</td><td class="px-5 py-4 text-slate-600">{{ $item->requester?->name ?? 'System' }}</td><td class="px-5 py-4 font-mono text-slate-500">{{ $item->requested_at?->format('d M Y H:i') }}</td><td class="px-5 py-4"><span class="border px-2 py-1 text-[10px] font-bold uppercase {{ $item->status === 'approved' ? 'border-emerald-300 bg-emerald-50 text-emerald-800' : ($item->status === 'pending' ? 'border-amber-300 bg-amber-50 text-amber-800' : 'border-slate-300 bg-slate-100 text-slate-700') }}">{{ $item->status }}</span></td>
                        <td class="px-5 py-4 text-right">@if($item->status === 'pending')<div class="flex justify-end gap-2"><button @click="decisionId={{ $item->id }}; decision='approved'; decisionTitle=@js($item->title); decisionOpen=true" class="border border-slate-900 bg-slate-900 px-3 py-2 text-[11px] font-bold text-white">Approve</button><button @click="decisionId={{ $item->id }}; decision='returned'; decisionTitle=@js($item->title); decisionOpen=true" class="border border-slate-300 bg-white px-3 py-2 text-[11px] font-bold text-slate-700">Return</button><button @click="decisionId={{ $item->id }}; decision='rejected'; decisionTitle=@js($item->title); decisionOpen=true" class="border border-rose-300 bg-white px-3 py-2 text-[11px] font-bold text-rose-700">Reject</button></div>@else<span class="text-[11px] text-slate-400">Decided {{ $item->decided_at?->format('d M Y') }}</span>@endif</td>
                    </tr>
                @empty<tr><td colspan="7" class="px-5 py-12 text-center text-sm text-slate-500">No {{ $status }} approval requests.</td></tr>@endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200 px-5 py-3">{{ $requests->links() }}</div>
    </div>

    <div x-show="decisionOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4">
        <div @click.outside="decisionOpen=false" class="w-full max-w-lg border border-slate-400 bg-white p-6">
            <div class="text-[11px] font-bold uppercase tracking-widest text-slate-500" x-text="decision + ' decision'"></div><h2 class="mt-1 text-lg font-bold text-slate-900" x-text="decisionTitle"></h2>
            <form :action="'/manager/approvals/' + decisionId + '/decision'" method="POST" class="mt-5">@csrf<input type="hidden" name="action" :value="decision"><label class="mb-1 block text-xs font-semibold text-slate-700">Decision comment <span x-show="decision !== 'approved'">*</span></label><textarea name="comment" :required="decision !== 'approved'" rows="4" placeholder="Record the decision, condition, or reason." class="w-full border border-slate-300 px-3 py-2.5 text-sm"></textarea><div class="mt-4 flex justify-end gap-2"><button type="button" @click="decisionOpen=false" class="border border-slate-300 px-4 py-2.5 text-xs font-semibold text-slate-700">Cancel</button><button class="bg-slate-900 px-4 py-2.5 text-xs font-semibold text-white" x-text="decision === 'approved' ? 'Approve request' : (decision === 'returned' ? 'Return request' : 'Reject request')"></button></div></form>
        </div>
    </div>
</div>
@endsection
