
<?php

require_once __DIR__ . '/../interfaces/OrdenRepositoryInterface.php';

class OrdenRepository implements OrdenRepositoryInterface
{
    private PDO $conexion;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    public function listarOrdenes(): array
    {
        $sql = "
            SELECT
                o.ID_ORDEN,
                o.ID_producto,
                p.NOMBRE AS MODELO,
                c.NOMBRE AS CLIENTE,
                o.CANTIDAD_PRODUCIDA,
                o.COLOR,
                o.FECHA_COMPROMISO,
                o.ID_linea,
                l.NOMBRE AS LINEA,
                o.ID_turno,
                o.ESTATUS
            FROM ordenes_produccion o
            INNER JOIN productos p
                ON o.ID_producto = p.ID_PRODUCTO
            INNER JOIN clientes c
                ON o.ID_cliente = c.ID_CLIENTE
            INNER JOIN lineas l
                ON o.ID_linea = l.ID_LINEA
            ORDER BY o.FECHA_INICIO DESC
        ";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute();

        return $consulta->fetchAll();
    }
}
