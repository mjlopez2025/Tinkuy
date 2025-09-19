<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

include_once("../config.php");
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
// 1. DOCENTES COMBINADOS 
// ================================
    public function docentesCombinados($page = 1, $perPage = 50, $searchTerm = '', $year = null)
    {
        $offset = ($page - 1) * $perPage;
        $whereConditions = [];
        $params = [];

        // ✅ EXCLUIR Cat1 a Cat7
        $whereConditions[] = "m.codc_categ NOT IN ('Cat1', 'Cat2', 'Cat3', 'Cat4', 'Cat5', 'Cat6', 'Cat7')";

        // ✅ Filtro búsqueda
        if (!empty($searchTerm)) {
            $whereConditions[] = "m.apellidonombre_desc ILIKE :searchTerm";
            $params[':searchTerm'] = '%' . $searchTerm . '%';
        }

        // ✅ Filtro de año: mantener docentes SIN DATOS de Guaraní
        if ($year !== null && $year !== '' && $year !== 'all') {
            $whereConditions[] = "(g.anio_guarani = :year OR g.anio_guarani IS NULL)";
            $params[':year'] = (int) $year;
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
        COALESCE(g.responsabilidad_academica_guarani, 'SIN DATOS') AS \"Resp Acad (G)\",
        COALESCE(g.propuesta_formativa_guarani, 'SIN DATOS') AS \"Propuesta (G)\", 
        COALESCE(g.comision_guarani, 'SIN DATOS') AS \"Com (G)\",
        COALESCE(g.anio_guarani::TEXT, 'SIN DATOS') AS \"Año (G)\",
        COALESCE(g.periodo_guarani, 'SIN DATOS') AS \"Período (G)\",
        COALESCE(g.actividad_guarani, 'SIN DATOS') AS \"Actividad (G)\",
        COALESCE(g.cursados_guarani, 'SIN DATOS') AS \"Est (G)\"
    FROM docentes_mapuche AS m 
    LEFT JOIN docentes_guarani g 
    ON TRIM(m.nro_docum::TEXT) = TRIM(g.num_doc_guarani::TEXT)
    $additionalWhere
    GROUP BY 
        m.nro_legaj, m.apellidonombre_desc, m.tipo_docum, m.nro_docum, m.nro_cargo,
        m.desc_dedic, m.desc_categ, m.desc_grupo, m.fec_alta, m.fec_baja,
        m.nrovarlicencia, m.fec_hasta, m.desc_item, m.tipo_norma, m.tipo_emite,
        m.fec_norma, m.nro_norma,
        g.responsabilidad_academica_guarani, g.propuesta_formativa_guarani,
        g.comision_guarani, g.anio_guarani, g.periodo_guarani,
        g.actividad_guarani, g.cursados_guarani
    ORDER BY m.nro_docum, g.anio_guarani DESC NULLS LAST";

        if ($perPage > 0) {
            $sql .= " LIMIT :limit OFFSET :offset";
        }

        $stmt = $this->conn->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        if ($perPage > 0) {
            $stmt->bindValue(':limit', (int) $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        }

        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data' => $data,
            'total' => $this->contarDocentesCombinados($searchTerm, $year)
        ];
    }

    public function contarDocentesCombinados($searchTerm = '', $year = null)
    {
        $whereConditions = [];
        $params = [];

        // ✅ EXCLUIR Cat1 a Cat7
        $whereConditions[] = "m.codc_categ NOT IN ('Cat1', 'Cat2', 'Cat3', 'Cat4', 'Cat5', 'Cat6', 'Cat7')";

        // ✅ Filtro de búsqueda por nombre
        if (!empty($searchTerm)) {
            $whereConditions[] = "m.apellidonombre_desc ILIKE :searchTerm";
            $params[':searchTerm'] = '%' . $searchTerm . '%';
        }

        // ✅ Filtro de año: mostrar los del año o los que no tienen datos en Guaraní
        if ($year !== null && $year !== '' && $year !== 'all') {
            $whereConditions[] = "(g.anio_guarani = :year OR g.anio_guarani IS NULL)";
            $params[':year'] = (int) $year;
        }

        $additionalWhere = '';
        if (!empty($whereConditions)) {
            $additionalWhere = 'WHERE ' . implode(' AND ', $whereConditions);
        }

        // ✅ DISTINCT asegura que un docente de Mapuche no se cuente más de una vez
        $sql = "SELECT COUNT(DISTINCT m.nro_docum)
    FROM docentes_mapuche AS m
    LEFT JOIN docentes_guarani AS g 
        ON m.nro_docum::VARCHAR = g.num_doc_guarani
    $additionalWhere";

        $stmt = $this->conn->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }


    // ================================
// 2. DOCENTES MAPUCHE 
// ================================
    public function obtenerDocentesMapuche($page = 1, $perPage = 50, $searchTerm = '', $year = null)
    {
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

    public function contarDocentesMapuche($searchTerm = '', $year = null)
    {
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
// 4. CONSULTAS JURUMI - STOCK
// ================================
    // ================================
// 4. DOCENTES JURUMI (STOCK)
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
                sd.id_unidad_medida AS \"Unidad Medida\",
                sd.cantidad AS \"Cantidad\",
                sd.monto_unitario AS \"Monto Unitario\",
                sd.id_area AS \"Área\",
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
                             ug.descripcion ILIKE :searchTerm OR
                             ec.observacion_egreso ILIKE :searchTerm OR
                             ed.id_egreso::TEXT ILIKE :searchTerm)";
            $params[':searchTerm'] = '%' . $searchTerm . '%';
        }

        $whereClause = '';
        if (!empty($whereConditions)) {
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        }

        $sql = "SELECT 
    ed.id_egreso_detalle AS \"ID Egreso Detalle\",
    ed.id_egreso AS \"ID Egreso\",
    ed.id_catalogo_bien_servicio AS \"ID Catálogo\",
    ed.cantidad_entregada AS \"Cantidad Entregada\",
    ec.id_ubicacion_fisica_destino AS \"ID Ubicación Destino\",
    ec.id_persona_solicitante AS \"ID Solicitante\",
    e.nombre AS \"Estado\",
    ec.ejercicio_egreso AS \"Ejercicio Egreso\",
    ec.observacion_egreso AS \"Observación\",
    ug.descripcion AS \"Unidad Gestión\",
    ea.cantidad AS \"Cantidad Almacén\"
FROM public.egresos_detalle ed
JOIN public.egresos_cabecera ec ON ed.id_egreso = ec.id_egreso 
JOIN public.egresos_almacen ea ON ea.id_egreso_detalle = ed.id_egreso_detalle  
JOIN public.estados e ON ec.estado = e.estado
JOIN public.unidades_gestion ug ON ec.id_unidad_gestion = ug.id_unidad_gestion
$whereClause
ORDER BY ed.id_egreso_detalle ASC";

        // ✅ SOLO APLICAR LIMIT SI NO ES EXPORTACIÓN
        if ($perPage > 0) {
            $sql .= " LIMIT :limit OFFSET :offset";
        }

        error_log("📝 SQL Detalle Entrega: " . $sql);

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

            error_log("✅ Resultados Detalle Entrega: " . count($resultados));

            if (count($resultados) > 0) {
                error_log("📋 Primer registro: " . print_r($resultados[0], true));
            }

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
                             ug.descripcion ILIKE :searchTerm OR
                             ec.observacion_egreso ILIKE :searchTerm OR
                             ed.id_egreso::TEXT ILIKE :searchTerm)";
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
    JOIN public.unidades_gestion ug ON ec.id_unidad_gestion = ug.id_unidad_gestion
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

                    $allowedTypes = ['guarani', 'mapuche', 'combinados', 'jurumi', 'entrega'];

                    if (!in_array($type, $allowedTypes)) {
                        throw new Exception("Tipo de consulta no válido. Opciones permitidas: " . implode(', ', $allowedTypes));
                    }

                    // Y en el match statement:
                    $result = match ($type) {
                        'guarani' => $consultas->obtenerDocentesGuarani($page, $perPage, $searchTerm, $year),
                        'mapuche' => $consultas->obtenerDocentesMapuche($page, $perPage, $searchTerm, $year),
                        'combinados' => $consultas->docentesCombinados($page, $perPage, $searchTerm, $year),
                        'jurumi' => $consultas->obtenerStockJurumi($page, $perPage, $searchTerm),
                        'entrega' => $consultas->obtenerDetalleEntrega($page, $perPage, $searchTerm) // ← NUEVO
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