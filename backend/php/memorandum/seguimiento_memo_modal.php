<?php
session_start();
require '../../db/conexion.php';

header('Content-Type: application/json; charset=utf-8');

$id_memo = $_GET['id_memo'] ?? null;
if (!$id_memo) {
    echo json_encode(['error' => 'ID de memorándum no especificado.']);
    exit;
}

try {
    // Traer el origen del memorándum y su fecha
    $stmt = $pdo->prepare("
        SELECT m.IdMemo, m.IdAreaOrigen, m.FechaEmision, a_origen.Nombre AS AreaOrigen
        FROM memorandums m
        LEFT JOIN areas a_origen ON m.IdAreaOrigen = a_origen.IdAreas
        WHERE m.IdMemo = :id_memo
    ");
    $stmt->execute([':id_memo' => $id_memo]);
    $memo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$memo) {
        echo json_encode(['error' => 'Memorándum no encontrado.']);
        exit;
    }

    // Traer destinos
    $stmt2 = $pdo->prepare("
        SELECT md.IdMemoDestino, md.Recibido, a_dest.Nombre AS AreaDestino
        FROM memorandum_destinos md
        LEFT JOIN areas a_dest ON md.IdAreaDestino = a_dest.IdAreas
        WHERE md.IdMemo = :id_memo
    ");
    $stmt2->execute([':id_memo' => $id_memo]);
    $destinos = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    // Construir array de movimientos
    $movimientos = [];
    foreach ($destinos as $dest) {
        $movimientos[] = [
            'AreaOrigen'   => $memo['AreaOrigen'],
            'AreaDestino'  => $dest['AreaDestino'] ?? $dest['IdAreaDestino'],
            'FechaEmision' => $memo['FechaEmision'],
            'Recibido'     => $dest['Recibido']
        ];
    }

    echo json_encode(['movimientos' => $movimientos]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
