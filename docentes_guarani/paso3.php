<?php
include_once("../config.php");

echo "==========================================================================\n";
echo "\nPaso 3. Separando registros con múltiples docentes...\n";
echo "==========================================================================\n";

try {
    // 1. Contar registros iniciales
    $total_inicial = $conn->query("SELECT COUNT(*) FROM Docentes_Guarani")->fetchColumn();
    echo "Registros iniciales: $total_inicial\n";

    // 2. Identificar registros con múltiples docentes (separados por " - ")
    $registros_multidocentes = $conn->query("
        SELECT COUNT(*) FROM Docentes_Guarani 
        WHERE docente_guarani LIKE '% - %'
    ")->fetchColumn();
    
    $registros = $conn->query("
        SELECT * FROM Docentes_Guarani 
        WHERE docente_guarani LIKE '% - %'
    ");

    // Inicializar contador de progreso
    $procesados = 0;
    $inicio = time();

    echo "\n🔍 Procesando $registros_multidocentes registros con múltiples docentes...\n";

    // 3. Procesar cada registro
    foreach ($registros->fetchAll(PDO::FETCH_ASSOC) as $registro) {
        $procesados++;
        $porcentaje = round(($procesados / $registros_multidocentes) * 100, 2);
        
        // Mostrar progreso cada 100 registros
        if ($procesados % 100 === 0 || $procesados === $registros_multidocentes) {
            echo "\r🔄 Progreso: $procesados/$registros_multidocentes ($porcentaje%) | ";
        }

        // Separar docentes (usando " - " como delimitador)
        $docentes = explode(" - ", $registro['docente_guarani']);

        // === Actualizar el primer docente en el registro original ===
        $conn->prepare("
            UPDATE Docentes_Guarani 
            SET docente_guarani = ?
            WHERE id = ?
        ")->execute([
            trim($docentes[0]),
            $registro['id']
        ]);

        // === Insertar docentes adicionales ===
        for ($i = 1; $i < count($docentes); $i++) {
            $nuevoRegistro = $registro;
            unset($nuevoRegistro['id']);
            $nuevoRegistro['docente_guarani'] = trim($docentes[$i]);

            $campos = implode(", ", array_keys($nuevoRegistro));
            $placeholders = implode(", ", array_fill(0, count($nuevoRegistro), "?"));

            $conn->prepare("
                INSERT INTO Docentes_Guarani ($campos) 
                VALUES ($placeholders)
            ")->execute(array_values($nuevoRegistro));
        }
    }

    // 4. Verificación final
    $total_final = $conn->query("SELECT COUNT(*) FROM Docentes_Guarani")->fetchColumn();
    $nuevos_registros = $total_final - $total_inicial;
    $tiempo_total = time() - $inicio;

    echo "\n\n✅ ¡Separación completada en $tiempo_total segundos!\n";
    echo "Registros iniciales: $total_inicial\n";
    echo "Registros nuevos creados: $nuevos_registros\n";
    echo "Total de registros ahora: $total_final\n\n";

} catch (PDOException $e) {
    echo "\n🚨 Error: " . $e->getMessage() . "\n";
}