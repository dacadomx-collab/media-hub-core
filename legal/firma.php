<?php
/**
 * MH-CORE: Firma Digital Obligatoria de Reglamentos.
 * Bloquea por completo el Dashboard hasta que el usuario lea y acepte
 * los 4 reglamentos inmutables (banderas en `user_legal_signatures`).
 *
 * El procesamiento del POST vive en api/signature.php; esta pagina solo
 * renderiza el formulario y muestra el error devuelto (si lo hay).
 */

session_start();

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../api/csrf.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$pdo = Database::getInstance()->getConnection();
$userId = (int) $_SESSION['user_id'];

$errorMessages = [
    'mismatch' => 'Debes marcar todos los reglamentos y escribir tu nombre completo exactamente como aparece en tu cuenta.',
    'csrf'     => 'Tu sesion expiro. Vuelve a iniciar sesion para continuar.',
];
$errorMessage = $errorMessages[$_GET['error'] ?? ''] ?? '';

// Fase 5.8 — Handshake Legal Condicional: solo se renderizan los
// documentos que legal_document_roles exige para el rol de la sesion
// (un Conductor no debe ver/firmar contratos internos de staff).
$stmt = $pdo->prepare(
    'SELECT d.id, d.code, d.title, d.content, d.version, s.signed
     FROM legal_documents d
     JOIN user_legal_signatures s ON s.document_id = d.id
     INNER JOIN legal_document_roles ldr ON ldr.document_id = d.id AND ldr.role = :role
     WHERE s.user_id = :user_id
     ORDER BY d.sort_order ASC'
);
$stmt->execute(['user_id' => $userId, 'role' => (string) $_SESSION['role']]);
$documents = $stmt->fetchAll();

$pendingDocuments = array_filter($documents, static fn ($doc) => (int) $doc['signed'] === 0);

if (count($pendingDocuments) === 0) {
    header('Location: ../dashboard/index.php');
    exit;
}

$csrfToken = csrf_token();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media HUB | Firma Digital de Reglamentos</title>
    <meta name="description" content="Lectura y firma digital obligatoria de reglamentos Media HUB">
    <link rel="icon" type="image/png" href="../assets/img/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/legal.css">
</head>
<body>
    <main class="viewport legal-viewport" aria-labelledby="legal-title">
        <section class="login-shell legal-shell" role="region" aria-label="Firma digital de reglamentos">
            <div class="brand-block">
                <div class="brand-mark" aria-hidden="true">
                    <img src="../assets/img/logo.png" alt="Isotipo Media HUB" loading="eager" decoding="async">
                </div>
                <p class="brand-label">MEDIA HUB</p>
            </div>

            <header class="panel-header">
                <h1 id="legal-title">Firma Digital Obligatoria</h1>
                <p>Hola <?php echo htmlspecialchars((string) $_SESSION['full_name'], ENT_QUOTES, 'UTF-8'); ?>, antes de continuar debes leer y aceptar los siguientes reglamentos.</p>
            </header>

            <?php if ($errorMessage !== ''): ?>
                <p class="system-message error"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>

            <form class="legal-form" method="post" action="../api/signature.php" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

                <div class="legal-doc-list">
                    <?php foreach ($pendingDocuments as $doc): ?>
                        <details class="legal-doc">
                            <summary>
                                <?php echo htmlspecialchars($doc['title'], ENT_QUOTES, 'UTF-8'); ?>
                                <span class="legal-doc-version">v<?php echo htmlspecialchars($doc['version'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </summary>
                            <div class="legal-doc-content">
                                <p><?php echo nl2br(htmlspecialchars($doc['content'], ENT_QUOTES, 'UTF-8')); ?></p>
                            </div>
                            <label class="legal-ack">
                                <input type="checkbox" name="ack[<?php echo (int) $doc['id']; ?>]" value="1" required>
                                <span>He leido y acepto: <?php echo htmlspecialchars($doc['title'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </label>
                        </details>
                    <?php endforeach; ?>
                </div>

                <label for="signature_name">Firma digital (escribe tu nombre completo)</label>
                <div class="field-wrap">
                    <input type="text" id="signature_name" name="signature_name" autocomplete="name" placeholder="Nombre y apellidos" required>
                    <span class="field-wave" aria-hidden="true"></span>
                </div>
                <small class="field-message">Debe coincidir con el nombre registrado en tu cuenta: <?php echo htmlspecialchars((string) $_SESSION['full_name'], ENT_QUOTES, 'UTF-8'); ?></small>

                <button type="submit" id="legalSubmitBtn">Firmar y Continuar al Dashboard</button>
            </form>
        </section>
    </main>
    <script>
        // Anti Double-Click (Regla de Oro Global): el envio de este formulario
        // navega a otra pagina, pero un doble clic aun puede duplicar el POST
        // antes de que el navegador redirija.
        document.querySelector('.legal-form').addEventListener('submit', function () {
            var btn = document.getElementById('legalSubmitBtn');
            btn.disabled = true;
            btn.textContent = 'Procesando...';
        });
    </script>
</body>
</html>
