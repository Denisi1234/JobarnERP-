import './bootstrap';
import { initAll } from 'govuk-frontend';
initAll();
import 'carbon-components';

// IBM Carbon singleton toast — single source placement, 5000ms, pause on hover, role=status
(function () {
  let toastEl, hideTimer, paused = false;
  function ensureToast() {
    if (toastEl) return toastEl;
    toastEl = document.createElement('div');
    toastEl.id = 'app-toast-singleton';
    toastEl.setAttribute('role', 'status');
    toastEl.setAttribute('aria-live', 'polite');
    toastEl.setAttribute('aria-atomic', 'true');
    toastEl.className = 'fixed bottom-6 right-6 z-[60] hidden items-center gap-3 px-4 py-3 border text-sm';
    toastEl.style.cssText = 'background:#161616;color:#fff;border:1px solid #393939;font-family:IBM Plex Sans,sans-serif;max-width:28rem;';
    toastEl.innerHTML = '<span class="material-symbols-outlined text-[18px] shrink-0" aria-hidden="true" id="app-toast-icon">check_circle</span><span id="app-toast-msg" class="flex-1 leading-5"></span><button type="button" aria-label="Close notification" class="ml-2 flex h-6 w-6 items-center justify-center text-white/70 hover:text-white shrink-0"><span class="material-symbols-outlined text-[16px]">close</span></button>';
    toastEl.querySelector('button').addEventListener('click', () => hide());
    toastEl.addEventListener('mouseenter', () => { paused = true; clearTimeout(hideTimer); });
    toastEl.addEventListener('mouseleave', () => { paused = false; hideTimer = setTimeout(hide, 1500); });
    document.body.appendChild(toastEl);
    return toastEl;
  }
  function hide() { const el = document.getElementById('app-toast-singleton'); if (el) { el.classList.add('hidden'); el.classList.remove('flex'); } }
  function show(msg, variant = 'success') {
    const el = ensureToast();
    const icon = el.querySelector('#app-toast-icon');
    const msgEl = el.querySelector('#app-toast-msg');
    msgEl.textContent = String(msg).replace(/✅/g, '').trim();
    if (variant === 'error') { el.style.borderLeft = '4px solid #da1e28'; icon.textContent = 'error'; icon.style.color = '#ffb3b8'; }
    else if (variant === 'warning') { el.style.borderLeft = '4px solid #f1c21b'; icon.textContent = 'warning'; icon.style.color = '#f1c21b'; }
    else { el.style.borderLeft = '4px solid #24a148'; icon.textContent = 'check_circle'; icon.style.color = '#a7f0ba'; }
    el.classList.remove('hidden'); el.classList.add('flex');
    clearTimeout(hideTimer);
    hideTimer = setTimeout(() => { if (!paused) hide(); }, 5000);
  }
  window.AppToast = { show, showSuccess: (m)=>show(m,'success'), showError: (m)=>show(m,'error'), showWarning: (m)=>show(m,'warning'), hide };
  window.showToast = function(msg, arg2) {
    // backward compat: showToast(msg), showToast(msg, icon), showToast(msg, isError)
    let variant = 'success';
    if (arg2 === true || arg2 === 'error') variant = 'error';
    else if (arg2 === 'warning') variant = 'warning';
    window.AppToast.show(msg, variant);
  };
})();
