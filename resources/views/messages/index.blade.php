@extends('reception.layout')
@section('content')
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<link href="https://unpkg.com/carbon-components@11.42.0/css/carbon-components.min.css" rel="stylesheet">
<style>*{font-family:'IBM Plex Sans',sans-serif} .mono{font-family:'IBM Plex Mono',monospace} [x-cloak]{display:none!important} .cds--tile,.cds--btn{border-radius:0!important}</style>

<div class="min-h-[70vh] bg-[#f4f4f4] -m-6 p-0">
  <div class="mx-auto max-w-[1480px] px-6 lg:px-8 py-6">
    <div class="bg-white border border-[#e0e0e0] border-t-[3px] border-t-[#0f62fe] px-5 py-4 mb-4 flex items-center justify-between">
      <div>
        <nav class="flex items-center gap-1.5 text-xs text-[#525252]"><a href="{{ match($portal){'Reception'=>route('reception.dashboard'),'IT'=>route('it.index'),'Sales'=>route('sales.index'),'Manager'=>route('manager.index'), default=>route('reception.dashboard')} }}" class="hover:text-[#161616] hover:underline">Home</a><span class="text-[#8d8d8d]">/</span><span class="font-semibold text-[#161616]">{{ $portal }} — Messages</span></nav>
        <h1 class="mt-1 text-[22px] font-semibold tracking-tight text-[#161616]" style="letter-spacing:-0.02em">Messages</h1>
      </div>
      <span class="mono text-xs text-[#525252] border border-[#e0e0e0] bg-[#f4f4f4] px-2.5 py-1">{{ $conversations->count() }} conversations</span>
    </div>

    <div class="bg-white border border-[#e0e0e0] flex overflow-hidden" style="height:640px">
      <!-- Sidebar -->
      <div class="w-[360px] shrink-0 border-r border-[#e0e0e0] flex flex-col bg-white">
        <div class="px-3 py-3 border-b border-[#e0e0e0] bg-[#f4f4f4] flex items-center gap-2">
          <span class="material-symbols-outlined text-[18px] text-[#525252]">search</span>
          <input id="conv-search" placeholder="Search" class="flex-1 bg-white border border-[#8d8d8d] px-2 py-1.5 text-sm text-[#161616] placeholder:text-[#8d8d8d] focus:border-[#0f62fe] focus:outline-none" style="font-family:'IBM Plex Sans',sans-serif">
        </div>
        <div id="conv-list" class="flex-1 overflow-y-auto">
          @forelse($conversations as $idx=>$c)
          <button data-key="{{ $idx }}" data-name="{{ $c['name'] }}" data-handle="{{ $c['handle'] }}" class="conv-item w-full text-left px-4 py-3 border-b border-[#e0e0e0] hover:bg-[#f4f4f4] flex gap-3 items-start {{ $idx===0 ? 'bg-[#edf5ff] border-l-[3px] border-l-[#0f62fe]' : 'bg-white border-l-[3px] border-l-transparent' }}">
            <div class="w-8 h-8 bg-[#0f62fe] text-white flex items-center justify-center text-xs font-semibold shrink-0">{{ strtoupper(substr($c['name'],0,1)) }}</div>
            <div class="flex-1 min-w-0">
              <div class="flex justify-between gap-2"><span class="text-sm font-semibold text-[#161616] truncate">{{ $c['name'] }}</span><span class="mono text-[11px] text-[#525252] shrink-0">{{ $c['last']->created_at->format('H:i') }}</span></div>
              <p class="text-xs text-[#525252] truncate">{{ \Str::limit($c['last']->body, 42) }}</p>
              @if($c['unread']>0)<span class="mt-1 inline-flex bg-[#da1e28] text-white text-[11px] font-bold px-1.5 py-0.5">{{ $c['unread'] }} new</span>@endif
            </div>
          </button>
          @empty
          <div class="p-8 text-center">
            <span class="material-symbols-outlined text-[32px] text-[#8d8d8d]">mail</span>
            <p class="mt-2 text-sm font-semibold text-[#161616]">No conversations</p>
            <p class="text-xs text-[#525252] mt-1">Messages will appear here</p>
          </div>
          @endforelse
        </div>
      </div>

      <!-- Thread -->
      <div class="flex-1 flex flex-col min-w-0 bg-[#f4f4f4]">
        <div id="thread-head" class="px-5 py-3 border-b border-[#e0e0e0] bg-white flex items-center justify-between">
          <div class="flex items-center gap-3 min-w-0">
            <div id="thread-avatar" class="w-8 h-8 bg-[#525252] text-white flex items-center justify-center text-xs font-semibold">—</div>
            <div class="min-w-0">
              <p id="thread-name" class="text-sm font-semibold text-[#161616] truncate">Select a conversation</p>
              <p id="thread-sub" class="mono text-[11px] text-[#525252] truncate"></p>
            </div>
          </div>
          <span class="mono text-[11px] text-[#525252] border border-[#e0e0e0] bg-[#f4f4f4] px-2 py-1" id="thread-count"></span>
        </div>
        <div id="thread-body" class="flex-1 overflow-y-auto p-5 flex flex-col gap-3 bg-[#f4f4f4]">
          <div id="thread-empty" class="flex-1 flex flex-col items-center justify-center text-center py-16">
            <span class="material-symbols-outlined text-[40px] text-[#8d8d8d]">chat</span>
            <p class="mt-2 text-sm text-[#525252]">Choose a conversation to read</p>
          </div>
        </div>
        <div id="thread-input" class="p-3 border-t border-[#e0e0e0] bg-white hidden">
          <div class="flex gap-2">
            <textarea id="msg-input" rows="2" placeholder="Type a message" class="flex-1 border border-[#8d8d8d] bg-white p-3 text-sm text-[#161616] placeholder:text-[#8d8d8d] focus:border-[#0f62fe] focus:outline-none resize-none" style="font-family:'IBM Plex Sans',sans-serif"></textarea>
            <button id="msg-send" class="self-end bg-[#0f62fe] hover:bg-[#0353e9] text-white px-5 py-3 text-sm font-semibold border border-[#0f62fe] flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">send</span> Send</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
const conversations = @json($conversationsJson ?? []);
let activeIdx = conversations.length ? 0 : null;
const meName = @json(auth()->user()->name);
function renderThread(idx){
  if(idx===null || !conversations[idx]) return;
  activeIdx = idx;
  const c = conversations[idx];
  document.getElementById('thread-name').textContent = c.name;
  document.getElementById('thread-sub').textContent = c.handle || '';
  document.getElementById('thread-avatar').textContent = c.name.charAt(0).toUpperCase();
  document.getElementById('thread-count').textContent = c.msgs.length + ' messages';
  document.getElementById('thread-input').classList.remove('hidden');
  document.getElementById('thread-empty')?.classList.add('hidden');
  document.querySelectorAll('.conv-item').forEach((el,i)=>{
    el.className = i===idx ? 'conv-item w-full text-left px-4 py-3 border-b border-[#e0e0e0] flex gap-3 items-start bg-[#edf5ff] border-l-[3px] border-l-[#0f62fe]' : 'conv-item w-full text-left px-4 py-3 border-b border-[#e0e0e0] flex gap-3 items-start bg-white border-l-[3px] border-l-transparent hover:bg-[#f4f4f4]';
  });
  const body = document.getElementById('thread-body');
  body.innerHTML = '';
  c.msgs.forEach(m=>{
    const div = document.createElement('div');
    div.className = m.mine ? 'self-end max-w-[72%] bg-[#0f62fe] text-white p-3 text-sm' : 'self-start max-w-[72%] bg-white border border-[#e0e0e0] p-3 text-sm text-[#161616]';
    div.style.fontFamily = "'IBM Plex Sans',sans-serif";
    const b = document.createElement('div'); b.textContent = m.body; div.appendChild(b);
    const meta = document.createElement('div'); meta.className = `mono text-[10px] ${m.mine ? 'text-[#d0e2ff]' : 'text-[#525252]'} mt-1`; meta.textContent = `${m.time} · ${m.from}`; div.appendChild(meta);
    body.appendChild(div);
  });
  body.scrollTop = body.scrollHeight;
}
document.querySelectorAll('.conv-item').forEach(el=> el.addEventListener('click',()=> renderThread(parseInt(el.dataset.key))));
document.getElementById('conv-search')?.addEventListener('input', e=>{
  const q = e.target.value.toLowerCase();
  document.querySelectorAll('.conv-item').forEach(el=>{
    el.style.display = el.dataset.name.toLowerCase().includes(q) ? '' : 'none';
  });
});
if(conversations.length) renderThread(0);
document.getElementById('msg-send')?.addEventListener('click', async ()=>{
  const input = document.getElementById('msg-input');
  const text = input.value.trim();
  if(!text || activeIdx===null) return;
  const handle = conversations[activeIdx].handle;
  const btn = document.getElementById('msg-send');
  const orig = btn.innerHTML; btn.disabled=true; btn.textContent='Sending...';
  try{
    const res = await fetch(@json(route('messages.store')), {method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content||''}, body:JSON.stringify({ recipient_handle: handle, recipient_name: conversations[activeIdx].name, body: text })});
    const data = await res.json();
    if(!res.ok || !data.success) throw new Error(data.message||'Failed');
    // append optimistically — hardened: textContent, no innerHTML
    const time = new Date().toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
    const body = document.getElementById('thread-body');
    const div = document.createElement('div');
    div.className = 'self-end max-w-[72%] bg-[#0f62fe] text-white p-3 text-sm';
    const b2 = document.createElement('div'); b2.textContent = text; div.appendChild(b2);
    const meta2 = document.createElement('div'); meta2.className = 'mono text-[10px] text-[#d0e2ff] mt-1'; meta2.textContent = `${time} · ${meName}`; div.appendChild(meta2);
    body.appendChild(div);
    body.scrollTop = body.scrollHeight;
    input.value='';
  }catch(err){ alert(err.message||'Send failed'); }
  finally{ btn.disabled=false; btn.innerHTML=orig; }
});
document.getElementById('msg-input')?.addEventListener('keydown', e=>{ if(e.key==='Enter' && !e.shiftKey){ e.preventDefault(); document.getElementById('msg-send').click(); }});
// Polling — real chat refresh every 10s (hardened, no websocket needed)
let lastUnread = {{ $conversations->sum('unread') }};
setInterval(async ()=>{
  try{
    const r = await fetch(@json(route('messages.unread')), {headers:{'Accept':'application/json'}});
    const d = await r.json();
    if(d.unread !== lastUnread){ location.reload(); }
  }catch(e){}
}, 10000);
</script>
@endsection
