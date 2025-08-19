<?php
function crearNotificacion($pdo, $area_id, $mensaje) {
    $stmt = $pdo->prepare("INSERT INTO notificaciones (IdAreas, Mensaje) VALUES (:area, :mensaje)");
    $stmt->execute([
        'area' => $area_id,
        'mensaje' => $mensaje
    ]);
}