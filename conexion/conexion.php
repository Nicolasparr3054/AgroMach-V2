<?php
// ================================================================
// ARCHIVO DE CONEXIÓN A LA BASE DE DATOS - AGROMATCH
// Ruta: conexion/conexion.php
// ================================================================

$host = 'localhost';
$dbname = 'Agromach';
$username = 'root';

// Array de contraseñas posibles para diferentes configuraciones
$passwords_to_try = ['', '123456', 'password', 'admin'];

$connection = null;

// Intentar conectar con diferentes configuraciones de contraseña
foreach ($passwords_to_try as $password) {
    try {
        $connection = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
        break;
    } catch (PDOException $e) {
        continue;
    }
}

if ($connection === null) {
    die("Error: No se pudo conectar a la base de datos.");
}

function getConnection() {
    global $connection;
    return $connection;
}
?>