<?php
declare(strict_types=1);

/**
 * listar_usuarios.php
 * Archivo para listar usuarios registrados.
 *
 * Requisitos:
 * - Definir variables de conexión mediante variables de entorno:
 *   DB_DSN, DB_USER, DB_PASS
 * - Tabla esperada: users (id, username, email, role, created_at)
 * - Evita exponer errores de base de datos al usuario; registrar errores en el log.
 */

header('Content-Type: text/html; charset=utf-8');
session_start();
require_once __DIR__ . '/config/Database.php';

// Acceso solo si hay sesión iniciada
if (empty($_SESSION['username'])) {
    $_SESSION['error'] = 'Debes iniciar sesión para ver el listado de usuarios.';
    header('Location: login.php');
    exit;
}

$dsn = getenv('DB_DSN') ?: 'mysql:host=127.0.0.1;dbname=myapp;charset=utf8mb4';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
}

try {
    $db = new Database();
    $pdo = $db->getConnection();
    $users = [];

    if (!$pdo) {
        $_SESSION['error'] = 'Error de conexión con la base de datos.';
        header('Location: dashboard.php');
        exit;
    }

    // Parámetros de paginación
    $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, [
        'options' => ['default' => 1, 'min_range' => 1]
    ]);
    $perPage = 50;
    $offset = ($page - 1) * $perPage;

    // Obtener total de registros para paginación
    $totalStmt = $pdo->query('SELECT COUNT(*) FROM users');
    $totalUsers = (int) $totalStmt->fetchColumn();

    // Obtener usuarios
    $stmt = $pdo->prepare('SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    http_response_code(500);
    error_log('DB_QUERY_ERROR: ' . $e->getMessage());
    echo '<!doctype html><meta charset="utf-8"><title>Error</title><h1>Error interno</h1><p>No se pudieron obtener los usuarios.</p>';
    exit;
}

$totalPages = (int) max(1, ceil($totalUsers / $perPage));

?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Usuarios registrados</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; margin: 1rem; color: #222; }
        table { border-collapse: collapse; width: 100%; max-width: 100%; }
        th, td { padding: .5rem .75rem; border: 1px solid #ddd; text-align: left; }
        th { background: #f9f9f9; }
        .meta { margin-bottom: 1rem; color: #555; }
        .pagination { margin-top: 1rem; display: flex; gap: .5rem; align-items: center; flex-wrap: wrap; }
        .pagination a { padding: .4rem .6rem; background: #f0f0f0; color: #111; text-decoration: none; border-radius: 4px; }
        .pagination a.current { background: #0078d7; color: #fff; pointer-events: none; }
        .no-data { color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Listado de usuarios</h1>
        <div class="meta">
            <strong>Total:</strong> <?php echo escape((string) $totalUsers); ?> — <strong>Página:</strong> <?php echo escape((string) $page); ?> / <?php echo escape((string) $totalPages); ?>
        </div>

        <?php if (empty($users)): ?>
            <p class="no-data">No se han encontrado usuarios.</p>
        <?php else: ?>
            <table role="table" aria-label="Lista de usuarios">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Correo</th>
                        <th>Rol</th>
                        <th>Creado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo escape((string) $user['id']); ?></td>
                            <td><?php echo escape((string) $user['username']); ?></td>
                            <td><?php echo escape((string) $user['email']); ?></td>
                            <td><?php echo escape((string) $user['role']); ?></td>
                            <td><?php echo escape((string) $user['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <nav class="pagination" aria-label="Paginación de usuarios">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>">&laquo; Anterior</a>
                <?php endif; ?>

                <?php
                    // Mostrar hasta 7 enlaces: (current -3..current +3) dentro de 1..totalPages
                    $start = max(1, $page - 3);
                    $end = min($totalPages, $page + 3);
                    for ($i = $start; $i <= $end; $i++):
                ?>
                    <a href="?page=<?php echo $i; ?>" class="<?php echo $i === $page ? 'current' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>">Siguiente &raquo;</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>

        <p style="margin-top:12px;">
            <a class="btn btn-outline" href="dashboard.php">Volver al panel</a>
        </p>
    </div>
</body>
</html>