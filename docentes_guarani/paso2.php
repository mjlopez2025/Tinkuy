<?php
include_once("../config.php");

try {
    // Conexión a la base Wichi (solo para leer datos)
    $conn_wichi->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Conexión a la base local (para guardar datos)
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Consulta SQL completa desde Wichi
    $sql_query = "WITH sql_olap_ft AS (
        SELECT 
            ft_rendimiento.responsable_academica_id, 
            ft_rendimiento.propuesta_formativa_id, 
            ft_rendimiento.comision_id, 
            ft_rendimiento.periodo_id, 
            ft_rendimiento.docente_id, 
            ft_rendimiento.actividad_id, 
            ft_rendimiento.anio_academico, 
            sum(ft_rendimiento.cantidad_registros_cursada) AS medida_cantidad_registros_cursada 
        FROM 
            (SELECT * FROM guarani3.ft_rendimiento) ft_rendimiento 
        WHERE 
            true AND (ft_rendimiento.cantidad_registros_cursada is not null) 
        GROUP BY 
            ft_rendimiento.responsable_academica_id,
            ft_rendimiento.propuesta_formativa_id,
            ft_rendimiento.comision_id,
            ft_rendimiento.periodo_id,
            ft_rendimiento.docente_id,
            ft_rendimiento.actividad_id,
            ft_rendimiento.anio_academico 
        ORDER BY 
            ft_rendimiento.responsable_academica_id,
            ft_rendimiento.propuesta_formativa_id,
            ft_rendimiento.comision_id,
            ft_rendimiento.periodo_id,
            ft_rendimiento.docente_id,
            ft_rendimiento.actividad_id,
            ft_rendimiento.anio_academico
    ) 
    SELECT 
        d_responsable_academica.responsable_academica_desc, 
        d_propuesta_formativa.propuesta_formativa_desc, 
        d_comision.comision_desc, 
        v_periodo_anio_academico_x_tipo_periodo.periodo_desc, 
        d_docente.docente_desc, 
        d_actividad.actividad_desc, 
        d_anio.anio_id, 
        sum(sql_olap_ft.medida_cantidad_registros_cursada) AS cantidad_registros_cursada 
    FROM 
        sql_olap_ft 
    INNER JOIN guarani3.d_responsable_academica d_responsable_academica ON d_responsable_academica.responsable_academica_id = sql_olap_ft.responsable_academica_id 
    INNER JOIN guarani3.d_propuesta_formativa d_propuesta_formativa ON d_propuesta_formativa.propuesta_formativa_id = sql_olap_ft.propuesta_formativa_id 
    INNER JOIN guarani3.d_comision d_comision ON d_comision.comision_id = sql_olap_ft.comision_id 
    INNER JOIN guarani3.v_periodo_anio_academico_x_tipo_periodo v_periodo_anio_academico_x_tipo_periodo ON v_periodo_anio_academico_x_tipo_periodo.periodo_id = sql_olap_ft.periodo_id 
    INNER JOIN guarani3.d_docente d_docente ON d_docente.docente_id = sql_olap_ft.docente_id 
    INNER JOIN guarani3.d_actividad d_actividad ON d_actividad.actividad_id = sql_olap_ft.actividad_id 
    INNER JOIN public.d_anio d_anio ON d_anio.anio_id = sql_olap_ft.anio_academico 
    GROUP BY 
        d_responsable_academica.responsable_academica_desc,
        d_propuesta_formativa.propuesta_formativa_desc,
        d_comision.comision_desc,
        v_periodo_anio_academico_x_tipo_periodo.periodo_desc,
        d_docente.docente_desc,
        d_actividad.actividad_desc,
        d_anio.anio_id 
    ORDER BY 
        d_responsable_academica.responsable_academica_desc,
        d_propuesta_formativa.propuesta_formativa_desc,
        d_comision.comision_desc,
        v_periodo_anio_academico_x_tipo_periodo.periodo_desc,
        d_docente.docente_desc,
        d_actividad.actividad_desc,
        d_anio.anio_id";

    // Ejecutar consulta
    $stmt_query = $conn_wichi->prepare($sql_query);
    $stmt_query->execute();
    $resultados = $stmt_query->fetchAll(PDO::FETCH_ASSOC);
    
    // Consulta preparada para INSERT
    $sql_insert = "INSERT INTO Docentes_Guarani (
        responsabilidad_academica_guarani, 
        propuesta_formativa_guarani, 
        comision_guarani, 
        periodo_guarani, 
        anio_guarani,
        docente_guarani, 
        tipo_doc_guarani,
        num_doc_guarani,
        actividad_guarani, 
        cursados_guarani,
        ape_nom1_Guarani,
        tipo_doc1_Guarani,
        num_doc1_Guarani,
        ape_nom2_Guarani,
        tipo_doc2_Guarani,
        num_doc2_Guarani,
        ape_nom3_Guarani,
        tipo_doc3_Guarani,
        num_doc3_Guarani,
        ape_nom4_Guarani,
        tipo_doc4_Guarani,
        num_doc4_Guarani
    ) VALUES (
        :responsabilidad_academica, 
        :propuesta_formativa, 
        :comision, 
        :periodo, 
        :anio,
        :docente, 
        :tipo_doc_guarani,
        :num_doc_guarani,
        :actividad, 
        :cursados,
        :ape_nom1_Guarani,
        :tipo_doc1_Guarani,
        :num_doc1_Guarani,
        :ape_nom2_Guarani,
        :tipo_doc2_Guarani,
        :num_doc2_Guarani,
        :ape_nom3_Guarani,
        :tipo_doc3_Guarani,
        :num_doc3_Guarani,
        :ape_nom4_Guarani,
        :tipo_doc4_Guarani,
        :num_doc4_Guarani
    )";

    $stmt = $conn->prepare($sql_insert);
    $importedCount = 0;
    $totalRegistros = count($resultados);
    $inicio = time();

    echo "===========================================================\n";
    echo "Paso 2. Iniciando importación desde datos de consulta SQL...\n";
    echo "Total de registros a procesar: $totalRegistros\n";
    echo "===========================================================\n\n";

    foreach ($resultados as $fila) {
        $importedCount++;
        $porcentaje = round(($importedCount / $totalRegistros) * 100, 2);
        $tiempoTranscurrido = time() - $inicio;
        $registrosPorSegundo = ($tiempoTranscurrido > 0) ? round($importedCount / $tiempoTranscurrido, 2) : 0;
        $tiempoRestante = ($registrosPorSegundo > 0) ? round(($totalRegistros - $importedCount) / $registrosPorSegundo) : 0;
        
        // Mostrar progreso cada 100 registros o en el último
        if ($importedCount % 100 === 0 || $importedCount === $totalRegistros) {
            echo "\r🔄 Progreso: $importedCount/$totalRegistros ($porcentaje%) | ";
        }

        // Procesamiento de datos
        $responsabilidad_academica = trim($fila['responsable_academica_desc']);
        $propuesta_formativa = trim($fila['propuesta_formativa_desc']);
        $comision = trim($fila['comision_desc']);
        $periodo = trim($fila['periodo_desc']);
        $anio = substr($periodo, 0, 4);
        $docente = trim($fila['docente_desc']);
        $actividad = trim($fila['actividad_desc']);
        $cursados = trim($fila['cantidad_registros_cursada']);

        // Procesar docentes
        $docentes = explode('-', $docente);
        $docentes = array_map('trim', $docentes);
        $primer_docente = $docentes[0] ?? null;
        
        // Extraer tipo y número de documento del primer docente
        $tipo_doc_guarani = null;
        $num_doc_guarani = null;
        if ($primer_docente) {
            $datos = array_map('trim', explode(',', $primer_docente));
            if (count($datos) >= 3) {
                $tipo_doc_guarani = $datos[1] !== '' ? $datos[1] : null;
                $num_doc_guarani = is_numeric($datos[2]) ? (int)$datos[2] : null;
            }
        }

        // Bind parameters
        $stmt->bindValue(':responsabilidad_academica', $responsabilidad_academica);
        $stmt->bindValue(':propuesta_formativa', $propuesta_formativa);
        $stmt->bindValue(':comision', $comision);
        $stmt->bindValue(':periodo', $periodo);
        $stmt->bindValue(':anio', $anio);
        $stmt->bindValue(':docente', $docente);
        $stmt->bindValue(':tipo_doc_guarani', $tipo_doc_guarani, PDO::PARAM_STR);
        $stmt->bindValue(':num_doc_guarani', $num_doc_guarani, is_null($num_doc_guarani) ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':actividad', $actividad);
        $stmt->bindValue(':cursados', $cursados);

        // Procesar hasta 4 docentes
        $docentesParams = [];
        for ($i = 1; $i <= 4; $i++) {
            $docentesParams["ape_nom{$i}_Guarani"] = '';
            $docentesParams["tipo_doc{$i}_Guarani"] = '';
            $docentesParams["num_doc{$i}_Guarani"] = '';
        }

        foreach ($docentes as $index => $docenteStr) {
            $docenteData = array_map('trim', explode(',', $docenteStr));
            if (count($docenteData) >= 3) {
                $i = $index + 1;
                if ($i <= 4) {
                    $docentesParams["ape_nom{$i}_Guarani"] = $docenteData[0];
                    $docentesParams["tipo_doc{$i}_Guarani"] = $docenteData[1];
                    $docentesParams["num_doc{$i}_Guarani"] = $docenteData[2];
                }
            }
        }

        // Bind parameters de docentes
        foreach ($docentesParams as $param => $value) {
            $stmt->bindValue(":{$param}", $value);
        }

        $stmt->execute();
    }

    $tiempoTotal = time() - $inicio;
    echo "\n\n✅ ¡Importación completada en $tiempoTotal segundos!\n";
    echo "Total de registros obtenidos: $totalRegistros\n";
    echo "Total de registros importados: $importedCount\n";
    echo "Velocidad promedio: " . round($importedCount / $tiempoTotal, 2) . " registros/segundo\n";

} catch (PDOException $e) {
    echo "\n🚨 Error de base de datos: " . $e->getMessage() . "\n";
    echo "Consulta que falló: " . (isset($sql_insert) ? $sql_insert : $sql_query) . "\n";
} catch (Exception $e) {
    echo "\n🚨 Error general: " . $e->getMessage() . "\n";
}