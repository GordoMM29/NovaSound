<?php

class conexion {

    private static ?conexion $instancia = null;
    private PDO $conexion;

    private string $host = "localhost";
    private string $db   = "novasounddb";
    private string $user = "root";
    private string $pass = "";
    private string $charset = "utf8mb4";

    private function __construct() {
        $dsn = "mysql:host={$this->host};dbname={$this->db};charset={$this->charset}";
        
        $opciones = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, 
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       
            PDO::ATTR_EMULATE_PREPARES   => false,                  
        ];

        try {

            $this->conexion = new PDO($dsn, $this->user, $this->pass, $opciones);

        } catch (PDOException $e) {

            echo "Error de conexión: " . $e->getMessage();

        }
    }

    public static function obtenerInstancia(): conexion {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    public function obtenerConexion(): PDO {
        return $this->conexion;
    }
    
}