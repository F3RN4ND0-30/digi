<?php
// exportar_excel.php
session_start();
if (!isset($_SESSION['dg_id'])) {
    header("Location: ../login.php");
    exit();
}

require '../../backend/db/conexion.php';
require '../../vendor/autoload.php'; // PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

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

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Título centrado
$sheet->mergeCells('A1:H1');
$sheet->setCellValue('A1', 'REPORTES - DOCUMENTOS EXTERNOS');
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

// Subtítulo
$sheet->mergeCells('A2:H2');
$sheet->setCellValue('A2', 'Municipalidad Provincial de Pisco');
$sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A2')->getFont()->setSize(12);

$sheet->mergeCells('A3:H3');
$sheet->setCellValue('A3', 'Generado: '.date("d/m/Y H:i:s"));
$sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A3')->getFont()->setSize(10);

// Encabezado de la tabla
$headers = ['Código','Fecha','Hora','Razón Social','Asunto','Área','Para','Folios'];
$sheet->fromArray($headers, null, 'A5');

// Estilo encabezado
$sheet->getStyle('A5:H5')->getFont()->setBold(true);
$sheet->getStyle('A5:H5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9D9D9');
$sheet->getStyle('A5:H5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Llenar datos
$rowNum = 6;
foreach ($documentos as $doc) {
    $sheet->setCellValue('A'.$rowNum, $doc['NumeroDocumento']);
    $sheet->setCellValue('B'.$rowNum, $doc['Fecha']);
    $sheet->setCellValue('C'.$rowNum, $doc['Hora']);
    $sheet->setCellValue('D'.$rowNum, $doc['NombreContribuyente']);
    $sheet->setCellValue('E'.$rowNum, $doc['Asunto']);
    $sheet->setCellValue('F'.$rowNum, $doc['AreaOrigen']);
    $sheet->setCellValue('G'.$rowNum, $doc['AreaDestino']);
    $sheet->setCellValue('H'.$rowNum, $doc['NumeroFolios']);
    $rowNum++;
}

// Ajustar ancho de columnas
foreach (range('A','H') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Descargar archivo
$filename = "reportes_" . date("Y-m-d_H-i") . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="'.$filename.'"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
