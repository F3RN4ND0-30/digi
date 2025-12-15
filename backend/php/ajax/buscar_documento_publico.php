<?php
require '../../db/conexion.php';

$dni_ruc             = trim($_GET['dni_ruc'] ?? '');
$expediente_num      = trim($_GET['expediente_num'] ?? ''); // 8 dígitos
$anio                = trim($_GET['anio'] ?? '');
$expediente_formato  = trim($_GET['expediente_formateado'] ?? '');

// Validación mínima
if ($dni_ruc === '' || ($expediente_num === '' && $expediente_formato === '')) {

    registrarAuditoria($pdo, $dni_ruc, null, $expediente_formato, "DATOS_INCOMPLETOS");

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([]);
    exit;
}

/*
  Construimos el LIKE:
  Ejemplo final:
  NumeroDocumento LIKE '%00000008-%'
*/
$likeExpediente = '%' . $expediente_num . '-%';

$sql = "
    SELECT 
        -- DOCUMENTO
        d.IdDocumentos,
        d.NumeroDocumento,
        d.Asunto,
        d.Finalizado,
        d.IdEstadoDocumento,
        d.Exterior,
        d.DniRuc,
        d.NombreContribuyente,
        d.FechaIngreso,
        d.NumeroFolios           AS DocumentoFolios,

        -- MOVIMIENTO
        md.IdMovimientoDocumento,
        md.AreaOrigen,
        md.AreaDestino,
        md.FechaMovimiento,
        md.NumeroFolios          AS MovimientoFolios,
        md.Recibido,
        md.Observacion,
        md.IdInforme,

        -- AREAS
        ao.Nombre                AS OrigenNombre,
        ad.Nombre                AS DestinoNombre,

        -- INFORME
        inf.NombreInforme        AS InformeNombre

    FROM documentos d
    INNER JOIN movimientodocumento md 
        ON md.IdDocumentos = d.IdDocumentos
    LEFT JOIN areas ao 
        ON md.AreaOrigen = ao.IdAreas
    LEFT JOIN areas ad 
        ON md.AreaDestino = ad.IdAreas
    LEFT JOIN informes inf 
        ON md.IdInforme = inf.IdInforme

    WHERE 
        d.DniRuc = :dni_ruc
        AND d.Exterior = 1
        AND d.Año = :anio
        AND d.NumeroDocumento LIKE :expediente_like

    ORDER BY md.FechaMovimiento ASC
";

$stmt = $pdo->prepare($sql);
$stmt->bindParam(':dni_ruc', $dni_ruc, PDO::PARAM_STR);
$stmt->bindParam(':anio', $anio, PDO::PARAM_INT);
$stmt->bindParam(':expediente_like', $likeExpediente, PDO::PARAM_STR);
$stmt->execute();

$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Auditoría
$nombreConsultado = $resultados[0]['NombreContribuyente'] ?? null;
$resultadoConsulta = empty($resultados) ? "NO_ENCONTRADO" : "ENCONTRADO";

registrarAuditoria(
    $pdo,
    $dni_ruc,
    $nombreConsultado,
    $expediente_formato ?: $expediente_num . '-' . $anio,
    $resultadoConsulta
);

// Respuesta
header('Content-Type: application/json; charset=utf-8');
echo json_encode($resultados);


/* ============================================================
   ================= FUNCIÓN AUDITORÍA ========================
   ============================================================ */
function registrarAuditoria($pdo, $dni_ruc, $nombre, $expediente, $resultado)
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';

    $sql = "INSERT INTO auditoria_consultas (
                DniRuc,
                Nombre,
                Expediente,
                IpUsuario,
                user_agent,
                Resultado,
                FechaConsulta
            ) VALUES (
                :dni_ruc,
                :nombre,
                :expediente,
                :ip,
                :userAgent,
                :resultado,
                NOW()
            )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':dni_ruc'    => $dni_ruc,
        ':nombre'     => $nombre,
        ':expediente' => $expediente,
        ':ip'         => $ip,
        ':userAgent'  => $userAgent,
        ':resultado'  => $resultado
    ]);
}
