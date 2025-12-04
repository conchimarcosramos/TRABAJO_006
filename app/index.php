<?php
// Iniciamos la sesión para poder mostrar mensajes y datos del usuario
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>Bienvenido a la Aplicación de Gestión</h1>

        <!-- Mensajes guardados en sesión: success / error -->
        <?php if (!empty($_SESSION['success'])): ?>
            <div class="message success"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error'])): ?>
            <div class="message error"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Comprobamos si el usuario está autenticado -->
        <?php if (!empty($_SESSION['username'])): ?>
            <div class="card">
                <h2>Hola, <?= htmlspecialchars($_SESSION['username']); ?></h2>
                <p>Has iniciado sesión correctamente. Usa las acciones disponibles abajo.</p>
                <p>
                    <a class="btn btn-primary" href="dashboard.php">Ir al panel</a>
                    <a class="btn btn-outline" href="logout.php">Cerrar sesión</a>
                </p>
            </div>
        <?php else: ?>
            <div class="card">
                <p>Aún no has iniciado sesión. Accede o regístrate para comenzar.</p>
                <p>
                    <a class="btn btn-primary" href="login.php">Iniciar sesión</a>
                    <a class="btn btn-outline" href="registro.php">Registrarse</a>
                </p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>