<?php
session_start();

if (!isset($_SESSION['dg_id'])) {
    header("Location: ../login.php");
    exit();
}

require '../../db/conexion.php';
require_once '../util/notificaciones_util.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_movimiento = $_POST['id_movimiento'] ?? null;

    if ($id_movimiento) {
        $pdo->beginTransaction();

        try {
            // 1. Obtener información relacionada, incluyendo el estado del documento
            $stmt2 = $pdo->prepare("
                SELECT md.IdDocumentos, md.AreaOrigen, d.NumeroDocumento, d.IdEstadoDocumento
                FROM movimientodocumento md
                JOIN documentos d ON d.IdDocumentos = md.IdDocumentos
                WHERE md.IdMovimientoDocumento = ?
            ");
            $stmt2->execute([$id_movimiento]);
            $datos = $stmt2->fetch(PDO::FETCH_ASSOC);

            if (!$datos) {
                throw new Exception("No se encontró el documento asociado al movimiento.");
            }

            // Validar que el estado del documento no sea 4
            if ($datos['IdEstadoDocumento'] == 4) {
                $pdo->rollBack();
                $_SESSION['mensaje'] = "⚠️ No se puede recepcionar un documento con estado 'Bloqueado'.";
                header("Location: ../../../frontend/archivos/recepcion.php");
                exit();
            }

            $id_documento = $datos['IdDocumentos'];
            $area_origen = $datos['AreaOrigen'];
            $numero_documento = $datos['NumeroDocumento'];

            // 2. Marcar como recibido
            $stmt = $pdo->prepare("UPDATE movimientodocumento SET Recibido = 1 WHERE IdMovimientoDocumento = ?");
            $stmt->execute([$id_movimiento]);

            // 3. Actualizar estado del documento a "Recibido"
            $stmt3 = $pdo->prepare("UPDATE documentos SET IdEstadoDocumento = 3 WHERE IdDocumentos = ?");
            $stmt3->execute([$id_documento]);

            // 4. Crear notificación para el área de origen
            $mensaje = "El documento N° $numero_documento ha sido recepcionado.";
            crearNotificacion($pdo, $area_origen, $mensaje);

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
