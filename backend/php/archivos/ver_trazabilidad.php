<?php
require '../../db/conexion.php';

$id = $_GET['id_documentos'] ?? null;
if (!$id) {
    echo "ID de documento no proporcionado.";
    exit;
}

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
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <title>Seguimiento del Documento</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f9f9f9;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 20px;
            background: white;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #f3f3f3;
        }

        .recibido {
            color: green;
            font-weight: bold;
        }

        .pendiente {
            color: orange;
            font-weight: bold;
        }

        h2 {
            margin-bottom: 0;
        }
    </style>
</head>

<body>
    <h2>📋 Trazabilidad del Documento N° <?= htmlspecialchars($id) ?></h2>

    <?php if (count($movimientos) === 0): ?>
        <p>No se encontraron movimientos para este documento.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Área Origen</th>
                    <th>Área Destino</th>
                    <th>Fecha de Movimiento</th>
                    <th>Estado de Recepción</th>
                    <th>Observación</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($movimientos as $mov): ?>
                    <tr>
                        <td><?= htmlspecialchars($mov['OrigenNombre'] ?? $mov['AreaOrigen']) ?></td>
                        <td><?= htmlspecialchars($mov['DestinoNombre'] ?? $mov['AreaDestino']) ?></td>
                        <td><?= $mov['FechaMovimiento'] ?></td>
                        <td class="<?= $mov['Recibido'] == 1 ? 'recibido' : 'pendiente' ?>">
                            <?= $mov['Recibido'] == 1 ? '✅ Recibido' : '⏳ Pendiente' ?>
                        </td>
                        <td><?= nl2br(htmlspecialchars($mov['Observacion'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>

</html>