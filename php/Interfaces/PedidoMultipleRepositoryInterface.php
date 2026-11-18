<?php
declare(strict_types=1);

interface PedidoMultipleRepositoryInterface
{
    public function listarProductos(): array;
    public function guardarPedido(array $cabecera, array $detalles): int;
}
