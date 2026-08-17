/**
 * DONMA Custom Modal System
 * Replaces browser confirm() and alert() with branded modals.
 * Usage:
 *   DonmaModal.confirm({ title, message, confirmText, cancelText, type, onConfirm })
 *   DonmaModal.alert({ title, message, type })
 *   DonmaModal.toast({ message, type })       — non-blocking top-right toast
 */
(function (global) {
  'use strict';

  // ── Inject CSS once ───────────────────────────────────────────────────────
  const STYLE_ID = 'donma-modal-css';
  if (!document.getElementById(STYLE_ID)) {
    const style = document.createElement('style');
    style.id = STYLE_ID;
    style.textContent = `
/* ── Overlay ── */
.dm-overlay {
  position: fixed; inset: 0; z-index: 9998;
  background: rgba(0,0,0,.55);
  backdrop-filter: blur(3px);
  display: flex; align-items: center; justify-content: center;
  padding: 1rem;
  opacity: 0; transition: opacity .2s ease;
}
.dm-overlay.dm-show { opacity: 1; }

/* ── Modal card ── */
.dm-modal {
  background: var(--cui-body-bg, #fff);
  border-radius: 16px;
  box-shadow: 0 24px 64px rgba(0,0,0,.25);
  width: 100%; max-width: 420px;
  transform: scale(.92) translateY(12px);
  transition: transform .22s ease, opacity .22s ease;
  opacity: 0;
  overflow: hidden;
}
.dm-overlay.dm-show .dm-modal { transform: scale(1) translateY(0); opacity: 1; }

/* ── Icon strip ── */
.dm-icon-strip {
  padding: 1.75rem 1.5rem .75rem;
  text-align: center;
}
.dm-icon-circle {
  width: 64px; height: 64px; border-radius: 50%;
  display: inline-flex; align-items: center; justify-content: center;
  font-size: 1.75rem; margin-bottom: .5rem;
}
.dm-type-danger  .dm-icon-circle { background: #fee2e2; color: #dc2626; }
.dm-type-warning .dm-icon-circle { background: #fef3c7; color: #d97706; }
.dm-type-success .dm-icon-circle { background: #dcfce7; color: #16a34a; }
.dm-type-info    .dm-icon-circle { background: #dbeafe; color: #1d4ed8; }
.dm-type-question .dm-icon-circle { background: #ede9fe; color: #7c3aed; }

/* ── Body ── */
.dm-body { padding: 0 1.75rem 1.25rem; text-align: center; }
.dm-title { font-size: 1.05rem; font-weight: 700; margin-bottom: .4rem; color: var(--cui-body-color, #1e293b); }
.dm-message { font-size: .875rem; color: var(--cui-secondary-color, #64748b); line-height: 1.6; }

/* ── Footer ── */
.dm-footer {
  padding: 1rem 1.5rem 1.5rem;
  display: flex; gap: .6rem; justify-content: center; flex-wrap: wrap;
}
.dm-btn {
  padding: .6rem 1.5rem; border-radius: 50px; font-size: .875rem;
  font-weight: 600; cursor: pointer; border: 2px solid transparent;
  transition: transform .15s, box-shadow .15s, background .15s;
  min-width: 90px;
}
.dm-btn:hover { transform: translateY(-1px); }
.dm-btn:active { transform: translateY(0); }
.dm-btn-confirm-danger  { background: #dc2626; color: #fff; }
.dm-btn-confirm-danger:hover  { background: #b91c1c; box-shadow: 0 4px 14px rgba(220,38,38,.35); }
.dm-btn-confirm-warning { background: #d97706; color: #fff; }
.dm-btn-confirm-warning:hover { background: #b45309; box-shadow: 0 4px 14px rgba(217,119,6,.35); }
.dm-btn-confirm-success { background: #16a34a; color: #fff; }
.dm-btn-confirm-success:hover { background: #15803d; box-shadow: 0 4px 14px rgba(22,163,74,.35); }
.dm-btn-confirm-info, .dm-btn-confirm-question { background: #1d4ed8; color: #fff; }
.dm-btn-confirm-info:hover, .dm-btn-confirm-question:hover { background: #1e40af; box-shadow: 0 4px 14px rgba(29,78,216,.35); }
.dm-btn-cancel { background: transparent; color: var(--cui-body-color,#374151); border-color: #d1d5db; }
.dm-btn-cancel:hover { background: #f3f4f6; }

/* ── Toast ── */
.dm-toast-container {
  position: fixed; top: 1.25rem; right: 1.25rem;
  z-index: 9999; display: flex; flex-direction: column; gap: .5rem;
  pointer-events: none;
}
.dm-toast {
  background: var(--cui-body-bg, #fff);
  border-radius: 12px; padding: .85rem 1.25rem;
  box-shadow: 0 8px 24px rgba(0,0,0,.15);
  display: flex; align-items: center; gap: .65rem;
  min-width: 240px; max-width: 360px;
  border-left: 4px solid #1d4ed8;
  pointer-events: all;
  animation: dmToastIn .25s ease both;
}
@keyframes dmToastIn { from{opacity:0;transform:translateX(20px)} to{opacity:1;transform:translateX(0)} }
@keyframes dmToastOut { to{opacity:0;transform:translateX(20px)} }
.dm-toast.dm-toast-out { animation: dmToastOut .2s ease both; }
.dm-toast-danger  { border-color: #dc2626; }
.dm-toast-warning { border-color: #d97706; }
.dm-toast-success { border-color: #16a34a; }
.dm-toast-info    { border-color: #1d4ed8; }
.dm-toast-icon { font-size: 1.1rem; flex-shrink: 0; }
.dm-toast-msg  { font-size: .85rem; font-weight: 500; color: var(--cui-body-color,#1e293b); flex: 1; }
.dm-toast-close { background: none; border: none; cursor: pointer; font-size: 1rem; color: #9ca3af; padding: 0; line-height: 1; pointer-events: all; }
.dm-toast-close:hover { color: #374151; }
    `;
    document.head.appendChild(style);
  }

  // ── Icon map ──────────────────────────────────────────────────────────────
  const ICONS = {
    danger:   '🗑️',
    warning:  '⚠️',
    success:  '✅',
    info:     'ℹ️',
    question: '❓',
  };

  // ── Build overlay ─────────────────────────────────────────────────────────
  function buildOverlay(type, iconOverride) {
    const overlay = document.createElement('div');
    overlay.className = `dm-overlay dm-type-${type}`;

    const modal = document.createElement('div');
    modal.className = 'dm-modal';

    const strip = document.createElement('div');
    strip.className = 'dm-icon-strip';
    strip.innerHTML = `<div class="dm-icon-circle">${iconOverride || ICONS[type] || 'ℹ️'}</div>`;

    modal.appendChild(strip);
    overlay.appendChild(modal);
    document.body.appendChild(overlay);

    // Animate in
    requestAnimationFrame(() => requestAnimationFrame(() => overlay.classList.add('dm-show')));

    return { overlay, modal };
  }

  function closeOverlay(overlay, cb) {
    overlay.classList.remove('dm-show');
    setTimeout(() => { overlay.remove(); if (cb) cb(); }, 220);
  }

  // ── confirm() replacement ─────────────────────────────────────────────────
  function showConfirm({
    title        = 'Are you sure?',
    message      = '',
    confirmText  = 'Confirm',
    cancelText   = 'Cancel',
    type         = 'danger',
    icon         = null,
    onConfirm    = () => {},
    onCancel     = () => {},
  } = {}) {
    const { overlay, modal } = buildOverlay(type, icon);

    const body = document.createElement('div');
    body.className = 'dm-body';
    body.innerHTML = `
      <div class="dm-title">${title}</div>
      ${message ? `<div class="dm-message">${message}</div>` : ''}
    `;

    const footer = document.createElement('div');
    footer.className = 'dm-footer';

    const btnCancel = document.createElement('button');
    btnCancel.className = 'dm-btn dm-btn-cancel';
    btnCancel.textContent = cancelText;
    btnCancel.onclick = () => closeOverlay(overlay, onCancel);

    const btnConfirm = document.createElement('button');
    btnConfirm.className = `dm-btn dm-btn-confirm-${type}`;
    btnConfirm.textContent = confirmText;
    btnConfirm.onclick = () => closeOverlay(overlay, onConfirm);

    footer.appendChild(btnCancel);
    footer.appendChild(btnConfirm);
    modal.appendChild(body);
    modal.appendChild(footer);

    // Close on overlay click
    overlay.addEventListener('click', (e) => { if (e.target === overlay) closeOverlay(overlay, onCancel); });
    // ESC key
    const onKey = (e) => { if (e.key === 'Escape') { closeOverlay(overlay, onCancel); document.removeEventListener('keydown', onKey); } };
    document.addEventListener('keydown', onKey);

    btnConfirm.focus();
  }

  // ── alert() replacement ───────────────────────────────────────────────────
  function showAlert({
    title   = 'Notice',
    message = '',
    type    = 'info',
    icon    = null,
    btnText = 'OK',
    onClose = () => {},
  } = {}) {
    const { overlay, modal } = buildOverlay(type, icon);

    const body = document.createElement('div');
    body.className = 'dm-body';
    body.innerHTML = `
      <div class="dm-title">${title}</div>
      ${message ? `<div class="dm-message">${message}</div>` : ''}
    `;

    const footer = document.createElement('div');
    footer.className = 'dm-footer';

    const btn = document.createElement('button');
    btn.className = `dm-btn dm-btn-confirm-${type}`;
    btn.textContent = btnText;
    btn.onclick = () => closeOverlay(overlay, onClose);

    footer.appendChild(btn);
    modal.appendChild(body);
    modal.appendChild(footer);

    overlay.addEventListener('click', (e) => { if (e.target === overlay) closeOverlay(overlay, onClose); });
    const onKey = (e) => { if (e.key === 'Escape' || e.key === 'Enter') { closeOverlay(overlay, onClose); document.removeEventListener('keydown', onKey); } };
    document.addEventListener('keydown', onKey);

    btn.focus();
  }

  // ── toast() ───────────────────────────────────────────────────────────────
  function showToast({ message = '', type = 'info', duration = 3500 } = {}) {
    let container = document.querySelector('.dm-toast-container');
    if (!container) {
      container = document.createElement('div');
      container.className = 'dm-toast-container';
      document.body.appendChild(container);
    }

    const toastIcons = { danger:'❌', warning:'⚠️', success:'✅', info:'ℹ️' };
    const toast = document.createElement('div');
    toast.className = `dm-toast dm-toast-${type}`;
    toast.innerHTML = `
      <span class="dm-toast-icon">${toastIcons[type] || 'ℹ️'}</span>
      <span class="dm-toast-msg">${message}</span>
      <button class="dm-toast-close" aria-label="Close">✕</button>
    `;

    const remove = () => {
      toast.classList.add('dm-toast-out');
      setTimeout(() => toast.remove(), 200);
    };

    toast.querySelector('.dm-toast-close').onclick = remove;
    container.appendChild(toast);
    setTimeout(remove, duration);
  }

  // ── Expose globally ───────────────────────────────────────────────────────
  global.DonmaModal = {
    confirm : showConfirm,
    alert   : showAlert,
    toast   : showToast,
  };

})(window);
