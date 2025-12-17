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
    $stmt = $pdo->prepare("SELECT MAX(Correlativo) AS max_correlativo FROM cartas WHERE IdArea = ? AND Año = ? FOR UPDATE");
    $stmt->execute([$id_area, $año_actual]);
    $max_correlativo = $stmt->fetchColumn();
    $correlativo = intval($max_correlativo) + 1;

    // Convertir a 3 dígitos: 001, 002, 003...
    $correlativo_formateado = str_pad($correlativo, 4, '0', STR_PAD_LEFT);

    // Generar título
    $titulo_final = "CARTA N°.{$correlativo_formateado}-{$año_actual}-MPP-{$area_abrev}";

    // Insertar en cartas
    $stmt = $pdo->prepare("
        INSERT INTO cartas (NombreCarta, IdDocumento, IdMemo, IdArea, FechaEmision, Año, Asunto, Correlativo)
        VALUES (?, ?, ?, ?, NOW(), ?, ?, ?)
    ");
    $stmt->execute([
        $titulo_final,
        $id_documento ?: null,
        $id_memo ?: null,
        $id_area,
        $año_actual,
        $titulo_final,
        $correlativo_formateado
    ]);

    // Commit de la transacción
    $pdo->commit();

    // Obtener el id de la nueva carta (usando lastInsertId)
    $id_carta = $pdo->lastInsertId();

    // Debug: Verificar el id_carta obtenido
    error_log("ID de la carta creada (lastInsertId): $id_carta");

    // Si el id_carta es 0, intentar con un SELECT adicional para obtenerlo directamente de la base de datos
    if ($id_carta == 0) {
        error_log("lastInsertId() devolvió 0, intentando con un SELECT adicional...");
        $stmt = $pdo->prepare("SELECT IdCarta FROM cartas WHERE NombreCarta = ?");
        $stmt->execute([$titulo_final]);
        $row = $stmt->fetch();
        $id_carta = $row['IdCarta'] ?? 0;
        error_log("ID da la carta obtenida con SELECT adicional: $id_carta");
    }

    // Devolver respuesta al frontend
    echo json_encode([
        'status' => 'success',
        'correlativo' => $correlativo_formateado,
        'nombre_final' => $titulo_final,
        'id_carta' => $id_carta
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();

    // Error en la ejecución
    error_log("Error en la creación de la carta: " . $e->getMessage());

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
