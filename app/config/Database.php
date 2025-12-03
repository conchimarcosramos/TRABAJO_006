<?php
/**
 * Archivo: config/Database.php
 * Clase para gestionar conexión a PostgreSQL usando PDO
 */

class Database {
    private $host = 'db';  // Nombre del servicio en docker-compose
    private $db = 'formulario_db';
    private $user = 'admin';
    private $password = 'admin123';
    private $port = '5432';
    
    private $pdo;

    // Constructor: establece la conexión
    public function __construct() {
        try {
            $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->db}";
            
            $this->pdo = new PDO(
                $dsn,
                $this->user,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }

    // Retorna la conexión PDO
    public function getConnection() {
        return $this->pdo;
    }

    // Método para obtener todos los usuarios
    public function getUsuarios() {
        $sql = "SELECT * FROM usuarios ORDER BY fecha_registro DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    // Método para insertar usuario
    public function crearUsuario($nombre, $email, $telefono, $mensaje) {
        $sql = "INSERT INTO usuarios (nombre, email, telefono, mensaje) 
                VALUES (:nombre, :email, :telefono, :mensaje)";
        
        $stmt = $this->pdo->prepare($sql);
        
        return $stmt->execute([
            ':nombre' => $nombre,
            ':email' => $email,
            ':telefono' => $telefono,
            ':mensaje' => $mensaje
        ]);
    }

    // Método para obtener cursos
    public function getCursos() {
        $sql = "SELECT * FROM cursos ORDER BY fecha_creacion DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }
}
?>