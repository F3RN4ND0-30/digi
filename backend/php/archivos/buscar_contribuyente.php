<?php
require '../../db/conexion.php';

if (!isset($_GET['documento'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No se proporcionó documento']);
    exit;
}

$documento = trim($_GET['documento']);

if (!preg_match('/^\d{8}$|^\d{11}$/', $documento)) {
    http_response_code(400);
    echo json_encode(['error' => 'Formato inválido']);
    exit;
}

// Verificar si ya hay un documento con ese DNI/RUC guardado
$stmt = $pdo->prepare("SELECT NombreContribuyente FROM documentos WHERE DniRuc = ? ORDER BY IdDocumentos DESC LIMIT 1");
$stmt->execute([$documento]);
$nombre = $stmt->fetchColumn();

if ($nombre) {
    echo json_encode(['nombre' => $nombre]);
} else {
    echo json_encode(['nombre' => '']); // No encontrado
}
