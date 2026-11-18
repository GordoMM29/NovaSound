<?php
session_start();
require_once __DIR__ . '/../database/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['usuario_id'])) {
    header("Location: ../../vistas/calidad.html");
    exit();
}

$idOrden       = trim($_POST['id_orden'] ?? '');
$numeroSerie   = trim($_POST['numero_serie'] ?? '');
$nuevoEstatus  = trim($_POST['nuevo_estatus'] ?? '');
$justificacion = trim($_POST['justificacion'] ?? '');
$usuarioId     = $_SESSION['usuario_id'];

if (empty($idOrden) || empty($numeroSerie) || empty($nuevoEstatus)) {
    header("Location: ../../vistas/calidad.html?error=datos_incompletos");
    exit();
}

try {
    $pdo = conexion::obtenerInstancia()->obtenerConexion();

    $sql = "UPDATE inspeccion 
            SET ESTATUS_FINAL = :estatus 
            WHERE NUMERO_SERIE = :serie AND ID_orden = :orden";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'estatus' => $nuevoEstatus,
        'serie'   => $numeroSerie,
        'orden'   => $idOrden
    ]);

    $sqlUnidad = "UPDATE unidades SET ESTATUS_CALIDAD = :estatus WHERE NUMERO_SERIE = :serie";
    $stmtU = $pdo->prepare($sqlUnidad);
    $stmtU->execute([
        'estatus' => $nuevoEstatus,
        'serie'   => $numeroSerie
    ]);

    header("Location: ../../vistas/calidad.html?exito=actualizado");
    exit();

} catch (Exception $e) {
    header("Location: ../../vistas/calidad.html?error=bd");
    exit();
}