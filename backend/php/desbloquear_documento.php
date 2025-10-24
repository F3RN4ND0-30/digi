<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['dg_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesión expirada.']);
    exit;
}

// Solo admin (1) y Defensoría (4)
$rol = (int)($_SESSION['dg_rol'] ?? 0);
if (!in_array($rol, [1, 4], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tienes permisos para desbloquear.']);
    exit;
}

require __DIR__ . '/../db/conexion.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$accion   = $_POST['accion'] ?? '';
$idDoc    = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$password = $_POST['password'] ?? '';

if ($accion !== 'desbloquear' || $idDoc <= 0) {
    echo json_encode(['success' => false, 'message' => 'Parámetros inválidos.']);
    exit;
}

if ($password === '') {
    echo json_encode(['success' => false, 'message' => 'Debes ingresar tu contraseña.']);
    exit;
}

try {
    // 1) Verificar usuario y contraseña
    $stmt = $pdo->prepare("SELECT Usuario, Clave FROM usuarios WHERE IdUsuarios = ?");
    $stmt->execute([$_SESSION['dg_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado.']);
        exit;
    }

    $hash = (string)$user['Clave'];
    $isBcrypt = preg_match('/^\$2[ayb]\$/', $hash) === 1;
    $okPass = $isBcrypt ? password_verify($password, $hash) : hash_equals($hash, $password);

    if (!$okPass) {
        echo json_encode(['success' => false, 'message' => 'Contraseña incorrecta.']);
        exit;
    }

    // 2) Verificar documento
    $stmt = $pdo->prepare("SELECT IdEstadoDocumento FROM documentos WHERE IdDocumentos = ?");
    $stmt->execute([$idDoc]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doc) {
        echo json_encode(['success' => false, 'message' => 'Documento no existe.']);
        exit;
    }

    $estadoActual = (int)$doc['IdEstadoDocumento'];

    if ($estadoActual !== 4) { // Solo desbloquear si estaba BLOQUEADO
        echo json_encode(['success' => false, 'message' => "El documento no está bloqueado (estado actual: $estadoActual)."]);
        exit;
    }

    // 3) Actualizar estado a DESBLOQUEADO (6) y guardar fecha de desbloqueo
    $upd = $pdo->prepare("
        UPDATE documentos 
        SET IdEstadoDocumento = 6, FechaDesbloqueo = NOW()
        WHERE IdDocumentos = ?
    ");
    $upd->execute([$idDoc]);
    $filas = $upd->rowCount();

    if ($filas === 0) {
        // Confirmar si realmente cambió
        $verif = $pdo->prepare("SELECT IdEstadoDocumento FROM documentos WHERE IdDocumentos = ?");
        $verif->execute([$idDoc]);
        $nuevoEstado = (int)$verif->fetchColumn();
    } else {
        $nuevoEstado = 6;
    }

    // 4) Registrar observación
    try {
        $obs = $pdo->prepare("
            INSERT INTO movimientodocumento (IdDocumentos, Observacion, FechaMovimiento)
            VALUES (?, ?, NOW())
        ");
        $obs->execute([$idDoc, 'Desbloqueado por ' . $user['Usuario'] . ' (estado → 6)']);
    } catch (Exception $e) {
        error_log('Error al registrar observación: ' . $e->getMessage());
    }

    echo json_encode([
        'success' => true,
        'message' => 'Documento desbloqueado correctamente.',
        'nuevo_estado' => $nuevoEstado
    ]);
} catch (Exception $e) {
    error_log('Desbloquear error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno en el servidor.']);
}
