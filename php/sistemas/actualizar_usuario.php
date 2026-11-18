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
$nombre = $data['nombre'] ?? '';
$apellidos = $data['apellidos'] ?? '';
$area = $data['area'] ?? '';
$idLinea = !empty($data['id_linea']) ? $data['id_linea'] : null;
$idTurno = !empty($data['id_turno']) ? $data['id_turno'] : null;
$idRol = $data['id_rol'] ?? '';

if (empty($id)) {
    echo json_encode(['success' => false, 'error' => 'ID de usuario requerido']);
    exit();
}

try {
    $pdo = conexion::obtenerInstancia()->obtenerConexion();
    $sql = "UPDATE usuarios 
            SET NOMBRE = :nombre, APELLIDOS = :apellidos, AREA = :area, ID_linea = :linea, ID_turno = :turno, ID_rol = :rol 
            WHERE ID_USUARIO = :id";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'nombre' => $nombre,
        'apellidos' => $apellidos,
        'area' => $area,
        'linea' => $idLinea,
        'turno' => $idTurno,
        'rol' => $idRol,
        'id' => $id
    ]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}