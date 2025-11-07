<?php
// backend/php/memorandum/guardar_memo.php
session_start();

if (!isset($_SESSION['dg_id'])) {
    header('Location: ../../../frontend/login.php');
    exit;
}

require '../../db/conexion.php';

try {
    // 1. Validar datos del formulario
    $usuarioId    = (int)($_SESSION['dg_id'] ?? 0);
    $areaOrigen   = (int)($_POST['area_emisora'] ?? 0);
    $tipoMemo     = $_POST['tipo_memo'] ?? '';
    $asunto       = trim($_POST['asunto'] ?? '');
    $areasDestino = isset($_POST['areas_destino']) ? (array)$_POST['areas_destino'] : [];

    if (!$usuarioId || !$areaOrigen || !$tipoMemo || !$asunto || empty($areasDestino)) {
        $_SESSION['mensaje_memo'] = '❌ Datos incompletos del memorándum.';
        header('Location: ../../../frontend/archivos/memorandums.php');
        exit;
    }

    // Limpiar destinos (solo ints y sin duplicados)
    $areasDestino = array_unique(array_map('intval', $areasDestino));

    $anio = (int)date('Y');

    // 2. Iniciar transacción
    $pdo->beginTransaction();

    // 3. Obtener siguiente correlativo para ESTA área y ESTE año
    $stmt = $pdo->prepare("
        SELECT COALESCE(MAX(NumeroCorrelativo), 0) + 1
        FROM memorandums
        WHERE IdAreaOrigen = ? AND `Año` = ?
    ");
    $stmt->execute([$areaOrigen, $anio]);
    $numeroCorrelativo = (int)$stmt->fetchColumn();

    // 4. Obtener abreviatura del área para armar el código
    $stmtArea = $pdo->prepare("SELECT Abreviatura FROM areas WHERE IdAreas = ?");
    $stmtArea->execute([$areaOrigen]);
    $abreviatura = $stmtArea->fetchColumn() ?: 'AREA';

    // Ej: SIS-MEMO-001-2025
    $codigoMemo = sprintf(
        '%s-MEMO-%03d-%d',
        $abreviatura,
        $numeroCorrelativo,
        $anio
    );

    // 5. Insertar encabezado del memorándum
    $stmtInsert = $pdo->prepare("
        INSERT INTO memorandums
            (IdAreaOrigen, TipoMemo, NumeroCorrelativo, `Año`, CodigoMemo, Asunto, FechaEmision, IdUsuarioEmisor, Estado)
        VALUES
            (:area, :tipo, :num, :anio, :codigo, :asunto, NOW(), :usuario, 1)
    ");

    $stmtInsert->execute([
        ':area'    => $areaOrigen,
        ':tipo'    => $tipoMemo,
        ':num'     => $numeroCorrelativo,
        ':anio'    => $anio,
        ':codigo'  => $codigoMemo,
        ':asunto'  => $asunto,
        ':usuario' => $usuarioId,
    ]);

    $idMemo = (int)$pdo->lastInsertId();

    // 6. Insertar destinos (varias áreas)
    $stmtDest = $pdo->prepare("
        INSERT INTO memorandum_destinos (IdMemo, IdAreaDestino)
        VALUES (:idMemo, :idArea)
    ");

    foreach ($areasDestino as $idAreaDestino) {
        $stmtDest->execute([
            ':idMemo' => $idMemo,
            ':idArea' => $idAreaDestino,
        ]);
    }

    // 7. Confirmar transacción
    $pdo->commit();

    $_SESSION['mensaje_memo'] =
        "✅ Memorándum registrado correctamente. Código: {$codigoMemo}";
    header('Location: ../../../frontend/archivos/memorandums.php');
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Si choca el índice único (muy raro), solo avisamos
    if ($e->getCode() === '23000') {
        $_SESSION['mensaje_memo'] =
            '❌ Conflicto de numeración. Intente guardar nuevamente.';
    } else {
        $_SESSION['mensaje_memo'] =
            '❌ Error al guardar el memorándum: ' . $e->getMessage();
    }

    header('Location: ../../../frontend/archivos/memorandums.php');
    exit;
}
