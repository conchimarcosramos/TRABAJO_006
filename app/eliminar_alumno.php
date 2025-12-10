<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/config/Database.php';

if (empty($_SESSION['username'])) {
    $_SESSION['error'] = 'Debes iniciar sesión.';
    header('Location: login.php');
    exit;
}

$alumno_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$alumno_id) {
    $_SESSION['error'] = 'ID de alumno no válido.';
    header('Location: listar_alumnos.php');
    exit;
}

try {
    $db = new Database();
    $pdo = $db->getConnection();
    if (!$pdo) throw new Exception('No hay conexión a la base de datos');

    // Obtener nombre del alumno antes de eliminar
    $stmt = $pdo->prepare('SELECT nombre FROM alumnos WHERE id = :id');
    $stmt->execute(['id' => $alumno_id]);
    $alumno = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$alumno) {
        $_SESSION['error'] = 'Alumno no encontrado.';
        header('Location: listar_alumnos.php');
        exit;
    }

    // Eliminar alumno (CASCADE eliminará las matrículas automáticamente)
    $stmt = $pdo->prepare('DELETE FROM alumnos WHERE id = :id');
    $stmt->execute(['id' => $alumno_id]);

    $_SESSION['success'] = 'Alumno "' . $alumno['nombre'] . '" eliminado correctamente.';
    
} catch (Throwable $e) {
    error_log('[eliminar_alumno] Error: ' . $e->getMessage());
    $_SESSION['error'] = 'Error al eliminar el alumno.';
}

header('Location: listar_alumnos.php');
exit;