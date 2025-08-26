<?php
include_once("../config.php");

// Definir variables principales primero
$consulta_sql = "";
$insertQuery = "";

try {
    // 1. Verificar que las conexiones existan en config.php
    if (!isset($conn_m) || !($conn_m instanceof PDO)) {
        die("❌ Error: Conexión a Mapuche no está disponible en config.php");
    }

    if (!isset($conn) || !($conn instanceof PDO)) {
        die("❌ Error: Conexión a Tinkuy no está disponible en config.php");
    }

    echo "✅ Conexiones cargadas desde config.php\n";

    // 2. Tu consulta SQL personalizada
    echo "⚙️ Ejecutando consulta SQL en mapuche...\n";
   $consulta_sql = "
    SELECT DISTINCT ON (d03.nro_legaj, d03.nro_cargo)
        d03.nro_legaj, 
        d01.desc_appat, 
        d01.desc_nombr, 
        CONCAT(TRIM(d01.desc_appat), ', ', TRIM(d01.desc_nombr)) AS apellidonombre_desc,
        d01.tipo_docum,
        d01.nro_docum,
        dhr2.codc_dedic,           
        dh31.desc_dedic,           
        d03.nro_cargo,
        d03.codc_categ,
        d61.desc_categ,  
        d03.codc_carac,
        d35.desc_grupo,  
        d03.fec_alta, 
        d03.fec_baja,
        d05.nrovarlicencia, 
        d05.fec_hasta,
        d03.codc_uacad,
        uacad.desc_item,
        d03.coddependesemp,
        d36.descdependesemp,  
        d03.tipo_norma,
        d03.tipo_emite,
        d03.fec_norma,
        d03.nro_norma
    FROM mapuche.dh03 d03
    INNER JOIN mapuche.dh01 d01 ON d01.nro_legaj = d03.nro_legaj
    LEFT JOIN mapuche.dhr2 dhr2 ON dhr2.nro_docum = d01.nro_docum   
    LEFT JOIN mapuche.dh31 dh31 ON dh31.codc_dedic = dhr2.codc_dedic    
    LEFT JOIN mapuche.dh05 d05 ON d05.nro_legaj = d03.nro_legaj
    LEFT JOIN mapuche.dh36 d36 ON d36.coddependesemp = d03.coddependesemp
    LEFT JOIN mapuche.dh61 d61 ON d61.codc_categ = d03.codc_categ
    LEFT JOIN mapuche.dh35 d35 ON d35.codc_carac = d03.codc_carac  
    LEFT JOIN mapuche.dh30 uacad ON uacad.desc_abrev = d03.codc_uacad AND uacad.nro_tabla = 13
    WHERE 
        (d03.fec_baja > '2025-06-01' OR d03.fec_baja IS NULL)
        AND (d05.fec_hasta > '2025-06-01' OR d05.fec_hasta IS NULL)
        AND d03.codc_categ NOT IN ('Cat1','Cat2','Cat3','Cat4','Cat5','Cat6','Cat7')
    ORDER BY d03.nro_legaj, d03.nro_cargo, d05.fec_hasta DESC
";


    $stmt_mapuche = $conn_m->query($consulta_sql);
    if (!$stmt_mapuche) {
        throw new PDOException("Error al ejecutar consulta SQL: " . implode(" ", $conn_m->errorInfo()));
    }

    $insertQuery = "
INSERT INTO docentes_mapuche (
    nro_legaj, apellidonombre_desc, tipo_docum, nro_docum,
    nro_cargo, codc_categ, desc_categ, codc_carac, desc_grupo,
    fec_alta, fec_baja, nrovarlicencia, fec_hasta, codc_uacad,
    desc_item, coddependesemp, descdependesemp, tipo_norma,
    tipo_emite, fec_norma, nro_norma, codc_dedic, desc_dedic  -- 23 columnas
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)  -- 23 parámetros
"; 
    
    $stmt_tinkuy = $conn->prepare($insertQuery);
    if (!$stmt_tinkuy) {
        throw new PDOException("Error al preparar consulta de inserción: " . implode(" ", $conn->errorInfo()));
    }

    // 4. Procesamiento por lotes con transacción
    echo "⏳ Iniciando transferencia de datos a docentes_mapuche...\n";
    
    // Limpiar tabla destino
    $conn->beginTransaction();
    $conn->exec("TRUNCATE TABLE docentes_mapuche");
    
    $totalRegistros = 0;
    $batchSize = 100;
    $batchCount = 0;

    while ($row = $stmt_mapuche->fetch(PDO::FETCH_ASSOC)) {
    $stmt_tinkuy->execute([
        $row['nro_legaj'] ?? null,
        $row['apellidonombre_desc'] ?? null,
        $row['tipo_docum'] ?? null,
        $row['nro_docum'] ?? null,
        $row['nro_cargo'] ?? null,
        $row['codc_categ'] ?? null,
        $row['desc_categ'] ?? null,
        $row['codc_carac'] ?? null,
        $row['desc_grupo'] ?? null,
        $row['fec_alta'] ?? null,
        $row['fec_baja'] ?? null,
        $row['nrovarlicencia'] ?? null,
        $row['fec_hasta'] ?? null,
        $row['codc_uacad'] ?? null,
        $row['desc_item'] ?? null,
        $row['coddependesemp'] ?? null,
        $row['descdependesemp'] ?? null,
        $row['tipo_norma'] ?? null,
        $row['tipo_emite'] ?? null,
        $row['fec_norma'] ?? null,
        $row['nro_norma'] ?? null,
        $row['codc_dedic'] ?? null,    // ← NUEVO: parámetro 22
        $row['desc_dedic'] ?? null     // ← NUEVO: parámetro 23
    ]);
        
        $totalRegistros++;
        $batchCount++;
        
        if ($batchCount >= $batchSize) {
            echo "📦 Lote transferido: $totalRegistros registros\n";
            $batchCount = 0;
        }
    }

    $conn->commit();
    echo "\n✅ Transferencia completada. Total registros: $totalRegistros\n";

    // 5. Verificación final
    $count = $conn->query("SELECT COUNT(*) FROM docentes_mapuche")->fetchColumn();
    if ($count != $totalRegistros) {
        echo "⚠️ Advertencia: El conteo final ($count) no coincide con los registros transferidos ($totalRegistros)\n";
    } else {
        echo "📊 Verificación exitosa: $count registros en tabla docentes_mapuche\n";
    }

} catch (PDOException $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
        echo "🔙 Se revirtió la transacción debido a un error\n";
    }
    
    echo "\n❌ Error crítico: " . $e->getMessage() . "\n";
    echo "Código de error: " . $e->getCode() . "\n";
    
    if (!empty($consulta_sql)) {
        error_log("Consulta fallida:\n" . $consulta_sql);
    }
    
    exit(1);
} finally {
    // Cerrar statements
    if (isset($stmt_mapuche)) $stmt_mapuche = null;
    if (isset($stmt_tinkuy)) $stmt_tinkuy = null;
    
    echo "\n🔌 Script finalizado correctamente\n";
}
?>