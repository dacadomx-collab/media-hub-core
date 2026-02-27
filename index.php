<?php
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media HUB | Login</title>
    <meta name="description" content="Acceso seguro al centro de control de Media HUB">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link rel="preload" href="assets/css/main.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="assets/css/main.css"></noscript>
</head>
<body>
    <main class="viewport" aria-labelledby="login-title">
        <section class="login-shell" role="region" aria-label="Panel de acceso Media HUB">
            <div class="brand-block">
                <div class="brand-mark" aria-hidden="true">
                    <img src="assets/img/logo.png" alt="Isotipo Media HUB" loading="eager" decoding="async">
                </div>
                <p class="brand-label">MEDIA HUB</p>
            </div>

            <header class="panel-header">
                <h1 id="login-title">Command Access</h1>
                <p>Autentica tu identidad para ingresar al núcleo digital.</p>
            </header>

            <form class="login-form" id="loginForm" method="post" action="#" novalidate>
                <input type="hidden" name="csrf_token" id="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

                <label for="email">Correo corporativo</label>
                <div class="field-wrap">
                    <input type="email" id="email" name="email" autocomplete="username" inputmode="email" placeholder="usuario@mediahub.com" required>
                    <span class="field-wave" aria-hidden="true"></span>
                </div>
                <small class="field-message" id="emailMessage" aria-live="polite"></small>

                <label for="password">Clave de acceso</label>
                <div class="field-wrap">
                    <input type="password" id="password" name="password" autocomplete="current-password" placeholder="••••••••••" minlength="8" required>
                    <span class="field-wave" aria-hidden="true"></span>
                </div>
                <small class="field-message" id="passwordMessage" aria-live="polite"></small>

                <button type="submit" id="submitBtn">Ingresar al HUB</button>
                <p class="system-message" id="systemMessage" aria-live="polite"></p>
            </form>
        </section>
    </main>

    <script src="assets/js/login.js" defer></script>
</body>
</html>
