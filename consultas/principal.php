<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
include_once("../config.php");
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Consulta de Docentes</title>
    <link rel="icon" type="image/x-icon" href="../imagenes/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="styles.css" />
<style>
    .producto-container {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
        border: 1px solid #dee2e6;
        margin: 15px 0;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .producto-container label {
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 10px;
    }
    
    .producto-container .form-select {
        border-radius: 8px;
        border: 2px solid #3498db;
        padding: 10px 15px;
        font-size: 14px;
        max-height: 200px;
        overflow-y: auto;
        width: 100%;
    }

    .producto-container .form-select option {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 500px;
        display: block;
    }
    
    .producto-container .form-select:focus {
        border-color: #2980b9;
        box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
    }
</style>
</head>

<body>
    <div class="app-container">
        <header class="app-header">
            <div class="header-container">
                <div class="undav-container">
                    <img class="undav" src="../imagenes/undav.png" />
                </div>
                <div class="logo-container">
                    <img class="logo" src="../imagenes/logo.png" />
                </div>
                <button id="logoutBtn" class="btn btn--sm">
                    <i class="fas fa-sign-out-alt"></i> Salir
                </button>
            </div>
        </header>

        <nav class="navbar navbar-expand-lg custom-navbar">
            <div class="container-fluid">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarSupportedContent">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link active" aria-current="page" href="principal.php">Home</a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                                aria-expanded="false">Docentes</a>
                            <ul class="dropdown-menu">
                                <li class="dropdown-submenu">
                                    <a class="dropdown-item dropdown-toggle">Docentes con Asignación Aulica</a>
                                    <ul class="dropdown-menu">
                                        <!-- Submenú de años -->
                                        <li><a class="dropdown-item year-item" data-value="2011"
                                                data-type="guarani">2011</a></li>
                                        <li><a class="dropdown-item year-item" data-value="2012"
                                                data-type="guarani">2012</a></li>
                                        <li><a class="dropdown-item year-item" data-value="2013"
                                                data-type="guarani">2013</a></li>
                                        <li><a class="dropdown-item year-item" data-value="2014"
                                                data-type="guarani">2014</a></li>
                                        <li><a class="dropdown-item year-item" data-value="2015"
                                                data-type="guarani">2015</a></li>
                                        <li><a class="dropdown-item year-item" data-value="2016"
                                                data-type="guarani">2016</a></li>
                                        <li><a class="dropdown-item year-item" data-value="2017"
                                                data-type="guarani">2017</a></li>
                                        <li><a class="dropdown-item year-item" data-value="2018"
                                                data-type="guarani">2018</a></li>
                                        <li><a class="dropdown-item year-item" data-value="2019"
                                                data-type="guarani">2019</a></li>
                                        <li><a class="dropdown-item year-item" data-value="2020"
                                                data-type="guarani">2020</a></li>
                                        <li><a class="dropdown-item year-item" data-value="2021"
                                                data-type="guarani">2021</a></li>
                                        <li><a class="dropdown-item year-item" data-value="2022"
                                                data-type="guarani">2022</a></li>
                                        <li><a class="dropdown-item year-item" data-value="2023"
                                                data-type="guarani">2023</a></li>
                                        <li><a class="dropdown-item year-item" data-value="2024"
                                                data-type="guarani">2024</a></li>
                                        <li><a class="dropdown-item year-item" data-value="2025"
                                                data-type="guarani">2025</a></li>
                                    </ul>
                                </li>
                                <li><a class="dropdown-item" data-value="mapuche">Designación Docente</a></li>
                                <li class="dropdown-submenu">
                                    <a class="dropdown-item dropdown-toggle">Docentes - Unificado</a>
                                    <ul class="dropdown-menu">
                                        <!-- Submenú de años -->
                                        <li><a class="dropdown-item year-item" data-value="2011"
                                                data-type="combinados">2011</a></li>
                                        <li><a class="dropdown-item year-item" data-value="2012"
                                                data-type="combinados">2012</a></li>
                                        <li><a class="dropdown-item year-item" data-value="2013"
                                                data-type="combinados">2013</a></li>
                                        <li><a class="dropdown-item year-item" data-value="2014"
                                                data-type="combinados">2014</a></li>
                                        <li><a class="dropdown-item year-item" data-value="2015"
                                                data-type="combinados">2015</a></li>
                                        <li><a class="dropdown-item year-item" data-value="2016"
                                                data-type="combinados">2016</a></li>
                                        <li><a class="dropdown-item year-item" data-value="2017"
                                                data-type="combinados">2017</a></li>
                                        <li><a class="dropdown-item year-item" data-value="2018"
                                                data-type="combinados">2018</a></li>
                                        <li><a class="dropdown-item year-item" data-value="2019"
                                                data-type="combinados">2019</a></li>
                                        <li><a class="dropdown-item year-item" data-value="2020"
                                                data-type="combinados">2020</a></li>
                                        <li><a class="dropdown-item year-item" data-value="2021"
                                                data-type="combinados">2021</a></li>
                                        <li><a class="dropdown-item year-item" data-value="2022"
                                                data-type="combinados">2022</a></li>
                                        <li><a class="dropdown-item year-item" data-value="2023"
                                                data-type="combinados">2023</a></li>
                                        <li><a class="dropdown-item year-item" data-value="2024"
                                                data-type="combinados">2024</a></li>
                                        <li><a class="dropdown-item year-item" data-value="2025"
                                                data-type="combinados">2025</a></li>
                                    </ul>
                                </li>
                            </ul>
                        </li>
                        <!-- Elemento del menú de Jurumí -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                                aria-expanded="false">Jurumí</a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item jurumi-item" data-value="jurumi" data-type="stock">Detalle
                                        de Stock</a></li>
                                <li><a class="dropdown-item jurumi-item" data-value="entrega" data-type="entrega">Detalle de Entrega</a>
                                </li>
                                <li><a class="dropdown-item jurumi-item" data-value="producto" data-type="producto">Detalle de Producto</a></li>
                            </ul>
                        </li>
                    </ul>
                    <!-- Contenedor del filtro -->
                    <div class="filter-container">
                        <label for="filterInput" class="filter-label">Filtrar:</label>
                        <input type="text" id="filterInput" class="form-control filter-input"
                            placeholder="Nombre/Apellido" />
                        <button type="button" id="filterBtn" class="btn btn-outline-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <p type="button" id="refreshBtn">
                    </p>
                </div>
            </div>
        </nav>

        <div class="header-container">
            <div id="selectionTitle" class="selection-title text-center"></div>
            <div id="exportButtons" class="export-buttons" style="display:none;">
                <button id="excelBtn" class="btn btn-success btn-sm"><i class="fas fa-file-excel"></i> Excel</button>
                <button id="pdfBtn" class="btn btn-danger btn-sm"><i class="fas fa-file-pdf"></i> PDF</button>
            </div>
        </div>


        <main class="app-main">
            <div id="resultsContainer" class="results-container"></div>
            <div id="logoFondo" class="logo-fondo"></div>
            <div id="paginationContainer" class="pagination-container"></div>
        </main>

        <footer class="app-footer">
            <p>TINKUY v.1.0 &copy; 2025 - Desarrollado por el Área de Sistemas de la UNDAV.</p>
        </footer>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.min.js"></script>
    <!-- SheetJS para Excel -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <!-- jsPDF y autoTable para PDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

    <script>
    const baseURL = "<?php echo BASE_URL; ?>";
    let currentPage = 1;
    const perPage = 10;
    let currentQueryType = '';
    let currentSelectionText = 'Seleccione un grupo de docentes del menú desplegable';
    let currentSearchTerm = '';
    let currentYear = 'all';
    let totalPages = 1;
    let currentJurumiType = '';
    let currentProductoId = '';
    let listaProductos = []; // NUEVO: Guardar la lista de productos

    // NUEVA FUNCIÓN: Cargar lista de productos para mostrar en tabla
    async function cargarListaProductos() {
        try {
            const response = await fetch(`${baseURL}?action=getProductos`);
            const data = await response.json();
            
            if (data.success && data.data.length > 0) {
                listaProductos = data.data; // Guardar la lista
                mostrarTablaProductos();
            } else {
                document.getElementById('resultsContainer').innerHTML = '<div class="alert alert-danger">Error al cargar productos</div>';
            }
        } catch (error) {
            console.error('Error:', error);
            document.getElementById('resultsContainer').innerHTML = '<div class="alert alert-danger">Error de conexión</div>';
        }
    }

    // NUEVA FUNCIÓN: Mostrar tabla con lista de productos
    function mostrarTablaProductos() {
        const resultsContainer = document.getElementById('resultsContainer');
        const paginationContainer = document.getElementById('paginationContainer');
        const exportButtons = document.getElementById('exportButtons');
        
        exportButtons.style.display = 'none';
        
        let html = '<div class="alert alert-info mb-3">Seleccione un producto haciendo clic en la fila</div>';
        
        html += `<div class="table-scroll-container">
            <div class="table-scroll-top" id="topScroll"></div>
            <div class="table-wrapper" id="tableWrapper">
                <table class="table table-striped table-bordered table-hover" style="width:100%; margin:0">
                    <thead><tr>
                        <th>ID Producto</th>
                        <th>Descripción del Producto</th>
                    </tr></thead>
                    <tbody>`;

        // Calcular paginación manual para la lista
        const startIndex = (currentPage - 1) * perPage;
        const endIndex = startIndex + perPage;
        const productosPagina = listaProductos.slice(startIndex, endIndex);
        totalPages = Math.ceil(listaProductos.length / perPage);

        productosPagina.forEach(producto => {
            // Cortar descripción si es muy larga
            const descripcionCorta = producto.text.length > 80 ? 
                producto.text.substring(0, 80) + '...' : producto.text;
                
            html += `<tr class="producto-row" data-id="${producto.id}" style="cursor: pointer;" 
                     onmouseover="this.style.backgroundColor='#f8f9fa'" 
                     onmouseout="this.style.backgroundColor='transparent'">
                <td><strong>${producto.id}</strong></td>
                <td title="${producto.text}">${descripcionCorta}</td>
            </tr>`;
        });

        html += '</tbody></table></div></div>';
        resultsContainer.innerHTML = html;

        // Paginación para la lista de productos
        let pagHtml = `<div class="pagination-new">
            <a href="#" class="btn-pagination ${currentPage === 1 ? 'disabled' : ''}" 
               onclick="${currentPage > 1 ? `cambiarPaginaProductos(${currentPage - 1});` : ''} return false;">
                ← Anterior
            </a>
            <span class="pagination-text">Página</span>
            <input type="number" class="page-input" id="pageInput" value="${currentPage}" min="1" max="${totalPages}" 
                   onkeypress="if(event.key === 'Enter') cambiarPaginaProductos(this.value)">
            <span class="pagination-text">de ${totalPages}</span>
            <a href="#" class="btn-pagination ${currentPage === totalPages ? 'disabled' : ''}" 
               onclick="${currentPage < totalPages ? `cambiarPaginaProductos(${currentPage + 1});` : ''} return false;">
                Siguiente →
            </a>
        </div>`;

        paginationContainer.innerHTML = pagHtml;

        // Agregar event listeners para las filas
        setTimeout(() => {
            document.querySelectorAll('.producto-row').forEach(row => {
                row.addEventListener('click', function() {
                    const id = this.dataset.id;
                    const descripcion = this.cells[1].title;
                    seleccionarProductoDesdeTabla(id, descripcion);
                });
            });

            // Sincronización scroll
            const topScroll = document.getElementById('topScroll');
            const tableWrapper = document.getElementById('tableWrapper');
            if (topScroll && tableWrapper) {
                topScroll.scrollLeft = 0;
                if (!topScroll.querySelector('.ghost')) {
                    const ghostDiv = document.createElement('div');
                    ghostDiv.className = 'ghost';
                    ghostDiv.style.width = tableWrapper.scrollWidth + 'px';
                    ghostDiv.style.height = '1px';
                    topScroll.appendChild(ghostDiv);
                }
                topScroll.onscroll = () => tableWrapper.scrollLeft = topScroll.scrollLeft;
                tableWrapper.onscroll = () => topScroll.scrollLeft = tableWrapper.scrollLeft;
            }
        }, 100);
    }

    // NUEVA FUNCIÓN: Cambiar página en la lista de productos
    function cambiarPaginaProductos(pagina) {
        const pageNum = parseInt(pagina);
        if (pageNum >= 1 && pageNum <= totalPages) {
            currentPage = pageNum;
            mostrarTablaProductos();
        }
    }

    // NUEVA FUNCIÓN: Seleccionar producto desde la tabla
    function seleccionarProductoDesdeTabla(id, descripcion) {
        currentProductoId = id;
        currentPage = 1;
        currentSelectionText = `Detalle de Producto: ${descripcion.substring(0, 50)}...`;
        document.getElementById('selectionTitle').textContent = currentSelectionText;
        cargarResultados();
    }

    async function cargarResultados() {
        const resultsContainer = document.getElementById('resultsContainer');
        const paginationContainer = document.getElementById('paginationContainer');
        const selectionTitle = document.getElementById('selectionTitle');
        const exportButtons = document.getElementById('exportButtons');

        exportButtons.style.display = 'none';

        if (!currentQueryType) {
            resultsContainer.innerHTML = '<div class="error">Seleccione un tipo de docentes del menú</div>';
            paginationContainer.innerHTML = '';
            selectionTitle.textContent = currentSelectionText;
            return;
        }

        // MODO PRODUCTO: Mostrar lista de productos si no hay uno seleccionado
        if (currentQueryType === 'producto' && !currentProductoId) {
            selectionTitle.textContent = 'Lista de Productos - Seleccione un producto';
            cargarListaProductos();
            return;
        }

        resultsContainer.innerHTML = '<div class="loading">Cargando datos...</div>';
        paginationContainer.innerHTML = '';
        selectionTitle.textContent = `${currentSelectionText}`;

        try {
            let url = `${baseURL}?action=getData&type=${currentQueryType}&page=${currentPage}&search=${encodeURIComponent(currentSearchTerm)}`;

            if (currentQueryType !== 'jurumi' && currentQueryType !== 'entrega' && currentQueryType !== 'producto') {
                url += `&year=${currentYear}`;
            }

            if (currentQueryType === 'producto' && currentProductoId) {
                url += `&producto_id=${currentProductoId}`;
            }

            const response = await fetch(url);
            if (!response.ok) throw new Error('Error en la respuesta del servidor');

            const data = await response.json();
            if (!data.success) throw new Error(data.error || 'Error desconocido');

            totalPages = data.pagination.total_pages || 1;

            let html = '';

            if (currentSearchTerm) {
                html += `<p class="search-info">Filtrado por: <strong>${currentSearchTerm}</strong></p>`;
            }

            if (currentYear !== 'all' && currentQueryType !== 'jurumi' && currentQueryType !== 'entrega' && currentQueryType !== 'producto') {
                html += `<p class="search-info">Año seleccionado: <strong>${currentYear}</strong></p>`;
            }

            // BOTÓN VOLVER para productos
            if (currentQueryType === 'producto' && currentProductoId) {
                html += `<button class="btn btn-secondary btn-sm mb-3" onclick="volverAListaProductos()">← Volver a la lista de productos</button>`;
            }

            html += `<div class="table-scroll-container">
                <div class="table-scroll-top" id="topScroll"></div>
                <div class="table-wrapper" id="tableWrapper">
                    <table class="table table-striped table-bordered" style="width:100%; margin:0">
                        <thead><tr>`;

            if (data.data.length > 0) {
                Object.keys(data.data[0]).forEach(key => {
                    html += `<th style="white-space: nowrap">${key}</th>`;
                });

                html += '</tr></thead><tbody>';

                data.data.forEach(row => {
                    html += '<tr>';
                    Object.values(row).forEach(value => {
                        html += `<td style="white-space: nowrap">${value ?? ''}</td>`;
                    });
                    html += '</tr>';
                });

                html += '</tbody></table></div></div>';
                resultsContainer.innerHTML = html;
                exportButtons.style.display = 'flex';

                setTimeout(() => {
                    const topScroll = document.getElementById('topScroll');
                    const tableWrapper = document.getElementById('tableWrapper');
                    if (topScroll && tableWrapper) {
                        topScroll.scrollLeft = 0;
                        if (!topScroll.querySelector('.ghost')) {
                            const ghostDiv = document.createElement('div');
                            ghostDiv.className = 'ghost';
                            ghostDiv.style.width = tableWrapper.scrollWidth + 'px';
                            ghostDiv.style.height = '1px';
                            topScroll.appendChild(ghostDiv);
                        }
                        topScroll.onscroll = () => tableWrapper.scrollLeft = topScroll.scrollLeft;
                        tableWrapper.onscroll = () => topScroll.scrollLeft = tableWrapper.scrollLeft;
                    }
                }, 100);
            } else {
                resultsContainer.innerHTML = '<div class="alert alert-info">No se encontraron resultados.</div>';
            }

            let pagHtml = `<div class="pagination-new">
                <a href="#" class="btn-pagination ${currentPage === 1 ? 'disabled' : ''}" 
                   onclick="${currentPage > 1 ? `irPagina(${currentPage - 1});` : ''} return false;">
                    ← Anterior
                </a>
                <span class="pagination-text">Página</span>
                <input type="number" class="page-input" id="pageInput" value="${currentPage}" min="1" max="${totalPages}" 
                       onkeypress="if(event.key === 'Enter') irPagina(this.value)">
                <span class="pagination-text">de ${totalPages}</span>
                <a href="#" class="btn-pagination ${currentPage === totalPages ? 'disabled' : ''}" 
                   onclick="${currentPage < totalPages ? `irPagina(${currentPage + 1});` : ''} return false;">
                    Siguiente →
                </a>
            </div>`;

            paginationContainer.innerHTML = pagHtml;

        } catch (error) {
            console.error('Error:', error);
            resultsContainer.innerHTML = `<div class="error"><strong>Error:</strong> ${error.message}</div>`;
        }
    }

    // NUEVA FUNCIÓN: Volver a la lista de productos
    function volverAListaProductos() {
        currentProductoId = '';
        currentPage = 1;
        currentSelectionText = 'Detalle de Producto';
        document.getElementById('selectionTitle').textContent = 'Lista de Productos - Seleccione un producto';
        cargarListaProductos();
    }

    function irPagina(pagina) {
        const pageNum = parseInt(pagina);
        if (pageNum >= 1 && pageNum <= totalPages) {
            currentPage = pageNum;
            cargarResultados();
            document.getElementById('pageInput').value = currentPage;
        }
    }

    async function obtenerTodosLosDatos() {
        try {
            let url = `${baseURL}?action=getData&type=${currentQueryType}&search=${encodeURIComponent(currentSearchTerm)}&perPage=100000`;
            if (currentQueryType !== 'jurumi' && currentQueryType !== 'entrega' && currentQueryType !== 'producto') {
                url += `&year=${currentYear}`;
            }
            if (currentQueryType === 'producto' && currentProductoId) {
                url += `&producto_id=${currentProductoId}`;
            }
            const response = await fetch(url);
            const data = await response.json();
            return data.data || [];
        } catch (error) {
            return [];
        }
    }

    async function exportarAExcel() {
        const datos = await obtenerTodosLosDatos();
        if (datos.length === 0) {
            alert("No hay datos para exportar.");
            return;
        }
        const wsData = [Object.keys(datos[0]), ...datos.map(row => Object.values(row))];
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.aoa_to_sheet(wsData);
        XLSX.utils.book_append_sheet(wb, ws, "Resultados");
        XLSX.writeFile(wb, "resultados.xlsx");
    }

    async function exportarAPDF() {
        const datos = await obtenerTodosLosDatos();
        if (datos.length === 0) {
            alert("No hay datos para exportar.");
            return;
        }
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF({ orientation: "landscape", unit: "mm", format: "a4" });
        doc.setFillColor(41, 128, 185);
        doc.rect(0, 0, doc.internal.pageSize.getWidth(), 20, 'F');
        doc.setFontSize(16);
        doc.setTextColor(255, 255, 255);
        doc.setFont("helvetica", "bold");
        
        const titulo = currentQueryType === 'jurumi' ? "DETALLE DE STOCK JURUMÍ" :
                      currentQueryType === 'producto' ? "DETALLE DE PRODUCTO JURUMÍ" : 
                      "LISTADO COMPLETO DE DOCENTES";
        doc.text(titulo, doc.internal.pageSize.getWidth() / 2, 12, { align: "center" });
        
        const headers = [Object.keys(datos[0])];
        const rows = datos.map(row => Object.values(row));
        doc.autoTable({
            startY: 35,
            head: headers,
            body: rows,
            margin: { top: 35, left: 10, right: 10 },
            styles: { fontSize: 7, cellPadding: 1 },
            headStyles: { fillColor: [41, 128, 185], textColor: 255, fontStyle: 'bold', fontSize: 8 }
        });
        
        let fileName = currentQueryType === 'jurumi' ? `stock_jurumi` :
                      currentQueryType === 'producto' ? `producto_jurumi` :
                      `docentes_${currentQueryType}`;
        doc.save(`${fileName}_${new Date().toISOString().split('T')[0]}.pdf`);
    }

    async function secureLogout() {
        try {
            await fetch('logout.php', { method: 'POST' });
            await Swal.fire({ title: '¡Sesión cerrada!', text: 'Vuelve pronto 😊', icon: 'success', timer: 2000 });
            window.location.replace(`../login/index.html?nocache=${Date.now()}`);
        } catch (error) {
            Swal.fire('Error', 'No se pudo cerrar sesión', 'error');
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('logoutBtn').addEventListener('click', secureLogout);
        document.getElementById('refreshBtn').addEventListener('click', () => { currentPage = 1; cargarResultados(); });
        document.getElementById('filterBtn').addEventListener('click', function () {
            currentSearchTerm = document.getElementById('filterInput').value.trim();
            currentPage = 1;
            cargarResultados();
        });
        document.getElementById('filterInput').addEventListener('keyup', function (e) {
            if (e.key === 'Enter') {
                currentSearchTerm = this.value.trim();
                currentPage = 1;
                cargarResultados();
            }
        });

        document.querySelectorAll('.dropdown-item:not(.year-item):not(.jurumi-item)').forEach(item => {
            item.addEventListener('click', function (e) {
                e.preventDefault();
                currentQueryType = this.dataset.value;
                currentSelectionText = this.textContent;
                currentPage = 1;
                currentSearchTerm = '';
                currentYear = 'all';
                currentProductoId = '';
                document.getElementById('filterInput').value = '';
                cargarResultados();
            });
        });

        document.querySelectorAll('.year-item').forEach(item => {
            item.addEventListener('click', function (e) {
                e.preventDefault();
                const queryType = this.dataset.type || 'combinados';
                currentQueryType = queryType;
                currentYear = this.dataset.value;
                currentSelectionText = queryType === 'guarani' ? 
                    `Docentes con Asignación guarani ${currentYear === 'all' ? '' : '(' + currentYear + ')'}` :
                    `Docentes - Unificado ${currentYear === 'all' ? '' : '(' + currentYear + ')'}`;
                currentPage = 1;
                currentProductoId = '';
                document.querySelectorAll('.year-item').forEach(yearItem => yearItem.classList.remove('active'));
                this.classList.add('active');
                cargarResultados();
            });
        });

        document.querySelectorAll('.jurumi-item').forEach(item => {
            item.addEventListener('click', function (e) {
                e.preventDefault();
                currentQueryType = this.dataset.value;
                currentJurumiType = this.dataset.type;
                currentSelectionText = this.textContent;
                currentProductoId = '';
                currentPage = 1;
                currentSearchTerm = '';
                currentYear = 'all';
                document.getElementById('filterInput').value = '';

                if (currentJurumiType === 'stock') {
                    document.getElementById('filterInput').placeholder = "Código/Descripción/Almacén";
                } else if (currentJurumiType === 'entrega') {
                    document.getElementById('filterInput').placeholder = "Estado/Unidad/Observación/Solicitante";
                } else if (currentJurumiType === 'producto') {
                    document.getElementById('filterInput').placeholder = "Buscar en productos...";
                }

                document.querySelectorAll('.jurumi-item').forEach(jurumiItem => jurumiItem.classList.remove('active'));
                this.classList.add('active');
                cargarResultados();
            });
        });

        document.getElementById('excelBtn').addEventListener('click', exportarAExcel);
        document.getElementById('pdfBtn').addEventListener('click', exportarAPDF);
    });
</script>
</body>

</html>