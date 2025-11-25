<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Archivo JSON donde se guarda el estado
$estadoFile = __DIR__ . "/modo_aviso.json";

// Leer estado actual
if (file_exists($estadoFile)) {
    $jsonData = json_decode(file_get_contents($estadoFile), true);
    $estado = $jsonData["modo_pago_activo"];
} else {
    $estado = true;
}

// Solo permitir acceso con ?admin_modo=1
if (isset($_GET['admin_modo']) && $_GET['admin_modo'] == 1) {

    // Si se envía el formulario
    if (isset($_POST['switch'])) {
        $nuevoEstado = ($_POST['switch'] === "on");

        // Guardar archivo JSON
        file_put_contents($estadoFile, json_encode([
            "modo_pago_activo" => $nuevoEstado
        ], JSON_PRETTY_PRINT));

        $estado = $nuevoEstado;
    }
?>
    <!DOCTYPE html>
    <html lang="es">

    <head>
        <meta charset="UTF-8">
        <title>Modo Aviso — Control</title>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
        <style>
            body {
                font-family: "Inter", sans-serif;
                margin: 0;
                padding: 0;
                background: linear-gradient(135deg, #1b1b1b, #333);
                height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                color: white;
            }

            .panel {
                background: #222;
                padding: 35px;
                border-radius: 16px;
                width: 350px;
                text-align: center;
                box-shadow: 0 0 30px rgba(0, 0, 0, 0.5);
                animation: fadeIn 0.6s ease-out;
            }

            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: scale(0.95);
                }

                to {
                    opacity: 1;
                    transform: scale(1);
                }
            }

            h1 {
                font-size: 26px;
                margin-bottom: 10px;
            }

            p {
                color: #ccc;
                margin-bottom: 25px;
            }

            /* INTERRUPTOR ESTILO iOS */
            .switch {
                position: relative;
                width: 60px;
                height: 32px;
                display: inline-block;
            }

            .switch input {
                opacity: 0;
                width: 0;
                height: 0;
            }

            .deslizador {
                position: absolute;
                cursor: pointer;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: #b31a1a;
                transition: .3s;
                border-radius: 34px;
            }

            .deslizador:before {
                position: absolute;
                content: "";
                height: 26px;
                width: 26px;
                left: 3px;
                bottom: 3px;
                background-color: white;
                transition: .3s;
                border-radius: 50%;
            }

            input:checked+.deslizador {
                background-color: #27ae60;
            }

            input:checked+.deslizador:before {
                transform: translateX(28px);
            }

            .estado {
                margin-top: 15px;
                font-weight: 800;
                font-size: 18px;
                letter-spacing: 1px;
            }

            .estado.ok {
                color: #2ecc71;
            }

            .estado.no {
                color: #e74c3c;
            }

            .btn-volver {
                display: inline-block;
                margin-top: 25px;
                padding: 10px 18px;
                background: #444;
                border-radius: 8px;
                color: white;
                text-decoration: none;
                transition: 0.2s;
            }

            .btn-volver:hover {
                background: #666;
            }
        </style>
    </head>

    <body>
        <div class="panel">
            <h1>¿Ya pagaron?</h1>
            <p>Activa o desactiva el modo aviso del sistema.</p>

            <form method="POST">
                <input type="hidden" name="switch" value="off">
                <label class="switch">
                    <input type="checkbox" name="switch" value="on" <?= $estado ? 'checked' : '' ?> onchange="this.form.submit()">
                    <span class="deslizador"></span>
                </label>
            </form>

            <?php if ($estado): ?>
                <div class="estado ok">SISTEMA NORMAL</div>
            <?php else: ?>
                <div class="estado no">AVISO ACTIVADO</div>
            <?php endif; ?>

            <a href="../frontend/sisvis/escritorio.php" class="btn-volver">Volver al sistema</a>
        </div>
    </body>

    </html>
<?php
    exit;
}

// Si no tiene admin_modo=1
echo "Acceso no autorizado";
