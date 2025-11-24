<?php

session_start();
if (!isset($_SESSION['dg_id'])) {
    header("Location: ../login.php");
    exit();
}

// ✅ Establecer zona horaria de Lima
date_default_timezone_set('America/Lima');

require '../../backend/db/conexion.php';
require '../../vendor/autoload.php'; // PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;

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

// Crear Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// ================= TITULOS ================= //
$sheet->mergeCells('A1:H1');
$sheet->setCellValue('A1', 'REPORTES - ' . strtoupper($tipo)); // Título dinámico dependiendo del tipo de reporte
$sheet->mergeCells('A2:H2');
$sheet->setCellValue('A2', 'Municipalidad Provincial de Pisco');
$sheet->mergeCells('A3:H3');
$sheet->setCellValue('A3', 'Generado: ' . date("d/m/Y H:i:s"));

// Estilos generales de los 3 títulos (centrado + borde abajo)
$sheet->getStyle('A1:A3')->applyFromArray([
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical'   => Alignment::VERTICAL_CENTER
    ],
    'borders' => [
        'bottom' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ]
]);

// Solo el primer título en azul y negrita
$sheet->getStyle('A1')->applyFromArray([
    'font' => [
        'bold' => true,
        'color' => ['rgb' => '1976D2'],
        'size' => 14
    ]
]);

// ================= ENCABEZADOS DE TABLA ================= //
$sheet->fromArray($headers, NULL, 'A5');

$sheet->getStyle('A5:H5')->applyFromArray([
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '1976D2']
    ],
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF']
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical'   => Alignment::VERTICAL_CENTER
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ]
]);

// ================= CONTENIDO ================= //
// Rellenar filas de memorandums con valores predeterminados para columnas faltantes
if ($tipo == 'documentos' && !empty($documentos)) {
    $sheet->fromArray($documentos, NULL, 'A6');
    $lastRow = 6 + count($documentos) - 1;
} elseif ($tipo == 'memorandums' && !empty($memorandums)) {
    // Agregar valores predeterminados ('---') en las columnas faltantes de memorandums
    foreach ($memorandums as &$mem) {
        // Asegurarse de que todas las filas tengan 8 columnas (completar las faltantes)
        $mem = array_pad($mem, 8, '---');
    }
    $sheet->fromArray($memorandums, NULL, 'A6');
    $lastRow = 6 + count($memorandums) - 1;
} else {
    die("No se encontraron datos para el tipo seleccionado.");
}

$sheet->getStyle("A6:H$lastRow")->applyFromArray([
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical'   => Alignment::VERTICAL_CENTER
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ]
]);

// Ajustar ancho automático
foreach (range('A', 'H') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Descargar
$filename = "reportes_" . strtoupper($tipo) . "_" . date("Y-m-d_H-i") . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
$writer = new Xlsx($spreadsheet);
$writer->save("php://output");
exit;
