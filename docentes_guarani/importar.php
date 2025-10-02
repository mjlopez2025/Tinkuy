<?php

CONST CSV_FILE_GUARANI = 'Docente_Guarani.csv';
CONST DELIMITER = ','; 
$LINES = str_repeat('-', 80)."\n";


//require_once "./paso1.php"; // verificar y crear tabla si no existe
//require_once "./paso2.php"; // 
//require_once "./paso3.php"; // separa los registros con dos o mas docentes
//require_once "./paso4.php"; // limpieza de registros
//require_once "./paso5.php"; // separa nombre, tipo, num doc
//require_once "./paso6.php"; // limpia periodo y extrae codigo de actividad
require_once "./paso7.php"; // coloca sin informacion en los campos vacios y borra las columnas vacias
 
 


// Cerrar conexión
$conn = null;

