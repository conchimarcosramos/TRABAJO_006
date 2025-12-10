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
        SELECT c.id, c.nombre, c.descripcion, c.duracion_horas, 
               c.fecha_creacion,
               TO_CHAR(c.fecha_creacion, \'DD-MM-YYYY\') AS fecha_formateada,
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
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
<!-- Font Awesome para iconos -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
.action-buttons {
    display: flex;
    gap: 10px;
    justify-content: center;
}
.icon-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.2s ease;
    font-size: 16px;
    border: none;
    cursor: pointer;
}
.icon-btn:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}
/* Colores de la paleta */
.icon-btn-view {
    background: linear-gradient(135deg, #CCD9B8 0%, #90A68A 100%);
    color: #3B5159;
}
.icon-btn-view:hover {
    background: linear-gradient(135deg, #90A68A 0%, #5C736F 100%);
    color: white;
}
.icon-btn-edit {
    background: linear-gradient(135deg, #90A68A 0%, #5C736F 100%);
    color: white;
}
.icon-btn-edit:hover {
    background: linear-gradient(135deg, #5C736F 0%, #3B5159 100%);
}
.icon-btn-delete {
    background: linear-gradient(135deg, #5C736F 0%, #3B5159 100%);
    color: white;
    opacity: 0.85;
}
.icon-btn-delete:hover {
    background: linear-gradient(135deg, #3B5159 0%, #2a3a40 100%);
    opacity: 1;
}
/* Tooltip con colores de la paleta */
.icon-btn {
    position: relative;
}
.icon-btn::after {
    content: attr(data-tooltip);
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%) translateY(-8px);
    background: #3B5159;
    color: #EBF2DC;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    white-space: nowrap;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.2s ease;
    z-index: 1000;
    box-shadow: 0 4px 12px rgba(59, 81, 89, 0.3);
}
.icon-btn:hover::after {
    opacity: 1;
}
</style>
</head>
<body>
<div class="container">
    <h2>Listado de cursos</h2>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="message success"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="message error"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

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
                    <th style="text-align:center;">Acciones</th>
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
                    <td><?= htmlspecialchars($curso['fecha_formateada']) ?></td>
                    <td>
                        <div class="action-buttons">
                            <a href="ver_alumnos_curso.php?curso_id=<?= htmlspecialchars((string)$curso['id']) ?>" 
                               class="icon-btn icon-btn-view"
                               data-tooltip="Ver alumnos">
                                <i class="fas fa-users"></i>
                            </a>
                            <a href="editar_curso.php?id=<?= htmlspecialchars((string)$curso['id']) ?>" 
                               class="icon-btn icon-btn-edit"
                               data-tooltip="Editar curso">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="eliminar_curso.php?id=<?= htmlspecialchars((string)$curso['id']) ?>" 
                               class="icon-btn icon-btn-delete"
                               data-tooltip="Eliminar curso"
                               onclick="return confirm('¿Estás seguro de eliminar el curso <?= htmlspecialchars(addslashes($curso['nombre'])) ?>?\n\nEsto eliminará también todas las matrículas asociadas.');">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </div>
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