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
  <div class="ibm-grid" style="padding-top:1rem; padding-bottom:1rem;">
    <nav class="cds--breadcrumb" aria-label="Breadcrumb" style="margin-bottom:0.75rem; display:flex; gap:0.5rem; font-size:0.75rem; color:#525252;">
      <div class="cds--breadcrumb-item"><a class="cds--link" href="{{ route('manager.index') }}" style="color:#0f62fe; text-decoration:none;">Manager</a></div>
      <div class="cds--breadcrumb-item"><span style="color:#8d8d8d;">/</span> <span style="color:#161616; margin-left:0.5rem;">Staff & Access</span></div>
    </nav>
    <div style="display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:1rem;">
      <div>
        <h1 style="font-size:2rem; font-weight:400; color:#161616; line-height:1.25; margin:0; font-family:'IBM Plex Sans',sans-serif; letter-spacing:-0.02em;">Staff & Access</h1>
        <p style="font-size:0.875rem; color:#525252; margin:0.375rem 0 0 0; max-width:42rem; line-height:1.5; font-family:'IBM Plex Sans',sans-serif;">Create staff accounts and route each person to the correct department workspace</p>
      </div>
    </div>
  </div>
</div>

<div class="ibm-grid" style="padding-top:1.5rem;">

  @if(session('success'))
  <x-success-popup :message="session('success')" />
  @endif
  @if(isset($errors) && $errors->any())
  <div class="cds--inline-notification cds--inline-notification--low-contrast cds--inline-notification--error" role="alert" style="margin-bottom:1rem; padding:1rem; background:#fff1f1; border:1px solid #ffb3b8; border-left:4px solid #da1e28;">
    <div style="font-size:0.875rem; color:#750e13; font-family:'IBM Plex Sans',sans-serif;"><strong>Please correct:</strong><ul style="margin:0.5rem 0 0 1.25rem;">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
  </div>
  @endif

  <div style="display:grid; grid-template-columns: 1.6fr 0.9fr; gap:1rem; align-items:start;">
    <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:0;">
      <div style="padding:1rem; border-bottom:1px solid #e0e0e0; background:#f4f4f4; display:flex; align-items:center; justify-content:space-between;">
        <div>
          <div style="font-size:0.875rem; font-weight:600; color:#161616; font-family:'IBM Plex Sans',sans-serif;">Organization directory</div>
          <div style="font-size:0.75rem; color:#525252; margin-top:0.125rem; font-family:'IBM Plex Sans',sans-serif;">Department membership controls workspace access</div>
        </div>
        <span style="background:#e0e0e0; color:#525252; font-size:0.75rem; padding:0.125rem 0.5rem; font-family:'IBM Plex Mono',monospace;">{{ $staff->total() }} total</span>
      </div>
      <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse; font-size:0.875rem;">
          <thead>
            <tr style="background:#e0e0e0;">
              <th style="padding:0.75rem 1rem; text-align:left; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6; font-family:'IBM Plex Sans',sans-serif;">Staff member</th>
              <th style="padding:0.75rem 1rem; text-align:left; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Department</th>
              <th style="padding:0.75rem 1rem; text-align:left; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Workspace</th>
            </tr>
          </thead>
          <tbody>
            @forelse($staff as $person)
            <tr style="border-bottom:1px solid #e0e0e0; background:#fff;">
              <td style="padding:0.75rem 1rem;">
                <div style="font-size:0.875rem; font-weight:600; color:#161616; font-family:'IBM Plex Sans',sans-serif;">{{ $person->full_name }}</div>
                <div style="font-size:0.75rem; color:#525252; font-family:'IBM Plex Mono',monospace; margin-top:0.125rem;">{{ $person->email }}</div>
              </td>
              <td style="padding:0.75rem 1rem; font-size:0.875rem; color:#161616; font-family:'IBM Plex Sans',sans-serif;">{{ $person->department?->name ?? 'Not assigned' }}</td>
              <td style="padding:0.75rem 1rem;"><span style="background:#e0e0e0; border:1px solid #c6c6c6; color:#393939; font-size:0.75rem; padding:0.125rem 0.5rem; font-family:'IBM Plex Mono',monospace; text-transform:uppercase; letter-spacing:0.02em;">{{ $person->portal_role ?? 'No access' }}</span></td>
            </tr>
            @empty
            <tr><td colspan="3" style="padding:2rem; text-align:center; color:#525252; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif;">No staff records yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div style="padding:1rem; border-top:1px solid #e0e0e0; background:#fff; display:flex; justify-content:space-between; align-items:center; font-size:0.75rem; color:#525252;">
        <span>Showing {{ $staff->firstItem() ?? 0 }}–{{ $staff->lastItem() ?? 0 }} of {{ $staff->total() }}</span>
        <span>{{ $staff->links() }}</span>
      </div>
    </div>

    <div style="display:flex; flex-direction:column; gap:1rem;">
      <form action="{{ route('manager.staff.link-account') }}" method="POST" class="cds--tile" style="background:#f4f4f4; border:1px solid #e0e0e0; padding:1.25rem; border-left:4px solid #0f62fe;">
        @csrf
        <div style="font-size:0.875rem; font-weight:600; color:#161616; font-family:'IBM Plex Sans',sans-serif;">Link existing account</div>
        <p style="font-size:0.75rem; color:#525252; margin-top:0.25rem; font-family:'IBM Plex Sans',sans-serif;">For existing IT, Sales, Reception, or Manager logins needing a department</p>
        <div style="margin-top:1rem;">
          <label style="font-size:0.75rem; font-weight:600; color:#525252; font-family:'IBM Plex Sans',sans-serif;">Portal account *</label>
          <select required name="user_id" style="width:100%; height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; margin-top:0.25rem; font-family:'IBM Plex Sans',sans-serif;">
            <option value="">Select account</option>
            @foreach($portalUsers as $portalUser)<option value="{{ $portalUser->id }}">{{ $portalUser->name }} — {{ strtoupper($portalUser->role) }} — {{ $portalUser->email }}</option>@endforeach
          </select>
        </div>
        <div style="margin-top:0.75rem;">
          <label style="font-size:0.75rem; font-weight:600; color:#525252;">Department *</label>
          <select required name="department_id" style="width:100%; height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; margin-top:0.25rem;">
            <option value="">Select department</option>
            @foreach($departments as $department)<option value="{{ $department->id }}">{{ $department->name }}</option>@endforeach
          </select>
        </div>
        <button type="submit" style="margin-top:1rem; width:100%; height:2.5rem; background:#0f62fe; color:#fff; border:1px solid #0f62fe; font-size:0.875rem; font-weight:600; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">Link account to department</button>
      </form>

      <form action="{{ route('manager.staff.store') }}" method="POST" class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:1.25rem;">
        @csrf
        <div style="font-size:0.875rem; font-weight:600; color:#161616; font-family:'IBM Plex Sans',sans-serif;">Create staff account</div>
        <p style="font-size:0.75rem; color:#525252; margin-top:0.25rem;">Creates employee record + login access</p>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; margin-top:1rem;">
          <div><label style="font-size:0.75rem; font-weight:600; color:#525252;">First name *</label><input required name="first_name" value="{{ old('first_name') }}" style="width:100%; height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; margin-top:0.25rem;"></div>
          <div><label style="font-size:0.75rem; font-weight:600; color:#525252;">Last name</label><input name="last_name" value="{{ old('last_name') }}" style="width:100%; height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; margin-top:0.25rem;"></div>
        </div>
        <div style="margin-top:0.75rem;">
          <label style="font-size:0.75rem; font-weight:600; color:#525252;">Email *</label>
          <input required type="email" name="email" value="{{ old('email') }}" placeholder="name@jobarn.co.tz" style="width:100%; height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; margin-top:0.25rem;">
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; margin-top:0.75rem;">
          <div>
            <label style="font-size:0.75rem; font-weight:600; color:#525252;">Role *</label>
            <select required name="role" style="width:100%; height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; margin-top:0.25rem;">
              <option value="reception">Reception</option>
              <option value="it">IT</option>
              <option value="sales">Sales</option>
              <option value="manager">Manager</option>
            </select>
          </div>
          <div>
            <label style="font-size:0.75rem; font-weight:600; color:#525252;">Department *</label>
            <select required name="department_id" style="width:100%; height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; margin-top:0.25rem;">
              @foreach($departments as $department)<option value="{{ $department->id }}">{{ $department->name }}</option>@endforeach
            </select>
          </div>
        </div>
        <div style="margin-top:0.75rem;">
          <label style="font-size:0.75rem; font-weight:600; color:#525252;">Password *</label>
          <input required type="password" name="password" style="width:100%; height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; margin-top:0.25rem;">
        </div>
        <button type="submit" style="margin-top:1rem; width:100%; height:2.5rem; background:#161616; color:#fff; border:1px solid #161616; font-size:0.875rem; font-weight:600; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">Create staff</button>
      </form>
    </div>
  </div>
</div>
</div>
@endsection
