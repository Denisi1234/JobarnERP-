@extends('reception.layout')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<link href="https://unpkg.com/carbon-components@11.42.0/css/carbon-components.min.css" rel="stylesheet">
<style>
  [x-cloak]{display:none!important}
  .cds--tile, .cds--data-table-container, .cds--btn, .cds--tag { border-radius:0 !important; }
  .ibm-grid { max-width:1584px; margin:0 auto; padding:0 1rem; }
  @media(min-width:66rem){ .ibm-grid{ padding:0 2rem; } }
</style>

<div class="cds--content" style="background:#f4f4f4; min-height:100vh; margin:-1.5rem -1.5rem 0 -1.5rem; padding:0 0 2rem 0;">

<div style="background:#ffffff; border-bottom:1px solid #e0e0e0;">
  <div class="ibm-grid" style="padding-top:1rem; padding-bottom:0;">
    <nav class="cds--breadcrumb" aria-label="Breadcrumb" style="margin-bottom:0.75rem; display:flex; gap:0.5rem; font-size:0.75rem; color:#525252;">
      <div class="cds--breadcrumb-item"><a class="cds--link" href="{{ route('sales.index') }}" style="color:#0f62fe; text-decoration:none;">Sales</a></div>
      <div class="cds--breadcrumb-item"><span style="color:#8d8d8d;">/</span> <span style="color:#161616; margin-left:0.5rem;">Logbook</span></div>
    </nav>
    <div style="padding-bottom:1rem;">
      <div style="font-size:0.75rem; font-weight:600; letter-spacing:0.04em; text-transform:uppercase; color:#525252; font-family:'IBM Plex Sans',sans-serif;">{{ $isManager ? 'Manager Portal / Live Department Logbooks' : 'Sales Workspace / Logbook' }}</div>
      <h1 style="font-size:2rem; font-weight:400; color:#161616; line-height:1.25; margin:0.25rem 0 0 0; font-family:'IBM Plex Sans',sans-serif; letter-spacing:-0.02em;">{{ $isManager ? 'Department Logbooks' : 'My Daily Logbook' }}</h1>
      <p style="font-size:0.875rem; color:#525252; margin:0.375rem 0 0 0; max-width:42rem; line-height:1.5; font-family:'IBM Plex Sans',sans-serif;">{{ $isManager ? $pending.' submitted entries awaiting review — filter by department' : 'Record your entry time, out time, and the activity performed today. Submitted to manager for review.' }}</p>
    </div>
  </div>
</div>

<div class="ibm-grid" style="padding-top:1.5rem;">

  @if(session('success'))
  <x-success-popup :message="session('success')" />
  @endif

  @if($errors->any())
  <div class="cds--inline-notification cds--inline-notification--low-contrast cds--inline-notification--error" role="alert" style="margin-bottom:1rem; padding:1rem; background:#fff1f1; border:1px solid #ffb3b8; border-left:4px solid #da1e28;">
    <div style="font-size:0.875rem; color:#750e13; font-family:'IBM Plex Sans',sans-serif;">{{ $errors->first() }}</div>
  </div>
  @endif

  @if($isManager)
  <form method="GET" class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:1rem; margin-bottom:1rem; display:flex; gap:0.75rem; align-items:end; flex-wrap:wrap;">
    <div style="flex:1; min-width:200px;">
      <label style="font-size:0.75rem; font-weight:600; color:#525252; font-family:'IBM Plex Sans',sans-serif;">Department</label>
      <select name="department_id" style="width:100%; height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; margin-top:0.25rem; font-family:'IBM Plex Sans',sans-serif;">
        <option value="">All departments</option>
        @foreach($departments as $department)
          <option value="{{ $department->id }}" @selected(request('department_id')==$department->id)>{{ $department->name }}</option>
        @endforeach
      </select>
    </div>
    <button type="submit" style="height:2rem; padding:0 1rem; background:#0f62fe; color:#fff; border:1px solid #0f62fe; font-size:0.875rem; font-weight:600; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">Filter</button>
  </form>
  @else
  <form action="{{ route('work-reports.store') }}" method="POST" class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:1.5rem; margin-bottom:1.5rem;">
    @csrf
    <input type="hidden" name="department_id" value="{{ $departmentId }}">
    <div style="font-size:0.875rem; font-weight:600; color:#161616; font-family:'IBM Plex Sans',sans-serif; margin-bottom:1rem; padding-bottom:0.75rem; border-bottom:1px solid #e0e0e0;">Submit Today Entry</div>
    <div style="display:grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap:1rem; margin-bottom:1rem;">
      <div>
        <label style="font-size:0.75rem; font-weight:600; color:#525252; font-family:'IBM Plex Sans',sans-serif;">Date <span style="color:#da1e28;">*</span></label>
        <input required type="date" max="{{ today()->format('Y-m-d') }}" name="report_date" value="{{ old('report_date', today()->format('Y-m-d')) }}" style="width:100%; height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; margin-top:0.25rem; font-family:'IBM Plex Sans',sans-serif;">
      </div>
      <div>
        <label style="font-size:0.75rem; font-weight:600; color:#525252;">Entry time <span style="color:#da1e28;">*</span></label>
        <input required type="time" name="entry_time" value="{{ old('entry_time') }}" style="width:100%; height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; margin-top:0.25rem;">
      </div>
      <div>
        <label style="font-size:0.75rem; font-weight:600; color:#525252;">Out time <span style="color:#da1e28;">*</span></label>
        <input required type="time" name="out_time" value="{{ old('out_time') }}" style="width:100%; height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; margin-top:0.25rem;">
      </div>
    </div>
    <div style="margin-bottom:1rem;">
      <label style="font-size:0.75rem; font-weight:600; color:#525252; font-family:'IBM Plex Sans',sans-serif;">Activity performed <span style="color:#da1e28;">*</span></label>
      <textarea required name="activity_performed" rows="5" placeholder="Write the work activity performed today." style="width:100%; border:1px solid #8d8d8d; padding:0.75rem; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif; margin-top:0.25rem;">{{ old('activity_performed') }}</textarea>
    </div>
    <div style="display:flex; justify-content:flex-end;">
      <button type="submit" style="height:2.5rem; padding:0 1.5rem; background:#0f62fe; color:#fff; border:1px solid #0f62fe; font-size:0.875rem; font-weight:600; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">Send to manager</button>
    </div>
  </form>
  @endif

  <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:0;">
    <div style="padding:1rem; border-bottom:1px solid #e0e0e0; display:flex; align-items:center; justify-content:space-between; background:#f4f4f4;">
      <div style="font-size:0.875rem; font-weight:600; font-family:'IBM Plex Sans',sans-serif; color:#161616;">{{ $isManager ? 'All Logbook Entries' : 'My Entries' }}</div>
      <span style="background:#e0e0e0; color:#525252; font-size:0.75rem; padding:0.125rem 0.5rem; font-family:'IBM Plex Mono',monospace;">{{ $reports->total() }} total</span>
    </div>
    <div style="overflow-x:auto;">
      <table style="width:100%; border-collapse:collapse; font-size:0.875rem;">
        <thead>
          <tr style="background:#e0e0e0;">
            <th style="padding:0.75rem 1rem; text-align:left; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6; font-family:'IBM Plex Sans',sans-serif;">Staff</th>
            <th style="padding:0.75rem 1rem; text-align:left; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Department</th>
            <th style="padding:0.75rem 1rem; text-align:left; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Date</th>
            <th style="padding:0.75rem 1rem; text-align:left; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Entry</th>
            <th style="padding:0.75rem 1rem; text-align:left; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Out</th>
            <th style="padding:0.75rem 1rem; text-align:left; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Activity</th>
            <th style="padding:0.75rem 1rem; text-align:left; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Status</th>
            @if($isManager)<th style="padding:0.75rem 1rem; text-align:left; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Manager Action</th>@endif
          </tr>
        </thead>
        <tbody>
          @forelse($reports as $report)
            <tr style="border-bottom:1px solid #e0e0e0; background:#fff;">
              <td style="padding:0.75rem 1rem; font-weight:600; color:#161616; font-family:'IBM Plex Sans',sans-serif;">{{ $report->user?->name }}</td>
              <td style="padding:0.75rem 1rem; color:#525252;">{{ $report->department?->name ?? 'No department' }}</td>
              <td style="padding:0.75rem 1rem; font-family:'IBM Plex Mono',monospace; font-size:0.875rem; color:#161616;">{{ $report->report_date->format('d M Y') }}</td>
              <td style="padding:0.75rem 1rem; font-family:'IBM Plex Mono',monospace; font-size:0.875rem;">{{ $report->entry_time ? $report->entry_time->format('H:i') : '-' }}</td>
              <td style="padding:0.75rem 1rem; font-family:'IBM Plex Mono',monospace; font-size:0.875rem;">{{ $report->out_time ? $report->out_time->format('H:i') : '-' }}</td>
              <td style="padding:0.75rem 1rem; max-width:24rem;">
                <div style="white-space:pre-line; font-size:0.875rem; color:#161616; font-family:'IBM Plex Sans',sans-serif;">{{ $report->activity_performed ?: $report->work_completed }}</div>
                @if($report->manager_comment)
                  <div style="margin-top:0.5rem; padding:0.5rem 0.75rem; background:#f4f4f4; border-left:3px solid #0f62fe; font-size:0.875rem; color:#161616;">{{ $report->manager_comment }}</div>
                @endif
              </td>
              <td style="padding:0.75rem 1rem;"><span style="background:{{ $report->status==='submitted' ? '#fff8e1' : ($report->status==='reviewed' ? '#defbe6' : '#e0e0e0') }}; border:1px solid {{ $report->status==='submitted' ? '#f1c21b' : ($report->status==='reviewed' ? '#a7f0ba' : '#c6c6c6') }}; color:{{ $report->status==='submitted' ? '#684e00' : ($report->status==='reviewed' ? '#044317' : '#525252') }}; font-size:0.75rem; padding:0.125rem 0.5rem; font-family:'IBM Plex Sans',sans-serif; font-weight:600; text-transform:uppercase; letter-spacing:0.02em;">{{ $report->status }}</span></td>
              @if($isManager)
                <td style="padding:0.75rem 1rem;">
                  @if($report->status === 'submitted')
                    <form action="{{ route('work-reports.review', $report) }}" method="POST" style="display:flex; flex-direction:column; gap:0.5rem; min-width:14rem;">
                      @csrf
                      <select name="status" style="height:2rem; padding:0 0.5rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif;">
                        <option value="reviewed">Mark reviewed</option>
                        <option value="returned">Return for correction</option>
                      </select>
                      <input name="manager_comment" placeholder="Manager comment" style="height:2rem; padding:0 0.5rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem;">
                      <button type="submit" style="height:2rem; padding:0 0.75rem; background:#0f62fe; color:#fff; border:1px solid #0f62fe; font-size:0.875rem; font-weight:600; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">Record</button>
                    </form>
                  @else
                    <span style="font-size:0.875rem; color:#525252; font-family:'IBM Plex Sans',sans-serif;">{{ $report->reviewer?->name ?? 'Reviewed' }}</span>
                  @endif
                </td>
              @endif
            </tr>
          @empty
            <tr><td colspan="{{ $isManager ? 8 : 7 }}" style="padding:2rem; text-align:center; color:#525252; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif;">No logbook entries found.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div style="padding:1rem; border-top:1px solid #e0e0e0; background:#fff; display:flex; justify-content:space-between; align-items:center;">
      <span style="font-size:0.75rem; color:#525252; font-family:'IBM Plex Sans',sans-serif;">Showing {{ $reports->firstItem() ?? 0 }}–{{ $reports->lastItem() ?? 0 }} of {{ $reports->total() }}</span>
      <span style="font-size:0.75rem;">{{ $reports->links() }}</span>
    </div>
  </div>

</div>
</div>
@endsection
