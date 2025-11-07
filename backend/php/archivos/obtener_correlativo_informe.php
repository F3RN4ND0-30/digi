<?php
session_start();
require '../../db/conexion.php';

if (!isset($_GET['area'])) {
    echo json_encode(['correlativo' => 1, 'año' => date('Y')]);
    exit;
}

$id_area = intval($_GET['area']);
$año_actual = date('Y');

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM informes WHERE IdArea = ? AND Año = ?");
$stmt->execute([$id_area, $año_actual]);
$total = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

$correlativo = $total + 1;

echo json_encode(['correlativo' => $correlativo, 'año' => $año_actual]);
