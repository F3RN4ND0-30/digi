<?php
session_start();
require '../../db/conexion.php';
header('Content-Type: application/json');

$id_documento = $_POST['id_documento'] ?? null;
$id_memo = $_POST['id_memo'] ?? null;
$id_area = $_POST['id_area'] ?? null;
$titulo = trim($_POST['titulo'] ?? '');

if ((!$id_documento && !$id_memo) || !$id_area || !$titulo) {
    echo json_encode(['status' => 'error', 'message' => 'Faltan datos']);
    exit;
}

// Validar documento o memo
try {
    if ($id_documento) {
        $stmt = $pdo->prepare("SELECT * FROM documentos WHERE IdDocumentos = ?");
        $stmt->execute([$id_documento]);
        $doc = $stmt->fetch();
        if (!$doc) throw new Exception("El documento no existe.");
    } elseif ($id_memo) {
        $stmt = $pdo->prepare("SELECT * FROM memorandums WHERE IdMemo = ?");
        $stmt->execute([$id_memo]);
        $memo = $stmt->fetch();
        if (!$memo) throw new Exception("El memorandum no existe.");
    }

    // Aquí sigue tu lógica de generar correlativo, nombre final e insertar
    $año_actual = date('Y');
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT MAX(Correlativo) AS max_correlativo FROM informes WHERE IdArea = ? AND Año = ? FOR UPDATE");
    $stmt->execute([$id_area, $año_actual]);
    $max_correlativo = $stmt->fetchColumn();
    $correlativo = intval($max_correlativo) + 1;

    $nombre_final = "INFORME N°.{$correlativo}-{$año_actual}-{$titulo}";

    $stmt = $pdo->prepare("
        INSERT INTO informes (NombreInforme, IdDocumento, IdMemo, IdArea, FechaEmision, Año, Asunto, Correlativo)
        VALUES (?, ?, ?, ?, NOW(), ?, ?, ?)
    ");
    $stmt->execute([
        $nombre_final,
        $id_documento ?: null,
        $id_memo ?: null,
        $id_area,
        $año_actual,
        $titulo,
        $correlativo
    ]);

    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'correlativo' => $correlativo,
        'nombre_final' => $nombre_final,
        'id_informe' => $pdo->lastInsertId()
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
