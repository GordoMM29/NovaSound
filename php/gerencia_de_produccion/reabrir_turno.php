<?php
session_start();
require_once __DIR__ . '/../database/conexion.php';
header('Content-Type: application/json');

if (empty($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$idTurno = $data['id_turno'] ?? '';

if (empty($idTurno)) {
    echo json_encode(['success' => false, 'error' => 'Turno no seleccionado']);
    exit();
}

try {
    $pdo = conexion::obtenerInstancia()->obtenerConexion();
    
    $sql = "UPDATE cierres_turno SET ESTATUS = 'Abierto' WHERE ID_TURNO = :id_turno AND fecha_cierre = current_date()";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id_turno' => $idTurno]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}