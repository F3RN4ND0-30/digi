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
    $tipo   = $_POST['tipo'] ?? 'DOC';
    $foliosPost = isset($_POST['folios']) ? max(0, (int)$_POST['folios']) : null;

    /* ================= DOC ================= */
    if ($tipo === 'DOC') {
        $id_movimiento = $_POST['id_movimiento'] ?? null;

        if ($id_movimiento) {
            $pdo->beginTransaction();
            try {
                $stmt2 = $pdo->prepare("
                    SELECT md.IdDocumentos, md.AreaOrigen,
                           d.NumeroDocumento, d.IdEstadoDocumento, d.NumeroFolios
                    FROM movimientodocumento md
                    JOIN documentos d ON d.IdDocumentos = md.IdDocumentos
                    WHERE md.IdMovimientoDocumento = ?
                ");
                $stmt2->execute([$id_movimiento]);
                $datos = $stmt2->fetch(PDO::FETCH_ASSOC);
                if (!$datos) {
                    throw new Exception("No se encontró el documento asociado al movimiento.");
                }
                if ((int)$datos['IdEstadoDocumento'] === 4) {
                    $pdo->rollBack();
                    $_SESSION['mensaje'] = "⚠️ No se puede recepcionar un documento con estado 'Bloqueado'.";
                    header("Location: ../../../frontend/archivos/recepcion.php");
                    exit();
                }

                // Guardar folios para reenvío
                $folios = $foliosPost ?? (int)$datos['NumeroFolios'];
                $_SESSION['folios_actual'] = max(0, (int)$folios);

                $id_documento     = (int)$datos['IdDocumentos'];
                $area_origen      = (int)$datos['AreaOrigen'];
                $numero_documento = $datos['NumeroDocumento'];

                $stmt = $pdo->prepare("
    UPDATE movimientodocumento 
    SET Recibido = 1,
        FechaRecibido = NOW(),
        IdUsuarioRecibe = ?
    WHERE IdMovimientoDocumento = ?
    AND Recibido = 0
");
                $stmt->execute([$_SESSION['dg_id'], $id_movimiento]);

                if ($stmt->rowCount() === 0) {
                    throw new Exception("El documento ya fue recepcionado anteriormente.");
                }


                $stmt3 = $pdo->prepare("UPDATE documentos SET IdEstadoDocumento = 3, IdAreaFinal = ? WHERE IdDocumentos = ?");
                $stmt3->execute([$area_destino, $id_documento]);

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

    /* ================= MEMO ================= */
    if ($tipo === 'MEMO') {
        $id_memo = $_POST['id_memo'] ?? null;

        if (!$area_destino || !$id_memo) {
            $_SESSION['mensaje'] = "❌ Datos inválidos.";
            header("Location: ../../../frontend/archivos/recepcion.php");
            exit();
        }

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT IdAreaOrigen, CodigoMemo, NumeroFolios FROM memorandums WHERE IdMemo = ?");
            $stmt->execute([$id_memo]);
            $memo = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$memo) {
                throw new Exception("Memorándum no encontrado.");
            }

            // Guardar folios para reenvío
            $folios = $foliosPost ?? (int)$memo['NumeroFolios'];
            $_SESSION['folios_actual'] = max(0, (int)$folios);

            $updDest = $pdo->prepare("
    UPDATE memorandum_destinos
    SET Recibido = 1,
        FechaRecibido = NOW(),
        IdUsuarioRecibe = ?
    WHERE IdMemo = ? 
    AND IdAreaDestino = ? 
    AND Recibido = 0
");
            $updDest->execute([$_SESSION['dg_id'], $id_memo, $area_destino]);

            $cnt = $pdo->prepare("
                SELECT COUNT(*) FROM memorandum_destinos
                WHERE IdMemo = ? AND Recibido = 0
            ");
            $cnt->execute([$id_memo]);
            $restantes = (int)$cnt->fetchColumn();

            if ($restantes === 0) {
                $upd = $pdo->prepare("UPDATE memorandums SET IdEstadoDocumento = 3 WHERE IdMemo = ?");
                $upd->execute([$id_memo]);
            }

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

    $_SESSION['mensaje'] = "❌ Tipo no soportado.";
    header("Location: ../../../frontend/archivos/recepcion.php");
    exit();
}
