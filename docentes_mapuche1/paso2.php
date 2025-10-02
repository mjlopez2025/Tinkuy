<?php
// paso2_fix_encoding.php
include_once("../config.php");

// --- Helpers de codificación ---
function is_utf8_str($str) {
    return (is_string($str) && @preg_match('//u', $str) === 1);
}

function fixEncoding($value) {
    if ($value === null) return null;
    if (!is_string($value)) return $value;
    if (is_utf8_str($value)) return $value;
    
    $out = @iconv('ISO-8859-1', 'UTF-8//TRANSLIT', $value);
    if ($out !== false) return $out;
    
    $out = @utf8_encode($value);
    return $out !== false ? $out : $value;
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

    try {
        $conn_m->exec("SET client_encoding TO 'LATIN1'");
    } catch (Exception $e) {
        echo "⚠️ No se pudo ejecutar SET client_encoding: " . $e->getMessage() . "\n";
    }

    // --- CONSULTA ORIGINAL + LEFT JOIN dh55 para calcular es_remunerada ---
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
        d11.codigoescalafon,
        d89.descesc,
        d03.codc_categ,
        d11.desc_categ,  
        d03.codc_carac,
        d35.desc_grupo,  
        d03.fec_alta, 
        d03.fec_baja,
        d05.fec_hasta,
        d03.codc_uacad,
        uacad.desc_item,
        d03.coddependesemp,
        d36.descdependesemp,  
        d03.tipo_norma,
        d03.tipo_emite,
        d03.fec_norma,
        d03.nro_norma,
        dh55.nro_legaj AS dh55_nro_legaj,
        dh55.nro_cargo AS dh55_nro_cargo,
        dh55.fec_hasta AS dh55_fec_hasta,
        dh55.nrovarlicencia AS dh55_nrovarlicencia
    FROM mapuche.dh03 d03
    INNER JOIN mapuche.dh01 d01 ON d01.nro_legaj = d03.nro_legaj
    LEFT JOIN mapuche.dhr2 dhr2 ON dhr2.nro_docum = d01.nro_docum   
    LEFT JOIN mapuche.dh31 dh31 ON dh31.codc_dedic = dhr2.codc_dedic    
    LEFT JOIN mapuche.dh05 d05 ON d05.nro_legaj = d03.nro_legaj
    LEFT JOIN mapuche.dh36 d36 ON d36.coddependesemp = d03.coddependesemp
    LEFT JOIN mapuche.dh35 d35 ON d35.codc_carac = d03.codc_carac  
    LEFT JOIN mapuche.dh30 uacad ON uacad.desc_abrev = d03.codc_uacad AND uacad.nro_tabla = 13
    JOIN mapuche.dh11 d11 ON d03.codc_categ = d11.codc_categ
    LEFT JOIN mapuche.dh89 d89 ON d11.codigoescalafon = d89.codigoescalafon
    LEFT JOIN mapuche.dh55 dh55 ON (dh55.nro_legaj = d03.nro_legaj OR dh55.nro_cargo = d03.nro_cargo)
    WHERE 
        (d03.fec_baja > '2025-06-01' OR d03.fec_baja IS NULL)
        AND (d05.fec_hasta > '2025-06-01' OR d05.fec_hasta IS NULL)
        AND d03.codc_categ NOT IN ('Cat1','Cat2','Cat3','Cat4','Cat5','Cat6','Cat7')
    ORDER BY d03.nro_legaj, d03.nro_cargo, d05.fec_hasta DESC;
    ";

    $stmt_mapuche = $conn_m->prepare($consulta_sql);
    $stmt_mapuche->execute();

    $insertQuery = "
    INSERT INTO docentes_mapuche (
        nro_legaj, apellidonombre_desc, tipo_docum, nro_docum,
        nro_cargo, codc_categ, desc_categ, codc_carac, desc_grupo,
        fec_alta, fec_baja, es_remunerada, fec_hasta, codc_uacad,
        desc_item, coddependesemp, descdependesemp, tipo_norma,
        tipo_emite, fec_norma, nro_norma, codc_dedic, desc_dedic,
        descesc, codigoescalafon
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    $stmt_tinkuy = $conn->prepare($insertQuery);

    $conn->beginTransaction();
    $conn->exec("TRUNCATE TABLE docentes_mapuche");

    $totalRegistros = 0;
    $batchSize = 100;
    $batchCount = 0;

    $log_failed_inserts = __DIR__ . '/failed_inserts.log';
    $log_problematic_rows = __DIR__ . '/problematic_rows.log';

    $hoy = date('Y-m-d');

    while ($row = $stmt_mapuche->fetch(PDO::FETCH_ASSOC)) {
        foreach ($row as $k => $v) {
            if (is_string($v)) {
                $fixed = fixEncoding($v);
                $row[$k] = $fixed !== false && $fixed !== null ? $fixed : $v;
            }
        }

        // --- Lógica es_remunerada ---
        $es_remunerada = "NO"; // default

        if (!empty($row['dh55_fec_hasta'])) {
            if ($row['dh55_fec_hasta'] >= $hoy) {
                if (!empty($row['dh55_nrovarlicencia'])) {
                    $stmt_dl02 = $conn_m->prepare("SELECT es_remunerada FROM mapuche.dl02 WHERE nrovarlicencia = ?");
                    $stmt_dl02->execute([$row['dh55_nrovarlicencia']]);
                    $dl02_row = $stmt_dl02->fetch(PDO::FETCH_ASSOC);
                    if ($dl02_row && ($dl02_row['es_remunerada'] === 't' || $dl02_row['es_remunerada'] === true)) {
                        $es_remunerada = "Es Remunerada";
                    } else {
                        $es_remunerada = "No Remunerada";
                    }
                } else {
                    $es_remunerada = "Es Remunerada";
                }
            } else {
                $es_remunerada = "NO";
            }
        }

        $params = [
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
            $es_remunerada,
            $row['fec_hasta'] ?? null,
            $row['codc_uacad'] ?? null,
            $row['desc_item'] ?? null,
            $row['coddependesemp'] ?? null,
            $row['descdependesemp'] ?? null,
            $row['tipo_norma'] ?? null,
            $row['tipo_emite'] ?? null,
            $row['fec_norma'] ?? null,
            $row['nro_norma'] ?? null,
            $row['codc_dedic'] ?? null,
            $row['desc_dedic'] ?? null,
            $row['descesc'] ?? null,
            $row['codigoescalafon'] ?? null
        ];

        try {
            $stmt_tinkuy->execute($params);
        } catch (PDOException $e) {
            $msg = date('c') . " Error insert legaj={$row['nro_legaj']}: " . $e->getMessage() . "\n";
            $msg .= "Row: " . json_encode($row, JSON_UNESCAPED_UNICODE) . "\n\n";
            file_put_contents($log_failed_inserts, $msg, FILE_APPEND);
            continue;
        }

        $totalRegistros++;
        $batchCount++;
        if ($batchCount >= $batchSize) {
            echo "📦 Lote transferido: $totalRegistros registros\n";
            $batchCount = 0;
        }
    }

    $conn->commit();
    echo "\n✅ Transferencia completada. Total registros: $totalRegistros\n";

    $count = $conn->query("SELECT COUNT(*) FROM docentes_mapuche")->fetchColumn();
    echo "📊 Registros en tabla docentes_mapuche: $count\n";

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
        echo "🔙 Se revirtió la transacción debido a un error\n";
    }
    echo "\n❌ Error crítico: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
