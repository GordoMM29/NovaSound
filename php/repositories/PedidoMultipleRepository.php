<?php
declare(strict_types=1);

require_once __DIR__ . '/../interfaces/PedidoMultipleRepositoryInterface.php';

final class PedidoMultipleRepository implements PedidoMultipleRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    public function listarProductos(): array
    {
        $stmt = $this->pdo->query('SELECT ID_PRODUCTO, NOMBRE, PRECIO FROM productos ORDER BY ID_PRODUCTO');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function guardarPedido(array $cabecera, array $detalles): int
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO solicitudes_pedido_cabecera
                 (NOMBRE_CLIENTE, DIRECCION, FECHA_COMPROMISO, ESTATUS, TOTAL_CAJAS, TOTAL_UNIDADES, COSTO_TOTAL)
                 VALUES (:cliente, :direccion, :fecha, :estatus, :cajas, :unidades, :costo)'
            );
            $stmt->execute([
                'cliente' => $cabecera['cliente'],
                'direccion' => $cabecera['direccion'],
                'fecha' => $cabecera['fecha'],
                'estatus' => 'PENDIENTE',
                'cajas' => $cabecera['cajas'],
                'unidades' => $cabecera['unidades'],
                'costo' => $cabecera['costo'],
            ]);
            $id = (int)$this->pdo->lastInsertId();

            $item = $this->pdo->prepare(
                'INSERT INTO solicitudes_pedido_detalle
                 (ID_PEDIDO, ID_PRODUCTO, COLOR, CANTIDAD_CAJAS, UNIDADES_POR_CAJA,
                  CANTIDAD_UNIDADES, PRECIO_UNITARIO, SUBTOTAL)
                 VALUES (:pedido, :producto, :color, :cajas, :porCaja, :unidades, :precio, :subtotal)'
            );
            foreach ($detalles as $detalle) {
                $item->execute([
                    'pedido' => $id,
                    'producto' => $detalle['id_producto'],
                    'color' => $detalle['color'],
                    'cajas' => $detalle['cajas'],
                    'porCaja' => $detalle['por_caja'],
                    'unidades' => $detalle['unidades'],
                    'precio' => $detalle['precio'],
                    'subtotal' => $detalle['subtotal'],
                ]);
            }
            $this->pdo->commit();
            return $id;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }
}
