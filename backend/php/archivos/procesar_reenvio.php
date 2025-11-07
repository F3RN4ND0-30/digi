<?php
session_start();
if (!isset($_SESSION['dg_id'])) {
    header("Location: ../../frontend/login.php");
    exit();
}

require '../../db/conexion.php';
require_once '../util/notificaciones_util.php';

$documento_id   = $_POST['id_documento'] ?? null;
$nueva_area     = $_POST['nueva_area'] ?? null;
$observacion    = trim($_POST['observacion'] ?? '');
$area_origen    = $_SESSION['dg_area_id'] ?? null;

// Nuevos campos
$numero_folios  = $_POST['numero_folios'] ?? null;
$id_informe     = $_POST['id_informe'] ?? null;

if (!$documento_id || !$nueva_area || !$area_origen) {
    die("❌ Datos incompletos para reenviar.");
}

try {
    // Validar que el informe exista en la BD
    if ($id_informe) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM informes WHERE IdInforme = ?");
        $stmt->execute([$id_informe]);
        if ($stmt->fetchColumn() == 0) {
            throw new Exception("❌ Informe inválido o inexistente.");
        }
    }

    // Verificar si el último movimiento fue desde esta área origen a la nueva área destino
    $ultimo_movimiento = $pdo->prepare("
        SELECT AreaOrigen, AreaDestino 
        FROM movimientodocumento 
        WHERE IdDocumentos = ? 
        ORDER BY IdMovimientoDocumento DESC 
        LIMIT 1
    ");
    $ultimo_movimiento->execute([$documento_id]);
    $ultimo = $ultimo_movimiento->fetch(PDO::FETCH_ASSOC);

    if ($ultimo && $ultimo['AreaOrigen'] == $area_origen && $ultimo['AreaDestino'] == $nueva_area) {
        throw new Exception("❌ Este documento ya fue reenviado a esta área.");
    }

    // Verificar si la transición entre áreas es válida
    $verificacion_transicion = $pdo->prepare("
        SELECT COUNT(*) FROM transiciones_areas 
        WHERE area_origen = ? AND area_destino = ?
    ");
    $verificacion_transicion->execute([$area_origen, $nueva_area]);
    if ($verificacion_transicion->fetchColumn() == 0) {
        throw new Exception("❌ No se puede reenviar el documento desde esta área a la nueva área.");
    }

    // Insertar el nuevo movimiento
    $stmt = $pdo->prepare("
        INSERT INTO movimientodocumento 
        (IdDocumentos, AreaOrigen, AreaDestino, FechaMovimiento, Observacion, Recibido, NumeroFolios, IdInforme)
        VALUES (?, ?, ?, NOW(), ?, 0, ?, ?)
    ");
    $stmt->execute([$documento_id, $area_origen, $nueva_area, $observacion, $numero_folios, $id_informe]);

    // Actualizar estado del documento y número de folios
    $update = $pdo->prepare("
        UPDATE documentos 
        SET IdEstadoDocumento = 5, NumeroFolios = ?
        WHERE IdDocumentos = ?
    ");
    $update->execute([$numero_folios, $documento_id]);

    // --- NUEVO: actualizar el área final ---
    $update_area_final = $pdo->prepare("
        UPDATE documentos 
        SET IdAreaFinal = ? 
        WHERE IdDocumentos = ?
    ");
    $update_area_final->execute([$nueva_area, $documento_id]);

    // Obtener el número del documento para la notificación
    $consulta = $pdo->prepare("SELECT NumeroDocumento FROM documentos WHERE IdDocumentos = ?");
    $consulta->execute([$documento_id]);
    $numero_documento = $consulta->fetchColumn();

    if ($numero_documento) {
        $mensaje = "Has recibido un documento reenviado: N° $numero_documento (Folios: $numero_folios)";
        crearNotificacion($pdo, $nueva_area, $mensaje);
    }

    // Liberar documento de la área origen
    $eliminar = $pdo->prepare("UPDATE documentos SET IdAreas = NULL WHERE IdDocumentos = ? AND IdAreas = ?");
    $eliminar->execute([$documento_id, $area_origen]);

    header("Location: ../../../frontend/archivos/reenviar.php");
    exit;
} catch (Exception $e) {
    die($e->getMessage());
}