<?php
session_start();
require_once __DIR__ . '/../database/conexion.php';
header('Content-Type: application/json');

if (empty($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$nombre = $data['nombre'] ?? '';
$apellidos = $data['apellidos'] ?? '';
$area = $data['area'] ?? '';
$idLinea = !empty($data['id_linea']) ? $data['id_linea'] : null;
$idTurno = !empty($data['id_turno']) ? $data['id_turno'] : null;
$idRol = $data['id_rol'] ?? '';
$fechaIngreso = $data['fecha_ingreso'] ?? date('Y-m-d');

if (empty($nombre) || empty($apellidos) || empty($area) || empty($idRol)) {
    echo json_encode(['success' => false, 'error' => 'Faltan campos obligatorios']);
    exit();
}

try {
    $pdo = conexion::obtenerInstancia()->obtenerConexion();

    $stmtMax = $pdo->query("SELECT COUNT(*) AS total FROM usuarios");
    $total = $stmtMax->fetch(PDO::FETCH_ASSOC)['total'] + 1;
    $idUsuario = 'USR-' . str_pad($total, 3, '0', STR_PAD_LEFT);
    $correo = strtolower(str_replace(' ', '', $nombre)) . '@novasound.com';
    $passwordDefault = 'pass123'; 

    $sql = "INSERT INTO usuarios (ID_USUARIO, NOMBRE, APELLIDOS, CORREO, CONTRASENA, AREA, ID_linea, ID_turno, ID_rol, FECHA_INGRESO) 
            VALUES (:id, :nombre, :apellidos, :correo, :pass, :area, :linea, :turno, :rol, :fecha)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'id' => $idUsuario,
        'nombre' => $nombre,
        'apellidos' => $apellidos,
        'correo' => $correo,
        'pass' => $passwordDefault,
        'area' => $area,
        'linea' => $idLinea,
        'turno' => $idTurno,
        'rol' => $idRol,
        'fecha' => $fechaIngreso
    ]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}