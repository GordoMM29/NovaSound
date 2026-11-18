<?php
declare(strict_types=1);

require_once __DIR__ . '/../interfaces/PedidoMultipleRepositoryInterface.php';

final class PedidoMultipleService
{
    private const CAPACIDAD = ['MINI' => 12, 'MAX' => 4, 'SPORT' => 8, 'PRO' => 6];
    private const COLORES = [
        'MINI' => ['ROJO', 'BLANCO', 'NEGRO'],
        'MAX' => ['NEGRO', 'BLANCO', 'PLATA'],
        'SPORT' => ['ROJO', 'AZUL', 'NEGRO'],
        'PRO' => ['PLATA', 'DORADO', 'NEGRO'],
    ];

    public function __construct(private PedidoMultipleRepositoryInterface $repository) {}

    public function catalogo(): array
    {
        $productos = [];
        foreach ($this->repository->listarProductos() as $producto) {
            $modelo = strtoupper((string)$producto['ID_PRODUCTO']);
            $modelo = preg_replace('/^NS-/', '', $modelo);
            if (!isset(self::CAPACIDAD[$modelo])) continue;
            $productos[] = [
                'id_producto' => $producto['ID_PRODUCTO'],
                'modelo' => $modelo,
                'nombre' => $producto['NOMBRE'],
                'precio' => round((float)$producto['PRECIO'], 2),
                'unidades_por_caja' => self::CAPACIDAD[$modelo],
                'colores' => self::COLORES[$modelo],
            ];
        }
        return $productos;
    }

    public function crear(array $datos): array
    {
        $cliente = trim((string)($datos['cliente'] ?? ''));
        $direccion = trim((string)($datos['direccion'] ?? ''));
        $fecha = (string)($datos['fecha_compromiso'] ?? '');
        if (preg_match('/^.{1,80}$/us', $cliente) !== 1) throw new InvalidArgumentException('Ingresa un cliente válido (máximo 80 caracteres).');
        if (preg_match('/^.{1,255}$/us', $direccion) !== 1) {
            throw new InvalidArgumentException('Ingresa una dirección válida (máximo 255 caracteres).');
        }
        $fechaObj = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
        if (!$fechaObj || $fechaObj->format('Y-m-d') !== $fecha || $fecha < date('Y-m-d')) {
            throw new InvalidArgumentException('La fecha compromiso debe ser hoy o posterior.');
        }
        if (!isset($datos['detalles']) || !is_array($datos['detalles']) || count($datos['detalles']) < 1 || count($datos['detalles']) > 30) {
            throw new InvalidArgumentException('Agrega entre 1 y 30 renglones de productos.');
        }
        $catalogo = [];
        foreach ($this->catalogo() as $prod) $catalogo[$prod['modelo']] = $prod;
        $detalles = [];
        $cajasTotales = 0;
        $unidadesTotales = 0;
        $costoTotalCentavos = 0;
        $combinaciones = [];
        foreach ($datos['detalles'] as $fila) {
            if (!is_array($fila)) throw new InvalidArgumentException('Detalle inválido.');
            $modelo = strtoupper(trim((string)($fila['modelo'] ?? '')));
            $color = strtoupper(trim((string)($fila['color'] ?? '')));
            $cajas = filter_var($fila['cajas'] ?? null, FILTER_VALIDATE_INT);
            if (!isset($catalogo[$modelo])) throw new InvalidArgumentException('Producto no disponible: ' . $modelo);
            if (!in_array($color, self::COLORES[$modelo], true)) throw new InvalidArgumentException('Color no permitido para ' . $modelo);
            if ($cajas === false || $cajas < 1 || $cajas > 10000) throw new InvalidArgumentException('Las cajas deben estar entre 1 y 10000.');
            $clave = $modelo . '/' . $color;
            if (isset($combinaciones[$clave])) throw new InvalidArgumentException('Modelo y color repetidos: ' . $clave);
            $combinaciones[$clave] = true;
            $producto = $catalogo[$modelo];
            $unidades = $cajas * $producto['unidades_por_caja'];
            // PRECIO de productos se interpreta como precio POR UNIDAD, no por caja.
            $precioCentavos = (int)round($producto['precio'] * 100);
            $subtotalCentavos = $unidades * $precioCentavos;
            $cajasTotales += $cajas;
            $unidadesTotales += $unidades;
            $costoTotalCentavos += $subtotalCentavos;
            if ($costoTotalCentavos > 999999999999) throw new InvalidArgumentException('El costo total excede el límite permitido.');
            $detalles[] = [
                'id_producto' => $producto['id_producto'], 'modelo' => $modelo,
                'color' => $color, 'cajas' => $cajas,
                'por_caja' => $producto['unidades_por_caja'], 'unidades' => $unidades,
                'precio' => number_format($precioCentavos / 100, 2, '.', ''),
                'subtotal' => number_format($subtotalCentavos / 100, 2, '.', ''),
            ];
        }
        $total = number_format($costoTotalCentavos / 100, 2, '.', '');
        $id = $this->repository->guardarPedido([
            'cliente' => $cliente, 'direccion' => $direccion, 'fecha' => $fecha,
            'cajas' => $cajasTotales, 'unidades' => $unidadesTotales, 'costo' => $total,
        ], $detalles);
        return ['id_pedido' => $id, 'folio' => 'PED-' . str_pad((string)$id, 6, '0', STR_PAD_LEFT),
            'total_cajas' => $cajasTotales, 'total_unidades' => $unidadesTotales,
            'costo_total' => $total, 'estatus' => 'PENDIENTE'];
    }
}
