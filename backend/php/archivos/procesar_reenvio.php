<?php
session_start();
if (!isset($_SESSION['dg_id'])) {
    header("Location: ../../frontend/login.php");
    exit();
}

require '../../db/conexion.php';
require_once '../util/notificaciones_util.php';

$documento_id = $_POST['id_documento'] ?? null;
$nueva_area = $_POST['nueva_area'] ?? null;
$observacion = trim($_POST['observacion'] ?? '');
$area_origen = $_SESSION['dg_area_id'] ?? null;

if (!$documento_id || !$nueva_area || !$area_origen) {
    die("❌ Datos incompletos para reenviar.");
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

if ($ultimo) {
    if ($ultimo['AreaOrigen'] == $area_origen && $ultimo['AreaDestino'] == $nueva_area) {
        die("❌ Este documento ya fue reenviado a esta área.");
    }
}

// Verificar si la transición entre áreas es válida (no puede reenviar a la misma área y debe existir la transición)
$verificacion_transicion = $pdo->prepare("SELECT COUNT(*) FROM transiciones_areas WHERE area_origen = ? AND area_destino = ?");
$verificacion_transicion->execute([$area_origen, $nueva_area]);
$transicion_valida = $verificacion_transicion->fetchColumn();

if ($transicion_valida == 0) {
    die("❌ No se puede reenviar el documento desde esta área a la nueva área.");
}

// Insertar el nuevo movimiento en movimientodocumento
$stmt = $pdo->prepare("
    INSERT INTO movimientodocumento (IdDocumentos, AreaOrigen, AreaDestino, FechaMovimiento, Observacion, Recibido)
    VALUES (?, ?, ?, NOW(), ?, 0)
");
$stmt->execute([$documento_id, $area_origen, $nueva_area, $observacion]);

// Cambiar el estado del documento a "Reenviado" (IdEstadoDocumento = 5)
$update = $pdo->prepare("UPDATE documentos SET IdEstadoDocumento = 5 WHERE IdDocumentos = ?");
$update->execute([$documento_id]);

// Obtener el número del documento para la notificación
$consulta = $pdo->prepare("SELECT NumeroDocumento FROM documentos WHERE IdDocumentos = ?");
$consulta->execute([$documento_id]);
$numero_documento = $consulta->fetchColumn();

// Crear la notificación para la nueva área destino
if ($numero_documento) {
    $mensaje = "Has recibido un documento reenviado: N° $numero_documento";
    crearNotificacion($pdo, $nueva_area, $mensaje);
}

// Actualizar el campo IdAreas para que el documento ya no esté en manos del área que lo envía
$eliminar = $pdo->prepare("UPDATE documentos SET IdAreas = NULL WHERE IdDocumentos = ? AND IdAreas = ?");
$eliminar->execute([$documento_id, $area_origen]);

// Redirigir a la página principal de reenviar
header("Location: ../../../frontend/archivos/reenviar.php");
exit;
