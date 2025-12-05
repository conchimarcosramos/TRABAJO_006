<?php
/**
 * Archivo: config/Database.php
 * Clase para gestionar conexión a PostgreSQL usando PDO
 *
 * Lee variables de entorno DB_HOST/DB_PORT/DB_USER/DB_PASS/DB_NAME.
 * Crea/ajusta tablas necesarias: users, cursos, alumnos.
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
        $this->host = getenv('DB_HOST') ?: 'formulario_db';
        $this->port = getenv('DB_PORT') ?: '5432';
        $this->user = getenv('DB_USER') ?: $this->user;
        $this->password = getenv('DB_PASS') ?: $this->password;
        $this->db = getenv('DB_NAME') ?: $this->db;
    }

    public function getConnection(): ?PDO
    {
        if ($this->conn instanceof PDO) {
            return $this->conn;
        }

        try {
            $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s;', $this->host, $this->port, $this->db);
            $this->conn = new PDO($dsn, $this->user, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            // Asegurar esquema: crear tablas y columnas necesarias
            $this->ensureSchema();

        } catch (PDOException $exception) {
            error_log('DB connection error: ' . $exception->getMessage());
            $this->conn = null;
        }

        return $this->conn;
    }

    /**
     * Crea/ajusta tablas y columnas necesarias para la app.
     */
    private function ensureSchema(): void
    {
        if (!($this->conn instanceof PDO)) {
            return;
        }

        try {
            // Extensiones útiles (si el usuario DB tiene el permiso)
            try {
                $this->conn->exec('CREATE EXTENSION IF NOT EXISTS "pgcrypto";');
            } catch (Throwable $e) {
                // No es crítico si falla por permisos; lo ignoramos pero lo logueamos.
                error_log('Could not create extension pgcrypto: ' . $e->getMessage());
            }

            // Tabla users (con email y role)
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id SERIAL PRIMARY KEY,
                    username VARCHAR(100) NOT NULL UNIQUE,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    role VARCHAR(50) DEFAULT 'user',
                    password_hash VARCHAR(255) NOT NULL,
                    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
                );
            ");

            // Si la tabla users existía sin 'email' o 'role', añádelos si faltan
            $this->conn->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS email VARCHAR(255);");
            // Establecer NOT NULL si ya hay valores o si email no puede ser null
            // NOTA: si hay filas con email NULL no se podrá aplicar NOT NULL; la app actualiza emails en caso necesario.
            // Añadir role
            $this->conn->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS role VARCHAR(50) DEFAULT 'user';");

            // Crear tabla cursos
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS cursos (
                    id SERIAL PRIMARY KEY,
                    nombre VARCHAR(255) NOT NULL,
                    descripcion TEXT,
                    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
                );
            ");

            // Crear tabla alumnos con FK a cursos
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS alumnos (
                    id SERIAL PRIMARY KEY,
                    nombre VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    curso_id INTEGER REFERENCES cursos(id) ON DELETE SET NULL,
                    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
                );
            ");

            // Índices sugeridos (si no existen)
            $this->conn->exec("CREATE INDEX IF NOT EXISTS idx_users_username ON users (username);");
            $this->conn->exec("CREATE INDEX IF NOT EXISTS idx_users_email ON users (email);");
            $this->conn->exec("CREATE INDEX IF NOT EXISTS idx_alumnos_email ON alumnos (email);");

        } catch (PDOException $e) {
            // Registrar error para diagnóstico, no volver a lanzar (evitar romper la app)
            error_log('Schema ensure error: ' . $e->getMessage());
        }
    }

    // Setters opcionales
    public function setHost(string $host): void { $this->host = $host; }
    public function setUser(string $user): void { $this->user = $user; }
    public function setPassword(string $password): void { $this->password = $password; }
    public function setDBName(string $db): void { $this->db = $db; }
    public function setPort(string $port): void { $this->port = $port; }
}