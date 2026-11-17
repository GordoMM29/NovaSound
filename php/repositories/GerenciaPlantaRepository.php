<?php
declare(strict_types=1);

require_once __DIR__ . '/../interfaces/GerenciaPlantaRepositoryInterface.php';

final class GerenciaPlantaRepository implements GerenciaPlantaRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    private function uno(string $sql): array
    {
        return $this->pdo->query($sql)->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public function resumen(): array
    {
        $produccion = $this->uno("SELECT
            (SELECT COUNT(*) FROM ordenes_produccion) AS ordenes,
            (SELECT COUNT(*) FROM unidades) AS unidades,
            (SELECT COUNT(*) FROM lineas) AS lineas");

        $calidad = $this->uno("SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN UPPER(ESTATUS_CALIDAD) IN ('APROBADO','APROBADA') THEN 1 ELSE 0 END) AS aprobadas,
            SUM(CASE WHEN UPPER(ESTATUS_CALIDAD) IN ('RECHAZADO','RECHAZADA','NO APROBADO') THEN 1 ELSE 0 END) AS rechazadas
            FROM unidades");

        $almacen = $this->uno("SELECT
            (SELECT COUNT(*) FROM cajas) AS cajas,
            (SELECT COUNT(*) FROM cajas WHERE UPPER(ESTATUS) = 'CERRADA') AS cajas_cerradas,
            (SELECT COUNT(*) FROM embarques) AS embarques,
            (SELECT COUNT(*) FROM incidencias) AS incidencias");

        $total = (int) ($calidad['total'] ?? 0);
        $aprobadas = (int) ($calidad['aprobadas'] ?? 0);
        $rechazadas = (int) ($calidad['rechazadas'] ?? 0);

        return [
            'produccion' => [
                'ordenes' => (int) $produccion['ordenes'],
                'unidades' => (int) $produccion['unidades'],
                'lineas' => (int) $produccion['lineas'],
            ],
            'calidad' => [
                'total' => $total,
                'aprobadas' => $aprobadas,
                'rechazadas' => $rechazadas,
                'otras' => max(0, $total - $aprobadas - $rechazadas),
            ],
            'almacen' => [
                'cajas' => (int) $almacen['cajas'],
                'cajas_cerradas' => (int) $almacen['cajas_cerradas'],
                'embarques' => (int) $almacen['embarques'],
                'incidencias' => (int) $almacen['incidencias'],
            ],
        ];
    }

    public function produccionPorLinea(): array
    {
        $sql = "SELECT l.ID_LINEA, l.NOMBRE AS LINEA,
                       COUNT(DISTINCT o.ID_ORDEN) AS ORDENES,
                       COUNT(u.NUMERO_SERIE) AS UNIDADES
                FROM lineas l
                LEFT JOIN ordenes_produccion o ON o.ID_linea = l.ID_LINEA
                LEFT JOIN unidades u ON u.ID_orden = o.ID_ORDEN
                GROUP BY l.ID_LINEA, l.NOMBRE
                ORDER BY l.ID_LINEA";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function incidencias(): array
    {
        $sql = "SELECT i.ID_INCIDENCIA, i.ID_orden AS ID_ORDEN,
                       i.ID_usuario AS ID_USUARIO, i.ID_producto AS ID_PRODUCTO,
                       DATE_FORMAT(i.FECHA, '%Y-%m-%d %H:%i:%s') AS FECHA,
                       i.DESCRIPCION
                FROM incidencias i ORDER BY i.FECHA DESC, i.ID_INCIDENCIA DESC LIMIT 1000";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function embarques(): array
    {
        $sql = "SELECT e.ID_EMBARQUE, c.NOMBRE AS CLIENTE,
                       DATE_FORMAT(e.FECHA_EMBARQUE, '%Y-%m-%d %H:%i:%s') AS FECHA_SALIDA,
                       DATE_FORMAT(o.FECHA_COMPROMISO, '%Y-%m-%d %H:%i:%s') AS FECHA_PROMESA,
                       e.DESTINO, e.ESTATUS
                FROM embarques e
                INNER JOIN cajas ca ON ca.ID_CAJA = e.ID_caja
                INNER JOIN ordenes_produccion o ON o.ID_ORDEN = ca.ID_orden
                INNER JOIN clientes c ON c.ID_CLIENTE = o.ID_cliente
                ORDER BY e.FECHA_EMBARQUE DESC, e.ID_EMBARQUE DESC LIMIT 1000";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
