<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../repositories/EnsambleRepository.php';
require_once __DIR__ . '/../services/EnsambleService.php';

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function responder(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if (!isset($_SESSION['usuario_id'])) {
    responder(401, ['success' => false, 'error' => 'Inicia sesión para acceder a Ensamble.']);
}

try {
    $pdo = Database::conectar();
    // El permiso se consulta en BD: no confiar en el rol enviado desde JavaScript.
    $stmt = $pdo->prepare('SELECT ID_USUARIO, NOMBRE, APELLIDOS, AREA, ID_rol
                           FROM usuarios WHERE ID_USUARIO = :id LIMIT 1');
    $stmt->execute(['id' => $_SESSION['usuario_id']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$usuario) responder(401, ['success' => false, 'error' => 'Sesión inválida.']);
    $area = strtolower(trim($usuario['AREA']));
    $esAdmin = $usuario['ID_rol'] === 'ROL-ADMIN';
    if (!$esAdmin && !in_array($area, ['produccion', 'producción', 'ensamble'], true)) {
        responder(403, ['success' => false, 'error' => 'No tienes permisos para Ensamble.']);
    }

    $servicio = new EnsambleService(new EnsambleRepository($pdo));
    $metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($metodo === 'GET') {
        if (!isset($_SESSION['csrf_ensamble'])) {
            $_SESSION['csrf_ensamble'] = bin2hex(random_bytes(32));
        }
        $orden = trim((string) ($_GET['orden'] ?? ''));
        responder(200, [
            'success' => true,
            'usuario' => trim($usuario['NOMBRE'] . ' ' . $usuario['APELLIDOS']),
            'csrf' => $_SESSION['csrf_ensamble'],
            'registros' => $servicio->listar($orden)
        ]);
    }

    if ($metodo !== 'POST') {
        header('Allow: GET, POST');
        responder(405, ['success' => false, 'error' => 'Método no permitido.']);
    }
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_ensamble'] ?? '', $token)) {
        responder(403, ['success' => false, 'error' => 'Token de seguridad inválido. Actualiza la página.']);
    }
    $datos = json_decode(file_get_contents('php://input'), true);
    if (!is_array($datos)) responder(400, ['success' => false, 'error' => 'JSON inválido.']);
    $accion = (string) ($datos['accion'] ?? '');
    $resultado = match ($accion) {
        'registrar' => $servicio->registrar($datos, $usuario['ID_USUARIO']),
        'enviar_pruebas' => $servicio->enviar($datos),
        'incidente' => ['id_incidencia' => $servicio->incidente($datos, $usuario['ID_USUARIO'])],
        default => throw new InvalidArgumentException('Acción desconocida.')
    };
    responder(200, ['success' => true, 'resultado' => $resultado]);
} catch (InvalidArgumentException | DomainException $e) {
    responder(422, ['success' => false, 'error' => $e->getMessage()]);
} catch (Throwable $e) {
    error_log('Ensamble: ' . $e->getMessage());
    responder(500, ['success' => false, 'error' => 'Error del servidor. Revisa la terminal de PHP.']);
}
