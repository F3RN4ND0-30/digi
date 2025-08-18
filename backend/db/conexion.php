<?php
// db/connection.php

$host = 'localhost';          // o 127.0.0.1
$dbname = 'digi';         // nombre de tu base de datos
$username = 'root';           // usuario por defecto en XAMPP
$password = '';               // contraseña por defecto en XAMPP (vacía)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    // Establecer modo de errores
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("❌ Error de conexión: " . $e->getMessage());
}
?>
