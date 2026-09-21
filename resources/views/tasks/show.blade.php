@extends('reception.layout')

@section('content')
@php
    $prefix = request()->routeIs('sales.*') ? 'sales' : (request()->routeIs('manager.*') ? 'manager' : 'reception');
    $statusRoute = $prefix === 'manager' ? 'reception.tasks.status' : $prefix . '.tasks.status';
    $verifyRoute = $prefix === 'manager' ? 'reception.tasks.verify' : $prefix . '.tasks.verify';
    $backRoute = $prefix === 'manager' ? 'manager.tasks' : $prefix . '.tasks' . ($prefix === 'sales' ? '.index' : '');
    $user = auth()->user();
    $isManager = $user && ($user->is_admin || ($user->role ?? '') === 'manager' || $task->created_by === $user->id);
    $canVerify = $isManager;
    $employeeStatuses = ['assigned' => 'Assigned', 'accepted' => 'Accepted', 'in_progress' => 'In Progress', 'waiting' => 'Waiting', 'blocked' => 'Blocked', 'submitted' => 'Submitted'];
    $managerStatuses = ['draft' => 'Draft', 'assigned' => 'Assigned', 'accepted' => 'Accepted', 'in_progress' => 'In Progress', 'waiting' => 'Waiting', 'blocked' => 'Blocked', 'submitted' => 'Submitted', 'completed' => 'Completed', 'under_review' => 'Under Review', 'returned' => 'Returned', 'verified' => 'Verified', 'closed' => 'Closed', 'cancelled' => 'Cancelled'];
    $statusOptions = $isManager ? $managerStatuses : $employeeStatuses;
    $blockers = $task->incompleteDependencies();
    $checklistDone = $task->checklists->where('is_done', true)->count();
    $checklistTotal = $task->checklists->count();
@endphp
<div class="w-full" x-data="{ tab: '{{ str_contains(session('success') ?? '', 'document') || str_contains(session('success') ?? '', 'uploaded') ? 'files' : 'overview' }}', updStatus: '{{ $task->status }}', showDecline: false, showReturn: false, showApproveModal: false, pendingApproveFormId: null, openApproveModal(formId){ this.pendingApproveFormId=formId; this.showApproveModal=true; this.$nextTick(()=>{ this.$refs.cancelApproveBtn && this.$refs.cancelApproveBtn.focus(); }); }, closeApproveModal(){ this.showApproveModal=false; this.pendingApproveFormId=null; }, confirmApprove(){ if(this.pendingApproveFormId){ let f=document.getElementById(this.pendingApproveFormId); if(f) f.submit(); } }, showChecklistDeleteModal:false, pendingChecklistId:null, pendingChecklistTitle:'', openChecklistDelete(id,title){ this.pendingChecklistId=id; this.pendingChecklistTitle=title; this.showChecklistDeleteModal=true; this.$nextTick(()=>{ this.$refs.cancelChecklistBtn && this.$refs.cancelChecklistBtn.focus(); }); }, closeChecklistDelete(){ this.showChecklistDeleteModal=false; this.pendingChecklistId=null; }, confirmChecklistDelete(){ if(this.pendingChecklistId){ let f=document.getElementById('delete-checklist-form-'+this.pendingChecklistId); if(f) f.submit(); } }, showDocDeleteModal:false, pendingDocIdx:null, pendingDocName:'', openDocDelete(idx,name){ this.pendingDocIdx=idx; this.pendingDocName=name; this.showDocDeleteModal=true; this.$nextTick(()=>{ this.$refs.cancelDocBtn && this.$refs.cancelDocBtn.focus(); }); }, closeDocDelete(){ this.showDocDeleteModal=false; this.pendingDocIdx=null; }, confirmDocDelete(){ if(this.pendingDocIdx!==null){ let f=document.getElementById('delete-doc-form-'+this.pendingDocIdx); if(f) f.submit(); } }, showDepDeleteModal:false, pendingDepId:null, pendingDepTitle:'', openDepDelete(id,title){ this.pendingDepId=id; this.pendingDepTitle=title; this.showDepDeleteModal=true; this.$nextTick(()=>{ this.$refs.cancelDepBtn && this.$refs.cancelDepBtn.focus(); }); }, closeDepDelete(){ this.showDepDeleteModal=false; this.pendingDepId=null; }, confirmDepDelete(){ if(this.pendingDepId){ let f=document.getElementById('delete-dep-form-'+this.pendingDepId); if(f) f.submit(); } } }" x-init="if (window.location.hash === '#files') tab='files'">
    <a href="{{ route($backRoute) }}" class="inline-flex items-center gap-1 text-xs font-medium text-slate-500 hover:text-slate-900 mb-6">
        <span class="material-symbols-outlined text-[16px]">arrow_back</span> Back to Tasks
    </a>

    @if(session('success'))
    <x-success-popup :message="session('success')" />
    @endif
    @if($errors->any())
    <div class="mb-6 rounded-none border border-rose-200 bg-rose-50 px-5 py-3 text-sm text-rose-700">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
    @endif

    {{-- Header --}}
    <div class="bg-white rounded-none border border-slate-200 p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-lg font-semibold tracking-tight text-slate-900 uppercase">{{ $task->title }}</h1>
                <div class="flex flex-wrap items-center gap-1.5 mt-2">
                    <span class="inline-flex items-center gap-1 rounded-none border px-2 py-0.5 text-[11px] font-medium {{ $task->priority==='urgent'||$task->priority==='critical' ? 'bg-rose-50 border-rose-200 text-rose-700' : 'bg-slate-50 border-slate-200 text-slate-600' }}">{{ ucfirst($task->priority ?? 'medium') }} priority</span>
                    <span class="inline-flex rounded-none border border-slate-200 bg-white px-2 py-0.5 text-[11px] font-medium text-slate-700">{{ strtoupper(str_replace('_',' ',$task->status)) }}</span>
                    @if($task->task_type)<span class="inline-flex rounded-none border border-slate-200 bg-white px-2 py-0.5 text-[11px] font-medium text-slate-600">{{ $task->task_type }}</span>@endif
                </div>
            </div>
        </div>
        <div class="mt-2 text-xs text-slate-500">Due {{ $task->due_at ? $task->due_at->format('d M Y') . ($task->all_day ? ' · all day' : ($task->due_time ? ' · ' . $task->due_time : '')) : '—' }}</div>
        <dl class="mt-6 grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
            <div><dt class="text-slate-400 font-medium">Owner</dt><dd class="font-medium text-slate-900 mt-0.5">{{ $task->assignee?->name ?? '—' }}</dd></div>
            <div><dt class="text-slate-400 font-medium">Assigned by</dt><dd class="font-medium text-slate-900 mt-0.5">{{ $task->creator?->name ?? '—' }} · {{ $task->created_at->format('d M Y') }}</dd></div>
            <div><dt class="text-slate-400 font-medium">Department</dt><dd class="font-medium text-slate-900 mt-0.5">{{ $task->department?->name ?? $task->department ?? '—' }}</dd></div>
            <div><dt class="text-slate-400 font-medium">Progress</dt><dd class="font-mono text-slate-900 mt-0.5">{{ $task->progress ?? 0 }}%</dd></div>
            @if($task->accepted_at)<div><dt class="text-slate-400 font-medium">Accepted</dt><dd class="font-medium text-slate-900 mt-0.5">{{ $task->accepted_at->format('d M, H:i') }}</dd></div>@endif
            @if($task->started_at)<div><dt class="text-slate-400 font-medium">Started</dt><dd class="font-medium text-slate-900 mt-0.5">{{ $task->started_at->format('d M, H:i') }}</dd></div>@endif
        </dl>
        @if($task->collaborators && $task->collaborators->count())
        <div class="mt-3 flex items-center gap-1.5 text-xs">
            <span class="text-slate-400 font-medium">Collaborators:</span>
            @foreach($task->collaborators as $c)
                <span class="inline-flex rounded-none border border-slate-200 bg-slate-50 px-2 py-0.5 text-[11px] font-medium text-slate-700">{{ $c->name }}</span>
            @endforeach
        </div>
        @endif
        @if(in_array($task->status, ['waiting','blocked']))
        <div class="mt-4 border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">
            <span class="font-semibold">{{ ucfirst($task->status) }}</span>
            @if($task->waiting_reason)<span> — {{ str_replace('_',' ',$task->waiting_reason) }}</span>@endif
            @if($task->expected_resolution_at)<span> · expected {{ $task->expected_resolution_at->format('d M Y') }}</span>@endif
        </div>
        @endif
        @if($blockers->count())
        <div class="mt-4 border border-rose-200 bg-rose-50 px-4 py-3 text-xs text-rose-700">
            <span class="font-semibold">Blocked by unfinished dependencies:</span> {{ $blockers->map(fn($d) => '#'.$d->id.' '.$d->title)->join(', ') }}
        </div>
        @endif
    </div>

    {{-- Receiver action bar — IBM mature: clear CTA per status --}}
    @if(in_array($task->status, ['assigned', 'pending', 'draft']))
    <div class="mt-6 bg-white border border-[#e0e0e0] p-0" style="border-left:3px solid #0f62fe">
        <div class="bg-[#f4f4f4] border-b border-[#e0e0e0] px-5 py-3 flex items-center gap-2">
            <span class="flex h-6 w-6 items-center justify-center bg-[#0f62fe] text-white"><span class="material-symbols-outlined text-[14px]">assignment_ind</span></span>
            <p class="text-sm font-semibold text-[#161616]">New task for you</p>
            <span class="ml-auto text-[11px] text-[#525252] mono">From {{ $task->creator?->name ?? '—' }} · due {{ $task->due_at ? $task->due_at->format('d M Y') : '—' }}</span>
        </div>
        <div class="p-5">
            <p class="text-sm text-[#525252]"><span class="font-semibold text-[#161616]">{{ $task->creator?->name ?? 'Assigner' }}</span> assigned <span class="font-semibold text-[#161616]">{{ $task->title }}</span> to you — please respond.</p>
            <div class="mt-4 flex flex-wrap gap-2">
                <form action="{{ route($statusRoute, $task->id) }}" method="POST">
                    @csrf
                    <input type="hidden" name="status" value="accepted">
                    <button type="submit" class="bg-[#0f62fe] hover:bg-[#0353e9] text-white px-5 py-2.5 text-sm font-semibold border border-[#0f62fe]">Accept — Start Work</button>
                </form>
                <button @click="showDecline = !showDecline" class="border border-[#8d8d8d] bg-white hover:bg-[#f4f4f4] text-[#161616] px-5 py-2.5 text-sm font-semibold">Decline / Ask Question</button>
            </div>
            <form action="{{ route($statusRoute, $task->id) }}" method="POST" x-show="showDecline" x-cloak class="mt-3 flex gap-2">
                @csrf
                <input type="hidden" name="status" value="declined">
                <input type="text" name="comment" required placeholder="What needs clarification? (required)" class="flex-1 border border-[#8d8d8d] px-3 py-2.5 text-sm focus:border-[#0f62fe] focus:outline-none" style="font-family:'IBM Plex Sans',sans-serif">
                <button type="submit" class="border border-[#da1e28] bg-[#fff1f1] text-[#da1e28] px-4 py-2.5 text-sm font-semibold hover:bg-[#ffd7d9]">Send to Assigner</button>
            </form>
        </div>
    </div>
    @elseif($task->status === 'accepted')
    <div class="mt-6 bg-white border border-[#e0e0e0] p-0" style="border-left:3px solid #0f62fe">
        <div class="bg-[#f4f4f4] border-b border-[#e0e0e0] px-5 py-3 flex items-center gap-2">
            <span class="flex h-6 w-6 items-center justify-center bg-[#0f62fe] text-white"><span class="material-symbols-outlined text-[14px]">play_arrow</span></span>
            <p class="text-sm font-semibold text-[#161616]">Accepted — ready to start</p>
            <span class="ml-auto mono text-xs text-[#525252]">{{ $task->accepted_at ? 'Accepted '.$task->accepted_at->format('d M, H:i') : '' }}</span>
        </div>
        <div class="p-5">
            <form action="{{ route($statusRoute, $task->id) }}" method="POST">
                @csrf
                <input type="hidden" name="status" value="in_progress">
                <button type="submit" class="bg-[#161616] hover:bg-black text-white px-5 py-2.5 text-sm font-semibold">Start Task Now</button>
            </form>
        </div>
    </div>
    @elseif($task->status === 'in_progress')
    <div class="mt-6 bg-white border border-[#e0e0e0] p-0">
        <div class="bg-[#f4f4f4] border-b border-[#e0e0e0] px-5 py-3 flex items-center gap-2">
            <span class="flex h-6 w-6 items-center justify-center bg-[#0e6027] text-white"><span class="material-symbols-outlined text-[14px]">engineering</span></span>
            <p class="text-sm font-semibold text-[#161616]">In progress — working</p>
            <span class="mono text-xs text-[#525252]">since {{ $task->started_at ? $task->started_at->format('d M, H:i') : 'now' }}</span>
        </div>
        <div class="p-5">
            <p class="text-sm text-[#525252]">Keep the assigner updated — use the update box below.</p>
            <a href="#update-task" @click="updStatus='waiting'; tab='overview'; $nextTick(() => document.getElementById('update-task')?.scrollIntoView({behavior:'smooth'}))" class="mt-3 inline-flex border border-[#8d8d8d] bg-white hover:bg-[#f4f4f4] px-4 py-2 text-sm font-semibold text-[#161616]">Put on Hold / Blocked → Respond</a>
        </div>
    </div>
    @endif

    {{-- Manager review — IBM mature: assigner response to receiver submission --}}
    @if($isManager && in_array($task->status, ['submitted', 'under_review', 'completed']))
    <div class="mt-6 bg-white border border-[#e0e0e0] p-0" style="border-left:3px solid #f1c21b">
        <div class="bg-[#fff8e1] border-b border-[#e0e0e0] px-5 py-3 flex items-center gap-2">
            <span class="flex h-6 w-6 items-center justify-center bg-[#f1c21b] text-[#4a3800]"><span class="material-symbols-outlined text-[14px]">rate_review</span></span>
            <h2 class="text-sm font-semibold text-[#4a3800]">Pending Review — your decision as assigner</h2>
            <span class="ml-auto text-xs text-[#8d8d8d] mono">Submitted {{ $task->submitted_at?->format('d M H:i') ?? $task->updated_at->format('d M H:i') }}</span>
        </div>
        <div class="p-5">
            <p class="text-sm text-[#525252]"><span class="font-semibold text-[#161616]">{{ $task->assignee?->name ?? 'Receiver' }}</span> says task is finished — review and respond.</p>
            <div class="mt-3 bg-[#f4f4f4] border border-[#e0e0e0] p-4 space-y-2 text-sm">
                @if($task->completion_what_was_done)<p><span class="font-semibold text-[#161616]">What was done:</span> <span class="text-[#525252]">{{ $task->completion_what_was_done }}</span></p>@endif
                @if($task->result)<p><span class="font-semibold text-[#161616]">Result:</span> <span class="text-[#525252]">{{ $task->result }}</span></p>@endif
                @if($task->completion_issues)<p><span class="font-semibold text-[#161616]">Issues:</span> <span class="text-[#525252]">{{ $task->completion_issues }}</span></p>@endif
                @if($task->next_action)<p><span class="font-semibold text-[#161616]">Next:</span> <span class="text-[#525252]">{{ $task->next_action }}</span></p>@endif
                @if(!empty($task->completion_attachments) && is_array($task->completion_attachments))
                    <div class="flex flex-wrap gap-1.5 pt-2">
                        @foreach($task->completion_attachments as $att)
                            <a href="{{ $att['path'] ?? '#' }}" target="_blank" class="inline-flex items-center gap-1 border border-[#e0e0e0] bg-white px-2 py-1 text-xs hover:bg-[#f4f4f4]">📎 {{ $att['name'] ?? 'file' }}</a>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
                <form id="approve-form-top" action="{{ route($verifyRoute, $task->id) }}" method="POST">
                    @csrf
                    <button type="button" @click="openApproveModal('approve-form-top')" class="bg-[#0e6027] hover:bg-[#044a18] text-white px-5 py-2.5 text-sm font-semibold border border-[#0e6027]">Approve & Close — Notify Receiver</button>
                </form>
                <button @click="showReturn = !showReturn" class="border border-[#8d8d8d] bg-white hover:bg-[#f4f4f4] text-[#161616] px-5 py-2.5 text-sm font-semibold">Return — Request Rework</button>
            </div>
            <form action="{{ route($statusRoute, $task->id) }}" method="POST" x-show="showReturn" x-cloak class="mt-3 flex gap-2">
                @csrf
                <input type="hidden" name="status" value="returned">
                <input type="text" name="comment" required placeholder="What should receiver do next? (required, will notify him)" class="flex-1 border border-[#8d8d8d] px-3 py-2.5 text-sm focus:border-[#0f62fe] focus:outline-none" style="font-family:'IBM Plex Sans',sans-serif">
                <button type="submit" class="bg-[#fff8e1] border border-[#f1c21b] text-[#4a3800] px-4 py-2.5 text-sm font-semibold hover:bg-[#f1c21b]">Send to Receiver</button>
            </form>
        </div>
    </div>
    @endif

    {{-- Tabs --}}
    <div class="flex items-center gap-1 text-xs font-medium border-b border-slate-200 mt-6 mb-4 overflow-x-auto">
        <template x-for="t in [['overview','Overview'],['activity','Activity'],['checklist','Checklist'],['files','Files'],['related','Related'],['history','History']]" :key="t[0]">
            <button @click="tab = t[0]" :class="tab===t[0] ? 'text-slate-900 border-slate-900' : 'text-slate-500 border-transparent hover:text-slate-900'" class="px-3 py-2 border-b-2 -mb-px whitespace-nowrap" x-text="t[1]"></button>
        </template>
    </div>

    {{-- OVERVIEW --}}
    <div x-show="tab==='overview'" class="space-y-4">
        <div class="grid sm:grid-cols-2 gap-4">
            <div class="bg-white rounded-none border border-slate-200 p-6">
                <h2 class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Instructions</h2>
                <p class="text-sm text-slate-800 mt-2 whitespace-pre-line">{{ $task->instructions ?? $task->description ?? 'No instructions provided.' }}</p>
            </div>
            <div class="bg-white rounded-none border border-slate-200 p-6">
                <h2 class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Expected outcome</h2>
                <p class="text-sm text-slate-800 mt-2 whitespace-pre-line">{{ $task->expected_outcome ?? 'No expected outcome recorded.' }}</p>
            </div>
        </div>
        <div class="bg-white rounded-none border border-slate-200 p-6">
            <div class="flex items-center justify-between text-xs"><span class="font-medium text-slate-600">Progress</span><span class="font-mono text-slate-700">{{ $task->progress ?? 0 }}%</span></div>
            <div class="mt-1 h-2 rounded-none bg-slate-100 overflow-hidden"><div class="h-2 rounded-none bg-emerald-600" style="width: {{ $task->progress ?? 0 }}%"></div></div>
            <p class="text-[11px] text-slate-400 mt-1">{{ $checklistDone }} / {{ $checklistTotal }} checklist items completed{{ $task->subtasks->count() ? ' • ' . $task->subtasks->count() . ' subtasks' : '' }}</p>
        </div>
        {{-- Subtasks --}}
        <div class="bg-white rounded-none border border-slate-200 p-6">
            <h2 class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Subtasks</h2>
            <div class="mt-2 divide-y divide-slate-100">
                @forelse($task->subtasks as $sub)
                <div class="py-2 flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <a href="{{ route($prefix . '.tasks.show', $sub->id) }}" class="text-sm font-medium text-slate-900 hover:underline">{{ $sub->title }}</a>
                        <div class="text-[11px] text-slate-400">{{ $sub->assignee?->name ?? 'Unassigned' }} • {{ $sub->progress ?? 0 }}%</div>
                    </div>
                    <span class="rounded-none border border-slate-200 bg-white px-2 py-0.5 text-[11px] font-medium shrink-0">{{ strtoupper(str_replace('_',' ',$sub->status)) }}</span>
                </div>
                @empty
                <p class="text-xs text-slate-500 py-1">No subtasks yet.</p>
                @endforelse
            </div>
            <form action="{{ route('tasks.subtasks.store', $task->id) }}" method="POST" class="mt-3 flex flex-wrap gap-2">
                @csrf
                <input type="text" name="title" required placeholder="New subtask title..." class="flex-1 min-w-[180px] rounded-none border border-slate-200 px-5 py-3 text-xs focus:border-slate-900 focus:outline-none">
                <select name="assigned_to" class="rounded-none border border-slate-200 bg-white px-2 py-2 text-xs focus:border-slate-900 focus:outline-none">
                    <option value="">Assign to —</option>
                    @foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
                </select>
                <button type="submit" class="rounded-none bg-slate-900 px-4 py-2 text-xs font-medium text-white hover:bg-black">Add subtask</button>
            </form>
        </div>
        {{-- Employee workspace: Add Update / Waiting vs Blocked distinct --}}
        <div id="update-task" class="bg-white rounded-none border border-slate-200 p-6">
            <h2 class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Add Update — keep the audit trail current</h2>
            <p class="text-[11px] text-slate-400 mt-1">Waiting = you are waiting for someone. Blocked = a real problem prevents progress. Both require a reason.</p>
            <form action="{{ route($statusRoute, $task->id) }}" method="POST" class="mt-3 space-y-4">
                @csrf
                <div class="grid sm:grid-cols-3 gap-3">
                    <select name="status" x-model="updStatus" class="rounded-none border border-slate-200 bg-white px-5 py-3 text-xs font-medium text-slate-700 focus:border-slate-900 focus:outline-none">
                        @foreach($statusOptions as $val => $label)
                            <option value="{{ $val }}" @selected($task->status===$val)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <input type="number" name="progress" min="0" max="100" value="{{ $task->progress ?? 0 }}" placeholder="Progress %" class="rounded-none border border-slate-200 px-5 py-3 text-xs focus:border-slate-900 focus:outline-none">
                    <input type="text" name="next_action" value="{{ old('next_action', $task->next_action) }}" placeholder="Next action" class="rounded-none border border-slate-200 px-5 py-3 text-xs focus:border-slate-900 focus:outline-none">
                </div>
                <div x-show="updStatus==='waiting'" x-cloak class="grid sm:grid-cols-2 gap-3 border border-amber-200 bg-amber-50 p-4">
                    <div>
                        <label class="block text-xs font-medium text-amber-800 mb-1">Waiting for… *</label>
                        <select name="waiting_reason" class="w-full rounded-none border border-amber-200 bg-white px-3 py-2 text-xs focus:border-slate-900 focus:outline-none">
                            <option value="">Select reason —</option>
                            <option value="waiting_for_customer">Waiting for customer</option>
                            <option value="waiting_for_manager">Waiting for manager</option>
                            <option value="waiting_for_department">Waiting for another department</option>
                            <option value="waiting_for_stock">Waiting for stock</option>
                            <option value="waiting_for_payment">Waiting for payment</option>
                            <option value="other">Other — explain in comment</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-amber-800 mb-1">Expected resolution</label>
                        <input type="date" name="expected_resolution_at" class="w-full rounded-none border border-amber-200 bg-white px-3 py-2 text-xs focus:border-slate-900 focus:outline-none">
                    </div>
                </div>
                <div x-show="updStatus==='blocked'" x-cloak class="border border-rose-200 bg-rose-50 p-4">
                    <label class="block text-xs font-medium text-rose-800 mb-1">Blocker — what problem prevents progress? *</label>
                    <select name="waiting_reason" class="w-full rounded-none border border-rose-200 bg-white px-3 py-2 text-xs focus:border-slate-900 focus:outline-none">
                        <option value="">Select blocker reason —</option>
                        <option value="system_unavailable">System unavailable — cannot complete registration</option>
                        <option value="stock_unavailable">Stock unavailable</option>
                        <option value="access_denied">Access / permission denied</option>
                        <option value="customer_unreachable">Customer unreachable</option>
                        <option value="other">Other — explain in comment</option>
                    </select>
                    <input type="date" name="expected_resolution_at" class="w-full mt-2 rounded-none border border-rose-200 bg-white px-3 py-2 text-xs focus:border-slate-900 focus:outline-none" placeholder="Expected fix date">
                    <p class="text-[11px] text-rose-600 mt-1">Blocked tasks require a reason and are surfaced to managers as attention-required.</p>
                </div>
                <textarea name="comment" rows="2" placeholder="Comment / progress update..." class="w-full rounded-none border border-slate-200 px-5 py-3 text-sm focus:border-slate-900 focus:outline-none"></textarea>
                <div class="flex flex-wrap gap-2 justify-end">
                    <button type="submit" name="status" value="waiting" formaction="{{ route($statusRoute, $task->id) }}" onclick="this.form.querySelector('[name=status]').value='waiting'" class="rounded-none border border-amber-300 bg-amber-50 px-4 py-2.5 text-xs font-medium text-amber-800 hover:bg-amber-100">Mark Waiting</button>
                    <button type="submit" name="status" value="blocked" formaction="{{ route($statusRoute, $task->id) }}" onclick="this.form.querySelector('[name=status]').value='blocked'" class="rounded-none border border-rose-300 bg-rose-50 px-4 py-2.5 text-xs font-medium text-rose-700 hover:bg-rose-100">Report Blocker</button>
                    <button type="submit" class="rounded-none bg-slate-900 px-5 py-2.5 text-xs font-medium text-white hover:bg-black">Add Update</button>
                </div>
            </form>
        </div>
        {{-- Professional Completion Report per spec section 5 --}}
        <div class="bg-white rounded-none border border-slate-200 p-6" x-data="{ showComplete: false }">
            <h2 class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Completion — submit for review</h2>
            <p class="text-[11px] text-slate-400 mt-1">An employee does <strong>not</strong> simply click “Complete”. Submit the report — the manager reviews and Approves & Closes or Returns it.</p>
            @if(in_array($task->status, ['in_progress','waiting','blocked']))
                <button @click="showComplete = !showComplete" class="w-full mt-3 rounded-none bg-slate-900 px-5 py-3 text-sm font-medium text-white hover:bg-black">Submit for Review</button>
            @else
                <p class="mt-3 text-xs text-slate-500">Complete the workflow above — Accept → Start → work → Submit for Review.</p>
            @endif
            <form action="{{ route($statusRoute, $task->id) }}" method="POST" enctype="multipart/form-data" x-show="showComplete" x-cloak class="mt-3 space-y-4 border-t border-slate-100 pt-4">
                @csrf
                <input type="hidden" name="status" value="submitted">
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">What did you do? *</label>
                    <textarea name="what_was_done" rows="2" required placeholder="Contacted the customer and discussed quotation QT-00231." class="w-full rounded-none border border-slate-200 px-5 py-3 text-sm focus:border-slate-900 focus:outline-none"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Result / Outcome *</label>
                    <textarea name="result" rows="2" required placeholder="Customer confirmed interest and requested delivery by Monday." class="w-full rounded-none border border-slate-200 px-5 py-3 text-sm focus:border-slate-900 focus:outline-none"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Issues encountered</label>
                    <textarea name="issues_encountered" rows="2" placeholder="None — or describe any problem (e.g. system unavailable)." class="w-full rounded-none border border-slate-200 px-5 py-3 text-sm focus:border-slate-900 focus:outline-none"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Next Action</label>
                    <input type="text" name="next_action" placeholder="Prepare revised quotation." class="w-full rounded-none border border-slate-200 px-5 py-3 text-sm focus:border-slate-900 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Attachments <span class="text-slate-400 font-normal">(optional evidence / document)</span></label>
                    <input type="file" name="completion_attachments[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.zip,.txt" class="w-full rounded-none border border-slate-200 bg-white px-5 py-3 text-sm file:mr-3 file:rounded-none file:border-0 file:bg-slate-900 file:px-3 file:py-1 file:text-xs file:font-medium file:text-white">
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="rounded-none bg-emerald-600 px-5 py-3 text-xs font-medium text-white hover:bg-emerald-700">Submit for Review</button>
                </div>
            </form>
            @if($task->status==='submitted' || $task->completion_what_was_done)
            <div class="mt-4 border border-amber-200 bg-amber-50 p-4 text-xs">
                <p class="font-semibold text-amber-800">Submitted completion report — awaiting manager review</p>
                @if($task->completion_what_was_done)<p class="mt-1"><span class="font-medium">What was done:</span> {{ $task->completion_what_was_done }}</p>@endif
                @if($task->result)<p class="mt-1"><span class="font-medium">Result:</span> {{ $task->result }}</p>@endif
                @if($task->completion_issues)<p class="mt-1"><span class="font-medium">Issues:</span> {{ $task->completion_issues }}</p>@endif
                @if($task->next_action)<p class="mt-1"><span class="font-medium">Next:</span> {{ $task->next_action }}</p>@endif
                <p class="mt-2 text-[11px] text-amber-700">Manager sees: “Employee says this task is finished. Review the work and approve or return it.”</p>
            </div>
            @endif
            @if($canVerify && !in_array($task->status, ['verified','closed']))
            <form id="approve-form-bottom" action="{{ route($verifyRoute, $task->id) }}" method="POST" class="mt-2">
                @csrf
                <button type="button" @click="openApproveModal('approve-form-bottom')" class="w-full rounded-none border border-emerald-200 bg-emerald-50 px-5 py-3 text-xs font-medium text-emerald-700 hover:bg-emerald-100">Approve & Close (manager)</button>
            </form>
            @endif
            @if($task->verified_at)
            <p class="mt-2 text-xs text-emerald-700">Verified & closed by {{ $task->verifier?->name }} on {{ $task->verified_at->format('d M Y, H:i') }}</p>
            @endif
            @if($task->returned_reason)
            <p class="mt-2 text-xs text-amber-700 border border-amber-200 bg-amber-50 px-3 py-2">Returned — {{ $task->returned_reason }}</p>
            @endif
        </div>
    </div>

    {{-- ACTIVITY --}}
    <div x-show="tab==='activity'" x-cloak class="bg-white rounded-none border border-slate-200 p-6">
        <h2 class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Activity</h2>
        <div class="mt-3 space-y-4">
            @forelse($task->updates as $u)
            <div class="flex gap-3.5">
                <span class="h-2 w-2 rounded-none bg-slate-300 mt-1.5 shrink-0"></span>
                <div class="text-xs">
                    <span class="font-medium text-slate-900">{{ $u->user?->name ?? 'System' }}</span>
                    <span class="text-slate-500"> {{ $u->status_from ? str_replace('_',' ',$u->status_from) . ' → ' : '' }}{{ str_replace('_',' ',$u->status_to) }}</span>
                    <span class="text-slate-400">• {{ $u->created_at->format('d M, H:i') }}</span>
                    @if($u->comment)<p class="text-slate-700 mt-0.5">{{ $u->comment }}</p>@endif
                    @if($u->result)<p class="text-slate-700 mt-0.5"><span class="font-medium">Result:</span> {{ $u->result }}</p>@endif
                </div>
            </div>
            @empty
            <p class="text-xs text-slate-500">No activity yet.</p>
            @endforelse
        </div>
    </div>

    {{-- CHECKLIST --}}
    <div x-show="tab==='checklist'" x-cloak class="bg-white rounded-none border border-slate-200 p-6">
        <h2 class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Checklist</h2>
        <p class="text-[11px] text-slate-400 mt-1">{{ $checklistDone }} / {{ $checklistTotal }} completed — parent progress follows this list.</p>
        <div class="mt-3 divide-y divide-slate-100">
            @forelse($task->checklists as $item)
            <div class="py-2 flex items-center gap-3">
                <form action="{{ route('tasks.checklist.toggle', [$task->id, $item->id]) }}" method="POST" class="shrink-0">
                    @csrf
                    <button type="submit" class="h-5 w-5 rounded-none border flex items-center justify-center {{ $item->is_done ? 'bg-emerald-600 border-emerald-600 text-white' : 'border-slate-300 bg-white text-transparent' }}">
                        <span class="material-symbols-outlined text-[12px] leading-none">check</span>
                    </button>
                </form>
                <span class="flex-1 text-sm {{ $item->is_done ? 'line-through text-slate-400' : 'text-slate-900' }}">{{ $item->title }}</span>
                <form id="delete-checklist-form-{{ $item->id }}" action="{{ route('tasks.checklist.destroy', [$task->id, $item->id]) }}" method="POST">
                    @csrf @method('DELETE')
                    <button type="button" @click="openChecklistDelete({{ $item->id }}, @js($item->title))" class="h-6 w-6 rounded-none text-slate-300 hover:text-rose-600 flex items-center justify-center"><span class="material-symbols-outlined text-[14px]">delete</span></button>
                </form>
            </div>
            @empty
            <p class="text-xs text-slate-500 py-2">No checklist items yet.</p>
            @endforelse
        </div>
        <form action="{{ route('tasks.checklist.store', $task->id) }}" method="POST" class="mt-3 flex gap-2">
            @csrf
            <input type="text" name="title" required placeholder="New checklist item..." class="flex-1 rounded-none border border-slate-200 px-4 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
            <button type="submit" class="rounded-none bg-slate-900 px-4 py-2 text-xs font-medium text-white hover:bg-black">Add</button>
        </form>
    </div>

    {{-- FILES — real document upload + success message --}}
    <div x-show="tab==='files'" x-cloak class="bg-white rounded-none border border-slate-200 p-6" x-data="{ files: null, uploading: false }">
        <h2 class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Documents & Evidence</h2>
        <p class="text-[11px] text-slate-400 mt-1">PDF, Images, Excel, Word, PPT, ZIP, TXT — up to 10MB each, 10 files at once. Upload is real — files stored at <code>storage/app/public/task_attachments</code> and served via <code>/storage</code>.</p>

        {{-- Success / error from last upload --}}


        {{-- Upload form --}}
        <form action="{{ route('tasks.attachments.store', $task->id) }}" method="POST" enctype="multipart/form-data" class="mt-4 border border-dashed border-slate-300 bg-slate-50 p-4" @submit="uploading = true">
            @csrf
            <label class="block text-xs font-semibold text-slate-700 mb-2">Upload documents</label>
            <div class="flex flex-wrap gap-3 items-center">
                <label class="inline-flex items-center gap-2 rounded-none border border-slate-900 bg-white px-4 py-2.5 text-xs font-medium text-slate-900 hover:bg-slate-50 cursor-pointer">
                    <span class="material-symbols-outlined text-[16px]">upload_file</span>
                    Choose files
                    <input type="file" name="attachments[]" multiple required accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.zip,.txt,.csv" class="hidden" @change="files = $event.target.files; const list = [...files].map(f => f.name + ' (' + (f.size/1024).toFixed(0) + ' KB)').join(', '); $refs.fileList.textContent = list || 'No file selected'">
                </label>
                <span x-ref="fileList" class="text-xs text-slate-500 truncate max-w-[260px]">No file selected</span>
                <button type="submit" class="ml-auto rounded-none bg-slate-900 px-5 py-2.5 text-xs font-medium text-white hover:bg-black disabled:opacity-50" :disabled="uploading" x-text="uploading ? 'Uploading…' : 'Upload'">
                </button>
            </div>
            <p class="text-[11px] text-slate-400 mt-2">After upload you will see a green success banner: e.g. <em>“2 document(s) uploaded successfully: quotation.pdf, photo.jpg”</em> — and files appear below instantly.</p>
            @error('attachments')<p class="text-xs text-rose-600 mt-2">{{ $message }}</p>@enderror
            @error('attachments.*')<p class="text-xs text-rose-600 mt-2">{{ $message }}</p>@enderror
        </form>

        {{-- Existing files --}}
        <h3 class="text-xs font-semibold tracking-wide text-slate-500 uppercase mt-6 mb-2">Attached files ({{ is_array($task->attachments) ? count($task->attachments) : 0 }})</h3>
        @if(!empty($task->attachments) && is_array($task->attachments))
        <div class="divide-y divide-slate-100 border border-slate-200">
            @foreach($task->attachments as $idx => $att)
                <div class="flex items-center gap-3 px-3 py-3 hover:bg-slate-50">
                    <span class="material-symbols-outlined text-slate-400 text-[18px] shrink-0">
                        @if(str_contains($att['mime'] ?? '', 'pdf')) picture_as_pdf
                        @elseif(str_contains($att['mime'] ?? '', 'image')) image
                        @elseif(str_contains($att['mime'] ?? '', 'sheet') || str_contains($att['mime'] ?? '', 'excel')) table_chart
                        @elseif(str_contains($att['mime'] ?? '', 'word')) description
                        @else attach_file
                        @endif
                    </span>
                    <div class="min-w-0 flex-1">
                        <a href="{{ $att['path'] ?? '#' }}" target="_blank" class="text-sm font-medium text-slate-900 hover:underline hover:text-emerald-700 truncate block">{{ $att['name'] ?? 'file' }}</a>
                        <div class="text-[11px] text-slate-400">
                            @if(isset($att['size'])){{ number_format($att['size']/1024, 1) }} KB · @endif
                            {{ $att['mime'] ?? 'file' }}
                            @if(isset($att['uploaded_at'])) · {{ \Carbon\Carbon::parse($att['uploaded_at'])->format('d M Y, H:i') }}@endif
                        </div>
                    </div>
                    <a href="{{ $att['path'] ?? '#' }}" target="_blank" class="shrink-0 rounded-none border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-medium text-slate-700 hover:bg-slate-900 hover:text-white hover:border-slate-900">Download</a>
                    <form id="delete-doc-form-{{ $idx }}" action="{{ route('tasks.attachments.destroy', [$task->id, $idx]) }}" method="POST" class="shrink-0">
                        @csrf @method('DELETE')
                        <button type="button" @click="openDocDelete({{ $idx }}, @js($att['name'] ?? 'document'))" class="h-8 w-8 rounded-none border border-transparent hover:border-rose-200 hover:bg-rose-50 text-slate-400 hover:text-rose-600 flex items-center justify-center" title="Remove document">
                            <span class="material-symbols-outlined text-[16px]">delete</span>
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
        @else
        <p class="text-xs text-slate-500 mt-2 border border-dashed border-slate-200 bg-white px-4 py-8 text-center">No files yet — upload your first document above. It will be stored permanently and shown here.</p>
        @endif
    </div>

    {{-- RELATED --}}
    <div x-show="tab==='related'" x-cloak class="space-y-4">
        @if($task->parent)
        <div class="bg-white rounded-none border border-slate-200 p-6">
            <h2 class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Parent task</h2>
            <a href="{{ route($prefix . '.tasks.show', $task->parent->id) }}" class="text-sm font-medium text-slate-900 hover:underline">{{ $task->parent->title }}</a>
        </div>
        @endif
        @if($task->customer_name || $task->lead || $task->quotation || $task->salesOrder || $task->product)
        <div class="bg-white rounded-none border border-slate-200 p-6">
            <h2 class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Related records</h2>
            <div class="mt-2 space-y-2 text-sm">
                @if($task->customer_name)<div class="flex justify-between"><span class="text-slate-500">Customer</span><span class="font-medium text-slate-900">{{ $task->customer_name }}</span></div>@endif
                @if($task->lead)<div class="flex justify-between"><span class="text-slate-500">Lead</span><span class="font-medium text-slate-900">{{ $task->lead->name ?? ('#'.$task->lead->id) }}</span></div>@endif
                @if($task->quotation)<div class="flex justify-between"><span class="text-slate-500">Quotation</span><a href="{{ route('sales.quotations.print', $task->quotation->id) }}" target="_blank" class="font-medium text-emerald-700 hover:underline">{{ $task->quotation->quote_number ?? ('#'.$task->quotation->id) }} — TSh {{ number_format($task->quotation->total_amount ?? 0) }}</a></div>@endif
                @if($task->salesOrder)<div class="flex justify-between"><span class="text-slate-500">Sales order</span><a href="{{ route('sales.invoices.receipt', $task->salesOrder->id) }}" target="_blank" class="font-medium text-emerald-700 hover:underline">{{ $task->salesOrder->receipt_number ?? ('#'.$task->salesOrder->id) }}</a></div>@endif
                @if($task->product)<div class="flex justify-between"><span class="text-slate-500">Product</span><span class="font-medium text-slate-900">{{ $task->product->name }}</span></div>@endif
            </div>
        </div>
        @endif
        <div class="bg-white rounded-none border border-slate-200 p-6">
            <h2 class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Dependencies</h2>
            @if($task->dependencies->count())
            <div class="mt-2 divide-y divide-slate-100">
                @foreach($task->dependencies as $dep)
                <div class="py-2 flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <a href="{{ route($prefix . '.tasks.show', $dep->id) }}" class="text-sm font-medium text-slate-900 hover:underline">{{ Str::limit($dep->title, 50) }}</a>
                        <div class="text-[11px] text-slate-400">#{{ $dep->id }} • {{ strtoupper(str_replace('_',' ',$dep->status)) }}</div>
                    </div>
                    <form id="delete-dep-form-{{ $dep->id }}" action="{{ route('tasks.dependencies.destroy', [$task->id, $dep->id]) }}" method="POST">
                        @csrf @method('DELETE')
                        <button type="button" @click="openDepDelete({{ $dep->id }}, @js(Str::limit($dep->title, 50)))" class="h-6 w-6 rounded-none text-slate-300 hover:text-rose-600 flex items-center justify-center"><span class="material-symbols-outlined text-[14px]">delete</span></button>
                    </form>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-xs text-slate-500 mt-2">No dependencies — this task can proceed freely.</p>
            @endif
            <form action="{{ route('tasks.dependencies.store', $task->id) }}" method="POST" class="mt-3 flex gap-2">
                @csrf
                <select name="depends_on_task_id" required class="flex-1 rounded-none border border-slate-200 bg-white px-3 py-2 text-xs focus:border-slate-900 focus:outline-none">
                    <option value="">Depends on task —</option>
                    @foreach($allTasks->where('id', '!=', $task->id) as $cand)
                        <option value="{{ $cand->id }}">#{{ $cand->id }} {{ Str::limit($cand->title, 40) }} ({{ $cand->status }})</option>
                    @endforeach
                </select>
                <button type="submit" class="rounded-none bg-slate-900 px-4 py-2 text-xs font-medium text-white hover:bg-black">Add</button>
            </form>
            @if($task->dependents->count())
            <div class="mt-3 border-t border-slate-100 pt-3">
                <p class="text-[11px] font-medium text-slate-500 mb-1">Blocking (waits on this task):</p>
                @foreach($task->dependents as $dep)
                    <a href="{{ route($prefix . '.tasks.show', $dep->id) }}" class="block text-xs text-slate-700 hover:underline">#{{ $dep->id }} {{ Str::limit($dep->title, 50) }}</a>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- HISTORY --}}
    <div x-show="tab==='history'" x-cloak class="bg-white rounded-none border border-slate-200 p-6">
        <h2 class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Task history</h2>
        <dl class="mt-3 grid grid-cols-2 gap-3 text-xs">
            <div><dt class="text-slate-400">Created by</dt><dd class="font-medium text-slate-900">{{ $task->creator?->name ?? '—' }}</dd></div>
            <div><dt class="text-slate-400">Created</dt><dd class="font-medium text-slate-900">{{ $task->created_at->format('d M Y, H:i') }}</dd></div>
            @if($task->completed_at)<div><dt class="text-slate-400">Completed</dt><dd class="font-medium text-slate-900">{{ $task->completed_at->format('d M Y, H:i') }}</dd></div>@endif
            @if($task->verified_at)<div><dt class="text-slate-400">Verified by</dt><dd class="font-medium text-slate-900">{{ $task->verifier?->name }} · {{ $task->verified_at->format('d M Y, H:i') }}</dd></div>@endif
            @if($task->submitted_at)<div><dt class="text-slate-400">Submitted</dt><dd class="font-medium text-slate-900">{{ $task->submitted_at->format('d M Y, H:i') }}</dd></div>@endif
        </dl>
        <div class="mt-4 space-y-4 border-t border-slate-100 pt-4">
            @forelse($task->updates as $u)
            <div class="flex gap-3.5">
                <span class="h-2 w-2 rounded-none bg-slate-300 mt-1.5 shrink-0"></span>
                <div class="text-xs">
                    <span class="font-medium text-slate-900">{{ $u->user?->name ?? 'System' }}</span>
                    <span class="text-slate-500"> {{ $u->status_from ? str_replace('_',' ',$u->status_from) . ' → ' : '' }}{{ str_replace('_',' ',$u->status_to) }}</span>
                    <span class="text-slate-400">• {{ $u->created_at->format('d M Y, H:i') }}</span>
                    @if($u->comment)<p class="text-slate-700 mt-0.5">{{ $u->comment }}</p>@endif
                    @if($u->result)<p class="text-slate-700 mt-0.5"><span class="font-medium">Result:</span> {{ $u->result }}</p>@endif
                    @if($u->next_action)<p class="text-slate-700 mt-0.5"><span class="font-medium">Next:</span> {{ $u->next_action }}</p>@endif
                </div>
            </div>
            @empty
            <p class="text-xs text-slate-500">No history yet.</p>
            @endforelse
        </div>
    </div>

    {{-- Edit details (priority / due) --}}
    <div class="mt-6 bg-white rounded-none border border-slate-200 p-6">
        <h2 class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Edit details</h2>
        <form action="{{ route('tasks.details.update', $task->id) }}" method="POST" class="mt-3 grid sm:grid-cols-3 gap-3">
            @csrf @method('PUT')
            <select name="priority" class="rounded-none border border-slate-200 bg-white px-4 py-2.5 text-xs font-medium focus:border-slate-900 focus:outline-none">
                @foreach(['low'=>'Low','medium'=>'Normal','high'=>'High','urgent'=>'Urgent','critical'=>'Critical'] as $val => $label)
                    <option value="{{ $val }}" @selected(($task->priority ?? 'medium')===$val)>{{ $label }}</option>
                @endforeach
            </select>
            <input type="date" name="due_at" value="{{ $task->due_at ? $task->due_at->format('Y-m-d') : '' }}" class="rounded-none border border-slate-200 px-4 py-2.5 text-xs focus:border-slate-900 focus:outline-none">
            <button type="submit" class="rounded-none bg-slate-900 px-4 py-2.5 text-xs font-medium text-white hover:bg-black">Save changes</button>
        </form>
    </div>

    {{-- Carbon modals — approve, checklist, document, dependency --}}
    <div x-show="showApproveModal" x-cloak @keydown.escape.window="closeApproveModal()" style="position:fixed; inset:0; z-index:9000; display:flex; align-items:center; justify-content:center; background:rgba(22,22,22,0.5); padding:1rem;">
      <div x-trap.noscroll="showApproveModal" role="dialog" aria-modal="true" aria-labelledby="approve-modal-title" class="cds--modal" style="background:#fff; border:1px solid #e0e0e0; width:100%; max-width:28rem; display:flex; flex-direction:column; box-shadow:0 8px 32px rgba(0,0,0,0.2);" @click.away="closeApproveModal()">
        <div style="padding:1rem 1.25rem; background:#f4f4f4; border-bottom:1px solid #e0e0e0; display:flex; align-items:center; justify-content:space-between;">
          <h3 id="approve-modal-title" style="font-size:1rem; font-weight:600; color:#161616; font-family:'IBM Plex Sans',sans-serif;">Approve and close?</h3>
          <button @click="closeApproveModal()" type="button" style="width:2rem; height:2rem; background:transparent; border:0; display:flex; align-items:center; justify-content:center; color:#525252; cursor:pointer;" aria-label="Close"><span class="material-symbols-outlined" style="font-size:18px;">close</span></button>
        </div>
        <div style="padding:1.25rem; background:#fff;">
          <p style="font-size:0.875rem; color:#525252; font-family:'IBM Plex Sans',sans-serif;">Approve and close — will notify receiver and close task. This action cannot be undone.</p>
        </div>
        <div style="padding:1rem 1.25rem; background:#f4f4f4; border-top:1px solid #e0e0e0; display:flex; justify-content:flex-end; gap:0.5rem;">
          <button x-ref="cancelApproveBtn" @click="closeApproveModal()" type="button" style="height:2.25rem; padding:0 1rem; background:#fff; border:1px solid #8d8d8d; color:#161616; font-size:0.875rem; font-weight:600; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">Cancel</button>
          <button @click="confirmApprove()" type="button" style="height:2.25rem; padding:0 1rem; background:#0f62fe; border:1px solid #0f62fe; color:#fff; font-size:0.875rem; font-weight:600; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">Confirm</button>
        </div>
      </div>
    </div>

    <div x-show="showChecklistDeleteModal" x-cloak @keydown.escape.window="closeChecklistDelete()" style="position:fixed; inset:0; z-index:9000; display:flex; align-items:center; justify-content:center; background:rgba(22,22,22,0.5); padding:1rem;">
      <div x-trap.noscroll="showChecklistDeleteModal" role="dialog" aria-modal="true" aria-labelledby="checklist-delete-title" class="cds--modal" style="background:#fff; border:1px solid #e0e0e0; width:100%; max-width:28rem; display:flex; flex-direction:column; box-shadow:0 8px 32px rgba(0,0,0,0.2);" @click.away="closeChecklistDelete()">
        <div style="padding:1rem 1.25rem; background:#f4f4f4; border-bottom:1px solid #e0e0e0; display:flex; align-items:center; justify-content:space-between;">
          <h3 id="checklist-delete-title" style="font-size:1rem; font-weight:600; color:#161616; font-family:'IBM Plex Sans',sans-serif;">Remove checklist item?</h3>
          <button @click="closeChecklistDelete()" type="button" style="width:2rem; height:2rem; background:transparent; border:0; display:flex; align-items:center; justify-content:center; color:#525252; cursor:pointer;" aria-label="Close"><span class="material-symbols-outlined" style="font-size:18px;">close</span></button>
        </div>
        <div style="padding:1.25rem; background:#fff;">
          <p style="font-size:0.875rem; color:#525252; font-family:'IBM Plex Sans',sans-serif;">Remove <span style="font-weight:600; color:#161616;" x-text="pendingChecklistTitle ? '“'+pendingChecklistTitle+'”' : 'this item'"></span>? This cannot be undone.</p>
        </div>
        <div style="padding:1rem 1.25rem; background:#f4f4f4; border-top:1px solid #e0e0e0; display:flex; justify-content:flex-end; gap:0.5rem;">
          <button x-ref="cancelChecklistBtn" @click="closeChecklistDelete()" type="button" style="height:2.25rem; padding:0 1rem; background:#fff; border:1px solid #8d8d8d; color:#161616; font-size:0.875rem; font-weight:600; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">Cancel</button>
          <button @click="confirmChecklistDelete()" type="button" style="height:2.25rem; padding:0 1rem; background:#da1e28; border:1px solid #da1e28; color:#fff; font-size:0.875rem; font-weight:600; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">Delete</button>
        </div>
      </div>
    </div>

    <div x-show="showDocDeleteModal" x-cloak @keydown.escape.window="closeDocDelete()" style="position:fixed; inset:0; z-index:9000; display:flex; align-items:center; justify-content:center; background:rgba(22,22,22,0.5); padding:1rem;">
      <div x-trap.noscroll="showDocDeleteModal" role="dialog" aria-modal="true" aria-labelledby="doc-delete-title" class="cds--modal" style="background:#fff; border:1px solid #e0e0e0; width:100%; max-width:28rem; display:flex; flex-direction:column; box-shadow:0 8px 32px rgba(0,0,0,0.2);" @click.away="closeDocDelete()">
        <div style="padding:1rem 1.25rem; background:#f4f4f4; border-bottom:1px solid #e0e0e0; display:flex; align-items:center; justify-content:space-between;">
          <h3 id="doc-delete-title" style="font-size:1rem; font-weight:600; color:#161616; font-family:'IBM Plex Sans',sans-serif;">Remove document?</h3>
          <button @click="closeDocDelete()" type="button" style="width:2rem; height:2rem; background:transparent; border:0; display:flex; align-items:center; justify-content:center; color:#525252; cursor:pointer;" aria-label="Close"><span class="material-symbols-outlined" style="font-size:18px;">close</span></button>
        </div>
        <div style="padding:1.25rem; background:#fff;">
          <p style="font-size:0.875rem; color:#525252; font-family:'IBM Plex Sans',sans-serif;">Remove <span style="font-weight:600; color:#161616;" x-text="pendingDocName"></span>? This cannot be undone.</p>
        </div>
        <div style="padding:1rem 1.25rem; background:#f4f4f4; border-top:1px solid #e0e0e0; display:flex; justify-content:flex-end; gap:0.5rem;">
          <button x-ref="cancelDocBtn" @click="closeDocDelete()" type="button" style="height:2.25rem; padding:0 1rem; background:#fff; border:1px solid #8d8d8d; color:#161616; font-size:0.875rem; font-weight:600; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">Cancel</button>
          <button @click="confirmDocDelete()" type="button" style="height:2.25rem; padding:0 1rem; background:#da1e28; border:1px solid #da1e28; color:#fff; font-size:0.875rem; font-weight:600; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">Delete</button>
        </div>
      </div>
    </div>

    <div x-show="showDepDeleteModal" x-cloak @keydown.escape.window="closeDepDelete()" style="position:fixed; inset:0; z-index:9000; display:flex; align-items:center; justify-content:center; background:rgba(22,22,22,0.5); padding:1rem;">
      <div x-trap.noscroll="showDepDeleteModal" role="dialog" aria-modal="true" aria-labelledby="dep-delete-title" class="cds--modal" style="background:#fff; border:1px solid #e0e0e0; width:100%; max-width:28rem; display:flex; flex-direction:column; box-shadow:0 8px 32px rgba(0,0,0,0.2);" @click.away="closeDepDelete()">
        <div style="padding:1rem 1.25rem; background:#f4f4f4; border-bottom:1px solid #e0e0e0; display:flex; align-items:center; justify-content:space-between;">
          <h3 id="dep-delete-title" style="font-size:1rem; font-weight:600; color:#161616; font-family:'IBM Plex Sans',sans-serif;">Remove dependency?</h3>
          <button @click="closeDepDelete()" type="button" style="width:2rem; height:2rem; background:transparent; border:0; display:flex; align-items:center; justify-content:center; color:#525252; cursor:pointer;" aria-label="Close"><span class="material-symbols-outlined" style="font-size:18px;">close</span></button>
        </div>
        <div style="padding:1.25rem; background:#fff;">
          <p style="font-size:0.875rem; color:#525252; font-family:'IBM Plex Sans',sans-serif;">Remove dependency <span style="font-weight:600; color:#161616;" x-text="pendingDepTitle ? '“'+pendingDepTitle+'”' : ''"></span>? This will unlink the tasks.</p>
        </div>
        <div style="padding:1rem 1.25rem; background:#f4f4f4; border-top:1px solid #e0e0e0; display:flex; justify-content:flex-end; gap:0.5rem;">
          <button x-ref="cancelDepBtn" @click="closeDepDelete()" type="button" style="height:2.25rem; padding:0 1rem; background:#fff; border:1px solid #8d8d8d; color:#161616; font-size:0.875rem; font-weight:600; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">Cancel</button>
          <button @click="confirmDepDelete()" type="button" style="height:2.25rem; padding:0 1rem; background:#da1e28; border:1px solid #da1e28; color:#fff; font-size:0.875rem; font-weight:600; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">Delete</button>
        </div>
      </div>
    </div>
</div>
@endsection
