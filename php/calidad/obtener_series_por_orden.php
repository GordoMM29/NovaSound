<?php
session_start();
require_once __DIR__ . '/../database/conexion.php';
header('Content-Type: application/json');

if (empty($_SESSION['usuario_id']) || empty($_GET['id_orden'])) {
    echo json_encode([]);
    exit();
}

$idOrden = trim($_GET['id_orden']);

try {
    $pdo = conexion::obtenerInstancia()->obtenerConexion();
    $sql = "SELECT NUMERO_SERIE FROM unidades WHERE ID_orden = :id_orden AND ESTATUS_PRODUCCION = 'Aprobado'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id_orden' => $idOrden]);
    $series = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($series);
} catch (Exception $e) {
    echo json_encode([]);
}