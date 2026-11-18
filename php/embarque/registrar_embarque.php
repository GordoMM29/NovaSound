<?php
session_start();
require_once __DIR__ . '/../database/conexion.php';
header('Content-Type: application/json');

if (empty($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$idCaja = $data['id_caja'] ?? '';
$fechaSalida = $data['fecha_salida'] ?? '';
$destino = $data['destino'] ?? '';

if (empty($idCaja) || empty($fechaSalida) || empty($destino)) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit();
}

try {
    $pdo = conexion::obtenerInstancia()->obtenerConexion();

    $sql = "INSERT INTO embarques ( ID_caja, FECHA_EMBARQUE, DESTINO, ESTATUS) 
            VALUES (:id_caja, :fecha_embarque, :destino, 'En tránsito')";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'id_caja' => $idCaja,
        'fecha_embarque' => $fechaSalida,
        'destino' => $destino
    ]);


    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}