<?php
/**
 * MH-CORE: Pantalla de Contrasena Segura (Onboarding por correo, Paso 2 de 2).
 * Recibe `uid` y `token` por query string (enlace de api/mailer.php ->
 * mh_mail_account_invite()). Valida en tiempo real la fortaleza de la
 * contrasena en el cliente; el procesamiento real del POST ocurre en
 * api/set_password.php (mismo patron que reset_password.php/api/reset_password.php).
 */

session_start();
require_once __DIR__ . '/api/csrf.php';

$csrfToken = csrf_token();

$uid   = (int) ($_GET['uid'] ?? 0);
$token = (string) ($_GET['token'] ?? '');

$setErrors = [
    'csrf'    => 'Tu sesion expiro. Recarga la pagina e intenta de nuevo.',
    'invalid' => 'El enlace de activacion no es valido o ya fue utilizado.',
    'expired' => 'El enlace de activacion ha expirado. Solicita una nueva invitacion a un Administrador.',
    'suspended' => 'Esta cuenta esta suspendida. Contacta a un Administrador antes de continuar.',
    'weak'    => 'La contrasena no cumple los requisitos minimos de seguridad.',
    'server'  => 'Error temporal del servidor. Intenta de nuevo.',
];
$setError = $setErrors[$_GET['error'] ?? ''] ?? '';
$setOk    = isset($_GET['success']);
?>
<!DOCTYPE html>
<html lang="es" class="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Media HUB | Crear Contrasena</title>
<link rel="icon" type="image/png" href="assets/img/logo.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/main.css">
<link rel="stylesheet" href="assets/css/login-widget.css">
<link rel="stylesheet" href="assets/css/set-password.css">
</head>
<body class="viewport">

  <section class="login-shell" role="region" aria-label="Crear contrasena Media HUB">
    <div class="brand-block">
      <div class="brand-mark" aria-hidden="true">
        <img src="assets/img/logo.png" alt="Isotipo Media HUB" loading="eager" decoding="async" class="h-16 md:h-20 w-auto object-contain mx-auto md:mx-0">
      </div>
      <p class="brand-label">MEDIA HUB</p>
    </div>

    <header class="panel-header">
      <h1>Crea tu contrasena</h1>
      <p>Este es el ultimo paso para activar tu cuenta en Media HUB.</p>
    </header>

    <?php if ($setOk): ?>
      <p class="system-message ok">Tu cuenta fue activada correctamente. Ya puedes <a href="index.php" style="color: var(--pacific-turquoise-light); text-decoration: underline;">iniciar sesion</a>.</p>
    <?php elseif ($uid <= 0 || $token === ''): ?>
      <p class="system-message error">El enlace de activacion no es valido. Solicita una nueva invitacion a un Administrador.</p>
    <?php else: ?>
    <form class="login-form" id="setPasswordForm" method="post" action="api/set_password.php" novalidate>
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="uid" value="<?php echo (int) $uid; ?>">
      <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">

      <label for="password">Nueva contrasena</label>
      <div class="field-wrap">
        <input type="password" id="password" name="password" autocomplete="new-password" placeholder="Minimo 8 caracteres" minlength="8" required>
        <span class="field-wave" aria-hidden="true"></span>
      </div>

      <ul class="sp-rules" id="spRules">
        <li id="ruleLength" data-rule="length">Minimo 8 caracteres</li>
        <li id="ruleUpper" data-rule="upper">Al menos 1 mayuscula</li>
        <li id="ruleLower" data-rule="lower">Al menos 1 minuscula</li>
        <li id="ruleNumber" data-rule="number">Al menos 1 numero</li>
        <li id="ruleSpecial" data-rule="special">Al menos 1 caracter especial (!@#$%...)</li>
      </ul>

      <label for="password_confirm">Confirmar contrasena</label>
      <div class="field-wrap">
        <input type="password" id="password_confirm" name="password_confirm" autocomplete="new-password" placeholder="Repite la contrasena" minlength="8" required>
        <span class="field-wave" aria-hidden="true"></span>
      </div>
      <small class="field-message" id="matchMessage" aria-live="polite"></small>

      <button type="submit" id="spSubmitBtn" disabled>Activar mi cuenta</button>
      <p class="system-message<?php echo $setError !== '' ? ' error' : ''; ?>" aria-live="polite"><?php echo htmlspecialchars($setError, ENT_QUOTES, 'UTF-8'); ?></p>
    </form>
    <?php endif; ?>
  </section>

  <script src="assets/js/set-password.js" defer></script>
</body>
</html>
