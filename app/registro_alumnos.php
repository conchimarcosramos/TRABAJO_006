<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/config/Database.php';

// Acceso solo con sesión iniciada
if (empty($_SESSION['username'])) {
    $_SESSION['error'] = 'Debes iniciar sesión para crear alumnos.';
    header('Location: login.php');
    exit;
}

$errors = [];
$cursos = [];

try {
    $pdo = (new Database())->getConnection();
    if ($pdo) {
        $stmt = $pdo->query('SELECT id, nombre FROM cursos ORDER BY nombre ASC');
        $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    error_log('[registro_alumnos] Error fetching cursos: ' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');

    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $mensaje = trim($_POST['mensaje'] ?? '');
    $curso_id = filter_input(INPUT_POST, 'curso_id', FILTER_VALIDATE_INT) ?: null;

    @mkdir(__DIR__ . '/logs', 0755, true);
    file_put_contents(__DIR__ . '/logs/registro_alumnos_post.log', date('c') . ' - POST: ' . json_encode(compact('nombre','email','telefono','mensaje','curso_id')) . PHP_EOL, FILE_APPEND);

    if ($nombre === '' || mb_strlen($nombre) < 2) { $errors[] = 'El nombre del alumno debe tener al menos 2 caracteres.'; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Introduce un email válido.'; }

    if (!$errors) {
        $db = new Database();
        $pdo = $db->getConnection();
        if (!$pdo) {
            file_put_contents(__DIR__ . '/logs/registro_alumnos_errors.log', date('c') . " - No DB connection\n", FILE_APPEND);
            $_SESSION['error'] = 'Error de conexión con la base de datos.'; header('Location: registro_alumnos.php'); exit;
        }

        try {
            // Comprueba duplicado por email
            $stmt = $pdo->prepare('SELECT id FROM alumnos WHERE email = :email LIMIT 1');
            $stmt->execute(['email' => $email]);
            if ($stmt->fetch()) {
                $_SESSION['error'] = 'El email ya está registrado para otro alumno.';
                header('Location: registro_alumnos.php'); exit;
            }

            // Validar curso_id (si viene)
            if ($curso_id !== null) {
                $cstmt = $pdo->prepare('SELECT id FROM cursos WHERE id = :id LIMIT 1');
                $cstmt->execute(['id' => $curso_id]);
                if (!$cstmt->fetch()) $curso_id = null;
            }

            // Insert con campos coincidentes con DB
            $istmt = $pdo->prepare('INSERT INTO alumnos (nombre, email, telefono, mensaje, curso_id) VALUES (:nombre, :email, :telefono, :mensaje, :curso_id)');
            $ok = $istmt->execute(['nombre' => $nombre, 'email' => $email, 'telefono' => $telefono ?: null, 'mensaje' => $mensaje ?: null, 'curso_id' => $curso_id]);

            if ($ok && $istmt->rowCount() > 0) {
                file_put_contents(__DIR__ . '/logs/registro_alumnos_success.log', date('c') . ' - Insert OK: ' . $email . PHP_EOL, FILE_APPEND);
                $_SESSION['success'] = 'Alumno registrado correctamente.';
                header('Location: listar_alumnos.php'); exit;
            } else {
                $err = $istmt->errorInfo();
                file_put_contents(__DIR__ . '/logs/registro_alumnos_errors.log', date('c') . ' - Insert failed: ' . json_encode($err) . PHP_EOL, FILE_APPEND);
                error_log('[registro_alumnos] Insert failed: ' . json_encode($err));
                $_SESSION['error'] = 'Error interno al guardar el alumno.';
                header('Location: registro_alumnos.php'); exit;
            }
        } catch (PDOException $e) {
            file_put_contents(__DIR__ . '/logs/registro_alumnos_exceptions.log', date('c') . ' - ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
            error_log('[registro_alumnos] PDOException: ' . $e->getMessage());
            $_SESSION['error'] = 'Error interno al guardar el alumno.';
            header('Location: registro_alumnos.php'); exit;
        }
    }

    if (!empty($errors)) { $_SESSION['error'] = implode(' ', $errors); header('Location: registro_alumnos.php'); exit; }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Registrar Alumno</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Open+Sans:wght@400;600&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
<!-- Inline debug styles: eliminar cuando confirmes que todo funciona -->
<style>
/* Forzar visibilidad por si .btn está oculta por CSS global */
.btn { display:inline-block !important; visibility: visible !important; opacity: 1 !important; }
.btn.btn-success { background-color: #28a745 !important; color:#fff !important; border: 1px solid #1e7e34 !important; padding: 8px 12px !important; }
</style>
</head>
<body>
<div class="container">
    <h2>Registrar alumno</h2>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="message error"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="message success"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <div class="card">
        <form action="registro_alumnos.php" method="post" autocomplete="off">
            <label for="nombre">Nombre del alumno:</label>
            <input id="nombre" name="nombre" type="text" required>

            <label for="email">Email:</label>
            <input id="email" name="email" type="email" required>

            <label for="telefono">Teléfono (opcional):</label>
            <input id="telefono" name="telefono" type="text">

            <label for="mensaje">Mensaje (opcional):</label>
            <textarea id="mensaje" name="mensaje" rows="3"></textarea>

            <label for="curso_id">Curso (opcional):</label>
            <select id="curso_id" name="curso_id">
                <option value="">-- ninguno --</option>
                <?php foreach ($cursos as $c):
                    $cursoId = isset($c['id']) ? (string)$c['id'] : '';
                    $cursoNombre = isset($c['nombre']) ? (string)$c['nombre'] : '';
                ?>
                    <option value="<?= htmlspecialchars($cursoId) ?>"><?= htmlspecialchars($cursoNombre) ?></option>
                <?php endforeach; ?>
            </select>

            <p style="margin-top:12px;">
                <button id="submit_registro_alumno" name="save" type="submit" class="btn btn-success"
                        style="display:inline-block!important;padding:8px 12px!important;cursor:pointer;position:relative;z-index:9999;">
                    Registrar alumno
                </button>

                <!-- Fallback seguro: botón sin clase ni dependencias de CSS -->
                <input id="submit_visible_fallback" type="submit" value="Guardar (visible)" 
                       style="display:inline-block;background:#007bff;color:#fff;padding:8px 12px;border-radius:4px;border:0;margin-left:8px;cursor:pointer;position:relative;z-index:9999;">

                <a class="btn btn-outline" href="listar_alumnos.php">Volver a alumnos</a>
            </p>
        </form>
    </div>
</div>
</body>
</html>