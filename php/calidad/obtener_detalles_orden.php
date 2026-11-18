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

    $sql = "SELECT DISTINCT
                u.NUMERO_SERIE,
                p.NOMBRE AS MODELO,
                COALESCE(i.ESTATUS_PRUEBAS, 'PENDIENTE') AS ESTATUS_PRUEBAS,
                COALESCE(i.ESTATUS_APARIENCIA, 'PENDIENTE') AS ESTATUS_APARIENCIA,
                COALESCE(i.ESTATUS_ETIQUETA, 'PENDIENTE') AS ESTATUS_ETIQUETA,
                COALESCE(i.ESTATUS_ACCESORIOS, 'PENDIENTE') AS ESTATUS_ACCESORIOS,
                COALESCE(i.ESTATUS_FINAL, 'PENDIENTE') AS ESTATUS_FINAL
            FROM unidades u
            INNER JOIN productos p ON u.ID_producto = p.ID_PRODUCTO
            LEFT JOIN inspeccion i ON u.NUMERO_SERIE = i.NUMERO_SERIE
            WHERE u.ID_orden = :id_orden 
              AND u.ESTATUS_PRODUCCION = 'Aprobado'
              AND u.ESTATUS_CALIDAD NOT IN ('Liberada', 'Scrap', 'Retenida', 'Retrabajar')";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id_orden' => $idOrden]);
    $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($detalles);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}