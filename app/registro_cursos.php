<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/config/Database.php';

if (empty($_SESSION['username'])) {
    $_SESSION['error'] = 'Debes iniciar sesión para crear cursos.'; header('Location: login.php'); exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $duracion = filter_input(INPUT_POST, 'duracion_horas', FILTER_VALIDATE_INT, ['options' => ['default' => null]]);

    @mkdir(__DIR__ . '/logs', 0755, true);
    file_put_contents(__DIR__ . '/logs/registro_cursos_post.log', date('c') . ' - POST: ' . json_encode(compact('nombre','descripcion','duracion')) . PHP_EOL, FILE_APPEND);

    if ($nombre === '' || mb_strlen($nombre) < 2) { $errors[] = 'El nombre del curso debe tener al menos 2 caracteres.'; }

    if (!$errors) {
        $pdo = (new Database())->getConnection();
        if (!$pdo) { $_SESSION['error'] = 'Error de conexión con la base de datos.'; header('Location: registro_cursos.php'); exit; }
        try {
            $chk = $pdo->prepare('SELECT id FROM cursos WHERE lower(nombre) = lower(:nombre) LIMIT 1');
            $chk->execute(['nombre' => $nombre]);
            if ($chk->fetch()) { $_SESSION['error'] = 'Ya existe un curso con ese nombre.'; header('Location: registro_cursos.php'); exit; }

            $stmt = $pdo->prepare('INSERT INTO cursos (nombre, descripcion, duracion_horas) VALUES (:nombre, :descripcion, :duracion)');
            $ok = $stmt->execute(['nombre' => $nombre, 'descripcion' => $descripcion, 'duracion' => $duracion]);

            if ($ok && $stmt->rowCount() > 0) {
                $_SESSION['success'] = 'Curso creado correctamente.'; header('Location: listar_cursos.php'); exit;
            } else {
                $err = $stmt->errorInfo(); file_put_contents(__DIR__ . '/logs/registro_cursos_errors.log', date('c') . ' - ' . json_encode($err) . PHP_EOL, FILE_APPEND);
                $_SESSION['error'] = 'Error interno al guardar el curso.'; header('Location: registro_cursos.php'); exit;
            }
        } catch (PDOException $e) {
            error_log('[registro_cursos] PDOException: ' . $e->getMessage());
            $_SESSION['error'] = 'Error interno al guardar el curso.'; header('Location: registro_cursos.php'); exit;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Registrar Curso</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Open+Sans:wght@400;600&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <h2>Registrar curso</h2>

    <?php if (!empty($_SESSION['error'])): ?><div class="message error"><?= htmlspecialchars($_SESSION['error']) ?></div><?php unset($_SESSION['error']); endif;?>
    <?php if (!empty($_SESSION['success'])): ?><div class="message success"><?= htmlspecialchars($_SESSION['success']) ?></div><?php unset($_SESSION['success']); endif;?>

    <div class="card">
        <form action="registro_cursos.php" method="post" autocomplete="off">
            <label for="nombre">Nombre del curso:</label>
            <input id="nombre" name="nombre" type="text" required>

            <label for="descripcion">Descripción (opcional):</label>
            <textarea id="descripcion" name="descripcion" rows="4"></textarea>

            <label for="duracion_horas">Duración (horas, opcional):</label>
            <input id="duracion_horas" name="duracion_horas" type="number" min="0">

            <p style="margin-top:12px;">
                <button class="btn btn-success" type="submit">Crear curso</button>
                <input type="submit" class="btn btn-success" style="display:none" value="Crear curso (fallback)">
                <a class="btn btn-outline" href="listar_cursos.php">Volver a cursos</a>
            </p>
        </form>
    </div>
</div>
</body>
</html>