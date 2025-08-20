<?php

/**
 * API para consulta de DNI
 * Archivo: consultar_dni.php
 * 
 * SEGURIDAD:
 * - Validación estricta de DNI
 * - Headers de seguridad
 * - Manejo de errores sin exponer información sensible
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

// Validar el DNI
if (!isset($input['numdni']) || !preg_match('/^\d{8}$/', $input['numdni'])) {
    echo json_encode(['status' => 'error', 'message' => 'DNI inválido. Debe contener exactamente 8 dígitos.']);
    exit;
}

$dni = $input['numdni'];

// Configuración de la API
$url = 'https://api.consultasperu.com/api/v1/query';
$token = '3a107bbac572e9f71bdce73bd69909c72d4fdff8e6e9beacebf5aaaea3706e17';

// Body de la petición
$fields = [
    'token' => $token,
    'type_document' => 'dni',
    'document_number' => $dni
];

try {
    // Enviar petición con cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'User-Agent: DIGI-System/1.0'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Timeout de 10 segundos
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // Timeout de conexión de 5 segundos
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Verificar error de conexión
    if ($response === false) {
        throw new Exception('Error de conexión con el servicio de consulta: ' . $error);
    }

    // Verificar código HTTP
    if ($httpCode !== 200) {
        throw new Exception('El servicio de consulta no está disponible temporalmente.');
    }

    // Decodificar respuesta
    $result = json_decode($response, true);

    if ($result === null) {
        throw new Exception('Respuesta inválida del servicio de consulta.');
    }

    // Validar datos devueltos
    if (isset($result['success']) && $result['success'] === true && isset($result['data'])) {
        $data = $result['data'];

        // Validar que los datos esenciales estén presentes
        if (empty($data['name']) && empty($data['first_last_name'])) {
            echo json_encode([
                'status' => 'error',
                'message' => 'No se encontraron datos para el DNI proporcionado.'
            ]);
            exit;
        }

        // Limpiar y validar datos
        $prenombres = trim($data['name'] ?? '');
        $apPrimer = trim($data['first_last_name'] ?? '');
        $apSegundo = trim($data['second_last_name'] ?? '');

        // Capitalizar nombres (opcional)
        $prenombres = ucwords(strtolower($prenombres));
        $apPrimer = ucwords(strtolower($apPrimer));
        $apSegundo = ucwords(strtolower($apSegundo));

        echo json_encode([
            'status' => 'success',
            'numDNI' => $dni,
            'prenombres' => $prenombres,
            'apPrimer' => $apPrimer,
            'apSegundo' => $apSegundo,
            'direccion' => trim($data['address'] ?? ''),
            'fecha_nacimiento' => $data['date_of_birth'] ?? '',
            'genero' => $data['gender'] ?? ''
        ]);
    } else {
        // Error de la API externa
        $mensaje = 'No se pudo obtener la información del DNI.';

        if (isset($result['message'])) {
            // Personalizar mensaje según el error
            $apiMessage = strtolower($result['message']);
            if (strpos($apiMessage, 'not found') !== false) {
                $mensaje = 'DNI no encontrado en la base de datos.';
            } elseif (strpos($apiMessage, 'invalid') !== false) {
                $mensaje = 'DNI inválido.';
            } elseif (strpos($apiMessage, 'token') !== false) {
                $mensaje = 'Error de configuración del servicio.';
            }
        }

        echo json_encode([
            'status' => 'error',
            'message' => $mensaje
        ]);
    }
} catch (Exception $e) {
    // Log del error (en producción, usar un sistema de logs apropiado)
    error_log("Error en consulta DNI: " . $e->getMessage());

    echo json_encode([
        'status' => 'error',
        'message' => 'Error interno del servidor. Intente nuevamente en unos momentos.'
    ]);
}
