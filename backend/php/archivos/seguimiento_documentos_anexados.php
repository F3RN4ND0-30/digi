<?php
require '../../db/conexion.php';

$data = json_decode(file_get_contents("php://input"), true);
$idDocumentoAnexado = $data['IdDocumentos'] ?? null;

if (!$idDocumentoAnexado) {
    echo "<p class='text-danger'>Documento no válido.</p>";
    exit;
}

/*
============================================================
1️⃣ OBTENER DOCUMENTO ORIGEN DEL ANEXO
============================================================
*/
$sqlOrigen = $pdo->prepare("
    SELECT 
        d.IdDocumentos,
        d.NumeroDocumento,
        d.Asunto
    FROM documento_relacion dr
    INNER JOIN documentos d 
        ON d.IdDocumentos = dr.IdDocumentoOrigen
    WHERE dr.IdDocumentoDestino = ?
    LIMIT 1
");
$sqlOrigen->execute([$idDocumentoAnexado]);
$documentoOrigen = $sqlOrigen->fetch(PDO::FETCH_ASSOC);

/*
============================================================
2️⃣ OBTENER TODOS LOS ANEXOS DEL DOCUMENTO ORIGEN
============================================================
*/
$documentos = [];

if ($documentoOrigen) {
    $documentos[] = [
        'tipo' => 'origen',
        ...$documentoOrigen
    ];

    // Obtener todos los anexos
    $sqlAnexos = $pdo->prepare("
        SELECT d.IdDocumentos, d.NumeroDocumento, d.Asunto
        FROM documento_relacion dr
        INNER JOIN documentos d ON d.IdDocumentos = dr.IdDocumentoDestino
        WHERE dr.IdDocumentoOrigen = ?
        ORDER BY dr.FechaRelacion ASC
    ");
    $sqlAnexos->execute([$documentoOrigen['IdDocumentos']]);
    $anexos = $sqlAnexos->fetchAll(PDO::FETCH_ASSOC);

    foreach ($anexos as $a) {
        $documentos[] = [
            'tipo' => 'anexo',
            ...$a
        ];
    }
} else {
    // Si no tiene origen, mostrar solo el documento seleccionado
    $sqlDoc = $pdo->prepare("
        SELECT IdDocumentos, NumeroDocumento, Asunto
        FROM documentos
        WHERE IdDocumentos = ?
    ");
    $sqlDoc->execute([$idDocumentoAnexado]);
    $doc = $sqlDoc->fetch(PDO::FETCH_ASSOC);
    if ($doc) {
        $documentos[] = [
            'tipo' => 'anexo',
            ...$doc
        ];
    }
}

/*
============================================================
3️⃣ MOSTRAR HISTORIAL DE CADA DOCUMENTO
============================================================
*/
foreach ($documentos as $doc) {

    $icono = $doc['tipo'] === 'origen'
        ? '📄 Documento Origen'
        : '📎 Documento Anexado';

    // Caja para el título
    echo "<div class='documento-titulo p-2 mb-2'>";
    echo "<strong>{$icono}: {$doc['NumeroDocumento']}</strong>";
    echo "</div>";

    // Caja para el asunto
    echo "<div class='documento-asunto p-2 mb-3'>";
    echo "<strong>Asunto:</strong> {$doc['Asunto']}";
    echo "</div>";

    $sqlMov = $pdo->prepare("
        SELECT 
            ao.Nombre AS AreaOrigenNombre,
            ad.Nombre AS AreaDestinoNombre,
            md.FechaMovimiento,
            md.NumeroFolios,
            md.Observacion,
            md.Recibido
        FROM movimientodocumento md
        LEFT JOIN areas ao ON ao.IdAreas = md.AreaOrigen
        LEFT JOIN areas ad ON ad.IdAreas = md.AreaDestino
        WHERE md.IdDocumentos = ?
        ORDER BY md.FechaMovimiento ASC
    ");
    $sqlMov->execute([$doc['IdDocumentos']]);
    $movimientos = $sqlMov->fetchAll(PDO::FETCH_ASSOC);

    if (!$movimientos) {
        echo "<p class='text-muted'>Sin movimientos registrados.</p>";
        continue;
    }

    echo "
        <table class='table table-sm table-bordered'>
            <thead>
                <tr>
                    <th>Área Origen</th>
                    <th>Área Destino</th>
                    <th>Fecha</th>
                    <th>Folios</th>
                    <th>Observación</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
    ";

    foreach ($movimientos as $m) {

        $estado = $m['Recibido'] == 1
            ? "<span class='badge bg-success'>Recibido</span>"
            : "<span class='badge bg-warning text-dark'>Pendiente</span>";

        $areaOrigen  = $m['AreaOrigenNombre'] ?? '—';
        $areaDestino = $m['AreaDestinoNombre'] ?? '—';

        echo "
            <tr>
                <td>{$areaOrigen}</td>
                <td>{$areaDestino}</td>
                <td>{$m['FechaMovimiento']}</td>
                <td>{$m['NumeroFolios']}</td>
                <td>{$m['Observacion']}</td>
                <td>{$estado}</td>
            </tr>
        ";
    }

    echo "</tbody></table><hr>";
}
