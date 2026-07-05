<?php
/**
 * MH-CORE: Modulo de Soporte (Fase 5.9).
 * Ubicacion: api/support.php (un nivel bajo la raiz).
 *
 * Formulario de un solo clic para que cualquier usuario autenticado
 * (hoy consumido por el rol Conductor, ver dashboard/index.php #soporte)
 * envie comentarios, sugerencias o reportes tecnicos a la administracion
 * por correo -- sin exponer una bandeja compartida ni credenciales.
 *
 * Acciones soportadas (contrato { status, message, data }):
 *   POST action=send -> Envia el mensaje de soporte a SUPPORT_EMAIL (.env).
 */

session_start();

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/response.php';
require_once __DIR__ . '/mailer.php';

$currentUser = mh_require_session();
$method      = $_SERVER['REQUEST_METHOD'];
$action      = $_GET['action'] ?? '';

try {
    if ($method === 'POST' && $action === 'send') {
        $payload = mh_read_json_body();
        mh_guard_request($payload, 'support_send');
        mh_require_csrf($payload);

        $message = trim((string) ($payload['message'] ?? ''));

        if ($message === '') {
            mh_json_response('error', 'Escribe un mensaje antes de enviar.', [], 422);
        }
        if (mb_strlen($message) > 4000) {
            mh_json_response('error', 'El mensaje es demasiado largo (maximo 4000 caracteres).', [], 422);
        }

        $mail = mh_mail_support_request(
            $currentUser['full_name'],
            $currentUser['email'],
            $currentUser['role'],
            $message
        );

        $supportRecipient = mh_mail_env('SUPPORT_EMAIL', mh_mail_env('MAIL_FROM_ADDRESS', 'no-reply@mediahubbcs.com'));
        mh_send_mail($supportRecipient, $mail['subject'], $mail['html']);

        mh_json_response('success', 'Tu mensaje fue enviado a la administracion. Te responderemos pronto.');
    }

    mh_json_response('error', 'Accion o metodo no soportado.', [], 405);
} catch (PDOException $e) {
    error_log('MH-CORE DB error en api/support.php: ' . $e->getMessage());
    mh_json_response('error', 'Error interno del servidor. Intenta mas tarde.', [], 500);
}
