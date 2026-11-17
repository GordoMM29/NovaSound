<?php
declare(strict_types=1);

require_once __DIR__ . '/../interfaces/GerenciaPlantaRepositoryInterface.php';

final class GerenciaPlantaService
{
    public function __construct(private GerenciaPlantaRepositoryInterface $repositorio) {}

    public function panel(): array
    {
        return [
            'resumen' => $this->repositorio->resumen(),
            'lineas' => $this->repositorio->produccionPorLinea(),
            'incidencias' => $this->repositorio->incidencias(),
            'embarques' => $this->repositorio->embarques(),
            'fecha_consulta' => date('Y-m-d H:i:s'),
        ];
    }

    public function reporte(string $tipo): array
    {
        return match ($tipo) {
            'incidencias' => $this->repositorio->incidencias(),
            'embarques' => $this->repositorio->embarques(),
            'lineas' => $this->repositorio->produccionPorLinea(),
            default => throw new InvalidArgumentException('Tipo de reporte no permitido.'),
        };
    }
}
