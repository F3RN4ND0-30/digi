<?php
session_start();
if (!isset($_SESSION['dg_id'])) {
    header("Location: ../../frontend/login.php");
    exit();
}

require '../../db/conexion.php';
require_once '../util/notificaciones_util.php';

$tipo         = $_POST['tipo'] ?? 'DOC';
$documento_id = $_POST['id_documento'] ?? null;
$memo_id      = $_POST['id_memo'] ?? null;
$nueva_area   = $_POST['nueva_area'] ?? null;
$observacion  = trim($_POST['observacion'] ?? '');
$area_origen  = $_SESSION['dg_area_id'] ?? null;

$numero_folios = $_POST['numero_folios'] ?? null;
$id_informe    = $_POST['id_informe'] ?? null;

$volver = "../../../frontend/archivos/reenviar.php";

if (!$area_origen) {
    $_SESSION['flash_type'] = 'error';
    $_SESSION['flash_text'] = 'No se pudo determinar el área del usuario.';
    header("Location: $volver");
    exit;
}

try {
    if ($tipo === 'DOC') {
        if (!$documento_id || !$nueva_area) {
            throw new Exception('Datos incompletos para reenviar documento.');
        }

        if ($id_informe) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM informes WHERE IdInforme = ?");
            $stmt->execute([$id_informe]);
            if ($stmt->fetchColumn() == 0) throw new Exception('Informe inválido o inexistente.');
        }

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
            throw new Exception('Este documento ya fue reenviado a esa área.');
        }

        $verif = $pdo->prepare("SELECT COUNT(*) FROM transiciones_areas WHERE area_origen = ? AND area_destino = ?");
        $verif->execute([$area_origen, $nueva_area]);
        if ($verif->fetchColumn() == 0) throw new Exception('No se puede reenviar el documento desde esta área a la nueva área.');

        $ins = $pdo->prepare("
            INSERT INTO movimientodocumento
                (IdDocumentos, AreaOrigen, AreaDestino, FechaMovimiento, Observacion, Recibido, NumeroFolios, IdInforme)
            VALUES (?, ?, ?, NOW(), ?, 0, ?, ?)
        ");
        $ins->execute([$documento_id, $area_origen, $nueva_area, $observacion, $numero_folios, $id_informe]);

        $upd = $pdo->prepare("UPDATE documentos SET IdEstadoDocumento = 5, NumeroFolios = ? WHERE IdDocumentos = ?");
        $upd->execute([$numero_folios, $documento_id]);

        $upd2 = $pdo->prepare("UPDATE documentos SET IdAreaFinal = ? WHERE IdDocumentos = ?");
        $upd2->execute([$nueva_area, $documento_id]);

        $numero_documento = $pdo->prepare("SELECT NumeroDocumento FROM documentos WHERE IdDocumentos = ?");
        $numero_documento->execute([$documento_id]);
        if ($nd = $numero_documento->fetchColumn()) {
            $msg = "Has recibido un documento reenviado: N° $nd (Folios: $numero_folios)";
            crearNotificacion($pdo, $nueva_area, $msg);
        }

        $eliminar = $pdo->prepare("UPDATE documentos SET IdAreas = NULL WHERE IdDocumentos = ? AND IdAreas = ?");
        $eliminar->execute([$documento_id, $area_origen]);

        $_SESSION['flash_type'] = 'success';
        $_SESSION['flash_text'] = 'Documento reenviado correctamente.';
        header("Location: $volver");
        exit;
    }

    if ($tipo === 'MEMO') {
        if (!$memo_id) throw new Exception('Falta el IdMemo para responder.');

        $q = $pdo->prepare("SELECT m.IdMemo, m.CodigoMemo, m.IdAreaOrigen FROM memorandums m WHERE m.IdMemo = ? LIMIT 1");
        $q->execute([$memo_id]);
        $memo = $q->fetch(PDO::FETCH_ASSOC);
        if (!$memo) throw new Exception('El memorándum no existe.');

        $destino_forzado = (int)$memo['IdAreaOrigen'];

        $chk = $pdo->prepare("
            SELECT COUNT(*) 
            FROM memorandum_destinos 
            WHERE IdMemo = ? AND IdAreaDestino = ? AND COALESCE(Recibido,0) = 0
        ");
        $chk->execute([$memo_id, $destino_forzado]);
        if ((int)$chk->fetchColumn() > 0) throw new Exception('Ya hay una respuesta pendiente enviada al área emisora.');

        $insR = $pdo->prepare("INSERT INTO memorandum_destinos (IdMemo, IdAreaDestino) VALUES (?, ?)");
        $insR->execute([$memo_id, $destino_forzado]);

        $updM = $pdo->prepare("UPDATE memorandums SET IdEstadoDocumento = 5 WHERE IdMemo = ?");
        $updM->execute([$memo_id]);

        $codigo = $memo['CodigoMemo'] ?? '';
        $msgM = "Tienes una respuesta al Memorándum {$codigo}.";
        crearNotificacion($pdo, $destino_forzado, $msgM);

        $_SESSION['flash_type'] = 'success';
        $_SESSION['flash_text'] = 'Respuesta enviada al área emisora.';
        header("Location: $volver");
        exit;
    }

    throw new Exception('Tipo de reenvío inválido.');
} catch (Exception $e) {
    $_SESSION['flash_type'] = 'error';
    $_SESSION['flash_text'] = $e->getMessage();
    header("Location: $volver");
    exit;
}
