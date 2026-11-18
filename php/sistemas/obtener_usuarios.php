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
                u.ID_USUARIO,
                u.NOMBRE,
                r.NOMBRE AS ROL,
                t.NOMBRE AS TURNO,
                u.AREA
            FROM usuarios u
            INNER JOIN roles r ON u.ID_rol = r.ID_ROL
            LEFT JOIN turnos t ON u.ID_turno = t.ID_TURNO
            ORDER BY u.ID_USUARIO ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($usuarios);
} catch (Exception $e) {
    echo json_encode([]);
}