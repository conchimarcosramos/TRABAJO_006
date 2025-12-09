<?php
declare(strict_types=1);
header('Content-Type: text/html; charset=utf-8');
session_start();
require_once __DIR__ . '/config/Database.php';

if (empty($_SESSION['username'])) {
    $_SESSION['error'] = 'Debes iniciar sesión para ver los alumnos.';
    header('Location: login.php');
    exit;
}

$curso_id = filter_input(INPUT_GET, 'curso_id', FILTER_VALIDATE_INT);
if (!$curso_id) {
    $_SESSION['error'] = 'ID de curso no válido.';
    header('Location: listar_cursos.php');
    exit;
}

try {
    $db = new Database();
    $pdo = $db->getConnection();
    if (!$pdo) {
        throw new Exception('No hay conexión a la base de datos');
    }

    // Obtener información del curso
    $cursoStmt = $pdo->prepare('SELECT id, nombre, descripcion FROM cursos WHERE id = :id');
    $cursoStmt->execute(['id' => $curso_id]);
    $curso = $cursoStmt->fetch(PDO::FETCH_ASSOC);

    if (!$curso) {
        $_SESSION['error'] = 'Curso no encontrado.';
        header('Location: listar_cursos.php');
        exit;
    }

    // Obtener alumnos matriculados en el curso
    $alumnosStmt = $pdo->prepare('
        SELECT a.id, a.nombre, a.email, a.telefono, 
               ac.fecha_matricula,
               TO_CHAR(ac.fecha_matricula, \'DD-MM-YYYY\') AS fecha_matricula_formateada
        FROM alumnos a
        INNER JOIN alumnos_cursos ac ON ac.alumno_id = a.id
        WHERE ac.curso_id = :curso_id
        ORDER BY ac.fecha_matricula DESC
    ');
    $alumnosStmt->execute(['curso_id' => $curso_id]);
    $alumnos = $alumnosStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    error_log('[ver_alumnos_curso] ' . $e->getMessage());
    http_response_code(500);
    echo '<!doctype html><meta charset="utf-8"><title>Error</title><h1>Error interno</h1><p>No se pudieron obtener los alumnos.</p>';
    exit;
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Alumnos del Curso: <?= htmlspecialchars($curso['nombre']) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Open+Sans:wght@400;600&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <h2>Alumnos matriculados en: <?= htmlspecialchars($curso['nombre']) ?></h2>

    <?php if (!empty($curso['descripcion'])): ?>
        <div class="card" style="margin-bottom: 20px;">
            <p><strong>Descripción:</strong> <?= htmlspecialchars($curso['descripcion']) ?></p>
        </div>
    <?php endif; ?>

    <?php if (empty($alumnos)): ?>
        <div class="message">No hay alumnos matriculados en este curso.</div>
    <?php else: ?>
        <p><strong>Total de alumnos:</strong> <?= count($alumnos) ?></p>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Fecha de matrícula</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($alumnos as $alumno): ?>
                <tr>
                    <td><?= htmlspecialchars((string)$alumno['id']) ?></td>
                    <td><?= htmlspecialchars($alumno['nombre']) ?></td>
                    <td><?= htmlspecialchars($alumno['email']) ?></td>
                    <td><?= htmlspecialchars($alumno['telefono'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($alumno['fecha_matricula_formateada']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p style="margin-top:20px;">
        <a class="btn btn-outline" href="listar_cursos.php">Volver a cursos</a>
        <a class="btn btn-primary" href="listar_alumnos.php">Ver todos los alumnos</a>
        <a class="btn btn-success" href="registro_alumnos.php">Registrar alumno</a>
    </p>
</div>
</body>
</html>