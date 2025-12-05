<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Open+Sans:wght@400;600&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>Bienvenido a la Aplicación</h1>
        <?php if (!empty($_SESSION['success'])): ?>
            <div class="message success"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        <?php if (!empty($_SESSION['error'])): ?>
            <div class="message error"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        <?php if (!empty($_SESSION['username'])): ?>
            <div class="card">
                <h2>Hola, <?= htmlspecialchars($_SESSION['username']); ?></h2>
                <p>Has iniciado sesión correctamente.</p>
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