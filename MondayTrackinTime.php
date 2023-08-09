<?php

$configFile = file('config.txt');
$apiToken = trim($configFile[1]);
$itemId = trim($configFile[3]);

// URL de la API de monday.com para obtener información del elemento
$url = 'https://api.monday.com/v2';

// Configurar la solicitud cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Authorization: ' . $apiToken
));

// Crear el cuerpo de la solicitud
$data = array(
    'query' => 'query { items (ids: ' . $itemId . ') { column_values { id text value } } }'
);
$jsonData = json_encode($data);

curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

// Realizar la solicitud cURL y obtener la respuesta
$response = curl_exec($ch);

// Verificar si hubo un error en la solicitud cURL
if (curl_errno($ch)) {
    echo "Error al conectarse con la API de monday.com: " . curl_error($ch);
} else {
    // Analizar la respuesta JSON
    $data = json_decode($response, true);

    // Verificar si se obtuvo la información del elemento
    if (isset($data['data']['items'][0]['column_values'])) {
        $columnValues = $data['data']['items'][0]['column_values'];

        // Buscar el valor del seguimiento de tiempo en las columnas
        $timeTrackingValue = null;
        foreach ($columnValues as $column) {
            if ($column['id'] === 'duration') {
                $timeTrackingValue = $column['value'];
                break;
            }
        }

        // Verificar si se encontró el valor del seguimiento de tiempo
        if ($timeTrackingValue !== null) {
            $timeTrackingData = json_decode($timeTrackingValue, true);

            // Verificar si el seguimiento de tiempo está encendido o apagado
            if (isset($timeTrackingData['running']) && $timeTrackingData['running']) {
                echo "El seguimiento de tiempo está encendido en el elemento con ID " . $itemId;
            } else {
                echo "El seguimiento de tiempo está apagado en el elemento con ID " . $itemId;
            }
        } else {
            echo "El elemento no tiene una columna de seguimiento de tiempo";
        }
    } else {
        echo "No se pudo obtener información del elemento con ID " . $itemId;
    }
}

// Cerrar la solicitud cURL
curl_close($ch);

?>

