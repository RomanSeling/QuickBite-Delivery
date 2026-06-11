'use strict';

/* ============================================================
   QuickBite – main.js
   ============================================================ */

// ── Modal helpers ─────────────────────────────────────────────
function openModal(id) {
  const overlay = document.getElementById(id);
  if (!overlay) return;
  overlay.classList.add('active');
  document.body.style.overflow = 'hidden';
}

function closeModal(id) {
  const overlay = document.getElementById(id);
  if (!overlay) return;
  overlay.classList.remove('active');
  document.body.style.overflow = '';
}

document.addEventListener('click', (e) => {
  if (e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('active');
    document.body.style.overflow = '';
  }
});

document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay.active').forEach(el => {
      el.classList.remove('active');
      document.body.style.overflow = '';
    });
  }
});

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-modal-open]').forEach(btn =>
    btn.addEventListener('click', () => openModal(btn.dataset.modalOpen))
  );
  document.querySelectorAll('[data-modal-close]').forEach(btn =>
    btn.addEventListener('click', () => closeModal(btn.dataset.modalClose))
  );
  document.querySelectorAll('.modal-close').forEach(btn => {
    btn.addEventListener('click', () => {
      const overlay = btn.closest('.modal-overlay');
      if (overlay) { overlay.classList.remove('active'); document.body.style.overflow = ''; }
    });
  });
});

// ── Toast notifications ───────────────────────────────────────
function showToast(message, type = 'default', duration = 3200) {
  let container = document.querySelector('.toast-container');
  if (!container) {
    container = document.createElement('div');
    container.className = 'toast-container';
    document.body.appendChild(container);
  }
  const icons = {
    success: '✓',
    danger:  '✕',
    default: 'ℹ',
  };
  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  toast.innerHTML = `<span style="font-weight:700">${icons[type] || icons.default}</span><span>${message}</span>`;
  container.appendChild(toast);
  setTimeout(() => {
    toast.style.transition = 'opacity .3s ease, transform .3s ease';
    toast.style.opacity    = '0';
    toast.style.transform  = 'translateX(120%)';
    setTimeout(() => toast.remove(), 300);
  }, duration);
}

// ── Mobile sidebar toggle ─────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  const toggle  = document.getElementById('sidebarToggle');
  const sidebar = document.getElementById('sidebar');
  if (!toggle || !sidebar) return;

  toggle.addEventListener('click', (e) => {
    e.stopPropagation();
    sidebar.classList.toggle('open');
  });
  document.addEventListener('click', (e) => {
    if (sidebar.classList.contains('open') && !sidebar.contains(e.target) && e.target !== toggle) {
      sidebar.classList.remove('open');
    }
  });
});

// ── Password visibility toggle ────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.password-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
      const input = btn.previousElementSibling;
      if (!input) return;
      const isText = input.type === 'text';
      input.type = isText ? 'password' : 'text';
      btn.innerHTML = isText
        ? '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>'
        : '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
    });
  });
});

// ── Orders table – CRUD actions ───────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  // Delete order
  document.querySelectorAll('.btn-delete-order').forEach(btn => {
    btn.addEventListener('click', () => {
      const overlay = document.getElementById('confirmDeleteModal');
      if (!overlay) return;
      overlay.dataset.deleteId = btn.dataset.id;
      overlay.querySelector('#confirmOrderId').textContent = '#' + btn.dataset.id;
      openModal('confirmDeleteModal');
    });
  });

  const confirmBtn = document.getElementById('confirmDeleteBtn');
  if (confirmBtn) {
    confirmBtn.addEventListener('click', () => {
      const overlay = document.getElementById('confirmDeleteModal');
      const id = overlay?.dataset.deleteId;
      if (!id) return;
      const row = document.querySelector(`tr[data-id="${id}"]`);
      if (row) {
        row.style.transition = 'opacity .3s, transform .3s';
        row.style.opacity    = '0';
        row.style.transform  = 'translateX(20px)';
        setTimeout(() => row.remove(), 300);
      }
      closeModal('confirmDeleteModal');
      showToast('Objednávka bola vymazaná.', 'success');
    });
  }

  // Edit order
  document.querySelectorAll('.btn-edit-order').forEach(btn => {
    btn.addEventListener('click', () => {
      const { id, customer, restaurant, total, status, address, note } = btn.dataset;
      const modal = document.getElementById('orderModal');
      if (!modal) return;
      if (!modal.querySelector('#modalTitle')) return;
      modal.querySelector('#modalTitle').textContent    = 'Upraviť objednávku #' + id;
      modal.querySelector('#orderId').value             = id       || '';
      modal.querySelector('#orderCustomer').value       = customer || '';
      modal.querySelector('#orderRestaurant').value     = restaurant || '';
      modal.querySelector('#orderTotal').value          = total    || '';
      modal.querySelector('#orderStatus').value         = status   || 'nova';
      modal.querySelector('#orderAddress').value        = address  || '';
      modal.querySelector('#orderNote').value           = note     || '';
      openModal('orderModal');
    });
  });

  // Add new order
  const addBtn = document.getElementById('addOrderBtn');
  if (addBtn) {
    addBtn.addEventListener('click', () => {
      const modal = document.getElementById('orderModal');
      if (!modal) return;
      modal.querySelector('#modalTitle').textContent = 'Nová objednávka';
      modal.querySelector('#orderId').value = '';
      modal.querySelector('#orderForm').reset();
      openModal('orderModal');
    });
  }

  // Save order (static demo)
  const orderForm = document.querySelector('#orderForm');
  if (orderForm) {
    orderForm.addEventListener('submit', (e) => {
      if (orderForm.dataset.demoSubmit !== 'true') return;
      e.preventDefault();
      closeModal('orderModal');
      showToast('Objednávka bola uložená.', 'success');
    });
  }

  // View order detail
  document.querySelectorAll('.btn-view-order').forEach(btn => {
    btn.addEventListener('click', () => openModal('orderDetailModal'));
  });
});

// ── Cookie consent ───────────────────────────────────────────
function setCookieConsent(type) {
  const exp = new Date();
  exp.setFullYear(exp.getFullYear() + 1);
  document.cookie = 'qb_cookie_consent=' + type + ';expires=' + exp.toUTCString() + ';path=/;SameSite=Lax';
  const banner = document.getElementById('cookieBanner');
  if (banner) {
    banner.style.transition = 'opacity .3s ease, transform .3s ease';
    banner.style.opacity    = '0';
    banner.style.transform  = 'translateY(20px)';
    setTimeout(() => banner.remove(), 320);
  }
}

// ── Search & status filter ────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  const search = document.getElementById('searchOrders');
  const filter = document.getElementById('statusFilter');

  function filterRows() {
    const q   = search?.value.toLowerCase().trim() || '';
    const val = filter?.value || '';
    document.querySelectorAll('tbody tr[data-id]').forEach(row => {
      const matchText   = !q   || row.textContent.toLowerCase().includes(q);
      const matchStatus = !val || (row.dataset.status || '') === val;
      row.style.display = (matchText && matchStatus) ? '' : 'none';
    });
  }

  search?.addEventListener('input', filterRows);
  filter?.addEventListener('change', filterRows);
});
