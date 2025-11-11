<?php
require '../../db/conexion.php';

header('Content-Type: application/json');

$id = $_GET['id_documentos'] ?? null;
if (!$id) {
    echo json_encode(['error' => 'ID de documento no proporcionado']);
    exit;
}

try {
    // 🔹 1. Obtener movimientos del documento
    $sql = "SELECT 
            md.IdMovimientoDocumento, 
            md.AreaOrigen, 
            md.AreaDestino, 
            md.FechaMovimiento, 
            md.NumeroFolios, 
            md.IdInforme, 
            md.Observacion, 
            md.Recibido,
            ao.Nombre AS OrigenNombre,
            ad.Nombre AS DestinoNombre,
            af.NombreInforme AS InformeNombre
        FROM movimientodocumento md
        LEFT JOIN informes af ON md.IdInforme = af.IdInforme
        LEFT JOIN areas ao ON md.AreaOrigen = ao.IdAreas
        LEFT JOIN areas ad ON md.AreaDestino = ad.IdAreas
        WHERE md.IdDocumentos = ?
        ORDER BY md.FechaMovimiento ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 🔹 2. Obtener info del documento (Finalizado e IdEstadoDocumento)
    $sqlFinalizado = "SELECT Finalizado, IdEstadoDocumento FROM documentos WHERE IdDocumentos = ?";
    $stmtFinal = $pdo->prepare($sqlFinalizado);
    $stmtFinal->execute([$id]);
    $docInfo = $stmtFinal->fetch(PDO::FETCH_ASSOC);

    $finalizado = $docInfo['Finalizado'] ?? 0;
    $idEstado = $docInfo['IdEstadoDocumento'] ?? null;

    // 🔹 3. Enviar todo al frontend
    echo json_encode([
        'success' => true,
        'id_documento' => $id,
        'Finalizado' => (int)$finalizado,
        'IdEstadoDocumento' => (int)$idEstado,
        'movimientos' => $movimientos
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Error al obtener datos: ' . $e->getMessage()]);
}
