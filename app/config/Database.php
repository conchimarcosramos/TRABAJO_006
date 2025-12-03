<?php
<?php
/**
 * Archivo: config/Database.php
 * Clase para gestionar conexión a PostgreSQL usando PDO
 *
 * Usa variables de entorno DB_HOST/DB_PORT/DB_USER/DB_PASS/DB_NAME.
 * Valores por defecto pensados para docker-compose:
 *  - DB_HOST=formulario_db
 *  - DB_PORT=5432
 *  - DB_NAME=formulario_db
 *  - DB_USER=admin
 *  - DB_PASS=admin123
 */

class Database {
    private string $host;
    private string $db = 'formulario_db';
    private string $user = 'admin';
    private string $password = 'admin123';
    private string $port;
    private ?PDO $conn = null;

    public function __construct()
    {
        // Leer variables de entorno si están definidas
        $this->host = getenv('DB_HOST') ?: 'formulario_db';
        $this->port = getenv('DB_PORT') ?: '5432';
        $this->user = getenv('DB_USER') ?: $this->user;
        $this->password = getenv('DB_PASS') ?: $this->password;
        $this->db = getenv('DB_NAME') ?: $this->db;
    }

    /**
     * Devuelve una conexión PDO o null si no se puede conectar.
     */
    public function getConnection(): ?PDO
    {
        if ($this->conn instanceof PDO) {
            return $this->conn;
        }

        try {
            $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s;', $this->host, $this->port, $this->db);

            $this->conn = new PDO(
                $dsn,
                $this->user,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );

            // Crear tabla `users` si no existe
            $create = "
                CREATE TABLE IF NOT EXISTS users (
                    id SERIAL PRIMARY KEY,
                    username VARCHAR(100) NOT NULL UNIQUE,
                    password_hash VARCHAR(255) NOT NULL,
                    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
                );
            ";
            $this->conn->exec($create);

        } catch (PDOException $exception) {
            // Log para diagnóstico y devolver null
            error_log('Error de conexión a la base de datos: ' . $exception->getMessage());
            $this->conn = null;
        }

        return $this->conn;
    }

    // Setters opcionales
    public function setHost(string $host): void { $this->host = $host; }
    public function setUser(string $user): void { $this->user = $user; }
    public function setPassword(string $password): void { $this->password = $password; }
    public function setDBName(string $db): void { $this->db = $db; }
    public function setPort(string $port): void { $this->port = $port; }
}