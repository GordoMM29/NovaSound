<?php
require_once __DIR__ . '/../interfaces/EmpaqueRepositoryInterface.php';

class EmpaqueRepository implements EmpaqueRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    public function listarOrdenes(): array
    {
        $s = $this->pdo->query('SELECT o.ID_ORDEN, o.ID_producto, p.NOMBRE AS MODELO,
           c.NOMBRE AS CLIENTE, c.DIRECCION, o.FECHA_COMPROMISO, o.TIPO_EMPAQUE,
           o.CANTIDAD_PRODUCIDA, o.ESTATUS,
           (SELECT COUNT(*) FROM unidades u WHERE u.ID_orden=o.ID_ORDEN
               AND u.ID_caja IS NULL AND UPPER(u.ESTATUS_CALIDAD) = "APROBADO") AS DISPONIBLES
           FROM ordenes_produccion o
           JOIN productos p ON p.ID_PRODUCTO=o.ID_producto
           JOIN clientes c ON c.ID_CLIENTE=o.ID_cliente
           ORDER BY o.FECHA_INICIO DESC LIMIT 200');
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarCajas(): array
    {
        $s = $this->pdo->query('SELECT ca.ID_CAJA, ca.ID_orden AS ID_ORDEN, ca.ID_producto AS ID_PRODUCTO,
           c.NOMBRE AS CLIENTE, c.DIRECCION, p.NOMBRE AS MODELO,
           ca.CANTIDAD_UNIDADES, ca.FECHA_CREACION, ca.FECHA_CIERRE,
           ca.TIPO_EMPAQUE, ca.ESTATUS, o.FECHA_COMPROMISO
           FROM cajas ca JOIN ordenes_produccion o ON o.ID_ORDEN=ca.ID_orden
           JOIN clientes c ON c.ID_CLIENTE=o.ID_cliente
           JOIN productos p ON p.ID_PRODUCTO=ca.ID_producto
           ORDER BY ca.FECHA_CREACION DESC, ca.ID_CAJA DESC LIMIT 300');
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerOrden(string $id): array|false
    {
        $s = $this->pdo->prepare('SELECT ID_ORDEN, ID_producto, TIPO_EMPAQUE, ESTATUS
             FROM ordenes_produccion WHERE ID_ORDEN = :id');
        $s->execute(['id' => $id]);
        return $s->fetch(PDO::FETCH_ASSOC);
    }

    public function crearCaja(string $orden, string $idCaja, string $tipoEmpaque): array
    {
        $this->pdo->beginTransaction();
        try {
            $s = $this->pdo->prepare('SELECT ID_ORDEN, ID_producto, ESTATUS FROM ordenes_produccion
                WHERE ID_ORDEN=:id FOR UPDATE');
            $s->execute(['id' => $orden]);
            $o = $s->fetch(PDO::FETCH_ASSOC);
            if (!$o) throw new DomainException('La orden no existe.');
            if (in_array(strtoupper($o['ESTATUS']), ['CANCELADA','CANCELADO'], true)) {
                throw new DomainException('No se pueden crear cajas para una orden cancelada.');
            }
            $s = $this->pdo->prepare('INSERT INTO cajas
                (ID_CAJA, ID_orden, ID_producto, CANTIDAD_UNIDADES, FECHA_CREACION, FECHA_CIERRE, TIPO_EMPAQUE, ESTATUS)
                VALUES (:id, :orden, :producto, 0, NOW(), NULL, :tipo, "ABIERTA")');
            $s->execute(['id' => $idCaja, 'orden' => $orden,
                'producto' => $o['ID_producto'], 'tipo' => $tipoEmpaque]);
            $this->pdo->commit();
            return ['id_caja' => $idCaja];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    public function asignarUnidades(string $idCaja, int $cantidad, int $capacidad): array
    {
        $this->pdo->beginTransaction();
        try {
            $s = $this->pdo->prepare('SELECT ca.ID_CAJA, ca.ID_orden, ca.ID_producto, ca.CANTIDAD_UNIDADES,
                ca.ESTATUS FROM cajas ca WHERE ca.ID_CAJA=:id FOR UPDATE');
            $s->execute(['id' => $idCaja]);
            $caja = $s->fetch(PDO::FETCH_ASSOC);
            if (!$caja) throw new DomainException('No existe la caja.');
            if ($caja['ESTATUS'] !== 'ABIERTA') throw new DomainException('La caja ya está cerrada.');
            $actual = (int) $caja['CANTIDAD_UNIDADES'];
            if ($actual + $cantidad > $capacidad) {
                throw new DomainException("Capacidad excedida: $actual/$capacidad unidades.");
            }
            // Bloqueo por orden para serializar operaciones de empaque de una misma orden.
            $s = $this->pdo->prepare('SELECT ID_ORDEN FROM ordenes_produccion WHERE ID_ORDEN=:id FOR UPDATE');
            $s->execute(['id' => $caja['ID_orden']]);
            $s->fetchColumn();

            $s = $this->pdo->prepare('SELECT NUMERO_SERIE FROM unidades
                WHERE ID_orden=:orden AND ID_producto=:producto AND ID_caja IS NULL
                AND UPPER(ESTATUS_CALIDAD) = "APROBADO"
                ORDER BY NUMERO_SERIE LIMIT ' . $cantidad . ' FOR UPDATE');
            $s->execute(['orden' => $caja['ID_orden'], 'producto' => $caja['ID_producto']]);
            $series = $s->fetchAll(PDO::FETCH_COLUMN);
            if (count($series) !== $cantidad) {
                throw new DomainException('No hay suficientes unidades aprobadas por Calidad disponibles.');
            }
            $update = $this->pdo->prepare('UPDATE unidades
                SET ID_caja=:caja, ESTATUS_PRODUCCION="EMPACADO"
                WHERE NUMERO_SERIE=:serie AND ID_caja IS NULL AND ESTATUS_CALIDAD="APROBADO"');
            foreach ($series as $serie) {
                $update->execute(['caja' => $idCaja, 'serie' => $serie]);
                if ($update->rowCount() !== 1) throw new DomainException('Una unidad cambió de estado durante el empaque.');
            }
            $s = $this->pdo->prepare('UPDATE cajas SET CANTIDAD_UNIDADES=:total WHERE ID_CAJA=:id');
            $s->execute(['total' => $actual + $cantidad, 'id' => $idCaja]);
            $this->pdo->commit();
            return ['id_caja' => $idCaja, 'agregadas' => $cantidad,
                'total' => $actual + $cantidad, 'capacidad' => $capacidad];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    public function cerrarCaja(string $idCaja, int $capacidad): array
    {
        $this->pdo->beginTransaction();
        try {
            $s = $this->pdo->prepare('SELECT CANTIDAD_UNIDADES, ESTATUS FROM cajas WHERE ID_CAJA=:id FOR UPDATE');
            $s->execute(['id' => $idCaja]);
            $c = $s->fetch(PDO::FETCH_ASSOC);
            if (!$c) throw new DomainException('La caja no existe.');
            if ($c['ESTATUS'] !== 'ABIERTA') throw new DomainException('La caja ya fue cerrada.');
            if ((int) $c['CANTIDAD_UNIDADES'] !== $capacidad) {
                throw new DomainException('Solo puedes cerrar una caja llena (' . (int)$c['CANTIDAD_UNIDADES'] . '/' . $capacidad . ').');
            }
            $s = $this->pdo->prepare('UPDATE cajas SET ESTATUS="CERRADA", FECHA_CIERRE=NOW() WHERE ID_CAJA=:id');
            $s->execute(['id' => $idCaja]);
            $this->pdo->commit();
            return ['id_caja' => $idCaja, 'estatus' => 'CERRADA'];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    public function reportarIncidente(string $idCaja, string $usuario, string $tipo, string $descripcion): int
    {
        $s = $this->pdo->prepare('SELECT ID_orden, ID_producto FROM cajas WHERE ID_CAJA=:id');
        $s->execute(['id' => $idCaja]);
        $caja = $s->fetch(PDO::FETCH_ASSOC);
        if (!$caja) throw new DomainException('La caja no existe.');
        // El esquema de incidencias actual no contiene ID_CAJA.
        $nota = '[EMPAQUE][' . $tipo . '][CAJA ' . $idCaja . '] ' . $descripcion;
        if (strlen($nota) > 255) throw new InvalidArgumentException('La descripción es demasiado larga.');
        $s = $this->pdo->prepare('INSERT INTO incidencias
            (ID_orden, ID_usuario, ID_producto, FECHA, DESCRIPCION)
            VALUES (:orden, :usuario, :producto, NOW(), :descripcion)');
        $s->execute(['orden' => $caja['ID_orden'], 'usuario' => $usuario,
            'producto' => $caja['ID_producto'], 'descripcion' => $nota]);
        return (int) $this->pdo->lastInsertId();
    }
}
