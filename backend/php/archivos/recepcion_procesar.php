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
        // Marcar como recibido
        $pdo->beginTransaction();

        try {
            // 1. Actualizar movimientodocumento
            $stmt = $pdo->prepare("UPDATE movimientodocumento SET Recibido = 1 WHERE IdMovimientoDocumento = ?");
            $stmt->execute([$id_movimiento]);

            // 2. Obtener IdDocumentos relacionado al movimiento
            $stmt2 = $pdo->prepare("SELECT IdDocumentos FROM movimientodocumento WHERE IdMovimientoDocumento = ?");
            $stmt2->execute([$id_movimiento]);
            $id_documento = $stmt2->fetchColumn();

            if ($id_documento) {
                // 3. Actualizar estado del documento a 3
                $stmt3 = $pdo->prepare("UPDATE documentos SET IdEstadoDocumento = 3 WHERE IdDocumentos = ?");
                $stmt3->execute([$id_documento]);
            }

            $pdo->commit();
            $_SESSION['mensaje'] = "✅ Documento marcado como recibido y estado actualizado.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['mensaje'] = "❌ Error al actualizar el estado del documento: " . $e->getMessage();
        }
    } else {
        $_SESSION['mensaje'] = "❌ Datos inválidos.";
    }

    header("Location: ../../../frontend/archivos/recepcion.php");
    exit();
}
