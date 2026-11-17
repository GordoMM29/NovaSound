
<?php

class Database
{
    public static function conectar(): PDO
    {
        $host = "127.0.0.1";
        $puerto = "3306";
        $bd = "NovaSoundDB";
        $usuario = "root";
        $contrasena = "0852"; // Cambien cada uno su contraseña muchachones

        $dsn = "mysql:host=$host;port=$puerto;dbname=$bd;charset=utf8mb4";

        return new PDO($dsn, $usuario, $contrasena, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
    }
}
