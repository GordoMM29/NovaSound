<?php
session_start();
require_once __DIR__ . '/../database/conexion.php';
if (empty($_SESSION['usuario_id'])) { exit('No autorizado'); }
$pdo = conexion::obtenerInstancia()->obtenerConexion();
$stmt = $pdo->query("SELECT c.ID_CAJA, c.ID_orden, c.CANTIDAD_UNIDADES, c.ESTATUS AS ESTATUS_CAJA, e.ID_EMBARQUE, e.FECHA_EMBARQUE, e.DESTINO, e.ESTATUS AS ESTATUS_EMBARQUE FROM cajas c LEFT JOIN embarques e ON c.ID_CAJA = e.ID_caja");
$datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Histórico - Almacén y Embarques</title>
<style>body{font-family:Arial;margin:20px;color:#333;}h1{color:#4a148c;text-align:center;}table{width:100%;border-collapse:collapse;margin-top:20px;}th,td{border:1px solid #ddd;padding:6px;text-align:center;font-size:11px;}th{background:#7b1fa2;color:white;}</style>
</head>
<body onload="window.print()">
    <h1>NovaSound - Reporte Histórico de Almacén y Embarques</h1>
    <p>Fecha de emisión: <?php echo date('d/m/Y H:i:s'); ?></p>
    <table><thead><tr><th>ID Caja</th><th>Orden</th><th>Unidades</th><th>Estatus Caja</th><th>ID Embarque</th><th>Fecha Embarque</th><th>Destino</th><th>Estatus Embarque</th></tr></thead>
    <tbody><?php foreach($datos as $row): ?><tr><td><?php echo $row['ID_CAJA']; ?></td><td><?php echo $row['ID_orden']; ?></td><td><?php echo $row['CANTIDAD_UNIDADES']; ?></td><td><?php echo $row['ESTATUS_CAJA']; ?></td><td><?php echo $row['ID_EMBARQUE'] ?? 'N/D'; ?></td><td><?php echo $row['FECHA_EMBARQUE'] ?? 'N/D'; ?></td><td><?php echo $row['DESTINO'] ?? 'N/D'; ?></td><td><?php echo $row['ESTATUS_EMBARQUE'] ?? 'N/D'; ?></td></tr><?php endforeach; ?></tbody></table>
</body>
</html>