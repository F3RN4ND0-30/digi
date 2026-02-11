<?php
require '../../db/conexion.php';

$term = trim($_GET['q'] ?? '');

$sql = "
    SELECT IdDocumentos, NumeroDocumento, Asunto, FechaIngreso
    FROM documentos
    WHERE NumeroDocumento LIKE ?
    ORDER BY FechaIngreso DESC
    LIMIT 20
";

$stmt = $pdo->prepare($sql);
$stmt->execute(["%$term%"]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
