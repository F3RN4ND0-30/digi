<?php
session_start();
if (!isset($_SESSION['dg_id'])) {
    header("Location: ../../../frontend/login.php");
    exit();
}

require '../../db/conexion.php';

$documento_id = $_POST['id_documento'] ?? null;
$usuario_actual = $_SESSION['dg_id'];

if (!$documento_id || !$usuario_actual) {
    die("❌ Datos inválidos.");
}

// Verificar si el usuario actual es el creador del documento
$consulta = $pdo->prepare("SELECT IdUsuarios FROM documentos WHERE IdDocumentos = ?");
$consulta->execute([$documento_id]);
$creador = $consulta->fetchColumn();

if ($creador != $usuario_actual) {
    die("❌ No tienes permiso para finalizar este documento.");
}

// Actualizar el campo Finalizado
$stmt = $pdo->prepare("UPDATE documentos SET Finalizado = 1 WHERE IdDocumentos = ?");
$stmt->execute([$documento_id]);

header("Location: ../../../frontend/archivos/reenviar.php");
exit;
