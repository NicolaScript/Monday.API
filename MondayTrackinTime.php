<?php

// Función para obtener el valor de una columna por su ID
function getColumnValue($columnValues, $columnId) {
    foreach ($columnValues as $column) {
        if ($column['id'] === $columnId) {
            return $column['value'];
        }
    }
    return null;
}

// Función para enviar una notificación a Slack
function sendSlackNotification($webhookUrl, $message) {
    $data = array('text' => $message);
    $jsonData = json_encode($data);

    $headers = array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonData)
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $webhookUrl);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_exec($ch);
    curl_close($ch);
}

// Obtener configuración desde el archivo
$configFile = file('config.txt');
$url = trim($configFile[1]);
$apiToken = trim($configFile[4]);
$itemId = trim($configFile[7]);
$slackWebhookUrl = trim($configFile[10]);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Authorization: ' . $apiToken
));

$data = array(
    'query' => 'query { items (ids: ' . $itemId . ') { column_values { id text value } } }'
);
$jsonData = json_encode($data);

curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo "Error al conectarse con la API de monday.com: " . curl_error($ch);
} else {
    $data = json_decode($response, true);

    if (isset($data['data']['items'][0]['column_values'])) {
        $columnValues = $data['data']['items'][0]['column_values'];

        $timeTrackingValue = getColumnValue($columnValues, 'duration');

        if ($timeTrackingValue !== null) {
            $timeTrackingData = json_decode($timeTrackingValue, true);

            $isTimeTrackingRunning = isset($timeTrackingData['running']) && $timeTrackingData['running'];

            $slackMessage = "El seguimiento de tiempo está " . ($isTimeTrackingRunning ? 'encendido' : 'apagado') . " en el elemento con ID $itemId";

            

            sendSlackNotification($slackWebhookUrl, $slackMessage);

            echo "Notificación enviada a Slack correctamente.";
        } else {
            echo "El elemento no tiene una columna de seguimiento de tiempo";
        }
    } else {
        echo "No se pudo obtener información del elemento con ID " . $itemId;
    }
}

curl_close($ch);

?>
