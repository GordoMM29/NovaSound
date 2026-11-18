<?php
session_start();
require_once __DIR__ . '/../database/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['usuario_id'])) {
    header("Location: ../../vistas/produccion.html");
    exit();
}

$idOrden  = trim($_POST['id_orden'] ?? '');
$cantidad = (int) ($_POST['cantidad_producida'] ?? 0);

if (empty($idOrden) || $cantidad <= 0) {
    header("Location: ../../vistas/produccion.html");
    exit();
}

try {
    $pdo = conexion::obtenerInstancia()->obtenerConexion();

    $stmtCheck = $pdo->prepare("SELECT CANTIDAD_REQUERIDA, CANTIDAD_PRODUCIDA FROM ordenes_produccion WHERE ID_ORDEN = :id_orden");
    $stmtCheck->execute(['id_orden' => $idOrden]);
    $orden = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($orden) {
        $requerida = (int)$orden['CANTIDAD_REQUERIDA'];
        $producida = (int)$orden['CANTIDAD_PRODUCIDA'];
        $pendiente = $requerida - $producida;

        if ($cantidad > $pendiente) {
            header("Location: ../../vistas/produccion.html");
            exit();
        }

        $stmt = $pdo->prepare("UPDATE ordenes_produccion 
                               SET CANTIDAD_PRODUCIDA = CANTIDAD_PRODUCIDA + :cantidad 
                               WHERE ID_ORDEN = :id_orden");
        $stmt->execute([
            'cantidad' => $cantidad,
            'id_orden' => $idOrden
        ]);

        $_SESSION['orden_producida'] = $producida + $cantidad;
    }

    header("Location: ../../vistas/produccion.html");
    exit();

} catch (Exception $e) {
    header("Location: ../../vistas/produccion.html");
    exit();
}