<?php
interface EmpaqueRepositoryInterface
{
    public function listarOrdenes(): array;
    public function listarCajas(): array;
    public function obtenerOrden(string $id): array|false;
    public function crearCaja(string $orden, string $idCaja, string $tipoEmpaque): array;
    public function asignarUnidades(string $idCaja, int $cantidad, int $capacidad): array;
    public function cerrarCaja(string $idCaja, int $capacidad): array;
    public function reportarIncidente(string $idCaja, string $usuario, string $tipo, string $descripcion): int;
}
