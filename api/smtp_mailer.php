<?php
/**
 * MH-CORE: Cliente SMTP nativo minimo (Fase 5.3, sin Composer/PHPMailer,
 * consistente con el stack "PHP 8.x nativo" del proyecto).
 *
 * Se activa SOLO si MAIL_HOST + MAIL_USER + MAIL_PASS estan completos en
 * `.env`. Si falta alguno, devuelve false y el llamador (mh_send_mail en
 * api/mailer.php) degrada al mail() nativo de PHP -- sin romper el
 * comportamiento actual mientras no haya credenciales SMTP reales.
 *
 * Soporta SSL implicito (puerto 465, cPanel estandar) via
 * stream_socket_client('ssl://...'). AUTH LOGIN (usuario/clave en base64).
 */

function mh_smtp_configured(): bool
{
    return mh_mail_env('MAIL_HOST', '') !== ''
        && mh_mail_env('MAIL_USER', '') !== ''
        && mh_mail_env('MAIL_PASS', '') !== '';
}

/**
 * Lee una respuesta SMTP completa (puede venir en varias lineas
 * multi-codigo "250-..." terminando en "250 ..."). Devuelve el texto crudo.
 */
function mh_smtp_read($socket): string
{
    $data = '';
    while (($line = fgets($socket, 515)) !== false) {
        $data .= $line;
        if (isset($line[3]) && $line[3] === ' ') {
            break;
        }
    }
    return $data;
}

function mh_smtp_write($socket, string $command): void
{
    fwrite($socket, $command . "\r\n");
}

/**
 * Envia un correo HTML via SMTP autenticado. Devuelve true/false; nunca
 * lanza excepciones al llamador (best-effort, igual que mh_send_mail()).
 * Cada fallo se registra en error_log con el motivo real (util para
 * diagnosticar credenciales/host/puerto incorrectos).
 */
function mh_smtp_send(string $to, string $subject, string $htmlBody, array $ccAddresses = []): bool
{
    if (!mh_smtp_configured()) {
        return false;
    }

    $host        = mh_mail_env('MAIL_HOST', '');
    $port        = (int) mh_mail_env('MAIL_PORT', '465');
    $user        = mh_mail_env('MAIL_USER', '');
    $pass        = mh_mail_env('MAIL_PASS', '');
    $fromName    = mh_mail_env('MAIL_FROM_NAME', 'Media HUB');
    $fromAddress = mh_mail_env('MAIL_FROM_ADDRESS', $user);

    $transport = $port === 465 ? 'ssl://' . $host : 'tcp://' . $host;

    // Contexto SSL ACOTADO para el certificado del servidor de correo: en
    // hosting compartido tipo cPanel/GreenGeeks es comun que MAIL_HOST
    // ("mediahub.tecnidepot.com") sea servido por un certificado emitido
    // para el hostname REAL del servidor compartido (ej.
    // "server123.greengeeks.net"), no para el dominio del cliente --
    // PHP rechaza la conexion por "CN mismatch" antes del saludo SMTP.
    //
    // Correccion de seguridad: SOLO se tolera el desajuste de nombre
    // (verify_peer_name=false). La cadena de confianza del certificado
    // (CA valida) SIGUE VALIDANDOSE (verify_peer=true) y NO se aceptan
    // certificados autofirmados/no confiables (allow_self_signed=false).
    // Desactivar verify_peer por completo abriria la puerta a un MITM
    // real sobre las credenciales AUTH LOGIN -- eso NO es lo que este
    // problema necesita resolver.
    $context = stream_context_create([
        'ssl' => [
            'verify_peer'       => true,
            'verify_peer_name'  => false,
            'allow_self_signed' => false,
        ],
    ]);

    $socket = @stream_socket_client(
        $transport . ':' . $port,
        $errno,
        $errstr,
        15,
        STREAM_CLIENT_CONNECT,
        $context
    );

    if ($socket === false) {
        error_log("MH-CORE SMTP: no se pudo conectar a {$host}:{$port} -- {$errstr} ({$errno})");
        return false;
    }

    try {
        $greeting = mh_smtp_read($socket);
        if (strpos($greeting, '220') !== 0) {
            throw new RuntimeException('Saludo SMTP invalido: ' . trim($greeting));
        }

        mh_smtp_write($socket, 'EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'mediahubbcs.com'));
        if (strpos(mh_smtp_read($socket), '250') !== 0) {
            throw new RuntimeException('EHLO rechazado por el servidor SMTP.');
        }

        mh_smtp_write($socket, 'AUTH LOGIN');
        if (strpos(mh_smtp_read($socket), '334') !== 0) {
            throw new RuntimeException('AUTH LOGIN no soportado/rechazado.');
        }

        mh_smtp_write($socket, base64_encode($user));
        if (strpos(mh_smtp_read($socket), '334') !== 0) {
            throw new RuntimeException('Usuario SMTP rechazado.');
        }

        mh_smtp_write($socket, base64_encode($pass));
        if (strpos(mh_smtp_read($socket), '235') !== 0) {
            throw new RuntimeException('Autenticacion SMTP fallida (usuario/clave incorrectos).');
        }

        mh_smtp_write($socket, 'MAIL FROM:<' . $fromAddress . '>');
        if (strpos(mh_smtp_read($socket), '250') !== 0) {
            throw new RuntimeException('MAIL FROM rechazado.');
        }

        foreach (array_merge([$to], $ccAddresses) as $recipient) {
            mh_smtp_write($socket, 'RCPT TO:<' . $recipient . '>');
            $rcptResp = mh_smtp_read($socket);
            if (strpos($rcptResp, '250') !== 0 && strpos($rcptResp, '251') !== 0) {
                throw new RuntimeException("RCPT TO rechazado para {$recipient}: " . trim($rcptResp));
            }
        }

        mh_smtp_write($socket, 'DATA');
        if (strpos(mh_smtp_read($socket), '354') !== 0) {
            throw new RuntimeException('Comando DATA rechazado.');
        }

        // Cabeceras de alineacion SPF/DMARC (Fase 5.5): From/Reply-To deben
        // vivir en el MISMO dominio que autentica la sesion SMTP
        // (MAIL_FROM_ADDRESS = hola@mediahub.tecnidepot.com, mismo dominio
        // que MAIL_USER) -- un From de otro dominio (ej. mediahubbcs.com)
        // rompe la alineacion DMARC y Yahoo/Hotmail descartan el correo en
        // silencio sin rebote visible. Return-Path aqui es informativo: el
        // "return-path" real que usan los MTA es el de MAIL FROM del sobre
        // SMTP (linea 126, ya alineado), no esta cabecera de contenido.
        $ccHeader = $ccAddresses !== [] ? ('Cc: ' . implode(', ', $ccAddresses) . "\r\n") : '';
        $headers  = "From: {$fromName} <{$fromAddress}>\r\n"
            . "To: {$to}\r\n"
            . $ccHeader
            . "Reply-To: {$fromAddress}\r\n"
            . "Return-Path: {$fromAddress}\r\n"
            . 'Subject: ' . mb_encode_mimeheader($subject, 'UTF-8') . "\r\n"
            . "MIME-Version: 1.0\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n"
            . "X-Mailer: PHP/MediaHUB-Core\r\n"
            . 'Message-ID: <' . uniqid('mh', true) . '@mediahub.tecnidepot.com>' . "\r\n";

        // Dot-stuffing SMTP: una linea que empieza con "." se duplica.
        $escapedBody = preg_replace('/^\./m', '..', $htmlBody);

        mh_smtp_write($socket, $headers . "\r\n" . $escapedBody . "\r\n.");
        $sendResp = mh_smtp_read($socket);
        if (strpos($sendResp, '250') !== 0) {
            throw new RuntimeException('El servidor SMTP rechazo el mensaje tras DATA: ' . trim($sendResp));
        }

        mh_smtp_write($socket, 'QUIT');
        fclose($socket);

        return true;
    } catch (RuntimeException $e) {
        error_log('MH-CORE SMTP error: ' . $e->getMessage());
        if (is_resource($socket)) {
            fclose($socket);
        }
        return false;
    }
}
