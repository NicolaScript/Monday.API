<?php
// Configuración de la API de Monday.com
$configFile = file('config.txt');
$url = trim($configFile[1]);
$apiToken = trim($configFile[7]);



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

$resultsBySubscriber = array();
global $message;

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

                // Verificar si la carpeta tiene hijos (tableros)
                if (isset($folder['children'])) {
                    $boards = $folder['children'];

                    foreach ($boards as $board) {
                        $boardId = $board['id'];
                        $boardName = $board['name'];

                        //echo "<h3 style='margin-left: 20px;'>Tablero ID: $boardId, Nombre: $boardName</h3>";

                        // Realizar la consulta para obtener elementos/items
                        $ch = curl_init($url);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                            'Content-Type: application/json',
                            'Authorization: ' . $apiToken
                        ));

                        $itemsData = array(
                            'query' => "query {
                                boards(ids: [$boardId]) {
                                    items {
                                        id
                                        name
                                        subscribers {
                                            id
                                            name
                                            email
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
                                            $subscriberEmail = $subscriber['email'];
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
                                                        $message = "Está trabajando en la tarea: *$itemName* en horario laboral.";
                                                    } elseif (!$isWeekday) {
                                                        $message = "Está trabajando en la tarea: *$itemName* fuera de un día laboral.";
                                                    } elseif ($currentHour > 17) {
                                                        $message = "Se quedó trabajando después de hora en la tarea: *$itemName*.";
                                                    } elseif ($currentHour < 9){
                                                        $message = "Está trabajando fuera de hora en la tarea: *$itemName*.";
                                                    }
                                                } else {
                                                    if ($isWeekday && $currentHour >= 9 && $currentHour <= 17) {
                                                        $message = "No está trabajando en la tarea: *$itemName* durante el horario laboral.";
                                                    } else {
                                                        $message = "Está descansando";
                                                    }
                                                }
                                            }

                                            if (!isset($userResponses[$subscriberName])) {
                                                $userResponses[$subscriberName] = array();
                                            }
                                            $userResponses[$subscriberName][] = $message;
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

//Variable de mensaje para Slak
$slakMessage = '';

// Imprimir los resultados de manera ordenada
foreach ($userResponses as $subscriberName => $responses) {
    $slakMessage .= "*$subscriberName:*\n";
    foreach ($responses as $response) {
        $slakMessage .= "- $response\n";
    }
    $slakMessage .= "\n";
}

// URL del webhook de Slack
$slackWebhookUrl = trim($configFile[10]);

// Configurar los datos para la solicitud cURL
$slackData = array('text' => $slakMessage);
$slackDataString = json_encode($slackData);
$slackHeaders = array(
    'Content-Type: application/json',
    'Content-Length: ' . strlen($slackDataString)
);

// Inicializar la solicitud cURL a la URL del webhook de Slack
$slackCh = curl_init();
curl_setopt($slackCh, CURLOPT_URL, $slackWebhookUrl);
curl_setopt($slackCh, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($slackCh, CURLOPT_POSTFIELDS, $slackDataString);
curl_setopt($slackCh, CURLOPT_HTTPHEADER, $slackHeaders);

// Ejecutar la solicitud cURL a Slack
$slackResponse = curl_exec($slackCh);

// Verificar si hubo un error en la solicitud cURL a Slack
if (curl_errno($slackCh)) {
    echo "Error al enviar la notificación a Slack: " . curl_error($slackCh);
} else {
    echo " Notificación enviada a Slack correctamente.";
}

// Cerrar la sesión cURL
curl_close($slackCh);


// // Envío de correos a usuarios
// foreach ($userResponses as $subscriberName => $responses) {
//     // Obtener el correo del usuario
//     $subscriberEmail = $userEmails[$subscriberName];
    
//     // Verificar las condiciones y enviar el correo correspondiente
//     if (in_array($subscriberName, $restingUsers)) {
//         // Enviar correo sobre descanso
//         $subject = "Hora de descansar!";
//         $body = "Gracias por dar la milla extra, pero nos preocupa el burnout. Si puedes, preferiríamos que descanses. Trabaja para vivir y no vivas para trabajar.";
//     } elseif (isset($userTasks[$subscriberName]) && count($userTasks[$subscriberName]) === 0) {
//         // Enviar correo sobre ninguna tarea asignada
//         $subject = "hora de trabajar!";
//         $taskList = implode(", ", array_unique($userTasks[$subscriberName]));
//         $body = "No tienes ninguna tarea asignada, te sugiero que actives alguna de las siguientes tareas: $taskList";
//     } else {
//         continue;
//     }
    
//     // Enviar correo usando la función de envío de correo que estés utilizando
//     enviarCorreo($subscriberEmail, $subject, $body);
//}

?>