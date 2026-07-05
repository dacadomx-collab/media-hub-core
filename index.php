<?php
session_start();
require_once __DIR__ . '/api/csrf.php';

$csrfToken = csrf_token();

$loginErrors = [
    'csrf'        => 'Tu sesion expiro. Intenta nuevamente.',
    'invalid'     => 'Revisa el formato de tu correo y clave.',
    'credentials' => 'Correo o clave incorrectos.',
    'suspended'   => 'Esta cuenta esta suspendida. Contacta al Administrador.',
    'server'      => 'Error temporal del servidor. Intenta de nuevo.',
];
$loginError = $loginErrors[$_GET['error'] ?? ''] ?? '';

$loginInfos = [
    'reset_sent' => 'Si el correo existe en nuestro sistema, recibiras un enlace de recuperacion en breve.',
];
$loginInfo = $loginInfos[$_GET['info'] ?? ''] ?? '';
?>
<!DOCTYPE html>
<html lang="es" class="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Media HUB | Estudio 5 de Mayo - La Paz, BCS</title>
<meta name="description" content="Media HUB: terminal inteligente de produccion audiovisual en La Paz, BCS. Estudio 5 de Mayo, unidades moviles y transmision Simulcast en vivo.">
<link rel="icon" type="image/png" href="assets/img/logo.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
<!--
  PRODUCCION: Tailwind via Play CDN (cdn.tailwindcss.com).
  Migracion futura: compilar con Tailwind CLI a assets/css/tailwind.min.css
  (paso de build en deploy.yml) sin alterar las clases utilitarias actuales.
  Mientras tanto se filtra unicamente la advertencia conocida de consola
  "cdn.tailwindcss.com should not be used in production" para mantener
  limpio el diagnostico forense, sin afectar el renderizado mobile-first.
-->
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
        },
        fontFamily: {
          montserrat: ['Montserrat', 'sans-serif'],
          roboto: ['Roboto', 'sans-serif'],
        },
      },
    },
  };
</script>
<link rel="stylesheet" href="assets/css/login-widget.css">
<style>
  html { scroll-behavior: smooth; }
  body { font-family: 'Roboto', sans-serif; }
  h1, h2, h3, .font-display { font-family: 'Montserrat', sans-serif; }
</style>
</head>
<body class="bg-white text-deep-sea dark:bg-deep-sea dark:text-digital-white transition-colors duration-300">

  <!-- ============ NAVBAR ============ -->
  <header class="sticky top-0 z-50 backdrop-blur-md bg-white/80 dark:bg-deep-sea/80 border-b border-turquoise/20">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between h-16">
      <a href="#inicio" class="flex items-center gap-2 font-display font-extrabold text-lg tracking-wide">
        <img src="assets/img/logo.png" alt="Media HUB Logo" class="h-16 md:h-20 w-auto object-contain mx-auto md:mx-0">
        <span>MEDIA <span class="text-turquoise">HUB</span></span>
      </a>

      <nav class="hidden md:flex items-center gap-6 text-sm font-medium">
        <a href="#estudio" class="hover:text-turquoise transition-colors">Estudio</a>
        <a href="#simulcast" class="hover:text-turquoise transition-colors">Simulcast</a>
        <a href="#programas" class="hover:text-turquoise transition-colors">Programas</a>
        <a href="#clientes" class="hover:text-turquoise transition-colors">Clientes Jornal</a>
        <a href="#contacto" class="hover:text-turquoise transition-colors">Contacto</a>
      </nav>

      <div class="flex items-center gap-2 sm:gap-3">
        <button id="themeToggle" type="button" aria-label="Cambiar tema" class="grid place-items-center w-9 h-9 rounded-full border border-turquoise/40 hover:border-turquoise transition-colors">
          <svg id="iconSun" class="hidden w-4 h-4 text-turquoise" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1.5m0 15V21m9-9h-1.5M4.5 12H3m15.364-6.364-1.06 1.06M6.697 17.303l-1.06 1.06m12.727 0-1.06-1.06M6.697 6.697 5.636 5.636M16.5 12a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z"/></svg>
          <svg id="iconMoon" class="w-4 h-4 text-deep-sea dark:text-turquoise" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z"/></svg>
        </button>

        <button type="button" data-open-login class="inline-flex items-center px-3.5 sm:px-4 py-2 rounded-full bg-turquoise text-deep-sea font-display font-bold text-xs sm:text-sm hover:brightness-110 transition">
          <span class="hidden sm:inline">Portal Staff</span>
          <span class="sm:hidden">Ingresar</span>
        </button>

        <button id="navToggle" type="button" aria-label="Abrir menu" aria-expanded="false" class="md:hidden grid place-items-center w-9 h-9 rounded-full border border-turquoise/40 hover:border-turquoise transition-colors">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5"/></svg>
        </button>
      </div>
    </div>

    <!-- Menu movil colapsable -->
    <nav id="mobileNav" class="hidden md:hidden border-t border-turquoise/15 bg-white/95 dark:bg-deep-sea/95">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 py-3 flex flex-col gap-3 text-sm font-medium">
        <a href="#estudio" class="hover:text-turquoise transition-colors">Estudio</a>
        <a href="#simulcast" class="hover:text-turquoise transition-colors">Simulcast</a>
        <a href="#programas" class="hover:text-turquoise transition-colors">Programas</a>
        <a href="#clientes" class="hover:text-turquoise transition-colors">Clientes Jornal</a>
        <a href="#contacto" class="hover:text-turquoise transition-colors">Contacto</a>
      </div>
    </nav>
  </header>

  <!-- ============ HERO ============ -->
  <section id="inicio" class="relative overflow-hidden">
    <div class="absolute inset-0 -z-10 bg-gradient-to-br from-deep-sea via-[#01355f] to-[#011c35] opacity-0 dark:opacity-100 transition-opacity"></div>
    <div class="absolute inset-0 -z-10 bg-gradient-to-br from-white via-[#eaf7f6] to-white opacity-100 dark:opacity-0 transition-opacity"></div>
    <div class="absolute inset-0 -z-10 [mask-image:radial-gradient(circle_at_center,black_25%,transparent_80%)] opacity-30">
      <div class="absolute inset-0 bg-[linear-gradient(rgba(0,191,178,0.15)_1px,transparent_1px),linear-gradient(90deg,rgba(0,191,178,0.15)_1px,transparent_1px)] bg-[size:42px_42px]"></div>
    </div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pt-16 pb-20 sm:pt-24 sm:pb-28 text-center">
      <span class="inline-block mb-4 px-4 py-1 rounded-full text-xs font-semibold tracking-widest uppercase bg-turquoise/10 text-turquoise border border-turquoise/30">
        La Paz, Baja California Sur
      </span>
      <h1 class="font-display font-extrabold text-4xl sm:text-5xl lg:text-6xl leading-tight mb-5">
        Tu produccion, <span class="text-turquoise">sin caos.</span><br class="hidden sm:block">
        El centro de mando audiovisual de BCS.
      </h1>
      <p class="max-w-2xl mx-auto text-base sm:text-lg text-deep-sea/70 dark:text-digital-white/70 mb-8">
        Media HUB coordina tu estudio, tu staff y tus unidades moviles &mdash;terrestres y maritimas&mdash; desde una sola plataforma. Agenda inteligente, inventario en tiempo real y transmision en vivo lista para tu marca.
      </p>
      <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
        <a href="#contacto" class="w-full sm:w-auto inline-flex justify-center items-center px-7 py-3.5 rounded-full bg-turquoise text-deep-sea font-display font-bold shadow-lg shadow-turquoise/30 hover:brightness-110 transition">
          Reserva tu Llamado
        </a>
        <a href="#estudio" class="w-full sm:w-auto inline-flex justify-center items-center px-7 py-3.5 rounded-full border border-turquoise/40 font-display font-semibold hover:border-turquoise hover:text-turquoise transition">
          Conoce el Estudio
        </a>
      </div>
    </div>
  </section>

  <!-- ============ ESTUDIO / INFRAESTRUCTURA ============ -->
  <section id="estudio" class="py-16 sm:py-24 bg-[#f4fbfb] dark:bg-[#031f3c]">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center max-w-2xl mx-auto mb-12">
        <span class="text-turquoise font-display font-bold text-sm uppercase tracking-widest">Infraestructura</span>
        <h2 class="font-display font-extrabold text-3xl sm:text-4xl mt-2 mb-4">Estudio 5 de Mayo</h2>
        <p class="text-deep-sea/70 dark:text-digital-white/70">
          Equipamiento profesional listo para entrevistas, paneles y produccion multicamara, respaldado por una flota movil para cobertura en cualquier punto de BCS.
        </p>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="rounded-2xl p-6 bg-white dark:bg-deep-sea/60 border border-turquoise/15 shadow-sm">
          <div class="w-11 h-11 rounded-xl bg-turquoise/10 grid place-items-center mb-4 text-turquoise text-xl">📹</div>
          <h3 class="font-display font-bold text-lg mb-2">Multicamara HD</h3>
          <p class="text-sm text-deep-sea/60 dark:text-digital-white/60">Camaras y opticas profesionales para set principal y secundario.</p>
        </div>
        <div class="rounded-2xl p-6 bg-white dark:bg-deep-sea/60 border border-turquoise/15 shadow-sm">
          <div class="w-11 h-11 rounded-xl bg-turquoise/10 grid place-items-center mb-4 text-turquoise text-xl">💡</div>
          <h3 class="font-display font-bold text-lg mb-2">Iluminacion LED Fria</h3>
          <p class="text-sm text-deep-sea/60 dark:text-digital-white/60">Paneles LED de bajo consumo termico para sesiones prolongadas.</p>
        </div>
        <div class="rounded-2xl p-6 bg-white dark:bg-deep-sea/60 border border-turquoise/15 shadow-sm">
          <div class="w-11 h-11 rounded-xl bg-turquoise/10 grid place-items-center mb-4 text-turquoise text-xl">🚐</div>
          <h3 class="font-display font-bold text-lg mb-2">Van de Produccion</h3>
          <p class="text-sm text-deep-sea/60 dark:text-digital-white/60">Unidad terrestre para cobertura externa en toda La Paz y alrededores.</p>
        </div>
        <div class="rounded-2xl p-6 bg-white dark:bg-deep-sea/60 border border-turquoise/15 shadow-sm">
          <div class="w-11 h-11 rounded-xl bg-turquoise/10 grid place-items-center mb-4 text-turquoise text-xl">⛵</div>
          <h3 class="font-display font-bold text-lg mb-2">Embarcacion Maritima</h3>
          <p class="text-sm text-deep-sea/60 dark:text-digital-white/60">Cobertura unica en el Mar de Cortes para producciones especiales.</p>
        </div>
      </div>

      <!-- Galeria del set: arsenal tecnologico (mobile-first, 1 col -> 2 col en md+) -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mt-8">
        <div class="rounded-2xl overflow-hidden border border-turquoise/15 shadow-sm aspect-video">
          <img src="assets/img/estudio-5-de-mayo.jpg" alt="Set principal del Estudio 5 de Mayo con iluminacion LED, multicamara y panel de entrevistas" class="w-full h-full object-cover" loading="lazy" width="1600" height="900">
        </div>
        <div class="rounded-2xl overflow-hidden border border-turquoise/15 shadow-sm aspect-video">
          <img src="assets/img/estudio-5-de-mayo-2.jpg" alt="Vista panoramica del croma verde y la mesa de panel del Estudio 5 de Mayo" class="w-full h-full object-cover" loading="lazy" width="1600" height="900">
        </div>
      </div>
    </div>
  </section>

  <!-- ============ SIMULCAST ============ -->
  <section id="simulcast" class="py-16 sm:py-24">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 grid lg:grid-cols-2 gap-10 items-center">
      <div>
        <span class="text-turquoise font-display font-bold text-sm uppercase tracking-widest">Transmision en vivo</span>
        <h2 class="font-display font-extrabold text-3xl sm:text-4xl mt-2 mb-4">
          Simulcast: tu programa en Facebook y YouTube, al mismo tiempo.
        </h2>
        <p class="text-deep-sea/70 dark:text-digital-white/70 mb-6">
          Nuestra infraestructura tecnologica transmite tu produccion en vivo de forma simultanea hacia Facebook y YouTube, ampliando tu audiencia sin duplicar esfuerzos ni equipo. Ideal para programas recurrentes, entrevistas y eventos institucionales.
        </p>
        <ul class="space-y-3 text-sm sm:text-base">
          <li class="flex items-start gap-3">
            <span class="mt-0.5 text-turquoise">✔</span>
            <span>Transmision simultanea a multiples plataformas sin perdida de calidad.</span>
          </li>
          <li class="flex items-start gap-3">
            <span class="mt-0.5 text-turquoise">✔</span>
            <span>Switching multicamara en vivo desde el Estudio 5 de Mayo.</span>
          </li>
          <li class="flex items-start gap-3">
            <span class="mt-0.5 text-turquoise">✔</span>
            <span>Pruebas de continuidad de audio previas a cada salida al aire.</span>
          </li>
        </ul>
      </div>
      <div class="relative rounded-3xl overflow-hidden border border-turquoise/20 shadow-xl min-h-[320px]">
        <img src="assets/img/simulcast-streaming-live.jpg" alt="Equipo de produccion de Media HUB preparando una transmision en vivo en el set" class="absolute inset-0 w-full h-full object-cover" loading="lazy" width="1600" height="1200">
        <div class="absolute inset-0 bg-deep-sea/85 dark:bg-[#031f3c]/90"></div>
        <div class="relative p-8">
          <div class="flex items-center gap-3 mb-6">
            <span class="w-3 h-3 rounded-full bg-red-500 animate-pulse"></span>
            <span class="text-digital-white font-display font-bold tracking-widest text-sm">EN VIVO</span>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div class="rounded-xl bg-white/5 border border-turquoise/20 p-4 text-center">
              <p class="text-digital-white font-display font-bold">Facebook</p>
              <p class="text-turquoise text-xs mt-1">Transmitiendo</p>
            </div>
            <div class="rounded-xl bg-white/5 border border-turquoise/20 p-4 text-center">
              <p class="text-digital-white font-display font-bold">YouTube</p>
              <p class="text-turquoise text-xs mt-1">Transmitiendo</p>
            </div>
          </div>
          <p class="text-digital-white/50 text-xs mt-6 text-center">Estudio 5 de Mayo &mdash; Media HUB Control Room</p>
        </div>
      </div>
    </div>
  </section>

  <!-- ============ PROGRAMAS NATIVOS (ARF-GRID) ============ -->
  <section id="programas" class="py-16 sm:py-24">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center max-w-2xl mx-auto mb-12">
        <span class="text-turquoise font-display font-bold text-sm uppercase tracking-widest">Contenido propio</span>
        <h2 class="font-display font-extrabold text-3xl sm:text-4xl mt-2 mb-4">Nuestros Programas</h2>
        <p class="text-deep-sea/70 dark:text-digital-white/70">
          Shows nativos de Media HUB, producidos en el Estudio 5 de Mayo con conduccion propia.
        </p>
      </div>

      <!-- Contenedor padre ARF-Grid: flex + flex-wrap + justify-center, sin anchos fijos en px -->
      <div id="programasGrid" class="flex flex-wrap justify-center gap-6" aria-live="polite">
        <p id="programasLoading" class="text-sm text-deep-sea/50 dark:text-digital-white/50">Cargando programas&hellip;</p>
      </div>

      <!-- ============ HIGHLIGHTS (placeholder responsivo para clips editados) ============ -->
      <div class="mt-16">
        <h3 class="font-display font-bold text-xl mb-6 text-center">Highlights</h3>
        <div class="flex flex-wrap justify-center gap-4">
          <div class="w-full sm:basis-[47%] lg:basis-[23%] max-w-sm aspect-[9/16] rounded-2xl border border-dashed border-turquoise/30 bg-[#f4fbfb] dark:bg-[#031f3c] grid place-items-center text-xs text-deep-sea/40 dark:text-digital-white/40">
            Proximamente
          </div>
          <div class="w-full sm:basis-[47%] lg:basis-[23%] max-w-sm aspect-[9/16] rounded-2xl border border-dashed border-turquoise/30 bg-[#f4fbfb] dark:bg-[#031f3c] grid place-items-center text-xs text-deep-sea/40 dark:text-digital-white/40">
            Proximamente
          </div>
          <div class="w-full sm:basis-[47%] lg:basis-[23%] max-w-sm aspect-[9/16] rounded-2xl border border-dashed border-turquoise/30 bg-[#f4fbfb] dark:bg-[#031f3c] grid place-items-center text-xs text-deep-sea/40 dark:text-digital-white/40">
            Proximamente
          </div>
          <div class="w-full sm:basis-[47%] lg:basis-[23%] max-w-sm aspect-[9/16] rounded-2xl border border-dashed border-turquoise/30 bg-[#f4fbfb] dark:bg-[#031f3c] grid place-items-center text-xs text-deep-sea/40 dark:text-digital-white/40">
            Proximamente
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ============ CLIENTES JORNAL / TESTIMONIALES ============ -->
  <section id="clientes" class="py-16 sm:py-24 bg-[#f4fbfb] dark:bg-[#031f3c]">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center max-w-2xl mx-auto mb-12">
        <span class="text-turquoise font-display font-bold text-sm uppercase tracking-widest">Casos de Exito</span>
        <h2 class="font-display font-extrabold text-3xl sm:text-4xl mt-2 mb-4">Clientes Jornal activos</h2>
        <p class="text-deep-sea/70 dark:text-digital-white/70">
          Programas recurrentes que crecen gracias a la coordinacion y produccion continua de Media HUB.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <article class="rounded-2xl p-7 bg-white dark:bg-deep-sea/60 border border-turquoise/15 shadow-sm">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-12 h-12 rounded-full bg-turquoise/15 grid place-items-center text-turquoise font-display font-bold text-lg">MS</div>
            <div>
              <h3 class="font-display font-bold">Medicina del Siglo XXI</h3>
              <p class="text-xs text-deep-sea/50 dark:text-digital-white/50">Dr. Efrain Torres</p>
            </div>
          </div>
          <p class="text-sm text-deep-sea/70 dark:text-digital-white/70 mb-4">
            "Gracias a la coordinacion de Media HUB, cada entrevista con especialistas se produce sin contratiempos: agenda confirmada, equipo listo y transmision impecable cada semana."
          </p>
          <span class="inline-block text-xs font-semibold px-3 py-1 rounded-full bg-turquoise/10 text-turquoise">Entrevistas a especialistas</span>
        </article>

        <article class="rounded-2xl p-7 bg-white dark:bg-deep-sea/60 border border-turquoise/15 shadow-sm">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-12 h-12 rounded-full bg-turquoise/15 grid place-items-center text-turquoise font-display font-bold text-lg">CC</div>
            <div>
              <h3 class="font-display font-bold">CCBCS</h3>
              <p class="text-xs text-deep-sea/50 dark:text-digital-white/50">Efrain Torres</p>
            </div>
          </div>
          <p class="text-sm text-deep-sea/70 dark:text-digital-white/70 mb-4">
            "Nuestro programa institucional mantiene una presencia constante en redes gracias al Simulcast de Media HUB hacia Facebook y YouTube, con produccion profesional en cada emision."
          </p>
          <span class="inline-block text-xs font-semibold px-3 py-1 rounded-full bg-turquoise/10 text-turquoise">Programa institucional recurrente</span>
        </article>
      </div>
    </div>
  </section>

  <!-- ============ CONTACTO / CTA FINAL ============ -->
  <section id="contacto" class="py-16 sm:py-24">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
      <h2 class="font-display font-extrabold text-3xl sm:text-4xl mb-4">Lleva tu produccion a Media HUB</h2>
      <p class="text-deep-sea/70 dark:text-digital-white/70 mb-8 max-w-xl mx-auto">
        Josefa Ortiz de Dominguez &amp; Calle 5 de Mayo, Zona Central, 23000 La Paz, B.C.S. &mdash; WhatsApp (612) 123-4567
      </p>
      <a href="https://wa.me/526121234567" target="_blank" rel="noopener" class="inline-flex items-center px-8 py-3.5 rounded-full bg-turquoise text-deep-sea font-display font-bold shadow-lg shadow-turquoise/30 hover:brightness-110 transition">
        Escribenos por WhatsApp
      </a>
    </div>
  </section>

  <!-- ============ FOOTER ============ -->
  <footer class="border-t border-turquoise/15 py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-deep-sea/50 dark:text-digital-white/50">
      <p>&copy; <span id="year"></span> Media HUB Audiovisual Studio. La Paz, BCS.</p>
      <div class="flex gap-4">
        <a href="https://www.facebook.com/mediahubBCS" target="_blank" rel="noopener" class="hover:text-turquoise transition-colors">Facebook</a>
        <a href="https://www.instagram.com/mediahubBCS" target="_blank" rel="noopener" class="hover:text-turquoise transition-colors">Instagram</a>
        <button type="button" data-open-login class="hover:text-turquoise transition-colors">Portal Staff</button>
      </div>
    </div>
  </footer>

  <!-- ============ MODAL: PORTAL STAFF (LOGIN) ============ -->
  <div id="loginModal" class="login-modal-overlay hidden" role="dialog" aria-modal="true" aria-labelledby="login-title">
    <div class="login-modal-center">
      <div class="relative w-full" style="max-width: 460px;">
        <button type="button" id="closeLoginBtn" class="login-modal-close" aria-label="Cerrar Portal Staff">&times;</button>

        <section class="login-shell" role="region" aria-label="Panel de acceso Media HUB">
          <div class="brand-block">
            <div class="brand-mark" aria-hidden="true">
              <img src="assets/img/logo.png" alt="Isotipo Media HUB" loading="eager" decoding="async" class="h-16 md:h-20 w-auto object-contain mx-auto md:mx-0">
            </div>
            <p class="brand-label">MEDIA HUB</p>
          </div>

          <header class="panel-header">
            <h1 id="login-title">Command Access</h1>
            <p>Autentica tu identidad para ingresar al núcleo digital.</p>
          </header>

          <form class="login-form" id="loginForm" method="post" action="api/login.php" novalidate>
            <input type="hidden" name="csrf_token" id="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

            <label for="email">Correo corporativo o ID de usuario</label>
            <div class="field-wrap">
              <input type="text" id="email" name="email" autocomplete="username" placeholder="usuario@mediahub.com o admin.glage" required>
              <span class="field-wave" aria-hidden="true"></span>
            </div>
            <small class="field-message" id="emailMessage" aria-live="polite"></small>

            <label for="password">Clave de acceso</label>
            <div class="field-wrap">
              <input type="password" id="password" name="password" autocomplete="current-password" placeholder="••••••••••" minlength="8" required>
              <span class="field-wave" aria-hidden="true"></span>
            </div>
            <small class="field-message" id="passwordMessage" aria-live="polite"></small>

            <label class="login-remember" for="remember_me">
              <input type="checkbox" id="remember_me" name="remember_me" value="1">
              <span>Mantener sesion iniciada por 60 dias</span>
            </label>

            <button type="submit" id="submitBtn">Ingresar al HUB</button>
            <p class="system-message<?php echo $loginError !== '' ? ' error' : ''; ?>" id="systemMessage" aria-live="polite"><?php echo htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php if ($loginInfo !== ''): ?>
            <p class="system-message ok"><?php echo htmlspecialchars($loginInfo, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
          </form>

          <button type="button" id="openForgotBtn" style="margin-top: 0.6rem; background: none; border: none; color: var(--text-soft); font-size: 0.82rem; text-decoration: underline; cursor: pointer; text-align: center; width: 100%;">
            ¿Olvidaste tu contrasena?
          </button>
        </section>
      </div>
    </div>
  </div>

  <!-- ============ MODAL: RECUPERAR CONTRASENA ============ -->
  <div id="forgotModal" class="login-modal-overlay hidden" role="dialog" aria-modal="true" aria-labelledby="forgot-title">
    <div class="login-modal-center">
      <div class="relative w-full" style="max-width: 460px;">
        <button type="button" id="closeForgotBtn" class="login-modal-close" aria-label="Cerrar recuperacion de contrasena">&times;</button>

        <section class="login-shell" role="region" aria-label="Recuperar contrasena Media HUB">
          <div class="brand-block">
            <div class="brand-mark" aria-hidden="true">
              <img src="assets/img/logo.png" alt="Isotipo Media HUB" loading="eager" decoding="async" class="h-16 md:h-20 w-auto object-contain mx-auto md:mx-0">
            </div>
            <p class="brand-label">MEDIA HUB</p>
          </div>

          <header class="panel-header">
            <h1>Recuperar Acceso</h1>
            <p>Te enviaremos un enlace para restablecer tu contrasena.</p>
          </header>

          <form class="login-form" method="post" action="api/forgot_password.php" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

            <label for="forgot_email">Correo corporativo</label>
            <div class="field-wrap">
              <input type="email" id="forgot_email" name="email" autocomplete="username" inputmode="email" placeholder="usuario@mediahub.com" required>
              <span class="field-wave" aria-hidden="true"></span>
            </div>

            <button type="submit">Enviar enlace de recuperacion</button>
          </form>
        </section>
      </div>
    </div>
  </div>

  <script src="assets/js/login.js" defer></script>
  <script src="assets/js/programas-catalog.js" defer></script>
  <script>
    document.getElementById('year').textContent = new Date().getFullYear();

    // ---- Tema claro/oscuro ----
    const root = document.documentElement;
    const themeToggle = document.getElementById('themeToggle');
    const iconSun = document.getElementById('iconSun');
    const iconMoon = document.getElementById('iconMoon');

    const applyTheme = (theme) => {
      if (theme === 'light') {
        root.classList.remove('dark');
        iconSun.classList.remove('hidden');
        iconMoon.classList.add('hidden');
      } else {
        root.classList.add('dark');
        iconSun.classList.add('hidden');
        iconMoon.classList.remove('hidden');
      }
      localStorage.setItem('mh-theme', theme);
    };

    const savedTheme = localStorage.getItem('mh-theme')
      || (window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');
    applyTheme(savedTheme);

    themeToggle.addEventListener('click', () => {
      const next = root.classList.contains('dark') ? 'light' : 'dark';
      applyTheme(next);
    });

    // ---- Menu movil ----
    const navToggle = document.getElementById('navToggle');
    const mobileNav = document.getElementById('mobileNav');
    navToggle.addEventListener('click', () => {
      const isHidden = mobileNav.classList.toggle('hidden');
      navToggle.setAttribute('aria-expanded', String(!isHidden));
    });

    // ---- Modal Portal Staff (Login) ----
    const loginModal = document.getElementById('loginModal');
    const openLoginBtns = document.querySelectorAll('[data-open-login]');
    const closeLoginBtn = document.getElementById('closeLoginBtn');

    const openLoginModal = () => {
      loginModal.classList.remove('hidden');
      mobileNav.classList.add('hidden');
      navToggle.setAttribute('aria-expanded', 'false');
    };
    const closeLoginModal = () => loginModal.classList.add('hidden');

    openLoginBtns.forEach((btn) => btn.addEventListener('click', openLoginModal));
    closeLoginBtn.addEventListener('click', closeLoginModal);
    loginModal.addEventListener('click', (event) => {
      if (event.target === loginModal) closeLoginModal();
    });
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && !loginModal.classList.contains('hidden')) closeLoginModal();
    });

    <?php if ($loginError !== '' || $loginInfo !== ''): ?>
    openLoginModal();
    <?php endif; ?>

    // ---- Auto-apertura via #login (CTA de correo "Cuenta Activada", Fase 5.1) ----
    if (window.location.hash === '#login') {
      openLoginModal();
    }

    // ---- Modal Recuperar Contrasena ----
    const forgotModal = document.getElementById('forgotModal');
    const openForgotBtn = document.getElementById('openForgotBtn');
    const closeForgotBtn = document.getElementById('closeForgotBtn');

    const openForgotModal = () => {
      closeLoginModal();
      forgotModal.classList.remove('hidden');
    };
    const closeForgotModal = () => forgotModal.classList.add('hidden');

    openForgotBtn.addEventListener('click', openForgotModal);
    closeForgotBtn.addEventListener('click', closeForgotModal);
    forgotModal.addEventListener('click', (event) => {
      if (event.target === forgotModal) closeForgotModal();
    });
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && !forgotModal.classList.contains('hidden')) closeForgotModal();
    });
  </script>
</body>
</html>
