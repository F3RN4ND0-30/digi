<?php
session_start();
require '../../db/conexion.php';

$id = $_POST['id'] ?? null;
$accion = $_POST['accion'] ?? null;

if (!$id || !$accion) {
    http_response_code(400);
    echo 'Datos inválidos';
    exit;
}

$estado = $accion === 'eliminar' ? 'eliminada' : 'vista';

$stmt = $pdo->prepare("UPDATE notificaciones SET Estado = :estado, FechaVisto = NOW() WHERE IdNotificacion = :id");
$stmt->execute([
    'estado' => $estado,
    'id' => $id
]);

echo 'ok';
