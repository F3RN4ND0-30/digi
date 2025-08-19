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

// Verificar si ya fue reenviado por esta área
$verificacion = $pdo->prepare("SELECT COUNT(*) FROM movimientodocumento WHERE IdDocumentos = ? AND AreaOrigen = ?");
$verificacion->execute([$documento_id, $area_origen]);
$ya_reenviado = $verificacion->fetchColumn();

if ($ya_reenviado > 0) {
    die("❌ Este documento ya fue reenviado.");
}

// Insertar nuevo movimiento
$stmt = $pdo->prepare("INSERT INTO movimientodocumento (IdDocumentos, AreaOrigen, AreaDestino, Recibido, Observacion)
                       VALUES (?, ?, ?, 0, ?)");
$stmt->execute([$documento_id, $area_origen, $nueva_area, $observacion]);

// Cambiar estado del documento a 2 (ej: 'Enviado')
$update = $pdo->prepare("UPDATE documentos SET IdEstadoDocumento = 2 WHERE IdDocumentos = ?");
$update->execute([$documento_id]);

// Obtener el número del documento
$consulta = $pdo->prepare("SELECT NumeroDocumento FROM documentos WHERE IdDocumentos = ?");
$consulta->execute([$documento_id]);
$numero_documento = $consulta->fetchColumn();

// Crear la notificación para la nueva área destino
if ($numero_documento) {
    $mensaje = "Has recibido un documento reenviado: N° $numero_documento";
    crearNotificacion($pdo, $nueva_area, $mensaje);
}

header("Location: ../../../frontend/archivos/reenviar.php");
exit;
