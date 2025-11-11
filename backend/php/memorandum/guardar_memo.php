<?php
// backend/php/memorandum/guardar_memo.php
session_start();

if (!isset($_SESSION['dg_id']) || !isset($_SESSION['dg_area_id'])) {
    header('Location: ../../../frontend/login.php');
    exit;
}

require '../../db/conexion.php';

// (opcional) helper de notificaciones
$usa_util_notis = true;
if ($usa_util_notis) {
    @require_once '../util/notificaciones_util.php';
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método inválido.');
    }

    $usuarioId    = (int)($_SESSION['dg_id'] ?? 0);
    $areaOrigenId = (int)($_POST['area_emisora'] ?? 0);
    $tipoMemo     = strtoupper(trim($_POST['tipo_memo'] ?? ''));
    $destinos     = array_filter(array_map('intval', $_POST['areas_destino'] ?? []));
    $asunto       = trim($_POST['asunto'] ?? '');

    if (
        !$usuarioId || !$areaOrigenId ||
        !in_array($tipoMemo, ['CIRCULAR', 'MULTIPLE'], true) ||
        $asunto === '' || empty($destinos)
    ) {
        throw new Exception('Datos incompletos para registrar el memorándum.');
    }

    $pdo->beginTransaction();

    // 1) Datos del área emisora
    $stArea = $pdo->prepare('SELECT Nombre, Abreviatura FROM areas WHERE IdAreas = ?');
    $stArea->execute([$areaOrigenId]);
    $area = $stArea->fetch(PDO::FETCH_ASSOC);
    if (!$area) {
        throw new Exception('Área emisora no válida.');
    }
    $nombreArea = $area['Nombre'];
    $abrev      = $area['Abreviatura'] ?: 'SIN-ABR';

    // 2) Correlativo por área + año
    $anio = (int) date('Y');

    $stCorr = $pdo->prepare('
        SELECT COALESCE(MAX(NumeroCorrelativo), 0) AS maxcorr
        FROM memorandums
        WHERE IdAreaOrigen = :area AND `Año` = :anio
        FOR UPDATE
    ');
    $stCorr->execute(['area' => $areaOrigenId, 'anio' => $anio]);
    $nuevoCorrelativo = ((int) $stCorr->fetchColumn()) + 1;

    // 3) Código visible
    $codStr = str_pad($nuevoCorrelativo, 3, '0', STR_PAD_LEFT);
    $codigo = $codStr . '-' . $anio . '-' . $abrev; // ej: 001-2025-UDS

    // 4) Insertar memorándum
    // OJO: usamos IdEstadoDocumento en vez de "Estado"
    // 1 = NUEVO en tu tabla estadodocumento
    $ins = $pdo->prepare('
        INSERT INTO memorandums
        (IdAreaOrigen, TipoMemo, NumeroCorrelativo, `Año`,
         CodigoMemo, Asunto, FechaEmision, IdUsuarioEmisor, IdEstadoDocumento)
        VALUES
        (:area, :tipo, :corr, :anio,
         :codigo, :asunto, NOW(), :usuario, 1)
    ');
    $ins->execute([
        'area'    => $areaOrigenId,
        'tipo'    => $tipoMemo,
        'corr'    => $nuevoCorrelativo,
        'anio'    => $anio,
        'codigo'  => $codigo,
        'asunto'  => $asunto,
        'usuario' => $usuarioId
    ]);
    $idMemo = (int) $pdo->lastInsertId();

    // 5) Destinos + notificaciones
    $insDest = $pdo->prepare('
        INSERT INTO memorandum_destinos (IdMemo, IdAreaDestino)
        VALUES (:memo, :dest)
    ');

    foreach ($destinos as $idDest) {
        if ($idDest <= 0) continue;

        $insDest->execute([
            'memo' => $idMemo,
            'dest' => $idDest
        ]);

        $mensaje = "Has recibido un MEMORÁNDUM {$tipoMemo} N° {$codigo}: \"{$asunto}\" de {$nombreArea}.";

        if ($usa_util_notis && function_exists('crearNotificacion')) {
            crearNotificacion($pdo, $idDest, $mensaje);
        } else {
            $stNoti = $pdo->prepare('
                INSERT INTO notificaciones (IdAreas, Mensaje, Estado, FechaVisto)
                VALUES (:area, :mensaje, "nueva", NOW())
            ');
            $stNoti->execute([
                'area'    => $idDest,
                'mensaje' => $mensaje
            ]);
        }
    }

    $pdo->commit();
    $_SESSION['mensaje_memo'] = "✅ Memorándum N° {$codigo} registrado y enviado.";
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $_SESSION['mensaje_memo'] = '❌ Error al registrar el memorándum: ' . $e->getMessage();
}

header('Location: ../../../frontend/archivos/memorandums.php');
exit;
