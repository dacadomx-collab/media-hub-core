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

require_once __DIR__ . '/response.php';
require_once __DIR__ . '/smtp_mailer.php';

/**
 * Lee una clave del .env raiz con valor por defecto. No expone el archivo
 * completo: solo la clave solicitada.
 *
 * Delega en Database::loadEnv() (parser propio linea-por-linea, ver
 * config/Database.php) en lugar de parse_ini_file() -- ese parser nativo
 * rompe (devuelve false para TODO el archivo) cuando alguna variable real
 * de GreenGeeks contiene "$", ";" u otros caracteres especiales de
 * sintaxis INI (mismo fix ya aplicado a api/response.php::mh_app_env()
 * en Fase 4.3; mh_mail_env() se habia quedado con la version fragil).
 */
function mh_mail_env(string $key, string $default = ''): string
{
    $env = Database::loadEnv();

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
 * Envia un correo HTML. Devuelve true si el envio reporto exito; en caso
 * contrario registra el intento en mail.log y devuelve false sin lanzar
 * excepciones (envio best-effort, no debe romper el flujo principal).
 *
 * TRANSPORTE (Fase 5.3): intenta primero SMTP autenticado real
 * (api/smtp_mailer.php::mh_smtp_send()) si `.env` tiene MAIL_HOST +
 * MAIL_USER + MAIL_PASS completos. Si falta cualquiera de los 3, degrada
 * automaticamente al mail() nativo de PHP (comportamiento previo, sin
 * romper entornos donde el SMTP aun no esta configurado).
 *
 * INTERCEPTOR TRANSITORIO DE PRUEBAS (Fase 5.1): si `.env` define
 * MH_MAIL_TEST_RECIPIENT, TODO correo transaccional (sin importar el
 * destinatario original) se redirige a esa direccion (con copia opcional
 * a MH_MAIL_TEST_CC), para auditar el diseno HTML responsive de las
 * plantillas antes de habilitar el envio real a usuarios finales. El
 * destinatario original se preserva en el asunto y en un aviso dentro
 * del cuerpo del correo, para no perder trazabilidad durante la prueba.
 * Desactivar: borrar/vaciar MH_MAIL_TEST_RECIPIENT en `.env`.
 */
function mh_send_mail(string $to, string $subject, string $htmlBody): bool
{
    $fromName    = mh_mail_env('MAIL_FROM_NAME', 'Media HUB');
    $fromAddress = mh_mail_env('MAIL_FROM_ADDRESS', 'no-reply@mediahubbcs.com');

    $testRecipient = mh_mail_env('MH_MAIL_TEST_RECIPIENT', '');
    $testCc        = mh_mail_env('MH_MAIL_TEST_CC', '');

    $actualTo = $to;
    $ccList   = [];

    if ($testRecipient !== '') {
        $actualTo = $testRecipient;
        $subject  = '[MODO PRUEBA | destinatario real: ' . $to . '] ' . $subject;

        $noticeHtml = '<div style="background:#fff3cd; border:1px solid #ffe08a; color:#7a5b00; '
            . 'padding:10px 14px; margin-bottom:16px; border-radius:6px; font-family:Arial, sans-serif; font-size:13px;">'
            . 'MODO PRUEBA TRANSITORIO -- este correo iba dirigido originalmente a <strong>'
            . htmlspecialchars($to, ENT_QUOTES, 'UTF-8') . '</strong>.</div>';
        $htmlBody = $noticeHtml . $htmlBody;

        if ($testCc !== '') {
            $ccList[] = $testCc;
        }
    }

    if (mh_smtp_configured()) {
        $sent = mh_smtp_send($actualTo, $subject, $htmlBody, $ccList);
        if ($sent) {
            return true;
        }
        // SMTP configurado pero fallo (credenciales/host/puerto incorrectos):
        // mh_smtp_send() ya registro el motivo real en error_log. Se intenta
        // mail() como ultimo recurso antes de dar el envio por perdido.
    }

    // Mismas cabeceras de alineacion SPF/DMARC que mh_smtp_send() (Fase 5.5)
    // -- ver comentario en api/smtp_mailer.php para el detalle completo.
    $headers   = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = sprintf('From: %s <%s>', $fromName, $fromAddress);
    $headers[] = 'Reply-To: ' . $fromAddress;
    $headers[] = 'Return-Path: ' . $fromAddress;
    $headers[] = 'X-Mailer: PHP/MediaHUB-Core';
    $headers[] = 'Message-ID: <' . uniqid('mh', true) . '@mediahub.tecnidepot.com>';
    if ($ccList !== []) {
        $headers[] = 'Cc: ' . implode(', ', $ccList);
    }

    $sent = @mail($actualTo, $subject, $htmlBody, implode("\r\n", $headers));

    if (!$sent) {
        $line = sprintf(
            "[%s] FALLO ENVIO a=%s (original=%s) subject=%s%s",
            date('Y-m-d H:i:s'),
            $actualTo,
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
    $appUrl   = htmlspecialchars(mh_detect_base_url(), ENT_QUOTES, 'UTF-8');

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

/**
 * Plantilla 5: Invitacion a crear contrasena (Onboarding por correo, Paso 1).
 * Se dispara al dar de alta un usuario con status='Pendiente'. El enlace
 * apunta a set_password.php?uid=...&token=... (token_hash en
 * password_resets con purpose='activation').
 */
function mh_mail_account_invite(string $fullName, string $email, string $role, string $activationUrl): array
{
    $fullName = htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');
    $email    = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $role     = htmlspecialchars($role, ENT_QUOTES, 'UTF-8');

    $body = <<<HTML
<h2 style="margin-top:0; color:#022D53;">Has sido invitado(a) a Media HUB</h2>
<p>Hola {$fullName}, el equipo de Media HUB Audiovisual Studio te ha dado de alta dentro de la plataforma con el rol <strong>{$role}</strong>.</p>
<table role="presentation" width="100%" cellpadding="8" cellspacing="0" style="background-color:#f2f7fa; border-radius:8px; margin:16px 0;">
<tr><td style="font-weight:bold; width:160px;">Tu usuario</td><td>{$email}</td></tr>
</table>
<p>Tu correo electronico sera tu identificador de acceso. Para activar tu cuenta, primero debes crear tu contrasena personal:</p>
HTML;

    $body .= mh_email_button('Crear mi contrasena', $activationUrl);
    $body .= '<p style="color:#3c5a6e; font-size:13px;">Este enlace es de un solo uso y expira en 7 dias. Si no esperabas esta invitacion, ignora este correo.</p>';

    return [
        'subject' => 'Media HUB | Invitacion a tu cuenta de acceso',
        'html'    => mh_email_layout('Invitacion a Media HUB', $body),
    ];
}

/**
 * Plantilla 6: Bienvenida oficial de acceso (Onboarding por correo, Paso 2).
 * Se dispara automaticamente al completar set_password.php con exito
 * (status pasa de 'Pendiente' a 'Activo'). El CTA abre la Landing con el
 * modal de Portal Staff listo para iniciar sesion.
 */
function mh_mail_account_activated(string $fullName, string $email = ''): array
{
    $fullName = htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');
    $email    = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $loginUrl = htmlspecialchars(mh_detect_base_url() . '/index.php#login', ENT_QUOTES, 'UTF-8');

    $body = <<<HTML
<h2 style="margin-top:0; color:#022D53;">Tu cuenta esta activa, {$fullName}</h2>
<p>Tu contrasena fue creada exitosamente y tu cuenta de Media HUB ya esta lista para operar. Bienvenido(a) oficialmente al equipo.</p>
HTML;

    if ($email !== '') {
        $body .= <<<HTML
<table role="presentation" width="100%" cellpadding="8" cellspacing="0" style="background-color:#f2f7fa; border-radius:8px; margin:16px 0;">
<tr><td style="font-weight:bold; width:160px;">Tu identificador de acceso</td><td>{$email}</td></tr>
</table>
<p style="color:#3c5a6e; font-size:13px;">Recuerda: para ingresar al Portal Staff, tu usuario en el modal de acceso sera siempre tu correo electronico ({$email}), el mismo con el que fuiste dado de alta.</p>
HTML;
    }

    $body .= mh_email_button('Iniciar sesion en Media HUB', $loginUrl);
    $body .= '<p style="color:#3c5a6e; font-size:13px;">Si tienes cualquier duda sobre tu acceso, contacta a un Administrador del sistema.</p>';

    return [
        'subject' => 'Media HUB | Tu cuenta ha sido activada',
        'html'    => mh_email_layout('Cuenta Activada', $body),
    ];
}

/**
 * Plantilla de Soporte (Fase 5.9): reporte/sugerencia enviado desde el
 * modulo "Soporte" del Dashboard (cualquier rol autenticado, actualmente
 * consumido por el Conductor). Va dirigido a SUPPORT_EMAIL/.env (o al
 * remitente por defecto si esa clave no existe).
 */
function mh_mail_support_request(string $fullName, string $email, string $role, string $message): array
{
    $fullNameSafe = htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');
    $emailSafe    = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $roleSafe     = htmlspecialchars($role, ENT_QUOTES, 'UTF-8');
    $messageSafe  = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));

    $body = <<<HTML
<h2 style="margin-top:0; color:#022D53;">Nuevo mensaje de Soporte</h2>
<table role="presentation" width="100%" cellpadding="8" cellspacing="0" style="background-color:#f2f7fa; border-radius:8px; margin:16px 0;">
<tr><td style="font-weight:bold; width:160px;">De</td><td>{$fullNameSafe} ({$roleSafe})</td></tr>
<tr><td style="font-weight:bold;">Correo</td><td>{$emailSafe}</td></tr>
</table>
<p style="color:#3c5a6e;">{$messageSafe}</p>
HTML;

    return [
        'subject' => 'Media HUB | Soporte: mensaje de ' . $fullName,
        'html'    => mh_email_layout('Nuevo mensaje de Soporte', $body),
    ];
}
