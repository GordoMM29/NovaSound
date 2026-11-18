<?php
require_once __DIR__ . '/../interfaces/EnsambleRepositoryInterface.php';

class EnsambleRepository implements EnsambleRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    public function listar(string $orden = ''): array
    {
        $sql = 'SELECT r.ID_REGISTRO, r.ID_ORDEN, p.NOMBRE AS MODELO,
                       CONCAT(u.NOMBRE, " ", u.APELLIDOS) AS USUARIO,
                       r.FECHA_REGISTRO, r.OBSERVACIONES, r.ESTATUS, r.CANTIDAD
                FROM registros_ensamble r
                INNER JOIN ordenes_produccion o ON o.ID_ORDEN = r.ID_ORDEN
                INNER JOIN productos p ON p.ID_PRODUCTO = o.ID_producto
                INNER JOIN usuarios u ON u.ID_USUARIO = r.ID_USUARIO';
        $args = [];
        if ($orden !== '') {
            $sql .= ' WHERE r.ID_ORDEN = :orden';
            $args['orden'] = $orden;
        }
        $sql .= ' ORDER BY r.FECHA_REGISTRO DESC, r.ID_REGISTRO DESC LIMIT 200';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($args);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function registrar(string $orden, string $usuario, int $cantidad, string $observaciones): array
    {
        $this->pdo->beginTransaction();
        try {
            // Esta parte se suara para sereañizar asi evitamos el sobre registro de unidades en caso de concurrencia.(Concurrencia que palabra mas sofisticada osea palabras menores evitar el sobrepedido )
            $stmt = $this->pdo->prepare('SELECT ID_ORDEN, ID_producto, COLOR, CANTIDAD_PRODUCIDA, ESTATUS
                                         FROM ordenes_produccion WHERE ID_ORDEN = :orden FOR UPDATE');
            $stmt->execute(['orden' => $orden]);
            $o = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$o) throw new DomainException('La orden no existe.');
            if (in_array(strtoupper(trim($o['ESTATUS'])), ['CANCELADA', 'CANCELADO', 'FINALIZADA', 'FINALIZADO'], true)) {
                throw new DomainException('No se pueden registrar unidades para esta orden.');
            }

            // CANTIDAD_PRODUCIDA sera nuesto limitante de unidades 
            $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM unidades WHERE ID_orden = :orden');
            $stmt->execute(['orden' => $orden]);
            $existentes = (int) $stmt->fetchColumn();
            $maximo = (int) $o['CANTIDAD_PRODUCIDA'];
            if ($maximo <= 0 || $existentes + $cantidad > $maximo) {
                throw new DomainException("Cantidad excedida. Registradas: $existentes; límite: $maximo.");
            }

            $stmt = $this->pdo->prepare('INSERT INTO registros_ensamble
                  (ID_ORDEN, ID_USUARIO, CANTIDAD, OBSERVACIONES, ESTATUS)
                  VALUES (:orden, :usuario, :cantidad, :observaciones, "REGISTRADO")');
            $stmt->execute([
                'orden' => $orden, 'usuario' => $usuario,
                'cantidad' => $cantidad,
                'observaciones' => $observaciones === '' ? null : $observaciones
            ]);
            $registro = (int) $this->pdo->lastInsertId();
            $insertUnidad = $this->pdo->prepare('INSERT INTO unidades
                 (NUMERO_SERIE, ETIQUETA, ID_orden, ID_producto, COLOR,
                  ESTATUS_PRODUCCION, ESTATUS_CALIDAD, ID_caja)
                 VALUES (:serie, :etiqueta, :orden, :producto, :color,
                         "ENSAMBLADO", "PENDIENTE", NULL)');
            $vinculo = $this->pdo->prepare('INSERT INTO registro_ensamble_unidades
                         (ID_REGISTRO, NUMERO_SERIE) VALUES (:registro, :serie)');
            for ($n = 1; $n <= $cantidad; $n++) {
                $serie = sprintf('ENS-%08d-%05d', $registro, $n);
                $insertUnidad->execute([
                    'serie' => $serie,
                    'etiqueta' => sprintf('NS-%08d-%05d', $registro, $n),
                    'orden' => $orden,
                    'producto' => $o['ID_producto'],
                    'color' => $o['COLOR']
                ]);
                $vinculo->execute(['registro' => $registro, 'serie' => $serie]);
            }
            $this->pdo->commit();
            return ['id_registro' => $registro, 'cantidad' => $cantidad];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    public function enviarAPruebas(string $orden): array
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('SELECT ID_ORDEN FROM ordenes_produccion WHERE ID_ORDEN = :orden FOR UPDATE');
            $stmt->execute(['orden' => $orden]);
            if (!$stmt->fetchColumn()) throw new DomainException('La orden no existe.');

            $stmt = $this->pdo->prepare('SELECT ID_REGISTRO FROM registros_ensamble
                     WHERE ID_ORDEN = :orden AND ESTATUS = "REGISTRADO" FOR UPDATE');
            $stmt->execute(['orden' => $orden]);
            $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
            if (!$ids) throw new DomainException('No hay registros pendientes de enviar a pruebas.');

            // Solo cambia las unidades creadas por este módulo y pendientes de pruebas.
            $stmt = $this->pdo->prepare('UPDATE unidades u
                 INNER JOIN registro_ensamble_unidades ru ON ru.NUMERO_SERIE = u.NUMERO_SERIE
                 INNER JOIN registros_ensamble r ON r.ID_REGISTRO = ru.ID_REGISTRO
                 SET u.ESTATUS_PRODUCCION = "EN_PRUEBAS", u.ESTATUS_CALIDAD = "PENDIENTE"
                 WHERE r.ID_ORDEN = :orden AND r.ESTATUS = "REGISTRADO"
                       AND u.ESTATUS_PRODUCCION = "ENSAMBLADO"');
            $stmt->execute(['orden' => $orden]);
            $unidades = $stmt->rowCount();
            $stmt = $this->pdo->prepare('UPDATE registros_ensamble SET ESTATUS = "EN_PRUEBAS"
                                         WHERE ID_ORDEN = :orden AND ESTATUS = "REGISTRADO"');
            $stmt->execute(['orden' => $orden]);
            $this->pdo->commit();
            return ['registros' => count($ids), 'unidades' => $unidades];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    public function reportarIncidente(string $orden, string $usuario, string $tipo, string $descripcion): int
    {
        $stmt = $this->pdo->prepare('SELECT ID_producto FROM ordenes_produccion WHERE ID_ORDEN = :orden');
        $stmt->execute(['orden' => $orden]);
        $producto = $stmt->fetchColumn();
        if (!$producto) throw new DomainException('La orden no existe.');
        $stmt = $this->pdo->prepare('INSERT INTO incidencias
                 (ID_orden, ID_usuario, ID_producto, FECHA, DESCRIPCION)
                 VALUES (:orden, :usuario, :producto, NOW(), :descripcion)');
        $stmt->execute([
            'orden' => $orden, 'usuario' => $usuario,
            'producto' => $producto, 'descripcion' => '[' . $tipo . '] ' . $descripcion
        ]);
        return (int) $this->pdo->lastInsertId();
    }
}
