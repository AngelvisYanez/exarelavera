
let empresas = [];
let empresasFiltradas = [];
let filtroActual = 'todos';
let filtroContadorActual = '';
let filtroRegimenActual = '';
let estadisticas = { total: 0, con_periodo: 0, sin_periodo: 0 };
let listadosGuardados = [];
let estadosEmpresasMap = {}; // Mapa de estados de empresas: { Emp_Cod: 'L' o 'A' }
let activandoPeriodo = false; // Flag para prevenir múltiples clics en activar período
let guardandoListado = false; // Flag para prevenir múltiples clics en guardar listado
let modoSoloLectura = false; // Flag para indicar si estamos en modo solo lectura
let checkboxDesbloquearVisible = false; // Flag para indicar si el checkbox de desbloquear debe estar visible

function limpiarMensajeParaAlert(msg) {
    return String(msg || '').replace(/\n/g, ' ').substring(0, 200);
}

function ajaxReq(data, success, error) {
    var fd = new FormData();
    $.each(data, function(k, v) {
        if ($.isArray(v) || $.isObj(v)) {
            fd.append(k, JSON.stringify(v));
        } else {
            fd.append(k, v);
        }
    });
    
    if (typeof jQuery !== 'undefined' && jQuery('#loader').length) {
        jQuery('#loader').show();
    }
    
    $.ajax({
        url: window.location.pathname,
        type: 'POST',
        data: fd,
        dataType: 'json',
        async: true,
        cache: false,
        contentType: false,
        processData: false
    })
    .done(function(res) {
        if (res.success && success) {
            success(res);
        } else if (!res.success && error) {
            error(res);
        } else if (!res.success) {
            var msg = limpiarMensajeParaAlert(res.message || 'Error al procesar la solicitud');
            if (typeof jQuery !== 'undefined' && typeof jQuery.alert !== 'undefined') {
                jQuery.alert(msg);
            } else {
                mostrarAlerta('danger', msg);
            }
        }
    })
    .fail(function() {
        if (error) {
            console.log({ success: false, message: 'Error de conexión' });
            // error({ success: false, message: 'Error de conexión' });
        } else {
            var msg = limpiarMensajeParaAlert('Error de conexión');
            if (typeof jQuery !== 'undefined' && typeof jQuery.alert !== 'undefined') {
                jQuery.alert(msg);
            } else {
                mostrarAlerta('danger', msg);
            }
        }
    })
    .always(function() {
        if (typeof jQuery !== 'undefined' && jQuery('#loader').length) {
            jQuery('#loader').fadeOut("slow");
        }
    });
}

function cargarListadosGuardados(callback) {
    ajaxReq({ action: 'cargar_listados' }, function(res) {
        listadosGuardados = res.data || [];
        listadosGuardados.forEach(function(listado) {
                if (!listado.empresasEstados) {
                    listado.empresasEstados = {};
                }
            });
            cargarEstadosEmpresas(function() {
                if (callback) callback();
            });
    }, function() {
        listadosGuardados = [];
        if (callback) callback();
    });
}

function cargarEstadosEmpresas(callback) {
    estadosEmpresasMap = {};
    
    if (empresas.length === 0) {
        if (callback) callback();
        return;
    }
    
    var empCods = empresas.map(function(emp) { return emp.Emp_Cod; });
    
    ajaxReq({
        action: 'verificar_empresas_en_listado',
        emp_cods: empCods
    }, function(res) {
        if (res.data) {
            $.each(res.data, function(empCod, estado) {
                estadosEmpresasMap[empCod] = estado;
            });
        }
        if (callback) callback();
    }, function() {
        if (callback) callback();
    });
}

// Usa el mapa de estados cargado
function estaEnListado(empCod) {
    if (estadosEmpresasMap[empCod]) {
        return estadosEmpresasMap[empCod];
    }
    return false;
}

function verificarEmpresaEnListado(empCod, callback) {
    ajaxReq({
        action: 'verificar_empresa_en_listado',
        emp_cod: empCod
    }, function(res) {
        if (res.data) {
            estadosEmpresasMap[empCod] = res.data;
            if (callback) callback(res.data);
        } else {
            if (callback) callback(false);
        }
    }, function() {
        if (callback) callback(false);
    });
}

let gridInicializado = false;

function inicializarGrid() {
    if (gridInicializado || typeof jQuery === 'undefined' || !jQuery('#tabla-empresas').length) {
        return;
    }
    
    jQuery('#tabla-empresas').createGrid({
        caption: 'Empresas',
        height: 270,
        datatype: 'local',
        data: [],
        colModel: [
            { label: 'Check id="select-all-table" onchange="toggleSelectAll()">', name: 'select', width: 50, align: 'center', formatter: function(cellvalue, options, rowObject) {
                const puedeAperturar = (rowObject && (rowObject.puede_aperturar === true || rowObject.puede_aperturar === 'true'));
                const estadoTexto = (rowObject && rowObject.estado_texto) ? rowObject.estado_texto : '';
                const empCod = (rowObject && rowObject.Emp_Cod) ? rowObject.Emp_Cod : '';
                
                let titleAttr = '';
                if (!puedeAperturar) {
                    if (estadoTexto === 'Aperturado') {
                        titleAttr = 'title="Esta empresa ya está aperturada. No se puede crear otro período, pero puede guardarse en el listado."';
                    } else {
                        titleAttr = 'title="Esta empresa ya tiene el período registrado. Puede cambiar el régimen pero no activar un nuevo período, pero puede guardarse en el listado."';
                    }
                }
                
                return '<input type="checkbox" class="emp-checkbox" value="' + empCod + '" onchange="actualizarSeleccion()" data-puede-aperturar="' + (puedeAperturar ? 'true' : 'false') + '" ' + titleAttr + '>';
            }, title: false, sortable: false },
            { label: 'Id. Cod', name: 'Emp_Cod', width: 50, align: 'center', key: true },
            { label: 'Nombre Empresa', name: 'Emp_Nom', width: 200 },
            { label: 'RUC', name: 'Emp_Ruc', width: 100, align: 'center' },
            { label: 'Correo', name: 'Emp_Cor', width: 150 },
            { label: 'Contador', name: 'Emp_Con', width: 200 },
            { label: 'Régimen', name: 'regimen_texto', align: 'center', width: 150 },
            { label: 'Base Datos', name: 'Dat_Dis', align: 'center', width: 60 },
            { label: 'Estado Período', name: 'estado_texto', width: 100, align: 'center' },
            { label: 'En Listado', name: 'en_listado', width: 80, align: 'center' }
        ],
        loadComplete: function() {
            actualizarSeleccion();
            setTimeout(function() {
                restaurarCheckboxesSeleccionados();
            }, 50);
        }
    }, true, '#tabla-empresasPager', { refresh: true });
    
    function actualizarCaptionGrid() {
        const grid = jQuery('#tabla-empresas');
        let captionHtml = 'Empresas';
        
        // Si hay un listado cargado, mostrar su nombre
        if (listadoEditando && listadoEditando.nombre) {
            captionHtml = 'Listado: ' + listadoEditando.nombre;
        }
        
        if (checkboxDesbloquearVisible) {
            const checkbox = document.getElementById('desbloquear-modificar-grid');
            const estaDesbloqueado = checkbox && checkbox.checked;
            const labelText = estaDesbloqueado ? 'Bloquear Edición' : 'Desbloquear Edicion';
            const checked = estaDesbloqueado ? 'checked' : '';
            const checkboxHtml = '<span style="float: right;"><input type="checkbox" id="desbloquear-modificar-grid" onchange="toggleDesbloquearModificar()" style="cursor: pointer; margin-left: 10px;" ' + checked + '><label for="desbloquear-modificar-grid" id="desbloquear-label-grid" style="margin-left: 5px; cursor: pointer; color:rgb(255, 255, 255); font-weight: bold;">' + labelText + '</label></span>';
            captionHtml = captionHtml + checkboxHtml;
        }
        
        grid.jqGrid('setCaption', captionHtml);
    }
    
    window.actualizarCaptionGrid = actualizarCaptionGrid;
    
    actualizarCaptionGrid();
    
    gridInicializado = true;
}

window.addEventListener('DOMContentLoaded', function() {
    inicializarGrid();
    
    const inputBusqueda = document.getElementById('buscar-empresas');
    if (inputBusqueda) {
        inputBusqueda.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                buscarEmpresasPorTexto();
            }
        });
    }
});

function buscarEmpresas() {
    const periodo = parseInt(document.getElementById('periodo').value);
    if (!periodo || periodo < 2000 || periodo > 2100) {
        mostrarAlerta('warning', 'Debe ingresar un año válido (entre 2000 y 2100)');
        return false;
    }
    
    modoSoloLectura = false;
    checkboxDesbloquearVisible = false;
    
    // Habilitar checkboxes y botones
    document.querySelectorAll('.emp-checkbox').forEach(cb => {
        cb.disabled = false;
    });
    document.querySelectorAll('button[onclick="activarPeriodo()"]').forEach(btn => {
        btn.disabled = false;
        btn.style.opacity = '1';
    });
    document.querySelectorAll('button[onclick="abrirModalCambiarRegimen()"]').forEach(btn => {
        btn.disabled = false;
        btn.style.opacity = '1';
    });
    document.querySelectorAll('button[onclick="guardarListado()"]').forEach(btn => {
        btn.disabled = false;
        btn.style.opacity = '1';
    });
    document.querySelectorAll('#select-all').forEach(cb => {
        cb.disabled = false;
        cb.style.opacity = '1';
    });
    
    if (typeof window.actualizarCaptionGrid === 'function') {
        window.actualizarCaptionGrid();
    }
    
    // Establecer fechas basadas en el año
    document.getElementById('fecha_inicial').value = periodo + '-01-01';
    document.getElementById('fecha_final').value = periodo + '-12-31';
    
    ajaxReq({
        action: 'buscar_empresas',
        fecha_inicial: periodo + '-01-01',
        fecha_final: periodo + '-12-31'
    }, function(res) {
        empresas = res.data;
        estadisticas = res.stats || { total: 0, con_periodo: 0, sin_periodo: 0 };
            actualizarEstadisticas();
            cargarContadores();
            filtrarEmpresas('todos');
            
        var cont = document.getElementById('empresas-container');
        if (cont) cont.style.display = 'block';
        var fs = document.getElementById('fieldset-stats');
        if (fs) fs.style.display = 'block';
        var sc = document.getElementById('stats-container');
        if (sc) sc.style.display = 'block';
        var fb = document.getElementById('filter-buttons');
        if (fb) fb.style.display = 'block';
        
            inicializarGrid();
            
            cargarListadosGuardados(function() {
                cargarEstadosEmpresas(function() {
                renderizarTabla();
                });
            });
            
        var filtroCont = document.getElementById('filtro-contador');
        if (filtroCont && filtroCont.value) {
            var contNom = filtroCont.options[filtroCont.selectedIndex].text;
            mostrarAlerta('info', 'Mostrando empresas del contador: ' + contNom + '. Seleccione las empresas que desea activar.');
        }
    }, function(res) {
        mostrarAlerta('danger', 'Error al cargar empresas: ' + (res.message || ''));
            if (typeof jQuery !== 'undefined' && jQuery('#tabla-empresas').length) {
                jQuery('#tabla-empresas').jqGrid('clearGridData');
            }
    }, function(res) {
        mostrarAlerta('danger', 'Error: ' + (res.message || ''));
        if (typeof jQuery !== 'undefined' && jQuery('#tabla-empresas').length) {
            jQuery('#tabla-empresas').jqGrid('clearGridData');
        }
    });
    
    return false;
}

function restaurarModoNormal() {
    modoSoloLectura = false;
    // Mantener el checkbox visible cuando se desbloquea (para poder bloquear de nuevo)
    checkboxDesbloquearVisible = true;
    
    // Habilitar checkboxes
    document.querySelectorAll('.emp-checkbox').forEach(cb => {
        cb.disabled = false;
    });
    
    // Habilitar botones de acción
    document.querySelectorAll('button[onclick="activarPeriodo()"]').forEach(btn => {
        btn.disabled = false;
        btn.style.opacity = '1';
    });
    document.querySelectorAll('button[onclick="abrirModalCambiarRegimen()"]').forEach(btn => {
        btn.disabled = false;
        btn.style.opacity = '1';
    });
    document.querySelectorAll('button[onclick="guardarListado()"]').forEach(btn => {
        btn.disabled = false;
        btn.style.opacity = '1';
    });
    document.querySelectorAll('#select-all').forEach(cb => {
        cb.disabled = false;
        cb.style.opacity = '1';
    });
    
    if (typeof window.actualizarCaptionGrid === 'function') {
        window.actualizarCaptionGrid();
    }
}

function toggleDesbloquearModificar() {
    // Buscar el checkbox
    const checkbox = document.getElementById('desbloquear-modificar-grid');
    
    if (!checkbox) return;
    
    if (checkbox.checked) {
        // Si se marca, restaurar modo normal (desbloquear)
        restaurarModoNormal();
    } else {
        // Si se desmarca, volver a modo solo lectura (bloquear)
        activarModoSoloLectura();
    }
    
    if (typeof window.actualizarCaptionGrid === 'function') {
        window.actualizarCaptionGrid();
    }
}

function activarModoSoloLectura() {
    modoSoloLectura = true;
    checkboxDesbloquearVisible = true;
    
    // Deshabilitar checkboxes
    document.querySelectorAll('.emp-checkbox').forEach(cb => {
        cb.disabled = true;
    });
    
    // Deshabilitar botones de acción
    document.querySelectorAll('button[onclick="activarPeriodo()"]').forEach(btn => {
        btn.disabled = true;
        btn.style.opacity = '0.6';
    });
    document.querySelectorAll('button[onclick="abrirModalCambiarRegimen()"]').forEach(btn => {
        btn.disabled = true;
        btn.style.opacity = '0.6';
    });
    document.querySelectorAll('button[onclick="guardarListado()"]').forEach(btn => {
        btn.disabled = true;
        btn.style.opacity = '0.6';
    });
    document.querySelectorAll('#select-all').forEach(cb => {
        cb.disabled = true;
        cb.style.opacity = '0.6';
    });
    
    if (typeof window.actualizarCaptionGrid === 'function') {
        window.actualizarCaptionGrid();
    }
}

function actualizarEstadisticas() {
    document.getElementById('stat-total').textContent = estadisticas.total;
    document.getElementById('stat-con-periodo').textContent = estadisticas.con_periodo;
    document.getElementById('stat-sin-periodo').textContent = estadisticas.sin_periodo;
}

function cargarContadores() {
    const contadores = [...new Set(empresas.map(emp => emp.Emp_Con || '').filter(c => c && c !== 'N/A'))].sort();
    
    const select = document.getElementById('filtro-contador');
    select.innerHTML = '<option value="">Todos los Contadores</option>';
    
    // Agregar cada contador como opción
    contadores.forEach(contador => {
        const option = document.createElement('option');
        option.value = contador;
        option.textContent = contador;
        select.appendChild(option);
    });
}

function filtrarPorContador() {
    filtroContadorActual = document.getElementById('filtro-contador').value;
    aplicarFiltros();
}

function filtrarPorRegimen() {
    filtroRegimenActual = document.getElementById('filtro-regimen').value;
    aplicarFiltros();
}

function esVerdadero(valor) {
    if (valor === true || valor === 1 || valor === 'true' || valor === '1') {
        return true;
    }
    if (valor === false || valor === 0 || valor === 'false' || valor === '0' || valor === null || valor === '' || valor === undefined) {
        return false;
    }
    return Boolean(valor);
}

function aplicarFiltros() {
    // Guardar las selecciones actuales antes de filtrar
    const seleccionesGuardadas = getEmpresasSeleccionadas();
    
    let resultado = empresas;
    
    // Aplicar filtro de período
    if (filtroActual === 'con-periodo') {
        resultado = resultado.filter(emp => {
            const tienePeriodo = esVerdadero(emp.tiene_periodo);
            return tienePeriodo;
        });
    } else if (filtroActual === 'sin-periodo') {
        // Sin período: tiene período del año anterior pero NO tiene período del año seleccionado
        resultado = resultado.filter(emp => {
            return esVerdadero(emp.tiene_periodo_anterior) && !esVerdadero(emp.tiene_periodo);
        });
    }
    
    // Aplicar filtro de contador
    if (filtroContadorActual && filtroContadorActual !== '') {
        resultado = resultado.filter(emp => emp.Emp_Con === filtroContadorActual);
    }
    
    // Aplicar filtro de régimen
    if (filtroRegimenActual && filtroRegimenActual !== '') {
        resultado = resultado.filter(emp => emp.Cof_Rim === filtroRegimenActual);
    }
    
    // Aplicar filtro de búsqueda por texto si existe
    if (textoBusqueda && textoBusqueda.trim() !== '') {
        const texto = textoBusqueda.toLowerCase();
        resultado = resultado.filter(emp => {
            const nombre = (emp.Emp_Nom || '').toLowerCase();
            const ruc = (emp.Emp_Ruc || '').toLowerCase();
            const correo = (emp.Emp_Cor || '').toLowerCase();
            const contador = (emp.Emp_Con || '').toLowerCase();
            
            return nombre.includes(texto) || 
                   ruc.includes(texto) || 
                   correo.includes(texto) || 
                   contador.includes(texto);
        });
    }
    
    empresasFiltradas = resultado;
    renderizarTabla();
    
    // Restaurar las selecciones después de renderizar
    setTimeout(function() {
        seleccionesGuardadas.forEach(function(empCod) {
            const checkbox = document.querySelector('.emp-checkbox[value="' + empCod + '"]');
            if (checkbox) {
                checkbox.checked = true;
            }
        });
        actualizarSeleccion();
    }, 100);
}

function filtrarEmpresas(filtro, btnElement) {
    const empresasSeleccionadasAntes = getEmpresasSeleccionadas();
    empresasSeleccionadasAntes.forEach(cod => empresasSeleccionadasPersistentes.add(cod));
    
    filtroActual = filtro;
    
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    if (btnElement) {
        btnElement.classList.add('active');
    } else {
        const btnFiltro = document.querySelector(`.filter-btn[data-filtro="${filtro}"]`);
        if (btnFiltro) {
            btnFiltro.classList.add('active');
        }
    }
    
    aplicarFiltros();
}

function renderizarTabla() {
    if (typeof jQuery === 'undefined' || !jQuery('#tabla-empresas').length) {
        return;
    }
    
    const grid = jQuery('#tabla-empresas');
    
    if (!gridInicializado) {
        inicializarGrid();
    }
    
    if (empresasFiltradas.length === 0) {
        grid.jqGrid('clearGridData');
        return;
    }
    
    // Preparar datos para el grid
    const gridData = empresasFiltradas.map(emp => {
        const tienePeriodo = emp.tiene_periodo || false;
        const tienePeriodoAnterior = emp.tiene_periodo_anterior || false;
        const esPeriodoActual = emp.es_periodo_actual || false;
        
        const estadoListado = estaEnListado(emp.Emp_Cod);
        
        // Determinar el texto del estado del período y si puede ser aperturada
        let estadoTexto = emp.Emp_Est;
        let puedeAperturar = true; // Por defecto puede aperturar
        
        if (estadoListado === 'A') {
            estadoTexto = 'Aperturado';
            puedeAperturar = false; // Ya está aperturada, no permitir aperturar de nuevo
        } else if (tienePeriodo && esPeriodoActual) {
            estadoTexto = 'Periodo Actual';
            puedeAperturar = true; // Puede aperturar el período siguiente
        } else if (tienePeriodo) {
            estadoTexto = 'CON PERÍODO';
            puedeAperturar = false; // Ya tiene período, no permitir
        } else if (tienePeriodoAnterior) {
            estadoTexto = 'PENDIENTE';
            puedeAperturar = true; // Puede aperturar
        }
        
        let enListadoTexto = 'No';
        if (estadoListado) {
            if (estadoListado === 'A') {
                enListadoTexto = 'Aperturado';
            } else {
                enListadoTexto = 'En Listado';
            }
        }
        
        // Mapear código de régimen a texto descriptivo
        let regimenTexto = 'N/A';
        if (emp.Cof_Rim) {
            switch(emp.Cof_Rim) {
                case 'N':
                    regimenTexto = 'Regimen General';
                    break;
                case 'NP':
                    regimenTexto = 'Rimpe Negocio Popular';
                    break;
                case 'EM':
                    regimenTexto = 'Rimpe Emprendedor';
                    break;
                default:
                    regimenTexto = emp.Cof_Rim;
            }
        }
        
        return {
            Emp_Cod: emp.Emp_Cod,
            Emp_Nom: emp.Emp_Nom || '',
            Emp_Ruc: emp.Emp_Ruc || '<NULL>',
            Emp_Cor: emp.Emp_Cor || '',
            Emp_Con: emp.Emp_Con || 'N/A',
            regimen_texto: regimenTexto,
            Dat_Dis: emp.Dat_Dis || 'N/A',
            estado_texto: estadoTexto,
            en_listado: enListadoTexto,
            tiene_periodo: tienePeriodo,
            tiene_periodo_anterior: tienePeriodoAnterior,
            es_periodo_actual: esPeriodoActual,
            puede_aperturar: puedeAperturar,
            Cof_Rim: emp.Cof_Rim,
            estado_listado: estadoListado
        };
    });
    
    const empresasSeleccionadasAntes = getEmpresasSeleccionadas();
    
    grid.jqGrid('clearGridData');
    grid.jqGrid('setGridParam', { data: gridData });
    grid.trigger('reloadGrid');
    
    setTimeout(function() {
        restaurarCheckboxesSeleccionados(empresasSeleccionadasAntes);
    }, 100);
    
    // Aplicar estilos a las filas después de cargar
    setTimeout(function() {
        gridData.forEach(function(row) {
            const rowId = jQuery.jgrid.htmlEncode(String(row.Emp_Cod));
            const rowElement = grid.find('tr#' + rowId);
            if (row.tiene_periodo) {
                rowElement.addClass('tiene-periodo');
            }
            
            // Aplicar estilos a la columna de estado
            const estadoCell = rowElement.find('td[aria-describedby="tabla-empresas_estado_texto"]');
            if (row.estado_texto === 'Aperturado' || row.estado_texto === 'Periodo Actual' || row.tiene_periodo || row.tiene_periodo_anterior) {
                estadoCell.css('color', '#006400').css('font-weight', 'bold');
            }
            
            // Aplicar estilos a la columna de listado
            const listadoCell = rowElement.find('td[aria-describedby="tabla-empresas_en_listado"]');
            if (row.estado_listado === 'A') {
                listadoCell.html('<span style="color: #006400;">Aperturado</span>');
            } else if (row.estado_listado) {
                listadoCell.html('<span>En Listado</span>');
            }
            
            // Aplicar estilos a la columna de régimen
            const regimenCell = rowElement.find('td[aria-describedby="tabla-empresas_regimen_texto"]');
            if (row.Cof_Rim === 'N') {
                regimenCell.addClass('regimen-general');
            } else if (row.Cof_Rim === 'NP') {
                regimenCell.addClass('regimen-popular');
            } else if (row.Cof_Rim === 'EM') {
                regimenCell.addClass('regimen-emprendedor');
            }
        });
        actualizarSeleccion();
    }, 200);
}


function toggleSelectAll() {
    const selectAllOutside = document.getElementById('select-all');
    const selectAllTable = document.getElementById('select-all-table');
    
    // Obtener el checkbox que fue clickeado (puede ser cualquiera de los dos)
    const clickedCheckbox = selectAllOutside || selectAllTable;
    if (!clickedCheckbox) return;
    
    const isChecked = clickedCheckbox.checked;
    
    // Sincronizar ambos checkboxes
    if (selectAllOutside) selectAllOutside.checked = isChecked;
    if (selectAllTable) selectAllTable.checked = isChecked;
    
    const checkboxes = document.querySelectorAll('.emp-checkbox');
    checkboxes.forEach(cb => cb.checked = isChecked);
    actualizarSeleccion();
}

function actualizarSeleccion() {
    const checkboxes = document.querySelectorAll('.emp-checkbox');
    const selectAllOutside = document.getElementById('select-all');
    const selectAllTable = document.getElementById('select-all-table');
    const checked = document.querySelectorAll('.emp-checkbox:checked');
    const todasSeleccionadas = checked.length === checkboxes.length && checkboxes.length > 0;
    if (selectAllOutside) selectAllOutside.checked = todasSeleccionadas;
    if (selectAllTable) selectAllTable.checked = todasSeleccionadas;
    
    // Actualizar el label del checkbox con el contador (seleccionados/total)
    const selectAllLabel = document.getElementById('select-all-label');
    if (selectAllLabel) {
        const seleccionados = checked.length;
        const total = checkboxes.length;
        selectAllLabel.textContent = 'Seleccionar Todas (' + seleccionados + '/' + total + ')';
    }
    
    if (listadoEditando && document.getElementById('modal-modificar-listado').style.display === 'flex') {
        actualizarInfoEmpresasDisponibles();
    }
}

function getEmpresasSeleccionadas() {
    const checkboxes = document.querySelectorAll('.emp-checkbox:checked');
    const seleccionadas = Array.from(checkboxes).map(cb => cb.value).filter(cod => cod);
    if (seleccionadas.length > 0) {
        seleccionadas.forEach(cod => empresasSeleccionadasPersistentes.add(cod));
    }
    return seleccionadas;
}

function getEmpresasSeleccionadasDisponibles() {
    const checkboxes = document.querySelectorAll('.emp-checkbox:checked:not(:disabled)');
    return Array.from(checkboxes).map(cb => cb.value);
}

function abrirModalCambiarRegimen() {
    const empresasSeleccionadas = getEmpresasSeleccionadas();
    
    if (empresasSeleccionadas.length === 0) {
        mostrarAlerta('warning', 'Debe seleccionar al menos una empresa para cambiar el régimen');
        return;
    }
    
    document.getElementById('modal-empresas-seleccionadas').textContent = empresasSeleccionadas.length;
    
    document.getElementById('nuevo_regimen').value = '';
    
    // Mostrar modal
    document.getElementById('modal-cambiar-regimen').style.display = 'flex';
}

function cerrarModalCambiarRegimen() {
    document.getElementById('modal-cambiar-regimen').style.display = 'none';
    document.getElementById('nuevo_regimen').value = '';
}

window.onclick = function(event) {
    const modalRegimen = document.getElementById('modal-cambiar-regimen');
    const modalPendiente = document.getElementById('modal-pendiente-grupal');
    const modalListados = document.getElementById('modal-listados-guardados');
    const modalModificar = document.getElementById('modal-modificar-listado');
    if (event.target === modalRegimen) {
        cerrarModalCambiarRegimen();
    }
    if (event.target === modalPendiente) {
        cerrarModalPendienteGrupal();
    }
    if (event.target === modalListados) {
        cerrarModalListadosGuardados();
    }
    if (event.target === modalModificar) {
        cerrarModalModificarListado();
    }
}

let listadoEditando = null;

function abrirModalModificarListado(listadoId) {
    const idNum = parseInt(listadoId);
    
    if (isNaN(idNum)) {
        if (typeof jQuery !== 'undefined' && typeof jQuery.alert !== 'undefined') {
            jQuery.alert('ID de listado inválido');
        } else {
            mostrarAlerta('danger', 'ID de listado inválido');
        }
        return;
    }
    
    // Función auxiliar para normalizar IDs para comparación
    const normalizeId = (id) => {
        if (typeof id === 'number') return id;
        const parsed = parseInt(id);
        return isNaN(parsed) ? id : parsed;
    };
    
    // Buscar el listado
    let listado = listadosGuardados.find(l => {
        const lId = normalizeId(l.id);
        const searchId = normalizeId(listadoId);
        return lId === searchId || l.id === listadoId || l.id === idNum;
    });
    
    if (!listado) {
        cargarListadosGuardados(function() {
            const listadoRecargado = listadosGuardados.find(l => {
                const lId = normalizeId(l.id);
                const searchId = normalizeId(listadoId);
                return lId === searchId || l.id === listadoId || l.id === idNum;
            });
            if (!listadoRecargado) {
                if (typeof jQuery !== 'undefined' && typeof jQuery.alert !== 'undefined') {
                    jQuery.alert('No se encontró el listado');
                } else {
                    mostrarAlerta('danger', 'No se encontró el listado');
                }
                return;
            }
            ejecutarModificarListado(listadoRecargado.id);
        });
        return;
    }
    
    ejecutarModificarListado(listado.id);
}

function ejecutarModificarListado(listadoId) {
    const listado = listadosGuardados.find(l => {
        const normalizeId = (id) => {
            if (typeof id === 'number') return id;
            const parsed = parseInt(id);
            return isNaN(parsed) ? id : parsed;
        };
        const lId = normalizeId(l.id);
        const searchId = normalizeId(listadoId);
        return lId === searchId || l.id === listadoId;
    });
    
    if (!listado || !listado.periodo) {
        if (typeof jQuery !== 'undefined' && typeof jQuery.alert !== 'undefined') {
            jQuery.alert('No se pudo obtener el período del listado');
        } else {
            mostrarAlerta('danger', 'No se pudo obtener el período del listado');
        }
        return;
    }
    
    cerrarModalListadosGuardados();
    
    // Establecer el período en el input
    document.getElementById('periodo').value = listado.periodo;
    document.getElementById('fecha_inicial').value = listado.fechaInicial || listado.fecha_inicial;
    document.getElementById('fecha_final').value = listado.fechaFinal || listado.fecha_final;
    
    // Establecer el Lis_Cod en el input oculto para modificación
    const lisCodInput = document.getElementById('Lis_Cod');
    if (lisCodInput) {
        lisCodInput.value = listado.id || listado.Lis_Cod || '';
    }
    
    ajaxReq({
        action: 'buscar_empresas',
        fecha_inicial: listado.fechaInicial || listado.fecha_inicial,
        fecha_final: listado.fechaFinal || listado.fecha_final
    }, function(resBuscar) {
        empresas = resBuscar.data;
        estadisticas = resBuscar.stats || { total: 0, con_periodo: 0, sin_periodo: 0 };
        actualizarEstadisticas();
        cargarContadores();
        filtrarEmpresas('todos');
        
        var cont = document.getElementById('empresas-container');
        if (cont) cont.style.display = 'block';
        var fs = document.getElementById('fieldset-stats');
        if (fs) fs.style.display = 'block';
        var sc = document.getElementById('stats-container');
        if (sc) sc.style.display = 'block';
        var fb = document.getElementById('filter-buttons');
        if (fb) fb.style.display = 'block';
        
        inicializarGrid();
        
        ajaxReq({
            action: 'obtener_empresas_listado',
            lis_id: listadoId
        }, function(resListado) {
            if (!resListado.data) {
            throw new Error('Error al cargar empresas del listado');
        }
        
            var empMarcMap = {};
            resListado.data.forEach(function(emp) {
            if (emp.Lis_Mar === 'S') {
                    empMarcMap[emp.Emp_Cod.toString()] = true;
            }
        });
        
        renderizarTabla();
        modoSoloLectura = false;
        
        listadoEditando = listado;
        
        // Actualizar el caption después de establecer listadoEditando
        if (typeof window.actualizarCaptionGrid === 'function') {
            window.actualizarCaptionGrid();
        }
        
        setTimeout(function() {
                document.querySelectorAll('.emp-checkbox').forEach(function(cb) {
                    var empCod = cb.value;
                    cb.checked = empMarcMap[empCod] === true;
                });
            actualizarSeleccion();
        }, 300);
        
        if (typeof jQuery !== 'undefined' && typeof jQuery.alert !== 'undefined') {
            jQuery.alert('Listado cargado para modificación');
        } else {
            mostrarAlerta('info', 'Listado cargado para modificación');
        }
        }, function() {
        if (typeof jQuery !== 'undefined' && typeof jQuery.alert !== 'undefined') {
                jQuery.alert('Error al cargar empresas del listado');
        } else {
                mostrarAlerta('danger', 'Error al cargar empresas del listado');
            }
        });
    }, function(res) {
        if (typeof jQuery !== 'undefined' && typeof jQuery.alert !== 'undefined') {
            jQuery.alert('Error: ' + (res.message || ''));
        } else {
            mostrarAlerta('danger', 'Error: ' + (res.message || ''));
        }
    });
}

function cerrarModalModificarListado() {
    document.getElementById('modal-modificar-listado').style.display = 'none';
    listadoEditando = null;
    
    // Actualizar el caption de vuelta a "Empresas" cuando se cierra el listado
    if (typeof window.actualizarCaptionGrid === 'function') {
        window.actualizarCaptionGrid();
    }
}

function renderizarEmpresasListado(empresasIds) {
    const container = document.getElementById('edit-empresas-listado-container');
    document.getElementById('edit-cantidad-empresas').textContent = empresasIds.length;
    
    if (empresasIds.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: #999; padding: 10px;">No hay empresas en el listado</p>';
        return;
    }
    
    const empresasInfo = empresas.filter(emp => 
        empresasIds.includes(emp.Emp_Cod.toString())
    );
    
    container.innerHTML = empresasInfo.map(emp => {
        // Mapear código de régimen a texto descriptivo
        let regimenTexto = 'N/A';
        if (emp.Cof_Rim) {
            switch(emp.Cof_Rim) {
                case 'N':
                    regimenTexto = 'Regimen General';
                    break;
                case 'NP':
                    regimenTexto = 'Rimpe Negocio Popular';
                    break;
                case 'EM':
                    regimenTexto = 'Rimpe Emprendedor';
                    break;
                default:
                    regimenTexto = emp.Cof_Rim;
            }
        }
        
        return `
            <div class="item-pendiente" style="margin-bottom: 8px;">
                <div class="info-empresa" style="flex: 1;">
                    <strong>${emp.Emp_Nom || 'Sin nombre'} (Código: ${emp.Emp_Cod})</strong>
                    <p style="margin: 3px 0; font-size: 11px;">RUC: ${emp.Emp_Ruc || 'N/A'} | Régimen: ${regimenTexto}</p>
                </div>
                <div class="acciones-empresa">
                    <button class="btn-accion btn-eliminar" onclick="quitarEmpresaDelListado('${emp.Emp_Cod}')" title="Quitar del listado" style="padding: 4px 8px; font-size: 11px;">
                        <span class="glyphicon glyphicon-remove"></span> Quitar
                    </button>
                </div>
            </div>
        `;
    }).join('');
}

function quitarEmpresaDelListado(empCod) {
    if (!listadoEditando) return;
    
    listadoEditando.empresas = listadoEditando.empresas.filter(id => id !== empCod.toString());
    listadoEditando.cantidad = listadoEditando.empresas.length;
    
    if (listadoEditando.empresasEstados) {
        delete listadoEditando.empresasEstados[empCod.toString()];
    }
    
    renderizarEmpresasListado(listadoEditando.empresas);
    actualizarInfoEmpresasDisponibles();
}

function agregarEmpresasSeleccionadasAlListado() {
    if (!listadoEditando) return;
    
    const empresasSeleccionadas = getEmpresasSeleccionadas();
    
    if (empresasSeleccionadas.length === 0) {
        mostrarAlerta('warning', 'Debe seleccionar al menos una empresa para agregar al listado');
        return;
    }
    
    if (!listadoEditando.empresasEstados) {
        listadoEditando.empresasEstados = {};
    }
    
    // Agregar empresas que no estén ya en el listado
    empresasSeleccionadas.forEach(empCod => {
        if (!listadoEditando.empresas.includes(empCod)) {
            listadoEditando.empresas.push(empCod);
            // Inicializar estado como "En Listado" si es nueva
            if (!listadoEditando.empresasEstados[empCod]) {
                listadoEditando.empresasEstados[empCod] = 'En Listado';
            }
        }
    });
    
    listadoEditando.cantidad = listadoEditando.empresas.length;
    
    renderizarEmpresasListado(listadoEditando.empresas);
    actualizarInfoEmpresasDisponibles();
    mostrarAlerta('success', 'Empresas agregadas al listado');
}

function actualizarInfoEmpresasDisponibles() {
    const empresasSeleccionadas = getEmpresasSeleccionadas();
    const info = document.getElementById('edit-empresas-disponibles');
    
    if (empresasSeleccionadas.length === 0) {
        info.textContent = 'Seleccione empresas en la tabla principal para agregarlas al listado';
        info.style.color = '#999';
    } else {
        info.textContent = `${empresasSeleccionadas.length} empresa(s) seleccionada(s) en la tabla principal`;
        info.style.color = '#5B6F88';
    }
}

function guardarCambiosListado() {
    if (!listadoEditando) return;
    
    const nuevoNombre = document.getElementById('edit-nombre-listado').value.trim();
    const nuevoPeriodo = document.getElementById('edit-periodo-listado').value;
    const nuevaFechaInicial = document.getElementById('edit-fecha-inicial-listado').value;
    const nuevaFechaFinal = document.getElementById('edit-fecha-final-listado').value;
    
    if (!nuevoNombre) {
        mostrarAlerta('warning', 'Debe ingresar un nombre para el listado');
        return;
    }
    
    if (!nuevoPeriodo || !nuevaFechaInicial || !nuevaFechaFinal) {
        mostrarAlerta('warning', 'Debe completar todos los campos del período');
        return;
    }
    
    if (!listadoEditando.empresas || listadoEditando.empresas.length === 0) {
        mostrarAlerta('warning', 'El listado debe contener al menos una empresa');
        return;
    }
    
    const empresasActuales = listadoEditando.empresas || [];
    const empresasSeleccionadas = getEmpresasSeleccionadas();
    
    // Empresas a agregar (están seleccionadas pero no en el listado)
    const empCodsAgregar = empresasSeleccionadas.filter(empCod => !empresasActuales.includes(empCod));
    
    const empCodsEliminar = empresasActuales.filter(empCod => !empresasSeleccionadas.includes(empCod));
    
    // Mostrar indicador de carga
    if (typeof jQuery !== 'undefined' && jQuery('#loader').length) {
        jQuery('#loader').show();
    }
    
    ajaxReq({
        action: 'actualizar_listado',
        lis_id: listadoEditando.id,
        nombre_listado: nuevoNombre,
        periodo: nuevoPeriodo,
        fecha_inicial: nuevaFechaInicial,
        fecha_final: nuevaFechaFinal,
        emp_cods_agregar: empCodsAgregar,
        emp_cods_eliminar: empCodsEliminar
    }, function(res) {
        mostrarAlerta('success', res.message);
            cerrarModalModificarListado();
            cargarListadosGuardados(function() {
                cargarEstadosEmpresas(function() {
                renderizarTabla();
                });
            });
        var modal = document.getElementById('modal-listados-guardados');
        if (modal && modal.style.display === 'flex') {
                abrirModalListadosGuardados();
            }
    }, function(res) {
        mostrarAlerta('danger', 'Error al actualizar listado: ' + (res.message || ''));
    });
}


function abrirModalPendienteGrupal() {
    const empresasSeleccionadas = getEmpresasSeleccionadas();
    
    if (empresasSeleccionadas.length === 0) {
        mostrarAlerta('warning', 'Debe seleccionar al menos una empresa para ver el pendiente grupal');
        return;
    }
    
 seleccionadas
    const empresasInfo = empresas.filter(emp => 
        empresasSeleccionadas.includes(emp.Emp_Cod.toString())
    );
    
    // Renderizar lista
    const container = document.getElementById('lista-pendientes-container');
    
    if (empresasInfo.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: #5B6F88; padding: 20px;">No se encontró información de las empresas seleccionadas</p>';
    } else {
        container.innerHTML = '<div class="lista-pendientes">' + empresasInfo.map(emp => {
            const tienePeriodo = emp.tiene_periodo || false;
            const tienePeriodoAnterior = emp.tiene_periodo_anterior || false;
            
            // Mapear código de régimen a texto descriptivo
            let regimenTexto = 'N/A';
            if (emp.Cof_Rim) {
                switch(emp.Cof_Rim) {
                    case 'N':
                        regimenTexto = 'Regimen General';
                        break;
                    case 'NP':
                        regimenTexto = 'Rimpe Negocio Popular';
                        break;
                    case 'EM':
                        regimenTexto = 'Rimpe Emprendedor';
                        break;
                    default:
                        regimenTexto = emp.Cof_Rim;
                }
            }
            
            let estadoTexto = tienePeriodo ? 'CON PERÍODO' : (tienePeriodoAnterior ? 'PENDIENTE' : 'SIN PERÍODO');
            let estadoColor = tienePeriodo ? '#006400' : '#8B0000';
            
            return `
                <div class="item-pendiente">
                    <div class="info-empresa">
                        <strong>${emp.Emp_Nom || 'Sin nombre'} (Código: ${emp.Emp_Cod})</strong>
                        <p>RUC: ${emp.Emp_Ruc || 'N/A'} | Contador: ${emp.Emp_Con || 'N/A'}</p>
                        <p>Régimen: ${regimenTexto} | Base: ${emp.Dat_Dis || 'N/A'}</p>
                        <p style="color: ${estadoColor}; font-weight: bold;">Estado: ${estadoTexto}</p>
                    </div>
                    <div class="acciones-empresa">
                        <button class="btn-accion btn-eliminar" onclick="eliminarDePendiente('${emp.Emp_Cod}')" title="Eliminar de la lista">
                            <span class="glyphicon glyphicon-trash"></span> Eliminar
                        </button>
                        <button class="btn-accion btn-modificar" onclick="modificarEmpresa('${emp.Emp_Cod}')" title="Modificar empresa">
                            <span class="glyphicon glyphicon-edit"></span> Modificar
                        </button>
                        <button class="btn-accion btn-activar" 
                                onclick="activarPeriodoIndividual('${emp.Emp_Cod}')" 
                                ${tienePeriodo ? 'disabled title="Esta empresa ya tiene período activo"' : 'title="Activar período contable"'}
                                style="${tienePeriodo ? 'opacity: 0.6;' : ''}">
                            <span class="glyphicon glyphicon-ok"></span> Activar Período
                        </button>
                    </div>
                </div>
            `;
        }).join('') + '</div>';
    }
    
    // Mostrar modal
    document.getElementById('modal-pendiente-grupal').style.display = 'flex';
}

function cerrarModalPendienteGrupal() {
    document.getElementById('modal-pendiente-grupal').style.display = 'none';
}

function eliminarDePendiente(empCod) {
    // Desmarcar el checkbox de la empresa
    const checkbox = document.querySelector(`.emp-checkbox[value="${empCod}"]`);
    if (checkbox) {
        checkbox.checked = false;
        actualizarSeleccion();
    }
    
    // Remover el elemento de la lista
    const container = document.getElementById('lista-pendientes-container');
    const items = container.querySelectorAll('.item-pendiente');
    items.forEach(item => {
        const btnEliminar = item.querySelector(`.btn-eliminar[onclick*="${empCod}"]`);
        if (btnEliminar) {
            item.remove();
        }
    });
    
    // Verificar si quedan empresas en la lista
    const itemsRestantes = container.querySelectorAll('.item-pendiente');
    if (itemsRestantes.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: #5B6F88; padding: 20px;">No hay empresas en la lista</p>';
    }
    
    mostrarAlerta('success', 'Empresa eliminada de la lista');
}

function modificarEmpresa(empCod) {
    // Buscar la empresa en el array
    const empresa = empresas.find(emp => emp.Emp_Cod.toString() === empCod);
    
    if (!empresa) {
        mostrarAlerta('danger', 'No se encontró información de la empresa');
        return;
    }
    
    // Abrir modal de cambiar régimen con esta empresa pre-seleccionada
    // Primero asegurarse de que solo esta empresa esté seleccionada
    document.querySelectorAll('.emp-checkbox').forEach(cb => {
        cb.checked = (cb.value === empCod);
    });
    actualizarSeleccion();
    
    // Cerrar modal de pendiente y abrir modal de cambiar régimen
    cerrarModalPendienteGrupal();
    abrirModalCambiarRegimen();
    
    mostrarAlerta('info', 'Puede modificar el régimen de la empresa seleccionada');
}

function activarPeriodoIndividual(empCod) {
    // Prevenir múltiples clics
    if (activandoPeriodo) {
        return;
    }
    
    const periodo = parseInt(document.getElementById('periodo').value);
    if (!periodo || periodo < 2000 || periodo > 2100) {
        if (typeof jQuery !== 'undefined' && typeof jQuery.alert !== 'undefined') {
            jQuery.alert('Debe seleccionar un período válido');
        } else {
            mostrarAlerta('warning', 'Debe seleccionar un período válido');
        }
        return;
    }
    
    const fechaInicial = periodo + '-01-01';
    const fechaFinal = periodo + '-12-31';
    
    // Buscar la empresa
    const empresa = empresas.find(emp => emp.Emp_Cod.toString() === empCod);
    
    if (!empresa) {
        if (typeof jQuery !== 'undefined' && typeof jQuery.alert !== 'undefined') {
            jQuery.alert('No se encontró información de la empresa');
        } else {
            mostrarAlerta('danger', 'No se encontró información de la empresa');
        }
        return;
    }
    
    // Verificar si puede ser aperturada
    const estadoListado = estaEnListado(empCod);
    if (estadoListado === 'A') {
        if (typeof jQuery !== 'undefined' && typeof jQuery.alert !== 'undefined') {
            jQuery.alert('Esta empresa ya está aperturada. No se puede crear otro período.');
        } else {
            mostrarAlerta('warning', 'Esta empresa ya está aperturada. No se puede crear otro período.');
        }
        return;
    }
    
    // Validar que si tiene período y es período actual (mismo periodo del input), no puede aperturar
    if (empresa.tiene_periodo && empresa.es_periodo_actual) {
        if (typeof jQuery !== 'undefined' && typeof jQuery.alert !== 'undefined') {
            jQuery.alert(`No se puede aperturar el período ${periodo} porque esta empresa ya tiene ese período vigente.`);
        } else {
            mostrarAlerta('warning', `No se puede aperturar el período ${periodo} porque esta empresa ya tiene ese período vigente.`);
        }
        return;
    }
    
    // Si tiene período pero no es período actual, no puede aperturar
    if (empresa.tiene_periodo && !empresa.es_periodo_actual) {
        if (typeof jQuery !== 'undefined' && typeof jQuery.alert !== 'undefined') {
            jQuery.alert('Esta empresa ya tiene período activo');
        } else {
            mostrarAlerta('warning', 'Esta empresa ya tiene período activo');
        }
        return;
    }
    
    if (typeof jQuery !== 'undefined' && typeof jQuery.createDialogConfirm !== 'undefined') {
        jQuery.createDialogConfirm(
            `¿Desea activar el período <span style="color: red; font-weight: bold;">${periodo}</span> para la empresa <span style="color: black; font-weight: bold;">${empresa.Emp_Nom || empCod}</span>?`,
            empCod,
            function(data) {
                ejecutarActivacionIndividual(data, periodo, fechaInicial, fechaFinal);
            }
        );
    } else {
        if (confirm(`¿Desea activar el período ${periodo} (${fechaInicial} al ${fechaFinal}) para la empresa ${empresa.Emp_Nom || empCod}?`)) {
            ejecutarActivacionIndividual(empCod, periodo, fechaInicial, fechaFinal);
        }
    }
}

function ejecutarActivacionIndividual(empCod, periodo, fechaInicial, fechaFinal) {
    activandoPeriodo = true;
    
    ajaxReq({
        action: 'activar_periodo',
        fecha_inicial: fechaInicial,
        fecha_final: fechaFinal,
        emp_cods: [empCod]
    }, function(res) {
        activandoPeriodo = false;
        var msg = limpiarMensajeParaAlert(res.message || 'Acción realizada con éxito');
            if (typeof jQuery !== 'undefined' && typeof jQuery.alert !== 'undefined') {
            jQuery.alert(msg);
            } else {
            mostrarAlerta('success', msg);
            }
            cerrarModalPendienteGrupal();
        setTimeout(function() {
            buscarEmpresas();
        }, 500);
    }, function(res) {
        activandoPeriodo = false;
        var msgErr = limpiarMensajeParaAlert(res.message || 'Error al procesar la solicitud');
            if (typeof jQuery !== 'undefined' && typeof jQuery.alert !== 'undefined') {
            jQuery.alert(msgErr);
            } else {
            mostrarAlerta('danger', msgErr);
        }
    });
}

// Función helper para crear un prompt personalizado usando jQuery UI dialog
function mostrarPromptPersonalizado(mensaje, valorPorDefecto, callback) {
    if (typeof jQuery === 'undefined') {
        // Fallback a prompt nativo si jQuery no está disponible
        const resultado = prompt(mensaje, valorPorDefecto);
        if (callback) callback(resultado);
        return;
    }
    
    // Crear un ID único para el diálogo
    const dialogId = 'dialog-prompt-' + Date.now();
    
    // Escapar HTML para evitar problemas con caracteres especiales
    const mensajeEscapado = jQuery('<div>').text(mensaje).html();
    const valorPorDefectoEscapado = jQuery('<div>').text(valorPorDefecto || '').html();
    
    // Crear el HTML del diálogo con el campo de entrada
    const dialogHtml = '<div id="' + dialogId + '" title="Guardar el Listado de Empresas">' +
        '<div style="font-size:14px; margin-bottom: 15px;">' + mensajeEscapado + '</div>' +
        '<input type="text" id="' + dialogId + '-input" value="' + valorPorDefectoEscapado + '" ' +
        'style="width: 100%; padding: 8px; border: 1px solid #4A9EFF; border-radius: 3px; font-size: 14px;" />' +
        '</div>';
    
    // Crear el diálogo
    const $dialog = jQuery(dialogHtml).appendTo('body');
    
    // Configurar el diálogo
    $dialog.dialog({
        dialogClass: 'dialog-alert-test',
        closeText: "Cerrar Mensaje",
        modal: true,
        autoOpen: true,
        resizable: false,
        width: 500,
        position: { my: "center", at: "center", of: jQuery('body') },
        buttons: [
            {
                text: "Aceptar",
                click: function() {
                    const valor = jQuery('#' + dialogId + '-input').val();
                    jQuery(this).dialog("close");
                    if (callback) callback(valor);
                },
                icons: { primary: "ui-icon-check" }
            },
            {
                text: "Cancelar",
                click: function() {
                    jQuery(this).dialog("close");
                    if (callback) callback(null);
                },
                icons: { primary: "ui-icon-closethick" }
            }
        ],
        close: function() {
            jQuery('.ui-widget-overlay').unbind('click');
            jQuery(this).remove();
        },
        show: { effect: "fade", duration: 500 },
        open: function() {
            // Seleccionar el texto del input al abrir
            const $input = jQuery('#' + dialogId + '-input');
            $input.focus().select();
            
            // Permitir Enter para aceptar
            $input.on('keypress', function(e) {
                if (e.which === 13) { // Enter
                    e.preventDefault();
                    $dialog.dialog('option', 'buttons')[0].click();
                }
            });
            
            // Cerrar al hacer clic en el overlay
            var dg = jQuery(this);
            jQuery(dg.parent()[0].nextSibling).bind('click', function() {
                dg.dialog('close');
                if (callback) callback(null);
            });
        }
    });
    
    // Agregar icono al título
    jQuery("#" + dialogId + ".dialog-alert-test").parent().children(".ui-dialog-titlebar").prepend(
        '<span class="ui-icon ui-icon-info" style="float:left; margin:2px 8px 0 0;"></span>'
    );
}

function guardarListado() {
    // Prevenir múltiples clics
    if (guardandoListado) {
        return;
    }
    
    const empresasSeleccionadas = getEmpresasSeleccionadas();
    
    if (empresasSeleccionadas.length === 0) {
        mostrarAlerta('warning', 'Debe seleccionar al menos una empresa para guardar en el listado');
        return;
    }
    
    const fechaInicial = document.getElementById('fecha_inicial').value;
    const fechaFinal = document.getElementById('fecha_final').value;
    const periodo = document.getElementById('periodo').value;
    
    if (!fechaInicial || !fechaFinal || !periodo) {
        mostrarAlerta('warning', 'Debe seleccionar un período primero');
        return;
    }
    
    // Verificar si hay un Lis_Cod en el input oculto (indica modificación)
    const lisCodInput = document.getElementById('Lis_Cod');
    const lisCodOculto = lisCodInput ? lisCodInput.value.trim() : '';
    
    // Si hay Lis_Cod oculto, es una modificación
    if (lisCodOculto) {
        // Si listadoEditando no está establecido, establecerlo con el ID del input oculto
        if (!listadoEditando) {
            listadoEditando = { id: parseInt(lisCodOculto) };
        }
        guardarListadoEditado();
        return;
    }
    
    // Si estamos editando un listado existente (usando la variable global)
    if (listadoEditando) {
        guardarListadoEditado();
        return;
    }
    
    // Es un nuevo listado, pedir nombre usando el modal personalizado
    const valorPorDefecto = `Listado ${new Date().toLocaleDateString()}`;
    mostrarPromptPersonalizado('Ingrese un nombre para este listado:', valorPorDefecto, function(nombreListado) {
    if (!nombreListado || nombreListado.trim() === '') {
        return;
    }
    
        // Continuar con el guardado del listado
        continuarGuardadoListado(nombreListado.trim());
    });
}

function continuarGuardadoListado(nombreListado) {
    // Obtener valores del DOM
    const fechaInicial = document.getElementById('fecha_inicial').value;
    const fechaFinal = document.getElementById('fecha_final').value;
    const periodo = document.getElementById('periodo').value;
    const empresasSeleccionadas = getEmpresasSeleccionadas();
    
    guardandoListado = true;
    
    // Mostrar indicador de carga
    if (typeof jQuery !== 'undefined' && jQuery('#loader').length) {
        jQuery('#loader').show();
    }
    
    const todasLasEmpresas = empresas.map(emp => emp.Emp_Cod.toString());
    const empresasMarcadas = empresasSeleccionadas;
    
    if (empresasMarcadas.length === 0) {
        mostrarAlerta('warning', 'Debe seleccionar al menos una empresa para guardar el listado');
        guardandoListado = false;
        if (typeof jQuery !== 'undefined' && jQuery('#loader').length) {
            jQuery('#loader').hide();
        }
        return;
    }
    
    ajaxReq({
        action: 'guardar_listado',
        nombre_listado: nombreListado,
        periodo: periodo,
        fecha_inicial: fechaInicial,
        fecha_final: fechaFinal,
        emp_cods: JSON.stringify(todasLasEmpresas),
        emp_cods_marcadas: JSON.stringify(empresasMarcadas)
    }, function(res) {
        mostrarAlerta('success', res.message);
        guardandoListado = false;
        var lisCodInput = document.getElementById('Lis_Cod');
        if (lisCodInput) lisCodInput.value = '';
            cargarListadosGuardados(function() {
                cargarEstadosEmpresas(function() {
                renderizarTabla();
                });
            });
    }, function(res) {
        mostrarAlerta('danger', 'Error al guardar listado: ' + (res.message || ''));
        guardandoListado = false;
        var lisCodInput = document.getElementById('Lis_Cod');
        if (lisCodInput) lisCodInput.value = '';
    });
}

function guardarListadoEditado() {
    // Prevenir múltiples clics
    if (guardandoListado) {
        return;
    }
    
    if (!listadoEditando || !listadoEditando.id) {
        mostrarAlerta('danger', 'No hay listado en edición');
        return;
    }
    
    const periodo = parseInt(document.getElementById('periodo').value);
    const fechaInicial = document.getElementById('fecha_inicial').value;
    const fechaFinal = document.getElementById('fecha_final').value;
    
    const empresasSeleccionadas = getEmpresasSeleccionadas();
    const empresasSeleccionadasInt = empresasSeleccionadas.map(cod => parseInt(cod));
    
    guardandoListado = true;
    
    // Mostrar loader
    if (typeof jQuery !== 'undefined' && jQuery('#loader').length) {
        jQuery('#loader').show();
    }
    
    ajaxReq({
        action: 'obtener_empresas_listado',
        lis_id: listadoEditando.id
    }, function(dataListado) {
        if (!dataListado.success || !dataListado.data) {
            guardandoListado = false;
            var lisCodInput = document.getElementById('Lis_Cod');
            if (lisCodInput) lisCodInput.value = '';
            if (typeof jQuery !== 'undefined' && typeof jQuery.alert !== 'undefined') {
                jQuery.alert('Error al obtener empresas del listado original');
            } else {
                mostrarAlerta('danger', 'Error al obtener empresas del listado original');
            }
            return;
        }
        
        const empresasListadoOriginal = dataListado.data.map(emp => parseInt(emp.Emp_Cod));
        
        // Solo incluir empresas que realmente pueden aperturar (no tienen el período activo para el año seleccionado)
        const empresasParaAperturar = empresas.filter(emp => {
            const empCod = parseInt(emp.Emp_Cod);
            
            // Verificar que esté en el listado original y esté marcada
            if (!empresasListadoOriginal.includes(empCod) || !empresasSeleccionadasInt.includes(empCod)) {
                return false;
            }
            
            // Verificar si puede aperturar (no tiene período activo para el año seleccionado)
            const puedeAperturar = emp.puede_aperturar === true || emp.puede_aperturar === 'true';
            
            // Verificación adicional: no debe tener período activo para el año seleccionado
            // Si tiene período y NO es período actual, no puede aperturar
            if (emp.tiene_periodo && !emp.es_periodo_actual) {
                return false;
            }
            
            // Si el estado es "Aperturado", ya fue aperturado, no puede aperturar de nuevo
            if (emp.estado_texto === 'Aperturado') {
                return false;
            }
            
            return puedeAperturar;
        }).map(emp => parseInt(emp.Emp_Cod));
        
        function continuarActualizacion() {
            var empCodsMarcadas = empresasSeleccionadasInt;
            
            if (!empCodsMarcadas || empCodsMarcadas.length === 0) {
                guardandoListado = false;
                var lisCodInput = document.getElementById('Lis_Cod');
                if (lisCodInput) lisCodInput.value = '';
                if (typeof jQuery !== 'undefined' && typeof jQuery.alert !== 'undefined') {
                    jQuery.alert('No hay empresas seleccionadas para guardar en el listado');
                    } else {
                    mostrarAlerta('danger', 'No hay empresas seleccionadas para guardar en el listado');
                }
                return;
            }
            
            var listadoInfo = listadosGuardados.find(function(l) {
                var normalizeId = function(id) {
                    if (typeof id === 'number') return id;
                    var parsed = parseInt(id);
                    return isNaN(parsed) ? id : parsed;
                };
                var lId = normalizeId(l.id);
                var searchId = normalizeId(listadoEditando.id);
                return lId === searchId || l.id === listadoEditando.id;
            });
            
            ajaxReq({
                action: 'actualizar_listado_det',
                lis_id: listadoEditando.id,
                emp_cods_marcadas: empCodsMarcadas,
                nombre_listado: listadoInfo ? (listadoInfo.nombre || '') : 'Listado Actualizado',
                periodo: listadoInfo ? (listadoInfo.periodo || periodo) : periodo,
                fecha_inicial: listadoInfo ? (listadoInfo.fechaInicial || listadoInfo.fecha_inicial || fechaInicial) : fechaInicial,
                fecha_final: listadoInfo ? (listadoInfo.fechaFinal || listadoInfo.fecha_final || fechaFinal) : fechaFinal
            }, function(data) {
        if (data.success) {
            if (typeof jQuery !== 'undefined' && typeof jQuery.alert !== 'undefined') {
                jQuery.alert('Acción realizada con éxito');
            } else {
                mostrarAlerta('success', 'Listado actualizado correctamente');
            }
            listadoEditando = null;
            // Actualizar el caption de vuelta a "Empresas"
            if (typeof window.actualizarCaptionGrid === 'function') {
                window.actualizarCaptionGrid();
            }
            buscarEmpresas();
                }
            }, function(res) {
                guardandoListado = false;
                var lisCodInput = document.getElementById('Lis_Cod');
                if (lisCodInput) lisCodInput.value = '';
        if (typeof jQuery !== 'undefined' && typeof jQuery.alert !== 'undefined') {
                    jQuery.alert('Error: ' + (res.message || 'Error al actualizar listado'));
        } else {
                    mostrarAlerta('danger', 'Error: ' + (res.message || 'Error al actualizar listado'));
                }
            });
        }
            
            if (empresasParaAperturar.length > 0) {
                var dataAct = {
                    action: 'activar_periodo',
                    fecha_inicial: fechaInicial,
                    fecha_final: fechaFinal,
                    emp_cods: empresasParaAperturar
                };
                if (listadoEditando.id) {
                    dataAct.lis_id = listadoEditando.id;
                }
                ajaxReq(dataAct, function(res) {
                    if (res.success || (res.message && res.message.includes('No se insertaron'))) {
                        continuarActualizacion();
                    } else {
            guardandoListado = false;
                        var lisCodInput = document.getElementById('Lis_Cod');
                        if (lisCodInput) lisCodInput.value = '';
                        if (typeof jQuery !== 'undefined' && typeof jQuery.alert !== 'undefined') {
                            jQuery.alert('Error: ' + (res.message || 'Error al activar periodos'));
                        } else {
                            mostrarAlerta('danger', 'Error: ' + (res.message || 'Error al activar periodos'));
                        }
                    }
                }, function() {
                    continuarActualizacion();
                });
            } else {
                continuarActualizacion();
            }
        }, function(res) {
            guardandoListado = false;
            var lisCodInput = document.getElementById('Lis_Cod');
            if (lisCodInput) lisCodInput.value = '';
            if (typeof jQuery !== 'undefined' && typeof jQuery.alert !== 'undefined') {
                jQuery.alert('Error: ' + (res.message || 'Error al obtener empresas del listado'));
            } else {
                mostrarAlerta('danger', 'Error: ' + (res.message || 'Error al obtener empresas del listado'));
            }
        });
}

function abrirModalListadosGuardados() {
    const container = document.getElementById('listados-container');
    container.innerHTML = '<p style="text-align: center; color: #5B6F88; padding: 20px;">Cargando listados...</p>';
    document.getElementById('modal-listados-guardados').style.display = 'flex';
    
    cargarListadosGuardados(function() {
        if (listadosGuardados.length === 0) {
            container.innerHTML = '<p style="text-align: center; color: #5B6F88; padding: 20px;">No hay listados guardados</p>';
        } else {
            container.innerHTML = '<div class="lista-pendientes">' + listadosGuardados.map(listado => {
                const fechaCreacion = new Date(listado.fechaCreacion).toLocaleString('es-ES');
                const listadoId = parseInt(listado.id) || listado.id;
                return `
                    <div class="listado-item">
                        <div class="listado-item-header">
                            <div class="listado-item-info">
                                <strong>${listado.nombre}</strong>
                                <p>Período: ${listado.periodo} | Fechas: ${listado.fechaInicial} al ${listado.fechaFinal}</p>
                                <p>Creado: ${fechaCreacion} | Empresas: ${listado.cantidad}</p>
                            </div>
                            <div class="listado-item-acciones">
                                <button class="btn-accion btn-activar btn-success" onclick="activarPeriodoDesdeListado(${listadoId})" title="Activar períodos en lote">
                                    <span class="glyphicon glyphicon-ok"></span> Activar en Lote
                                </button>
                                <button class="btn-accion btn-modificar btn-primary" onclick="abrirModalModificarListado(${listadoId})" title="Modificar listado">
                                    <span class="glyphicon glyphicon-edit"></span> Modificar
                                </button>
                                <button class="btn-accion btn-modificar btn-info" onclick="verListado(${listadoId})" title="Ver empresas del listado" style="background-color: #17a2b8;">
                                    <span class="glyphicon glyphicon-eye-open"></span> Ver
                                </button>
                                <button class="btn-accion btn-eliminar btn-danger" onclick="eliminarListado(${listadoId})" title="Eliminar listado">
                                    <span class="glyphicon glyphicon-trash"></span> Eliminar
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            }).join('') + '</div>';
        }
    });
}

function cerrarModalListadosGuardados() {
    document.getElementById('modal-listados-guardados').style.display = 'none';
}

function activarPeriodoDesdeListado(listadoId) {
    // Prevenir múltiples clics
    if (activandoPeriodo) {
        return;
    }
    
    // Función auxiliar para normalizar IDs para comparación
    const normalizeId = (id) => {
        if (typeof id === 'number') return id;
        const parsed = parseInt(id);
        return isNaN(parsed) ? id : parsed;
    };
    
    const idNum = parseInt(listadoId);
    const searchId = normalizeId(listadoId);
    
    // Buscar el listado con normalización de IDs
    let listado = listadosGuardados.find(l => {
        const lId = normalizeId(l.id);
        return lId === searchId || l.id === listadoId || l.id === idNum;
    });
    
    if (!listado) {
        // Si no se encuentra, recargar listados y volver a intentar
        cargarListadosGuardados(function() {
            const listadoRecargado = listadosGuardados.find(l => {
                const lId = normalizeId(l.id);
                return lId === searchId || l.id === listadoId || l.id === idNum;
            });
            if (!listadoRecargado) {
                if (typeof jQuery !== 'undefined' && typeof jQuery.alert !== 'undefined') {
                    jQuery.alert('No se encontró el listado');
                } else {
                    mostrarAlerta('danger', 'No se encontró el listado');
                }
                return;
            }
            // Continuar con el listado recargado
            ejecutarActivacionLoteDirecto(listadoRecargado.id, listadoRecargado);
        });
        return;
    }
    
    // Continuar con la ejecución normal
    ejecutarActivacionLoteDirecto(listado.id, listado);
}

function ejecutarActivacionLoteDirecto(listadoId, listado) {
    const periodo = parseInt(document.getElementById('periodo').value);
    if (!periodo || periodo < 2000 || periodo > 2100) {
        if (typeof jQuery !== 'undefined' && typeof jQuery.alert !== 'undefined') {
            jQuery.alert('Debe seleccionar un período válido');
        } else {
            mostrarAlerta('warning', 'Debe seleccionar un período válido');
        }
        return;
    }
    const fechaInicial = periodo + '-01-01';
    const fechaFinal = periodo + '-12-31';
    
    if (typeof jQuery !== 'undefined' && typeof jQuery.createDialogConfirm !== 'undefined') {
        jQuery.createDialogConfirm(
            `¿Desea activar el período <span style="color: red; font-weight: bold;">${periodo}</span> para <span style="color: black; font-weight: bold;">${listado.cantidad}</span> empresa(s)?`,
            listadoId,
            function(data) {
                ejecutarActivacionLote(data, periodo, fechaInicial, fechaFinal);
            }
        );
    } else {
        if (confirm(`¿Desea activar el período ${periodo} (${fechaInicial} al ${fechaFinal}) para ${listado.cantidad} empresa(s) del listado "${listado.nombre}"?`)) {
            ejecutarActivacionLote(listadoId, periodo, fechaInicial, fechaFinal);
        }
    }
}

function ejecutarActivacionLote(listadoId, periodo, fechaInicial, fechaFinal) {
    
    activandoPeriodo = true;
    
    // Mostrar indicador de carga
    if (typeof jQuery !== 'undefined' && jQuery('#loader').length) {
        jQuery('#loader').show();
    }
    
    ajaxReq({
        action: 'obtener_empresas_listado',
        lis_id: listadoId
    }, function(dataEmp) {
        if (!dataEmp.success || !dataEmp.data) {
            if (typeof jQuery !== 'undefined' && typeof jQuery.alert !== 'undefined') {
                jQuery.alert('Error al cargar empresas del listado');
            } else {
                mostrarAlerta('danger', 'Error al cargar empresas del listado');
            }
            activandoPeriodo = false;
            if (typeof jQuery !== 'undefined' && jQuery('#loader').length) {
                jQuery('#loader').fadeOut("slow");
            }
            return;
        }
        
        // Filtrar solo las empresas marcadas (Lis_Mar = 'S') del listado
        const empresasMarcadas = dataEmp.data.filter(emp => emp.Lis_Mar === 'S');
        
        if (empresasMarcadas.length === 0) {
            if (typeof jQuery !== 'undefined' && typeof jQuery.alert !== 'undefined') {
                jQuery.alert('No hay empresas marcadas en este listado para aperturar');
            } else {
                mostrarAlerta('warning', 'No hay empresas marcadas en este listado para aperturar');
            }
            activandoPeriodo = false;
            if (typeof jQuery !== 'undefined' && jQuery('#loader').length) {
                jQuery('#loader').fadeOut("slow");
            }
            return;
        }
        
        const empCodsMarcadas = empresasMarcadas.map(emp => emp.Emp_Cod.toString());
        
        // Filtrar las empresas marcadas que están disponibles para aperturar
        const empresasDisponibles = empresas.filter(emp => {
            // Solo procesar empresas marcadas en el listado
            if (!empCodsMarcadas.includes(emp.Emp_Cod.toString())) {
                return false;
            }
            
            const estadoListado = estaEnListado(emp.Emp_Cod);
            // Si está aperturado, no puede aperturar
            if (estadoListado === 'A') {
                return false;
            }
            // Validar que si tiene período y es período actual (mismo periodo del input), no puede aperturar
            if (emp.tiene_periodo && emp.es_periodo_actual) {
                return false;
            }
            if (!emp.tiene_periodo) {
                return true;
            }
            // En cualquier otro caso, no puede aperturar
            return false;
        });
        
        if (empresasDisponibles.length === 0) {
            if (typeof jQuery !== 'undefined' && typeof jQuery.alert !== 'undefined') {
                jQuery.alert('Las empresas marcadas no pueden ser aperturadas. Verifique que no estén en estado "Aperturado" o que no tengan el mismo período vigente.');
            } else {
                mostrarAlerta('warning', 'Las empresas marcadas no pueden ser aperturadas. Verifique que no estén en estado "Aperturado" o que no tengan el mismo período vigente.');
            }
            activandoPeriodo = false;
            if (typeof jQuery !== 'undefined' && jQuery('#loader').length) {
                jQuery('#loader').fadeOut("slow");
            }
            return;
        }
        
        var empCodsDisponibles = empresasDisponibles.map(function(emp) { return emp.Emp_Cod.toString(); });
        
        ajaxReq({
            action: 'activar_periodo',
            fecha_inicial: fechaInicial,
            fecha_final: fechaFinal,
            emp_cods: empCodsDisponibles,
            lis_id: listadoId
        }, function(data) {
            activandoPeriodo = false;
            var msg = limpiarMensajeParaAlert(data.message || 'Acción realizada con éxito');
            if (typeof jQuery !== 'undefined' && typeof jQuery.alert !== 'undefined') {
                jQuery.alert(msg);
            } else {
                mostrarAlerta('success', msg);
            }
            cerrarModalListadosGuardados();
            setTimeout(function() {
            buscarEmpresas();
            }, 500);
        }, function(res) {
            activandoPeriodo = false;
            var msgErr = limpiarMensajeParaAlert(res.message || 'Error al procesar la solicitud');
            if (typeof jQuery !== 'undefined' && typeof jQuery.alert !== 'undefined') {
                jQuery.alert(msgErr);
            } else {
                mostrarAlerta('danger', msgErr);
            }
        });
    }, function(res) {
        activandoPeriodo = false;
        if (typeof jQuery !== 'undefined' && jQuery('#loader').length) {
            jQuery('#loader').fadeOut("slow");
        }
        if (typeof jQuery !== 'undefined' && typeof jQuery.alert !== 'undefined') {
            jQuery.alert('Error al cargar empresas del listado');
        } else {
            mostrarAlerta('danger', 'Error al cargar empresas del listado');
        }
    });
}

function verListado(listadoId) {
    const idNum = parseInt(listadoId);
    
    if (isNaN(idNum)) {
        if (typeof jQuery !== 'undefined' && typeof jQuery.alert !== 'undefined') {
            jQuery.alert('ID de listado inválido');
        } else {
            mostrarAlerta('danger', 'ID de listado inválido');
        }
        return;
    }
    
    // Función auxiliar para normalizar IDs para comparación
    const normalizeId = (id) => {
        if (typeof id === 'number') return id;
        const parsed = parseInt(id);
        return isNaN(parsed) ? id : parsed;
    };
    
    // Buscar el listado
    let listado = listadosGuardados.find(l => {
        const lId = normalizeId(l.id);
        const searchId = normalizeId(listadoId);
        return lId === searchId || l.id === listadoId || l.id === idNum;
    });
    
    if (!listado) {
        cargarListadosGuardados(function() {
            const listadoRecargado = listadosGuardados.find(l => {
                const lId = normalizeId(l.id);
                const searchId = normalizeId(listadoId);
                return lId === searchId || l.id === listadoId || l.id === idNum;
            });
            if (!listadoRecargado) {
                if (typeof jQuery !== 'undefined' && typeof jQuery.alert !== 'undefined') {
                    jQuery.alert('No se encontró el listado');
                } else {
                    mostrarAlerta('danger', 'No se encontró el listado');
                }
                return;
            }
            ejecutarVerListado(listadoRecargado.id);
        });
        return;
    }
    
    ejecutarVerListado(listado.id);
}

function ejecutarVerListado(listadoId) {
    const listado = listadosGuardados.find(l => {
        const normalizeId = (id) => {
            if (typeof id === 'number') return id;
            const parsed = parseInt(id);
            return isNaN(parsed) ? id : parsed;
        };
        const lId = normalizeId(l.id);
        const searchId = normalizeId(listadoId);
        return lId === searchId || l.id === listadoId;
    });
    
    if (!listado || !listado.periodo) {
        if (typeof jQuery !== 'undefined' && typeof jQuery.alert !== 'undefined') {
            jQuery.alert('No se pudo obtener el período del listado');
        } else {
            mostrarAlerta('danger', 'No se pudo obtener el período del listado');
        }
        return;
    }
    
    cerrarModalListadosGuardados();
    
    // Establecer el período en el input
    document.getElementById('periodo').value = listado.periodo;
    document.getElementById('fecha_inicial').value = listado.fechaInicial || listado.fecha_inicial;
    document.getElementById('fecha_final').value = listado.fechaFinal || listado.fecha_final;
    
    // Mostrar loader
    if (typeof jQuery !== 'undefined' && jQuery('#loader').length) {
        jQuery('#loader').show();
    }
    
    ajaxReq({
        action: 'buscar_empresas',
        fecha_inicial: listado.fechaInicial || listado.fecha_inicial,
        fecha_final: listado.fechaFinal || listado.fecha_final
    }, function(dataBuscar) {
        empresas = dataBuscar.data;
        estadisticas = dataBuscar.stats || { total: 0, con_periodo: 0, sin_periodo: 0 };
        actualizarEstadisticas();
        cargarContadores();
        filtrarEmpresas('todos');
        
        var cont = document.getElementById('empresas-container');
        if (cont) cont.style.display = 'block';
        var fs = document.getElementById('fieldset-stats');
        if (fs) fs.style.display = 'block';
        var sc = document.getElementById('stats-container');
        if (sc) sc.style.display = 'block';
        var fb = document.getElementById('filter-buttons');
        if (fb) fb.style.display = 'block';
        
        inicializarGrid();
        
        ajaxReq({
            action: 'obtener_empresas_listado',
            lis_id: listadoId
        }, function(dataListado) {
            if (!dataListado.data) {
                if (typeof jQuery !== 'undefined' && typeof jQuery.alert !== 'undefined') {
                    jQuery.alert('Error al cargar empresas del listado');
                } else {
                    mostrarAlerta('danger', 'Error al cargar empresas del listado');
                }
                return;
            }
            
            var empMarcMap = {};
            dataListado.data.forEach(function(emp) {
            if (emp.Lis_Mar === 'S') {
                    empMarcMap[emp.Emp_Cod.toString()] = true;
            }
        });
        
        renderizarTabla();
        activarModoSoloLectura();
        
        setTimeout(function() {
            if (typeof window.actualizarCaptionGrid === 'function') {
                window.actualizarCaptionGrid();
            }
                document.querySelectorAll('.emp-checkbox').forEach(function(cb) {
                    var empCod = cb.value;
                    cb.checked = empMarcMap[empCod] === true;
                });
            actualizarSeleccion();
        }, 300);
        
        if (typeof jQuery !== 'undefined' && typeof jQuery.alert !== 'undefined') {
            jQuery.alert('Listado cargado en modo solo lectura');
        } else {
            mostrarAlerta('info', 'Listado cargado en modo solo lectura');
        }
        }, function(res) {
        if (typeof jQuery !== 'undefined' && typeof jQuery.alert !== 'undefined') {
                jQuery.alert('Error al cargar empresas del listado');
        } else {
                mostrarAlerta('danger', 'Error al cargar empresas del listado');
            }
        });
    }, function(res) {
        if (typeof jQuery !== 'undefined' && typeof jQuery.alert !== 'undefined') {
            jQuery.alert('Error: ' + (res.message || 'Error al cargar empresas'));
        } else {
            mostrarAlerta('danger', 'Error: ' + (res.message || 'Error al cargar empresas'));
        }
    });
}

function eliminarListado(listadoId) {
    const idNum = parseInt(listadoId);
    
    if (isNaN(idNum)) {
        if (typeof jQuery !== 'undefined' && typeof jQuery.alert !== 'undefined') {
            jQuery.alert('ID de listado inválido');
        } else {
            mostrarAlerta('danger', 'ID de listado inválido');
        }
        return;
    }
    
    // Función auxiliar para normalizar IDs para comparación
    const normalizeId = (id) => {
        if (typeof id === 'number') return id;
        const parsed = parseInt(id);
        return isNaN(parsed) ? id : parsed;
    };
    
    // Buscar el listado (comparar tanto como número como string)
    let listado = listadosGuardados.find(l => {
        const lId = normalizeId(l.id);
        const searchId = normalizeId(listadoId);
        return lId === searchId || lId === idNum || l.id === listadoId || l.id === idNum;
    });
    
    if (!listado) {
        cargarListadosGuardados(function() {
            const listadoRecargado = listadosGuardados.find(l => {
                const lId = normalizeId(l.id);
                const searchId = normalizeId(listadoId);
                return lId === searchId || lId === idNum || l.id === listadoId || l.id === idNum;
            });
            if (!listadoRecargado) {
                if (typeof jQuery !== 'undefined' && typeof jQuery.alert !== 'undefined') {
                    jQuery.alert('No se encontró el listado');
                } else {
                    mostrarAlerta('danger', 'No se encontró el listado');
                }
                return;
            }
            
            if (typeof jQuery !== 'undefined' && typeof jQuery.createDialogConfirm !== 'undefined') {
                // Asegurar que el diálogo tenga z-index mayor que el modal de listados
                setTimeout(function() {
                    jQuery('.ui-dialog, .ui-dialog-overlay').css('z-index', '2000');
                }, 100);
                
                jQuery.createDialogConfirm(
                    `¿Está seguro de eliminar el listado "${listadoRecargado.nombre}"?`,
                    listadoRecargado.id,
                    function(data) {
                        ejecutarEliminacionListado(data);
                    }
                );
            } else {
                if (confirm(`¿Está seguro de eliminar el listado "${listadoRecargado.nombre}"?`)) {
                    ejecutarEliminacionListado(listadoRecargado.id);
                }
            }
        });
        return;
    }
    
    if (typeof jQuery !== 'undefined' && typeof jQuery.createDialogConfirm !== 'undefined') {
        // Asegurar que el diálogo tenga z-index mayor que el modal de listados
        setTimeout(function() {
            jQuery('.ui-dialog, .ui-dialog-overlay').css('z-index', '2000');
        }, 100);
        
        jQuery.createDialogConfirm(
            `¿Está seguro de eliminar el listado "${listado.nombre}"?`,
            listado.id,
            function(data) {
                ejecutarEliminacionListado(data);
            }
        );
    } else {
        if (confirm(`¿Está seguro de eliminar el listado "${listado.nombre}"?`)) {
            ejecutarEliminacionListado(listado.id);
        }
    }
}

function ejecutarEliminacionListado(listadoId) {
    // Mostrar indicador de carga
    if (typeof jQuery !== 'undefined' && jQuery('#loader').length) {
        jQuery('#loader').show();
    }
    
    ajaxReq({
        action: 'eliminar_listado',
        lis_id: listadoId
    }, function(res) {
        var msg = limpiarMensajeParaAlert('Acción realizada con éxito');
            if (typeof jQuery !== 'undefined' && typeof jQuery.alert !== 'undefined') {
            jQuery.alert(msg);
            } else {
            mostrarAlerta('success', res.message);
            }
            cargarListadosGuardados(function() {
            abrirModalListadosGuardados();
            renderizarTabla();
            });
    }, function(res) {
        var msgErr = limpiarMensajeParaAlert('Error al eliminar listado: ' + (res.message || ''));
            if (typeof jQuery !== 'undefined' && typeof jQuery.alert !== 'undefined') {
            jQuery.alert(msgErr);
            } else {
            mostrarAlerta('danger', msgErr);
        }
    });
}
function activarPeriodo() {
    // Prevenir múltiples clics
    if (activandoPeriodo) {
        return;
    }
    
    const periodo = parseInt(document.getElementById('periodo').value);
    if (!periodo || periodo < 2000 || periodo > 2100) {
        if (typeof jQuery !== 'undefined' && typeof jQuery.alert !== 'undefined') {
            jQuery.alert('Debe seleccionar un período válido');
        } else {
            mostrarAlerta('warning', 'Debe seleccionar un período válido');
        }
        return;
    }
    
    const fechaInicial = periodo + '-01-01';
    const fechaFinal = periodo + '-12-31';
    
    const empCods = getEmpresasSeleccionadas();
    
    if (empCods.length === 0) {
        if (typeof jQuery !== 'undefined' && typeof jQuery.alert !== 'undefined') {
            jQuery.alert('Debe seleccionar al menos una empresa');
        } else {
            mostrarAlerta('warning', 'Debe seleccionar al menos una empresa');
        }
        return;
    }
    
    // Filtrar solo empresas que pueden ser aperturadas (no estén en estado "Aperturado")
    const empresasDisponibles = empresas.filter(emp => {
        if (!empCods.includes(emp.Emp_Cod.toString())) {
            return false;
        }
        const estadoListado = estaEnListado(emp.Emp_Cod);
        // Si está aperturado, no puede aperturar
        if (estadoListado === 'A') {
            return false;
        }
        // Validar que si tiene período y es período actual (mismo periodo del input), no puede aperturar
        if (emp.tiene_periodo && emp.es_periodo_actual) {
            return false;
        }
        // Si no tiene período, puede aperturar
        if (!emp.tiene_periodo) {
            return true;
        }
        // En cualquier otro caso, no puede aperturar
        return false;
    });
    
    if (empresasDisponibles.length === 0) {
        if (typeof jQuery !== 'undefined' && typeof jQuery.alert !== 'undefined') {
            jQuery.alert('Las empresas seleccionadas no pueden ser aperturadas. Verifique que no estén en estado "Aperturado" o que no tengan el mismo período vigente.');
        } else {
            mostrarAlerta('warning', 'Las empresas seleccionadas no pueden ser aperturadas. Verifique que no estén en estado "Aperturado" o que no tengan el mismo período vigente.');
        }
        return;
    }
    
    const empCodsDisponibles = empresasDisponibles.map(emp => emp.Emp_Cod.toString());
    
    if (typeof jQuery !== 'undefined' && typeof jQuery.createDialogConfirm !== 'undefined') {
        jQuery.createDialogConfirm(
            `¿Desea activar el período <span style="color: red; font-weight: bold;">${periodo}</span> para <span style="color: black; font-weight: bold;">${empCodsDisponibles.length}</span> empresa(s)?`,
            empCodsDisponibles,
            function(data) {
                ejecutarActivacionLoteMultiple(data, periodo, fechaInicial, fechaFinal);
            }
        );
    } else {
        if (confirm(`¿Desea activar el período ${periodo} (${fechaInicial} al ${fechaFinal}) para ${empCodsDisponibles.length} empresa(s)? (Se omitirán las empresas que ya tienen período)`)) {
            ejecutarActivacionLoteMultiple(empCodsDisponibles, periodo, fechaInicial, fechaFinal);
        }
    }
}

function ejecutarActivacionLoteMultiple(empCodsDisponibles, periodo, fechaInicial, fechaFinal) {
    activandoPeriodo = true;
    
    // Mostrar loader
    if (typeof jQuery !== 'undefined' && jQuery('#loader').length) {
        jQuery('#loader').show();
    }
    
    ajaxReq({
        action: 'activar_periodo',
        fecha_inicial: fechaInicial,
        fecha_final: fechaFinal,
        emp_cods: empCodsDisponibles
    }, function(res) {
        activandoPeriodo = false;
        var msg = limpiarMensajeParaAlert(res.message || 'Acción realizada con éxito');
            if (typeof jQuery !== 'undefined' && typeof jQuery.alert !== 'undefined') {
            jQuery.alert(msg);
            } else {
            mostrarAlerta('success', msg);
            }
        setTimeout(function() {
            buscarEmpresas();
        }, 500);
    }, function(res) {
        activandoPeriodo = false;
        var msgErr = limpiarMensajeParaAlert(res.message || 'Error al procesar la solicitud');
            if (typeof jQuery !== 'undefined' && typeof jQuery.alert !== 'undefined') {
            jQuery.alert(msgErr);
            } else {
            mostrarAlerta('danger', msgErr);
        }
    });
}

function cambiarRegimen() {
    const nuevoRegimen = document.getElementById('nuevo_regimen').value;
    const empCods = getEmpresasSeleccionadas();
    
    if (!nuevoRegimen) {
        mostrarAlerta('warning', 'Debe seleccionar un régimen');
        return;
    }
    
    if (empCods.length === 0) {
        mostrarAlerta('warning', 'Debe seleccionar al menos una empresa');
        cerrarModalCambiarRegimen();
        return;
    }
    
    // Mapear código a texto para el mensaje
    let regimenTexto = nuevoRegimen;
    switch(nuevoRegimen) {
        case 'N':
            regimenTexto = 'Regimen General';
            break;
        case 'NP':
            regimenTexto = 'Rimpe Negocio Popular';
            break;
        case 'EM':
            regimenTexto = 'Rimpe Emprendedor';
            break;
    }
    
    if (!confirm(`¿Desea cambiar el régimen a "${regimenTexto}" para ${empCods.length} empresa(s)?`)) {
        return;
    }
    
    ajaxReq({
        action: 'cambiar_regimen',
        nuevo_regimen: nuevoRegimen,
        emp_cods: empCods
    }, function(res) {
        mostrarAlerta('success', res.message);
            cerrarModalCambiarRegimen();
            buscarEmpresas();
    }, function(res) {
        mostrarAlerta('danger', res.message || 'Error al cambiar régimen');
    });
}

function consultarRegimen() {
    const empCods = getEmpresasSeleccionadas();
    
    if (empCods.length === 0) {
        mostrarAlerta('warning', 'Debe seleccionar al menos una empresa');
        return;
    }
    
    ajaxReq({
        action: 'obtener_regimen',
        emp_cods: empCods
    }, function(res) {
        var msg = 'Régimen actual de las empresas seleccionadas:\n\n';
        res.data.forEach(function(reg) {
            msg += 'Empresa ' + reg.Emp_Cod + ': ' + (reg.Tipo_Regimen || 'No definido') + '\n';
        });
        alert(msg);
    }, function(res) {
        mostrarAlerta('danger', res.message || 'Error al obtener régimen');
    });
}

function limpiarMensajeParaAlert(mensaje) {
    if (!mensaje) return '';
    
    // Convertir a string
    let msg = String(mensaje);
    
    // Escapar caracteres HTML peligrosos
    const div = document.createElement('div');
    div.textContent = msg;
    msg = div.innerHTML;
    
    // Reemplazar saltos de línea con espacios
    msg = msg.replace(/\n/g, ' ');
    msg = msg.replace(/\r/g, ' ');
    
    // Limitar longitud
    if (msg.length > 200) {
        msg = msg.substring(0, 197) + '...';
    }
    
    return msg;
}

function mostrarAlerta(tipo, mensaje) {
    const container = document.getElementById('alert-container');
    const alert = document.createElement('div');
    alert.className = `alert alert-${tipo}`;
    alert.textContent = mensaje;
    alert.style.display = 'block';
    container.innerHTML = '';
    container.appendChild(alert);
    
    setTimeout(() => {
        alert.style.display = 'none';
    }, 5000);
}

if (typeof window !== 'undefined') {
    window.activarPeriodoDesdeListado = activarPeriodoDesdeListado;
    window.abrirModalModificarListado = abrirModalModificarListado;
    window.ejecutarModificarListado = ejecutarModificarListado;
    window.verListado = verListado;
    window.eliminarListado = eliminarListado;
    window.ejecutarEliminacionListado = ejecutarEliminacionListado;
    window.activarPeriodoIndividual = activarPeriodoIndividual;
    window.ejecutarActivacionIndividual = ejecutarActivacionIndividual;
    window.ejecutarActivacionLote = ejecutarActivacionLote;
    window.ejecutarActivacionLoteMultiple = ejecutarActivacionLoteMultiple;
    window.ejecutarEliminacionListado = ejecutarEliminacionListado;
    window.ejecutarVerListado = ejecutarVerListado;
    window.eliminarDePendiente = eliminarDePendiente;
    window.modificarEmpresa = modificarEmpresa;
    window.verListado = verListado;
    window.toggleDesbloquearModificar = toggleDesbloquearModificar;
    window.restaurarModoNormal = restaurarModoNormal;
    window.activarModoSoloLectura = activarModoSoloLectura;
    window.guardarListadoEditado = guardarListadoEditado;
    window.filtrarEmpresas = filtrarEmpresas;
    window.buscarEmpresasPorTexto = buscarEmpresasPorTexto;
    window.limpiarBusqueda = limpiarBusqueda;
}

let textoBusqueda = '';
let empresasSeleccionadasPersistentes = new Set();

function restaurarCheckboxesSeleccionados(empresasSeleccionadas) {
    if (!empresasSeleccionadas) {
        empresasSeleccionadas = Array.from(empresasSeleccionadasPersistentes);
    }
    
    if (empresasSeleccionadas && empresasSeleccionadas.length > 0) {
        empresasSeleccionadas.forEach(empCod => {
            const checkbox = document.querySelector(`.emp-checkbox[value="${empCod}"]`);
            if (checkbox) {
                checkbox.checked = true;
            }
        });
        actualizarSeleccion();
    }
}

function buscarEmpresasPorTexto() {
    const empresasSeleccionadasAntes = getEmpresasSeleccionadas();
    empresasSeleccionadasAntes.forEach(cod => empresasSeleccionadasPersistentes.add(cod));
    
    const texto = document.getElementById('buscar-empresas').value.trim().toLowerCase();
    textoBusqueda = texto;
    
    aplicarFiltros();
}

function limpiarBusqueda() {
    const empresasSeleccionadasAntes = getEmpresasSeleccionadas();
    empresasSeleccionadasAntes.forEach(cod => empresasSeleccionadasPersistentes.add(cod));
    
    document.getElementById('buscar-empresas').value = '';
    textoBusqueda = '';
    aplicarFiltros();
}

