/**
 * MH-CORE: Dashboard - Logica cliente (Fase 3).
 * Consume los endpoints de api/ via fetch y renderiza los modulos del
 * panel: perfil/obligaciones, agenda, checklist operativo, inventario/flota
 * y administracion de staff. Vanilla JS, sin dependencias externas.
 */
(function () {
  'use strict';

  const CSRF       = window.MH_CSRF;
  const USER_ID    = window.MH_USER_ID;
  const IS_MANAGER = window.MH_IS_MANAGER;
  const IS_ADMIN   = window.MH_IS_ADMIN;

  const ITEM_ICONS = {
    checkout_hardware: '\u{1F4E6}',
    calibracion_led: '\u{1F4A1}',
    encuadres: '\u{1F3A5}',
    aislamiento_acustico: '\u{1F507}',
    bitrate_simulcast: '\u{1F4E1}',
    monitoreo_gain: '\u{1F39B}\u{FE0F}',
    clima_set: '\u{1F321}\u{FE0F}',
    modo_avion: '\u{2708}\u{FE0F}',
    prohibicion_liquidos: '\u{1F6B1}',
    respaldo_raw: '\u{1F4BE}',
    limpieza_set: '\u{1F9F9}',
    apagado_master: '\u{23FB}',
    checkin_hardware: '\u{1F4E5}',
  };

  /* ------------------------------------------------------------------ */
  /* Helpers                                                              */
  /* ------------------------------------------------------------------ */
  const $  = (sel, ctx) => (ctx || document).querySelector(sel);
  const $$ = (sel, ctx) => Array.from((ctx || document).querySelectorAll(sel));

  async function api(path, opts = {}) {
    const init = { method: opts.method || 'GET', headers: {} };
    if (opts.body) {
      init.headers['Content-Type'] = 'application/json';
      init.body = JSON.stringify(Object.assign({}, opts.body, { csrf_token: CSRF }));
    }
    try {
      const res  = await fetch(path, init);
      const json = await res.json();
      return json;
    } catch (err) {
      return { status: 'error', message: 'No se pudo conectar con el servidor.', data: {} };
    }
  }

  function esc(str) {
    const div = document.createElement('div');
    div.textContent = str === null || str === undefined ? '' : String(str);
    return div.innerHTML;
  }

  function fmtDate(d) {
    if (!d) return '';
    const date = new Date(d + 'T00:00:00');
    return date.toLocaleDateString('es-MX', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
  }

  function fmtTime(t) {
    return t ? t.slice(0, 5) : '';
  }

  function money(v) {
    if (v === null || v === undefined || v === '') return null;
    return '$' + Number(v).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function showFeedback(el, message, ok) {
    el.textContent = message;
    el.className = 'mh-feedback text-sm ' + (ok ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500');
  }

  /* Bloquea controles de un usuario sobre su propia fila administrativa. */
  function lockIfSelf(id) {
    return id === USER_ID ? 'disabled title="No puedes modificar tu propia cuenta desde aqui"' : '';
  }

  /* ------------------------------------------------------------------ */
  /* Tema claro / oscuro (persistido en localStorage)                     */
  /* ------------------------------------------------------------------ */
  function initTheme() {
    const saved = localStorage.getItem('mh-theme');
    const root  = document.documentElement;
    if (saved === 'light') root.classList.remove('dark');
    else root.classList.add('dark');
    syncThemeIcon();
  }

  function toggleTheme() {
    const root = document.documentElement;
    root.classList.toggle('dark');
    localStorage.setItem('mh-theme', root.classList.contains('dark') ? 'dark' : 'light');
    syncThemeIcon();
  }

  function syncThemeIcon() {
    const isDark = document.documentElement.classList.contains('dark');
    $('#iconSun').classList.toggle('hidden', !isDark);
    $('#iconMoon').classList.toggle('hidden', isDark);
  }

  /* ------------------------------------------------------------------ */
  /* Menu movil (hamburguesa)                                             */
  /* ------------------------------------------------------------------ */
  function openSidebar() {
    $('#sidebar').classList.remove('-translate-x-full');
    $('#sidebarOverlay').classList.remove('hidden');
  }

  function closeSidebar() {
    $('#sidebar').classList.add('-translate-x-full');
    $('#sidebarOverlay').classList.add('hidden');
  }

  function bindNavHighlight() {
    const links = $$('#sidebar .nav-link');
    links.forEach((link) => {
      link.addEventListener('click', () => {
        links.forEach((l) => l.classList.remove('active'));
        link.classList.add('active');
        closeSidebar();
      });
    });
  }

  /* ------------------------------------------------------------------ */
  /* Boton flotante "Volver arriba"                                       */
  /* ------------------------------------------------------------------ */
  function bindBackToTop() {
    const btn = $('#backToTop');
    if (!btn) return;

    window.addEventListener('scroll', () => {
      const show = window.scrollY > 300;
      btn.classList.toggle('opacity-0', !show);
      btn.classList.toggle('translate-y-3', !show);
      btn.classList.toggle('pointer-events-none', !show);
    });

    btn.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  /* ==================================================================== */
  /* A) PERFIL + MIS OBLIGACIONES (api/users.php?action=me)                */
  /* ==================================================================== */
  async function loadProfile() {
    const res = await api('../api/users.php?action=me');
    if (res.status !== 'success') {
      $('#obligationsList').innerHTML = `<p class="text-sm text-red-500">${esc(res.message)}</p>`;
      return;
    }
    renderObligations(res.data.checklist || []);
  }

  function badgeClassForAssignment(status) {
    switch (status) {
      case 'Completado':  return 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400';
      case 'En Progreso': return 'bg-turquoise/15 text-turquoise';
      default:            return 'bg-amber-500/15 text-amber-600 dark:text-amber-400';
    }
  }

  function renderObligations(tasks) {
    const wrap = $('#obligationsList');
    if (!tasks.length) {
      wrap.innerHTML = '<p class="text-sm text-slate-500 dark:text-digital-white/60">No tienes tareas pendientes asignadas. &#9989;</p>';
      return;
    }
    wrap.innerHTML = tasks.map((t) => `
      <div class="flex items-start gap-3 rounded-2xl border border-slate-200 dark:border-turquoise/10 bg-white dark:bg-[#01243f] p-4">
        <div class="shrink-0 w-12 h-12 rounded-xl bg-turquoise/10 grid place-items-center text-turquoise font-display font-bold text-[11px] text-center leading-tight">
          ${esc(fmtTime(t.start_time))}<br>${esc(fmtTime(t.end_time))}
        </div>
        <div class="flex-1 min-w-0">
          <p class="font-display font-semibold text-sm truncate">${esc(t.title)}</p>
          <p class="text-xs text-slate-500 dark:text-digital-white/60 mt-0.5">${esc(fmtDate(t.call_date))} &middot; ${esc(t.location)}</p>
          ${t.task_description ? `<p class="text-sm mt-1.5">${esc(t.task_description)}</p>` : ''}
        </div>
        <span class="shrink-0 badge ${badgeClassForAssignment(t.assignment_status)}">${esc(t.assignment_status)}</span>
      </div>
    `).join('');
  }

  /* ==================================================================== */
  /* CENTRO DE COMANDO EJECUTIVO (api/finance.php?action=kpis)             */
  /* ==================================================================== */
  async function loadKpis() {
    const grid = $('#kpiGrid');
    if (!grid) return;

    const res = await api('../api/finance.php?action=kpis');
    if (res.status !== 'success') {
      grid.innerHTML = `<p class="text-sm text-red-500 col-span-full">${esc(res.message)}</p>`;
      return;
    }
    renderKpiCards(res.data);
  }

  function renderKpiCards(data) {
    const grid = $('#kpiGrid');
    const fleetAlerts = data.fleet_maintenance || [];

    const cards = [
      { label: 'Ingresos Mensuales', icon: '\u{1F4B0}', value: money(data.monthly_revenue) || '$0.00', accent: 'text-emerald-500' },
      { label: 'Utilidad Neta Proyectada (40%)', icon: '\u{1F4C8}', value: money(data.projected_profit) || '$0.00', accent: 'text-turquoise' },
      { label: 'IVA Acumulado de Ley (16%)', icon: '\u{1F9FE}', value: money(data.iva_accrued) || '$0.00', accent: 'text-amber-500' },
      { label: 'Anticipos Pendientes por Cobrar', icon: '\u{23F3}', value: money(data.pending_advances) || '$0.00', accent: 'text-red-500' },
      { label: 'Horas de Estudio Consumidas', icon: '\u{1F3AC}', value: `${data.studio_hours} h`, accent: 'text-blue-500' },
      {
        label: 'Alertas de Flota en Mantenimiento',
        icon: '\u{1F69A}',
        value: String(fleetAlerts.length),
        accent: fleetAlerts.length ? 'text-red-500' : 'text-emerald-500',
        detail: fleetAlerts.length
          ? fleetAlerts.map((f) => `${esc(f.name)} (${esc(f.type)})`).join(', ')
          : 'Van Terrestre y Embarcacion Maritima operativas.',
      },
    ];

    grid.innerHTML = cards.map((c) => `
      <div class="rounded-2xl border border-slate-200 dark:border-turquoise/10 bg-white dark:bg-[#01243f] p-4 sm:p-5">
        <div class="flex items-center justify-between mb-2">
          <p class="text-xs uppercase tracking-wide font-display font-bold text-slate-400 dark:text-digital-white/50">${esc(c.label)}</p>
          <span class="text-xl">${c.icon}</span>
        </div>
        <p class="font-display font-extrabold text-2xl ${c.accent}">${c.value}</p>
        ${c.detail ? `<p class="text-xs text-slate-500 dark:text-digital-white/50 mt-1.5">${c.detail}</p>` : ''}
      </div>
    `).join('');
  }

  /* ==================================================================== */
  /* B) AGENDA Y PREVENCION DE COLISIONES (api/agenda.php?action=list)     */
  /* ==================================================================== */
  let agendaCalls = [];

  async function loadAgenda() {
    const res = await api('../api/agenda.php?action=list');
    if (res.status !== 'success') {
      $('#agendaList').innerHTML = `<p class="text-sm text-red-500">${esc(res.message)}</p>`;
      return;
    }
    agendaCalls = res.data.calls || [];
    renderAgenda();
    populateChecklistCallSelect();
  }

  function renderAgenda() {
    const wrap = $('#agendaList');
    if (!agendaCalls.length) {
      wrap.innerHTML = '<p class="text-sm text-slate-500 dark:text-digital-white/60">No hay llamados agendados todavia.</p>';
      return;
    }
    wrap.innerHTML = agendaCalls.map(renderAgendaCard).join('');

    $$('.js-verify-advance').forEach((btn) => btn.addEventListener('click', onVerifyAdvance));
    $$('.js-status-select').forEach((sel) => sel.addEventListener('change', onStatusChange));
  }

  function renderAgendaCard(call) {
    const advancePaid = Number(call.advance_paid) === 1;
    let statusBadge;

    if (call.status === 'Cancelado') {
      statusBadge = '<span class="badge bg-slate-400/15 text-slate-500 dark:text-digital-white/50">Cancelado</span>';
    } else if (call.status === 'Completado') {
      statusBadge = '<span class="badge bg-turquoise/15 text-turquoise">Completado</span>';
    } else if (call.status === 'Confirmado') {
      statusBadge = '<span class="badge bg-emerald-500/15 text-emerald-600 dark:text-emerald-400">&#10003; Confirmado</span>';
    } else if (!advancePaid) {
      statusBadge = '<span class="badge bg-amber-500/15 text-amber-600 dark:text-amber-400">&#9888; Bloqueo por falta de anticipo</span>';
    } else {
      statusBadge = '<span class="badge bg-blue-500/15 text-blue-600 dark:text-blue-400">Pendiente de confirmacion</span>';
    }

    const advanceBadge = advancePaid
      ? '<span class="badge bg-emerald-500/15 text-emerald-600 dark:text-emerald-400">Anticipo 50% verificado</span>'
      : '<span class="badge bg-red-500/15 text-red-600 dark:text-red-400">Anticipo 50% pendiente</span>';

    const staff = (call.assignments || [])
      .map((a) => `<span class="badge bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-digital-white/70">${esc(a.full_name)}</span>`)
      .join('') || '<span class="text-xs text-slate-400 dark:text-digital-white/40">Sin staff asignado</span>';

    const advancePct    = Number(call.advance_required_pct) || 50;
    const advanceAmount = call.total_amount ? money((Number(call.total_amount) * advancePct) / 100) : null;

    let actions = '';
    if (IS_ADMIN && call.status !== 'Cancelado') {
      const targetAdvance = advancePaid ? 0 : 1;
      const label = advancePaid ? 'Revertir anticipo' : 'Verificar anticipo 50%';
      actions += `<button type="button" data-call="${call.id}" data-advance="${targetAdvance}" class="js-verify-advance btn-ghost">${label}</button>`;
    }
    if (IS_MANAGER) {
      const statuses = ['Pendiente', 'Confirmado', 'Cancelado', 'Completado'];
      actions += `<select data-call="${call.id}" class="js-status-select select-sm">
        ${statuses.map((s) => `<option value="${s}" ${s === call.status ? 'selected' : ''}>${s}</option>`).join('')}
      </select>`;
    }

    const amount = money(call.total_amount);

    return `
    <article class="rounded-2xl border border-slate-200 dark:border-turquoise/10 bg-white dark:bg-[#01243f] p-4 sm:p-5">
      <div class="flex flex-wrap items-start justify-between gap-2 mb-2">
        <div class="min-w-0">
          <p class="font-display font-bold text-base truncate">${esc(call.title)}</p>
          <p class="text-xs text-slate-500 dark:text-digital-white/60 truncate">${esc(call.client_name)}${call.client_company ? ' &mdash; ' + esc(call.client_company) : ''} &middot; ${esc(call.program_name)}</p>
        </div>
        <div class="flex flex-wrap gap-1.5 justify-end">${statusBadge}${advanceBadge}</div>
      </div>
      <div class="flex flex-wrap gap-x-4 gap-y-1 text-sm text-slate-600 dark:text-digital-white/70 mb-3">
        <span>&#128197; ${esc(fmtDate(call.call_date))}</span>
        <span>&#128337; ${esc(fmtTime(call.start_time))} - ${esc(fmtTime(call.end_time))}</span>
        <span>&#128205; ${esc(call.location)}</span>
        ${amount ? `<span>&#128181; ${amount}</span>` : ''}
      </div>
      <div class="flex flex-wrap gap-1.5 mb-3">${staff}</div>
      ${actions ? `<div class="flex flex-wrap items-center gap-2 pt-3 border-t border-slate-100 dark:border-white/5">${actions}</div>` : ''}
      ${!advancePaid ? `
      <div class="mt-3 rounded-xl border border-amber-400/40 bg-amber-400/10 p-3 flex items-start gap-2">
        <span class="text-base shrink-0">&#9888;&#65039;</span>
        <p class="text-xs text-amber-700 dark:text-amber-300">
          <strong>Bloqueo de asignacion de staff:</strong> el Anticipo Obligatorio del ${advancePct}%
          ${advanceAmount ? `(${advanceAmount} MXN)` : ''} no ha sido verificado para este llamado.
          No es posible asignar personal ni confirmar la fecha hasta que el Administrador verifique el anticipo.
        </p>
      </div>` : ''}
    </article>`;
  }

  async function onVerifyAdvance(e) {
    const btn         = e.currentTarget;
    const callId      = Number(btn.dataset.call);
    const advancePaid = btn.dataset.advance === '1';
    btn.disabled = true;
    const res = await api('../api/agenda.php?action=verify_advance', {
      method: 'PUT',
      body: { call_id: callId, advance_paid: advancePaid },
    });
    if (res.status !== 'success') alert(res.message);
    loadAgenda();
  }

  async function onStatusChange(e) {
    const sel    = e.currentTarget;
    const callId = Number(sel.dataset.call);
    const status = sel.value;
    const res = await api('../api/agenda.php?action=update_status', {
      method: 'PUT',
      body: { call_id: callId, status },
    });
    if (res.status !== 'success') alert(res.message);
    loadAgenda();
  }

  async function populateCallProgramSelect() {
    const sel = $('#callProgramId');
    if (!sel) return;
    const res = await api('../api/programs.php?action=list');
    if (res.status !== 'success') {
      sel.innerHTML = '<option value="">No se pudieron cargar los programas</option>';
      return;
    }
    const programs = (res.data.programs || []).filter((p) => Number(p.is_active) === 1);
    sel.innerHTML = programs.map((p) => `<option value="${p.id}">${esc(p.name)} &mdash; ${esc(p.client_name || '')}</option>`).join('')
      || '<option value="">Sin programas activos</option>';
  }

  function bindCallForm() {
    const form = $('#callForm');
    if (!form) return;
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const body     = Object.fromEntries(new FormData(form).entries());
      const feedback = $('#callFormFeedback');
      feedback.textContent = 'Guardando...';
      feedback.className = 'mh-feedback text-sm text-slate-500 dark:text-digital-white/60';

      const res = await api('../api/agenda.php?action=create_call', { method: 'POST', body });
      if (res.status === 'success') {
        showFeedback(feedback, 'Llamado creado correctamente.', true);
        form.reset();
        loadAgenda();
      } else {
        showFeedback(feedback, res.message, false);
      }
    });
  }

  /* ==================================================================== */
  /* C) CONSOLA DE CHECKLISTS DE SET (api/checklist.php)                   */
  /* ==================================================================== */
  let currentCallId = null;

  function populateChecklistCallSelect() {
    const sel = $('#checklistCallSelect');
    if (!sel) return;

    const eligible = IS_MANAGER
      ? agendaCalls
      : agendaCalls.filter((c) => (c.assignments || []).some((a) => a.user_id === USER_ID));

    if (!eligible.length) {
      sel.innerHTML = '<option value="">No tienes llamados asignados</option>';
      return;
    }

    const previous = sel.value;
    sel.innerHTML = '<option value="">Selecciona un llamado...</option>' + eligible.map((c) =>
      `<option value="${c.id}">${esc(fmtDate(c.call_date))} &middot; ${esc(c.title)} (${esc(c.location)})</option>`
    ).join('');

    if (previous && eligible.some((c) => String(c.id) === previous)) {
      sel.value = previous;
    }
  }

  function bindChecklistSelect() {
    const sel = $('#checklistCallSelect');
    sel.addEventListener('change', () => {
      const callId = Number(sel.value);
      if (!callId) {
        currentCallId = null;
        $('#checklistTabs').classList.add('hidden');
        $('#checklistEmpty').classList.remove('hidden');
        return;
      }
      currentCallId = callId;
      loadChecklist(callId);
    });
  }

  function bindChecklistTabs() {
    $$('#checklistTabs .tab-btn').forEach((btn) => {
      btn.addEventListener('click', () => {
        $$('#checklistTabs .tab-btn').forEach((b) => b.classList.remove('active'));
        btn.classList.add('active');
        const tab = btn.dataset.tab;
        $$('#checklistTabs [data-tab-panel]').forEach((panel) => {
          panel.classList.toggle('hidden', panel.dataset.tabPanel !== tab);
        });
      });
    });
  }

  async function loadChecklist(callId) {
    const res = await api(`../api/checklist.php?action=get&call_id=${callId}`);
    if (res.status !== 'success') {
      $('#checklistTabs').classList.add('hidden');
      $('#checklistEmpty').classList.remove('hidden');
      $('#checklistEmpty').textContent = res.message;
      return;
    }
    $('#checklistEmpty').classList.add('hidden');
    $('#checklistTabs').classList.remove('hidden');

    renderChecklistPhase('checklistAntes', res.data.checklist.Antes || []);
    renderChecklistPhase('checklistDurante', res.data.checklist.Durante || []);
    renderChecklistPhase('checklistDespues', res.data.checklist.Despues || []);

    renderInventoryActionLists();
  }

  function renderChecklistPhase(containerId, items) {
    const wrap = $('#' + containerId);
    wrap.innerHTML = items.map((item) => {
      const icon = ITEM_ICONS[item.item_key] || '☑️';
      const meta = item.is_checked && item.checked_by_name
        ? `<span class="block text-xs text-slate-400 dark:text-digital-white/40 mt-0.5">Confirmado por ${esc(item.checked_by_name)} &middot; ${esc(item.checked_at || '')}</span>`
        : '';
      return `
      <label class="checklist-row cursor-pointer">
        <input type="checkbox" data-template="${item.template_id}" ${item.is_checked ? 'checked' : ''}>
        <span class="text-sm flex-1">
          <span class="mr-1">${icon}</span>${esc(item.item_label)}
          ${meta}
        </span>
      </label>`;
    }).join('');

    $$('input[type="checkbox"]', wrap).forEach((cb) => cb.addEventListener('change', onChecklistToggle));
  }

  async function onChecklistToggle(e) {
    const cb         = e.currentTarget;
    const templateId = Number(cb.dataset.template);
    const isChecked  = cb.checked;
    cb.disabled = true;

    const res = await api('../api/checklist.php?action=toggle', {
      method: 'POST',
      body: { call_id: currentCallId, template_id: templateId, is_checked: isChecked },
    });

    cb.disabled = false;
    if (res.status !== 'success') {
      alert(res.message);
      cb.checked = !isChecked;
      return;
    }
    loadChecklist(currentCallId);
  }

  /* ==================================================================== */
  /* INVENTARIO Y FLOTA (api/inventory.php)                                */
  /* ==================================================================== */
  let inventoryData = { inventory_items: [], fleet_vehicles: [] };

  async function loadInventory() {
    const res = await api('../api/inventory.php?action=list');
    if (res.status !== 'success') {
      $('#inventoryItemsBody').innerHTML = `<tr><td colspan="3" class="py-3 text-red-500 text-sm">${esc(res.message)}</td></tr>`;
      return;
    }
    inventoryData = res.data;
    renderInventoryTable();
    renderFleetTable();
    if (currentCallId) renderInventoryActionLists();
  }

  function statusBadgeClass(status) {
    switch (status) {
      case 'Disponible':    return 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400';
      case 'En Uso':        return 'bg-blue-500/15 text-blue-600 dark:text-blue-400';
      case 'Mantenimiento': return 'bg-amber-500/15 text-amber-600 dark:text-amber-400';
      default:              return 'bg-slate-400/15 text-slate-500';
    }
  }

  function renderInventoryTable() {
    const body = $('#inventoryItemsBody');
    const items = inventoryData.inventory_items || [];
    if (!items.length) {
      body.innerHTML = '<tr><td colspan="3" class="py-3 text-sm text-slate-500 dark:text-digital-white/60">Sin equipo registrado.</td></tr>';
      return;
    }
    body.innerHTML = items.map((item) => `
      <tr class="border-t border-slate-100 dark:border-white/5">
        <td class="py-2 pr-2 font-medium">${esc(item.name)}</td>
        <td class="py-2 pr-2 text-slate-500 dark:text-digital-white/60">${esc(item.category || '—')}</td>
        <td class="py-2"><span class="badge ${statusBadgeClass(item.status)}">${esc(item.status)}</span></td>
      </tr>
    `).join('');
  }

  function renderFleetTable() {
    const body = $('#fleetBody');
    const vehicles = inventoryData.fleet_vehicles || [];
    if (!vehicles.length) {
      body.innerHTML = `<tr><td colspan="${IS_MANAGER ? 4 : 3}" class="py-3 text-sm text-slate-500 dark:text-digital-white/60">Sin unidades registradas.</td></tr>`;
      return;
    }
    body.innerHTML = vehicles.map((v) => {
      const action = IS_MANAGER
        ? (v.status !== 'Mantenimiento'
          ? `<button type="button" class="btn-ghost btn-danger js-set-maintenance" data-asset-id="${v.id}">Marcar mantenimiento</button>`
          : '<span class="text-xs text-slate-400 dark:text-digital-white/40">&mdash;</span>')
        : '';
      return `
      <tr class="border-t border-slate-100 dark:border-white/5">
        <td class="py-2 pr-2 font-medium">${esc(v.name)}</td>
        <td class="py-2 pr-2 text-slate-500 dark:text-digital-white/60">${esc(v.type)}</td>
        <td class="py-2 pr-2"><span class="badge ${statusBadgeClass(v.status)}">${esc(v.status)}</span></td>
        ${IS_MANAGER ? `<td class="py-2">${action}</td>` : ''}
      </tr>`;
    }).join('');

    if (IS_MANAGER) {
      $$('.js-set-maintenance', body).forEach((btn) => btn.addEventListener('click', onSetMaintenance));
    }
  }

  async function onSetMaintenance(e) {
    const btn     = e.currentTarget;
    const assetId = Number(btn.dataset.assetId);
    if (!confirm('Confirmas marcar esta unidad como Mantenimiento?')) return;

    btn.disabled = true;
    const res = await api('../api/inventory.php?action=set_maintenance', {
      method: 'POST',
      body: { asset_type: 'Vehiculo', asset_id: assetId },
    });
    if (res.status !== 'success') alert(res.message);
    loadInventory();
  }

  /* Check-out / Check-in contextual al llamado seleccionado en Checklist */
  function renderInventoryActionLists() {
    const checkoutWrap = $('#checkoutList');
    const checkinWrap  = $('#checkinList');
    const items = inventoryData.inventory_items || [];

    const available = items.filter((i) => i.status === 'Disponible');
    const inUse     = items.filter((i) => i.status === 'En Uso');

    checkoutWrap.innerHTML = available.length
      ? available.map((item) => `
        <div class="flex items-center justify-between gap-2 text-sm">
          <span class="truncate">${esc(item.name)} <span class="text-xs text-slate-400 dark:text-digital-white/40">(${esc(item.category || '—')})</span></span>
          <button type="button" class="btn-ghost js-checkout shrink-0" data-asset-id="${item.id}">Check-Out</button>
        </div>
      `).join('')
      : '<p class="text-xs text-slate-400 dark:text-digital-white/40">No hay equipo disponible para check-out.</p>';

    checkinWrap.innerHTML = inUse.length
      ? inUse.map((item) => `
        <div class="flex flex-wrap items-center gap-2 text-sm" data-checkin-row="${item.id}">
          <span class="truncate flex-1 min-w-[120px]">${esc(item.name)} <span class="text-xs text-slate-400 dark:text-digital-white/40">(${esc(item.category || '—')})</span></span>
          <label class="flex items-center gap-1 text-xs">
            <input type="checkbox" class="js-damaged-toggle" data-asset-id="${item.id}"> Da&ntilde;ado
          </label>
          <input type="text" class="field-input hidden flex-1 min-w-[140px] js-damage-notes" data-asset-id="${item.id}" placeholder="Detalle del dano (obligatorio)">
          <button type="button" class="btn-ghost js-checkin shrink-0" data-asset-id="${item.id}">Check-In</button>
        </div>
      `).join('')
      : '<p class="text-xs text-slate-400 dark:text-digital-white/40">No hay equipo en uso para check-in.</p>';

    $$('.js-checkout', checkoutWrap).forEach((btn) => btn.addEventListener('click', onCheckout));
    $$('.js-checkin', checkinWrap).forEach((btn) => btn.addEventListener('click', onCheckin));
    $$('.js-damaged-toggle', checkinWrap).forEach((cb) => cb.addEventListener('change', (e) => {
      const row = e.currentTarget.closest('[data-checkin-row]');
      $('.js-damage-notes', row).classList.toggle('hidden', !e.currentTarget.checked);
    }));
  }

  async function onCheckout(e) {
    const btn = e.currentTarget;
    btn.disabled = true;
    const res = await api('../api/inventory.php?action=checkout', {
      method: 'POST',
      body: { asset_type: 'Inventario', asset_id: Number(btn.dataset.assetId), call_id: currentCallId },
    });
    if (res.status !== 'success') alert(res.message);
    await loadInventory();
  }

  async function onCheckin(e) {
    const btn   = e.currentTarget;
    const row   = btn.closest('[data-checkin-row]');
    const dmg   = $('.js-damaged-toggle', row);
    const notes = $('.js-damage-notes', row);
    const damaged = dmg.checked;

    if (damaged && !notes.value.trim()) {
      alert('Si reportas dano, debes describir el detalle.');
      notes.focus();
      return;
    }

    btn.disabled = true;
    const res = await api('../api/inventory.php?action=checkin', {
      method: 'POST',
      body: {
        asset_type: 'Inventario',
        asset_id: Number(btn.dataset.assetId),
        call_id: currentCallId,
        damaged,
        condition_notes: notes.value.trim(),
      },
    });
    if (res.status !== 'success') alert(res.message);
    await loadInventory();
  }

  /* ==================================================================== */
  /* D) PANEL ADMINISTRATIVO MULTI-ROL (api/users.php)                     */
  /* ==================================================================== */
  const ROLE_OPTIONS   = ['Administrador', 'Lider_Proyecto', 'Staff_Tecnico', 'Lider_Logistica', 'Cliente'];
  const STATUS_OPTIONS = ['Activo', 'Suspendido', 'Troll_Mode'];

  async function loadUsers() {
    const body = $('#usersBody');
    if (!body) return;
    const res = await api('../api/users.php?action=list');
    if (res.status !== 'success') {
      body.innerHTML = `<tr><td colspan="6" class="py-3 text-red-500 text-sm">${esc(res.message)}</td></tr>`;
      return;
    }
    renderUsersTable(res.data.users || []);
  }

  function statusUserBadgeClass(status) {
    switch (status) {
      case 'Activo':     return 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400';
      case 'Suspendido': return 'bg-amber-500/15 text-amber-600 dark:text-amber-400';
      default:           return 'bg-red-500/15 text-red-600 dark:text-red-400';
    }
  }

  function renderUsersTable(users) {
    const body = $('#usersBody');
    if (!users.length) {
      body.innerHTML = '<tr><td colspan="6" class="py-3 text-sm text-slate-500 dark:text-digital-white/60">Sin usuarios registrados.</td></tr>';
      return;
    }

    body.innerHTML = users.map((u) => `
      <tr class="border-t border-slate-100 dark:border-white/5" data-user-row="${u.id}">
        <td class="py-2 pr-2 font-mono text-xs">${esc(u.user_id)}</td>
        <td class="py-2 pr-2 font-medium whitespace-nowrap">${esc(u.full_name)}</td>
        <td class="py-2 pr-2 text-slate-500 dark:text-digital-white/60 truncate max-w-[160px]">${esc(u.email)}</td>
        <td class="py-2 pr-2">
          <select class="select-sm js-role-select" data-id="${u.id}" ${lockIfSelf(u.id)}>
            ${ROLE_OPTIONS.map((r) => `<option value="${r}" ${r === u.role ? 'selected' : ''}>${r.replace('_', ' ')}</option>`).join('')}
          </select>
        </td>
        <td class="py-2 pr-2">
          <select class="select-sm js-status-select-admin" data-id="${u.id}" ${lockIfSelf(u.id)}>
            ${STATUS_OPTIONS.map((s) => `<option value="${s}" ${s === u.status ? 'selected' : ''}>${s.replace('_', ' ')}</option>`).join('')}
          </select>
          <span class="badge ${statusUserBadgeClass(u.status)} ml-1 align-middle">${esc(u.status.replace('_', ' '))}</span>
        </td>
        <td class="py-2">
          <div class="flex flex-wrap gap-1.5">
            <button type="button" class="btn-ghost js-save-user" data-id="${u.id}" ${lockIfSelf(u.id)}>Guardar</button>
            <button type="button" class="btn-ghost btn-danger js-suspend-user" data-id="${u.id}" ${u.status === 'Suspendido' || u.id === USER_ID ? 'disabled' : ''}>Suspender</button>
          </div>
        </td>
      </tr>
    `).join('');

    $$('.js-save-user', body).forEach((btn) => btn.addEventListener('click', onSaveUser));
    $$('.js-suspend-user', body).forEach((btn) => btn.addEventListener('click', onSuspendUser));
  }

  async function onSaveUser(e) {
    const btn = e.currentTarget;
    const id  = Number(btn.dataset.id);
    const row = btn.closest('[data-user-row]');
    const role   = $('.js-role-select', row).value;
    const status = $('.js-status-select-admin', row).value;

    btn.disabled = true;
    const res = await api('../api/users.php?action=update', {
      method: 'PUT',
      body: { id, role, status },
    });
    btn.disabled = false;

    if (res.status !== 'success') {
      alert(res.message);
      return;
    }
    loadUsers();
  }

  async function onSuspendUser(e) {
    const btn = e.currentTarget;
    const id  = Number(btn.dataset.id);
    if (!confirm('Confirmas suspender a este usuario?')) return;

    btn.disabled = true;
    const res = await api('../api/users.php?action=deactivate', {
      method: 'POST',
      body: { id },
    });
    if (res.status !== 'success') alert(res.message);
    loadUsers();
  }

  function bindUserForm() {
    const form = $('#userForm');
    if (!form) return;
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const body     = Object.fromEntries(new FormData(form).entries());
      const feedback = $('#userFormFeedback');
      feedback.textContent = 'Guardando...';
      feedback.className = 'mh-feedback text-sm text-slate-500 dark:text-digital-white/60';

      const res = await api('../api/users.php?action=create', { method: 'POST', body });
      if (res.status === 'success') {
        showFeedback(feedback, 'Usuario registrado correctamente. Se envio un correo de bienvenida.', true);
        form.reset();
        loadUsers();
      } else {
        showFeedback(feedback, res.message, false);
      }
    });
  }

  /* ==================================================================== */
  /* INICIALIZACION                                                        */
  /* ==================================================================== */
  document.addEventListener('DOMContentLoaded', () => {
    initTheme();
    bindNavHighlight();
    bindChecklistSelect();
    bindChecklistTabs();
    bindCallForm();
    bindUserForm();

    $('#themeToggle').addEventListener('click', toggleTheme);
    $('#sidebarToggle').addEventListener('click', openSidebar);
    $('#sidebarClose').addEventListener('click', closeSidebar);
    $('#sidebarOverlay').addEventListener('click', closeSidebar);
    $('#refreshObligationsBtn').addEventListener('click', loadProfile);
    $('#refreshAgendaBtn').addEventListener('click', loadAgenda);
    $('#refreshInventoryBtn').addEventListener('click', loadInventory);
    bindBackToTop();

    loadProfile();
    loadInventory();

    if (IS_MANAGER) {
      populateCallProgramSelect();
      loadUsers();
    }

    if (IS_ADMIN) {
      loadKpis();
      const kpiBtn = $('#refreshKpisBtn');
      if (kpiBtn) kpiBtn.addEventListener('click', loadKpis);
    }

    loadAgenda();
  });
})();
