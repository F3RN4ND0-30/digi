<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require '../../db/conexion.php';

header('Content-Type: application/json; charset=utf-8');

$area_id = $_SESSION['dg_area_id'] ?? null;
$rol_id  = $_SESSION['dg_rol'] ?? null;

if (!$rol_id) {
    echo json_encode([]);
    exit;
}

$busqueda = $_GET['busqueda'] ?? '';
$like     = "%$busqueda%";

$tipo     = $_GET['tipo'] ?? ''; // DOC – MEMO – ''

try {

    /* ============================================================
       1) DOCUMENTOS → último movimiento de cada documento
       ============================================================ */
    $sqlDocs = "
        SELECT
            'DOC' AS TipoRegistro,
            md1.IdDocumentos            AS IdDocMemo,
            d.NumeroDocumento           AS NumeroDocumento,
            d.Asunto                    AS Asunto,
            d.Finalizado                AS Finalizado,
            d.IdEstadoDocumento         AS IdEstadoDocumento,
            md1.AreaDestino             AS AreaDestino,
            a.Nombre                    AS NombreAreaDestino,
            md1.FechaMovimiento         AS FechaMovimiento,
            md1.Recibido                AS Recibido
        FROM movimientodocumento md1
        INNER JOIN (
            SELECT IdDocumentos, MAX(FechaMovimiento) AS MaxFecha
            FROM movimientodocumento
            GROUP BY IdDocumentos
        ) ult ON md1.IdDocumentos = ult.IdDocumentos
             AND md1.FechaMovimiento = ult.MaxFecha
        INNER JOIN documentos d ON d.IdDocumentos = md1.IdDocumentos
        LEFT JOIN areas a ON a.IdAreas = md1.AreaDestino
        WHERE (d.NumeroDocumento LIKE :busqueda OR d.Asunto LIKE :busqueda)
    ";

    // Usuarios normales → filtrar por área
    if ($rol_id != 1) {
        $sqlDocs .= "
            AND md1.IdDocumentos IN (
                SELECT DISTINCT IdDocumentos
                FROM movimientodocumento
                WHERE AreaOrigen = :area_id OR AreaDestino = :area_id
            )
        ";
    }


    /* ============================================================
       2) MEMORÁNDUMS → último movimiento es la emisión
       ============================================================ */
    $sqlMemos = "
    SELECT
        'MEMO' AS TipoRegistro,
        m.IdMemo                   AS IdDocMemo,
        m.CodigoMemo              AS NumeroDocumento,
        m.Asunto                  AS Asunto,
        0                         AS Finalizado,              -- MEMO NO TIENE ESTA COLUMNA
        m.IdEstadoDocumento       AS IdEstadoDocumento,
        COALESCE(
            GROUP_CONCAT(DISTINCT a_dest2.Nombre ORDER BY a_dest2.Nombre SEPARATOR ', '),
            'SIN DESTINOS'
        )                         AS NombreAreaDestino,       -- ← renombrado correctamente
        m.FechaEmision            AS FechaMovimiento,
        CASE 
            WHEN SUM(CASE WHEN md.Recibido = 0 THEN 1 ELSE 0 END) > 0 THEN 0
            ELSE 1
        END                       AS Recibido,
        0                         AS AreaDestino              -- MEMO NO TIENE DESTINO ÚNICO
    FROM memorandums m
    LEFT JOIN memorandum_destinos md   ON md.IdMemo        = m.IdMemo
    LEFT JOIN areas a_dest2            ON md.IdAreaDestino = a_dest2.IdAreas
    WHERE (m.CodigoMemo LIKE :busqueda OR m.Asunto LIKE :busqueda)
";

    if ($rol_id != 1) {
        $sqlMemos .= " AND m.IdAreaOrigen = :area_id ";
    }

    $sqlMemos .= "
    GROUP BY 
        m.IdMemo,
        m.CodigoMemo,
        m.Asunto,
        m.IdEstadoDocumento,
        m.FechaEmision
";



    /* ============================================================
       FILTRAR POR TIPO: DOC o MEMO
       ============================================================ */

    if ($tipo === 'DOC') {
        $stmt = $pdo->prepare($sqlDocs . " ORDER BY FechaMovimiento DESC LIMIT 100");
        $stmt->bindValue(':busqueda', $like);
        if ($rol_id != 1) $stmt->bindValue(':area_id', $area_id);
        $stmt->execute();
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    if ($tipo === 'MEMO') {
        $stmt = $pdo->prepare($sqlMemos . " ORDER BY FechaMovimiento DESC LIMIT 100");
        $stmt->bindValue(':busqueda', $like);
        if ($rol_id != 1) $stmt->bindValue(':area_id', $area_id);
        $stmt->execute();
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }


    /* ============================================================
       MIXTO → UNION ALL
       ============================================================ */
    $sqlFinal = "
        ($sqlDocs)
        UNION ALL
        ($sqlMemos)
        ORDER BY FechaMovimiento DESC
        LIMIT 100
    ";

    $stmt = $pdo->prepare($sqlFinal);
    $stmt->bindValue(':busqueda', $like);
    if ($rol_id != 1) $stmt->bindValue(':area_id', $area_id);

    $stmt->execute();
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
