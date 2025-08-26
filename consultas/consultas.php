<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

include_once("../config.php");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class ConsultasDocentes {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // ================================
// 1. DOCENTES COMBINADOS 
// ================================
public function docentesCombinados($page = 1, $perPage = 50, $searchTerm = '', $year = null) {
    $offset = ($page - 1) * $perPage;
    $joinCondition = "ON m.nro_docum::VARCHAR = g.num_doc_guarani";
    $whereConditions = [];
    $params = [];

    // ✅ EXCLUIR Cat1 a Cat7 (AGREGAR ESTA LÍNEA)
    $whereConditions[] = "m.codc_categ NOT IN ('Cat1', 'Cat2', 'Cat3', 'Cat4', 'Cat5', 'Cat6', 'Cat7')";

    // Búsqueda por nombre
    if (!empty($searchTerm)) {
        $whereConditions[] = "m.apellidonombre_desc ILIKE :searchTerm";
        $params[':searchTerm'] = '%' . $searchTerm . '%';
    }

    // Filtro por año
    if ($year !== null && $year !== '' && $year !== 'all') {
        $whereConditions[] = "g.anio_guarani = :year";
        $params[':year'] = (int)$year;
    }

    $additionalWhere = '';
    if (!empty($whereConditions)) {
        $additionalWhere = 'WHERE ' . implode(' AND ', $whereConditions);
    }

    $sql = "SELECT 
        m.nro_legaj AS \"Nro Legajo (M)\",
        m.apellidonombre_desc AS \"Apellido y Nombre (M)\",
        m.tipo_docum AS \"Tipo Doc. (M)\",
        m.nro_docum AS \"Nro Doc. (M)\",
        m.nro_cargo::TEXT AS \"Cargo (M)\",
        m.desc_dedic AS \"Dedicacion (M)\",
        m.desc_categ AS \"Categoría (M)\",
        m.desc_grupo AS \"Carácter (M)\",
        m.fec_alta::TEXT AS \"Fecha Alta (M)\",
        m.fec_baja::TEXT AS \"Fecha Baja (M)\",
        m.nrovarlicencia AS \"Licencia (M)\",
        m.fec_hasta::TEXT AS \"Fecha Hasta (M)\",
        m.desc_item AS \"Unidad Acad.(M)\",
        m.tipo_norma AS \"Tipo Norma (M)\",
        m.tipo_emite AS \"Tipo Emite (M)\",
        m.fec_norma::TEXT AS \"Fecha Norma (M)\",
        m.nro_norma AS \"Nro Norma (M)\",
        g.responsabilidad_academica_guarani AS \"Resp Acad (G)\",
        g.propuesta_formativa_guarani AS \"Propuesta (G)\", 
        g.comision_guarani AS \"Com (G)\",
        g.anio_guarani::TEXT AS \"Año (G)\",
        g.periodo_guarani AS \"Período (G)\",
        g.actividad_guarani AS \"Actividad (G)\",
        g.cursados_guarani AS \"Est (G)\"
    FROM docentes_mapuche AS m 
    INNER JOIN docentes_guarani AS g 
        $joinCondition
    $additionalWhere
    GROUP BY
        m.nro_legaj, m.apellidonombre_desc, m.tipo_docum, m.nro_docum, m.nro_cargo,
        m.desc_dedic,
        m.codc_categ, m.desc_categ, m.codc_carac, m.desc_grupo, m.fec_alta,
        m.fec_baja, m.nrovarlicencia, m.fec_hasta, m.codc_uacad, m.desc_item,
        m.coddependesemp, m.descdependesemp, m.tipo_norma, m.tipo_emite, m.fec_norma,
        m.nro_norma, g.responsabilidad_academica_guarani,
        g.propuesta_formativa_guarani, g.comision_guarani, g.anio_guarani,
        g.periodo_guarani, g.actividad_guarani, g.cursados_guarani
    ORDER BY m.apellidonombre_desc";

    // ✅ SOLO APLICAR LIMIT SI NO ES EXPORTACIÓN ($perPage > 0)
    if ($perPage > 0) {
        $sql .= " LIMIT :limit OFFSET :offset";
    }

    $stmt = $this->conn->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    
    // ✅ SOLO BINDEAR LIMIT/OFFSET SI NO ES EXPORTACIÓN
    if ($perPage > 0) {
        $stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    }

    $stmt->execute();

    return [
        'data'  => $stmt->fetchAll(PDO::FETCH_ASSOC),
        'total' => $this->contarDocentesCombinados($searchTerm, $year)
    ];
}

    public function contarDocentesCombinados($searchTerm = '', $year = null) {
    $joinCondition = "ON m.nro_docum::VARCHAR = g.num_doc_guarani";
    $whereConditions = [];
    $params = [];

    // ✅ EXCLUIR Cat1 a Cat7 (AGREGAR ESTA LÍNEA)
    $whereConditions[] = "m.codc_categ NOT IN ('Cat1', 'Cat2', 'Cat3', 'Cat4', 'Cat5', 'Cat6', 'Cat7')";

    if (!empty($searchTerm)) {
        $whereConditions[] = "m.apellidonombre_desc ILIKE :searchTerm";
        $params[':searchTerm'] = '%' . $searchTerm . '%';
    }

    if ($year !== null && $year !== '' && $year !== 'all') {
        $whereConditions[] = "g.anio_guarani = :year";
        $params[':year'] = (int)$year;
    }

    $additionalWhere = '';
    if (!empty($whereConditions)) {
        $additionalWhere = 'WHERE ' . implode(' AND ', $whereConditions);
    }

    $sql = "SELECT COUNT(*) FROM (
        SELECT 
            m.nro_legaj, m.apellidonombre_desc, m.tipo_docum, m.nro_docum, m.nro_cargo,
            m.codc_categ, m.desc_categ, m.codc_carac, m.desc_grupo, m.fec_alta,
            m.fec_baja, m.nrovarlicencia, m.fec_hasta, m.codc_uacad, m.desc_item,
            m.coddependesemp, m.descdependesemp, m.tipo_norma, m.tipo_emite, m.fec_norma,
            m.nro_norma, g.responsabilidad_academica_guarani,
            g.propuesta_formativa_guarani, g.comision_guarani, g.anio_guarani,
            g.periodo_guarani, g.actividad_guarani, g.cursados_guarani
        FROM docentes_mapuche AS m
        INNER JOIN docentes_guarani AS g 
            $joinCondition
        $additionalWhere
        GROUP BY
            m.nro_legaj, m.apellidonombre_desc, m.tipo_docum, m.nro_docum, m.nro_cargo,
            m.codc_categ, m.desc_categ, m.codc_carac, m.desc_grupo, m.fec_alta,
            m.fec_baja, m.nrovarlicencia, m.fec_hasta, m.codc_uacad, m.desc_item,
            m.coddependesemp, m.descdependesemp, m.tipo_norma, m.tipo_emite, m.fec_norma,
            m.nro_norma, g.responsabilidad_academica_guarani,
            g.propuesta_formativa_guarani, g.comision_guarani, g.anio_guarani,
            g.periodo_guarani, g.actividad_guarani, g.cursados_guarani
    ) AS subquery";

    $stmt = $this->conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();
    return (int)$stmt->fetchColumn();
}

    // ================================
// 2. DOCENTES MAPUCHE 
// ================================
public function obtenerDocentesMapuche($page = 1, $perPage = 50, $searchTerm = '', $year = null) {
    $offset = ($page - 1) * $perPage;
    $whereClause = '';
    $params = [];
    $conditions = [];
    
    // ✅ EXCLUIR Cat1 a Cat7 - VERSIÓN CORREGIDA
    $conditions[] = "(COALESCE(codc_categ, '') NOT IN ('Cat1','Cat2','Cat3','Cat4','Cat5','Cat6','Cat7','1','2','3','4','5','6','7')
                AND COALESCE(desc_categ, '') NOT IN ('Cat1','Cat2','Cat3','Cat4','Cat5','Cat6','Cat7','1','2','3','4','5','6','7'))";


    
    if (!empty($searchTerm)) {
        $conditions[] = "apellidonombre_desc ILIKE :searchTerm";
        $params[':searchTerm'] = '%' . $searchTerm . '%';
    }
    
    if ($year !== null && $year !== 'all') {
        $conditions[] = "anio_id = :year";
        $params[':year'] = $year;
    }
    
    if (!empty($conditions)) {
        $whereClause = " WHERE " . implode(" AND ", $conditions);
    }

    $sql = "SELECT 
        DISTINCT ON (
            apellidonombre_desc, 
            nro_docum,
            desc_categ,
            nro_cargo
        )
        COALESCE(nro_legaj, 'Sin información') AS \"Nro Legajo\",
        COALESCE(apellidonombre_desc, 'Sin información') AS \"Apellido y Nombre\",
        COALESCE(tipo_docum, 'Sin información') AS \"Tipo Doc.\",
        COALESCE(nro_docum, 'Sin información') AS \"Nro Doc.\",
        COALESCE(nro_cargo::TEXT, 'Sin información') AS \"Cargo\",
        COALESCE(desc_dedic, 'Sin información') AS \"Dedicacion\",
        COALESCE(desc_categ, 'Sin información') AS \"Categoría\",
        COALESCE(desc_grupo, 'Sin información') AS \"Carácter\",
        COALESCE(fec_alta::TEXT, 'Sin información') AS \"Fecha Alta\",
        COALESCE(fec_baja::TEXT, 'Sin información') AS \"Fecha Baja\",
        COALESCE(nrovarlicencia, 'Sin información') AS \"Licencia\",
        COALESCE(fec_hasta::TEXT, 'Sin información') AS \"Fecha Hasta\",
        COALESCE(desc_item, 'Sin información') AS \"Unidad Acad.\",
        COALESCE(tipo_norma, 'Sin información') AS \"Tipo Norma\",
        COALESCE(tipo_emite, 'Sin información') AS \"Tipo Emite\",
        COALESCE(fec_norma::TEXT, 'Sin información') AS \"Fecha Norma\",
        COALESCE(nro_norma, 'Sin información') AS \"Nro Norma\"
    FROM 
        docentes_mapuche
    $whereClause
    ORDER BY 
        apellidonombre_desc, 
        nro_docum,
        desc_categ,
        nro_cargo";

    // ✅ SOLO APLICAR LIMIT SI NO ES EXPORTACIÓN
    if ($perPage > 0) {
        $sql .= " LIMIT :limit OFFSET :offset";
    }

    // ✅ DEBUG TEMPORAL - QUITA ESTO DESPUÉS
    error_log("CONSULTA MAPUCHE: " . $sql);
    error_log("PARÁMETROS: " . print_r($params, true));

    try {
        $stmt = $this->conn->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        // ✅ SOLO BINDEAR LIMIT/OFFSET SI NO ES EXPORTACIÓN
        if ($perPage > 0) {
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data' => $resultados,
            'total' => $this->contarDocentesMapuche($searchTerm, $year)
        ];

    } catch (PDOException $e) {
        error_log("Error en obtenerDocentesMapuche: " . $e->getMessage());
        return [
            'data' => [],
            'total' => 0,
            'error' => $e->getMessage()
        ];
    }
}

public function contarDocentesMapuche($searchTerm = '', $year = null) {
    $conditions = [];
    $params = [];
    
    // ✅ EXCLUIR Cat1 a Cat7 - VERSIÓN CORREGIDA
    $conditions[] = "COALESCE(codc_categ, '') NOT IN ('Cat1', 'Cat2', 'Cat3', 'Cat4', 'Cat5', 'Cat6', 'Cat7')";
    
    if (!empty($searchTerm)) {
        $conditions[] = "apellidonombre_desc ILIKE :searchTerm";
        $params[':searchTerm'] = '%' . $searchTerm . '%';
    }
    
    if ($year !== null && $year !== 'all') {
        $conditions[] = "anio_id = :year";
        $params[':year'] = $year;
    }
    
    $whereClause = '';
    if (!empty($conditions)) {
        $whereClause = " WHERE " . implode(" AND ", $conditions);
    }
    
    $sql = "SELECT COUNT(*) FROM (
        SELECT DISTINCT ON (
            apellidonombre_desc, 
            nro_docum,
            desc_categ,
            nro_cargo
        ) 1
        FROM docentes_mapuche
        $whereClause
    ) AS subquery";
    
    try {
        $stmt = $this->conn->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return (int)$stmt->fetchColumn();
        
    } catch (PDOException $e) {
        error_log("Error en contarDocentesMapuche: " . $e->getMessage());
        return 0;
    }
}

    // ================================
    // 3. DOCENTES GUARANI 
    // ================================
    public function obtenerDocentesGuarani($page = 1, $perPage = 50, $searchTerm = '', $year = null) {
        $offset = ($page - 1) * $perPage;
        $whereConditions = [];
        $params = [];

        if (!empty($searchTerm)) {
            $whereConditions[] = "num_doc_guarani::TEXT ILIKE :searchTerm";
            $params[':searchTerm'] = '%' . $searchTerm . '%';
        }

        if ($year !== null && $year !== '' && $year !== 'all') {
            $whereConditions[] = "anio_guarani = :year";
            $params[':year'] = (int)$year;
        }

        $whereClause = '';
        if (!empty($whereConditions)) {
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        }

        $sql = "SELECT 
            COALESCE(responsabilidad_academica_guarani, 'Sin información') AS \"Resp. Acad.\",
            COALESCE(propuesta_formativa_guarani, 'Sin información') AS \"Propuesta\",
            COALESCE(comision_guarani, 'Sin información') AS \"Comisión\",
            COALESCE(anio_guarani::TEXT, 'Sin información') AS \"Año\",
            COALESCE(periodo_guarani, 'Sin información') AS \"Periodo\",
            COALESCE(actividad_guarani, 'Sin información') AS \"Actividad\",
            COALESCE(codigo_guarani, 'Sin información') AS \"Código\",
            COALESCE(cursados_guarani, 'Sin información') AS \"Est\",
            COALESCE(num_doc_guarani::TEXT, 'Sin información') AS \"Num. Doc.\"
        FROM 
            docentes_guarani
        $whereClause
        ORDER BY 
            propuesta_formativa_guarani";

        // ✅ SOLO APLICAR LIMIT SI NO ES EXPORTACIÓN
        if ($perPage > 0) {
            $sql .= " LIMIT :limit OFFSET :offset";
        }

        $stmt = $this->conn->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        // ✅ SOLO BINDEAR LIMIT/OFFSET SI NO ES EXPORTACIÓN
        if ($perPage > 0) {
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        }
        
        $stmt->execute();

        return [
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => $this->contarDocentesGuarani($searchTerm, $year)
        ];
    }

    public function contarDocentesGuarani($searchTerm = '', $year = null) {
        $whereConditions = [];
        $params = [];

        if (!empty($searchTerm)) {
            $whereConditions[] = "num_doc_guarani::TEXT ILIKE :searchTerm";
            $params[':searchTerm'] = '%' . $searchTerm . '%';
        }

        if ($year !== null && $year !== '' && $year !== 'all') {
            $whereConditions[] = "anio_guarani = :year";
            $params[':year'] = (int)$year;
        }

        $whereClause = '';
        if (!empty($whereConditions)) {
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        }

        $sql = "SELECT COUNT(*) FROM docentes_guarani $whereClause";
        
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
}

// Configuración de conexión
try {
    $dsn = "pgsql:host={$config_tinkuy['host']};port={$config_tinkuy['port']};dbname={$config_tinkuy['dbname']}";
    $conn = new PDO($dsn, $config_tinkuy['user'], $config_tinkuy['password']);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $consultas = new ConsultasDocentes($conn);

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
        $response = [];
        $export = isset($_GET['export']) && $_GET['export'] === 'true';
        $page = $export ? 1 : (isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1);
        $perPage = $export ? 0 : 50; // ⬅️ CAMBIO CRÍTICO: 0 para exportación

        $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
        $year = (isset($_GET['year']) && $_GET['year'] !== '' && $_GET['year'] !== 'all') ? (int)$_GET['year'] : null;
        $type = $_GET['type'] ?? '';

        switch ($_GET['action']) {
            case 'getData':
                try {
                    if (!isset($_GET['type'])) {
                        throw new Exception("Tipo de consulta no especificado");
                    }

                    $type = $_GET['type'];
                    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
                    $perPage = isset($_GET['perPage']) ? max(1, (int)$_GET['perPage']) : 50;
                    $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
                    $year = (isset($_GET['year']) && $_GET['year'] !== '' && $_GET['year'] !== 'all') ? (int)$_GET['year'] : null;

                    $allowedTypes = ['guarani', 'mapuche', 'combinados'];
                    if (!in_array($type, $allowedTypes)) {
                        throw new Exception("Tipo de consulta no válido. Opciones permitidas: " . implode(', ', $allowedTypes));
                    }

                    $result = match($type) {
                        'guarani'    => $consultas->obtenerDocentesGuarani($page, $perPage, $searchTerm, $year),
                        'mapuche'    => $consultas->obtenerDocentesMapuche($page, $perPage, $searchTerm, $year),
                        'combinados' => $consultas->docentesCombinados($page, $perPage, $searchTerm, $year)
                    };

                    if (!isset($result['data']) || !isset($result['total'])) {
                        throw new Exception("Estructura de respuesta inválida desde el modelo");
                    }

                    $response = [
                        'success' => true,
                        'data' => $result['data'],
                        'pagination' => [
                            'current_page' => $page,
                            'per_page' => $perPage,
                            'total' => $result['total'],
                            'total_pages' => $perPage > 0 ? ceil($result['total'] / $perPage) : 1
                        ]
                    ];

                } catch (Exception $e) {
                    error_log("ERROR EN getData: " . $e->getMessage());
                    header('Content-Type: application/json');
                    $response = [
                        'success' => false,
                        'error' => $e->getMessage(),
                        'request_params' => $_GET
                    ];
                    echo json_encode($response);
                    exit;
                }
                break;

            default:
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'error' => 'Acción no válida',
                    'actions_allowed' => ['getData']
                ]);
                exit;
        }

        echo json_encode($response);
        exit;
    }

    throw new Exception("Solicitud no válida");

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
    exit;
}
?>