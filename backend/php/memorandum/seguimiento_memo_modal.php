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

    /* =====================================================
       1️⃣ DATOS PRINCIPALES DEL MEMORÁNDUM
       ===================================================== */
    $stmt = $pdo->prepare("
        SELECT 
            m.IdMemo,
            m.IdAreaOrigen,
            m.FechaEmision,
            a_origen.Nombre AS AreaOrigen
        FROM memorandums m
        LEFT JOIN areas a_origen 
            ON m.IdAreaOrigen = a_origen.IdAreas
        WHERE m.IdMemo = :id_memo
    ");
    $stmt->execute([':id_memo' => $id_memo]);
    $memo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$memo) {
        echo json_encode(['error' => 'Memorándum no encontrado.']);
        exit;
    }

    /* =====================================================
       2️⃣ INFORME ASOCIADO AL MEMORÁNDUM (SI EXISTE)
       ===================================================== */
    $stmtInf = $pdo->prepare("
        SELECT 
            IdInforme,
            NombreInforme,
            FechaEmision
        FROM informes
        WHERE IdMemo = :id_memo
        ORDER BY FechaEmision DESC
        LIMIT 1
    ");
    $stmtInf->execute([':id_memo' => $id_memo]);
    $informe = $stmtInf->fetch(PDO::FETCH_ASSOC);

    /* =====================================================
       3️⃣ DESTINOS / MOVIMIENTOS DEL MEMORÁNDUM
       ===================================================== */
    $stmt2 = $pdo->prepare("
        SELECT 
            md.IdMemoDestino,
            md.Recibido,
            a_dest.Nombre AS AreaDestino
        FROM memorandum_destinos md
        LEFT JOIN areas a_dest 
            ON md.IdAreaDestino = a_dest.IdAreas
        WHERE md.IdMemo = :id_memo
    ");
    $stmt2->execute([':id_memo' => $id_memo]);
    $destinos = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    /* =====================================================
       4️⃣ ARMAR MOVIMIENTOS
       ===================================================== */
    $movimientos = [];
    foreach ($destinos as $dest) {
        $movimientos[] = [
            'AreaOrigen'   => $memo['AreaOrigen'],
            'AreaDestino'  => $dest['AreaDestino'] ?? '-',
            'FechaEmision' => $memo['FechaEmision'],
            'Recibido'     => $dest['Recibido']
        ];
    }

    /* =====================================================
       5️⃣ RESPUESTA FINAL
       ===================================================== */
    echo json_encode([
        'success'     => true,
        'informe'     => $informe ?: null,
        'movimientos' => $movimientos
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
