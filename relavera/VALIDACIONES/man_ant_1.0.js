// Función para descargar la imagen del voucher
function descargarImagenVoucher() {
    var imagenData = $("#pagosForm #Ama_Img").val() || $("#Ama_Img").val();

    if (!imagenData || imagenData.trim() === '') {
        $.alert('No hay imagen para descargar');
        return;
    }

    try {
        // Si es un data URI (data:image/...)
        if (imagenData.startsWith('data:')) {
            // Extraer el tipo MIME y los datos base64
            var matches = imagenData.match(/^data:([^;]+);base64,(.+)$/);
            if (!matches || matches.length !== 3) {
                $.alert('Formato de imagen no válido');
                return;
            }

            var mimeType = matches[1];
            var base64Data = matches[2];

            // Convertir base64 a binario
            var byteCharacters = atob(base64Data);
            var byteNumbers = new Array(byteCharacters.length);
            for (var i = 0; i < byteCharacters.length; i++) {
                byteNumbers[i] = byteCharacters.charCodeAt(i);
            }
            var byteArray = new Uint8Array(byteNumbers);
            var blob = new Blob([byteArray], { type: mimeType });

            // Determinar la extensión del archivo
            var extension = 'jpg';
            if (mimeType.includes('png')) extension = 'png';
            else if (mimeType.includes('gif')) extension = 'gif';

            // Crear URL temporal y descargar
            var url = window.URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = 'voucher_' + new Date().getTime() + '.' + extension;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        } else {
            // Si es una URL normal, abrir en nueva pestaña para descargar
            window.open(imagenData, '_blank');
        }
    } catch (error) {
        console.error('Error al descargar imagen:', error);
        $.alert('Error al descargar la imagen: ' + error.message);
    }
}

// Variable global para el nivel de zoom de la imagen
var zoomLevelImagenVoucher = 1.0;

// Función para hacer zoom en la imagen del voucher
function zoomImagenVoucher(factor) {
    zoomLevelImagenVoucher *= factor;

    // Limitar el zoom entre 0.1x y 5x
    if (zoomLevelImagenVoucher < 0.1) {
        zoomLevelImagenVoucher = 0.1;
    } else if (zoomLevelImagenVoucher > 5) {
        zoomLevelImagenVoucher = 5;
    }

    $("#imagenVoucherContent").css('transform', 'scale(' + zoomLevelImagenVoucher + ')');
    $("#zoomLevel").text(Math.round(zoomLevelImagenVoucher * 100) + '%');
}

// Función para restablecer el zoom
function resetZoomImagenVoucher() {
    zoomLevelImagenVoucher = 1.0;
    $("#imagenVoucherContent").css('transform', 'scale(1.0)');
    $("#zoomLevel").text('100%');
}

// Función para ver la imagen del voucher en un modal
function verImagenVoucher() {
    // Buscar la imagen en múltiples ubicaciones posibles
    var imagenData = $("#pagosForm #Ama_Img").val() ||
        $("#Ama_Img").val() ||
        $("#Ama_Img_Visual").attr('src') ||
        '';

    // Si no hay imagen en los campos, intentar obtenerla de la vista previa
    if (!imagenData || imagenData.trim() === '') {
        var previewSrc = $("#Ama_Img_Visual").attr('src');
        if (previewSrc && previewSrc.trim() !== '') {
            imagenData = previewSrc;
        }
    }

    if (!imagenData || imagenData.trim() === '') {
        $.alert('No hay imagen cargada para mostrar');
        return false;
    }

    // Configurar y mostrar el modal
    if ($("#imagenVoucherDialog").length === 0) {
        $.alert('Error: El modal de imagen no se encuentra en el DOM');
        return false;
    }

    // Resetear zoom al abrir
    resetZoomImagenVoucher();

    $("#imagenVoucherContent").attr('src', imagenData);

    // Usar el mismo tamaño que el modal de datos (600x350 aprox, pero ajustado para imagen)
    $("#imagenVoucherDialog").dialog({
        modal: true,
        width: 700,
        height: 550,
        resizable: true,
        position: { my: "center", at: "center", of: window },
        buttons: {
            "Cerrar": function () {
                $(this).dialog("close");
            }
        }
    });

    // Agregar evento de rueda del mouse para zoom
    $("#imagenVoucherContainer").off('wheel').on('wheel', function (e) {
        e.preventDefault();
        var delta = e.originalEvent.deltaY;
        if (delta < 0) {
            // Scroll hacia arriba = acercar
            zoomImagenVoucher(1.1);
        } else {
            // Scroll hacia abajo = alejar
            zoomImagenVoucher(0.9);
        }
    });

    return false; // Prevenir comportamiento por defecto del enlace
}

// Funcion auxiliar para habilitar/deshabilitar campos del formulario
function setFormReadOnly(readOnly) {
    if (readOnly) {
        // Modo solo lectura: deshabilitar todos los campos
        $("#pagosForm input:not([type='hidden'])").prop('readonly', true).css('background-color', '#f5f5f5');
        $("#pagosForm select").prop('disabled', true).css('background-color', '#f5f5f5');
        $("#pagosForm textarea").prop('readonly', true).css('background-color', '#f5f5f5');
        // Ocultar botón de guardar
        $("#pagosForm button[onclick*='AgregarPago']").hide();
        $("#pagosForm a[onclick*='AgregarPago']").hide();
        // Deshabilitar datepicker si existe
        if ($("#pagosForm .hasDatepicker").length > 0) {
            $("#pagosForm .hasDatepicker").datepicker('disable');
        }
        // Deshabilitar botones de búsqueda de cliente
        $("#pagosForm #btnBusCLi").hide();
        // Deshabilitar input file de imagen
        $("#pagosForm #Ama_Img_File").prop('disabled', true);
    } else {
        // Modo edición: habilitar todos los campos (respetando campos readonly nativos)
        $("#pagosForm input:not([type='hidden'])").each(function () {
            if (!$(this).hasClass('always-readonly')) {
                $(this).prop('readonly', false).css('background-color', '');
            }
        });
        $("#pagosForm select").prop('disabled', false).css('background-color', '');
        $("#pagosForm textarea").prop('readonly', false).css('background-color', '');
        // Mostrar botón de guardar
        $("#pagosForm button[onclick*='AgregarPago']").show();
        $("#pagosForm a[onclick*='AgregarPago']").show();
        // Habilitar datepicker si existe
        if ($("#pagosForm .hasDatepicker").length > 0) {
            $("#pagosForm .hasDatepicker").datepicker('enable');
        }
        // Habilitar botones de búsqueda según corresponda
        toggleBotonesCliente();
        // Habilitar input file de imagen
        $("#pagosForm #Ama_Img_File").prop('disabled', false);
    }
}

// Función para ver los detalles completos del anticipo (modo consulta)
function verDetallesAnticipo(rowObject) {
    // Si te pasan un array (como [row, idGrid]), recupera el objeto correcto
    var row = Array.isArray(rowObject) ? rowObject[0] : rowObject;

    if (!row || !row.Ama_Cod) {
        $.alert("No se encuentra el código del anticipo.");
        return;
    }

    // Limpia el formulario para evitar residuos
    $('#pagosForm')[0].reset();

    // Cambiar el título del modal a modo consulta
    $("#pagosDialog").dialog("option", "title", "Consultar Anticipo");

    // Hacer AJAX para obtener los datos completos del anticipo
    $.ajax({
        url: '../FRONT/man_ant_1.0.php',
        method: 'GET',
        data: { getAnticipoAjax: true, Ama_Cod: row.Ama_Cod },
        dataType: 'json',
        success: function (response) {
            if (response.success && response.data) {
                var data = response.data;

                // Guardar el código del anticipo
                $("#pagosForm #Ama_Cod").val(data.Ama_Cod || '');

                // Datos del Cliente
                $("#pagosForm #Cli_Cod").val(data.Cli_Cod || '');
                $("#pagosForm #Prs_Cod").val(data.Prs_Cod || '');
                $("#pagosForm #Prs_Ced").val(data.Prs_Ced || '');
                $("#pagosForm #nombre").val(data.cliente || '');
                $("#bandera_prov").val("sel");

                // Datos del Pago
                $("#pagosForm #Ama_Fec").val(data.Ama_Fec || '');
                $("#pagosForm #Pag_Cod").val(data.Ama_Tde || data.Pag_Cod || '').trigger('change');
                $("#pagosForm #Ama_Doc").val(data.Ama_Doc || '');
                $("#pagosForm #Pac_Val").val(data.Ama_Val || '0.00');
                $("#pagosForm #Ama_Obs").val(data.Ama_Obs || '');

                // Datos adicionales
                if (data.Pla_Cod) {
                    $("#pagosForm #Pla_Cod").val(data.Pla_Cod || '');
                }
                if (data.Pla_Nom) {
                    $("#pagosForm #Pla_Nom").val(data.Pla_Nom || '');
                }
                if (data.Pla_Lic) {
                    $("#pagosForm #Pla_Lic").val(data.Pla_Lic || '');
                }

                // Cargar imagen del voucher si existe
                if (data.Ama_Img && data.Ama_Img.trim() !== '' && data.Ama_Img !== 'NULL' && data.Ama_Img.toLowerCase() !== 'null') {
                    $("#pagosForm #Ama_Img").val(data.Ama_Img);
                    // Mostrar preview de la imagen
                    $("#Ama_Img_Visual").attr('src', data.Ama_Img);
                    $("#Ama_Img_Preview").show();
                    // Mostrar el enlace para ver la imagen
                    $("#Ama_Img_Link").show();
                    $("#Ama_Img_Status").show().text('Imagen cargada').css('color', '#5cb85c');
                } else {
                    $("#pagosForm #Ama_Img").val('');
                    $("#Ama_Img_Visual").attr('src', '');
                    $("#Ama_Img_Preview").hide();
                    $("#Ama_Img_Link").hide();
                    $("#Ama_Img_Status").hide();
                }

                // Esperar a que se carguen los bancos después del cambio de tipo de pago
                setTimeout(function () {
                    $("#pagosForm #Ban_Cod").val(data.Ban_Cod || '');
                    $("#pagosForm #Bak_Cod").val(data.Bak_Cod || '');

                    // Si hay plugin chosen, actualizar
                    if ($("#pagosForm #Ban_Cod").hasClass("chosen-select")) {
                        $("#pagosForm #Ban_Cod").trigger("chosen:updated");
                    }
                    if ($("#pagosForm #Bak_Cod").hasClass("chosen-select")) {
                        $("#pagosForm #Bak_Cod").trigger("chosen:updated");
                    }

                    // Deshabilitar todos los campos (modo solo lectura)
                    setFormReadOnly(true);
                }, 500);

                // Abrir el modal
                $('#pagosDialog').dialog('open');
            } else {
                $.alert('Error al cargar los datos: ' + (response.message || 'Error desconocido'));
            }
        },
        error: function (xhr, status, error) {
            $.alert('Error al comunicarse con el servidor: ' + error);
        }
    });
}

// Función auxiliar para ver imagen desde el modal de detalles
function verImagenVoucherDesdeDetalle(imagenData) {
    if ($("#imagenVoucherDialog").length === 0) {
        $.alert('Error: El modal de imagen no se encuentra en el DOM');
        return;
    }

    // Resetear zoom al abrir
    resetZoomImagenVoucher();

    $("#imagenVoucherContent").attr('src', imagenData);

    $("#imagenVoucherDialog").dialog({
        modal: true,
        width: 800,
        height: 700,
        resizable: true,
        position: { my: "center", at: "center", of: window },
        buttons: {
            "Cerrar": function () {
                $(this).dialog("close");
            }
        }
    });

    // Agregar evento de rueda del mouse para zoom
    $("#imagenVoucherContainer").off('wheel').on('wheel', function (e) {
        e.preventDefault();
        var delta = e.originalEvent.deltaY;
        if (delta < 0) {
            // Scroll hacia arriba = acercar
            zoomImagenVoucher(1.1);
        } else {
            // Scroll hacia abajo = alejar
            zoomImagenVoucher(0.9);
        }
    });
}// Declaracion de variables globales para asignacion de grids
var id_pagos = 0;

var man_ant = $('#man_antGrid');
// var pagosMan = $('#pagosManGrid');
var client = $('#clientGrid')


$(function () {
    // rango de fechas
    // $.createDateRange('#Fec_IniM', '#Fec_IniM');
    $.createDateRange('#Fec_IniM', '#Fec_FinM');
    // inicializa componentes de fecha en formulario de anticipos
    $('.datepicker').createDatePickers({ checkAvailability: true, hideMsg: false }).mask('9999-99-99', { placeholder: '_' });

    // Inicializar periodos y fechas después de crear los dateRange para que no se sobrescriban
    desbloquear();

    // grid de anticipos del manifiesto AMBIENTE PRINCIPAL
    man_ant.createGrid({
        // data:[], 
        caption: 'Anticipos del Manifiesto <div class="pull-right"><b>FILTRADO POR:</b>&nbsp;<select id="FilterBy" onchange="cargarSelect();"><option value="">No filtrar</option><option value="P">Pendiente</option><option value="A">Acreditado</option><option value="R">Rechazado</option></select>&nbsp;</div>',
        rowNum: 1000,
        height: 332,
        footerrow: true,
        colModel: [
            { label: 'Cod. Int.', name: 'Ama_Cod', key: true, width: 15, align: "center" },
            { label: 'No. Compr.', name: 'Com_Cod', width: 30, align: "left", hidden: true },
            { label: 'Fecha', name: 'Ama_Fec', width: 35, align: "center" },
            { label: 'Usu_Cod.', name: 'Usu_Cod', width: 25, align: "center", hidden: true },
            { label: 'Responsable', name: 'usuario', width: 100, align: "left" },
            { label: 'Cli_Cod.', name: 'Cli_Cod', width: 25, align: "center", hidden: true },
            { label: 'Cliente', name: 'cliente', width: 100, align: "left" },
            { label: 'Ama_Tde', name: 'Ama_Tde', width: 100, align: "left", hidden: true },
            { label: 'Forma Pago', name: 'Pag_Des', width: 45, align: "center" },
            { label: 'Cuenta Acr.', name: 'Pld_Des', width: 55, align: "center" },
            { label: 'Nº de Trfs.', name: 'Ama_Doc', width: 45, align: "center" },
            {
                label: 'Valor', name: 'Ama_Val', width: 40, align: 'right', formatter: 'currency',
                formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '0.00' }
            },
            {
                label: 'Abono', name: 'Abono', width: 40, align: 'right', formatter: 'currency',
                formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '0.00' }
            },
            {
                label: 'Saldo', name: 'Saldo', width: 40, align: 'right', formatter: 'currency',
                formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', decimalPlaces: 2, defaultValue: '0.00' }
            },
            // { label: 'Tipo', name: 'Ama_Tip', width: 30, align: "center", hidden: true },
            {
                label: 'Est. Pago', name: 'Ama_Tip', width: 40, align: "center",
                formatter: function (cellvalue) {
                    if (cellvalue === 'P') return 'Pendiente';
                    if (cellvalue === 'A') return 'Acreditado';
                    if (cellvalue === 'R') return 'Rechazado';
                    if (cellvalue === 'I') return 'Anulado';
                    if (cellvalue === null) return '';
                    return cellvalue;
                }
            },
            { label: 'Observaci&oacute;n', name: 'Ama_Obs', width: 100, align: "left", hidden: true },
            { label: 'Estado', name: 'Ama_Est', width: 30, align: "center", hidden: true },
            {
                label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'btns_anti', width: 60, align: 'center', viewable: false,
                formatter: function (cellvalue, options, rowObject) {
                    // Si el registro está anulado, no mostrar ningún botón
                    if (rowObject.Ama_Est && String(rowObject.Ama_Est).toUpperCase() === 'I') {
                        return '';
                    }

                    let botones = "";
                    let parm_print_reg = [rowObject, "" + man_antGrid.table_id];
                    // Botón de ver detalles del registro
                    let parm_view = [rowObject, "" + man_antGrid.table_id];
                    if (rowObject.Pag_Des !== 'Retencion') {
                        botones += $.getGridButton(verDetallesAnticipo, parm_view, 'Ver Detalles', 'eye-open', '', 'primary') + "&nbsp;";
                    }
                    // Botón de imprimir reporte de registro (siempre visible si no está anulado)
                    botones += $.getGridButton(ImpRegAnticipo, parm_print_reg, 'Imprimir Registro', 'print', '', 'info') + "&nbsp;";

                    if (rowObject.Ama_Tip && String(rowObject.Ama_Tip).toUpperCase() === 'A') {
                        // Si está acreditado, solo mostrar botones de impresión (no editar)
                        let parm_print = [rowObject, "" + man_antGrid.table_id];
                        botones += $.getGridButton(ImpCom, parm_print, 'Imprimir Comprobante', 'print', '', 'info') + "&nbsp;";
                        return botones;
                    } else if (rowObject.Ama_Tip && String(rowObject.Ama_Tip).toUpperCase() === 'R') {
                        // Si está rechazado, solo mostrar el botón de registro
                        return botones;
                    }

                    // Debes tener var Ses_Usu_Cod = "..."; declarada en tu HTML (PHP)
                    let parm_anu = [rowObject, "" + man_antGrid.table_id];
                    let parm_getdet = [rowObject, man_antGrid.row_id];

                    // Sólo permitir edición/cancelar si es del usuario logeado
                    if (!$.isEmpty(cliente_manifiesto)) {
                        botones += $.getGridButton(editAnticipo, parm_getdet, 'Modificar anticipo', 'pencil', '', 'success') + "&nbsp;";
                        botones += $.getGridButton(cancelAnticipo, parm_anu, 'Anular Anticipo', 'trash', '', 'danger');
                    } else {
                        // Si es de otro usuario, solo editar/confirmar/rechazar
                        //botones += $.getGridButton(editAnticipo, parm_getdet, 'Modificar anticipo', 'pencil', '', 'success') + "&nbsp;";
                        
                        // Ocultar botones de Aprobar y Rechazar para el perfil "Contralor"
                        if (prf && prf.length > 0 && prf[0]['Per_Des'] !== 'Contralor') {
                            botones += $.getGridButton(confirmAnticipo, parm_anu, 'Aprobar Anticipo', 'check', '', 'success') + "&nbsp;";
                            botones += $.getGridButton(declineAnticipo, parm_anu, 'Rechazar Anticipo', 'remove', '', 'danger');
                        }
                    }
                    return botones;
                }
            }
        ],
        loadComplete: function () {
            var ids = man_ant.jqGrid('getDataIDs');
            var totalVal = 0, totalAbono = 0, totalSaldo = 0;
            ids.forEach(function (id) {
                var r = man_ant.jqGrid('getRowData', id);
                var val = parseFloat(String(r.Ama_Val).replace(/[^\d.-]/g, '')) || 0;
                var abono = parseFloat(String(r.Abono).replace(/[^\d.-]/g, '')) || 0;
                var saldo = parseFloat((val - abono).toFixed(2));

                totalVal += val;
                totalAbono += abono;
                totalSaldo += saldo;

                // seteamos saldo con 2 decimales
                man_ant.jqGrid('setCell', id, 'Saldo', saldo);

                if ($.trim(r.Ama_Tip).toUpperCase() === 'R') {
                    man_ant.jqGrid('setRowData', id, false, {
                        background: '#FADDDD'
                    });
                }

                // Resaltar en rojo las filas con Ama_Est = 'I' (Inactivo/Anulado)
                if ($.trim(r.Ama_Est).toUpperCase() === 'I') {
                    man_ant.jqGrid('setRowData', id, false, { background: '#FFCCCC' });
                }
            });

            man_ant.jqGrid('footerData', 'set', {
                Ama_Doc: '<div class="txtRight">TOTAL:</div>',
                Ama_Val: totalVal,
                Abono: totalAbono,
                Saldo: parseFloat(totalSaldo.toFixed(2))
            }, true);
        },

        userDataOnFooter: false

    }, true, 'man_antGridPager', { view: false, refresh: true });

    // Definición de botones del grid
    var gridButtons = [
        {
            caption: 'Exportar Excel', buttonicon: 'glyphicon glyphicon-download',
            onClickButton: function () { //descargar excel de anticipos del manifiesto
                man_ant.jqGrid('exportGridExcel', {
                    nombre: 'Manifiesto_Anticipos',
                    hoja: 'HOJA 1',
                    footer: true
                });
            }
        }
    ];

    // Mostrar el botón de "Vouchers PDF" solo para Administrador de Sistemas
    if (prf && prf.length > 0 && prf[0]['Per_Des'] === 'Administrador de Sistemas') {
        gridButtons.push({
            caption: 'Vouchers PDF', buttonicon: 'glyphicon glyphicon-picture',
            onClickButton: function () {
                // Abrir modal de vouchers masivos
                if ($('#vouchersMasivosDialog').length) {
                    $('#vouchersMasivosDialog').dialog({
                        width: 700,
                        height: 480,
                        modal: true,
                        open: function () {
                            // Limpiar tabla al abrir
                            $('#vouchersTable tbody').html('<tr><td colspan="4" class="text-center">Seleccione un rango y busque...</td></tr>');
                            $('#checkAllVouchers').prop('checked', false);
                            $('#btnGenerarDescargaVouchers').prop('disabled', true);
                        }
                    });
                }
            }
        });
    }

    man_ant.gridButtonsAdd(gridButtons);

    // Función para limpiar estilos de error cuando el usuario completa los campos
    function limpiarErrorCampo(selector) {
        $(selector).on('input change', function () {
            $(this).css('border-color', '');
        });
    }

    $(document).ready(function () {
        // Agregar listeners para limpiar errores cuando se completen los campos
        limpiarErrorCampo("#pagosForm #Prs_Ced");
        limpiarErrorCampo("#pagosForm #nombre");
        limpiarErrorCampo("#pagosForm #Ama_Fec");
        limpiarErrorCampo("#pagosForm #Pag_Cod");
        limpiarErrorCampo("#pagosForm #Ban_Cod");
        limpiarErrorCampo("#pagosForm #Bak_Cod");
        limpiarErrorCampo("#pagosForm #Ama_Img_File");
        limpiarErrorCampo("#pagosForm #Ama_Doc");
        limpiarErrorCampo("#pagosForm #Pac_Val");

        // Re-validar documento cuando cambia el banco o tipo de pago
        $(document).on('change', '#pagosForm #Bak_Cod, #pagosForm #Pag_Cod', function () {
            var doc = $('#pagosForm #Ama_Doc').val();
            if (doc && doc.trim() !== '') {
                validarAmaDoc(doc);
            }
        });

        // Limpiar error del voucher cuando se suba la imagen
        $(document).on('change', '#pagosForm #Ama_Img_File', function () {
            setTimeout(function () {
                var amaImg = $("#pagosForm #Ama_Img").val();
                if (amaImg && amaImg.trim() !== '') {
                    $("#pagosForm #Ama_Img_File").css('border-color', '');
                }
            }, 500);
        });
        if ($('#searchGrid').length === 1)
            cargarAnticipos();

        if ($("#Ama_Fec").length === 1)
            $.post("", { obtenerPeriodoMinMax: true }, function (responce) {
                if (responce['success'] === true) {
                    $("#Ama_Fec").dateLimits(responce['data']['minimo'], responce['data']['maximo']);
                } else {
                    console.log(responce['message']);
                }
            }, 'json')
                .fail(function (error) {
                    console.log("El Servidor ha fallado en responder!");
                });
        $("#Tia_Cod option[data-abr='IN']").prop("selected", true);
    });

    // Grid del modal oculto de Cliente 
    if ($('#clientesDialog').length === 1)
        $.createSearchDialog('clientesDialog', [
            { label: 'C&oacute;d.Int.', name: 'Cli_Cod', key: true, width: 15, align: "center", hidden: true },
            { label: 'C&eacute;dula/RUC', name: 'Prs_Ced', width: 50 },
            { label: 'Cliente', name: 'nombre', width: 100 },
            { label: 'Direcc.', name: 'Prs_Dir', width: 60 },
            { label: 'Pla_Cod', name: 'Pla_Cod', width: 10, align: "center", hidden: true },
            { label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: selectCliente } }
        ], null, null, null, { headertitles: true }, { title: 'Cliente', text: 'searchCli' });

    // inicializa el dialog de registrar pagos de anticipo
    if ($('#pagosDialog').length === 1)
        $('#pagosDialog').createDialog({ height: 400, width: 600, icon: 'usd' });

    // Mostrar/ocultar botones según si hay cliente manifiesto
    toggleBotonesCliente();

    // Ocultar botón "Nuevo Anticipo" para el perfil "Contralor"
    if (prf && prf.length > 0 && prf[0]['Per_Des'] === 'Contralor') {
        $('#btnNuevoAnticipo').hide();
    }
});

// cargar el select de filtrado - NUEVA
function cargarSelect() {
    let filtro = $("#FilterBy").val();
    if (filtro === "") {
        $("#filtroAnt").val("");
    } else {
        $("#filtroAnt").val(filtro);
    }
    // Recargar el grid con el nuevo filtro
    $('#man_antGrid').Search('#searchManifesto', 'LoadManifAjax');
}

/**
 * Función para mostrar u ocultar los botones según si hay cliente manifiesto
 * La variable global 'tieneClienteManifiesto' debe estar definida en el HTML (PHP)
 */
function toggleBotonesCliente() {
    // Verificar que la variable global esté definida
    if (typeof tieneClienteManifiesto === 'undefined') {
        tieneClienteManifiesto = false;
    }

    if (!tieneClienteManifiesto) {
        // Si NO hay cliente manifiesto (la consulta no trae datos), mostrar los botones de búsqueda
        $('#radsf1, #radsf2').show();
        $('label[for="radsf1"], label[for="radsf2"]').show();
        // Mostrar el botón de búsqueda de cliente - remover completamente el estilo inline
        $('#btnBusCLi').removeAttr('style').show();
    } else {
        // Si SÍ hay cliente manifiesto (la consulta trae datos), ocultar los botones de búsqueda
        $('#radsf1, #radsf2').hide();
        $('label[for="radsf1"], label[for="radsf2"]').hide();
        // Ocultar el botón de búsqueda de cliente
        $('#btnBusCLi').css('display', 'none');
        // Si radsf1 estaba seleccionado, cambiar a radsf3 (Manifiesto)
        if ($('#radsf1').is(':checked')) {
            $('#radsf3').prop('checked', true);
        }
    }
}

/* Imprimir comprobante */
function ImpCom(rowObject) {
    // Si te pasan un array (como [row, idGrid]), recupera el objeto correcto
    var row = Array.isArray(rowObject) ? rowObject[0] : rowObject;

    if (!row || !row.Com_Cod) {
        $.alert("No se encuentra el código del comprobante para imprimir.");
        return;
    }

    $.getDataJson('', { 'cargarReportes': true }, function (res) {
        var reportes = res['reportes'];
        if ($.varValid(reportes[2])) {
            $.imprimirUrl(reportes[2] + '?codigo=' + row.Com_Cod);
        } else {
            $.alert('Sin Reportes Asociados');
        }
    }, function (err) {
        console.log(err['message']);
        $.alert('Error al cargar los reportes: ' + (err['message'] || 'Error desconocido'));
    });
}

function ImpRegAnticipo(rowObject) {
    // Si te pasan un array (como [row, idGrid]), recupera el objeto correcto
    var row = Array.isArray(rowObject) ? rowObject[0] : rowObject;

    if (!row || !row.Ama_Cod) {
        $.alert("No se encuentra el código del anticipo para imprimir.");
        return;
    }

    // Abrir el reporte en una nueva ventana
    var url = 'man_reg_1.0.php?Ama_Cod=' + row.Ama_Cod;
    $.imprimirUrl(url);
}

$.fn.createInputDiario3 = function (element, tipo) {
    var jgrid = this,
        rowId = $(element).closest('tr.jqgrow').attr('id'),
        tip = jgrid.jqGrid('getCell', rowId, 'Det_Tip');
    $(element).parent().removeAttr("title");
    if (tip === tipo) {
        $(element).on('change', function () {
            var totalesgrid = actualizarTotales();
            var hab = totalesgrid.haber;
            var deb = totalesgrid.debe;
            if (deb !== hab) { hab = deb; }
            $("#Ant_Val").val(parseFloat(hab).toFixed(2));
            $("[id*='_Haber']").val($("#Ant_Val").val());
            $(this).val($.toFixed($(this).val()));
            jgrid.updateGridDiario();
        });
        $(element).attr('onkeypress', 'return  validar_decimal(event)');
        if (parseFloat($(element).val()) === 0) $(element).val("");
        $(element).css('text-align', 'right').focus();
    } else { $(element).parent().html(''); };
};

// Función para ajustar las fechas según el periodo seleccionado
function desbloquear() {
    // Obtener el option seleccionado
    const seleccionado = $('#Pec_Cod option:selected');
    const valor = (seleccionado.val() || '').toString();

    // Caso "T" (Todos) o valor vacio: establecer desde el año mínimo hasta el máximo y deshabilitar edición
    if (valor === 'T' || valor === '') {
        let years = [];
        $('#Pec_Cod option').each(function () {
            // soportar varias variantes de data attribute
            let y = $(this).attr('data--year') || $(this).data('year') || $(this).data('--year');
            if (y == null) return;
            const yi = parseInt(y, 10);
            if (!isNaN(yi)) years.push(yi);
        });

        if (years.length > 0) {
            const minYear = Math.min.apply(null, years);
            const maxYear = Math.max.apply(null, years);
            $('#Fec_IniM').val(minYear + '-01-01').prop('disabled', true);
            $('#Fec_FinM').val(maxYear + '-12-31').prop('disabled', true);
        } else {
            $('#Fec_IniM, #Fec_FinM').prop('disabled', true).val('');
        }
        return;
    }

    // Caso "PF": rango del mes actual pero editable (opción para cambiarlo)
    if (valor === 'PF') {
        const now = new Date();
        const year = now.getFullYear();
        const month = now.getMonth(); // 0-based
        const first = new Date(year, month, 1);
        const last = new Date(year, month + 1, 0);
        const fmt = d => d.toISOString().split('T')[0];
        $('#Fec_IniM').val(fmt(first)).prop('disabled', false);
        $('#Fec_FinM').val(fmt(last)).prop('disabled', false);
        return;
    }

    // Caso específico: usar data-year para establecer el año completo si existe, sino data-inicio
    const year = seleccionado.data('year') || seleccionado.attr('data-year');
    if (year && year !== '') {
        $('#Fec_IniM').val(year + '-01-01').prop('disabled', false);
        $('#Fec_FinM').val(year + '-12-31').prop('disabled', false);
    } else {
        const inicio = seleccionado.data('inicio') || seleccionado.attr('data-inicio') || '';
        const fin = seleccionado.data('fin') || seleccionado.attr('data-fin') || '';
        $('#Fec_IniM').val(inicio).prop('disabled', false);
        $('#Fec_FinM').val(fin).prop('disabled', false);
    }
}

// function cambioAmbiente() {
//     // ambiente actual luego el ambiente nuevo
//     $("#documentoSearch").moveComp("#documentoSearch2");
// }

// function regresarAmbiente() {
//     // ambiente actual luego el ambiente anterior
//     $("#documentoSearch2").moveComp("#documentoSearch");
// }

// Bind para ejecutar al cargar y al cambiar la selección
$(function () {
    $('#Pec_Cod').on('change', function () {
        desbloquear();
        // Actualizar el grid cuando cambie el periodo
        if (man_ant && man_ant.length) {
            man_ant.gridUpdate();
        }
    });
    // Ejecutar una vez al inicio para reflejar el valor por defecto (por ejemplo 'T')
    // desbloquear();

    // Actualizar el grid cuando cambien las fechas
    $('#Fec_IniM, #Fec_FinM').on('change', function () {
        if (man_ant && man_ant.length) {
            man_ant.gridUpdate();
        }
    });
});

//para asignar un cliente al anticipo a crear
function selectCliente(cliente) {
    // Si el parámetro viene como array, obtener el primer elemento (objeto de la fila)
    var rowData = Array.isArray(cliente) ? cliente[0] : cliente;

    // Solo pasar Prs_Ced y nombre como solicitó el usuario
    $("#pagosForm #Cli_Cod").val(rowData.Cli_Cod || '');
    $("#pagosForm #Prs_Ced").val(rowData.Prs_Ced || '');
    $("#pagosForm #Pla_Cod").val(rowData.Pla_Cod || '');
    $("#pagosForm #Pla_Nom").val(rowData.Pla_Nom || '');
    $("#pagosForm #Pla_Lic").val(rowData.Pla_Lic || '');
    $("#pagosForm #nombre").val(rowData.nombre || '');

    // También llenar los campos ocultos necesarios para el guardado
    $("#pagosForm #Prs_Cod").val(rowData.Prs_Cod || '');
    $("#pagosForm #Cli_Cod").val(rowData.Cli_Cod || '');
    $("#pagosForm #Pla_Cod").val(rowData.Pla_Cod || '');
    $("#pagosForm #Pla_Nom").val(rowData.Pla_Nom || '');
    $("#pagosForm #Pla_Lic").val(rowData.Pla_Lic || '');
    $("#pagosForm #bandera_prov").val("sel");

    // Cerrar el diálogo de búsqueda
    $('#clientesDialog').dialog('close');
}

function gestionarPago() {
    $('#Pag_Cod').trigger('change');
    $('#Ama_Val').trigger('change');
    $('#pagosDialog').dialog('open');
    $('#pagosForm').removeData();

    // Establecer la fecha actual si el campo está vacío (solo para nuevos registros)
    var $amaFec = $('#pagosForm #Ama_Fec');
    var amaCod = $('#pagosForm #Ama_Cod').val();
    // Solo establecer fecha si es un nuevo registro (Ama_Cod vacío) y el campo está vacío
    if ((!amaCod || amaCod.trim() === '') && (!$amaFec.val() || $amaFec.val().trim() === '')) {
        var hoy = new Date();
        // Establecer la fecha usando el datepicker si está inicializado
        if ($amaFec.hasClass('hasDatepicker') || $amaFec.hasClass('datepicker')) {
            $amaFec.datepicker('setDate', hoy);
        } else {
            // Si no está inicializado, establecer el valor directamente
            var fechaFormateada = hoy.getFullYear() + '-' +
                String(hoy.getMonth() + 1).padStart(2, '0') + '-' +
                String(hoy.getDate()).padStart(2, '0');
            $amaFec.val(fechaFormateada);
        }
    }
}

// habilita y deshabilita campos dependiendo del tipo de pago seleccionado, recive el tipo de pago y su abreviatura
function cambiarCamposPagos(tipo_pago, tipo_pago_abr) {

    if (tipo_pago_abr != "RET") {

        $("#Pac_Cto").val("");
        var data = new Object();
        data['tipo'] = tipo_pago_abr;
        data['LoadPayment'] = true;

        $.getDataJson('', { 'LoadPayment': true, tipo: tipo_pago_abr }, function (result) {
            $("#Ban_Cod option").remove();

            for (i = 0; i < result['data'].length; i++) {
                if (tipo_pago_abr === "TRF" || tipo_pago_abr === "DEP") {
                    $("#Ban_Cod").append("<option value='" + result['data'][i].Ban_Cod + "' data-pla='" + result['data'][i].Pld_Cod + "' data-cdc='" + result['data'][i].Pld_Cdc + "' data-cue='" + result['data'][i].Ban_Cue + "' data-des='" + result['data'][i].Pld_Des + "'>" + result['data'][i].Pld_Des + " - " + result['data'][i].Ban_Cue + "</option>");
                }
            }
            if (result['data_ban'] !== null) {
                for (i = 0; i < result['data_ban'].length; i++) {
                    $("#Ban_Cod").append("<option value='" + result['data_ban'][i].Ban_Cod + "' data-pla='" + result['data_ban'][i].Pld_Cod + "' data-cdc='" + result['data_ban'][i].Pld_Cdc + "' data-cue='" + result['data_ban'][i].Ban_Cue + "' data-des='" + result['data_ban'][i].Pld_Des + "'>" + result['data_ban'][i].Pld_Des + " - " + result['data_ban'][i].Ban_Cue + "</option>");
                }
            }

            var opcion = $('#Pag_Cod').children("option:selected").text();
            if (opcion == 'NotaCredito') {
                $('#doc').text("Referencia:");
            }

            // Ocultar y mostrar grupos según el tipo de pago dentro del fieldset detPagos
            $('#detPagos .form-group').addClass('hidden');
            $('#detPagos .form-group.' + tipo_pago).removeClass('hidden');
            $('#detPagos .form-group.' + tipo_pago).find('.form-control').prop('required', true);
            $('#detPagos .form-group.center').removeClass('hidden');
        }, function (err) {
            $.alert(err['message']);
        });
    } else {
        $("div.hide_banco").hide();
    }

}

// valida que los campos no esten vacios dependiendo del tipo de pago seleccionado
function validarPagosForm(tipo) {
    var bandera_pagos = false;

    if (tipo === "DEP" || tipo === "TRF") {
        if ($("#Ama_Val").val() !== "" || $("#Pac_Cto").val() !== "") {
            bandera_pagos = false;
        } else {
            bandera_pagos = true;
        }
    }

    return bandera_pagos;
}

function limpiarPagosDialog() {
    // Limpiar estilos de error de todos los campos
    $("#pagosForm #Prs_Ced").css('border-color', '');
    $("#pagosForm #nombre").css('border-color', '');
    $("#pagosForm #Ama_Fec").css('border-color', '');
    $("#pagosForm #Pag_Cod").css('border-color', '');
    $("#pagosForm #Ban_Cod").css('border-color', '');
    $("#pagosForm #Bak_Cod").css('border-color', '');
    $("#pagosForm #Ama_Img_File").css('border-color', '');
    $("#pagosForm #Ama_Doc").css('border-color', '');
    $("#pagosForm #Pac_Val").css('border-color', '');

    // Limpia los inputs de texto, números, ocultos y textarea
    // $('#pagosForm').find('input[type="text"], input[type="number"], input[type="hidden"], textarea').val("");
    // $('#pagosForm').find('select').prop('selectedIndex', 0);
    // Restaurar selección por defecto del tipo de pago (primera opción)
    var selectPag = $('#pagosForm #Pag_Cod');
    if (selectPag.find('option').length > 0) {
        selectPag.prop('selectedIndex', 0);
        // Disparar manualmente el evento change para que se ejecute la lógica de ocultar/mostrar campos
        selectPag.trigger('change');
    }
    // Asegurar que Ama_Cod esté vacío para indicar que es un nuevo registro
    $('#pagosForm #Ama_Cod').val('');
    
    // Limpiar el ícono de validación del documento
    $('#Ama_Doc_Est').removeClass().css('color', '');
    
    // Limpiar el campo de imagen del voucher
    $('#pagosForm #Ama_Img_File').val('');
    $('#pagosForm #Ama_Img').val('');

    // Ocultar enlace de imagen y previsualización
    $("#Ama_Img_Link").hide();
    $("#Ama_Img_Status").hide().text('');
    $("#Ama_Img_Preview").hide();
    $("#Ama_Img_Visual").attr('src', '');

    // Restaurar título del modal
    $("#pagosDialog").dialog("option", "title", "Agregar Pagos");

    // Habilitar campos para nuevo registro
    // setFormReadOnly(false);
    // $("#pagosForm #Prs_Ced, #pagosForm #nombre, #pagosForm #Pla_Nom, #pagosForm #Pla_Lic").prop('readonly', true).css('background-color', '#f5f5f5');
    // $('#Ama_Img_Status').hide().text('');
    // $('#Ama_Img_Link').hide();
    // Restaurar título del modal
    // $("#pagosDialog").dialog("option", "title", "Agregar Pagos");
    // Habilitar campos para nuevo registro y resetear readonly de cliente
    setFormReadOnly(false);
    $("#pagosForm #Prs_Ced, #pagosForm #nombre, #pagosForm #Pla_Nom, #pagosForm #Pla_Lic").prop('readonly', true).css('background-color', '#f5f5f5');
}
/* cumple con la funcion de guardado para consumir el ajax correspondiente */
function AgregarPago() {
    // Validar campos requeridos antes de continuar
    var errores = [];

    // Validar Cliente (Cédula/RUC)
    var prsCed = $("#pagosForm #Prs_Ced").val();
    if (!prsCed || prsCed.trim() === '') {
        errores.push('Debe seleccionar un Cliente (Cédula/RUC)');
        $("#pagosForm #Prs_Ced").css('border-color', '#d9534f');
    } else {
        $("#pagosForm #Prs_Ced").css('border-color', '');
    }

    // Validar Nombre del Cliente
    var nombreCliente = $("#pagosForm #nombre").val();
    if (!nombreCliente || nombreCliente.trim() === '') {
        errores.push('Debe seleccionar un Cliente');
        $("#pagosForm #nombre").css('border-color', '#d9534f');
    } else {
        $("#pagosForm #nombre").css('border-color', '');
    }

    // Validar Fecha
    var amaFec = $("#pagosForm #Ama_Fec").val();
    if (!amaFec || amaFec.trim() === '') {
        errores.push('La Fecha es requerida');
        $("#pagosForm #Ama_Fec").css('border-color', '#d9534f');
    } else {
        $("#pagosForm #Ama_Fec").css('border-color', '');
    }

    // Validar Tipo de Pago
    var pagCod = $("#pagosForm #Pag_Cod").val();
    if (!pagCod || pagCod.trim() === '' || pagCod === '0') {
        errores.push('El Tipo de Pago es requerido');
        $("#pagosForm #Pag_Cod").css('border-color', '#d9534f');
    } else {
        $("#pagosForm #Pag_Cod").css('border-color', '');
    }

    // Validar Acreditar a (Banco)
    var banCod = $("#pagosForm #Ban_Cod").val();
    if (!banCod || banCod.trim() === '' || banCod === '0') {
        errores.push('Debe seleccionar una cuenta para Acreditar a');
        $("#pagosForm #Ban_Cod").css('border-color', '#d9534f');
    } else {
        $("#pagosForm #Ban_Cod").css('border-color', '');
    }

    // Validar Banco Origen (solo para Transferencia)
    var tipoPago = $("#pagosForm #Pag_Cod option:selected").text().toLowerCase();
    var bakCod = $("#pagosForm #Bak_Cod").val();
    var bakText = $("#pagosForm #Bak_Cod option:selected").text();

    if (tipoPago.indexOf('transferencia') !== -1 || tipoPago.indexOf('transfer') !== -1) {
        if (!bakCod || bakCod.trim() === '' || bakCod === '0' || bakText.trim().toLowerCase() === 'ninguno') {
            errores.push('El Banco Origen es requerido para Transferencias');
            $("#pagosForm #Bak_Cod").css('border-color', '#d9534f');
        } else {
            $("#pagosForm #Bak_Cod").css('border-color', '');
        }
    }

    // Validar Voucher (imagen)
    var amaImg = $("#pagosForm #Ama_Img").val();
    if (!amaImg || amaImg.trim() === '') {
        errores.push('Debe subir un Voucher (imagen)');
        $("#pagosForm #Ama_Img_File").css('border-color', '#d9534f');
    } else {
        $("#pagosForm #Ama_Img_File").css('border-color', '');
    }

    // Validar Número de Documento
    var amaDoc = $("#pagosForm #Ama_Doc").val();
    if (!amaDoc || amaDoc.trim() === '') {
        errores.push('El Número de Documento es requerido');
        $("#pagosForm #Ama_Doc").css('border-color', '#d9534f');
    } else {
        $("#pagosForm #Ama_Doc").css('border-color', '');
    }

    // Validar Valor
    var pacVal = $("#pagosForm #Pac_Val").val();
    if (!pacVal || pacVal.trim() === '' || parseFloat(pacVal) <= 0) {
        errores.push('El Valor debe ser mayor a cero');
        $("#pagosForm #Pac_Val").css('border-color', '#d9534f');
    } else {
        $("#pagosForm #Pac_Val").css('border-color', '');
    }

    // Si hay errores, mostrarlos y detener el proceso
    if (errores.length > 0) {
        var mensajeError = 'Por favor complete los siguientes campos requeridos:\n\n' + errores.join('\n');
        $.alert(mensajeError);
        // Hacer scroll al primer campo con error
        $('html, body').animate({
            scrollTop: $("#pagosForm").offset().top - 50
        }, 500);
        return false;
    }

    // Obtener el código del anticipo para determinar si es INSERT o UPDATE
    var amaCod = $("#pagosForm #Ama_Cod").val();
    var esEdicion = amaCod && amaCod.trim() !== '';

    // Recolectar datos desde el formulario/modal
    var data = {
        saveManiAjax: true,
        Ban_Cod: $("#pagosForm #Ban_Cod").val(),
        Bak_Cod: $("#pagosForm #Bak_Cod").val(),
        Cli_Cod: $("#pagosForm #Cli_Cod").val(),
        Pla_Cod: $("#pagosForm #Pla_Cod").val(),
        // Pla_Nom: $("#pagosForm #Pla_Nom").val(),
        // Pla_Lic: $("#pagosForm #Pla_Lic").val(),
        Ama_Val: $("#pagosForm #Pac_Val").val(),
        Pag_Cod: $("#pagosForm #Pag_Cod").val(),
        Ama_Doc: $("#pagosForm #Ama_Doc").val(),
        Ama_Fec: $("#pagosForm #Ama_Fec").val(),
        Ama_Obs: $("#pagosForm #Ama_Obs").val(),
        Ama_Img: $("#pagosForm #Ama_Img").val()
    };

    // Si hay Ama_Cod, es una edición (UPDATE), agregarlo a los datos
    if (esEdicion) {
        data.Ama_Cod = amaCod;
    }

    var mensajeConfirmacion = esEdicion
        ? '¿Está seguro que desea actualizar el anticipo?'
        : '¿Está seguro que desea registrar el anticipo?';

    var mensajeExito = esEdicion
        ? '¡Anticipo actualizado correctamente!'
        : '¡Anticipo registrado correctamente!';
    console.log(data);
    $.createDialogConfirm(mensajeConfirmacion, data, function (d) {
        $("#loader").show();
        $.ajax({
            url: '../FRONT/man_ant_1.0.php',
            method: 'POST',
            data: d,
            dataType: 'json',
            success: function (response) {
                $("#loader").hide();
                if (response.success) {
                    $.alert(mensajeExito);
                    limpiarPagosDialog();
                    $("#pagosDialog").dialog('close');
                    // Forzar recargar inmediata del grid principal usando la función de búsqueda
                    setTimeout(function () {
                        // $('#man_antGrid').jqGrid('setGridParam', { page: 1 }).trigger('reloadGrid');
                        $('#man_antGrid').Search('#searchManifesto', 'LoadManifAjax');
                    }, 200);
                } else {
                    $.alert(response.message);
                }
            },
            error: function () {
                $("#loader").hide();
                $.alert("Ocurrió un error en la petición");
            }
        });
    });
}

function cambioValPago(elemento) {
    if (elemento.val() != '') {
        elemento.val(parseFloat(elemento.val()).toFixed(2));
    } else {
        elemento.val("0.00");
    }
}

// Subir voucher a Google Drive (o sistema de almacenamiento)
function subirVoucher(inputFile) {
    var file = inputFile.files[0];
    if (!file) {
        $('#Ama_Img').val('');
        $('#Ama_Img_Status').hide().text('');
        $('#Ama_Img_Link').hide();
        return;
    }

    // Validar que sea una imagen
    if (!file.type.match('image.*')) {
        $.alert('Por favor seleccione un archivo de imagen');
        $(inputFile).val('');
        return;
    }

    // Validar tamaño (máximo 6MB, el servidor comprimirá si es > 3MB)
    if (file.size > 6 * 1024 * 1024) {
        $.alert('El archivo es demasiado grande. Máximo 6MB');
        $(inputFile).val('');
        return;
    }

    // Mostrar estado de carga
    $('#Ama_Img_Status').show().text('Subiendo imagen...').css('color', '#337ab7');
    $('#Ama_Img_Link').hide();

    // Crear FormData para enviar el archivo
    var formData = new FormData();
    formData.append('uploadVoucherAjax', true);
    formData.append('voucher_file', file);
    formData.append('Cli_Cod', $("#pagosForm #Cli_Cod").val() || '');
    formData.append('Pla_Cod', $("#pagosForm #Pla_Cod").val() || '');

    // Subir el archivo
    $.ajax({
        url: '../FRONT/man_ant_1.0.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function (response) {
            if (response.success && response.url) {
                // Guardar el enlace en el campo oculto
                var $formImg = $('#pagosForm #Ama_Img');
                if ($formImg.length > 0) {
                    $formImg.val(response.url);
                } else {
                    $('#Ama_Img').val(response.url);
                }

                $('#Ama_Img_Status').text('Imagen subida correctamente').css('color', '#5cb85c');
                $('#Ama_Img_Link_Href').removeAttr('href').attr('href', 'javascript:void(0);');
                // Mostrar preview de la imagen
                $('#Ama_Img_Visual').attr('src', response.url);
                $('#Ama_Img_Preview').show();
                $('#Ama_Img_Link').show();
            } else {
                $('#Ama_Img_Status').text(response.message || 'Error al subir la imagen').css('color', '#d9534f');
                $('#pagosForm #Ama_Img').val('');
                $('#Ama_Img').val('');
                $('#Ama_Img_Link').hide();
            }
        },
        error: function (xhr, status, error) {
            $('#Ama_Img_Status').text('Error al comunicarse con el servidor').css('color', '#d9534f');
            $('#Ama_Img').val('');
            $('#Ama_Img_Link').hide();
            console.error('Error al subir voucher:', error);
        }
    });
}

// Validar si el número de documento (Ama_Doc) ya existe
function validarAmaDoc(numDoc) {
    var $icono = $('#Ama_Doc_Est');

    // Si el campo está vacío, limpiar el ícono
    if (!numDoc || numDoc.trim() === '') {
        $icono.removeClass().css('color', '');
        return;
    }

    // Obtener el código del anticipo si es una edición
    var amaCod = $('#pagosForm #Ama_Cod').val() || '';

    // Realizar la validación vía AJAX
    $.post("", {
        validarAmaDocAjax: true,
        Ama_Doc: numDoc.trim(),
        Ama_Cod: amaCod,
        Bak_Cod: $('#Bak_Cod').val(),
        Pag_Cod: $('#Pag_Cod').val()
    }, function (response) {
        if (response.success) {
            if (response.existe) {
                // El documento ya existe, mostrar X roja
                $icono.removeClass().addClass('fa fa-close').css('color', 'red')
                    .attr('title', 'El número de documento ya existe');
                $('#btnAgregarPago').removeAttr('onclick');
            } else {
                // El documento no existe, mostrar check verde
                $icono.removeClass().addClass('fa fa-check').css('color', 'green')
                    .attr('title', 'Número de documento válido');
                $('#btnAgregarPago').attr('onclick', 'AgregarPago()');
            }
        } else {
            // Error en la validación
            $icono.removeClass().addClass('fa fa-close').css('color', 'orange')
                .attr('title', response.message || 'Error al validar');
        }
    }, 'json')
        .fail(function (error) {
            $icono.removeClass().addClass('fa fa-close').css('color', 'orange')
                .attr('title', 'Error al comunicarse con el servidor');
            console.error("Error al validar documento:", error);
        });
}

//permite el ingreso unicamente de numeros
function soloNumeros(e) {
    // valor = valor.replace(/[^0-9]/g,'');
    var key = window.Event ? e.which : e.keyCode
    return (key >= 48 && key <= 57)
}

function cargarClienteAnticipo() {
    $.ajax({
        url: "",
        type: "POST",
        data: { loadCliAjax: true },
        dataType: "json",

        success: function (resp) {
            // console.log(resp);

            // Asegurar que trae datos
            if (!resp || resp.length === 0) {
                $.alert("No se encontraron datos del cliente.");
                return;
            }

            // Si es un array, tomar el primer registro
            var data = resp[0];

            // Llenar los componentes dentro del formulario pagosForm
            $("#pagosForm #Prs_Cod").val(data.Prs_Cod || '');
            $("#pagosForm #Prs_Ced").val(data.Prs_Ced || '');
            $("#pagosForm #Cli_Cod").val(data.Cli_Cod || '');
            $("#pagosForm #nombre").val(data.Cliente || '');

            // Llenar campos de planta
            $("#pagosForm #Pla_Cod").val(data.Pla_Cod || '');
            $("#pagosForm #Pla_Nom").val(data.Pla_Nom || '');
            $("#pagosForm #Pla_Lic").val(data.Pla_Lic || '');

            $.getDataJson('', { 'getLastAntAjax': true }, function (result) {
                $("#Ant_Doc").val(result['data']);
                $("#Ant_Doc_ver").val("ANT - " + result['data']);
            }, function (err) {
                $.alert(err['message']);
            });

            $("#pagosForm #bandera_prov").val("sel");
            $("#Ant_Obs").val("ANT. CLIENTE - " + data.Cliente);

            cuentaAnticipoIni();

        },

        error: function () {
            $.alert("Error al cargar datos del cliente.");
        }
    });
}

function abrirModalPagos() {
    // Limpiar el formulario y asegurar que Ama_Cod esté vacío para nuevo registro
    limpiarPagosDialog();
    // Mueve el componente y abre el modal
    gestionarPago();
    // $("#documentoSearch").moveComp("#documentoSearch2");
    // ahora carga los datos del cliente
    cargarClienteAnticipo();
}

function cuentaAnticipoIni() {
    $.getDataJson('', { 'LoadPayment': true, tipo: "INICIAL" }, function (result) {
        if (result['bandera'] === true) {
            array_p = ["inicial", "", "", "-", result['data'].Pld_Des, result['data'].Pld_Cdc, "", "", "", "", "", result['data'].Pld_Cod, "H", "Ant. Cli: " + $("#nombre").val(), "", "0.00", "first"];
            // addPago(array_p);
            $("[id*='_Haber']").attr("readonly", "");
            $("#save_bnd").val("s");
        } else {
            $.alert("NO EXISTE UNA CUENTA PARAMETRIZADA PARA " + result['message']);
            $("#save_bnd").val("n");
        }
    }, function (err) {
        $.alert(err['message']);
    });
}

function editAnticipo(rowObject) {
    // Si te pasan un array (como [row, idGrid]), recupera el objeto correcto
    var row = Array.isArray(rowObject) ? rowObject[0] : rowObject;

    if (!row || !row.Ama_Cod) {
        var tipoPago = $("#pagosForm #Pag_Cod option:selected").text().toLowerCase();
        var bakCod = $("#pagosForm #Bak_Cod").val();
        var bakText = $("#pagosForm #Bak_Cod option:selected").text();

        if (tipoPago.indexOf('transferencia') !== -1 || tipoPago.indexOf('transfer') !== -1) {
            if (!bakCod || bakCod.trim() === '' || bakCod === '0' || bakText.trim().toLowerCase() === 'ninguno') {
                errores.push('El Banco Origen es requerido para Transferencias');
                $("#pagosForm #Bak_Cod").css('border-color', '#d9534f');
            } else {
                $("#pagosForm #Bak_Cod").css('border-color', '');
            }
        }
        $.alert("No se encuentra el código del anticipo.");
        return;
    }

    // Limpia el formulario para evitar residuos
    $('#pagosForm')[0].reset();

    // Restaurar el título del modal a modo edición
    $("#pagosDialog").dialog("option", "title", "Agregar Pagos");

    // Hacer AJAX para obtener los datos completos del anticipo
    $.ajax({
        url: '../FRONT/man_ant_1.0.php',
        method: 'GET',
        data: { getAnticipoAjax: true, Ama_Cod: row.Ama_Cod },
        dataType: 'json',
        success: function (response) {
            if (response.success && response.data) {
                var data = response.data;

                // Guardar el código del anticipo para identificar que es una edición
                $("#pagosForm #Ama_Cod").val(data.Ama_Cod || '');

                // Datos del Cliente
                $("#pagosForm #Cli_Cod").val(data.Cli_Cod || '');
                $("#pagosForm #Prs_Cod").val(data.Prs_Cod || '');
                $("#pagosForm #Prs_Ced").val(data.Prs_Ced || '');
                $("#pagosForm #nombre").val(data.cliente || '');
                $("#bandera_prov").val("sel");

                // Datos del Pago
                $("#pagosForm #Ama_Fec").val(data.Ama_Fec || '');
                $("#pagosForm #Pag_Cod").val(data.Ama_Tde || data.Pag_Cod || '').trigger('change');
                $("#pagosForm #Ama_Doc").val(data.Ama_Doc || '');
                $("#pagosForm #Pac_Val").val(data.Ama_Val || '0.00');
                $("#pagosForm #Ama_Obs").val(data.Ama_Obs || '');

                // Cargar imagen del voucher si existe (puede ser data URI o URL)
                if (data.Ama_Img && data.Ama_Img.trim() !== '') {
                    $("#pagosForm #Ama_Img").val(data.Ama_Img);
                    // Mostrar preview de la imagen
                    $("#Ama_Img_Visual").attr('src', data.Ama_Img);
                    $("#Ama_Img_Preview").show();
                    // Mostrar el enlace para ver la imagen (funciona con data URI o URL)
                    $("#Ama_Img_Link").show();
                    $("#Ama_Img_Status").show().text('Imagen cargada').css('color', '#5cb85c');
                } else {
                    $("#pagosForm #Ama_Img").val('');
                    $("#Ama_Img_Visual").attr('src', '');
                    $("#Ama_Img_Preview").hide();
                    $("#Ama_Img_Link").hide();
                    $("#Ama_Img_Status").hide();
                }

                // Datos adicionales
                if (data.Pla_Cod) {
                    $("#pagosForm #Pla_Cod").val(data.Pla_Cod || '');
                }
                if (data.Pla_Nom) {
                    $("#pagosForm #Pla_Nom").val(data.Pla_Nom || '');
                }
                if (data.Pla_Lic) {
                    $("#pagosForm #Pla_Lic").val(data.Pla_Lic || '');
                }

                // Esperar a que se carguen los bancos después del cambio de tipo de pago
                setTimeout(function () {
                    $("#pagosForm #Ban_Cod").val(data.Ban_Cod || '');
                    $("#pagosForm #Bak_Cod").val(data.Bak_Cod || '');

                    // Si hay plugin chosen, actualizar
                    if ($("#pagosForm #Ban_Cod").hasClass("chosen-select")) {
                        $("#pagosForm #Ban_Cod").trigger("chosen:updated");
                    }
                    if ($("#pagosForm #Bak_Cod").hasClass("chosen-select")) {
                        $("#pagosForm #Bak_Cod").trigger("chosen:updated");
                    }

                    // Habilitar campos editables (modo edición)
                    setFormReadOnly(false);

                    // Deshabilitar campos del cliente (no se pueden cambiar al editar)
                    $("#pagosForm #Prs_Ced").prop('readonly', true).css('background-color', '#f5f5f5');
                    $("#pagosForm #nombre").prop('readonly', true).css('background-color', '#f5f5f5');
                    $("#pagosForm #Pla_Nom").prop('readonly', true).css('background-color', '#f5f5f5');
                    $("#pagosForm #Pla_Lic").prop('readonly', true).css('background-color', '#f5f5f5');

                    // Ocultar botón de búsqueda de cliente
                    $("#pagosForm #btnBusCLi").hide();
                }, 500);

                // Muestra el modal
                $('#pagosDialog').dialog('open');
            } else {
                $.alert(response.message || 'No se pudieron cargar los datos del anticipo');
            }
        },
        error: function () {
            $.alert('Ocurrió un error al cargar los datos del anticipo');
        }
    });
}

/* Valida que el anticipo esté en condiciones de ser aprobado y generar comprobante */
function validarAnticipoParaComprobante(rowObject) {
    // Si te pasan un array (como [row, idGrid]), recupera el objeto correcto
    var row = Array.isArray(rowObject) ? rowObject[0] : rowObject;
    // Validar que exista el objeto
    if (!row || !row.Ama_Cod) { return { valid: false, message: 'No se encuentra el código del anticipo.' }; }

    // Validar que el anticipo esté activo (no anulado)
    if (row.Ama_Est && String(row.Ama_Est).toUpperCase() === 'I') { return { valid: false, message: 'No se puede aprobar un anticipo anulado.' }; }

    // Validar que el anticipo esté pendiente (Ama_Tip = 'P')
    if (row.Ama_Tip && String(row.Ama_Tip).toUpperCase() !== 'P') { return { valid: false, message: 'Solo se pueden aprobar anticipos pendientes. Este anticipo ya fue procesado.' }; }

    // Validar que tenga valor
    if (!row.Ama_Val || parseFloat(row.Ama_Val) <= 0) { return { valid: false, message: 'El anticipo debe tener un valor mayor a cero.' }; }

    // Validar que tenga fecha
    if (!row.Ama_Fec || row.Ama_Fec.trim() === '') { return { valid: false, message: 'El anticipo debe tener una fecha válida.' }; }

    // Validar que tenga cliente
    if (!row.Cli_Cod) { return { valid: false, message: 'El anticipo debe estar asociado a un cliente.' }; }

    // Todas las validaciones pasaron
    return { valid: true, message: 'El anticipo está listo para ser aprobado.' };
}

/* Genera el comprobante del anticipo */
function generarComprobanteAnticipo(rowObject) {
    // Si te pasan un array (como [row, idGrid]), recupera el objeto correcto
    var row = Array.isArray(rowObject) ? rowObject[0] : rowObject;

    if (!row || !row.Ama_Cod) {
        $.alert('No se encuentra el código del anticipo.');
        return;
    }

    // Mostrar indicador de carga
    $('#loader').show();

    $.ajax({
        url: '../FRONT/man_ant_1.0.php',
        method: 'POST',
        data: {
            saveComprobanteAjax: true,
            Ama_Cod: row.Ama_Cod
        },
        dataType: 'json',
        success: function (response) {
            $('#loader').fadeOut("slow");

            if (response.success) {
                $.alert('¡Comprobante generado correctamente!');
                // Recargar el grid para mostrar el estado actualizado
                $('#man_antGrid').trigger('reloadGrid');
                // El registro en pag_anticipo_cli ya se realiza en el servidor
                // durante la generación del comprobante; sólo recargamos el grid.
            } else {
                $.alert('Error al generar el comprobante: ' + (response.message || 'Error desconocido'));
            }
        },
        error: function (xhr, status, error) {
            $('#loader').fadeOut("slow");
            $.alert('Error al comunicarse con el servidor: ' + error);
        }
    });
}

/**
 * Función principal para confirmar/aprobar el anticipo
 * Valida y genera el comprobante
 */
function confirmAnticipo(row) {
    // Si te pasan un array (como [row, idGrid]), recupera el objeto correcto
    var rowObject = Array.isArray(row) ? row[0] : row;
    // Validar el anticipo
    var validacion = validarAnticipoParaComprobante(rowObject);

    if (!validacion.valid) {
        $.alert(validacion.message);
        return;
    }

    // Confirmar con el usuario
    $.createDialogConfirm(
        '¿Está seguro que desea aprobar este anticipo y generar el comprobante?',
        rowObject,
        function (confirmedRow) {
            generarComprobanteAnticipo(confirmedRow);
        }
    );
}

function declineAnticipo(row) {
    $.createDialogConfirm('¿Est&aacute; seguro que desea rechazar este Abono?', row, rechazoAnticipo);
}

function rechazoAnticipo(row) {
    $.saveDataJson("", { rechazoAjax: true, Ama_Cod: row[0].Ama_Cod, fila: row[0] }, function (responce) {
        $('#man_antGrid').trigger("reloadGrid");
        $.alert("Abono rechazado con &eacute;xito!");
        return false;
    }, function (responce) { $.alert(responce['message']); }/*,
        function (responce) { $.alert(responce['message']); }*/);
}

function cancelAnticipo(row) {
    $.createDialogConfirm('¿Est&aacute; seguro que desea anular este Abono?', row, cancelAnticipoMan);
}

function cancelAnticipoMan(row) {

    $.saveDataJson("", { cancelAntiAjax: true, Ama_Cod: row[0].Ama_Cod, fila: row[0] }, function (responce) {
        $('#man_antGrid').trigger("reloadGrid");
        $.alert("Abono anulado con &eacute;xito!");
        return false;
    }, function (responce) { $.alert(responce['message']); }/*,
        function (responce) { $.alert(responce['message']); }*/);
}

// Funcion para ver la imagen del voucher en un modal
function verImagenVoucher() {
    var imagenData = $("#pagosForm #Ama_Img").val() ||
        $("#Ama_Img").val() ||
        $("#Ama_Img_Visual").attr('src') ||
        '';

    // Si no hay imagen en los campos, intentar obtenerla de la vista previa
    if (!imagenData || imagenData.trim() === '') {
        var previewSrc = $("#Ama_Img_Visual").attr('src');
        if (previewSrc && previewSrc.trim() !== '') {
            imagenData = previewSrc;
        }
    }

    if (!imagenData || imagenData.trim() === '') {
        $.alert('No hay imagen cargada para mostrar');
        return false;
    }

    if ($("#imagenVoucherDialog").length === 0) {
        $.alert('Error: El modal de imagen no se encuentra en el DOM');
        return false;
    }

    // Resetear zoom al abrir
    resetZoomImagenVoucher();

    $("#imagenVoucherContent").attr('src', imagenData);

    // Usar el mismo tamaño que el modal de datos (600x350 aprox, pero ajustado para imagen)
    $("#imagenVoucherDialog").dialog({
        modal: true,
        width: 700,
        height: 550,
        resizable: true,
        position: { my: "center", at: "center", of: window },
        buttons: {
            "Cerrar": function () {
                $(this).dialog("close");
            }
        }
    });

    // Agregar evento de rueda del mouse para zoom
    $("#imagenVoucherContainer").off('wheel').on('wheel', function (e) {
        e.preventDefault();
        var delta = e.originalEvent.deltaY;
        if (delta < 0) {
            // Scroll hacia arriba = acercar
            zoomImagenVoucher(1.1);
        } else {
            // Scroll hacia abajo = alejar
            zoomImagenVoucher(0.9);
        }
    });

    return false; // Prevenir comportamiento por defecto del enlace

}

// Funcion auxiliar para ver imagen desde el modal de detalles
function verImagenVoucherDesdeDetalle(imagenData) {
    if ($("#imagenVoucherDialog").length === 0) {
        $.alert('Error: El modal de imagen no se encuentra en el DOM');
        return;
    }

    $("#imagenVoucherContent").attr('src', imagenData);

    // Usar el mismo tamaño que el modal de datos (600x350 aprox, pero ajustado para imagen)
    $("#imagenVoucherDialog").dialog({
        modal: true,
        width: 600,
        height: 450,
        resizable: true,
        position: { my: "center", at: "center", of: window },
        buttons: {
            "Cerrar": function () {
                $(this).dialog("close");
            }
        }
    });
}

// Funciones para Descarga Masiva de Vouchers
function buscarClientesVouchers() {
    var fIni = $('#Vou_Fec_Ini').val();
    var fFin = $('#Vou_Fec_Fin').val();

    if (!fIni || !fFin) {
        $.alert("Por favor seleccione un rango de fechas.");
        return;
    }

    $('#vouchersTable tbody').html('<tr><td colspan="4" class="text-center"><i class="fa fa-spinner fa-spin"></i> Buscando...</td></tr>');
    $('#btnGenerarDescargaVouchers').prop('disabled', true);

    $.ajax({
        url: '../FRONT/man_ant_1.0.php',
        method: 'GET',
        data: { buscarClientesVouchersAjax: true, Fec_Des: fIni, Fec_Has: fFin },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                var html = '';
                var totalVouchers = 0;
                var totalRegistros = 0;
                
                if (response.data && response.data.length > 0) {
                    totalRegistros = response.data.length;
                    $.each(response.data, function(i, item) {
                        html += '<tr>' +
                            '<td><input type="checkbox" class="vou-check" data-cli="' + item.Cli_Cod + '" data-pla="' + (item.Pla_Cod || '') + '"></td>' +
                            '<td>' + item.Cliente + '</td>' +
                            '<td>' + (item.Pla_Nom || 'SIN PLANTA') + '</td>' +
                            '<td class="text-center">' + item.Cant + '</td>' +
                            '</tr>';
                        totalVouchers += parseInt(item.Cant) || 0;
                    });
                    $('#btnGenerarDescargaVouchers').prop('disabled', false);
                } else {
                    html = '<tr><td colspan="4" class="text-center">No se encontraron registros con vouchers en este rango.</td></tr>';
                    $('#btnGenerarDescargaVouchers').prop('disabled', true);
                }
                $('#vouchersTable tbody').html(html);
                $('#checkAllVouchers').prop('checked', false);
                
                $('#lblVouchersRegistros').text(totalRegistros);
                $('#lblVouchersTotal').text(totalVouchers);
            } else {
                $.alert(response.message || "Error al buscar registros.");
            }
        },
        error: function() {
            $.alert("Error de comunicación con el servidor.");
        }
    });
}

function toggleAllVouchers(source) {
    $('.vou-check').prop('checked', $(source).is(':checked'));
}

// Función para seguir el estado de la descarga mediante cookies
function trackDownloadVouchers(token) {
    var checkInterval = setInterval(function() {
        var name = "downloadToken_" + token + "=";
        var decodedCookie = decodeURIComponent(document.cookie);
        var ca = decodedCookie.split(';');
        for(var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) == ' ') {
                c = c.substring(1);
            }
            if (c.indexOf(name) == 0) {
                // Cookie encontrada, la descarga está lista
                clearInterval(checkInterval);
                // Borrar la cookie para futuras descargas
                document.cookie = "downloadToken_" + token + "=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
                if ($("#loader").length) {
                    $("#loader").fadeOut("slow");
                }
                return;
            }
        }
    }, 1000);

    // Timeout de seguridad (10 minutos)
    setTimeout(function() {
        clearInterval(checkInterval);
        if ($("#loader").length && $("#loader").is(":visible")) {
            $("#loader").fadeOut("slow");
        }
    }, 600000);
}

function generarDescargaMasiva() {
    var seleccionados = [];
    $('.vou-check:checked').each(function() {
        var cli = $(this).attr('data-cli') || $(this).data('cli');
        var pla = $(this).attr('data-pla') || $(this).data('pla');
        if (cli) {
            seleccionados.push({
                cli: cli,
                pla: (pla === undefined || pla === null) ? "" : pla
            });
        }
    });

    if (seleccionados.length === 0) {
        $.alert("Por favor seleccione al menos un registro con vouchers.");
        return;
    }

    var fIni = $('#Vou_Fec_Ini').val();
    var fFin = $('#Vou_Fec_Fin').val();
    
    // Generar un token único para esta descarga
    var downloadToken = new Date().getTime() + "_" + Math.floor(Math.random() * 1000);

    // Mostrar loader
    if ($("#loader").length) {
        $("#loader").fadeIn("fast");
    }

    // Iniciar el rastreador de la descarga
    trackDownloadVouchers(downloadToken);

    // Crear un formulario temporal para hacer POST y descargar el archivo
    var form = $('<form>', {
        action: 'man_rep_vouchers_zip.php',
        method: 'POST',
        target: '_blank'
    });

    form.append($('<input type="hidden" name="generarZipVouchers" value="true">'));
    form.append($('<input type="hidden" name="Fec_Des">').val(fIni));
    form.append($('<input type="hidden" name="Fec_Has">').val(fFin));
    form.append($('<input type="hidden" name="seleccionados">').val(JSON.stringify(seleccionados)));
    form.append($('<input type="hidden" name="Ses_Emp_Nom">').val(Ses_Emp_Nom || ""));
    form.append($('<input type="hidden" name="downloadToken">').val(downloadToken));

    $('body').append(form);
    form.submit();
    setTimeout(function() { form.remove(); }, 1000);
}
