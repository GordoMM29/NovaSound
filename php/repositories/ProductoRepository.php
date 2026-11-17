
<?php

require_once __DIR__ . '/../interfaces/ProductoRepositoryInterface.php';

class ProductoRepository implements ProductoRepositoryInterface
{
    private PDO $conexion;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    public function obtenerTodos(): array
    {
        $sql = "SELECT ID_PRODUCTO, NOMBRE, DESCRIPCION, PRECIO
                FROM productos
                ORDER BY ID_PRODUCTO ASC";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute();

        return $consulta->fetchAll();
    }
}
