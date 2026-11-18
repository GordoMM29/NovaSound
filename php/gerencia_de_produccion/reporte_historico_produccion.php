<?php
session_start();
require_once __DIR__ . '/../database/conexion.php';
if (empty($_SESSION['usuario_id'])) { exit('No autorizado'); }
$pdo = conexion::obtenerInstancia()->obtenerConexion();
$stmt = $pdo->query("SELECT * FROM ordenes_produccion");
$datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Histórico - Producción</title>
<style>body{font-family:Arial;margin:20px;color:#333;}h1{color:#4a148c;text-align:center;}table{width:100%;border-collapse:collapse;margin-top:20px;}th,td{border:1px solid #ddd;padding:6px;text-align:center;font-size:10px;}th{background:#7b1fa2;color:white;}</style>
</head>
<body onload="window.print()">
    <h1>NovaSound - Reporte Histórico de Órdenes de Producción</h1>
    <p>Fecha de emisión: <?php echo date('d/m/Y H:i:s'); ?></p>
    <table><thead><tr><th>ID Orden</th><th>Producto</th><th>Cliente</th><th>Línea</th><th>Turno</th><th>Cant. Prod</th><th>Color</th><th>F. Inicio</th><th>F. Compromiso</th><th>Estatus</th></tr></thead>
    <tbody><?php foreach($datos as $row): ?><tr><td><?php echo $row['ID_ORDEN']; ?></td><td><?php echo $row['ID_producto']; ?></td><td><?php echo $row['ID_cliente']; ?></td><td><?php echo $row['ID_linea']; ?></td><td><?php echo $row['ID_turno']; ?></td><td><?php echo $row['CANTIDAD_PRODUCIDA']; ?></td><td><?php echo $row['COLOR']; ?></td><td><?php echo $row['FECHA_INICIO']; ?></td><td><?php echo $row['FECHA_COMPROMISO']; ?></td><td><?php echo $row['ESTATUS']; ?></td></tr><?php endforeach; ?></tbody></table>
</body>
</html>