<?php
/**
 * MH-CORE: Soporte (Fase 5.11) — pagina fisica aislada del Panel de Control.
 * Desacoplada de dashboard/index.php para no mezclar ruido de navegacion
 * con la consola operativa principal (Fase 5.11, directiva del Arquitecto).
 * Comparte sesion, sidebar, tema claro/oscuro y assets/js/dashboard.js con
 * el resto del Dashboard -- solo cambia el contenido de <main>.
 */

session_start();
require_once __DIR__ . '/../api/auth_guard.php';
require_once __DIR__ . '/../api/csrf.php';

$user      = mh_require_auth();
$csrfToken = csrf_token();

$roleLabels = [
    'Super_admin'      => 'Super Admin',
    'Admin'            => 'Administrador',
    'Lider_Proyecto'   => 'Lider de Proyecto',
    'Staff_Tecnico'    => 'Staff Tecnico',
    'Lider_Logistica'  => 'Lider de Logistica',
    'Cliente'          => 'Cliente',
    'Team'             => 'Equipo Interno',
    'Conductor'        => 'Conductor',
];

$roleLabel    = $roleLabels[$user['role']] ?? $user['role'];
$isManager    = in_array($user['role'], ['Super_admin', 'Admin', 'Lider_Proyecto'], true);
$isAdmin      = in_array($user['role'], ['Super_admin', 'Admin'], true);
$isSuperAdmin = $user['role'] === 'Super_admin';
$isConductor  = $user['role'] === 'Conductor';
?>
<!DOCTYPE html>
<html lang="es" class="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Media HUB | Soporte</title>
<link rel="icon" type="image/png" href="../assets/img/logo.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
<script>
  (function () {
    var originalWarn = console.warn;
    console.warn = function () {
      if (typeof arguments[0] === 'string' && arguments[0].indexOf('cdn.tailwindcss.com should not be used in production') !== -1) {
        return;
      }
      originalWarn.apply(console, arguments);
    };
  })();
</script>
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

  .btn-primary {
    background: #00BFB2; color: #022D53; font-weight: 700;
    border-radius: 999px; padding: .55rem 1.2rem; font-size: .85rem;
    font-family: 'Montserrat', sans-serif; transition: filter .15s ease;
    border: none; cursor: pointer;
  }
  .btn-primary:hover { filter: brightness(1.08); }
  .btn-primary:disabled { opacity: .6; cursor: not-allowed; }
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
  .nav-link { transition: color .15s ease, background-color .15s ease; }
  .nav-link.active { background: rgba(0,191,178,.12); color: #00BFB2; }
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
        <a href="index.php#inicio" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-turquoise/10">
          <span class="text-base">&#127968;</span> Inicio
        </a>

        <?php if ($isConductor): ?>
        <a href="index.php#mi-programa" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-turquoise/10">
          <span class="text-base">&#128250;</span> Mi Programa
        </a>
        <a href="index.php#mis-invitados" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-turquoise/10">
          <span class="text-base">&#128101;</span> Mis Invitados
        </a>
        <a href="support.php" class="nav-link active flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-turquoise/10">
          <span class="text-base">&#128233;</span> Soporte
        </a>
        <?php else: ?>
        <a href="index.php#agenda" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-turquoise/10">
          <span class="text-base">&#128197;</span> Agenda
        </a>
        <a href="index.php#checklist" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-turquoise/10">
          <span class="text-base">&#9989;</span> Checklist de Set
        </a>
        <a href="index.php#inventario" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-turquoise/10">
          <span class="text-base">&#128230;</span> Inventario y Flota
        </a>
        <a href="support.php" class="nav-link active flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-turquoise/10">
          <span class="text-base">&#128233;</span> Soporte
        </a>
        <?php endif; ?>

        <?php if ($isManager): ?>
        <a href="index.php#admin" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-turquoise/10">
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
              <p class="font-display font-extrabold text-sm sm:text-base truncate">Soporte</p>
              <p class="text-xs text-slate-500 dark:text-digital-white/50 truncate">Media HUB &mdash; Estudio 5 de Mayo</p>
            </div>
          </div>
          <span class="hidden sm:inline-block badge bg-turquoise/10 text-turquoise"><?php echo htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
      </header>

      <main class="flex-1 px-4 sm:px-6 py-6 space-y-8 pb-28 max-w-3xl w-full mx-auto">

        <section id="soporte" class="space-y-4">
          <div>
            <h1 class="font-display font-bold text-lg">Soporte</h1>
            <p class="text-sm text-slate-500 dark:text-digital-white/60 mt-1">
              Escribe tu duda, sugerencia o reporte tecnico. Tu mensaje llega directo a la administracion de Media HUB.
            </p>
          </div>
          <div class="rounded-2xl border border-slate-200 dark:border-turquoise/10 bg-white dark:bg-[#01243f] p-4 sm:p-5">
            <form id="supportForm" class="space-y-3">
              <label class="field-label">Tu mensaje
                <textarea name="message" class="field-input" rows="5" maxlength="4000" placeholder="Cuentanos que sucede..." required></textarea>
              </label>
              <button type="submit" class="btn-primary">Enviar a Media HUB</button>
              <p class="mh-feedback text-sm" id="supportFeedback"></p>
            </form>
          </div>
        </section>

      </main>
    </div>
  </div>

  <!-- ============ TOGGLE DE TEMA FLOTANTE ============ -->
  <button id="themeToggle" type="button" aria-label="Cambiar tema" class="fixed bottom-5 right-5 z-50 grid place-items-center w-12 h-12 rounded-full bg-deep-sea dark:bg-turquoise text-turquoise dark:text-deep-sea shadow-lg shadow-black/20 border border-turquoise/40 hover:scale-105 active:scale-95 transition-transform">
    <svg id="iconSun" class="hidden w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1.5m0 15V21m9-9h-1.5M4.5 12H3m15.364-6.364-1.06 1.06M6.697 17.303l-1.06 1.06m12.727 0-1.06-1.06M6.697 6.697 5.636 5.636M16.5 12a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z"/></svg>
    <svg id="iconMoon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z"/></svg>
  </button>

  <script>
    window.MH_CSRF       = <?php echo json_encode($csrfToken); ?>;
    window.MH_ROLE       = <?php echo json_encode($user['role']); ?>;
    window.MH_USER_ID    = <?php echo json_encode($user['user_id']); ?>;
    window.MH_IS_MANAGER     = <?php echo $isManager ? 'true' : 'false'; ?>;
    window.MH_IS_ADMIN       = <?php echo $isAdmin ? 'true' : 'false'; ?>;
    window.MH_IS_SUPER_ADMIN = <?php echo $isSuperAdmin ? 'true' : 'false'; ?>;
    window.MH_IS_CONDUCTOR   = <?php echo $isConductor ? 'true' : 'false'; ?>;
  </script>
  <script src="../assets/js/dashboard.js" defer></script>
</body>
</html>
