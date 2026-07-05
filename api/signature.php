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

// Fase 5.8 — Handshake Legal Condicional: solo se exigen/actualizan los
// documentos que legal_document_roles asigna al rol de la sesion (debe
// coincidir exactamente con el filtro de legal/firma.php).
$role = (string) $_SESSION['role'];

$stmt = $pdo->prepare(
    'SELECT d.id, d.code, d.title
     FROM legal_documents d
     JOIN user_legal_signatures s ON s.document_id = d.id
     INNER JOIN legal_document_roles ldr ON ldr.document_id = d.id AND ldr.role = :role
     WHERE s.user_id = :user_id AND s.signed = 0'
);
$stmt->execute(['user_id' => $userId, 'role' => $role]);
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

// Solo se marcan como firmados los documentos que legal_document_roles
// exige para este rol -- no todos los signed=0 del usuario -- para no
// firmar "de facto" un documento que ni siquiera se le mostro.
$update = $pdo->prepare(
    'UPDATE user_legal_signatures uls
     INNER JOIN legal_document_roles ldr ON ldr.document_id = uls.document_id AND ldr.role = :role
     SET uls.signed = 1, uls.signed_at = NOW(), uls.ip_address = :ip, uls.signer_full_name = :signer_full_name
     WHERE uls.user_id = :user_id AND uls.signed = 0'
);
$update->execute([
    'role'              => $role,
    'ip'                => $_SERVER['REMOTE_ADDR'] ?? null,
    'signer_full_name'  => $signatureName,
    'user_id'           => $userId,
]);

header('Location: ../dashboard/index.php');
exit;
