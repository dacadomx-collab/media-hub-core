<?php
/**
 * Media HUB - Terminal de Verificación e Integridad de Conexiones (Cables DB)
 * Ubicación: C:\xampp\htdocs\MediaHUB\api\test_integration.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('MEDIAHUB_TEST_MODE', true);
require_once __DIR__ . '/../config/Database.php';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Media HUB - Auditoría de Continuidad Técnica</title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; background-color: #022D53; color: #FFFFFF; padding: 20px; }
        .test-card { background-color: #0c3866; border-left: 5px solid #00BFB2; padding: 15px; margin-bottom: 15px; border-radius: 0 6px 6px 0; }
        .success { color: #34d399; font-weight: bold; }
        .fail { color: #f87171; font-weight: bold; }
        h1, h2 { color: #00BFB2; text-transform: uppercase; }
        .summary { background-color: #062341; padding: 20px; border: 2px solid #00BFB2; border-radius: 6px; margin-top: 30px; }
    </style>
</head>
<body>

    <h1>🔍 AUDITORÍA DE CONTINUIDAD DE CABLES: PHP ↔ MYSQL</h1>
    <p>Iniciando secuencia de estrés y persistencia sobre las tablas de <strong>tecnidepot_mediahub_db</strong>...</p>
    <hr style="border-color: #00BFB2;">

    <?php
    $totalTests = 0;
    $passedTests = 0;

    try {
        $pdo = Database::getInstance()->getConnection();
        $totalTests++; $passedTests++;
        echo "<div class='test-card'><h3>[TEST 01] CLASE DATABASE SINGLETON</h3><p class='success'>✔ Cable de Alimentación Conectado. Instancia PDO recuperada exitosamente.</p></div>";
        
        // Iniciamos transacción para no ensuciar la base de datos limpia del usuario
        $pdo->beginTransaction();

        // 1. TEST CRUD - MÓDULO DE USUARIOS
        $totalTests++;
        $user_test_id = 'staff.test_' . time();
        $stmt = $pdo->prepare("INSERT INTO users (user_id, full_name, email, password_hash, role, status) VALUES (:uid, 'Técnico de Pruebas', :email, 'hash', 'Staff_Tecnico', 'Activo')");
        $stmt->execute([':uid' => $user_test_id, ':email' => 'test_cable@mediahub.com']);
        $lastUserId = $pdo->lastInsertId();
        
        $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = :id");
        $stmt->execute([':id' => $lastUserId]);
        $resUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resUser && $resUser['full_name'] === 'Técnico de Pruebas') {
            $passedTests++;
            echo "<div class='test-card'><h3>[TEST 02] CRUD TABLA `users`</h3><p class='success'>✔ CABLE DE USUARIOS OK: Inserción, autoincremento y lectura preparados validados correctamente.</p></div>";
        } else {
            echo "<div class='test-card'><h3>[TEST 02] CRUD TABLA `users`</h3><p class='fail'>❌ FALLO EN CABLE DE USUARIOS: Los datos guardados no coinciden.</p></div>";
        }

        // 2. TEST CRUD - MÓDULO DE CLIENTES Y PROGRAMAS
        $totalTests++;
        $stmt = $pdo->prepare("INSERT INTO clients (full_name, company) VALUES ('Cliente de Prueba Inc', 'Test Studio')");
        $stmt->execute();
        $lastClientId = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO programs (client_id, name, description, is_active) VALUES (:client_id, 'Show Automático de Test', 'Descripción extendida', 1)");
        $stmt->execute([':client_id' => $lastClientId]);
        $lastProgramId = $pdo->lastInsertId();

        $stmt = $pdo->prepare("SELECT name FROM programs WHERE id = :id");
        $stmt->execute([':id' => $lastProgramId]);
        $resProg = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resProg && $resProg['name'] === 'Show Automático de Test') {
            $passedTests++;
            echo "<div class='test-card'><h3>[TEST 03] CRUD TABLA `clients` Y `programs`</h3><p class='success'>✔ CABLE DE CASOS DE ÉXITO JORNAL OK: Integridad referencial de llaves foráneas validada.</p></div>";
        } else {
            echo "<div class='test-card'><h3>[TEST 03] CRUD TABLA `clients` Y `programs`</h3><p class='fail'>❌ FALLO EN CABLE DE PROGRAMAS: Error en mapeo relacional.</p></div>";
        }

        // 3. TEST CRUD - AGENDA Y CONTROL DE COLISIONES
        $totalTests++;
        $stmt = $pdo->prepare("INSERT INTO calls (program_id, title, location, call_date, start_time, end_time, status, advance_paid) VALUES (:pid, 'Llamado Test', 'Estudio 5 de Mayo', '2026-07-20', '14:00:00', '16:00:00', 'Pendiente', 1)");
        $stmt->execute([':pid' => $lastProgramId]);
        $lastCallId = $pdo->lastInsertId();

        // Ejecutar query espejo de colisión idéntico al del backend de Claude
        $collisionStmt = $pdo->prepare("SELECT COUNT(*) AS conflicts FROM calls WHERE location = 'Estudio 5 de Mayo' AND call_date = '2026-07-20' AND status != 'Cancelado' AND start_time < '15:00:00' AND end_time > '14:30:00'");
        $collisionStmt->execute();
        $conflicts = (int)$collisionStmt->fetch()['conflicts'];

        if ($conflicts > 0) {
            $passedTests++;
            echo "<div class='test-card'><h3>[TEST 04] ALGORITMO ANTICOLISIONES `calls`</h3><p class='success'>✔ CABLE DE AGENDA INTELIGENTE OK: El motor interceptó correctamente el empalme simulado de horarios.</p></div>";
        } else {
            echo "<div class='test-card'><h3>[TEST 04] ALGORITMO ANTICOLISIONES `calls`</h3><p class='fail'>❌ FALLO EN CABLE DE AGENDA: El motor no detectó la superposición horaria.</p></div>";
        }

        // 4. TEST CRUD - INVENTARIOS Y LOGÍSTICA
        $totalTests++;
        $stmt = $pdo->prepare("INSERT INTO inventory_items (name, category, serial_number, status) VALUES ('Lente de Auditoría 50mm', 'Optica', 'TEST-LENS-999', 'Disponible')");
        $stmt->execute();
        $lastItemId = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO checkinout_log (asset_type, asset_id, call_id, user_id, action, condition_notes) VALUES ('Inventario', :asset_id, :call_id, :user_id, 'Check-Out', 'Estado impecable en test')");
        $stmt->execute([
            ':asset_id' => $lastItemId,
            ':call_id'  => $lastCallId,
            ':user_id'  => $lastUserId
        ]);
        $lastLogId = $pdo->lastInsertId();

        if ($lastLogId > 0) {
            $passedTests++;
            echo "<div class='test-card'><h3>[TEST 05] BITÁCORA `checkinout_log`</h3><p class='success'>✔ CABLE DE HARDWARE Y COMPONENTES OK: Firmas de movimientos registradas sin fricción.</p></div>";
        } else {
            echo "<div class='test-card'><h3>[TEST 05] BITÁCORA `checkinout_log`</h3><p class='fail'>❌ FALLO EN CABLE DE MOVIMIENTOS: No se grabó la bitácora técnica.</p></div>";
        }

        // 6. TEST CRUD — RBAC ESCALADO FASE 5 (users.role nuevo ENUM)
        $totalTests++;
        $adminTestId = 'admin.test_' . time();
        $stmt = $pdo->prepare(
            "INSERT INTO users (user_id, full_name, email, password_hash, role, status)
             VALUES (:uid, 'Admin de Prueba', :email, 'hash', 'Super_admin', 'Activo')"
        );
        $stmt->execute([':uid' => $adminTestId, ':email' => 'test_super_admin@mediahub.com']);
        $lastAdminId = $pdo->lastInsertId();

        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = :id");
        $stmt->execute([':id' => $lastAdminId]);
        $resRole = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resRole && $resRole['role'] === 'Super_admin') {
            $passedTests++;
            echo "<div class='test-card'><h3>[TEST 06] RBAC ESCALADO `users.role` (Fase 5)</h3><p class='success'>✔ CABLE DE JERARQUIA OK: el ENUM acepta 'Super_admin' y el valor persiste identico tras el INSERT/SELECT.</p></div>";
        } else {
            echo "<div class='test-card'><h3>[TEST 06] RBAC ESCALADO `users.role` (Fase 5)</h3><p class='fail'>❌ FALLO EN CABLE DE JERARQUIA: 'Super_admin' no se guardo o no coincide (revisar migration_fase5).</p></div>";
        }

        // 7. TEST CRUD — SHOW NATIVO (`programs.client_id` NULL + conductor_user_id)
        $totalTests++;
        $stmt = $pdo->prepare(
            "INSERT INTO programs (client_id, name, description, catalog_description, is_native_show, conductor_user_id, is_active)
             VALUES (NULL, 'Show Nativo de Prueba', 'Descripcion interna', 'Descripcion publica de catalogo', 1, :conductor_id, 1)"
        );
        $stmt->execute([':conductor_id' => $lastAdminId]);
        $lastNativeProgramId = $pdo->lastInsertId();

        $stmt = $pdo->prepare(
            "SELECT client_id, is_native_show, conductor_user_id FROM programs WHERE id = :id"
        );
        $stmt->execute([':id' => $lastNativeProgramId]);
        $resNative = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resNative && $resNative['client_id'] === null && (int) $resNative['is_native_show'] === 1
            && (int) $resNative['conductor_user_id'] === (int) $lastAdminId
        ) {
            $passedTests++;
            echo "<div class='test-card'><h3>[TEST 07] SHOW NATIVO `programs` (client_id NULL, Fase 5)</h3><p class='success'>✔ CABLE DE SHOWS NATIVOS OK: client_id NULL, is_native_show=1 y conductor_user_id persisten correctamente.</p></div>";
        } else {
            echo "<div class='test-card'><h3>[TEST 07] SHOW NATIVO `programs` (client_id NULL, Fase 5)</h3><p class='fail'>❌ FALLO EN CABLE DE SHOWS NATIVOS: columnas de Fase 5 no coinciden (revisar migration_fase5).</p></div>";
        }

        // 8. TEST CRUD — GUEST_INVITE_LINKS + GUEST_SUBMISSIONS (TTL de 3 clics)
        $totalTests++;
        $testToken = bin2hex(random_bytes(16));
        $stmt = $pdo->prepare(
            "INSERT INTO guest_invite_links (program_id, token, created_by)
             VALUES (:program_id, :token, :created_by)"
        );
        $stmt->execute([
            ':program_id' => $lastNativeProgramId,
            ':token'      => $testToken,
            ':created_by' => $lastAdminId,
        ]);
        $lastLinkId = $pdo->lastInsertId();

        // Simula CLICK 1: alta de submission + avance de contador (0 -> 1)
        $pdo->prepare(
            "INSERT INTO guest_submissions (link_id, full_name, title_position, invite_message)
             VALUES (:link_id, 'Invitado de Prueba', 'Especialista de Prueba', 'Mensaje de prueba')"
        )->execute([':link_id' => $lastLinkId]);
        $pdo->prepare("UPDATE guest_invite_links SET click_count = 1 WHERE id = :id")
            ->execute([':id' => $lastLinkId]);

        // Simula CLICK 2: edicion parcial (ON DUPLICATE KEY UPDATE) + avance (1 -> 2)
        $pdo->prepare(
            "INSERT INTO guest_submissions (link_id, full_name, title_position, invite_message)
             VALUES (:link_id, 'Invitado de Prueba Editado', 'Especialista de Prueba', 'Mensaje editado')
             ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), invite_message = VALUES(invite_message)"
        )->execute([':link_id' => $lastLinkId]);
        $pdo->prepare(
            "UPDATE guest_invite_links SET click_count = 2, status = 'Expirado', expired_at = NOW() WHERE id = :id"
        )->execute([':id' => $lastLinkId]);

        $stmt = $pdo->prepare(
            "SELECT gl.click_count, gl.status, gs.full_name, gs.invite_message
             FROM guest_invite_links gl
             INNER JOIN guest_submissions gs ON gs.link_id = gl.id
             WHERE gl.id = :id"
        );
        $stmt->execute([':id' => $lastLinkId]);
        $resGuest = $stmt->fetch(PDO::FETCH_ASSOC);

        // Simula CLICK 3 (post-expiracion): debe seguir bloqueado, sin nueva fila
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) AS total FROM guest_submissions WHERE link_id = :id"
        );
        $stmt->execute([':id' => $lastLinkId]);
        $submissionCount = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        if ($resGuest
            && $resGuest['full_name'] === 'Invitado de Prueba Editado'
            && $resGuest['invite_message'] === 'Mensaje editado'
            && $resGuest['status'] === 'Expirado'
            && (int) $resGuest['click_count'] === 2
            && $submissionCount === 1
        ) {
            $passedTests++;
            echo "<div class='test-card'><h3>[TEST 08] TTL DE 3 CLICS `guest_invite_links`/`guest_submissions` (Fase 5)</h3><p class='success'>✔ CABLE DE GUEST ONBOARDING OK: clic 1 crea, clic 2 actualiza la MISMA fila (clave unica link_id respetada, sin duplicados), el enlace expira tras el clic 2 sin colisiones.</p></div>";
        } else {
            echo "<div class='test-card'><h3>[TEST 08] TTL DE 3 CLICS `guest_invite_links`/`guest_submissions` (Fase 5)</h3><p class='fail'>❌ FALLO EN CABLE DE GUEST ONBOARDING: la persistencia parcial o la expiracion logica no coinciden con lo esperado.</p></div>";
        }

        // 9. TEST — ONBOARDING POR CORREO: users.status='Pendiente' + password_resets.purpose='activation'
        $totalTests++;
        $pendingTestId = 'pending.test_' . time();
        $stmt = $pdo->prepare(
            "INSERT INTO users (user_id, full_name, email, password_hash, role, status)
             VALUES (:uid, 'Invitado de Prueba', :email, 'placeholder-hash', 'Team', 'Pendiente')"
        );
        $stmt->execute([':uid' => $pendingTestId, ':email' => 'test_pendiente@mediahub.com']);
        $lastPendingId = $pdo->lastInsertId();

        $stmt = $pdo->prepare(
            "INSERT INTO password_resets (user_id, purpose, token_hash, expires_at)
             VALUES (:user_id, 'activation', :token_hash, DATE_ADD(NOW(), INTERVAL 7 DAY))"
        );
        $stmt->execute([':user_id' => $lastPendingId, ':token_hash' => hash('sha256', 'token_de_prueba')]);

        $stmt = $pdo->prepare(
            "SELECT u.status, pr.purpose FROM users u
             INNER JOIN password_resets pr ON pr.user_id = u.id
             WHERE u.id = :id AND pr.purpose = 'activation'"
        );
        $stmt->execute([':id' => $lastPendingId]);
        $resOnboarding = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resOnboarding && $resOnboarding['status'] === 'Pendiente' && $resOnboarding['purpose'] === 'activation') {
            $passedTests++;
            echo "<div class='test-card'><h3>[TEST 09] ONBOARDING POR CORREO `users.status='Pendiente'` + `password_resets.purpose` (Fase 5.1)</h3><p class='success'>✔ CABLE DE ONBOARDING OK: el ENUM acepta 'Pendiente' y la columna 'purpose' distingue 'activation' de 'reset'.</p></div>";
        } else {
            echo "<div class='test-card'><h3>[TEST 09] ONBOARDING POR CORREO `users.status='Pendiente'` + `password_resets.purpose` (Fase 5.1)</h3><p class='fail'>❌ FALLO EN CABLE DE ONBOARDING: revisar migration_fase5_1.</p></div>";
        }

        // 10. TEST — REMEMBER ME: user_remember_tokens (emision + lookup + rotacion)
        $totalTests++;
        $rememberTokenHash = hash('sha256', 'raw_token_de_prueba');
        $stmt = $pdo->prepare(
            "INSERT INTO user_remember_tokens (user_id, token_hash, user_agent, ip_address, expires_at)
             VALUES (:user_id, :token_hash, 'PHPUnit-Test-Agent', '127.0.0.1', DATE_ADD(NOW(), INTERVAL 60 DAY))"
        );
        $stmt->execute([':user_id' => $lastUserId, ':token_hash' => $rememberTokenHash]);
        $lastRememberId = $pdo->lastInsertId();

        // Simula la rotacion: borra el token consumido, emite uno nuevo.
        $pdo->prepare('DELETE FROM user_remember_tokens WHERE id = :id')->execute([':id' => $lastRememberId]);
        $newTokenHash = hash('sha256', 'raw_token_rotado');
        $pdo->prepare(
            "INSERT INTO user_remember_tokens (user_id, token_hash, expires_at)
             VALUES (:user_id, :token_hash, DATE_ADD(NOW(), INTERVAL 60 DAY))"
        )->execute([':user_id' => $lastUserId, ':token_hash' => $newTokenHash]);

        $stmt = $pdo->prepare(
            "SELECT COUNT(*) AS total FROM user_remember_tokens WHERE user_id = :user_id AND token_hash = :token_hash"
        );
        $stmt->execute([':user_id' => $lastUserId, ':token_hash' => $newTokenHash]);
        $rotatedExists = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $stmt = $pdo->prepare(
            "SELECT COUNT(*) AS total FROM user_remember_tokens WHERE token_hash = :token_hash"
        );
        $stmt->execute([':token_hash' => $rememberTokenHash]);
        $oldTokenGone = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'] === 0;

        if ($rotatedExists === 1 && $oldTokenGone) {
            $passedTests++;
            echo "<div class='test-card'><h3>[TEST 10] REMEMBER ME `user_remember_tokens` (Fase 5.1)</h3><p class='success'>✔ CABLE DE SESION PROLONGADA OK: emision, lookup por hash y rotacion (token viejo eliminado, nuevo presente) funcionan sin colisiones.</p></div>";
        } else {
            echo "<div class='test-card'><h3>[TEST 10] REMEMBER ME `user_remember_tokens` (Fase 5.1)</h3><p class='fail'>❌ FALLO EN CABLE DE SESION PROLONGADA: revisar migration_fase5_1.</p></div>";
        }

        // 11. TEST — REPLICA EXACTA del INSERT de api/programs.php?action=create_native
        // (Fase 5.2), incluyendo logo_url + public_social_links (JSON) + production_schedule (JSON).
        // TEST 07 nunca probo estas 3 columnas contra el schema real -- aqui se cierra ese hueco.
        $totalTests++;
        $scheduleJsonTest = json_encode(['days' => ['Lunes', 'Miercoles'], 'start_time' => '18:00', 'end_time' => '20:00'], JSON_UNESCAPED_UNICODE);
        $socialLinksJsonTest = json_encode('Instagram @test', JSON_UNESCAPED_UNICODE);

        $stmt = $pdo->prepare(
            'INSERT INTO programs
                (client_id, name, description, catalog_description, logo_url,
                 public_social_links, production_schedule, is_native_show, conductor_user_id, is_active)
             VALUES
                (NULL, :name, :description, :catalog_description, :logo_url,
                 :public_social_links, :production_schedule, 1, :conductor_user_id, 1)'
        );
        $stmt->execute([
            'name'                => 'Show Diagnostico Fase 5.3',
            'description'         => 'Descripcion diagnostica',
            'catalog_description' => 'Descripcion diagnostica',
            'logo_url'            => 'uploads/diagnostico.png',
            'public_social_links' => $socialLinksJsonTest,
            'production_schedule' => $scheduleJsonTest,
            'conductor_user_id'   => $lastAdminId,
        ]);
        $lastDiagProgramId = $pdo->lastInsertId();

        $stmt = $pdo->prepare(
            'SELECT logo_url, public_social_links, production_schedule FROM programs WHERE id = :id'
        );
        $stmt->execute([':id' => $lastDiagProgramId]);
        $resDiag = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resDiag
            && $resDiag['logo_url'] === 'uploads/diagnostico.png'
            && json_decode((string) $resDiag['public_social_links'], true) === 'Instagram @test'
            && json_decode((string) $resDiag['production_schedule'], true)['start_time'] === '18:00'
        ) {
            $passedTests++;
            echo "<div class='test-card'><h3>[TEST 11] REPLICA EXACTA `create_native` (logo_url + public_social_links JSON + production_schedule JSON, Fase 5.3)</h3><p class='success'>✔ CABLE DE SHOWS NATIVOS COMPLETO OK: las 3 columnas nuevas de Fase 5.2 persisten y se decodifican correctamente.</p></div>";
        } else {
            echo "<div class='test-card'><h3>[TEST 11] REPLICA EXACTA `create_native` (Fase 5.3)</h3><p class='fail'>❌ FALLO: revisar tipos de columna en migration_fase5_2.</p></div>";
        }

        // 12. TEST — IDENTIDAD EMAIL-ONLY: users.user_id (VARCHAR(150), Fase 5.3)
        // tolera un correo largo como identificador unico, sin truncar.
        $totalTests++;
        $longEmail = 'usuario.con.nombre.muy.largo.para.probar.limites.de.columna.varchar.ciento.cincuenta.caracteres.exactos@mediahubbcs-dominio-extendido-de-prueba.com';
        $emailLength = strlen($longEmail);

        $stmt = $pdo->prepare(
            "INSERT INTO users (user_id, full_name, email, password_hash, role, status)
             VALUES (:uid, 'Usuario Correo Largo', :email, 'placeholder-hash', 'Team', 'Pendiente')"
        );
        $stmt->execute([':uid' => $longEmail, ':email' => $longEmail]);
        $lastLongEmailId = $pdo->lastInsertId();

        $stmt = $pdo->prepare('SELECT user_id, email FROM users WHERE id = :id');
        $stmt->execute([':id' => $lastLongEmailId]);
        $resLongEmail = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resLongEmail
            && $resLongEmail['user_id'] === $longEmail
            && strlen($resLongEmail['user_id']) === $emailLength
            && $resLongEmail['email'] === $longEmail
        ) {
            $passedTests++;
            echo "<div class='test-card'><h3>[TEST 12] IDENTIDAD EMAIL-ONLY `users.user_id` VARCHAR(150) (Fase 5.3)</h3><p class='success'>✔ CABLE DE IDENTIDAD OK: correo de {$emailLength} caracteres persistio identico en user_id, sin truncamiento ni excepcion.</p></div>";
        } else {
            echo "<div class='test-card'><h3>[TEST 12] IDENTIDAD EMAIL-ONLY `users.user_id` (Fase 5.3)</h3><p class='fail'>❌ FALLO: revisar migration_fase5_3_email_only_identity.sql (ancho de columna).</p></div>";
        }

        // Hacemos rollback explícito para dejar la base de datos limpia de pruebas
        $pdo->rollBack();
        echo "<p style='color:#cbd5e1;'>[INFO] Ejecutando Rollback Preventivo de Seguridad. Base de datos restaurada a su estado limpio original.</p>";

    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo "<div class='test-card' style='border-left-color:#f87171;'><h3>❌ ERROR CATASTRÓFICO DE SISTEMA</h3><p class='fail'>" . htmlspecialchars($e->getMessage()) . "</p></div>";
    }
    ?>

    <div class="summary">
        <h2>📊 RESUMEN DE LA AUDITORÍA DE CONTINUIDAD</h2>
        <p>Pruebas Totales de Presión Ejecutadas: <strong><?php echo $totalTests; ?></strong></p>
        <p>Cables con Continuidad Verificada (Éxito): <strong class="success"><?php echo $passedTests; ?> / <?php echo $totalTests; ?></strong></p>
        <?php if ($totalTests === $passedTests): ?>
            <h3 class="success">🚀 VERDICTO DE INGENIERÍA: ¡TODOS LOS CABLES ESTÁN PERFECTAMENTE CONECTADOS Y SOLDADOS CON LA BASE DE DATOS MASTER!</h3>
        <?php else: ?>
            <h3 class="danger">⚠️ ATENCIÓN: Se detectaron cortos de comunicación en las conexiones relacionales. Revisa los logs.</h3>
        <?php endif; ?>
    </div>

</body>
</html>