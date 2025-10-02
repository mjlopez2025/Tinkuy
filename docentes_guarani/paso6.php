<?php
include_once("../config.php");
try {
    // 1. Añadir columna código si no existe
    echo "================================\n";
    echo "Paso 4. Preparando estructura...\n";
    echo "================================\n";
    $conn->exec("ALTER TABLE public.Docentes_Guarani ADD COLUMN IF NOT EXISTS codigo_guarani VARCHAR(20)");

    // 2. Normalización del periodo y extracción del año
    echo "4.2. Procesando periodo_guarani y año...\n";

    // Extraemos el año y eliminamos la parte "YYYY - "
    $conn->exec("
        UPDATE public.Docentes_Guarani 
    SET 
    anio_guarani = SUBSTRING(periodo_guarani FROM 1 FOR 4)::INTEGER,
    periodo_guarani = TRIM(SUBSTRING(periodo_guarani FROM 11))
    WHERE periodo_guarani ~ '^\d{4}\s+\-\s+';
");


    // 3. Extracción de códigos de actividad
    echo "4.4 Procesando códigos de actividad...\n";
    
    // Primero extraemos el código (contenido entre paréntesis)
    $conn->exec("
        UPDATE public.Docentes_Guarani 
        SET codigo_guarani = SUBSTRING(
            actividad_guarani FROM '\(([^)]+)\)'
        )
        WHERE actividad_guarani ~ '\([A-Za-z0-9]+\)'
    ");
    
    // Luego limpiamos la actividad (eliminamos código y guión)
    $conn->exec("
        UPDATE public.Docentes_Guarani 
        SET actividad_guarani = TRIM(
            REGEXP_REPLACE(actividad_guarani, '^\([^)]+\)\s*-\s*', '')
        )
        WHERE actividad_guarani ~ '\([^)]+\)\s*-\s*'
    ");

    // 5. Verificación final
    echo "\n4.5. Verificación de resultados:\n";
    
    $sample = $conn->query("
        SELECT 
            anio_guarani, 
            periodo_guarani, 
            codigo_guarani,
            LEFT(actividad_guarani, 30) as actividad,
            LEFT(docente_guarani, 20) as docente
        FROM public.Docentes_Guarani 
        LIMIT 5
    ");
    
    echo str_pad("anio_guarani", 6) . 
         str_pad("Periodo", 20) . 
         str_pad("Código", 12) . 
         str_pad("Actividad", 30) . 
         "Docente\n";
    echo str_repeat("-", 90) . "\n";
    
    foreach ($sample->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo str_pad($row['anio_guarani'], 6) . 
             str_pad($row['periodo_guarani'], 20) . 
             str_pad($row['codigo_guarani'] ?? '', 12) . 
             str_pad($row['actividad'] ?? '', 30) . 
             ($row['docente'] ?? '') . "\n";
    }
    
    echo "\nNormalización completada con éxito!\n";
    
} catch (PDOException $e) {
    echo "\nError de base de datos: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "\nError general: " . $e->getMessage() . "\n";
}