var gridDecimoCuartoIess;
// Mapa de formas de pago/actividad sectorial por cédula desde archivo IESS "Consulta rol de empleados"
var mapFormaPagoIess = {};
// Matriz del consolidado IESS cargado (para procesar al hacer clic en "Cargar")
var matConsolidadoIess = null;

function normalizaTexto(s) {
    return String(s || "").trim().toLowerCase();
}

// Leer archivo XLSX del IESS
document.addEventListener('DOMContentLoaded', function () {
    var input = document.getElementById('fileIess');
    if (input) {
        input.addEventListener('change', handleFileIess);
    }
    var inputPago = document.getElementById('fileIessPago');
    if (inputPago) {
        inputPago.addEventListener('change', handleFilePagoIess);
    }
    var btnCargar = document.getElementById('btnCargarIess');
    if (btnCargar) {
        btnCargar.addEventListener('click', procesarDecimoCuartoIess);
    }
    // Inicializar campos de fecha (1 de marzo del año actual hasta 28/29 de febrero del año siguiente)
    var hoy = new Date();
    var anioActual = hoy.getFullYear();
    var dateRolini = document.getElementById('dateRolini');
    var dateRolfin = document.getElementById('dateRolfin');
    if (dateRolini) {
        dateRolini.value = anioActual + '-03-01';
    }
    if (dateRolfin) {
        // 28 o 29 de febrero del año siguiente (verificar si es año bisiesto)
        var anioSiguiente = anioActual + 1;
        var esBisiesto = (anioSiguiente % 4 === 0 && anioSiguiente % 100 !== 0) || (anioSiguiente % 400 === 0);
        var diaFebrero = esBisiesto ? '29' : '28';
        dateRolfin.value = anioSiguiente + '-02-' + diaFebrero;
    }
    crearGridDecimoCuartoIess([]);
});

function handleFileIess(ev) {
    var f = ev.target.files[0];
    if (!f) return;

    var rd = new FileReader();
    rd.onload = function (e) {
        var wb = XLSX.read(new Uint8Array(e.target.result), { type: 'array' });
        var ws = wb.Sheets[wb.SheetNames[0]];
        matConsolidadoIess = XLSX.utils.sheet_to_json(ws, { header: 1, defval: "" });
        $.alert("Consolidado IESS cargado. Ahora presione Cargar para procesar.",null,'success');
    };
    rd.readAsArrayBuffer(f);
}

// Leer archivo de formas de pago / actividad sectorial IESS
function handleFilePagoIess(ev) {
    var f = ev.target.files[0];
    if (!f) return;

    var rd = new FileReader();
    rd.onload = function (e) {
        var wb = XLSX.read(new Uint8Array(e.target.result), { type: 'array' });
        var ws = wb.Sheets[wb.SheetNames[0]];
        var mat = XLSX.utils.sheet_to_json(ws, { header: 1, defval: "" });
        construirMapaFormasPago(mat);
        // Si ya hay datos cargados en el grid, los actualizamos
        if (gridDecimoCuartoIess && gridDecimoCuartoIess[0] && gridDecimoCuartoIess[0].p) {
            var rows = gridDecimoCuartoIess[0].p.data || [];
            fusionarFormasPagoIess(rows);
            crearGridDecimoCuartoIess(rows);
        }
        $.alert("Archivo de formas de pago cargado.",null,'success');
    };
    rd.readAsArrayBuffer(f);
}

// Procesa el consolidado actualmente cargado y llena el grid
function procesarDecimoCuartoIess() {
    if (!Array.isArray(matConsolidadoIess) || matConsolidadoIess.length === 0) {
        $.alert('Primero cargue el archivo de consolidado IESS.', null, 'alert');
        return;
    }
    var resumen = calcularDecimoCuartoDesdeConsolidado(matConsolidadoIess);
    if (!Array.isArray(resumen) || resumen.length === 0) {
        crearGridDecimoCuartoIess([]);
        $.alert('No se encontró información válida en el consolidado.', null, 'alert');
        return;
    }
    // Enriquecer con datos desde la BD (género, ocupación, forma de pago, mensualización, sueldo básico unificado)
    enriquecerConDatosBDCuarto(resumen, function (rowsCompletos) {
        fusionarFormasPagoIess(rowsCompletos);
        crearGridDecimoCuartoIess(rowsCompletos);
        $.alert("Cargados " + rowsCompletos.length + " registros de personas.",null,'success');
    });
}

// Busca la fila de encabezado que contiene Periodo y Cédula
function encontrarFilaHeader(mat) {
    for (var i = 0; i < Math.min(10, mat.length); i++) {
        var row = mat[i] || [];
        var tienePeriodo = row.some(function (c) { return normalizaTexto(c) === 'periodo'; });
        var tieneCedula = row.some(function (c) {
            var t = normalizaTexto(c);
            return t.indexOf('cédula') === 0 || t.indexOf('cedula') === 0;
        });
        if (tienePeriodo && tieneCedula) return i;
    }
    return -1;
}

// Calcula días y décimo cuarto por persona en base al consolidado (usando sueldos básicos unificados)
function calcularDecimoCuartoDesdeConsolidado(mat) {
    if (!Array.isArray(mat) || mat.length < 2) return [];

    var headerRowIdx = encontrarFilaHeader(mat);
    if (headerRowIdx === -1) {
        $.alert("No se encontró encabezado válido (Periodo, Cédula, Nombre, Días).",null,'warning');
        return [];
    }

    var header = mat[headerRowIdx] || [];
    var idxPeriodo = header.findIndex(function (c) { return normalizaTexto(c) === 'periodo'; });
    var idxCedula = header.findIndex(function (c) {
        var t = normalizaTexto(c);
        return t.indexOf('cédula') === 0 || t.indexOf('cedula') === 0;
    });
    var idxNombre = header.findIndex(function (c) { return normalizaTexto(c) === 'nombre'; });
    var idxDias = header.findIndex(function (c) {
        var t = normalizaTexto(c);
        return t === 'días' || t === 'dias';
    });

    if (idxPeriodo === -1 || idxCedula === -1 || idxNombre === -1 || idxDias === -1) {
        $.alert("No se pudieron identificar todas las columnas necesarias (Periodo, Cédula, Nombre, Días).",null,'warning');
        return [];
    }

    var personas = {}; // key: cédula
    var maxYear = 0;

    for (var i = headerRowIdx + 1; i < mat.length; i++) {
        var row = mat[i] || [];
        var periodo = row[idxPeriodo];
        var cedula = String(row[idxCedula] || "").trim();
        var nombre = String(row[idxNombre] || "").trim();
        if (!cedula || !periodo) continue;

        var partesPer = String(periodo).split("-");
        if (partesPer.length < 2) continue;
        var y = parseInt(partesPer[0], 10);
        var m = parseInt(partesPer[1], 10);
        if (!y || !m) continue;
        if (y > maxYear) maxYear = y;

        if (!personas[cedula]) {
            personas[cedula] = {
                cedula: cedula,
                nombre: nombre,
                registros: []
            };
        }

        personas[cedula].registros.push({
            anio: y,
            mes: m,
            diasRaw: row[idxDias]
        });
    }

    if (maxYear === 0) return [];

    // Obtener fechas seleccionadas por el usuario
    var fechaDesde = document.getElementById('dateRolini') ? document.getElementById('dateRolini').value : '';
    var fechaHasta = document.getElementById('dateRolfin') ? document.getElementById('dateRolfin').value : '';
    
    // Extraer año y mes de las fechas seleccionadas
    var anioDesde = 0, mesDesde = 0, anioHasta = 0, mesHasta = 0;
    if (fechaDesde) {
        var partesDesde = fechaDesde.split('-');
        if (partesDesde.length === 3) {
            anioDesde = parseInt(partesDesde[0], 10);
            mesDesde = parseInt(partesDesde[1], 10);
        }
    }
    if (fechaHasta) {
        var partesHasta = fechaHasta.split('-');
        if (partesHasta.length === 3) {
            anioHasta = parseInt(partesHasta[0], 10);
            mesHasta = parseInt(partesHasta[1], 10);
        }
    }
    
    // Si no hay fechas seleccionadas, usar el rango por defecto (marzo año actual - febrero año siguiente)
    if (!anioDesde || !anioHasta) {
        anioDesde = maxYear;
        mesDesde = 3;  // Marzo
        anioHasta = maxYear + 1;
        mesHasta = 2;  // Febrero
    }

    var resumen = [];
    var anioObj = maxYear; // año del décimo (ej: 2025)

    Object.keys(personas).forEach(function (key) {
        var p = personas[key];
        var dias = 0;
        
        // Filtrar y guardar los registros filtrados para calcular sueldo básico por período
        var registrosFiltrados = [];
        p.registros.forEach(function (r) {
            // Filtrar según las fechas seleccionadas
            var enRango = false;
            if (r.anio > anioDesde && r.anio < anioHasta) {
                enRango = true;
            } else if (r.anio === anioDesde && r.anio === anioHasta) {
                // Mismo año: mes debe estar entre mesDesde y mesHasta
                enRango = (r.mes >= mesDesde && r.mes <= mesHasta);
            } else if (r.anio === anioDesde) {
                // Año inicial: mes debe ser >= mesDesde
                enRango = (r.mes >= mesDesde);
            } else if (r.anio === anioHasta) {
                // Año final: mes debe ser <= mesHasta
                enRango = (r.mes <= mesHasta);
            }
            if (enRango) {
                var d = parseNumeroIess(r.diasRaw);
                dias += d;
                registrosFiltrados.push(r);
            }
        });

        if (dias === 0) return;

        // Separar nombre completo en nombres y apellidos (si es posible)
        // El formato del Excel es: APELLIDOS NOMBRES
        var nombreCompleto = String(p.nombre || '').trim();
        var partes = nombreCompleto.split(/\s+/);
        var nombres = '';
        var apellidos = '';
        if (partes.length >= 2) {
            // Primeras dos palabras como apellidos, resto como nombres
            apellidos = partes.slice(0, 2).join(' ');
            nombres = partes.slice(2).join(' ');
        } else {
            nombres = nombreCompleto;
        }
        
        resumen.push({
            EMP_RUC: $('#rucEmpresa').val() || '',
            SUCURSAL_IESS: '0001',
            ANIO: anioObj,
            rol_dias: dias,
            PRS_CED: p.cedula,
            nombres: nombreCompleto,  // nombre completo desde el Excel (fallback)
            Nombres: nombres,  // nombres separados desde Excel
            Apellidos: apellidos,  // apellidos separados desde Excel
            Prs_Sex: '',
            registros: registrosFiltrados,  // Guardar registros para calcular sueldo básico por período
            sueldo_bas: 0,  // Se calculará desde rol_defaults por período
            decimo: 0  // Se calculará después de obtener sueldo_bas por período
        });
    });

    return resumen;
}

// Llama al backend para obtener género, ocupación, forma de pago, mensualización y sueldo básico desde rol_defaults por período
function enriquecerConDatosBDCuarto(resumen, callback) {
    var cedulasUnicas = [];
    var mapCedulas = {};
    resumen.forEach(function (r) {
        var c = (r.PRS_CED || '').toString().trim();
        if (c && !mapCedulas[c]) {
            cedulasUnicas.push(c);
            mapCedulas[c] = true;
        }
    });
    if (cedulasUnicas.length === 0) {
        console.log('DEBUG - No hay cédulas únicas para buscar en BD');
        callback(resumen);
        return;
    }

    // Obtener la fecha final (dateRolfin)
    var fechaFinal = '';
    var dateRolfin = document.getElementById('dateRolfin');
    if (dateRolfin && dateRolfin.value) {
        fechaFinal = dateRolfin.value;
    }
    
    console.log('DEBUG - Cédulas a enviar al servidor:', cedulasUnicas);
    console.log('DEBUG - Número de cédulas:', cedulasUnicas.length);
    console.log('DEBUG - Fecha final:', fechaFinal);
    console.log('DEBUG - Resumen completo (primeras 3 filas):', resumen.slice(0, 3));
    
    $.ajax({
        url: 'rhu_alt_decimo_cuarto_iess.php',
        type: 'POST',
        dataType: 'json',
        data: {
            getDatosIessCuarto: true,
            cedulas: JSON.stringify(cedulasUnicas),
            fechaFinal: fechaFinal
        },
        error: function(xhr, status, error) {
            console.error('DEBUG - Error en AJAX:', status, error);
            console.error('DEBUG - Respuesta del servidor:', xhr.responseText);
        },
        success: function (resp) {
            console.log('DEBUG - Respuesta completa del servidor:', resp);
            if (resp && resp.debug) {
                console.log('DEBUG - Información de debug del servidor:', resp.debug);
            }
            if (resp && resp.success && Array.isArray(resp.rows)) {
                console.log('DEBUG - Número de filas recibidas:', resp.rows.length);
                if (resp.rows.length === 0) {
                    console.warn('DEBUG - ADVERTENCIA: No se recibieron filas del servidor. Las cédulas pueden no existir en la BD o hay un problema con la consulta.');
                }
                var mapExtra = {};
                var sueldoBasFinal = parseFloat(resp.sueldo_bas_final || 0); // Sueldo básico de la fecha final
                
                console.log('DEBUG - Total de registros en resumen ANTES de enriquecer:', resumen.length);
                
                resp.rows.forEach(function (r) {
                    var c = (r.Prs_Ced || r.PRS_CED || '').toString().trim();
                    if (!c) {
                        console.log('DEBUG - Fila sin cédula:', r);
                        return;
                    }
                    console.log('DEBUG - Procesando cédula:', c, 'Datos completos:', r);
                    // Manejar Afi_Dcu: puede venir como NULL, 'S', 'N', o cadena vacía
                    var afiDcuBD = r.Afi_Dcu;
                    if (afiDcuBD === null || afiDcuBD === undefined) {
                        afiDcuBD = '';
                    } else {
                        afiDcuBD = String(afiDcuBD).trim();
                    }
                    
                    mapExtra[c] = {
                        Prs_Sex: r.Prs_Sex || '',  // Género desde persona.Prs_Sex
                        Prs_Ape: r.Prs_Ape || '',
                        Prs_Nom: r.Prs_Nom || '',
                        car_des: r.car_des || '',  // Ocupación desde BD (para usar como fallback en CSV)
                        Pag_Con_Cue: r.Pag_Con_Cue || '',
                        Afi_Dcu: afiDcuBD  // Mensualización: si es 'N' marca X (NO acumula)
                    };
                    
                    // Debug temporal: ver qué valor tiene para trabajadores específicos
                    if (c === '0705196426' || c === '1102220546' || c === '0703534685') {
                        console.log('DEBUG BD - Cédula:', c, 'Prs_Sex:', r.Prs_Sex, 'Afi_Dcu desde BD:', r.Afi_Dcu, 'Afi_Dcu procesado:', afiDcuBD);
                    }
                });
                
                console.log('DEBUG - Total de cédulas en mapExtra:', Object.keys(mapExtra).length);
                console.log('DEBUG - Cédulas en mapExtra:', Object.keys(mapExtra));
                
                var cedulasResumen = resumen.map(function(r) { return (r.PRS_CED || '').toString().trim(); });
                console.log('DEBUG - Cédulas en resumen:', cedulasResumen);
                
                resumen.forEach(function (r) {
                    var c = (r.PRS_CED || '').toString().trim();
                    var divisor = 360;
                    var dias = parseInt(r.rol_dias || 0, 10);
                    var sueldoBas = 0;
                    
                    if (mapExtra[c]) {
                        // Género: siempre asignar desde BD (Prs_Sex de tabla persona)
                        r.Prs_Sex = String(mapExtra[c].Prs_Sex || '').trim();
                        console.log('DEBUG - Asignando género a cédula', c, 'Prs_Sex desde mapExtra:', mapExtra[c].Prs_Sex, 'Prs_Sex asignado:', r.Prs_Sex);
                        
                        // Nombres y Apellidos: priorizar BD si existe, si no mantener del Excel
                        if (mapExtra[c].Prs_Nom && String(mapExtra[c].Prs_Nom).trim()) {
                            r.Nombres = String(mapExtra[c].Prs_Nom).trim();
                        }
                        if (mapExtra[c].Prs_Ape && String(mapExtra[c].Prs_Ape).trim()) {
                            r.Apellidos = String(mapExtra[c].Prs_Ape).trim();
                        }
                        
                        r.Pag_Con_Cue = mapExtra[c].Pag_Con_Cue;
                        // Afi_Dcu: si es 'N' marca X (mensualiza = NO acumula)
                        var afiDcu = String(mapExtra[c].Afi_Dcu || '').trim().toUpperCase();
                        r.mensualiza = (afiDcu === 'N' || afiDcu === 'NO') ? 'X' : '';
                        console.log('DEBUG - Asignando mensualiza a cédula', c, 'Afi_Dcu:', mapExtra[c].Afi_Dcu, 'afiDcu uppercase:', afiDcu, 'mensualiza:', r.mensualiza);
                        
                        // Usar sueldo básico unificado de la fecha final
                        // Divisor siempre 360 para décimo cuarto (a menos que se necesite medio tiempo más adelante)
                        divisor = 360;
                        sueldoBas = sueldoBasFinal;
                    } else {
                        // Si no está en BD, usar sueldo básico unificado de la fecha final
                        // Género se queda vacío si no está en BD
                        r.Prs_Sex = '';
                        r.mensualiza = ''; // No mensualiza si no está en BD
                        sueldoBas = sueldoBasFinal;
                    }
                    
                    // Calcular décimo cuarto: (sueldo_bas / divisor) * dias
                    r.sueldo_bas = Number(sueldoBas.toFixed(2));
                    r.decimo = Number(((sueldoBas / divisor) * dias).toFixed(2));
                    
                    // Asegurar que siempre haya nombres (fallback al nombre completo del Excel)
                    // El formato del Excel es: APELLIDOS NOMBRES
                    if (!r.Nombres && r.nombres) {
                        var partes = String(r.nombres).trim().split(/\s+/);
                        if (partes.length >= 2) {
                            // Primeras dos palabras como apellidos, resto como nombres
                            if (!r.Apellidos) r.Apellidos = partes.slice(0, 2).join(' ');
                            r.Nombres = partes.slice(2).join(' ');
                        } else {
                            r.Nombres = r.nombres;
                        }
                    }
                    // Asegurar apellidos también
                    if (!r.Apellidos && r.nombres) {
                        var partes2 = String(r.nombres).trim().split(/\s+/);
                        if (partes2.length >= 2) {
                            r.Apellidos = partes2.slice(0, 2).join(' ');
                        }
                    }
                });
                
                console.log('DEBUG - Resumen después de enriquecer (primer registro):', resumen.length > 0 ? {
                    PRS_CED: resumen[0].PRS_CED,
                    Prs_Sex: resumen[0].Prs_Sex,
                    mensualiza: resumen[0].mensualiza,
                    Nombres: resumen[0].Nombres,
                    Apellidos: resumen[0].Apellidos
                } : 'Sin registros');
            }
            callback(resumen);
        },
        error: function () {
            // Si falla la consulta a BD, continuar sin datos
            callback(resumen);
        }
    });
}

// Construye el mapa global de formas de pago y actividad sectorial desde el XLSX de consulta rol
function construirMapaFormasPago(mat) {
    mapFormaPagoIess = {};
    if (!Array.isArray(mat) || mat.length < 2) return;

    // Buscar encabezado que tenga Cédula y Actividad Sectorial o Forma Pago
    var headerRow = -1;
    for (var i = 0; i < Math.min(10, mat.length); i++) {
        var row = mat[i] || [];
        var tieneCed = row.some(function (c) { return normalizaTexto(c) === 'cédula' || normalizaTexto(c) === 'cedula'; });
        var tieneAct = row.some(function (c) { return normalizaTexto(c).indexOf('actividad sectorial') === 0; });
        var tieneForma = row.some(function (c) { return normalizaTexto(c).indexOf('forma pago') === 0; });
        if (tieneCed && (tieneAct || tieneForma)) { headerRow = i; break; }
    }
    if (headerRow === -1) return;

    var header = mat[headerRow] || [];
    var idxCed = header.findIndex(function (c) { var t = normalizaTexto(c); return t === 'cédula' || t === 'cedula'; });
    var idxAct = header.findIndex(function (c) { return normalizaTexto(c).indexOf('actividad sectorial') === 0; });
    var idxForma = header.findIndex(function (c) { return normalizaTexto(c).indexOf('forma pago') === 0; });
    // Intentar localizar una columna de periodo/año si existe
    var idxPeriodo = header.findIndex(function (c) {
        var t = normalizaTexto(c);
        return t === 'periodo' || t === 'año' || t === 'anio' || t.indexOf('anio') === 0;
    });

    for (var i = headerRow + 1; i < mat.length; i++) {
        var row = mat[i] || [];
        var ced = (row[idxCed] || '').toString().trim();
        if (!ced) continue;
        var act = idxAct >= 0 ? String(row[idxAct] || '').trim() : '';
        var forma = idxForma >= 0 ? String(row[idxForma] || '').trim() : '';
        var periodoVal = idxPeriodo >= 0 ? String(row[idxPeriodo] || '').trim() : '';
        var anio = 0;
        if (periodoVal) {
            var m = periodoVal.match(/(\d{4})/);
            if (m) anio = parseInt(m[1], 10);
        }
        var ex = mapFormaPagoIess[ced];
        if (!ex || anio >= (ex.anio || 0)) {
            mapFormaPagoIess[ced] = { actividad: act, forma: forma, anio: anio };
        }
    }
}

// Mezcla el mapa de formas de pago en las filas calculadas (ocupación y tipo de depósito)
function fusionarFormasPagoIess(rows) {
    if (!rows || !Array.isArray(rows)) return;
    rows.forEach(function (r) {
        var ced = (r.PRS_CED || '').toString().trim();
        var info = ced && mapFormaPagoIess[ced] ? mapFormaPagoIess[ced] : null;
        if (info) {
            if (info.actividad) r.car_des = info.actividad; // Ocupación (Actividad Sectorial)
            if (info.forma) {
                r.forma_pago_iess = info.forma;
                r.tipo_deposito = info.forma; // P / A / RA / RP según archivo
            }
        }
        // Si no hay tipo_deposito del archivo IESS, calcularlo desde BD
        if (!r.tipo_deposito && !r.forma_pago_iess) {
            var tieneCuenta = !!(r['Pag_Con_Cue'] && String(r['Pag_Con_Cue']).trim() !== '');
            r.tipo_deposito = tieneCuenta ? 'A' : 'P';
        }
    });
}

function parseNumeroIess(v) {
    if (v == null || v === "") return 0;
    if (typeof v === "number") return v;
    var s = String(v).trim();
    if (!s) return 0;
    s = s.replace(/[^0-9,.\-]/g, "");
    var lastComma = s.lastIndexOf(","), lastDot = s.lastIndexOf(".");
    var dec = (lastComma > -1 && lastDot > -1) ? (lastComma > lastDot ? "," : ".") : (lastComma > -1 ? "," : ".");
    if (dec === ",") {
        s = s.replace(/\./g, "");
        s = s.replace(/,/g, ".");
    } else {
        s = s.replace(/,/g, "");
    }
    var n = parseFloat(s);
    return isNaN(n) ? 0 : n;
}

// jqGrid para visualizar resumen
function crearGridDecimoCuartoIess(rows) {
    var model = [
        { label: 'Cédula', name: 'PRS_CED', width: 30, align: "center" },
        { label: 'Nombres', name: 'Nombres', width: 40, align: "left" },
        { label: 'Apellidos', name: 'Apellidos', width: 40, align: "left" },
        { label: 'Género', name: 'Prs_Sex', width: 20, align: "center" },
        { label: 'Ocupación', name: 'car_des', width: 40, align: "left" },
        { label: 'Tipo Depósito', name: 'tipo_deposito', width: 20, align: "center" },
        { label: 'Días', name: 'rol_dias', width: 20, align: "center" },
        {
            label: 'Sueldo Básico', name: 'sueldo_bas', width: 30, align: "right", formatter: 'currency',
            formatoptions: { prefix: '', thousandsSeparator: ',', decimalSeparator: '.' }
        },
        {
            label: 'Décimo Cuarto', name: 'decimo', width: 30, align: "right", formatter: 'currency',
            formatoptions: { prefix: '', thousandsSeparator: ',', decimalSeparator: '.' }
        },
        { label: 'Mensualiza', name: 'mensualiza', width: 20, align: "center" }
    ];

    if (!gridDecimoCuartoIess) {
        gridDecimoCuartoIess = $("#gridDecimoCuartoIess");
        gridDecimoCuartoIess.createGrid({
            stateCol: 'PRS_CED',
            height: 250,
            caption: '&nbsp;',
            rowNum: 10000000,
            rownumbers: false,
            sortname: 'PRS_CED',
            sortorder: 'asc',
            colModel: model
        }, true, "pagerDecimoCuartoIess", { refresh: false, view: true })
            .gridButtonsAdd([
                {
                    caption: 'Exportar Décimo Cuarto', buttonicon: 'glyphicon glyphicon-download', onClickButton: function () {
                        exportarDecimoCuartoIessExcelDesdeGrid();
                    }
                },
                {
                    caption: 'Exportar CSV IESS', buttonicon: 'glyphicon glyphicon-download', onClickButton: function () {
                        exportarDecimoCuartoCsvIessDesdeGrid();
                    }
                }
            ]);
    }

    gridDecimoCuartoIess.setRows(rows || []);
}

// Exportar Excel con formato de plantilla de RRHH usando gridDecimoCuartoIess
function exportarDecimoCuartoIessExcelDesdeGrid() {
    if (!gridDecimoCuartoIess || !gridDecimoCuartoIess[0] || !gridDecimoCuartoIess[0].p) {
        $.alert('No existe información para exportar.', null, 'alert');
        return;
    }
    var rows = gridDecimoCuartoIess[0].p.data || [];
    if (!Array.isArray(rows) || rows.length === 0) {
        $.alert('No existe información para exportar.', null, 'alert');
        return;
    }

    var rucCompania = $('#rucEmpresa').val() || (rows[0]['EMP_RUC'] || '');
    var razonSocial = $('#razonSocialEmpresa').val() || '';

    var totalDecimo = 0;

    var html = '';

    html += '<table cellspacing="0" cellpadding="0" border="0" style="font-size:11px;">';
    html += '<tr><th colspan="11" style="text-align:center;">DECIMA CUARTA REMUNERACION</th></tr>';
    html += '<tr><td colspan="11">&nbsp;</td></tr>';
    html += '<tr><td colspan="2">&nbsp;</td><td colspan="3">RAZON SOCIAL: ' + razonSocial + '</td><td colspan="6">&nbsp;</td></tr>';
    html += '<tr><td colspan="2">&nbsp;</td><td colspan="3">RUC: ' + rucCompania + '</td><td colspan="6">&nbsp;</td></tr>';
    html += '<tr><td colspan="11">&nbsp;</td></tr>';
    html += '<tr><td colspan="2">&nbsp;</td><th colspan="9" style="text-align:left;">RESUMEN DECIMA CUARTA REMUNERACION</th></tr>';
    html += '</table>';

    html += '<table cellspacing="0" cellpadding="0" border="1" style="border-collapse:collapse;font-size:11px;">';
    html += '<tr>';
    html += '<th style="width:25px;">N°</th>';
    html += '<th style="width:90px;">Cédula</th>';
    html += '<th style="width:160px;">Nombre</th>';
    html += '<th style="width:160px;">Apellidos</th>';
    html += '<th style="width:120px;">Ocupación</th>';
    html += '<th style="width:40px;">Género</th>';
    html += '<th style="width:50px;">Días</th>';
    html += '<th style="width:90px;">Valor Décimo</th>';
    html += '<th style="width:140px;">Firma o Huella Digital</th>';
    html += '<th style="width:40px;">&nbsp;</th>';
    html += '</tr>';

    for (var i = 0; i < rows.length; i++) {
        var r = rows[i];
        var nombres = (r['Nombres'] || r['nombres'] || '').trim();
        var apellidos = (r['Apellidos'] || '').trim();

        var dias = parseInt(r['rol_dias'], 10);
        if (isNaN(dias)) dias = '';

        var valorDecimo = parseFloat(String(r['decimo']).replace(/,/g, ''));
        if (isNaN(valorDecimo)) valorDecimo = 0;

        totalDecimo += valorDecimo;

        html += '<tr>';
        html += '<td style="text-align:center;">' + (i + 1) + '</td>';
        html += '<td style="text-align:center;">' + (r['PRS_CED'] || '') + '</td>';
        html += '<td>' + nombres + '</td>';
        html += '<td>' + apellidos + '</td>';
        html += '<td>' + (r['car_des'] || '') + '</td>';
        html += '<td style="text-align:center;">' + (r['Prs_Sex'] || '') + '</td>';
        html += '<td style="text-align:center;">' + dias + '</td>';
        html += '<td style="text-align:right;">' + $.toFixed(valorDecimo, 2) + '</td>';
        html += '<td></td>';
        html += '<td></td>';
        html += '</tr>';
    }

    html += '<tr>';
    html += '<td colspan="7" style="text-align:right;font-weight:bold;">Total</td>';
    html += '<td style="text-align:right;font-weight:bold;">' + $.toFixed(totalDecimo, 2) + '</td>';
    html += '<td></td><td></td>';
    html += '</tr>';
    html += '</table>';

    html += '<table cellspacing="0" cellpadding="0" border="0" style="font-size:11px;">';
    html += '<tr><td colspan="11">&nbsp;</td></tr>';
    html += '<tr><td colspan="2">&nbsp;</td><td colspan="3">PERIODO REPORTADO</td><td colspan="6">&nbsp;</td></tr>';
    html += '<tr><td colspan="11">&nbsp;</td></tr>';
    html += '<tr><td colspan="2">&nbsp;</td><td colspan="3">FIRMA DEL REPRESENTANTE LEGAL</td><td colspan="6">&nbsp;</td></tr>';
    html += '</table>';

    $.downloadFile($.exportarExcelBlob(html, 'DECIMO_CUARTO_IESS'), 'DECIMO_CUARTO_IESS_' + $.getDate() + '.xls');
}

// Exportar CSV IESS desde gridDecimoCuartoIess
function exportarDecimoCuartoCsvIessDesdeGrid() {
    if (!gridDecimoCuartoIess || !gridDecimoCuartoIess[0] || !gridDecimoCuartoIess[0].p) {
        $.alert('No existe información para exportar.', null, 'alert');
        return;
    }
    var rows = gridDecimoCuartoIess[0].p.data || [];
    if (!Array.isArray(rows) || rows.length === 0) {
        $.alert('No existe información para exportar.', null, 'alert');
        return;
    }

    var csv = 'Cédula (Ejm.:0502366503);Nombres;Apellidos;Genero (Masculino=M ó Femenino=F);Ocupación(codigo iess);Días laborados (360 días equivalen a un año);Tipo de Pago(Pago Directo=P,Acreditación en Cuenta=A,Retencion Pago Directo=RP,Retencion Acreditación en Cuenta=RA);Solo si el trabajador posee JORNADA PARCIAL PERMANENTE ponga una X;DETERMINE EN HORAS LA JORNADA PARCIAL PERMANENTE SEMANAL ESTIPULADO EN EL CONTRATO;Solo si su trabajador posee algun tipo de discapacidad ponga una X;Fecha de Jubilación;valor Retencion;SOLO SI SU TRABAJADOR MENSUALIZA EL PAGO DE LA DECIMOCUARTA REMUNERACIÓN PONGA UNA X';
    csv += '\r\n';

    for (var i = 0; i < rows.length; i++) {
        var r = rows[i];
        var cedula = (r['PRS_CED'] || '').toString().trim();
        var nombres = (r['Nombres'] || r['nombres'] || '').trim();
        var apellidos = (r['Apellidos'] || '').trim();

        var genero = (r['Prs_Sex'] || '').toString().trim();
        // Ocupación: priorizar del archivo Excel, si no hay usar de BD
        var ocupacion = (r['car_des'] || '').toString().trim();

        var dias = parseInt(r['rol_dias'], 10);
        if (isNaN(dias)) dias = '';

        // Tipo de pago: priorizar forma de pago IESS si está, si no usar cuenta bancaria
        var tieneCuenta = !!(r['Pag_Con_Cue'] && String(r['Pag_Con_Cue']).trim() !== '');
        var tipoPago = tieneCuenta ? 'A' : 'P';
        if (r['tipo_deposito']) {
            tipoPago = String(r['tipo_deposito']).trim();
        } else if (r['forma_pago_iess']) {
            tipoPago = String(r['forma_pago_iess']).trim();
        }

        // Jornada parcial permanente: dejar vacío por ahora (no viene en la consulta)
        var jornadaParcialX = '';
        var horasJornadaParcial = '';
        
        // Discapacidad: buscar en la base de datos si existe campo (por ahora vacío)
        var discapacidadX = '';
        
        // Fecha de Jubilación: buscar en la base de datos si existe campo (por ahora vacío)
        var fechaJubilacion = '';
        
        // Valor Retención: buscar en la base de datos si existe campo (por ahora vacío)
        var valorRetencion = '';

        // Mensualización: usar el campo 'mensualiza' que ya se calculó al cargar los datos
        var mensualizaX = (r['mensualiza'] || '').trim();

        var cols = [
            cedula,
            nombres,
            apellidos,
            genero,
            ocupacion,
            dias,
            tipoPago,
            jornadaParcialX,
            horasJornadaParcial,
            discapacidadX,
            fechaJubilacion,
            valorRetencion,
            mensualizaX
        ];

        csv += cols.join(';') + '\r\n';
    }

    $.downloadFile(csv, 'FORMATO10MOCUARTO_IESS_' + $.getDate() + '.csv');
}
