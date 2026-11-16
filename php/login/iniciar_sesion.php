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

$stmt = $pdo->prepare("SELECT nombre, contrasena FROM usuarios WHERE usuario = :usuario");
$stmt->execute(['usuario' => $usuarioInput]);
$datosUsuario = $stmt->fetch();

if ($datosUsuario && password_verify($password, $datosUsuario['contrasena'])) {
    session_regenerate_id(true);

    $_SESSION['usuario_id']     = $datosUsuario['id'];
    $_SESSION['usuario_nombre'] = $datosUsuario['nombre'];

    header("Location: ../../views/inicio.html");
    exit();
} else {
    header("Location: ../../views/login.html");
    exit();
}