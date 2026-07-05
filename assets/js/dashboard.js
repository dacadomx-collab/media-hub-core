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

  /**
   * Regla Anti-Doble-Clic (Codex, decreto obligatorio): todo boton de accion
   * asincrona se deshabilita y muestra un estado de "Procesando..." mientras
   * la promesa esta en vuelo. Solo se restaura si la promesa termina en
   * error -- en exito, el llamador decide (normalmente un re-render lo
   * reemplaza). Envolver asi: `await withLoadingState(btn, 'Procesando...', async () => { ... })`.
   */
  async function withLoadingState(btn, loadingText, fn) {
    const original = btn.textContent;
    const wasDisabled = btn.disabled;
    btn.disabled = true;
    btn.textContent = loadingText;
    try {
      const result = await fn();
      return result;
    } catch (err) {
      btn.disabled = wasDisabled;
      btn.textContent = original;
      throw err;
    }
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
    // El Conductor no tiene la seccion "Mis Obligaciones" (Fase 5.9 -- fue
    // sustituida por los accesos rapidos de #conductorHomeCards).
    if (!$('#obligationsList')) return;

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
    // El Conductor no ve la Agenda General (Clientes Jornal) -- gestiona su
    // propio show desde #mis-invitados (Fase 5.9).
    if (!$('#agendaList')) return;

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

    const res = await withLoadingState(btn, 'Procesando...', () => api('../api/agenda.php?action=verify_advance', {
      method: 'PUT',
      body: { call_id: callId, advance_paid: advancePaid },
    }));
    if (res.status !== 'success') alert(res.message);
    loadAgenda();
  }

  async function onStatusChange(e) {
    const sel    = e.currentTarget;
    const callId = Number(sel.dataset.call);
    const status = sel.value;
    sel.disabled = true;

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
    if (!sel) return;

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
    // El Conductor no ve Inventario y Flota (Fase 5.9 -- no es parte de sus
    // tareas operativas).
    if (!$('#inventoryItemsBody')) return;

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

    const res = await withLoadingState(btn, 'Procesando...', () => api('../api/inventory.php?action=set_maintenance', {
      method: 'POST',
      body: { asset_type: 'Vehiculo', asset_id: assetId },
    }));
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
    const res = await withLoadingState(btn, 'Procesando...', () => api('../api/inventory.php?action=checkout', {
      method: 'POST',
      body: { asset_type: 'Inventario', asset_id: Number(btn.dataset.assetId), call_id: currentCallId },
    }));
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

    const res = await withLoadingState(btn, 'Procesando...', () => api('../api/inventory.php?action=checkin', {
      method: 'POST',
      body: {
        asset_type: 'Inventario',
        asset_id: Number(btn.dataset.assetId),
        call_id: currentCallId,
        damaged,
        condition_notes: notes.value.trim(),
      },
    }));
    if (res.status !== 'success') alert(res.message);
    await loadInventory();
  }

  /* ==================================================================== */
  /* D) PANEL ADMINISTRATIVO MULTI-ROL (api/users.php)                     */
  /* ==================================================================== */
  const ROLE_OPTIONS   = ['Super_admin', 'Admin', 'Lider_Proyecto', 'Staff_Tecnico', 'Lider_Logistica', 'Team', 'Conductor', 'Cliente'];
  const STATUS_OPTIONS = ['Activo', 'Suspendido', 'Troll_Mode', 'Pendiente'];

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
      case 'Pendiente':  return 'bg-sky-500/15 text-sky-600 dark:text-sky-400';
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
            <button type="button" class="btn-ghost js-reset-password" data-email="${esc(u.email)}" ${u.status !== 'Activo' ? 'disabled title="Solo disponible para cuentas Activas"' : ''}>Resetear Contrasena</button>
            <button type="button" class="btn-ghost js-resend-invite" data-id="${u.id}" ${['Pendiente', 'Suspendido', 'Troll_Mode'].includes(u.status) ? '' : 'disabled title="Solo disponible para Pendiente/Suspendido/Troll Mode"'}>Reenviar invitacion</button>
            <button type="button" class="btn-ghost btn-danger js-suspend-user" data-id="${u.id}" ${u.status === 'Suspendido' || u.id === USER_ID ? 'disabled' : ''}>Suspender</button>
          </div>
        </td>
      </tr>
    `).join('');

    $$('.js-save-user', body).forEach((btn) => btn.addEventListener('click', onSaveUser));
    $$('.js-suspend-user', body).forEach((btn) => btn.addEventListener('click', onSuspendUser));
    $$('.js-reset-password', body).forEach((btn) => btn.addEventListener('click', onResetPassword));
    $$('.js-resend-invite', body).forEach((btn) => btn.addEventListener('click', onResendInvite));
  }

  /**
   * Dispara api/forgot_password.php a nombre del Administrador (Hito 2,
   * Fase 5.3). Ese endpoint es un procesador de formulario clasico (no
   * JSON, responde con redirect) reutilizado tal cual -- comparte la
   * misma sesion PHP del dashboard, por lo que el csrf_token de la sesion
   * activa es valido. Se ignora el cuerpo de la respuesta (HTML del
   * redirect); solo importa que la peticion se haya completado.
   */
  async function onResetPassword(e) {
    const btn = e.currentTarget;
    const email = btn.dataset.email;
    if (!confirm(`Enviar enlace de restablecimiento de contrasena a ${email}?`)) return;

    try {
      await withLoadingState(btn, 'Procesando...', () => fetch('../api/forgot_password.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ email, csrf_token: CSRF }).toString(),
      }));
      alert(`Enlace de restablecimiento enviado a ${email} (si la cuenta esta Activa).`);
    } catch (err) {
      alert('No se pudo conectar con el servidor.');
    } finally {
      btn.disabled = false;
    }
  }

  async function onSaveUser(e) {
    const btn = e.currentTarget;
    const id  = Number(btn.dataset.id);
    const row = btn.closest('[data-user-row]');
    const role   = $('.js-role-select', row).value;
    const status = $('.js-status-select-admin', row).value;

    const res = await withLoadingState(btn, 'Procesando...', () => api('../api/users.php?action=update', {
      method: 'PUT',
      body: { id, role, status },
    }));

    if (res.status !== 'success') {
      btn.disabled = false;
      btn.textContent = 'Guardar';
      alert(res.message);
      return;
    }

    // Feedback visual instantaneo (Fase 5.7): antes esto guardaba en
    // silencio -- el backend siempre respondia bien, pero sin confirmacion
    // visible parecia que el clic "no se proceso". Mismo patron que
    // onCopyGuestLink() (cambio de texto temporal en el boton). loadUsers()
    // se pospone hasta despues del setTimeout porque re-renderiza toda la
    // tabla de inmediato -- si se llamara antes, este mismo boton (btn) ya
    // habria sido reemplazado en el DOM y el cambio de texto seria invisible.
    btn.textContent = 'Guardado ✓';
    setTimeout(() => {
      loadUsers();
    }, 900);
  }

  async function onSuspendUser(e) {
    const btn = e.currentTarget;
    const id  = Number(btn.dataset.id);
    if (!confirm('Confirmas suspender a este usuario?')) return;

    const res = await withLoadingState(btn, 'Procesando...', () => api('../api/users.php?action=deactivate', {
      method: 'POST',
      body: { id },
    }));
    if (res.status !== 'success') {
      btn.disabled = false;
      btn.textContent = 'Suspender';
      alert(res.message);
      return;
    }
    loadUsers();
  }

  async function onResendInvite(e) {
    const btn = e.currentTarget;
    const id  = Number(btn.dataset.id);
    if (!confirm('Reenviar invitacion de activacion (Plantilla 1) a este usuario?')) return;

    const res = await withLoadingState(btn, 'Procesando...', () => api('../api/users.php?action=resend_invite', {
      method: 'POST',
      body: { id },
    }));

    if (res.status !== 'success') {
      btn.disabled = false;
      btn.textContent = 'Reenviar invitacion';
      alert(res.message);
      return;
    }

    btn.textContent = 'Enviado ✓';
    setTimeout(() => { loadUsers(); }, 900);
  }

  function bindUserForm() {
    const form = $('#userForm');
    if (!form) return;
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const body     = Object.fromEntries(new FormData(form).entries());
      const feedback = $('#userFormFeedback');
      const submitBtn = form.querySelector('button[type="submit"]');
      feedback.textContent = '';

      const res = await withLoadingState(submitBtn, 'Procesando...', () => api('../api/users.php?action=create', { method: 'POST', body }));

      submitBtn.disabled = false;
      submitBtn.textContent = 'Enviar Invitacion';

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
  /* SHOWS NATIVOS + CONDUCTOR INLINE (Fase 5.2)                           */
  /* ==================================================================== */
  const IS_CONDUCTOR = window.MH_IS_CONDUCTOR;

  function scheduleLabel(schedule) {
    if (!schedule) return 'Sin calendario definido';
    let parsed = schedule;
    if (typeof schedule === 'string') {
      try { parsed = JSON.parse(schedule); } catch (err) { return 'Sin calendario definido'; }
    }
    const days  = (parsed.days || []).join(', ') || 'Dias no definidos';
    const start = parsed.start_time || '--:--';
    const end   = parsed.end_time || '--:--';
    return `${days} &middot; ${start} - ${end}`;
  }

  async function loadNativeShows() {
    const list = $('#nativeShowsList');
    if (!list) return;
    const res = await api('../api/programs.php?action=list_native');
    if (res.status !== 'success') {
      list.innerHTML = `<p class="text-sm text-red-500">${esc(res.message)}</p>`;
      return;
    }
    renderNativeShowsList(res.data.programs || []);
  }

  function renderNativeShowsList(programs) {
    const list = $('#nativeShowsList');
    if (!programs.length) {
      list.innerHTML = '<p class="text-sm text-slate-500 dark:text-digital-white/60">Sin shows nativos registrados todavia.</p>';
      return;
    }

    list.innerHTML = programs.map((p) => `
      <div class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 dark:border-white/10">
        ${p.logo_url
          ? `<img src="../${esc(p.logo_url)}" alt="" class="w-12 h-12 rounded-lg object-cover shrink-0">`
          : `<div class="w-12 h-12 rounded-lg bg-turquoise/15 grid place-items-center text-turquoise font-display font-bold shrink-0">${esc((p.name || '?').slice(0, 2).toUpperCase())}</div>`
        }
        <div class="min-w-0 flex-1">
          <p class="font-display font-semibold text-sm truncate">${esc(p.name)}</p>
          <p class="text-xs text-slate-500 dark:text-digital-white/50 truncate">${scheduleLabel(p.production_schedule)}</p>
          <p class="text-xs text-turquoise">${p.conductor_name ? 'Conductor: ' + esc(p.conductor_name) : 'Sin Conductor asignado'}</p>
        </div>
        <span class="badge ${p.is_active ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400' : 'bg-slate-400/15 text-slate-500'} shrink-0">${p.is_active ? 'Activo' : 'Inactivo'}</span>
      </div>
    `).join('');
  }

  /**
   * Componente dinamico de Redes Sociales (Hito 3, Fase 5.3; reutilizado
   * por el Conductor en Fase 5.11): cero, una o varias filas de
   * "plataforma + URL". `containerId` permite reusar el mismo componente
   * en distintos formularios (alta de Show Nativo por Admin vs. ficha
   * propia del Conductor) sin que sus filas se mezclen.
   */
  function addSocialLinkRow(platform, url, containerId) {
    const container = $('#' + (containerId || 'socialLinksRows'));
    if (!container) return;

    const row = document.createElement('div');
    row.className = 'flex gap-2 js-social-link-row';
    row.innerHTML = `
      <input type="text" class="field-input js-social-platform" placeholder="Plataforma (Instagram, YouTube...)" value="${platform ? esc(platform) : ''}" maxlength="60">
      <input type="url" class="field-input js-social-url" placeholder="https://" value="${url ? esc(url) : ''}" maxlength="255">
      <button type="button" class="btn-ghost btn-danger js-remove-social-link" aria-label="Quitar">&times;</button>
    `;
    container.appendChild(row);
    row.querySelector('.js-remove-social-link').addEventListener('click', () => row.remove());
  }

  /* `scope` acota la busqueda de filas a un contenedor especifico -- sin
     esto, dos formularios con redes sociales en la misma pagina mezclarian
     sus filas al recolectar. */
  function collectSocialLinks(scope) {
    return $$('.js-social-link-row', scope).reduce((acc, row) => {
      const platform = row.querySelector('.js-social-platform').value.trim();
      const url = row.querySelector('.js-social-url').value.trim();
      if (platform || url) acc.push({ platform, url });
      return acc;
    }, []);
  }

  function bindNativeShowForm() {
    const form = $('#nativeShowForm');
    if (!form) return;

    const toggle = $('#createConductorToggle');
    const fields = $('#conductorInlineFields');
    if (toggle && fields) {
      toggle.addEventListener('change', () => {
        fields.classList.toggle('hidden', !toggle.checked);
      });
    }

    const addSocialBtn = $('#addSocialLinkBtn');
    if (addSocialBtn) {
      addSocialBtn.addEventListener('click', () => addSocialLinkRow());
    }

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const feedback = $('#nativeShowFormFeedback');
      feedback.textContent = 'Guardando...';
      feedback.className = 'mh-feedback text-sm text-slate-500 dark:text-digital-white/60';

      $('#socialLinksJsonInput').value = JSON.stringify(collectSocialLinks());

      const formData = new FormData(form);
      formData.append('csrf_token', CSRF);

      const submitBtn = form.querySelector('button[type="submit"]');

      try {
        const json = await withLoadingState(submitBtn, 'Procesando...', async () => {
          const res = await fetch('../api/programs.php?action=create_native', { method: 'POST', body: formData });
          return res.json();
        });

        if (json.status === 'success') {
          showFeedback(feedback, json.message || 'Show nativo creado correctamente.', true);
          form.reset();
          $('#socialLinksRows').innerHTML = '';
          fields.classList.add('hidden');
          loadNativeShows();
        } else {
          showFeedback(feedback, json.message || 'No se pudo crear el show.', false);
        }
      } catch (err) {
        showFeedback(feedback, 'No se pudo conectar con el servidor.', false);
      } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Crear Show Nativo';
      }
    });
  }

  /* ==================================================================== */
  /* CONSOLA DEL CONDUCTOR (Fase 5.2/5.5/5.8)                              */
  /* ==================================================================== */
  let conductorProgramId = null;
  let conductorShowData  = null;
  let conductorCalls     = [];

  async function loadConductorShow() {
    const card = $('#conductorShowCard');
    if (!card) return;

    const res = await api('../api/programs.php?action=my_native_show');
    if (res.status !== 'success' || !(res.data.programs || []).length) {
      card.innerHTML = '<p class="text-sm text-slate-500 dark:text-digital-white/60">Aun no tienes un show nativo asignado. Contacta a un Administrador.</p>';
      return;
    }

    const show = res.data.programs[0];
    conductorProgramId = show.id;
    conductorShowData  = show;

    card.innerHTML = `
      <div class="flex items-start gap-4">
        ${show.logo_url
          ? `<img src="../${esc(show.logo_url)}" alt="" class="w-16 h-16 rounded-xl object-cover shrink-0">`
          : `<div class="w-16 h-16 rounded-xl bg-turquoise/15 grid place-items-center text-turquoise font-display font-bold text-xl shrink-0">${esc((show.name || '?').slice(0, 2).toUpperCase())}</div>`
        }
        <div class="min-w-0">
          <h3 class="font-display font-bold text-lg">${esc(show.name)}</h3>
          ${show.affiliated_channel ? `<p class="text-xs text-turquoise font-semibold mt-0.5">${esc(show.affiliated_channel)}</p>` : ''}
          <p class="text-sm text-slate-500 dark:text-digital-white/60 mt-1">${esc(show.catalog_description || '')}</p>
          <p class="text-xs text-turquoise mt-2">${scheduleLabel(show.production_schedule)}</p>
          ${show.conductor_notes ? `<p class="text-xs text-slate-500 dark:text-digital-white/50 mt-2 italic">${esc(show.conductor_notes)}</p>` : ''}
        </div>
      </div>
    `;

    prefillConductorProfileForm(show);
    renderConductorHomeCards();
    loadConductorAgenda();
    loadGuestLinks();
  }

  /* ---- Accesos rapidos de Inicio para el Conductor (Fase 5.9) ---- */
  function renderConductorHomeCards() {
    const wrap = $('#conductorHomeCards');
    if (!wrap) return;

    const next = conductorCalls.length ? nextUpcomingCall(conductorCalls) : null;
    const showName = (conductorShowData && conductorShowData.name) || 'tu programa';

    wrap.innerHTML = `
      <a href="#mi-programa" class="rounded-2xl border border-slate-200 dark:border-turquoise/10 bg-white dark:bg-[#01243f] p-4 sm:p-5 block hover:border-turquoise transition-colors">
        <p class="text-xs uppercase tracking-wide text-turquoise font-semibold mb-1">Mi Programa</p>
        <p class="font-display font-bold text-base">${esc(showName)}</p>
        <p class="text-xs text-slate-500 dark:text-digital-white/50 mt-1">Ver ficha y editar tus datos</p>
      </a>
      <a href="#mis-invitados" class="rounded-2xl border border-slate-200 dark:border-turquoise/10 bg-white dark:bg-[#01243f] p-4 sm:p-5 block hover:border-turquoise transition-colors">
        <p class="text-xs uppercase tracking-wide text-turquoise font-semibold mb-1">Siguiente Programa</p>
        ${next
          ? `<p class="font-display font-bold text-base">${esc(fmtDate(next.call_date))}</p>
             <p class="text-xs text-slate-500 dark:text-digital-white/50 mt-1">${esc(fmtTime(next.start_time))} - ${esc(fmtTime(next.end_time))} &middot; Gestiona tus invitados</p>`
          : `<p class="font-display font-bold text-base">Sin llamado agendado</p>
             <p class="text-xs text-slate-500 dark:text-digital-white/50 mt-1">Aun no hay un proximo llamado</p>`
        }
      </a>
    `;
  }

  /* ---- Editar Ficha y Contacto (Fase 5.8) ---- */
  async function prefillConductorProfileForm(show) {
    const form = $('#conductorProfileForm');
    if (!form) return;

    form.affiliated_channel.value = show.affiliated_channel || '';
    form.conductor_notes.value    = show.conductor_notes || '';

    // Redes sociales del show (Fase 5.11): reutiliza el mismo componente
    // dinamico de filas "plataforma + URL" del alta de Show Nativo.
    const socialRows = $('#conductorSocialLinksRows');
    if (socialRows) {
      socialRows.innerHTML = '';
      let socialLinks = [];
      try {
        socialLinks = show.public_social_links ? JSON.parse(show.public_social_links) : [];
      } catch (err) {
        socialLinks = [];
      }
      socialLinks.forEach((link) => addSocialLinkRow(link.platform, link.url, 'conductorSocialLinksRows'));
    }

    const res = await api('../api/users.php?action=me');
    if (res.status === 'success') {
      const profile = res.data.profile || {};
      form.whatsapp.value                       = profile.whatsapp || '';
      form.show_whatsapp_publicly.checked       = !!Number(profile.show_whatsapp_publicly);
      form.show_email_publicly.checked          = !!Number(profile.show_email_publicly);
    }
  }

  function bindConductorProfileForm() {
    const toggleBtn = $('#toggleConductorProfileFormBtn');
    const form      = $('#conductorProfileForm');
    if (!toggleBtn || !form) return;

    toggleBtn.addEventListener('click', () => {
      form.classList.toggle('hidden');
    });

    const addSocialBtn = $('#addConductorSocialLinkBtn');
    if (addSocialBtn) {
      addSocialBtn.addEventListener('click', () => addSocialLinkRow(null, null, 'conductorSocialLinksRows'));
    }

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const feedback = $('#conductorProfileFeedback');
      const submitBtn = form.querySelector('button[type="submit"]');
      feedback.textContent = '';

      if (!conductorProgramId) {
        showFeedback(feedback, 'No tienes un show nativo asignado.', false);
        return;
      }

      const [profileRes, showRes] = await withLoadingState(submitBtn, 'Procesando...', () => Promise.all([
        api('../api/users.php?action=update_self', {
          method: 'PUT',
          body: {
            whatsapp:                form.whatsapp.value.trim(),
            show_whatsapp_publicly:  form.show_whatsapp_publicly.checked,
            show_email_publicly:     form.show_email_publicly.checked,
          },
        }),
        api('../api/programs.php?action=update_conductor_profile', {
          method: 'PUT',
          body: {
            program_id:           conductorProgramId,
            affiliated_channel:   form.affiliated_channel.value.trim(),
            conductor_notes:      form.conductor_notes.value.trim(),
            public_social_links:  collectSocialLinks($('#conductorSocialLinksRows')),
          },
        }),
      ]));

      submitBtn.disabled = false;
      submitBtn.textContent = 'Guardar Ficha';

      if (profileRes.status === 'success' && showRes.status === 'success') {
        showFeedback(feedback, 'Ficha actualizada correctamente.', true);
        loadConductorShow();
      } else {
        showFeedback(feedback, (profileRes.status !== 'success' ? profileRes.message : showRes.message), false);
      }
    });
  }

  /* ---- Agenda de Llamados del show (Fase 5.5) ---- */
  /* Fase 5.11: la lista visual "Agenda General de Llamados" fue removida
     por no aportar valor operativo al Conductor (Dead Code). Esta funcion
     YA NO depende de ningun contenedor de esa lista -- solo obtiene los
     datos de la agenda del show y alimenta el planner "Siguiente Programa"
     (#nextCallCard) y los accesos rapidos de Inicio (#conductorHomeCards).
     Root cause de un bug anterior (Fase 5.10): gatear esta funcion entera
     con `if (!list) return;` apagaba TODO lo demas en cuanto se removia el
     contenedor de la lista -- ya no depende de el en absoluto. */
  async function loadConductorAgenda() {
    if (!conductorProgramId) return;

    const res = await api(`../api/agenda.php?action=list&program_id=${conductorProgramId}`);
    if (res.status !== 'success') {
      conductorCalls = [];
      renderNextCallCard(conductorCalls);
      renderConductorHomeCards();
      return;
    }

    conductorCalls = res.data.calls || [];
    renderNextCallCard(conductorCalls);
    renderConductorHomeCards();
  }

  /* ---- Siguiente Programa: planner del proximo llamado (Fase 5.8) ---- */
  function nextUpcomingCall(calls) {
    const today = new Date().toISOString().slice(0, 10);
    return calls.find((c) => c.call_date >= today && c.status !== 'Cancelado') || null;
  }

  /* Fase 5.10 — Automatizacion predictiva: si el Conductor aun no tiene un
     proximo llamado agendado, se precarga la fecha del proximo miercoles
     (incluye hoy si hoy ya es miercoles) y el horario institucional por
     defecto 17:00-17:30. El Conductor puede cambiar ambos libremente. */
  function nextWednesdayDate() {
    const d = new Date();
    const diff = (3 - d.getDay() + 7) % 7;
    d.setDate(d.getDate() + diff);
    return d.toISOString().slice(0, 10);
  }

  function renderNextCallCard(calls) {
    const wrap = $('#nextCallCard');
    if (!wrap) return;

    const next = nextUpcomingCall(calls);
    const guestOptions = [1, 2, 3, 4, 5, 6].map((n) => `<option value="${n}">${n} invitado${n > 1 ? 's' : ''}</option>`).join('');

    const defaults = next
      ? {
          call_date: next.call_date,
          start_time: (next.start_time || '').slice(0, 5),
          end_time: (next.end_time || '').slice(0, 5),
          episode_theme: next.episode_theme || '',
        }
      : { call_date: nextWednesdayDate(), start_time: '17:00', end_time: '17:30', episode_theme: '' };

    const summary = next
      ? `<div class="p-3 rounded-xl border border-slate-200 dark:border-white/10">
          <p class="font-display font-semibold text-sm">${esc(next.title)}</p>
          <p class="text-xs text-slate-500 dark:text-digital-white/50">${esc(fmtDate(next.call_date))} &middot; ${esc(fmtTime(next.start_time))} - ${esc(fmtTime(next.end_time))} &middot; ${esc(next.location)}</p>
        </div>`
      : `<p class="text-sm text-slate-500 dark:text-digital-white/60">Aun no tienes un proximo llamado agendado. Te sugerimos el proximo miercoles a las 5:00 PM &mdash; puedes cambiarlo libremente.</p>`;

    wrap.innerHTML = `
      ${summary}
      <form id="nextProgramForm" class="space-y-3">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <label class="field-label">Fecha
            <input type="date" name="call_date" class="field-input" value="${esc(defaults.call_date)}" required>
          </label>
          <label class="field-label">Hora inicio
            <input type="time" name="start_time" class="field-input" value="${esc(defaults.start_time)}" required>
          </label>
          <label class="field-label">Hora fin
            <input type="time" name="end_time" class="field-input" value="${esc(defaults.end_time)}" required>
          </label>
        </div>
        <label class="field-label">Tema del episodio
          <input type="text" name="episode_theme" class="field-input" maxlength="255" value="${esc(defaults.episode_theme)}" placeholder="Ej. Elecciones municipales 2026">
        </label>
        <div class="flex flex-wrap items-end gap-2">
          <label class="field-label">Cuantos invitados
            <select name="quantity" class="field-input">${guestOptions}</select>
          </label>
          <button type="submit" class="btn-primary">Generar Programa e Invitados</button>
        </div>
        <p class="mh-feedback text-sm" id="nextProgramFeedback"></p>
        <div id="batchGuestLinksResult" class="space-y-1"></div>
      </form>
    `;

    const form = $('#nextProgramForm', wrap);
    form.dataset.callId = next ? next.id : '';
    form.addEventListener('submit', onSaveNextProgram);
  }

  /* Un solo viaje asincrono (desde la perspectiva del Conductor: un clic,
     un boton, un estado de carga): primero crea/actualiza el llamado con
     save_conductor_call, y con el call_id resultante genera el lote de
     enlaces de invitado en create_batch (Fase 5.10). */
  async function onSaveNextProgram(e) {
    e.preventDefault();
    const form = e.currentTarget;
    const feedback = $('#nextProgramFeedback');
    const resultBox = $('#batchGuestLinksResult');
    const btn = form.querySelector('button[type="submit"]');
    const originalLabel = btn.textContent;
    feedback.textContent = '';
    resultBox.innerHTML = '';

    if (!conductorProgramId) {
      showFeedback(feedback, 'No tienes un show nativo asignado.', false);
      return;
    }

    const existingCallId = form.dataset.callId ? Number(form.dataset.callId) : null;

    const res = await withLoadingState(btn, 'Procesando lote...', async () => {
      const saveRes = await api('../api/agenda.php?action=save_conductor_call', {
        method: 'POST',
        body: {
          program_id:     conductorProgramId,
          call_id:        existingCallId,
          call_date:      form.call_date.value,
          start_time:     form.start_time.value,
          end_time:       form.end_time.value,
          episode_theme:  form.episode_theme.value.trim(),
        },
      });

      if (saveRes.status !== 'success') {
        return saveRes;
      }

      return api('../api/guest_links.php?action=create_batch', {
        method: 'POST',
        body: {
          program_id: conductorProgramId,
          call_id:    saveRes.data.id,
          quantity:   Number(form.quantity.value),
        },
      });
    });

    btn.disabled = false;
    btn.textContent = originalLabel;

    if (res.status === 'success' && res.data && res.data.links) {
      showFeedback(feedback, res.message, true);
      const links = res.data.links || [];
      resultBox.innerHTML = links.map((l, i) => `
        <div class="flex items-center justify-between gap-2 text-xs p-2 rounded-lg bg-turquoise/5">
          <span>Invitado ${i + 1}</span>
          <button type="button" class="btn-ghost js-copy-batch-link" data-token="${esc(l.token)}">Copiar enlace</button>
        </div>
      `).join('');
      $$('.js-copy-batch-link', resultBox).forEach((b) => b.addEventListener('click', onCopyGuestLink));
      // Nota: NO se vuelve a llamar loadConductorAgenda() aqui -- eso
      // re-renderiza #nextCallCard por completo (incluido este resultBox
      // recien pintado con los enlaces para copiar). Solo se refresca la
      // grilla de estatus de invitados; la agenda historica se actualiza
      // la proxima vez que el Conductor la abra o pulse "Actualizar".
      loadGuestLinks();
    } else {
      showFeedback(feedback, res.message, false);
    }
  }

  /* ==================================================================== */
  /* SOPORTE (Fase 5.9)                                                    */
  /* ==================================================================== */
  function bindSupportForm() {
    const form = $('#supportForm');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const feedback = $('#supportFeedback');
      const submitBtn = form.querySelector('button[type="submit"]');
      const message = form.message.value.trim();
      feedback.textContent = '';

      if (!message) {
        showFeedback(feedback, 'Escribe un mensaje antes de enviar.', false);
        return;
      }

      const res = await withLoadingState(submitBtn, 'Procesando...', () => api('../api/support.php?action=send', {
        method: 'POST',
        body: { message },
      }));

      submitBtn.disabled = false;
      submitBtn.textContent = 'Enviar a Media HUB';

      if (res.status === 'success') {
        showFeedback(feedback, res.message, true);
        form.reset();
      } else {
        showFeedback(feedback, res.message, false);
      }
    });
  }

  /* ---- Estatus de Cintillos y Datos de Invitados (Fase 5.5/5.9) ---- */
  function completionTone(link) {
    if (link.status === 'Expirado' && !link.completion.has_submission) {
      return 'gray';
    }
    if (!link.completion.has_submission) {
      return 'gray';
    }
    if (link.completion.required_done < link.completion.required_total) {
      return 'red';
    }
    if (link.completion.optional_done < link.completion.optional_total) {
      return 'amber';
    }
    return 'green';
  }

  const COMPLETION_TONE_CLASSES = {
    gray:  'bg-slate-400/15 text-slate-500',
    red:   'bg-red-500/15 text-red-600 dark:text-red-400',
    amber: 'bg-amber-500/15 text-amber-600 dark:text-amber-400',
    green: 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400',
  };

  async function loadGuestLinks() {
    const grid = $('#guestLinksGrid');
    if (!grid || !conductorProgramId) return;

    const res = await api(`../api/guest_links.php?action=list&program_id=${conductorProgramId}`);
    if (res.status !== 'success') {
      grid.innerHTML = `<p class="text-sm text-red-500">${esc(res.message)}</p>`;
      return;
    }

    renderGuestLinksGrid(res.data.links || []);
  }

  function renderGuestLinksGrid(links) {
    const grid = $('#guestLinksGrid');
    if (!links.length) {
      grid.innerHTML = '<p class="text-sm text-slate-500 dark:text-digital-white/60 sm:col-span-2 lg:col-span-3">Aun no has generado enlaces.</p>';
      return;
    }

    const OPTIONAL_FIELD_LABELS = {
      social_links:   'Redes sociales',
      whatsapp:       'WhatsApp',
      website:        'Web',
      invite_message: 'Mensaje',
    };

    grid.innerHTML = links.map((l) => {
      const tone = completionTone(l);
      const toneClass = COMPLETION_TONE_CLASSES[tone];
      const callInfo = l.call_title
        ? `<p class="text-xs text-turquoise truncate">${esc(l.call_title)} &middot; ${esc(l.call_date)} ${esc(l.start_time)}</p>`
        : '<p class="text-xs text-slate-400">Enlace suelto (sin llamado atado)</p>';

      // Monitor OBS (Fase 5.4/5.5): badges individuales por campo obligatorio.
      const requiredBadges = Object.entries(l.completion.required_fields || {}).map(([field, done]) => `
        <span class="badge ${done ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400' : 'bg-slate-400/15 text-slate-500'}">
          ${done ? '&#10003;' : '&#10007;'} ${field === 'full_name' ? 'Nombre' : 'Puesto'}
        </span>
      `).join('');

      // Campos opcionales vacios se normalizan a "" / [] en el backend
      // (nunca null) -- si el monitor detecta que faltan, se avisa por
      // consola para que produccion/OBS sepa que aun no hay dato real.
      if (l.completion.optional_done < l.completion.optional_total) {
        console.warn('[MH-OBS-MONITOR] Datos opcionales vacios para el invitado, inicializados como array seguro.', {
          link_id: l.id,
          guest: l.guest_full_name || '(sin nombre aun)',
        });
      }

      const optionalBadges = Object.entries(l.completion.optional_fields || {}).map(([field, done]) => `
        <span class="badge ${done ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400' : 'bg-amber-500/15 text-amber-600 dark:text-amber-400'}">
          ${done ? '&#10003;' : '&#8226;'} ${OPTIONAL_FIELD_LABELS[field] || field}
        </span>
      `).join('');

      return `
        <div class="rounded-xl border border-slate-200 dark:border-white/10 p-3 space-y-2">
          <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
              <p class="font-display font-semibold text-sm truncate">${l.guest_full_name ? esc(l.guest_full_name) : '<span class="text-slate-400">Invitado sin datos</span>'}</p>
              ${l.guest_title_position ? `<p class="text-xs text-slate-500 dark:text-digital-white/50 truncate">${esc(l.guest_title_position)}</p>` : ''}
            </div>
            <span class="badge ${toneClass} shrink-0">${l.status === 'Expirado' ? 'Expirado' : 'Activo'}</span>
          </div>
          ${callInfo}
          <div>
            <p class="text-[11px] text-slate-500 dark:text-digital-white/50 mb-0.5">Obligatorios (listo para el generador de caracteres)</p>
            <div class="flex flex-wrap gap-1">${requiredBadges}</div>
          </div>
          <div>
            <p class="text-[11px] text-slate-500 dark:text-digital-white/50 mb-0.5">Opcionales (difusion/redes)</p>
            <div class="flex flex-wrap gap-1">${optionalBadges}</div>
          </div>
          <p class="text-[11px] text-slate-400">Clic ${Math.min(l.click_count, 3)}/3</p>
          <button type="button" class="btn-ghost w-full js-copy-link" data-token="${esc(l.token)}" ${l.status === 'Expirado' ? 'disabled' : ''}>Copiar enlace</button>
        </div>
      `;
    }).join('');

    $$('.js-copy-link', grid).forEach((btn) => btn.addEventListener('click', onCopyGuestLink));
  }

  async function onCopyGuestLink(e) {
    const btn = e.currentTarget;
    const url = `${window.location.origin}${window.location.pathname.replace('dashboard/index.php', '')}guest_form.php?token=${btn.dataset.token}`;

    try {
      await navigator.clipboard.writeText(url);
      const original = btn.textContent;
      btn.textContent = 'Copiado!';
      setTimeout(() => { btn.textContent = original; }, 1800);
    } catch (err) {
      alert('No se pudo copiar automaticamente. Enlace: ' + url);
    }
  }

  async function onGenerateGuestLink(e) {
    const feedback = $('#guestLinkFeedback');
    if (!conductorProgramId) {
      showFeedback(feedback, 'No tienes un show nativo asignado.', false);
      return;
    }

    const callId = e && e.currentTarget ? e.currentTarget.dataset.callId : undefined;
    const btn = e && e.currentTarget ? e.currentTarget : $('#generateGuestLinkBtn');
    const originalLabel = btn.textContent;

    feedback.textContent = '';

    const body = { program_id: conductorProgramId };
    if (callId) body.call_id = Number(callId);

    const res = await withLoadingState(btn, 'Procesando...', () => api('../api/guest_links.php?action=create', { method: 'POST', body }));
    btn.disabled = false;
    btn.textContent = originalLabel;

    if (res.status === 'success') {
      showFeedback(feedback, 'Enlace generado correctamente.', true);
      loadGuestLinks();
    } else {
      showFeedback(feedback, res.message, false);
    }
  }

  /* ==================================================================== */
  /* PRUEBA DE HUMO — FLUJO COMPLETO DE ONBOARDING (Fase 5.2)              */
  /* ==================================================================== */
  async function onSmokeTest() {
    const feedback = $('#smokeTestFeedback');
    const btn = $('#smokeTestBtn');
    if (!confirm('Esto creara un usuario de prueba REAL (rol Team) y enviara un correo a la bandeja de pruebas configurada. Continuar?')) return;

    feedback.textContent = '';

    const stamp = Date.now();
    const res = await withLoadingState(btn, 'Procesando...', () => api('../api/users.php?action=create', {
      method: 'POST',
      body: {
        full_name: 'Usuario Prueba de Humo',
        email: `smoke.test.${stamp}@mediahubbcs.com`,
        role: 'Team',
      },
    }));

    btn.disabled = false;
    btn.textContent = 'Simular Flujo Onboarding completo';

    if (res.status === 'success') {
      showFeedback(feedback, 'Usuario de prueba creado. Revisa la bandeja de pruebas configurada en .env para la Plantilla 1, completa set_password.php y confirma la Plantilla 2.', true);
      loadUsers();
    } else {
      showFeedback(feedback, res.message, false);
    }
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
    // Fase 5.11: #supportForm ahora vive en la pagina aislada
    // dashboard/support.php -- bindSupportForm() se auto-descarta (no-op)
    // en cualquier pagina donde ese formulario no exista.
    bindSupportForm();

    $('#themeToggle').addEventListener('click', toggleTheme);
    $('#sidebarToggle').addEventListener('click', openSidebar);
    $('#sidebarClose').addEventListener('click', closeSidebar);
    $('#sidebarOverlay').addEventListener('click', closeSidebar);

    // Fase 5.9: #refreshObligationsBtn/#refreshAgendaBtn/#refreshInventoryBtn
    // viven dentro de secciones que ya NO se renderizan para el Conductor
    // (#inicio muestra #conductorHomeCards en su lugar; #agenda/#inventario
    // quedan ocultas por completo) -- sin esta guarda, addEventListener
    // sobre null tronaba de forma sincrona y congelaba TODO el resto del
    // handler de DOMContentLoaded (root cause del Fase 5.10).
    const refreshObligationsBtn = $('#refreshObligationsBtn');
    if (refreshObligationsBtn) refreshObligationsBtn.addEventListener('click', loadProfile);
    const refreshAgendaBtn = $('#refreshAgendaBtn');
    if (refreshAgendaBtn) refreshAgendaBtn.addEventListener('click', loadAgenda);
    const refreshInventoryBtn = $('#refreshInventoryBtn');
    if (refreshInventoryBtn) refreshInventoryBtn.addEventListener('click', loadInventory);

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

      bindNativeShowForm();
      loadNativeShows();
      const nativeBtn = $('#refreshNativeShowsBtn');
      if (nativeBtn) nativeBtn.addEventListener('click', loadNativeShows);

      const smokeBtn = $('#smokeTestBtn');
      if (smokeBtn) smokeBtn.addEventListener('click', onSmokeTest);
    }

    if (IS_CONDUCTOR) {
      bindConductorProfileForm();
      loadConductorShow();
      const genBtn = $('#generateGuestLinkBtn');
      if (genBtn) genBtn.addEventListener('click', onGenerateGuestLink);
    }

    loadAgenda();
  });
})();
