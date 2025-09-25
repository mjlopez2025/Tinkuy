<?php
require_once 'consultas.php';
require_once '../config.php';

try {
    $dsn = "pgsql:host={$config_tinkuy['host']};port={$config_tinkuy['port']};dbname={$config_tinkuy['dbname']}";
    $conn = new PDO($dsn, $config_tinkuy['user'], $config_tinkuy['password']);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $consultas = new ConsultasDocentes($conn);
    $selected_query = $_GET['query_type'] ?? '';
    $pagina_actual = $_GET['pagina'] ?? 1;
    $registros_por_pagina = 10; // Puedes ajustar este valor

    $resultados = [];
    $titulo = 'Seleccione un grupo de docentes';
    $tiempo_ejecucion = 0;
    $total_registros = 0;
    $total_paginas = 0;

    if ($selected_query === 'combinados') {
        $inicio = microtime(true);
        
        // Obtener el total de registros
        $total_registros = $consultas->contarDocentesCombinados();
        $total_paginas = ceil($total_registros / $registros_por_pagina);
        
        // Calcular offset
        $offset = ($pagina_actual - 1) * $registros_por_pagina;
        
        // Obtener resultados paginados
        $resultados = $consultas->docentesCombinados($registros_por_pagina, $offset);
        $titulo = 'Docentes Combinados';
        
        $fin = microtime(true);
        $tiempo_ejecucion = number_format($fin - $inicio, 2);
    }

    include 'resultados.html.php';

} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>