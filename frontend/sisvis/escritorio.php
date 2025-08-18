<?php
// dashboard.php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: ../login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestidoc - Panel</title>
</head>

<body>
    <h1>Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre']); ?> (<?php echo $_SESSION['area']; ?>)</h1>

    <p><a href="../logout.php">Cerrar sesión</a></p>

    <!-- Aquí irá el listado de documentos, derivaciones, etc. -->
</body>

</html>