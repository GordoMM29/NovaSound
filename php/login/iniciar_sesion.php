<?php
session_start();
require_once __DIR__ . '/../database/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../views/login.html");
    exit();
}

$usuarioInput = trim($_POST['usuario'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($usuarioInput) || empty($password)) {
    exit();
}

$pdo = conexion::obtenerInstancia()->obtenerConexion();

$stmt = $pdo->prepare("SELECT ID_USUARIO, NOMBRE, CONTRASENA FROM usuarios WHERE CORREO = :correo LIMIT 1");
$stmt->execute(['correo' => $usuarioInput]);
$datosUsuario = $stmt->fetch();

if ($datosUsuario && $password === $datosUsuario['CONTRASENA']) {
    session_regenerate_id(true);

    $_SESSION['usuario_id']     = $datosUsuario['ID_USUARIO'];
    $_SESSION['usuario_nombre'] = $datosUsuario['NOMBRE'];

    header("Location: ../../vistas/inicio.html");
    exit();
} else {
    header("Location: ../../vistas/login.html");
    exit();
}