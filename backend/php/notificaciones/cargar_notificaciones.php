<?php
session_start();
require '../../db/conexion.php';

$area_id = $_SESSION['dg_area_id'] ?? null;
if (!$area_id) {
    echo json_encode([]);
    exit;
}

// Solo traer notificaciones NUEVAS
$sql = "SELECT IdNotificacion, Mensaje, FechaVisto 
        FROM notificaciones 
        WHERE IdAreas = :area AND Estado = 'nueva'
        ORDER BY FechaVisto DESC
        LIMIT 20";

$stmt = $pdo->prepare($sql);
$stmt->execute(['area' => $area_id]);

$notificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($notificaciones);
