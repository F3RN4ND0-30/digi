<?php

/**
 * Backend para exportación de reportes de supervisión
 * exportar-supervision.php - VERSIÓN COMPACTA FINAL
 */

// Verificar que sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Verificar autenticación de sesión
session_start();
if (!isset($_SESSION['dg_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Verificar rol (solo defensoría y administradores)
if (($_SESSION['dg_rol'] ?? 999) != 1 && ($_SESSION['dg_rol'] ?? 999) != 4) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado']);
    exit;
}

// Buscar y cargar Composer autoloader
$autoloadPaths = [
    __DIR__ . '/../../../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/vendor/autoload.php',
    dirname(__DIR__, 3) . '/vendor/autoload.php',
    dirname(__DIR__, 2) . '/vendor/autoload.php'
];

$autoloadLoaded = false;
foreach ($autoloadPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $autoloadLoaded = true;
        break;
    }
}

try {
    // Obtener datos del request
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['formato'])) {
        throw new Exception('Datos inválidos recibidos');
    }

    $formato = $input['formato'];
    $datos = $input['datos'] ?? [];
    $stats = $input['stats'] ?? [];

    if (empty($datos)) {
        throw new Exception('No hay datos para exportar');
    }

    // Generar archivo según formato
    switch ($formato) {
        case 'excel':
            generarExcelReal($datos, $stats, $autoloadLoaded);
            break;
        case 'pdf':
            generarPDFReal($datos, $stats, $autoloadLoaded);
            break;
        case 'csv':
            generarCSV($datos, $stats);
            break;
        default:
            throw new Exception('Formato no soportado: ' . $formato);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

/**
 * Generar Excel usando PhpSpreadsheet
 */
function generarExcelReal($datos, $stats, $autoloadLoaded)
{
    if (!$autoloadLoaded || !class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
        generarCSV($datos, $stats);
        return;
    }

    try {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Supervisión Documentos');

        // Configurar anchos de columna optimizados
        $sheet->getColumnDimension('A')->setWidth(6);   // N°
        $sheet->getColumnDimension('B')->setWidth(20);  // Documento
        $sheet->getColumnDimension('C')->setWidth(45);  // Asunto
        $sheet->getColumnDimension('D')->setWidth(15);  // Estado
        $sheet->getColumnDimension('E')->setWidth(12);  // Fecha
        $sheet->getColumnDimension('F')->setWidth(25);  // Área
        $sheet->getColumnDimension('G')->setWidth(15);  // Días
        $sheet->getColumnDimension('H')->setWidth(30);  // Observación
        $sheet->getColumnDimension('I')->setWidth(20);  // Recibido

        // TÍTULO PRINCIPAL
        $sheet->mergeCells('A1:I1');
        $sheet->setCellValue('A1', 'REPORTE DE SUPERVISIÓN - DOCUMENTOS EXTERNOS');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => '4472C4']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ]
        ]);
        $sheet->getRowDimension(1)->setRowHeight(25);

        // SUBTÍTULO
        $sheet->mergeCells('A2:I2');
        $sheet->setCellValue('A2', 'Defensoría del Pueblo - Municipalidad Provincial de Pisco');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
                'color' => ['rgb' => '333333']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
            ]
        ]);

        // FECHA
        $sheet->mergeCells('A3:I3');
        $sheet->setCellValue('A3', 'Generado: ' . date('d/m/Y H:i:s'));
        $sheet->getStyle('A3')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
        ]);

        // ESTADÍSTICAS
        $sheet->setCellValue('A5', 'ESTADÍSTICAS:');
        $sheet->getStyle('A5')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '4472C4']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => ['rgb' => 'E8F4FD']
            ]
        ]);

        $sheet->setCellValue('A6', 'Total de documentos:');
        $sheet->setCellValue('B6', $stats['total']);
        $sheet->setCellValue('A7', 'En tiempo (1-3 días):');
        $sheet->setCellValue('B7', $stats['enTiempo']);
        $sheet->setCellValue('A8', 'Requieren atención (4-6 días):');
        $sheet->setCellValue('B8', $stats['atencion']);
        $sheet->setCellValue('A9', 'Urgentes (7+ días):');
        $sheet->setCellValue('B9', $stats['urgentes']);

        // Estilos para estadísticas
        $sheet->getStyle('A6:B9')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => ['rgb' => 'F8F9FA']
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '4472C4']
                ]
            ]
        ]);

        // ENCABEZADOS DE TABLA
        $headers = ['N°', 'Documento', 'Asunto', 'Estado', 'Fecha', 'Área', 'Días', 'Observación', 'Recibido'];
        $col = 'A';
        $row = 11;

        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $col++;
        }

        // Estilo para encabezados
        $sheet->getStyle('A11:I11')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => ['rgb' => '4472C4']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '2C5AA0']
                ]
            ]
        ]);
        $sheet->getRowDimension(11)->setRowHeight(25);

        // DATOS
        $row = 12;
        foreach ($datos as $item) {
            $sheet->setCellValue('A' . $row, $item['numero']);
            $sheet->setCellValue('B' . $row, $item['documento']);
            $sheet->setCellValue('C' . $row, $item['asunto']);
            $sheet->setCellValue('D' . $row, $item['estado']);
            $sheet->setCellValue('E' . $row, $item['fecha']);
            $sheet->setCellValue('F' . $row, $item['area']);
            $sheet->setCellValue('G' . $row, $item['dias']);
            $sheet->setCellValue('H' . $row, $item['observacion']);
            $sheet->setCellValue('I' . $row, $item['recibido']);

            // Estilos para datos
            $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray([
                'alignment' => [
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    'wrapText' => true
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => 'DDDDDD']
                    ]
                ]
            ]);

            // Centrar columnas específicas
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('D' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('G' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            $sheet->getRowDimension($row)->setRowHeight(30);

            // Color alternado
            if ($row % 2 == 0) {
                $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'color' => ['rgb' => 'F8F9FA']
                    ]
                ]);
            }

            $row++;
        }

        // Enviar archivo
        $fecha = date('Y-m-d');
        $hora = date('H-i');
        $filename = "supervision_documentos_{$fecha}_{$hora}.xlsx";

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Pragma: public');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    } catch (Exception $e) {
        generarCSV($datos, $stats);
    }
}

/**
 * Generar PDF COMPACTO - Solo columnas esenciales
 */
function generarPDFReal($datos, $stats, $autoloadLoaded)
{
    if (!$autoloadLoaded || !class_exists('TCPDF')) {
        generarCSV($datos, $stats);
        return;
    }

    try {
        $pdf = new \TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Configurar documento
        $pdf->SetCreator('DIGI - Sistema de Supervisión');
        $pdf->SetAuthor('Defensoría del Pueblo');
        $pdf->SetTitle('Reporte de Supervisión - Documentos Externos');

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(8, 15, 8); // Márgenes mínimos
        $pdf->SetAutoPageBreak(TRUE, 15);

        $pdf->AddPage();

        // Título
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->SetTextColor(68, 114, 196);
        $pdf->Cell(0, 6, 'REPORTE DE SUPERVISIÓN - DOCUMENTOS EXTERNOS', 0, 1, 'C');

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 5, 'Defensoría del Pueblo - Municipalidad Provincial de Pisco', 0, 1, 'C');

        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell(0, 5, 'Generado: ' . date('d/m/Y H:i:s'), 0, 1, 'C');
        $pdf->Ln(5);

        // Estadísticas en una línea
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetTextColor(68, 114, 196);
        $pdf->Cell(0, 5, "ESTADÍSTICAS: Total: {$stats['total']} | En tiempo: {$stats['enTiempo']} | Atención: {$stats['atencion']} | Urgentes: {$stats['urgentes']}", 0, 1, 'L');
        $pdf->Ln(5);

        // Tabla COMPACTA - Solo 6 columnas esenciales
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetFillColor(68, 114, 196);
        $pdf->SetTextColor(255, 255, 255);

        // Headers compactos - Total: 265 puntos
        $pdf->Cell(15, 7, 'N°', 1, 0, 'C', 1);
        $pdf->Cell(45, 7, 'Documento', 1, 0, 'C', 1);
        $pdf->Cell(90, 7, 'Asunto', 1, 0, 'C', 1);
        $pdf->Cell(30, 7, 'Estado', 1, 0, 'C', 1);
        $pdf->Cell(25, 7, 'Fecha', 1, 0, 'C', 1);
        $pdf->Cell(30, 7, 'Días', 1, 0, 'C', 1);
        $pdf->Cell(30, 7, 'Área', 1, 1, 'C', 1);

        // Datos
        $pdf->SetFont('helvetica', '', 6); // Fuente más pequeña
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor(248, 249, 250);

        foreach ($datos as $index => $item) {
            // Verificar si necesita nueva página
            if ($pdf->GetY() > 180) {
                $pdf->AddPage();

                // Repetir headers
                $pdf->SetFont('helvetica', 'B', 8);
                $pdf->SetFillColor(68, 114, 196);
                $pdf->SetTextColor(255, 255, 255);

                $pdf->Cell(15, 7, 'N°', 1, 0, 'C', 1);
                $pdf->Cell(45, 7, 'Documento', 1, 0, 'C', 1);
                $pdf->Cell(90, 7, 'Asunto', 1, 0, 'C', 1);
                $pdf->Cell(30, 7, 'Estado', 1, 0, 'C', 1);
                $pdf->Cell(25, 7, 'Fecha', 1, 0, 'C', 1);
                $pdf->Cell(30, 7, 'Días', 1, 0, 'C', 1);
                $pdf->Cell(30, 7, 'Área', 1, 1, 'C', 1);

                $pdf->SetFont('helvetica', '', 6);
                $pdf->SetTextColor(0, 0, 0);
            }

            $fill = ($index % 2 == 0) ? 1 : 0;

            // Extraer solo el número de días para mostrar de forma compacta
            $diasTexto = $item['dias'];
            preg_match('/(\d+)/', $diasTexto, $matches);
            $soloNumero = isset($matches[1]) ? $matches[1] . 'd' : '0d';

            // Celdas de datos - mismas dimensiones que headers
            $pdf->Cell(15, 6, $item['numero'], 1, 0, 'C', $fill);
            $pdf->Cell(45, 6, substr($item['documento'], 0, 22), 1, 0, 'L', $fill);
            $pdf->Cell(90, 6, substr($item['asunto'], 0, 55), 1, 0, 'L', $fill);
            $pdf->Cell(30, 6, substr($item['estado'], 0, 12), 1, 0, 'C', $fill);
            $pdf->Cell(25, 6, $item['fecha'], 1, 0, 'C', $fill);
            $pdf->Cell(30, 6, $soloNumero, 1, 0, 'C', $fill); // Solo número + 'd'
            $pdf->Cell(30, 6, substr($item['area'], 0, 15), 1, 1, 'L', $fill);
        }

        // Enviar archivo
        $fecha = date('Y-m-d');
        $hora = date('H-i');
        $filename = "supervision_documentos_{$fecha}_{$hora}.pdf";

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Pragma: public');

        $pdf->Output($filename, 'D');
        exit;
    } catch (Exception $e) {
        generarCSV($datos, $stats);
    }
}

/**
 * Generar CSV
 */
function generarCSV($datos, $stats)
{
    $fecha = date('Y-m-d');
    $hora = date('H-i');
    $filename = "supervision_documentos_{$fecha}_{$hora}.csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    header('Pragma: public');

    $output = fopen('php://output', 'w');

    // BOM para UTF-8
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Headers
    fputcsv($output, [
        'N°',
        'Documento',
        'Asunto',
        'Estado',
        'Fecha',
        'Área',
        'Días',
        'Observación',
        'Recibido'
    ]);

    // Datos
    foreach ($datos as $item) {
        fputcsv($output, [
            $item['numero'],
            $item['documento'],
            $item['asunto'],
            $item['estado'],
            $item['fecha'],
            $item['area'],
            $item['dias'],
            $item['observacion'],
            $item['recibido']
        ]);
    }

    fclose($output);
    exit;
}
