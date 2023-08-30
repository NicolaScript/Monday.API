<?php

$configFile = file('config.txt');
$url = trim($configFile[1]);
$apiToken = trim($configFile[4]);
$itemId = trim($configFile[13]);

// URL de la API de monday.com para obtener información del elemento


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

if (curl_errno($ch)) {
    echo "Error al conectarse con la API de monday.com: " . curl_error($ch);
} else {

    $data = json_decode($response, true);

    file_put_contents('response.txt', "Value: " . print_r($data, TRUE) . " \n", FILE_APPEND);

    // Verificar si se obtuvo la información del elemento
    if (isset($data['data']['items'][0]['column_values'])) {
        $columnValues = $data['data']['items'][0]['column_values'];

        foreach ($columnValues as $column) {
            $id = $column['id'];
            $text = $column['text'];
            $value = $column['value'];

            echo "ID: $id<br>";
            echo "Text: $text<br>";
            echo "Value: " . json_encode(json_decode($value), JSON_PRETTY_PRINT) . "<br>";
            echo "<br>";
        }

        //echo '<pre>';
        //   var_dump($columnValues);
        //echo '</pre>';

    } else {
        echo "No se pudo obtener información del elemento con ID " . $itemId;
    }
}

curl_close($ch);

?>
