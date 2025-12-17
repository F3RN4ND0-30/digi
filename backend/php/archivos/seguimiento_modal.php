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
            md.IdDocumentos,
            md.AreaOrigen, 
            md.AreaDestino, 
            md.FechaMovimiento, 
            md.NumeroFolios, 
            md.IdInforme,
            md.IdCarta, 
            md.Observacion, 
            md.Recibido,
            ao.Nombre AS OrigenNombre,
            ad.Nombre AS DestinoNombre,
            inf.NombreInforme AS InformeNombre,
            car.NombreCarta AS CartaNombre,
            doc.NumeroDocumento AS NumeroDocumento
        FROM movimientodocumento md
        LEFT JOIN cartas car ON md.IdCarta = car.IdCarta
        LEFT JOIN informes inf ON md.IdInforme = inf.IdInforme
        LEFT JOIN areas ao ON md.AreaOrigen = ao.IdAreas
        LEFT JOIN areas ad ON md.AreaDestino = ad.IdAreas
        LEFT JOIN documentos doc ON md.IdDocumentos = doc.IdDocumentos
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
