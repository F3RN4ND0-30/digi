<?php
// exportar_pdf.php
session_start();
if (!isset($_SESSION['dg_id'])) {
    header("Location: ../login.php");
    exit();
}

require '../../backend/db/conexion.php';
require '../../vendor/autoload.php'; // Dompdf

use Dompdf\Dompdf;

$area = $_GET['area'] ?? '';
if ($area == '') {
    die("Área no especificada.");
}

// Obtener documentos del área seleccionada
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

// Generar HTML
$html = '
<style>
    body { font-family: Arial, sans-serif; font-size: 10pt; }
    h2, p { text-align: center; margin: 0; }
    table { border-collapse: collapse; width: 100%; margin-top: 10px; font-size: 9pt; }
    th, td { border: 1px solid #000; padding: 4px; text-align: center; }
    th { background-color: #d9d9d9; }
</style>

<h2>REPORTES - DOCUMENTOS EXTERNOS</h2>
<p>Municipalidad Provincial de Pisco</p>
<p>Generado: '.date("d/m/Y H:i:s").'</p>

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
        <td>'.htmlspecialchars($doc['NumeroDocumento']).'</td>
        <td>'.htmlspecialchars($doc['Fecha']).'</td>
        <td>'.htmlspecialchars($doc['Hora']).'</td>
        <td>'.htmlspecialchars($doc['NombreContribuyente']).'</td>
        <td>'.htmlspecialchars($doc['Asunto']).'</td>
        <td>'.htmlspecialchars($doc['AreaOrigen']).'</td>
        <td>'.htmlspecialchars($doc['AreaDestino']).'</td>
        <td>'.htmlspecialchars($doc['NumeroFolios']).'</td>
    </tr>';
}

$html .= '</tbody></table>';

// Crear PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait'); // vertical
$dompdf->render();

// Nombre de archivo
$filename = "reportes_" . date("Y-m-d_H-i") . ".pdf";

// Forzar descarga
$dompdf->stream($filename, array("Attachment" => true));
exit;
