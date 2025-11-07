<?php
session_start();
require '../../db/conexion.php';

if (!isset($_GET['id_documento'])) {
    echo json_encode([]);
    exit;
}

$id_documento = intval($_GET['id_documento']);
$id_area = $_SESSION['dg_area_id'];

// Obtener informes asignados al documento y creados por mi área
$stmt = $pdo->prepare("
    SELECT IdInforme, NombreInforme 
    FROM informes 
    WHERE IdDocumento = ? AND IdArea = ? 
    ORDER BY FechaEmision DESC
");
$stmt->execute([$id_documento, $id_area]);

$informes = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($informes);
