<?php
include_once("../config.php");

echo "==========================================================================\n";
echo "\nProcesando y normalizando datos de docentes...\n";
echo "==========================================================================\n";

try {
    // 1. Contar registros iniciales
    $total_inicial = $conn->query("SELECT COUNT(*) FROM Docentes_Guarani")->fetchColumn();
    echo "📊 Registros iniciales: $total_inicial\n";

    // 2. Obtener todos los registros a procesar
    $registros = $conn->query("SELECT id, docente_guarani FROM Docentes_Guarani");
    $total_registros = $registros->rowCount();

    // Inicializar contadores
    $procesados = 0;
    $actualizados = 0;
    $inicio = time();

    echo "\n🔍 Procesando $total_registros registros...\n";

    // 3. Procesar cada registro
    foreach ($registros->fetchAll(PDO::FETCH_ASSOC) as $registro) {
        $procesados++;
        $porcentaje = round(($procesados / $total_registros) * 100, 2);
        
        // Mostrar progreso
        if ($procesados % 100 === 0 || $procesados === $total_registros) {
            echo "\r🔄 Progreso: $procesados/$total_registros ($porcentaje%)";
            flush();
        }

        // Extraer datos del docente
        $datos = extraerDatosDocente($registro['docente_guarani']);
        
        // Solo actualizar si encontramos datos válidos
        if ($datos['docente_guarani'] !== $registro['docente_guarani'] || 
            !empty($datos['tipo_doc_guarani'])) {
            
            $conn->prepare("
                UPDATE Docentes_Guarani 
                SET docente_guarani = ?,
                    tipo_doc_guarani = ?,
                    num_doc_guarani = ?
                WHERE id = ?
            ")->execute([
                $datos['docente_guarani'],
                $datos['tipo_doc_guarani'],
                $datos['num_doc_guarani'],
                $registro['id']
            ]);
            
            $actualizados++;
        }
    }

    // 4. Resultados finales
    $tiempo_total = time() - $inicio;

    echo "\n\n✅ ¡Proceso completado en $tiempo_total segundos!\n";
    echo "📝 Registros procesados: $procesados\n";
    echo "🔄 Registros actualizados: $actualizados\n\n";

} catch (PDOException $e) {
    echo "\n🚨 Error: " . $e->getMessage() . "\n";
}

/**
 * Función para extraer y normalizar datos del docente
 */
function extraerDatosDocente($texto) {
    $resultado = [
        'docente_guarani' => $texto,
        'tipo_doc_guarani' => null,
        'num_doc_guarani' => null
    ];
    
    // Patrón 1: "Apellido Nombre, TIPO_DOC, NUMERO"
    if (preg_match('/^(.*?),\s*( DNI| DNT| CI| LC| LE| PASAPORTE),\s*(\d+)\s*$/i', $texto, $matches)) {
        $resultado['docente_guarani'] = trim($matches[1]);
        $resultado['tipo_doc_guarani'] = strtoupper(trim($matches[2]));
        $resultado['num_doc_guarani'] = trim($matches[3]);
    }
    // Patrón 2: "Apellido Nombre (TIPO_DOC NUMERO)"
    elseif (preg_match('/^(.*?)\s*\(( DNI| DNT| CI| LC| LE| PASAPORTE)\s*(\d+)\)\s*$/i', $texto, $matches)) {
        $resultado['docente_guarani'] = trim($matches[1]);
        $resultado['tipo_doc_guarani'] = strtoupper(trim($matches[2]));
        $resultado['num_doc_guarani'] = trim($matches[3]);
    }
    // Patrón 3: Solo nombre (sin documento)
    elseif (preg_match('/^([^,\(\)]+)$/', $texto, $matches)) {
        $resultado['docente_guarani'] = trim($matches[1]);
    }
    
    return $resultado;
}