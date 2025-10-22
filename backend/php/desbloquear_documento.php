<?php

/**
 * Desbloquear documento
 * Ruta esperada: backend/php/desbloquear_documento.php
 */
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Solo POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }

    // Usuario logueado
    if (!isset($_SESSION['dg_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Sesión no válida']);
        exit;
    }

    // Roles permitidos: 1 (Admin), 4 (Defensoría)
    $rol = (int)($_SESSION['dg_rol'] ?? 0);
    if (!in_array($rol, [1, 4], true)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No tienes permisos para desbloquear']);
        exit;
    }

    // Validar ID
    $id = $_POST['id'] ?? $_POST['IdDocumentos'] ?? null;
    if ($id === null || !ctype_digit((string)$id)) {
        echo json_encode(['success' => false, 'message' => 'ID de documento inválido']);
        exit;
    }
    $id = (int)$id;

    // Conexión
    require_once __DIR__ . '/../db/conexion.php'; // <- ajustado al árbol que mostraste

    // Verificar existencia y estado actual
    $stmt = $pdo->prepare("SELECT IdDocumentos, IdEstadoDocumento FROM documentos WHERE IdDocumentos = ?");
    $stmt->execute([$id]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doc) {
        echo json_encode(['success' => false, 'message' => 'Documento no encontrado']);
        exit;
    }

    // Si ya NO está bloqueado, salir con info
    if ((int)$doc['IdEstadoDocumento'] !== 4) {
        echo json_encode(['success' => true, 'message' => 'El documento no está bloqueado']);
        exit;
    }

    // Transacción para asegurar consistencia
    $pdo->beginTransaction();

    // Poner en SEGUIMIENTO (IdEstadoDocumento = 2)
    $upd = $pdo->prepare("UPDATE documentos SET IdEstadoDocumento = 2 WHERE IdDocumentos = ?");
    $upd->execute([$id]);

    // Dejar traza en movimientodocumento (si la tabla existe en tu BD)
    try {
        $ins = $pdo->prepare("
            INSERT INTO movimientodocumento (IdDocumentos, IdUsuarios, Observacion, FechaMovimiento)
            VALUES (?, ?, ?, NOW())
        ");
        $obs = 'Documento DESBLOQUEADO por el sistema (Defensoría/Admin).';
        $ins->execute([$id, (int)$_SESSION['dg_id'], $obs]);
    } catch (\Throwable $t) {
        // Si no existe la tabla o falla el insert, no rompemos la operación principal
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Documento desbloqueado (estado: SEGUIMIENTO)'
    ]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Desbloquear documento error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
