<?php
interface SubgerenciaRepositoryInterface
{
    public function resumenLineas(): array;
    public function listarSolicitudes(): array;
    public function reporteOrdenes(): array;
    public function reporteLineas(): array;
    public function obtenerSolicitudParaActualizar(int $id): ?array;
    public function cambiarEstadoSolicitud(int $id, string $nuevoEstado): bool;
}
