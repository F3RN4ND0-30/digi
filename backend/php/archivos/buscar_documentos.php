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

// Preparamos la consulta para buscar en documentos y memorándums
$sql = "
    (SELECT IdDocumentos AS Id, NumeroDocumento, Asunto
    FROM documentos
    WHERE UPPER(NumeroDocumento) LIKE ? OR UPPER(Asunto) LIKE ?)
    
    UNION
    
    (SELECT IdMemo AS Id, CodigoMemo AS NumeroDocumento, Asunto AS Asunto
    FROM memorandums
    WHERE UPPER(CodigoMemo) LIKE ? OR UPPER(Asunto) LIKE ?)
    
    ORDER BY NumeroDocumento DESC
    LIMIT 10
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    "%$query_upper%",
    "%$query_upper%",  // Para buscar en documentos
    "%$query_upper%",
    "%$query_upper%"   // Para buscar en memorándums
]);

// Fetch de los resultados
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Retornamos en formato JSON
echo json_encode($resultados);
