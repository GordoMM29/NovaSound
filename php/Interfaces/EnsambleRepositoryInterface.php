<?php
interface EnsambleRepositoryInterface
{
    public function listar(string $orden = ''): array;
    public function registrar(string $orden, string $usuario, int $cantidad, string $observaciones): array;
    public function enviarAPruebas(string $orden): array;
    public function reportarIncidente(string $orden, string $usuario, string $tipo, string $descripcion): int;
}
