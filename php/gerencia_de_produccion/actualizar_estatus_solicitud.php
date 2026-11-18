<?php
session_start();
require_once __DIR__ . '/../database/conexion.php';
header('Content-Type: application/json');

if (empty($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$idSolicitud = $data['id'] ?? '';
$nuevoEstatus = $data['estatus'] ?? ''; 

if (empty($idSolicitud) || empty($nuevoEstatus)) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit();
}

try {
    $pdo = conexion::obtenerInstancia()->obtenerConexion();
    $sql = "UPDATE solicitudes_cambios SET ESTATUS = :estatus WHERE ID_SOLICITUD = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'estatus' => $nuevoEstatus,
        'id' => $idSolicitud
    ]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}