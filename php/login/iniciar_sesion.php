<?php
session_start();
require_once __DIR__ . '/../database/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../vistas/login.html");
    exit();
}

$usuarioInput = trim($_POST['usuario'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($usuarioInput) || empty($password)) {
    header("Location: ../../vistas/login.html?error=campos");
    exit();
}

$pdo = conexion::obtenerInstancia()->obtenerConexion();

$stmt = $pdo->prepare("SELECT ID_USUARIO, NOMBRE, CONTRASENA, AREA, ID_rol FROM usuarios WHERE CORREO = :correo LIMIT 1");
$stmt->execute(['correo' => $usuarioInput]);
$datosUsuario = $stmt->fetch();

if ($datosUsuario && $password === $datosUsuario['CONTRASENA']) {
    session_regenerate_id(true);

    $_SESSION['usuario_id']     = $datosUsuario['ID_USUARIO'];
    $_SESSION['usuario_nombre'] = $datosUsuario['NOMBRE'];
    $_SESSION['usuario_area']   = $datosUsuario['AREA'];
    $_SESSION['usuario_rol']    = $datosUsuario['ID_rol'];

    switch (strtoupper($_SESSION['usuario_area'])) {
        case 'PRODUCCION':
            if ($_SESSION['usuario_rol'] === 'ROL-GERPRO') {
                header("Location: ../../vistas/gerenciaProduccion.html");
            } elseif ($_SESSION['usuario_rol'] === 'ROL-SUBPRO') {
                header("Location: ../../vistas/subgerencia.html");
            } else {
                header("Location: ../produccion/buscar_orden_produccion.php");            
                }
            break;
        case 'ENSAMBLE':
            header("Location: ../../vistas/ensamble.html");
            break;
        case 'CALIDAD':
            header("Location: ../../vistas/calidad.html");
            break;
        case 'EMPAQUE':
            header("Location: ../../vistas/empaque.html");
            break;
        case 'EMBARQUE':
            header("Location: ../../vistas/embarque.html");
            break;
        case 'SISTEMAS':
            header("Location: ../../vistas/sistemas.html");
            break;
        case 'GERENCIA DE PLANTA':
            header("Location: ../../vistas/gerenciaPlanta.html");
            break;
        default:
            session_destroy();
            header("Location: ../../vistas/login.html");
            break;
    }
    exit();
} else {
    header("Location: ../../vistas/login.html?error=credenciales");
    exit();
}