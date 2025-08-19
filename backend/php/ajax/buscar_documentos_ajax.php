<?php
// 🔧 Mostrar errores (solo en desarrollo)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 🧠 Iniciar sesión y obtener el área
session_start();
require '../../db/conexion.php';

$area_id = $_SESSION['dg_area_id'] ?? null;
if (!$area_id) {
    echo json_encode([]);
    exit;
}

// 📌 Obtener término de búsqueda
$busqueda = $_GET['busqueda'] ?? '';

// 📦 Consulta SQL optimizada
$sql = "
    SELECT 
        md1.IdDocumentos,
        d.NumeroDocumento,
        d.Asunto,
        md1.AreaDestino,
        md1.FechaMovimiento,
        md1.Recibido
    FROM movimientodocumento md1
    INNER JOIN (
        SELECT IdDocumentos, MAX(FechaMovimiento) AS MaxFecha
        FROM movimientodocumento
        GROUP BY IdDocumentos
    ) ult ON md1.IdDocumentos = ult.IdDocumentos AND md1.FechaMovimiento = ult.MaxFecha
    INNER JOIN documentos d ON d.IdDocumentos = md1.IdDocumentos
    WHERE md1.IdDocumentos IN (
        SELECT DISTINCT IdDocumentos
        FROM movimientodocumento
        WHERE AreaOrigen = :area_id
    )
    AND (d.NumeroDocumento LIKE :busqueda OR d.Asunto LIKE :busqueda)
    ORDER BY md1.FechaMovimiento DESC
    LIMIT 100
";

// ⚙️ Preparar y ejecutar
$stmt = $pdo->prepare($sql);
$like = "%$busqueda%";
$stmt->bindParam(':busqueda', $like);
$stmt->bindParam(':area_id', $area_id);
$stmt->execute();

// ✅ Devolver resultados en JSON
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
header('Content-Type: application/json');
echo json_encode($resultados);
