<?php
session_start();
require_once __DIR__ . '/../database/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['usuario_id'])) {
    header("Location: ../../vistas/produccion.html");
    exit();
}

$idOrden       = trim($_POST['id_orden'] ?? '');
$tipoIncidente = trim($_POST['tipo_incidente'] ?? 'General');
$descripcion   = trim($_POST['descripcion'] ?? '');
$idUsuario     = $_SESSION['usuario_id'];
$idProducto    = $_SESSION['orden_producto_id'] ?? '';

if (empty($idOrden) || empty($descripcion) || empty($idProducto)) {
    header("Location: ../../vistas/produccion.html?error=datos_incidente_invalidos");
    exit();
}

try {
    $pdo = conexion::obtenerInstancia()->obtenerConexion();

    $stmt = $pdo->prepare("SELECT UUID() AS id_incidencia");
    $stmt->execute();
    $idIncidencia = $stmt->fetchColumn();

    $descripcionCompleta = "[$tipoIncidente] " . $descripcion;

    $sql = "INSERT INTO incidencias (ID_INCIDENCIA, ID_orden, ID_usuario, ID_producto, FECHA, DESCRIPCION) 
            VALUES (:id_incidencia, :id_orden, :id_usuario, :id_producto, NOW(), :descripcion)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'id_incidencia' => $idIncidencia,
        'id_orden'      => $idOrden,
        'id_usuario'    => $idUsuario,
        'id_producto'   => $idProducto,
        'descripcion'   => $descripcionCompleta
    ]);

    header("Location: ../../vistas/produccion.html?exito=incidente_reportado");
    exit();

} catch (Exception $e) {
    header("Location: ../../vistas/produccion.html?error=bd_incidente");
    exit();
}