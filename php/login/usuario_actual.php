
<?php

session_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);

    echo json_encode([
        'success' => false,
        'error' => 'No hay una sesión activa'
    ]);

    exit;
}

echo json_encode([
    'success' => true,
    'usuario' => [
        'id' => $_SESSION['usuario_id'],
        'nombre' => $_SESSION['usuario_nombre'] ?? 'Usuario'
    ]
], JSON_UNESCAPED_UNICODE);
