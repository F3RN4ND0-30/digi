<?php
require '../../db/conexion.php';

header('Content-Type: application/json');

$id = $_GET['id_documentos'] ?? null;
if (!$id) {
    echo json_encode(['error' => 'ID de documento no proporcionado']);
    exit;
}

try {
    $sql = "SELECT md.IdMovimientoDocumento, md.AreaOrigen, md.AreaDestino, md.FechaMovimiento, md.Observacion, md.Recibido,
                   ao.Nombre AS OrigenNombre,
                   ad.Nombre AS DestinoNombre
            FROM movimientodocumento md
            LEFT JOIN areas ao ON md.AreaOrigen = ao.IdAreas
            LEFT JOIN areas ad ON md.AreaDestino = ad.IdAreas
            WHERE md.IdDocumentos = ?
            ORDER BY md.FechaMovimiento ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'id_documento' => $id,
        'movimientos' => $movimientos
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => 'Error al obtener datos: ' . $e->getMessage()]);
}
?>