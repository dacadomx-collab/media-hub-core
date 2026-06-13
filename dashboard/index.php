<?php
/**
 * MH-CORE: Dashboard - Panel de Control privado (Fase 3).
 * Consume de forma asincrona los endpoints de api/ (users, agenda,
 * checklist, inventory) para ofrecer una experiencia mobile-first
 * tipo Stripe/Vercel con sidebar colapsable, tema claro/oscuro y
 * consola de checklists operativos.
 */

session_start();
require_once __DIR__ . '/../api/auth_guard.php';
require_once __DIR__ . '/../api/csrf.php';

$user      = mh_require_auth();
$csrfToken = csrf_token();

$roleLabels = [
    'Administrador'    => 'Administrador',
    'Lider_Proyecto'   => 'Lider de Proyecto',
    'Staff_Tecnico'    => 'Staff Tecnico',
    'Lider_Logistica'  => 'Lider de Logistica',
    'Cliente'          => 'Cliente',
];

$roleDescriptions = [
    'Administrador'    => 'Control total del sistema: usuarios, agenda, inventario y modulo legal.',
    'Lider_Proyecto'   => 'Supervision de programas, agenda del Estudio 5 de Mayo y asignacion de staff por llamado.',
    'Staff_Tecnico'    => 'Tareas tecnicas asignadas por llamado y check-in/check-out de equipo.',
    'Lider_Logistica'  => 'Operacion y check-in/check-out de unidades moviles (Van Terrestre / Embarcacion Maritima).',
    'Cliente'          => 'Seguimiento de tu programa y agenda de llamados en Media HUB.',
];

$roleLabel       = $roleLabels[$user['role']] ?? $user['role'];
$roleDescription = $roleDescriptions[$user['role']] ?? '';
$isManager       = in_array($user['role'], ['Administrador', 'Lider_Proyecto'], true);
$isAdmin         = $user['role'] === 'Administrador';
?>
<!DOCTYPE html>
<html lang="es" class="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Media HUB | Panel de Control</title>
<link rel="icon" type="image/png" href="../assets/img/logo.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    darkMode: 'class',
    theme: {
      extend: {
        colors: {
          'deep-sea': '#022D53',
          'turquoise': '#00BFB2',
          'digital-white': '#FFFFFF',
          'broken-white': '#F8FAFC',
          'slate-charcoal': '#1E293B',
        },
        fontFamily: {
          montserrat: ['Montserrat', 'sans-serif'],
          roboto: ['Roboto', 'sans-serif'],
        },
      },
    },
  };
</script>
<style>
  html { scroll-behavior: smooth; }
  body { font-family: 'Roboto', sans-serif; }
  h1, h2, h3, .font-display { font-family: 'Montserrat', sans-serif; }
  ::-webkit-scrollbar { width: 8px; height: 8px; }
  ::-webkit-scrollbar-thumb { background: rgba(0, 191, 178, .35); border-radius: 999px; }

  .badge {
    display: inline-flex; align-items: center; gap: .25rem;
    padding: .25rem .6rem; border-radius: 999px;
    font-size: .68rem; font-weight: 700; letter-spacing: .03em;
    text-transform: uppercase; white-space: nowrap; line-height: 1.3;
  }
  .btn-primary {
    background: #00BFB2; color: #022D53; font-weight: 700;
    border-radius: 999px; padding: .55rem 1.2rem; font-size: .85rem;
    font-family: 'Montserrat', sans-serif; transition: filter .15s ease;
    border: none; cursor: pointer;
  }
  .btn-primary:hover { filter: brightness(1.08); }
  .btn-primary:disabled { opacity: .6; cursor: not-allowed; }
  .btn-ghost {
    border: 1px solid currentColor; opacity: .75; border-radius: 999px;
    padding: .4rem .9rem; font-size: .76rem; background: transparent;
    cursor: pointer; font-family: 'Montserrat', sans-serif; font-weight: 600;
    transition: opacity .15s ease, border-color .15s ease, color .15s ease;
  }
  .btn-ghost:hover { opacity: 1; border-color: #00BFB2; color: #00BFB2; }
  .btn-ghost:disabled { opacity: .4; cursor: not-allowed; }
  .btn-danger { color: #ef4444; border-color: rgba(239,68,68,.45); }
  .btn-danger:hover { border-color: #ef4444; color: #ef4444; }
  .field-input, .field-select, .field-textarea {
    border: 1px solid rgba(148,163,184,.35); border-radius: .65rem;
    background: transparent; padding: .5rem .7rem; font-size: .85rem;
    width: 100%; color: inherit;
  }
  .field-input:focus, .field-select:focus, .field-textarea:focus {
    outline: none; border-color: #00BFB2; box-shadow: 0 0 0 3px rgba(0,191,178,.15);
  }
  .field-label {
    display: grid; gap: .3rem; font-size: .76rem; font-weight: 600;
    color: inherit; opacity: .8; font-family: 'Montserrat', sans-serif;
  }
  .select-sm {
    border: 1px solid rgba(148,163,184,.35); border-radius: .6rem;
    background: transparent; padding: .35rem .55rem; font-size: .76rem; color: inherit;
  }
  .tab-btn {
    padding: .6rem 1.1rem; border-radius: .8rem; font-size: .82rem;
    font-weight: 700; font-family: 'Montserrat', sans-serif; cursor: pointer;
    border: none; transition: all .15s ease; white-space: nowrap;
  }
  .tab-btn.active { background: #00BFB2; color: #022D53; }
  .tab-btn:not(.active) { background: transparent; color: inherit; opacity: .55; }
  .tab-btn:not(.active):hover { opacity: .85; }
  .checklist-row {
    display: flex; align-items: flex-start; gap: .7rem; padding: .7rem 0;
    border-bottom: 1px solid rgba(148,163,184,.14);
  }
  .checklist-row:last-child { border-bottom: none; }
  .checklist-row input[type="checkbox"] {
    width: 1.2rem; height: 1.2rem; accent-color: #00BFB2; margin-top: .15rem;
    flex-shrink: 0; cursor: pointer;
  }
  .nav-link { transition: color .15s ease, background-color .15s ease; }
  .nav-link.active { background: rgba(0,191,178,.12); color: #00BFB2; }
  .skeleton {
    background: linear-gradient(90deg, rgba(148,163,184,.12) 25%, rgba(148,163,184,.22) 37%, rgba(148,163,184,.12) 63%);
    background-size: 400% 100%; animation: skeleton-loading 1.4s ease infinite; border-radius: .75rem;
  }
  @keyframes skeleton-loading { 0% { background-position: 100% 50%; } 100% { background-position: 0 50%; } }
</style>
</head>
<body class="bg-broken-white text-slate-charcoal dark:bg-deep-sea dark:text-digital-white font-roboto transition-colors duration-300 min-h-screen">

  <div class="flex min-h-screen">

    <!-- ================= SIDEBAR (desktop) / DRAWER (mobile) ================= -->
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 w-72 -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out bg-white dark:bg-[#01243f] border-r border-slate-200 dark:border-turquoise/10 flex flex-col">
      <div class="flex items-center justify-between px-5 py-5 border-b border-slate-100 dark:border-white/5">
        <img src="../assets/img/logo.png" alt="Media HUB" class="h-16 md:h-22 w-auto object-contain">
        <button id="sidebarClose" type="button" aria-label="Cerrar menu" class="lg:hidden grid place-items-center w-9 h-9 rounded-full border border-turquoise/30 hover:border-turquoise transition-colors">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
        </button>
      </div>

      <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1 text-sm font-display font-semibold">
        <a href="#inicio" class="nav-link active flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-turquoise/10">
          <span class="text-base">&#127968;</span> Inicio
        </a>
        <a href="#agenda" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-turquoise/10">
          <span class="text-base">&#128197;</span> Agenda
        </a>
        <a href="#checklist" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-turquoise/10">
          <span class="text-base">&#9989;</span> Checklist de Set
        </a>
        <a href="#inventario" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-turquoise/10">
          <span class="text-base">&#128230;</span> Inventario y Flota
        </a>
        <?php if ($isManager): ?>
        <a href="#admin" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-turquoise/10">
          <span class="text-base">&#9881;&#65039;</span> Administracion
        </a>
        <?php endif; ?>
      </nav>

      <div class="px-3 py-4 border-t border-slate-100 dark:border-white/5 space-y-2">
        <div class="px-3 py-2.5 rounded-xl bg-slate-50 dark:bg-white/5">
          <p class="font-display font-bold text-sm truncate"><?php echo htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8'); ?></p>
          <p class="text-xs text-turquoise font-semibold mt-0.5"><?php echo htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <a href="../api/logout.php" class="flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl border border-slate-200 dark:border-white/10 text-sm font-display font-semibold hover:border-turquoise hover:text-turquoise transition-colors">
          <span>&#128274;</span> Cerrar sesion
        </a>
      </div>
    </aside>

    <!-- Overlay para movil -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-black/50 z-30 hidden lg:hidden"></div>

    <!-- ================= CONTENIDO PRINCIPAL ================= -->
    <div class="flex-1 lg:pl-72 flex flex-col min-h-screen w-full">

      <!-- Topbar -->
      <header class="sticky top-0 z-20 backdrop-blur-md bg-broken-white/80 dark:bg-deep-sea/80 border-b border-slate-200 dark:border-turquoise/10">
        <div class="flex items-center justify-between gap-3 px-4 sm:px-6 h-16">
          <div class="flex items-center gap-3 min-w-0">
            <button id="sidebarToggle" type="button" aria-label="Abrir menu" class="lg:hidden grid place-items-center w-10 h-10 rounded-full border border-turquoise/30 hover:border-turquoise transition-colors shrink-0">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5"/></svg>
            </button>
            <div class="min-w-0">
              <p class="font-display font-extrabold text-sm sm:text-base truncate">Panel de Control</p>
              <p class="text-xs text-slate-500 dark:text-digital-white/50 truncate">Media HUB &mdash; Estudio 5 de Mayo</p>
            </div>
          </div>
          <span class="hidden sm:inline-block badge bg-turquoise/10 text-turquoise"><?php echo htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
      </header>

      <main class="flex-1 px-4 sm:px-6 py-6 space-y-8 pb-28 max-w-6xl w-full mx-auto">

        <?php if ($isAdmin): ?>
        <!-- ============ CENTRO DE COMANDO EJECUTIVO (KPIs) ============ -->
        <section id="kpis" class="space-y-3">
          <div class="flex items-center justify-between gap-2">
            <h2 class="font-display font-bold text-lg">Centro de Comando Ejecutivo</h2>
            <button id="refreshKpisBtn" class="btn-ghost">Actualizar</button>
          </div>
          <div id="kpiGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <div class="skeleton h-24"></div>
            <div class="skeleton h-24"></div>
            <div class="skeleton h-24"></div>
            <div class="skeleton h-24"></div>
            <div class="skeleton h-24"></div>
            <div class="skeleton h-24"></div>
          </div>
        </section>
        <?php endif; ?>

        <!-- ============ A) HEADER COGNITIVO + MIS OBLIGACIONES ============ -->
        <section id="inicio" class="space-y-4">
          <div class="rounded-2xl bg-gradient-to-br from-deep-sea via-[#01355f] to-[#011c35] dark:from-[#033a6b] dark:via-[#022D53] dark:to-[#011626] text-white p-5 sm:p-6">
            <p class="text-xs uppercase tracking-widest text-turquoise font-semibold mb-1">Bienvenido de nuevo</p>
            <h1 class="font-display font-extrabold text-xl sm:text-2xl mb-1">Hola, <?php echo htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="text-sm text-white/70 max-w-2xl"><?php echo htmlspecialchars($roleDescription, ENT_QUOTES, 'UTF-8'); ?></p>
          </div>

          <div class="rounded-2xl border border-slate-200 dark:border-turquoise/10 bg-white dark:bg-[#01243f] p-4 sm:p-5">
            <div class="flex items-center justify-between mb-3">
              <h2 class="font-display font-bold text-base">Mis Obligaciones del Llamado Activo</h2>
              <button id="refreshObligationsBtn" class="btn-ghost">Actualizar</button>
            </div>
            <div id="obligationsList" class="space-y-2.5">
              <div class="skeleton h-16"></div>
              <div class="skeleton h-16"></div>
            </div>
          </div>
        </section>

        <!-- ============ B) AGENDA Y PREVENCION DE COLISIONES ============ -->
        <section id="agenda" class="space-y-4">
          <div class="flex items-center justify-between gap-2">
            <h2 class="font-display font-bold text-lg">Agenda &mdash; Clientes Jornal</h2>
            <button id="refreshAgendaBtn" class="btn-ghost">Actualizar</button>
          </div>

          <?php if ($isManager): ?>
          <details class="rounded-2xl border border-slate-200 dark:border-turquoise/10 bg-white dark:bg-[#01243f] overflow-hidden">
            <summary class="cursor-pointer list-none px-4 sm:px-5 py-3.5 font-display font-semibold text-sm flex items-center justify-between">
              <span>&#10133; Crear nuevo llamado</span>
              <span class="text-turquoise text-xs">Tocar para expandir</span>
            </summary>
            <form id="callForm" class="px-4 sm:px-5 pb-5 pt-1 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
              <label class="field-label sm:col-span-2 lg:col-span-1">Programa
                <select name="program_id" id="callProgramId" class="field-select" required></select>
              </label>
              <label class="field-label sm:col-span-2 lg:col-span-2">Titulo del llamado
                <input type="text" name="title" class="field-input" required maxlength="150" placeholder="Ej. Grabacion Episodio 12">
              </label>
              <label class="field-label">Locacion
                <select name="location" class="field-select" required>
                  <option value="Estudio 5 de Mayo">Estudio 5 de Mayo</option>
                  <option value="Locacion Externa">Locacion Externa</option>
                  <option value="Van Terrestre">Van Terrestre</option>
                  <option value="Embarcacion Maritima">Embarcacion Maritima</option>
                </select>
              </label>
              <label class="field-label">Fecha
                <input type="date" name="call_date" class="field-input" required>
              </label>
              <label class="field-label">Hora inicio
                <input type="time" name="start_time" class="field-input" required>
              </label>
              <label class="field-label">Hora fin
                <input type="time" name="end_time" class="field-input" required>
              </label>
              <label class="field-label">Monto total (MXN)
                <input type="number" name="total_amount" class="field-input" step="0.01" min="0" placeholder="18274.06">
              </label>
              <div class="flex items-end sm:col-span-2 lg:col-span-3">
                <button type="submit" class="btn-primary w-full sm:w-auto">Crear Llamado</button>
              </div>
              <p class="mh-feedback text-sm sm:col-span-2 lg:col-span-3" id="callFormFeedback"></p>
            </form>
          </details>
          <?php endif; ?>

          <div id="agendaList" class="space-y-3">
            <div class="skeleton h-32"></div>
            <div class="skeleton h-32"></div>
          </div>
        </section>

        <!-- ============ C) CONSOLA DE CHECKLISTS DE SET ============ -->
        <section id="checklist" class="space-y-4">
          <h2 class="font-display font-bold text-lg">Checklist Operativo de Set</h2>
          <p class="text-sm text-slate-500 dark:text-digital-white/60">Protocolos normativos del Estudio 5 de Mayo &mdash; Antes, Durante y Despues del llamado.</p>

          <div class="rounded-2xl border border-slate-200 dark:border-turquoise/10 bg-white dark:bg-[#01243f] p-4 sm:p-5 space-y-4">
            <label class="field-label">Selecciona un llamado
              <select id="checklistCallSelect" class="field-select">
                <option value="">Cargando llamados...</option>
              </select>
            </label>

            <div id="checklistTabs" class="hidden">
              <div class="flex gap-2 mb-4 overflow-x-auto pb-1">
                <button type="button" class="tab-btn active" data-tab="Antes">&#127959;&#65039; Antes (Montaje)</button>
                <button type="button" class="tab-btn" data-tab="Durante">&#128227; Durante (Live)</button>
                <button type="button" class="tab-btn" data-tab="Despues">&#128230; Despues (Desmontaje)</button>
              </div>

              <!-- ANTES -->
              <div data-tab-panel="Antes" class="space-y-4">
                <div id="checklistAntes" class="divide-y divide-slate-100 dark:divide-white/5"></div>
                <div class="rounded-xl border border-amber-400/30 bg-amber-400/5 p-3.5">
                  <p class="text-xs font-display font-bold uppercase tracking-wide text-amber-600 dark:text-amber-400 mb-2">&#128268; Check-Out digital de hardware</p>
                  <div id="checkoutList" class="space-y-2"></div>
                </div>
              </div>

              <!-- DURANTE -->
              <div data-tab-panel="Durante" class="space-y-4 hidden">
                <div id="checklistDurante" class="divide-y divide-slate-100 dark:divide-white/5"></div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div class="rounded-xl border border-turquoise/30 bg-turquoise/5 p-3.5 text-sm">
                    <p class="font-display font-bold mb-1">&#9992;&#65039; Modo avion obligatorio</p>
                    <p class="text-slate-500 dark:text-digital-white/60">Todo el personal en set debe colocar su telefono en modo avion durante la transmision en vivo.</p>
                  </div>
                  <div class="rounded-xl border border-red-400/30 bg-red-400/5 p-3.5 text-sm">
                    <p class="font-display font-bold mb-1 text-red-500">&#128679; Prohibicion de liquidos</p>
                    <p class="text-slate-500 dark:text-digital-white/60">Estrictamente prohibido colocar liquidos o alimentos cerca de las consolas y equipo de transmision.</p>
                  </div>
                </div>
              </div>

              <!-- DESPUES -->
              <div data-tab-panel="Despues" class="space-y-4 hidden">
                <div id="checklistDespues" class="divide-y divide-slate-100 dark:divide-white/5"></div>
                <div class="rounded-xl border border-emerald-400/30 bg-emerald-400/5 p-3.5">
                  <p class="text-xs font-display font-bold uppercase tracking-wide text-emerald-600 dark:text-emerald-400 mb-2">&#128229; Check-In digital de hardware (deslinde de responsabilidad)</p>
                  <div id="checkinList" class="space-y-2"></div>
                </div>
              </div>
            </div>

            <p id="checklistEmpty" class="text-sm text-slate-500 dark:text-digital-white/60">Selecciona un llamado para ver su checklist operativo.</p>
          </div>
        </section>

        <!-- ============ INVENTARIO Y FLOTA ============ -->
        <section id="inventario" class="space-y-4">
          <div class="flex items-center justify-between gap-2">
            <h2 class="font-display font-bold text-lg">Inventario y Flota</h2>
            <button id="refreshInventoryBtn" class="btn-ghost">Actualizar</button>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="rounded-2xl border border-slate-200 dark:border-turquoise/10 bg-white dark:bg-[#01243f] p-4 sm:p-5">
              <h3 class="font-display font-bold text-sm mb-3">Inventario (Camaras, opticas, luces, audio)</h3>
              <div class="overflow-x-auto">
                <table class="w-full text-sm">
                  <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-400 dark:text-digital-white/40">
                      <th class="py-2 pr-2">Activo</th>
                      <th class="py-2 pr-2">Categoria</th>
                      <th class="py-2">Estatus</th>
                    </tr>
                  </thead>
                  <tbody id="inventoryItemsBody">
                    <tr><td colspan="3" class="py-3"><div class="skeleton h-6"></div></td></tr>
                  </tbody>
                </table>
              </div>
            </div>

            <div class="rounded-2xl border border-slate-200 dark:border-turquoise/10 bg-white dark:bg-[#01243f] p-4 sm:p-5">
              <h3 class="font-display font-bold text-sm mb-3">Flota (Van Terrestre / Embarcacion Maritima)</h3>
              <div class="overflow-x-auto">
                <table class="w-full text-sm">
                  <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-400 dark:text-digital-white/40">
                      <th class="py-2 pr-2">Unidad</th>
                      <th class="py-2 pr-2">Tipo</th>
                      <th class="py-2 pr-2">Estatus</th>
                      <?php if ($isManager): ?><th class="py-2">Accion</th><?php endif; ?>
                    </tr>
                  </thead>
                  <tbody id="fleetBody">
                    <tr><td colspan="<?php echo $isManager ? 4 : 3; ?>" class="py-3"><div class="skeleton h-6"></div></td></tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </section>

        <?php if ($isManager): ?>
        <!-- ============ D) PANEL ADMINISTRATIVO MULTI-ROL ============ -->
        <section id="admin" class="space-y-4">
          <h2 class="font-display font-bold text-lg">Administracion &mdash; Organigrama de Staff</h2>

          <details class="rounded-2xl border border-slate-200 dark:border-turquoise/10 bg-white dark:bg-[#01243f] overflow-hidden">
            <summary class="cursor-pointer list-none px-4 sm:px-5 py-3.5 font-display font-semibold text-sm flex items-center justify-between">
              <span>&#10133; Crear nuevo usuario de staff</span>
              <span class="text-turquoise text-xs">Tocar para expandir</span>
            </summary>
            <form id="userForm" class="px-4 sm:px-5 pb-5 pt-1 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
              <label class="field-label">ID de usuario
                <input type="text" name="user_id" class="field-input" required maxlength="20" placeholder="MH-0007">
              </label>
              <label class="field-label sm:col-span-2">Nombre completo
                <input type="text" name="full_name" class="field-input" required maxlength="120" placeholder="Nombre y apellidos">
              </label>
              <label class="field-label">Correo
                <input type="email" name="email" class="field-input" required maxlength="150" placeholder="correo@mediahubbcs.com">
              </label>
              <label class="field-label">Contrasena temporal
                <input type="password" name="password" class="field-input" required minlength="8" placeholder="Minimo 8 caracteres">
              </label>
              <label class="field-label">Rol
                <select name="role" class="field-select" required>
                  <option value="Staff_Tecnico">Staff Tecnico</option>
                  <option value="Lider_Proyecto">Lider de Proyecto</option>
                  <option value="Lider_Logistica">Lider de Logistica</option>
                  <option value="Administrador">Administrador</option>
                  <option value="Cliente">Cliente</option>
                </select>
              </label>
              <div class="flex items-end sm:col-span-2 lg:col-span-3">
                <button type="submit" class="btn-primary w-full sm:w-auto">Registrar Usuario</button>
              </div>
              <p class="mh-feedback text-sm sm:col-span-2 lg:col-span-3" id="userFormFeedback"></p>
            </form>
          </details>

          <div class="rounded-2xl border border-slate-200 dark:border-turquoise/10 bg-white dark:bg-[#01243f] p-4 sm:p-5">
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead>
                  <tr class="text-left text-xs uppercase tracking-wide text-slate-400 dark:text-digital-white/40">
                    <th class="py-2 pr-2">ID</th>
                    <th class="py-2 pr-2">Nombre</th>
                    <th class="py-2 pr-2">Correo</th>
                    <th class="py-2 pr-2">Rol</th>
                    <th class="py-2 pr-2">Estatus</th>
                    <th class="py-2">Acciones</th>
                  </tr>
                </thead>
                <tbody id="usersBody">
                  <tr><td colspan="6" class="py-3"><div class="skeleton h-6"></div></td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </section>
        <?php endif; ?>

      </main>
    </div>
  </div>

  <!-- ============ TOGGLE DE TEMA FLOTANTE ============ -->
  <button id="themeToggle" type="button" aria-label="Cambiar tema" class="fixed bottom-5 right-5 z-50 grid place-items-center w-12 h-12 rounded-full bg-deep-sea dark:bg-turquoise text-turquoise dark:text-deep-sea shadow-lg shadow-black/20 border border-turquoise/40 hover:scale-105 active:scale-95 transition-transform">
    <svg id="iconSun" class="hidden w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1.5m0 15V21m9-9h-1.5M4.5 12H3m15.364-6.364-1.06 1.06M6.697 17.303l-1.06 1.06m12.727 0-1.06-1.06M6.697 6.697 5.636 5.636M16.5 12a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z"/></svg>
    <svg id="iconMoon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z"/></svg>
  </button>

  <!-- ============ BOTON FLOTANTE "VOLVER ARRIBA" ============ -->
  <button id="backToTop" type="button" aria-label="Volver arriba" class="fixed bottom-20 right-5 z-50 grid place-items-center w-12 h-12 rounded-full bg-turquoise text-deep-sea shadow-lg shadow-black/20 opacity-0 translate-y-3 pointer-events-none transition-all duration-300 hover:scale-105 active:scale-95">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5"/></svg>
  </button>

  <script>
    window.MH_CSRF       = <?php echo json_encode($csrfToken); ?>;
    window.MH_ROLE       = <?php echo json_encode($user['role']); ?>;
    window.MH_USER_ID    = <?php echo json_encode($user['user_id']); ?>;
    window.MH_IS_MANAGER = <?php echo $isManager ? 'true' : 'false'; ?>;
    window.MH_IS_ADMIN   = <?php echo $isAdmin ? 'true' : 'false'; ?>;
  </script>
  <script src="../assets/js/dashboard.js" defer></script>
</body>
</html>
