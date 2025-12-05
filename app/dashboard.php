<?php
// Página privada: requiere estar autenticado
session_start();

if (empty($_SESSION['username'])) {
    $_SESSION['error'] = 'Debes iniciar sesión para acceder al dashboard.';
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <h2>Área interna / Dashboard</h2>
    <p>Bienvenido, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></p>
    <p><a href="logout.php" class="btn btn-logout">Cerrar sesión</a></p>
    <p><a href="index.php" class="btn">Volver a inicio</a></p>
</div>
<div class="card">
    <h2>Panel</h2>
    <p>Contenido del panel...</p>

    <!-- Añadir botón para ver listado de usuarios -->
    <p>
        <a class="btn btn-primary" href="listar_usuarios.php">Ver usuarios</a>
        <!-- ...otros botones existentes... -->
    </p>
</div>
</body>
</html>