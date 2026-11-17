
<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../repositories/ProductoRepository.php';
require_once __DIR__ . '/../services/ProductoService.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        header('Allow: GET');
        http_response_code(405);

        echo json_encode([
            'error' => 'Método no permitido'
        ]);
        exit;
    }

    $conexion = Database::conectar();

    $repositorio = new ProductoRepository($conexion);
    $servicio = new ProductoService($repositorio);

    $productos = $servicio->listarProductos();

    echo json_encode([
        'success' => true,
        'productos' => $productos
    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

} catch (Throwable $error) {
    error_log($error->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error' => $error->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
