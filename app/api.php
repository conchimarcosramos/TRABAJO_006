<?php
/**
 * Archivo: api.php
 * Endpoints API REST para acceder a datos desde JavaScript/fetch
 * Ejemplo: GET http://localhost:8080/api.php?action=getUsuarios
 */
header('Content-Type: application/json; charset=utf-8');

require_once 'config/Database.php';

// Obtener acción desde query parameter
$action = $_GET['action'] ?? 'none';

try {
    $db = new Database();

    switch ($action) {
        // Obtener todos los usuarios
        case 'getUsuarios':
            $usuarios = $db->getUsuarios();
            echo json_encode([
                'success' => true,
                'data' => $usuarios,
                'total' => count($usuarios)
            ]);
            break;

        // Obtener todos los cursos
        case 'getCursos':
            $cursos = $db->getCursos();
            echo json_encode([
                'success' => true,
                'data' => $cursos,
                'total' => count($cursos)
            ]);
            break;

        // Crear usuario (solo POST)
        case 'crearUsuario':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Método no permitido']);
                break;
            }

            $nombre = $_POST['nombre'] ?? '';
            $email = $_POST['email'] ?? '';
            $telefono = $_POST['telefono'] ?? '';
            $mensaje = $_POST['mensaje'] ?? '';

            if (empty($nombre) || empty($email) || empty($mensaje)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Faltan campos obligatorios'
                ]);
                break;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Email inválido'
                ]);
                break;
            }

            if ($db->crearUsuario($nombre, $email, $telefono, $mensaje)) {
                http_response_code(201);
                echo json_encode([
                    'success' => true,
                    'message' => 'Usuario creado exitosamente'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Error al crear el usuario'
                ]);
            }
            break;

        // Acción no encontrada
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Acción no válida',
                'acciones_disponibles' => [
                    'getUsuarios' => 'GET',
                    'getCursos' => 'GET',
                    'crearUsuario' => 'POST'
                ]
            ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>