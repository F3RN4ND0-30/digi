<?php
require '../../db/conexion.php';

$dni_ruc    = $_GET['dni_ruc']    ?? '';
$expediente = $_GET['expediente'] ?? '';

$dni_ruc    = trim($dni_ruc);
$expediente = trim($expediente);

// Si faltan datos, devolvemos vacío
if ($dni_ruc === '' || $expediente === '') {

    // Registrar auditoría incluso si el usuario no completó todo
    registrarAuditoria($pdo, $dni_ruc, null, $expediente, "DATOS_INCOMPLETOS");

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([]);
    exit;
}

$sql = "
    SELECT 
        -- DATOS DEL DOCUMENTO
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

        -- DATOS DEL MOVIMIENTO
        md.IdMovimientoDocumento,
        md.AreaOrigen,
        md.AreaDestino,
        md.FechaMovimiento,
        md.NumeroFolios          AS MovimientoFolios,
        md.Recibido,
        md.Observacion,
        md.IdInforme,

        -- NOMBRES DE ÁREAS
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
        d.DniRuc           = :dni_ruc
        AND d.NumeroDocumento = :expediente
        AND d.Exterior     = 1        -- SOLO DOCUMENTOS EXTERNOS

    ORDER BY md.FechaMovimiento ASC
";

$stmt = $pdo->prepare($sql);
$stmt->bindParam(':dni_ruc', $dni_ruc, PDO::PARAM_STR);
$stmt->bindParam(':expediente', $expediente, PDO::PARAM_STR);
$stmt->execute();

$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener nombre para auditoría (si existe)
$nombreConsultado = $resultados[0]['NombreContribuyente'] ?? null;

// Determinar resultado de la búsqueda
$resultadoConsulta = empty($resultados) ? "NO_ENCONTRADO" : "ENCONTRADO";

// Registrar auditoría
registrarAuditoria($pdo, $dni_ruc, $nombreConsultado, $expediente, $resultadoConsulta);

// Devolver datos
header('Content-Type: application/json; charset=utf-8');
echo json_encode($resultados);



/* ============================================================
   ===============   FUNCIÓN DE AUDITORÍA   ===================
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
        ':dni_ruc'   => $dni_ruc,
        ':nombre'    => $nombre,
        ':expediente' => $expediente,
        ':ip'        => $ip,
        ':userAgent' => $userAgent,
        ':resultado' => $resultado
    ]);
}
