<?php
session_start();
require_once __DIR__ . '/../database/conexion.php';

header('Content-Type: application/json');

if (empty($_SESSION['usuario_id'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

try {
    $pdo = conexion::obtenerInstancia()->obtenerConexion();

    $sql = "SELECT 
                u.ID_orden AS ORDEN, 
                p.NOMBRE AS MODELO, 
                u.ESTATUS_CALIDAD AS ESTATUS
            FROM unidades u
            INNER JOIN productos p ON u.ID_producto = p.ID_PRODUCTO
            WHERE u.ESTATUS_PRODUCCION = 'Aprobado' AND (u.ESTATUS_CALIDAD IS NULL OR TRIM(u.ESTATUS_CALIDAD) = '' OR TRIM(u.ESTATUS_CALIDAD) NOT IN ('Liberada', 'Scrap', 'Retenida', 'Retrabajar'))
            ORDER BY u.NUMERO_SERIE DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $unidades = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($unidades);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}