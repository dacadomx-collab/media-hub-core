<?php
// Carga manual de .env para la prueba rápida
$env = parse_ini_file('.env');

try {
    $dsn = "mysql:host={$env['DB_HOST']};dbname={$env['DB_NAME']};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    
    $pdo = new PDO($dsn, $env['DB_USER'], $env['DB_PASS'], $options);
    
    // Si llegamos aquí, la conexión es exitosa
    echo "<div style='background:#022D53; color:#00BFB2; padding:20px; font-family:Montserrat, sans-serif; border:2px solid #00BFB2;'>";
    echo "🏗️ MEDIA HUB: Conexión establecida con el Estándar Oro.";
    echo "</div>";

} catch (PDOException $e) {
    // Troll Mode preventivo: No mostramos el error al usuario
    error_log("FALLO DE CONEXIÓN HUB: " . $e->getMessage());
    echo "<div style='background:#FF5733; color:white; padding:20px;'>";
    echo "⚠️ Error de Sincronización. El incidente ha sido reportado.";
    echo "</div>";
}