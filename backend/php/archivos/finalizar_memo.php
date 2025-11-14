<?php
session_start();
if (!isset($_SESSION['dg_id'])) {
    header("Location: ../../frontend/login.php");
    exit();
}

require '../../db/conexion.php';

/*
 Finaliza un MEMORÁNDUM:
 - Verifica que el memo exista.
 - Verifica que esté recepcionado (Recibido = 1) en el área del usuario.
 - Cambia estado del memo a FINALIZADO (IdEstadoDocumento = 7).
 - Marca la fila de memorandum_destinos del área actual como cerrada (Recibido = 2),
   para que desaparezca de la bandeja.
 - Devuelve flash y redirige a reenviar.php.
*/

$area_usuario = $_SESSION['dg_area_id'] ?? null;
$id_memo = isset($_POST['id_memo']) ? (int)$_POST['id_memo'] : 0;

if (!$area_usuario || $id_memo <= 0) {
    $_SESSION['flash_type'] = 'error';
    $_SESSION['flash_text'] = '❌ Datos inválidos para finalizar el memorándum.';
    header("Location: ../../../frontend/archivos/reenviar.php");
    exit;
}

try {
    // 1) Verificar existencia del memo
    $qMemo = $pdo->prepare("SELECT IdMemo, IdEstadoDocumento FROM memorandums WHERE IdMemo = ?");
    $qMemo->execute([$id_memo]);
    $memo = $qMemo->fetch(PDO::FETCH_ASSOC);

    if (!$memo) {
        throw new Exception("❌ Memorándum no encontrado.");
    }

    // Si ya está finalizado, corta
    if ((int)$memo['IdEstadoDocumento'] === 7) {
        $_SESSION['flash_type'] = 'success';
        $_SESSION['flash_text'] = 'ℹ️ El memorándum ya estaba finalizado.';
        header("Location: ../../../frontend/archivos/reenviar.php");
        exit;
    }

    // 2) Verificar que esté recepcionado en MI área (bandeja actual)
    $qRec = $pdo->prepare("
        SELECT IdMemoDestino
        FROM memorandum_destinos
        WHERE IdMemo = ? AND IdAreaDestino = ? AND Recibido = 1
        LIMIT 1
    ");
    $qRec->execute([$id_memo, $area_usuario]);
    $dest = $qRec->fetch(PDO::FETCH_ASSOC);

    if (!$dest) {
        throw new Exception("❌ El memorándum no está recepcionado en tu área o ya fue gestionado.");
    }

    // 3) Finalizar: estado = 7
    $updMemo = $pdo->prepare("UPDATE memorandums SET IdEstadoDocumento = 7 WHERE IdMemo = ?");
    $updMemo->execute([$id_memo]);

    // 4) Cerrar mi asignación de bandeja (Recibido = 2)
    $updDest = $pdo->prepare("
        UPDATE memorandum_destinos
        SET Recibido = 2
        WHERE IdMemo = ? AND IdAreaDestino = ? AND Recibido = 1
        LIMIT 1
    ");
    $updDest->execute([$id_memo, $area_usuario]);

    $_SESSION['flash_type'] = 'success';
    $_SESSION['flash_text'] = '✅ Memorándum finalizado correctamente.';
    header("Location: ../../../frontend/archivos/reenviar.php");
    exit;
} catch (Exception $e) {
    $_SESSION['flash_type'] = 'error';
    $_SESSION['flash_text'] = $e->getMessage();
    header("Location: ../../../frontend/archivos/reenviar.php");
    exit;
}
