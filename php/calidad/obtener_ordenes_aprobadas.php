<?php
session_start();
require_once __DIR__ . '/../database/conexion.php';
header('Content-Type: application/json');

if (empty($_SESSION['usuario_id'])) {
    echo json_encode([]);
    exit();
}

try {
    $pdo = conexion::obtenerInstancia()->obtenerConexion();
 
 
    $sql = "SELECT DISTINCT ID_orden 
            FROM unidades 
            WHERE ESTATUS_PRODUCCION = 'Aprobado' 
              AND (ESTATUS_CALIDAD IS NULL 
                   OR TRIM(ESTATUS_CALIDAD) = '' 
                   OR TRIM(ESTATUS_CALIDAD) NOT IN ('Liberada', 'Scrap', 'Retenida', 'Retrabajar'))";
                   
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $ordenes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($ordenes);
} catch (Exception $e) {
    echo json_encode([]);
}