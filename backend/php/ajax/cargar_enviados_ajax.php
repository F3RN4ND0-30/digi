<?php
// backend/php/ajax/cargar_enviados_ajax.php
session_start();
require '../../db/conexion.php';

header('Content-Type: application/json; charset=utf-8');

$area_id = $_SESSION['dg_area_id'] ?? null;
if (!$area_id) {
    echo json_encode([]);
    exit;
}

$tipo = $_GET['tipo'] ?? ''; // '', DOC, MEMO

try {
    /* =========================
       1) DOCUMENTOS
       ========================= */
    $sqlDocs = "
        SELECT 
            'DOC'                   AS TipoRegistro,
            m.IdMovimientoDocumento AS IdMovimientoDocumento,
            d.NumeroDocumento       AS NumeroDocumento,
            d.Asunto                AS Asunto,
            e.Estado                AS Estado,
            m.FechaMovimiento       AS FechaMovimiento,
            a_dest.Nombre           AS AreaDestino,
            m.Observacion           AS Observacion,
            m.Recibido              AS Recibido
        FROM movimientodocumento m
        INNER JOIN documentos d        ON m.IdDocumentos      = d.IdDocumentos
        INNER JOIN estadodocumento e   ON d.IdEstadoDocumento = e.IdEstadoDocumento
        INNER JOIN areas a_dest        ON m.AreaDestino       = a_dest.IdAreas
        WHERE m.AreaOrigen = :area
    ";

    /* =========================
       2) MEMORÁNDUMS
       ========================= */
    $sqlMemos = "
        SELECT
            'MEMO'                       AS TipoRegistro,
            m.IdMemo                     AS IdMovimientoDocumento,
            m.CodigoMemo                 AS NumeroDocumento,
            m.Asunto                     AS Asunto,
            ed.Estado                    AS Estado,
            m.FechaEmision               AS FechaMovimiento,
            COALESCE(
                GROUP_CONCAT(DISTINCT a_dest2.Nombre ORDER BY a_dest2.Nombre SEPARATOR ', '),
                'SIN DESTINOS'
            )                            AS AreaDestino,
            ''                           AS Observacion,
            CASE 
                WHEN SUM(CASE WHEN md.Recibido = 0 THEN 1 ELSE 0 END) > 0 THEN 0
                ELSE 1
            END                          AS Recibido
        FROM memorandums m
        LEFT JOIN memorandum_destinos md   ON md.IdMemo           = m.IdMemo
        LEFT JOIN areas a_dest2            ON md.IdAreaDestino    = a_dest2.IdAreas
        LEFT JOIN estadodocumento ed       ON m.IdEstadoDocumento = ed.IdEstadoDocumento
        WHERE m.IdAreaOrigen = :area
        GROUP BY 
            m.IdMemo,
            m.CodigoMemo,
            m.Asunto,
            ed.Estado,
            m.FechaEmision
    ";

    // Solo documentos
    if ($tipo === 'DOC') {
        $stmt = $pdo->prepare($sqlDocs . " ORDER BY FechaMovimiento DESC");
        $stmt->execute(['area' => $area_id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Solo memorándums
    if ($tipo === 'MEMO') {
        $stmt = $pdo->prepare($sqlMemos . " ORDER BY FechaMovimiento DESC");
        $stmt->execute(['area' => $area_id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Mixto (por si lo usas en otro lado)
    $sql = "
        ($sqlDocs)
        UNION ALL
        ($sqlMemos)
        ORDER BY FechaMovimiento DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['area' => $area_id]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
