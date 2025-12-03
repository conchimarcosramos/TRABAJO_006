<?php
session_start();
require_once __DIR__ . '/config/Database.php';

$errors = [];

// Procesamos registro cuando el método es POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    // Validaciones básicas
    if (strlen($username) < 3) {
        $errors[] = 'El nombre de usuario debe tener al menos 3 caracteres.';
    }
    if (preg_match('/\s/', $username)) {
        $errors[] = 'El nombre de usuario no puede contener espacios.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'La contraseña debe tener al menos 6 caracteres.';
    }
    if ($password !== $password2) {
        $errors[] = 'Las contraseñas no coinciden.';
    }

    if (!$errors) {
        $db = new Database();
        $pdo = $db->getConnection();
        if (!$pdo) {
            $_SESSION['error'] = 'Error de conexión con la base de datos.';
            header('Location: registro.php');
            exit;
        }

        try {
            // Comprobar si ya existe el usuario
            $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username LIMIT 1');
            $stmt->execute(['username' => $username]);
            if ($stmt->fetch()) {
                $errors[] = 'El nombre de usuario ya existe.';
            } else {
                // Insertar usuario con contraseña hasheada
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $insert = $pdo->prepare('INSERT INTO users (username, password_hash) VALUES (:username, :hash)');
                $insert->execute(['username' => $username, 'hash' => $hash]);
                $_SESSION['success'] = 'Registro correcto. Ya puedes iniciar sesión.';
                header('Location: index.php');
                exit;
            }
        } catch (Exception $e) {
            error_log('Registro error: ' . $e->getMessage());
            $_SESSION['error'] = 'Error interno al guardar usuario. Contacta con el administrador.';
            header('Location: registro.php');
            exit;
        }
    }

    // Si hay errores de validación
    if (!empty($errors)) {
        $_SESSION['error'] = implode(' ', $errors);
        header('Location: registro.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <h2>Registro</h2>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="message error"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <form action="registro.php" method="post" autocomplete="off">
        <label for="username">Usuario:</label>
        <input id="username" name="username" type="text" required>
        <label for="password">Contraseña:</label>
        <input id="password" name="password" type="password" required>
        <label for="password2">Repetir contraseña:</label>
        <input id="password2" name="password2" type="password" required>
        <button class="btn" type="submit">Registrar</button>
    </form>

    <p><a href="index.php">Volver</a></p>
</div>
</body>
</html>