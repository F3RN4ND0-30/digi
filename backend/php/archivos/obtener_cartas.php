<?php
session_start();
require '../../db/conexion.php';

if (!isset($_GET['id_documento'])) {
    echo json_encode([]);
    exit;
}

$id_documento = intval($_GET['id_documento']);
$id_area = $_SESSION['dg_area_id'];

// Obtener solo cartas de esta área
$stmt = $pdo->prepare("
    SELECT IdCarta, NombreCarta 
    FROM cartas 
    WHERE IdDocumento = ? AND IdArea = ? 
    ORDER BY FechaEmision DESC
");
$stmt->execute([$id_documento, $id_area]);
$cartas = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($cartas);
