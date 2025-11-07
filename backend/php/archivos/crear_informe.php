<?php
session_start();
require '../../db/conexion.php';
header('Content-Type: application/json');

if (!isset($_POST['id_documento'], $_POST['id_area'], $_POST['titulo'])) {
    echo json_encode(['status' => 'error', 'message' => 'Faltan datos']);
    exit;
}

$id_documento = intval($_POST['id_documento']);
$id_area = intval($_POST['id_area']);
$titulo = trim($_POST['titulo']);
$año_actual = date('Y');

try {
    // Iniciar transacción
    $pdo->beginTransaction();

    // Bloquear filas existentes para calcular correlativo seguro
    $stmt = $pdo->prepare("
        SELECT MAX(Correlativo) AS max_correlativo
        FROM informes
        WHERE IdArea = ? AND Año = ?
        FOR UPDATE
    ");
    $stmt->execute([$id_area, $año_actual]);
    $max_correlativo = $stmt->fetchColumn();
    $correlativo = intval($max_correlativo) + 1;

    // Generar nombre final
    $nombre_final = "INFORME N°.{$correlativo}-{$año_actual}-{$titulo}";

    // Insertar informe
    $stmt = $pdo->prepare("
        INSERT INTO informes (NombreInforme, IdDocumento, IdArea, FechaEmision, Año, Asunto, Correlativo)
        VALUES (?, ?, ?, NOW(), ?, ?, ?)
    ");
    $stmt->execute([$nombre_final, $id_documento, $id_area, $año_actual, $titulo, $correlativo]);

    // Commit
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
