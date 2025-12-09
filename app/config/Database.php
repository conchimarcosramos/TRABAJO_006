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
        $this->port = getenv('DB_PORT') ?: '5434';
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
            $this->conn->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS email VARCHAR(255);");
            $this->conn->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS role VARCHAR(50) DEFAULT 'user';");

            // Tabla cursos
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS cursos (
                    id SERIAL PRIMARY KEY,
                    nombre VARCHAR(255) NOT NULL,
                    descripcion TEXT,
                    duracion_horas INTEGER DEFAULT NULL,
                    fecha_creacion TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
                );
            ");

            // Tabla alumnos: normalizar a esquema esperado y adaptar columnas legacy si es necesario
            // Crear tabla si no existe
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS alumnos (
                    id SERIAL PRIMARY KEY,
                    nombre VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    telefono VARCHAR(50),
                    mensaje TEXT,
                    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
                    curso_id INTEGER REFERENCES cursos(id) ON DELETE SET NULL
                );
            ");

            // Detectar columnas existentes para posibles renames/migraciones
            $stmtCols = $this->conn->prepare("
                SELECT column_name
                FROM information_schema.columns
                WHERE table_schema = current_schema()
                  AND table_name = 'alumnos'
            ");
            $stmtCols->execute();
            $columns = $stmtCols->fetchAll(PDO::FETCH_COLUMN);

            // Migrar "fecha_registro" -> created_at
            if (in_array('fecha_registro', $columns) && !in_array('created_at', $columns)) {
                $this->conn->exec("ALTER TABLE alumnos RENAME COLUMN fecha_registro TO created_at;");
                $this->conn->exec("ALTER TABLE alumnos ALTER COLUMN created_at SET DEFAULT CURRENT_TIMESTAMP;");
                error_log('[DB] Renamed fecha_registro -> created_at in alumnos');
            }

            // Renombrar columna con punto "nombre.curso" (si existe) a curso_nombre
            if (in_array('nombre.curso', $columns)) {
                // Use double quotes since name has dot
                $this->conn->exec('ALTER TABLE alumnos RENAME COLUMN "nombre.curso" TO curso_nombre;');
                error_log('[DB] Renamed "nombre.curso" -> curso_nombre in alumnos');
            }

            // Si existe curso_nombre, crear/llenar curso_id
            $stmtCols->execute();
            $columns = $stmtCols->fetchAll(PDO::FETCH_COLUMN);
            if (in_array('curso_id', $columns) === false) {
                // Añadir curso_id sin constraints todavía
                $this->conn->exec("ALTER TABLE alumnos ADD COLUMN IF NOT EXISTS curso_id INTEGER;");
                error_log('[DB] Added column curso_id to alumnos');
            }

            // Si existe curso_nombre, mapear curso_id a partir de cursos.nombre
            if (in_array('curso_nombre', $columns)) {
                $this->conn->exec("
                    UPDATE alumnos a
                    SET curso_id = c.id
                    FROM cursos c
                    WHERE a.curso_nombre = c.nombre
                      AND (a.curso_id IS NULL OR a.curso_id <> c.id)
                ");
                error_log('[DB] Mapped curso_nombre -> curso_id in alumnos');
            }

            // Añadir FK si no existe
            // Check constraint by name (fk_alumnos_cursos)
            $checkFk = $this->conn->query("
                SELECT conname FROM pg_constraint
                WHERE conrelid = 'alumnos'::regclass AND contype = 'f'
            ")->fetchAll(PDO::FETCH_COLUMN);

            if (!in_array('fk_alumnos_cursos', $checkFk)) {
                // Add FK constraint safely
                try {
                    $this->conn->exec("ALTER TABLE alumnos ADD CONSTRAINT fk_alumnos_cursos FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE SET NULL;");
                    error_log('[DB] Added fk_alumnos_cursos constraint');
                } catch (Throwable $e) {
                    // If adding fails (duplicate or incompatible), skip but log
                    error_log('[DB] Could not add fk_alumnos_cursos: ' . $e->getMessage());
                }
            }

            // Tabla pivot para relación many-to-many: alumnos <-> cursos
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS alumnos_cursos (
                    alumno_id INTEGER NOT NULL,
                    curso_id INTEGER NOT NULL,
                    fecha_matricula TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (alumno_id, curso_id),
                    FOREIGN KEY (alumno_id) REFERENCES alumnos(id) ON DELETE CASCADE,
                    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE
                );
            ");
            $this->conn->exec("CREATE INDEX IF NOT EXISTS idx_alumnos_cursos_alumno_id ON alumnos_cursos(alumno_id);");
            $this->conn->exec("CREATE INDEX IF NOT EXISTS idx_alumnos_cursos_curso_id ON alumnos_cursos(curso_id);");

            // Migrar datos existentes de alumnos.curso_id a alumnos_cursos si existe la columna
            $stmtCols->execute();
            $columns = $stmtCols->fetchAll(PDO::FETCH_COLUMN);
            if (in_array('curso_id', $columns)) {
                $this->conn->exec("
                    INSERT INTO alumnos_cursos (alumno_id, curso_id)
                    SELECT id, curso_id FROM alumnos WHERE curso_id IS NOT NULL
                    ON CONFLICT DO NOTHING;
                ");
                error_log('[DB] Migrated alumnos.curso_id to alumnos_cursos pivot table.');
            }

            // Ensure indexes
            $this->conn->exec("CREATE INDEX IF NOT EXISTS idx_users_username ON users (username);");
            $this->conn->exec("CREATE INDEX IF NOT EXISTS idx_users_email ON users (email);");
            $this->conn->exec("CREATE INDEX IF NOT EXISTS idx_alumnos_email ON alumnos (email);");
            $this->conn->exec("CREATE INDEX IF NOT EXISTS idx_alumnos_curso_id ON alumnos (curso_id);");

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