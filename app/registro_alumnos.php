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
    $curso_ids_raw = $_POST['curso_ids'] ?? []; // puede venir como array o no
    $curso_ids = [];
    if (is_array($curso_ids_raw)) {
        foreach ($curso_ids_raw as $cId) {
            $c = filter_var($cId, FILTER_VALIDATE_INT);
            if ($c !== false && $c !== null) $curso_ids[] = (int)$c;
        }
    }

    @mkdir(__DIR__ . '/logs', 0755, true);
    file_put_contents(__DIR__ . '/logs/registro_alumnos_post.log', date('c') . ' - POST: ' . json_encode(compact('nombre','email','telefono','mensaje','curso_ids')) . PHP_EOL, FILE_APPEND);

    if ($nombre === '' || mb_strlen($nombre) < 2) { $errors[] = 'El nombre del alumno debe tener al menos 2 caracteres.'; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Introduce un email válido.'; }

    if (!$errors) {
        $db = new Database();
        $pdo = $db->getConnection();
        try {
            $pdo->beginTransaction();
            $istmt = $pdo->prepare('INSERT INTO alumnos (nombre, email, telefono, mensaje, curso_id) VALUES (:nombre, :email, :telefono, :mensaje, :curso_id)');
            // Insertamos con curso_id NULL (ya que mantuvimos la columna para compatibilidad)
            $istmt->execute([
                'nombre' => $nombre,
                'email' => $email,
                'telefono' => $telefono ?: null,
                'mensaje' => $mensaje ?: null,
                'curso_id' => null
            ]);
            // PostgreSQL retorna el ID automáticamente con lastInsertId()
            $alumnoId = (int)$pdo->lastInsertId();
            if (!$alumnoId) {
                // Fallback: usar RETURNING
                throw new Exception('No se pudo obtener el ID del alumno insertado');
            }

            if (!empty($curso_ids)) {
                $pstmt = $pdo->prepare('INSERT INTO alumnos_cursos (alumno_id, curso_id) VALUES (:alumno_id, :curso_id) ON CONFLICT DO NOTHING');
                foreach ($curso_ids as $cid) {
                    $pstmt->execute(['alumno_id' => $alumnoId, 'curso_id' => $cid]);
                }
            }
            $pdo->commit();
            $_SESSION['success'] = 'Alumno registrado correctamente.';
            header('Location: listar_alumnos.php');
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errorMsg = $e->getMessage();
            $errorFile = $e->getFile();
            $errorLine = $e->getLine();
            
            error_log("[registro_alumnos] Error: {$errorMsg} in {$errorFile}:{$errorLine}");
            @file_put_contents(__DIR__ . '/logs/registro_alumnos_exceptions.log', 
                date('c') . " - {$errorMsg}\nFile: {$errorFile}:{$errorLine}\nTrace:\n{$e->getTraceAsString()}\n\n", 
                FILE_APPEND
            );
            
            // TEMPORAL: mostrar error completo (QUITAR EN PRODUCCIÓN)
            $_SESSION['error'] = "Error al guardar: " . htmlspecialchars($errorMsg) . " (línea {$errorLine})";
            header('Location: registro_alumnos.php');
            exit;
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

            <label for="curso_ids">Cursos (mantén pulsada Ctrl/Cmd para seleccionar varios):</label>
            <select id="curso_ids" name="curso_ids[]" multiple size="4">
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