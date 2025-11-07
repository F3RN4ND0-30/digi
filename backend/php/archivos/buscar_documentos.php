<?php
session_start();
require '../../db/conexion.php';

// Si no viene la query, devolvemos array vacío
if (!isset($_GET['q'])) {
    echo json_encode([]);
    exit;
}

// Limpiamos la query
$query = trim($_GET['q']);
if ($query === '') {
    echo json_encode([]);
    exit;
}

// Convertimos a mayúsculas para búsquedas insensibles a mayúsculas
$query_upper = strtoupper($query);

// Preparamos la consulta
$sql = "SELECT IdDocumentos, NumeroDocumento, Asunto
        FROM documentos
        WHERE UPPER(NumeroDocumento) LIKE ? OR UPPER(Asunto) LIKE ?
        ORDER BY FechaIngreso DESC
        LIMIT 10";

$stmt = $pdo->prepare($sql);
$stmt->execute(["%$query_upper%", "%$query_upper%"]);

$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Retornamos en JSON
echo json_encode($resultados);
