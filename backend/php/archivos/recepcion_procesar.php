<?php
session_start();

if (!isset($_SESSION['dg_id'])) {
    header("Location: ../login.php");
    exit();
}

require '../../db/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_movimiento = $_POST['id_movimiento'] ?? null;

    if ($id_movimiento) {
        // Actualizar para marcar como recibido
        $stmt = $pdo->prepare("UPDATE movimientodocumento SET Recibido = 1 WHERE IdMovimientoDocumento = ?");
        if ($stmt->execute([$id_movimiento])) {
            $_SESSION['mensaje'] = "✅ Documento marcado como recibido.";
        } else {
            $_SESSION['mensaje'] = "❌ Error al actualizar el estado del documento.";
        }
    } else {
        $_SESSION['mensaje'] = "❌ Datos inválidos.";
    }

    header("Location: ../../../frontend/archivos/recepcion.php");
    exit();
}
