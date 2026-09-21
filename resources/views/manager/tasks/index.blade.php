@extends('reception.layout')

@section('content')
<div class="w-full" x-data="{ showCreate: false, showBulk: false, showTemplateForm: false, bulkRows: [{title:'', assigned_to:''}], view: '{{ $view }}', section: '{{ $section ?? 'all' }}', tab: '{{ $section ?? 'all' }}', fDept: 'all', fAssignee: 'all', fType: 'all', fPriority: 'all', fDue: 'all', fStatus: 'all', search: '', showTemplateDeleteModal:false, pendingTemplateId:null, pendingTemplateTitle:'', openTemplateDelete(id,title){ this.pendingTemplateId=id; this.pendingTemplateTitle=title; this.showTemplateDeleteModal=true; this.$nextTick(()=>{ this.$refs.cancelTemplateBtn && this.$refs.cancelTemplateBtn.focus(); }); }, closeTemplateDelete(){ this.showTemplateDeleteModal=false; this.pendingTemplateId=null; }, confirmTemplateDelete(){ if(this.pendingTemplateId){ let f=document.getElementById('delete-template-form-'+this.pendingTemplateId); if(f) f.submit(); } }, matchesTab(s, mine, archived) { return this.tab==='all' ? !archived : this.tab==='my' ? mine : this.tab==='team' ? !mine && !archived : this.tab==='pending_review' ? ['submitted','under_review','completed'].includes(s) : this.tab==='waiting' ? (s==='waiting'||s==='blocked') : this.tab==='completed' ? ['verified','closed'].includes(s) : this.tab==='archive' ? archived : true; }, matchesDue(due, overdue) { return this.fDue==='all' || (this.fDue==='overdue' && overdue) || (this.fDue==='today' && due==='today') || (this.fDue==='week' && (due==='today'||due==='week')); } }">
    {{-- Header: Work Management --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <p class="text-[11px] font-semibold tracking-widest text-slate-400 uppercase">Work Management</p>
            <h1 class="text-xl font-semibold tracking-tight text-slate-900 mt-1">Work Centre</h1>
            <p class="text-xs text-slate-500 mt-1">Internal work-control system — Customer Tickets remain separate.</p>
        </div>
        <div class="flex items-center gap-2 flex-1 max-w-xl justify-end flex-wrap">
            <div class="relative flex-1 min-w-[220px] max-w-sm">
                <span class="material-symbols-outlined absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-[16px]">search</span>
                <input type="text" x-model="search" placeholder="Search tasks, employees, customers..." class="w-full pl-8 pr-3 py-2.5 rounded-none border border-slate-200 text-sm focus:outline-none focus:border-slate-900 bg-white">
            </div>
            <div class="flex rounded-none border border-slate-200 bg-white text-xs font-medium overflow-hidden">
                <a href="{{ route('manager.tasks', ['view' => 'list']) }}" class="px-3 py-2.5 {{ $view==='list' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-50' }}">List</a>
                <a href="{{ route('manager.tasks', ['view' => 'board']) }}" class="px-3 py-2.5 border-l border-slate-200 {{ $view==='board' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-50' }}">Board</a>
                <a href="{{ route('manager.tasks', ['view' => 'calendar']) }}" class="px-3 py-2.5 border-l border-slate-200 {{ $view==='calendar' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-50' }}">Calendar</a>
            </div>
            <button @click="showCreate = !showCreate" class="rounded-none bg-slate-900 px-5 py-2.5 text-sm font-medium text-white hover:bg-black shrink-0" x-text="showCreate ? 'Close' : '+ Create Task'"></button>
        </div>
    </div>

    {{-- Work Management sub-nav per spec --}}
    <div class="flex items-center gap-1 text-xs font-medium border-b border-slate-200 mb-6 overflow-x-auto">
        @php $sections = [['all','All Tasks'],['my','My Work'],['team','Team Tasks'],['pending_review','Pending Review'],['calendar','Calendar'],['templates','Task Templates'],['reports','Reports']]; @endphp
        @foreach($sections as [$key,$label])
            <a href="{{ $key==='calendar' ? route('manager.tasks',['view'=>'calendar']) : ($key==='templates' ? '#' : route('manager.tasks',['section'=>$key])) }}" @if($key==='templates') @click.prevent="showTemplateForm=!showTemplateForm; section='templates'; tab='templates'" @endif class="px-3 py-2 border-b-2 -mb-px whitespace-nowrap {{ (($section ?? 'all')===$key || ($view==='calendar' && $key==='calendar')) ? 'text-slate-900 border-slate-900' : 'text-slate-500 border-transparent hover:text-slate-900' }}">{{ $label }} @if($key==='pending_review')<span class="ml-1 inline-flex items-center justify-center h-4 min-w-[16px] px-1 rounded-none bg-amber-500 text-white text-[10px] font-bold">{{ $stats['pending_review'] ?? 0 }}</span>@endif</a>
        @endforeach
    </div>

    @if(session('success'))
    <x-success-popup :message="session('success')" />
    @endif
    @if($errors->any())
    <div class="mb-6 rounded-none border border-rose-200 bg-rose-50 px-5 py-3 text-sm text-rose-700">
        <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    {{-- 7-metric ERP summary per spec --}}
    <h2 class="text-[11px] font-semibold tracking-widest text-slate-400 uppercase mb-2">Operational summary</h2>
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3 mb-8">
        @php
            $statItems = [
                'all'=>'Total Tasks',
                'assigned'=>'Assigned',
                'in_progress'=>'In Progress',
                'waiting'=>'Waiting',
                'blocked'=>'Blocked',
                'overdue'=>'Overdue',
                'pending_review'=>'Pending Review',
            ];
        @endphp
        @foreach($statItems as $key => $label)
        <div class="bg-white rounded-none border {{ $key==='overdue' && ($stats[$key]??0)>0 ? 'border-rose-200' : ($key==='pending_review' && ($stats[$key]??0)>0 ? 'border-amber-200' : 'border-slate-200') }} p-4 text-center">
            <div class="text-xl font-semibold {{ $key==='overdue' && ($stats[$key]??0)>0 ? 'text-rose-600' : ($key==='pending_review' && ($stats[$key]??0)>0 ? 'text-amber-600' : 'text-slate-900') }}">{{ $stats[$key] ?? 0 }}</div>
            <div class="text-[11px] text-slate-500 font-medium mt-0.5">{{ $label }}</div>
        </div>
        @endforeach
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3 mb-8 -mt-4">
        <div class="bg-emerald-50 rounded-none border border-emerald-200 p-3 text-center">
            <div class="text-lg font-semibold text-emerald-700">{{ $stats['completed'] ?? 0 }}</div>
            <div class="text-[11px] text-emerald-600 font-medium">Completed / Closed</div>
        </div>
    </div>

    {{-- Secondary tabs inside list --}}
    <div class="flex items-center gap-1 text-xs font-medium border-b border-slate-200 mb-4 overflow-x-auto" x-show="view==='list' && section!=='reports' && section!=='templates'">
        <template x-for="t in [['all','All'],['my','My Work'],['team','Team'],['pending_review','Pending Review'],['waiting','Waiting/Blocked'],['completed','Completed']]" :key="t[0]">
            <button @click="tab = t[0]" :class="tab===t[0] ? 'text-slate-900 border-slate-900' : 'text-slate-500 border-transparent hover:text-slate-900'" class="px-3 py-2 border-b-2 -mb-px whitespace-nowrap" x-text="t[1]"></button>
        </template>
        <span class="ml-auto text-[11px] text-slate-400 hidden sm:inline">Professional lifecycle: Draft → Assigned → Accepted → In Progress → Waiting/Blocked → Submitted → Approved/Returned</span>
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap items-center gap-2 text-xs mb-4" x-show="view==='list' && section!=='reports' && section!=='templates'">
        <span class="text-slate-500 font-medium">Filter:</span>
        <select x-model="fDept" class="rounded-none border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 focus:border-slate-900 focus:outline-none">
            <option value="all">Department</option>
            @foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
        </select>
        <select x-model="fAssignee" class="rounded-none border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 focus:border-slate-900 focus:outline-none">
            <option value="all">Assignee</option>
            @foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
        </select>
        <select x-model="fType" class="rounded-none border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 focus:border-slate-900 focus:outline-none">
            <option value="all">Task Type</option>
            @foreach($taskTypes as $group => $types)<optgroup label="{{ $group }}">@foreach($types as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach</optgroup>@endforeach
        </select>
        <select x-model="fPriority" class="rounded-none border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 focus:border-slate-900 focus:outline-none">
            <option value="all">Priority</option>
            <option value="low">Low</option><option value="medium">Normal</option><option value="normal">Normal</option>
            <option value="high">High</option><option value="urgent">Urgent</option><option value="critical">Critical</option>
        </select>
        <select x-model="fDue" class="rounded-none border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 focus:border-slate-900 focus:outline-none">
            <option value="all">Due Date</option>
            <option value="overdue">Overdue</option>
            <option value="today">Due today</option>
            <option value="week">Due this week</option>
        </select>
        <select x-model="fStatus" class="rounded-none border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 focus:border-slate-900 focus:outline-none">
            <option value="all">Status</option>
            <option value="draft">Draft</option><option value="assigned">Assigned</option><option value="accepted">Accepted</option><option value="in_progress">In Progress</option><option value="waiting">Waiting</option><option value="blocked">Blocked</option><option value="submitted">Submitted</option><option value="returned">Returned</option><option value="verified">Verified</option>
        </select>
    </div>

    {{-- CREATE TASK — professional ERP form per spec section 3 --}}
    <form action="{{ route('reception.tasks.store') }}" method="POST" enctype="multipart/form-data" x-show="showCreate" x-cloak class="bg-white rounded-none border border-slate-200 p-6 mb-8 space-y-6">
        @csrf
        <div class="flex items-center justify-between">
            <h2 class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Create Task — professional assignment</h2>
            <span class="text-[11px] text-slate-400">Task lifecycle starts as <strong>Draft</strong> or <strong>Assigned</strong></span>
        </div>

        <div>
            <label class="block text-xs font-medium text-slate-700 mb-1">Task Title *</label>
            <input type="text" name="title" required placeholder="Follow up with ABC Construction quotation" class="w-full rounded-none border border-slate-200 px-4 py-3 text-sm font-medium focus:border-slate-900 focus:outline-none">
        </div>

        <div>
            <p class="text-[11px] font-semibold tracking-wide text-slate-400 uppercase mb-2">Basic Information</p>
            <div class="grid sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Task Type</label>
                    <select name="task_type" class="w-full rounded-none border border-slate-200 bg-white px-4 py-3 text-sm focus:border-slate-900 focus:outline-none">
                        <option value="general">General</option>
                        @foreach($taskTypes as $group => $types)
                            <optgroup label="{{ $group }}">@foreach($types as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach</optgroup>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Department *</label>
                    <select name="department_id" class="w-full rounded-none border border-slate-200 bg-white px-4 py-3 text-sm focus:border-slate-900 focus:outline-none">
                        <option value="">Select department —</option>
                        @foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Priority</label>
                    <div class="flex flex-wrap items-center gap-3 pt-2.5 text-sm text-slate-700">
                        <label class="inline-flex items-center gap-1.5 cursor-pointer"><input type="radio" name="priority" value="low" class="accent-slate-900"> Low</label>
                        <label class="inline-flex items-center gap-1.5 cursor-pointer"><input type="radio" name="priority" value="normal" checked class="accent-slate-900"> Normal</label>
                        <label class="inline-flex items-center gap-1.5 cursor-pointer"><input type="radio" name="priority" value="high" class="accent-slate-900"> High</label>
                        <label class="inline-flex items-center gap-1.5 cursor-pointer"><input type="radio" name="priority" value="critical" class="accent-slate-900"> Critical</label>
                    </div>
                </div>
            </div>
            <div class="grid sm:grid-cols-3 gap-4 mt-4">
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Responsible Person *</label>
                    <select name="assigned_to" class="w-full rounded-none border border-slate-200 bg-white px-4 py-3 text-sm focus:border-slate-900 focus:outline-none">
                        <option value="">Select employee —</option>
                        @foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-slate-700 mb-1">Collaborators</label>
                    <div class="flex flex-wrap gap-1.5 max-h-20 overflow-y-auto border border-slate-200 p-2 bg-white">
                        @foreach($users as $u)
                            <label class="inline-flex items-center gap-1 rounded-none border border-slate-200 bg-white px-2 py-1 text-[11px] font-medium text-slate-600 cursor-pointer hover:bg-slate-50">
                                <input type="checkbox" name="collaborators[]" value="{{ $u->id }}" class="h-3 w-3 rounded-none border-slate-300"> {{ $u->name }}
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="mt-4">
                <label class="block text-xs font-medium text-slate-700 mb-1">Watchers <span class="text-slate-400 font-normal">(visibility only, no responsibility)</span></label>
                <div class="flex flex-wrap gap-1.5 max-h-20 overflow-y-auto border border-slate-200 p-2 bg-white">
                    @foreach($users as $u)
                        <label class="inline-flex items-center gap-1 rounded-none border border-slate-200 bg-white px-2 py-1 text-[11px] font-medium text-slate-600 cursor-pointer hover:bg-slate-50">
                            <input type="checkbox" name="watchers[]" value="{{ $u->id }}" class="h-3 w-3 rounded-none border-slate-300"> {{ $u->name }}
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        <div>
            <p class="text-[11px] font-semibold tracking-wide text-slate-400 uppercase mb-2">Schedule</p>
            <div class="grid sm:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Start Date & Time</label>
                    <input type="date" name="start_date" class="w-full rounded-none border border-slate-200 px-4 py-3 text-sm focus:border-slate-900 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Due Date & Time *</label>
                    <input type="date" name="due_at" required class="w-full rounded-none border border-slate-200 px-4 py-3 text-sm focus:border-slate-900 focus:outline-none">
                    <input type="time" name="due_time" class="w-full mt-2 rounded-none border border-slate-200 px-4 py-2.5 text-sm focus:border-slate-900 focus:outline-none" placeholder="Time">
                    <label class="mt-1.5 inline-flex items-center gap-1.5 text-xs text-slate-600 cursor-pointer"><input type="checkbox" name="all_day" value="1" class="accent-slate-900"> All day</label>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Estimated Duration (minutes)</label>
                    <input type="number" name="estimated_duration_minutes" min="0" max="10080" placeholder="e.g. 120" class="w-full rounded-none border border-slate-200 px-4 py-3 text-sm focus:border-slate-900 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Recurring Task</label>
                    <select name="repeat_interval" class="w-full rounded-none border border-slate-200 bg-white px-4 py-3 text-sm focus:border-slate-900 focus:outline-none">
                        <option value="">None</option>
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                        <option value="quarterly">Quarterly</option>
                        <option value="custom">Custom</option>
                    </select>
                    <input type="date" name="repeat_until" placeholder="Repeat until" class="w-full mt-2 rounded-none border border-slate-200 px-4 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                </div>
            </div>
        </div>

        <div>
            <label class="block text-xs font-medium text-slate-700 mb-1">Instructions * <span class="text-slate-400 font-normal">— explain exactly what needs to be done, procedures, background</span></label>
            <textarea name="instructions" rows="3" required placeholder="Explain exactly what needs to be done, relevant background, required procedures, and any important instructions." class="w-full rounded-none border border-slate-200 px-4 py-3 text-sm focus:border-slate-900 focus:outline-none"></textarea>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-700 mb-1">Expected Outcome * <span class="text-slate-400 font-normal">— prevents differing interpretations of done</span></label>
            <textarea name="expected_outcome" rows="2" required placeholder="e.g. Customer contacted, quotation discussed, customer response recorded and next action identified." class="w-full rounded-none border border-slate-200 px-4 py-3 text-sm focus:border-slate-900 focus:outline-none"></textarea>
        </div>
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Related Record <span class="text-slate-400 font-normal">(connect to existing ERP record)</span></label>
                <select id="relatedKind" onchange="document.querySelectorAll('.related-field').forEach(e=>e.classList.add('hidden'));const v=this.value;if(v)document.getElementById('related-'+v)?.classList.remove('hidden');" class="w-full rounded-none border border-slate-200 bg-white px-4 py-3 text-sm focus:border-slate-900 focus:outline-none">
                    <option value="">None</option>
                    <option value="customer">Customer</option>
                    <option value="lead">Lead</option>
                    <option value="quotation">Quotation</option>
                    <option value="order">Sales Order / Invoice</option>
                    <option value="product">Product</option>
                </select>
            </div>
            <div>
                <div id="related-customer" class="related-field hidden">
                    <label class="block text-xs font-medium text-slate-700 mb-1">Customer Name</label>
                    <input type="text" name="customer_name" placeholder="Customer name — e.g. ABC Construction" class="w-full rounded-none border border-slate-200 px-4 py-3 text-sm focus:border-slate-900 focus:outline-none">
                </div>
                <div id="related-lead" class="related-field hidden">
                    <label class="block text-xs font-medium text-slate-700 mb-1">Lead</label>
                    <select name="lead_id" class="w-full rounded-none border border-slate-200 bg-white px-4 py-3 text-sm focus:border-slate-900 focus:outline-none"><option value="">Select lead —</option>@foreach($leads as $l)<option value="{{ $l->id }}">{{ $l->name ?? ('Lead #'.$l->id) }}</option>@endforeach</select>
                </div>
                <div id="related-quotation" class="related-field hidden">
                    <label class="block text-xs font-medium text-slate-700 mb-1">Quotation</label>
                    <select name="quotation_id" class="w-full rounded-none border border-slate-200 bg-white px-4 py-3 text-sm focus:border-slate-900 focus:outline-none"><option value="">Select quotation — e.g. QT-00231</option>@foreach($quotations as $q)<option value="{{ $q->id }}">{{ $q->quote_number ?? ('QT-'.$q->id) }}</option>@endforeach</select>
                </div>
                <div id="related-order" class="related-field hidden">
                    <label class="block text-xs font-medium text-slate-700 mb-1">Sales Order / Invoice</label>
                    <select name="sales_order_id" class="w-full rounded-none border border-slate-200 bg-white px-4 py-3 text-sm focus:border-slate-900 focus:outline-none"><option value="">Select order —</option>@foreach($orders as $o)<option value="{{ $o->id }}">{{ $o->receipt_number ?? ('SO-'.$o->id) }}</option>@endforeach</select>
                </div>
                <div id="related-product" class="related-field hidden">
                    <label class="block text-xs font-medium text-slate-700 mb-1">Product</label>
                    <select name="product_id" class="w-full rounded-none border border-slate-200 bg-white px-4 py-3 text-sm focus:border-slate-900 focus:outline-none"><option value="">Select product —</option>@foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select>
                </div>
            </div>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-700 mb-1">Checklist <span class="text-slate-400 font-normal">(one per line) — e.g. Call customer, Confirm quotation, Discuss delivery date</span></label>
            <textarea name="checklist_items" rows="2" placeholder="Call customer&#10;Confirm quotation&#10;Discuss delivery date&#10;Record customer response" class="w-full rounded-none border border-slate-200 px-4 py-3 text-sm focus:border-slate-900 focus:outline-none"></textarea>
        </div>
        <div x-data="{ files: null, names: '' }">
            <label class="block text-xs font-medium text-slate-700 mb-1">Attachments <span class="text-slate-400 font-normal">(PDF, Images, Excel, Documents — real upload, max 10MB each)</span></label>
            <input type="file" name="attachments[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.zip,.txt,.csv" class="w-full rounded-none border border-slate-200 bg-white px-5 py-3 text-sm file:mr-3 file:rounded-none file:border-0 file:bg-slate-900 file:px-3 file:py-1 file:text-xs file:font-medium file:text-white" @change="files = $event.target.files; names = [...files].map(f => f.name + ' (' + (f.size/1024).toFixed(0) + ' KB)').join(', ');">
            <p x-show="names" x-text="names" class="mt-2 text-xs text-emerald-700 bg-emerald-50 border border-emerald-200 px-3 py-2"></p>
            <p class="text-[11px] text-slate-400 mt-1">Selected files will be stored permanently in <code>storage/app/public/task_attachments</code> and you will get a green success banner with file names after creation.</p>
        </div>
        <div class="flex justify-end gap-3 pt-2 border-t border-slate-100">
            <button type="button" @click="showCreate = false" class="rounded-none border border-slate-200 bg-white px-5 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</button>
            <button type="submit" name="save_action" value="draft" class="rounded-none border border-slate-900 bg-white px-5 py-2 text-sm font-medium text-slate-900 hover:bg-slate-50">Save Draft</button>
            <button type="submit" name="save_action" value="assign" class="rounded-none bg-slate-900 px-5 py-2 text-sm font-medium text-white hover:bg-black">Create & Assign</button>
        </div>
    </form>

    {{-- Attention required --}}
    @if($attention->count())
    <div class="bg-white rounded-none border border-rose-200 p-4 mb-8">
        <h2 class="text-xs font-semibold tracking-wide text-rose-700 uppercase">Attention required — blocked / overdue</h2>
        <div class="mt-2 space-y-2">
            @foreach($attention as $t)
            <div class="flex items-center justify-between gap-3 text-sm">
                <div class="min-w-0"><span class="font-medium text-slate-900">{{ $t->assignee?->name ?? 'Unassigned' }}</span><span class="text-slate-500"> — {{ Str::limit($t->title, 40) }}</span> <span class="text-[11px] px-1.5 py-0.5 border {{ $t->status==='blocked' ? 'bg-rose-50 border-rose-200 text-rose-700' : 'bg-amber-50 border-amber-200 text-amber-700' }}">{{ ucfirst($t->status) }}</span></div>
                <span class="text-[11px] text-rose-600 font-medium shrink-0">{{ $t->due_at ? 'Due '.$t->due_at->format('d M') : ucfirst($t->status) }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Team performance (spec section 18) --}}
    <div class="bg-white rounded-none border border-slate-200 overflow-hidden mb-8" x-show="section!=='reports' && section!=='templates' && view!=='calendar'">
        <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Team task performance</h2>
            <a href="#" @click.prevent="section='reports'; tab='reports'" class="text-[11px] text-indigo-600 hover:underline font-medium">View Reports →</a>
        </div>
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 text-[11px] font-medium text-slate-500"><tr><th class="px-5 py-3">Employee</th><th class="px-5 py-3 text-right">Assigned</th><th class="px-5 py-3 text-right">Completed</th><th class="px-5 py-3 text-right">Overdue</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($teamPerformance as $row)
                <tr><td class="px-5 py-3 font-medium text-slate-900">{{ $row['name'] }}</td><td class="px-5 py-3 text-right font-mono">{{ $row['assigned'] }}</td><td class="px-5 py-3 text-right font-mono text-emerald-700">{{ $row['completed'] }}</td><td class="px-5 py-3 text-right font-mono {{ $row['overdue'] ? 'text-rose-600' : '' }}">{{ $row['overdue'] }}</td></tr>
                @empty
                <tr><td colspan="4" class="px-4 py-4 text-center text-slate-500">No assignments yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Reports section per spec 18 --}}
    <div x-show="section==='reports'" x-cloak class="space-y-6 mb-8">
        <div class="bg-white rounded-none border border-slate-200 p-6">
            <h2 class="text-sm font-semibold text-slate-900">Task Performance Reports</h2>
            <p class="text-xs text-slate-500 mt-1">Operational data for management interpretation — not automated performance judgment.</p>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-4">
                <div class="border border-slate-200 p-4 text-center"><div class="text-xl font-semibold text-slate-900">{{ $report['created_today'] ?? 0 }}</div><div class="text-[11px] text-slate-500">Created today</div></div>
                <div class="border border-slate-200 p-4 text-center"><div class="text-xl font-semibold text-emerald-700">{{ $report['completed_today'] ?? 0 }}</div><div class="text-[11px] text-slate-500">Completed today</div></div>
                <div class="border border-slate-200 p-4 text-center"><div class="text-xl font-semibold text-amber-600">{{ $report['blocked_count'] ?? 0 }}</div><div class="text-[11px] text-slate-500">Blocked</div></div>
                <div class="border border-slate-200 p-4 text-center"><div class="text-xl font-semibold text-slate-900">{{ $report['avg_completion_hours'] ?? '—' }}</div><div class="text-[11px] text-slate-500">Avg hours to complete</div></div>
            </div>
            <div class="grid sm:grid-cols-2 gap-6 mt-6">
                <div>
                    <h3 class="text-xs font-semibold tracking-wide text-slate-500 uppercase mb-2">Employee Workload — Active / Completed / Overdue</h3>
                    <table class="w-full text-left text-xs border border-slate-200">
                        <thead class="bg-slate-50 text-[11px] text-slate-500"><tr><th class="px-3 py-2">Employee</th><th class="px-3 py-2 text-right">Active</th><th class="px-3 py-2 text-right">Completed</th><th class="px-3 py-2 text-right">Overdue</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($teamPerformance as $row)
                            <tr><td class="px-3 py-2 font-medium">{{ $row['name'] }}</td><td class="px-3 py-2 text-right font-mono">{{ $row['assigned'] - $row['completed'] }}</td><td class="px-3 py-2 text-right font-mono text-emerald-700">{{ $row['completed'] }}</td><td class="px-3 py-2 text-right font-mono {{ $row['overdue']?'text-rose-600':'' }}">{{ $row['overdue'] }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div>
                    <h3 class="text-xs font-semibold tracking-wide text-slate-500 uppercase mb-2">Department Performance</h3>
                    <table class="w-full text-left text-xs border border-slate-200">
                        <thead class="bg-slate-50 text-[11px] text-slate-500"><tr><th class="px-3 py-2">Department</th><th class="px-3 py-2 text-right">Total</th><th class="px-3 py-2 text-right">Completed</th><th class="px-3 py-2 text-right">Overdue</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($deptPerformance as $d)
                            <tr><td class="px-3 py-2 font-medium">{{ $d['name'] }}</td><td class="px-3 py-2 text-right font-mono">{{ $d['total'] }}</td><td class="px-3 py-2 text-right font-mono text-emerald-700">{{ $d['completed'] }}</td><td class="px-3 py-2 text-right font-mono {{ $d['overdue']?'text-rose-600':'' }}">{{ $d['overdue'] }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Bulk creation + Templates toggle --}}
    <div class="flex gap-3 mb-8" x-show="section!=='reports' && view!=='calendar'">
        <button @click="showBulk = !showBulk; showTemplateForm = false" class="rounded-none border border-slate-200 bg-white px-5 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50" x-text="showBulk ? 'Close bulk' : '+ Bulk create'"></button>
        <button @click="showTemplateForm = !showTemplateForm; showBulk = false" class="rounded-none border border-slate-200 bg-white px-5 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50" x-text="showTemplateForm ? 'Close templates' : 'Task Templates'"></button>
    </div>

    {{-- BULK TASK CREATION --}}
    <form action="{{ route('manager.tasks.bulk') }}" method="POST" x-show="showBulk" x-cloak class="bg-white rounded-none border border-slate-200 p-6 mb-8 space-y-4">
        @csrf
        <h2 class="text-sm font-semibold text-slate-900">Bulk task creation</h2>
        <div class="grid sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Department</label>
                <select name="department_id" class="w-full rounded-none border border-slate-200 bg-white px-4 py-3 text-sm focus:border-slate-900 focus:outline-none">
                    <option value="">Select —</option>
                    @foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Priority</label>
                <select name="priority" class="w-full rounded-none border border-slate-200 bg-white px-4 py-3 text-sm focus:border-slate-900 focus:outline-none">
                    <option value="low">Low</option>
                    <option value="medium" selected>Normal</option>
                    <option value="high">High</option>
                    <option value="urgent">Urgent</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Due Date</label>
                <input type="date" name="due_at" class="w-full rounded-none border border-slate-200 px-4 py-3 text-sm focus:border-slate-900 focus:outline-none">
            </div>
        </div>
        <div class="space-y-2">
            <div class="grid grid-cols-[1fr_180px_32px] gap-3 text-[11px] font-medium text-slate-500 px-1"><span>Task</span><span>Assigned</span><span></span></div>
            <template x-for="(row, i) in bulkRows" :key="i">
                <div class="grid grid-cols-[1fr_180px_32px] gap-3">
                    <input type="text" :name="'tasks['+i+'][title]'" x-model="row.title" required placeholder="Task title" class="rounded-none border border-slate-200 px-5 py-3 text-sm focus:border-slate-900 focus:outline-none">
                    <select :name="'tasks['+i+'][assigned_to]'" x-model="row.assigned_to" class="rounded-none border border-slate-200 bg-white px-2 py-2 text-xs focus:border-slate-900 focus:outline-none">
                        <option value="">Unassigned</option>
                        @foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
                    </select>
                    <button type="button" @click="bulkRows.splice(i,1)" class="h-9 w-8 rounded-none border border-transparent hover:border-rose-200 hover:bg-rose-50 text-slate-400 hover:text-rose-600 flex items-center justify-center" title="Remove row">
                        <span class="material-symbols-outlined text-[16px]">delete</span>
                    </button>
                </div>
            </template>
        </div>
        <div class="flex items-center justify-between">
            <button type="button" @click="bulkRows.push({title:'', assigned_to:''})" class="text-xs font-medium text-slate-600 hover:text-slate-900">+ Add row</button>
            <button type="submit" class="rounded-none bg-slate-900 px-5 py-2 text-sm font-medium text-white hover:bg-black" x-text="'Create ' + bulkRows.length + ' Tasks'"></button>
        </div>
    </form>

    {{-- TEMPLATES (spec section 12) --}}
    <div x-show="showTemplateForm || section==='templates'" x-cloak class="space-y-4 mb-8">
        <div class="bg-white rounded-none border border-slate-200 p-6">
            <h2 class="text-sm font-semibold text-slate-900">Task Templates</h2>
            <p class="text-xs text-slate-500 mt-1">Manager can create templates like <em>Daily Sales Follow-up, New Employee Onboarding, Monthly Stock Count</em>. <strong>Create from Template</strong> generates checklist, instructions and expected outcome.</p>
            <div class="mt-3 divide-y divide-slate-100 border border-slate-100 rounded-none overflow-hidden">
                @forelse($templates as $tpl)
                <div class="flex items-center gap-4 px-4 py-3 hover:bg-slate-50">
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-medium text-slate-900">{{ $tpl->title }}</div>
                        <div class="text-[11px] text-slate-500">{{ $tpl->task_type ?? 'general' }} • {{ $tpl->priority }} • {{ $tpl->default_duration_days }} day(s) @if($tpl->checklist_items) • {{ count(preg_split('/\r\n|\r|\n/', $tpl->checklist_items)) }} checklist items @endif</div>
                        @if($tpl->instructions)<div class="text-[11px] text-slate-400 truncate">{{ Str::limit($tpl->instructions, 80) }}</div>@endif
                    </div>
                    <form action="{{ route('manager.task-templates.use', $tpl->id) }}" method="POST" class="flex items-center gap-1.5">
                        @csrf
                        <select name="assigned_to" class="rounded-none border border-slate-200 bg-white px-2 py-1.5 text-[11px] focus:border-slate-900 focus:outline-none">
                            <option value="">Assign to —</option>
                            @foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
                        </select>
                        <button type="submit" class="rounded-none bg-emerald-600 px-3 py-1.5 text-[11px] font-medium text-white hover:bg-emerald-700">Create from Template</button>
                    </form>
                    <form id="delete-template-form-{{ $tpl->id }}" action="{{ route('manager.task-templates.destroy', $tpl->id) }}" method="POST">
                        @csrf @method('DELETE')
                        <button type="button" @click="openTemplateDelete({{ $tpl->id }}, @js($tpl->title))" class="h-7 w-7 rounded-none border border-transparent hover:border-rose-200 hover:bg-rose-50 text-slate-400 hover:text-rose-600 flex items-center justify-center" title="Delete template">
                            <span class="material-symbols-outlined text-[16px]">delete</span>
                        </button>
                    </form>
                </div>
                @empty
                <p class="px-3 py-4 text-center text-xs text-slate-500">No templates yet — save one below.</p>
                @endforelse
            </div>
        </div>
        <form action="{{ route('manager.task-templates.store') }}" method="POST" class="bg-white rounded-none border border-slate-200 p-6 space-y-3">
            @csrf
            <h3 class="text-sm font-semibold text-slate-900">New template</h3>
            <div class="grid sm:grid-cols-2 gap-4">
                <input type="text" name="title" required placeholder="e.g. Daily Sales Follow-up, Monthly Stock Count" class="rounded-none border border-slate-200 px-4 py-3 text-sm font-medium focus:border-slate-900 focus:outline-none">
                <select name="task_type" class="rounded-none border border-slate-200 bg-white px-4 py-3 text-sm focus:border-slate-900 focus:outline-none">
                    <option value="general">General</option>
                    @foreach($taskTypes as $group => $types)
                        <optgroup label="{{ $group }}">@foreach($types as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach</optgroup>
                    @endforeach
                </select>
            </div>
            <textarea name="instructions" rows="2" placeholder="Default instructions: Contact assigned customers and update the CRM with the result." class="w-full rounded-none border border-slate-200 px-4 py-3 text-sm focus:border-slate-900 focus:outline-none"></textarea>
            <textarea name="expected_outcome" rows="2" placeholder="Expected outcome: Customer contacted, response recorded, next action identified." class="w-full rounded-none border border-slate-200 px-4 py-3 text-sm focus:border-slate-900 focus:outline-none"></textarea>
            <textarea name="checklist_items" rows="2" placeholder="Checklist (one per line): Call customer&#10;Confirm quotation&#10;Discuss delivery date" class="w-full rounded-none border border-slate-200 px-4 py-3 text-sm focus:border-slate-900 focus:outline-none"></textarea>
            <div class="grid grid-cols-3 gap-4">
                <select name="priority" class="rounded-none border border-slate-200 bg-white px-4 py-3 text-sm focus:border-slate-900 focus:outline-none">
                    <option value="low">Low</option>
                    <option value="medium" selected>Normal</option>
                    <option value="high">High</option>
                    <option value="urgent">Urgent</option>
                    <option value="critical">Critical</option>
                </select>
                <select name="department_id" class="rounded-none border border-slate-200 bg-white px-4 py-3 text-sm focus:border-slate-900 focus:outline-none">
                    <option value="">Department —</option>
                    @foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                </select>
                <input type="number" name="default_duration_days" min="1" max="90" value="1" title="Default duration (days)" class="rounded-none border border-slate-200 px-4 py-3 text-sm focus:border-slate-900 focus:outline-none">
            </div>
            <div class="flex justify-end">
                <button type="submit" class="rounded-none bg-slate-900 px-5 py-2 text-sm font-medium text-white hover:bg-black">Save template</button>
            </div>
        </form>
    </div>

    {{-- Task list — ERP table per spec section 8 --}}
    <div class="bg-white rounded-none border border-slate-200 overflow-hidden" x-show="view==='list' && section!=='reports' && section!=='templates'">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-[11px] font-medium text-slate-500"><tr><th class="px-5 py-3">Task</th><th class="px-5 py-3">Department</th><th class="px-5 py-3">Responsible</th><th class="px-5 py-3">Priority</th><th class="px-5 py-3">Due</th><th class="px-5 py-3">Progress</th><th class="px-5 py-3">Status</th><th class="px-5 py-3 text-right">View</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($tasks as $t)
                    @php
                        $tArchived = in_array($t->status, ['closed','cancelled','verified']);
                        $tMine = (int)($t->assigned_to ?? 0) === (int)auth()->id();
                        $tDue = !$t->due_at ? 'none' : ($t->due_at->isPast() && !in_array($t->status, ['verified','closed','cancelled']) ? 'overdue' : ($t->due_at->isToday() ? 'today' : ($t->due_at->isCurrentWeek() ? 'week' : 'later')));
                        $tIsPendingReview = in_array($t->status, ['submitted','completed','under_review']) && is_null($t->verified_at);
                    @endphp
                    <tr class="hover:bg-slate-50"
                        data-archived="{{ $tArchived ? '1' : '0' }}"
                        x-show="(tab==='all' || (tab==='my' && {{ $tMine ? 'true':'false' }}) || (tab==='team' && !{{ $tMine ? 'true':'false' }} && !{{ $tArchived ? 'true':'false' }}) || (tab==='pending_review' && {{ $tIsPendingReview ? 'true':'false' }}) || (tab==='waiting' && ['waiting','blocked'].includes('{{ $t->status }}')) || (tab==='completed' && ['verified','closed'].includes('{{ $t->status }}'))) && (fDept==='all' || '{{ $t->department_id }}'===fDept) && (fAssignee==='all' || '{{ $t->assigned_to }}'===fAssignee) && (fType==='all' || @js($t->task_type ?? 'general')===fType) && (fPriority==='all' || '{{ $t->priority }}'===fPriority) && (fStatus==='all' || '{{ $t->status }}'===fStatus) && matchesDue('{{ $tDue }}', {{ $t->due_at && $t->due_at->isPast() && !in_array($t->status, ['verified','closed','cancelled']) ? 'true' : 'false' }}) && (@js(strtolower($t->title).' '.strtolower($t->assignee?->name ?? '').' '.strtolower($t->customer_name ?? '')).toLowerCase().includes(search.toLowerCase()))">
                        <td class="px-5 py-3 font-medium text-slate-900">{{ Str::limit($t->title, 36) }} @if($t->customer_name)<span class="text-slate-400 font-normal">· {{ Str::limit($t->customer_name,16) }}</span>@endif</td>
                        <td class="px-5 py-3 text-slate-600">{{ $t->department?->name ?? $t->department ?? '—' }}</td>
                        <td class="px-5 py-3 text-slate-700">{{ $t->assignee?->name ?? '—' }} @if($t->collaborators->count())<span class="text-[10px] text-slate-400">+{{ $t->collaborators->count() }}</span>@endif</td>
                        <td class="px-5 py-3"><span class="inline-flex rounded-none border px-2 py-0.5 text-[11px] {{ in_array($t->priority,['critical','urgent']) ? 'bg-rose-50 border-rose-200 text-rose-700' : ($t->priority==='high' ? 'bg-amber-50 border-amber-200 text-amber-700' : 'border-slate-200 bg-slate-50 text-slate-600') }}">{{ ucfirst($t->priority ?? 'medium') }}</span></td>
                        <td class="px-5 py-3 font-mono text-slate-600">{{ $t->due_at ? $t->due_at->format('d M') : '—' }} @if($t->due_at && $t->due_at->isPast() && !in_array($t->status, ['verified','closed','cancelled']))<span class="text-rose-600 font-bold text-[10px]">OVERDUE</span>@endif</td>
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-1.5">
                                <div class="w-16 h-1.5 bg-slate-100 overflow-hidden"><div class="h-1.5 {{ $t->status==='blocked' ? 'bg-rose-600' : ($t->status==='waiting' ? 'bg-amber-500' : 'bg-slate-900') }}" style="width: {{ $t->progress ?? 0 }}%"></div></div>
                                <span class="font-mono text-[11px] text-slate-600">{{ $t->progress ?? 0 }}%</span>
                            </div>
                        </td>
                        <td class="px-5 py-3"><span class="inline-flex rounded-none border px-2 py-0.5 text-[11px] font-medium {{ $t->status==='blocked' ? 'border-rose-200 bg-rose-50 text-rose-700' : ($t->status==='waiting' ? 'border-amber-200 bg-amber-50 text-amber-700' : ($tIsPendingReview ? 'border-amber-200 bg-amber-50 text-amber-800' : 'border-slate-200 bg-white text-slate-700')) }}">{{ strtoupper(str_replace('_',' ',$t->status)) }}</span></td>
                        <td class="px-5 py-3 text-right"><a href="{{ route('manager.tasks.show', $t->id) }}" class="text-emerald-700 hover:underline font-medium">View</a></td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-slate-500">No tasks yet — create one above. Customer Tickets remain separate.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Board view --}}
    <div x-show="view==='board'" x-cloak class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4">
        @foreach(['assigned' => 'Assigned', 'in_progress' => 'In Progress', 'waiting' => 'Waiting / Blocked', 'review' => 'Pending Review'] as $col => $colLabel)
        <div class="bg-slate-50 border border-slate-200">
            <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between bg-white">
                <span class="text-xs font-semibold tracking-wide text-slate-600 uppercase">{{ $colLabel }}</span>
                <span class="text-[11px] font-mono text-slate-500">{{ $tasks->filter(fn($x) => $col==='assigned' ? in_array($x->status,['draft','assigned','accepted','pending']) : ($col==='in_progress' ? $x->status==='in_progress' : ($col==='waiting' ? in_array($x->status,['waiting','blocked']) : in_array($x->status,['submitted','under_review']) || ($x->status==='completed' && is_null($x->verified_at)))))->count() }}</span>
            </div>
            <div class="p-3 space-y-3">
                @forelse($tasks->filter(fn($x) => $col==='assigned' ? in_array($x->status,['draft','assigned','accepted','pending']) : ($col==='in_progress' ? $x->status==='in_progress' : ($col==='waiting' ? in_array($x->status,['waiting','blocked']) : in_array($x->status,['submitted','under_review']) || ($x->status==='completed' && is_null($x->verified_at))))) as $t)
                <a href="{{ route('manager.tasks.show', $t->id) }}" class="block bg-white border border-slate-200 p-3 hover:border-slate-400">
                    <div class="text-xs font-medium text-slate-900 leading-snug">{{ Str::limit($t->title, 60) }}</div>
                    <div class="flex items-center justify-between mt-2">
                        <span class="text-[11px] text-slate-500">{{ $t->assignee?->name ?? 'Unassigned' }} • {{ $t->task_type ?? 'general' }}</span>
                        <span class="text-[11px] font-mono {{ $t->due_at && $t->due_at->isPast() ? 'text-rose-600 font-semibold' : 'text-slate-400' }}">{{ $t->due_at ? $t->due_at->format('d M') : '—' }}</span>
                    </div>
                </a>
                @empty
                <p class="text-[11px] text-slate-400 text-center py-4">Empty</p>
                @endforelse
            </div>
        </div>
        @endforeach
    </div>

    {{-- Calendar view (spec section 5) --}}
    <div x-show="view==='calendar'" x-cloak class="bg-white border border-slate-200">
        <div class="flex items-center justify-between px-5 py-3 border-b border-slate-100">
            <a href="{{ route('manager.tasks', ['view' => 'calendar', 'month' => $monthDate->copy()->subMonth()->format('Y-m')]) }}" class="px-3 py-1.5 border border-slate-200 text-xs font-medium text-slate-700 hover:bg-slate-50">← Prev</a>
            <span class="text-sm font-semibold text-slate-900">{{ $monthDate->format('F Y') }}</span>
            <a href="{{ route('manager.tasks', ['view' => 'calendar', 'month' => $monthDate->copy()->addMonth()->format('Y-m')]) }}" class="px-3 py-1.5 border border-slate-200 text-xs font-medium text-slate-700 hover:bg-slate-50">Next →</a>
        </div>
        <div class="grid grid-cols-7 text-center text-[11px] font-medium text-slate-500 border-b border-slate-100">
            @foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $d)<div class="py-2">{{ $d }}</div>@endforeach
        </div>
        @foreach($calendarWeeks as $week)
        <div class="grid grid-cols-7 border-b border-slate-100 last:border-0">
            @foreach($week as $day)
            <div class="min-h-[84px] p-1.5 border-r border-slate-100 last:border-0 {{ $day['inMonth'] ? '' : 'bg-slate-50' }}">
                <div class="text-[11px] font-mono {{ $day['date']->isToday() ? 'font-bold text-slate-900' : 'text-slate-400' }}">{{ $day['date']->format('j') }}</div>
                @foreach($day['tasks'] as $t)
                <a href="{{ route('manager.tasks.show', $t->id) }}" title="{{ $t->title }}" class="block truncate text-[11px] px-1 py-0.5 mt-0.5 border-l-2 {{ in_array($t->status,['verified','closed']) ? 'border-emerald-500 text-slate-400 line-through' : ($t->status==='blocked' ? 'border-rose-500 text-rose-700' : 'border-slate-900 text-slate-700') }} bg-white hover:bg-slate-50">{{ Str::limit($t->title, 18) }}</a>
                @endforeach
                @if($day['count'] > $day['tasks']->count())
                <div class="text-[10px] text-slate-400 px-1">+{{ $day['count'] - $day['tasks']->count() }} more</div>
                @endif
            </div>
            @endforeach
        </div>
        @endforeach
    </div>

    {{-- Delete template Carbon modal --}}
    <div x-show="showTemplateDeleteModal" x-cloak @keydown.escape.window="closeTemplateDelete()" style="position:fixed; inset:0; z-index:9000; display:flex; align-items:center; justify-content:center; background:rgba(22,22,22,0.5); padding:1rem;">
      <div x-trap.noscroll="showTemplateDeleteModal" role="dialog" aria-modal="true" aria-labelledby="delete-template-title" class="cds--modal" style="background:#fff; border:1px solid #e0e0e0; width:100%; max-width:28rem; display:flex; flex-direction:column; box-shadow:0 8px 32px rgba(0,0,0,0.2);" @click.away="closeTemplateDelete()">
        <div style="padding:1rem 1.25rem; background:#f4f4f4; border-bottom:1px solid #e0e0e0; display:flex; align-items:center; justify-content:space-between;">
          <h3 id="delete-template-title" style="font-size:1rem; font-weight:600; color:#161616; font-family:'IBM Plex Sans',sans-serif;">Delete template?</h3>
          <button @click="closeTemplateDelete()" type="button" style="width:2rem; height:2rem; background:transparent; border:0; display:flex; align-items:center; justify-content:center; color:#525252; cursor:pointer;" aria-label="Close"><span class="material-symbols-outlined" style="font-size:18px;">close</span></button>
        </div>
        <div style="padding:1.25rem; background:#fff;">
          <p style="font-size:0.875rem; color:#525252; font-family:'IBM Plex Sans',sans-serif;">Delete <span style="font-weight:600; color:#161616;" x-text="pendingTemplateTitle ? '“'+pendingTemplateTitle+'”' : 'this template'"></span>? This cannot be undone.</p>
        </div>
        <div style="padding:1rem 1.25rem; background:#f4f4f4; border-top:1px solid #e0e0e0; display:flex; justify-content:flex-end; gap:0.5rem;">
          <button x-ref="cancelTemplateBtn" @click="closeTemplateDelete()" type="button" style="height:2.25rem; padding:0 1rem; background:#fff; border:1px solid #8d8d8d; color:#161616; font-size:0.875rem; font-weight:600; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">Cancel</button>
          <button @click="confirmTemplateDelete()" type="button" style="height:2.25rem; padding:0 1rem; background:#da1e28; border:1px solid #da1e28; color:#fff; font-size:0.875rem; font-weight:600; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">Delete</button>
        </div>
      </div>
    </div>
</div>
@endsection
