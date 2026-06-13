<?php
/**
 * MH-CORE: Procesador de Firma Digital de Reglamentos.
 * Recibe el POST del formulario en legal/firma.php, valida CSRF y
 * Troll Mode, verifica que todos los reglamentos pendientes hayan sido
 * marcados y que el nombre de firma coincida con el usuario en sesion.
 *
 * Ubicacion: api/signature.php (un nivel bajo la raiz). Todas las
 * redirecciones son relativas a esta carpeta.
 */

session_start();

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/security.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../legal/firma.php');
    exit;
}

mh_guard_request($_POST, 'legal_firma');

if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    header('Location: ../index.php?error=csrf');
    exit;
}

$pdo = Database::getInstance()->getConnection();
$userId = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare(
    'SELECT d.id, d.code, d.title
     FROM legal_documents d
     JOIN user_legal_signatures s ON s.document_id = d.id
     WHERE s.user_id = :user_id AND s.signed = 0'
);
$stmt->execute(['user_id' => $userId]);
$pendingDocs = $stmt->fetchAll();

$ack = $_POST['ack'] ?? [];
$signatureName = trim((string) ($_POST['signature_name'] ?? ''));

$allAccepted = true;
foreach ($pendingDocs as $doc) {
    if (empty($ack[$doc['id']])) {
        $allAccepted = false;
        break;
    }
}

$signatureMatches = $signatureName !== ''
    && mb_strtolower($signatureName) === mb_strtolower((string) $_SESSION['full_name']);

if (!$allAccepted || !$signatureMatches) {
    header('Location: ../legal/firma.php?error=mismatch');
    exit;
}

$update = $pdo->prepare(
    'UPDATE user_legal_signatures
     SET signed = 1, signed_at = NOW(), ip_address = :ip
     WHERE user_id = :user_id AND signed = 0'
);
$update->execute([
    'ip'      => $_SERVER['REMOTE_ADDR'] ?? null,
    'user_id' => $userId,
]);

header('Location: ../dashboard/index.php');
exit;
