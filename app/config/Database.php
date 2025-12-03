<?php
    /**
     * Archivo: config/Database.php
     * Clase para gestionar conexión a PostgreSQL usando PDO
     */

class Database {
    //DATOS DE DOCKER-COMPOSE.YML Y INIT.SQL
    private $host = 'db';  // Nombre del servicio de Postgres en docker-compose
    private $db = 'formulario_db'; // Nombre de la base de datos en init.sql
    private $user = 'admin'; // Usuario de la base de datos POSTGRES-USER
    private $password = 'admin123'; // Contraseña de la base de datos POSTGRES-PASSWORD
    private $port = '5432'; // Puerto por defecto de PostgreSQL 
    private ?pdo $conn = null; // Conexión PDO, por defecto nula

    // Constructor: establece la conexión
    /* Devuelve una conexión PDO a la base de datos PostgreSQL */

    public function getConnection(): ?PDO 
    {
        if ($this->conn instanceof PDO) {
            return $this->conn;
        }

        try {
            $dsn = "pgsql:host={$this->host};dbname={$this->db}";
            $this->conn = new PDO(
                $dsn,
                $this->username,
                $this->password,);

            //Opciones para el desarrollo
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            ;
        } catch (PDOException $exception) {
            //En producción conviene logear y mostrar el detalle en un archivo de logs
            echo('Error de conexión a la base de datos:' . $exception->getMessage());
            $this->conn = null;
        }
        return $this->conn;
    }
}
?>