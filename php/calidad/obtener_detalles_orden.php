<?php
session_start();
require_once __DIR__ . '/../database/conexion.php';

header('Content-Type: application/json');

if (empty($_SESSION['usuario_id']) || empty($_GET['id_orden'])) {
    echo json_encode(['error' => 'Datos incompletos']);
    exit();
}

$idOrden = trim($_GET['id_orden']);

try {
    $pdo = conexion::obtenerInstancia()->obtenerConexion();

    $sql = "SELECT 
                u.NUMERO_SERIE,
                p.NOMBRE AS MODELO,
                i.ESTATUS_PRUEBAS,
                i.ESTATUS_APARIENCIA,
                i.ESTATUS_ETIQUETA,
                i.ESTATUS_ACCESORIOS,
                i.ESTATUS_FINAL
            FROM unidades u
            INNER JOIN productos p ON u.ID_producto = p.ID_PRODUCTO
            INNER JOIN inspeccion i ON u.NUMERO_SERIE = i.NUMERO_SERIE
            WHERE u.ID_orden = :id_orden";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id_orden' => $idOrden]);
    $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($detalles);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}