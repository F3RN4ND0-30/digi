<?php
session_start();
if (!isset($_SESSION['dg_id'])) {
    header('Location: ../../../frontend/login.php');
    exit;
}

require '../../db/conexion.php';

$id_usuario = $_SESSION['dg_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombres = trim($_POST['nombres'] ?? '');
    $apellidoPat = trim($_POST['apellidoPat'] ?? '');
    $apellidoMat = trim($_POST['apellidoMat'] ?? '');
    $usuarioNombre = trim($_POST['usuario'] ?? '');
    $clave = trim($_POST['clave'] ?? '');
    $repetir_clave = trim($_POST['repetir_clave'] ?? '');

    if ($nombres === '' || $apellidoPat === '' || $apellidoMat === '' || $usuarioNombre === '') {
        $_SESSION['mensaje'] = "❌ Por favor, complete todos los campos obligatorios.";
        header("Location: ../../../frontend/configuracion/perfil.php");
        exit;
    }

    // Validar que las contraseñas coincidan si se intenta cambiar
    if (!empty($clave) && $clave !== $repetir_clave) {
        $_SESSION['mensaje'] = "❌ Las contraseñas no coinciden.";
        header("Location: ../../../frontend/configuracion/perfil.php");
        exit;
    }

    // Preparar SQL
    if (!empty($clave)) {
        $claveHasheada = password_hash($clave, PASSWORD_DEFAULT);
        $sql = "UPDATE usuarios 
                SET Nombres = ?, ApellidoPat = ?, ApellidoMat = ?, Usuario = ?, Clave = ? 
                WHERE IdUsuarios = ?";
        $params = [$nombres, $apellidoPat, $apellidoMat, $usuarioNombre, $claveHasheada, $id_usuario];
    } else {
        $sql = "UPDATE usuarios 
                SET Nombres = ?, ApellidoPat = ?, ApellidoMat = ?, Usuario = ? 
                WHERE IdUsuarios = ?";
        $params = [$nombres, $apellidoPat, $apellidoMat, $usuarioNombre, $id_usuario];
    }

    $stmt = $pdo->prepare($sql);
    $ok = $stmt->execute($params);

    $_SESSION['mensaje'] = $ok
        ? "✅ Perfil actualizado correctamente."
        : "❌ Error al actualizar el perfil.";

    header("Location: ../../../frontend/configuracion/perfil.php");
    exit;
}
