<?php
/**
 * MH-CORE: Endpoint de Programas Recurrentes (CRUD + gestion de estados).
 * Ubicacion: api/programs.php (un nivel bajo la raiz).
 *
 * Acciones soportadas (contrato { status, message, data }):
 *   GET  ?action=public_catalog -> PUBLICO (sin sesion). Shows nativos activos
 *                                  para el catalogo de la Landing (index.php).
 *   GET  ?action=list       -> Listado de programas de Clientes Jornal (filtro opcional client_id).
 *   POST action=create      -> Alta de programa de Cliente Jornal (Super_admin, Admin, Lider_Proyecto).
 *                               Dispara correo "Nuevo Programa" al cliente si tiene email.
 *   PUT  action=update      -> Edicion de nombre/descripcion/cliente (Super_admin, Admin, Lider_Proyecto).
 *   POST action=deactivate  -> Baja logica -> is_active = 0 (Super_admin, Admin, Lider_Proyecto).
 *   POST action=activate    -> Reactivacion -> is_active = 1 (Super_admin, Admin, Lider_Proyecto).
 *   GET  ?action=list_native   -> Listado de Shows Nativos (Super_admin, Admin) -- Fase 5.2.
 *   POST action=create_native -> Alta de Show Nativo (Super_admin, Admin) -- Fase 5.2.
 *                                 multipart/form-data (logo opcional) + Conductor inline opcional.
 *   GET  ?action=my_native_show -> El/los show(s) propios del Conductor autenticado -- Fase 5.2.
 */

session_start();

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/response.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/user_provisioning.php';

const MH_LOGO_MAX_BYTES = 5 * 1024 * 1024; // 5 MB
const MH_LOGO_ALLOWED_MIME = [
    'image/png'  => 'png',
    'image/jpeg' => 'jpg',
    'image/webp' => 'webp',
];
const MH_SCHEDULE_VALID_DAYS = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo'];

/**
 * Valida (MIME real via finfo, no la extension declarada por el cliente)
 * y persiste el logo subido en /uploads con nombre re-generado por hash.
 * Devuelve la ruta relativa (ej. "uploads/ab12...ef.png") o null si no se
 * envio ningun archivo. Lanza RuntimeException con mensaje seguro ante
 * cualquier archivo invalido -- el llamador debe envolver en try/catch.
 */
function mh_handle_logo_upload(?array $file): ?string
{
    if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Error al subir el archivo (codigo ' . $file['error'] . ').');
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Archivo de logo invalido.');
    }

    if ($file['size'] > MH_LOGO_MAX_BYTES) {
        throw new RuntimeException('El logo excede el tamano maximo de 5 MB.');
    }

    // La extension fileinfo puede no estar disponible en algunos entornos
    // de hosting compartido -- degradar con RuntimeException (capturada
    // por el llamador) en vez de un Error fatal no capturado (causa
    // conocida de 500 "silenciosos": Error no es Exception, un catch
    // tipado a RuntimeException/PDOException no lo detiene).
    if (class_exists('finfo')) {
        $realMime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    } elseif (function_exists('mime_content_type')) {
        $realMime = mime_content_type($file['tmp_name']);
    } else {
        throw new RuntimeException('El servidor no puede validar el tipo de archivo (fileinfo no disponible).');
    }

    if ($realMime === false) {
        throw new RuntimeException('No se pudo determinar el tipo real del archivo.');
    }

    if (!isset(MH_LOGO_ALLOWED_MIME[$realMime])) {
        throw new RuntimeException('Formato de logo no permitido. Usa PNG, JPEG o WEBP.');
    }

    $extension  = MH_LOGO_ALLOWED_MIME[$realMime];
    $uploadsDir = __DIR__ . '/../uploads';

    if (!is_dir($uploadsDir) && !mkdir($uploadsDir, 0755, true) && !is_dir($uploadsDir)) {
        throw new RuntimeException('No se pudo preparar el directorio de carga.');
    }

    $fileName = hash('sha256', $file['tmp_name'] . microtime(true) . random_bytes(8)) . '.' . $extension;
    $destPath = $uploadsDir . '/' . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        throw new RuntimeException('No se pudo guardar el archivo del logo.');
    }

    return 'uploads/' . $fileName;
}

/**
 * Construye el JSON de production_schedule a partir del payload de
 * formulario, o null si no se proporciono ningun dato de calendario.
 */
function mh_build_schedule_json(array $days, string $startTime, string $endTime): ?string
{
    $validDays = array_values(array_intersect($days, MH_SCHEDULE_VALID_DAYS));

    if ($validDays === [] && $startTime === '' && $endTime === '') {
        return null;
    }

    return json_encode([
        'days'       => $validDays,
        'start_time' => $startTime !== '' ? $startTime : null,
        'end_time'   => $endTime !== '' ? $endTime : null,
    ], JSON_UNESCAPED_UNICODE);
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

$pdo = Database::getInstance()->getConnection();

try {
    // -----------------------------------------------------------------
    // GET ?action=public_catalog — PUBLICO, sin sesion (catalogo Landing)
    // Solo shows nativos activos, columnas publicas exclusivamente.
    // -----------------------------------------------------------------
    if ($method === 'GET' && $action === 'public_catalog') {
        $stmt = $pdo->query(
            'SELECT id, name, catalog_description, logo_url, public_social_links
             FROM programs
             WHERE is_native_show = 1 AND is_active = 1
             ORDER BY name'
        );

        mh_json_response('success', 'Catalogo publico cargado.', ['programs' => $stmt->fetchAll()]);
    }

    // A partir de aqui, todas las acciones requieren sesion de staff.
    $currentUser = mh_require_session();

    // -----------------------------------------------------------------
    // GET ?action=list — Listado de programas
    // -----------------------------------------------------------------
    if ($method === 'GET' && $action === 'list') {
        mh_require_role($currentUser, ['Super_admin', 'Admin', 'Lider_Proyecto']);

        $sql    = 'SELECT pr.id, pr.client_id, pr.name, pr.description, pr.is_active, pr.created_at,
                          cl.full_name AS client_name, cl.company AS client_company
                   FROM programs pr
                   INNER JOIN clients cl ON cl.id = pr.client_id';
        $params = [];

        if (!empty($_GET['client_id'])) {
            $sql               .= ' WHERE pr.client_id = :client_id';
            $params['client_id'] = (int) $_GET['client_id'];
        }
        $sql .= ' ORDER BY pr.is_active DESC, pr.name';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        mh_json_response('success', 'Programas cargados.', ['programs' => $stmt->fetchAll()]);
    }

    // -----------------------------------------------------------------
    // POST action=create — Alta de programa
    // -----------------------------------------------------------------
    if ($method === 'POST' && $action === 'create') {
        mh_require_role($currentUser, ['Super_admin', 'Admin', 'Lider_Proyecto']);

        $payload = mh_read_json_body();
        mh_guard_request($payload, 'programs_create');
        mh_require_csrf($payload);

        $clientId    = (int) ($payload['client_id'] ?? 0);
        $name        = trim((string) ($payload['name'] ?? ''));
        $description = trim((string) ($payload['description'] ?? ''));

        if ($clientId <= 0 || $name === '') {
            mh_json_response('error', 'Faltan campos obligatorios (client_id, name).', [], 422);
        }

        $clientStmt = $pdo->prepare('SELECT full_name, email FROM clients WHERE id = :id');
        $clientStmt->execute(['id' => $clientId]);
        $client = $clientStmt->fetch();

        if (!$client) {
            mh_json_response('error', 'El cliente indicado no existe.', [], 404);
        }

        $stmt = $pdo->prepare(
            'INSERT INTO programs (client_id, name, description)
             VALUES (:client_id, :name, :description)'
        );
        $stmt->execute([
            'client_id'   => $clientId,
            'name'        => $name,
            'description' => $description !== '' ? $description : null,
        ]);

        $newId = (int) $pdo->lastInsertId();

        // Notificacion "Nuevo Programa Creado" al Cliente Jornal (best-effort).
        if (!empty($client['email'])) {
            $mail = mh_mail_new_program($client['full_name'], $name, $description);
            mh_send_mail($client['email'], $mail['subject'], $mail['html']);
        }

        mh_json_response('success', 'Programa registrado correctamente.', ['id' => $newId], 201);
    }

    // -----------------------------------------------------------------
    // PUT action=update — Edicion de programa
    // -----------------------------------------------------------------
    if ($method === 'PUT' && $action === 'update') {
        mh_require_role($currentUser, ['Super_admin', 'Admin', 'Lider_Proyecto']);

        $payload = mh_read_json_body();
        mh_guard_request($payload, 'programs_update');
        mh_require_csrf($payload);

        $targetId = (int) ($payload['id'] ?? 0);
        if ($targetId <= 0) {
            mh_json_response('error', 'Falta el id del programa a editar.', [], 422);
        }

        $fields = [];
        $params = ['id' => $targetId];

        if (isset($payload['name']) && trim((string) $payload['name']) !== '') {
            $fields[]        = 'name = :name';
            $params['name']  = trim((string) $payload['name']);
        }
        if (isset($payload['description'])) {
            $description           = trim((string) $payload['description']);
            $fields[]               = 'description = :description';
            $params['description']  = $description !== '' ? $description : null;
        }
        if (isset($payload['client_id'])) {
            $clientId = (int) $payload['client_id'];
            if ($clientId <= 0) {
                mh_json_response('error', 'client_id invalido.', [], 422);
            }
            $fields[]            = 'client_id = :client_id';
            $params['client_id'] = $clientId;
        }

        if ($fields === []) {
            mh_json_response('error', 'No se proporcionaron campos para actualizar.', [], 422);
        }

        $stmt = $pdo->prepare('UPDATE programs SET ' . implode(', ', $fields) . ' WHERE id = :id');
        $stmt->execute($params);

        if ($stmt->rowCount() === 0) {
            mh_json_response('error', 'El programa no existe.', [], 404);
        }

        mh_json_response('success', 'Programa actualizado correctamente.', ['id' => $targetId]);
    }

    // -----------------------------------------------------------------
    // POST action=deactivate — Baja logica
    // -----------------------------------------------------------------
    if ($method === 'POST' && $action === 'deactivate') {
        mh_require_role($currentUser, ['Super_admin', 'Admin', 'Lider_Proyecto']);

        $payload = mh_read_json_body();
        mh_guard_request($payload, 'programs_deactivate');
        mh_require_csrf($payload);

        $targetId = (int) ($payload['id'] ?? 0);
        if ($targetId <= 0) {
            mh_json_response('error', 'Falta el id del programa a dar de baja.', [], 422);
        }

        $stmt = $pdo->prepare('UPDATE programs SET is_active = 0 WHERE id = :id');
        $stmt->execute(['id' => $targetId]);

        mh_json_response('success', 'Programa dado de baja (is_active = 0).', ['id' => $targetId]);
    }

    // -----------------------------------------------------------------
    // POST action=activate — Reactivacion
    // -----------------------------------------------------------------
    if ($method === 'POST' && $action === 'activate') {
        mh_require_role($currentUser, ['Super_admin', 'Admin', 'Lider_Proyecto']);

        $payload = mh_read_json_body();
        mh_guard_request($payload, 'programs_activate');
        mh_require_csrf($payload);

        $targetId = (int) ($payload['id'] ?? 0);
        if ($targetId <= 0) {
            mh_json_response('error', 'Falta el id del programa a reactivar.', [], 422);
        }

        $stmt = $pdo->prepare('UPDATE programs SET is_active = 1 WHERE id = :id');
        $stmt->execute(['id' => $targetId]);

        mh_json_response('success', 'Programa reactivado (is_active = 1).', ['id' => $targetId]);
    }

    // -----------------------------------------------------------------
    // GET ?action=list_native — Listado de Shows Nativos (Fase 5.2)
    // -----------------------------------------------------------------
    if ($method === 'GET' && $action === 'list_native') {
        mh_require_role($currentUser, ['Super_admin', 'Admin']);

        $stmt = $pdo->query(
            'SELECT p.id, p.name, p.catalog_description, p.logo_url, p.public_social_links,
                    p.production_schedule, p.is_active, p.created_at,
                    p.conductor_user_id, u.full_name AS conductor_name, u.email AS conductor_email,
                    u.status AS conductor_status
             FROM programs p
             LEFT JOIN users u ON u.id = p.conductor_user_id
             WHERE p.is_native_show = 1
             ORDER BY p.created_at DESC'
        );

        mh_json_response('success', 'Shows nativos cargados.', ['programs' => $stmt->fetchAll()]);
    }

    // -----------------------------------------------------------------
    // POST action=create_native — Alta de Show Nativo + Conductor inline (Fase 5.2)
    // multipart/form-data (NO JSON): $_POST + $_FILES['logo'] opcional.
    // -----------------------------------------------------------------
    if ($method === 'POST' && $action === 'create_native') {
        mh_require_role($currentUser, ['Super_admin', 'Admin']);

        mh_guard_request($_POST, 'programs_create_native');

        if (!csrf_validate($_POST['csrf_token'] ?? null)) {
            mh_json_response('error', 'Token CSRF invalido o sesion expirada.', [], 403);
        }

        $name               = trim((string) ($_POST['name'] ?? ''));
        $catalogDescription = trim((string) ($_POST['catalog_description'] ?? ''));
        $scheduleDays       = array_map('strval', (array) ($_POST['schedule_days'] ?? []));
        $scheduleStart      = trim((string) ($_POST['schedule_start_time'] ?? ''));
        $scheduleEnd        = trim((string) ($_POST['schedule_end_time'] ?? ''));

        if ($name === '') {
            mh_json_response('error', 'Falta el nombre del show.', [], 422);
        }

        // ---------------------------------------------------------------
        // Redes sociales (Hito 3, Fase 5.3): arreglo estructurado
        // [{platform, url}, ...] enviado como JSON en un campo oculto por
        // el componente dinamico del formulario. Cero, una o varias filas.
        // ---------------------------------------------------------------
        $socialLinksRaw = (string) ($_POST['public_social_links_json'] ?? '');
        $socialLinksArr = [];

        if ($socialLinksRaw !== '') {
            $decoded = json_decode($socialLinksRaw, true);

            if (!is_array($decoded)) {
                mh_json_response('error', 'Formato invalido en redes sociales.', [], 422);
            }

            foreach ($decoded as $entry) {
                $platform = trim((string) ($entry['platform'] ?? ''));
                $url      = trim((string) ($entry['url'] ?? ''));

                if ($platform === '' && $url === '') {
                    continue;
                }
                if ($url !== '' && !filter_var($url, FILTER_VALIDATE_URL)) {
                    mh_json_response('error', "La URL de red social \"{$url}\" no es valida.", [], 422);
                }

                $socialLinksArr[] = ['platform' => $platform, 'url' => $url];
            }
        }

        // ---------------------------------------------------------------
        // Conductor Inline (opcional): solo si al menos un campo viene lleno.
        // Identidad Email-Only (Fase 5.3): sin campo de ID manual -- el
        // correo del Conductor es su identificador unico internamente.
        // ---------------------------------------------------------------
        $conductorFullName = trim((string) ($_POST['conductor_full_name'] ?? ''));
        $conductorEmail    = trim((string) ($_POST['conductor_email'] ?? ''));
        $createConductor   = $conductorFullName !== '' || $conductorEmail !== '';

        if ($createConductor) {
            if ($conductorFullName === '' || $conductorEmail === '') {
                mh_json_response('error', 'Para crear un Conductor inline se requieren nombre completo y correo.', [], 422);
            }
            if (!filter_var($conductorEmail, FILTER_VALIDATE_EMAIL)) {
                mh_json_response('error', 'El correo del Conductor no tiene un formato valido.', [], 422);
            }
        }

        // Validacion del logo ANTES de abrir la transaccion (falla rapido, sin tocar la BD).
        try {
            $logoUrl = mh_handle_logo_upload($_FILES['logo'] ?? null);
        } catch (RuntimeException $e) {
            mh_json_response('error', $e->getMessage(), [], 422);
        }

        $scheduleJson = mh_build_schedule_json($scheduleDays, $scheduleStart, $scheduleEnd);

        $pdo->beginTransaction();

        try {
            $conductorUserId = null;
            $provision       = null;

            if ($createConductor) {
                $provision        = mh_provision_pending_user($pdo, $conductorEmail, $conductorFullName, $conductorEmail, 'Conductor');
                $conductorUserId  = $provision['id'];
            }

            $stmt = $pdo->prepare(
                'INSERT INTO programs
                    (client_id, name, description, catalog_description, logo_url,
                     public_social_links, production_schedule, is_native_show, conductor_user_id, is_active)
                 VALUES
                    (NULL, :name, :description, :catalog_description, :logo_url,
                     :public_social_links, :production_schedule, 1, :conductor_user_id, 1)'
            );
            $stmt->execute([
                'name'                => $name,
                'description'         => $catalogDescription !== '' ? $catalogDescription : null,
                'catalog_description' => $catalogDescription !== '' ? $catalogDescription : null,
                'logo_url'            => $logoUrl,
                // public_social_links es columna JSON real (migration_fase5) --
                // arreglo estructurado [{platform,url}] (Hito 3, Fase 5.3).
                // Sin filas -> se persiste "[]" (arreglo vacio explicito), no
                // NULL -- evita distinguir "sin dato" de "sin redes" en el
                // frontend (ambos casos ya son JSON valido de cualquier forma).
                'public_social_links' => json_encode($socialLinksArr, JSON_UNESCAPED_UNICODE),
                'production_schedule' => $scheduleJson,
                'conductor_user_id'   => $conductorUserId,
            ]);

            $newProgramId = (int) $pdo->lastInsertId();

            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();

            // El programa no se creo: el logo ya subido queda huerfano, se limpia.
            if ($logoUrl !== null) {
                @unlink(__DIR__ . '/../' . $logoUrl);
            }

            if ((int) $e->errorInfo[1] === 1062) {
                mh_json_response('error', 'Ya existe un usuario registrado con el correo del Conductor indicado. Usa un correo distinto o edita al usuario existente desde el organigrama.', [], 409);
            }

            throw $e;
        }

        // Plantilla 1 al Conductor recien creado (best-effort, post-commit).
        if ($provision !== null) {
            mh_dispatch_account_invite($conductorFullName, $conductorEmail, 'Conductor', $provision['activation_url']);
        }

        mh_json_response('success', 'Show nativo creado correctamente.', [
            'id'            => $newProgramId,
            'conductor_id'  => $conductorUserId,
        ], 201);
    }

    // -----------------------------------------------------------------
    // GET ?action=my_native_show — Show(s) propios del Conductor (Fase 5.2)
    // -----------------------------------------------------------------
    if ($method === 'GET' && $action === 'my_native_show') {
        mh_require_role($currentUser, ['Conductor']);

        $stmt = $pdo->prepare(
            'SELECT id, name, catalog_description, logo_url, public_social_links, production_schedule,
                    conductor_notes, affiliated_channel
             FROM programs
             WHERE is_native_show = 1 AND conductor_user_id = :conductor_id AND is_active = 1
             ORDER BY name'
        );
        $stmt->execute(['conductor_id' => $currentUser['user_id']]);

        mh_json_response('success', 'Show(s) cargado(s).', ['programs' => $stmt->fetchAll()]);
    }

    // -----------------------------------------------------------------
    // PUT action=update_conductor_profile — El Conductor edita las notas
    // y el canal afiliado de SU PROPIO show nativo (Fase 5.8).
    // -----------------------------------------------------------------
    if ($method === 'PUT' && $action === 'update_conductor_profile') {
        mh_require_role($currentUser, ['Conductor']);

        $payload = mh_read_json_body();
        mh_guard_request($payload, 'programs_update_conductor_profile');
        mh_require_csrf($payload);

        $programId = (int) ($payload['program_id'] ?? 0);
        if ($programId <= 0) {
            mh_json_response('error', 'Falta el campo program_id.', [], 422);
        }

        $ownStmt = $pdo->prepare(
            'SELECT id FROM programs WHERE id = :id AND is_native_show = 1 AND conductor_user_id = :conductor_id'
        );
        $ownStmt->execute(['id' => $programId, 'conductor_id' => $currentUser['user_id']]);

        if (!$ownStmt->fetch()) {
            mh_json_response('error', 'No eres el conductor asignado a este show.', [], 403);
        }

        $conductorNotes    = trim((string) ($payload['conductor_notes'] ?? ''));
        $affiliatedChannel = trim((string) ($payload['affiliated_channel'] ?? ''));

        // Redes sociales del show (Fase 5.11): reutiliza la misma columna
        // JSON `public_social_links` que ya usa create_native (Admin) --
        // aqui el Conductor edita las propias. El body llega como JSON
        // real (no multipart), asi que el array ya viene decodificado por
        // mh_read_json_body(); solo se valida que sea un arreglo antes de
        // volver a codificarlo (una cadena plana rompe la columna JSON).
        $socialLinks = $payload['public_social_links'] ?? [];
        if (!is_array($socialLinks)) {
            mh_json_response('error', 'Formato invalido para redes sociales.', [], 422);
        }

        $stmt = $pdo->prepare(
            'UPDATE programs
             SET conductor_notes = :conductor_notes, affiliated_channel = :affiliated_channel,
                 public_social_links = :public_social_links
             WHERE id = :id'
        );
        $stmt->execute([
            'conductor_notes'     => $conductorNotes !== '' ? $conductorNotes : null,
            'affiliated_channel'  => $affiliatedChannel !== '' ? $affiliatedChannel : null,
            'public_social_links' => $socialLinks !== [] ? json_encode($socialLinks, JSON_UNESCAPED_UNICODE) : null,
            'id'                  => $programId,
        ]);

        mh_json_response('success', 'Perfil del show actualizado correctamente.');
    }

    mh_json_response('error', 'Accion o metodo no soportado.', [], 405);
} catch (Throwable $e) {
    // Puente de diagnostico forense (mismo patron que api/login.php): un
    // catch(PDOException) NO detiene Error/TypeError no capturados (ej.
    // finfo no disponible, argumento invalido) -- esos se colaban como
    // 500 sin rastro. Throwable cubre Exception + Error; con
    // MH_DEBUG_TOKEN revela el mensaje real, sin token degrada seguro.
    error_log('MH-CORE error en api/programs.php: ' . get_class($e) . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());

    $expectedToken = mh_app_env('MH_DEBUG_TOKEN', '');
    $providedToken = (string) ($_GET['debug_token'] ?? '');

    if ($expectedToken !== '' && hash_equals($expectedToken, $providedToken)) {
        mh_json_response('error', get_class($e) . ': ' . $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine(), [], 500);
    }

    mh_json_response('error', 'Error interno del servidor. Intenta mas tarde.', [], 500);
}
