/* SST Manager v2.0 — app.js */
document.addEventListener('DOMContentLoaded', function() {

  // ── TEMA ──────────────────────────────────────────────────────
  const html = document.getElementById('html-root');
  const saved = getCookie('theme') || 'dark';
  if (html) html.setAttribute('data-theme', saved);

  document.getElementById('themeToggle')?.addEventListener('click', () => {
    const cur = html.getAttribute('data-theme');
    const next = cur === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-theme', next);
    setCookie('theme', next, 365);
  });

  // ── SIDEBAR ────────────────────────────────────────────────────
  const layout = document.getElementById('layout');
  const togBtn = document.getElementById('sidebarToggle');
  const mobBtn = document.getElementById('mobileToggle');
  const sidebar = document.querySelector('.sidebar');

  const isCollapsed = getCookie('sidebar_collapsed') === '1';
  if (isCollapsed && layout) {
    layout.classList.add('sidebar-collapsed');
    if (togBtn) togBtn.querySelector('i').className = 'fas fa-chevron-right';
  }

  togBtn?.addEventListener('click', () => {
    layout.classList.toggle('sidebar-collapsed');
    const collapsed = layout.classList.contains('sidebar-collapsed');
    setCookie('sidebar_collapsed', collapsed ? '1' : '0', 365);
    togBtn.querySelector('i').className = collapsed ? 'fas fa-chevron-right' : 'fas fa-chevron-left';
  });

  mobBtn?.addEventListener('click', () => sidebar?.classList.toggle('mobile-open'));

  // Close sidebar on outside click (mobile)
  document.addEventListener('click', (e) => {
    if (sidebar?.classList.contains('mobile-open') && !sidebar.contains(e.target) && e.target !== mobBtn) {
      sidebar.classList.remove('mobile-open');
    }
  });

  // ── NAV MENUS ──────────────────────────────────────────────────
  window.toggleNav = function(id) {
    const group = document.querySelector(`[data-id="${id}"]`);
    if (!group) return;
    const isOpen = group.classList.contains('open');
    // Close others
    document.querySelectorAll('.nav-group.open').forEach(g => g !== group && g.classList.remove('open'));
    group.classList.toggle('open', !isOpen);
  };

  // ── NOTIFICAÇÕES ───────────────────────────────────────────────
  const notifBtn = document.getElementById('notifBtn');
  const notifPanel = document.getElementById('notifPanel');
  const notifBadge = document.getElementById('notifBadge');

  notifBtn?.addEventListener('click', (e) => {
    e.stopPropagation();
    notifPanel?.classList.toggle('show');
    if (notifPanel?.classList.contains('show')) loadNotifs();
  });

  async function loadNotifs() {
    try {
      const r = await apiFetch('/api/notificacoes');
      if (r.total > 0 && notifBadge) {
        notifBadge.style.display = 'flex';
        notifBadge.textContent = r.total > 9 ? '9+' : r.total;
      }
      const list = document.getElementById('notifList');
      if (!list) return;
      list.innerHTML = r.items?.length
        ? r.items.map(n => `<div class="notif-item notif-${n.nivel}" style="display:flex;align-items:flex-start;gap:12px;padding:12px 16px;border-bottom:1px solid var(--border);cursor:pointer">
            <div style="width:36px;height:36px;border-radius:var(--r-sm);background:rgba(220,38,38,.12);color:var(--danger);display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0"><i class="${n.icon}"></i></div>
            <div><div style="font-size:13px;font-weight:600">${n.titulo}</div><div style="font-size:11px;color:var(--text-3);margin-top:2px">${n.descricao}</div></div>
          </div>`).join('')
        : '<div style="padding:20px;text-align:center;color:var(--text-3);font-size:13px">✅ Nenhum alerta</div>';
    } catch {}
  }
  // Load badge on page load (skip on login page)
  if (!document.querySelector('.login-page')) loadNotifs();

  // ── USER MENU ──────────────────────────────────────────────────
  const userTrigger = document.getElementById('userTrigger');
  const userDropdown = document.getElementById('userDropdown');
  userTrigger?.addEventListener('click', (e) => {
    e.stopPropagation();
    userDropdown?.classList.toggle('show');
  });

  // Close all popups on outside click
  document.addEventListener('click', () => {
    notifPanel?.classList.remove('show');
    userDropdown?.classList.remove('show');
    document.getElementById('searchDropdown')?.classList.remove('show');
  });

  // ── GLOBAL SEARCH ──────────────────────────────────────────────
  const searchInput = document.getElementById('globalSearch');
  const searchDrop = document.getElementById('searchDropdown');
  let searchTimer;

  searchInput?.addEventListener('input', () => {
    clearTimeout(searchTimer);
    const q = searchInput.value.trim();
    if (q.length < 2) { searchDrop?.classList.remove('show'); return; }
    searchTimer = setTimeout(async () => {
      try {
        const data = await apiFetch(`/api/search?q=${encodeURIComponent(q)}`);
        if (!searchDrop) return;
        searchDrop.innerHTML = data.results?.length
          ? data.results.map(r => `<a href="${r.url}" class="search-item">
              <div class="search-item-avatar">${r.initials}</div>
              <div class="search-item-info">
                <div style="font-size:13px;font-weight:600">${r.nome}</div>
                <div style="font-size:11px;color:var(--text-3)">${r.meta}</div>
              </div></a>`).join('')
          : '<div style="padding:14px;font-size:13px;color:var(--text-3)">Nenhum resultado</div>';
        searchDrop.classList.add('show');
      } catch {}
    }, 280);
  });
  searchInput?.addEventListener('click', e => e.stopPropagation());

  // ── FLASH AUTO-DISMISS ─────────────────────────────────────────
  const flash = document.getElementById('flash');
  if (flash) {
    setTimeout(() => { flash.style.opacity = '0'; setTimeout(() => flash.remove(), 300); }, 4500);
  }

  // ── MODALS ─────────────────────────────────────────────────────
  window.openModal  = id => document.getElementById(id)?.classList.add('show');
  window.closeModal = id => document.getElementById(id)?.classList.remove('show');

  document.querySelectorAll('.modal-overlay').forEach(ov => {
    ov.addEventListener('click', e => { if (e.target === ov) ov.classList.remove('show'); });
  });
  document.querySelectorAll('[data-modal]').forEach(btn => btn.addEventListener('click', () => openModal(btn.dataset.modal)));
  document.querySelectorAll('[data-close]').forEach(btn => btn.addEventListener('click', () => closeModal(btn.dataset.close)));

  // ── TABS ───────────────────────────────────────────────────────
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.tc').forEach(c => c.classList.remove('active'));
      btn.classList.add('active');
      document.getElementById('tab-' + btn.dataset.tab)?.classList.add('active');
    });
  });

  // ── CHECKBOXES + BULK ──────────────────────────────────────────
  const selectAll = document.getElementById('selectAll');
  const bulkBar   = document.getElementById('bulkBar');
  const bulkCount = document.getElementById('bulkCount');

  function updateBulk() {
    const checked = document.querySelectorAll('.row-check:checked');
    bulkBar?.classList.toggle('show', checked.length > 0);
    if (bulkCount) bulkCount.textContent = checked.length;
    document.querySelectorAll('.row-check').forEach(cb => {
      cb.closest('tr')?.classList.toggle('tr-selected', cb.checked);
    });
  }

  selectAll?.addEventListener('change', () => {
    document.querySelectorAll('.row-check').forEach(cb => cb.checked = selectAll.checked);
    updateBulk();
  });
  document.querySelectorAll('.row-check').forEach(cb => {
    cb.addEventListener('change', () => {
      updateBulk();
      if (selectAll) {
        const all = [...document.querySelectorAll('.row-check')];
        selectAll.checked = all.every(c => c.checked);
        selectAll.indeterminate = all.some(c => c.checked) && !all.every(c => c.checked);
      }
    });
  });

  window.getSelectedIds = () => [...document.querySelectorAll('.row-check:checked')].map(c => c.value);

  // ── SORTABLE TH ────────────────────────────────────────────────
  document.querySelectorAll('th[data-sort]').forEach(th => {
    th.addEventListener('click', () => {
      const url = new URL(window.location.href);
      const cur = url.searchParams.get('sort');
      const dir = url.searchParams.get('dir');
      url.searchParams.set('sort', th.dataset.sort);
      url.searchParams.set('dir', cur === th.dataset.sort && dir === 'asc' ? 'desc' : 'asc');
      window.location.href = url.toString();
    });
  });

  // ── DYNAMIC SELECTS (empresa → setor → funcao) ─────────────────
  const empSel   = document.getElementById('empresa_id');
  const setorSel = document.getElementById('setor_id');
  const funcSel  = document.getElementById('funcao_id');

  async function populateSel(sel, url, placeholder) {
    if (!sel) return;
    sel.innerHTML = `<option value="">${placeholder}</option>`;
    try {
      const data = await apiFetch(url);
      data.forEach(item => {
        const o = document.createElement('option');
        o.value = item.id; o.textContent = item.nome || item.label;
        sel.appendChild(o);
      });
    } catch {}
  }

  empSel?.addEventListener('change', async () => {
    if (empSel.value) {
      await populateSel(setorSel, `/api/setores?empresa_id=${empSel.value}`, 'Selecione o setor');
      if (funcSel) funcSel.innerHTML = '<option value="">Selecione a função</option>';
    }
  });

  setorSel?.addEventListener('change', async () => {
    if (setorSel.value) await populateSel(funcSel, `/api/funcoes?setor_id=${setorSel.value}`, 'Selecione a função');
  });

  // ── CONFIRM DELETE ─────────────────────────────────────────────
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', e => {
      if (!confirm(el.dataset.confirm || 'Confirmar esta ação?')) e.preventDefault();
    });
  });

  // ── FORM LOADING ───────────────────────────────────────────────
  document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function() {
      const sb = this.querySelector('[type=submit]');
      if (sb && !sb.dataset.noLoading) {
        const orig = sb.innerHTML;
        sb.disabled = true;
        sb.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Aguarde...';
        setTimeout(() => { sb.disabled = false; sb.innerHTML = orig; }, 10000);
      }
    });
  });

  // ── DROPZONE ───────────────────────────────────────────────────
  document.querySelectorAll('.dropzone').forEach(zone => {
    const input = zone.querySelector('input[type=file]');
    zone.addEventListener('click', () => input?.click());
    zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag-over'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
    zone.addEventListener('drop', e => {
      e.preventDefault(); zone.classList.remove('drag-over');
      if (input && e.dataTransfer.files.length) {
        input.files = e.dataTransfer.files;
        const fn = zone.querySelector('#dropFilename');
        if (fn) fn.textContent = e.dataTransfer.files[0].name;
        input.dispatchEvent(new Event('change'));
      }
    });
    input?.addEventListener('change', () => {
      const fn = zone.querySelector('#dropFilename');
      if (fn && input.files[0]) fn.textContent = input.files[0].name;
    });
  });

});

// ── UTILS ──────────────────────────────────────────────────────
function getCookie(name) {
  return document.cookie.split(';').find(c => c.trim().startsWith(name + '='))?.split('=')[1] || '';
}
function setCookie(name, value, days) {
  const d = new Date(); d.setTime(d.getTime() + days * 86400000);
  document.cookie = `${name}=${value};expires=${d.toUTCString()};path=/;SameSite=Lax`;
}
async function apiFetch(url, opts = {}) {
  const token = document.querySelector('meta[name=csrf-token]')?.content || '';
  const res = await fetch(url, {
    headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': token, ...opts.headers },
    ...opts
  });
  if (!res.ok) throw new Error(`HTTP ${res.status}`);
  return res.json();
}
function showAlert(msg, type = 'success') {
  const el = document.createElement('div');
  el.className = `flash flash-${type}`;
  el.innerHTML = `<i class="fas fa-${type==='success'?'check-circle':'exclamation-circle'}"></i> ${msg}<button onclick="this.parentElement.remove()" class="flash-close">×</button>`;
  document.querySelector('.page-content')?.prepend(el);
  setTimeout(() => { el.style.opacity='0'; setTimeout(()=>el.remove(),300); }, 5000);
}
