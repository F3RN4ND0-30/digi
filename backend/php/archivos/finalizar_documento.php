<?php
session_start();
if (!isset($_SESSION['dg_id'])) {
    header("Location: ../../../frontend/login.php");
    exit();
}

require '../../db/conexion.php';

$documento_id = $_POST['id_documento'] ?? null;
$area_usuario = $_SESSION['dg_area_id'] ?? null;

if (!$documento_id || !$area_usuario) {
    die("❌ Datos inválidos.");
}

// Verificar si el área del usuario es el área final del documento
$consulta = $pdo->prepare("SELECT IdAreaFinal, Finalizado FROM documentos WHERE IdDocumentos = ?");
$consulta->execute([$documento_id]);
$documento = $consulta->fetch(PDO::FETCH_ASSOC);

if (!$documento) {
    die("❌ Documento no encontrado.");
}

if ((int)$documento['IdAreaFinal'] !== (int)$area_usuario) {
    die("❌ No tienes permiso para finalizar este documento. Área usuario: $area_usuario");
}

if ($documento['Finalizado']) {
    die("❌ El documento ya está finalizado.");
}

// Actualizar el campo Finalizado
$stmt = $pdo->prepare("UPDATE documentos SET Finalizado = 1, IdEstadoDocumento = 1 WHERE IdDocumentos = ?");
$stmt->execute([$documento_id]);

header("Location: ../../../frontend/archivos/reenviar.php?msg=Documento finalizado correctamente");
exit;
