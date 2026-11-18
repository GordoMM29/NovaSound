<?php
session_start();
require_once __DIR__ . '/../database/conexion.php';

if (empty($_SESSION['usuario_id'])) {
    header("Location: ../../vistas/login.html");
    exit();
}

try {
    $pdo = conexion::obtenerInstancia()->obtenerConexion();

    $sql = "SELECT 
                op.ID_ORDEN, 
                c.NOMBRE AS CLIENTE, 
                p.NOMBRE AS MODELO, 
                op.CANTIDAD_PRODUCIDA AS CANTIDAD, 
                op.COLOR, 
                op.FECHA_COMPROMISO, 
                l.NOMBRE AS LINEA, 
                t.NOMBRE AS TURNO
            FROM usuarios u
            INNER JOIN lineas l ON u.ID_linea = l.ID_LINEA
            INNER JOIN turnos t ON u.ID_turno = t.ID_TURNO
            INNER JOIN ordenes_produccion op ON op.ID_linea = l.ID_LINEA
            INNER JOIN clientes c ON op.ID_cliente = c.ID_CLIENTE
            INNER JOIN productos p ON op.ID_producto = p.ID_PRODUCTO
            WHERE u.ID_USUARIO = :usuario_id
            ORDER BY op.FECHA_INICIO DESC
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'usuario_id' => $_SESSION['usuario_id']
    ]);
    $orden = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($orden) {
        $_SESSION['orden_id']       = $orden['ID_ORDEN'];
        $_SESSION['orden_cliente']  = $orden['CLIENTE'];
        $_SESSION['orden_modelo']   = $orden['MODELO'];
        $_SESSION['orden_cantidad'] = $orden['CANTIDAD'];
        $_SESSION['orden_color']    = $orden['COLOR'];
        $_SESSION['orden_fecha']    = $orden['FECHA_COMPROMISO'];
        $_SESSION['orden_linea']    = $orden['LINEA'];
        $_SESSION['orden_turno']    = $orden['TURNO'];
    } else {
        $_SESSION['orden_id']       = 'SIN ASIGNAR';
        $_SESSION['orden_cliente']  = 'N/D';
        $_SESSION['orden_modelo']   = 'N/D';
        $_SESSION['orden_cantidad'] = 0;
        $_SESSION['orden_color']    = 'N/D';
        $_SESSION['orden_fecha']    = date('Y-m-d');
        $_SESSION['orden_linea']    = 'Sin línea';
        $_SESSION['orden_turno']    = 'Sin turno';
    }

    header("Location: ../../vistas/produccion.html");
    exit();

} catch (Exception $e) {
    $_SESSION['orden_id']       = 'ERROR SQL';
    $_SESSION['orden_cliente']  = $e->getMessage();
    $_SESSION['orden_modelo']   = '';
    $_SESSION['orden_cantidad'] = '';
    $_SESSION['orden_color']    = '';
    $_SESSION['orden_fecha']    = '';
    $_SESSION['orden_linea']    = '';
    $_SESSION['orden_turno']    = '';
    
    header("Location: ../../vistas/produccion.html");
    exit();
}