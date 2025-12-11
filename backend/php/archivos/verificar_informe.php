<?php
require '../../db/conexion.php';

$id_documento = $_GET['id_documento'] ?? null;

if (!$id_documento) {
    echo json_encode(['existe_informe' => false]);
    exit;
}

// Consultar si ya existe un informe para este documento
$stmt = $pdo->prepare("SELECT COUNT(*) FROM informes WHERE IdDocumento = ?");
$stmt->execute([$id_documento]);
$existe_informe = $stmt->fetchColumn() > 0;

echo json_encode(['existe_informe' => $existe_informe]);
