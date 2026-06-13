<?php
/**
 * Media HUB - Sistema de Inicialización del Primer Super Administrador
 * Sincronizado al Schema de la Fase 1 (Claude Core Layout)
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('MEDIAHUB_SETUP', true);
$db_path = __DIR__ . '/config/Database.php';

if (!file_exists($db_path)) {
    die("Error Crítico: No se encontró config/Database.php");
}

require_once $db_path;
$message = "";
$status = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email  = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $pass   = $_POST['password'] ?? '';
    $pin    = $_POST['security_pin'] ?? '';

    if ($pin !== 'MH_GENESIS_2026') {
        $message = "Código PIN de instalación incorrecto.";
        $status = "error";
    } elseif (empty($nombre) || !$email || empty($pass)) {
        $message = "Todos los campos son obligatorios y el email debe ser válido.";
        $status = "error";
    } else {
        try {
            $db = Database::getInstance()->getConnection();
            $db->beginTransaction();

            // Evitar duplicados de administradores
            $check = $db->prepare("SELECT id FROM users WHERE role = 'Administrador' LIMIT 1");
            $check->execute();
            if ($check->fetch()) {
                throw new Exception("Ya existe un Administrador en el sistema.");
            }

            // Crear el identificador único string que pide el campo user_id de Claude
            $user_string_id = 'admin.' . strtolower(explode(' ', $nombre)[0]);
            $password_hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);

            // Inserción exacta con los campos del nuevo schema.sql
            $stmtUser = $db->prepare("INSERT INTO users (user_id, full_name, email, password_hash, role, status) VALUES (:uid, :name, :email, :pass, 'Administrador', 'Activo')");
            $stmtUser->execute([
                ':uid'   => $user_string_id,
                ':name'  => $nombre,
                ':email' => $email,
                ':pass'  => $password_hash
            ]);

            $user_table_id = $db->lastInsertId();

            // Insertar de manera relacional las firmas de los 4 documentos en 1 (Firmado) para el Admin
            $stmtSign = $db->prepare("INSERT INTO user_legal_signatures (user_id, document_id, signed, signed_at, ip_address) 
                                      SELECT :user_id, id, 1, NOW(), '127.0.0.1' FROM legal_documents");
            $stmtSign->execute([
                ':user_id' => $user_table_id
            ]);

            $db->commit();
            $message = "¡Super Administrador raíz activado! Identificador asignado: $user_string_id. Redireccionando...";
            $status = "success";

        } catch (Exception $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            $message = "Error en base de datos: " . $e->getMessage();
            $status = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media HUB - Alta Inicial Sincronizada</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #022D53; color: #FFFFFF; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .container { background-color: #0c3866; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.3); width: 100%; max-width: 400px; box-sizing: border-box; }
        h2 { color: #00BFB2; text-align: center; margin-top: 0; text-transform: uppercase; letter-spacing: 1px; font-size: 20px; }
        label { display: block; margin: 12px 0 5px 0; font-size: 14px; color: #e2e8f0; }
        input { width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #00BFB2; background-color: #022D53; color: #FFFFFF; box-sizing: border-box; }
        .btn { background-color: #00BFB2; color: #022D53; font-weight: bold; padding: 12px; border: none; border-radius: 4px; width: 100%; cursor: pointer; margin-top: 20px; text-transform: uppercase; }
        .alert { padding: 12px; margin-bottom: 15px; border-radius: 4px; font-size: 14px; text-align: center; font-weight: bold; }
        .alert-error { background-color: #f87171; color: #7f1d1d; }
        .alert-success { background-color: #34d399; color: #064e3b; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Alta de Super Admin V2</h2>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $status === 'success' ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php if ($status === 'success'): ?>
                <script>
                    setTimeout(function(){ window.location.href = 'index.php'; }, 3000);
                </script>
                <?php @unlink(__FILE__); exit(); ?>
            <?php endif; ?>
        <?php endif; ?>

        <form method="POST" action="">
            <label>PIN de Instalación Técnica:</label>
            <input type="password" name="security_pin" placeholder="Ingresa el PIN maestro" required>

            <label>Nombre Completo del Administrador:</label>
            <input type="text" name="nombre" placeholder="Tu Nombre y Apellido" required>

            <label>Correo Electrónico:</label>
            <input type="email" name="email" placeholder="correo@tuempresa.com" required>

            <label>Contraseña Segura:</label>
            <input type="password" name="password" placeholder="Mínimo 8 caracteres" required>

            <button type="submit" class="btn">Inicializar Ecosistema</button>
        </form>
    </div>
</body>
</html>