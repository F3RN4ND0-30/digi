<?php

/**
 * Backend para exportación de reportes de supervisión
 * exportar-supervision.php - VERSIÓN COMPLETA CON LIBRERÍAS
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

try {
    // Obtener datos del request
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['formato'])) {
        throw new Exception('Datos inválidos');
    }

    $formato = $input['formato'];

    // Obtener datos actualizados de la base de datos
    require '../db/conexion.php';
    $datosReales = obtenerDatosSupervision($pdo);

    // Generar archivo según formato
    switch ($formato) {
        case 'excel':
            generarExcelReal($datosReales);
            break;
        case 'pdf':
            generarPDFReal($datosReales);
            break;
        case 'csv':
            generarCSV($datosReales);
            break;
        default:
            throw new Exception('Formato no soportado');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

/**
 * Obtener datos actualizados de supervisión
 */
function obtenerDatosSupervision($pdo)
{
    $stmt = $pdo->query("
        SELECT 
            d.IdDocumentos,
            d.NumeroDocumento,
            d.Asunto,
            d.FechaIngreso,
            d.IdEstadoDocumento,
            a.Nombre as AreaDestino,
            u.Nombres as NombreUsuario,
            u.ApellidoPat as ApellidoUsuario,
            md.Observacion as UltimaObservacion,
            md.FechaMovimiento as FechaUltimaObservacion,
            CASE 
                WHEN d.IdEstadoDocumento = 1 THEN 'PENDIENTE'
                WHEN d.IdEstadoDocumento = 2 THEN 'EN PROCESO'
                WHEN d.IdEstadoDocumento = 3 THEN 'REVISADO'
                WHEN d.IdEstadoDocumento = 4 THEN 'FINALIZADO'
                ELSE 'SIN ESTADO'
            END as EstadoTexto
        FROM documentos d
        LEFT JOIN areas a ON d.IdAreaFinal = a.IdAreas
        LEFT JOIN usuarios u ON d.IdUsuarios = u.IdUsuarios
        LEFT JOIN (
            SELECT 
                IdDocumentos,
                Observacion,
                FechaMovimiento,
                ROW_NUMBER() OVER (PARTITION BY IdDocumentos ORDER BY FechaMovimiento DESC) as rn
            FROM movimientodocumento 
            WHERE Observacion IS NOT NULL AND Observacion != ''
        ) md ON d.IdDocumentos = md.IdDocumentos AND md.rn = 1
        WHERE d.Exterior = 1
        ORDER BY d.FechaIngreso DESC
    ");

    $documentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcular días transcurridos
    foreach ($documentos as &$doc) {
        $inicio = new DateTime($doc['FechaIngreso']);
        $hoy = new DateTime();
        $inicio->setTime(0, 0, 0);
        $hoy->setTime(0, 0, 0);
        $diferencia = $hoy->diff($inicio);
        $doc['DiasTranscurridos'] = $diferencia->days;

        // Determinar semáforo
        if ($doc['DiasTranscurridos'] <= 2) {
            $doc['SemaforoTexto'] = 'En tiempo';
        } elseif ($doc['DiasTranscurridos'] <= 5) {
            $doc['SemaforoTexto'] = 'Atención';
        } else {
            $doc['SemaforoTexto'] = 'Urgente';
        }
    }

    return $documentos;
}

/**
 * Generar Excel REAL usando PhpSpreadsheet
 */
function generarExcelReal($datos)
{
    // Buscar autoloader de Composer en diferentes ubicaciones
    $autoloadPaths = [
        __DIR__ . '/../../../vendor/autoload.php',
        __DIR__ . '/../../vendor/autoload.php',
        __DIR__ . '/../vendor/autoload.php',
        __DIR__ . '/vendor/autoload.php'
    ];

    $autoloadFound = false;
    foreach ($autoloadPaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $autoloadFound = true;
            break;
        }
    }

    if (!$autoloadFound || !class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
        // Fallback a CSV si no se encuentra PhpSpreadsheet
        generarCSV($datos);
        return;
    }

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Configurar título
    $sheet->setCellValue('A1', 'REPORTE DE SUPERVISIÓN - DOCUMENTOS EXTERNOS');
    $sheet->mergeCells('A1:J1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

    // Información adicional
    $sheet->setCellValue('A2', 'Defensoría del Pueblo - Municipalidad Provincial de Pisco');
    $sheet->mergeCells('A2:J2');
    $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

    $sheet->setCellValue('A3', 'Generado el: ' . date('d/m/Y H:i:s'));
    $sheet->mergeCells('A3:J3');
    $sheet->getStyle('A3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

    // Estadísticas
    $total = count($datos);
    $enTiempo = count(array_filter($datos, fn($d) => $d['DiasTranscurridos'] <= 2));
    $atencion = count(array_filter($datos, fn($d) => $d['DiasTranscurridos'] > 2 && $d['DiasTranscurridos'] <= 5));
    $urgentes = count(array_filter($datos, fn($d) => $d['DiasTranscurridos'] > 5));

    $sheet->setCellValue('A5', 'ESTADÍSTICAS:');
    $sheet->getStyle('A5')->getFont()->setBold(true);
    $sheet->setCellValue('A6', 'Total de documentos:');
    $sheet->setCellValue('B6', $total);
    $sheet->setCellValue('A7', 'En tiempo (1-2 días):');
    $sheet->setCellValue('B7', $enTiempo);
    $sheet->setCellValue('A8', 'Requieren atención (3-5 días):');
    $sheet->setCellValue('B8', $atencion);
    $sheet->setCellValue('A9', 'Urgentes (6+ días):');
    $sheet->setCellValue('B9', $urgentes);

    // Headers de la tabla
    $headers = ['N°', 'Número Documento', 'Asunto', 'Estado', 'Fecha Ingreso', 'Área Destino', 'Días Transcurridos', 'Estado Tiempo', 'Observación', 'Recibido Por'];
    $col = 'A';
    $row = 11;

    foreach ($headers as $header) {
        $sheet->setCellValue($col . $row, $header);
        $sheet->getStyle($col . $row)->getFont()->setBold(true);
        $sheet->getStyle($col . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF6c5ce7');
        $sheet->getStyle($col . $row)->getFont()->getColor()->setARGB('FFFFFFFF');
        $col++;
    }

    // Datos
    $row = 12;
    foreach ($datos as $index => $doc) {
        $sheet->setCellValue('A' . $row, $index + 1);
        $sheet->setCellValue('B' . $row, $doc['NumeroDocumento']);
        $sheet->setCellValue('C' . $row, $doc['Asunto']);
        $sheet->setCellValue('D' . $row, $doc['EstadoTexto']);
        $sheet->setCellValue('E' . $row, date('d/m/Y', strtotime($doc['FechaIngreso'])));
        $sheet->setCellValue('F' . $row, $doc['AreaDestino'] ?? 'No asignada');
        $sheet->setCellValue('G' . $row, $doc['DiasTranscurridos']);
        $sheet->setCellValue('H' . $row, $doc['SemaforoTexto']);
        $sheet->setCellValue('I' . $row, $doc['UltimaObservacion'] ?? 'Sin observaciones');
        $sheet->setCellValue('J' . $row, $doc['NombreUsuario'] . ' ' . $doc['ApellidoUsuario']);
        $row++;
    }

    // Ajustar anchos de columna
    foreach (range('A', 'J') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Aplicar bordes a la tabla
    $sheet->getStyle('A11:J' . ($row - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

    // Enviar archivo
    $fecha = date('Y-m-d_H-i-s');
    $filename = "supervision_documentos_{$fecha}.xlsx";

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    header('Pragma: public');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

/**
 * Generar PDF REAL usando TCPDF
 */
function generarPDFReal($datos)
{
    // Buscar autoloader de Composer en diferentes ubicaciones
    $autoloadPaths = [
        __DIR__ . '/../../../vendor/autoload.php',
        __DIR__ . '/../../vendor/autoload.php',
        __DIR__ . '/../vendor/autoload.php',
        __DIR__ . '/vendor/autoload.php'
    ];

    $autoloadFound = false;
    foreach ($autoloadPaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $autoloadFound = true;
            break;
        }
    }

    if (!$autoloadFound || !class_exists('TCPDF')) {
        // Fallback a CSV si no se encuentra TCPDF
        generarCSV($datos);
        return;
    }

    $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Configurar documento
    $pdf->SetCreator('DIGI - Sistema de Supervisión');
    $pdf->SetAuthor('Defensoría del Pueblo');
    $pdf->SetTitle('Reporte de Supervisión - Documentos Externos');
    $pdf->SetSubject('Monitoreo de documentos externos');

    // Quitar header y footer por defecto
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    // Configurar página
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(TRUE, 20);
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    // Agregar página
    $pdf->AddPage();

    // Título
    $pdf->SetFont('helvetica', 'B', 18);
    $pdf->Cell(0, 10, 'REPORTE DE SUPERVISIÓN', 0, 1, 'C');
    $pdf->Cell(0, 8, 'DOCUMENTOS EXTERNOS', 0, 1, 'C');

    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 8, 'Defensoría del Pueblo - Municipalidad Provincial de Pisco', 0, 1, 'C');
    $pdf->Cell(0, 8, 'Generado el: ' . date('d/m/Y H:i:s'), 0, 1, 'C');
    $pdf->Ln(10);

    // Estadísticas
    $total = count($datos);
    $enTiempo = count(array_filter($datos, fn($d) => $d['DiasTranscurridos'] <= 2));
    $atencion = count(array_filter($datos, fn($d) => $d['DiasTranscurridos'] > 2 && $d['DiasTranscurridos'] <= 5));
    $urgentes = count(array_filter($datos, fn($d) => $d['DiasTranscurridos'] > 5));

    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 8, 'RESUMEN ESTADÍSTICO:', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(0, 6, "Total de documentos externos: {$total}", 0, 1, 'L');
    $pdf->Cell(0, 6, "En tiempo (1-2 días): {$enTiempo} (" . round(($enTiempo / $total) * 100, 1) . "%)", 0, 1, 'L');
    $pdf->Cell(0, 6, "Requieren atención (3-5 días): {$atencion} (" . round(($atencion / $total) * 100, 1) . "%)", 0, 1, 'L');
    $pdf->Cell(0, 6, "Urgentes (6+ días): {$urgentes} (" . round(($urgentes / $total) * 100, 1) . "%)", 0, 1, 'L');
    $pdf->Ln(10);

    // Tabla de documentos
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'DETALLE DE DOCUMENTOS:', 0, 1, 'L');

    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetFillColor(108, 92, 231);
    $pdf->SetTextColor(255, 255, 255);

    // Headers
    $pdf->Cell(15, 8, 'N°', 1, 0, 'C', 1);
    $pdf->Cell(35, 8, 'Documento', 1, 0, 'C', 1);
    $pdf->Cell(50, 8, 'Asunto', 1, 0, 'C', 1);
    $pdf->Cell(25, 8, 'Estado', 1, 0, 'C', 1);
    $pdf->Cell(25, 8, 'Fecha', 1, 0, 'C', 1);
    $pdf->Cell(30, 8, 'Días', 1, 1, 'C', 1);

    // Datos
    $pdf->SetFont('helvetica', '', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(248, 249, 250);

    foreach ($datos as $index => $doc) {
        // Verificar si necesita nueva página
        if ($pdf->GetY() > 250) {
            $pdf->AddPage();

            // Repetir headers
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->SetFillColor(108, 92, 231);
            $pdf->SetTextColor(255, 255, 255);

            $pdf->Cell(15, 8, 'N°', 1, 0, 'C', 1);
            $pdf->Cell(35, 8, 'Documento', 1, 0, 'C', 1);
            $pdf->Cell(50, 8, 'Asunto', 1, 0, 'C', 1);
            $pdf->Cell(25, 8, 'Estado', 1, 0, 'C', 1);
            $pdf->Cell(25, 8, 'Fecha', 1, 0, 'C', 1);
            $pdf->Cell(30, 8, 'Días', 1, 1, 'C', 1);

            $pdf->SetFont('helvetica', '', 7);
            $pdf->SetTextColor(0, 0, 0);
        }

        $fill = ($index % 2 == 0) ? 1 : 0;

        $pdf->Cell(15, 6, $index + 1, 1, 0, 'C', $fill);
        $pdf->Cell(35, 6, substr($doc['NumeroDocumento'], 0, 20), 1, 0, 'L', $fill);
        $pdf->Cell(50, 6, substr($doc['Asunto'], 0, 35) . '...', 1, 0, 'L', $fill);
        $pdf->Cell(25, 6, substr($doc['EstadoTexto'], 0, 12), 1, 0, 'C', $fill);
        $pdf->Cell(25, 6, date('d/m/Y', strtotime($doc['FechaIngreso'])), 1, 0, 'C', $fill);
        $pdf->Cell(30, 6, $doc['DiasTranscurridos'] . ' días (' . $doc['SemaforoTexto'] . ')', 1, 1, 'C', $fill);
    }

    // Enviar archivo
    $fecha = date('Y-m-d_H-i-s');
    $filename = "supervision_documentos_{$fecha}.pdf";

    $pdf->Output($filename, 'D');
    exit;
}

/**
 * Generar CSV
 */
function generarCSV($datos)
{
    $fecha = date('Y-m-d_H-i-s');
    $filename = "supervision_documentos_{$fecha}.csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $output = fopen('php://output', 'w');

    // BOM para UTF-8
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Headers
    fputcsv($output, [
        'N°',
        'Número Documento',
        'Asunto',
        'Estado',
        'Fecha Ingreso',
        'Área Destino',
        'Días Transcurridos',
        'Estado Tiempo',
        'Observación',
        'Recibido Por'
    ]);

    // Datos
    foreach ($datos as $index => $doc) {
        fputcsv($output, [
            $index + 1,
            $doc['NumeroDocumento'],
            $doc['Asunto'],
            $doc['EstadoTexto'],
            date('d/m/Y', strtotime($doc['FechaIngreso'])),
            $doc['AreaDestino'] ?? 'No asignada',
            $doc['DiasTranscurridos'],
            $doc['SemaforoTexto'],
            $doc['UltimaObservacion'] ?? 'Sin observaciones',
            $doc['NombreUsuario'] . ' ' . $doc['ApellidoUsuario']
        ]);
    }

    fclose($output);
    exit;
}
