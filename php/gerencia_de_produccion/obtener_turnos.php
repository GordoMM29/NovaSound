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
    $stmt = $pdo->query("SELECT ID_TURNO, NOMBRE FROM turnos");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo json_encode([]);
}