<?php
include_once("../config.php");

echo "\nIniciando normalización de campos vacíos o NULL...\n";

try {
    $conn->beginTransaction();

    // Normalizar docente_guarani
    $stmt1 = $conn->prepare("
        UPDATE Docentes_Guarani
        SET docente_guarani = 'Sin Información'
        WHERE docente_guarani IS NULL 
           OR TRIM(LOWER(docente_guarani)) IN ('', 'vacio', 'null')
    ");
    $stmt1->execute();
    $afectados1 = $stmt1->rowCount();

    // Normalizar tipo_doc_guarani
    $stmt2 = $conn->prepare("
        UPDATE Docentes_Guarani
        SET tipo_doc_guarani = 'Sin Información'
        WHERE tipo_doc_guarani IS NULL 
           OR TRIM(LOWER(tipo_doc_guarani)) IN ('', 'vacio', 'null')
    ");
    $stmt2->execute();
    $afectados2 = $stmt2->rowCount();

    // Normalizar num_doc_guarani
    $stmt3 = $conn->prepare("
        UPDATE Docentes_Guarani
        SET num_doc_guarani = 'Sin Información'
        WHERE num_doc_guarani IS NULL 
           OR TRIM(LOWER(num_doc_guarani)) IN ('', 'vacio', 'null')
    ");
    $stmt3->execute();
    $afectados3 = $stmt3->rowCount();

    $conn->commit();

    echo "\n🎉 Normalización completada:";
    echo "\n- docente_guarani actualizados: $afectados1";
    echo "\n- tipo_doc_guarani actualizados: $afectados2";
    echo "\n- num_doc_guarani actualizados: $afectados3\n";

} catch (PDOException $e) {
    $conn->rollBack();
    echo "\n🚨 Error en normalización: " . $e->getMessage();
    echo "\n[Transacción revertida]\n";
}

// =============================================
// BLOQUE 3: LIMPIEZA Y ELIMINACIÓN DE COLUMNAS ADICIONALES
// =============================================
echo "\n\nIniciando limpieza y eliminación de columnas adicionales...\n";

try {
    $conn->beginTransaction();

    $columnas = [
        'ape_nom1_guarani', 'tipo_doc1_guarani', 'num_doc1_guarani',
        'ape_nom2_guarani', 'tipo_doc2_guarani', 'num_doc2_guarani',
        'ape_nom3_guarani', 'tipo_doc3_guarani', 'num_doc3_guarani',
        'ape_nom4_guarani', 'tipo_doc4_guarani', 'num_doc4_guarani',
        'ape_nom5_guarani', 'tipo_doc5_guarani', 'num_doc5_guarani'
    ];

    $total_limpiados = 0;
    $total_eliminados = 0;

    foreach ($columnas as $columna) {
        $existe = $conn->query("
            SELECT EXISTS (
                SELECT 1 
                FROM information_schema.columns 
                WHERE table_name = 'docentes_guarani' 
                AND column_name = '$columna'
            )
        ")->fetchColumn();

        if ($existe) {
            $conn->exec("UPDATE Docentes_Guarani SET $columna = NULL");
            $afectados = $conn->query("SELECT COUNT(*) FROM Docentes_Guarani WHERE $columna IS NOT NULL")->fetchColumn();
            $conn->exec("ALTER TABLE Docentes_Guarani DROP COLUMN $columna");

            echo "\n✔️ Columna '$columna':";
            echo "\n   - Limpiada (registros afectados: $afectados)";
            echo "\n   - Eliminada permanentemente";

            $total_limpiados += $afectados;
            $total_eliminados++;
        } else {
            echo "\n⚠️ Columna '$columna' no existe en la tabla, se omite";
        }
    }

    $conn->commit();

    echo "\n\n🎉 PROCESO COMPLETADO:";
    echo "\n- Columnas procesadas: " . count($columnas);
    echo "\n- Columnas eliminadas: $total_eliminados";
    echo "\n- Registros limpiados: $total_limpiados";
    echo "\n[Transacción confirmada]\n";

} catch (PDOException $e) {
    $conn->rollBack();
    echo "\n🚨 ERROR DURANTE EL PROCESO: " . $e->getMessage();
    echo "\n[Transacción revertida - Ningún cambio aplicado]\n";
}
