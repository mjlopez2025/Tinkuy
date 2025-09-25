<?php
include ("../config.php");


echo "=============================================\n";
echo "SCRIPT PARA CREAR TABLA DOCENTES_GUARANI PARTICIONADA\n";
echo "=============================================\n";
echo "\nIniciando procesamiento....\n";

try {
    
    
    echo "✅ Conexión exitosa a PostgreSQL\n";
    echo "Servidor: {$config_tinkuy['host']}:{$config_tinkuy['port']}\n";
    echo "Base de datos: {$config_tinkuy['dbname']}\n\n";

    // Eliminar tabla existente si es necesario
    $conn->exec("DROP TABLE IF EXISTS docentes_guarani CASCADE;");
    echo "✅ Tabla existente eliminada (si existía)\n";

    echo "=============================================================\n";
    echo "Paso 1. Creando tabla particionada Docentes_Guarani...\n";
    echo "=============================================================\n";
    
    // 1. Crear tabla principal particionada con anio después de comision_guarani
    $createTableSQL = "CREATE TABLE docentes_guarani (
    id SERIAL,
    responsabilidad_academica_guarani VARCHAR(500),
    propuesta_formativa_guarani VARCHAR(500),
    comision_guarani VARCHAR(500),
    anio_guarani INTEGER NOT NULL,
    periodo_guarani VARCHAR(500),
    docente_guarani VARCHAR(500),
    tipo_doc_guarani VARCHAR(100),
    num_doc_guarani VARCHAR(500),
    actividad_guarani VARCHAR(500),
    cursados_guarani VARCHAR(500),
    ape_nom1_Guarani VARCHAR(500),
    tipo_doc1_Guarani VARCHAR(500),
    num_doc1_Guarani VARCHAR(50),
    ape_nom2_Guarani VARCHAR(500),
    tipo_doc2_Guarani VARCHAR(500),
    num_doc2_Guarani VARCHAR(500),
    ape_nom3_Guarani VARCHAR(500),
    tipo_doc3_Guarani VARCHAR(500),
    num_doc3_Guarani VARCHAR(500),
    ape_nom4_Guarani VARCHAR(500),
    tipo_doc4_Guarani VARCHAR(500),
    num_doc4_Guarani VARCHAR(500),
    PRIMARY KEY (id, anio_guarani)
) PARTITION BY RANGE (anio_guarani);";


    $conn->exec($createTableSQL);
    echo "✅ Tabla principal Docentes_Guarani creada exitosamente.\n";

    // 2. Crear particiones para cada año desde 2011 hasta 2040
    echo "\nCreando particiones por año...\n";
    
    for ($year = 2011; $year <= 2040; $year++) {
        $nextYear = $year + 1;
        $partitionName = "docentes_guarani_y{$year}";
        
        $partitionSQL = "CREATE TABLE {$partitionName} 
            PARTITION OF docentes_guarani 
            FOR VALUES FROM ({$year}) TO ({$nextYear});";
        
        try {
            $conn->exec($partitionSQL);
            echo "✅ Partición para año {$year} creada exitosamente.\n";
        } catch (PDOException $e) {
            echo "⚠️ Error al crear partición para año {$year}: " . $e->getMessage() . "\n";
        }
    }

    // Verificación final
    echo "\n🔍 Verificación final:\n";
    $tables = $conn->query("
        SELECT table_name 
        FROM information_schema.tables 
        WHERE table_schema = 'public' 
        AND table_name LIKE 'docentes_guarani%'
        ORDER BY table_name
    ")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Tablas de particiones existentes:\n";
    print_r($tables);

} catch (PDOException $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    if (strpos($e->getMessage(), 'already exists') !== false) {
        echo "ℹ️ La tabla o partición ya existe en la base de datos.\n";
    }
}

echo "\nProceso completado.\n";
?>