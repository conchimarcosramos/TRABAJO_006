<?php
session_start();
require_once __DIR__ . '/config/Database.php';

$errors = [];

// Procesamos registro cuando el método es POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    // Validaciones básicas
    if (strlen($username) < 3) { $errors[] = 'El nombre de usuario debe tener al menos 3 caracteres.'; }
    if (preg_match('/\s/', $username)) { $errors[] = 'El nombre de usuario no puede contener espacios.'; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Introduce un email válido.'; }
    if (strlen($password) < 6) { $errors[] = 'La contraseña debe tener al menos 6 caracteres.'; }
    if ($password !== $password2) { $errors[] = 'Las contraseñas no coinciden.'; }

    if (!$errors) {
        $db = new Database();
        $pdo = $db->getConnection();
        if (!$pdo) {
            $_SESSION['error'] = 'Error de conexión con la base de datos.';
            header('Location: registro.php');
            exit;
        }

        try {
            // Comprobar si ya existe el usuario o el email
            $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1');
            $stmt->execute(['username' => $username, 'email' => $email]);
            if ($stmt->fetch()) {
                $errors[] = 'El nombre de usuario o el email ya existen.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $insert = $pdo->prepare('INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :hash)');
                $ok = $insert->execute(['username' => $username, 'email' => $email, 'hash' => $hash]);

                if ($ok) {
                    $_SESSION['success'] = 'Registro correcto. Ya puedes iniciar sesión.';
                    header('Location: index.php');
                    exit;
                } else {
                    $err = $insert->errorInfo();
                    error_log('[registro] Insert failed: ' . json_encode($err));
                    $_SESSION['error'] = 'Error interno al guardar usuario. Contacta con el administrador.';
                    header('Location: registro.php');
                    exit;
                }
            }
        } catch (PDOException $e) {
            error_log('[registro] PDOException: ' . $e->getMessage());
            $_SESSION['error'] = 'Error interno al guardar usuario. Contacta con el administrador.';
            header('Location: registro.php');
            exit;
        }
    }

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

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="message success"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <div class="card">
        <form action="registro.php" method="post" autocomplete="off">
            <label for="username">Usuario:</label>
            <input id="username" name="username" type="text" required>
            <label for="email">Email:</label>
            <input id="email" name="email" type="email" required>
            <label for="password">Contraseña:</label>
            <input id="password" name="password" type="password" required>
            <label for="password2">Repetir contraseña:</label>
            <input id="password2" name="password2" type="password" required>
            <p style="margin-top:12px;">
                <button class="btn btn-success" type="submit">Registrar</button>
                <a class="btn btn-outline" href="index.php">Volver</a>
            </p>
        </form>
    </div>
</div>
</body>
</html>