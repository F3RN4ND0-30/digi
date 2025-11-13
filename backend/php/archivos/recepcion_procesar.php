<?php
session_start();

if (!isset($_SESSION['dg_id'])) {
    header("Location: ../login.php");
    exit();
}

require '../../db/conexion.php';
require_once '../util/notificaciones_util.php';

$area_destino = $_SESSION['dg_area_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo'] ?? 'DOC';

    /* ======================================================
       FLUJO DOCUMENTO (igual que ya tenías)
       ====================================================== */
    if ($tipo === 'DOC') {
        $id_movimiento = $_POST['id_movimiento'] ?? null;

        if ($id_movimiento) {
            $pdo->beginTransaction();
            try {
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

                if ($datos['IdEstadoDocumento'] == 4) { // BLOQUEADO
                    $pdo->rollBack();
                    $_SESSION['mensaje'] = "⚠️ No se puede recepcionar un documento con estado 'Bloqueado'.";
                    header("Location: ../../../frontend/archivos/recepcion.php");
                    exit();
                }

                $id_documento     = $datos['IdDocumentos'];
                $area_origen      = $datos['AreaOrigen'];
                $numero_documento = $datos['NumeroDocumento'];

                // 2. Marcar como recibido en movimiento
                $stmt = $pdo->prepare("UPDATE movimientodocumento SET Recibido = 1 WHERE IdMovimientoDocumento = ?");
                $stmt->execute([$id_movimiento]);

                // 3. Actualizar estado del documento a "Recibido" (3)
                $stmt3 = $pdo->prepare("UPDATE documentos SET IdEstadoDocumento = 3, IdAreaFinal = ? WHERE IdDocumentos = ?");
                $stmt3->execute([$id_documento, $area_destino]);

                // 4. Notificación al área de origen
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

    /* ======================================================
       FLUJO MEMORÁNDUM
       - Marca Recibido = 1 en memorandum_destinos para esta área
       - Si ya no quedan destinos con Recibido = 0 => memo IdEstadoDocumento = 3 (RECIBIDO)
       - Notifica al área de origen
       ====================================================== */
    if ($tipo === 'MEMO') {
        $id_memo = $_POST['id_memo'] ?? null;

        if (!$area_destino || !$id_memo) {
            $_SESSION['mensaje'] = "❌ Datos inválidos.";
            header("Location: ../../../frontend/archivos/recepcion.php");
            exit();
        }

        $pdo->beginTransaction();
        try {
            // Info del memo
            $stmt = $pdo->prepare("SELECT IdAreaOrigen, CodigoMemo FROM memorandums WHERE IdMemo = ?");
            $stmt->execute([$id_memo]);
            $memo = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$memo) {
                throw new Exception("Memorándum no encontrado.");
            }

            // 1) Marcar ESTE destino como recibido (si aún no lo estaba)
            $updDest = $pdo->prepare("
                UPDATE memorandum_destinos
                SET Recibido = 1
                WHERE IdMemo = ? AND IdAreaDestino = ? AND Recibido = 0
            ");
            $updDest->execute([$id_memo, $area_destino]);

            // Si no afectó filas, probablemente ya estaba recepcionado
            if ($updDest->rowCount() === 0) {
                throw new Exception("Este memorándum ya fue recepcionado por tu área.");
            }

            // 2) ¿Quedan destinos sin recibir?
            $cnt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM memorandum_destinos 
                WHERE IdMemo = ? AND Recibido = 0
            ");
            $cnt->execute([$id_memo]);
            $restantes = (int)$cnt->fetchColumn();

            if ($restantes === 0) {
                // Nadie más pendiente -> marcar memo como RECIBIDO (IdEstadoDocumento = 3)
                $upd = $pdo->prepare("UPDATE memorandums SET IdEstadoDocumento = 3 WHERE IdMemo = ?");
                $upd->execute([$id_memo]);
            }

            // 3) Notificar al área de origen
            $codigo  = $memo['CodigoMemo'];
            $mensaje = "El memorándum N° $codigo ha sido recepcionado.";
            crearNotificacion($pdo, (int)$memo['IdAreaOrigen'], $mensaje);

            $pdo->commit();
            $_SESSION['mensaje'] = "✅ Memorándum recepcionado correctamente.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['mensaje'] = "❌ Error al recepcionar el memorándum: " . $e->getMessage();
        }

        header("Location: ../../../frontend/archivos/recepcion.php");
        exit();
    }

    // Tipo desconocido
    $_SESSION['mensaje'] = "❌ Tipo no soportado.";
    header("Location: ../../../frontend/archivos/recepcion.php");
    exit();
}
