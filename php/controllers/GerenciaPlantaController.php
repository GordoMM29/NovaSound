<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../repositories/GerenciaPlantaRepository.php';
require_once __DIR__ . '/../services/GerenciaPlantaService.php';

session_start();
header('Cache-Control: no-store');

function responder(int $codigo, array $datos): never
{
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}

if (!isset($_SESSION['usuario_id'])) {
    responder(401, ['success' => false, 'error' => 'Debes iniciar sesión.']);
}

// De momento ROL-ADMIN es el único rol directivo definido en el SQL.
// Ajustar la lista cuando el equipo implemente permisos específicos de Gerencia.
$rol = (string) ($_SESSION['usuario_rol'] ?? '');
if ($rol !== 'ROL-ADMIN') {
    responder(403, ['success' => false, 'error' => 'No tienes permisos para consultar Gerencia de Planta.']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    responder(405, ['success' => false, 'error' => 'Método no permitido.']);
}

try {
    $servicio = new GerenciaPlantaService(
        new GerenciaPlantaRepository(Database::conectar())
    );
    $accion = (string) ($_GET['accion'] ?? 'panel');

    if ($accion === 'panel') {
        responder(200, [
            'success' => true,
            'usuario' => (string) ($_SESSION['usuario_nombre'] ?? 'Usuario'),
            'datos' => $servicio->panel(),
        ]);
    }

    if ($accion !== 'descargar') {
        responder(400, ['success' => false, 'error' => 'Acción desconocida.']);
    }

    $tipo = (string) ($_GET['tipo'] ?? '');
    $columnas = match ($tipo) {
        'incidencias' => ['ID_INCIDENCIA','ID_ORDEN','ID_USUARIO','ID_PRODUCTO','FECHA','DESCRIPCION'],
        'embarques' => ['ID_EMBARQUE','CLIENTE','FECHA_SALIDA','FECHA_PROMESA','DESTINO','ESTATUS'],
        'lineas' => ['ID_LINEA','LINEA','ORDENES','UNIDADES'],
        default => throw new InvalidArgumentException('Tipo de reporte no permitido.'),
    };
    $filas = $servicio->reporte($tipo);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="NovaSound_' . $tipo . '_' . date('Ymd_His') . '.csv"');
    echo "\xEF\xBB\xBF";
    $salida = fopen('php://output', 'wb');
    fputcsv($salida, $columnas);
    foreach ($filas as $fila) {
        $valores = [];
        foreach ($columnas as $campo) {
            $valor = (string) ($fila[$campo] ?? '');
            // Evita ejecución de fórmulas al abrir CSV en Excel/LibreOffice.
            if (preg_match('/^[\s]*[=+@\-]/u', $valor)) {
                $valor = "'" . $valor;
            }
            $valores[] = $valor;
        }
        fputcsv($salida, $valores);
    }
    fclose($salida);
} catch (InvalidArgumentException $e) {
    responder(400, ['success' => false, 'error' => $e->getMessage()]);
} catch (Throwable $e) {
    error_log('GerenciaPlanta: ' . $e->getMessage());
    responder(500, ['success' => false, 'error' => 'No fue posible consultar los datos de Gerencia de Planta.']);
}
