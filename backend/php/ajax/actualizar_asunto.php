<?php
session_start();
require '../../db/conexion.php';

header('Content-Type: application/json; charset=utf-8');

$id = $_POST['id'];
$tipo = $_POST['tipo'];
$asunto = $_POST['asunto'];

if ($tipo === 'DOC') {
    $sql = "UPDATE documentos d
INNER JOIN movimientodocumento m
ON m.IdDocumentos = d.IdDocumentos
SET d.Asunto = :asunto
WHERE m.IdMovimientoDocumento = :id";
} else {
    $sql = "UPDATE memorandums
SET Asunto = :asunto
WHERE IdMemo = :id";
}

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':asunto' => $asunto,
    ':id' => $id
]);

echo json_encode(['success' => true]);
