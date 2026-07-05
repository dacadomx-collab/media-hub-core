<?php
/**
 * MH-CORE: Consumo publico de Enlaces de Invitado (Guest Onboarding).
 * Ubicacion: api/guest_submissions.php (un nivel bajo la raiz).
 *
 * SIN SESION DE USUARIO — el invitado no tiene cuenta. La unica prueba de
 * acceso es la posesion del token de 256 bits en la URL. Sujeto igual a
 * Troll Mode (mh_guard_request) y a un CSRF de sesion anonima de un solo
 * uso por visita (ver knowledge/03_CONTRATOS_API_Y_RUTAS.md Contrato 10).
 *
 * Acciones soportadas (contrato { status, message, data }):
 *   GET  ?token=...  -> Consulta el estado del enlace (TTL de 3 clics) y
 *                       devuelve los datos publicos del show + formulario
 *                       vacio (click 1) o precargado (click 2).
 *   POST ?token=...  -> Guardado parcial (crea o actualiza) de los datos
 *                       del invitado.
 */

session_start();

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/response.php';

const MH_GUEST_MAX_CLICKS = 3;

$method = $_SERVER['REQUEST_METHOD'];
$token  = (string) ($_GET['token'] ?? '');

if ($token === '') {
    mh_json_response('error', 'Falta el token del enlace.', [], 422);
}

$pdo = Database::getInstance()->getConnection();

/**
 * Resuelve el enlace por token, o responde 404/410 y termina la ejecucion.
 */
function mh_guest_resolve_link(PDO $pdo, string $token): array
{
    // Fase 5.5: LEFT JOIN calls -- si el enlace se genero atado a un llamado
    // especifico (guest_invite_links.call_id), se devuelve fecha/hora reales
    // en vez del texto generico "se confirmaran con el equipo de produccion".
    // Fase 5.8: LEFT JOIN users (conductor) -- expone WhatsApp/Email SOLO si
    // el propio Conductor activo su opt-in de visibilidad publica.
    $stmt = $pdo->prepare(
        'SELECT gl.id, gl.program_id, gl.click_count, gl.max_clicks, gl.status,
                p.name AS program_name, p.catalog_description, p.logo_url, p.public_social_links,
                p.conductor_notes, p.affiliated_channel,
                c.call_date, c.start_time, c.end_time, c.location AS call_location, c.episode_theme,
                u.full_name AS conductor_full_name,
                CASE WHEN u.show_whatsapp_publicly = 1 THEN u.whatsapp ELSE NULL END AS conductor_whatsapp,
                CASE WHEN u.show_email_publicly = 1 THEN u.email ELSE NULL END AS conductor_email
         FROM guest_invite_links gl
         INNER JOIN programs p ON p.id = gl.program_id
         LEFT JOIN calls c ON c.id = gl.call_id
         LEFT JOIN users u ON u.id = p.conductor_user_id
         WHERE gl.token = :token'
    );
    $stmt->execute(['token' => $token]);
    $link = $stmt->fetch();

    if (!$link) {
        mh_json_response('error', 'El enlace no existe.', [], 404);
    }
    if ($link['status'] === 'Expirado' || (int) $link['click_count'] >= (int) $link['max_clicks']) {
        mh_json_response('error', 'Este enlace ha expirado. Visita la pagina publica de Media HUB.', [], 410);
    }

    return $link;
}

try {
    // -----------------------------------------------------------------
    // GET ?token=... — Consulta + avance del contador de clics (TTL)
    // -----------------------------------------------------------------
    if ($method === 'GET') {
        $link = mh_guest_resolve_link($pdo, $token);

        $currentClicks = (int) $link['click_count'];
        $newClicks     = $currentClicks + 1;
        $willExpire    = $newClicks >= MH_GUEST_MAX_CLICKS;

        $pdo->beginTransaction();
        try {
            if ($willExpire) {
                $pdo->prepare(
                    "UPDATE guest_invite_links
                     SET click_count = :clicks, status = 'Expirado', expired_at = NOW()
                     WHERE token = :token"
                )->execute(['clicks' => $newClicks, 'token' => $token]);
            } else {
                $pdo->prepare(
                    'UPDATE guest_invite_links SET click_count = :clicks WHERE token = :token'
                )->execute(['clicks' => $newClicks, 'token' => $token]);
            }
            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            throw $e;
        }

        // Formulario precargado si ya existe una submission previa (click 2).
        // qa_notes se precarga para que el invitado edite su propio comentario;
        // no se usa en graficos de video ni en el catalogo publico (§Contrato 10).
        $submissionStmt = $pdo->prepare(
            'SELECT full_name, title_position, social_links, whatsapp, website, email, invite_message, qa_notes
             FROM guest_submissions WHERE link_id = :link_id'
        );
        $submissionStmt->execute(['link_id' => $link['id']]);
        $existingSubmission = $submissionStmt->fetch() ?: null;

        mh_json_response('success', 'Enlace valido.', [
            'program' => [
                'name'                 => $link['program_name'],
                'catalog_description'  => $link['catalog_description'],
                'logo_url'             => $link['logo_url'],
                'public_social_links'  => $link['public_social_links'],
                'conductor_notes'      => $link['conductor_notes'],
                'affiliated_channel'   => $link['affiliated_channel'],
            ],
            'conductor' => [
                'full_name' => $link['conductor_full_name'],
                'whatsapp'  => $link['conductor_whatsapp'],
                'email'     => $link['conductor_email'],
            ],
            'call' => $link['call_date'] !== null ? [
                'call_date'     => $link['call_date'],
                'start_time'    => $link['start_time'],
                'end_time'      => $link['end_time'],
                'location'      => $link['call_location'],
                'episode_theme' => $link['episode_theme'],
            ] : null,
            'click_number'      => $newClicks,
            'clicks_restantes'  => max(0, MH_GUEST_MAX_CLICKS - $newClicks),
            'submission'        => $existingSubmission,
            'csrf_token'        => csrf_token(),
        ]);
    }

    // -----------------------------------------------------------------
    // POST ?token=... — Guardado parcial (crea o actualiza)
    // -----------------------------------------------------------------
    if ($method === 'POST') {
        $link = mh_guest_resolve_link($pdo, $token);

        $payload = mh_read_json_body();
        mh_guard_request($payload, 'guest_submission_save');
        mh_require_csrf($payload);

        $fullName      = trim((string) ($payload['full_name'] ?? ''));
        $titlePosition = trim((string) ($payload['title_position'] ?? ''));
        $socialLinks   = $payload['social_links'] ?? null;
        $whatsapp      = trim((string) ($payload['whatsapp'] ?? ''));
        $website       = trim((string) ($payload['website'] ?? ''));
        $email         = trim((string) ($payload['email'] ?? ''));
        $inviteMessage = trim((string) ($payload['invite_message'] ?? ''));
        $qaNotes       = trim((string) ($payload['qa_notes'] ?? ''));

        if ($fullName === '' || $titlePosition === '') {
            mh_json_response('error', 'Faltan campos obligatorios (full_name, title_position).', [], 422);
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            mh_json_response('error', 'El correo electronico no tiene un formato valido.', [], 422);
        }

        // social_links llega como texto libre desde el formulario publico
        // (no como objeto estructurado); se persiste como JSON valido.
        if (is_string($socialLinks)) {
            $socialLinks = trim($socialLinks) !== '' ? trim($socialLinks) : null;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO guest_submissions
                (link_id, full_name, title_position, social_links, whatsapp, website, email, invite_message, qa_notes)
             VALUES
                (:link_id, :full_name, :title_position, :social_links, :whatsapp, :website, :email, :invite_message, :qa_notes)
             ON DUPLICATE KEY UPDATE
                full_name = VALUES(full_name),
                title_position = VALUES(title_position),
                social_links = VALUES(social_links),
                whatsapp = VALUES(whatsapp),
                website = VALUES(website),
                email = VALUES(email),
                invite_message = VALUES(invite_message),
                qa_notes = VALUES(qa_notes)'
        );
        $stmt->execute([
            'link_id'         => $link['id'],
            'full_name'       => $fullName,
            'title_position'  => $titlePosition,
            'social_links'    => $socialLinks !== null ? json_encode($socialLinks, JSON_UNESCAPED_UNICODE) : null,
            'whatsapp'        => $whatsapp !== '' ? $whatsapp : null,
            'website'         => $website !== '' ? $website : null,
            'email'           => $email !== '' ? $email : null,
            'invite_message'  => $inviteMessage !== '' ? $inviteMessage : null,
            'qa_notes'        => $qaNotes !== '' ? $qaNotes : null,
        ]);

        mh_json_response('success', 'Datos guardados.', [
            'click_count'      => (int) $link['click_count'],
            'clicks_restantes' => max(0, MH_GUEST_MAX_CLICKS - (int) $link['click_count']),
        ]);
    }

    mh_json_response('error', 'Metodo no soportado.', [], 405);
} catch (PDOException $e) {
    error_log('MH-CORE DB error en api/guest_submissions.php: ' . $e->getMessage());
    mh_json_response('error', 'Error interno del servidor. Intenta mas tarde.', [], 500);
}
