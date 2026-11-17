<?php
require_once __DIR__ . '/../interfaces/EnsambleRepositoryInterface.php';

class EnsambleService
{
    public function __construct(private EnsambleRepositoryInterface $repositorio) {}

    public function listar(string $orden): array
    {
        if (strlen($orden) > 30) throw new InvalidArgumentException('Orden inválida.');
        return $this->repositorio->listar($orden);
    }

    public function registrar(array $datos, string $usuario): array
    {
        $orden = trim((string) ($datos['orden'] ?? ''));
        $cantidad = filter_var($datos['cantidad'] ?? null, FILTER_VALIDATE_INT);
        $observaciones = trim((string) ($datos['observaciones'] ?? ''));
        if ($orden === '' || strlen($orden) > 30) throw new InvalidArgumentException('Selecciona una orden válida.');
        if ($cantidad === false || $cantidad < 1 || $cantidad > 10000) {
            throw new InvalidArgumentException('La cantidad debe ser un entero entre 1 y 10000.');
        }
        if (!preg_match('/^.{0,300}$/us', $observaciones)) {
            throw new InvalidArgumentException('Máximo 300 caracteres en observaciones.');
        }
        return $this->repositorio->registrar($orden, $usuario, $cantidad, $observaciones);
    }

    public function enviar(array $datos): array
    {
        $orden = trim((string) ($datos['orden'] ?? ''));
        if ($orden === '' || strlen($orden) > 30) throw new InvalidArgumentException('Selecciona una orden válida.');
        return $this->repositorio->enviarAPruebas($orden);
    }

    public function incidente(array $datos, string $usuario): int
    {
        $orden = trim((string) ($datos['orden'] ?? ''));
        $tipo = trim((string) ($datos['tipo'] ?? ''));
        $descripcion = trim((string) ($datos['descripcion'] ?? ''));
        $permitidos = ['Pieza dañada', 'Falta de componente', 'Falla de equipo', 'Conteo incorrecto', 'Otro'];
        if ($orden === '' || strlen($orden) > 30) throw new InvalidArgumentException('Selecciona una orden válida.');
        if (!in_array($tipo, $permitidos, true)) throw new InvalidArgumentException('Tipo de incidente inválido.');
        // La columna incidencias.DESCRIPCION admite hasta 255 caracteres.
        if ($descripcion === '' || !preg_match('/^.{1,200}$/us', $descripcion)) {
            throw new InvalidArgumentException('La descripción debe tener entre 1 y 200 caracteres.');
        }
        return $this->repositorio->reportarIncidente($orden, $usuario, $tipo, $descripcion);
    }
}
