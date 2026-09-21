@extends('reception.layout')

@section('content')
<div class="flex flex-col w-full">

    {{-- ───────────────── HEADER ───────────────── --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-space-md mb-space-lg">
        <div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface tracking-tight font-bold">Deliveries &amp; Packages</h1>
            <p class="font-body-sm text-body-sm text-on-surface-variant mt-0.5">
                {{ now()->format('l, d F Y') }}
            </p>
        </div>
        <div class="flex items-center gap-space-xs">
            <button id="btn-add" type="button"
                    class="flex items-center gap-space-xs px-space-md py-space-sm bg-primary text-on-primary font-label-lg text-label-lg rounded-lg shadow-sm transition-all hover:opacity-90">
                <span class="material-symbols-outlined text-[18px]">add_box</span>
                <span>+ Log Parcel In</span>
            </button>
            <button type="button" onclick="exportDeliveriesCsv()"
                    class="flex items-center gap-space-xs px-space-md py-space-sm bg-surface-container-lowest hover:bg-surface-container text-on-surface font-label-lg text-label-lg rounded-lg shadow-sm transition-all" title="Export CSV">
                <span class="material-symbols-outlined text-on-surface-variant text-[18px]">download</span>
                <span>Export CSV</span>
            </button>
        </div>
    </div>

  <!-- IN / OUT TABS -->
  <div class="flex items-center gap-0 bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden">
    <button id="tab-in" class="parcel-tab flex-1 py-3 flex items-center justify-center gap-space-xs font-label-lg text-label-lg font-semibold transition-all bg-primary text-on-primary" data-tab="in">
      <span class="material-symbols-outlined text-[20px]">inventory_2</span>
      <span>Parcels In</span>
      <span class="ml-1 px-2 py-0.5 rounded-full bg-on-primary/20 text-on-primary font-label-mono text-[12px] font-bold" id="count-in">0</span>
    </button>
    <button id="tab-out" class="parcel-tab flex-1 py-3 flex items-center justify-center gap-space-xs font-label-lg text-label-lg font-semibold transition-all bg-surface-container-low text-on-surface-variant hover:bg-surface-container" data-tab="out">
      <span class="material-symbols-outlined text-[20px]">outbox</span>
      <span>Parcels Out</span>
      <span class="ml-1 px-2 py-0.5 rounded-full bg-surface-container text-on-surface-variant font-label-mono text-[12px] font-bold" id="count-out">0</span>
    </button>
  </div>

  <!-- PARCELS IN TABLE -->
  <div id="section-in" class="bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left">
        <thead>
          <tr class="bg-surface-container-low text-on-surface-variant font-label-md text-label-md uppercase tracking-wider">
            <th class="py-3 px-space-md w-12">#</th>
            <th class="py-3 px-space-md">Time In</th>
            <th class="py-3 px-space-md">Description</th>
            <th class="py-3 px-space-md">From / Carrier</th>
            <th class="py-3 px-space-md">For</th>
            <th class="py-3 px-space-md">Where Stored</th>
            <th class="py-3 px-space-md text-right">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-surface-container-low" id="tbody-in">
          <!-- Empty — parcels logged via form appear here -->
        </tbody>
      </table>
    </div>
  </div>

  <!-- PARCELS OUT TABLE (hidden by default) -->
  <div id="section-out" class="bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left">
        <thead>
          <tr class="bg-surface-container-low text-on-surface-variant font-label-md text-label-md uppercase tracking-wider">
            <th class="py-3 px-space-md">Time In</th>
            <th class="py-3 px-space-md">Time Out</th>
            <th class="py-3 px-space-md">Description</th>
            <th class="py-3 px-space-md">From / Carrier</th>
            <th class="py-3 px-space-md">Collected By</th>
            <th class="py-3 px-space-md">Signed</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-surface-container-low" id="tbody-out">
          <!-- Empty — signed-out parcels appear here -->
        </tbody>
      </table>
    </div>
  </div>

  <!-- LOG NEW PARCEL IN -->
  <div class="bg-surface-container-lowest rounded-xl p-space-lg shadow-sm" id="log-section">
    <h2 class="font-headline-sm text-headline-sm text-on-surface font-bold mb-space-md flex items-center gap-space-xs">
      <span class="material-symbols-outlined text-primary text-[20px]">add_circle</span>
      New Parcel In
    </h2>
    <form id="log-form" class="flex flex-col gap-space-sm">
      <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-space-sm">
        <label class="flex flex-col gap-1">
          <span class="font-label-md text-label-md text-on-surface-variant">What is it?</span>
          <input id="f-desc" required placeholder="e.g. 2 Boxes" class="h-9 px-3 rounded-lg border border-outline-variant/50 bg-surface-container-lowest font-body-md text-body-md focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none" />
        </label>
        <label class="flex flex-col gap-1">
          <span class="font-label-md text-label-md text-on-surface-variant">From / Carrier</span>
          <input id="f-carrier" required placeholder="e.g. FedEx, DHL" class="h-9 px-3 rounded-lg border border-outline-variant/50 bg-surface-container-lowest font-body-md text-body-md focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none" />
        </label>
        <label class="flex flex-col gap-1">
          <span class="font-label-md text-label-md text-on-surface-variant">Tracking # <span class="text-on-surface-variant font-normal">(optional)</span></span>
          <input id="f-tracking" placeholder="#" class="h-9 px-3 rounded-lg border border-outline-variant/50 bg-surface-container-lowest font-label-mono text-label-mono focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none" />
        </label>
        <label class="flex flex-col gap-1">
          <span class="font-label-md text-label-md text-on-surface-variant">For who?</span>
          <input id="f-for" required placeholder="Recipient name" class="h-9 px-3 rounded-lg border border-outline-variant/50 bg-surface-container-lowest font-body-md text-body-md focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none" />
        </label>
        <label class="flex flex-col gap-1">
          <span class="font-label-md text-label-md text-on-surface-variant">Stored where?</span>
          <input id="f-stored" placeholder="e.g. Locker, Desk" class="h-9 px-3 rounded-lg border border-outline-variant/50 bg-surface-container-lowest font-body-md text-body-md focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none" />
        </label>
        <div class="flex items-end gap-space-sm">
          <label class="flex items-center gap-2 mb-2">
            <input id="f-urgent" type="checkbox" class="rounded text-error focus:ring-error w-4 h-4" />
            <span class="font-label-md text-label-md text-error font-semibold">Urgent</span>
          </label>
          <button type="submit" class="h-9 px-space-lg bg-primary hover:bg-primary-container text-on-primary rounded-lg font-label-lg text-label-lg font-semibold shadow-sm transition-all flex items-center justify-center gap-1 flex-1">
            <span class="material-symbols-outlined text-[16px]">inventory_2</span>
            Log In
          </button>
        </div>
      </div>
    </form>
  </div>

</div>

<!-- SIGN-OUT MODAL -->
<div id="signout-modal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4">
  <div id="signout-backdrop" class="absolute inset-0 bg-inverse-surface/50 backdrop-blur-[2px]"></div>
  <div class="relative bg-surface-container-lowest rounded-xl shadow-xl w-full max-w-sm p-space-lg flex flex-col gap-space-md">
    <h3 class="font-headline-sm text-headline-sm text-on-surface font-bold">Sign Out Parcel</h3>
    <p class="font-body-md text-body-md text-on-surface-variant" id="signout-desc">Who is collecting this?</p>
    <form id="signout-form" class="flex flex-col gap-space-sm">
      <label class="flex flex-col gap-1">
        <span class="font-label-md text-label-md text-on-surface-variant">Collected by</span>
        <input id="so-who" required placeholder="Name of person collecting" class="h-9 px-3 rounded-lg border border-outline-variant/50 bg-surface-container-lowest font-body-md text-body-md focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none" autofocus />
      </label>
      <div class="flex items-center justify-end gap-space-xs pt-space-xs">
        <button type="button" id="so-cancel" class="px-space-md py-2 rounded-lg bg-surface-container-low text-on-surface font-label-lg text-label-lg hover:bg-surface-container transition-colors">Cancel</button>
        <button type="submit" class="px-space-lg py-2 rounded-lg bg-primary text-on-primary font-label-lg text-label-lg font-semibold shadow-sm hover:bg-primary-container transition-all flex items-center gap-1">
          <span class="material-symbols-outlined text-[16px]">check</span>
          Confirm Sign Out
        </button>
      </div>
    </form>
  </div>
</div>

<!-- TOAST -->
<div id="toast" class="hidden fixed bottom-6 left-1/2 -translate-x-1/2 z-[80] px-4 py-2.5 rounded-full bg-inverse-surface text-inverse-on-surface font-label-md text-label-md shadow-xl flex items-center gap-2">
  <span class="material-symbols-outlined text-[18px] text-tertiary-fixed">check_circle</span>
  <span id="toast-text">Done</span>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const toast = document.getElementById('toast');
  const toastText = document.getElementById('toast-text');
  const modal = document.getElementById('signout-modal');
  const signoutForm = document.getElementById('signout-form');
  const signoutDesc = document.getElementById('signout-desc');
  let activeRow = null;

  function showToast(msg) {
    toastText.textContent = msg;
    toast.classList.remove('hidden');
    setTimeout(() => toast.classList.add('hidden'), 2200);
  }

  function updateCounts() {
    document.getElementById('count-in').textContent = document.querySelectorAll('#tbody-in .parcel-in-row').length;
    document.getElementById('count-out').textContent = document.querySelectorAll('#tbody-out tr').length;
  }

  // Tab switching
  document.querySelectorAll('.parcel-tab').forEach(tab => {
    tab.addEventListener('click', () => {
      const target = tab.dataset.tab;
      document.querySelectorAll('.parcel-tab').forEach(t => {
        t.classList.remove('bg-primary', 'text-on-primary');
        t.classList.add('bg-surface-container-low', 'text-on-surface-variant');
      });
      tab.classList.remove('bg-surface-container-low', 'text-on-surface-variant');
      tab.classList.add('bg-primary', 'text-on-primary');

      document.getElementById('section-in').classList.toggle('hidden', target !== 'in');
      document.getElementById('section-out').classList.toggle('hidden', target !== 'out');
    });
  });

  // Search
  document.getElementById('search')?.addEventListener('input', (e) => {
    const q = e.target.value.toLowerCase();
    document.querySelectorAll('#tbody-in .parcel-in-row, #tbody-out tr').forEach(r => {
      r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });

  // Sign Out button → opens modal
  function bindSignOut(btn) {
    btn.addEventListener('click', () => {
      activeRow = btn.closest('.parcel-in-row');
      if (!activeRow) return;
      const desc = activeRow.querySelector('.font-headline-sm')?.textContent || 'Parcel';
      const forWho = activeRow.querySelectorAll('td')[4]?.querySelector('.font-semibold')?.textContent || '';
      signoutDesc.textContent = 'Signing out: ' + desc + ' (for ' + forWho + ')';
      document.getElementById('so-who').value = forWho;
      modal.classList.remove('hidden');
      setTimeout(() => document.getElementById('so-who').focus(), 100);
    });
  }
  document.querySelectorAll('.btn-out').forEach(bindSignOut);

  // Close modal
  document.getElementById('so-cancel')?.addEventListener('click', () => modal.classList.add('hidden'));
  document.getElementById('signout-backdrop')?.addEventListener('click', () => modal.classList.add('hidden'));
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') modal.classList.add('hidden'); });

  // Confirm sign out → move row from IN to OUT
  signoutForm?.addEventListener('submit', (e) => {
    e.preventDefault();
    if (!activeRow) return;
    const who = document.getElementById('so-who').value;
    const now = new Date();
    const timeOut = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
    const timeIn = activeRow.querySelectorAll('td')[1]?.querySelector('.font-semibold')?.textContent || '';
    const desc = activeRow.querySelector('.font-headline-sm')?.textContent || '';
    const tracking = activeRow.querySelector('.font-body-sm')?.textContent || '';
    const carrier = activeRow.querySelectorAll('td')[3]?.textContent?.trim() || '';
    const forDept = activeRow.querySelectorAll('td')[4]?.querySelector('.font-body-sm')?.textContent || '';

    // Add to OUT table
    const outRow = document.createElement('tr');
    outRow.className = 'hover:bg-surface-container-low/60 transition-colors';
    outRow.innerHTML = `
      <td class="py-3 px-space-md font-label-mono text-label-mono text-on-surface-variant">${timeIn}</td>
      <td class="py-3 px-space-md font-label-mono text-label-mono text-on-surface font-semibold">${timeOut}</td>
      <td class="py-3 px-space-md">
        <div class="font-headline-sm text-headline-sm text-on-surface font-semibold">${desc}</div>
        <div class="font-body-sm text-body-sm text-on-surface-variant">${tracking}</div>
      </td>
      <td class="py-3 px-space-md font-label-lg text-label-lg text-on-surface">${carrier}</td>
      <td class="py-3 px-space-md">
        <div class="font-label-lg text-label-lg text-on-surface font-semibold">${who}</div>
        <div class="font-body-sm text-body-sm text-on-surface-variant">${forDept}</div>
      </td>
      <td class="py-3 px-space-md">
        <span class="inline-flex items-center gap-1 font-label-md text-label-md text-tertiary font-semibold">
          <span class="material-symbols-outlined text-[16px]">check_circle</span> Yes
        </span>
      </td>
    `;
    document.getElementById('tbody-out').prepend(outRow);

    // Remove from IN table
    activeRow.remove();
    modal.classList.add('hidden');
    signoutForm.reset();
    updateCounts();
    showToast('Parcel signed out to ' + who + ' ✅');
    activeRow = null;
  });

  // Remind buttons
  function bindRemind(btn) {
    btn.addEventListener('click', () => {
      showToast('Reminder sent to ' + (btn.dataset.name || 'recipient') + ' ✅');
    });
  }
  document.querySelectorAll('.btn-remind').forEach(bindRemind);

  // Log new parcel IN
  document.getElementById('log-form')?.addEventListener('submit', (e) => {
    e.preventDefault();
    const desc = document.getElementById('f-desc').value;
    const carrier = document.getElementById('f-carrier').value;
    const tracking = document.getElementById('f-tracking').value;
    const forWho = document.getElementById('f-for').value;
    const stored = document.getElementById('f-stored').value || 'Front Desk';
    const urgent = document.getElementById('f-urgent').checked;
    const now = new Date();
    const timeStr = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });

    const row = document.createElement('tr');
    row.className = 'parcel-in-row hover:bg-surface-container-low/60 transition-colors';
    if (urgent) row.dataset.urgent = 'true';
    row.innerHTML = `
      <td class="py-3 px-space-md">
        <span class="w-2.5 h-2.5 rounded-full ${urgent ? 'bg-error' : 'bg-surface-container-high'} inline-block" ${urgent ? 'title="Urgent"' : ''}></span>
      </td>
      <td class="py-3 px-space-md">
        <div class="font-label-mono text-label-mono text-on-surface font-semibold">${timeStr}</div>
      </td>
      <td class="py-3 px-space-md">
        <div class="font-headline-sm text-headline-sm text-on-surface font-semibold">${desc}</div>
        ${tracking ? `<div class="font-body-sm text-body-sm text-on-surface-variant">#${tracking}</div>` : ''}
      </td>
      <td class="py-3 px-space-md font-label-lg text-label-lg text-on-surface">${carrier}</td>
      <td class="py-3 px-space-md">
        <div class="font-label-lg text-label-lg text-on-surface font-semibold">${forWho}</div>
      </td>
      <td class="py-3 px-space-md font-label-lg text-label-lg text-on-surface">${stored}</td>
      <td class="py-3 px-space-md text-right">
        <div class="flex items-center justify-end gap-space-xs">
          <button type="button" class="btn-remind w-8 h-8 rounded-lg hover:bg-surface-container flex items-center justify-center text-on-surface-variant hover:text-primary transition-colors" title="Send reminder" data-name="${forWho}">
            <span class="material-symbols-outlined text-[18px]">sms</span>
          </button>
          <button type="button" class="btn-out px-space-md py-1.5 rounded-lg bg-primary hover:bg-primary-container text-on-primary font-label-lg text-label-lg font-semibold transition-all flex items-center gap-1">
            <span class="material-symbols-outlined text-[16px]">outbox</span>
            Sign Out
          </button>
        </div>
      </td>
    `;

    // Bind events on new row
    bindSignOut(row.querySelector('.btn-out'));
    const remindBtn = row.querySelector('.btn-remind');
    if (remindBtn) bindRemind(remindBtn);

    document.getElementById('tbody-in').prepend(row);
    e.target.reset();
    updateCounts();
    showToast('Parcel logged in for ' + forWho + ' ✅');
  });

  // Export Parcels to CSV
  window.exportDeliveriesCsv = function() {
    const inRows = Array.from(document.querySelectorAll('#tbody-in tr:not(#empty-in-row)'));
    const outRows = Array.from(document.querySelectorAll('#tbody-out tr:not(#empty-out-row)'));

    if (inRows.length === 0 && outRows.length === 0) {
      showToast('No parcels logged to export');
      return;
    }

    const headers = ['Type / Status', 'Time Logged', 'Description', 'Carrier', 'Recipient / For', 'Storage Location', 'Signed Out Time', 'Collected By'];
    const csvRows = [headers.join(',')];

    inRows.forEach(r => {
      const cells = r.querySelectorAll('td');
      if (cells.length >= 6) {
        const time = cells[1]?.innerText?.trim() || '';
        const desc = cells[2]?.innerText?.trim().replace(/\n/g, ' ') || '';
        const carrier = cells[3]?.innerText?.trim() || '';
        const forWho = cells[4]?.innerText?.trim() || '';
        const storage = cells[5]?.innerText?.trim() || '';
        csvRows.push([
          '"PARCEL IN (PENDING)"',
          `"${time.replace(/"/g, '""')}"`,
          `"${desc.replace(/"/g, '""')}"`,
          `"${carrier.replace(/"/g, '""')}"`,
          `"${forWho.replace(/"/g, '""')}"`,
          `"${storage.replace(/"/g, '""')}"`,
          '"—"',
          '"—"'
        ].join(','));
      }
    });

    outRows.forEach(r => {
      const cells = r.querySelectorAll('td');
      if (cells.length >= 6) {
        const timeIn = cells[0]?.innerText?.trim() || '';
        const timeOut = cells[1]?.innerText?.trim() || '';
        const desc = cells[2]?.innerText?.trim().replace(/\n/g, ' ') || '';
        const carrier = cells[3]?.innerText?.trim() || '';
        const collectedBy = cells[4]?.innerText?.trim().replace(/\n/g, ' ') || '';
        csvRows.push([
          '"PARCEL OUT (COLLECTED)"',
          `"${timeIn.replace(/"/g, '""')}"`,
          `"${desc.replace(/"/g, '""')}"`,
          `"${carrier.replace(/"/g, '""')}"`,
          `"${collectedBy.replace(/"/g, '""')}"`,
          '"Collected"',
          `"${timeOut.replace(/"/g, '""')}"`,
          `"${collectedBy.replace(/"/g, '""')}"`
        ].join(','));
      }
    });

    const csvContent = '\uFEFF' + csvRows.join('\r\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.setAttribute('href', url);
    link.setAttribute('download', `parcels_register_${new Date().toISOString().slice(0, 10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
    showToast(`Exported ${inRows.length + outRows.length} parcels to CSV`);
  };

  // "+ New Parcel In" header button
  document.getElementById('btn-add')?.addEventListener('click', () => {
    document.getElementById('log-section')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    setTimeout(() => document.getElementById('f-desc')?.focus(), 300);
  });
});
</script>
@endsection
