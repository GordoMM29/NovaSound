
<?php

require_once __DIR__ . '/../interfaces/OrdenRepositoryInterface.php';

class OrdenService
{
    private OrdenRepositoryInterface $repositorio;

    public function __construct(OrdenRepositoryInterface $repositorio)
    {
        $this->repositorio = $repositorio;
    }

    public function obtenerOrdenes(): array
    {
        return $this->repositorio->listarOrdenes();
    }
}
