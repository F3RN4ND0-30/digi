<?php
session_start();
if (!isset($_SESSION['dg_id'])) {
    header("Location: ../../../frontend/login.php");
    exit();
}

require '../../db/conexion.php';

// Recibir datos del formulario
$documento_id = $_POST['id_documento'] ?? null;
$numero_folios = $_POST['numero_folios'] ?? null;
$id_informe = $_POST['id_informe'] ?? null;
$area_usuario = $_SESSION['dg_area_id'] ?? null;

if (!$documento_id || !$area_usuario) {
    die("❌ Datos inválidos.");
}

// Convertir folios e informe a enteros si vienen
$numero_folios = $numero_folios ? intval($numero_folios) : null;
$id_informe = $id_informe ? intval($id_informe) : null;

try {
    // Obtener documento
    $stmt = $pdo->prepare("SELECT IdAreaFinal, Finalizado, NumeroFolios FROM documentos WHERE IdDocumentos = ?");
    $stmt->execute([$documento_id]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doc) {
        throw new Exception("❌ Documento no encontrado.");
    }

    // Verificar permiso de área
    if ((int)$doc['IdAreaFinal'] !== (int)$area_usuario) {
        throw new Exception("❌ No tienes permiso para finalizar este documento. Área usuario: $area_usuario");
    }

    // Verificar si ya está finalizado
    if ($doc['Finalizado']) {
        throw new Exception("❌ El documento ya está finalizado.");
    }

    // Si no viene número de folios desde el form, usar el del documento
    if (!$numero_folios) {
        $numero_folios = $doc['NumeroFolios'];
    }

    // Actualizar documento como finalizado
    $stmtUpdate = $pdo->prepare("UPDATE documentos SET Finalizado = 1, IdEstadoDocumento = 7, NumeroFolios = ? WHERE IdDocumentos = ?");
    $stmtUpdate->execute([$numero_folios, $documento_id]);

    // Insertar movimiento final en movimientodocumento
    $stmtInsert = $pdo->prepare("
        INSERT INTO movimientodocumento 
        (IdDocumentos, AreaOrigen, AreaDestino, FechaMovimiento, Observacion, Recibido, NumeroFolios, IdInforme)
        VALUES (?, ?, ?, NOW(), ?, 1, ?, ?)
    ");

    $observacion = "Documento finalizado";
    $stmtInsert->execute([$documento_id, $area_usuario, $area_usuario, $observacion, $numero_folios, $id_informe]);

    header("Location: ../../../frontend/archivos/reenviar.php?msg=Documento finalizado correctamente");
    exit();
} catch (Exception $e) {
    die("❌ Error al finalizar el documento: " . $e->getMessage());
}
