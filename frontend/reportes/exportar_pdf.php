<?php

session_start();
if (!isset($_SESSION['dg_id'])) {
    header("Location: ../login.php");
    exit();
}

require '../../backend/db/conexion.php';
require '../../vendor/autoload.php'; // Dompdf

use Dompdf\Dompdf;
use Dompdf\Options;

$area = $_GET['area'] ?? '';
if ($area == '') {
    die("Área no especificada.");
}

// Obtener documentos
$sql = "SELECT d.NumeroDocumento, DATE(d.FechaIngreso) as Fecha, TIME(d.FechaIngreso) as Hora, d.NombreContribuyente, d.Asunto,
        a.Nombre AS AreaOrigen, ad.Nombre AS AreaDestino, d.NumeroFolios
        FROM documentos d
        LEFT JOIN areas a ON d.IdAreas = a.IdAreas
        LEFT JOIN areas ad ON (
            SELECT md.AreaDestino
            FROM movimientodocumento md
            WHERE md.IdDocumentos = d.IdDocumentos
            ORDER BY md.IdMovimientoDocumento DESC LIMIT 1
        ) = ad.IdAreas
        WHERE d.IdAreas = ?
        ORDER BY d.FechaIngreso DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$area]);
$documentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// HTML PDF

date_default_timezone_set('America/Lima');

$html = '
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        h2, h3, h4 { text-align: center; margin: 0; }
        table { border-collapse: collapse; width: 100%; margin-top: 15px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: center; }
        th {
            background-color: #1976D2; /* Azul */
            color: #fff; /* Letras blancas */
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h2>REPORTES - DOCUMENTOS EXTERNOS</h2>
    <h3>Municipalidad Provincial de Pisco</h3>
    <h4>Generado: '.date("d/m/Y H:i:s").'</h4>
    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Fecha</th>
                <th>Hora</th>
                <th>Razón Social</th>
                <th>Asunto</th>
                <th>Área</th>
                <th>Para</th>
                <th>Folios</th>
            </tr>
        </thead>
        <tbody>';

foreach ($documentos as $doc) {
    $html .= '<tr>
        <td>'.$doc['NumeroDocumento'].'</td>
        <td>'.$doc['Fecha'].'</td>
        <td>'.$doc['Hora'].'</td>
        <td>'.$doc['NombreContribuyente'].'</td>
        <td>'.$doc['Asunto'].'</td>
        <td>'.$doc['AreaOrigen'].'</td>
        <td>'.$doc['AreaDestino'].'</td>
        <td>'.$doc['NumeroFolios'].'</td>
    </tr>';
}

$html .= '
        </tbody>
    </table>
</body>
</html>';

// Configuración Dompdf
$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$filename = "reportes_" . date("Y-m-d_H-i") . ".pdf";
$dompdf->stream($filename, ["Attachment" => true]);
exit;
