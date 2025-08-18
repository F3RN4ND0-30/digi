<?php
session_start();
require '../../db/conexion.php';

$area_id = $_SESSION['dg_area_id'] ?? null;

if (!$area_id) {
    exit("❌ Área de sesión no válida.");
}

$sql = "SELECT 
            m.IdMovimientoDocumento,
            d.NumeroDocumento,
            d.Asunto,
            e.Estado,
            a_origen.Nombre AS AreaOrigen,
            m.Observacion,
            m.FechaMovimiento
        FROM movimientodocumento m
        INNER JOIN documentos d ON m.IdDocumentos = d.IdDocumentos
        INNER JOIN estadodocumento e ON d.IdEstadoDocumento = e.IdEstadoDocumento
        INNER JOIN areas a_origen ON m.AreaOrigen = a_origen.IdAreas
        WHERE m.AreaDestino = ? AND m.Recibido = 0
        ORDER BY m.FechaMovimiento DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$area_id]);
$docs = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($docs as $doc) {
    echo "<tr>
            <td>" . htmlspecialchars($doc['NumeroDocumento']) . "</td>
            <td>" . htmlspecialchars($doc['Asunto']) . "</td>
            <td>" . htmlspecialchars($doc['Estado']) . "</td>
            <td>" . htmlspecialchars($doc['AreaOrigen']) . "</td>
            <td>" . date('d/m/Y H:i', strtotime($doc['FechaMovimiento'])) . "</td>
            <td>" . htmlspecialchars($doc['Observacion']) . "</td>
            <td>
                <form method='POST' action='../../backend/php/archivos/recepcion_procesar.php'>
                    <input type='hidden' name='id_movimiento' value='" . $doc['IdMovimientoDocumento'] . "'>
                    <button type='submit'>✅ Recibir</button>
                </form>
            </td>
        </tr>";
}
