<?php
// Configuración para mostrar todos los errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuración de conexión al servidor
$config_tinkuy = [
    'host'     => '172.16.1.58',
    'port'     => '5433',
    'dbname'   => 'tinkuy',
    'user'     => 'mjlopez',
    'password' => '13082019'
]; 

// Crear string de conexión DSN
$dsn = "pgsql:host={$config_tinkuy['host']};port={$config_tinkuy['port']};dbname={$config_tinkuy['dbname']}";

// Establecer conexión
    $conn = new PDO($dsn, $config_tinkuy['user'], $config_tinkuy['password']);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);



// Configuración de conexión a Mapuche
$config_mapuche = [
    'host'     => '172.16.1.10',
    'port'     => '5432',
    'dbname'   => 'siu',
    'user'     => 'readonly_mapuche',
    'password' => 'A7h30i5K4I5RenSHgED0'
]; 

// Crear string de conexión DSN
$dsn = "pgsql:host={$config_mapuche['host']};port={$config_mapuche['port']};dbname={$config_mapuche['dbname']}";

// Establecer conexión
$conn_m = new PDO($dsn, $config_mapuche['user'], $config_mapuche['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

// 🔑 Forzar conversión de la codificación de cliente
$conn_m->exec("SET client_encoding TO LATIN1");




// Configuración de la conexión a la base de datos Wichi (solo para consulta)
$config_wichi = [
    'host'     => '172.16.1.61',
    'port'     => '5432',
    'dbname'   => 'siu_wichi',
    'user'     => 'postgres',
    'password' => 'postgres',
    'esquemas' => [
        'principal' => 'mapuche',  // Para este script
        'default'   => 'public'    // Para otros scripts
    ]
];

$conn_wichi = new PDO(
        "pgsql:host={$config_wichi['host']};port={$config_wichi['port']};dbname={$config_wichi['dbname']}",
        $config_wichi['user'],
        $config_wichi['password']
    );


$config_jurumi = [
    'host'     => '172.16.1.67',
    'port'     => '5432',
    'dbname'   => 'jurumi',
    'user'     => 'postgres',
    'password' => 'Juanju',
];

$conn_jurumi = new PDO(
        "pgsql:host={$config_jurumi['host']};port={$config_jurumi['port']};dbname={$config_jurumi['dbname']}",
        $config_jurumi['user'],
        $config_jurumi['password']
    );


    //Produccion: http://172.16.1.58/Tinkuy/consultas/consultas.php
    // Desarrollo: http://localhost:8000/Tinkuy/consultas/consultas.php

define('BASE_URL', 'http://172.16.1.58/Tinkuy/consultas/consultas.php');

