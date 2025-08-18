<?php
// dashboard.php
session_start();
if (!isset($_SESSION['dg_id'])) {
    header('Location: ../login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Escritorio</title>
    <link rel="stylesheet" href="../../backend/css/sisvis/escritorio.css" />
</head>

<body>
    <div class="layout-escritorio">

        <aside class="sidebar">
            <h2>DIGI - MPP</h2>
            <nav>
                <a href="#">🏠 Inicio</a>
                <a href="#">📊 Reportes</a>
                <a href="#">⚙️ Configuración</a>
                <a href="../logout.php">🚪 Cerrar sesión</a>
            </nav>
        </aside>

        <main class="contenido-principal">
            <header class="barra-superior">
                <div class="usuario">
                    <span>👤 HOLA, <?php echo $_SESSION['dg_nombre']; ?></span>
                </div>
            </header>

            <div>
                <p>
                    
                </p>
            </div>

            <section class="paneles">
                <div class="tarjeta">
                    <h3>Ventas</h3>
                    <p>$10,000 este mes</p>
                </div>
                <div class="tarjeta">
                    <h3>Usuarios</h3>
                    <p>250 registrados</p>
                </div>
                <div class="tarjeta">
                    <h3>Soporte</h3>
                    <p>5 tickets pendientes</p>
                </div>
            </section>
        </main>
    </div>
</body>

</html>