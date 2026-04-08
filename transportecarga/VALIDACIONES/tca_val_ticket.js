$(function () {
    // Variable para guardar el rowId que se está editando
    var currentEditRowId = null;

    // Función para convertir código de tipo de vehículo a descripción
    function getTipoVehiculoDesc(codigo) {
        var tipos = {
            'V': 'Volqueta Sencilla',
            'VM': 'Volqueta Mula',
            'VB': 'Volqueta Bañera',
            'D': 'TIPO DUMPER',
            'B': 'Bus',
            'C': 'CAMION',
            'T': 'Tractor',
            'M': 'Moto',
            'O': 'Otro'
        };
        return tipos[codigo] || codigo || '';
    }

    // Definir funciones dentro del scope del documento listo
    function cambiarCliente(rowObject) {
        $('#Cli_Cod').val(rowObject.Cli_Cod);
        $('#Cli_Des').val(rowObject.cliente);
        $('#Cli_Ced').val(rowObject.Prs_Ced || '');
        $('#Cli_Dir').val(rowObject.Prs_Dir || '');
        $('#Cli_Cor').val(rowObject.Prs_Cor || '');
        $('#clienteDialog').dialog('close');

        // Cargar anticipos, total tickets y saldo del cliente
        if (rowObject.Cli_Cod) {
            $.get("", { obtenerAnticipoClienteAjax: true, Cli_Cod: rowObject.Cli_Cod },
                function (response) {
                    var saldoAnt = (response && response['success'] === true && response['saldo_anticipo'] !== undefined)
                        ? parseFloat(response['saldo_anticipo']) || 0
                        : 0;
                    var totalTickets = (response && response['total_tickets'] !== undefined)
                        ? parseFloat(response['total_tickets']) || 0
                        : 0;
                    var saldo = (response && response['saldo'] !== undefined)
                        ? parseFloat(response['saldo']) || 0
                        : (saldoAnt - totalTickets);
                    $('#Val_Ant').val(saldoAnt.toFixed(2));
                    $('#Val_Total_Tickets').val(totalTickets.toFixed(2));
                    $('#Val_Saldo').val(saldo.toFixed(2));
                }, 'json')
                .fail(function () {
                    $('#Val_Ant').val('0.00');
                    $('#Val_Total_Tickets').val('0.00');
                    $('#Val_Saldo').val('0.00');
                });
        } else {
            $('#Val_Ant').val('0.00');
            $('#Val_Total_Tickets').val('0.00');
            $('#Val_Saldo').val('0.00');
        }

        // Cargar vehiculo automáticamente
        if (rowObject.Cli_Cod) {
            $.get("", { obtenerVehiculoAjax: true, Cli_Cod: rowObject.Cli_Cod },
                function (response) {
                    if (response['success'] === true && response['vehiculo']) {
                        var vehiculo = response['vehiculo'];
                        $('#Veh_Cod').val(vehiculo.Veh_Cod);
                        $('#Veh_Pla').val(vehiculo.Veh_Pla);
                        $('#Veh_Cap').val(vehiculo.Veh_Cap || '');
                        $('#Veh_Tit').val(getTipoVehiculoDesc(vehiculo.Veh_Tit || ''));
                    } else {
                        // Si no hay vehiculo, limpiar el campo pero permitir selección manual
                        $('#Veh_Cod').val('');
                        $('#Veh_Pla').val('');
                        $('#Veh_Cap').val('');
                        $('#Veh_Tit').val('');
                    }
                }, 'json')
                .fail(function () {
                    // En caso de error, permitir selección manual
                    $('#Veh_Cod').val('');
                    $('#Veh_Pla').val('');
                    $('#Veh_Cap').val('');
                    $('#Veh_Tit').val('');
                });
        }
    }

    function cambiarVehiculo(rowObject) {
        $('#Veh_Cod').val(rowObject.Veh_Cod);
        $('#Veh_Pla').val(rowObject.Veh_Pla);
        $('#Veh_Cap').val(rowObject.Veh_Cap || '');
        $('#Veh_Tit').val(getTipoVehiculoDesc(rowObject.Veh_Tit || ''));
        $('#vehiculoDialog').dialog('close');
    }

    function agregarProducto(rowObject) {
        var existe = false;
        var gridData = $('#Det_Ticket').jqGrid('getRowData');

        // Verificar si el producto ya existe
        $.each(gridData, function (i, row) {
            if (row.Pro_Cod == rowObject.Pro_Cod) {
                existe = true;
                return false;
            }
        });

        if (existe) {
            $.alert('El producto ya se encuentra en el detalle');
            return;
        }

        var newRow = {
            Dtk_Cod: '',
            Pro_Cod: rowObject.Pro_Cod,
            Pro_Des: rowObject.Pro_Des,
            Dtk_Det: rowObject.Pro_Des,
            Dtk_Can: '1.0000',
            Dtk_Pru: rowObject.Pro_Pru || '0.0000',
            Dtk_Tot: (parseFloat(rowObject.Pro_Pru || 0) * 1).toFixed(4)
        };

        // Generar ID temporal único para nuevas filas
        var rowId = newRow.Dtk_Cod || 'new_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        $('#Det_Ticket').jqGrid('addRowData', rowId, newRow);
        // Asegurar que el grid esté en modo edición después de agregar
        $('#Det_Ticket').startGridEdit();
        // Calcular totales después de agregar el producto (con timeout para asegurar que el grid esté actualizado)
        setTimeout(function () {
            calcularTotales();
        }, 300);
        $('#productoDialog').dialog('close');
    }

    // Hacer las funciones disponibles globalmente para getGridButton
    window.cambiarCliente = cambiarCliente;
    window.cambiarVehiculo = cambiarVehiculo;
    window.agregarProducto = agregarProducto;

    // Asegurar que el formulario aparezca arriba en los diálogos
    function reorganizarDialogo(dialogId) {
        var $dialog = $('#' + dialogId);
        var $form = $dialog.find('form');
        var $container = $dialog.find('.condensed');
        if ($form.length && $container.length && $form.next().is($container)) {
            // Si el formulario está después del contenedor, moverlo antes
            $form.insertBefore($container);
        } else if ($form.length && $container.length && !$form.prev().length) {
            // Si el formulario está al inicio pero el contenedor no está después, reorganizar
            $container.insertAfter($form);
        }
    }

    $('#clienteDialog, #vehiculoDialog, #productoDialog').on('dialogopen', function () {
        var dialogId = $(this).attr('id');
        var $dialog = $(this);
        setTimeout(function () {
            reorganizarDialogo(dialogId);
        }, 100);

        // Si es el modal de clientes y hay un Cli_Cod seleccionado, cargar anticipos, total tickets y saldo
        if (dialogId === 'clienteDialog') {
            var cliCod = $('#Cli_Cod').val();
            if (cliCod) {
                $.get("", { obtenerAnticipoClienteAjax: true, Cli_Cod: cliCod },
                    function (response) {
                        var saldoAnt = (response && response['success'] === true && response['saldo_anticipo'] !== undefined)
                            ? parseFloat(response['saldo_anticipo']) || 0
                            : 0;
                        var totalTickets = (response && response['total_tickets'] !== undefined)
                            ? parseFloat(response['total_tickets']) || 0
                            : 0;
                        var saldo = (response && response['saldo'] !== undefined)
                            ? parseFloat(response['saldo']) || 0
                            : (saldoAnt - totalTickets);
                        $('#Val_Ant').val(saldoAnt.toFixed(2));
                        $('#Val_Total_Tickets').val(totalTickets.toFixed(2));
                        $('#Val_Saldo').val(saldo.toFixed(2));
                    }, 'json')
                    .fail(function () {
                        $('#Val_Ant').val('0.00');
                        $('#Val_Total_Tickets').val('0.00');
                        $('#Val_Saldo').val('0.00');
                    });
            } else {
                $('#Val_Ant').val('0.00');
                $('#Val_Total_Tickets').val('0.00');
                $('#Val_Saldo').val('0.00');
            }
        }

        // Si es el modal de vehículos y hay un Cli_Cod seleccionado, filtrar por ese cliente
        if (dialogId === 'vehiculoDialog') {
            var cliCod = $('#Cli_Cod').val() || $dialog.data('Cli_Cod');
            if (cliCod) {
                var form = $('#vehiculoForm');
                if (form.length > 0) {
                    form.find('input[name="Cli_Cod"]').remove();
                    form.append('<input type="hidden" name="Cli_Cod" value="' + cliCod + '">');
                }
            }
            // Cargar vehículos al abrir el diálogo
            setTimeout(function () {
                $.Search('vehiculo');
            }, 200);
        }
    });

    //Inicio del dialogo para presentar clientes
    $.createSearchDialog('#clienteDialog', [
        { label: 'C&oacute;d.Int.', name: 'Cli_Cod', key: true, hidden: true },
        { label: 'C&eacute;dula', name: 'Prs_Ced', width: 30 },
        { label: 'Cliente', name: 'cliente', width: 70 },
        {
            label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act', width: 18, align: 'center', viewable: false,
            formatter: function (cellvalue, options, rowObject) {
                return $.getGridButton(cambiarCliente, rowObject);
            }
        }
    ], null, null, null, null, {
        title: 'Clientes', options: [{ label: '&nbsp;&nbsp;Nombre&nbsp;&nbsp;', value: 'd' },
        { label: '&nbsp;&nbsp;C&eacute;dula&nbsp;&nbsp;', value: 'c' }]
    });

    //Inicio del dialogo para presentar vehiculos
    $.createSearchDialog('#vehiculoDialog', [
        { label: 'C&oacute;d.Int.', name: 'Veh_Cod', key: true, hidden: true },
        { label: 'Cliente', name: 'cliente_nombre', width: 50 },
        { label: 'Placa', name: 'Veh_Pla', width: 30 },
        { label: 'Capacidad', name: 'Veh_Cap', width: 25, align: 'right' },
        { label: 'Tipo Veh.', name: 'Veh_Tit', width: 40, formatter: function (cellvalue) { return getTipoVehiculoDesc(cellvalue); } },
        {
            label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act', width: 18, align: 'center', viewable: false,
            formatter: function (cellvalue, options, rowObject) {
                return $.getGridButton(cambiarVehiculo, rowObject);
            }
        }
    ], null, null, null, null, {
        text: 'search',
        fields: [
            { label: 'Buscar Cliente:', name: 'search_cliente', type: 'text', placeholder: 'Nombre o cédula...', classes: 'form-control input-xs' }
        ]
    }, { title: 'Veh&iacute;culos', options: [{ label: '&nbsp;&nbsp;Placa&nbsp;&nbsp;', value: 'd' }] });

    //Inicio del dialogo para presentar productos
    $.createSearchDialog('#productoDialog', [
        { label: 'C&oacute;d.Int.', name: 'Pro_Cod', key: true, hidden: true },
        { label: 'C&oacute;digo', name: 'Pro_Cod', width: 30 },
        { label: 'Descripci&oacute;n', name: 'Pro_Des', width: 70 },
        {
            label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act', width: 18, align: 'center', viewable: false,
            formatter: function (cellvalue, options, rowObject) {
                return $.getGridButton(agregarProducto, rowObject);
            }
        }
    ], null, null, null, {
        url: '',
        datatype: 'json',
        mtype: 'GET',
        jsonReader: {
            root: "rows",
            page: "page",
            total: "total",
            records: "records",
            repeatitems: false,
            id: "Pro_Cod"
        }
    }, { title: 'Productos', options: [{ label: '&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;', value: 'd' }] });

    // Cargar productos cuando se abre el diálogo
    $('#productoDialog').on('dialogopen', function () {
        setTimeout(function () {
            $.Search('producto');
        }, 200);
    });

    // Función para actualizar los valores de la fila (patrón similar a fac_val_factura2.js)
    function updateRowItemTicket(obj) {
        if (!obj || !obj.rowId) {
            console.log('updateRowItemTicket: obj o rowId no disponible', obj);
            return;
        }

        try {
            // Obtener datos de la fila del grid
            var datosa = $('#Det_Ticket').jqGrid('getRowData', obj['rowId']);
            // Obtener datos del input que se está editando (valores actuales)
            var tr = $('#Det_Ticket').find('tr#' + obj['rowId']);

            var cantidad = 0;
            var precio = 0;

            // Intentar obtener valores directamente de los inputs si están visibles
            if (tr.length) {
                // Buscar el input de cantidad en la fila
                var cantInput = tr.find('input[name="Dtk_Can"], input[id*="Dtk_Can"], td[aria-describedby*="Dtk_Can"] input');
                if (cantInput.length && cantInput.val() !== undefined) {
                    cantidad = parseFloat(cantInput.val() || 0) || 0;
                } else {
                    cantidad = parseFloat(datosa.Dtk_Can || 0) || 0;
                }

                // Buscar el input de precio en la fila
                var pruInput = tr.find('input[name="Dtk_Pru"], input[id*="Dtk_Pru"], td[aria-describedby*="Dtk_Pru"] input');
                if (pruInput.length && pruInput.val() !== undefined) {
                    precio = parseFloat(pruInput.val() || 0) || 0;
                } else {
                    precio = parseFloat(datosa.Dtk_Pru || 0) || 0;
                }

                // Si hay getDataForced, usarlo como alternativa (puede obtener valores mientras se edita)
                if (typeof tr.getDataForced === 'function') {
                    try {
                        var datosb = tr.getDataForced();
                        if (datosb) {
                            if (datosb.Dtk_Can !== undefined && datosb.Dtk_Can !== null && datosb.Dtk_Can !== '') {
                                cantidad = parseFloat(datosb.Dtk_Can) || 0;
                            }
                            if (datosb.Dtk_Pru !== undefined && datosb.Dtk_Pru !== null && datosb.Dtk_Pru !== '') {
                                precio = parseFloat(datosb.Dtk_Pru) || 0;
                            }
                        }
                    } catch (e) {
                        console.log('Error en getDataForced:', e);
                    }
                }
            } else {
                // Si no se encuentra el tr, usar solo los datos del grid
                cantidad = parseFloat(datosa.Dtk_Can || 0) || 0;
                precio = parseFloat(datosa.Dtk_Pru || 0) || 0;
            }

            // Calcular total de la fila
            var total = (cantidad * precio).toFixed(4);

            // Actualizar solo el campo Dtk_Tot usando setCell (sin cerrar la edición)
            $('#Det_Ticket').jqGrid('setCell', obj['rowId'], 'Dtk_Tot', total, '', '', false);

            // Recalcular totales del documento
            calcularTotales();
        } catch (e) {
            console.error('Error en updateRowItemTicket:', e);
        }
    }

    // Funciones para formatear inputs numéricos (patrón similar a fac_val_factura2.js)
    function styleCantTicket(elem, obj, opt) {
        elem.style.textAlign = 'right';
        elem.style.width = '100%';
        elem.placeholder = '0.0000';

        // Guardar referencia al input y rowId
        var $input = $(elem);
        var rowId = obj && obj.rowId ? obj.rowId : null;

        // Si no tenemos rowId, intentar obtenerlo del elemento
        if (!rowId) {
            var tr = $input.closest('tr[id]');
            if (tr.length) {
                rowId = tr.attr('id');
            }
        }

        // Guardar rowId en el elemento
        if (rowId) {
            $input.data('rowid', rowId);
        }

        $input.on('keyup', function () {
            var value = parseFloat(this.value) || 0;
            if (isNaN(value) || value <= 0) {
                $(this).val('1.0000').focus();
            } else if (value % 1 !== 0) {
                var dec = String(value).split('.');
                if (typeof dec[1] !== 'undefined' && dec[1].length > 4) {
                    this.value = parseFloat(value).toFixed(4);
                }
            }
            // Obtener rowId y actualizar la fila
            var currentRowId = $(this).data('rowid') || rowId;
            if (currentRowId) {
                updateRowItemTicket({ rowId: currentRowId });
            }
        });

        $input.on('blur', function () {
            var value = parseFloat(this.value) || 0;
            if (value <= 0) {
                this.value = '1.0000';
            } else {
                this.value = parseFloat(value).toFixed(4);
            }
            // Obtener rowId y actualizar la fila después de perder el foco
            var currentRowId = $(this).data('rowid') || rowId;
            if (currentRowId) {
                updateRowItemTicket({ rowId: currentRowId });
            }
        });
    }

    function stylePruTicket(elem, obj, opt) {
        elem.style.textAlign = 'right';
        elem.style.width = '100%';
        elem.placeholder = '0.0000';

        // Guardar referencia al input y rowId
        var $input = $(elem);
        var rowId = obj && obj.rowId ? obj.rowId : null;

        // Si no tenemos rowId, intentar obtenerlo del elemento
        if (!rowId) {
            var tr = $input.closest('tr[id]');
            if (tr.length) {
                rowId = tr.attr('id');
            }
        }

        // Guardar rowId en el elemento
        if (rowId) {
            $input.data('rowid', rowId);
        }

        $input.on('keyup', function () {
            var value = parseFloat(this.value) || 0;
            if (isNaN(value)) {
                $(this).val('').focus();
            } else if (value % 1 !== 0) {
                var dec = String(value).split('.');
                if (typeof dec[1] !== 'undefined' && dec[1].length > 4) {
                    this.value = parseFloat(value).toFixed(4);
                }
            }
            // Obtener rowId y actualizar la fila
            var currentRowId = $(this).data('rowid') || rowId;
            if (currentRowId) {
                updateRowItemTicket({ rowId: currentRowId });
            }
        });

        $input.on('blur', function () {
            var value = parseFloat(this.value) || 0;
            if (value < 0) {
                this.value = '0.0000';
            } else {
                this.value = parseFloat(value).toFixed(4);
            }
            // Obtener rowId y actualizar la fila después de perder el foco
            var currentRowId = $(this).data('rowid') || rowId;
            if (currentRowId) {
                updateRowItemTicket({ rowId: currentRowId });
            }
        });
    }

    // Un solo tipo de pago: Contado, Crédito o Firma (exclusivo)
    $(document).on('change', '#Tck_Pag_Contado, #Tck_Pag_Credito, #Tck_Pag_Firma', function () {
        var $contado = $('#Tck_Pag_Contado');
        var $credito = $('#Tck_Pag_Credito');
        var $firma = $('#Tck_Pag_Firma');
        var id = $(this).attr('id');
        if ($(this).is(':checked')) {
            if (id !== 'Tck_Pag_Contado') $contado.prop('checked', false);
            if (id !== 'Tck_Pag_Credito') $credito.prop('checked', false);
            if (id !== 'Tck_Pag_Firma') $firma.prop('checked', false);
        } else {
            setTimeout(function () {
                if (!$contado.is(':checked') && !$credito.is(':checked') && !$firma.is(':checked')) {
                    $contado.prop('checked', true);
                }
            }, 0);
        }
    });

    //Inicio Grid para presentar el detalle de ticket
    $('#Det_Ticket').createGrid({
        caption: 'DETALLE DE TICKET', height: 'auto',
        data: [],
        rowNum: 10000000,
        colModel: [
            { label: 'C&oacute;digo', name: 'Dtk_Cod', key: true, width: 50, align: 'center', hidden: true },
            { label: 'Pro_Cod', name: 'Pro_Cod', hidden: true },
            { label: 'Producto', name: 'Pro_Des', align: 'left', width: 200 },
            { label: 'Detalle', name: 'Dtk_Det', align: 'left', width: 200, editable: true, edittype: 'text' },
            {
                label: 'Cant.', name: 'Dtk_Can', align: 'center', width: 80, editable: true, edittype: 'text',
                formatter: 'number', formatoptions: { decimalPlaces: 4 },
                editoptions: { dataInit: styleCantTicket }
            },
            {
                label: 'P.Unitario', name: 'Dtk_Pru', align: 'right', width: 100, editable: true, edittype: 'text',
                formatter: 'number', formatoptions: { decimalPlaces: 4 },
                editoptions: { dataInit: stylePruTicket }
            },
            { label: 'Total', name: 'Dtk_Tot', align: 'right', width: 100, formatter: 'number', formatoptions: { decimalPlaces: 4 } },



            {
                label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act', width: 18, align: 'center', viewable: false,
                formatter: function (cellvalue, options, rowObject) {
                    return '<button type="button" class="btn btn-danger btn-xs" ' +
                        'onclick="quitarItem(\'' + options.rowId + '\')" title="Eliminar">' +
                        '<i class="glyphicon glyphicon-remove"></i></button>';
                }


            }



        ], pgbuttons: false, pgtext: null, footerrow: true, datatype: 'local'
    }, true);
    $('#Det_Ticket').getFootRow(true);
    $('#Det_Ticket').jqGrid('footerData', 'set', {
        Dtk_Can: '<div class="footerTicket"><label>SUBTOTAL:</label></div>',
        Dtk_Tot: '<div class="footerTicket"><input id="t_subtotal" name="t_subtotal" type="text" readonly/></div>'
    }, false);


    // Evento para quitar item del detalle
    /*  window.quitarItem = function (rowObject) {
          $.createDialogConfirm('¿Está seguro que desea eliminar este item?', null, function () {
              // Obtener el rowid de la fila
              var rowIds = $('#Det_Ticket').jqGrid('getDataIDs');
              var rowId = null;
              $.each(rowIds, function (i, id) {
                  var row = $('#Det_Ticket').jqGrid('getRowData', id);
                  if (row.Pro_Cod == rowObject.Pro_Cod) {
                      rowId = id;
                      return false;
                  }
              });
              if (rowId) {
                  $('#Det_Ticket').jqGrid('delRowData', rowId);
              }
              calcularTotales();
          });
      };*/


    window.quitarItem = function (rowId) {
        $.createDialogConfirm('¿Está seguro que desea eliminar este item?', null, function () {
            $('#Det_Ticket').jqGrid('delRowData', rowId);
            calcularTotales();
        });
    };







    // Calcular totales
    window.calcularTotales = function () {
        try {
            var rowIds = $('#Det_Ticket').jqGrid('getDataIDs');
            var subtotal = 0;

            $.each(rowIds, function (i, rowId) {
                // Obtener datos de la fila
                var row = $('#Det_Ticket').jqGrid('getRowData', rowId);
                var tr = $('#Det_Ticket').find('tr#' + rowId);

                var cantidad = 0;
                var precio = 0;

                // Intentar obtener valores de los inputs si están en edición
                if (tr.length) {
                    // Buscar inputs de cantidad
                    var cantInput = tr.find('input[name="Dtk_Can"], input[id*="Dtk_Can"]');
                    if (cantInput.length && cantInput.val() !== undefined && cantInput.val() !== '') {
                        cantidad = parseFloat(cantInput.val()) || 0;
                    } else {
                        cantidad = parseFloat(row.Dtk_Can || 0) || 0;
                    }

                    // Buscar inputs de precio
                    var pruInput = tr.find('input[name="Dtk_Pru"], input[id*="Dtk_Pru"]');
                    if (pruInput.length && pruInput.val() !== undefined && pruInput.val() !== '') {
                        precio = parseFloat(pruInput.val()) || 0;
                    } else {
                        precio = parseFloat(row.Dtk_Pru || 0) || 0;
                    }

                    // Intentar usar getDataForced si está disponible
                    if (typeof tr.getDataForced === 'function') {
                        try {
                            var datosb = tr.getDataForced();
                            if (datosb) {
                                if (datosb.Dtk_Can !== undefined && datosb.Dtk_Can !== null && datosb.Dtk_Can !== '') {
                                    cantidad = parseFloat(datosb.Dtk_Can) || 0;
                                }
                                if (datosb.Dtk_Pru !== undefined && datosb.Dtk_Pru !== null && datosb.Dtk_Pru !== '') {
                                    precio = parseFloat(datosb.Dtk_Pru) || 0;
                                }
                            }
                        } catch (e) { }
                    }
                } else {
                    // Si no se encuentra el tr, usar solo los datos del grid
                    cantidad = parseFloat(row.Dtk_Can || 0) || 0;
                    precio = parseFloat(row.Dtk_Pru || 0) || 0;
                }

                var total = cantidad * precio;

                // Actualizar total en la fila
                $('#Det_Ticket').jqGrid('setCell', rowId, 'Dtk_Tot', total.toFixed(4));
                subtotal += total;
            });

            // Actualizar footer del grid y campos del formulario
            var subtotalFormatted = subtotal.toFixed(4);
            $('#t_subtotal').val(subtotalFormatted);
            $('#Det_Ticket').jqGrid('footerData', 'set', {
                Dtk_Tot: '<div class="footerTicket"><input id="t_subtotal" name="t_subtotal" type="text" readonly value="' + subtotalFormatted + '"/></div>'
            });

            $('#Tck_Val').val(subtotalFormatted);

            // Calcular total con IVA
            var iva = parseFloat($('#Tck_IvA').val() || 0) || 0;
            var total = subtotal + iva;
            $('#Tck_Tot').val(total.toFixed(4));
        } catch (e) {
            console.error('Error en calcularTotales:', e);
        }
    };

    // La edición se maneja a través de startGridEdit() y los eventos en dataInit

    // Guardar ticket
    window.saveTicket = function () {
        if (!$('#Cli_Cod').val() || !$('#Veh_Cod').val()) {
            $.alert('Debe seleccionar un cliente y un vehículo');
            return;
        }

        var rowIds = $('#Det_Ticket').jqGrid('getDataIDs');
        if (rowIds.length === 0) {
            $.alert('Debe agregar al menos un producto al detalle');
            return;
        }

        var detalle = [];
        $.each(rowIds, function (i, rowId) {
            // Obtener datos de la fila del grid
            var row = $('#Det_Ticket').jqGrid('getRowData', rowId);
            // Obtener datos forzados (valores en edición) si están disponibles
            var tr = $('#Det_Ticket').find('tr#' + rowId);
            if (tr.length && typeof tr.getDataForced === 'function') {
                try {
                    var datosForced = tr.getDataForced();
                    if (datosForced) {
                        row = $.extend({}, row, datosForced);
                    }
                } catch (e) {
                    // Si falla, usar solo los datos del grid
                }
            }

            detalle.push({
                Dtk_Cod: row.Dtk_Cod || '',
                Pro_Cod: row.Pro_Cod || '',
                Dtk_Det: row.Dtk_Det || '',
                Dtk_Can: row.Dtk_Can || '0.0000',
                Dtk_Pru: row.Dtk_Pru || '0.0000',
                Dtk_Tot: row.Dtk_Tot || '0.0000'
            });
        });

        // Tipo de pago: sólo uno puede estar marcado (E=Contado, C=Crédito, F=Firma)
        var tipoPago = 'E';
        if ($('#Tck_Pag_Firma').is(':checked')) {
            tipoPago = 'F';
        } else if ($('#Tck_Pag_Credito').is(':checked')) {
            tipoPago = 'C';
        } else if ($('#Tck_Pag_Contado').is(':checked')) {
            tipoPago = 'E';
        } else {
            $('#Tck_Pag_Contado').prop('checked', true);
            $('#Tck_Pag_Credito').prop('checked', false);
            $('#Tck_Pag_Firma').prop('checked', false);
            tipoPago = 'E';
        }
        $('#Tck_Pag').val(tipoPago);

        var data = {
            saveTicket: true,
            Tck_Cod: $('#Tck_Cod').val(),
            Cli_Cod: $('#Cli_Cod').val(),
            Veh_Cod: $('#Veh_Cod').val(),
            Tck_Fec: $('#Tck_Fec').val(),
            Tck_Val: $('#Tck_Val').val(),
            Tck_IvA: $('#Tck_IvA').val(),
            Tck_Tot: $('#Tck_Tot').val(),
            Tck_Est: $('#Tck_Est').val(),
            Tck_Pag: $('#Tck_Pag').val(),
            Det_Ticket: JSON.stringify(detalle)
        };

        $.createDialogConfirm('¿Está seguro que desea guardar el ticket?', data, function (d) {
            $.post("", d,
                function (response) {
                    if (response['success'] === true) {
                        var tckCod = response['Tck_Cod'];
                        var tckNum = response['Tck_Num'] || '';
                        $.alert("Ticket guardado correctamente!<br><br><button class='btn btn-success btn-sm' onclick='imprimirTicket(" + tckCod + ")'><i class='glyphicon glyphicon-print'></i> Imprimir Ticket</button>", function () {
                            $('#Tck_Cod').val(tckCod);
                            if (tckNum) {
                                $('#Tck_Num').val(tckNum);
                                $('#Tck_Num_Display').val(tckNum);
                            }
                            limpiarFormulario();
                        });
                    } else {
                        $.alert(response['message']);
                    }
                }, 'json')
                .fail(function () {
                    $.alert('Error al guardar el ticket');
                });
        });
    };

    // Obtener siguiente numero de ticket
    function obtenerSiguienteNumero() {
        $.get("", { getSiguienteNumeroAjax: true },
            function (response) {
                if (response['success'] === true && response['numero']) {
                    $('#Tck_Num').val(response['numero']);
                    $('#Tck_Num_Display').val(response['numero']);
                }
            }, 'json')
            .fail(function () {
                // En caso de error, mostrar 1 por defecto
                $('#Tck_Num').val('1');
                $('#Tck_Num_Display').val('1');
            });
    }

    // Limpiar formulario
    window.limpiarFormulario = function () {
        $('#frm_ticket')[0].reset();
        $('#Tck_Cod').val('');
        $('#Tck_Num').val('');
        $('#Tck_Num_Display').val('');
        $('#Cli_Cod').val('');
        $('#Cli_Des').val('');
        $('#Cli_Ced').val('');
        $('#Cli_Dir').val('');
        $('#Cli_Cor').val('');
        $('#Veh_Cod').val('');
        $('#Veh_Pla').val('');
        $('#Veh_Cap').val('');
        $('#Veh_Tit').val('');
        $('#Val_Ant').val('0.00');
        $('#Val_Total_Tickets').val('0.00');
        $('#Val_Saldo').val('0.00');
        var now = new Date();
        var fecha = now.getFullYear() + '-' +
            String(now.getMonth() + 1).padStart(2, '0') + '-' +
            String(now.getDate()).padStart(2, '0') + 'T' +
            String(now.getHours()).padStart(2, '0') + ':' +
            String(now.getMinutes()).padStart(2, '0');
        $('#Tck_Fec').val(fecha);
        $('#Tck_Val').val('0.0000');
        $('#Tck_IvA').val('0.0000');
        $('#Tck_Tot').val('0.0000');
        $('#Tck_Est').val('A');
        $('#Tck_Pag').val('E');
        $('#Tck_Pag_Contado').prop('checked', true);
        $('#Tck_Pag_Credito').prop('checked', false);
        $('#Tck_Pag_Firma').prop('checked', false);
        $('#Det_Ticket').jqGrid('clearGridData');
        calcularTotales();
        // Obtener siguiente numero de ticket después de limpiar
        obtenerSiguienteNumero();
    };

    // Función para imprimir ticket
    window.imprimirTicket = function (Tck_Cod, formato) {
        formato = formato || 'escpos'; // Por defecto usa ESC/POS, pero se puede cambiar a 'html' si se necesita
        if (!Tck_Cod) {
            $.alert('No se puede imprimir el ticket. Código inválido.');
            return;
        }
        
        if (formato === 'escpos') {
            // Abrir directamente para imprimir (sin previsualización)
            var ventana = window.open(
                "?imprimirTicketAjax=true&Tck_Cod=" + Tck_Cod + "&formato=escpos&accion=preview",
                '_blank',
                'width=600,height=700'
            );
            ventana.focus();
        } else {
            // Imprimir HTML (comportamiento alternativo, se puede usar con imprimirTicket(Tck_Cod, 'html'))
            $.get("", { imprimirTicketAjax: true, Tck_Cod: Tck_Cod, formato: 'html' },
                function (response) {
                    if (response['success'] === true && response['html']) {
                        // Crear ventana de impresión
                        var ventana = window.open('', '_blank', 'width=800,height=600');
                        ventana.document.write(response['html']);
                        ventana.document.close();
                        ventana.focus();
                        // Esperar a que se cargue el contenido antes de imprimir
                        setTimeout(function () {
                            ventana.print();
                        }, 500);
                    } else {
                        $.alert('Error al generar el ticket para imprimir');
                    }
                }, 'json')
                .fail(function () {
                    $.alert('Error al obtener los datos del ticket');
                });
        }
    };

    // Función para cargar datos del ticket para editar
    function cargarTicketParaEditar(Tck_Cod) {
        if (!Tck_Cod) {
            obtenerSiguienteNumero();
            calcularTotales();
            return;
        }

        $.get("", { cargarTicketAjax: true, Tck_Cod: Tck_Cod },
            function (response) {
                if (response['success'] === true && response['ticket']) {
                    var ticket = response['ticket'];

                    // Cargar datos principales
                    $('#Tck_Cod').val(ticket.Tck_Cod || '');
                    $('#Tck_Num').val(ticket.Tck_Num || '');
                    $('#Tck_Num_Display').val(ticket.Tck_Num || '');
                    $('#Tck_Fec').val(ticket.Tck_Fec || '');
                    $('#Cli_Cod').val(ticket.Cli_Cod || '');
                    $('#Cli_Des').val(ticket.cliente_nombre || '');
                    $('#Cli_Ced').val(ticket.Prs_Ced || '');
                    $('#Cli_Dir').val(ticket.Prs_Dir || '');
                    $('#Cli_Cor').val(ticket.Prs_Cor || '');
                    $('#Veh_Cod').val(ticket.Veh_Cod || '');
                    $('#Veh_Pla').val(ticket.Veh_Pla || '');
                    $('#Veh_Cap').val(ticket.Veh_Cap || '');
                    var vehTipo = ticket.Veh_Tit || ticket.Veh_Tip || '';
                    $('#Veh_Tit').val(getTipoVehiculoDesc(vehTipo));
                    $('#Tck_Val').val(parseFloat(ticket.Tck_Val || 0).toFixed(4));
                    $('#Tck_IvA').val(parseFloat(ticket.Tck_IvA || 0).toFixed(4));
                    $('#Tck_Tot').val(parseFloat(ticket.Tck_Tot || 0).toFixed(4));

                    // Tipo de pago (E=Contado, C=Crédito, F=Firma)
                    var tckPag = (ticket.Tck_Pag || 'E').toUpperCase();
                    $('#Tck_Pag').val(tckPag);
                    $('#Tck_Pag_Contado').prop('checked', tckPag === 'E');
                    $('#Tck_Pag_Credito').prop('checked', tckPag === 'C');
                    $('#Tck_Pag_Firma').prop('checked', tckPag === 'F');

                    // Cargar anticipo, total tickets y saldo del cliente
                    if (ticket.Cli_Cod) {
                        $.get("", { obtenerAnticipoClienteAjax: true, Cli_Cod: ticket.Cli_Cod },
                            function (resp) {
                                var saldoAnt = (resp && resp['success'] === true && resp['saldo_anticipo'] !== undefined)
                                    ? parseFloat(resp['saldo_anticipo']) || 0
                                    : 0;
                                var totalTickets = (resp && resp['total_tickets'] !== undefined)
                                    ? parseFloat(resp['total_tickets']) || 0
                                    : 0;
                                var saldo = (resp && resp['saldo'] !== undefined)
                                    ? parseFloat(resp['saldo']) || 0
                                    : (saldoAnt - totalTickets);
                                $('#Val_Ant').val(saldoAnt.toFixed(4));
                                $('#Val_Total_Tickets').val(totalTickets.toFixed(4));
                                $('#Val_Saldo').val(saldo.toFixed(4));
                            }, 'json').fail(function () {
                                $('#Val_Ant').val('0.0000');
                                $('#Val_Total_Tickets').val('0.0000');
                                $('#Val_Saldo').val('0.0000');
                            });
                    } else {
                        $('#Val_Ant').val('0.0000');
                        $('#Val_Total_Tickets').val('0.0000');
                        $('#Val_Saldo').val('0.0000');
                    }

                    // Cargar detalles
                    $('#Det_Ticket').jqGrid('clearGridData');
                    if (response['detalles'] && response['detalles'].length > 0) {
                        $.each(response['detalles'], function (index, det) {
                            var rowData = {
                                Dtk_Cod: det.Dtk_Cod || '',
                                Pro_Cod: det.Pro_Cod || '',
                                Pro_Des: det.Pro_Des || '',
                                Dtk_Det: det.Dtk_Det || '',
                                Dtk_Can: parseFloat(det.Dtk_Can || 0).toFixed(4),
                                Dtk_Pru: parseFloat(det.Dtk_Pru || 0).toFixed(4),
                                Dtk_Tot: parseFloat(det.Dtk_Tot || 0).toFixed(4)
                            };
                            var rowId = det.Dtk_Cod || 'new_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                            $('#Det_Ticket').jqGrid('addRowData', rowId, rowData);
                        });
                        $('#Det_Ticket').startGridEdit();
                    }

                    // Calcular totales después de cargar
                    setTimeout(function () {
                        calcularTotales();
                    }, 300);
                } else {
                    $.alert(response['message'] || 'Error al cargar los datos del ticket');
                    obtenerSiguienteNumero();
                    calcularTotales();
                }
            }, 'json')
            .fail(function () {
                $.alert('Error al obtener los datos del ticket');
                obtenerSiguienteNumero();
                calcularTotales();
            });
    }

    // Detectar si hay Tck_Cod en la URL y cargar datos
    var urlParams = new URLSearchParams(window.location.search);
    var tckCod = urlParams.get('Tck_Cod');
    if (tckCod) {
        cargarTicketParaEditar(parseInt(tckCod));
    } else {
        // Obtener siguiente numero de ticket al cargar la página
        obtenerSiguienteNumero();
        // Inicializar calculo de totales
        calcularTotales();
    }

    // Inicializar dialog para registrar vehículo
    $('#vehiculoRegistroDialog').dialog({
        autoOpen: false,
        modal: true,
        width: 600,
        resizable: false,
        close: function () {
            // Limpiar formulario al cerrar
            $('#frm_vehiculo_registro')[0].reset();
            $('#Veh_Cod_Reg').val('');
        }
    });

    // Función para abrir modal de registro de vehículo
    window.registrarVehiculo = function () {
        var cliCod = $('#Cli_Cod').val();
        if (!cliCod || cliCod === '') {
            $.alert('Debe seleccionar un cliente antes de registrar un vehículo');
            return;
        }
        $('#Cli_Cod_Reg').val(cliCod);
        $('#vehiculoRegistroDialog').dialog('open');
    };

    // Función para guardar vehículo
    window.guardarVehiculo = function () {
        var form = $('#frm_vehiculo_registro');
        // Validar campos requeridos
        if (!form[0].checkValidity()) {
            form[0].reportValidity();
            return;
        }
        var data = {
            saveVehiculo: true,
            Cli_Cod: $('#Cli_Cod_Reg').val(),
            Veh_Pla: $('#Veh_Pla_Reg').val().trim().toUpperCase(),
            Veh_Mar: $('#Veh_Mar_Reg').val().trim(),
            Veh_Col: $('#Veh_Col_Reg').val().trim(),
            Veh_Cap: $('#Veh_Cap_Reg').val() || 0,
            Veh_Tit: $('#Veh_Tit_Reg').val(),

        };

        if (!data.Cli_Cod || data.Cli_Cod === '') {
            $.alert('Debe seleccionar un cliente');
            return;
        }

        if (!data.Veh_Pla || data.Veh_Pla === '') {
            $.alert('La placa del vehículo es requerida');
            return;
        }

        $.createDialogConfirm('¿Está seguro que desea registrar este vehículo?', data, function (d) {
            $.post("", d,
                function (response) {
                    if (response['success'] === true) {
                        $.alert("Vehículo registrado correctamente!", function () {
                            // Cerrar modal
                            $('#vehiculoRegistroDialog').dialog('close');
                            // Si hay datos del vehículo, seleccionarlo automáticamente
                            if (response['vehiculo']) {
                                cambiarVehiculo(response['vehiculo']);
                            } else {
                                // Recargar el grid de vehículos para que aparezca el nuevo
                                setTimeout(function () {
                                    $.Search('vehiculo');
                                }, 300);
                            }
                        });
                    } else {
                        $.alert(response['message'] || 'Error al registrar el vehículo');
                    }
                }, 'json')
                .fail(function () {
                    $.alert('Error al registrar el vehículo');
                });
        });
    };
});

