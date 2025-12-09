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

try {
    $db = new Database();
    $pdo = $db->getConnection();
    if (!$pdo) throw new Exception('No hay conexión a la base de datos');

    // Paginación
    $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
    $perPage = 25;
    $offset = ($page - 1) * $perPage;

    $totalStmt = $pdo->query('SELECT COUNT(*) FROM alumnos');
    $totalAlumnos = (int)$totalStmt->fetchColumn();
    $totalPages = (int)max(1, ceil($totalAlumnos / $perPage));

    // Select con JOIN para agregar nombres de cursos usando string_agg
    $stmt = $pdo->prepare('
        SELECT a.id, a.nombre, a.email, a.telefono, a.mensaje, a.created_at,
               STRING_AGG(c.nombre, \', \' ORDER BY c.nombre) FILTER (WHERE c.nombre IS NOT NULL) AS cursos
        FROM alumnos a
        LEFT JOIN alumnos_cursos ac ON ac.alumno_id = a.id
        LEFT JOIN cursos c ON c.id = ac.curso_id
        GROUP BY a.id, a.nombre, a.email, a.telefono, a.mensaje, a.created_at
        ORDER BY a.created_at DESC
        LIMIT :limit OFFSET :offset
    ');
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $alumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('[listar_alumnos] ' . $e->getMessage());
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
<title>Listado de Alumnos</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Open+Sans:wght@400;600&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <h2>Listado de alumnos</h2>

    <?php if ($totalAlumnos === 0): ?>
        <div class="message">No hay alumnos registrados.</div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Cursos matriculado</th>
                    <th>Registrado</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($alumnos as $alumno): ?>
                <tr>
                    <td><?= htmlspecialchars((string)$alumno['id']) ?></td>
                    <td><?= htmlspecialchars($alumno['nombre']) ?></td>
                    <td><?= htmlspecialchars($alumno['email']) ?></td>
                    <td><?= htmlspecialchars($alumno['telefono'] ?? '—') ?></td>
                    <td>
                        <?php if (!empty($alumno['cursos'])): ?>
                            <?= htmlspecialchars($alumno['cursos']) ?>
                        <?php else: ?>
                            <em style="color:#999;">Sin cursos</em>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars((string)$alumno['created_at']) ?></td>
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
        <a class="btn btn-primary" href="listar_cursos.php">Ver cursos</a>
        <a class="btn btn-success" href="registro_alumnos.php">Registrar alumno</a>
    </p>
</div>
</body>
</html>