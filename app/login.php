<?php
session_start();
require_once __DIR__ . '/config/Database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $_SESSION['error'] = 'Usuario y contraseña son obligatorios.';
        header('Location: login.php');
        exit;
    }

    try {
        $db = new Database();
        $pdo = $db->getConnection();
        if (!$pdo) {
            $_SESSION['error'] = 'Error de conexión con la base de datos.';
            header('Location: login.php');
            exit;
        }

        $stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $_SESSION['error'] = 'Credenciales inválidas.';
            header('Location: login.php');
            exit;
        }

        $_SESSION['username'] = $username;
        $_SESSION['success'] = 'Has iniciado sesión correctamente.';
        header('Location: index.php');
        exit;
    } catch (Exception $e) {
        error_log('[login] ' . $e->getMessage());
        $_SESSION['error'] = 'Error de conexión. Inténtalo más tarde.';
        header('Location: login.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <h2>Iniciar Sesión</h2>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="message error"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="message success"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <div class="card">
        <form action="login.php" method="post" autocomplete="off">
            <label for="username">Usuario:</label>
            <input id="username" name="username" type="text" required>
            <label for="password">Contraseña:</label>
            <input id="password" name="password" type="password" required>
            <p style="margin-top:12px;">
                <button class="btn btn-primary" type="submit">Entrar</button>
                <a class="btn btn-outline" href="index.php">Volver</a>
            </p>
        </form>
    </div>
</div>
</body>
</html>