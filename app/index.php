<?php
/**
 * Archivo: index.php
 * Formulario principal con conexión a PostgreSQL
 */

// Incluir clase de base de datos
require_once 'config/Database.php';

// Inicializar variables
$mensaje_exito = '';
$mensaje_error = '';
$usuarios = [];
$cursos = [];

// Crear instancia de la base de datos
$db = new Database();

// Procesar formulario si se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar datos
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $mensaje = trim($_POST['mensaje'] ?? '');

    // Validaciones básicas
    if (empty($nombre) || empty($email) || empty($mensaje)) {
        $mensaje_error = "Por favor, completa los campos obligatorios (nombre, email, mensaje).";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje_error = "El email no es válido.";
    } else {
        try {
            // Insertar en base de datos
            if ($db->crearUsuario($nombre, $email, $telefono, $mensaje)) {
                $mensaje_exito = "✓ Registro guardado exitosamente en PostgreSQL.";
                // Limpiar formulario
                $_POST = [];
            } else {
                $mensaje_error = "Error al guardar los datos.";
            }
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'duplicate key') !== false) {
                $mensaje_error = "Este email ya está registrado.";
            } else {
                $mensaje_error = "Error en la base de datos: " . $e->getMessage();
            }
        }
    }
}

// Obtener datos para mostrar
$usuarios = $db->getUsuarios();
$cursos = $db->getCursos();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario - Base de datos - Docker - PostgreSQL - PHP 8.2</title>
    <!-- Enlace al CSS externo generado: css/style.css -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎓 Formulario BD </h1>
            <p>Docker Compose, PHP 8.2 y PostgreSQL 16</p>
        </div>

        <div class="grid">
            <!-- FORMULARIO DE REGISTRO -->
            <div class="card">
                <h2>📝 Formulario de Registro</h2>

                <?php if (!empty($mensaje_exito)): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($mensaje_exito) ?></div>
                <?php endif; ?>

                <?php if (!empty($mensaje_error)): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($mensaje_error) ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="nombre">Nombre <span class="required">*</span></label>
                        <input 
                            type="text" 
                            id="nombre" 
                            name="nombre" 
                            value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="email">Email <span class="required">*</span></label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="telefono">Teléfono</label>
                        <input 
                            type="tel" 
                            id="telefono" 
                            name="telefono" 
                            value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="mensaje">Mensaje <span class="required">*</span></label>
                        <textarea 
                            id="mensaje" 
                            name="mensaje" 
                            required
                        ><?= htmlspecialchars($_POST['mensaje'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" class="btn">Enviar Registro</button>
                </form>

                <div class="meta-info">
                    <strong>Credenciales BD:</strong>
                    <p>Host: <code>db</code></p>
                    <p>Usuario: <code>developer</code></p>
                    <p>Base de datos: <code>teleformation_db</code></p>
                </div>
            </div>

            <!-- INFORMACIÓN -->
            <div class="card">
                <h2>📊 Información del Sistema</h2>

                <div class="stat-box">
                    <strong>Registros en Base de Datos:</strong>
                    <p class="stat-number">
                        <?= count($usuarios) ?>
                    </p>
                </div>

                <h3 class="section-subtitle">📚 Cursos Disponibles</h3>

                <?php if (empty($cursos)): ?>
                    <div class="empty-state">No hay cursos disponibles</div>
                <?php else: ?>
                    <div class="cursos-grid">
                        <?php foreach ($cursos as $curso): ?>
                            <div class="curso-card">
                                <h3><?= htmlspecialchars($curso['nombre']) ?></h3>
                                <p><?= htmlspecialchars($curso['descripcion']) ?></p>
                                <span class="badge">⏱️ <?= $curso['duracion_horas'] ?> horas</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ÚLTIMOS REGISTROS -->
        <div class="card">
            <h2>📋 Últimos Registros</h2>

            <?php if (empty($usuarios)): ?>
                <div class="empty-state">No hay registros aún. ¡Completa el formulario!</div>
            <?php else: ?>
                <div class="usuarios-list">
                    <?php foreach ($usuarios as $usuario): ?>
                        <div class="usuario-item">
                            <strong><?= htmlspecialchars($usuario['nombre']) ?></strong>
                            <div class="email">📧 <?= htmlspecialchars($usuario['email']) ?></div>
                            <?php if ($usuario['telefono']): ?>
                                <div class="email">📱 <?= htmlspecialchars($usuario['telefono']) ?></div>
                            <?php endif; ?>
                            <div class="email">💬 <?= htmlspecialchars(substr($usuario['mensaje'], 0, 50)) ?>...</div>
                            <div class="fecha">📅 <?= date('d/m/Y H:i', strtotime($usuario['fecha_registro'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>