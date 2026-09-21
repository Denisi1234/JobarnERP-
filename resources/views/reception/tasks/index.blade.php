@extends('reception.layout')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<link href="https://unpkg.com/carbon-components@11.42.0/css/carbon-components.min.css" rel="stylesheet">
<style>
  [x-cloak]{display:none!important}
  .cds--tile, .cds--data-table-container, .cds--btn, .cds--tag, .cds--search-input { border-radius:0 !important; }
  .ibm-grid { max-width:1584px; margin:0 auto; padding:0 1rem; }
  @media(min-width:66rem){ .ibm-grid{ padding:0 2rem; } }
  .ibm-hgrid-4 { display:grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap:1rem; margin-bottom:1rem; }
  @media(max-width:66rem){ .ibm-hgrid-4 { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
  @media(max-width:42rem){ .ibm-hgrid-4 { grid-template-columns: 1fr; } }
</style>

<div class="cds--content" style="background:#f4f4f4; min-height:100vh; margin:-1.5rem -1.5rem 0 -1.5rem; padding:0 0 2rem 0;" x-data="{ filter: 'all', search: '', priorityFilter: 'all', assigneeFilter: 'all', sortBy: 'newest', showDeleteModal: false, pendingDeleteId: null, pendingDeleteTitle: '', openDeleteModal(id, title){ this.pendingDeleteId=id; this.pendingDeleteTitle=title; this.showDeleteModal=true; this.$nextTick(()=>{ this.$refs.cancelDeleteBtn && this.$refs.cancelDeleteBtn.focus(); }); }, closeDeleteModal(){ this.showDeleteModal=false; }, confirmDelete(){ if(this.pendingDeleteId){ let f=document.getElementById('delete-todo-form-'+this.pendingDeleteId); if(f) f.submit(); } } }">

<div style="background:#ffffff; border-bottom:1px solid #e0e0e0;">
  <div class="ibm-grid" style="padding-top:1rem; padding-bottom:0;">
    <nav class="cds--breadcrumb" aria-label="Breadcrumb" style="margin-bottom:0.75rem; display:flex; gap:0.5rem; font-size:0.75rem; color:#525252;">
      <div class="cds--breadcrumb-item"><a class="cds--link" href="{{ route('sales.index') }}" style="color:#0f62fe; text-decoration:none;">Sales</a></div>
      <div class="cds--breadcrumb-item"><span style="color:#8d8d8d;">/</span> <span style="color:#161616; margin-left:0.5rem;">Tasks</span></div>
    </nav>
    <div style="display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:1rem; padding-bottom:1rem;">
      <div>
        <h1 style="font-size:2rem; font-weight:400; color:#161616; line-height:1.25; margin:0; font-family:'IBM Plex Sans',sans-serif; letter-spacing:-0.02em;">Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }}, {{ explode(' ', auth()->user()->name ?? 'there')[0] }}</h1>
        <p style="font-size:0.875rem; color:#525252; margin:0.375rem 0 0 0; max-width:42rem; line-height:1.5; font-family:'IBM Plex Sans',sans-serif;">What do you need to do today? — Carbon task management</p>
      </div>
      <div style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap;">
        <span style="display:inline-flex; align-items:center; gap:0.375rem; background:#fff; border:1px solid #e0e0e0; padding:0.375rem 0.75rem; font-size:0.75rem; font-weight:600; color:#525252; font-family:'IBM Plex Sans',sans-serif;">
          <span style="width:0.5rem; height:0.5rem; background:#e9730c;"></span> {{ $tasks->where('status','!=','completed')->count() }} pending
        </span>
        <span style="display:inline-flex; align-items:center; gap:0.375rem; background:#defbe6; border:1px solid #a7f0ba; padding:0.375rem 0.75rem; font-size:0.75rem; font-weight:600; color:#044317; font-family:'IBM Plex Sans',sans-serif;">
          <span style="width:0.5rem; height:0.5rem; background:#24a148;"></span> {{ $tasks->where('status','completed')->count() }} done
        </span>
        <a href="{{ route('reception.tasks.export') }}" style="display:inline-flex; align-items:center; gap:0.375rem; background:#fff; border:1px solid #8d8d8d; padding:0.375rem 0.75rem; font-size:0.75rem; font-weight:600; color:#161616; text-decoration:none; font-family:'IBM Plex Sans',sans-serif;">
          <span class="material-symbols-outlined" style="font-size:14px;">download</span> Export
        </a>
      </div>
    </div>
  </div>
</div>

<div class="ibm-grid" style="padding-top:1.5rem;">

  <x-success-popup :message="session('success')" />

  {{-- IBM KPI Tiles — 4 --}}
  <div class="ibm-hgrid-4">
    <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:1.25rem; text-align:center;">
      <div style="font-size:1.5rem; font-weight:600; font-family:'IBM Plex Mono',monospace; color:#161616;">{{ $myWork['today'] }}</div>
      <div style="font-size:0.75rem; font-weight:600; letter-spacing:0.04em; text-transform:uppercase; color:#525252; margin-top:0.25rem; font-family:'IBM Plex Sans',sans-serif;">Today</div>
    </div>
    <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:1.25rem; text-align:center;">
      <div style="font-size:1.5rem; font-weight:600; font-family:'IBM Plex Mono',monospace; color:{{ $myWork['overdue'] ? '#da1e28' : '#161616' }};">{{ $myWork['overdue'] }}</div>
      <div style="font-size:0.75rem; font-weight:600; letter-spacing:0.04em; text-transform:uppercase; color:#525252; margin-top:0.25rem;">Overdue</div>
    </div>
    <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:1.25rem; text-align:center;">
      <div style="font-size:1.5rem; font-weight:600; font-family:'IBM Plex Mono',monospace; color:#161616;">{{ $myWork['in_progress'] }}</div>
      <div style="font-size:0.75rem; font-weight:600; letter-spacing:0.04em; text-transform:uppercase; color:#525252; margin-top:0.25rem;">In Progress</div>
    </div>
    <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:1.25rem; text-align:center;">
      <div style="font-size:1.5rem; font-weight:600; font-family:'IBM Plex Mono',monospace; color:#161616;">{{ $myWork['waiting'] }}</div>
      <div style="font-size:0.75rem; font-weight:600; letter-spacing:0.04em; text-transform:uppercase; color:#525252; margin-top:0.25rem;">Waiting</div>
    </div>
  </div>

  @if($priorityTasks->count())
  <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:0; margin-bottom:1rem;">
    <div style="padding:1rem; border-bottom:1px solid #e0e0e0; display:flex; align-items:center; justify-content:space-between;">
      <div style="font-size:0.875rem; font-weight:600; font-family:'IBM Plex Sans',sans-serif; color:#161616;">Priority tasks</div>
      <span style="background:#ffd7d9; border:1px solid #ffb3b8; color:#750e13; font-size:0.75rem; padding:0.125rem 0.5rem; font-family:'IBM Plex Sans',sans-serif; font-weight:600;">{{ $priorityTasks->count() }} urgent</span>
    </div>
    <div style="divide-y:1px solid #e0e0e0;">
      @foreach($priorityTasks as $pt)
      <a href="{{ route('reception.tasks.show', $pt->id) }}" style="display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:0.75rem 1rem; text-decoration:none; border-bottom:1px solid #e0e0e0; {{ $loop->even ? 'background:#f4f4f4;' : 'background:#fff;' }}">
        <div style="min-width:0; flex:1;">
          <div style="font-size:0.875rem; font-weight:600; color:#161616; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-family:'IBM Plex Sans',sans-serif;">{{ $pt->title }}</div>
          <div style="font-size:0.75rem; color:#525252; font-family:'IBM Plex Mono',monospace;">Due {{ $pt->due_at ? $pt->due_at->format('d M, H:i') : '—' }}</div>
        </div>
        <span style="background:{{ $pt->priority==='urgent'||$pt->priority==='critical' ? '#ffd7d9' : '#fff8e1' }}; border:1px solid {{ $pt->priority==='urgent'||$pt->priority==='critical' ? '#ffb3b8' : '#f1c21b' }}; color:{{ $pt->priority==='urgent'||$pt->priority==='critical' ? '#750e13' : '#684e00' }}; font-size:0.75rem; padding:0.125rem 0.5rem; font-family:'IBM Plex Sans',sans-serif; font-weight:600; display:inline-flex; align-items:center; gap:0.25rem; flex-shrink:0;">
          <span style="width:0.5rem; height:0.5rem; background:{{ $pt->priority==='urgent'||$pt->priority==='critical' ? '#da1e28' : '#e9730c' }};"></span>
          {{ ucfirst($pt->priority ?? 'medium') }}
        </span>
      </a>
      @endforeach
    </div>
  </div>
  @endif

  <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem;">
    <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:1.25rem;">
      <div style="font-size:0.75rem; font-weight:600; letter-spacing:0.04em; text-transform:uppercase; color:#525252; font-family:'IBM Plex Sans',sans-serif;">My workload</div>
      <div style="margin-top:1rem; display:flex; flex-direction:column; gap:1rem;">
        <div>
          <div style="display:flex; justify-content:space-between; font-size:0.75rem; color:#525252; margin-bottom:0.375rem; font-family:'IBM Plex Sans',sans-serif;"><span>Today</span><span style="font-family:'IBM Plex Mono',monospace; font-weight:600;">{{ $myWork['today_pct'] }}%</span></div>
          <div style="height:0.5rem; background:#e0e0e0; border:1px solid #e0e0e0;"><div style="height:100%; background:#0f62fe; width: {{ $myWork['today_pct'] }}%"></div></div>
        </div>
        <div>
          <div style="display:flex; justify-content:space-between; font-size:0.75rem; color:#525252; margin-bottom:0.375rem;"><span>This week</span><span style="font-family:'IBM Plex Mono',monospace; font-weight:600;">{{ $myWork['week_pct'] }}%</span></div>
          <div style="height:0.5rem; background:#e0e0e0; border:1px solid #e0e0e0;"><div style="height:100%; background:#0f62fe; width: {{ $myWork['week_pct'] }}%"></div></div>
        </div>
      </div>
    </div>
    <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:1.25rem;">
      <div style="font-size:0.75rem; font-weight:600; letter-spacing:0.04em; text-transform:uppercase; color:#525252;">Recent activity</div>
      <div style="margin-top:1rem; display:flex; flex-direction:column; gap:0.75rem;">
        @forelse($recentActivity as $a)
        <div style="display:flex; gap:0.75rem; font-size:0.875rem;">
          <div style="width:0.5rem; height:0.5rem; background:#0f62fe; margin-top:0.375rem; flex-shrink:0;"></div>
          <div style="flex:1; min-width:0;">
            <div style="font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif;"><span style="font-weight:600; color:#161616;">{{ $a->user?->name ?? 'System' }}</span> <span style="color:#525252;">{{ $a->status_from ? str_replace('_',' ',$a->status_from).' → ' : '' }}{{ str_replace('_',' ',$a->status_to) }}</span></div>
            <div style="font-size:0.75rem; color:#525252; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ Str::limit($a->task?->title ?? '', 48) }}</div>
          </div>
        </div>
        @empty
        <p style="font-size:0.875rem; color:#525252; font-family:'IBM Plex Sans',sans-serif;">No recent activity.</p>
        @endforelse
      </div>
    </div>
  </div>

  {{-- Add Todo — IBM Carbon --}}
  <form action="{{ route('reception.tasks.store') }}" method="POST" enctype="multipart/form-data" class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:1.25rem; margin-bottom:1rem;" x-data="{ showMore: false }">
    @csrf
    <div style="display:flex; gap:0.75rem;">
      <input type="text" name="title" required placeholder="What needs to be done?" style="flex:1; height:2.5rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#f4f4f4; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif;">
      <label style="height:2.5rem; width:2.5rem; background:#fff; border:1px solid #8d8d8d; display:flex; align-items:center; justify-content:center; color:#525252; cursor:pointer; flex-shrink:0;" title="Attach file">
        <span class="material-symbols-outlined" style="font-size:18px;">attach_file</span>
        <input type="file" name="attachments[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.zip,.txt" style="display:none;" onchange="this.nextElementSibling.textContent = this.files.length ? this.files.length + ' file(s)' : ''">
        <span style="display:none;"></span>
      </label>
      <button type="submit" style="height:2.5rem; padding:0 1.25rem; background:#0f62fe; color:#fff; border:1px solid #0f62fe; font-size:0.875rem; font-weight:600; cursor:pointer; font-family:'IBM Plex Sans',sans-serif; flex-shrink:0;">Add</button>
    </div>
    <div style="margin-top:0.75rem; display:flex; gap:0.5rem; flex-wrap:wrap;">
      <input type="text" name="description" placeholder="Details (optional)" style="flex:1; min-width:200px; height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif;">
      <select name="priority" style="height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif;">
        <option value="medium">Medium</option>
        <option value="low">Low</option>
        <option value="high">High</option>
        <option value="urgent">Urgent</option>
      </select>
      <select name="assigned_to" style="height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif;">
        <option value="">Responsible —</option>
        @foreach($users as $u)
          <option value="{{ $u->id }}">{{ $u->name }}</option>
        @endforeach
      </select>
      <button type="button" @click="showMore = !showMore" style="height:2rem; padding:0 0.75rem; background:#fff; border:1px solid #8d8d8d; color:#161616; font-size:0.875rem; font-weight:600; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;" x-text="showMore ? 'Less' : 'More'"></button>
    </div>
    <div x-show="showMore" x-cloak style="margin-top:1rem; padding-top:1rem; border-top:1px solid #e0e0e0; display:flex; flex-direction:column; gap:1rem;">
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem;">
        <textarea name="instructions" rows="2" placeholder="Instructions — what exactly should be done?" style="border:1px solid #8d8d8d; padding:0.75rem; font-size:0.875rem; background:#fff; font-family:'IBM Plex Sans',sans-serif;"></textarea>
        <textarea name="expected_outcome" rows="2" placeholder="Expected outcome — what should be achieved?" style="border:1px solid #8d8d8d; padding:0.75rem; font-size:0.875rem; background:#fff; font-family:'IBM Plex Sans',sans-serif;"></textarea>
      </div>
      <div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:0.5rem;">
        <select name="task_type" style="height:2rem; padding:0 0.5rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif;">
          <option value="general">Type: General</option>
          @foreach($taskTypes as $group => $types)
            <optgroup label="{{ $group }}">
              @foreach($types as $type)
                <option value="{{ $type }}">{{ $type }}</option>
              @endforeach
            </optgroup>
          @endforeach
        </select>
        <select name="department_id" style="height:2rem; padding:0 0.5rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem;">
          <option value="">Department —</option>
          @foreach($departments as $d)
            <option value="{{ $d->id }}">{{ $d->name }}</option>
          @endforeach
        </select>
        <input type="date" name="due_at" style="height:2rem; padding:0 0.5rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem;">
        <input type="time" name="due_time" style="height:2rem; padding:0 0.5rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem;">
      </div>
      <div>
        <label style="font-size:0.75rem; font-weight:600; color:#525252; font-family:'IBM Plex Sans',sans-serif;">Collaborators</label>
        <div style="display:flex; flex-wrap:wrap; gap:0.375rem; margin-top:0.5rem;">
          @foreach($users as $u)
            <label style="display:inline-flex; align-items:center; gap:0.375rem; background:#e0e0e0; border:1px solid #c6c6c6; padding:0.25rem 0.5rem; font-size:0.75rem; color:#161616; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">
              <input type="checkbox" name="collaborators[]" value="{{ $u->id }}" style="width:1rem; height:1rem; accent-color:#0f62fe;"> {{ $u->name }}
            </label>
          @endforeach
        </div>
      </div>
      <input type="text" name="customer_name" placeholder="Related customer (optional)" style="width:100%; height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif;">
    </div>
    <p style="font-size:0.75rem; color:#8d8d8d; margin-top:0.75rem; font-family:'IBM Plex Sans',sans-serif;">Attach PDF, Word, Excel, images — up to 10MB each</p>
  </form>

  {{-- Filters — IBM Carbon --}}
  <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:1rem; margin-bottom:1rem;">
    <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:1rem;">
      <div style="display:flex; align-items:center; gap:0.375rem; flex-wrap:wrap;">
        <button @click="filter='all'" :style="filter==='all' ? 'background:#0f62fe; color:#fff; border:1px solid #0f62fe;' : 'background:#fff; color:#525252; border:1px solid #e0e0e0;'" style="height:2rem; padding:0 0.75rem; font-size:0.8125rem; font-weight:600; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">All ({{ $tasks->count() }})</button>
        <button @click="filter='pending'" :style="filter==='pending' ? 'background:#0f62fe; color:#fff; border:1px solid #0f62fe;' : 'background:#fff; color:#525252; border:1px solid #e0e0e0;'" style="height:2rem; padding:0 0.75rem; font-size:0.8125rem; font-weight:600; cursor:pointer;">Pending ({{ $tasks->where('status','!=','completed')->count() }})</button>
        <button @click="filter='completed'" :style="filter==='completed' ? 'background:#0f62fe; color:#fff; border:1px solid #0f62fe;' : 'background:#fff; color:#525252; border:1px solid #e0e0e0;'" style="height:2rem; padding:0 0.75rem; font-size:0.8125rem; font-weight:600; cursor:pointer;">Done ({{ $tasks->where('status','completed')->count() }})</button>
      </div>
      <div style="position:relative;">
        <svg style="position:absolute; left:0.625rem; top:50%; transform:translateY(-50%); width:1rem; height:1rem; fill:#525252;" viewBox="0 0 32 32"><path d="M14 4A10 10 0 1 0 24 14A10 10 0 0 0 14 4zm0 18A8 8 0 1 1 22 14A8 8 0 0 1 14 22z"/><path d="M26.7 24.7L21.3 19.3L20 20.7l5.4 5.4z"/></svg>
        <input type="text" x-model="search" placeholder="Search todos..." style="height:2rem; padding:0 0.75rem 0 2rem; width:16rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif;">
      </div>
    </div>
    <div style="display:flex; flex-wrap:wrap; align-items:center; gap:0.75rem; margin-top:1rem; padding-top:1rem; border-top:1px solid #e0e0e0;">
      <span style="font-size:0.75rem; font-weight:600; color:#525252; font-family:'IBM Plex Sans',sans-serif;">Filter:</span>
      <select x-model="priorityFilter" style="height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif;">
        <option value="all">All priorities</option>
        <option value="urgent">Urgent</option>
        <option value="high">High</option>
        <option value="medium">Medium</option>
        <option value="low">Low</option>
      </select>
      <select x-model="assigneeFilter" style="height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif;">
        <option value="all">All assignees</option>
        <option value="me">Assigned to me</option>
        @foreach($users as $u)
          <option value="{{ strtolower($u->name) }}">{{ $u->name }}</option>
        @endforeach
      </select>
      <select x-model="sortBy" style="height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif;">
        <option value="newest">Newest</option>
        <option value="oldest">Oldest</option>
        <option value="priority">Priority</option>
      </select>
      <span style="margin-left:auto; font-size:0.75rem; color:#8d8d8d; font-family:'IBM Plex Mono',monospace;" x-text="document.querySelectorAll('[data-todo]').length + ' shown'"></span>
    </div>
  </div>

  {{-- Todo List — IBM Carbon Structured List --}}
  <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:0;">
    <div style="divide-y:1px solid #e0e0e0;">
      @forelse($tasks->sortByDesc('created_at') as $t)
      <div data-todo
           x-show="(filter==='all' || (filter==='pending' && '{{ $t->status }}'!=='completed') || (filter==='completed' && '{{ $t->status }}'==='completed')) && (priorityFilter==='all' || '{{ $t->priority }}'===priorityFilter) && (assigneeFilter==='all' || (assigneeFilter==='me' && '{{ strtolower($t->assignee?->name ?? '') }}'==='{{ strtolower(auth()->user()->name ?? '') }}') || '{{ strtolower($t->assignee?->name ?? '') }}'===assigneeFilter) && (@js(strtolower($t->title) . ' ' . strtolower($t->description ?? '') . ' ' . strtolower($t->assignee?->name ?? '')).toLowerCase().includes(search.toLowerCase()))"
           style="display:flex; align-items:center; gap:1rem; padding:1rem; border-bottom:1px solid #e0e0e0; {{ $loop->even ? 'background:#f4f4f4;' : 'background:#fff;' }}">
        <form action="{{ route('reception.tasks.status', $t->id) }}" method="POST" style="flex-shrink:0;">
          @csrf
          <input type="hidden" name="status" value="{{ $t->status==='completed' ? 'pending' : 'completed' }}">
          <button type="submit" style="width:1.5rem; height:1.5rem; border:2px solid {{ $t->status==='completed' ? '#0f62fe' : '#8d8d8d' }}; background:{{ $t->status==='completed' ? '#0f62fe' : '#fff' }}; color:{{ $t->status==='completed' ? '#fff' : 'transparent' }}; display:flex; align-items:center; justify-content:center; cursor:pointer;" title="{{ $t->status==='completed' ? 'Mark pending' : 'Mark done' }}">
            <span class="material-symbols-outlined" style="font-size:14px; line-height:1;">check</span>
          </button>
        </form>
        <div style="flex:1; min-width:0;">
          <a href="{{ route('reception.tasks.show', $t->id) }}" style="font-size:0.875rem; line-height:1.3; text-decoration:none; {{ $t->status==='completed' ? 'text-decoration:line-through; color:#8d8d8d;' : 'color:#161616; font-weight:600;' }} font-family:'IBM Plex Sans',sans-serif;">{{ $t->title }}</a>
          @if($t->description)
            <div style="font-size:0.875rem; color:#525252; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-family:'IBM Plex Sans',sans-serif;">{{ $t->description }}</div>
          @endif
          @if(!empty($t->attachments) && is_array($t->attachments))
            <div style="display:flex; flex-wrap:wrap; gap:0.375rem; margin-top:0.5rem;">
              @foreach($t->attachments as $att)
                <a href="{{ $att['path'] ?? '#' }}" target="_blank" style="display:inline-flex; align-items:center; gap:0.25rem; background:#e0e0e0; border:1px solid #c6c6c6; padding:0.125rem 0.375rem; font-size:0.75rem; color:#393939; text-decoration:none; font-family:'IBM Plex Sans',sans-serif;">
                  <span class="material-symbols-outlined" style="font-size:12px;">attach_file</span> {{ Str::limit($att['name'] ?? 'file', 16) }}
                </a>
              @endforeach
            </div>
          @endif
          <div style="display:flex; align-items:center; gap:0.5rem; margin-top:0.5rem; flex-wrap:wrap;">
            <span style="background:{{ $t->priority==='urgent' ? '#ffd7d9' : ($t->priority==='high' ? '#fff8e1' : '#e0e0e0') }}; border:1px solid {{ $t->priority==='urgent' ? '#ffb3b8' : ($t->priority==='high' ? '#f1c21b' : '#c6c6c6') }}; color:{{ $t->priority==='urgent' ? '#750e13' : ($t->priority==='high' ? '#684e00' : '#525252') }}; font-size:0.75rem; padding:0.125rem 0.375rem; font-family:'IBM Plex Sans',sans-serif; font-weight:600; display:inline-flex; align-items:center; gap:0.25rem;">
              <span style="width:0.5rem; height:0.5rem; background:{{ $t->priority==='urgent' ? '#da1e28' : ($t->priority==='high' ? '#e9730c' : '#8d8d8d') }};"></span>
              {{ ucfirst($t->priority ?? 'medium') }}
            </span>
            <span style="font-size:0.75rem; color:#8d8d8d; font-family:'IBM Plex Mono',monospace;">{{ $t->created_at->diffForHumans() }}</span>
            @if($t->assignee)
              <span style="font-size:0.75rem; color:#525252; font-family:'IBM Plex Sans',sans-serif;">• {{ $t->assignee->name }}</span>
            @endif
            @if($t->collaborators && $t->collaborators->count())
              <span style="font-size:0.75rem; color:#525252;">+{{ $t->collaborators->count() }} collab</span>
            @endif
          </div>
        </div>
        <div style="display:flex; align-items:center; gap:0.375rem; flex-shrink:0;">
          <form action="{{ route('reception.tasks.status', $t->id) }}" method="POST" style="display:none;" class="sm:block">
            @csrf
            <input type="hidden" name="status" value="{{ $t->status==='completed' ? 'pending' : 'completed' }}">
            <button type="submit" style="height:1.75rem; padding:0 0.5rem; background:#fff; border:1px solid #8d8d8d; color:#161616; font-size:0.75rem; font-weight:600; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">
              {{ $t->status==='completed' ? 'Undo' : 'Done' }}
            </button>
          </form>
          <form id="delete-todo-form-{{ $t->id }}" action="{{ route('reception.tasks.destroy', $t->id) }}" method="POST">
            @csrf @method('DELETE')
            <button type="button" @click="openDeleteModal({{ $t->id }}, @js($t->title))" style="width:2rem; height:2rem; background:#fff; border:1px solid #e0e0e0; display:flex; align-items:center; justify-content:center; color:#da1e28; cursor:pointer;" title="Delete">
              <span class="material-symbols-outlined" style="font-size:16px;">delete</span>
            </button>
          </form>
        </div>
      </div>
      @empty
      <div style="padding:2rem; text-align:center; background:#fff;">
        <div style="width:3rem; height:3rem; background:#f4f4f4; border:1px solid #e0e0e0; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto; color:#8d8d8d;">
          <span class="material-symbols-outlined">checklist</span>
        </div>
        <p style="font-size:0.875rem; font-weight:600; color:#161616; margin-top:0.75rem; font-family:'IBM Plex Sans',sans-serif;">All clear</p>
        <p style="font-size:0.875rem; color:#525252; margin-top:0.25rem;">No todos yet — add one above.</p>
      </div>
      @endforelse
    </div>
  </div>

  <p style="font-size:0.75rem; color:#8d8d8d; margin-top:1rem; text-align:center; font-family:'IBM Plex Sans',sans-serif;">Carbon task management — click circle to complete • Click again to undo</p>

  {{-- Delete confirm Carbon modal --}}
  <div x-show="showDeleteModal" x-cloak @keydown.escape.window="closeDeleteModal()" style="position:fixed; inset:0; z-index:9000; display:flex; align-items:center; justify-content:center; background:rgba(22,22,22,0.5); padding:1rem;">
    <div x-trap.noscroll="showDeleteModal" role="dialog" aria-modal="true" aria-labelledby="delete-todo-title" class="cds--modal" style="background:#fff; border:1px solid #e0e0e0; width:100%; max-width:28rem; display:flex; flex-direction:column; box-shadow:0 8px 32px rgba(0,0,0,0.2);" @click.away="closeDeleteModal()">
      <div style="padding:1rem 1.25rem; background:#f4f4f4; border-bottom:1px solid #e0e0e0; display:flex; align-items:center; justify-content:space-between;">
        <h3 id="delete-todo-title" style="font-size:1rem; font-weight:600; color:#161616; font-family:'IBM Plex Sans',sans-serif;">Delete todo?</h3>
        <button @click="closeDeleteModal()" type="button" style="width:2rem; height:2rem; background:transparent; border:0; display:flex; align-items:center; justify-content:center; color:#525252; cursor:pointer;" aria-label="Close"><span class="material-symbols-outlined" style="font-size:18px;">close</span></button>
      </div>
      <div style="padding:1.25rem; background:#fff;">
        <p style="font-size:0.875rem; color:#525252; font-family:'IBM Plex Sans',sans-serif;">Are you sure you want to delete <span style="font-weight:600; color:#161616;" x-text="pendingDeleteTitle ? '“'+pendingDeleteTitle+'”' : 'this todo'"></span>? This action cannot be undone.</p>
      </div>
      <div style="padding:1rem 1.25rem; background:#f4f4f4; border-top:1px solid #e0e0e0; display:flex; justify-content:flex-end; gap:0.5rem;">
        <button x-ref="cancelDeleteBtn" @click="closeDeleteModal()" type="button" style="height:2.25rem; padding:0 1rem; background:#fff; border:1px solid #8d8d8d; color:#161616; font-size:0.875rem; font-weight:600; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">Cancel</button>
        <button @click="confirmDelete()" type="button" style="height:2.25rem; padding:0 1rem; background:#da1e28; border:1px solid #da1e28; color:#fff; font-size:0.875rem; font-weight:600; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">Delete</button>
      </div>
    </div>
  </div>
</div>
@endsection
