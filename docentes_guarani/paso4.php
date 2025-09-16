<?php
include_once("../config.php");

// =========================================
// SCRIPT MEJORADO PARA LIMPIAR PREFIJOS Y TIPOS DE DOC
// =========================================
echo "\n\n🔥 Iniciando limpieza COMPLETA en 'docentes_guarani'...\n";

$tabla = 'docentes_guarani';         
$campo = 'docente_guarani';      
$campo_tipo_doc = 'tipo_doc_guarani'; // Columna a limpiar

try {
    // ---- PARTE 1: Limpieza de prefijos ----
    echo "🔧 FASE 1: Limpieza de prefijos\n";
    $query = "
        SELECT id, $campo, $campo_tipo_doc 
        FROM $tabla 
        WHERE $campo ~* '^(Adjunto, |Titular, |JTP, |Asociado, |Ayudante 1ra, |Ayudante 2da, |\\.-, |\\s-|^\\s*\\.,)'
           OR $campo_tipo_doc IS NOT NULL
    ";
    $stmt = $conn->query($query);
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "📊 Registros a procesar: " . count($registros) . "\n\n";

    $procesados = 0;
    $modificados = [];
    $tipos_invalidos = 0;

    // Contador de progreso
    $total = count($registros);
    $inicio = time();

    foreach ($registros as $index => $registro) {
        $id = $registro['id'];
        $texto_original = $registro[$campo];
        $tipo_doc_original = $registro[$campo_tipo_doc];

        // ---- Limpieza de prefijos ----
        $nuevo_texto = preg_replace([
            '/^(Adjunto, |Titular, |JTP, |Asociado, |Ayudante 1ra, |Ayudante 2da, |.-, |\.-|\s-|\s*\s*)/i',
            '/^[\s\.\-]+/',
            '/\s*\s*(?= DNI| DNT| CI| LC)/i'
        ], '', $texto_original);

        // ---- Validación de tipo de documento ----
        $tipo_doc_limpio = null;
        if (!empty($tipo_doc_original)) {
            // Mantener solo DNI, DNT, CI, LC (case-insensitive)
            if (preg_match('/^( DNI| DNT| CI| LC)$/i', trim($tipo_doc_original))) {
                $tipo_doc_limpio = strtoupper(trim($tipo_doc_original));
            } else {
                $tipos_invalidos++;
                $tipo_doc_limpio = null; // Borrar tipos no válidos
            }
        }

        // Solo actualizar si hubo cambios
        if ($nuevo_texto !== $texto_original || $tipo_doc_limpio !== $tipo_doc_original) {
            $update = $conn->prepare("
                UPDATE $tabla 
                SET $campo = :nuevo_texto, 
                    $campo_tipo_doc = :tipo_doc 
                WHERE id = :id
            ");
            $update->execute([
                ':nuevo_texto' => trim($nuevo_texto),
                ':tipo_doc' => $tipo_doc_limpio,
                ':id' => $id
            ]);

            $procesados++;
            $modificados[] = [
                'id' => $id,
                'antes' => $texto_original,
                'despues' => trim($nuevo_texto),
                'tipo_antes' => $tipo_doc_original,
                'tipo_despues' => $tipo_doc_limpio
            ];
        }

        // Mostrar progreso
        if (($index + 1) % 100 === 0 || ($index + 1) === $total) {
            $porcentaje = round(($index + 1) / $total * 100, 2);
            $tiempo = time() - $inicio;
            echo "\r🔄 Progreso: " . ($index + 1) . "/$total ($porcentaje%) | ⏱️ $tiempo seg";
            flush();
        }
    }

    // ---- RESULTADOS ----
    echo "\n\n✅ ¡Limpieza completada en " . (time() - $inicio) . " segundos!\n";
    echo "📝 Registros modificados: $procesados\n";
    echo "🚮 Tipos de documento eliminados: $tipos_invalidos\n\n";

    // Mostrar ejemplos
    echo "🔍 Ejemplos de cambios:\n";
    foreach (array_slice($modificados, 0, 5) as $mod) {
        echo "  ID {$mod['id']}:\n";
        echo "    TEXTO ANTES: '{$mod['antes']}'\n";
        echo "    TEXTO AHORA: '{$mod['despues']}'\n";
        echo "    TIPO DOC ANTES: '" . ($mod['tipo_antes'] ?? 'NULL') . "'\n";
        echo "    TIPO DOC AHORA: '" . ($mod['tipo_despues'] ?? 'NULL') . "'\n\n";
    }

} catch (PDOException $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
}