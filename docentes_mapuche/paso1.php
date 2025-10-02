<?php
include_once("../config.php");


$dsn = "pgsql:host={$config_tinkuy['host']};port={$config_tinkuy['port']};dbname={$config_tinkuy['dbname']}";

try {
    // Establecer conexión
    $conn = new PDO($dsn, $config_tinkuy['user'], $config_tinkuy['password']);
    
    // Configurar PDO para que lance excepciones en errores
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Conexión exitosa a PostgreSQL\n\n";
    
    // 1. Verificar si la tabla ya existe
    echo "🔍 Verificando si la tabla docentes_mapuche existe...\n";
    $checkTableSQL = "SELECT EXISTS (
        SELECT 1 
        FROM information_schema.tables 
        WHERE table_schema = 'public' 
        AND table_name = 'docentes_mapuche'
    )";
    
    $tableExists = $conn->query($checkTableSQL)->fetchColumn();
    
    if ($tableExists) {
        echo "ℹ️ La tabla docentes_mapuche ya existe\n";
    } else {
        echo "ℹ️ La tabla docentes_mapuche no existe. Creándola...\n";
    }
    
    // 2. Crear tabla docentes_mapuche
    $createTableSQL = "
    CREATE TABLE IF NOT EXISTS docentes_mapuche (
    id SERIAL PRIMARY KEY,
    nro_legaj VARCHAR(50),
    apellidonombre_desc VARCHAR(200),
    tipo_docum VARCHAR(20),
    nro_docum VARCHAR(200),
    num_cargo INTEGER,
    codc_grupo VARCHAR(200),
    desc_dedic VARCHAR(200),
    desc_categ VARCHAR(200),  -- ✅ ESTA ES LA QUE FALTA
    codc_categ VARCHAR(200),
    codc_carac VARCHAR(200),
    fec_alta DATE,
    fec_baja DATE,
    licencia VARCHAR(200),
    fec_hasta DATE,
    desc_item VARCHAR(200),
    tipo_norma VARCHAR(200),
    tipo_emite VARCHAR(200),
    fec_norma DATE,
    nro_norma VARCHAR(200)
);
    ";
    echo "⚙️ Ejecutando sentencia CREATE TABLE...\n";
    $conn->exec($createTableSQL);
    echo "✅ Operación CREATE TABLE completada\n";
    
    // 3. Verificar nuevamente si la tabla existe
    $tableExistsNow = $conn->query($checkTableSQL)->fetchColumn();
    
    if ($tableExistsNow) {
        echo "\n🎉 ¡Tabla docentes_mapuche verificada con éxito!\n";
    } else {
        echo "\n⚠️ La tabla docentes_mapuche no se creó, pero no hubo errores. Verifica permisos.\n";
    }
    
} catch (PDOException $e) {
    die("\n❌ Error: " . $e->getMessage() . "\n");
}

// Verificar tablas existentes después de las operaciones
echo "\n🔍 Verificación final de tablas en la base de datos:\n";

$tables = $conn->query("
    SELECT table_name 
    FROM information_schema.tables 
    WHERE table_schema = 'public'
")->fetchAll(PDO::FETCH_COLUMN);



