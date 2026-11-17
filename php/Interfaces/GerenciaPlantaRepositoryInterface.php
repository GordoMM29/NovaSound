<?php
declare(strict_types=1);

interface GerenciaPlantaRepositoryInterface
{
    public function resumen(): array;
    public function produccionPorLinea(): array;
    public function incidencias(): array;
    public function embarques(): array;
}
