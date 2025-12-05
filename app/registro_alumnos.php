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
    $db = new Database();
    $pdo = $db->getConnection();
    if ($pdo) {
        // Obtener cursos para el select
        $stmt = $pdo->query('SELECT id, nombre FROM cursos ORDER BY nombre ASC');
        $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    error_log('[registro_alumnos] Error fetching cursos: ' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $curso_id = filter_input(INPUT_POST, 'curso_id', FILTER_VALIDATE_INT) ?: null;

    if ($nombre === '' || mb_strlen($nombre) < 2) { $errors[] = 'El nombre del alumno debe tener al menos 2 caracteres.'; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Introduce un email válido.'; }

    if (!$errors) {
        $db = new Database();
        $pdo = $db->getConnection();
        if (!$pdo) {
            $_SESSION['error'] = 'Error de conexión con la base de datos.';
            header('Location: registro_alumnos.php'); exit;
        }

        try {
            $stmt = $pdo->prepare('SELECT id FROM alumnos WHERE email = :email LIMIT 1');
            $stmt->execute(['email' => $email]);
            if ($stmt->fetch()) {
                $_SESSION['error'] = 'El email ya está registrado para otro alumno.';
                header('Location: registro_alumnos.php'); exit;
            }

            if ($curso_id !== null) {
                $cstmt = $pdo->prepare('SELECT id FROM cursos WHERE id = :id LIMIT 1');
                $cstmt->execute(['id' => $curso_id]);
                if (!$cstmt->fetch()) $curso_id = null;
            }

            $istmt = $pdo->prepare('INSERT INTO alumnos (nombre, email, curso_id) VALUES (:nombre, :email, :curso_id)');
            $ok = $istmt->execute(['nombre' => $nombre, 'email' => $email, 'curso_id' => $curso_id]);

            if ($ok) {
                $_SESSION['success'] = 'Alumno registrado correctamente.';
                header('Location: listar_alumnos.php'); exit;
            } else {
                $err = $istmt->errorInfo();
                error_log('[registro_alumnos] Insert failed: ' . json_encode($err));
                $_SESSION['error'] = 'Error interno al guardar el alumno. Contacta con el administrador.';
                header('Location: registro_alumnos.php'); exit;
            }
        } catch (PDOException $e) {
            error_log('[registro_alumnos] PDOException: ' . $e->getMessage());
            $_SESSION['error'] = 'Error interno al guardar el alumno. Contacta con el administrador.';
            header('Location: registro_alumnos.php'); exit;
        }
    }

    if (!empty($errors)) {
        $_SESSION['error'] = implode(' ', $errors);
        header('Location: registro_alumnos.php'); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Registrar Alumno</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Open+Sans:wght@400;600&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
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

            <label for="curso_id">Curso (opcional):</label>
            <select id="curso_id" name="curso_id">
                <option value="">-- ninguno --</option>
                <?php foreach ($cursos as $c): ?>
                    <option value="<?= htmlspecialchars($c['id']) ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                <?php endforeach; ?>
            </select>

            <p style="margin-top:12px;">
                <button class="btn btn-success" type="submit">Registrar alumno</button>
                <a class="btn btn-outline" href="listar_alumnos.php">Volver a alumnos</a>
            </p>
        </form>
    </div>
</div>
</body>
</html>