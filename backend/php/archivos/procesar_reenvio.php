<?php
session_start();
if (!isset($_SESSION['dg_id'])) {
    header("Location: ../../frontend/login.php");
    exit();
}

require '../../db/conexion.php';
require_once '../util/notificaciones_util.php';

/*
    Procesa:
      - tipo = 'DOC'  -> reenvío de documentos (igual a tu lógica)
      - tipo = 'MEMO' -> responder memorándum (OBLIGA N° INFORME) y FINALIZA
*/

$tipo = $_POST['tipo'] ?? 'DOC';
$area_origen = isset($_SESSION['dg_area_id']) ? (int)$_SESSION['dg_area_id'] : null;

if (!$area_origen) {
    $_SESSION['flash_type'] = 'error';
    $_SESSION['flash_text'] = '❌ No se pudo determinar tu área.';
    header("Location: ../../../frontend/archivos/reenviar.php");
    exit;
}

try {
    if ($tipo === 'DOC') {
        // -------------------- REENVIAR DOCUMENTO --------------------
        $documento_id  = isset($_POST['id_documento']) ? (int)$_POST['id_documento'] : null;
        $nueva_area    = isset($_POST['nueva_area']) ? (int)$_POST['nueva_area'] : null;
        $observacion   = trim($_POST['observacion'] ?? '');

        $numero_folios = isset($_POST['numero_folios']) ? (int)$_POST['numero_folios'] : null;
        $id_informe    = isset($_POST['id_informe']) && $_POST['id_informe'] !== '' ? (int)$_POST['id_informe'] : null;

        if (!$documento_id || !$nueva_area) {
            throw new Exception("❌ Datos incompletos para reenviar.");
        }

        // Si no vino folios, tomar el actual del documento
        if (!$numero_folios || $numero_folios < 1) {
            $qFol = $pdo->prepare("SELECT COALESCE(NULLIF(NumeroFolios,0),1) FROM documentos WHERE IdDocumentos = ?");
            $qFol->execute([$documento_id]);
            $numero_folios = (int)$qFol->fetchColumn();
            if ($numero_folios < 1) $numero_folios = 1;
        }

        // Validar informe si viene
        if ($id_informe) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM informes WHERE IdInforme = ?");
            $stmt->execute([$id_informe]);
            if ((int)$stmt->fetchColumn() === 0) {
                throw new Exception("❌ Informe inválido o inexistente.");
            }
        }

        // Doble envío al mismo destino (último movimiento)
        $ultimo_movimiento = $pdo->prepare("
            SELECT AreaOrigen, AreaDestino 
            FROM movimientodocumento 
            WHERE IdDocumentos = ? 
            ORDER BY IdMovimientoDocumento DESC 
            LIMIT 1
        ");
        $ultimo_movimiento->execute([$documento_id]);
        $ultimo = $ultimo_movimiento->fetch(PDO::FETCH_ASSOC);

        if ($ultimo && (int)$ultimo['AreaOrigen'] === $area_origen && (int)$ultimo['AreaDestino'] === $nueva_area) {
            throw new Exception("❌ Este documento ya fue reenviado a esta área.");
        }

        // Validar transición entre áreas
        $verificacion_transicion = $pdo->prepare("
            SELECT COUNT(*) FROM transiciones_areas 
            WHERE area_origen = ? AND area_destino = ?
        ");
        $verificacion_transicion->execute([$area_origen, $nueva_area]);
        if ((int)$verificacion_transicion->fetchColumn() === 0) {
            throw new Exception("❌ No se puede reenviar el documento desde esta área a la nueva área.");
        }

        // Insertar movimiento
        $stmt = $pdo->prepare("
            INSERT INTO movimientodocumento 
            (IdDocumentos, AreaOrigen, AreaDestino, FechaMovimiento, Observacion, Recibido, NumeroFolios, IdInforme)
            VALUES (?, ?, ?, NOW(), ?, 0, ?, ?)
        ");
        $stmt->execute([$documento_id, $area_origen, $nueva_area, $observacion, $numero_folios, $id_informe]);

        // Actualizar documento: REENVIADO (5) + folios + destino final
        $update = $pdo->prepare("
            UPDATE documentos 
            SET IdEstadoDocumento = 5, NumeroFolios = ?, IdAreaFinal = ?
            WHERE IdDocumentos = ?
        ");
        $update->execute([$numero_folios, $nueva_area, $documento_id]);

        // Notificación
        $consulta = $pdo->prepare("SELECT NumeroDocumento FROM documentos WHERE IdDocumentos = ?");
        $consulta->execute([$documento_id]);
        $numero_documento = $consulta->fetchColumn();

        if ($numero_documento) {
            $mensaje = "Has recibido un documento reenviado: N° $numero_documento (Folios: $numero_folios)";
            crearNotificacion($pdo, $nueva_area, $mensaje);
        }

        // Liberar documento del área origen si aplica
        $eliminar = $pdo->prepare("UPDATE documentos SET IdAreas = NULL WHERE IdDocumentos = ? AND IdAreas = ?");
        $eliminar->execute([$documento_id, $area_origen]);

        $_SESSION['flash_type'] = 'success';
        $_SESSION['flash_text'] = '✅ Documento reenviado correctamente.';
        header("Location: ../../../frontend/archivos/reenviar.php");
        exit;
    }

    if ($tipo === 'MEMO') {
        // -------------------- RESPONDER MEMORÁNDUM (FINALIZA) --------------------
        $id_memo       = isset($_POST['id_memo']) ? (int)$_POST['id_memo'] : null;
        $nueva_area    = isset($_POST['nueva_area']) ? (int)$_POST['nueva_area'] : null; // normalmente = área origen del memo
        $observacion   = trim($_POST['observacion'] ?? '');
        $numero_folios = isset($_POST['numero_folios']) ? (int)$_POST['numero_folios'] : 0;
        $id_informe    = isset($_POST['id_informe']) && $_POST['id_informe'] !== '' ? (int)$_POST['id_informe'] : null;

        if (!$id_memo || !$nueva_area) {
            throw new Exception("❌ Datos incompletos para responder el memorándum.");
        }
        if (!$id_informe || $id_informe < 1) {
            throw new Exception("❌ Ingrese el N° de Informe para responder el memorándum.");
        }

        // Validar existencia del memo
        $qMemo = $pdo->prepare("SELECT CodigoMemo, IdAreaOrigen FROM memorandums WHERE IdMemo = ?");
        $qMemo->execute([$id_memo]);
        $memo = $qMemo->fetch(PDO::FETCH_ASSOC);
        if (!$memo) {
            throw new Exception("❌ Memorándum no encontrado.");
        }

        // La respuesta de MEMO ya no reenvía: FINALIZA y notifica
        $pdo->beginTransaction();

        // Marcar destinos como recibidos por si quedara alguno pendiente
        $pdo->prepare("UPDATE memorandum_destinos SET Recibido = 1 WHERE IdMemo = ?")->execute([$id_memo]);

        // Finalizar memo (usa 6 si tu catálogo = FINALIZADO)
        $pdo->prepare("UPDATE memorandums SET IdEstadoDocumento = 6 WHERE IdMemo = ?")->execute([$id_memo]);

        // Notificación al emisor/origen con N° de informe
        $codigo  = $memo['CodigoMemo'];
        $msg = "Respuesta al memorándum N° {$codigo}: Informe #{$id_informe}";
        if ($observacion !== '') { $msg .= " - {$observacion}"; }
        crearNotificacion($pdo, (int)$memo['IdAreaOrigen'], $msg);

        $pdo->commit();

        $_SESSION['flash_type'] = 'success';
        $_SESSION['flash_text'] = '✅ Memorándum respondido y finalizado.';
        header("Location: ../../../frontend/archivos/reenviar.php");
        exit;
    }

    // Tipo no soportado
    throw new Exception("❌ Tipo no soportado.");
} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) { $pdo->rollBack(); }
    $_SESSION['flash_type'] = 'error';
    $_SESSION['flash_text'] = $e->getMessage();
    header("Location: ../../../frontend/archivos/reenviar.php");
    exit;
}
