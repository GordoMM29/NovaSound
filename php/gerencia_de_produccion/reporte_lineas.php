<?php
session_start();
require_once __DIR__ . '/../database/conexion.php';
if (empty($_SESSION['usuario_id'])) { exit('No autorizado'); }
$pdo = conexion::obtenerInstancia()->obtenerConexion();
$stmt = $pdo->query("SELECT l.ID_LINEA, l.NOMBRE AS LINEA, l.AREA, t.NOMBRE AS TURNO FROM lineas l INNER JOIN turnos t ON l.ID_turno = t.ID_TURNO");
$datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Reporte - Líneas de Producción</title>
<style>body{font-family:Arial;margin:20px;color:#333;}h1{color:#4a148c;text-align:center;}table{width:100%;border-collapse:collapse;margin-top:20px;}th,td{border:1px solid #ddd;padding:8px;text-align:center;font-size:12px;}th{background:#7b1fa2;color:white;}</style>
</head>
<body onload="window.print()">
    <h1>NovaSound - Reporte de Líneas de Producción</h1>
    <p>Fecha de emisión: <?php echo date('d/m/Y H:i:s'); ?></p>
    <table><thead><tr><th>ID Línea</th><th>Nombre de Línea</th><th>Área</th><th>Turno Asociado</th></tr></thead>
    <tbody><?php foreach($datos as $row): ?><tr><td><?php echo $row['ID_LINEA']; ?></td><td><?php echo $row['LINEA']; ?></td><td><?php echo $row['AREA']; ?></td><td><?php echo $row['TURNO']; ?></td></tr><?php endforeach; ?></tbody></table>
</body>
</html>