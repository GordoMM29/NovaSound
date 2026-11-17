
<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../repositories/OrdenRepository.php';
require_once __DIR__ . '/../services/OrdenService.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    http_response_code(405);

    echo json_encode([
        'success' => false,
        'error' => 'Método no permitido'
    ]);
    exit;
}

try {
    $conexion = Database::conectar();

    $repositorio = new OrdenRepository($conexion);
    $servicio = new OrdenService($repositorio);

    echo json_encode([
        'success' => true,
        'ordenes' => $servicio->obtenerOrdenes()
    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

} catch (Throwable $error) {
    error_log($error->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error' => 'No fue posible consultar las órdenes'
    ]);
}
