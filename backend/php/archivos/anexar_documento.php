<?php
session_start();
require '../../db/conexion.php';

header('Content-Type: application/json');

try {
    // 1. Recibir datos
    $idOrigen  = intval($_POST['id_documento_origen'] ?? 0);
    $idDestino = intval($_POST['id_documento_destino'] ?? 0);
    $tipo      = $_POST['tipo_relacion'] ?? '';
    $obs       = trim($_POST['observacion'] ?? '');

    // 2. Validaciones básicas
    if ($idOrigen <= 0 || $idDestino <= 0) {
        throw new Exception('Expedientes inválidos');
    }

    if ($idOrigen === $idDestino) {
        throw new Exception('No se puede anexar un expediente consigo mismo');
    }

    $tiposPermitidos = ['ANEXO', 'SUBSANACION', 'CONTINUACION'];
    if (!in_array($tipo, $tiposPermitidos)) {
        throw new Exception('Tipo de relación no válido');
    }

    // 3. Verificar que ambos documentos existan
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM documentos 
        WHERE IdDocumentos IN (?, ?)
    ");
    $stmt->execute([$idOrigen, $idDestino]);

    if ($stmt->fetchColumn() != 2) {
        throw new Exception('Uno o ambos expedientes no existen');
    }

    // 4. Evitar relación duplicada
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM documento_relacion
        WHERE IdDocumentoOrigen = ?
          AND IdDocumentoDestino = ?
    ");
    $stmt->execute([$idOrigen, $idDestino]);

    if ($stmt->fetchColumn() > 0) {
        throw new Exception('Este expediente ya está anexado');
    }

    // 5. Evitar ciclos simples (destino → origen)
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM documento_relacion
        WHERE IdDocumentoOrigen = ?
          AND IdDocumentoDestino = ?
    ");
    $stmt->execute([$idDestino, $idOrigen]);

    if ($stmt->fetchColumn() > 0) {
        throw new Exception('Relación circular no permitida');
    }

    // 6. Insertar relación
    $stmt = $pdo->prepare("
        INSERT INTO documento_relacion
        (IdDocumentoOrigen, IdDocumentoDestino, TipoRelacion, Observacion)
        VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([
        $idOrigen,
        $idDestino,
        $tipo,
        $obs
    ]);

    echo json_encode([
        'ok' => true,
        'mensaje' => 'Documento anexado correctamente'
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'mensaje' => $e->getMessage()
    ]);
}
