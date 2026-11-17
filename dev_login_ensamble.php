
<?php

// SOLO PARA PRUEBAS LOCALES.
// Eliminar antes de publicar el proyecto.

$ip = $_SERVER['REMOTE_ADDR'] ?? '';

if (!in_array($ip, ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit('Acceso no permitido.');
}

require_once __DIR__ . '/php/config/Database.php';

session_start();

try {
    $pdo = Database::conectar();

    $consulta = $pdo->prepare("
        SELECT ID_USUARIO, NOMBRE, APELLIDOS, ID_rol
        FROM usuarios
        WHERE ID_USUARIO = :id
          AND ID_rol = 'ROL-ADMIN'
        LIMIT 1
    ");

    $consulta->execute([
        'id' => 'USR-PRUEBA'
    ]);

    $usuario = $consulta->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        http_response_code(404);
        exit('No se encontró el usuario de prueba.');
    }

    session_regenerate_id(true);

    $_SESSION['usuario_id'] = $usuario['ID_USUARIO'];
    $_SESSION['usuario_nombre'] =
        $usuario['NOMBRE'] . ' ' . $usuario['APELLIDOS'];
    $_SESSION['usuario_rol'] = $usuario['ID_rol'];

    header('Location: /vistas/ensamble.html');
    exit;

} catch (Throwable $error) {
    error_log($error->getMessage());
    http_response_code(500);
    exit('No fue posible iniciar la sesión de prueba.');
}
