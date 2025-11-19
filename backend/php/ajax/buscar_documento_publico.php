<?php
require '../../db/conexion.php';

$dni_ruc = $_GET['dni_ruc'] ?? '';
$expediente = $_GET['expediente'] ?? '';

if ($dni_ruc == '' || $expediente == '') {
    echo json_encode([]);
    exit;
}

$sql = "
    SELECT 
        d.IdDocumentos,
        d.NumeroDocumento,
        d.Asunto,
        d.Finalizado,
        d.IdEstadoDocumento,

        md.AreaOrigen,
        md.AreaDestino,
        md.FechaMovimiento,
        md.NumeroFolios,
        md.Recibido,
        md.Observacion,

        ao.Nombre AS OrigenNombre,
        ad.Nombre AS DestinoNombre,
        inf.NombreInforme AS InformeNombre

    FROM documentos d
    INNER JOIN movimientodocumento md ON md.IdDocumentos = d.IdDocumentos
    LEFT JOIN areas ao ON md.AreaOrigen = ao.IdAreas
    LEFT JOIN areas ad ON md.AreaDestino = ad.IdAreas
    LEFT JOIN informes inf ON md.IdInforme = inf.IdInforme

    WHERE d.DniRuc = :dni_ruc
      AND d.NumeroDocumento = :expediente

    ORDER BY md.FechaMovimiento ASC
";

$stmt = $pdo->prepare($sql);
$stmt->bindParam(':dni_ruc', $dni_ruc);
$stmt->bindParam(':expediente', $expediente);
$stmt->execute();

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
