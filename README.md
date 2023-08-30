# Monday.com API Scripts

Este repositorio contiene scripts PHP para interactuar con la API de Monday.com.

## Configuración

1. Crea un archivo de texto llamado `config.txt` en la misma ubicación que los scripts. 
2. Abre `config.txt` y agrega tu API Token y el ID de la tarea en el siguiente formato:  
  
API_MONDAY  
<<URL_API_DE_MONDAY>>  
  
MONDAY_ADMIN_TOKEN
<<TOKEN_DE_MONDAY>>  
  
MONDAY_USER_TOKEN
<<ID_DE_LA_TAREA_DE_MONDAY>>  
  
SLAK_TOKEN 
<<WEBHOOK_DE_SLACK>>

ITEM_ID
<<ID_DE_TAREA>>
  
Reemplazando <<...>> con los valores correspondientes.  

## Script.php

Este script verifica si el seguimiento de tiempo está encendido o apagado en una tarea específica.

1. Asegúrate de haber configurado `config.txt` con el token y el ID correctos.
2. Ejecuta el script `Script.php` desde la línea de comandos o en un servidor PHP.

El script mostrará un mensaje indicando si el seguimiento de tiempo está encendido o apagado en la tarea especificada.

## checkData.php

Este script obtiene y guarda en un archivo de texto todos los valores de columna de una tarea específica.

1. Asegúrate de haber configurado `config.txt` con el token y el ID correctos.
2. Ejecuta el script `checkData.php` desde la línea de comandos o en un servidor PHP.

El script generará un archivo `response.txt` que contendrá todos los valores de columna devueltos por la API para la tarea especificada.

## Notas

- Asegúrate de mantener tu token y ID en secreto y no compartirlos públicamente.
- Para obtener más información sobre la API de Monday.com, visita su documentación oficial.
