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

try {

    /*
    ============================================================
    DOCUMENTOS ANEXADOS + ÚLTIMO MOVIMIENTO
    ============================================================
    */

    $sql = "
        SELECT
            d_anexo.IdDocumentos              AS IdDocumentos,
            d_anexo.NumeroDocumento           AS NumeroDocumento,
            d_anexo.Asunto                    AS Asunto,

            d_origen.NumeroDocumento          AS DocumentoOrigen,

            dr.FechaRelacion                  AS FechaRelacion,

            md.Recibido                       AS Recibido

        FROM documento_relacion dr

        INNER JOIN documentos d_anexo
            ON d_anexo.IdDocumentos = dr.IdDocumentoDestino

        INNER JOIN documentos d_origen
            ON d_origen.IdDocumentos = dr.IdDocumentoOrigen

        INNER JOIN movimientodocumento md
            ON md.IdDocumentos = d_anexo.IdDocumentos

        INNER JOIN (
            SELECT IdDocumentos, MAX(FechaMovimiento) AS MaxFecha
            FROM movimientodocumento
            GROUP BY IdDocumentos
        ) ult
            ON ult.IdDocumentos = md.IdDocumentos
           AND ult.MaxFecha = md.FechaMovimiento

        WHERE
            (d_anexo.NumeroDocumento LIKE :busqueda
             OR d_anexo.Asunto LIKE :busqueda)
    ";

    // ------------------------------------------------------------
    // FILTRO POR ÁREA (USUARIOS NORMALES)
    // ------------------------------------------------------------
    if ($rol_id != 1) {
        $sql .= "
            AND (
                md.AreaDestino = :area_id
                OR md.AreaOrigen = :area_id
            )
        ";
    }

    $sql .= "
        ORDER BY dr.FechaRelacion DESC
        LIMIT 100
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':busqueda', $like);

    if ($rol_id != 1) {
        $stmt->bindValue(':area_id', $area_id, PDO::PARAM_INT);
    }

    $stmt->execute();
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
