<?php
session_start();
require '../../db/conexion.php';
header('Content-Type: application/json');

// Obtener datos desde POST y limpiar espacios
$id_documento = isset($_POST['id_documento']) ? trim($_POST['id_documento']) : null;
$id_memo = isset($_POST['id_memo']) ? trim($_POST['id_memo']) : null;
$id_area = $_SESSION['dg_area_id'] ?? null;

// Debug: Imprimir los datos recibidos
error_log("Datos recibidos: id_documento = $id_documento, id_memo = $id_memo, id_area = $id_area");

if (!$id_area) {
    echo json_encode(['status' => 'error', 'message' => 'Área no definida']);
    exit;
}

// Obtener abreviatura del área
$stmt = $pdo->prepare("SELECT Abreviatura FROM areas WHERE IdAreas = ?");
$stmt->execute([$id_area]);
$area_row = $stmt->fetch();
$area_abrev = $area_row['Abreviatura'] ?? 'XX';

// Debug: Imprimir abreviatura del área
error_log("Abreviatura del área: $area_abrev");

try {
    // Validar documento o memo
    if ($id_documento) {
        $stmt = $pdo->prepare("SELECT * FROM documentos WHERE IdDocumentos = ?");
        $stmt->execute([$id_documento]);
        $doc = $stmt->fetch();
        if (!$doc) throw new Exception("El documento no existe.");
    } elseif ($id_memo) {
        $stmt = $pdo->prepare("SELECT * FROM memorandums WHERE IdMemo = ?");
        $stmt->execute([$id_memo]);
        $memo = $stmt->fetch();
        if (!$memo) throw new Exception("El memorándum no existe.");
    } else {
        throw new Exception("Falta id_documento o id_memo.");
    }

    $año_actual = date('Y');

    // Debug: Imprimir el año actual
    error_log("Año actual: $año_actual");

    // Iniciar transacción
    $pdo->beginTransaction();

    // Obtener correlativo
    $stmt = $pdo->prepare("SELECT MAX(Correlativo) AS max_correlativo FROM informes WHERE IdArea = ? AND Año = ? FOR UPDATE");
    $stmt->execute([$id_area, $año_actual]);
    $max_correlativo = $stmt->fetchColumn();
    $correlativo = intval($max_correlativo) + 1;

    // Debug: Imprimir el correlativo calculado
    error_log("Correlativo calculado: $correlativo");

    // Generar título
    $titulo_final = "INFORME N°.{$correlativo}-{$año_actual}-MPP-{$area_abrev}";

    // Debug: Imprimir el título final
    error_log("Título final generado: $titulo_final");

    // Insertar en informes
    $stmt = $pdo->prepare("
        INSERT INTO informes (NombreInforme, IdDocumento, IdMemo, IdArea, FechaEmision, Año, Asunto, Correlativo)
        VALUES (?, ?, ?, ?, NOW(), ?, ?, ?)
    ");
    $stmt->execute([
        $titulo_final,
        $id_documento ?: null,
        $id_memo ?: null,
        $id_area,
        $año_actual,
        $titulo_final,
        $correlativo
    ]);

    // Commit de la transacción
    $pdo->commit();

    // Obtener el id del nuevo informe (usando lastInsertId)
    $id_informe = $pdo->lastInsertId();

    // Debug: Verificar el id_informe obtenido
    error_log("ID del informe creado (lastInsertId): $id_informe");

    // Si el id_informe es 0, intentar con un SELECT adicional para obtenerlo directamente de la base de datos
    if ($id_informe == 0) {
        error_log("lastInsertId() devolvió 0, intentando con un SELECT adicional...");
        $stmt = $pdo->prepare("SELECT IdInforme FROM informes WHERE NombreInforme = ?");
        $stmt->execute([$titulo_final]);
        $row = $stmt->fetch();
        $id_informe = $row['IdInforme'] ?? 0;
        error_log("ID del informe obtenido con SELECT adicional: $id_informe");
    }

    // Devolver respuesta al frontend
    echo json_encode([
        'status' => 'success',
        'correlativo' => $correlativo,
        'nombre_final' => $titulo_final,
        'id_informe' => $id_informe
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();

    // Error en la ejecución
    error_log("Error en la creación del informe: " . $e->getMessage());

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
