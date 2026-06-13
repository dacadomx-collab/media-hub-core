<?php
/**
 * MH-CORE: Pantalla de contencion "Troll Mode".
 * Destino obligatorio para peticiones que disparan el detector de
 * patrones de inyeccion o cuentas marcadas como Troll_Mode.
 */
http_response_code(403);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media HUB | Acceso Restringido</title>
    <link rel="preload" href="assets/css/main.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="assets/css/main.css"></noscript>
</head>
<body>
    <main class="viewport" aria-labelledby="troll-title">
        <section class="login-shell" role="alert">
            <div class="brand-block">
                <div class="brand-mark" aria-hidden="true">
                    <img src="assets/img/logo.png" alt="Isotipo Media HUB" loading="eager" decoding="async">
                </div>
                <p class="brand-label">MEDIA HUB</p>
            </div>

            <header class="panel-header">
                <h1 id="troll-title">Acceso Restringido</h1>
                <p>Se detecto actividad sospechosa en esta peticion. El incidente fue registrado y tu IP ha sido bloqueada temporalmente.</p>
            </header>

            <p class="system-message error" style="text-align:center;">
                Si consideras que esto es un error, contacta al Administrador de Media HUB.
            </p>
        </section>
    </main>
</body>
</html>
