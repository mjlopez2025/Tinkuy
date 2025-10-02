<?php
include_once("../config.php");

header("Access-Control-Allow-Origin: http://localhost:8000"); // Permite solo tu frontend
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Si recibes una solicitud OPTIONS (preflight), finaliza el script
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class ConsultasDocentes
{
    private $conn;
    private $conn_jurumi;

    public function __construct($conn, $conn_jurumi = null)
    {
        $this->conn = $conn;          // Conexión a Tinkuy
        $this->conn_jurumi = $conn_jurumi; // Conexión a Jurumi

        // ✅ FORZAR UTF-8 en la conexión Jurumi
        if ($this->conn_jurumi) {
            $this->conn_jurumi->exec("SET NAMES 'UTF8'");
            $this->conn_jurumi->exec("SET CLIENT_ENCODING TO 'UTF8'");
        }
    }

// ================================
// 1. DOCENTES COMBINADOS - OPTIMIZADO CON LEFT JOIN
// ================================
public function docentesCombinados($page = 1, $perPage = 50, $searchTerm = '', $year = null)
{
    $offset = ($page - 1) * $perPage;
    $whereConditions = [];
    $params = [];

    // ✅ CONDICIONES MÁS EFICIENTES
    $whereConditions[] = "m.codc_categ NOT IN ('Cat1', 'Cat2', 'Cat3', 'Cat4', 'Cat5', 'Cat6', 'Cat7')";
    $whereConditions[] = "COALESCE(m.codc_grupo, '') NOT IN ('NODO', 'AUTO', 'ESC', 'SUPE', 'DINV', 'ADMI')";
    $whereConditions[] = "(m.fec_baja IS NULL OR m.fec_baja >= CURRENT_DATE)";

    // ✅ BÚSQUEDA OPTIMIZADA - SOLO SI ES NECESARIA
    if (!empty($searchTerm)) {
        $whereConditions[] = "(m.apellidonombre_desc ILIKE :searchTerm OR
                         m.num_cargo::TEXT ILIKE :searchTerm OR 
                         m.nro_legaj::TEXT ILIKE :searchTerm) OR
                         m.desc_item ILIKE :searchTerm";
        $params[':searchTerm'] = $searchTerm;  // ✅ Busqueda por inicio es más rápida
    }

    // ✅ FILTRO DE AÑO MÁS INTELIGENTE - AHORA CON LEFT JOIN
    if ($year !== null && $year !== '' && $year !== 'all') {
        $whereConditions[] = "(g.anio_guarani = :year OR g.anio_guarani IS NULL)";
        $params[':year'] = (int) $year;
    }

    $additionalWhere = '';
    if (!empty($whereConditions)) {
        $additionalWhere = 'WHERE ' . implode(' AND ', $whereConditions);
    }

    // ✅ CONSULTA CON LEFT JOIN PARA MOSTRAR TODOS LOS DOCENTES MAPUCHE
    $sql = "SELECT
        m.nro_legaj AS \"Nro Legajo (M)\",
        m.apellidonombre_desc AS \"Apellido y Nombre (M)\",
        m.tipo_docum AS \"Tipo Doc. (M)\",
        m.nro_docum AS \"Nro Doc. (M)\",
        m.num_cargo::TEXT AS \"Cargo (M)\",
        m.codc_grupo::TEXT AS \"Escalafon (M)\",
        m.desc_dedic AS \"Dedicacion (M)\",
        m.desc_categ AS \"Categoría (M)\",
        m.codc_categ::TEXT AS \"CodC_Categ (M)\",
        m.codc_carac::TEXT AS \"Carácter (M)\",
        m.fec_alta::TEXT AS \"Fecha Alta (M)\",
        COALESCE(m.fec_baja::TEXT, 'SIN DATOS') AS \"Fecha Baja (M)\",
        m.licencia AS \"Licencia (M)\",
        m.fec_hasta::TEXT AS \"Fecha Hasta (M)\",
        m.desc_item AS \"Dependencia (M)\",
        m.tipo_norma AS \"Tipo Norma (M)\",
        m.tipo_emite AS \"Tipo Emite (M)\",
        m.fec_norma::TEXT AS \"Fecha Norma (M)\",
        m.nro_norma AS \"Nro Norma (M)\",
        COALESCE(g.responsabilidad_academica_guarani, 'SIN DATOS') AS \"Resp Acad (G)\",
        COALESCE(g.propuesta_formativa_guarani, 'SIN DATOS') AS \"Propuesta (G)\", 
        COALESCE(g.comision_guarani, 'SIN DATOS') AS \"Com (G)\",
        COALESCE(g.anio_guarani::TEXT, 'SIN DATOS') AS \"Año (G)\",
        COALESCE(g.periodo_guarani, 'SIN DATOS') AS \"Período (G)\",
        COALESCE(g.actividad_guarani, 'SIN DATOS') AS \"Actividad (G)\",
        COALESCE(g.cursados_guarani, 'SIN DATOS') AS \"Est (G)\"
    FROM docentes_mapuche AS m 
    LEFT JOIN docentes_guarani g 
        ON m.nro_docum = g.num_doc_guarani  -- ✅ CAMBIADO A LEFT JOIN
    $additionalWhere
    ORDER BY m.apellidonombre_desc, m.nro_docum  -- ✅ ORDEN MÁS SIMPLE
    LIMIT :limit OFFSET :offset";

    try {
        $stmt = $this->conn->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        $stmt->bindValue(':limit', (int) $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);

        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data' => $data,
            'total' => $this->contarDocentesCombinados($searchTerm, $year)
        ];

    } catch (PDOException $e) {
        error_log("Error en docentesCombinados: " . $e->getMessage());
        return [
            'data' => [],
            'total' => 0,
            'error' => 'Error al cargar los datos'
        ];
    }
}

public function contarDocentesCombinados($searchTerm = '', $year = null)
{
    $whereConditions = [];
    $params = [];

    // ✅ MISMAS CONDICIONES OPTIMIZADAS
    $whereConditions[] = "m.codc_categ NOT IN ('Cat1', 'Cat2', 'Cat3', 'Cat4', 'Cat5', 'Cat6', 'Cat7')";
    $whereConditions[] = "COALESCE(m.codc_grupo, '') NOT IN ('NODO', 'AUTO', 'ESC', 'ADMI', 'SUPE', 'DINV')";
    $whereConditions[] = "(m.fec_baja IS NULL OR m.fec_baja >= CURRENT_DATE)";

    if (!empty($searchTerm)) {
        $whereConditions[] = "(m.apellidonombre_desc ILIKE :searchTerm OR
                              m.num_cargo::TEXT ILIKE :searchTerm OR
                              m.nro_legaj::TEXT ILIKE :searchTerm OR
                              m.desc_item ILIKE :searchTerm)";
        $params[':searchTerm'] = $searchTerm;  // ✅ Búsqueda por inicio
    }

    // ✅ FILTRO DE AÑO ACTUALIZADO PARA LEFT JOIN
    if ($year !== null && $year !== '' && $year !== 'all') {
        $whereConditions[] = "(g.anio_guarani = :year OR g.anio_guarani IS NULL)";
        $params[':year'] = (int) $year;
    }

    $additionalWhere = '';
    if (!empty($whereConditions)) {
        $additionalWhere = 'WHERE ' . implode(' AND ', $whereConditions);
    }

    // ✅ COUNT CON LEFT JOIN CONSISTENTE
    $sql = "SELECT COUNT(DISTINCT m.nro_docum)
    FROM docentes_mapuche AS m
    LEFT JOIN docentes_guarani AS g 
        ON m.nro_docum = g.num_doc_guarani  -- ✅ LEFT JOIN CONSISTENTE
    $additionalWhere";

    try {
        $stmt = $this->conn->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        $stmt->execute();
        return (int) $stmt->fetchColumn();

    } catch (PDOException $e) {
        error_log("Error en contarDocentesCombinados: " . $e->getMessage());
        return 0;
    }
}


// ================================
// 2. DOCENTES MAPUCHE 
// ================================
public function obtenerDocentesMapuche($page = 1, $perPage = 50, $searchTerm = '', $year = null)
{
    
    // ✅ DEBUG: VER TODAS LAS CATEGORÍAS QUE EXISTEN EN LA BD
    $debugSql = "SELECT DISTINCT desc_categ, codc_categ FROM docentes_mapuche ORDER BY desc_categ";
    $debugStmt = $this->conn->prepare($debugSql);
    $debugStmt->execute();
    $todasLasCategorias = $debugStmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("=== TODAS LAS CATEGORÍAS EN LA BASE DE DATOS ===");
    foreach ($todasLasCategorias as $cat) {
        error_log("desc_categ: '" . $cat['desc_categ'] . "' | codc_categ: '" . $cat['codc_categ'] . "'");
    }
    
    // Resto de tu código normal...
    $offset = ($page - 1) * $perPage;
    $whereClause = '';
    $params = [];
    $conditions = [];

    // ✅ CONDICIONES ACTUALIZADAS PARA LA NUEVA ESTRUCTURA
    $conditions[] = "(desc_categ NOT LIKE 'Cat%' OR desc_categ IS NULL)";
    $conditions[] = "(codc_categ::text NOT LIKE 'Cat%' OR codc_categ IS NULL)"; // Convertir a text para LIKE
    $conditions[] = "(codc_grupo NOT IN ('NODO', 'AUTO', 'ESC', 'SUPE', 'DINV'))";
    $conditions[] = "(fec_baja IS NULL OR fec_baja >= CURRENT_DATE)";
    
    // ✅ EXCLUIR NÚMEROS SOLITOS (convertir a text para la comparación)
    $conditions[] = "(desc_categ NOT IN ('1','2','3','4','5','6','7') OR desc_categ IS NULL)";
    $conditions[] = "(codc_categ::text NOT IN ('1','2','3','4','5','6','7') OR codc_categ IS NULL)";

    if (!empty($searchTerm)) {
    $conditions[] = "(apellidonombre_desc ILIKE :searchTerm OR 
                     num_cargo::TEXT ILIKE :searchTerm OR 
                     nro_legaj::TEXT ILIKE :searchTerm OR 
                     desc_item ILIKE :searchTerm)";
    $params[':searchTerm'] = $searchTerm;
}

    if ($year !== null && $year !== 'all') {
        // Si necesitas filtrar por año, necesitaríamos agregar un campo año a la tabla
        // $conditions[] = "anio_id = :year";
        // $params[':year'] = $year;
    }

    if (!empty($conditions)) {
        $whereClause = " WHERE " . implode(" AND ", $conditions);
    }

    // ✅ CONSULTA ACTUALIZADA CON LOS NUEVOS NOMBRES DE COLUMNAS
    $sql = "SELECT 
    COALESCE(nro_legaj, 'Sin información') AS \"Nro Legajo\",
    COALESCE(apellidonombre_desc, 'Sin información') AS \"Apellido y Nombre\",
    COALESCE(tipo_docum, 'Sin información') AS \"Tipo Doc.\",
    COALESCE(nro_docum, 'Sin información') AS \"Nro Doc.\",
    COALESCE(num_cargo::TEXT, 'Sin información') AS \"Cargo\",
    COALESCE(codc_grupo::TEXT, 'Sin información') AS \"Escalafon\",
    COALESCE(desc_dedic, 'Sin información') AS \"Dedicacion\",
    COALESCE(desc_categ, 'Sin información') AS \"Categoría\",
    COALESCE(codc_categ::TEXT, 'Sin información') AS \"CodC_Categ\",
    COALESCE(codc_carac::TEXT, 'Sin información') AS \"Carácter\",
    COALESCE(fec_alta::TEXT, 'Sin información') AS \"Fecha Alta\",
    COALESCE(fec_baja::TEXT, 'Sin información') AS \"Fecha Baja\",
    COALESCE(licencia, 'Sin información') AS \"Licencia\",
    COALESCE(fec_hasta::TEXT, 'Sin información') AS \"Fecha Hasta\",
    COALESCE(desc_item, 'Sin información') AS \"Dependencia\",
    COALESCE(tipo_norma, 'Sin información') AS \"Tipo Norma\",
    COALESCE(tipo_emite, 'Sin información') AS \"Tipo Emite\",
    COALESCE(fec_norma::TEXT, 'Sin información') AS \"Fecha Norma\",
    COALESCE(nro_norma, 'Sin información') AS \"Nro Norma\"
FROM 
    docentes_mapuche
    $whereClause
GROUP BY 
    nro_legaj, apellidonombre_desc, tipo_docum, nro_docum, num_cargo,
    codc_grupo, desc_dedic, desc_categ, codc_categ, codc_carac,
    fec_alta, fec_baja, licencia, fec_hasta, desc_item,
    tipo_norma, tipo_emite, fec_norma, nro_norma
ORDER BY 
    apellidonombre_desc,
    nro_docum,
    desc_categ,
    num_cargo";

    if ($perPage > 0) {
        $sql .= " LIMIT :limit OFFSET :offset";
    }

    error_log("CONSULTA FINAL: " . $sql);
    try {
        $stmt = $this->conn->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        if ($perPage > 0) {
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        }

        $stmt->execute();
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ✅ DEBUG: VER QUÉ CATEGORÍAS ESTÁN PASANDO EL FILTRO
        error_log("=== CATEGORÍAS EN LOS RESULTADOS FILTRADOS ===");
        $categoriasEnResultados = [];
        foreach ($resultados as $index => $row) {
            $categoria = $row['Categoría'];
            $codc = $row['CodC_Categ'];
            if (!in_array($categoria, $categoriasEnResultados)) {
                $categoriasEnResultados[] = $categoria;
                error_log("Categoría que pasa el filtro: '$categoria' | CodC: '$codc'");
            }
        }

        return [
            'data' => $resultados,
            'total' => $this->contarDocentesMapuche($searchTerm, $year),
            'debug_categorias' => $todasLasCategorias // Temporal para debug
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

public function contarDocentesMapuche($searchTerm = '', $year = null)
{
    $conditions = [];
    $params = [];

    // ✅ MISMA CONDICIÓN QUE EL MÉTODO PRINCIPAL (ACTUALIZADA)
    $conditions[] = "(codc_categ IS NULL OR codc_categ::text NOT IN ('Cat1','Cat2','Cat3','Cat4','Cat5','Cat6','Cat7','1','2','3','4','5','6','7'))";
    $conditions[] = "(desc_categ IS NULL OR desc_categ NOT IN ('Cat1','Cat2','Cat3','Cat4','Cat5','Cat6','Cat7','1','2','3','4','5','6','7'))";
    $conditions[] = "(codc_grupo NOT IN ('NODO', 'AUTO', 'ESC','SUPE','DINV') OR codc_grupo IS NULL)";
    $conditions[] = "(fec_baja IS NULL OR fec_baja >= CURRENT_DATE)";

    if (!empty($searchTerm)) {
    $conditions[] = "(apellidonombre_desc ILIKE :searchTerm OR 
                     num_cargo::TEXT ILIKE :searchTerm OR 
                     nro_legaj::TEXT ILIKE :searchTerm OR 
                     desc_item ILIKE :searchTerm)";
    $params[':searchTerm'] =  $searchTerm;
}

    if ($year !== null && $year !== 'all') {
    }

    $whereClause = '';
    if (!empty($conditions)) {
        $whereClause = " WHERE " . implode(" AND ", $conditions);
    }

    // ✅ COUNT ACTUALIZADO CON LA NUEVA ESTRUCTURA
    $sql = "SELECT COUNT(*) FROM (
        SELECT DISTINCT ON (
            apellidonombre_desc, 
            nro_docum,
            desc_categ,
            num_cargo  -- ✅ CAMBIADO: nro_cargo → num_cargo
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
        return (int) $stmt->fetchColumn();

    } catch (PDOException $e) {
        error_log("Error en contarDocentesMapuche: " . $e->getMessage());
        return 0;
    }
}


    // ================================
    // 3. DOCENTES GUARANI 
    // ================================
    public function obtenerDocentesGuarani($page = 1, $perPage = 50, $searchTerm = '', $year = null)
    {
        $offset = ($page - 1) * $perPage;
        $whereConditions = [];
        $params = [];

        if (!empty($searchTerm)) {
            $whereConditions[] = "num_doc_guarani::TEXT ILIKE :searchTerm";
            $params[':searchTerm'] = '%' . $searchTerm . '%';
        }

        if ($year !== null && $year !== '' && $year !== 'all') {
            $whereConditions[] = "anio_guarani = :year";
            $params[':year'] = (int) $year;
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

    public function contarDocentesGuarani($searchTerm = '', $year = null)
    {
        $whereConditions = [];
        $params = [];

        if (!empty($searchTerm)) {
            $whereConditions[] = "num_doc_guarani::TEXT ILIKE :searchTerm";
            $params[':searchTerm'] = '%' . $searchTerm . '%';
        }

        if ($year !== null && $year !== '' && $year !== 'all') {
            $whereConditions[] = "anio_guarani = :year";
            $params[':year'] = (int) $year;
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
        return (int) $stmt->fetchColumn();
    }


// ================================
// 4. JURUMI (STOCK)
// ================================
public function obtenerStockJurumi($page = 1, $perPage = 50, $searchTerm = '')
{
    if (!$this->conn_jurumi) {
        return ['data' => [], 'total' => 0, 'error' => 'Conexión a Jurumi no disponible'];
    }

    $offset = ($page - 1) * $perPage;
    $params = [];
    $conditions = [];

    if (!empty($searchTerm)) {
        $conditions[] = "(sd.descripcion ILIKE :searchTerm OR 
                      a.nombre_almacen ILIKE :searchTerm OR 
                      sd.id_unidad_medida::TEXT ILIKE :searchTerm)";
        $params[':searchTerm'] = '%' . $searchTerm . '%';
    }

    $whereClause = '';
    if (!empty($conditions)) {
        $whereClause = 'WHERE ' . implode(' AND ', $conditions);
    }

    $sql = "SELECT 
            sa.id_stock_almacen AS \"ID Stock\",
            sa.id_almacen AS \"ID Almacén\",
            a.nombre_almacen AS \"Nombre Almacén\",
            sa.cantidad_ingresada AS \"Cantidad Ingresada\",
            sa.cantidad_disponible AS \"Cantidad Disponible\",
            sd.cantidad AS \"Cantidad\",
            sd.monto_unitario AS \"Monto Unitario\",
            sd.descripcion AS \"Descripción\"
        FROM public.stock_en_almacen AS sa
        JOIN public.stock_detalle sd ON sa.id_stock_detalle = sd.id_stock_detalle 
        JOIN public.almacenes a ON sa.id_almacen = a.id_almacen
        $whereClause
        ORDER BY sa.id_stock_almacen";

    if ($perPage > 0) {
        $sql .= " LIMIT :limit OFFSET :offset";
    }

    try {
        $stmt = $this->conn_jurumi->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        if ($perPage > 0) {
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        }

        $stmt->execute();
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data' => $resultados,
            'total' => $this->contarStockJurumi($searchTerm)
        ];

    } catch (PDOException $e) {
        error_log("❌ Error en obtenerStockJurumi: " . $e->getMessage());
        return ['data' => [], 'total' => 0, 'error' => $e->getMessage()];
    }
}

public function contarStockJurumi($searchTerm = '')
{
    if (!$this->conn_jurumi) {
        return 0;
    }

    $params = [];
    $conditions = [];

    if (!empty($searchTerm)) {
        $conditions[] = "(sd.descripcion ILIKE :searchTerm OR 
                      a.nombre_almacen ILIKE :searchTerm OR 
                      sd.id_unidad_medida::TEXT ILIKE :searchTerm)";
        $params[':searchTerm'] = '%' . $searchTerm . '%';
    }

    $whereClause = '';
    if (!empty($conditions)) {
        $whereClause = 'WHERE ' . implode(' AND ', $conditions);
    }

    $sql = "SELECT COUNT(*) 
        FROM public.stock_en_almacen AS sa
        JOIN public.stock_detalle sd ON sa.id_stock_detalle = sd.id_stock_detalle 
        JOIN public.almacenes a ON sa.id_almacen = a.id_almacen
        $whereClause";

    try {
        $stmt = $this->conn_jurumi->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return (int) $stmt->fetchColumn();

    } catch (PDOException $e) {
        error_log("❌ Error al contar Jurumi: " . $e->getMessage());
        return 0;
    }
}

// ================================
// 5. CONSULTAS JURUMI - DETALLE DE ENTREGA
// ================================
public function obtenerDetalleEntrega($page = 1, $perPage = 50, $searchTerm = '')
{
    error_log("🔍 INICIANDO DETALLE DE ENTREGA JURUMI");

    if (!$this->conn_jurumi) {
        error_log("❌ Error: No hay conexión a Jurumi");
        return ['data' => [], 'total' => 0, 'error' => 'Conexión no configurada'];
    }

    $offset = ($page - 1) * $perPage;
    $whereConditions = [];
    $params = [];

    if (!empty($searchTerm)) {
        $whereConditions[] = "(e.nombre ILIKE :searchTerm OR 
                         ec.observacion_egreso ILIKE :searchTerm OR
                         ed.id_egreso::TEXT ILIKE :searchTerm OR
                         sd.descripcion ILIKE :searchTerm)";
        $params[':searchTerm'] = '%' . $searchTerm . '%';
    }

    $whereClause = '';
    if (!empty($whereConditions)) {
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    }

    $sql = "SELECT 
        ed.id_egreso_detalle AS \"ID Egreso Detalle\",
        ed.id_egreso AS \"ID Egreso\",
        ec.fecha_egreso AS \"Fecha de Egreso\",
        ed.cantidad_entregada AS \"Cantidad Entregada\",
        ec.id_ubicacion_fisica_destino AS \"ID Ubicación Física\",
        ec.id_persona_solicitante AS \"ID Responsable\",
        e.nombre AS \"Estado\",
        ec.ejercicio_egreso AS \"Ejercicio Egreso\",
        ec.observacion_egreso AS \"Observaciones\",
        ea.cantidad AS \"Cantidad\",
        sd.descripcion AS \"Descripcion\"
    FROM public.egresos_detalle ed
    JOIN public.egresos_cabecera ec ON ed.id_egreso = ec.id_egreso 
    JOIN public.egresos_almacen ea ON ea.id_egreso_detalle = ed.id_egreso_detalle  
    JOIN public.estados e ON ec.estado = e.estado
    JOIN public.stock_en_almacen sea ON ea.id_stock_almacen = sea.id_stock_almacen 
    JOIN public.stock_detalle sd ON sea.id_stock_detalle = sd.id_stock_detalle 
    $whereClause
    ORDER BY ed.id_egreso_detalle ASC";

    if ($perPage > 0) {
        $sql .= " LIMIT :limit OFFSET :offset";
    }

    try {
        $stmt = $this->conn_jurumi->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        if ($perPage > 0) {
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        }

        $stmt->execute();
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data' => $resultados,
            'total' => $this->contarDetalleEntrega($searchTerm)
        ];

    } catch (PDOException $e) {
        error_log("❌ Error PDO en Detalle Entrega: " . $e->getMessage());
        return [
            'data' => [],
            'total' => 0,
            'error' => $e->getMessage()
        ];
    }
}

public function contarDetalleEntrega($searchTerm = '')
{
    if (!$this->conn_jurumi) {
        return 0;
    }

    $whereConditions = [];
    $params = [];

    if (!empty($searchTerm)) {
        $whereConditions[] = "(e.nombre ILIKE :searchTerm OR 
                         ec.observacion_egreso ILIKE :searchTerm OR
                         ed.id_egreso::TEXT ILIKE :searchTerm OR
                         sd.descripcion ILIKE :searchTerm)";
        $params[':searchTerm'] = '%' . $searchTerm . '%';
    }

    $whereClause = '';
    if (!empty($whereConditions)) {
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    }

    $sql = "SELECT COUNT(*)
        FROM public.egresos_detalle ed
        JOIN public.egresos_cabecera ec ON ed.id_egreso = ec.id_egreso 
        JOIN public.egresos_almacen ea ON ea.id_egreso_detalle = ed.id_egreso_detalle  
        JOIN public.estados e ON ec.estado = e.estado
        JOIN public.stock_en_almacen sea ON ea.id_stock_almacen = sea.id_stock_almacen 
        JOIN public.stock_detalle sd ON sea.id_stock_detalle = sd.id_stock_detalle 
        $whereClause";

    try {
        $stmt = $this->conn_jurumi->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return (int) $stmt->fetchColumn();

    } catch (PDOException $e) {
        error_log("❌ Error al contar Detalle Entrega: " . $e->getMessage());
        return 0;
    }
}

// ================================
// 6. JURUMI - LISTA DE PRODUCTOS (stock_detalle)
// ================================
public function obtenerListaProductos($page = 1, $perPage = 50, $searchTerm = '')
{
    if (!$this->conn_jurumi) {
        return ['data' => [], 'total' => 0, 'error' => 'Conexión a Jurumi no disponible'];
    }

    $offset = ($page - 1) * $perPage;
    $params = [];
    $conditions = [];

    if (!empty($searchTerm)) {
        $conditions[] = "(sd.descripcion ILIKE :searchTerm OR 
                      sd.id_stock_detalle::TEXT ILIKE :searchTerm)";
        $params[':searchTerm'] = '%' . $searchTerm . '%';
    }

    $whereClause = '';
    if (!empty($conditions)) {
        $whereClause = 'WHERE ' . implode(' AND ', $conditions);
    }

    $sql = "SELECT 
            sd.id_stock_detalle AS \"ID Producto\",
            sd.descripcion AS \"Descripción\"
        FROM public.stock_detalle AS sd
        $whereClause
        ORDER BY sd.id_stock_detalle";

    if ($perPage > 0) {
        $sql .= " LIMIT :limit OFFSET :offset";
    }

    try {
        $stmt = $this->conn_jurumi->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        if ($perPage > 0) {
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        }

        $stmt->execute();
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data' => $resultados,
            'total' => $this->contarListaProductos($searchTerm)
        ];

    } catch (PDOException $e) {
        error_log("❌ Error en obtenerListaProductos: " . $e->getMessage());
        return ['data' => [], 'total' => 0, 'error' => $e->getMessage()];
    }
}

public function contarListaProductos($searchTerm = '')
{
    if (!$this->conn_jurumi) {
        return 0;
    }

    $params = [];
    $conditions = [];

    if (!empty($searchTerm)) {
        $conditions[] = "(sd.descripcion ILIKE :searchTerm OR 
                      sd.id_stock_detalle::TEXT ILIKE :searchTerm)";
        $params[':searchTerm'] = '%' . $searchTerm . '%';
    }

    $whereClause = '';
    if (!empty($conditions)) {
        $whereClause = 'WHERE ' . implode(' AND ', $conditions);
    }

    $sql = "SELECT COUNT(*) 
        FROM public.stock_detalle AS sd
        $whereClause";

    try {
        $stmt = $this->conn_jurumi->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return (int) $stmt->fetchColumn();

    } catch (PDOException $e) {
        error_log("❌ Error al contar productos: " . $e->getMessage());
        return 0;
    }
}

// ================================
// 7. JURUMI - DETALLE DE UN PRODUCTO ESPECÍFICO (TODOS LOS CAMPOS SIN ALIAS)
// ================================
public function obtenerDetalleProducto($productoId, $page = 1, $perPage = 50, $searchTerm = '')
{
    if (!$this->conn_jurumi) {
        return ['data' => [], 'total' => 0, 'error' => 'Conexión a Jurumi no disponible'];
    }

    $offset = ($page - 1) * $perPage;
    $params = [':producto_id' => $productoId];
    $conditions = ["id_stock_detalle = :producto_id"];

    if (!empty($searchTerm)) {
        $conditions[] = "(descripcion ILIKE :searchTerm)";
        $params[':searchTerm'] = '%' . $searchTerm . '%';
    }

    $whereClause = 'WHERE ' . implode(' AND ', $conditions);

    // CONSULTA MÁS SIMPLE - TODOS LOS CAMPOS SIN ALIAS
    $sql = "SELECT *
        FROM public.stock_detalle
        $whereClause
        ORDER BY id_stock_detalle";

    if ($perPage > 0) {
        $sql .= " LIMIT :limit OFFSET :offset";
    }

    try {
        $stmt = $this->conn_jurumi->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        if ($perPage > 0) {
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        }

        $stmt->execute();
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data' => $resultados,
            'total' => $this->contarDetalleProducto($productoId, $searchTerm)
        ];

    } catch (PDOException $e) {
        error_log("❌ Error en obtenerDetalleProducto: " . $e->getMessage());
        return ['data' => [], 'total' => 0, 'error' => $e->getMessage()];
    }
}

public function contarDetalleProducto($productoId, $searchTerm = '')
{
    if (!$this->conn_jurumi) {
        return 0;
    }

    $params = [':producto_id' => $productoId];
    $conditions = ["id_stock_detalle = :producto_id"];

    if (!empty($searchTerm)) {
        $conditions[] = "(descripcion ILIKE :searchTerm)";
        $params[':searchTerm'] = '%' . $searchTerm . '%';
    }

    $whereClause = 'WHERE ' . implode(' AND ', $conditions);

    $sql = "SELECT COUNT(*)
        FROM public.stock_detalle
        $whereClause";

    try {
        $stmt = $this->conn_jurumi->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return (int) $stmt->fetchColumn();

    } catch (PDOException $e) {
        error_log("❌ Error al contar detalle producto: " . $e->getMessage());
        return 0;
    }
}
}


// Configuración de conexión
try {
    $dsn = "pgsql:host={$config_tinkuy['host']};port={$config_tinkuy['port']};dbname={$config_tinkuy['dbname']}";
    $conn = new PDO($dsn, $config_tinkuy['user'], $config_tinkuy['password']);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ✅ NUEVA: Conexión a Jurumi (para stock)
    $dsn_jurumi = "pgsql:host={$config_jurumi['host']};port={$config_jurumi['port']};dbname={$config_jurumi['dbname']}";
    $conn_jurumi = new PDO($dsn_jurumi, $config_jurumi['user'], $config_jurumi['password']);
    $conn_jurumi->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $consultas = new ConsultasDocentes($conn, $conn_jurumi);

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
        $response = [];
        $export = isset($_GET['export']) && $_GET['export'] === 'true';
        $page = $export ? 1 : (isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1);
        $perPage = $export ? 0 : 50; // ⬅️ CAMBIO CRÍTICO: 0 para exportación

        $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
        $year = (isset($_GET['year']) && $_GET['year'] !== '' && $_GET['year'] !== 'all') ? (int) $_GET['year'] : null;
        $type = $_GET['type'] ?? '';

        switch ($_GET['action']) {
    case 'getData':
        try {
            if (!isset($_GET['type'])) {
                throw new Exception("Tipo de consulta no especificado");
            }

           $type = $_GET['type'];
        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $perPage = isset($_GET['perPage']) ? max(1, (int) $_GET['perPage']) : 50;
        $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
        $year = (isset($_GET['year']) && $_GET['year'] !== '' && $_GET['year'] !== 'all') ? (int) $_GET['year'] : null;
        $productoId = isset($_GET['producto_id']) ? (int) $_GET['producto_id'] : null;

            // AGREGAR 'producto' A LOS TIPOS PERMITIDOS
            $allowedTypes = ['guarani', 'mapuche', 'combinados', 'jurumi', 'entrega', 'producto'];

            if (!in_array($type, $allowedTypes)) {
                throw new Exception("Tipo de consulta no válido. Opciones permitidas: " . implode(', ', $allowedTypes));
            }

            // AGREGAR EL CASO PARA 'producto' EN EL MATCH
            $result = match ($type) {
                'guarani' => $consultas->obtenerDocentesGuarani($page, $perPage, $searchTerm, $year),
                'mapuche' => $consultas->obtenerDocentesMapuche($page, $perPage, $searchTerm, $year),
                'combinados' => $consultas->docentesCombinados($page, $perPage, $searchTerm, $year),
                'jurumi' => $consultas->obtenerStockJurumi($page, $perPage, $searchTerm),
                'entrega' => $consultas->obtenerDetalleEntrega($page, $perPage, $searchTerm),
                'producto' => $productoId ? 
                $consultas->obtenerDetalleProducto($productoId, $page, $perPage, $searchTerm) : 
                $consultas->obtenerListaProductos($page, $perPage, $searchTerm)
                
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
    case 'getProductos':
    try {
        $result = $consultas->obtenerListaProductos(1, 10000, ''); // Obtener todos los productos
        
        if ($result['success'] !== false) {
            // Formatear para el select (si es necesario) o para la tabla
            $productos = array_map(function($producto) {
                return [
                    'id' => $producto['ID Producto'],
                    'text' => $producto['Descripción']
                ];
            }, $result['data']);
            
            $response = [
                'success' => true,
                'data' => $productos
            ];
        } else {
            $response = [
                'success' => false,
                'error' => $result['error'] ?? 'Error al cargar productos'
            ];
        }
        
    } catch (Exception $e) {
        $response = [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
    echo json_encode($response);
    exit;
    break;
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