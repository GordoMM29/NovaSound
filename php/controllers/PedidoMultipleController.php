<?php
declare(strict_types=1);

ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
session_start();

try {
    require_once __DIR__ . '/../config/Database.php';
    require_once __DIR__ . '/../repositories/PedidoMultipleRepository.php';
    require_once __DIR__ . '/../services/PedidoMultipleService.php';
    $service = new PedidoMultipleService(new PedidoMultipleRepository(Database::conectar()));

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (!isset($_SESSION['csrf_pedido'])) $_SESSION['csrf_pedido'] = bin2hex(random_bytes(32));
        echo json_encode(['success' => true, 'productos' => $service->catalogo(),
            'csrf' => $_SESSION['csrf_pedido']], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Allow: GET, POST');
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido.']);
        exit;
    }
    if (strlen((string)($_SERVER['CONTENT_LENGTH'] ?? '0')) > 50000) {
        throw new InvalidArgumentException('La solicitud es demasiado grande.');
    }
    $input = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($input) || !hash_equals((string)($_SESSION['csrf_pedido'] ?? ''), (string)($input['csrf'] ?? '')) || empty($_SESSION['csrf_pedido'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Sesión de pedido inválida. Actualiza la página.']);
        exit;
    }
    $resultado = $service->crear($input);
    $_SESSION['csrf_pedido'] = bin2hex(random_bytes(32));
    echo json_encode(['success' => true, 'pedido' => $resultado, 'csrf' => $_SESSION['csrf_pedido']], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
} catch (InvalidArgumentException | JsonException $e) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('PedidoMultiple: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno al guardar el pedido. Revisa el registro de PHP y la migración SQL.'], JSON_UNESCAPED_UNICODE);
}
