<?php
session_start();
require __DIR__ . '/../db/conexion.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$passwordIngresada = $data['password'] ?? '';

// ID del usuario logueado
$idUsuario = $_SESSION['dg_id'] ?? null;

if (!$idUsuario) {
    echo json_encode(['success' => false]);
    exit;
}

// Obtenemos el hash de la contraseña
$stmt = $pdo->prepare("SELECT Clave FROM usuarios WHERE IdUsuarios = :id LIMIT 1");
$stmt->execute(['id' => $idUsuario]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if ($usuario && password_verify($passwordIngresada, $usuario['Clave'])) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
