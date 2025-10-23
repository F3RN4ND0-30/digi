<?php
// backend/php/desbloquear_documento.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['dg_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesión expirada. Vuelve a iniciar sesión.']);
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

// Input
$accion   = $_POST['accion'] ?? '';
$idDoc    = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$password = $_POST['password'] ?? '';
// Por si quieres restaurar a SEGUIMIENTO (2), puedes enviar estado_destino=2 desde el JS.
// Por defecto lo dejamos en 1 = NUEVO (pendiente).
$estadoDestino = isset($_POST['estado_destino']) ? (int)$_POST['estado_destino'] : 1;

if ($accion !== 'desbloquear' || $idDoc <= 0) {
    echo json_encode(['success' => false, 'message' => 'Parámetros inválidos.']);
    exit;
}

if ($password === '') {
    echo json_encode(['success' => false, 'message' => 'Debes ingresar tu contraseña.']);
    exit;
}

try {
    // 1) Obtener hash de contraseña del usuario actual
    $stmt = $pdo->prepare("SELECT Usuario, Password FROM usuarios WHERE IdUsuarios = ?");
    $stmt->execute([$_SESSION['dg_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado.']);
        exit;
    }

    $hash = (string)$user['Password'];

    // Compatibilidad: si está hasheado con password_hash -> password_verify;
    // si no, comparar plano (no recomendado, pero por si ya existe así en tu BD).
    $isBcrypt = preg_match('/^\$2[ayb]\$/', $hash) === 1;
    $okPass = $isBcrypt ? password_verify($password, $hash) : hash_equals($hash, $password);

    if (!$okPass) {
        echo json_encode(['success' => false, 'message' => 'Contraseña incorrecta.']);
        exit;
    }

    // 2) Verificar documento y que esté bloqueado
    $stmt = $pdo->prepare("SELECT IdEstadoDocumento FROM documentos WHERE IdDocumentos = ?");
    $stmt->execute([$idDoc]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doc) {
        echo json_encode(['success' => false, 'message' => 'Documento no existe.']);
        exit;
    }

    // Solo desbloquea si está en 4 (BLOQUEADO)
    if ((int)$doc['IdEstadoDocumento'] !== 4) {
        echo json_encode(['success' => false, 'message' => 'El documento no está bloqueado.']);
        exit;
    }

    // 3) Actualizar estado
    $upd = $pdo->prepare("UPDATE documentos SET IdEstadoDocumento = ? WHERE IdDocumentos = ?");
    $upd->execute([$estadoDestino, $idDoc]);

    if ($upd->rowCount() < 1) {
        echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el estado.']);
        exit;
    }

    // (Opcional) Registrar una observación del desbloqueo
    // Solo si tu tabla permite nulos en AreaOrigen/AreaDestino; si no, comenta este bloque.
    try {
        $obs = $pdo->prepare("
            INSERT INTO movimientodocumento (IdDocumentos, Observacion, FechaMovimiento)
            VALUES (?, ?, NOW())
        ");
        $obs->execute([$idDoc, 'Desbloqueado por ' . $user['Usuario'] . ' (cambio a estado ' . $estadoDestino . ')']);
    } catch (Exception $e) {
        // No interrumpir si falla el log de observación
    }

    echo json_encode([
        'success' => true,
        'message' => 'Documento desbloqueado correctamente.',
        'nuevo_estado' => $estadoDestino
    ]);
} catch (Exception $e) {
    error_log('Desbloquear error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error en el servidor.']);
}
