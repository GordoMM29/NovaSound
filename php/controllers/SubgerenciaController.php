<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../repositories/SubgerenciaRepository.php';
require_once __DIR__ . '/../services/SubgerenciaService.php';

session_start();
header('Cache-Control: no-store');

function respuestaJson(int $codigo, array $datos): void
{
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}

function campoCsv($valor): string
{
    $texto = str_replace(["\r", "\n", "\t"], ' ', (string)($valor ?? ''));
    // Evita que programas de hoja de cálculo interpreten celdas como fórmulas.
    if (preg_match('/^\s*[=+@-]/u', $texto)) $texto = "'" . $texto;
    return $texto;
}

try {
    $idUsuario = $_SESSION['usuario_id'] ?? null;
    if (!is_string($idUsuario) || $idUsuario === '') {
        respuestaJson(401, ['success' => false, 'error' => 'No hay sesión activa.']);
    }

    $pdo = Database::conectar();
    $aut = $pdo->prepare('SELECT ID_USUARIO, NOMBRE, APELLIDOS, ID_rol, AREA FROM usuarios WHERE ID_USUARIO = :id LIMIT 1');
    $aut->execute(['id' => $idUsuario]);
    $usuario = $aut->fetch(PDO::FETCH_ASSOC);
    if (!$usuario) {
        respuestaJson(401, ['success' => false, 'error' => 'La sesión ya no corresponde a un usuario válido.']);
    }
    $rol = strtoupper((string)$usuario['ID_rol']);
    $area = strtoupper((string)$usuario['AREA']);
    if ($rol !== 'ROL-ADMIN' && strpos($rol, 'SUBGER') === false && strpos($area, 'SUBGERENCIA') === false) {
        respuestaJson(403, ['success' => false, 'error' => 'No tienes permisos de Subgerencia.']);
    }

    if (empty($_SESSION['csrf_subgerencia'])) {
        $_SESSION['csrf_subgerencia'] = bin2hex(random_bytes(32));
    }

    $repo = new SubgerenciaRepository($pdo);
    $servicio = new SubgerenciaService($repo, $pdo);
    $metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($metodo === 'GET') {
        $accion = $_GET['accion'] ?? 'tablero';
        if ($accion === 'tablero') {
            respuestaJson(200, [
                'success' => true,
                'usuario' => trim($usuario['NOMBRE'] . ' ' . $usuario['APELLIDOS']),
                'csrf' => $_SESSION['csrf_subgerencia'],
                'datos' => $servicio->tablero()
            ]);
        }
        if (in_array($accion, ['reporte_lineas', 'reporte_ordenes'], true)) {
            $tipo = $accion === 'reporte_lineas' ? 'lineas' : 'ordenes';
            $filas = $servicio->reporte($tipo);
            $cabeceras = $tipo === 'lineas'
                ? ['ID_LINEA','LINEA','ORDENES','UNIDADES']
                : ['ID_ORDEN','ID_producto','PRODUCTO','CLIENTE','ID_linea','ID_turno','CANTIDAD_PRODUCIDA','COLOR','FECHA_INICIO','FECHA_COMPROMISO','FECHA_FIN','ESTATUS','TIPO_EMPAQUE','UNIDADES_REGISTRADAS'];
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="novasound_' . $tipo . '_' . date('Ymd') . '.csv"');
            $salida = fopen('php://output', 'w');
            fwrite($salida, "\xEF\xBB\xBF");
            fputcsv($salida, $cabeceras);
            foreach ($filas as $fila) {
                $celdas = [];
                foreach ($cabeceras as $columna) $celdas[] = campoCsv($fila[$columna] ?? '');
                fputcsv($salida, $celdas);
            }
            fclose($salida);
            exit;
        }
        respuestaJson(400, ['success' => false, 'error' => 'Consulta no válida.']);
    }

    if ($metodo === 'POST') {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!is_string($token) || !hash_equals($_SESSION['csrf_subgerencia'], $token)) {
            respuestaJson(403, ['success' => false, 'error' => 'Token de seguridad inválido.']);
        }
        $datos = json_decode(file_get_contents('php://input'), true);
        if (!is_array($datos)) respuestaJson(400, ['success' => false, 'error' => 'JSON inválido.']);
        $id = filter_var($datos['id_solicitud'] ?? null, FILTER_VALIDATE_INT);
        $accion = $datos['accion'] ?? '';
        if ($id === false || $id === null || !is_string($accion)) {
            respuestaJson(400, ['success' => false, 'error' => 'Datos de solicitud inválidos.']);
        }
        $estado = $servicio->decidirSolicitud($id, $accion);
        respuestaJson(200, ['success' => true, 'id_solicitud' => $id, 'estatus' => $estado]);
    }

    header('Allow: GET, POST');
    respuestaJson(405, ['success' => false, 'error' => 'Método no permitido.']);
} catch (InvalidArgumentException $e) {
    respuestaJson(400, ['success' => false, 'error' => $e->getMessage()]);
} catch (DomainException $e) {
    respuestaJson(409, ['success' => false, 'error' => $e->getMessage()]);
} catch (Throwable $e) {
    error_log('Subgerencia: ' . $e->getMessage());
    respuestaJson(500, ['success' => false, 'error' => 'Error del servidor. Revisa la terminal de PHP.']);
}
