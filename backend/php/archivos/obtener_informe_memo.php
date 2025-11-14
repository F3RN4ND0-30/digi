<?php
session_start();
require '../../db/conexion.php';

if (!isset($_GET['id_memo'])) {
    echo json_encode([]);
    exit;
}

$id_memo = intval($_GET['id_memo']);
$id_area = $_SESSION['dg_area_id'];

// Obtener informes asignados al memo y creados por mi área
$stmt = $pdo->prepare("
    SELECT IdInforme, NombreInforme 
    FROM informes 
    WHERE IdMemo = ? AND IdArea = ? 
    ORDER BY FechaEmision DESC
");
$stmt->execute([$id_memo, $id_area]);

$informes = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($informes);
