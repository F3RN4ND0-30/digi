<?php
// procesar_login.php
session_start();
require_once 'db/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'];
    $contraseña = $_POST['clave'];

    $stmt = $pdo->prepare("SELECT u.*, a.Nombre AS nombre_area
                            FROM usuarios u
                            INNER JOIN areas a ON u.IdAreas = a.IdAreas
                            WHERE u.usuario = :usuario AND u.estado = 1
                            LIMIT 1");
    $stmt->execute(['usuario' => $usuario]);
    $usuarioDB = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuarioDB && password_verify($contraseña, $usuarioDB['Clave'])) {
        $_SESSION['usuario'] = $usuarioDB['Usuario'];
        $_SESSION['area'] = $usuarioDB['nombre_area'];
        $_SESSION['nombre'] = $usuarioDB['Nombres'];
        $_SESSION['rol'] = $usuarioDB['IdRol'];
        $_SESSION['id'] = $usuarioDB['IdUsuarios'];
        header('Location: /digi/frontend/sisvis/escritorio.php');
    } else {
        header('Location: login.php?error=Usuario o contraseña incorrectos');
    }
}
