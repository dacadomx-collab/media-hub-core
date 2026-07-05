<?php
/**
 * MH-CORE: Onboarding publico de Invitados (Guest Onboarding).
 * Vista publica, SIN sesion de usuario -- la unica credencial es el
 * token en la URL. Toda la logica de clics/TTL y persistencia vive en
 * api/guest_submissions.php (esta vista es un shell ligero que consume
 * ese endpoint via fetch en assets/js/guest-form.js).
 *
 * Fase 5.8 — Light Mode: esta pagina NO se presenta como "Media HUB" al
 * invitado. Se presenta como el show del cliente/Conductor que lo invito
 * (nombre, canal afiliado, tema del episodio) -- Media HUB queda como
 * credito discreto en el pie de pagina.
 *
 * Ver knowledge/03_CONTRATOS_API_Y_RUTAS.md Contrato 10.
 */

$token = trim((string) ($_GET['token'] ?? ''));
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Invitacion a grabacion | Media HUB</title>
<meta name="robots" content="noindex, nofollow">
<link rel="icon" type="image/png" href="assets/img/logo.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/guest-form.css">
</head>
<body class="gf-body">

  <main class="gf-shell">

    <!-- ============ ESTADO: CARGANDO ============ -->
    <section id="gfLoading" class="gf-card gf-state">
      <p class="gf-loading-text">Cargando invitacion&hellip;</p>
    </section>

    <!-- ============ ESTADO: ENLACE INVALIDO / EXPIRADO ============ -->
    <section id="gfExpired" class="gf-card gf-state hidden">
      <h1 class="gf-title">Este enlace ya no esta disponible</h1>
      <p class="gf-text" id="gfExpiredMessage">El enlace de invitacion expiro o no es valido.</p>
      <a href="index.php" class="gf-btn gf-btn-primary">Ir a la pagina de Media HUB</a>
    </section>

    <!-- ============ FORMULARIO PRINCIPAL ============ -->
    <section id="gfForm" class="gf-card hidden">

      <!-- Cabecera dinamica del show (marca del CLIENTE, no de Media HUB) -->
      <header class="gf-header">
        <img id="gfLogo" src="assets/img/logo.png" alt="" class="gf-logo hidden">
        <div>
          <span class="gf-eyebrow" id="gfChannelBadge">Invitacion de produccion</span>
          <h1 id="gfProgramName" class="gf-title">&nbsp;</h1>
          <p id="gfProgramDescription" class="gf-text"></p>
        </div>
      </header>

      <!-- Mensaje de invitacion personalizado del Conductor -->
      <p class="gf-invite-line" id="gfInviteLine"></p>

      <div class="gf-meta">
        <p class="gf-meta-item">
          <strong>Locacion:</strong> Estudio 5 de Mayo &mdash; Josefa Ortiz de Dominguez &amp; Calle 5 de Mayo, Zona Central, La Paz, B.C.S.
        </p>
        <p class="gf-meta-item" id="gfDateNotice">
          <strong>Fecha y hora:</strong> se confirmaran directamente contigo con el equipo de produccion.
        </p>
        <p class="gf-meta-item hidden" id="gfThemeNotice"></p>
        <p class="gf-arrival-banner">
          &#9888;&#65039; <strong>Nota de Produccion:</strong> Se solicita cordialmente su arribo <strong>10 minutos antes</strong> de la hora programada para la colocacion de microfonia y pruebas de cintillos en video, evitando asi contratiempos en la transmision en vivo.
        </p>
        <a
          class="gf-btn gf-btn-outline"
          href="https://www.google.com/maps/search/?api=1&query=Josefa+Ortiz+de+Dominguez+%26+Calle+5+de+Mayo%2C+Zona+Central%2C+23000+La+Paz%2C+BCS"
          target="_blank" rel="noopener"
        >
          Ver ubicacion en Google Maps
        </a>
      </div>

      <!-- Recomendaciones del Conductor para este show (Fase 5.8) -->
      <div class="gf-notes hidden" id="gfConductorNotesBox">
        <p class="gf-notes-label">Recomendaciones de tu anfitrion</p>
        <p class="gf-text" id="gfConductorNotesText"></p>
      </div>

      <!-- Contacto directo del Conductor, opt-in (Fase 5.8) -->
      <div class="gf-contact hidden" id="gfContactBox">
        <p class="gf-notes-label">Contacto directo</p>
        <div class="gf-contact-buttons" id="gfContactButtons"></div>
      </div>

      <!-- Banner de uso publico de los datos -->
      <div class="gf-banner">
        Nota: esta informacion se utilizara para la difusion de tu participacion y graficos en video; es de acceso publico.
      </div>

      <form id="guestForm" class="gf-form" novalidate>
        <input type="hidden" id="csrf_token" name="csrf_token" value="">

        <div class="gf-field">
          <label for="full_name">Nombre completo <span class="gf-required">*</span></label>
          <input type="text" id="full_name" name="full_name" autocomplete="name" required>
          <small class="gf-field-message" id="full_name_message"></small>
        </div>

        <div class="gf-field">
          <label for="title_position">Titulo o puesto corporativo <span class="gf-required">*</span></label>
          <input type="text" id="title_position" name="title_position" autocomplete="organization-title" required>
          <small class="gf-field-message" id="title_position_message"></small>
        </div>

        <fieldset class="gf-fieldset">
          <legend>Datos opcionales de difusion</legend>

          <div class="gf-field">
            <label for="social_links">Redes sociales <span class="gf-optional">(opcional)</span></label>
            <input type="text" id="social_links" name="social_links" placeholder="Instagram, Facebook, LinkedIn&hellip;">
          </div>

          <div class="gf-field">
            <label for="whatsapp">WhatsApp publico <span class="gf-optional">(opcional)</span></label>
            <input type="tel" id="whatsapp" name="whatsapp" autocomplete="tel">
          </div>

          <div class="gf-field">
            <label for="website">Pagina web <span class="gf-optional">(opcional)</span></label>
            <input type="url" id="website" name="website" placeholder="https://">
          </div>

          <div class="gf-field">
            <label for="email">Email <span class="gf-optional">(opcional)</span></label>
            <input type="email" id="email" name="email" autocomplete="email">
            <small class="gf-field-message" id="email_message"></small>
          </div>
        </fieldset>

        <div class="gf-field">
          <label for="invite_message">Mensaje corto de invitacion <span class="gf-optional">(opcional)</span></label>
          <textarea id="invite_message" name="invite_message" rows="3" placeholder="Ej. &iexcl;Dale click a mi video corto!"></textarea>
        </div>

        <div class="gf-field">
          <label for="qa_notes">Comentarios o sugerencias sobre este proceso <span class="gf-optional">(opcional)</span></label>
          <textarea id="qa_notes" name="qa_notes" rows="2"></textarea>
        </div>

        <button type="submit" id="gfSubmitBtn" class="gf-btn gf-btn-primary gf-btn-block">Guardar mis datos</button>
        <p class="gf-system-message" id="gfSystemMessage" aria-live="polite"></p>
        <p class="gf-clicks-left" id="gfClicksLeft"></p>
      </form>
    </section>

    <footer class="gf-footer">
      <p id="gfThanksMessage">Nos vemos en el Estudio 5 de Mayo.</p>
      <p class="gf-footer-credit">Produccion tecnica por Media HUB Audiovisual Studio &mdash; La Paz, B.C.S.</p>
    </footer>

  </main>

  <script>window.MH_GUEST_TOKEN = <?php echo json_encode($token, JSON_UNESCAPED_UNICODE); ?>;</script>
  <script src="assets/js/guest-form.js" defer></script>
</body>
</html>
