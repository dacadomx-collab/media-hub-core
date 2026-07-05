<?php
/**
 * MH-CORE: Aprovisionamiento de cuentas 'Pendiente' (Onboarding por correo,
 * Fase 5.1). Logica compartida entre api/users.php (alta directa de staff)
 * y api/programs.php (creacion inline de Conductor al dar de alta un show
 * nativo) -- una sola implementacion, sin duplicar (Mandamiento 8).
 *
 * Convencion: las funciones de este archivo NO envian correo. Solo hacen
 * trabajo de base de datos, para poder ejecutarse dentro de la transaccion
 * del llamador. El envio de la Plantilla 1 se hace DESPUES del commit via
 * mh_dispatch_account_invite(), igual que el resto de notificaciones
 * best-effort del sistema (ver api/agenda.php).
 */

require_once __DIR__ . '/mailer.php';

/**
 * Crea un usuario 'Pendiente' + firmas legales pendientes + token de
 * activacion (password_resets, purpose='activation', 7 dias). Debe
 * ejecutarse dentro de una transaccion ya abierta por el llamador.
 *
 * @return array{id:int, activation_url:string}
 */
function mh_provision_pending_user(PDO $pdo, string $userCode, string $fullName, string $email, string $role): array
{
    $placeholderHash = password_hash(bin2hex(random_bytes(32)), PASSWORD_BCRYPT, ['cost' => 12]);

    $stmt = $pdo->prepare(
        "INSERT INTO users (user_id, full_name, email, password_hash, role, status)
         VALUES (:user_id, :full_name, :email, :password_hash, :role, 'Pendiente')"
    );
    $stmt->execute([
        'user_id'       => $userCode,
        'full_name'     => $fullName,
        'email'         => $email,
        'password_hash' => $placeholderHash,
        'role'          => $role,
    ]);

    $newId = (int) $pdo->lastInsertId();

    $pdo->prepare(
        'INSERT INTO user_legal_signatures (user_id, document_id, signed)
         SELECT :new_user_id, id, 0 FROM legal_documents'
    )->execute(['new_user_id' => $newId]);

    return [
        'id'             => $newId,
        'activation_url' => mh_issue_activation_token($pdo, $newId),
    ];
}

/**
 * Emite un token de activacion nuevo (password_resets, purpose='activation',
 * 7 dias) para un user_id EXISTENTE, invalidando cualquier token de
 * activacion previo sin usar. Compartido por mh_provision_pending_user()
 * (usuario nuevo) y api/users.php?action=resend_invite (Fase 5.7 --
 * reenvio de invitacion a una cuenta 'Pendiente'/'Suspendido' existente).
 */
function mh_issue_activation_token(PDO $pdo, int $userId): string
{
    $pdo->prepare(
        "UPDATE password_resets SET used = 1 WHERE user_id = :user_id AND purpose = 'activation' AND used = 0"
    )->execute(['user_id' => $userId]);

    $rawToken  = bin2hex(random_bytes(32));
    $tokenHash = hash_hmac('sha256', $rawToken, mh_app_env('CSRF_SECRET', 'mediahub_fallback_secret'));
    $expiresAt = date('Y-m-d H:i:s', time() + (7 * 24 * 60 * 60));

    $pdo->prepare(
        "INSERT INTO password_resets (user_id, purpose, token_hash, expires_at)
         VALUES (:user_id, 'activation', :token_hash, :expires_at)"
    )->execute([
        'user_id'    => $userId,
        'token_hash' => $tokenHash,
        'expires_at' => $expiresAt,
    ]);

    return mh_detect_base_url() . '/set_password.php?uid=' . $userId . '&token=' . $rawToken;
}

/**
 * Despacha la Plantilla 1 (Invitacion a crear contrasena). Llamar SOLO
 * despues de un commit exitoso -- nunca dentro de la transaccion.
 */
function mh_dispatch_account_invite(string $fullName, string $email, string $role, string $activationUrl): void
{
    $mail = mh_mail_account_invite($fullName, $email, $role, $activationUrl);
    mh_send_mail($email, $mail['subject'], $mail['html']);
}
