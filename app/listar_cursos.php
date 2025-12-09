<?php
declare(strict_types=1);
header('Content-Type: text/html; charset=utf-8');
session_start();
require_once __DIR__ . '/config/Database.php';

if (empty($_SESSION['username'])) {
    $_SESSION['error'] = 'Debes iniciar sesión para ver los cursos.';
    header('Location: login.php');
    exit;
}

try {
    $db = new Database();
    $pdo = $db->getConnection();
    if (!$pdo) {
        throw new Exception('No hay conexión a la base de datos');
    }

    // Paginación
    $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
    $perPage = 25;
    $offset = ($page - 1) * $perPage;

    $totalStmt = $pdo->query('SELECT COUNT(*) FROM cursos');
    $totalCursos = (int)$totalStmt->fetchColumn();
    $totalPages = (int)max(1, ceil($totalCursos / $perPage));

    // JOIN con alumnos_cursos para contar matriculados
    $stmt = $pdo->prepare('
        SELECT c.id, c.nombre, c.descripcion, c.duracion_horas, c.fecha_creacion,
               COUNT(ac.alumno_id) AS matriculados
        FROM cursos c
        LEFT JOIN alumnos_cursos ac ON ac.curso_id = c.id
        GROUP BY c.id, c.nombre, c.descripcion, c.duracion_horas, c.fecha_creacion
        ORDER BY c.fecha_creacion DESC
        LIMIT :limit OFFSET :offset
    ');
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    error_log('[listar_cursos] ' . $e->getMessage());
    http_response_code(500);
    echo '<!doctype html><meta charset="utf-8"><title>Error</title><h1>Error interno</h1><p>No se pudieron obtener los cursos.</p>';
    exit;
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Listado de Cursos</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Open+Sans:wght@400;600&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <h2>Listado de cursos</h2>

    <?php if ($totalCursos === 0): ?>
        <div class="message">No hay cursos registrados.</div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Duración (h)</th>
                    <th>Matriculados</th>
                    <th>Creado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($cursos as $curso): ?>
                <tr>
                    <td><?= htmlspecialchars((string)$curso['id']) ?></td>
                    <td><?= htmlspecialchars((string)$curso['nombre']) ?></td>
                    <td><?= htmlspecialchars((string)($curso['descripcion'] ?? '—')) ?></td>
                    <td><?= htmlspecialchars((string)($curso['duracion_horas'] ?? '—')) ?></td>
                    <td style="text-align:center;">
                        <strong><?= htmlspecialchars((string)$curso['matriculados']) ?></strong>
                    </td>
                    <td><?= htmlspecialchars((string)$curso['fecha_creacion']) ?></td>
                    <td style="text-align:center;">
                        <a class="btn btn-outline" href="ver_alumnos_curso.php?curso_id=<?= htmlspecialchars((string)$curso['id']) ?>">
                            Ver alumnos
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <p style="margin-top:12px;">
            <?php if ($page > 1): ?><a class="btn btn-outline" href="?page=<?= $page - 1 ?>">Anterior</a><?php endif; ?>
            <?php if ($page < $totalPages): ?><a class="btn btn-primary" href="?page=<?= $page + 1 ?>">Siguiente</a><?php endif; ?>
        </p>
    <?php endif; ?>

    <p style="margin-top:12px;">
        <a class="btn btn-outline" href="dashboard.php">Volver al panel</a>
        <a class="btn btn-success" href="registro_cursos.php">Registrar curso</a>
        <a class="btn btn-primary" href="listar_alumnos.php">Ver alumnos</a>
    </p>
</div>
</body>
</html>