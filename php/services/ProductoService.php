
<?php

require_once __DIR__ . '/../interfaces/ProductoRepositoryInterface.php';

class ProductoService
{
    private ProductoRepositoryInterface $repositorio;

    public function __construct(ProductoRepositoryInterface $repositorio)
    {
        $this->repositorio = $repositorio;
    }

    public function listarProductos(): array
    {
        return $this->repositorio->obtenerTodos();
    }
}
//Dios vendia al codigo corto