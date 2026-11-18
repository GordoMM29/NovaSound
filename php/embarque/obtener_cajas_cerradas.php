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
    
    $sql = "SELECT ID_CAJA, CANTIDAD_UNIDADES 
            FROM cajas 
            WHERE ESTATUS = 'Cerrada' 
              AND ID_CAJA NOT IN (SELECT ID_caja FROM embarques WHERE ESTATUS = 'En tránsito')";
              
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $cajas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($cajas);
} catch (Exception $e) {
    echo json_encode([]);
}