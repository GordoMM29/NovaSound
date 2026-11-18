<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../repositories/EmpaqueRepository.php';
require_once __DIR__ . '/../services/EmpaqueService.php';

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respuestaEmpaque(int $estado, array $datos): never
{
    http_response_code($estado);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if (empty($_SESSION['usuario_id'])) {
    respuestaEmpaque(401, ['success'=>false, 'error'=>'Inicia sesión para acceder a Empaque.']);
}

try {
    $pdo = Database::conectar();
    $stmt = $pdo->prepare('SELECT ID_USUARIO, NOMBRE, APELLIDOS, AREA, ID_rol FROM usuarios WHERE ID_USUARIO=:id LIMIT 1');
    $stmt->execute(['id'=>$_SESSION['usuario_id']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$usuario) respuestaEmpaque(401, ['success'=>false, 'error'=>'La sesión no corresponde a un usuario válido.']);
    $area = strtolower(trim((string)$usuario['AREA']));
    if ($usuario['ID_rol'] !== 'ROL-ADMIN' && $area !== 'Empaque') {
        respuestaEmpaque(403, ['success'=>false, 'error'=>'No tienes permisos para Empaque.']);
    }

    $servicio = new EmpaqueService(new EmpaqueRepository($pdo));
    $metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($metodo === 'GET') {
        $_SESSION['csrf_empaque'] ??= bin2hex(random_bytes(32));
        respuestaEmpaque(200, ['success'=>true,
            'usuario'=>trim($usuario['NOMBRE'].' '.$usuario['APELLIDOS']),
            'csrf'=>$_SESSION['csrf_empaque'], ...$servicio->consultar()]);
    }
    if ($metodo !== 'POST') {
        header('Allow: GET, POST');
        respuestaEmpaque(405, ['success'=>false, 'error'=>'Método no permitido.']);
    }
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($token) || empty($_SESSION['csrf_empaque']) ||
        !hash_equals($_SESSION['csrf_empaque'], $token)) {
        respuestaEmpaque(403, ['success'=>false, 'error'=>'Token de seguridad inválido. Recarga la página.']);
    }
    $datos = json_decode(file_get_contents('php://input'), true);
    if (!is_array($datos)) respuestaEmpaque(400, ['success'=>false, 'error'=>'JSON inválido.']);
    respuestaEmpaque(200, ['success'=>true,
        'resultado'=>$servicio->ejecutar($datos, $usuario['ID_USUARIO'])]);
} catch (DomainException | InvalidArgumentException $e) {
    respuestaEmpaque(422, ['success'=>false, 'error'=>$e->getMessage()]);
} catch (Throwable $e) {
    error_log('Empaque: '.$e->getMessage());
    respuestaEmpaque(500, ['success'=>false, 'error'=>'Error del servidor. Revisa la terminal de PHP.']);
}
