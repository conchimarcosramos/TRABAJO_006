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
    <p>
        <a class="btn btn-primary" href="listar_usuarios.php">Ver usuarios</a>
        <a class="btn btn-primary" href="listar_alumnos.php">Ver alumnos</a>
        <a class="btn btn-primary" href="listar_cursos.php">Ver cursos</a>
    </p>
    <p>
        <a class="btn btn-success" href="registro_alumnos.php">Registrar alumno</a> <!-- confirmado -->
        <a class="btn btn-success" href="registro_cursos.php">Registrar curso</a>
        <a class="btn btn-outline" href="index.php">Volver</a>
    </p>
</div>
</body>
</html>