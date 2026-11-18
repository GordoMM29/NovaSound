<?php
session_start();
require_once __DIR__ . '/../database/conexion.php';

if (empty($_SESSION['usuario_id'])) {
    exit('No autorizado');
}

try {
    $pdo = conexion::obtenerInstancia()->obtenerConexion();
    $sql = "SELECT u.ID_USUARIO, u.NOMBRE, u.APELLIDOS, r.NOMBRE AS ROL, t.NOMBRE AS TURNO, u.AREA, u.FECHA_INGRESO
            FROM usuarios u
            INNER JOIN roles r ON u.ID_rol = r.ID_ROL
            LEFT JOIN turnos t ON u.ID_turno = t.ID_TURNO";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Reporte de Usuarios - NovaSound</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
            h1 { text-align: center; color: #4a148c; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: center; font-size: 12px; }
            th { background-color: #7b1fa2; color: white; }
        </style>
    </head>
    <body onload="window.print()">
        <h1>NovaSound - Reporte de Usuarios del Sistema</h1>
        <p>Fecha de emisión: <?php echo date('d/m/Y H:i:s'); ?></p>
        <table>
            <thead>
                <tr>
                    <th>ID Usuario</th>
                    <th>Nombre Completo</th>
                    <th>Rol</th>
                    <th>Turno</th>
                    <th>Área</th>
                    <th>Fecha Ingreso</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($usuarios as $u): ?>
                <tr>
                    <td><?php echo $u['ID_USUARIO']; ?></td>
                    <td><?php echo $u['NOMBRE'] . ' ' . $u['APELLIDOS']; ?></td>
                    <td><?php echo $u['ROL']; ?></td>
                    <td><?php echo $u['TURNO'] ?? 'N/D'; ?></td>
                    <td><?php echo $u['AREA']; ?></td>
                    <td><?php echo $u['FECHA_INGRESO']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </body>
    </html>
    <?php
} catch (Exception $e) {
    echo "Error al generar el reporte";
}