<?php
require_once __DIR__ . '/../interfaces/SubgerenciaRepositoryInterface.php';

class SubgerenciaService
{
    private SubgerenciaRepositoryInterface $repo;
    private PDO $pdo;

    public function __construct(SubgerenciaRepositoryInterface $repo, PDO $pdo)
    {
        $this->repo = $repo;
        $this->pdo = $pdo;
    }

    public function tablero(): array
    {
        $lineas = $this->repo->resumenLineas();
        $totalOrdenes = 0;
        $totalUnidades = 0;
        foreach ($lineas as &$linea) {
            $linea['ORDENES'] = (int) $linea['ORDENES'];
            $linea['UNIDADES'] = (int) $linea['UNIDADES'];
            $totalOrdenes += $linea['ORDENES'];
            $totalUnidades += $linea['UNIDADES'];
        }
        unset($linea);
        return [
            'lineas' => $lineas,
            'total_ordenes' => $totalOrdenes,
            'total_unidades' => $totalUnidades,
            'total_lineas' => count($lineas),
            'solicitudes' => $this->repo->listarSolicitudes(),
            'nota' => 'Producción acumulada; la base actual no contiene metas por turno ni fecha individual de ensamble.'
        ];
    }

    public function reporte(string $tipo): array
    {
        if ($tipo === 'lineas') return $this->repo->reporteLineas();
        if ($tipo === 'ordenes') return $this->repo->reporteOrdenes();
        throw new InvalidArgumentException('Tipo de reporte no permitido.');
    }

    public function decidirSolicitud(int $id, string $accion): string
    {
        if ($id <= 0) throw new InvalidArgumentException('ID de solicitud inválido.');
        if (!in_array($accion, ['aprobar', 'rechazar'], true)) {
            throw new InvalidArgumentException('Acción no permitida.');
        }
        $nuevo = $accion === 'aprobar' ? 'APROBADA' : 'RECHAZADA';
        $this->pdo->beginTransaction();
        try {
            $solicitud = $this->repo->obtenerSolicitudParaActualizar($id);
            if (!$solicitud) throw new InvalidArgumentException('La solicitud no existe.');
            if (!in_array(strtoupper(trim($solicitud['ESTATUS'])), ['PENDIENTE', 'EN_REVISION', 'EN REVISIÓN', 'EN REVISION'], true)) {
                throw new DomainException('Esta solicitud ya fue resuelta o no está pendiente.');
            }
            if (!$this->repo->cambiarEstadoSolicitud($id, $nuevo)) {
                throw new RuntimeException('No fue posible actualizar la solicitud.');
            }
            $this->pdo->commit();
            return $nuevo;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }
}
