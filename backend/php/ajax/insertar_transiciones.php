<?php
require '../../db/conexion.php';

// Obtener todas las áreas
$query = $pdo->query("SELECT IdAreas FROM areas");
$areas = $query->fetchAll(PDO::FETCH_ASSOC);

// Insertar las transiciones entre todas las áreas
foreach ($areas as $area_origen) {
    foreach ($areas as $area_destino) {
        // Asegurarse de que no se inserte la transición a sí misma
        if ($area_origen['IdAreas'] != $area_destino['IdAreas']) {
            // Verificar si la transición ya existe antes de insertar
            $verificacion = $pdo->prepare("SELECT COUNT(*) FROM transiciones_areas WHERE area_origen = ? AND area_destino = ?");
            $verificacion->execute([$area_origen['IdAreas'], $area_destino['IdAreas']]);
            $transicion_existente = $verificacion->fetchColumn();

            // Si la transición no existe, insertamos
            if ($transicion_existente == 0) {
                $stmt = $pdo->prepare("INSERT INTO transiciones_areas (area_origen, area_destino) VALUES (?, ?)");
                $stmt->execute([$area_origen['IdAreas'], $area_destino['IdAreas']]);
            }
        }
    }
}

echo "Transiciones insertadas correctamente!";
