<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/config/Database.php';

// Verificar sesión
if (empty($_SESSION['username'])) {
    $_SESSION['error'] = 'Debes iniciar sesión para acceder al panel.';
    header('Location: login.php');
    exit;
}

$username = $_SESSION['username'];
$role = $_SESSION['role'] ?? 'user';

// Obtener estadísticas
$totalAlumnos = 0;
$totalCursos = 0;
$totalUsuarios = 0;
$ultimosAlumnos = [];

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    if (!$pdo) {
        throw new Exception('No se pudo conectar a la base de datos');
    }
    
    $totalAlumnos = (int)$pdo->query('SELECT COUNT(*) FROM alumnos')->fetchColumn();
    $totalCursos = (int)$pdo->query('SELECT COUNT(*) FROM cursos')->fetchColumn();
    $totalUsuarios = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    
    // Últimos 5 alumnos registrados
    $stmt = $pdo->query("
        SELECT id, nombre, email, TO_CHAR(created_at, 'DD-MM-YYYY') AS fecha_registro
        FROM alumnos
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $ultimosAlumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Throwable $e) {
    error_log('[dashboard] Error: ' . $e->getMessage());
    $_SESSION['error'] = 'Error al cargar estadísticas: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Panel de Control</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Open+Sans:wght@400;600&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style_2.css">
<link rel="stylesheet" href="css/style_3.css">
</head>
<body>
<div class="container">
    <h2>Panel de Control</h2>
    
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="message success"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="message error"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="card">
        <p><strong>Bienvenido:</strong> <?= htmlspecialchars($username) ?></p>
        <p><strong>Rol:</strong> <?= htmlspecialchars($role) ?></p>
    </div>

   <h3>Estadísticas</h3>
<div class="stats-grid">
    <div class="card card-alumnos">
        <h4>Alumnos</h4>
        <p><?= $totalAlumnos ?></p>
    </div>
    <div class="card card-cursos">
        <h4>Cursos</h4>
        <p><?= $totalCursos ?></p>
    </div>
    <div class="card card-usuarios">
        <h4>Usuarios</h4>
        <p><?= $totalUsuarios ?></p>
    </div>
</div>

    <?php if (!empty($ultimosAlumnos)): ?>
        <h3>Últimos alumnos registrados</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($ultimosAlumnos as $alumno): ?>
                <tr>
                    <td><?= htmlspecialchars((string)$alumno['id']) ?></td>
                    <td><?= htmlspecialchars($alumno['nombre']) ?></td>
                    <td><?= htmlspecialchars($alumno['email']) ?></td>
                    <td><?= htmlspecialchars($alumno['fecha_registro']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h3>Acciones rápidas</h3>
    <p style="margin-top:12px;">
        <a class="btn btn-primary" href="listar_alumnos.php">Ver alumnos</a>
        <a class="btn btn-primary" href="listar_cursos.php">Ver cursos</a>
        <a class="btn btn-success" href="registro_alumnos.php">Registrar alumno</a>
        <a class="btn btn-success" href="registro_cursos.php">Registrar curso</a>
    </p>

    <p style="margin-top:20px;">
        <a class="btn btn-outline" href="logout.php">Cerrar sesión</a>
    </p>
</div>
</body>
</html>