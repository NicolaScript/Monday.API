<?php
// Configuración de la API de Monday.com
$configFile = file('config.txt');
$url = trim($configFile[1]);
$apiToken = trim($configFile[4]);

// Realizar la consulta para obtener los workspaces
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Authorization: ' . $apiToken
));

$workspacesData = array(
    'query' => 'query {
        workspaces {
            id
            name
            users_subscribers {
                id
                name
                email
            }
        }
    }'
);

$jsonWorkspacesData = json_encode($workspacesData);

curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonWorkspacesData);

$responseWorkspaces = curl_exec($ch);

if (curl_errno($ch)) {
    echo "Error al conectarse con la API de monday.com: " . curl_error($ch);
    curl_close($ch);
    exit();
}

curl_close($ch);

// Decodificar la respuesta JSON para workspaces
$responseWorkspacesData = json_decode($responseWorkspaces, true);



// Verificar si se obtuvieron los datos correctamente para workspaces
if (isset($responseWorkspacesData['data']['workspaces'])) {
    $workspaces = $responseWorkspacesData['data']['workspaces'];

    foreach ($workspaces as $workspace) {
        $workspaceId = $workspace['id'];
        $workspaceName = $workspace['name'];

        //echo "<h1>Workspace ID: $workspaceId, Nombre: $workspaceName</h1>";

        if (isset($workspace['users_subscribers'])) {
            $subscribers = $workspace['users_subscribers'];

            // echo "<h3>Usuarios suscritos en el workspace:</h3>";
            // foreach ($subscribers as $subscriber) {
            //     $subscriberId = $subscriber['id'];
            //     $subscriberName = $subscriber['name'];
            //     $subscriberEmail = $subscriber['email'];

            //     echo "ID: $subscriberId, Nombre: $subscriberName, Email: $subscriberEmail<br/>";
            // }
        } else {
            echo "<p>No se pudieron obtener los usuarios suscritos en este workspace.</p>";
        }

        // Realizar la consulta para obtener las carpetas dentro de este workspace
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: ' . $apiToken
        ));

        $foldersData = array(
            'query' => "query {
                folders(workspace_ids: [$workspaceId]) {
                    id
                    name
                    children {
                        id
                        name
                    }
                }
            }"
        );

        $jsonFoldersData = json_encode($foldersData);

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonFoldersData);

        $responseFolders = curl_exec($ch);

        if (curl_errno($ch)) {
            echo "Error al conectarse con la API de monday.com: " . curl_error($ch);
            curl_close($ch);
            exit();
        }

        // Decodificar la respuesta JSON para carpetas
        $responseFoldersData = json_decode($responseFolders, true);

        // Verificar si se obtuvieron los datos correctamente para carpetas
        if (isset($responseFoldersData['data']['folders'])) {
            $folders = $responseFoldersData['data']['folders'];

            foreach ($folders as $folder) {
                $folderId = $folder['id'];
                $folderName = $folder['name'];

                //echo "<h2>Carpeta ID: $folderId, Nombre: $folderName</h2>";

                // Verificar si la carpeta tiene hijos (subcarpetas)
                if (isset($folder['children'])) {
                    $subfolders = $folder['children'];

                    foreach ($subfolders as $subfolder) {
                        $subfolderId = $subfolder['id'];
                        $subfolderName = $subfolder['name'];

                        //echo "<h3 style='margin-left: 20px;'>Tablero ID: $subfolderId, Nombre: $subfolderName</h3>";

                        // Realizar la consulta para obtener elementos/items
                        $ch = curl_init($url);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                            'Content-Type: application/json',
                            'Authorization: ' . $apiToken
                        ));

                        $itemsData = array(
                            'query' => "query {
                                boards(ids: [$subfolderId]) {
                                    items {
                                        id
                                        name
                                        subscribers {
                                            id
                                            name
                                            time_zone_identifier
                                        }
                                        column_values {
                                            id 
                                            title
                                            text
                                            value
                                        }
                                    }
                                }
                            }"
                        );

                        $jsonItemsData = json_encode($itemsData);

                        curl_setopt($ch, CURLOPT_POST, 1);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonItemsData);

                        $responseItems = curl_exec($ch);

                        if (curl_errno($ch)) {
                            echo "Error al conectarse con la API de monday.com: " . curl_error($ch);
                            curl_close($ch);
                            exit();
                        }

                        // Decodificar la respuesta JSON para elementos
                        $responseItemsData = json_decode($responseItems, true);

                        // Verificar si se obtuvieron los datos correctamente para elementos
                        if (isset($responseItemsData['data']['boards'][0]['items'])) {
                            $items = $responseItemsData['data']['boards'][0]['items'];

                            foreach ($items as $item) {
                                $itemId = $item['id'];
                                $itemName = $item['name'];
                                $subscribers = $item['subscribers'];

                                //echo "<h4 style='margin-left: 40px;'>Tarea ID: $itemId, Nombre: $itemName</h4>";
                                //echo "<h4 style='margin-left: 60px;'>Columnas:</h4>";

                                // Imprimir columnas
                                if (isset($item['column_values'])) {
                                    $columnValues = $item['column_values'];
                                    
                                    // Buscar el valor de la columna "Estado"
                                    $estadoValue = "";
                                    foreach ($columnValues as $columnValue) {
                                        if ($columnValue['title'] === 'Estado') {
                                            $estadoValue = $columnValue['text'];
                                            break;
                                        }
                                    }

                                    $allowedStates = array(
                                        'En progreso',
                                        'En proceso',
                                        'Listo para empezar',
                                        'En revisión',
                                        'Esperando Revisión',
                                        'Esperando Implementación',
                                        'Implementación pendiente'
                                    );

                                    if (in_array($estadoValue, $allowedStates)) {
                                        //echo "Elemento ID: $itemId, Nombre: $itemName<br/>";
                                        //echo "Estado: $estadoValue<br/>";
                                    
                                        // Verificar horario laboral solo para elementos con estados permitidos
                                        foreach ($subscribers as $subscriber) {
                                            $subscriberId = $subscriber['id'];
                                            $subscriberName = $subscriber['name'];
                                            $timeZoneIdentifier = $subscriber['time_zone_identifier'];

                                            // Obtener la fecha y hora actual en la zona horaria del suscriptor
                                            $now = new DateTime('now', new DateTimeZone($timeZoneIdentifier));

                                            // Verificar si es un día laboral (de lunes a viernes)
                                            $dayOfWeek = $now->format('N'); // 1 (lunes) a 7 (domingo)
                                            $isWeekday = ($dayOfWeek >= 1 && $dayOfWeek <= 5);

                                            // Obtener la hora actual en formato 24 horas
                                            $currentHour = (int)$now->format('H');

                                            // Buscar el valor del seguimiento de tiempo en las columnas
                                            $timeTrackingValue = null;
                                            foreach ($columnValues as $column) {
                                                if ($column['id'] === 'duration') {
                                                    $timeTrackingValue = $column['value'];
                                                    break;
                                                }
                                            }

                                            if ($timeTrackingValue !== null) {
                                                $timeTrackingData = json_decode($timeTrackingValue, true);
                                                if (isset($timeTrackingData['running']) && $timeTrackingData['running']) {
                                                    if ($isWeekday && $currentHour >= 9 && $currentHour <= 17) {
                                                        echo "El usuario $subscriberName está trabajando en el elemento $itemName en horario laboral.<br/>";
                                                    } elseif (!$isWeekday) {
                                                        echo "El usuario $subscriberName está trabajando en el elemento $itemName fuera de un día laboral.<br/>";
                                                    } elseif ($currentHour > 17) {
                                                        echo "El usuario $subscriberName se quedó trabajando después de hora en el elemento $itemName.<br/>";
                                                    } elseif ($currentHour < 9){
                                                        echo "El usuario $subscriberName está trabajando fuera de hora en el elemento $itemName.<br/>";
                                                    }
                                                } else {
                                                    if ($isWeekday && $currentHour >= 9 && $currentHour <= 17) {
                                                        echo "El usuario $subscriberName no está trabajando en el elemento $itemName durante el horario laboral.<br/>";
                                                    } else {
                                                        echo "El usuario $subscriberName está descansando<br/>";
                                                    }
                                                }

                                            }
                                        }

                                    }


                                    // foreach ($columnValues as $column) {
                                    //     $columnTitle = $column['title'];
                                    //     $columnText = $column['text'];
                                    //     $columnValue = $column['value'];
                                    //     $columnType = $column['type'];

                                    //     echo "<p style='margin-left: 80px;'><strong>Columna:</strong> $columnTitle, Texto: $columnText, Valor: $columnValue, Tipo: $columnType</p>";
                                    // }

                                    

                                } else {
                                    echo "<p style='margin-left: 80px;'>No se pudieron obtener las columnas de este elemento.</p>";
                                }
                            }
                        //} else {
                            //echo "<p style='margin-left: 40px;'>No se pudieron obtener los datos de los elementos en este tablero (subcarpeta).</p>";
                        }
                    }
                }
            }
        } else {
            echo "No se pudieron obtener los datos de las carpetas en este workspace.<br/>";
        }
    }
} else {
    echo "No se pudieron obtener los nombres de los workspaces.<br/>";
}
?>