<?php
/**
 * MH-CORE: Conexion PDO Singleton.
 * Aisla credenciales en .env (raiz del proyecto) y expone una unica
 * instancia PDO reutilizable en toda la aplicacion.
 */
class Database
{
    private static ?Database $instance = null;
    private PDO $connection;

    private function __construct()
    {
        $envFile = __DIR__ . '/../.env';

        if (!file_exists($envFile)) {
            throw new RuntimeException('Archivo .env no encontrado en la raiz del proyecto.');
        }

        $previousHandler = set_error_handler(function (): bool {
            return true; // Silencia el warning nativo; el fallo se evalua via el valor de retorno.
        });

        try {
            $env = parse_ini_file($envFile, false, INI_SCANNER_RAW);
        } finally {
            restore_error_handler();
        }

        if ($env === false) {
            throw new RuntimeException('Configuracion de entorno invalida.');
        }

        $required = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];

        foreach ($required as $key) {
            if (!array_key_exists($key, $env) || $env[$key] === '') {
                throw new RuntimeException('Configuracion de entorno incompleta.');
            }
        }

        $host    = $env['DB_HOST'];
        $name    = $env['DB_NAME'];
        $user    = $env['DB_USER'];
        $pass    = $env['DB_PASS'];
        $charset = $env['DB_CHARSET'] ?? 'utf8mb4';

        $dsn = "mysql:host={$host};dbname={$name};charset={$charset}";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $this->connection = new PDO($dsn, $user, $pass, $options);
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }

    private function __clone()
    {
    }

    public function __wakeup()
    {
        throw new RuntimeException('No se permite deserializar el Singleton de Database.');
    }
}
