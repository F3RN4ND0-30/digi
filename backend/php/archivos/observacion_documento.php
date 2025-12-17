<?php
session_start();
if (!isset($_SESSION['dg_id'])) {
    header("Location: ../../../frontend/login.php");
    exit();
}

require '../../db/conexion.php';

/* =========================
   DATOS DE SESIÓN
========================= */
$area_usuario = $_SESSION['dg_area_id'] ?? null;
$rol_usuario = $_SESSION['dg_rol'] ?? null;

if (!$rol_usuario) {
    die("❌ No se pudo determinar el rol del usuario.");
}

/* =========================
   DATOS DEL FORM
========================= */
$documento_id  = $_POST['id_documento'] ?? null;
$numero_folios = $_POST['numero_folios'] ?? null;
$id_informe    = $_POST['id_informe'] ?? null;

if (!$documento_id || !$area_usuario) {
    die("❌ Datos inválidos.");
}

$documento_id  = intval($documento_id);
$numero_folios = $numero_folios ? intval($numero_folios) : null;
$id_informe    = $id_informe ? intval($id_informe) : null;

$observacion   = trim($_POST['observacion'] ?? '');

$id_carta = null;

try {

    /* =========================
       OBTENER DOCUMENTO
    ========================= */
    $stmt = $pdo->prepare("
        SELECT IdAreaFinal, Finalizado, NumeroFolios 
        FROM documentos 
        WHERE IdDocumentos = ?
    ");
    $stmt->execute([$documento_id]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doc) {
        throw new Exception("Documento no encontrado.");
    }

    if ((int)$doc['IdAreaFinal'] !== (int)$area_usuario) {
        throw new Exception("No tienes permiso para observar este documento.");
    }

    if ($doc['Finalizado']) {
        throw new Exception("El documento ya está finalizado.");
    }

    if (!$numero_folios) {
        $numero_folios = $doc['NumeroFolios'];
    }

    /* =========================
       INICIAR TRANSACCIÓN
    ========================= */
    $pdo->beginTransaction();

    /* =========================
       SI ROL 1 O 5 → CREAR CARTA
    ========================= */
    if (in_array($rol_usuario, [1, 5])) {

        $año_actual = date('Y');

        // Obtener abreviatura del área
        $stmt = $pdo->prepare("SELECT Abreviatura FROM areas WHERE IdAreas = ?");
        $stmt->execute([$area_usuario]);
        $area_abrev = $stmt->fetchColumn() ?: 'XX';

        // Obtener correlativo (bloqueo seguro)
        $stmt = $pdo->prepare("
            SELECT MAX(Correlativo)
            FROM cartas
            WHERE IdArea = ? AND Año = ?
            FOR UPDATE
        ");
        $stmt->execute([$area_usuario, $año_actual]);
        $correlativo = intval($stmt->fetchColumn()) + 1;

        $correlativo_formateado = str_pad($correlativo, 4, '0', STR_PAD_LEFT);

        $nombre_carta = "CARTA N°.{$correlativo_formateado}-{$año_actual}-MPP-{$area_abrev}";

        // Insertar carta
        $stmt = $pdo->prepare("
            INSERT INTO cartas
            (NombreCarta, IdDocumento, IdArea, FechaEmision, Año, Asunto, Correlativo)
            VALUES (?, ?, ?, NOW(), ?, ?, ?)
        ");
        $stmt->execute([
            $nombre_carta,
            $documento_id,
            $area_usuario,
            $año_actual,
            $nombre_carta,
            $correlativo_formateado
        ]);

        $id_carta   = $pdo->lastInsertId();
        $id_informe = null; // 🔴 nunca ambos
    }

    /* =========================
       FINALIZAR DOCUMENTO
    ========================= */
    $stmtUpdate = $pdo->prepare("
        UPDATE documentos
        SET Finalizado = 1,
            IdEstadoDocumento = 8,
            NumeroFolios = ?
        WHERE IdDocumentos = ?
    ");
    $stmtUpdate->execute([$numero_folios, $documento_id]);

    /* =========================
       INSERT MOVIMIENTO
    ========================= */
    $stmtInsert = $pdo->prepare("
        INSERT INTO movimientodocumento
        (IdDocumentos, AreaOrigen, AreaDestino, FechaMovimiento, Observacion,
         Recibido, NumeroFolios, IdInforme, IdCarta)
        VALUES (?, ?, ?, NOW(), ?, 1, ?, ?, ?)
    ");

    $stmtInsert->execute([
        $documento_id,
        $area_usuario,
        $area_usuario,
        $observacion,
        $numero_folios,
        $id_informe,
        $id_carta
    ]);

    /* =========================
       COMMIT
    ========================= */
    $pdo->commit();

    if ($id_carta) {
        header("Location: ../../../frontend/archivos/reenviar.php?sw=carta&nombre=" . urlencode($nombre_carta));
    } else {
        header("Location: ../../../frontend/archivos/reenviar.php?sw=observado");
    }
    exit();
} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    die("❌ Error al observar el documento: " . $e->getMessage());
}
