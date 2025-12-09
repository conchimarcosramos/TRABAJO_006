<?php
require_once __DIR__ . '/config/Database.php';
echo '<pre>';
try {
    $db = new Database();
    $pdo = $db->getConnection();
    if (!$pdo) { echo "No DB connection.\n"; exit; }
    echo "Connected OK.\n";

    $tables = ['users','cursos','alumnos'];
    foreach ($tables as $t) {
        $exists = $pdo->query("SELECT to_regclass('public.{$t}')")->fetchColumn();
        echo "Table {$t}: " . ($exists ? "exists" : "NO") . "\n";
        if ($exists) {
            $count = $pdo->query("SELECT COUNT(*) FROM {$t}")->fetchColumn();
            echo "  rows = {$count}\n";
            echo "  columns: \n";
            $cols = $pdo->query("
                SELECT column_name, data_type
                FROM information_schema.columns
                WHERE table_schema = current_schema() AND table_name = '{$t}'
            ")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($cols as $c) {
                echo "    - {$c['column_name']} ({$c['data_type']})\n";
            }
        }
    }
// Si muestra datos de ejemplo, añade formato:
$stmt = $pdo->query("
    SELECT id, nombre, email, 
           TO_CHAR(created_at, 'DD-MM-YYYY') AS fecha 
    FROM alumnos 
    LIMIT 5
");
    $alumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Datos de ejemplo de la tabla alumnos:\n";
    foreach ($alumnos as $alumno) {
        echo "  - ID: {$alumno['id']}, Nombre: {$alumno['nombre']}, Email: {$alumno['email']}, Fecha: {$alumno['fecha']}\n";
    }
} catch (Throwable $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
    error_log('[db_debug] ' . $e->getMessage());
}
echo '</pre>';
?>