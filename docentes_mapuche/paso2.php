<?php
// paso2_fix_encoding.php
include_once("../config.php");

// --- Helpers de codificación mejorados ---
function fixEncoding($value) {
    if ($value === null || !is_string($value)) {
        return $value;
    }
    
    // Detectar si ya es UTF-8
    if (mb_detect_encoding($value, 'UTF-8', true) === 'UTF-8') {
        return $value;
    }
    
    // Intentar convertir desde LATIN1
    $converted = @iconv('LATIN1', 'UTF-8//IGNORE', $value);
    if ($converted !== false) {
        return $converted;
    }
    
    // Si falla iconv, usar mb_convert_encoding
    $converted = @mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
    if ($converted !== false) {
        return $converted;
    }
    
    // Último recurso: utf8_encode (para ISO-8859-1)
    return utf8_encode($value);
}

// --- Inicio ---
try {
    if (!isset($conn_m) || !($conn_m instanceof PDO)) {
        throw new Exception("❌ Error: Conexión a Mapuche no está disponible en config.php");
    }
    if (!isset($conn) || !($conn instanceof PDO)) {
        throw new Exception("❌ Error: Conexión a Tinkuy no está disponible en config.php");
    }

    echo "✅ Conexiones cargadas desde config.php\n";

    // 🔥 CONFIGURAR CODIFICACIÓN PARA MAPUCHE (LATIN1)
    try {
        $conn_m->exec("SET client_encoding TO 'LATIN1'");
        echo "✅ Configurada codificación LATIN1 para Mapuche\n";
    } catch (Exception $e) {
        echo "⚠️ No se pudo ejecutar SET client_encoding: " . $e->getMessage() . "\n";
    }

    // 🔥 CONFIGURAR CODIFICACIÓN PARA TINKUY (UTF-8)
    try {
        $conn->exec("SET NAMES 'UTF8'");
        $conn->exec("SET client_encoding TO 'UTF8'");
        echo "✅ Configurada codificación UTF-8 para Tinkuy\n";
    } catch (Exception $e) {
        echo "⚠️ No se pudo configurar UTF-8 para Tinkuy: " . $e->getMessage() . "\n";
    }

    // ✅ CONSULTA ACTUALIZADA
    $consulta_sql = "
    SELECT 
        dh03.nro_legaj as legajo,
        CONCAT(TRIM(dh01.desc_appat), ', ', TRIM(dh01.desc_nombr)) AS apellidonombre_desc,
        dh01.tipo_docum as tipo_de_doc, 
        dh01.nro_docum as num_doc,
        dh03.nro_cargo as cargo,  
        dh11.codigoescalafon as escalafon,  
        dh31.desc_dedic as dedicacion,
        dh11.desc_categ as categoria,
        dh11.codc_categ as cod_categ, 
        dh03.codc_carac as caracter, 
        dh03.fec_alta as fecha_alta,   
        dh03.fec_baja as fecha_baja,  
        CASE
            WHEN dh05.nro_legaj IS NULL AND dh05.nro_cargo IS NULL THEN 'NO'
            WHEN dl02.es_remunerada IS TRUE THEN 'Remunerada'
            ELSE 'No Remunerada'  
        END AS licencia,
        dh05.fec_hasta as fecha_hasta,
        dh30.desc_item,
        dh03.tipo_norma,
        dh03.tipo_emite,
        dh03.fec_norma,
        dh03.nro_norma
    FROM mapuche.dh03 AS dh03
    JOIN mapuche.dh11 AS dh11 ON dh11.codc_categ = dh03.codc_categ
    JOIN mapuche.dh31 AS dh31 ON dh11.codc_dedic = dh31.codc_dedic
    JOIN mapuche.dh01 AS dh01 ON dh03.nro_legaj = dh01.nro_legaj 
    LEFT JOIN mapuche.dh05 dh05 ON dh05.nro_cargo = dh03.nro_cargo
    LEFT JOIN mapuche.dh55 dh55 ON dh55.nro_cargo = dh03.nro_cargo
    LEFT JOIN mapuche.dl02 dl02 ON dl02.nrovarlicencia = dh05.nrovarlicencia
    JOIN mapuche.dh36 dh36 ON dh36.coddependesemp = dh03.coddependesemp
    JOIN mapuche.dh30 dh30 ON dh03.codc_uacad = dh30.desc_abrev
    ";

    echo "🔍 Ejecutando consulta en Mapuche...\n";
    $stmt_mapuche = $conn_m->prepare($consulta_sql);
    $stmt_mapuche->execute();

    // Verificar si hay resultados
    $rowCount = $stmt_mapuche->rowCount();
    echo "📊 Registros encontrados en Mapuche: " . $rowCount . "\n";

    if ($rowCount === 0) {
        echo "⚠️ No se encontraron registros. Verifica la consulta y los filtros.\n";
        exit;
    }

    // ✅ INSERT CORREGIDO - COINCIDIENDO EXACTAMENTE CON TU TABLA
    $insertQuery = "
    INSERT INTO docentes_mapuche (
        nro_legaj, 
        apellidonombre_desc, 
        tipo_docum, 
        nro_docum,
        num_cargo, 
        codc_grupo, 
        desc_dedic, 
        desc_categ, 
        codc_categ, 
        codc_carac, 
        fec_alta, 
        fec_baja, 
        licencia,
        fec_hasta, 
        desc_item, 
        tipo_norma,
        tipo_emite, 
        fec_norma, 
        nro_norma
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    $stmt_tinkuy = $conn->prepare($insertQuery);

    // 🚨 IMPORTANTE: NO USAR TRANSACCIÓN GLOBAL - manejar cada inserción individualmente
    $conn->exec("TRUNCATE TABLE docentes_mapuche");
    echo "🗑️ Tabla docentes_mapuche truncada\n";

    $totalRegistros = 0;
    $totalErrores = 0;
    $batchSize = 100;
    $batchCount = 0;

    $log_failed_inserts = __DIR__ . '/failed_inserts.log';
    $log_problematic_rows = __DIR__ . '/problematic_rows.log';

    echo "🔄 Iniciando transferencia de datos...\n";

    while ($row = $stmt_mapuche->fetch(PDO::FETCH_ASSOC)) {
        // 🔥 APLICAR CONVERSIÓN DE CODIFICACIÓN A TODOS LOS CAMPOS STRING
        foreach ($row as $key => $value) {
            if (is_string($value)) {
                $row[$key] = fixEncoding($value);
            }
        }

        // ✅ PARAMS CORREGIDOS - COINCIDIENDO EXACTAMENTE CON LA TABLA
        $params = [
            $row['legajo'] ?? null,
            $row['apellidonombre_desc'] ?? null,
            $row['tipo_de_doc'] ?? null,
            $row['num_doc'] ?? null,
            $row['cargo'] ?? null,                    // Se mapea a num_cargo
            $row['escalafon'] ?? null,                // Se mapea a codc_grupo
            $row['dedicacion'] ?? null,               // Se mapea a desc_dedic
            $row['categoria'] ?? null,                // Se mapea a desc_categ
            $row['cod_categ'] ?? null,                // Se mapea a codc_categ
            $row['caracter'] ?? null,                 // Se mapea a codc_carac
            $row['fecha_alta'] ?? null,               // Se mapea a fec_alta
            $row['fecha_baja'] ?? null,               // Se mapea a fec_baja
            $row['licencia'] ?? null,
            $row['fecha_hasta'] ?? null,              // Se mapea a fec_hasta
            $row['desc_item'] ?? null,
            $row['tipo_norma'] ?? null,
            $row['tipo_emite'] ?? null,
            $row['fec_norma'] ?? null,
            $row['nro_norma'] ?? null
        ];

        try {
            // 🚨 INSERT INDIVIDUAL SIN TRANSACCIÓN GLOBAL
            $stmt_tinkuy->execute($params);
            $totalRegistros++;
            $batchCount++;
            
            if ($batchCount >= $batchSize) {
                echo "📦 Lote procesado: $totalRegistros registros insertados, $totalErrores errores\n";
                $batchCount = 0;
            }
        } catch (PDOException $e) {
            $totalErrores++;
            $legajo = $row['legajo'] ?? 'DESCONOCIDO';
            $msg = date('c') . " Error insert legaj={$legajo}: " . $e->getMessage() . "\n";
            $msg .= "Row: " . json_encode($row, JSON_UNESCAPED_UNICODE) . "\n\n";
            file_put_contents($log_failed_inserts, $msg, FILE_APPEND);
            echo "❌ Error en registro legaj={$legajo}: " . $e->getMessage() . "\n";
            
            // 🚨 CONTINUAR CON EL SIGUIENTE REGISTRO A PESAR DEL ERROR
            continue;
        }
    }

    echo "\n🎊 PROCESO COMPLETADO:\n";
    echo "✅ Registros insertados exitosamente: $totalRegistros\n";
    echo "❌ Registros con errores: $totalErrores\n";
    echo "📊 Total procesado: " . ($totalRegistros + $totalErrores) . "\n";

    // Verificación final
    $count = $conn->query("SELECT COUNT(*) FROM docentes_mapuche")->fetchColumn();
    echo "📋 Registros en tabla docentes_mapuche: $count\n";

    if ($totalRegistros === 0) {
        echo "\n🔍 DEBUG: No se insertaron registros. Revisa:\n";
        echo "   1. La estructura de la tabla docentes_mapuche\n";
        echo "   2. Los tipos de datos de los campos\n";
        echo "   3. El primer registro fallido en el log: $log_failed_inserts\n";
        
        // Verificar estructura de tabla
        echo "\n🔍 Verificando estructura de tabla...\n";
        try {
            $structure = $conn->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'docentes_mapuche'")->fetchAll(PDO::FETCH_ASSOC);
            echo "Estructura de docentes_mapuche:\n";
            foreach ($structure as $col) {
                echo "  - {$col['column_name']}: {$col['data_type']}\n";
            }
        } catch (Exception $e) {
            echo "Error al verificar estructura: " . $e->getMessage() . "\n";
        }
    }

} catch (Exception $e) {
    echo "\n❌ Error crítico: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}