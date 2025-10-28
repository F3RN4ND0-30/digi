<?php
// 🔧 Mostrar errores (solo en desarrollo)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 🧠 Iniciar sesión
session_start();
require '../../db/conexion.php';

// 🧾 Obtener datos de sesión
$area_id = $_SESSION['dg_area_id'] ?? null;
$rol_id = $_SESSION['dg_rol'] ?? null;
if (!$rol_id) {
    echo json_encode([]);
    exit;
}

// 🔍 Obtener término de búsqueda
$busqueda = $_GET['busqueda'] ?? '';
$like = "%$busqueda%";

// 📦 ADMIN (IdRol = 1): puede ver todo
if ($rol_id == 1) {
    $sql = "
        SELECT 
            md1.IdDocumentos,
            d.NumeroDocumento,
            d.Asunto,
            d.Finalizado,                            -- ✅ Agregado
            md1.AreaDestino,
            a.Nombre AS NombreAreaDestino,
            md1.FechaMovimiento,
            md1.Recibido
        FROM movimientodocumento md1
        LEFT JOIN areas a ON md1.AreaDestino = a.IdAreas
        INNER JOIN (
            SELECT IdDocumentos, MAX(FechaMovimiento) AS MaxFecha
            FROM movimientodocumento
            GROUP BY IdDocumentos
        ) ult ON md1.IdDocumentos = ult.IdDocumentos AND md1.FechaMovimiento = ult.MaxFecha
        INNER JOIN documentos d ON d.IdDocumentos = md1.IdDocumentos
        WHERE d.NumeroDocumento LIKE :busqueda OR d.Asunto LIKE :busqueda
        ORDER BY md1.FechaMovimiento DESC
        LIMIT 100
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':busqueda', $like);
} else {
    // 👤 Usuarios comunes: solo documentos relacionados a su área
    if (!$area_id) {
        echo json_encode([]);
        exit;
    }

    $sql = "
        SELECT 
            md1.IdDocumentos,
            d.NumeroDocumento,
            d.Asunto,
            d.Finalizado,                            -- ✅ Agregado
            md1.AreaDestino,
            a.Nombre AS NombreAreaDestino,
            md1.FechaMovimiento,
            md1.Recibido
        FROM movimientodocumento md1
        LEFT JOIN areas a ON md1.AreaDestino = a.IdAreas
        INNER JOIN (
            SELECT IdDocumentos, MAX(FechaMovimiento) AS MaxFecha
            FROM movimientodocumento
            GROUP BY IdDocumentos
        ) ult ON md1.IdDocumentos = ult.IdDocumentos AND md1.FechaMovimiento = ult.MaxFecha
        INNER JOIN documentos d ON d.IdDocumentos = md1.IdDocumentos
        WHERE md1.IdDocumentos IN (
            SELECT DISTINCT IdDocumentos
            FROM movimientodocumento
            WHERE AreaOrigen = :area_id OR AreaDestino = :area_id
        )
        AND (d.NumeroDocumento LIKE :busqueda OR d.Asunto LIKE :busqueda)
        ORDER BY md1.FechaMovimiento DESC
        LIMIT 100
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':busqueda', $like);
    $stmt->bindParam(':area_id', $area_id);
}

// ✅ Ejecutar y devolver resultados
$stmt->execute();
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($resultados);
