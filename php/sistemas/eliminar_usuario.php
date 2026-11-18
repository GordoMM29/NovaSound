<?php
session_start();
require_once __DIR__ . '/../database/conexion.php';
header('Content-Type: application/json');

if (empty($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? '';

if (empty($id)) {
    echo json_encode(['success' => false, 'error' => 'ID requerido']);
    exit();
}

try {
    $pdo = conexion::obtenerInstancia()->obtenerConexion();
    $sql = "DELETE FROM usuarios WHERE ID_USUARIO = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'No se puede eliminar el usuario porque tiene registros asociados.']);
}