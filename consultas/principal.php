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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="styles.css" />
    
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
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link active" aria-current="page" href="principal.php">Home</a>
                        </li>
                        <li class="nav-item dropdown nav-color">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Docentes</a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" data-value="guarani">Docentes con Asignación Aulica</a></li>
                                <li><a class="dropdown-item" data-value="mapuche">Designación Docente</a></li>
                                <!-- Ítem con submenú -->
                                <li class="dropdown-submenu">
                                    <a class="dropdown-item dropdown-toggle">Docentes - Unificado</a>
                                    <ul class="dropdown-menu">
                                        <!-- Submenú de años (sin funcionalidad) --> 
                                        <li><a class="dropdown-item year-item active" data-value="2011" data-type="combinados">2011</a></li>
                                        <li><a class="dropdown-item year-item active" data-value="2012" data-type="combinados">2012</a></li>
                                        <li><a class="dropdown-item year-item active" data-value="2013" data-type="combinados">2013</a></li>
                                        <li><a class="dropdown-item year-item active" data-value="2014" data-type="combinados">2014</a></li>
                                        <li><a class="dropdown-item year-item active" data-value="2015" data-type="combinados">2015</a></li>
                                        <li><a class="dropdown-item year-item active" data-value="2016" data-type="combinados">2016</a></li>
                                        <li><a class="dropdown-item year-item active" data-value="2017" data-type="combinados">2017</a></li>
                                        <li><a class="dropdown-item year-item active" data-value="2018" data-type="combinados">2018</a></li>
                                        <li><a class="dropdown-item year-item active" data-value="2019" data-type="combinados">2019</a></li>
                                        <li><a class="dropdown-item year-item active" data-value="2020" data-type="combinados">2020</a></li>
                                        <li><a class="dropdown-item year-item active" data-value="2021" data-type="combinados">2021</a></li>
                                        <li><a class="dropdown-item year-item active" data-value="2022" data-type="combinados">2022</a></li>
                                        <li><a class="dropdown-item year-item active" data-value="2023" data-type="combinados">2023</a></li>
                                        <li><a class="dropdown-item year-item active" data-value="2024" data-type="combinados">2024</a></li>
                                        <li><a class="dropdown-item year-item active" data-value="2025" data-type="combinados">2025</a></li>
                                    </ul>
                                </li>
                            </ul>
                        </li>
                    </ul>
                    <!-- Contenedor del filtro -->
                    <div class="filter-container">
                        <label for="filterInput" class="filter-label">Filtrar:</label>
                        <input type="text" id="filterInput" class="form-control filter-input" placeholder="Nombre/Apellido" />
                        <button type="button" id="filterBtn" class="btn btn-outline-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <p type="button" id="refreshBtn"></p>
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
        let currentYear = 'all'; // Variable para almacenar el año seleccionado
        let totalPages = 1; // Variable para almacenar el total de páginas

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

            resultsContainer.innerHTML = '<div class="loading">Cargando datos...</div>';
            paginationContainer.innerHTML = '';
            selectionTitle.textContent = `${currentSelectionText}`;

            try {
                const response = await fetch(
                    `${baseURL}?action=getData&type=${currentQueryType}&page=${currentPage}&search=${encodeURIComponent(currentSearchTerm)}&year=${currentYear}`
                );

                if (!response.ok) throw new Error('Error en la respuesta del servidor');

                const data = await response.json();
                if (!data.success) throw new Error(data.error || 'Error desconocido');

                // Actualizar el total de páginas
                totalPages = data.pagination.total_pages || 1;

                let html = '';

                if (currentSearchTerm) {
                    html += `<p class="search-info">Filtrado por: <strong>${currentSearchTerm}</strong></p>`;
                }

                if (currentYear !== 'all') {
                    html += `<p class="search-info">Año seleccionado: <strong>${currentYear}</strong></p>`;
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
                    exportButtons.style.gap = '10px';

                    // Sincronización scroll horizontal superior e inferior
                    setTimeout(() => {
                        const topScroll = document.getElementById('topScroll');
                        const tableWrapper = document.getElementById('tableWrapper');

                        if (topScroll && tableWrapper) {
                            // Ajustar el ancho del scroll superior para que coincida con la tabla
                            topScroll.scrollLeft = 0;

                            // Crear un div fantasma para ocupar ancho igual al scroll de la tabla
                            if (!topScroll.querySelector('.ghost')) {
                                const ghostDiv = document.createElement('div');
                                ghostDiv.className = 'ghost';
                                ghostDiv.style.width = tableWrapper.scrollWidth + 'px';
                                ghostDiv.style.height = '1px';
                                topScroll.appendChild(ghostDiv);
                            }

                            topScroll.onscroll = () => {
                                tableWrapper.scrollLeft = topScroll.scrollLeft;
                            };

                            tableWrapper.onscroll = () => {
                                topScroll.scrollLeft = tableWrapper.scrollLeft;
                            };
                        }
                    }, 100);
                } else {
                    resultsContainer.innerHTML = '<div class="alert alert-info">No se encontraron resultados.</div>';
                }

                // Nueva paginación según la imagen
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

        function irPagina(pagina) {
            const pageNum = parseInt(pagina);
            if (pageNum >= 1 && pageNum <= totalPages) {
                currentPage = pageNum;
                cargarResultados();
                document.getElementById('pageInput').value = currentPage;
                document.querySelector('.query-panel').scrollIntoView({ behavior: 'smooth' });
            }
        }

        async function obtenerTodosLosDatos() {
            try {
                const response = await fetch(
                    `${baseURL}?action=getData&type=${currentQueryType}&search=${encodeURIComponent(currentSearchTerm)}&year=${currentYear}&perPage=100000`
                );
                const data = await response.json();
                return data.data || [];
            } catch (error) {
                console.error("Error al obtener todos los datos:", error);
                return [];
            }
        }

        // Funciones de exportación (sin cambios)
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
            const doc = new jsPDF({
                orientation: "landscape",
                unit: "mm",
                format: "a4"
            });

            // Configuración de estilos
            const primaryColor = [41, 128, 185]; // Azul profesional
            const secondaryColor = [240, 240, 240]; // Gris claro
            const margin = 10;
            const pageWidth = doc.internal.pageSize.getWidth();
            const pageHeight = doc.internal.pageSize.getHeight();
            const availableWidth = pageWidth - (margin * 2);

            // Encabezado profesional
            doc.setFillColor(...primaryColor);
            doc.rect(0, 0, pageWidth, 20, 'F');
            doc.setFontSize(16);
            doc.setTextColor(255, 255, 255);
            doc.setFont("helvetica", "bold");
            doc.text("LISTADO COMPLETO DE DOCENTES", pageWidth / 2, 12, { align: "center" });
            doc.setFontSize(10);
            doc.text(`Exportado el: ${new Date().toLocaleString('es-AR')}`, pageWidth / 2, 18, { align: "center" });

            // Información de filtros aplicados
            doc.setFontSize(9);
            doc.setTextColor(100, 100, 100);
            doc.setFont("helvetica", "normal");
            let filterInfo = `Tipo: ${currentQueryType.toUpperCase()}`;
            if (currentYear && currentYear !== 'all') {
                filterInfo += ` | Año: ${currentYear}`;
            }
            if (currentSearchTerm) {
                filterInfo += ` | Búsqueda: "${currentSearchTerm}"`;
            }
            doc.text(filterInfo, margin, 30);
            doc.text(`Total de registros: ${datos.length}`, pageWidth - margin, 30, { align: "right" });

            // Preparar datos para la tabla
            const headers = [Object.keys(datos[0])];
            const rows = datos.map(row => Object.values(row));

            const addWatermark = () => {
                doc.setFontSize(60);
                doc.setTextColor(240, 240, 240);
                doc.setFont("helvetica", "bold");
                doc.text("CONFIDENCIAL", pageWidth / 2, pageHeight / 2, {
                    align: "center",
                    angle: 45,
                    opacity: 0.1
                });
                doc.setTextColor(50, 50, 50);
            };

            addWatermark();

            // Configuración de la tabla profesional
            doc.autoTable({
                startY: 35,
                head: headers,
                body: rows,
                margin: { top: 35, left: margin, right: margin },
                styles: {
                    fontSize: 7,
                    cellPadding: 1,
                    lineColor: [200, 200, 200],
                    lineWidth: 0.1,
                    textColor: [50, 50, 50]
                },
                headStyles: {
                    fillColor: primaryColor,
                    textColor: 255,
                    fontStyle: 'bold',
                    fontSize: 8,
                    halign: 'center',
                    valign: 'middle'
                },
                alternateRowStyles: {
                    fillColor: secondaryColor
                },
                theme: 'grid',
                tableLineColor: [150, 150, 150],
                tableLineWidth: 0.2,
                columnStyles: {
                    // Ajustes específicos para columnas comunes
                    0: { cellWidth: 'auto', halign: 'left' },
                    1: { cellWidth: 'auto', halign: 'left' },
                    2: { cellWidth: 'auto', halign: 'center' },
                    3: { cellWidth: 'auto', halign: 'center' }
                },
                didDrawPage: function(data) {
                    // Pie de página profesional
                    doc.setFontSize(8);
                    doc.setTextColor(100, 100, 100);
                    doc.setFont("helvetica", "italic");
                    doc.text(`Página ${doc.internal.getNumberOfPages()}`, pageWidth / 2, pageHeight - 5, { align: "center" });
                    
                    // Línea decorativa
                    doc.setDrawColor(200, 200, 200);
                    doc.line(margin, pageHeight - 10, pageWidth - margin, pageHeight - 10);
                }
            });

            // Guardar con nombre descriptivo
            const fileName = `docentes_${currentQueryType}_${currentYear || 'todos'}_${new Date().toISOString().split('T')[0]}.pdf`;
            doc.save(fileName);
        }

        async function secureLogout() {
            try {
                await fetch('logout.php', { method: 'POST' });
                await Swal.fire({
                    title: '¡Sesión cerrada!',
                    text: 'Vuelve pronto 😊',
                    icon: 'success',
                    confirmButtonColor: '#2c3e50',
                    background: '#fff',
                    timer: 2000
                });
                window.location.replace(`../login/index.html?nocache=${Date.now()}`);
            } catch (error) {
                Swal.fire('Error', 'No se pudo cerrar sesión', 'error');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('logoutBtn').addEventListener('click', secureLogout);

            document.getElementById('refreshBtn').addEventListener('click', () => {
                currentPage = 1;
                cargarResultados();
            });

            document.getElementById('filterBtn').addEventListener('click', function() {
                currentSearchTerm = document.getElementById('filterInput').value.trim();
                currentPage = 1;
                cargarResultados();
            });

            document.getElementById('filterInput').addEventListener('keyup', function(e) {
                if (e.key === 'Enter') {
                    currentSearchTerm = this.value.trim();
                    currentPage = 1;
                    cargarResultados();
                }
            });

            // Manejo del menú principal
            document.querySelectorAll('.dropdown-item:not(.year-item)').forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    currentQueryType = this.dataset.value;
                    currentSelectionText = this.textContent;
                    currentPage = 1;
                    currentSearchTerm = '';
                    currentYear = 'all';
                    document.getElementById('filterInput').value = '';
                    cargarResultados();
                });
            });

            // Manejo del submenú de años
            document.querySelectorAll('.year-item').forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    // 1. Actualizar parámetros
                    currentQueryType = 'combinados'; // ← FIJO para este submenú
                    currentYear = this.dataset.value === 'combinados' ? 'all' : this.dataset.value;
                    currentSelectionText = `Docentes - Unificado ${currentYear === 'all' ? '' : '('+currentYear+')'}`;
                    currentPage = 1;

                    // 2. Actualizar estilos
                    document.querySelectorAll('.year-item').forEach(yearItem => {
                        yearItem.classList.remove('active');
                    });
                    this.classList.add('active');

                    // 3. Cargar resultados
                    cargarResultados();
                });
            });

            document.getElementById('excelBtn').addEventListener('click', exportarAExcel);
            document.getElementById('pdfBtn').addEventListener('click', exportarAPDF);
        });
    </script>
</body>
</html>