<?php
session_start();
require_once __DIR__ . '/../database/conexion.php';
if (empty($_SESSION['usuario_id'])) { exit('No autorizado'); }
$pdo = conexion::obtenerInstancia()->obtenerConexion();
$stmt = $pdo->query("SELECT * FROM cierres_turno");
$datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Reporte - Cierres de Turno</title>
<style>body{font-family:Arial;margin:20px;color:#333;}h1{color:#4a148c;text-align:center;}table{width:100%;border-collapse:collapse;margin-top:20px;}th,td{border:1px solid #ddd;padding:6px;text-align:center;font-size:11px;}th{background:#7b1fa2;color:white;}</style>
</head>
<body onload="window.print()">
    <h1>NovaSound - Reporte de Cierres de Turno</h1>
    <p>Fecha de emisión: <?php echo date('d/m/Y H:i:s'); ?></p>
    <table><thead><tr><th>ID Cierre</th><th>Turno</th><th>Línea</th><th>Supervisor</th><th>Fecha</th><th>Prod.</th><th>Insp.</th><th>Emp.</th><th>Emb.</th><th>Estatus</th></tr></thead>
    <tbody><?php foreach($datos as $row): ?><tr><td><?php echo $row['ID_CIERRE']; ?></td><td><?php echo $row['ID_TURNO']; ?></td><td><?php echo $row['ID_LINEA']; ?></td><td><?php echo $row['ID_USUARIO_SUPERVISOR']; ?></td><td><?php echo $row['FECHA_CIERRE']; ?></td><td><?php echo $row['UNIDADES_PRODUCIDAS']; ?></td><td><?php echo $row['UNIDADES_INSPECCIONADAS']; ?></td><td><?php echo $row['UNIDADES_EMPACADAS']; ?></td><td><?php echo $row['CAJAS_EMBARCADAS']; ?></td><td><?php echo $row['ESTATUS']; ?></td></tr><?php endforeach; ?></tbody></table>
</body>
</html>