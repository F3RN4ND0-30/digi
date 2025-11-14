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

    $usuarioId     = (int)($_SESSION['dg_id'] ?? 0);
    $areaOrigenId  = (int)($_POST['area_emisora'] ?? 0);
    $tipoMemo      = strtoupper(trim($_POST['tipo_memo'] ?? ''));
    $destinos      = array_filter(array_map('intval', $_POST['areas_destino'] ?? []));
    $asunto        = trim($_POST['asunto'] ?? '');

    // Folios: si no viene o viene vacío, usar 0
    $numeroFolios  = (isset($_POST['numero_folios']) && $_POST['numero_folios'] !== '')
        ? max(0, (int)$_POST['numero_folios'])
        : 0;

    if (
        !$usuarioId || !$areaOrigenId ||
        !in_array($tipoMemo, ['CIRCULAR', 'MULTIPLE'], true) ||
        $asunto === '' || empty($destinos)
    ) {
        throw new Exception('Datos incompletos para registrar el memorándum.');
    }

    $pdo->beginTransaction();

    // 1) Área emisora (para abreviatura)
    $stArea = $pdo->prepare('SELECT Nombre, Abreviatura FROM areas WHERE IdAreas = ?');
    $stArea->execute([$areaOrigenId]);
    $area = $stArea->fetch(PDO::FETCH_ASSOC);
    if (!$area) {
        throw new Exception('Área emisora no válida.');
    }
    $nombreArea = $area['Nombre'];
    $abrev      = $area['Abreviatura'] ?: 'SIN-ABR';

    // 2) Correlativo por área + año (bloquea fila para concurrencia)
    $anio = (int) date('Y');
    $stCorr = $pdo->prepare('
        SELECT COALESCE(MAX(NumeroCorrelativo), 0) AS maxcorr
        FROM memorandums
        WHERE IdAreaOrigen = :area AND `Año` = :anio
        FOR UPDATE
    ');
    $stCorr->execute(['area' => $areaOrigenId, 'anio' => $anio]);
    $nuevoCorrelativo = ((int) $stCorr->fetchColumn()) + 1;

    // 3) Componer códigos
    $codStr          = str_pad($nuevoCorrelativo, 3, '0', STR_PAD_LEFT);
    $codigoSimple    = $codStr . '-' . $anio . '-' . $abrev; // ej: 001-2025-UDS
    $prefijoTipo     = ($tipoMemo === 'MULTIPLE') ? 'MEMORÁNDUM MÚLTIPLE' : 'MEMORÁNDUM CIRCULAR';
    $codigoCompleto  = $prefijoTipo . ' N° ' . $codigoSimple;

    // 4) Insertar Memorándum
    // IdEstadoDocumento: usa el que corresponda a "NUEVO" o "REGISTRADO" (aquí 1)
    $ins = $pdo->prepare('
        INSERT INTO memorandums
        (IdAreaOrigen, TipoMemo, NumeroCorrelativo, `Año`,
         CodigoMemo, Asunto, NumeroFolios, FechaEmision,
         IdUsuarioEmisor, IdEstadoDocumento)
        VALUES
        (:area, :tipo, :corr, :anio,
         :codigo, :asunto, :folios, NOW(),
         :usuario, 1)
    ');
    $ins->execute([
        'area'    => $areaOrigenId,
        'tipo'    => $tipoMemo,
        'corr'    => $nuevoCorrelativo,
        'anio'    => $anio,
        'codigo'  => $codigoCompleto,  
        'asunto'  => $asunto,
        'folios'  => $numeroFolios,    
        'usuario' => $usuarioId
    ]);
    $idMemo = (int) $pdo->lastInsertId();

    // 5) Destinos + notificaciones
    // Si tu tabla memorandum_destinos tiene Recibido con default 0, no es necesario pasarlo.
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

        $mensaje = "Has recibido un {$prefijoTipo} {$codigoSimple}: \"{$asunto}\" de {$nombreArea}.";

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
    $_SESSION['mensaje_memo'] = "✅ {$prefijoTipo} {$codigoSimple} registrado y enviado.";
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $_SESSION['mensaje_memo'] = '❌ Error al registrar el memorándum: ' . $e->getMessage();
}

header('Location: ../../../frontend/archivos/memorandums.php');
exit;
