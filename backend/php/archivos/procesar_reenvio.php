<?php
session_start();
if (!isset($_SESSION['dg_id'])) {
    header("Location: ../../frontend/login.php");
    exit();
}

require '../../db/conexion.php';
require_once '../util/notificaciones_util.php';

$tipo           = $_POST['tipo'] ?? 'DOC';       // DOC | MEMO
$documento_id   = $_POST['id_documento'] ?? null;
$memo_id        = $_POST['id_memo'] ?? null;
$nueva_area     = $_POST['nueva_area'] ?? null;   // en MEMO llega el área emisora (hidden) pero igual se fuerza
$observacion    = trim($_POST['observacion'] ?? '');
$area_origen    = $_SESSION['dg_area_id'] ?? null;

$numero_folios  = $_POST['numero_folios'] ?? null;   // obligatorio en DOC, opcional en MEMO
$id_informe     = $_POST['id_informe'] ?? null;       // solo DOC

if (!$area_origen) {
    die("❌ No se pudo determinar el área del usuario.");
}

try {
    if ($tipo === 'DOC') {
        /* =======================  DOCUMENTO  ======================= */
        if (!$documento_id || !$nueva_area) {
            throw new Exception("❌ Datos incompletos para reenviar documento.");
        }

        // Validar informe si llega
        if ($id_informe) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM informes WHERE IdInforme = ?");
            $stmt->execute([$id_informe]);
            if ($stmt->fetchColumn() == 0) {
                throw new Exception("❌ Informe inválido o inexistente.");
            }
        }

        // Último movimiento (evitar duplicados exactos)
        $ultimo_mov = $pdo->prepare("
            SELECT AreaOrigen, AreaDestino 
            FROM movimientodocumento 
            WHERE IdDocumentos = ? 
            ORDER BY IdMovimientoDocumento DESC 
            LIMIT 1
        ");
        $ultimo_mov->execute([$documento_id]);
        $ultimo = $ultimo_mov->fetch(PDO::FETCH_ASSOC);

        if ($ultimo && (int)$ultimo['AreaOrigen'] === (int)$area_origen && (int)$ultimo['AreaDestino'] === (int)$nueva_area) {
            throw new Exception("❌ Este documento ya fue reenviado a esa área.");
        }

        // Verificar transición válida
        $verif = $pdo->prepare("SELECT COUNT(*) FROM transiciones_areas WHERE area_origen = ? AND area_destino = ?");
        $verif->execute([$area_origen, $nueva_area]);
        if ($verif->fetchColumn() == 0) {
            throw new Exception("❌ No se puede reenviar el documento desde esta área a la nueva área.");
        }

        // Insertar movimiento
        $ins = $pdo->prepare("
            INSERT INTO movimientodocumento 
                (IdDocumentos, AreaOrigen, AreaDestino, FechaMovimiento, Observacion, Recibido, NumeroFolios, IdInforme)
            VALUES (?, ?, ?, NOW(), ?, 0, ?, ?)
        ");
        $ins->execute([$documento_id, $area_origen, $nueva_area, $observacion, $numero_folios, $id_informe]);

        // Actualizar estado y folios
        $upd = $pdo->prepare("UPDATE documentos SET IdEstadoDocumento = 5, NumeroFolios = ? WHERE IdDocumentos = ?");
        $upd->execute([$numero_folios, $documento_id]);

        // Actualizar el área final
        $upd2 = $pdo->prepare("UPDATE documentos SET IdAreaFinal = ? WHERE IdDocumentos = ?");
        $upd2->execute([$nueva_area, $documento_id]);

        // Notificación
        $numero_documento = $pdo->prepare("SELECT NumeroDocumento FROM documentos WHERE IdDocumentos = ?");
        $numero_documento->execute([$documento_id]);
        if ($nd = $numero_documento->fetchColumn()) {
            $msg = "Has recibido un documento reenviado: N° $nd (Folios: $numero_folios)";
            crearNotificacion($pdo, $nueva_area, $msg);
        }

        // Liberar de mi área (si aplica)
        $eliminar = $pdo->prepare("UPDATE documentos SET IdAreas = NULL WHERE IdDocumentos = ? AND IdAreas = ?");
        $eliminar->execute([$documento_id, $area_origen]);

        header("Location: ../../../frontend/archivos/reenviar.php");
        exit;
    }

    /* =======================  MEMORÁNDUM  ======================= */
    if ($tipo === 'MEMO') {
        if (!$memo_id) {
            throw new Exception("❌ Falta el IdMemo para responder.");
        }

        // Datos del MEMO (para saber el área emisora real)
        $q = $pdo->prepare("
            SELECT m.IdMemo, m.CodigoMemo, m.IdAreaOrigen
            FROM memorandums m
            WHERE m.IdMemo = ?
            LIMIT 1
        ");
        $q->execute([$memo_id]);
        $memo = $q->fetch(PDO::FETCH_ASSOC);
        if (!$memo) {
            throw new Exception("❌ El memorándum no existe.");
        }

        $area_emisora = (int)$memo['IdAreaOrigen'];

        // Forzar que la respuesta vaya SOLO al área emisora
        $destino_forzado = $area_emisora;

        // Evitar duplicados pendientes (respuesta ya creada y sin recepcionar)
        // (asumiendo que memorandum_destinos tiene Recibido con default 0)
        $chk = $pdo->prepare("
            SELECT COUNT(*) 
            FROM memorandum_destinos 
            WHERE IdMemo = ? AND IdAreaDestino = ? AND COALESCE(Recibido,0) = 0
        ");
        $chk->execute([$memo_id, $destino_forzado]);
        if ((int)$chk->fetchColumn() > 0) {
            throw new Exception("❌ Ya hay una respuesta pendiente enviada al área emisora.");
        }

        // Registrar respuesta (solo los campos que existen seguro)
        $insR = $pdo->prepare("
            INSERT INTO memorandum_destinos (IdMemo, IdAreaDestino)
            VALUES (?, ?)
        ");
        $insR->execute([$memo_id, $destino_forzado]);

        // Poner el MEMO en estado reenviado/seguimiento (usa el que corresponda; aquí 5)
        $updM = $pdo->prepare("UPDATE memorandums SET IdEstadoDocumento = 5 WHERE IdMemo = ?");
        $updM->execute([$memo_id]);

        // Notificación al área emisora
        $codigo = $memo['CodigoMemo'] ?? '';
        $msgM = "Tienes una respuesta al Memorándum {$codigo}.";
        crearNotificacion($pdo, $destino_forzado, $msgM);

        header("Location: ../../../frontend/archivos/reenviar.php");
        exit;
    }

    throw new Exception("❌ Tipo de reenvío inválido.");
} catch (Exception $e) {
    die($e->getMessage());
}
