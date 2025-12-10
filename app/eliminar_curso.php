<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/config/Database.php';

if (empty($_SESSION['username'])) {
    $_SESSION['error'] = 'Debes iniciar sesión.';
    header('Location: login.php');
    exit;
}

$curso_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$curso_id) {
    $_SESSION['error'] = 'ID de curso no válido.';
    header('Location: listar_cursos.php');
    exit;
}

try {
    $db = new Database();
    $pdo = $db->getConnection();
    if (!$pdo) throw new Exception('No hay conexión a la base de datos');

    // Obtener nombre del curso antes de eliminar
    $stmt = $pdo->prepare('SELECT nombre FROM cursos WHERE id = :id');
    $stmt->execute(['id' => $curso_id]);
    $curso = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$curso) {
        $_SESSION['error'] = 'Curso no encontrado.';
        header('Location: listar_cursos.php');
        exit;
    }

    // Eliminar curso (CASCADE eliminará las matrículas automáticamente)
    $stmt = $pdo->prepare('DELETE FROM cursos WHERE id = :id');
    $stmt->execute(['id' => $curso_id]);

    $_SESSION['success'] = 'Curso "' . $curso['nombre'] . '" eliminado correctamente.';
    
} catch (Throwable $e) {
    error_log('[eliminar_curso] Error: ' . $e->getMessage());
    $_SESSION['error'] = 'Error al eliminar el curso.';
}

header('Location: listar_cursos.php');
exit;