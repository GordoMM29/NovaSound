<?php
require_once __DIR__ . '/../interfaces/SubgerenciaRepositoryInterface.php';

class SubgerenciaRepository implements SubgerenciaRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function resumenLineas(): array
    {
        $sql = "SELECT l.ID_LINEA, l.NOMBRE AS LINEA,
                       COUNT(DISTINCT o.ID_ORDEN) AS ORDENES,
                       COUNT(DISTINCT u.NUMERO_SERIE) AS UNIDADES
                FROM lineas l
                LEFT JOIN ordenes_produccion o ON o.ID_linea = l.ID_LINEA
                LEFT JOIN unidades u ON u.ID_orden = o.ID_ORDEN
                GROUP BY l.ID_LINEA, l.NOMBRE
                ORDER BY l.ID_LINEA";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarSolicitudes(): array
    {
        $sql = "SELECT s.ID_SOLICITUD, s.OBJETO, s.ID_usuario,
                       CONCAT(u.NOMBRE, ' ', u.APELLIDOS) AS USUARIO,
                       s.FECHA_SOLICITUD, s.MOTIVO, s.ESTATUS
                FROM solicitudes_cambios s
                JOIN usuarios u ON u.ID_USUARIO = s.ID_usuario
                ORDER BY s.FECHA_SOLICITUD DESC, s.ID_SOLICITUD DESC";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function reporteLineas(): array
    {
        return $this->resumenLineas();
    }

    public function reporteOrdenes(): array
    {
        $sql = "SELECT o.ID_ORDEN, o.ID_producto, p.NOMBRE AS PRODUCTO,
                       c.NOMBRE AS CLIENTE, o.ID_linea, o.ID_turno,
                       o.CANTIDAD_PRODUCIDA, o.COLOR,
                       o.FECHA_INICIO, o.FECHA_COMPROMISO, o.FECHA_FIN,
                       o.ESTATUS, o.TIPO_EMPAQUE,
                       (SELECT COUNT(*) FROM unidades u
                        WHERE u.ID_orden = o.ID_ORDEN) AS UNIDADES_REGISTRADAS
                FROM ordenes_produccion o
                JOIN productos p ON p.ID_PRODUCTO = o.ID_producto
                JOIN clientes c ON c.ID_CLIENTE = o.ID_cliente
                ORDER BY o.FECHA_INICIO DESC, o.ID_ORDEN";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerSolicitudParaActualizar(int $id): ?array
    {
        $consulta = $this->pdo->prepare(
            'SELECT ID_SOLICITUD, ESTATUS FROM solicitudes_cambios WHERE ID_SOLICITUD = :id FOR UPDATE'
        );
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch(PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    public function cambiarEstadoSolicitud(int $id, string $nuevoEstado): bool
    {
        $consulta = $this->pdo->prepare(
            'UPDATE solicitudes_cambios SET ESTATUS = :estado WHERE ID_SOLICITUD = :id'
        );
        $consulta->execute(['estado' => $nuevoEstado, 'id' => $id]);
        return $consulta->rowCount() === 1;
    }
}
