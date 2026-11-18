<?php
session_start();
require_once __DIR__ . '/../database/conexion.php';
header('Content-Type: application/json');

if (empty($_SESSION['usuario_id']) || empty($_GET['id'])) {
    echo json_encode(['error' => 'Datos incompletos']);
    exit();
}

$idUsuario = trim($_GET['id']);

try {
    $pdo = conexion::obtenerInstancia()->obtenerConexion();
    $sql = "SELECT ID_USUARIO, NOMBRE, APELLIDOS, AREA, ID_linea, ID_turno, ID_rol, FECHA_INGRESO 
            FROM usuarios 
            WHERE ID_USUARIO = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $idUsuario]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        echo json_encode($usuario);
    } else {
        echo json_encode(['error' => 'Usuario no encontrado']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}