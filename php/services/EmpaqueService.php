<?php
require_once __DIR__ . '/../interfaces/EmpaqueRepositoryInterface.php';

class EmpaqueService
{
    private const CAPACIDADES = ['NS-MINI'=>12, 'NS-MAX'=>4, 'NS-SPORT'=>8, 'NS-PRO'=>6];
    public function __construct(private EmpaqueRepositoryInterface $repositorio) {}

    public function consultar(): array
    {
        return [
            'ordenes' => $this->repositorio->listarOrdenes(),
            'cajas' => $this->repositorio->listarCajas(),
            'capacidades' => self::CAPACIDADES
        ];
    }

    private function capacidad(string $producto): int
    {
        if (!isset(self::CAPACIDADES[$producto])) {
            throw new DomainException('No se ha configurado la capacidad de este modelo: ' . $producto);
        }
        return self::CAPACIDADES[$producto];
    }

    private function caja(string $id): array
    {
        $cajas = $this->repositorio->listarCajas();
        foreach ($cajas as $c) {
            if ($c['ID_CAJA'] === $id) return $c;
        }
        throw new DomainException('Caja no encontrada en los registros disponibles.');
    }

    public function ejecutar(array $datos, string $usuario): array
    {
        $accion = (string)($datos['accion'] ?? '');
        $orden = trim((string)($datos['orden'] ?? ''));
        $idCaja = trim((string)($datos['id_caja'] ?? ''));

        return match ($accion) {
            'crear' => $this->crear($orden),
            'agregar' => $this->agregar($idCaja, $datos),
            'cerrar' => $this->cerrar($idCaja),
            'incidente' => $this->incidente($idCaja, $usuario, $datos),
            default => throw new InvalidArgumentException('Acción de empaque desconocida.')
        };
    }

    private function crear(string $orden): array
    {
        if ($orden === '' || strlen($orden) > 30) throw new InvalidArgumentException('Selecciona una orden válida.');
        $o = $this->repositorio->obtenerOrden($orden);
        if (!$o) throw new DomainException('La orden no existe.');
        $this->capacidad($o['ID_producto']);
        $id = 'CJ-' . strtoupper(bin2hex(random_bytes(6)));
        return $this->repositorio->crearCaja($orden, $id, (string)$o['TIPO_EMPAQUE']);
    }

    private function agregar(string $idCaja, array $datos): array
    {
        $this->validarId($idCaja);
        $cantidad = filter_var($datos['cantidad'] ?? null, FILTER_VALIDATE_INT);
        if ($cantidad === false || $cantidad < 1 || $cantidad > 12) {
            throw new InvalidArgumentException('La cantidad debe ser un entero entre 1 y 12.');
        }
        $c = $this->caja($idCaja);
        return $this->repositorio->asignarUnidades($idCaja, $cantidad, $this->capacidad($c['ID_PRODUCTO']));
    }

    private function cerrar(string $idCaja): array
    {
        $this->validarId($idCaja);
        $c = $this->caja($idCaja);
        return $this->repositorio->cerrarCaja($idCaja, $this->capacidad($c['ID_PRODUCTO']));
    }

    private function incidente(string $idCaja, string $usuario, array $datos): array
    {
        $this->validarId($idCaja);
        $tipos = ['Unidades faltantes','Etiqueta incorrecta','Contenido equivocado','Caja dañada','Otro'];
        $tipo = trim((string)($datos['tipo'] ?? ''));
        $descripcion = trim((string)($datos['descripcion'] ?? ''));
        if (!in_array($tipo, $tipos, true)) throw new InvalidArgumentException('Tipo de incidente inválido.');
        if ($descripcion === '' || strlen($descripcion) > 170) {
            throw new InvalidArgumentException('La descripción debe tener entre 1 y 170 caracteres.');
        }
        return ['id_incidencia' => $this->repositorio->reportarIncidente($idCaja, $usuario, $tipo, $descripcion)];
    }

    private function validarId(string $id): void
    {
        if ($id === '' || strlen($id) > 30) throw new InvalidArgumentException('Selecciona una caja válida.');
    }
}
