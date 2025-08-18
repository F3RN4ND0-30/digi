<?php
session_start();
require '../../db/conexion.php';

$area_id = $_SESSION['dg_area_id'] ?? null;

if (!$area_id) {
    exit("❌ Área no definida.");
}

$sql = "SELECT m.IdMovimientoDocumento, d.NumeroDocumento, d.Asunto, e.Estado, a.Nombre AS AreaDestino, m.Recibido, m.Observacion
        FROM movimientodocumento m
        INNER JOIN documentos d ON m.IdDocumentos = d.IdDocumentos
        INNER JOIN estadodocumento e ON d.IdEstadoDocumento = e.IdEstadoDocumento
        INNER JOIN areas a ON m.AreaDestino = a.IdAreas
        WHERE m.AreaOrigen = ?
        ORDER BY m.IdMovimientoDocumento DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$area_id]);
$documentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($documentos as $doc) {
    $estadoRecepcion = $doc['Recibido'] ? "✅ Recibido" : "⌛ Pendiente";

    echo "<tr>
            <td>" . htmlspecialchars($doc['NumeroDocumento']) . "</td>
            <td>" . htmlspecialchars($doc['Asunto']) . "</td>
            <td>" . htmlspecialchars($doc['Estado']) . "</td>
            <td>" . htmlspecialchars($doc['AreaDestino']) . "</td>
            <td>" . htmlspecialchars($doc['Observacion']) . "</td>
            <td>" . htmlspecialchars($estadoRecepcion) . "</td>
        </tr>";
}
