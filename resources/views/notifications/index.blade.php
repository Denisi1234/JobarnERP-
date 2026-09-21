@extends('reception.layout')

@section('content')
@php
    $role = auth()->user()->role ?? 'reception';
@endphp
<div class="max-w-3xl mx-auto">
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-900">Notifications</h1>
            <p class="text-sm text-slate-500 mt-1">Task assignments, overdue alerts, completions and verifications.</p>
        </div>
        <form action="{{ route('notifications.read-all') }}" method="POST">
            @csrf
            <button type="submit" class="rounded-none border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100">Mark all read</button>
        </form>
    </div>

    @if(session('success'))
    <x-success-popup :message="session('success')" />
    @endif

    <div class="bg-white border border-slate-300 divide-y divide-slate-200 overflow-hidden">
        @forelse($notifications as $n)
        @php
            $kind = $n->data['kind'] ?? 'assigned';
            $icon = match($kind) {
                'overdue' => 'warning',
                'completed' => 'task_alt',
                'verified' => 'verified',
                'submitted' => 'approval',
                'returned' => 'assignment_return',
                'declined' => 'do_not_disturb_on',
                default => 'assignment',
            };
            $taskRoute = match($role) {
                'sales' => 'sales.tasks.show',
                'manager', 'admin' => 'manager.tasks.show',
                default => 'reception.tasks.show',
            };
            $taskId = $n->data['task_id'] ?? null;
        @endphp
        <div class="flex items-start gap-4 px-5 py-4 {{ is_null($n->read_at) ? 'bg-slate-100' : 'hover:bg-slate-50' }}">
            <span class="w-8 h-8 bg-white border border-slate-300 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-[18px] text-slate-700">{{ $icon }}</span>
            </span>
            <div class="min-w-0 flex-1">
                <div class="text-sm font-medium text-slate-900">{{ $n->data['title'] ?? 'Notification' }}</div>
                <div class="text-xs text-slate-600 mt-0.5">{{ $n->data['message'] ?? '' }}</div>
                <div class="text-[11px] text-slate-400 mt-1">{{ $n->created_at->diffForHumans() }}@if(!empty($n->data['due'])) • due {{ $n->data['due'] }}@endif</div>
            </div>
            <div class="flex items-center gap-1 shrink-0">
                @if($taskId && \Illuminate\Support\Facades\Route::has($taskRoute))
                <form action="{{ route('notifications.read', $n->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="px-2.5 py-1.5 bg-slate-900 text-white text-xs font-medium hover:bg-black">Open</button>
                </form>
                @elseif(is_null($n->read_at))
                <form action="{{ route('notifications.read', $n->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="px-2.5 py-1.5 border border-slate-300 bg-white text-xs font-medium text-slate-700 hover:bg-slate-100">Mark read</button>
                </form>
                @endif
            </div>
        </div>
        @empty
        <div class="p-10 text-center">
            <div class="h-12 w-12 bg-slate-100 border border-slate-300 flex items-center justify-center mx-auto">
                <span class="material-symbols-outlined text-slate-400">notifications_none</span>
            </div>
            <p class="text-sm font-medium text-slate-900 mt-4">No notifications</p>
            <p class="text-xs text-slate-500 mt-1">New assignments and alerts will appear here.</p>
        </div>
        @endforelse
    </div>

    <div class="mt-6">{{ $notifications->links() }}</div>
</div>
@endsection
