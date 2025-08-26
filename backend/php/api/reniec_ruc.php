<?php
header('Content-Type: application/json');

$ruc = $_GET['ruc'] ?? null;

if (!$ruc || !preg_match('/^\d{11}$/', $ruc)) {
    echo json_encode(['status' => 'error', 'message' => 'RUC inválido']);
    exit;
}

// Configuración
$url = 'https://api.consultasperu.com/api/v1/query';
$token = '3a107bbac572e9f71bdce73bd69909c72d4fdff8e6e9beacebf5aaaea3706e17';

// Body de la petición
$fields = [
    'token' => $token,
    'type_document' => 'ruc',
    'document_number' => $ruc
];

// Enviar petición con cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded'
]);

$response = curl_exec($ch);
$error = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false) {
    echo json_encode(['status' => 'error', 'message' => 'cURL error: ' . $error]);
    exit;
}

$result = json_decode($response, true);

if (
    $httpCode === 200 &&
    isset($result['success']) && $result['success'] === true &&
    isset($result['data'])
) {
    $data = $result['data'];

    echo json_encode([
        'status' => 'success',
        'ruc' => $ruc,
        'razon_social' => $data['name'] ?? '',
        'direccion' => $data['address'] ?? '',
    ]);
    exit;
} else {
    echo json_encode([
        'status' => 'error',
        'message' => $result['message'] ?? 'No se pudo obtener la información'
    ]);
    exit;
}
