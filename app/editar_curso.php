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
if (!$curso_id && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'ID de curso no válido.';
    header('Location: listar_cursos.php');
    exit;
}

$errors = [];
$curso = [];

try {
    $db = new Database();
    $pdo = $db->getConnection();
    if (!$pdo) throw new Exception('No hay conexión a la base de datos');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $curso_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $duracion = trim($_POST['duracion_horas'] ?? '');

        // Validaciones
        if (empty($nombre)) $errors[] = 'El nombre del curso es obligatorio.';
        if (!empty($duracion) && !is_numeric($duracion)) {
            $errors[] = 'La duración debe ser un número.';
        }

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare('
                    UPDATE cursos 
                    SET nombre = :nombre, descripcion = :descripcion, duracion_horas = :duracion
                    WHERE id = :id
                ');
                $stmt->execute([
                    'nombre' => $nombre,
                    'descripcion' => $descripcion ?: null,
                    'duracion' => $duracion ?: null,
                    'id' => $curso_id
                ]);

                $_SESSION['success'] = 'Curso actualizado correctamente.';
                header('Location: listar_cursos.php');
                exit;

            } catch (Throwable $e) {
                error_log('[editar_curso] Error: ' . $e->getMessage());
                $errors[] = 'Error al actualizar el curso.';
            }
        }
    } else {
        // Cargar datos del curso
        $stmt = $pdo->prepare('SELECT * FROM cursos WHERE id = :id');
        $stmt->execute(['id' => $curso_id]);
        $curso = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$curso) {
            $_SESSION['error'] = 'Curso no encontrado.';
            header('Location: listar_cursos.php');
            exit;
        }
    }

} catch (Throwable $e) {
    error_log('[editar_curso] ' . $e->getMessage());
    $_SESSION['error'] = 'Error al cargar los datos.';
    header('Location: listar_cursos.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Editar Curso</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <h2>Editar curso</h2>

    <?php if (!empty($errors)): ?>
        <div class="message error">
            <ul style="margin:0; padding-left:20px;">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="editar_curso.php">
        <input type="hidden" name="id" value="<?= htmlspecialchars((string)$curso['id']) ?>">

        <label for="nombre">Nombre del curso *</label>
        <input type="text" id="nombre" name="nombre" 
               value="<?= htmlspecialchars($curso['nombre'] ?? '') ?>" 
               required placeholder="Ej: PHP Avanzado">

        <label for="descripcion">Descripción</label>
        <textarea id="descripcion" name="descripcion" rows="4" 
                  placeholder="Descripción del curso..."><?= htmlspecialchars($curso['descripcion'] ?? '') ?></textarea>

        <label for="duracion_horas">Duración (horas)</label>
        <input type="number" id="duracion_horas" name="duracion_horas" 
               value="<?= htmlspecialchars((string)($curso['duracion_horas'] ?? '')) ?>" 
               min="1" step="0.5" placeholder="Ej: 40">

        <p style="margin-top:20px;">
            <button type="submit" class="btn btn-success">Guardar cambios</button>
            <a href="listar_cursos.php" class="btn btn-outline">Cancelar</a>
        </p>
    </form>
</div>
</body>
</html>