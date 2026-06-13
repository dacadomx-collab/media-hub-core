<?php
/**
 * MH-CORE: Motor de Correo Transaccional (HTML Responsivo).
 * Ubicacion: api/mailer.php (un nivel bajo la raiz).
 *
 * Envia notificaciones automaticas usando la funcion nativa mail() de PHP
 * (configurable via sendmail/SMTP del servidor) con plantillas HTML
 * responsivas basadas en tablas, usando la paleta oficial:
 *   - Azul Profundo (Deep Sea Blue):  #022D53 -> estructura/layout
 *   - Turquesa de Acento:             #00BFB2 -> botones/acentos
 *
 * Ningun dato sensible se hardcodea: el remitente se lee de .env
 * (MAIL_FROM_NAME / MAIL_FROM_ADDRESS), con valores por defecto seguros
 * si esas claves no existen todavia.
 *
 * Si mail() falla (comun en entornos XAMPP locales sin SMTP configurado),
 * el envio se registra en mail.log para depuracion, sin interrumpir el
 * flujo principal de la aplicacion (las notificaciones son "best effort").
 */

/**
 * Lee una clave del .env raiz con valor por defecto. No expone el archivo
 * completo: solo la clave solicitada.
 */
function mh_mail_env(string $key, string $default = ''): string
{
    static $env = null;

    if ($env === null) {
        $envFile = __DIR__ . '/../.env';
        $env     = file_exists($envFile) ? (parse_ini_file($envFile) ?: []) : [];
    }

    $value = $env[$key] ?? '';

    return $value !== '' ? (string) $value : $default;
}

/**
 * Envuelve el contenido de un correo en la plantilla HTML responsiva
 * (tabla 600px) con la identidad visual de Media HUB.
 */
function mh_email_layout(string $title, string $bodyHtml, string $footerNote = ''): string
{
    $appName = htmlspecialchars(mh_mail_env('APP_NAME', 'Media HUB Audiovisual Studio'), ENT_QUOTES, 'UTF-8');
    $title   = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $year    = date('Y');

    if ($footerNote === '') {
        $footerNote = 'Este es un mensaje automatico, por favor no respondas a este correo.';
    }
    $footerNote = htmlspecialchars($footerNote, ENT_QUOTES, 'UTF-8');

    return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$title}</title>
</head>
<body style="margin:0; padding:0; background-color:#01243f; font-family:Arial, Helvetica, sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#01243f; padding:24px 0;">
<tr>
<td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:12px; overflow:hidden;">
<tr>
<td style="background-color:#022D53; padding:24px 32px; text-align:center;">
<span style="font-family:Arial, Helvetica, sans-serif; font-size:20px; font-weight:bold; letter-spacing:2px; color:#00BFB2;">MEDIA HUB</span>
<div style="font-family:Arial, Helvetica, sans-serif; font-size:11px; letter-spacing:3px; color:#A9DCE8; margin-top:4px;">AUDIOVISUAL STUDIO</div>
</td>
</tr>
<tr>
<td style="padding:32px; color:#022D53; font-size:15px; line-height:1.6;">
{$bodyHtml}
</td>
</tr>
<tr>
<td style="background-color:#f2f7fa; padding:16px 32px; text-align:center; font-size:12px; color:#6b8a9a;">
{$footerNote}<br>
&copy; {$year} {$appName}
</td>
</tr>
</table>
</td>
</tr>
</table>
</body>
</html>
HTML;
}

/**
 * Genera un boton/CTA con el acento turquesa oficial.
 */
function mh_email_button(string $label, string $url): string
{
    $label = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
    $url   = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');

    return <<<HTML
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:20px 0;">
<tr>
<td style="border-radius:8px; background-color:#00BFB2;">
<a href="{$url}" target="_blank" style="display:inline-block; padding:12px 28px; font-family:Arial, Helvetica, sans-serif; font-size:14px; font-weight:bold; color:#022D53; text-decoration:none; border-radius:8px;">{$label}</a>
</td>
</tr>
</table>
HTML;
}

/**
 * Envia un correo HTML. Devuelve true si mail() reporto exito; en caso
 * contrario registra el intento en mail.log y devuelve false sin lanzar
 * excepciones (envio best-effort, no debe romper el flujo principal).
 */
function mh_send_mail(string $to, string $subject, string $htmlBody): bool
{
    $fromName    = mh_mail_env('MAIL_FROM_NAME', 'Media HUB');
    $fromAddress = mh_mail_env('MAIL_FROM_ADDRESS', 'no-reply@mediahubbcs.com');

    $headers   = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = sprintf('From: %s <%s>', $fromName, $fromAddress);

    $sent = @mail($to, $subject, $htmlBody, implode("\r\n", $headers));

    if (!$sent) {
        $line = sprintf(
            "[%s] FALLO ENVIO a=%s subject=%s%s",
            date('Y-m-d H:i:s'),
            $to,
            $subject,
            PHP_EOL
        );
        file_put_contents(__DIR__ . '/../mail.log', $line, FILE_APPEND | LOCK_EX);
    }

    return $sent;
}

/**
 * Plantilla 1: Bienvenida / Nuevo Usuario (envio de credenciales + ID unico).
 */
function mh_mail_welcome(string $fullName, string $userCode, string $email, string $tempPassword): array
{
    $fullName = htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');
    $userCode = htmlspecialchars($userCode, ENT_QUOTES, 'UTF-8');
    $email    = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $password = htmlspecialchars($tempPassword, ENT_QUOTES, 'UTF-8');
    $appUrl   = htmlspecialchars(mh_mail_env('APP_URL', ''), ENT_QUOTES, 'UTF-8');

    $body = <<<HTML
<h2 style="margin-top:0; color:#022D53;">Bienvenido(a) a Media HUB, {$fullName}</h2>
<p>Se ha creado tu cuenta dentro del ecosistema digital de Media HUB Audiovisual Studio. A continuacion tus datos de acceso:</p>
<table role="presentation" width="100%" cellpadding="8" cellspacing="0" style="background-color:#f2f7fa; border-radius:8px; margin:16px 0;">
<tr><td style="font-weight:bold; width:160px;">ID de Usuario</td><td>{$userCode}</td></tr>
<tr><td style="font-weight:bold;">Correo</td><td>{$email}</td></tr>
<tr><td style="font-weight:bold;">Contrasena temporal</td><td>{$password}</td></tr>
</table>
<p>Por seguridad, te recomendamos iniciar sesion y cambiar tu contrasena lo antes posible desde tu perfil.</p>
HTML;

    if ($appUrl !== '') {
        $body .= mh_email_button('Ir al Dashboard', $appUrl . '/index.php');
    }

    return ['subject' => 'Media HUB | Bienvenido al equipo', 'html' => mh_email_layout('Bienvenido a Media HUB', $body)];
}

/**
 * Plantilla 2: Nuevo Programa Creado (notificacion a Cliente Jornal).
 */
function mh_mail_new_program(string $clientName, string $programName, string $programDescription = ''): array
{
    $clientName  = htmlspecialchars($clientName, ENT_QUOTES, 'UTF-8');
    $programName = htmlspecialchars($programName, ENT_QUOTES, 'UTF-8');
    $description = $programDescription !== ''
        ? '<p style="color:#3c5a6e;">' . htmlspecialchars($programDescription, ENT_QUOTES, 'UTF-8') . '</p>'
        : '';

    $body = <<<HTML
<h2 style="margin-top:0; color:#022D53;">Nuevo Programa Registrado</h2>
<p>Hola {$clientName}, te informamos que se ha creado un nuevo programa recurrente bajo tu perfil de Cliente Jornal en Media HUB:</p>
<table role="presentation" width="100%" cellpadding="8" cellspacing="0" style="background-color:#f2f7fa; border-radius:8px; margin:16px 0;">
<tr><td style="font-weight:bold; width:160px;">Programa</td><td>{$programName}</td></tr>
</table>
{$description}
<p>Nuestro equipo se pondra en contacto contigo para programar los proximos llamados de produccion.</p>
HTML;

    return ['subject' => 'Media HUB | Nuevo programa: ' . $programName, 'html' => mh_email_layout('Nuevo Programa Registrado', $body)];
}

/**
 * Plantilla 3: Fecha Confirmada + Personal Reservado (auto-disparada al
 * validarse el anticipo del 50%).
 */
function mh_mail_call_confirmed(
    string $clientName,
    string $programName,
    string $title,
    string $location,
    string $callDate,
    string $startTime,
    string $endTime,
    array $staffNames = []
): array {
    $clientName  = htmlspecialchars($clientName, ENT_QUOTES, 'UTF-8');
    $programName = htmlspecialchars($programName, ENT_QUOTES, 'UTF-8');
    $title       = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $location    = htmlspecialchars($location, ENT_QUOTES, 'UTF-8');
    $callDate    = htmlspecialchars($callDate, ENT_QUOTES, 'UTF-8');
    $startTime   = htmlspecialchars($startTime, ENT_QUOTES, 'UTF-8');
    $endTime     = htmlspecialchars($endTime, ENT_QUOTES, 'UTF-8');

    $staffRows = '';
    foreach ($staffNames as $name) {
        $staffRows .= '<li>' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</li>';
    }
    $staffBlock = $staffRows !== ''
        ? '<p style="font-weight:bold; margin-bottom:6px;">Staff reservado para este llamado:</p><ul style="margin-top:0; color:#3c5a6e;">' . $staffRows . '</ul>'
        : '<p style="color:#3c5a6e;">El equipo de produccion sera asignado en breve.</p>';

    $body = <<<HTML
<h2 style="margin-top:0; color:#022D53;">Fecha Confirmada</h2>
<p>Hola {$clientName}, tu anticipo del 50% ha sido verificado. La fecha de tu programa <strong>{$programName}</strong> queda confirmada con los siguientes detalles:</p>
<table role="presentation" width="100%" cellpadding="8" cellspacing="0" style="background-color:#f2f7fa; border-radius:8px; margin:16px 0;">
<tr><td style="font-weight:bold; width:160px;">Llamado</td><td>{$title}</td></tr>
<tr><td style="font-weight:bold;">Locacion</td><td>{$location}</td></tr>
<tr><td style="font-weight:bold;">Fecha</td><td>{$callDate}</td></tr>
<tr><td style="font-weight:bold;">Horario</td><td>{$startTime} - {$endTime}</td></tr>
</table>
{$staffBlock}
<p>Nos vemos en el set. ¡Gracias por confiar en Media HUB!</p>
HTML;

    return ['subject' => 'Media HUB | Fecha confirmada: ' . $title, 'html' => mh_email_layout('Fecha Confirmada', $body)];
}

/**
 * Plantilla 4: Recuperacion de Contrasena (enlace HMAC, expira en 1 hora).
 */
function mh_mail_password_reset(string $fullName, string $resetUrl): array
{
    $fullName = htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');

    $body = <<<HTML
<h2 style="margin-top:0; color:#022D53;">Recuperacion de Contrasena</h2>
<p>Hola {$fullName}, recibimos una solicitud para restablecer tu contrasena de acceso a Media HUB.</p>
HTML;

    $body .= mh_email_button('Restablecer mi contrasena', $resetUrl);
    $body .= '<p style="color:#3c5a6e; font-size:13px;">Este enlace es valido durante <strong>1 hora</strong>. Si no solicitaste este cambio, ignora este mensaje y tu contrasena permanecera sin cambios.</p>';

    return ['subject' => 'Media HUB | Recuperacion de contrasena', 'html' => mh_email_layout('Recuperacion de Contrasena', $body)];
}
