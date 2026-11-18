<?php
session_start();
require_once __DIR__ . '/../database/conexion.php';
if (empty($_SESSION['usuario_id'])) { exit('No autorizado'); }
$pdo = conexion::obtenerInstancia()->obtenerConexion();
$stmt = $pdo->query("SELECT ID_ORDEN, ID_producto, CANTIDAD_PRODUCIDA, CANTIDAD_REQUERIDA, ESTATUS FROM ordenes_produccion WHERE CANTIDAD_PRODUCIDA < CANTIDAD_REQUERIDA");
$datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Reporte - Órdenes Incompletas</title>
<style>body{font-family:Arial;margin:20px;color:#333;}h1{color:#4a148c;text-align:center;}table{width:100%;border-collapse:collapse;margin-top:20px;}th,td{border:1px solid #ddd;padding:8px;text-align:center;font-size:12px;}th{background:#7b1fa2;color:white;}</style>
</head>
<body onload="window.print()">
    <h1>NovaSound - Órdenes de Producción (Producida < Requerida)</h1>
    <p>Fecha de emisión: <?php echo date('d/m/Y H:i:s'); ?></p>
    <table><thead><tr><th>ID Orden</th><th>Producto</th><th>Cant. Producida</th><th>Cant. Requerida</th><th>Estatus</th></tr></thead>
    <tbody><?php foreach($datos as $row): ?><tr><td><?php echo $row['ID_ORDEN']; ?></td><td><?php echo $row['ID_producto']; ?></td><td><?php echo $row['CANTIDAD_PRODUCIDA']; ?></td><td><?php echo $row['CANTIDAD_REQUERIDA']; ?></td><td><?php echo $row['ESTATUS']; ?></td></tr><?php endforeach; ?></tbody></table>
</body>
</html>