<?php
// login.php
session_start();
if (isset($_SESSION['usuario'])) {
    header('Location: digi/frontend/sisvis/escritorio.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>digi - Iniciar Sesión</title>
    <link rel="stylesheet" href="css/styles.css">
</head>

<body>
    <h2>digi - Ingreso de Usuario</h2>

    <?php if (isset($_GET['error'])): ?>
        <p style="color: red;"><?php echo htmlspecialchars($_GET['error']); ?></p>
    <?php endif; ?>

    <form action="../backend/procesar_login.php" method="post">
        <label for="usuario">Usuario:</label><br>
        <input type="text" name="usuario" required><br><br>

        <label for="clave">Contraseña:</label><br>
        <input type="password" name="clave" required><br><br>

        <button type="submit">Iniciar Sesión</button>
    </form>
</body>

</html>