$(function () {
    $.createDateRange('#Fec_Ini', '#Fec_Fin');
});

var Tic_Cod_Previo;
//Función para setear el datepicker al periodo seleccionado
function fechas(inicio, fin, placod) {
    $('#Caj_Fec').dateLimits(inicio, fin);
    $('.placod').val(placod);
}

//Función para obtener el número de secuencia y validar el mismo
var num_old;
function cargarFormasPago() {
    $('#Pag_Pld_Nota').empty();
    $('#For_Cod_Nota').empty();
    $.post('', { 'getForCod': true }, function (resp) {
        $.each(resp['data'], function (index, item) {
            var opcion = $('<option></option>').attr('value', item.For_Cod).text(item.For_Des).data(item);
            $('#For_Cod_Nota').append(opcion);
        });
        $.post('', { 'buscarCuentas': true, 'Pla_Cod': $('#Pec_Cod').find(':selected').data('placod') }, function (resp) {
            $.each($.merge(resp['Contado'], resp['Credito']), function (index, item) {
                //console.log(item);
                var opcion = $('<option></option>').attr('value', item.Pld_Cod).attr('forma', validaOpcion(item)).text(item.Pld_Des).data(item);
                $('#Pag_Pld_Nota').append(opcion);
            });
            $('#For_Cod_Nota').trigger('change');
        }, 'json').fail(function () {
            (Conf_Con === 'S' ? $.alert('Sin cuentas asociadas a Pagos') : '');
        });
    }, 'json').fail(function () { $.alert('Error al buscar las Formas de Pago'); });
}


function validaOpcion(item) {
    var tipo = '';
    if ($.varValid(item.Cpc_Cxc)) {
        tipo = 2;
    }
    if ($.varValid(item.Ban_Tip)) {
        if (item.Ban_Tip === 'C') {
            tipo = 1;
        }
    }
    if ($.varValid(item.Tpa_Abr)) {
        if (item.Tpa_Abr === 'CBA') {
            tipo = 1;
        }
    }
    return tipo;
}

function filtrarCuentasFormasPago(dataFormaPago) {
    $('#Pag_Pld_Nota').children().addClass('hidden');
    if (!$.isEmptyObject(dataFormaPago) && Cof_Con === 'S') {
        var elemento = $('#Pag_Pld_Nota').find('option[forma=' + dataFormaPago.For_Cod * 1 + ']').removeClass('hidden').val();
        $('#Pag_Pld_Nota').val(elemento);
    }
}

var array_contado = [], array_credito = [];
function deletePago(Vet_Num) {
    pagos.jqGrid('delRowData', Vet_Num);
    pagos.trigger('reloadGrid');
    updateDocument();
}


function clearDocument() {
    $('#clieFormTemp').setData({});
    //$('#Tic_Cod').trigger('change');
    $('#Cop_Fec').trigger('change');
    $('#Ciu_Cod').trigger('chosen:updated');
    items.clearGrid();
    pagos.setRows([]);
    $('#Cop_Aut').attr('title', '');
    addItem({});
    //validaRetNum();
    updateDocument();
    $('select[name=Tic_Cod]').attr('disabled', false);
    $('select[name=Pec_Cod]').attr('disabled', false);
    $('select[name=Cmb_Mes]').attr('disabled', false);
    if (!$.isUnd(reembolsos)) {
        $('#Vet_Rem').prop('checked', false).trigger('change');
    }
}


function clear() {
    $('#clieCreateForm').setData({ Cli_Tic: 'N', Prs_Ciu: 'Ec', Prs_Sex: 'M' });
    $('#Prs_Ced').val('').focus();
    $('.juridico').hide(); $('.natural').show();
}

function addPago(pago, carga_inicial = false) {
    var next = pagos.jqGrid('getCol', 'Vet_Num', false, 'max');
    var text = $('#Pag_Cod').find('option:selected').text().toUpperCase();
    pago['Vet_Num'] = (isNaN(next) ? 1 : next + 1);
    pago['Tipo_Cod'] = (carga_inicial ? pago['Pag_Cod'] : $('#Pag_Cod option:selected').val());
    pago['Forma_Cod'] = $('#For_Cod option:selected').val();
    if (text === 'TRANSFERENCIA' || text === 'DEPOSITO') {
        pago['Pag_Pld'] = (carga_inicial ? pago['Pag_Pld'] : $('#Ban_Cod option:selected').data('pldcod'));
    }

    if (text === 'CHEQUE') {
        if (carga_inicial == false) {
            pago['Bak_Cod'] = $('#Bak_Cod').val();
            pago['Fec_che'] = $('#Fec_che').val();
        }
    }

    if (carga_inicial && pago['Pag_Pld'] * 1 <= 0) {
        pago['Pag_Pld'] = $('#Pag_Pld').val();
    }
    pagos.jqGrid('addRowData', next, pago);
    pagos.trigger('reloadGrid');
    $('#pagosDialog').dialog('close');
    var pagos_tot = pagos.jqGrid('getCol', 'Vet_Tot', false, 'sum');
    $('#For_Cod').val(1).trigger('change');
    $('.porCobrar').setData({ 'Val_Pcc_2': $.toFixed($('#Val_Pcc').val() - pagos_tot) });
}

function registarPagos() {
    if (pagos.jqGrid('getDataIDs').length > 0) $('#For_Cod').attr('disabled', 'disabled');
    else $('#For_Cod').removeAttr('disabled');
    $('#For_Cod').val(1).trigger('change');
    $('#pagosDialog').dialog('open');
    $('.saldos').setData({ Vet_Tot: $('#Val_Pcc_2').val() });
}

//GENERAR FACTURAS DE LOS MANIFIESTOS
function generarFacturas() {
    var manifiestos = $('#manifiestosGrid').getGridBatch(); //Obtengo todas las filas dle grid que estan marcadas
    if (manifiestos.length === 0) {
        return $.alert('No hay manifiestos seleccionados para generar facturas.');
    }

    // fac_group debería devolver true si el checkbox está marcado, false si no lo está.
    var fac_group = $('#fac_group').is(':checked');
    // fac_individual debería devolver true si el checkbox está marcado (selección individual que también agrupa)
    var fac_individual = $('#fac_individual').is(':checked');

    const selectedIds = $('#manifiestosGrid').find('input.row-select:checked').map(function () {
        return $(this).data('id');
    }).get();
    if (!selectedIds.length) {
        return $.alert('No hay manifiestos marcados para generar facturas.');
    }
    manifiestos = selectedIds.map(function (id) {
        try {
            return $('#manifiestosGrid').jqGrid('getRowData', id);
        } catch (e) {
            return manifiestos.find(function (m) {
                return String(m.Man_Cod) === String(id);
            }) || { error: 'No se encontró manifiesto con id ' + id + (e && e.message ? ' - ' + e.message : '') };
        }
    });

    // Si está marcado el checkbox de agrupar O el de individual (ambos agrupan en una sola factura)
    if (fac_group || fac_individual) {
        if (manifiestos.length === 0) {
            return $.alert('No hay manifiestos seleccionados para agrupar.');
        }
        
        // Tomar el primer manifiesto como referencia para datos del cliente, precio, etc.
        var primerMan = manifiestos[0];
        var pesoTotal = 0;
        var precioUnitario = parseFloat(primerMan.Man_Pun || 0);
        var ivaPor = parseFloat(primerMan.Iva_Por || 0);
        var manNums = [];
        var plantas = [];
        
        // Sumar el peso de todos los manifiestos
        manifiestos.forEach(function (man) {
            pesoTotal += parseFloat(man.Man_Pes || 0);
            if (man.Man_Num) {
                manNums.push(man.Man_Num);
            }
            if (man.Pla_Nom && plantas.indexOf(man.Pla_Nom) === -1) {
                plantas.push(man.Pla_Nom);
            }
        });
        
        // Calcular subtotal: cantidad_total * precio_unitario
        var subtotalSinIva = pesoTotal * precioUnitario;
        // Calcular IVA
        var ivaCalculado = subtotalSinIva * (ivaPor / 100);
        // Calcular total con IVA
        var totalConIva = subtotalSinIva + ivaCalculado;
        
        // Crear un solo manifiesto "virtual" con todos los totales sumados
        manifiestos = [{
            Man_Cod: primerMan.Man_Cod, // Usar el primero como referencia
            Cli_Cod: primerMan.Cli_Cod,
            Ciu_Cod: primerMan.Ciu_Cod,
            Prs_Ced: primerMan.Prs_Ced,
            cliente: primerMan.cliente,
            Pla_Cod: primerMan.Pla_Cod,
            Pla_Nom: plantas.join(', '), // Concatenar todas las plantas
            Iva_Cod: primerMan.Iva_Cod,
            Iva_Por: ivaPor,
            Man_Pes: pesoTotal, // Peso total sumado
            Man_Pun: precioUnitario, // Precio unitario (mismo para todos)
            subtotal: subtotalSinIva, // Subtotal calculado: cantidad * precio
            total_iva: ivaCalculado, // IVA calculado
            total: totalConIva, // Total con IVA
            Man_Num: manNums.join(', '), // Concatenar números de manifiestos
            _agrupado: true, // Flag para indicar que está agrupado
           // _manifiestos_originales: (manifiestos) // Guardar los manifiestos originales
             _manifiestos_originales: manifiestos.map(function (m) { return m.Man_Cod; }) // Solo códigos Man_Cod
           // _manifiestos_originales: (manifiestos) // Guardar los manifiestos originales
       
        }];
    }

    /* manifiestos.forEach(function (m) {
        if (Array.isArray(m._manifiestos_originales)) {
            m._manifiestos_originales = JSON.stringify(m._manifiestos_originales);
        }
    }); */

    // Función para solicitar clave de acceso si es necesaria
    function solicitarClaveAcceso(callback) {
        // Verificar si la autorización requiere clave de acceso (Aut_Tem == 'E')
        if (typeof autorizacionInfo !== 'undefined' && autorizacionInfo && autorizacionInfo.Aut_Tem === 'E') {
            // Primero verificar si existe una clave de acceso activa para esta empresa
            $.ajax({
                url: '',
                type: 'POST',
                data: {
                    verificarClaveAccesoExiste: true
                },
                dataType: 'json',
                async: false, // Síncrono para esperar la respuesta antes de continuar
                success: function(resp) {
                    if (resp && resp.existe === true) {
                        // Existe una clave activa, solicitar la clave
                        mostrarDialogoClaveAcceso(callback);
                    } else {
                        // No existe clave activa, continuar sin solicitar
                        callback('');
                    }
                },
                error: function() {
                    // En caso de error, continuar sin solicitar clave
                    callback('');
                }
            });
        } else {
            // No requiere clave de acceso, continuar directamente
            callback('');
        }
    }
    
    // Función para mostrar el diálogo de clave de acceso
    function mostrarDialogoClaveAcceso(callback) {
            // Crear diálogo para solicitar clave de acceso
            var dialogHtml = '<div id="claveAccesoDialog" style="padding: 15px;">' +
                '<div class="form-group">' +
                '<label for="inputClaveAcceso" style="display: block; margin-bottom: 8px; font-weight: bold;">Clave de Acceso:</label>' +
                '<input type="text" id="inputClaveAcceso" class="form-control" placeholder="Ingrese la clave de acceso" style="width: 100%;" maxlength="10" />' +
                '<small class="help-block" style="margin-top: 5px; color: #666;">La clave de acceso es requerida para facturación electrónica.</small>' +
                '<div id="claveAccesoError" style="color: #d9534f; margin-top: 5px; display: none;"></div>' +
                '</div>' +
                '</div>';
            
            var $dialog = $(dialogHtml).appendTo('body');
            var $btnAceptar = null;
            
            $dialog.dialog({
                title: 'Clave de Acceso Requerida',
                modal: true,
                width: 450,
                resizable: false,
                buttons: {
                    'Aceptar': function() {
                        var claveAcceso = $('#inputClaveAcceso').val().trim();
                        var $errorDiv = $('#claveAccesoError');
                        var $input = $('#inputClaveAcceso');
                        var $dialogInstance = $dialog;
                        var $btnAceptar = $dialogInstance.parent().find('.ui-dialog-buttonset button').first();
                        var $btnCancelar = $dialogInstance.parent().find('.ui-dialog-buttonset button').last();
                        
                        if (!claveAcceso || claveAcceso.length === 0) {
                            $errorDiv.text('Debe ingresar la clave de acceso para continuar.').show();
                            return;
                        }
                        
                        // Deshabilitar input temporalmente mientras se valida
                        $input.prop('disabled', true);
                        $errorDiv.hide();
                        
                        // Deshabilitar botones temporalmente (sin que desaparezcan)
                        $btnAceptar.prop('disabled', true).addClass('ui-state-disabled');
                        $btnCancelar.prop('disabled', true).addClass('ui-state-disabled');
                        
                        // Validar clave contra la base de datos
                        $.ajax({
                            url: '',
                            type: 'POST',
                            data: {
                                validarClaveAcceso: true,
                                Cla_Cod: claveAcceso
                            },
                            dataType: 'json',
                            success: function(resp) {
                                // Rehabilitar input y botones
                                $input.prop('disabled', false);
                                $btnAceptar.prop('disabled', false).removeClass('ui-state-disabled');
                                $btnCancelar.prop('disabled', false).removeClass('ui-state-disabled');
                                
                                if (resp && resp.success === true) {
                                    $dialogInstance.dialog('close');
                                    callback(claveAcceso);
                                } else {
                                    var errorMsg = resp && resp.message ? resp.message : 'Clave de acceso inválida o inactiva.';
                                    $errorDiv.text(errorMsg).show();
                                    $input.focus().select();
                                }
                            },
                            error: function() {
                                // Rehabilitar input y botones
                                $input.prop('disabled', false);
                                $btnAceptar.prop('disabled', false).removeClass('ui-state-disabled');
                                $btnCancelar.prop('disabled', false).removeClass('ui-state-disabled');
                                
                                $errorDiv.text('Error al validar la clave de acceso. Intente nuevamente.').show();
                                $input.focus().select();
                            }
                        });
                    },
                    'Cancelar': function() {
                        $(this).dialog('close');
                        callback(null);
                    }
                },
                open: function() {
                    $('#inputClaveAcceso').focus();
                    // Permitir Enter para aceptar
                    $('#inputClaveAcceso').on('keypress', function(e) {
                        if (e.which === 13) {
                            e.preventDefault();
                            $dialog.dialog('option', 'buttons')['Aceptar'].click();
                        }
                    });
                },
                close: function() {
                    $(this).remove();
                }
            });
    }

    // Primero solicitar clave de acceso si es necesaria
    solicitarClaveAcceso(function(claveAcceso) {
        if (claveAcceso === null) {
            // Usuario canceló
            return;
        }
        
        // Ahora mostrar el diálogo de confirmación
        $.createDialogConfirm('¿Está seguro de generar facturas para los manifiestos seleccionados?', { manifiestos: manifiestos, input_autorizacion: '' }, function (data) {
        //$.createDialogConfirm('¿Está seguro de generar facturas para los manifiestos seleccionados?', { manifiestos: manifiestos, input_autorizacion: claveAcceso }, function (data) {
        console.log(data);
        var cantidad = manifiestos.length;
        var $statusBar = $("#statusBarFacturacionMasiva");
        if ($statusBar.length === 0) {
            $("<div>", { id: "statusBarFacturacionMasiva" }).insertBefore("#btn_register");
            $statusBar = $("#statusBarFacturacionMasiva");
        }
        // Limpiamos la barra de estado antes de iniciar
        $statusBar.stop(true, true).show().html('');
        var $progress = $("<div class='progress' style='height:24px; margin-bottom:10px;'>" +
            "<div class='progress-bar progress-bar-striped active' role='progressbar' style='width: 0%; min-width:40px;'>0 / " + cantidad + "</div>" +
            "</div>");
        $statusBar.append($progress);
        $('#btn_register').prop('disabled', true);
        // Proceso en lote y actualizar barra
        var total = cantidad;
        var current = 0;
        var errores = [];
        function actualizarResumen() {
            var exitos = current - errores.length;
            var resumen = '<div class="row" style="margin: 10px 0;">' +
                '<div class="col-auto me-3"><span class="badge bg-success">Registradas correctamente: <b>' + exitos + '</b></span></div>' +
                '<div class="col-auto"><span class="badge bg-danger">Con error: <b>' + errores.length + '</b></span></div>' +
                '</div>';
            $statusBar.find('.resumen-facturas').remove();
            $statusBar.append('<div class="resumen-facturas">' + resumen + '</div>');
        }

        function formateaTextoError(err) {
            // Manejo uniforme para errores tipo Object y evita [object Object]
            if (typeof err === "object" && err !== null) {
                if (err.message) return String(err.message);
                if (err.error) {
                    if (typeof err.error === "object") {
                        try {
                            return err.error.message || JSON.stringify(err.error);
                        } catch (e) {
                            return "Error desconocido";
                        }
                    }
                    return String(err.error);
                }
                if (err.responseText && err.responseText.length && err.responseText.length < 200) {
                    return String(err.responseText);
                }
                try {
                    return JSON.stringify(err, null, 2);
                } catch (_) {
                    return "Error desconocido";
                }
            }
            if (err == null) return "Error desconocido";
            return String(err);
        }

        function procesarUnoAuno(idx) {
            if (idx >= manifiestos.length) {
                $('#manifiestosGrid').trigger('reloadGrid');
                $progress.find('.progress-bar').removeClass('progress-bar-striped active');
                actualizarResumen();
                let mensaje = '';
                if (errores.length === 0) {
                    mensaje = '';
                } else {
                    mensaje = '<div class="alert alert-warning show"><b>Errores:</b><br><ul style="margin:0 0 0 1em;">' +
                        errores.map(function (err) {
                            return '<li>' + formateaTextoError(err) + '</li>';
                        }).join('') +
                        '</ul></div>';
                    $.alert('Ocurrieron errores al registrar algunas facturas. Revise la lista de errores para más detalles.');
                }
                $statusBar.append(mensaje);
                $('#btn_register').prop('disabled', false);
                return;
            }
            var m = manifiestos[idx];
            var pct = Math.round(((current) / total) * 100);
            $progress.find('.progress-bar').css('width', pct + '%').html(current + " / " + total);
            
            // Si tiene _manifiestos_originales, convertirlo a JSON string para asegurar que se envíe correctamente
            if (m._manifiestos_originales && Array.isArray(m._manifiestos_originales)) {
                // Crear una copia del objeto para no modificar el original
                var mCopy = $.extend({}, m);
                mCopy._manifiestos_originales_json = JSON.stringify(m._manifiestos_originales);
                // Mantener también el array original por si acaso
                m = mCopy;
            }
            
            // Enviar individualmente
            $.ajax({
                url: '', // ajusta el endpoint si es necesario
                type: 'POST',
                data: { 
                    generarFacturasAjax: true, 
                    manifiestos: [m],
                    fac_group: (fac_group || fac_individual), // Enviar el flag de agrupación (true si fac_group O fac_individual está marcado)
                    input_autorizacion: data.input_autorizacion || '', // Enviar la clave de acceso
                    Fec_Ini: ($('#Fec_Ini').val() || $('#sf_fec_ini').val() || ''),
                    Fec_Fin: ($('#Fec_Fin').val() || $('#sf_fec_fin').val() || '')
                },
                dataType: 'json',
                success: function (resp) {
                    current++;
                    var pct = Math.round((current / total) * 100);
                    var $pb = $progress.find('.progress-bar');
                    $pb.css('width', pct + '%');
                    $pb.html(current + " / " + total);
                    actualizarResumen();
                    if (resp && (resp.error || (resp.errores && resp.errores.length > 0) || (resp.errores_manifiestos && resp.errores_manifiestos.length > 0))) {
                        if (resp.error) {
                            errores.push('[' + (m.Man_Cod || idx) + '] ' + formateaTextoError(resp.error));
                        }
                        if (resp.errores && Array.isArray(resp.errores)) {
                            resp.errores.forEach(function (e) {
                                errores.push('[' + (m.Man_Cod || idx) + '] ' + formateaTextoError(e));
                            });
                        }
                        if (resp.errores_manifiestos && Array.isArray(resp.errores_manifiestos)) {
                            resp.errores_manifiestos.forEach(function (e) {
                                errores.push('[' + (m.Man_Cod || idx) + '] ' + formateaTextoError(e));
                            });
                        }
                    } else if (resp && resp.success === false) {
                        let msg = typeof resp.message === "object"
                            ? (resp.message && resp.message.message ? resp.message.message : JSON.stringify(resp.message))
                            : (resp.message || 'No se registró la factura por razón desconocida.');
                        errores.push('[' + (m.Man_Cod || idx) + '] ' + formateaTextoError(msg));
                    } else if (!resp || typeof resp !== "object") {
                        errores.push('[' + (m.Man_Cod || idx) + "] Respuesta inesperada del servidor");
                    }
                },
                error: function (err) {
                    current++;
                    var pct = Math.round((current / total) * 100);
                    var $pb = $progress.find('.progress-bar');
                    $pb.css('width', pct + '%');
                    $pb.html(current + " / " + total);
                    actualizarResumen();
                    let errMsg = '';
                    if (err && err.responseJSON && err.responseJSON.error) {
                        errMsg = formateaTextoError(err.responseJSON.error);
                    } else if (err && err.responseText) {
                        try {
                            let parsed = JSON.parse(err.responseText);
                            if (parsed && parsed.error) {
                                errMsg = formateaTextoError(parsed.error);
                            } else if (parsed && parsed.message) {
                                errMsg = formateaTextoError(parsed.message);
                            } else {
                                errMsg = err.responseText.length < 120 ? err.responseText : 'Error desconocido';
                            }
                        } catch (e) {
                            errMsg = err.responseText.length < 120 ? err.responseText : 'Error desconocido';
                        }
                    } else {
                        errMsg = 'Error desconocido';
                    }
                    errores.push('[' + (m.Man_Cod || idx) + '] ' + errMsg);
                },
                complete: function () {
                    procesarUnoAuno(idx + 1);
                }
            });
        }
        procesarUnoAuno(0);
        });
    });
}

