/**
 * Validaciones y lógica para RDEP
 * Maneja la carga de 4 archivos y genera el formato RDEP final
 */

// Tabla de sueldos básicos por año
var sueldosBasicosPorAnio = {
    2021: 400,
    2022: 425,
    2023: 450,
    2024: 460,
    2025: 470,
    2026: 482
};

/**
 * Obtiene el sueldo básico para un año dado
 * @param {number} anio - Año para obtener el sueldo básico
 * @returns {number} - Sueldo básico del año, o 0 si no existe
 */
function obtenerSueldoBasicoPorAnio(anio) {
    return sueldosBasicosPorAnio[anio] || 0;
}

var archivosRdep = {
    consolidado: null,
    decimoTercero: null,
    decimoCuarto: null,
    utilidades: null, // Excel con DISTR_EQUIT_DE_TR y DISTR_POR_CA
    utilidadesCsv: null, // CSV con fondos_reserva_2021 y otros campos
    avisosEntrada: null
};

var datosRdep = [];
var gridRdep = null;

$(document).ready(function () {
    inicializarRdep();
});

function inicializarRdep() {
    // Inicializar año y fechas por defecto
    var anioActual = new Date().getFullYear();
    var anioInput = $('#anioRdep');
    var dateIni = $('#dateRdepIni');
    var dateFin = $('#dateRdepFin');
    
    if (anioInput.length > 0 && !anioInput.val()) {
        anioInput.val(anioActual);
    }
    
    // Establecer fechas por defecto: 1 enero - 31 diciembre del año seleccionado
    function actualizarFechas() {
        var anio = parseInt(anioInput.val()) || anioActual;
        dateIni.val(anio + '-01-01');
        dateFin.val(anio + '-12-31');
    }
    
    anioInput.on('change', actualizarFechas);
    actualizarFechas(); // Inicializar al cargar
    
    // Event listeners para los archivos
    $('#fileConsolidado').on('change', function (e) {
        manejarArchivo(e.target.files[0], 'consolidado');
    });
    
    $('#fileDecimoTercero').on('change', function (e) {
        manejarArchivo(e.target.files[0], 'decimoTercero');
    });
    
    $('#fileDecimoCuarto').on('change', function (e) {
        manejarArchivo(e.target.files[0], 'decimoCuarto');
    });
    
    $('#fileUtilidades').on('change', function (e) {
        // Utilidades Excel (XLSX/XLS)
        if (e.target.files[0]) {
            manejarArchivo(e.target.files[0], 'utilidades');
        }
    });
    
    $('#fileUtilidadesCsv').on('change', function (e) {
        // Utilidades CSV (para fondos de reserva y otros campos)
        if (e.target.files[0]) {
            manejarArchivo(e.target.files[0], 'utilidadesCsv');
        }
    });
    
    $('#fileAvisosEntrada').on('change', function (e) {
        manejarArchivo(e.target.files[0], 'avisosEntrada');
    });
    
    $('#btnProcesar').on('click', function () {
        procesarArchivosRdep();
    });
}

function manejarArchivo(archivo, tipo) {
    if (!archivo) {
        actualizarEstadoArchivo(tipo, false, '');
        return;
    }
    
    var nombre = archivo.name;
    var extension = nombre.split('.').pop().toLowerCase();
    
    // Validar extensiones
    var extensionesValidas = {
        'consolidado': ['xlsx', 'xls'],
        'decimoTercero': ['csv'],
        'decimoCuarto': ['csv'],
        'utilidades': ['xlsx', 'xls'],
        'utilidadesCsv': ['csv'],
        'avisosEntrada': ['xlsx', 'xls']
    };
    
    if (!extensionesValidas[tipo] || !extensionesValidas[tipo].includes(extension)) {
        $.alert('Formato de archivo no válido para ' + tipo, null, 'alert');
        actualizarEstadoArchivo(tipo, false, '');
        return;
    }
    
    // Leer archivo según tipo
    var reader = new FileReader();
    reader.onload = function (e) {
        try {
            if (tipo === 'consolidado' || tipo === 'avisosEntrada' || tipo === 'utilidades') {
                procesarConsolidado(e.target.result, archivo, tipo);
            } else if (tipo === 'utilidadesCsv') {
                // Procesar CSV de utilidades
                procesarCsv(e.target.result, 'utilidadesCsv', archivo);
            } else {
                procesarCsv(e.target.result, tipo, archivo);
            }
        } catch (error) {
            console.error('Error al procesar archivo:', error);
            $.alert('Error al procesar el archivo: ' + error.message, null, 'alert');
            actualizarEstadoArchivo(tipo, false, '');
        }
    };
    
    if (tipo === 'consolidado' || tipo === 'avisosEntrada' || tipo === 'utilidades') {
        reader.readAsArrayBuffer(archivo);
    } else {
        reader.readAsText(archivo, 'ISO-8859-1');
    }
}

function procesarConsolidado(data, archivo, tipo) {
    try {
        var workbook = XLSX.read(data, { type: 'array' });
        var primeraHoja = workbook.Sheets[workbook.SheetNames[0]];
        var datos = XLSX.utils.sheet_to_json(primeraHoja, { header: 1, defval: '' });
        
        if (tipo === 'avisosEntrada') {
            // Procesar archivo de avisos de entrada
            procesarAvisosEntrada(datos, archivo);
        } else if (tipo === 'utilidades') {
            // Procesar archivo de utilidades (XLSX/XLS)
            procesarUtilidadesExcel(datos, archivo);
        } else {
            archivosRdep.consolidado = {
                nombre: archivo.name,
                datos: datos,
                workbook: workbook
            };
            
            actualizarEstadoArchivo('consolidado', true, archivo.name);
            verificarArchivosListos();
        }
    } catch (error) {
        console.error('Error al leer archivo:', error);
        $.alert('Error al leer el archivo: ' + error.message, null, 'alert');
        actualizarEstadoArchivo(tipo, false, '');
    }
}

/**
 * Procesa el archivo Excel de Utilidades y extrae las columnas DISTR_EQUIT_DE_TR y DISTR_POR_CA
 */
function procesarUtilidadesExcel(datos, archivo) {
    try {
        if (datos.length < 2) {
            throw new Error('El archivo de utilidades está vacío o no tiene datos.');
        }
        
        var resultado = {};
        var filaEncabezados = -1;
        var indiceCedula = -1;
        var indiceDistrEquit = -1;
        var indiceDistrCargas = -1;
        var encabezados = [];
        
        // Buscar la fila de encabezados (buscar en las primeras 15 filas)
        for (var filaIdx = 0; filaIdx < Math.min(15, datos.length); filaIdx++) {
            var fila = datos[filaIdx] || [];
            for (var colIdx = 0; colIdx < fila.length; colIdx++) {
                var header = String(fila[colIdx] || '').trim().toUpperCase();
                
                // Buscar columnas
                if ((header.includes('CEDULA') || header.includes('CÉDULA')) && indiceCedula === -1) {
                    indiceCedula = colIdx;
                }
                if ((header.includes('DISTR_EQUIT_DE_TR') || header.includes('DISTR_EQUIT')) && indiceDistrEquit === -1) {
                    indiceDistrEquit = colIdx;
                    filaEncabezados = filaIdx;
                    encabezados = fila;
                }
                if ((header.includes('DISTR_POR_CA') || header.includes('DISTR_POR_CARGAS') || header.includes('DISTR_POR')) && indiceDistrCargas === -1) {
                    indiceDistrCargas = colIdx;
                    if (filaEncabezados === -1) {
                        filaEncabezados = filaIdx;
                        encabezados = fila;
                    }
                }
            }
            if (filaEncabezados !== -1 && indiceCedula !== -1 && indiceDistrEquit !== -1 && indiceDistrCargas !== -1) {
                break;
            }
        }
        
        // Si no encuentra encabezados, intentar usar primera fila
        if (filaEncabezados === -1 || indiceCedula === -1) {
            filaEncabezados = 0;
            encabezados = datos[0] || [];
            // Buscar cédula en las primeras columnas
            for (var i = 0; i < Math.min(5, encabezados.length); i++) {
                var header = String(encabezados[i] || '').trim().toUpperCase();
                if (header.includes('CEDULA') || header.includes('CÉDULA')) {
                    indiceCedula = i;
                    break;
                }
            }
        }
        
        if (indiceCedula === -1) {
            throw new Error('No se encontró la columna "Cédula" en el archivo de utilidades.');
        }
        
        if (indiceDistrEquit === -1 || indiceDistrCargas === -1) {
            throw new Error('No se encontraron las columnas "DISTR_EQUIT_DE_TR" y "DISTR_POR_CA" en el archivo de utilidades.');
        }
        
        // console.log('Procesando Utilidades Excel - Encabezados encontrados en fila:', filaEncabezados);
        // console.log('Índices - Cédula:', indiceCedula, 'Distr Equit:', indiceDistrEquit, 'Distr Cargas:', indiceDistrCargas);
        
        // Procesar filas de datos (empezar después de la fila de encabezados)
        for (var i = filaEncabezados + 1; i < datos.length; i++) {
            var fila = datos[i] || [];
            
            if (fila.length > indiceCedula && fila[indiceCedula]) {
                var cedulaRaw = String(fila[indiceCedula]).trim().replace(/[^0-9]/g, '');
                var cedula = normalizarCedula(cedulaRaw);
                
                if (cedula && cedula.length >= 9) {
                    // Extraer valores de las dos columnas
                    var distrEquit = 0;
                    var distrCargas = 0;
                    
                    if (fila.length > indiceDistrEquit && fila[indiceDistrEquit] !== null && fila[indiceDistrEquit] !== '') {
                        var valorEquit = String(fila[indiceDistrEquit] || '0').trim();
                        distrEquit = parseFloat(valorEquit.replace(',', '.')) || 0;
                    }
                    
                    if (fila.length > indiceDistrCargas && fila[indiceDistrCargas] !== null && fila[indiceDistrCargas] !== '') {
                        var valorCargas = String(fila[indiceDistrCargas] || '0').trim();
                        distrCargas = parseFloat(valorCargas.replace(',', '.')) || 0;
                    }
                    
                    // Sumar ambos valores para obtener la participación de utilidades
                    var participacionTotal = distrEquit + distrCargas;
                    
                    // Guardar por cédula (si hay duplicados, sumar)
                    if (!resultado[cedula]) {
                        resultado[cedula] = {
                            distr_equit_de_trabaj: 0,
                            distr_por_cargas: 0,
                            participacion_utilidades_2022: 0
                        };
                    }
                    
                    resultado[cedula].distr_equit_de_trabaj += distrEquit;
                    resultado[cedula].distr_por_cargas += distrCargas;
                    resultado[cedula].participacion_utilidades_2022 += participacionTotal;
                }
            }
        }
        
        archivosRdep.utilidades = {
            nombre: archivo.name,
            datos: resultado // Objeto con cédulas como keys
        };
        
        // console.log('Utilidades Excel procesado - Total registros:', Object.keys(resultado).length);
        actualizarEstadoArchivo('utilidades', true, archivo.name);
        verificarArchivosListos();
        
    } catch (error) {
        console.error('Error al procesar archivo de utilidades:', error);
        $.alert('Error al procesar el archivo de utilidades: ' + error.message, null, 'alert');
        actualizarEstadoArchivo('utilidades', false, '');
    }
}

function procesarAvisosEntrada(datos, archivo) {
    try {
        // Buscar encabezados (primera fila)
        if (datos.length < 2) {
            throw new Error('El archivo de avisos de entrada está vacío o no tiene datos.');
        }
        
        var encabezados = datos[0] || [];
        var indiceCedula = -1;
        var indiceAvisoEntrada = -1;
        
        // Buscar columnas
        for (var i = 0; i < encabezados.length; i++) {
            var header = String(encabezados[i] || '').trim().toLowerCase();
            if (header.includes('cedula') || header.includes('cédula') || header === 'cedula') {
                indiceCedula = i;
            }
            if (header.includes('aviso') && header.includes('entrada')) {
                indiceAvisoEntrada = i;
            }
        }
        
        if (indiceCedula === -1) {
            throw new Error('No se encontró la columna "Cédula" en el archivo de avisos de entrada.');
        }
        if (indiceAvisoEntrada === -1) {
            throw new Error('No se encontró la columna "Aviso de entrada" en el archivo.');
        }
        
        var avisosPorCedula = {};
        
        // Procesar filas de datos
        for (var i = 1; i < datos.length; i++) {
            var fila = datos[i] || [];
            if (fila.length > indiceCedula && fila[indiceCedula]) {
                var cedulaRaw = String(fila[indiceCedula]).trim();
                var cedula = normalizarCedula(cedulaRaw);
                if (cedula && cedula.length >= 9) {
                    var fechaAviso = '';
                    if (fila.length > indiceAvisoEntrada && fila[indiceAvisoEntrada]) {
                        var fechaRaw = fila[indiceAvisoEntrada];
                        
                        // Si es un número (serie de Excel), convertir a fecha
                        if (typeof fechaRaw === 'number' || (!isNaN(parseFloat(fechaRaw)) && isFinite(fechaRaw))) {
                            var fechaNum = parseFloat(fechaRaw);
                            if (fechaNum > 0 && fechaNum < 1000000) { // Rango razonable para fechas Excel
                                var fechaDate = excelSerialToDate(fechaNum);
                                fechaAviso = formatearFecha(fechaDate);
                            } else {
                                fechaAviso = String(fechaRaw).trim();
                            }
                        } else {
                            // Si ya es una fecha en formato texto
                            fechaAviso = String(fechaRaw).trim();
                        }
                    }
                    if (fechaAviso) {
                        avisosPorCedula[cedula] = fechaAviso;
                        // console.log('Fecha aviso procesada para cédula', cedula, ':', fechaAviso, 'Valor original:', fila[indiceAvisoEntrada]);
                    }
                }
            }
        }
        
        archivosRdep.avisosEntrada = {
            nombre: archivo.name,
            datos: avisosPorCedula
        };
        
        actualizarEstadoArchivo('avisosEntrada', true, archivo.name);
        verificarArchivosListos();
    } catch (error) {
        console.error('Error al procesar avisos de entrada:', error);
        $.alert('Error al procesar el archivo de avisos de entrada: ' + error.message, null, 'alert');
        actualizarEstadoArchivo('avisosEntrada', false, '');
    }
}

function procesarCsv(data, tipo, archivo) {
    try {
        var lineas = data.split('\n');
        var encabezados = lineas[0].split(';');
        var datos = [];
        
        for (var i = 1; i < lineas.length; i++) {
            if (lineas[i].trim()) {
                var valores = lineas[i].split(';');
                if (valores.length > 0 && valores[0].trim()) {
                    var registro = {};
                    for (var j = 0; j < encabezados.length && j < valores.length; j++) {
                        registro[encabezados[j].trim()] = valores[j].trim();
                    }
                    datos.push(registro);
                }
            }
        }
        
        archivosRdep[tipo] = {
            nombre: archivo.name,
            datos: datos,
            encabezados: encabezados
        };
        
        actualizarEstadoArchivo(tipo, true, archivo.name);
        verificarArchivosListos();
    } catch (error) {
        console.error('Error al leer CSV:', error);
        $.alert('Error al leer el archivo CSV: ' + error.message, null, 'alert');
        actualizarEstadoArchivo(tipo, false, '');
    }
}

function actualizarEstadoArchivo(tipo, cargado, nombre) {
    // Mapear tipos especiales
    var tipoMap = {
        'utilidadesCsv': 'UtilidadesCsv'
    };
    
    var tipoCapitalizado = tipoMap[tipo] || (tipo.charAt(0).toUpperCase() + tipo.slice(1));
    var statusId = 'status' + tipoCapitalizado;
    var $status = $('#' + statusId);
    
    if (cargado) {
        $status.removeClass('loaded').addClass('loaded');
        $status.html('<i class="glyphicon glyphicon-ok"></i> ' + nombre);
    } else {
        $status.removeClass('loaded');
        $status.html('');
    }
}

function verificarArchivosListos() {
    // El consolidado es obligatorio, los demás son opcionales
    var consolidadoListo = archivosRdep.consolidado !== null;
    
    $('#btnProcesar').prop('disabled', !consolidadoListo);
}

function procesarArchivosRdep() {
    if (!archivosRdep.consolidado) {
        $.alert('Debe cargar al menos el archivo consolidado', null, 'alert');
        return;
    }
    
    try {
        // Procesar consolidado
        var consolidadoData = procesarDatosConsolidado(archivosRdep.consolidado.datos);
        
        // Validar si hay datos del año seleccionado
        var anioSeleccionado = $('#anioRdep').val() || new Date().getFullYear();
        var totalRegistros = Object.keys(consolidadoData).filter(function(k) { 
            return k.charAt(0) !== '_'; // Excluir propiedades internas
        }).length;
        
        if (totalRegistros === 0) {
            $.alert('No se encontraron datos del año ' + anioSeleccionado + ' en el archivo consolidado.\n\n' +
                   'Por favor verifique:\n' +
                   '• Que el año seleccionado coincida con los periodos del archivo\n' +
                   '• Que el archivo contenga datos del año ' + anioSeleccionado, null, 'alert');
            return;
        }
        
        // Procesar CSVs
        var decimoTerceroData = archivosRdep.decimoTercero ? 
            procesarDatosDecimoTercero(archivosRdep.decimoTercero.datos) : {};
        var decimoCuartoData = archivosRdep.decimoCuarto ? 
            procesarDatosDecimoCuarto(archivosRdep.decimoCuarto.datos) : {};
        
        // Procesar Utilidades: combinar datos del Excel y del CSV
        var utilidadesExcelData = archivosRdep.utilidades ? 
            procesarDatosUtilidades(archivosRdep.utilidades.datos) : {};
        var utilidadesCsvData = archivosRdep.utilidadesCsv ? 
            procesarDatosUtilidadesCsv(archivosRdep.utilidadesCsv.datos) : {};
        
        // Combinar datos de utilidades (priorizar CSV para campos adicionales, Excel para participación)
        var utilidadesData = combinarDatosUtilidades(utilidadesExcelData, utilidadesCsvData);
        
        // Combinar datos
        datosRdep = combinarDatosRdep(consolidadoData, decimoTerceroData, decimoCuartoData, utilidadesData);
        
        // Crear grid
        crearGridRdep(datosRdep);
        
        $.alert('Archivos procesados correctamente. ' + datosRdep.length + ' registros encontrados.', null, 'success');
        
    } catch (error) {
        console.error('Error al procesar:', error);
        $.alert('Error al procesar los archivos: ' + error.message, null, 'alert');
    }
}

/**
 * Normaliza una cédula para que tenga exactamente 10 dígitos
 * Elimina ceros iniciales innecesarios y agrega cero al inicio si tiene 9 dígitos
 */
function normalizarCedula(cedula) {
    if (!cedula) return '';
    var ced = String(cedula).trim().replace(/[^0-9]/g, '');
    
    // Si está vacía, retornar vacío
    if (!ced || ced.length === 0) return '';
    
    // Eliminar ceros iniciales innecesarios (pero mantener al menos un dígito)
    // Ejemplo: "03050184377" -> "3050184377", "0001234567" -> "1234567"
    while (ced.length > 1 && ced.charAt(0) === '0') {
        ced = ced.substring(1);
    }
    
    // Si tiene 9 dígitos, agregar cero al inicio para tener 10 dígitos
    if (ced.length === 9) {
        ced = '0' + ced;
    }
    
    // Si tiene más de 10 dígitos, tomar los últimos 10 (por si hay errores de formato)
    if (ced.length > 10) {
        ced = ced.substring(ced.length - 10);
    }
    
    return ced;
}

// Función para convertir número de serie de Excel a fecha (global)
function excelSerialToDate(serial) {
    // Excel cuenta desde el 1 de enero de 1900
    // Pero Excel tiene un bug: considera 1900 como año bisiesto
    // Por eso hay que ajustar
    var excelEpoch = new Date(1899, 11, 30); // 30 de diciembre de 1899
    var days = Math.floor(serial);
    var date = new Date(excelEpoch);
    date.setDate(date.getDate() + days);
    return date;
}

// Función para formatear fecha a DD/MM/YYYY (global)
function formatearFecha(date) {
    if (!date || isNaN(date.getTime())) return '';
    var dia = date.getDate();
    var mes = date.getMonth() + 1;
    var anio = date.getFullYear();
    return dia + '/' + mes + '/' + anio;
}

function procesarDatosConsolidado(datos) {
    var resultado = {};
    
    // Buscar la fila de encabezados (puede estar en las primeras filas)
    if (datos.length < 2) return resultado;
    
    var filaEncabezados = -1;
    var indiceCedula = -1;
    var encabezados = [];
    
    // Buscar en las primeras 10 filas la que tenga "Cédula" o "Cedula"
    for (var filaIdx = 0; filaIdx < Math.min(10, datos.length); filaIdx++) {
        var fila = datos[filaIdx] || [];
        for (var colIdx = 0; colIdx < fila.length; colIdx++) {
            var header = String(fila[colIdx] || '').trim().toLowerCase();
            // Buscar variaciones de cédula
            if (header === 'cedula' || header === 'cédula' || 
                header === 'cédula (ejm.:0502366503)' || 
                header.includes('cedula') || header.includes('cédula') ||
                header === 'ced' || header === 'ced.' || header === 'céd.') {
                filaEncabezados = filaIdx;
                indiceCedula = colIdx;
                encabezados = fila;
                break;
            }
        }
        if (filaEncabezados !== -1) break;
    }
    
    if (indiceCedula === -1) {
        // Si no encuentra, intentar con la primera fila y buscar por posición común (columna B = índice 1)
        // console.log('No se encontró encabezado de cédula, intentando con primera fila');
        filaEncabezados = 0;
        encabezados = datos[0] || [];
        // Intentar en las primeras columnas (A=0, B=1, C=2)
        for (var i = 0; i < Math.min(5, encabezados.length); i++) {
            var header = String(encabezados[i] || '').trim().toLowerCase();
            if (header.includes('periodo') || header.includes('período')) {
                // Si encuentra "Periodo", la cédula probablemente está en la siguiente columna
                indiceCedula = i + 1;
                break;
            }
        }
        // Si aún no encuentra, usar columna B (índice 1) como fallback
        if (indiceCedula === -1 && encabezados.length > 1) {
            indiceCedula = 1; // Columna B es común para cédula
            // console.log('Usando columna B (índice 1) como cédula por defecto');
        }
    }
    
    if (indiceCedula === -1) {
        throw new Error('No se encontró la columna de cédula en el consolidado. Encabezados encontrados: ' + 
                       (encabezados.length > 0 ? encabezados.join(', ') : 'ninguno'));
    }
    
    // console.log('Columna de cédula encontrada en índice:', indiceCedula, 'Fila encabezados:', filaEncabezados);
    // console.log('Encabezados:', encabezados);
    
    // Buscar índice de columna de periodo, sueldo, Individual, Días, Nombres y Apellidos
    var indicePeriodo = -1;
    var indiceSueldo = -1;
    var indiceIndividual = -1;
    var indiceDias = -1;
    var indiceNombres = -1;
    var indiceApellidos = -1;
    
    for (var j = 0; j < encabezados.length; j++) {
        var header = String(encabezados[j] || '').trim().toLowerCase();
        if ((header.includes('periodo') || header === 'periodo') && indicePeriodo === -1) {
            indicePeriodo = j;
        }
        if (header.includes('sueldo') && !header.includes('neto') && !header.includes('basico') && indiceSueldo === -1) {
            indiceSueldo = j;
        }
        if (header === 'individual' || header.includes('individual')) {
            indiceIndividual = j;
        }
        if ((header.includes('dias') || header.includes('días')) && !header.includes('laborados')) {
            indiceDias = j;
        }
        if (header.includes('nombre') && !header.includes('apellido')) {
            indiceNombres = j;
        }
        if (header.includes('apellido') || header.includes('apellidos')) {
            indiceApellidos = j;
        }
    }
    // Si no encuentra periodo, usar columna A (índice 0)
    if (indicePeriodo === -1 && encabezados.length > 0) {
        indicePeriodo = 0;
    }
    // Si no encuentra sueldo, buscar en columna E (índice 4) que es común
    if (indiceSueldo === -1 && encabezados.length > 4) {
        indiceSueldo = 4;
    }
    
    // Obtener el año seleccionado para filtrar
    var anioSeleccionado = $('#anioRdep').val() || new Date().getFullYear();
    // console.log('Año seleccionado para filtrar consolidado:', anioSeleccionado);
    
    // Función para convertir número de mes a formato abreviado
    function mesAbreviado(mes) {
        var meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        var mesNum = parseInt(mes, 10);
        if (mesNum >= 1 && mesNum <= 12) {
            return meses[mesNum - 1];
        }
        return '';
    }
    
    // Función para extraer el mes del periodo
    function extraerMesPeriodo(periodo) {
        if (!periodo) return null;
        var periodoStr = String(periodo).trim();
        var mes = null;
        
        if (periodoStr.includes('-')) {
            // Formato "2024-12" o "2024-1"
            var partes = periodoStr.split('-');
            if (partes.length >= 2) {
                mes = parseInt(partes[1], 10);
            }
        } else if (periodoStr.includes('/')) {
            // Formato "12/2024"
            var partes = periodoStr.split('/');
            if (partes.length >= 2) {
                mes = parseInt(partes[0], 10);
            }
        } else if (periodoStr.length >= 6) {
            // Formato "202412" (año+mes)
            mes = parseInt(periodoStr.substring(4, 6), 10);
        }
        
        return mes;
    }
    
    // Procesar filas (empezar después de la fila de encabezados)
    // Agrupar por cédula para sumar sueldos (solo del año seleccionado)
    var sueldosPorCedula = {};
    var ultimoPeriodoPorCedula = {}; // Para rastrear el último periodo de cada empleado
    var sueldosPorCedulaYMes = {}; // Para guardar sueldos por cédula y mes (para cálculo de fondos de reserva)
    var individualPorCedula = {}; // Para sumar la columna Individual (Aporte personal)
    var periodosPorCedula = {}; // Para contar periodos únicos por cédula (para calcular días) - puede ser Set o objeto {_dias_total: X}
    var nombresApellidosPorCedula = {}; // Para guardar nombres y apellidos del consolidado
    var usarColumnaDias = {}; // Para rastrear qué cédulas usan columna "Días" directa
    
    for (var i = filaEncabezados + 1; i < datos.length; i++) {
        var fila = datos[i] || [];
        if (fila.length > indiceCedula && fila[indiceCedula]) {
            var cedulaRaw = String(fila[indiceCedula]).trim();
            var cedula = normalizarCedula(cedulaRaw);
            if (cedula && cedula.length >= 9) { // Las cédulas ecuatorianas tienen al menos 9 dígitos
                
                // Filtrar por año del periodo
                var incluirFila = true;
                var periodo = '';
                var mesPeriodo = null;
                if (indicePeriodo >= 0 && fila.length > indicePeriodo) {
                    periodo = String(fila[indicePeriodo] || '').trim();
                    // El periodo puede ser: "2024-12", "2024-1", "12/2024", etc.
                    var anioPeriodo = '';
                    if (periodo.includes('-')) {
                        anioPeriodo = periodo.split('-')[0]; // "2024-12" -> "2024"
                    } else if (periodo.includes('/')) {
                        var partes = periodo.split('/');
                        anioPeriodo = partes[partes.length - 1]; // "12/2024" -> "2024"
                    } else if (periodo.length >= 4) {
                        anioPeriodo = periodo.substring(0, 4); // "202412" -> "2024"
                    }
                    
                    if (anioPeriodo && anioPeriodo !== String(anioSeleccionado)) {
                        incluirFila = false;
                    } else {
                        // Extraer el mes del periodo
                        mesPeriodo = extraerMesPeriodo(periodo);
                    }
                }
                
                if (incluirFila) {
                    // Sumar sueldos solo si está en el año seleccionado
                    if (indiceSueldo >= 0 && fila.length > indiceSueldo) {
                        var sueldo = parseFloat(String(fila[indiceSueldo] || '0').replace(',', '.')) || 0;
                        if (!sueldosPorCedula[cedula]) {
                            sueldosPorCedula[cedula] = 0;
                        }
                        sueldosPorCedula[cedula] += sueldo;
                        
                        // Guardar sueldo por mes para cálculo de fondos de reserva
                        if (mesPeriodo !== null) {
                            if (!sueldosPorCedulaYMes[cedula]) {
                                sueldosPorCedulaYMes[cedula] = {};
                            }
                            if (!sueldosPorCedulaYMes[cedula][mesPeriodo]) {
                                sueldosPorCedulaYMes[cedula][mesPeriodo] = 0;
                            }
                            sueldosPorCedulaYMes[cedula][mesPeriodo] += sueldo;
                        }
                    }
                    
                    // Si hay columna de días directa, usar esa (tiene prioridad sobre contar periodos)
                    // Verificar primero si hay columna "Días" en esta fila
                    var tieneColumnaDias = (indiceDias >= 0 && fila.length > indiceDias);
                    
                    if (tieneColumnaDias && incluirFila) {
                        var diasFila = parseFloat(String(fila[indiceDias] || '0').replace(',', '.')) || 0;
                        // Marcar que esta cédula usa columna "Días" directa
                        usarColumnaDias[cedula] = true;
                        if (!periodosPorCedula[cedula]) {
                            periodosPorCedula[cedula] = { _dias_total: 0 };
                        }
                        // Si es un Set, convertir a objeto con días
                        if (periodosPorCedula[cedula] instanceof Set) {
                            periodosPorCedula[cedula] = { _dias_total: 0 };
                        }
                        // Sumar días de esta fila
                        if (typeof periodosPorCedula[cedula] === 'object' && !(periodosPorCedula[cedula] instanceof Set)) {
                            periodosPorCedula[cedula]._dias_total = (periodosPorCedula[cedula]._dias_total || 0) + diasFila;
                        }
                    }
                    
                    // Contar periodos únicos para calcular días laborados (30 días por mes)
                    // Solo si NO se está usando columna "Días" directa para esta cédula
                    if (periodo && periodo.trim() && incluirFila && !usarColumnaDias[cedula]) {
                        if (!periodosPorCedula[cedula]) {
                            periodosPorCedula[cedula] = new Set();
                        }
                        // Solo agregar al Set si efectivamente es un Set
                        if (periodosPorCedula[cedula] instanceof Set) {
                            periodosPorCedula[cedula].add(periodo.trim());
                        }
                    }
                    
                    // Guardar nombres y apellidos del consolidado (solo una vez por cédula)
                    if (!nombresApellidosPorCedula[cedula]) {
                        nombresApellidosPorCedula[cedula] = {
                            nombres: '',
                            apellidos: ''
                        };
                        
                        // Buscar nombres y apellidos
                        if (indiceNombres >= 0 && fila.length > indiceNombres) {
                            var nombreCompleto = String(fila[indiceNombres] || '').trim();
                            nombresApellidosPorCedula[cedula].nombres = nombreCompleto;
                        }
                        
                        if (indiceApellidos >= 0 && fila.length > indiceApellidos) {
                            nombresApellidosPorCedula[cedula].apellidos = String(fila[indiceApellidos] || '').trim();
                        }
                        
                        // Si no hay apellidos separados, buscar en otras columnas comunes
                        if (!nombresApellidosPorCedula[cedula].apellidos && !nombresApellidosPorCedula[cedula].nombres) {
                            for (var k = 0; k < encabezados.length && k < fila.length; k++) {
                                var headerName = String(encabezados[k] || '').trim().toLowerCase();
                                if (headerName.includes('nombre') && !headerName.includes('apellido')) {
                                    nombresApellidosPorCedula[cedula].nombres = String(fila[k] || '').trim();
                                }
                                if (headerName.includes('apellido')) {
                                    nombresApellidosPorCedula[cedula].apellidos = String(fila[k] || '').trim();
                                }
                            }
                        }
                        
                        // Si no hay apellidos separados pero hay un campo "Nombres" que contiene ambos
                        if (!nombresApellidosPorCedula[cedula].apellidos && nombresApellidosPorCedula[cedula].nombres) {
                            var nombreCompleto = nombresApellidosPorCedula[cedula].nombres;
                            // Intentar separar: generalmente en Excel viene "APELLIDOS NOMBRES"
                            var partes = nombreCompleto.trim().split(/\s+/);
                            if (partes.length >= 2) {
                                // Los últimos elementos son nombres, el resto apellidos
                                // Generalmente el último es el nombre de pila
                                nombresApellidosPorCedula[cedula].apellidos = partes.slice(0, -1).join(' '); // Todo menos el último
                                nombresApellidosPorCedula[cedula].nombres = partes[partes.length - 1]; // El último
                            }
                        }
                    }
                    
                    // Sumar Individual (Aporte personal a la seguridad social)
                    if (indiceIndividual >= 0 && fila.length > indiceIndividual) {
                        var individual = parseFloat(String(fila[indiceIndividual] || '0').replace(',', '.')) || 0;
                        if (!individualPorCedula[cedula]) {
                            individualPorCedula[cedula] = 0;
                        }
                        individualPorCedula[cedula] += individual;
                    }
                    
                    // Rastrear el último periodo (mes más reciente)
                    if (mesPeriodo !== null) {
                        if (!ultimoPeriodoPorCedula[cedula] || mesPeriodo > ultimoPeriodoPorCedula[cedula]) {
                            ultimoPeriodoPorCedula[cedula] = mesPeriodo;
                        }
                    }
                    
                    // Guardar registro completo (solo el último para datos generales)
                    var registro = {};
                    for (var j = 0; j < encabezados.length && j < fila.length; j++) {
                        var key = String(encabezados[j] || '').trim();
                        if (key) {
                            registro[key] = fila[j] || '';
                        }
                    }
                    // También guardar por índice de columna para acceso alternativo
                    registro._cedula_col = indiceCedula;
                    registro._sueldo_total = sueldosPorCedula[cedula]; // Agregar suma de sueldos
                    registro._sueldos_por_mes = sueldosPorCedulaYMes[cedula] || {}; // Guardar sueldos por mes
                    registro._individual_total = individualPorCedula[cedula] || 0; // Suma de Individual (Aporte personal)
                    
                    // Calcular días laborados desde el consolidado
                    var diasLaboradosConsolidado = 0;
                    if (periodosPorCedula[cedula]) {
                        if (periodosPorCedula[cedula] instanceof Set) {
                            // Contar periodos únicos y multiplicar por 30 días (aproximación: 1 mes = 30 días)
                            diasLaboradosConsolidado = periodosPorCedula[cedula].size * 30;
                        } else if (typeof periodosPorCedula[cedula] === 'object' && periodosPorCedula[cedula]._dias_total !== undefined) {
                            // Si hay días totales calculados de la columna Días
                            diasLaboradosConsolidado = periodosPorCedula[cedula]._dias_total || 0;
                        }
                    }
                    registro._dias_laborados = diasLaboradosConsolidado;
                    
                    // Guardar nombres y apellidos del consolidado
                    if (nombresApellidosPorCedula[cedula]) {
                        registro._nombres_consolidado = nombresApellidosPorCedula[cedula].nombres || '';
                        registro._apellidos_consolidado = nombresApellidosPorCedula[cedula].apellidos || '';
                    }
                    
                    resultado[cedula] = registro;
                }
            }
        }
    }
    
    // Agregar último mes de aporte a cada registro
    Object.keys(resultado).forEach(function(cedula) {
        if (ultimoPeriodoPorCedula[cedula]) {
            resultado[cedula]._ultimo_mes_aporte = mesAbreviado(ultimoPeriodoPorCedula[cedula]);
        } else {
            resultado[cedula]._ultimo_mes_aporte = '';
        }
    });
    
    // console.log('Registros procesados del consolidado:', Object.keys(resultado).length);
    
    // Guardar el año seleccionado para validación posterior
    resultado._anioSeleccionado = anioSeleccionado;
    resultado._totalRegistros = Object.keys(resultado).length - 1; // -1 por _anioSeleccionado
    
    return resultado;
}

function procesarDatosDecimoTercero(datos) {
    var resultado = {};
    
    datos.forEach(function (row) {
        var cedulaRaw = String(row['Cédula (Ejm.:0502366503)'] || row['Cedula'] || row['CÉDULA'] || '').trim();
        var cedula = normalizarCedula(cedulaRaw);
        if (cedula) {
            // Obtener el campo "Sueldos" del archivo XIII
            var sueldosDecimoTercero = parseFloat(String(row['Sueldos'] || row['SUELDOS'] || row['Sueldo'] || '0').replace(',', '.')) || 0;
            
            resultado[cedula] = {
                nombres: row['Nombres'] || '',
                apellidos: row['Apellidos'] || '',
                genero: row['Genero (Masculino=M ó Femenino=F)'] || row['Genero'] || '',
                ocupacion: row['Ocupación'] || row['Ocupacion'] || '',
                sueldos: sueldosDecimoTercero, // Campo "Sueldos" del archivo XIII
                total_ganado: parseFloat(String(row['Total_ganado (Ejm.:1000.56)'] || row['Total_ganado'] || '0').replace(',', '.')) || 0,
                dias_laborados: parseInt(row['Días laborados (360 días equivalen a un año)'] || row['Dias laborados'] || '0') || 0,
                tipo_deposito: row['Tipo de Deposito(Pago Directo=P,Acreditación en Cuenta=A,Retencion Pago Directo=RP,Retencion Acreditación en Cuenta=RA)'] || row['Tipo de Deposito'] || '',
                jornada_parcial: row['Solo si el trabajador posee JORNADA PARCIAL PERMANENTE ponga una X'] || '',
                horas_jornada: row['DETERMINE EN HORAS LA JORNADA PARCIAL PERMANENTE SEMANAL ESTIPULADO EN EL CONTRATO'] || '',
                discapacidad: row['Solo si su trabajador posee algun tipo de discapacidad ponga una X'] || '',
                valor_retencion: parseFloat(String(row['Ingrese el valor retenido'] || '0').replace(',', '.')) || 0,
                mensualiza: row['SOLO SI SU TRABAJADOR MENSUALIZA EL PAGO DE LA DECIMOTERCERA REMUNERACIÓN PONGA UNA X'] || ''
            };
        }
    });
    
    return resultado;
}

function procesarDatosDecimoCuarto(datos) {
    var resultado = {};
    
    datos.forEach(function (row) {
        var cedulaRaw = String(row['Cédula (Ejm.:0502366503)'] || row['Cedula'] || row['CÉDULA'] || '').trim();
        var cedula = normalizarCedula(cedulaRaw);
        if (cedula) {
            resultado[cedula] = {
                nombres: row['Nombres'] || '',
                apellidos: row['Apellidos'] || '',
                genero: row['Genero (Masculino=M ó Femenino=F)'] || row['Genero'] || '',
                ocupacion: row['Ocupación(codigo iess)'] || row['Ocupacion'] || '',
                dias_laborados: parseInt(row['Días laborados (360 días equivalen a un año)'] || row['Dias laborados'] || '0') || 0,
                tipo_pago: row['Tipo de Pago(Pago Directo=P,Acreditación en Cuenta=A,Retencion Pago Directo=RP,Retencion Acreditación en Cuenta=RA)'] || row['Tipo de Pago'] || '',
                jornada_parcial: row['Solo si el trabajador posee JORNADA PARCIAL PERMANENTE ponga una X'] || '',
                horas_jornada: row['DETERMINE EN HORAS LA JORNADA PARCIAL PERMANENTE SEMANAL ESTIPULADO EN EL CONTRATO'] || '',
                discapacidad: row['Solo si su trabajador posee algun tipo de discapacidad ponga una X'] || '',
                fecha_jubilacion: row['Fecha de Jubilación'] || row['Fecha de Jubilacion'] || '',
                valor_retencion: parseFloat(String(row['valor Retencion'] || row['valor Retencion'] || '0').replace(',', '.')) || 0,
                mensualiza: row['SOLO SI SU TRABAJADOR MENSUALIZA EL PAGO DE LA DECIMOCUARTA REMUNERACIÓN PONGA UNA X'] || ''
            };
        }
    });
    
    return resultado;
}

/**
 * Procesa el CSV de Utilidades para obtener fondos_reserva_2021 y otros campos
 */
function procesarDatosUtilidadesCsv(datos) {
    var resultado = {};
    
    if (!Array.isArray(datos)) {
        return resultado;
    }
    
    datos.forEach(function (row) {
        var cedulaRaw = String(row['Cédula (Ejm.:0502366503)'] || row['Cedula'] || row['CÉDULA'] || '').trim();
        var cedula = normalizarCedula(cedulaRaw);
        if (cedula) {
            resultado[cedula] = {
                nombres: row['Nombres'] || '',
                apellidos: row['Apellidos'] || '',
                genero: row['Genero (Masculino=M ó Femenino=F)'] || row['Genero'] || '',
                ocupacion: row['Ocupación'] || row['Ocupacion'] || '',
                cargas_familiares: parseInt(row['Cargas familiares'] || '0') || 0,
                dias_laborados: parseInt(row['Días laborados (360 días equivalen a un año)'] || row['Dias laborados'] || '0') || 0,
                tipo_pago_utilidad: row['Tipo de Pago Utilidad(Pago Directo=P, Depósito MDT=D Para Declaraciones < 2015 y Depósito Empresa = E para Declaraciones >= 2015, Acreditación en Cuenta=A, Retención Pago Directo=RP, Retención Depósito MDT=RD, Retención Acreditación en Cuenta=RA)'] || row['Tipo de Pago Utilidad'] || '',
                jornada_parcial: row['JORNADA PARCIAL PERMANENTE(Ponga una X si el trabajador tiene un JORNADA PARCIAL PERMANENTE)'] || '',
                horas_jornada: row['DETERMINE EN HORAS LA JORNADA PARCIAL PERMANENTE SEMANAL ESTIPULADO EN EL CONTRATO'] || '',
                discapacidad: row['DISCAPACITADOS(Ponga una X si el trabajador tienediscapacidad)'] || '',
                ruc_empresa_complementaria: row['RUC DE LA EMPRESA COMPLEMENTARIA O DE UNIFICACION'] || '',
                decimo_tercero_2021: parseFloat(String(row['DECIMOTERCERO VALOR PROPORCIONAL AL TIEMPO LABORADO 2021'] || '0').replace(',', '.')) || 0,
                decimo_cuarto_2021: parseFloat(String(row['DECIMOCUARTO VALOR PROPORCIONAL AL TIEMPO LABORADO DEL 2021'] || '0').replace(',', '.')) || 0,
                salarios_percibidos_2021: parseFloat(String(row['SALARIOS PERCIBIDOS 2021'] || '0').replace(',', '.')) || 0,
                fondos_reserva_2021: parseFloat(String(row['FONDOS DE RESERVA 2021'] || '0').replace(',', '.')) || 0,
                comisiones_2021: parseFloat(String(row['COMISIONES DEL 2021'] || '0').replace(',', '.')) || 0,
                beneficios_adicionales_2021: parseFloat(String(row['BENEFICIOS ADICIONALES EN EFECTIVO 2021'] || '0').replace(',', '.')) || 0,
                anticipo_utilidad: parseFloat(String(row['Anticipo de Utilidad'] || '0').replace(',', '.')) || 0,
                retencion_judicial: parseFloat(String(row['Retencion Judicial'] || '0').replace(',', '.')) || 0,
                impuesto_retencion: parseFloat(String(row['Impuesto Retencion '] || row['Impuesto Retencion'] || '0').replace(',', '.')) || 0,
                informacion_mdt: row['Información MDT(No ingrese datos en esta columna)'] || '',
                tipo_pago_salario_digno: row['Tipo de Pago Salario Digno(Pago Directo=P, Depósito MDT=D Para Declaraciones < 2015 y Depósito Empresa = E para Declaraciones >= 2015, Acreditación en Cuenta=A)'] || ''
            };
        }
    });
    
    return resultado;
}

/**
 * Combina datos de utilidades del Excel y del CSV
 */
function combinarDatosUtilidades(utilidadesExcel, utilidadesCsv) {
    var resultado = {};
    var todasLasCedulas = new Set();
    
    // Recopilar todas las cédulas
    Object.keys(utilidadesExcel).forEach(function(ced) { todasLasCedulas.add(ced); });
    Object.keys(utilidadesCsv).forEach(function(ced) { todasLasCedulas.add(ced); });
    
    // Combinar datos
    todasLasCedulas.forEach(function(cedula) {
        var excel = utilidadesExcel[cedula] || {};
        var csv = utilidadesCsv[cedula] || {};
        
        resultado[cedula] = {
            nombres: csv.nombres || '',
            apellidos: csv.apellidos || '',
            genero: csv.genero || '',
            ocupacion: csv.ocupacion || '',
            cargas_familiares: csv.cargas_familiares || 0,
            dias_laborados: csv.dias_laborados || 0,
            tipo_pago_utilidad: csv.tipo_pago_utilidad || '',
            jornada_parcial: csv.jornada_parcial || '',
            horas_jornada: csv.horas_jornada || '',
            discapacidad: csv.discapacidad || '',
            ruc_empresa_complementaria: csv.ruc_empresa_complementaria || '',
            decimo_tercero_2021: csv.decimo_tercero_2021 || 0,
            decimo_cuarto_2021: csv.decimo_cuarto_2021 || 0,
            participacion_utilidades_2022: excel.participacion_utilidades_2022 || 0, // Del Excel
            salarios_percibidos_2021: csv.salarios_percibidos_2021 || 0,
            fondos_reserva_2021: csv.fondos_reserva_2021 || 0, // Del CSV (importante para cálculo)
            comisiones_2021: csv.comisiones_2021 || 0,
            beneficios_adicionales_2021: csv.beneficios_adicionales_2021 || 0,
            anticipo_utilidad: csv.anticipo_utilidad || 0,
            retencion_judicial: csv.retencion_judicial || 0,
            impuesto_retencion: csv.impuesto_retencion || 0,
            informacion_mdt: csv.informacion_mdt || '',
            tipo_pago_salario_digno: csv.tipo_pago_salario_digno || ''
        };
    });
    
    return resultado;
}

function procesarDatosUtilidades(datos) {
    var resultado = {};
    
    // Si datos es un objeto (viene del Excel procesado), ya está en el formato correcto
    if (datos && typeof datos === 'object' && !Array.isArray(datos)) {
        // Ya viene procesado del Excel con participacion_utilidades_2022 calculado
        // Solo necesitamos convertirlo al formato esperado
        Object.keys(datos).forEach(function(cedula) {
            var utilidadData = datos[cedula];
            resultado[cedula] = {
                nombres: '',
                apellidos: '',
                genero: '',
                ocupacion: '',
                cargas_familiares: 0,
                dias_laborados: 0,
                tipo_pago_utilidad: '',
                jornada_parcial: '',
                horas_jornada: '',
                discapacidad: '',
                ruc_empresa_complementaria: '',
                decimo_tercero_2021: 0,
                decimo_cuarto_2021: 0,
                participacion_utilidades_2022: utilidadData.participacion_utilidades_2022 || 0, // Suma de las dos columnas
                salarios_percibidos_2021: 0,
                fondos_reserva_2021: 0,
                comisiones_2021: 0,
                beneficios_adicionales_2021: 0,
                anticipo_utilidad: 0,
                retencion_judicial: 0,
                impuesto_retencion: 0,
                informacion_mdt: '',
                tipo_pago_salario_digno: ''
            };
        });
        return resultado;
    }
    
    // Si es un array (formato CSV antiguo), procesarlo como antes
    if (Array.isArray(datos)) {
        datos.forEach(function (row) {
            var cedulaRaw = String(row['Cédula (Ejm.:0502366503)'] || row['Cedula'] || row['CÉDULA'] || '').trim();
            var cedula = normalizarCedula(cedulaRaw);
            if (cedula) {
                resultado[cedula] = {
                    nombres: row['Nombres'] || '',
                    apellidos: row['Apellidos'] || '',
                    genero: row['Genero (Masculino=M ó Femenino=F)'] || row['Genero'] || '',
                    ocupacion: row['Ocupación'] || row['Ocupacion'] || '',
                    cargas_familiares: parseInt(row['Cargas familiares'] || '0') || 0,
                    dias_laborados: parseInt(row['Días laborados (360 días equivalen a un año)'] || row['Dias laborados'] || '0') || 0,
                    tipo_pago_utilidad: row['Tipo de Pago Utilidad(Pago Directo=P, Depósito MDT=D Para Declaraciones < 2015 y Depósito Empresa = E para Declaraciones >= 2015, Acreditación en Cuenta=A, Retención Pago Directo=RP, Retención Depósito MDT=RD, Retención Acreditación en Cuenta=RA)'] || row['Tipo de Pago Utilidad'] || '',
                    jornada_parcial: row['JORNADA PARCIAL PERMANENTE(Ponga una X si el trabajador tiene un JORNADA PARCIAL PERMANENTE)'] || '',
                    horas_jornada: row['DETERMINE EN HORAS LA JORNADA PARCIAL PERMANENTE SEMANAL ESTIPULADO EN EL CONTRATO'] || '',
                    discapacidad: row['DISCAPACITADOS(Ponga una X si el trabajador tienediscapacidad)'] || '',
                    ruc_empresa_complementaria: row['RUC DE LA EMPRESA COMPLEMENTARIA O DE UNIFICACION'] || '',
                    decimo_tercero_2021: parseFloat(String(row['DECIMOTERCERO VALOR PROPORCIONAL AL TIEMPO LABORADO 2021'] || '0').replace(',', '.')) || 0,
                    decimo_cuarto_2021: parseFloat(String(row['DECIMOCUARTO VALOR PROPORCIONAL AL TIEMPO LABORADO DEL 2021'] || '0').replace(',', '.')) || 0,
                    participacion_utilidades_2022: parseFloat(String(row['PARTICIPACION DE UTILIDADES 2022'] || '0').replace(',', '.')) || 0,
                    salarios_percibidos_2021: parseFloat(String(row['SALARIOS PERCIBIDOS 2021'] || '0').replace(',', '.')) || 0,
                    fondos_reserva_2021: parseFloat(String(row['FONDOS DE RESERVA 2021'] || '0').replace(',', '.')) || 0,
                    comisiones_2021: parseFloat(String(row['COMISIONES DEL 2021'] || '0').replace(',', '.')) || 0,
                    beneficios_adicionales_2021: parseFloat(String(row['BENEFICIOS ADICIONALES EN EFECTIVO 2021'] || '0').replace(',', '.')) || 0,
                    anticipo_utilidad: parseFloat(String(row['Anticipo de Utilidad'] || '0').replace(',', '.')) || 0,
                    retencion_judicial: parseFloat(String(row['Retencion Judicial'] || '0').replace(',', '.')) || 0,
                    impuesto_retencion: parseFloat(String(row['Impuesto Retencion '] || row['Impuesto Retencion'] || '0').replace(',', '.')) || 0,
                    informacion_mdt: row['Información MDT(No ingrese datos en esta columna)'] || '',
                    tipo_pago_salario_digno: row['Tipo de Pago Salario Digno(Pago Directo=P, Depósito MDT=D Para Declaraciones < 2015 y Depósito Empresa = E para Declaraciones >= 2015, Acreditación en Cuenta=A)'] || ''
                };
            }
        });
    }
    
    return resultado;
}

function combinarDatosRdep(consolidado, decimoTercero, decimoCuarto, utilidades) {
    var resultado = [];
    var mapaUnificado = {}; // Mapa para unificar por cédula normalizada
    
    // Función auxiliar para agregar datos al mapa unificado
    function agregarAlMapa(cedula, tipo, datos) {
        var cedulaNorm = normalizarCedula(cedula);
        if (!cedulaNorm) return;
        
        if (!mapaUnificado[cedulaNorm]) {
            mapaUnificado[cedulaNorm] = {
                cedula: cedulaNorm,
                consolidado: {},
                decimo_tercero: {},
                decimo_cuarto: {},
                utilidades: {}
            };
        }
        
        // Combinar datos (si ya hay datos, mantener los existentes y agregar los nuevos)
        if (tipo === 'consolidado') {
            // Preservar _sueldo_total y _sueldos_por_mes si ya existe y sumar si hay nuevo
            var existente = mapaUnificado[cedulaNorm].consolidado;
            var sueldoExistente = existente._sueldo_total || 0;
            var sueldoNuevo = datos._sueldo_total || 0;
            var sueldosPorMesExistente = existente._sueldos_por_mes || {};
            var sueldosPorMesNuevo = datos._sueldos_por_mes || {};
            
            // Combinar sueldos por mes
            var sueldosPorMesCombinado = Object.assign({}, sueldosPorMesExistente);
            Object.keys(sueldosPorMesNuevo).forEach(function(mes) {
                if (sueldosPorMesCombinado[mes]) {
                    sueldosPorMesCombinado[mes] += sueldosPorMesNuevo[mes];
                } else {
                    sueldosPorMesCombinado[mes] = sueldosPorMesNuevo[mes];
                }
            });
            
            mapaUnificado[cedulaNorm].consolidado = Object.assign({}, existente, datos);
            // Sumar sueldos si ambos tienen valores
            if (sueldoExistente > 0 && sueldoNuevo > 0) {
                mapaUnificado[cedulaNorm].consolidado._sueldo_total = sueldoExistente + sueldoNuevo;
            } else if (sueldoNuevo > 0) {
                mapaUnificado[cedulaNorm].consolidado._sueldo_total = sueldoNuevo;
            } else if (sueldoExistente > 0) {
                mapaUnificado[cedulaNorm].consolidado._sueldo_total = sueldoExistente;
            }
            // Preservar sueldos por mes combinados
            mapaUnificado[cedulaNorm].consolidado._sueldos_por_mes = sueldosPorMesCombinado;
        } else if (tipo === 'decimo_tercero') {
            // Si ya hay datos, combinar (priorizar los que tienen valores)
            var existente = mapaUnificado[cedulaNorm].decimo_tercero;
            mapaUnificado[cedulaNorm].decimo_tercero = Object.assign({}, existente, datos);
        } else if (tipo === 'decimo_cuarto') {
            var existente = mapaUnificado[cedulaNorm].decimo_cuarto;
            mapaUnificado[cedulaNorm].decimo_cuarto = Object.assign({}, existente, datos);
        } else if (tipo === 'utilidades') {
            var existente = mapaUnificado[cedulaNorm].utilidades;
            mapaUnificado[cedulaNorm].utilidades = Object.assign({}, existente, datos);
        }
    }
    
    // Procesar consolidado
    Object.keys(consolidado).forEach(function (cedula) {
        agregarAlMapa(cedula, 'consolidado', consolidado[cedula]);
    });
    
    // Procesar décimo tercero
    Object.keys(decimoTercero).forEach(function (cedula) {
        agregarAlMapa(cedula, 'decimo_tercero', decimoTercero[cedula]);
    });
    
    // Procesar décimo cuarto
    Object.keys(decimoCuarto).forEach(function (cedula) {
        agregarAlMapa(cedula, 'decimo_cuarto', decimoCuarto[cedula]);
    });
    
    // Procesar utilidades
    Object.keys(utilidades).forEach(function (cedula) {
        agregarAlMapa(cedula, 'utilidades', utilidades[cedula]);
    });
    
    // Convertir mapa a array y obtener datos principales
    // SOLO incluir registros que tengan datos en el consolidado
    Object.keys(mapaUnificado).forEach(function (cedulaNorm) {
        var registro = mapaUnificado[cedulaNorm];
        
        // Verificar si tiene datos en el consolidado (sueldo total > 0 o tiene algún dato del consolidado)
        var tieneDatosConsolidado = false;
        
        // Verificar si tiene sueldo total del consolidado
        if (registro.consolidado && registro.consolidado._sueldo_total && registro.consolidado._sueldo_total > 0) {
            tieneDatosConsolidado = true;
        }
        
        // O también verificar si tiene cualquier dato del consolidado (no solo sueldo)
        if (!tieneDatosConsolidado && registro.consolidado) {
            var tieneDatos = Object.keys(registro.consolidado).length > 0;
            // Excluir propiedades internas como _cedula_col
            var keysReales = Object.keys(registro.consolidado).filter(function(k) {
                return !k.startsWith('_');
            });
            tieneDatosConsolidado = keysReales.length > 0;
        }
        
        // Solo agregar si tiene datos en el consolidado
        if (!tieneDatosConsolidado) {
            return; // Saltar este registro
        }
        
        // Obtener datos principales (prioridad: utilidades > decimo_cuarto > decimo_tercero > consolidado)
        // Nombres: usar los del consolidado si están separados, si no intentar separar del campo combinado
        var nombresConsolidado = registro.consolidado._nombres_consolidado || '';
        var apellidosConsolidado = registro.consolidado._apellidos_consolidado || '';
        
        // Si no se encontraron nombres/apellidos separados, buscar en campos comunes del consolidado
        if (!nombresConsolidado && !apellidosConsolidado) {
            nombresConsolidado = registro.consolidado['Nombre'] || registro.consolidado['NOMBRE'] || 
                                registro.consolidado['Nombres'] || registro.consolidado['NOMBRES'] || 
                                registro.consolidado['Trabajador'] || registro.consolidado['TRABAJADOR'] || '';
            apellidosConsolidado = registro.consolidado['Apellidos'] || registro.consolidado['APELLIDOS'] || '';
            
            // Si no hay apellidos separados pero hay un campo de nombre completo, intentar separarlo
            // En Excel generalmente viene "APELLIDOS NOMBRES"
            if (!apellidosConsolidado && nombresConsolidado) {
                var nombreCompleto = nombresConsolidado.trim();
                var partes = nombreCompleto.split(/\s+/);
                if (partes.length >= 2) {
                    // Los primeros elementos son apellidos, el último es nombre
                    apellidosConsolidado = partes.slice(0, -1).join(' ');
                    nombresConsolidado = partes[partes.length - 1];
                }
            }
        }
        
        registro.nombres = registro.utilidades.nombres || registro.decimo_cuarto.nombres || 
                           registro.decimo_tercero.nombres || nombresConsolidado;
        registro.apellidos = registro.utilidades.apellidos || registro.decimo_cuarto.apellidos || 
                            registro.decimo_tercero.apellidos || apellidosConsolidado;
        registro.genero = registro.utilidades.genero || registro.decimo_cuarto.genero || 
                         registro.decimo_tercero.genero || '';
        registro.ocupacion = registro.utilidades.ocupacion || registro.decimo_cuarto.ocupacion || 
                            registro.decimo_tercero.ocupacion || '';
        
        // Guardar días laborados del consolidado
        registro._dias_laborados_consolidado = registro.consolidado._dias_laborados || 0;
        
        resultado.push(registro);
    });
    
    return resultado;
}

function crearGridRdep(rows) {
    var model = [
        { 
            label: 'Cédula', name: 'cedula', width: 30, align: "center",
            formatter: function(cellvalue, options, rowObject) {
                // Si es la fila de totales, mostrar vacío
                if (rowObject._es_total || cellvalue === 'ZZZZZZZZZZ') {
                    return '';
                }
                return cellvalue;
            }
        },
        { label: 'Nombres', name: 'nombres', width: 40, align: "left" },
        { label: 'Apellidos', name: 'apellidos', width: 40, align: "left" },
        { label: 'Género', name: 'genero', width: 20, align: "center" },
        { label: 'Ocupación', name: 'ocupacion', width: 40, align: "left" },
        { label: 'Último Aporte', name: 'ultimo_aporte', width: 30, align: "center" },
        { label: 'Días Laborados', name: 'dias_laborados', width: 25, align: "center" },
        { label: 'Cargas Familiares', name: 'cargas_familiares', width: 25, align: "center" },
        { 
            label: 'Sueldos', name: 'sueldos_total', width: 30, align: "right", 
            formatter: 'currency', formatoptions: { prefix: '', thousandsSeparator: ',', decimalSeparator: '.' } 
        },
        { 
            label: 'Décimo Tercero', name: 'decimo_tercero_valor', width: 30, align: "right", 
            formatter: 'currency', formatoptions: { prefix: '', thousandsSeparator: ',', decimalSeparator: '.' } 
        },
        { 
            label: 'Décimo Cuarto', name: 'decimo_cuarto_valor', width: 30, align: "right", 
            formatter: 'currency', formatoptions: { prefix: '', thousandsSeparator: ',', decimalSeparator: '.' } 
        },
        { 
            label: 'Utilidades', name: 'utilidades_valor', width: 30, align: "right", 
            formatter: 'currency', formatoptions: { prefix: '', thousandsSeparator: ',', decimalSeparator: '.' } 
        },
        { 
            label: 'Fondos Reserva', name: 'fondos_reserva', width: 30, align: "right", 
            formatter: 'currency', formatoptions: { prefix: '', thousandsSeparator: ',', decimalSeparator: '.' } 
        },
        { 
            label: 'Total', name: 'total', width: 35, align: "right", 
            formatter: 'currency', formatoptions: { prefix: '', thousandsSeparator: ',', decimalSeparator: '.' } 
        }
    ];
    
    // Preparar datos para el grid
    // Obtener año seleccionado para calcular décimo cuarto
    var anioSeleccionado = parseInt($('#anioRdep').val()) || new Date().getFullYear();
    var sueldoBasicoAnio = obtenerSueldoBasicoPorAnio(anioSeleccionado);
    
    var datosGrid = rows.map(function (r) {
        // Décimo tercero: dividir entre 12 si viene el total_ganado
        var decimoTerceroTotal = r.decimo_tercero.total_ganado || 0;
        var decimoTerceroVal = decimoTerceroTotal > 0 ? (decimoTerceroTotal / 12) : 0;
        
        var decimoTercero2021 = r.utilidades.decimo_tercero_2021 || 0;
        
        // Décimo cuarto: calcular con fórmula (Sueldo Básico / 360) * Días Laborados
        // Los días laborados vienen del archivo de Décimo Cuarto (XIV)
        var diasLaboradosDecimoCuarto = r.decimo_cuarto.dias_laborados || 0;
        var decimoCuarto2021 = 0;
        if (sueldoBasicoAnio > 0 && diasLaboradosDecimoCuarto > 0) {
            decimoCuarto2021 = (sueldoBasicoAnio / 360) * diasLaboradosDecimoCuarto;
        }
        var utilidadesVal = r.utilidades.participacion_utilidades_2022 || 0;
        var sueldosTotal = r.consolidado._sueldo_total || 0; // Suma de sueldos del consolidado
        
        // Calcular fondos de reserva
        var fondosReservaUtilidades = parseFloat(r.utilidades.fondos_reserva_2021) || 0;
        var fondosReserva = 0;
        
        // console.log('Calculando fondos reserva para cédula:', r.cedula, 'Valor en utilidades:', fondosReservaUtilidades, 'Sueldos total:', sueldosTotal);
        
        if (fondosReservaUtilidades > 0) {
            // Si hay valor en utilidades, calcular automáticamente: sueldos * 8.33%
            fondosReserva = sueldosTotal * 0.0833;
            // console.log('Fondos reserva calculado con utilidades:', fondosReserva);
        } else {
            // Si no hay valor en utilidades (0 o vacío), calcular con fecha de aviso de entrada
            var avisosEntrada = archivosRdep.avisosEntrada ? archivosRdep.avisosEntrada.datos : {};
            var fechaAvisoStr = avisosEntrada[r.cedula] || '';
            
            // console.log('Buscando fecha aviso para cédula:', r.cedula, 'Fecha encontrada:', fechaAvisoStr, 'Consolidado existe:', !!r.consolidado, 'Sueldos por mes existe:', !!(r.consolidado && r.consolidado._sueldos_por_mes));
            
            if (fechaAvisoStr && r.consolidado && r.consolidado._sueldos_por_mes) {
                // Parsear fecha de aviso de entrada (puede ser DD/MM/YYYY o YYYY-MM-DD)
                var fechaAviso = null;
                if (fechaAvisoStr.includes('/')) {
                    var partes = fechaAvisoStr.split('/');
                    if (partes.length === 3) {
                        // Formato DD/MM/YYYY
                        var dia = parseInt(partes[0], 10);
                        var mes = parseInt(partes[1], 10);
                        var anio = parseInt(partes[2], 10);
                        fechaAviso = new Date(anio, mes - 1, dia);
                    }
                } else if (fechaAvisoStr.includes('-')) {
                    fechaAviso = new Date(fechaAvisoStr);
                }
                
                if (fechaAviso && !isNaN(fechaAviso.getTime())) {
                    // Calcular fecha de inicio (fecha aviso + 1 año)
                    var fechaInicio = new Date(fechaAviso);
                    fechaInicio.setFullYear(fechaInicio.getFullYear() + 1);
                    
                    // Obtener año seleccionado
                    var anioSeleccionado = parseInt($('#anioRdep').val()) || new Date().getFullYear();
                    
                    // Si la fecha de inicio está dentro del año seleccionado
                    if (fechaInicio.getFullYear() === anioSeleccionado || fechaInicio.getFullYear() < anioSeleccionado) {
                        var sueldosParaFondos = 0;
                        var sueldosPorMes = r.consolidado._sueldos_por_mes || {};
                        
                        // Calcular desde el mes de inicio hasta diciembre del año seleccionado
                        var mesInicio = 1;
                        if (fechaInicio.getFullYear() === anioSeleccionado) {
                            mesInicio = fechaInicio.getMonth() + 1; // Mes 1-12
                        }
                        var mesFin = 12;
                        
                        // console.log('Calculando fondos desde mes', mesInicio, 'hasta', mesFin, 'para cédula', r.cedula, 'Sueldos por mes:', sueldosPorMes);
                        
                        for (var mes = mesInicio; mes <= mesFin; mes++) {
                            if (sueldosPorMes[mes] !== undefined && sueldosPorMes[mes] !== null) {
                                var sueldoMes = parseFloat(sueldosPorMes[mes]) || 0;
                                sueldosParaFondos += sueldoMes;
                                // console.log('Mes', mes, 'Sueldo:', sueldoMes, 'Suma acumulada:', sueldosParaFondos);
                            }
                        }
                        
                        // Calcular 8.33% de los sueldos desde fecha + 1 año
                        if (sueldosParaFondos > 0) {
                            fondosReserva = sueldosParaFondos * 0.0833;
                            // console.log('✓ Fondos reserva calculado para', r.cedula, ':', fondosReserva.toFixed(2), 'Sueldos desde mes', mesInicio, ':', sueldosParaFondos.toFixed(2));
                        } else {
                            // console.log('✗ No hay sueldos para fondos. Sueldos acumulados:', sueldosParaFondos, 'Sueldos por mes:', sueldosPorMes);
                        }
                    } else {
                        // console.log('✗ Fecha inicio no está en año seleccionado. Fecha inicio año:', fechaInicio.getFullYear(), 'Año seleccionado:', anioSeleccionado);
                    }
                } else {
                    // console.log('✗ Fecha de aviso no válida o no parseada. Fecha string:', fechaAvisoStr, 'Fecha parseada:', fechaAviso);
                }
            } else {
                if (!fechaAvisoStr) {
                    // console.log('✗ No se encontró fecha de aviso para cédula:', r.cedula);
                }
                if (!r.consolidado) {
                    // console.log('✗ No hay datos de consolidado para cédula:', r.cedula);
                } else if (!r.consolidado._sueldos_por_mes) {
                    // console.log('✗ No hay sueldos por mes en consolidado para cédula:', r.cedula, 'Keys consolidado:', Object.keys(r.consolidado));
                }
            }
        }
        var comisiones = r.utilidades.comisiones_2021 || 0;
        var beneficios = r.utilidades.beneficios_adicionales_2021 || 0;
        var anticipo = r.utilidades.anticipo_utilidad || 0;
        var retencionJudicial = r.utilidades.retencion_judicial || 0;
        var impuestoRetencion = r.utilidades.impuesto_retencion || 0;
        var total = decimoTerceroVal + decimoTercero2021 + decimoCuarto2021 + utilidadesVal + 
                   sueldosTotal + fondosReserva + beneficios - anticipo - retencionJudicial - impuestoRetencion;
        
        // Días laborados: SIEMPRE del consolidado
        var diasLaborados = r._dias_laborados_consolidado || 0;
        var cargasFamiliares = r.utilidades.cargas_familiares || 0;
        
        return {
            cedula: r.cedula,
            nombres: r.nombres,
            apellidos: r.apellidos,
            genero: r.genero,
            ocupacion: r.ocupacion,
            ultimo_aporte: r.consolidado._ultimo_mes_aporte || '',
            dias_laborados: diasLaborados,
            cargas_familiares: cargasFamiliares,
            decimo_tercero_valor: decimoTerceroVal,
            decimo_cuarto_valor: decimoCuarto2021,
            utilidades_valor: utilidadesVal,
            sueldos_total: sueldosTotal,
            fondos_reserva: fondosReserva,
            total: total,
            _datos_completos: r // Guardar datos completos para exportación
        };
    });
    
    // Calcular totales
    var totales = {
        sueldos_total: 0,
        decimo_tercero_valor: 0,
        decimo_cuarto_valor: 0,
        utilidades_valor: 0,
        fondos_reserva: 0,
        total: 0
    };
    
    datosGrid.forEach(function(row) {
        totales.sueldos_total += parseFloat(row.sueldos_total) || 0;
        totales.decimo_tercero_valor += parseFloat(row.decimo_tercero_valor) || 0;
        totales.decimo_cuarto_valor += parseFloat(row.decimo_cuarto_valor) || 0;
        totales.utilidades_valor += parseFloat(row.utilidades_valor) || 0;
        totales.fondos_reserva += parseFloat(row.fondos_reserva) || 0;
        totales.total += parseFloat(row.total) || 0;
    });
    
    // Separar datos normales de totales
    var datosNormales = datosGrid.filter(function(row) {
        return !row._es_total;
    });
    
    // Agregar fila de totales al final (usar cédula 'ZZZZZZZZZZ' para asegurar que quede al final al ordenar)
    datosNormales.push({
        cedula: 'ZZZZZZZZZZ',
        nombres: '<b>TOTALES</b>',
        apellidos: '',
        genero: '',
        ocupacion: '',
        ultimo_aporte: '',
        dias_laborados: '',
        cargas_familiares: '',
        sueldos_total: totales.sueldos_total,
        decimo_tercero_valor: totales.decimo_tercero_valor,
        decimo_cuarto_valor: totales.decimo_cuarto_valor,
        utilidades_valor: totales.utilidades_valor,
        fondos_reserva: totales.fondos_reserva,
        total: totales.total,
        _es_total: true
    });
    
    datosGrid = datosNormales; // Usar datos con totales al final
    
    if (!gridRdep) {
        gridRdep = $("#gridRdep");
        gridRdep.createGrid({
            stateCol: 'cedula',
            height: 400,
            caption: 'RDEP - Retención en la Fuente de Empleados',
            rowNum: 10000000,
            rownumbers: false,
            sortname: 'cedula',
            sortorder: 'asc',
            colModel: model,
            gridComplete: function() {
                // Resaltar fila de totales y moverla al final
                var grid = $(this);
                var rows = grid.jqGrid('getDataIDs');
                rows.forEach(function(rowId) {
                    var rowData = grid.jqGrid('getRowData', rowId);
                    if (rowData.nombres && rowData.nombres.indexOf('TOTALES') > -1) {
                        $('#' + rowId).css({
                            'background-color': '#e8f4f8',
                            'font-weight': 'bold'
                        });
                        // Ocultar la cédula en la fila de totales
                        $('#' + rowId + ' td[aria-describedby="gridRdep_cedula"]').text('');
                    }
                });
            },
            sortable: {
                update: function(perm) {
                    // Asegurar que la fila de totales siempre quede al final después de ordenar
                    var $grid = $(this);
                    var totalesRow = $grid.find('tr:contains("TOTALES")');
                    if (totalesRow.length && totalesRow.index() !== $grid.find('tbody tr').length - 1) {
                        $grid.find('tbody').append(totalesRow);
                    }
                }
            }
        }, true, "pagerRdep", { refresh: false, view: true })
            .gridButtonsAdd([
                {
                    caption: 'Exportar Grid Excel', buttonicon: 'glyphicon glyphicon-download', onClickButton: function () {
                        exportarGridExcel();
                    }
                },
                {
                    caption: 'Exportar RDEP Formato', buttonicon: 'glyphicon glyphicon-file', onClickButton: function () {
                        exportarRdepExcel();
                    }
                },
                {
                    caption: 'Formato Aviso Entrada', buttonicon: 'glyphicon glyphicon-list-alt', onClickButton: function () {
                        exportarExcelSinFondosReserva();
                    }
                }
            ]);
    }
    
    gridRdep.setRows(datosGrid || []);
    
    // Asegurar que la fila de totales quede al final después de cargar
    setTimeout(function() {
        var $grid = $('#gridRdep');
        var totalesRow = null;
        var tbody = $grid.find('tbody[role="rowgroup"]');
        
        // Buscar y mover fila de totales al final
        tbody.find('tr').each(function() {
            var $row = $(this);
            var nombreCell = $row.find('td[aria-describedby="gridRdep_nombres"]');
            if (nombreCell.length && nombreCell.html() && nombreCell.html().indexOf('TOTALES') > -1) {
                totalesRow = $row;
                $row.css({
                    'background-color': '#e8f4f8',
                    'font-weight': 'bold'
                });
                // Ocultar cédula
                $row.find('td[aria-describedby="gridRdep_cedula"]').text('');
            }
        });
        
        // Mover al final si existe
        if (totalesRow && totalesRow.length) {
            tbody.append(totalesRow);
        }
    }, 200);
}

function exportarGridExcel() {
    if (!gridRdep || !datosRdep || datosRdep.length === 0) {
        $.alert('No hay datos para exportar', null, 'alert');
        return;
    }
    
    // Generar Excel tal cual como está en el grid
    var wb = XLSX.utils.book_new();
    var datosExcel = [];
    
    // Encabezados según el grid (sin comisiones)
    datosExcel.push([
        'Cédula',
        'Nombres',
        'Apellidos',
        'Género',
        'Ocupación',
        'Último Aporte',
        'Días Laborados',
        'Cargas Familiares',
        'Sueldos',
        'Décimo Tercero',
        'Décimo Cuarto',
        'Utilidades',
        'Fondos Reserva',
        'Total'
    ]);
    
    // Obtener datos del grid (filtrar la fila de totales)
    var rowIds = gridRdep.jqGrid('getDataIDs');
    var totalesRow = null;
    
    rowIds.forEach(function(rowId) {
        var rowData = gridRdep.jqGrid('getRowData', rowId);
        
        // Separar fila de totales
        if (rowData.cedula === 'ZZZZZZZZZZ' || (rowData.nombres && rowData.nombres.indexOf('TOTALES') > -1)) {
            totalesRow = rowData;
            return;
        }
        
        // Agregar datos normales
        datosExcel.push([
            rowData.cedula,
            rowData.nombres,
            rowData.apellidos,
            rowData.genero,
            rowData.ocupacion,
            rowData.ultimo_aporte || '',
            rowData.dias_laborados || '',
            rowData.cargas_familiares || '',
            parseFloat(rowData.sueldos_total) || 0,
            parseFloat(rowData.decimo_tercero_valor) || 0,
            parseFloat(rowData.decimo_cuarto_valor) || 0,
            parseFloat(rowData.utilidades_valor) || 0,
            parseFloat(rowData.fondos_reserva) || 0,
            parseFloat(rowData.total) || 0
        ]);
    });
    
    // Agregar fila de totales al final
    if (totalesRow) {
        datosExcel.push([
            '',
            'TOTALES',
            '',
            '',
            '',
            '',
            '',
            '',
            parseFloat(totalesRow.sueldos_total) || 0,
            parseFloat(totalesRow.decimo_tercero_valor) || 0,
            parseFloat(totalesRow.decimo_cuarto_valor) || 0,
            parseFloat(totalesRow.utilidades_valor) || 0,
            parseFloat(totalesRow.fondos_reserva) || 0,
            parseFloat(totalesRow.total) || 0
        ]);
    }
    
    var ws = XLSX.utils.aoa_to_sheet(datosExcel);
    
    // Ajustar ancho de columnas
    ws['!cols'] = [
        { wch: 12 },  // Cédula
        { wch: 20 },  // Nombres
        { wch: 20 },  // Apellidos
        { wch: 8 },   // Género
        { wch: 20 },  // Ocupación
        { wch: 12 },  // Último Aporte
        { wch: 12 },  // Días Laborados
        { wch: 12 },  // Cargas Familiares
        { wch: 15 },  // Sueldos
        { wch: 15 },  // Décimo Tercero
        { wch: 15 },  // Décimo Cuarto
        { wch: 15 },  // Utilidades
        { wch: 15 },  // Fondos Reserva
        { wch: 15 }   // Total
    ];
    
    XLSX.utils.book_append_sheet(wb, ws, 'RDEP Grid');
    
    // Descargar
    var anio = $('#anioRdep').val() || new Date().getFullYear();
    var nombreArchivo = 'RDEP_Grid_' + anio + '_' + $.getDate() + '.xlsx';
    XLSX.writeFile(wb, nombreArchivo);
    $.alert('Archivo Excel del Grid generado correctamente', null, 'success');
}

function exportarRdepExcel() {
    if (!datosRdep || datosRdep.length === 0) {
        $.alert('No hay datos para exportar', null, 'alert');
        return;
    }
    
    // Generar Excel con formato RDEP completo
    var wb = XLSX.utils.book_new();
    
    // Crear hoja de datos principal
    var datosExcel = [];
    
    // Encabezados según formato RDEP_FORMATO1.xlsx (orden exacto según el formato)
    // Columnas A-N: Información del trabajador
    // Columnas O-AE: Ingresos y deducciones
    // Columnas AF-AS: Gastos personales y cálculos
    datosExcel.push([
        // A: Es beneficiario de Galápagos
        'Es beneficiario de Galápagos',
        // B: Trabajador con enfermedades catastróficas
        'Trabajador con o a cargo de personas con enfermedades catastróficas, raras y/o huérfanas',
        // C: Número de cargas familiares (AMARILLO)
        'Número de cargas familiares para rebaja de gastos personales',
        // D: Tipo de identificación (AMARILLO)
        'Tipo de identificación del trabajador',
        // E: Número de identificación (AMARILLO)
        'Número de identificación del trabajador',
        // F: Apellidos (AMARILLO)
        'Apellidos del trabajador',
        // G: Nombres (AMARILLO)
        'Nombres del trabajador',
        // H: Código del establecimiento
        'Código del establecimiento',
        // I: Tipo de residencia
        'Tipo de residencia',
        // J: País de residencia
        'País de residencia',
        // K: ¿Aplica convenio para evitar doble imposición?
        '¿Aplica convenio para evitar doble imposición?',
        // L: Condición del trabajador respecto a discapacidades
        'Condición del trabajador respecto a discapacidades',
        // M: Porcentaje de discapacidad
        'Porcentaje de discapacidad',
        // N: Tipo de identificación de la persona con discapacidad
        'Tipo de identificación de la persona con discapacidad a quien sustituye o representa',
        // O: Número de identificación de la persona con discapacidad
        'Número de identificación de la persona con discapacidad a quien sustituye o representa',
        // P: Sueldos, salarios (AMARILLO)
        'Sueldos, salarios y otros ingresos gravados de impuesto a la renta (materia gravada)',
        // Q: Otros ingresos gravados
        'Otros ingresos gravados de impuesto a la renta (materia no gravada de la seguridad social)',
        // R: Participación de utilidades (AMARILLO)
        'Participación de utilidades',
        // S: Ingresos gravados generados con otros empleadores
        'Ingresos gravados generados con otros empleadores',
        // T: Impuesto a la Renta asumido por este empleador
        'Impuesto a la Renta asumido por este empleador',
        // U: Décimo tercer sueldo (AMARILLO)
        'Décimo tercer sueldo',
        // V: Décimo cuarto sueldo (AMARILLO)
        'Décimo cuarto sueldo',
        // W: Fondo de reserva (AMARILLO)
        'Fondo de reserva',
        // X: Compensación Económica Salario Digno
        'Compensación Económica Salario Digno',
        // Y: Otros ingresos en relación de dependencia
        'Otros ingresos en relación de dependencia a que no constituyen materia gravada de impuesto a la renta',
        // Z: Ingresos gravados con el empleador
        'Ingresos gravados con el empleador',
        // AA: Tipo sistema salario neto
        'Tipo sistema salario neto',
        // AB: Aporte personal a la seguridad social (AMARILLO)
        'Aporte personal a la seguridad social con este empleador (únicamente pagado por el trabajador), aportes personales a las cajas Militar o Policial para',
        // AC: Aporte personal con otros empleadores
        'Aporte personal a la seguridad social con otros empleadores (únicamente pagado por el trabajador)',
        // AD: Gastos personales por vivienda
        'Gastos personales por vivienda',
        // AE: Gastos personales por salud
        'Gastos personales por salud',
        // AF: Gastos personales por educación arte y cultura
        'Gastos personales por educación arte y cultura',
        // AG: Gastos personales por alimentación
        'Gastos personales por alimentación',
        // AH: Gastos personales por vestimenta
        'Gastos personales por vestimenta',
        // AI: Gastos personales por turismo
        'Gastos personales por turismo',
        // AJ: Exoneración por discapacidad
        'Exoneración por discapacidad',
        // AK: Exoneración por tercera edad
        'Exoneración por tercera edad',
        // AL: Base imponible gravada
        'Base imponible gravada',
        // AM: Impuesto a la Renta causado
        'Impuesto a la Renta causado',
        // AN: Rebaja por gastos personales
        'Rebaja por gastos personales',
        // AO: Impuesto a la renta después de la rebaja de gastos personales
        'Impuesto a la renta despues de la rebaja de gastos personales',
        // AP: I. Renta retenido y asumido por otros empleadores
        'I. Renta retenido y asumido por otros empleadores',
        // AQ: I. Renta asumido por este empleador
        'I. Renta asumido por este empleador',
        // AR: I. Renta retenido al trabajador
        'I. Renta retenido al trabajador'
    ]);
    
    // Datos combinados de todos los archivos
    // Obtener año seleccionado para calcular décimo cuarto
    var anioSeleccionado = parseInt($('#anioRdep').val()) || new Date().getFullYear();
    var sueldoBasicoAnio = obtenerSueldoBasicoPorAnio(anioSeleccionado);
    
    var filaNumero = 2; // Empezar en fila 2 (fila 1 es encabezado)
    datosRdep.forEach(function (r) {
        // Décimo tercero: usar el mismo valor que está en el grid (total_ganado / 12)
        var decimoTerceroTotal = r.decimo_tercero.total_ganado || 0;
        var decimoTercerSueldo = decimoTerceroTotal > 0 ? (decimoTerceroTotal / 12) : 0;
        
        // Décimo cuarto: calcular con fórmula (Sueldo Básico / 360) * Días Laborados
        // Los días laborados vienen del archivo de Décimo Cuarto (XIV)
        var diasLaboradosDecimoCuarto = r.decimo_cuarto.dias_laborados || 0;
        var decimoCuarto2021 = 0;
        if (sueldoBasicoAnio > 0 && diasLaboradosDecimoCuarto > 0) {
            decimoCuarto2021 = (sueldoBasicoAnio / 360) * diasLaboradosDecimoCuarto;
        }
        
        // Utilidades
        var utilidades = r.utilidades.participacion_utilidades_2022 || 0;
        
        // Sueldos
        var sueldos = r.consolidado._sueldo_total || 0; // Suma de sueldos del consolidado
        
        // Calcular fondos de reserva: si hay valor en utilidades, calcular 8.33% de sueldos
        var fondosReservaUtilidades = parseFloat(r.utilidades.fondos_reserva_2021) || 0;
        var fondosReserva = 0;
        
        if (fondosReservaUtilidades > 0) {
            // Si hay valor en utilidades, calcular automáticamente: sueldos * 8.33%
            fondosReserva = sueldos * 0.0833;
        } else {
            // Si no hay valor en utilidades (0 o vacío), calcular con fecha de aviso de entrada
            var avisosEntrada = archivosRdep.avisosEntrada ? archivosRdep.avisosEntrada.datos : {};
            var fechaAvisoStr = avisosEntrada[r.cedula] || '';
            
            if (fechaAvisoStr && r.consolidado && r.consolidado._sueldos_por_mes) {
                // Parsear fecha de aviso de entrada (puede ser DD/MM/YYYY o YYYY-MM-DD)
                var fechaAviso = null;
                if (fechaAvisoStr.includes('/')) {
                    var partes = fechaAvisoStr.split('/');
                    if (partes.length === 3) {
                        // Formato DD/MM/YYYY
                        var dia = parseInt(partes[0], 10);
                        var mes = parseInt(partes[1], 10);
                        var anio = parseInt(partes[2], 10);
                        fechaAviso = new Date(anio, mes - 1, dia);
                    }
                } else if (fechaAvisoStr.includes('-')) {
                    fechaAviso = new Date(fechaAvisoStr);
                }
                
                if (fechaAviso && !isNaN(fechaAviso.getTime())) {
                    // Calcular fecha de inicio (fecha aviso + 1 año)
                    var fechaInicio = new Date(fechaAviso);
                    fechaInicio.setFullYear(fechaInicio.getFullYear() + 1);
                    
                    // Obtener año seleccionado
                    var anioSeleccionado = parseInt($('#anioRdep').val()) || new Date().getFullYear();
                    
                    // Si la fecha de inicio está dentro del año seleccionado
                    if (fechaInicio.getFullYear() === anioSeleccionado || fechaInicio.getFullYear() < anioSeleccionado) {
                        var sueldosParaFondos = 0;
                        var sueldosPorMes = r.consolidado._sueldos_por_mes || {};
                        
                        // Calcular desde el mes de inicio hasta diciembre del año seleccionado
                        var mesInicio = 1;
                        if (fechaInicio.getFullYear() === anioSeleccionado) {
                            mesInicio = fechaInicio.getMonth() + 1; // Mes 1-12
                        }
                        var mesFin = 12;
                        
                        for (var mes = mesInicio; mes <= mesFin; mes++) {
                            if (sueldosPorMes[mes] !== undefined && sueldosPorMes[mes] !== null) {
                                sueldosParaFondos += parseFloat(sueldosPorMes[mes]) || 0;
                            }
                        }
                        
                        // Calcular 8.33% de los sueldos desde fecha + 1 año
                        if (sueldosParaFondos > 0) {
                            fondosReserva = sueldosParaFondos * 0.0833;
                        }
                    }
                }
            }
        }
        
        var comisiones = r.utilidades.comisiones_2021 || 0;
        var beneficios = r.utilidades.beneficios_adicionales_2021 || 0;
        var anticipo = r.utilidades.anticipo_utilidad || 0;
        var retencionJudicial = r.utilidades.retencion_judicial || 0;
        var impuestoRetencion = r.utilidades.impuesto_retencion || 0;
        // Total calculado correctamente (sin duplicar décimo tercero)
        var total = decimoTercerSueldo + decimoCuarto2021 + utilidades + 
                   sueldos + fondosReserva + beneficios - anticipo - retencionJudicial - impuestoRetencion;
        
        // Obtener datos de cada fuente con prioridad
        // Días laborados: SIEMPRE del consolidado
        var diasLaborados = r._dias_laborados_consolidado || 0;
        var tipoPago = r.utilidades.tipo_pago_utilidad || r.decimo_cuarto.tipo_pago || r.decimo_tercero.tipo_deposito || '';
        var jornadaParcial = r.utilidades.jornada_parcial || r.decimo_cuarto.jornada_parcial || r.decimo_tercero.jornada_parcial || '';
        var horasJornada = r.utilidades.horas_jornada || r.decimo_cuarto.horas_jornada || r.decimo_tercero.horas_jornada || '';
        var discapacidad = r.utilidades.discapacidad || r.decimo_cuarto.discapacidad || r.decimo_tercero.discapacidad || '';
        var fechaJubilacion = r.decimo_cuarto.fecha_jubilacion || '';
        var valorRetencion = r.utilidades.impuesto_retencion || r.decimo_cuarto.valor_retencion || r.decimo_tercero.valor_retencion || 0;
        var mensualizaTercero = r.decimo_tercero.mensualiza || '';
        var mensualizaCuarto = r.decimo_cuarto.mensualiza || '';
        var cargasFamiliares = r.utilidades.cargas_familiares || 0;
        
        // Obtener último aporte del consolidado
        var ultimoAporte = r.consolidado._ultimo_mes_aporte || '';
        
        // Obtener suma de Individual (Aporte personal a la seguridad social)
        var aportePersonal = r.consolidado._individual_total || 0;
        
        // Función para formatear números a 2 decimales
        function formato2Decimales(valor) {
            var num = parseFloat(valor) || 0;
            return Math.round(num * 100) / 100; // Redondear a 2 decimales
        }
        
        // Valores para las columnas (según el formato RDEP) - con 2 decimales
        var otrosIngresosGravados = 0.00; // Columna Q
        var ingresosOtrosEmpleadores = 0.00; // Columna S
        var impuestoRentaAsumido = 0.00; // Columna T
        
        // Formatear valores a 2 decimales
        sueldos = formato2Decimales(sueldos);
        utilidades = formato2Decimales(utilidades);
        decimoTercerSueldo = formato2Decimales(decimoTercerSueldo);
        decimoCuarto2021 = formato2Decimales(decimoCuarto2021);
        fondosReserva = formato2Decimales(fondosReserva);
        aportePersonal = formato2Decimales(aportePersonal);
        
        // Valores para cálculos adicionales
        var aportePersonalOtrosEmpleadores = 0.00; // Columna AC
        var exoneracionDiscapacidad = 0.00; // Columna AJ
        var exoneracionTerceraEdad = 0.00; // Columna AK
        
        // Preparar fórmulas para Excel
        // Columna Z (índice 25): Ingresos gravados con el empleador = P+Q+R+T
        // P = columna 15 (índice 15), Q = 16, R = 17, T = 19
        var formulaIngresosGravados = '=P' + filaNumero + '+Q' + filaNumero + '+R' + filaNumero + '+T' + filaNumero;
        
        // Columna AL (índice 37): Base imponible gravada = Z+S-AB-AC-AJ-AK
        // Z = 25, S = 18, AB = 27, AC = 28, AJ = 35, AK = 36
        var formulaBaseImponible = '=Z' + filaNumero + '+S' + filaNumero + '-AB' + filaNumero + '-AC' + filaNumero + '-AJ' + filaNumero + '-AK' + filaNumero;
        
        datosExcel.push([
            // A: Es beneficiario de Galápagos
            'NO',
            // B: Trabajador con enfermedades catastróficas
            'NO',
            // C: Número de cargas familiares (AMARILLO)
            cargasFamiliares,
            // D: Tipo de identificación (AMARILLO) - "C" para cédula
            'C',
            // E: Número de identificación (AMARILLO)
            r.cedula,
            // F: Apellidos (AMARILLO)
            r.apellidos,
            // G: Nombres (AMARILLO)
            r.nombres,
            // H: Código del establecimiento
            '001',
            // I: Tipo de residencia
            '01',
            // J: País de residencia
            '593',
            // K: ¿Aplica convenio para evitar doble imposición?
            'NA',
            // L: Condición del trabajador respecto a discapacidades
            '01',
            // M: Porcentaje de discapacidad
            '0',
            // N: Tipo de identificación de la persona con discapacidad
            'N',
            // O: Número de identificación de la persona con discapacidad
            '999',
            // P: Sueldos, salarios (AMARILLO)
            sueldos,
            // Q: Otros ingresos gravados
            otrosIngresosGravados,
            // R: Participación de utilidades (AMARILLO)
            utilidades,
            // S: Ingresos gravados generados con otros empleadores
            ingresosOtrosEmpleadores,
            // T: Impuesto a la Renta asumido por este empleador
            impuestoRentaAsumido,
            // U: Décimo tercer sueldo (AMARILLO)
            decimoTercerSueldo,
            // V: Décimo cuarto sueldo (AMARILLO)
            decimoCuarto2021,
            // W: Fondo de reserva (AMARILLO)
            fondosReserva,
            // X: Compensación Económica Salario Digno
            0.00,
            // Y: Otros ingresos en relación de dependencia
            0.00,
            // Z: Ingresos gravados con el empleador (fórmula: =P+Q+R+T)
            { f: formulaIngresosGravados },
            // AA: Tipo sistema salario neto
            1,
            // AB: Aporte personal a la seguridad social (AMARILLO)
            aportePersonal,
            // AC: Aporte personal con otros empleadores
            aportePersonalOtrosEmpleadores,
            // AD: Gastos personales por vivienda
            0.00,
            // AE: Gastos personales por salud
            0.00,
            // AF: Gastos personales por educación arte y cultura
            0.00,
            // AG: Gastos personales por alimentación
            0.00,
            // AH: Gastos personales por vestimenta
            0.00,
            // AI: Gastos personales por turismo
            0.00,
            // AJ: Exoneración por discapacidad
            exoneracionDiscapacidad,
            // AK: Exoneración por tercera edad
            exoneracionTerceraEdad,
            // AL: Base imponible gravada (fórmula: =Z+S-AB-AC-AJ-AK)
            { f: formulaBaseImponible },
            // AM: Impuesto a la Renta causado
            0.00,
            // AN: Rebaja por gastos personales
            0.00,
            // AO: Impuesto a la renta después de la rebaja de gastos personales
            0.00,
            // AP: I. Renta retenido y asumido por otros empleadores
            0.00,
            // AQ: I. Renta asumido por este empleador
            0.00,
            // AR: I. Renta retenido al trabajador
            0.00
        ]);
        
        filaNumero++; // Incrementar número de fila para la siguiente iteración
    });
    
    var ws = XLSX.utils.aoa_to_sheet(datosExcel);
    
    // Ajustar ancho de columnas según formato RDEP_FORMATO1.xlsx (44 columnas A-AR, sin AS Individual)
    var colWidths = [];
    for (var i = 0; i < 44; i++) {
        colWidths.push({ wch: 15 }); // Ancho estándar
    }
    // Ajustar anchos específicos para columnas importantes
    colWidths[0] = { wch: 20 };  // A: Es beneficiario de Galápagos
    colWidths[1] = { wch: 50 };  // B: Trabajador con enfermedades
    colWidths[2] = { wch: 15 };  // C: Cargas familiares
    colWidths[3] = { wch: 15 };  // D: Tipo identificación
    colWidths[4] = { wch: 20 };  // E: Número identificación
    colWidths[5] = { wch: 20 };  // F: Apellidos
    colWidths[6] = { wch: 20 };  // G: Nombres
    colWidths[15] = { wch: 20 }; // P: Sueldos
    colWidths[17] = { wch: 20 }; // R: Participación utilidades
    colWidths[20] = { wch: 18 }; // U: Décimo tercer sueldo
    colWidths[21] = { wch: 18 }; // V: Décimo cuarto sueldo
    colWidths[22] = { wch: 18 }; // W: Fondo de reserva
    colWidths[27] = { wch: 50 }; // AB: Aporte personal
    ws['!cols'] = colWidths;
    
    XLSX.utils.book_append_sheet(wb, ws, 'RDEP');
    
    // Obtener año seleccionado
    var anio = $('#anioRdep').val() || new Date().getFullYear();
    
    // Descargar con nombre similar al formato
    var nombreArchivo = 'RDEP_FORMATO1_' + anio + '_' + $.getDate() + '.xlsx';
    XLSX.writeFile(wb, nombreArchivo);
    $.alert('Archivo RDEP generado correctamente', null, 'success');
}

/**
 * Exporta un Excel con empleados que tienen Fondos de Reserva = 0
 * Formato: Cedula, Nombres, Apellidos, Aviso de entrada
 */
function exportarExcelSinFondosReserva() {
    if (!datosRdep || datosRdep.length === 0) {
        $.alert('No hay datos para exportar', null, 'alert');
        return;
    }
    
    // Filtrar empleados con fondos de reserva = 0 (excluir totales)
    var empleadosSinFondos = datosRdep.filter(function(r) {
        // Excluir fila de totales
        if (r.cedula === 'ZZZZZZZZZZ' || r._es_total) {
            return false;
        }
        
        // Calcular fondos de reserva igual que en el grid
        var fondosReservaUtilidades = parseFloat(r.utilidades.fondos_reserva_2021) || 0;
        var fondosReserva = 0;
        var sueldosTotal = r.consolidado._sueldo_total || 0;
        
        if (fondosReservaUtilidades > 0) {
            fondosReserva = sueldosTotal * 0.0833;
        } else {
            // Calcular con fecha de aviso de entrada
            var avisosEntrada = archivosRdep.avisosEntrada ? archivosRdep.avisosEntrada.datos : {};
            var fechaAvisoStr = avisosEntrada[r.cedula] || '';
            
            if (fechaAvisoStr && r.consolidado && r.consolidado._sueldos_por_mes) {
                var fechaAviso = null;
                if (fechaAvisoStr.includes('/')) {
                    var partes = fechaAvisoStr.split('/');
                    if (partes.length === 3) {
                        var dia = parseInt(partes[0], 10);
                        var mes = parseInt(partes[1], 10);
                        var anio = parseInt(partes[2], 10);
                        fechaAviso = new Date(anio, mes - 1, dia);
                    }
                } else if (fechaAvisoStr.includes('-')) {
                    fechaAviso = new Date(fechaAvisoStr);
                } else {
                    var fechaNum = parseFloat(fechaAvisoStr);
                    if (!isNaN(fechaNum) && fechaNum > 0 && fechaNum < 1000000) {
                        fechaAviso = excelSerialToDate(fechaNum);
                    }
                }
                
                if (fechaAviso && !isNaN(fechaAviso.getTime())) {
                    var fechaInicio = new Date(fechaAviso);
                    fechaInicio.setFullYear(fechaInicio.getFullYear() + 1);
                    
                    var anioSeleccionado = parseInt($('#anioRdep').val()) || new Date().getFullYear();
                    
                    if (fechaInicio.getFullYear() === anioSeleccionado || fechaInicio.getFullYear() < anioSeleccionado) {
                        var sueldosParaFondos = 0;
                        var sueldosPorMes = r.consolidado._sueldos_por_mes || {};
                        
                        var mesInicio = 1;
                        if (fechaInicio.getFullYear() === anioSeleccionado) {
                            mesInicio = fechaInicio.getMonth() + 1;
                        }
                        var mesFin = 12;
                        
                        for (var mes = mesInicio; mes <= mesFin; mes++) {
                            if (sueldosPorMes[mes] !== undefined && sueldosPorMes[mes] !== null) {
                                sueldosParaFondos += parseFloat(sueldosPorMes[mes]) || 0;
                            }
                        }
                        
                        if (sueldosParaFondos > 0) {
                            fondosReserva = sueldosParaFondos * 0.0833;
                        }
                    }
                }
            }
        }
        
        // Incluir solo si fondos de reserva = 0
        return fondosReserva === 0;
    });
    
    // Obtener avisos de entrada del grid (valores editados por el usuario)
    var avisosEntradaGrid = {};
    if (gridRdep && gridRdep.jqGrid) {
        var filas = gridRdep.jqGrid('getRowData');
        filas.forEach(function(fila) {
            if (fila.cedula && fila.cedula !== 'ZZZZZZZZZZ' && fila.aviso_entrada) {
                avisosEntradaGrid[fila.cedula] = fila.aviso_entrada;
            }
        });
    }
    
    // Obtener avisos de entrada del archivo original
    var avisosEntradaArchivo = archivosRdep.avisosEntrada ? archivosRdep.avisosEntrada.datos : {};
    
    // Generar Excel
    var wb = XLSX.utils.book_new();
    var datosExcel = [];
    
    // Encabezados
    datosExcel.push(['Cedula', 'Nombres', 'Apellidos', 'Aviso de entrada']);
    
    // Datos: empleados con fondos de reserva = 0
    empleadosSinFondos.forEach(function(r) {
        // Obtener aviso de entrada (prioridad: grid editado > archivo > vacío)
        var avisoEntrada = avisosEntradaGrid[r.cedula] || avisosEntradaArchivo[r.cedula] || '';
        
        // Formatear fecha si viene del archivo Excel (serial number)
        if (avisoEntrada && typeof avisoEntrada === 'number') {
            var fechaAviso = excelSerialToDate(avisoEntrada);
            if (fechaAviso && !isNaN(fechaAviso.getTime())) {
                avisoEntrada = formatearFecha(fechaAviso); // DD/MM/YYYY
            }
        }
        
        datosExcel.push([
            r.cedula || '',
            r.nombres || '',
            r.apellidos || '',
            avisoEntrada || ''
        ]);
    });
    
    var ws = XLSX.utils.aoa_to_sheet(datosExcel);
    
    // Ajustar ancho de columnas
    ws['!cols'] = [
        { wch: 15 }, // Cedula
        { wch: 30 }, // Nombres
        { wch: 30 }, // Apellidos
        { wch: 20 }  // Aviso de entrada
    ];
    
    XLSX.utils.book_append_sheet(wb, ws, 'Empleados Sin Fondos Reserva');
    
    // Obtener año seleccionado
    var anio = $('#anioRdep').val() || new Date().getFullYear();
    
    // Descargar
    var nombreArchivo = 'Empleados_Sin_Fondos_Reserva_' + anio + '_' + $.getDate() + '.xlsx';
    XLSX.writeFile(wb, nombreArchivo);
    
    if (empleadosSinFondos.length === 0) {
        $.alert('Archivo Excel generado con encabezados únicamente. No hay empleados con Fondos de Reserva = 0', null, 'info');
    } else {
        $.alert('Archivo Excel generado correctamente. Total de empleados: ' + empleadosSinFondos.length, null, 'success');
    }
}

