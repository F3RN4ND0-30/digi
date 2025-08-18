<?php
session_start();
require '../../db/conexion.php';

header('Content-Type: application/json');

$area_id = $_SESSION['dg_area_id'] ?? null;

if (!$area_id) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT m.IdMovimientoDocumento, m.FechaMovimiento, d.NumeroDocumento, d.Asunto, e.Estado, 
               a.Nombre AS AreaDestino, m.Recibido, m.Observacion
        FROM movimientodocumento m
        INNER JOIN documentos d ON m.IdDocumentos = d.IdDocumentos
        INNER JOIN estadodocumento e ON d.IdEstadoDocumento = e.IdEstadoDocumento
        INNER JOIN areas a ON m.AreaDestino = a.IdAreas
        WHERE m.AreaOrigen = ?
        ORDER BY m.IdMovimientoDocumento DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$area_id]);
$documentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($documentos);
exit;
