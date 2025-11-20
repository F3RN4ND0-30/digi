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
$tipo = $_GET['tipo'] ?? 'documentos'; // Tipo de reporte, por defecto 'documentos'

if (empty($area)) {
    die("Área no especificada.");
}

// Asegurarse de que el área sea un número entero (si es necesario)
$area = (int) $area;

$documentos = [];
$memorandums = [];
$headers = [];

if ($tipo == 'documentos') {
    // Obtener documentos
    $sql = "SELECT d.NumeroDocumento, 
                   DATE(d.FechaIngreso) as Fecha, 
                   TIME(d.FechaIngreso) as Hora, 
                   d.NombreContribuyente, 
                   d.Asunto,
                   a.Nombre AS AreaOrigen, 
                   ad.Nombre AS AreaDestino, 
                   d.NumeroFolios
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

    // Definir encabezados para documentos
    $headers = ['Código', 'Fecha', 'Hora', 'Razón Social', 'Asunto', 'Área', 'Para', 'Folios'];
} elseif ($tipo == 'memorandums') {
    // Obtener memorándums con los nombres completos del área y usuario emisor
    $sql = "SELECT m.CodigoMemo, 
                   DATE(m.FechaEmision) as FechaEmision, 
                   m.Año, 
                   a.Nombre AS AreaOrigen,  -- Nombre del área
                   m.TipoMemo, 
                   m.Asunto, 
                   CONCAT(u.Nombres, ' ', u.ApellidoPat, ' ', u.ApellidoMat) AS UsuarioEmisor,  -- Nombre completo del usuario emisor
                   m.NumeroFolios
            FROM memorandums m
            LEFT JOIN areas a ON m.IdAreaOrigen = a.IdAreas
            LEFT JOIN usuarios u ON m.IdUsuarioEmisor = u.IdUsuarios
            WHERE m.IdAreaOrigen = ?
            ORDER BY m.FechaEmision DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$area]);
    $memorandums = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Definir encabezados para memorándums
    $headers = ['Código Memo', 'Fecha Emisión', 'Año', 'Área Origen', 'Tipo Memo', 'Asunto', 'Usuario Emisor', 'Folios'];
}

// Verificación de resultados
if (empty($documentos) && empty($memorandums)) {
    die("No se encontraron datos para el área seleccionada.");
}

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
    <h2>REPORTES - ' . strtoupper($tipo) . '</h2>
    <h3>Municipalidad Provincial de Pisco</h3>
    <h4>Generado: ' . date("d/m/Y H:i:s") . '</h4>
    <table>
        <thead>
            <tr>';

// Encabezados dinámicos según el tipo de reporte
foreach ($headers as $header) {
    $html .= '<th>' . $header . '</th>';
}

$html .= '</tr>
        </thead>
        <tbody>';

if ($tipo == 'documentos') {
    // Mostrar datos de documentos
    foreach ($documentos as $doc) {
        $html .= '<tr>
            <td>' . $doc['NumeroDocumento'] . '</td>
            <td>' . $doc['Fecha'] . '</td>
            <td>' . $doc['Hora'] . '</td>
            <td>' . $doc['NombreContribuyente'] . '</td>
            <td>' . $doc['Asunto'] . '</td>
            <td>' . $doc['AreaOrigen'] . '</td>
            <td>' . $doc['AreaDestino'] . '</td>
            <td>' . $doc['NumeroFolios'] . '</td>
        </tr>';
    }
} elseif ($tipo == 'memorandums') {
    // Mostrar datos de memorándums
    foreach ($memorandums as $mem) {
        $html .= '<tr>
            <td>' . $mem['CodigoMemo'] . '</td>
            <td>' . $mem['FechaEmision'] . '</td>
            <td>' . $mem['Año'] . '</td>
            <td>' . $mem['AreaOrigen'] . '</td>
            <td>' . $mem['TipoMemo'] . '</td>
            <td>' . $mem['Asunto'] . '</td>
            <td>' . $mem['UsuarioEmisor'] . '</td>
            <td>' . $mem['NumeroFolios'] . '</td>
        </tr>';
    }
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

$filename = "reportes_" . strtoupper($tipo) . "_" . date("Y-m-d_H-i") . ".pdf";
$dompdf->stream($filename, ["Attachment" => true]);
exit;
