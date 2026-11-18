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
    
    $sql = "SELECT 
                e.ID_EMBARQUE AS ID,
                c.NOMBRE AS CLIENTE,
                e.FECHA_EMBARQUE AS FECHA_SALIDA,
                op.FECHA_COMPROMISO AS FECHA_PROMESA,
                e.ESTATUS
            FROM embarques e
            INNER JOIN cajas caj ON e.ID_caja = caj.ID_CAJA
            INNER JOIN ordenes_produccion op ON caj.ID_orden = op.ID_ORDEN
            INNER JOIN clientes c ON op.ID_cliente = c.ID_CLIENTE
            WHERE e.ESTATUS = 'En tránsito'
            ORDER BY e.ID_EMBARQUE DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $embarques = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($embarques);
} catch (Exception $e) {
    echo json_encode([]);
}