@extends('reception.layout')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
  *{font-family:'IBM Plex Sans',sans-serif}
  .mono{font-family:'IBM Plex Mono',monospace}
  [x-cloak]{display:none!important}
</style>
<div class="flex flex-col w-full space-y-6" style="max-width:1480px; margin-inline:auto">

    {{-- ───────────────── HEADER — IBM polished ───────────────── --}}
    <div class="bg-white border border-[#e0e0e0] border-t-[3px] border-t-[#0f62fe] p-5 lg:p-6 mb-6">
        <nav class="flex items-center gap-2 text-xs text-[#525252] mb-3">
            <a href="{{ route('reception.dashboard') }}" class="hover:text-[#161616] hover:underline underline-offset-4">Reception</a>
            <span class="text-[#8d8d8d]">/</span>
            <span class="font-semibold text-[#161616]">Staff Directory</span>
            <span class="mono ml-2 border border-[#d0e2ff] bg-[#edf5ff] px-2 py-0.5 text-[11px] font-medium text-[#0f62fe]">{{ isset($employees) ? $employees->count() : 0 }} members</span>
        </nav>
        <h1 class="text-[26px] font-semibold tracking-tight text-[#161616]" style="letter-spacing:-0.02em; font-family:'IBM Plex Sans',sans-serif">Staff Directory</h1>
        <p class="text-sm leading-6 text-[#525252] mt-1.5" style="font-family:'IBM Plex Sans',sans-serif">{{ now()->format('l, d F Y') }} — View team and start a conversation</p>
    </div>

  <div class="flex items-center gap-1 bg-white border border-[#e0e0e0] p-1">
    <div class="flex-1 py-2.5 flex items-center justify-center gap-2 text-xs font-semibold bg-[#0f62fe] border border-[#0f62fe] text-white">
      <span class="material-symbols-outlined text-[16px]">badge</span>
      <span>Staff Directory</span>
      <span class="ml-1 px-1.5 py-0.5 border border-white/30 bg-white/20 text-white font-bold text-[10px]" style="font-family:'IBM Plex Mono',monospace" id="count-staff">
        {{ isset($employees) ? $employees->count() : 4 }}
      </span>
    </div>
  </div>
  <div class="border border-[#e0e0e0] border-t-0 bg-[#f4f4f4] px-4 py-2.5 flex items-center gap-2 text-xs text-[#525252]">
    <span class="material-symbols-outlined text-[14px] text-[#8d8d8d]">info</span>
    <span>Directory is view-only — new staff are added by the <strong class="text-[#161616]">Manager</strong> in Staff & Access. Contact your manager to add members.</span>
  </div>

  <!-- TAB 1: STAFF DIRECTORY — IBM polished, view-only -->
  <div id="section-staff" class="bg-white border border-[#e0e0e0] overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left">
        <thead>
          <tr class="bg-[#f4f4f4] border-b border-[#e0e0e0] text-[#525252] text-[11px] font-semibold tracking-widest uppercase">
            <th class="py-3 px-space-md w-12">#</th>
            <th class="py-3 px-space-md">Staff Member &amp; Role</th>
            <th class="py-3 px-space-md">Department</th>
            <th class="py-3 px-space-md">Station / Bench</th>
            <th class="py-3 px-space-md">Internal Chat ID</th>
            <th class="py-3 px-space-md">Status</th>
            <th class="py-3 px-space-md text-right">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-surface-container-low" id="tbody-staff">
          @forelse($employees as $index => $emp)
            @php
              $fullName = $emp->full_name ?: ($emp->first_name . ' ' . $emp->last_name);
              $deptName = $emp->department?->name ?? 'Workshop Staff';
              $roleName = $emp->designation?->name ?? 'Staff';
              $handle = '@' . strtolower(str_replace(' ', '.', $fullName));
              $initials = strtoupper(substr($emp->first_name, 0, 1) . substr($emp->last_name, 0, 1));
              
              $station = match(strtolower($deptName)) {
                'front desk operations' => 'Reception Lobby',
                'information technology' => 'IT Repair Bench',
                'sales & business dev' => 'Sales Counter & POS',
                'administration' => 'Management Desk',
                default => 'Main Workshop'
              };
            @endphp
            <tr class="staff-row hover:bg-surface-container-low/60 transition-colors cursor-pointer"
                data-name="{{ $fullName }}"
                data-role="{{ $roleName }}"
                data-dept="{{ strtolower($deptName) }}"
                data-handle="{{ $handle }}"
                data-location="{{ $station }}"
                data-status="Active">
              <td class="py-3 px-space-md">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block" title="Online"></span>
              </td>
              <td class="py-3 px-space-md">
                <div class="flex items-center gap-space-sm">
                  <div class="w-8 h-8 rounded-lg bg-primary/10 text-primary font-bold flex items-center justify-center font-label-mono text-[11px] shrink-0">
                    {{ $initials ?: 'ST' }}
                  </div>
                  <div class="flex flex-col min-w-0">
                    <span class="font-headline-sm text-headline-sm text-on-surface font-semibold truncate">{{ $fullName }}</span>
                    <span class="font-body-sm text-body-sm text-on-surface-variant truncate">{{ $roleName }}</span>
                  </div>
                </div>
              </td>
              <td class="py-3 px-space-md font-label-lg text-label-lg text-on-surface">{{ $deptName }}</td>
              <td class="py-3 px-space-md font-label-lg text-label-lg text-on-surface-variant">{{ $station }}</td>
              <td class="py-3 px-space-md font-label-mono text-label-mono text-primary font-semibold">{{ $handle }}</td>
              <td class="py-3 px-space-md">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full font-label-md text-label-md bg-emerald-50 text-emerald-700 font-semibold uppercase tracking-wider">Online</span>
              </td>
              <td class="py-3 px-space-md text-right" onclick="event.stopPropagation()">
                <div class="flex items-center justify-end gap-space-xs">
                  <button type="button" class="btn-open-chat px-3 py-1.5 rounded-lg bg-primary hover:bg-primary-container text-on-primary font-label-md text-label-md font-semibold transition-all flex items-center gap-1.5" data-name="{{ $fullName }}" data-handle="{{ $handle }}">
                    <span class="material-symbols-outlined text-[16px]">chat</span>
                    Message
                  </button>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="py-12 text-center text-on-surface-variant">
                <p class="font-headline-sm text-on-surface font-semibold">No staff members in directory</p>
                <p class="font-body-sm text-on-surface-variant mt-1">Staff records will appear here.</p>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>



  <!-- Manager creates new staff — reception is view-only -->

</div>

<!-- ==================== IBM CHAT DRAWER ==================== -->
<div id="chat-drawer" class="fixed inset-0 z-[70] hidden">
  <div id="chat-drawer-backdrop" class="absolute inset-0 bg-black/30 backdrop-blur-sm"></div>
  <aside class="absolute inset-y-0 right-0 max-w-full flex pl-10">
    <div class="w-screen max-w-md bg-white shadow-2xl border-l border-[#e0e0e0] flex flex-col justify-between overflow-hidden">
      <!-- Header — IBM -->
      <div class="px-5 py-4 border-b border-[#e0e0e0] flex items-center justify-between bg-[#f4f4f4] sticky top-0 z-10">
        <div class="flex items-center gap-3 min-w-0">
          <div class="w-9 h-9 bg-[#0f62fe] text-white font-semibold flex items-center justify-center text-xs shrink-0" style="font-family:'IBM Plex Mono',monospace" id="chat-avatar">ST</div>
          <div class="min-w-0">
            <div class="flex items-center gap-1.5">
              <h3 class="text-sm font-semibold text-[#161616] truncate" style="font-family:'IBM Plex Sans',sans-serif" id="chat-target-name">Staff Member</h3>
              <span class="w-2 h-2 rounded-full bg-[#0e6027] shrink-0" title="Online"></span>
              <span class="mono text-[11px] text-[#0e6027] border border-[#a7f0ba] bg-[#defbe6] px-1.5 py-0">Online</span>
            </div>
            <p class="text-[11px] text-[#525252] truncate mono" id="chat-target-sub">Workshop Intercom</p>
          </div>
        </div>
        <button id="chat-drawer-close" class="w-8 h-8 hover:bg-white border border-transparent hover:border-[#e0e0e0] flex items-center justify-center text-[#525252] hover:text-[#161616] transition-colors shrink-0" type="button">
          <span class="material-symbols-outlined text-[20px]">close</span>
        </button>
      </div>
      <!-- Thread — IBM -->
      <div class="p-5 overflow-y-auto flex-1 flex flex-col gap-3 bg-white" id="chat-thread">
        <div class="flex flex-col gap-1 self-start max-w-[85%]">
          <div class="bg-[#f4f4f4] border border-[#e0e0e0] p-3 text-sm text-[#161616]" style="font-family:'IBM Plex Sans',sans-serif">Workshop intercom connected. Messages send directly to staff station.</div>
          <span class="mono text-[10px] text-[#525252] px-1">Workshop Intercom · Online</span>
        </div>
      </div>
      <!-- Presets — IBM tags -->
      <div class="px-5 py-3 bg-white border-t border-[#e0e0e0] flex flex-col gap-2">
        <span class="text-[11px] font-semibold tracking-widest text-[#525252] uppercase">Quick Presets</span>
        <div class="flex flex-wrap gap-1.5">
          <button type="button" class="btn-preset-chip cds--tag cds--tag--cool-gray border border-[#e0e0e0] bg-[#f4f4f4] hover:bg-[#e0e0e0] text-[#525252] px-2.5 py-1 text-xs" data-msg="A customer is waiting at reception with a device for repair.">PC Repair Intake</button>
          <button type="button" class="btn-preset-chip cds--tag cds--tag--blue border border-[#d0e2ff] bg-[#edf5ff] hover:bg-[#d0e2ff] text-[#0f62fe] px-2.5 py-1 text-xs" data-msg="A customer wants to purchase an item / OS license at Sales.">Sales Inquiry</button>
          <button type="button" class="btn-preset-chip cds--tag cds--tag--green border border-[#a7f0ba] bg-[#defbe6] hover:bg-[#a7f0ba] text-[#0e6027] px-2.5 py-1 text-xs" data-msg="Customer has arrived for device pickup and payment collection.">Ready for Pickup</button>
        </div>
      </div>
      <!-- Input — IBM -->
      <div class="p-4 border-t border-[#e0e0e0] bg-[#f4f4f4] flex items-end gap-2">
        <div class="flex-1 relative">
          <textarea id="chat-input" rows="2" placeholder="Type message or click preset above..." class="w-full p-3 border border-[#8d8d8d] bg-white text-sm text-[#161616] focus:border-[#0f62fe] focus:ring-1 focus:ring-[#0f62fe] outline-none resize-none leading-relaxed" style="font-family:'IBM Plex Sans',sans-serif"></textarea>
        </div>
        <button id="btn-send-chat" type="button" class="w-10 h-10 bg-[#0f62fe] hover:bg-[#0353e9] text-white flex items-center justify-center shrink-0 border border-[#0f62fe]">
          <span class="material-symbols-outlined text-[20px]">send</span>
        </button>
      </div>
    </div>
  </aside>
</div>

<!-- TOAST — IBM -->
<div id="toast" class="hidden fixed bottom-6 left-1/2 -translate-x-1/2 z-[80] px-4 py-3 bg-[#161616] text-white text-sm font-medium shadow-xl flex items-center gap-2 border border-[#393939]">
  <span class="material-symbols-outlined text-[18px] text-[#42be65]">check_circle</span>
  <span id="toast-text" class="mono text-[13px]">Message sent</span>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const toast = document.getElementById('toast');
  const toastText = document.getElementById('toast-text');
  const drawer = document.getElementById('chat-drawer');
  const chatInput = document.getElementById('chat-input');
  const chatThread = document.getElementById('chat-thread');
  let currentChatTarget = 'Staff Member';

  function showToast(msg) {
    toastText.textContent = msg;
    toast.classList.remove('hidden');
    setTimeout(() => toast.classList.add('hidden'), 2200);
  }

  function updateStaffCount() {
    const count = document.querySelectorAll('#tbody-staff .staff-row').length;
    document.getElementById('count-staff').textContent = count;
  }

  // Staff directory only — department channels removed

  // Search & Department Filter
  function applyFilters() {
    const q = document.getElementById('search')?.value?.toLowerCase().trim() || '';
    const dept = document.getElementById('filter-dept')?.value || 'all';

    document.querySelectorAll('#tbody-staff .staff-row').forEach(row => {
      const d = row.dataset;
      const text = row.textContent.toLowerCase();

      let matchDept = (dept === 'all') ? true : (d.dept && d.dept.includes(dept));
      let matchSearch = q ? text.includes(q) : true;

      row.style.display = (matchDept && matchSearch) ? '' : 'none';
    });
  }

  document.getElementById('search')?.addEventListener('input', applyFilters);
  document.getElementById('filter-dept')?.addEventListener('change', applyFilters);

  let currentChatHandle = null;
  // Open Chat Drawer — 4 portals
  function openChat(name, sub, initials, handle=null) {
    currentChatTarget = name;
    currentChatHandle = handle || '@' + name.toLowerCase().replace(/\s+/g,'.');
    document.getElementById('chat-target-name').textContent = name;
    document.getElementById('chat-target-sub').textContent = sub || 'Workshop Intercom';
    document.getElementById('chat-avatar').textContent = initials || name.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase();
    drawer.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
    setTimeout(() => chatInput?.focus(), 150);
  }

  function closeChat() {
    drawer.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
  }

  document.getElementById('chat-drawer-close')?.addEventListener('click', closeChat);
  document.getElementById('chat-drawer-backdrop')?.addEventListener('click', closeChat);

  // Send Message — REAL: POST to backend so recipient portal receives (sales/reception/manager/it)
  async function sendMessage() {
    const text = chatInput.value.trim();
    if (!text) return;
    const btn = document.getElementById('btn-send-chat');
    const orig = btn.innerHTML;
    btn.disabled = true; btn.innerHTML = '<span class="material-symbols-outlined text-[18px] animate-spin">progress_activity</span>';
    try {
      const res = await fetch('{{ route('messages.store') }}', {
        method: 'POST',
        headers: {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}'},
        body: JSON.stringify({ recipient_handle: currentChatHandle, recipient_name: currentChatTarget, body: text })
      });
      const data = await res.json();
      if(!res.ok || !data.success) throw new Error(data.message || 'Failed to send');
      const timeStr = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
      const msgDiv = document.createElement('div');
      msgDiv.className = 'flex flex-col gap-1 self-end max-w-[85%]';
      msgDiv.innerHTML = `<div class="bg-[#0f62fe] text-white p-3 border border-[#0f62fe] text-sm" style="font-family:'IBM Plex Sans',sans-serif">${text}</div><span class="mono text-[10px] text-[#525252] self-end px-1">Reception · ${timeStr} · Delivered to ${currentChatTarget} portal ✓</span>`;
      chatThread.appendChild(msgDiv);
      chatThread.scrollTop = chatThread.scrollHeight;
      chatInput.value = '';
      showToast('Delivered to ' + currentChatTarget + ' — check Messages inbox on his portal (Sales/Reception/Manager/IT) ✅');
    } catch(e){
      showToast(e.message || 'Send failed — try again', true);
    } finally {
      btn.disabled = false; btn.innerHTML = orig;
    }
  }

  document.getElementById('btn-send-chat')?.addEventListener('click', sendMessage);
  chatInput?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      sendMessage();
    }
  });

  // Preset chips click
  document.querySelectorAll('.btn-preset-chip').forEach(btn => {
    btn.addEventListener('click', () => {
      chatInput.value = btn.dataset.msg;
      chatInput.focus();
    });
  });

  // Bind Staff Row events — pass handle so backend can resolve to correct portal user (many customers via manager)
  function bindStaffRow(row) {
    row.onclick = () => {
      const name = row.dataset.name;
      const role = row.dataset.role;
      const initials = row.querySelector('td:nth-child(2) .font-bold')?.textContent?.trim() || '';
      openChat(name, role, initials, row.dataset.handle || null);
    };
    row.querySelector('.btn-open-chat')?.addEventListener('click', (e) => {
      e.stopPropagation();
      const name = row.dataset.name;
      const role = row.dataset.role;
      const initials = row.querySelector('td:nth-child(2) .font-bold')?.textContent?.trim() || '';
      openChat(name, role, initials, row.dataset.handle || null);
    });
  }

  document.querySelectorAll('#tbody-staff .staff-row').forEach(bindStaffRow);



  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeChat();
  });
});
</script>
@endsection

