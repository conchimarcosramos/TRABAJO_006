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
if (!$alumno_id && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'ID de alumno no válido.';
    header('Location: listar_alumnos.php');
    exit;
}

$errors = [];
$alumno = [];
$cursos = [];
$cursosAlumno = [];

try {
    $db = new Database();
    $pdo = $db->getConnection();
    if (!$pdo) throw new Exception('No hay conexión a la base de datos');

    // Obtener todos los cursos disponibles
    $stmt = $pdo->query('SELECT id, nombre FROM cursos ORDER BY nombre');
    $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $alumno_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $mensaje = trim($_POST['mensaje'] ?? '');
        $curso_ids = $_POST['curso_ids'] ?? [];

        // Validaciones
        if (empty($nombre)) $errors[] = 'El nombre es obligatorio.';
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El email no es válido.';
        }

        if (empty($errors)) {
            $pdo->beginTransaction();
            try {
                // Actualizar datos del alumno
                $stmt = $pdo->prepare('
                    UPDATE alumnos 
                    SET nombre = :nombre, email = :email, telefono = :telefono, mensaje = :mensaje
                    WHERE id = :id
                ');
                $stmt->execute([
                    'nombre' => $nombre,
                    'email' => $email,
                    'telefono' => $telefono ?: null,
                    'mensaje' => $mensaje ?: null,
                    'id' => $alumno_id
                ]);

                // Eliminar matrículas antiguas
                $stmt = $pdo->prepare('DELETE FROM alumnos_cursos WHERE alumno_id = :alumno_id');
                $stmt->execute(['alumno_id' => $alumno_id]);

                // Insertar nuevas matrículas
                if (!empty($curso_ids)) {
                    $stmt = $pdo->prepare('INSERT INTO alumnos_cursos (alumno_id, curso_id) VALUES (:alumno_id, :curso_id)');
                    foreach ($curso_ids as $curso_id) {
                        $curso_id = filter_var($curso_id, FILTER_VALIDATE_INT);
                        if ($curso_id) {
                            $stmt->execute(['alumno_id' => $alumno_id, 'curso_id' => $curso_id]);
                        }
                    }
                }

                $pdo->commit();
                $_SESSION['success'] = 'Alumno actualizado correctamente.';
                header('Location: listar_alumnos.php');
                exit;

            } catch (Throwable $e) {
                $pdo->rollBack();
                error_log('[editar_alumno] Error: ' . $e->getMessage());
                $errors[] = 'Error al actualizar el alumno.';
            }
        }
    } else {
        // Cargar datos del alumno
        $stmt = $pdo->prepare('SELECT * FROM alumnos WHERE id = :id');
        $stmt->execute(['id' => $alumno_id]);
        $alumno = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$alumno) {
            $_SESSION['error'] = 'Alumno no encontrado.';
            header('Location: listar_alumnos.php');
            exit;
        }

        // Cargar cursos del alumno
        $stmt = $pdo->prepare('SELECT curso_id FROM alumnos_cursos WHERE alumno_id = :id');
        $stmt->execute(['id' => $alumno_id]);
        $cursosAlumno = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

} catch (Throwable $e) {
    error_log('[editar_alumno] ' . $e->getMessage());
    $_SESSION['error'] = 'Error al cargar los datos.';
    header('Location: listar_alumnos.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Editar Alumno</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <h2>Editar alumno</h2>

    <?php if (!empty($errors)): ?>
        <div class="message error">
            <ul style="margin:0; padding-left:20px;">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="editar_alumno.php">
        <input type="hidden" name="id" value="<?= htmlspecialchars((string)$alumno['id']) ?>">

        <label for="nombre">Nombre completo *</label>
        <input type="text" id="nombre" name="nombre" 
               value="<?= htmlspecialchars($alumno['nombre'] ?? '') ?>" 
               required placeholder="Juan Pérez">

        <label for="email">Email *</label>
        <input type="email" id="email" name="email" 
               value="<?= htmlspecialchars($alumno['email'] ?? '') ?>" 
               required placeholder="ejemplo@mail.com">

        <label for="telefono">Teléfono</label>
        <input type="text" id="telefono" name="telefono" 
               value="<?= htmlspecialchars($alumno['telefono'] ?? '') ?>" 
               placeholder="600123456">

        <label for="curso_ids">Cursos (mantén Ctrl para seleccionar múltiples)</label>
        <select id="curso_ids" name="curso_ids[]" multiple>
            <?php foreach ($cursos as $curso): ?>
                <option value="<?= htmlspecialchars((string)$curso['id']) ?>"
                    <?= in_array($curso['id'], $cursosAlumno) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($curso['nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="mensaje">Mensaje / Observaciones</label>
        <textarea id="mensaje" name="mensaje" rows="4" placeholder="Observaciones adicionales..."><?= htmlspecialchars($alumno['mensaje'] ?? '') ?></textarea>

        <p style="margin-top:20px;">
            <button type="submit" class="btn btn-success">Guardar cambios</button>
            <a href="listar_alumnos.php" class="btn btn-outline">Cancelar</a>
        </p>
    </form>
</div>
</body>
</html>