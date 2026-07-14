var global_horometros_ids = [];

$(document).ready(function () {
    $('.chosen-select').chosen({ width: '100%', allow_single_deselect: true });

    cargarMaquinas();
    cargarOperadores();
});

function cargarMaquinas() {
    $.ajax({
        url: 'man_alt_maquinaria_preliquidacion.php?listMaquinasAjax=1',
        type: 'GET',
        dataType: 'json',
        success: function(res) {
            var $sel = $('#fil_vehiculo');
            $sel.empty().append('<option value="">Todos los Vehículos</option>');
            if (res && res.length > 0) {
                $.each(res, function(i, v) {
                    $sel.append('<option value="'+v.Veh_Cod+'">'+v.Veh_Pla+' - '+v.Veh_Mar+'</option>');
                });
            }
            $sel.trigger('chosen:updated');
        }
    });
}

function cargarOperadores() {
    $.ajax({
        url: 'man_alt_maquinaria_preliquidacion.php?listOperadoresAjax=1',
        type: 'GET',
        dataType: 'json',
        success: function(res) {
            var $sel = $('#fil_operador');
            $sel.empty().append('<option value="">Todos los Operadores</option>');
            if (res && res.length > 0) {
                $.each(res, function(i, v) {
                    // Guardamos el per_cod en un data attribute para usarlo en alimentación
                    $sel.append('<option value="'+v.Cho_Cod+'" data-percod="'+(v.Per_Cod || '')+'">'+v.nombre+'</option>');
                });
            }
            $sel.trigger('chosen:updated');
        }
    });
}

function limpiarFiltros() {
    $('#fil_vehiculo').val('').trigger('chosen:updated');
    $('#fil_operador').val('').trigger('chosen:updated');
    
    var d = new Date();
    var firstDay = new Date(d.getFullYear(), d.getMonth(), 1);
    var lastDay = new Date(d.getFullYear(), d.getMonth() + 1, 0);
    
    $('#fil_fec_ini').val(firstDay.toISOString().split('T')[0]);
    $('#fil_fec_fin').val(lastDay.toISOString().split('T')[0]);
    
    resetUI();
}

function buscarUltimoOperador(veh_cod) {
    if (!veh_cod) return;
    
    $.ajax({
        url: 'man_alt_maquinaria_preliquidacion.php?getLastOperatorAjax=1&veh_cod=' + veh_cod,
        type: 'GET',
        dataType: 'json',
        success: function(res) {
            if (res.success && res.Cho_Cod) {
                $('#fil_operador').val(res.Cho_Cod).trigger('chosen:updated');
            } else {
                $('#fil_operador').val('').trigger('chosen:updated');
                if (typeof $.alert !== 'undefined') $.alert('No se encontró operador previo para este vehículo.');
            }
        },
        error: function() {
            // Silencioso
        }
    });
}

function resetUI() {
    $('#tblHorometros tbody').html('<tr><td colspan="9" class="text-center text-muted">Use el botón Generar para consultar información.</td></tr>');
    $('#tblCombustible tbody').html('<tr><td colspan="6" class="text-center text-muted">Use el botón Generar para consultar información.</td></tr>');
    $('#tblCompras tbody').html('<tr><td colspan="4" class="text-center text-muted">Use el botón Generar para consultar información.</td></tr>');
    
    $('#bdg_hor').text('0');
    $('#bdg_comb').text('0');
    $('#bdg_com').text('0');
    
    $('#kpi_horas').text('0.0');
    $('#kpi_comb_gal').text('0 Gls');
    $('#kpi_comb_cost').text('$0.00');
    $('#kpi_compras').text('$0.00');
    $('#kpi_total').text('$0.00');
    
    $('#btnGuardarPre').prop('disabled', true);
    $('#lblEstadoVisual').html('<span class="label label-warning">PENDIENTE</span>');
    
    global_horometros_ids = [];
    $('#lblModalTotalCom').text('0');
    $('#lblModalTotalHor').text('0');
}

// -------------------------------------------------------------------------
// SELECTOR DE MODO
// -------------------------------------------------------------------------
var modoActual = 'individual';

function cambiarModo(modo) {
    modoActual = modo;
    
    // Explicitly handle button active states
    $('#btn_modo_ind').removeClass('active');
    $('#btn_modo_mas').removeClass('active');
    
    if (modo === 'individual') {
        $('#btn_modo_ind').addClass('active');
        $('#btn_modo_ind input').prop('checked', true);
        
        $('#div_filtros_individual').show();
        $('#div_kpis_individual').show();
        $('#div_tabs_individual').show();
        $('#hlp_modo_ind').show();
        
        $('#div_filtros_masivo').hide();
        $('#div_tabla_masiva').hide();
        $('#hlp_modo_mas').hide();
    } else {
        $('#btn_modo_mas').addClass('active');
        $('#btn_modo_mas input').prop('checked', true);
        
        $('#div_filtros_individual').hide();
        $('#div_kpis_individual').hide();
        $('#div_tabs_individual').hide();
        $('#hlp_modo_ind').hide();
        
        $('#div_filtros_masivo').show();
        $('#div_tabla_masiva').show();
        $('#hlp_modo_mas').show();
    }
}

// -------------------------------------------------------------------------
// MODO INDIVIDUAL
// -------------------------------------------------------------------------
function autoSeleccionarOperador(veh_cod) {
    $.ajax({
        url: 'man_alt_maquinaria_preliquidacion.php',
        type: 'GET',
        data: { getLastOperadorAjax: 1, veh_cod: veh_cod },
        dataType: 'json',
        success: function(res) {
            if (res.success && res.Cho_Cod) {
                $('#fil_operador').val(res.Cho_Cod).trigger('chosen:updated');
            }
        }
    });
}

function generarPreliquidacion() {
    var ini = $('#fil_fec_ini').val();
    var fin = $('#fil_fec_fin').val();
    var veh = $('#fil_vehiculo').val();
    
    if (!veh) {
        if (typeof $.alert !== 'undefined') $.alert('Debe seleccionar un vehículo / maquinaria.');
        return;
    }

    var dataPost = {
        generarPreliquidacionAjax: 1,
        fecha_ini: ini,
        fecha_fin: fin,
        veh_cod: veh,
        cho_cod: $('#fil_operador').val()
    };
    
    $.ajax({
        url: 'man_alt_maquinaria_preliquidacion.php',
        type: 'POST',
        data: dataPost,
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                poblarTablas(res);
            } else {
                if(typeof $.alert !== 'undefined') $.alert('Error al generar la consulta.');
            }
        },
        error: function(xhr, status, error) {
            if(typeof $.alert !== 'undefined') $.alert('Error de red al consultar información.');
        }
    });
}

function poblarTablas(data) {
    global_horometros_ids = [];
    
    var hasData = false;

    // 1. Horometros
    var tbodyHor = $('#tblHorometro tbody');
    tbodyHor.empty();
    var sumTotalHoras = 0;
    var sumTotalHorasMonto = 0;
    
    if (data.horometro && data.horometro.length > 0) {
        hasData = true;
        $('#bdg_hor').text(data.horometro.length);
        $.each(data.horometro, function(i, row) {
            var labelEstado = '';
            switch(row.estado) {
                case 'A': labelEstado = 'Activo'; break;
                case 'F': labelEstado = 'Finalizado'; break;
                case 'P': labelEstado = 'Pendiente'; break;
                case 'I': labelEstado = 'Inactivo'; break;
                default: labelEstado = row.estado || ''; break;
            }
            
            var horas_trab = parseFloat(row.horas_trab) || 0;
            var valor_pactado = parseFloat(row.valor_pactado) || 0;
            var total_valor_pactado = horas_trab * valor_pactado;
            sumTotalHoras += horas_trab;
            sumTotalHorasMonto += total_valor_pactado;
            
            var btnAcciones = '';
            if ((row.img_ini && row.img_ini !== 'null') || (row.img_fin && row.img_fin !== 'null')) {
                btnAcciones = '<button type="button" class="btn btn-default btn-xs" onclick="verImagenesHorometro(\''+row.img_ini+'\', \''+row.img_fin+'\')" title="Ver Evidencias"><i class="fa fa-search"></i></button>';
            } else {
                btnAcciones = '<span class="text-muted" style="font-size:10px;">N/A</span>';
            }

            tbodyHor.append('<tr>'+
                '<td>'+(row.fecha||'')+'</td>'+
                '<td>'+(row.chofer||'')+'</td>'+
                '<td class="text-right">'+(row.hora_ini||'0')+'</td>'+
                '<td class="text-right">'+(row.hora_fin||'0')+'</td>'+
                '<td class="text-right">'+(row.lec_ini||'0.00')+'</td>'+
                '<td class="text-right">'+(row.lec_fin||'0.00')+'</td>'+
                '<td class="text-right font-weight-bold" style="color:#3b82f6;">'+horas_trab.toFixed(2)+'</td>'+
                '<td class="text-right font-weight-bold" style="color:#10b981;">$'+total_valor_pactado.toFixed(2)+'</td>'+
                '<td class="text-center">'+labelEstado+'</td>'+
                '<td>'+(row.observacion||'')+'</td>'+
                '<td class="text-center">'+btnAcciones+'</td>'+
            '</tr>');
        });
        
        $('#lblTotalHor').text(sumTotalHoras.toFixed(2));
        $('#lblTotalHorMonto').text('$' + sumTotalHorasMonto.toFixed(2));
        $('#lblModalTotalHor').text(data.horometro.length);
    } else {
        $('#bdg_hor').text('0');
        $('#lblModalTotalHor').text('0');
        $('#lblTotalHor').text('0.00');
        $('#lblTotalHorMonto').text('$0.00');
        tbodyHor.html('<tr><td colspan="11" class="text-center text-muted">No se encontraron registros de horómetro pendientes en el periodo.</td></tr>');
    }
    
    // 2. Combustible
    var tbodyComb = $('#tblCombustible tbody');
    tbodyComb.empty();
    var sumTotalComb = 0;
    
    if (data.combustible && data.combustible.length > 0) {
        hasData = true;
        $('#bdg_comb').text(data.combustible.length);
        $('#lblModalTotalCom').text(data.combustible.length);
        $.each(data.combustible, function(i, row) {
            var costo = parseFloat(row.costo) || 0;
            sumTotalComb += costo;
            
            tbodyComb.append('<tr>'+
                '<td>'+(row.fecha||'')+'</td>'+
                '<td class="text-right">'+parseFloat(row.cantidad).toFixed(2)+'</td>'+
                '<td class="text-right">$'+parseFloat(row.precio_unitario).toFixed(2)+'</td>'+
                '<td class="text-right font-weight-bold" style="color:#ca8a04;">$'+costo.toFixed(2)+'</td>'+
                '<td>'+(row.chofer||'')+'</td>'+
                '<td>'+(row.observacion||'')+'</td>'+
            '</tr>');
        });
        
        $('#lblTotalCom').text('$' + sumTotalComb.toFixed(2));
    } else {
        $('#bdg_comb').text('0');
        $('#lblModalTotalCom').text('0');
        $('#lblTotalCom').text('$0.00');
        tbodyComb.html('<tr><td colspan="6" class="text-center text-muted">No se encontraron despachos de combustible.</td></tr>');
    }

    // 4. Compras (Vacio por requerimiento de DB)
    var tbodyCom = $('#tblCompras tbody');
    tbodyCom.empty();
    if (data.compras && data.compras.length > 0) {
        // En caso de que se agreguen despues
    } else {
        $('#bdg_com').text('0');
        tbodyCom.html('<tr><td colspan="4" class="text-center text-muted">A la espera de estructura de módulo de compras / No hay registros.</td></tr>');
    }

    // Actualizar KPIs (Tarjetas superiores)
    if (data.resumen) {
        $('#kpi_horas').text(parseFloat(data.resumen.total_horas).toFixed(2));
        $('#kpi_comb_gal').text(parseFloat(data.resumen.total_combustible).toFixed(2) + ' Gls');
        $('#kpi_comb_cost').text('$' + parseFloat(data.resumen.costo_combustible).toFixed(2));
        $('#kpi_compras').text('$' + parseFloat(data.resumen.total_compras).toFixed(2));
        $('#kpi_total').text('$' + parseFloat(data.resumen.costo_total_referencial).toFixed(2));
    }
    
    // Actualizar TAB Resumen Económico (Nuevo Diseño)
    var vehiculoTexto = "";
    var operadorTexto = "";
    var periodoTexto = "";
    
    if (data.resumen && data.resumen.vehiculo_desc) {
        // Modo histórico
        vehiculoTexto = data.resumen.vehiculo_desc;
        operadorTexto = data.resumen.chofer_desc;
        periodoTexto = (data.resumen.Mal_Fec_Ini || '') + " al " + (data.resumen.Mal_Fec_Fin || '');
        
        // Asignar los descuentos/totales guardados al visualizador
        $('#inp_descuento_hora').val(data.resumen.Mal_Des_Hor).prop('disabled', true);
        
        // Configurar botones Histórico
        $('#btnCancelarPre').hide();
        $('#btnGuardarPre').hide();
        $('#btnAtrasPre').show();
        $('#txtImprimirPre').html('<i class="fa fa-print"></i> Imprimir Reporte');
    } else {
        // Modo nuevo
        vehiculoTexto = $("#fil_vehiculo option:selected").text();
        operadorTexto = $("#fil_operador option:selected").text();
        if (vehiculoTexto === "Todos los Vehículos" || !$('#fil_vehiculo').val()) vehiculoTexto = "NO SELECCIONADO";
        if (operadorTexto === "Todos los Operadores" || !$('#fil_operador').val()) operadorTexto = "NO SELECCIONADO";
        periodoTexto = $('#fil_fec_ini').val() + " al " + $('#fil_fec_fin').val();
        
        $('#inp_descuento_hora').prop('disabled', false);
        
        // Configurar botones Nuevo
        $('#btnCancelarPre').show();
        $('#btnGuardarPre').show();
        $('#btnAtrasPre').hide();
        $('#txtImprimirPre').html('<i class="fa fa-print"></i> Imprimir Vista Previa');
    }
    
    $('#res_vehiculo').text(vehiculoTexto);
    $('#res_operador').text(operadorTexto);
    $('#res_periodo').text(periodoTexto);
    
    var nroPreliq = (data.resumen && data.resumen.next_mal_num) ? data.resumen.next_mal_num : "NUEVO DOCUMENTO";
    $('#res_num_doc').text(nroPreliq);
    
    $('#res_cant_hor').text((data.horometro ? data.horometro.length : 0) + ' Registros');
    var horasTrab = data.resumen ? parseFloat(data.resumen.total_horas) : 0;
    $('#res_tot_hor').text(horasTrab.toFixed(2) + ' Horas');
    
    $('#res_cant_com').text((data.combustible ? data.combustible.length : 0) + ' Despachos');
    $('#res_gls_com').text((data.resumen ? parseFloat(data.resumen.total_combustible).toFixed(2) : '0.00') + ' Galones');
    $('#res_cost_com').text('$' + (data.resumen ? parseFloat(data.resumen.costo_combustible).toFixed(2) : '0.00'));
    $('#res_cost_gas').text('$' + (data.resumen ? parseFloat(data.resumen.total_compras).toFixed(2) : '0.00'));
    
    // Extraer Valor Pactado de la primera fila de horómetro (si existe)
    var val_hora = 0;
    if (data.horometro && data.horometro.length > 0) {
        val_hora = parseFloat(data.horometro[0].valor_pactado) || 0;
    }
    
    $('#res_val_hora').text(val_hora.toFixed(2));
    $('#res_horas_trab').text(horasTrab.toFixed(2));
    
    // Recalcular Económico
    recalcularEconomico(data);

    if (hasData) {
        $('#btnGuardarPre').prop('disabled', false);
        $('#btnImprimirPre').prop('disabled', false);
        
        // Cambiar automáticamente a la pestaña de resumen
        $('.nav-tabs a[href="#tab-resumen"]').tab('show');
    } else {
        $('#btnGuardarPre').prop('disabled', true);
        $('#btnImprimirPre').prop('disabled', true);
        if(typeof $.alert !== 'undefined') $.alert('No existen registros pendientes para preliquidar en este periodo.');
    }
}

function recalcularEconomico(data) {
    var val_hora = parseFloat($('#res_val_hora').text()) || 0;
    var horasTrab = parseFloat($('#res_horas_trab').text()) || 0;
    var subtotal = val_hora * horasTrab;
    
    var descuento_hora = parseFloat($('#inp_descuento_hora').val()) || 0;
    var total_descuento = descuento_hora * horasTrab;
    var total_cobrar = subtotal - total_descuento;
    
    // Si viene en modo historico, mostramos los valores reales almacenados (sobreescribe los calculos)
    if (data && data.resumen && data.resumen.vehiculo_desc) {
        total_descuento = parseFloat(data.resumen.Mal_Tot_Des) || 0;
        total_cobrar = parseFloat(data.resumen.Mal_Tot_Cob) || 0;
    }
    
    $('#res_subtotal').text(subtotal.toFixed(2));
    $('#res_tot_des').text(total_descuento.toFixed(2));
    $('#res_tot_cobrar').text(total_cobrar.toFixed(2));
}

function guardarPreliquidacionEconomica() {
    var ini = $('#fil_fec_ini').val();
    var fin = $('#fil_fec_fin').val();
    var obs = $('#inp_observaciones').val();
    var veh = $('#fil_vehiculo').val();
    var cho = $('#fil_operador').val();
    
    var horasTrab = parseFloat($('#res_horas_trab').text()) || 0;
    var hasHor = parseInt($('#res_cant_hor').text()) > 0 ? 1 : 0;
    var hasCom = parseInt($('#res_cant_com').text()) > 0 ? 1 : 0;
    
    if (horasTrab <= 0) {
        if (typeof $.alert !== 'undefined') $.alert('Las horas trabajadas deben ser mayores a 0 para generar la preliquidación.');
        return;
    }
    
    if (!veh) {
        if (typeof $.alert !== 'undefined') $.alert('Debe seleccionar un vehículo específico.');
        return;
    }
    
    if (!cho) {
        if (typeof $.alert !== 'undefined') $.alert('Debe seleccionar un operador específico.');
        return;
    }
    
    if (hasHor === 0) {
        if (typeof $.alert !== 'undefined') $.alert('No existen registros de horómetro.');
        return;
    }
    
    var proceder = function() {
        if (typeof $.carga === 'function') { $.carga('show'); }
        $('#btnGuardarPre').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Guardando...');
        
        var mal_tot_hor = horasTrab;
        var mal_des_hor = parseFloat($('#inp_descuento_hora').val()) || 0;
        var mal_tot_des = parseFloat($('#res_tot_des').text()) || 0;
        var mal_tot_cob = parseFloat($('#res_tot_cobrar').text()) || 0;

        $.ajax({
            url: 'man_alt_maquinaria_preliquidacion.php',
            type: 'POST',
            data: {
                guardarPreliquidacionAjax: 1,
                fecha_ini: ini,
                fecha_fin: fin,
                veh_cod: veh,
                cho_cod: cho,
                observacion: obs,
                has_hor: hasHor,
                has_com: hasCom,
                mal_tot_hor: mal_tot_hor,
                mal_des_hor: mal_des_hor,
                mal_tot_des: mal_tot_des,
                mal_tot_cob: mal_tot_cob
            },
            dataType: 'json',
            success: function(res) {
                if (typeof $.carga === 'function') { $.carga('hide'); }
                $('#btnGuardarPre').prop('disabled', false).html('<i class="fa fa-save"></i> Asentar Preliquidación');
                
                if (res.success) {
                    if (typeof $.alert !== 'undefined') {
                        $.alert(res.message, function () {
                            cerrarDetalleHistorico();
                        });
                    } else {
                        alert(res.message);
                        cerrarDetalleHistorico();
                    }
                } else {
                    if (typeof $.alert !== 'undefined') {
                        $.alert(res.message);
                    } else {
                        alert(res.message);
                    }
                }
            },
            error: function() {
                if (typeof $.carga === 'function') { $.carga('hide'); }
                $('#btnGuardarPre').prop('disabled', false).html('<i class="fa fa-save"></i> Asentar Preliquidación');
                if (typeof $.alert !== 'undefined') $.alert('Error de red al intentar guardar.');
            }
        });
    };
    
    if (hasCom === 0) {
        if (typeof $.confirm !== 'undefined') {
            $.confirm({
                title: 'Advertencia',
                content: 'No existen registros de combustible. ¿Desea guardar de todas formas?',
                type: 'orange',
                buttons: {
                    guardar: {
                        text: 'Sí, Guardar',
                        btnClass: 'btn-success',
                        action: proceder
                    },
                    cancelar: {
                        text: 'Cancelar'
                    }
                }
            });
        } else {
            if (confirm('No existen registros de combustible. ¿Desea guardar de todas formas?')) {
                proceder();
            }
        }
    } else {
        proceder();
    }
}

function modalGuardar() {
    var totalHor = parseInt($('#lblModalTotalHor').text()) || 0;
    var totalCom = parseInt($('#lblModalTotalCom').text()) || 0;
    if (totalHor === 0 && totalCom === 0) {
        if (typeof $.alert !== 'undefined') $.alert('Debe generar la consulta y tener registros pendientes.');
        return;
    }
    $('#txtModalObs').val('');
    $('#modalGuardarPre').modal('show');
}

function ejecutarGuardar() {
    var ini = $('#fil_fec_ini').val();
    var fin = $('#fil_fec_fin').val();
    var obs = $('#txtModalObs').val();
    var veh = $('#fil_vehiculo').val();
    var cho = $('#fil_operador').val();

    var hasHor = parseInt($('#lblModalTotalHor').text()) > 0 ? 1 : 0;
    var hasCom = parseInt($('#lblModalTotalCom').text()) > 0 ? 1 : 0;

    var dataPost = {
        guardarPreliquidacionAjax: 1,
        fecha_ini: ini,
        fecha_fin: fin,
        veh_cod: veh,
        cho_cod: cho,
        observacion: obs,
        has_hor: hasHor,
        has_com: hasCom
    };

    if (typeof $.carga === 'function') { $.carga('show'); }
    $('#btnConfirmarGuardar').prop('disabled', true).text('Guardando...');
    
    $.ajax({
        url: 'man_alt_maquinaria_preliquidacion.php',
        type: 'POST',
        data: dataPost,
        dataType: 'json',
        success: function(res) {
            if (typeof $.carga === 'function') { $.carga('hide'); }
            $('#btnConfirmarGuardar').prop('disabled', false).text('Confirmar y Guardar');
            
            if (res.success) {
                $('#modalGuardarPre').modal('hide');
                if (typeof $.alert !== 'undefined') $.alert(res.message);
                
                // Recargar grillas
                $('#btnGenerar').click();
                cargarRealizadas();
            } else {
                if (typeof $.alert !== 'undefined') $.alert(res.message || 'Error al guardar.');
            }
        },
        error: function(xhr, status, error) {
            if (typeof $.carga === 'function') { $.carga('hide'); }
            $('#btnConfirmarGuardar').prop('disabled', false).text('Confirmar y Guardar');
            if (typeof $.alert !== 'undefined') $.alert('Error de red al intentar guardar.');
        }
    });
}

function imprimirPre() {
    var vehiculo = $('#res_vehiculo').text();
    var operador = $('#res_operador').text();
    var periodo = $('#res_periodo').text();
    var nroPreliq = $('#res_num_doc').text();
    
    var valHora = parseFloat($('#res_val_hora').text()) || 0;
    var hrsTrab = parseFloat($('#res_horas_trab').text()) || 0;
    var subHoras = parseFloat($('#res_subtotal').text()) || 0;
    
    var glsComb = parseFloat($('#res_gls_com').text()) || 0;
    var subComb = parseFloat($('#res_cost_com').text().replace('$','')) || 0;
    
    var valCompras = parseFloat($('#res_cost_gas').text().replace('$','')) || 0;
    
    var dctoHora = parseFloat($('#inp_descuento_hora').val()) || 0;
    var dctoBase = dctoHora * hrsTrab;
    
    var totCobrar = parseFloat($('#res_tot_cobrar').text()) || 0;
    
    var observaciones = $('#inp_observaciones').val();
    var estado = $('#lblEstadoVisual').text().trim() || 'PENDIENTE';

    var html = `
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Preliquidación - ${nroPreliq}</title>
        <style>
            body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; padding: 20px; font-size: 14px; color: #333; margin: 0; }
            .header { text-align: center; border-bottom: 2px solid #333; margin-bottom: 15px; padding-bottom: 10px; }
            .header h2 { margin: 0; font-size: 20px; color: #1e293b; }
            .header p { margin: 5px 0 0 0; color: #64748b; font-size: 13px; }
            
            .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px; }
            .info-box { border: 1px solid #cbd5e1; padding: 12px; border-radius: 6px; background: #f8fafc; }
            .info-box h4 { margin: 0 0 8px 0; font-size: 12px; text-transform: uppercase; color: #64748b; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; }
            .info-item { margin-bottom: 6px; font-size: 13px; }
            .info-item strong { display: inline-block; width: 120px; }
            
            .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 13px; }
            .table th, .table td { border: 1px solid #cbd5e1; padding: 8px; text-align: left; }
            .table th { background: #f1f5f9; color: #334155; font-size: 11px; text-transform: uppercase; }
            .text-right { text-align: right; }
            
            .totales { width: 300px; float: right; border: 2px solid #0f172a; border-radius: 6px; padding: 12px; background: #f8fafc; }
            .totales-row { display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 14px; }
            .totales-row.gran-total { font-size: 18px; font-weight: bold; border-top: 2px solid #cbd5e1; padding-top: 8px; margin-top: 4px; color: #0f172a; }
            .totales-row.descuento { color: #ef4444; }
            
            .clearfix::after { content: ""; display: table; clear: both; }
            .observaciones { margin-top: 30px; border-top: 1px dashed #cbd5e1; padding-top: 15px; font-size: 13px; }
            
            @media print {
                @page { margin: 1cm; }
                body { padding: 0; }
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h2>REPORTE DE PRELIQUIDACIÓN</h2>
            <p>Documento: <strong>${nroPreliq}</strong> | Estado: <strong>${estado}</strong></p>
        </div>
        
        <div class="info-grid">
            <div class="info-box">
                <h4>Datos Generales</h4>
                <div class="info-item"><strong>Vehículo/Maq:</strong> ${vehiculo}</div>
                <div class="info-item"><strong>Operador/Chofer:</strong> ${operador}</div>
                <div class="info-item"><strong>Período:</strong> ${periodo}</div>
            </div>
            <div class="info-box">
                <h4>Resumen Horas y Combustible</h4>
                <div class="info-item"><strong>Horas Totales:</strong> ${hrsTrab.toFixed(2)} hrs</div>
                <div class="info-item"><strong>Valor x Hora:</strong> $${valHora.toFixed(2)}</div>
                <div class="info-item"><strong>Total Galones:</strong> ${glsComb.toFixed(2)} Gls</div>
            </div>
        </div>
        
        <table class="table">
            <thead>
                <tr>
                    <th>Concepto</th>
                    <th class="text-right">Cantidad / Base</th>
                    <th class="text-right">Valor Unitario</th>
                    <th class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Ingresos por Horas Trabajadas</td>
                    <td class="text-right">${hrsTrab.toFixed(2)} hrs</td>
                    <td class="text-right">$${valHora.toFixed(2)}</td>
                    <td class="text-right">$${subHoras.toFixed(2)}</td>
                </tr>
                <tr>
                    <td>Deducción por Combustible</td>
                    <td class="text-right">${glsComb.toFixed(2)} Gls</td>
                    <td class="text-right">-</td>
                    <td class="text-right">-$${subComb.toFixed(2)}</td>
                </tr>
                <tr>
                    <td>Compras y Gastos Adicionales</td>
                    <td class="text-right">-</td>
                    <td class="text-right">-</td>
                    <td class="text-right">-$${valCompras.toFixed(2)}</td>
                </tr>
                <tr>
                    <td>Descuento General</td>
                    <td class="text-right">${hrsTrab.toFixed(2)} hrs</td>
                    <td class="text-right">$${dctoHora.toFixed(2)} /h</td>
                    <td class="text-right">-$${dctoBase.toFixed(2)}</td>
                </tr>
            </tbody>
        </table>
        
        <div class="clearfix">
            <div class="totales">
                <div class="totales-row">
                    <span>Subtotal Ingresos:</span>
                    <span>$${subHoras.toFixed(2)}</span>
                </div>
                <div class="totales-row descuento">
                    <span>(-) Total Descuentos:</span>
                    <span>-$${(subComb + valCompras + dctoBase).toFixed(2)}</span>
                </div>
                <div class="totales-row gran-total">
                    <span>TOTAL A COBRAR:</span>
                    <span>$${totCobrar.toFixed(2)}</span>
                </div>
            </div>
        </div>
        
        <div class="observaciones">
            <strong>Observaciones del Documento:</strong><br>
            <p>${observaciones || 'Ninguna observación ingresada.'}</p>
        </div>
        
        <div style="margin-top: 60px; text-align: center;">
            <div style="display:inline-block; width:40%; border-top: 1px solid #333; margin:0 5%; padding-top: 5px;">Firma de Conformidad (Operador)</div>
            <div style="display:inline-block; width:40%; border-top: 1px solid #333; margin:0 5%; padding-top: 5px;">Autorizado por</div>
        </div>
    </body>
    </html>
    `;

    var printIframe = document.getElementById('print-iframe-pre');
    if (!printIframe) {
        printIframe = document.createElement('iframe');
        printIframe.id = 'print-iframe-pre';
        printIframe.style.position = 'absolute';
        printIframe.style.width = '0px';
        printIframe.style.height = '0px';
        printIframe.style.border = 'none';
        document.body.appendChild(printIframe);
    }
    
    var doc = printIframe.contentWindow.document;
    doc.open();
    doc.write(html);
    doc.close();

    setTimeout(function() {
        printIframe.contentWindow.focus();
        printIframe.contentWindow.print();
    }, 500);
}

function cargarRealizadas() {
    var tbodyReal = $('#tblRealizadas tbody');
    tbodyReal.html('<tr><td colspan="7" class="text-center text-muted">Cargando registros...</td></tr>');
    
    var params = {
        listRealizadasAjax: 1,
        fil_hist_est: $('#fil_hist_est').val() || '',
        fil_hist_veh: $('#fil_hist_veh').val() || '',
        fil_hist_doc: $('#fil_hist_doc').val() || ''
    };
    
    $.ajax({
        url: 'man_alt_maquinaria_preliquidacion.php',
        type: 'GET',
        data: params,
        dataType: 'json',
        success: function(res) {
            tbodyReal.empty();
            if (res.success && res.data && res.data.length > 0) {
                $.each(res.data, function(i, row) {
                    var badgeEstado = '';
                    if (row.estado == 'I') {
                        badgeEstado = '<span class="label label-danger">ANULADO</span>';
                    } else if (row.estado == 'A' && (!row.Cop_Cod || row.Cop_Cod == 0)) {
                        badgeEstado = '<span class="label label-success">PRELIQUIDADO</span>';
                    } else if (row.estado == 'A' && row.Cop_Cod > 0) {
                        badgeEstado = '<span class="label label-primary">LIQUIDADO</span>';
                    } else {
                        badgeEstado = '<span class="label label-default">'+row.estado+'</span>';
                    }
                    
                    var id_show = row.Mal_Num ? row.Mal_Num : ('#' + row.Mal_Cod);
                    
                    tbodyReal.append('<tr>'+
                        '<td>'+id_show+'</td>'+
                        '<td>'+(row.fecha||'')+'</td>'+
                        '<td>'+(row.vehiculo||'')+'</td>'+
                        '<td>'+(row.periodo||'')+'</td>'+
                        '<td>'+(row.usuario||'')+'</td>'+
                        '<td>'+badgeEstado+'</td>'+
                        '<td class="text-center">'+
                            '<button type="button" class="btn btn-xs btn-info" onclick="verDetalleHistorico('+row.Mal_Cod+')"><i class="fa fa-search"></i> Ver Detalle</button>'+
                        '</td>'+
                    '</tr>');
                });
            } else {
                tbodyReal.html('<tr><td colspan="7" class="text-center">No se encontraron preliquidaciones guardadas</td></tr>');
            }
        },
        error: function() {
            tbodyReal.html('<tr><td colspan="7" class="text-center text-danger">Error de conexión al cargar historial.</td></tr>');
        }
    });
}

$(document).ready(function() {
    cargarRealizadas();
});

function verDetalleHistorico(mal_cod) {
    if (typeof $.carga === 'function') { $.carga('show'); }
    
    $.ajax({
        url: 'man_alt_maquinaria_preliquidacion.php?getDetalleHistoricoAjax=1&mal_cod=' + mal_cod,
        type: 'GET',
        dataType: 'json',
        success: function(res) {
            if (typeof $.carga === 'function') { $.carga('hide'); }
            
            if (res.success) {
                poblarTablas(res);
                $('#lblEstadoVisual').html('<span class="label label-primary">HISTÓRICO (#'+mal_cod+')</span>');
                $('#btnGuardarPre').prop('disabled', true);
                
                // Cambiar focus al tab de resumen
                $('#tabsDetalle a[href="#tab-resumen"]').tab('show');
            } else {
                if (typeof $.alert !== 'undefined') $.alert('Error al cargar detalle histórico.');
            }
        },
        error: function() {
            if (typeof $.carga === 'function') { $.carga('hide'); }
            if (typeof $.alert !== 'undefined') $.alert('Error de red al intentar ver detalle.');
        }
    });
}

// -------------------------------------------------------------------------
// MODO MASIVO
// -------------------------------------------------------------------------
function buscarPendientesMasivo() {
    var ini = $('#fil_mas_fec_ini').val();
    var fin = $('#fil_mas_fec_fin').val();
    var desc = parseFloat($('#fil_mas_desc').val()) || 0;
    
    if (!ini || !fin || ini > fin) {
        if (typeof $.alert !== 'undefined') $.alert('Fechas inválidas.');
        return;
    }

    if (typeof $.carga === 'function') { $.carga('show'); }

    $.ajax({
        url: 'man_alt_maquinaria_preliquidacion.php',
        type: 'POST',
        data: {
            listMasivoAjax: 1,
            fecha_ini: ini,
            fecha_fin: fin,
            descuento: desc
        },
        dataType: 'json',
        success: function(res) {
            if (typeof $.carga === 'function') { $.carga('hide'); }
            
            var tbody = $('#tblMasivo tbody');
            tbody.empty();
            $('#chkMasivoAll').prop('checked', false);

            if (res.success && res.data && res.data.length > 0) {
                $.each(res.data, function(i, row) {
                    var chk = '';
                    var colorEstado = 'label-info';
                    var txtEstado = 'Pendiente';
                    
                    if (parseFloat(row.valor_hora) === 0) {
                        txtEstado = 'Bloqueado: Sin Valor Hora';
                        colorEstado = 'label-danger';
                        chk = '<input type="checkbox" disabled>';
                    } else if (parseFloat(row.combustible_cargado) === 0) {
                        txtEstado = 'Advertencia: Sin Comb.';
                        colorEstado = 'label-warning';
                        chk = '<input type="checkbox" class="chkMasivoRow" value="'+row.Veh_Cod+'_'+row.Cho_Cod+'">';
                    } else {
                        chk = '<input type="checkbox" class="chkMasivoRow" value="'+row.Veh_Cod+'_'+row.Cho_Cod+'">';
                    }

                    var tr = '<tr style="cursor:pointer;" onclick="toggleRowCheckbox(this, event)">'+
                        '<td class="text-center">'+chk+'</td>'+
                        '<td>'+(row.vehiculo_desc||'--')+'</td>'+
                        '<td>'+(row.chofer_desc||'--')+'</td>'+
                        '<td class="text-right">'+row.total_registros_horometro+'</td>'+
                        '<td class="text-right font-weight-bold" style="color:#3b82f6;">'+parseFloat(row.total_horas).toFixed(2)+'</td>'+
                        '<td class="text-right">'+row.total_despachos+'</td>'+
                        '<td class="text-right font-weight-bold" style="color:#ca8a04;">'+parseFloat(row.combustible_cargado).toFixed(2)+'</td>'+
                        '<td class="text-right">$'+parseFloat(row.valor_hora).toFixed(2)+'</td>'+
                        '<td class="text-right">$'+parseFloat(row.descuento_hora).toFixed(2)+'</td>'+
                        '<td class="text-right text-danger">$'+parseFloat(row.total_descuento).toFixed(2)+'</td>'+
                        '<td class="text-right font-weight-bold" style="color:#15803d; font-size:16px;">$'+parseFloat(row.total_cobrar).toFixed(2)+'</td>'+
                        '<td class="text-center"><span class="label '+colorEstado+'">'+txtEstado+'</span></td>'+
                        '<td class="text-center"><button class="btn btn-xs btn-info" onclick="verDetalleMasivo(\''+row.Veh_Cod+'\', \''+row.Cho_Cod+'\', this)"><i class="fa fa-eye"></i> Detalle</button></td>'+
                    '</tr>'+
                    '<tr id="det_'+row.Veh_Cod+'_'+row.Cho_Cod+'" style="display:none; background:#f8fafc;"><td colspan="13" class="p-0"><div style="padding:15px;" class="det-content">Cargando...</div></td></tr>';
                    tbody.append(tr);
                });
                $('#btnGuardarMasivo').html('<i class="fa fa-cogs"></i> Generar Documentos Seleccionados');
                actualizarBotonGenerarMasivo();
            } else {
                tbody.html('<tr><td colspan="13" class="text-center text-muted">No se encontraron preliquidaciones pendientes.</td></tr>');
                $('#btnGuardarMasivo').prop('disabled', true);
            }
        },
        error: function() {
            if (typeof $.carga === 'function') { $.carga('hide'); }
            if (typeof $.alert !== 'undefined') $.alert('Error al buscar lote masivo.');
        }
    });
}

function toggleMasivoAll(source) {
    $('.chkMasivoRow:not(:disabled)').prop('checked', source.checked);
    actualizarBotonGenerarMasivo();
}

function toggleRowCheckbox(row, e) {
    // Ignorar clics en botones u otros inputs para no duplicar acciones
    var tag = e.target.tagName.toLowerCase();
    if (tag === 'button' || tag === 'i') {
        return;
    }
    
    // Si no hizo clic directamente en el checkbox, lo cambiamos nosotros
    if (tag !== 'input') {
        var chk = $(row).find('.chkMasivoRow');
        if (!chk.prop('disabled')) {
            chk.prop('checked', !chk.prop('checked'));
        }
    }
    
    actualizarBotonGenerarMasivo();
}

function actualizarBotonGenerarMasivo() {
    var seleccionados = $('.chkMasivoRow:checked').length;
    $('#btnGuardarMasivo').prop('disabled', seleccionados === 0);
}

function guardarMasivoAjax() {
    var ini = $('#fil_mas_fec_ini').val();
    var fin = $('#fil_mas_fec_fin').val();
    var desc = parseFloat($('#fil_mas_desc').val()) || 0;
    var obs = $('#fil_mas_obs').val();

    var seleccionados = [];
    $('.chkMasivoRow:checked').each(function() {
        seleccionados.push($(this).val());
    });

    if (seleccionados.length === 0) {
        if (typeof $.alert !== 'undefined') $.alert('Debe seleccionar al menos una fila.');
        return;
    }

    if(confirm('Se generarán ' + seleccionados.length + ' preliquidaciones. ¿Desea continuar?')) {
        if (typeof $.carga === 'function') { $.carga('show'); }
        $('#btnGuardarMasivo').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Generando...');
        
        $.ajax({
            url: 'man_alt_maquinaria_preliquidacion.php',
            type: 'POST',
            data: {
                guardarMasivoAjax: 1,
                fecha_ini: ini,
                fecha_fin: fin,
                descuento: desc,
                observacion: obs,
                seleccionados: seleccionados
            },
            dataType: 'json',
            success: function(res) {
                if (typeof $.carga === 'function') { $.carga('hide'); }
                $('#btnGuardarMasivo').prop('disabled', false).html('<i class="fa fa-cogs"></i> Generar Documentos Seleccionados');
                
                if (res.success) {
                    if (typeof $.alert !== 'undefined') {
                        $.alert(res.message, function() { 
                            buscarPendientesMasivo(); 
                        });
                    } else {
                        alert(res.message);
                        buscarPendientesMasivo();
                    }
                } else {
                    if (typeof $.alert !== 'undefined') $.alert(res.message);
                    else alert(res.message);
                }
            },
            error: function() {
                if (typeof $.carga === 'function') { $.carga('hide'); }
                $('#btnGuardarMasivo').prop('disabled', false).html('<i class="fa fa-cogs"></i> Generar Documentos Seleccionados');
                if (typeof $.alert !== 'undefined') $.alert('Error al guardar el lote masivo.');
            }
        });
    }
}

function verDetalleMasivo(veh_cod, cho_cod, btn) {
    var trDet = $('#det_' + veh_cod + '_' + cho_cod);
    var content = trDet.find('.det-content');
    
    if (trDet.is(':visible')) {
        trDet.hide();
        $(btn).html('<i class="fa fa-eye"></i> Detalle');
        return;
    }
    
    trDet.show();
    $(btn).html('<i class="fa fa-eye-slash"></i> Ocultar');
    content.html('<div class="text-center text-muted"><i class="fa fa-spinner fa-spin"></i> Cargando detalles...</div>');
    
    var ini = $('#fil_mas_fec_ini').val();
    var fin = $('#fil_mas_fec_fin').val();
    
    $.ajax({
        url: 'man_alt_maquinaria_preliquidacion.php',
        type: 'POST',
        data: {
            getDetalleMasivoRowAjax: 1,
            veh_cod: veh_cod,
            cho_cod: cho_cod,
            fecha_ini: ini,
            fecha_fin: fin
        },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                var html = '<div class="row"><div class="col-sm-6"><h5><i class="fa fa-clock-o"></i> Horómetros ('+res.horometros.length+')</h5><table class="table table-condensed table-bordered" style="background:#fff;"><thead><tr><th>Fecha</th><th>Horas</th></tr></thead><tbody>';
                $.each(res.horometros, function(i, h) {
                    html += '<tr><td>'+h.fecha+'</td><td class="text-right">'+h.horas_trab+'</td></tr>';
                });
                html += '</tbody></table></div><div class="col-sm-6"><h5><i class="fa fa-tint"></i> Combustible ('+res.combustibles.length+')</h5><table class="table table-condensed table-bordered" style="background:#fff;"><thead><tr><th>Fecha</th><th>Galones</th><th>Costo</th></tr></thead><tbody>';
                $.each(res.combustibles, function(i, c) {
                    html += '<tr><td>'+c.fecha+'</td><td class="text-right">'+parseFloat(c.cantidad).toFixed(2)+'</td><td class="text-right">$'+parseFloat(c.costo).toFixed(2)+'</td></tr>';
                });
                html += '</tbody></table></div></div>';
                content.html(html);
            } else {
                content.html('<div class="alert alert-warning">No se pudo cargar el detalle.</div>');
            }
        },
        error: function() {
            content.html('<div class="alert alert-danger">Error de conexión.</div>');
        }
    });
}

function cerrarDetalleHistorico() {
    // Limpiar UI visualizada
    $('#tblHorometro tbody').empty();
    $('#tblCombustible tbody').empty();
    $('#res_vehiculo').text('');
    $('#res_operador').text('');
    $('#res_periodo').text('');
    $('#res_num_doc').text('');
    $('#res_cant_hor, #res_tot_hor, #res_cant_com, #res_gls_com, #res_cost_com, #res_cost_gas').text('');
    $('#res_val_hora, #res_horas_trab, #res_subtotal, #res_tot_des, #res_tot_cobrar').text('');
    $('#inp_descuento_hora').val('0').prop('disabled', false);
    $('#btnGuardarPre').prop('disabled', true);
    $('#lblEstadoVisual').html('');
    
    // Restaurar botones por defecto
    $('#btnCancelarPre').show();
    $('#btnGuardarPre').show();
    $('#btnAtrasPre').hide();
    $('#txtImprimirPre').html('<i class="fa fa-print"></i> Imprimir Vista Previa');
    
    // Cambiar al tab Historial y refrescar
    $('#tabsDetalle a[href="#tab-realizadas"]').tab('show');
    cargarRealizadas();
}

// -------------------------------------------------------------------------
// FUNCIONES AUXILIARES PARA VER EVIDENCIAS (MODAL)
// -------------------------------------------------------------------------
function verImagenesHorometro(imgIni, imgFin) {
    var base_url = '../../imagenes/620/horometro/';
    
    if (imgIni && imgIni !== 'null' && imgIni !== 'undefined') {
        $('#visor_preliq_img_ini').html('<img src="' + base_url + imgIni + '" style="max-width:100%; max-height:400px; border-radius:4px;" />');
    } else {
        $('#visor_preliq_img_ini').html('<span class="text-muted">Sin imagen inicial</span>');
    }
    
    if (imgFin && imgFin !== 'null' && imgFin !== 'undefined') {
        $('#visor_preliq_img_fin').html('<img src="' + base_url + imgFin + '" style="max-width:100%; max-height:400px; border-radius:4px;" />');
    } else {
        $('#visor_preliq_img_fin').html('<span class="text-muted">Sin imagen final</span>');
    }
    
    $('#modalVerImagenes').modal('show');
}
