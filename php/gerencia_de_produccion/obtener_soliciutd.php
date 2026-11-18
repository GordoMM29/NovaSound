<?php
session_start();
require_once __DIR__ . '/../database/conexion.php';
header('Content-Type: application/json');

if (empty($_SESSION['usuario_id']) || empty($_GET['id'])) {
    echo json_encode(['error' => 'Datos incompletos']);
    exit();
}

$idSolicitud = intval($_GET['id']);

try {
    $pdo = conexion::obtenerInstancia()->obtenerConexion();
    $sql = "SELECT s.ID_SOLICITUD, s.OBJETO, s.ID_usuario, u.NOMBRE AS NOMBRE_USUARIO, s.FECHA_SOLICITUD, s.MOTIVO, s.ESTATUS 
            FROM solicitudes_cambios s WHEREE s.ESTATUS= 'Pendiente'
            INNER JOIN usuarios u ON s.ID_usuario = u.ID_USUARIO
            WHERE s.ID_SOLICITUD = :id" ;
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $idSolicitud]);
    $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($solicitud) {
        echo json_encode($solicitud);
    } else {
        echo json_encode(['error' => 'Solicitud no encontrada']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}