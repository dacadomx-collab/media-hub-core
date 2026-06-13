<?php
/**
 * MH-CORE: Pagina publica de Recuperacion de Contrasena.
 * Recibe `uid` y `token` por query string (enlace enviado por correo),
 * y presenta el formulario para definir una nueva contrasena.
 * El procesamiento real ocurre en api/reset_password.php.
 */

session_start();
require_once __DIR__ . '/api/csrf.php';

$csrfToken = csrf_token();

$uid   = (int) ($_GET['uid'] ?? 0);
$token = (string) ($_GET['token'] ?? '');

$resetErrors = [
    'csrf'    => 'Tu sesion expiro. Recarga la pagina e intenta de nuevo.',
    'invalid' => 'El enlace de recuperacion no es valido o ya fue utilizado.',
    'expired' => 'El enlace de recuperacion ha expirado. Solicita uno nuevo.',
    'weak'    => 'La nueva contrasena debe tener al menos 8 caracteres.',
    'server'  => 'Error temporal del servidor. Intenta de nuevo.',
];
$resetError = $resetErrors[$_GET['error'] ?? ''] ?? '';
$resetOk    = isset($_GET['success']);
?>
<!DOCTYPE html>
<html lang="es" class="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Media HUB | Recuperacion de Contrasena</title>
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
  html, body { min-height: 100%; }
  body { font-family: 'Roboto', sans-serif; }
  h1, h2, h3, .font-display { font-family: 'Montserrat', sans-serif; }
</style>
</head>
<body class="bg-white text-deep-sea dark:bg-deep-sea dark:text-digital-white transition-colors duration-300 grid place-items-center min-h-screen p-4">

  <section class="login-shell" role="region" aria-label="Recuperacion de contrasena Media HUB">
    <div class="brand-block">
      <div class="brand-mark" aria-hidden="true">
        <img src="assets/img/logo.png" alt="Isotipo Media HUB" loading="eager" decoding="async" class="h-16 md:h-20 w-auto object-contain mx-auto md:mx-0">
      </div>
      <p class="brand-label">MEDIA HUB</p>
    </div>

    <header class="panel-header">
      <h1>Recuperar Contrasena</h1>
      <p>Define una nueva contrasena para tu cuenta.</p>
    </header>

    <?php if ($resetOk): ?>
      <p class="system-message ok">Tu contrasena fue actualizada correctamente. Ya puedes <a href="index.php" style="color: var(--pacific-turquoise-light); text-decoration: underline;">iniciar sesion</a>.</p>
    <?php elseif ($uid <= 0 || $token === ''): ?>
      <p class="system-message error">El enlace de recuperacion no es valido. Solicita uno nuevo desde el Portal Staff.</p>
    <?php else: ?>
    <form class="login-form" id="resetForm" method="post" action="api/reset_password.php" novalidate>
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="uid" value="<?php echo (int) $uid; ?>">
      <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">

      <label for="password">Nueva contrasena</label>
      <div class="field-wrap">
        <input type="password" id="password" name="password" autocomplete="new-password" placeholder="Minimo 8 caracteres" minlength="8" required>
        <span class="field-wave" aria-hidden="true"></span>
      </div>

      <label for="password_confirm">Confirmar contrasena</label>
      <div class="field-wrap">
        <input type="password" id="password_confirm" name="password_confirm" autocomplete="new-password" placeholder="Repite la contrasena" minlength="8" required>
        <span class="field-wave" aria-hidden="true"></span>
      </div>
      <small class="field-message" id="matchMessage" aria-live="polite"></small>

      <button type="submit">Actualizar Contrasena</button>
      <p class="system-message<?php echo $resetError !== '' ? ' error' : ''; ?>" aria-live="polite"><?php echo htmlspecialchars($resetError, ENT_QUOTES, 'UTF-8'); ?></p>
    </form>
    <?php endif; ?>
  </section>

  <script>
    const form = document.getElementById('resetForm');
    if (form) {
      form.addEventListener('submit', (event) => {
        const pass = document.getElementById('password').value;
        const confirm = document.getElementById('password_confirm').value;
        const msg = document.getElementById('matchMessage');

        if (pass !== confirm) {
          event.preventDefault();
          msg.textContent = 'Las contrasenas no coinciden.';
          msg.classList.add('error');
        }
      });
    }
  </script>
</body>
</html>
