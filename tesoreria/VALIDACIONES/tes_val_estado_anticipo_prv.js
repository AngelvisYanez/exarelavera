var grid = $('#searchGrid');
var arrayAsiento = [],
    arrayCheques = [],
    arrayConsumos = [],
    arraySubAntConsumidos = [],
    arrayModAsiento = [],
    arrayCuentasPlan = [],
    arrayDetAsiento = [];

var perCodAct = 0,
    existeCheq = false;
var anticipoDetalleActual = null;
var consumoDetalleActual = null;

$(function () {
    $("#successDialog").createDialog({ width: 500, height: 200, icon: 'print' });
    $("#verPagosDialog").createDialog({ width: 400, height: 290, icon: 'info-sign' });
    $("#verPagosDialogMod").createDialog({ width: 700, height: 450, icon: 'info-sign' });
    $("#verAsientoDialogMod").createDialog({ width: 760, height: 460, icon: 'info-data' });
    $("#cruceDialog" ).createDialog({width:900,height:485,icon:'info-sign'});
    $('#pagosDialog').createDialog({ height: 325, icon: 'usd' });
    $("#tabs_ant_det").tabs();
    $('#tabs_sub_ant_det').tabs();
    $('.datepicker').createDatePickers({ checkAvailability: true, hideMsg: false }).mask('9999-99-99', { placeholder: '_' });
    window._provPickerTarget = 'form';
    changePeriodo();
    createGrid();
    crearGridShowPagosAsi();
    crearGridshowPagosChe();
    crearGridConsumosAnticipo();
    createPagosModGrid();
    gridCruce();
    createGridShowAsiDetalle();
    createGridSubAntConsumidos();
    gridCruce();
    gridCuentasCruce();
    changeCuentaCod();

     $.createSearchDialog('proveedoresDialog',[
       	{ label: 'C&oacute;d.Int.', name: 'Prv_Cod', key: true, width: 15,align:"center",hidden:true },
       	{ label: 'C&eacute;dula/RUC', name: 'Prs_Ced', width: 50 },
       	{ label: 'Proveedor', name: 'nombre', width: 100},
       	{ label: 'Direcc.', name: 'Prs_Dir', width: 60 },
       	{ label:'&nbsp;', name: 'act1', width: 20, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{action:selectProveedorCruce} }
   	],null,null,null,{headertitles:true},{ title:'Proveedor', text:'searchPrv' });
});

function atpEstKardexRow(reg) {
    if (!reg) return '';
    var e = reg.Atp_Est != null ? reg.Atp_Est : (reg.atp_est != null ? reg.atp_est : reg.ATP_EST);
    return String(e == null ? '' : e).trim().toUpperCase();
}

function actualizarVisibilidadColumnaProveedorKardex() {
    var $g = $('#searchGrid');
    if (!$g.length || !$g.data('jqGrid')) return;
    var prvCod = String($('#busq_Prv_Cod').val() || '').trim();
    var ocultar = prvCod !== '' && prvCod !== '0';
    if (ocultar) {
        $g.jqGrid('hideCol', 'nombre');
    } else {
        $g.jqGrid('showCol', 'nombre');
    }
}

/** Ajusta HTML del reporte para impresión/PDF: bordes, texto largo, quita col. botón, anulados en color */
function prepararTablaReporteKardexParaImpresion(g) {
    var $t = $('#tablaReporte');
    $t.addClass('kardex-tabla-lista');
    function mainHeaderTr() {
        var $h = null;
        $t.find('thead tr').each(function () {
            var tx = ($(this).find('th').first().text() || '').trim();
            if (tx === 'Tipo' || tx.indexOf('Tipo') === 0) {
                $h = $(this);
                return false;
            }
        });
        if (!$h || !$h.length) {
            $h = $t.find('thead tr').has('th').filter(function () {
                return $(this).find('th').length > 3;
            }).first();
        }
        return $h;
    }
    var $headerTr = mainHeaderTr();
    if ($headerTr && $headerTr.length) {
        var $ths = $headerTr.find('th');
        var lastIdx = $ths.length - 1;
        var $lastTh = $ths.last();
        var lastTxt = ($lastTh.text() || '').replace(/\s+/g, '');
        var lastHtml = ($lastTh.html() || '');
        var lastIsBtn = lastTxt === '' || lastHtml.indexOf('ui-icon') >= 0 || $lastTh.find('.ui-icon').length > 0;
        if (lastIsBtn && lastIdx >= 0) {
            $t.find('tr').each(function () {
                var $cells = $(this).find('th, td');
                if ($cells.length > lastIdx) {
                    $cells.eq(lastIdx).remove();
                }
            });
        }
        $headerTr = mainHeaderTr();
        $ths = $headerTr.find('th');
        $ths.each(function (col) {
            var h = $(this).text() || '';
            if (h.indexOf('Concepto') >= 0 || h.indexOf('obs') >= 0 || h.indexOf('Glosa') >= 0) {
                $(this).addClass('kardex-print-glosa');
                $t.find('tbody tr').each(function () {
                    var $tds = $(this).find('td');
                    if ($tds.length > col) {
                        $tds.eq(col).addClass('kardex-print-glosa');
                    }
                });
            }
        });
    }
    $t.css({ width: '100%', 'table-layout': 'auto', 'border-collapse': 'collapse' });
    $t.find('th, td').each(function () {
        var $c = $(this);
        var s = $c.attr('style') || '';
        s = s.replace(/white-space\s*:\s*nowrap/gi, 'white-space: normal');
        s = s.replace(/overflow\s*:\s*hidden/gi, 'overflow: visible');
        $c.attr('style', s);
        $c.css({
            border: '1px solid #555',
            padding: '3px 5px',
            'vertical-align': 'top',
            'word-wrap': 'break-word',
            'word-break': 'break-word'
        });
    });
    var data = g.jqGrid('getGridParam', 'data') || [];
    var $dataRows = $t.find('tbody > tr').filter(function () {
        return !$(this).hasClass('footrow');
    });
    $dataRows.each(function (i) {
        var rd = data[i];
        if (!rd) {
            return;
        }
        if (atpEstKardexRow(rd) === 'I') {
            $(this).find('td').css({ 'background-color': '#FADDDD', color: '#222' });
        }
    });
}

function getKardexRowsTodasLasPaginas() {
    var g = $('#searchGrid');
    var totalReg = parseInt(g.jqGrid('getGridParam', 'records'), 10) || 0;
    var rowsReq = Math.max(totalReg, 1000);
    var post = ($('#searchAnticipos').length ? $('#searchAnticipos').getData('anticiposAjax') : {});
    post = $.extend({}, post, { anticiposAjax: true, page: 1, rows: rowsReq });
    return new Promise(function (resolve, reject) {
        $.getDataJson("", post, function (result) {
            resolve((result && result.rows) ? result.rows : []);
        }, function (err) {
            reject(err);
        });
    });
}

function escapeKardexHtml(v) {
    return String(v == null ? '' : v)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function renderKardexTablaCompleta(rows, g) {
    var mostrarProveedor = true;
    try {
        mostrarProveedor = !((g.jqGrid('getColProp', 'nombre') || {}).hidden === true);
    } catch (e) { mostrarProveedor = true; }

    var html = '<table class="kardex-tabla-lista" cellspacing="0" cellpadding="0" style="width:100%;border-collapse:collapse;table-layout:auto;font-size:12px;">';
    html += '<thead><tr>';
    html += '<th>Tipo</th><th>Estado</th><th>Cod. Int.</th><th>No. Compr.</th><th>Fecha</th><th>Cédula</th>';
    if (mostrarProveedor) html += '<th>Proveedor</th>';
    html += '<th>Concepto / obs.</th><th>Anticipo</th><th>Consumo</th><th>Saldo</th></tr></thead><tbody>';

    var sumT = 0, sumC = 0, lastSaldo = 0;
    $.each(rows || [], function (_, r) {
        var est = atpEstKardexRow(r);
        var t = parseFloat(r.TOTAL || r.total || 0) || 0;
        var c = parseFloat(r.CONSUMO || r.consumo || 0) || 0;
        var s = parseFloat(r.tot_anti || r.TOT_ANTI || 0) || 0;
        if (est !== 'I') { sumT += t; sumC += c; }
        lastSaldo = s;
        var rowStyle = (est === 'I') ? ' style="background:#FADDDD;"' : '';
        html += '<tr' + rowStyle + '>';
        html += '<td>' + escapeKardexHtml(r.Tipo_Linea) + '</td>';
        html += '<td>' + escapeKardexHtml(r.Estado || '') + '</td>';
        html += '<td>' + escapeKardexHtml(r.Atp_Cod) + '</td>';
        html += '<td>' + escapeKardexHtml(r.codigoCompra) + '</td>';
        html += '<td>' + escapeKardexHtml(r.Fecha_Mov) + '</td>';
        html += '<td>' + escapeKardexHtml(r.cedProv || r.Prs_Ced || '') + '</td>';
        if (mostrarProveedor) html += '<td>' + escapeKardexHtml(r.nombre || '') + '</td>';
        html += '<td>' + escapeKardexHtml(r.Glosa || '') + '</td>';
        html += '<td style="text-align:right;">' + formatMoney(t) + '</td>';
        html += '<td style="text-align:right;">' + formatMoney(c) + '</td>';
        html += '<td style="text-align:right;">' + formatMoney(s) + '</td>';
        html += '</tr>';
    });

    var colspan = mostrarProveedor ? 8 : 7;
    html += '<tr class="footrow"><td colspan="' + colspan + '" style="text-align:right;"><b>TOTALES:</b></td>';
    html += '<td style="text-align:right;"><b>' + formatMoney(sumT) + '</b></td>';
    html += '<td style="text-align:right;"><b>' + formatMoney(sumC) + '</b></td>';
    html += '<td style="text-align:right;"><b>' + formatMoney(lastSaldo) + '</b></td></tr>';
    html += '</tbody></table>';
    return html;
}

/** Impresión del kardex (misma tabla que Exportar PDF); usable desde la barra de filtros */
function imprimirReporteKardexAnticipos() {
    var g = $('#searchGrid');
    if (!g.length || !g[0].grid) {
        if (typeof $.alert === 'function') {
            $.alert('La grilla no está lista. Pulse Buscar primero.',null,'warning');
        } else {
            $.alert('La grilla no está lista. Pulse Buscar primero.',null,'warning');
        }
        return;
    }
    var n = parseInt(g.jqGrid('getGridParam', 'records'), 10) || 0;
    if (n < 1) {
        if (typeof $.alert === 'function') {
            $.alert('No hay datos en el reporte para imprimir.',null,'warning');
        } else {
            $.alert('No hay datos en el reporte para imprimir.',null,'warning');
        }
        return;
    }
    getKardexRowsTodasLasPaginas().then(function (rows) {
        if (!rows || !rows.length) {
            $.alert('No hay datos en el reporte para imprimir.');
            return;
        }
        $('#tablaReporte').html(renderKardexTablaCompleta(rows, g));
        $('#imprimir').printElement({
            pageTitle: 'Estado de Cuenta de Anticipo a Proveedores',
            overrideElementCSS: [{ href: '../../mascaras/model1/estilos/print.css', media: 'print' }]
        });
    }).catch(function () {
        $.alert('No se pudo cargar todas las páginas para imprimir.');
    });
}

function exportarExcelKardexAnticipos() {
    var g = $('#searchGrid');
    if (!g.length || !g[0].grid) {
        if (typeof $.alert === 'function') {
            $.alert('La grilla no está lista. Pulse Buscar primero.',null,'warning');
        } else {
            $.alert('La grilla no está lista. Pulse Buscar primero.',null,'warning');
        }
        return;
    }
    var n = parseInt(g.jqGrid('getGridParam', 'records'), 10) || 0;
    if (n < 1) {
        $.alert('No hay datos en el reporte para exportar.',null,'warning');
        return;
    }
    getKardexRowsTodasLasPaginas().then(function (rows) {
        if (!rows || !rows.length) {
            $.alert('No hay datos en el reporte para exportar.');
            return;
        }
        $('#tablaReporte').html(renderKardexTablaCompleta(rows, g));
        $.downloadFile(
            $.exportarExcelBlob($('#imprimir').html(), 'Ant-Prov'),
            'Ant-Prov_' + $.getDate() + '.xls'
        );
    }).catch(function () {
        $.alert('No se pudo cargar todas las páginas para exportar.');
    });
}

function createGrid() {
    grid.createGrid({
        caption: 'Estado de anticipos (kardex por fecha)', stateCol: 'Tipo_Linea',
        height: '300',
        url: (typeof UrlSaveJson !== 'undefined' ? UrlSaveJson : ''),
        mtype: 'GET',
        postData: ($('#searchAnticipos').length ? $('#searchAnticipos').getData('anticiposAjax') : {}),
        rowattr: function (rd) {
            if (!rd) {
                return {};
            }
            var tipo = rd.Tipo_Linea || '';
            if (tipo === 'Saldo inicial') {
                return {};
            }
            var est = atpEstKardexRow(rd);
            if (est === 'I' && (tipo === 'Anticipo' || tipo === 'Consumo')) {
                return { 'class': 'row-anulado-anticipo' };
            }
            return {};
        },
        colModel: [
            { label: '', name: 'row_id', key: true, hidden: true },
            { label: 'Tipo', name: 'Tipo_Linea', width: 28, align: "left" },
            { label: 'Estado', name: 'Estado', width: 14, align: "center" },
            { label: 'Cod. Int.', name: 'Atp_Cod', width: 22, align: "left" },
            { label: 'No. Compr.', name: 'codigoCompra', width: 30, align: "left" },
            { label: 'Fecha', name: 'Fecha_Mov', width: 28, align: "left" },
            { label: ' ', name: 'Prv_Cod', hidden: true },
            { label: ' ', name: 'Prs_Cod', hidden: true },
            { label: ' ', name: 'prvCod', hidden: true },
            { label: ' ', name: 'Cli_Cod', hidden: true },
            { label: ' ', name: 'Asi_Cod', hidden: true },
            { label: ' ', name: 'Pag_Cod', hidden: true },
            { label: ' ', name: 'Pap_Ctd', hidden: true },
            { label: ' ', name: 'Pap_Obs', hidden: true },
            { label: ' ', name: 'Com_Val', hidden: true },
            { label: ' ', name: 'Com_Fec', hidden: true },
            { label: ' ', name: 'Com_Cod', hidden: true },
            { label: ' ', name: 'Com_Cod_eg', hidden: true },
            { label: '', name: 'Atp_Est', hidden: true },
            { label: '', name: 'Pag_Des', hidden: true },
            { label: '', name: 'Pap_Es2', hidden: true },
            { label: '', name: 'Atp_Fec', hidden: true },
            { label: '', name: 'usuario', hidden: true },
            { label: '', name: 'Com_Sys', hidden: true },
            { label: '', name: 'Atp_Obs', hidden: true },
            { label: 'C&eacute;dula', name: 'cedProv', width: 36, align: "left", cellattr: function () { return 'style="' + excelFormats.text + '"'; } },
            { label: '', name: 'Prs_Ced', hidden: true },
            { label: 'Proveedor', name: 'nombre', width: 90, align: "left" },
            { label: 'Concepto / obs.', name: 'Glosa', width: 70, align: "left" },
            { label: 'Anticipo', name: 'TOTAL', width: 52, align: 'right', formatter: 'currency', formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '$ 0.00' } },
            { label: 'Consumo', name: 'CONSUMO', width: 52, align: 'right', formatter: 'currency', formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '$ 0.00' } },
            { label: 'Saldo', name: 'tot_anti', width: 52, align: 'right', formatter: 'currency', formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '$ 0.00' } },
            {
                label: '<center><i class="ui-icon ui-icon-info"></i></center>',
                name: 'btn_det',
                width: 10,
                align: 'center',
                sortable: false,
                viewable: false,
                title: false,
                formatter: function (cellValue, options, rowObject) {
                    if ((rowObject.Tipo_Linea || '') === 'Saldo inicial') {
                        return '-';
                    }
                    return $.getGridButton(verInfoKardex, rowObject, 'Ver detalle', 'info-sign', '', 'primary');
                }
            }

        ],
        footerrow: true,
        userDataOnFooter: true,
        subGrid: false,
        rowNum: 1000,
        rowList: [1000, 1500, 2000],
        gridview: true,
        viewrecords: true,
        loadComplete: function (data) {
            actualizarVisibilidadColumnaProveedorKardex();
            calculateValFooter();
            cellColors();
        }
    }, false, '#searchGridPager', {
        refresh: false, view: false
    });

}

function verInfoKardex(data) {
    if (!data) return;
    if ((data.Tipo_Linea || '') === 'Anticipo') {
        verAnticipo([data]);
        return;
    }
    verMovimiento([data]);
}

/****************************************************************/
/************************** jose cumbicos ***********************/
function selectProveedorCruce(proveedor){
    $('#PrsCed').html(proveedor.Prs_Ced);
    $('#Prs_Nom_Pagos').val(proveedor.nombre);
    $('#Prv_Cod_Pagos').val(proveedor.Prv_Cod);
    $('#Prs_Cod_Pagos').val(proveedor.Prs_Cod);
    $('#proveedoresDialog').dialog('close');  
    $('#crucesGrid').clearGrid(true);
    $.get('', { anticiposCruceAjax: true, Prv_Cod:proveedor.Prv_Cod}, function(r){
        if(r.rows.length>0)
            $('#crucesGrid').setRows(r.rows);
       
    },'json')
	.fail(function(error) {
		console.log("El Servidor ha fallado en responder!");
	});
}
function habilitaCacilleros(tipoPago){
//`
    $('.Bloqueo').prop('disabled', true); 
    $('.'+tipoPago).prop('disabled', false);    
    //$('#infoCruce').find('.' + tipoPago).removeClass('hidden');
    //$('#infoCruce').find('.' + tipoPago).find('.form-control').prop('required', true);
    
    $('#BanCod option').hide();
    if(tipoPago==='Efectivo'||tipoPago==='Deposito')
        $('#BanCod option[data-tip="C"]').show();        
    if(tipoPago =='Transferencia'||tipoPago=='Deposito'||tipoPago=='Cheque')   
        $('#BanCod option[data-tip="B"]').show();
    
    
    $('#BanCod option').filter(function() {
        return $(this).css('display') !== 'none';
    }).first().prop('selected', true);
    if(tipoPago=='Otros')
        $('#btnCuenta').removeClass('disabled');         
    else{ 
        $('#btnCuenta').addClass('disabled');
        $('#Pld_Des_Otr').val('');$('#infPldCdc').html('');
    }
    
}
function preanularConsumo(o){
    $.createDialogConfirm('¿Est&aacute; seguro que desea anular el Consumo?',o,saveBajaConsumo);
}
function saveBajaConsumo(o){
	$.post( "", {bajaConsumoAjax:true,Com_Cod:o.Com_Cod}, function(r) {
		if(r['success']===true){
            $.alert("¡Se Anulo Correctamente!");	
            grid.trigger("reloadGrid");	
		}else{
			$.alert(r['message']);
		}
    },'json')
        .fail(function(error) {
          $.alert("El Servidor ha fallado en responder!");
    }); 
}
function validaNumChequeExt(num) {
    $.getDataJson('', { verificaChequeExt: true, Che_Num:num,Ban_Cod:$('#BakCod').val(),Che_Cta:$('#PapCtd').val()}, (r) => {
        //resolve(r.numCheque);
        if($.isEmpty(r.numCheque))
            $("#estadoNumChe").removeClass("fa fa-close").addClass("fa fa-check").css("color", "green").attr('title','Numero Aceptado');
        else{
            $("#estadoNumChe").removeClass("fa fa-check").addClass("fa fa-close").css("color", "red").attr('title','El Numero <b>'+ num +'</b> ya Existe !');
            $('#cruceForm input[name="Che_Num"]').val('');
        }
    }, (err) => {
        reject(err);
    });    
}
function validaNumCheque(num) {        
    $.getDataJson('', { verificaCheque: true, Che_Num:num,Ban_Cod:$('#BanCod').val()}, (r) => {
        //resolve(r.numCheque);
        if($.isEmpty(r.numCheque))
            $("#estadoNumChe").removeClass("fa fa-close").addClass("fa fa-check").css("color", "green").attr('title','Numero Aceptado');
        else{
            $("#estadoNumChe").removeClass("fa fa-check").addClass("fa fa-close").css("color", "red").attr('title','El Numero <b>'+ num +'</b> ya Existe !');
            $('#cruceForm input[name="Che_Num"]').val('');
        }
    }, (err) => {
        reject(err);
    });    
}
function cambiarCuenta(row){
    $('#cruceForm input[name="Pld_Cod_Otr"]').val(row.Pld_Cod);
    $('#cruceForm input[name="Pld_Des_Otr"]').val(row.Pld_Des);
    $('#infPldCdc').html(row.Pld_Cdc);    
    $('#cuentasDialog').dialog('close');
}
function gridCuentasCruce() {
  $.createSearchDialog('cuentasDialog',[
    {label: 'Cod. Int.', name: 'Pld_Cod', width: 20, align: "left"},
    {label: 'C&oacute;digo', name: 'Pld_Cdc', width: 50, align: "left"},
    {label: 'Cuenta Contable', name: 'Pld_Des', width: 100, align: "left"},
    { label:'&nbsp;', name: 'plsel', width: 15, align: 'center',viewable: false,title:false,
      formatter:function (cellvalue, options, rowObject) {
        return $.getGridButton(cambiarCuenta, rowObject, 'Seleccionar cuenta', 'check','','success');
      }
    }
     ],null,null,null,null,{ title:'Cuenta', options:[{label:'&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;',value:'d'},{label:'&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;',value:'c'}] })
     .find('.form-group-options').append('<div class="col-md-4"> <label class="control-label label-xs">Plan de Cuentas:</label><input name="Periodo" type="text" size="6" readonly style="text-align: center;display: inline-block;width: auto;" class="form-control input-xs" /> <input name="Pec_Cod" type="hidden" /><input name="Asi_Cod" type="hidden" /></div>');
}
function gridCruce() {
    $('#crucesGrid').createGrid({
        viewrecords: false,
        caption: "<center>Anticipos del Proveedor</center>",
        data: [], rowNum: 100,height: 130,width: 850,footerrow: true,responsive: false,totalCols:['Atp_Val','cruce','pendiente'],
        onSelectRow: function (rowid, e) { $(this).resetSelection(); },
        colModel: [
            //{ label: 'index', name: 'index', hidden: true, classes: 'bgNoRight' },
            { label: 'ID', key: true,name: 'Atp_Cod', width: 6 },
            { label: 'Diario', name: 'Com_Num', width: 10, align: "left" },
            { label: 'Fecha', name: 'Atp_Fec', width: 10, align: "center" },
            { label: 'Observ.', name: 'Atp_Obs', width: 15, align: "left" },            
            { label:'<i class="ui-icon ui-icon-circle-check"></i>', name: 'chkAnt',align:"center", width:4,formatter:'checkboxExa',formatoptions:{ dataEvents:{ Change:'setPagoCellAnt(this.dataset.rowId);'}}},
            //{ label: 'Obser.', name: 'Atp_Obs', width: 25, align: "left" },            
            { label: 'Saldo', name: 'Atp_Val', width: 10, align: 'right', formatter:'currency', decimalPlaces: '2', summaryRound: 2,formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "Total: {0}",summaryType: "sum"},
            { label: 'A Cruzar', name:'cruce',classes:'columnDisabled no_padding', width:10, align:"right", title:false, formatter:'textboxExa', formatoptions:{type:'decimal', decimals:8,attr: {disabled:'disabled'}, dataEvents:{ keyup:'updateRowItemAnt.call(this);'}} },                        
            { label: 'Pendiente', name: 'pendiente', width: 10, align: 'right', formatter:'currency', decimalPlaces: '2', summaryRound: 2,formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "Total: {0}",summaryType: "sum"}
        ]
    }, true, '', { view: false });
}
function setPagoCellAnt(row){
    let saldo = $('#crucesGrid').getCell(row,'Atp_Val');
    if($("#"+row+"_chkAnt").prop('checked')){
		$("#"+row+"_cruce").prop("disabled", false);
		$("#"+row+"_cruce").val(saldo.toNum());
		$('#crucesGrid').setCell(row,'pendiente','0.00');
	}else{
		$("#"+row+"_cruce").prop("disabled", true);
		$("#"+row+"_cruce").val("0.00");
		$('#crucesGrid').setCell(row,'pendiente',saldo);
	}
	let sum_cruce_ant= $('#crucesGrid').getGridSummary(['cruce']);
	let sum_pendi= $('#crucesGrid').getGridSummary(['pendiente'])
	$('#crucesGrid').jqGrid("footerData", "set", {cruce:""+sum_cruce_ant.cruce.toFixed(2),pendiente:sum_pendi.pendiente.toFixed(2)});
	$('#PapVal').val(sum_cruce_ant.cruce.toFixed(2));
}
function updateRowItemAnt(){
    let rowId=$(this).data('rowId');
    let saldo=$('#crucesGrid').getCell(rowId,'Atp_Val');  
  	let cruce_act=$('#crucesGrid').getCell(rowId,'cruce');
	
    if(cruce_act.toNum() >= saldo.toNum()){  
        $("#"+rowId+"_cruce").val( $.toFixed(saldo,2)); 
        $('#crucesGrid').setCell(rowId,'pendiente','0.00');
        //$('#'+rowId+'_chk').prop("checked", false).trigger("onchange");
    }else 
		$('#crucesGrid').setCell(rowId,'pendiente',$.toFixed(saldo.toNum() - cruce_act.toNum(),2));
	let sum_cruce_ant= $('#crucesGrid').getGridSummary(['cruce']);
	let sum_pendi= $('#crucesGrid').getGridSummary(['pendiente'])
	$('#crucesGrid').jqGrid("footerData", "set", {cruce:""+sum_cruce_ant.cruce.toFixed(2),pendiente:sum_pendi.pendiente.toFixed(2)});
	$('#PapVal').val(sum_cruce_ant.cruce.toFixed(2));
}
function editaConsumo(data){  
    vaciarGridCruce();
    $("input[name='Prv_Cod_Pagos']").val(data.prvCod);
    $("input[name='Cli_Cod_Pagos']").val(data.Cli_Cod);
    $("input[name='Prs_Cod_Pagos']").val(data.Prs_Cod);
    $("input[name='Com_Cod']").val(data.Com_Cod);
    $("input[name='Prs_Nom_Pagos']").val(data.nombre);
    $('#PrsCed').html(data.Prs_Ced); 
    $('#PapCtd').val(data.Pap_Ctd); 
    $('#PapVal').val(data.Com_Val); 
    $('#PapObs').val(data.Pap_Obs); 
    $('#Com_Fec_Old').val(data.Com_Fec); 
    $("#PagCod").val(data.Pag_Cod).trigger('change');
    $("input[name='Com_Fec']").val(data.Com_Fec);
    //$('#Com_Cod').val(data.Com_Cod); 
    //$('#Prs_Nom_Pagos').val(data.Prs_Nom); 
    //$('#PagCod').val(data.Pag_Cod); 
    //$('#Com_Fec').val(data.Com_Fec); 
    
    $.get('', { getDetalleConsumo: true, Asi_Cod:data.Asi_Cod,Com_Cod:data.Com_Cod,Prv_Cod:data.prvCod}, (r) => {        
        if(r.che){
            $("#CheNum").val(r.che.Che_Num);
            $("#CheFec").val(r.che.Che_Fec);
            $("#estadoNumChe").removeClass("fa fa-close").addClass("fa fa-check").css("color", "green").attr('title','Numero Aceptado');
        }
        $('#crucesGrid').setRows(r.det);
        $.each(r.det,function(i,v){ if(v.cruce*1>0)$('#'+v.Atp_Cod+'_cruce').prop('disabled',false)});
        return false;
                    
    },'json')
    .fail(function(error) {
        console.log("El Servidor ha fallado en responder!");
    });
    $('#cruceDialog').dialog('open');  

}
function preSaveConsumo(){
	let data = $('#cruceForm').getData();
	data.Pld_Cod_banco=$('#BanCod option:selected').attr('data-pld');
	data.Pld_Des_banco=$('#BanCod option:selected').attr('data-des');
    data.Pap_Cto=$('#BanCod option:selected').attr('data-cta');
	data.Bak_Des_banco=$('#BakCod option:selected').attr('data-des');
    data.BakCod=$('#BakCod').val();
	data.Bak_Cta_banco=$('#Pem_Cba').val(); 
	data.tipo=$('#PagCod option:selected').attr('data-abr');
	data.anticipo=$.map($('#crucesGrid').getGridBatch(o=>o.chkAnt==='S'),o=>[{Atp_Cod:o.Atp_Cod,Acl_Cru:o.cruce}]);
	data.saveConsumoAjax=true;
	console.log(data);
	$.createDialogConfirm('Est&aacute; seguro que desea guardar los datos?',data,saveConsumo);
}
function saveConsumo(data){
	$.saveDataJson('',data,function(r){
        vaciarGridCruce();
        $('#cruceDialog').dialog('close');  		
		if($.ifEmpty(r.link))
            window.open(r.link);
    },function(r){
        console.log(r);
    });
}
function vaciarGridCruce(){
     $('#PapObs').val('');    
    $('#CheNum').val('');
    $('#PapCtd').val('');
    $('#PapVal').val('');
    $('#cheCli').val('');  		
    $('#Pld_Cod_Otr').val('');  
    $('#Pld_Des_Otr').val('');
    $('#infPldCdc').html(''); 
    $("input[name='Com_Cod']").val('');
    $('#Prv_Cod_Pagos').val('');
    $('#Com_Fec_Old').val('');
    
    
    $('#Prs_Nom_Pagos').val(''); 
    $('#PrsCed').html('');         
    $('#crucesGrid').clearGrid(true);
    $("#estadoNumChe").removeClass("fa fa-close").removeClass("fa fa-check")
}
/********************* fin *******************/


function verificaChequeEstado() {
    let ids = verficaDataInGrid();
    if ($.varValid(ids)) {
        for (let j = 0, z = ids.length; j < z; j++) {
            let getRowData = $('#pagos').jqGrid('getRowData', ids[j]);
            if (getRowData['grid_tipp'] !== 'inicial' && getRowData['Che_Est'] !== 'A') {
                //console.log(getRowData);
                $("#" + getRowData['index'] + "_Haber").attr("readonly", "");
            }
        }
    }
}

function cellColors() {
    let $g = $('#searchGrid');
    let ids = $g.jqGrid('getDataIDs');
    let rawRows = $g.jqGrid('getGridParam', 'data') || [];
    let rawById = {};
    for (let k = 0; k < rawRows.length; k++) {
        let rr = rawRows[k];
        let rid = rr.row_id || rr.id || rr.ROW_ID;
        if (rid) {
            rawById[String(rid)] = rr;
        }
    }
    if ($.varValid(ids)) {
        for (let i = 0, z = ids.length; i < z; i++) {
            let rowid = ids[i];
            let raw = rawById[String(rowid)] || {};
            let getRowData = $g.jqGrid('getRowData', rowid) || {};
            let tipo = getRowData['Tipo_Linea'] || getRowData['tipo_linea'] || raw.Tipo_Linea || '';
            let est = atpEstKardexRow(raw) || String(getRowData['Atp_Est'] || '').trim().toUpperCase();
            let $tr = $g.find('tr.jqgrow#' + $.jgrid.jqID(String(rowid)));
            let $td = $tr.children('td').not('.jqgrid-rownum');
            $tr.removeClass('row-anulado-anticipo');
            $td.removeClass('cellGreen2 cellGray cellBlue2 cellRed2');
            if (tipo === 'Saldo inicial') {
                $td.addClass('cellGray');
            } else if (tipo === 'Consumo' && est === 'I') {
                $tr.addClass('row-anulado-anticipo');
                $td.addClass('cellRed2');
            } else if (tipo === 'Consumo') {
                $td.addClass('cellBlue2');
            } else if (tipo === 'Anticipo') {
                if (est === 'I') {
                    $tr.addClass('row-anulado-anticipo');
                    $td.addClass('cellRed2');
                } else if (est === 'U') {
                    $td.addClass('cellGreen2');
                } else if (est === 'C') {
                    $td.addClass('cellGray');
                }
            }
        }
    }
}

/*
function cambioPreiodoSearch(parm_peri) {
   // $("#txt_fec_ini").dateLimits($("#Pec_Cod option:selected").attr("data-inicio"), $("#Pec_Cod option:selected").attr("data-fin"));
   // $("#txt_fec_fin").dateLimits($("#Pec_Cod option:selected").attr("data-inicio"), $("#Pec_Cod option:selected").attr("data-fin"));
    $("#txt_fec_ini").val($("#Pec_Cod option:selected").attr("data-inicio"));
    $("#txt_fec_fin").val($("#Pec_Cod option:selected").attr("data-fin"));
}*/
// funcional 04-02-2025
// function cambioPreiodoSearch(parm_peri) {
//     var selectedOption = $("#Pec_Cod option:selected");
//     var inicio = selectedOption.attr("data-inicio");
//     var fin = selectedOption.attr("data-fin");
//     var value = selectedOption.val();

//     // Si el valor seleccionado es "T" (Todos), no tocar las fechas actuales
//     if (value === "T") {
//         // Permitir que el usuario ingrese cualquier fecha sin sobrescribir
//         $("#txt_fec_ini").datepicker("option", "minDate", null);
//         $("#txt_fec_ini").datepicker("option", "maxDate", null);
//         $("#txt_fec_fin").datepicker("option", "minDate", null);
//         $("#txt_fec_fin").datepicker("option", "maxDate", null);
//     } else {
//         // Si es un período específico, limitar las fechas al rango del período
//         $("#txt_fec_ini").val(inicio);
//         $("#txt_fec_fin").val(fin);

//         // Configurar límites de fecha basados en el período
//         $("#txt_fec_ini").datepicker("option", "minDate", new Date(inicio));
//         $("#txt_fec_ini").datepicker("option", "maxDate", new Date(fin));
//         $("#txt_fec_fin").datepicker("option", "minDate", new Date(inicio));
//         $("#txt_fec_fin").datepicker("option", "maxDate", new Date(fin));
//     }
// }

// Variables para almacenar las fechas seleccionadas
let selectedStartDate = null;
let selectedEndDate = null;

function toLocalDateFromYmd(ymd) {
    var s = String(ymd || '').trim();
    var m = s.match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (!m) return new Date(s);
    return new Date(parseInt(m[1], 10), parseInt(m[2], 10) - 1, parseInt(m[3], 10));
}

function cambioPreiodoSearch(parm_peri) {
    var selectedOption = $("#Pec_Cod option:selected");
    var value = selectedOption.val();

    if (value === "T") {
        // Caso "Todos": usar rango calendario completo de los periodos listados.
        const years = [];
        $("#Pec_Cod option").each(function () {
            const $option = $(this);
            const v = String($option.val() || "");
            if (v !== "T" && v !== "Corte") {
                const y = parseInt($option.data("year"), 10);
                if (!isNaN(y)) years.push(y);
            }
        });
        const minYear = years.length ? Math.min.apply(null, years) : new Date().getFullYear();
        const maxYear = years.length ? Math.max.apply(null, years) : new Date().getFullYear();
        const inicio = minYear + "-01-01";
        const fin = maxYear + "-12-31";

        $("#txt_fec_ini").datepicker("setDate", toLocalDateFromYmd(inicio));
        $("#txt_fec_fin").datepicker("setDate", toLocalDateFromYmd(fin));

        $("#txt_fec_ini, #txt_fec_fin").datepicker("option", {
            minDate: toLocalDateFromYmd(inicio),
            maxDate: toLocalDateFromYmd(fin)
        });

        // Resetear las fechas seleccionadas
        selectedStartDate = null;
        selectedEndDate = null;
    } else if (value === "Corte") {
        // Extraer años solo de los periodos reales (excluyendo 'Todos' y 'Corte')
        const years = [];
        $("#Pec_Cod option").each(function () {
            const $option = $(this);
            // Excluir opciones no numéricas
            if ($option.val() !== "T" && $option.val() !== "Corte") {
                const year = parseInt($option.data("year"));
                if (!isNaN(year)) years.push(year);
            }
        });

        const minYear = years.length > 0 ? Math.min(...years) : new Date().getFullYear();

        // Establecer la fecha de inicio al año mínimo
        if (!selectedStartDate) {
            selectedStartDate = `${minYear}-01-01`;
        }
        if (!selectedEndDate) {
            selectedEndDate = new Date(); // Fecha actual
        }

        $("#txt_fec_ini").datepicker("setDate", toLocalDateFromYmd(selectedStartDate));
        $("#txt_fec_fin").datepicker("setDate", toLocalDateFromYmd(selectedEndDate));

        $("#txt_fec_ini, #txt_fec_fin").datepicker("option", {
            minDate: new Date(minYear, 0, 1),
            maxDate: new Date()
        });
    } else {
        // Caso períodos normales: iniciar siempre el 01-01 del año del periodo.
        var fin = selectedOption.data('fin');
        var year = parseInt(selectedOption.data('year'), 10);
        var inicio = selectedOption.data('inicio');
        var inicioPeriodo = (!isNaN(year) ? (year + '-01-01') : inicio);

        $("#txt_fec_ini").datepicker("setDate", toLocalDateFromYmd(inicioPeriodo));
        $("#txt_fec_fin").datepicker("setDate", toLocalDateFromYmd(fin));

        $("#txt_fec_ini, #txt_fec_fin").datepicker("option", {
            minDate: toLocalDateFromYmd(inicioPeriodo),
            maxDate: toLocalDateFromYmd(fin)
        });

        // Resetear las fechas seleccionadas
        selectedStartDate = null;
        selectedEndDate = null;
    }
}

// Evento para cambiar las fechas
$("#txt_fec_ini").change(function () {
    selectedStartDate = $(this).val(); // Actualiza la fecha de inicio seleccionada
});

$("#txt_fec_fin").change(function () {
    selectedEndDate = $(this).val(); // Actualiza la fecha de fin seleccionada
});

function changePeriodo() {
    $('#Pec_Cod').off('change.antPrvPeriodo').on('change.antPrvPeriodo', function () {
        cambioPreiodoSearch('periodo');
        $('#txt_fec_ini').trigger('change');
        $('#txt_fec_fin').trigger('change');
    }).trigger('change');
}

function formatMoney(number, places, symbol, thousand, decimal) {
    number = number || 0;
    places = !isNaN(places = Math.abs(places)) ? places : 2;
    symbol = symbol !== undefined ? symbol : "$";
    thousand = thousand || ",";
    decimal = decimal || ".";
    var negative = number < 0 ? "-" : "",
        i = parseInt(number = Math.abs(+number || 0).toFixed(places), 10) + "",
        j = (j = i.length) > 3 ? j % 3 : 0;
    return symbol + negative + (j ? i.substr(0, j) + thousand : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousand) + (places ? decimal + Math.abs(number - i).toFixed(places).slice(2) : "");
}

function calculateValFooter() {
    let rawRows = $('#searchGrid').jqGrid('getGridParam', 'data') || [];
    let ids = $('#searchGrid').jqGrid('getDataIDs');
    let sumT = 0;
    let sumC = 0;
    let lastSaldo = 0;
    if (rawRows.length > 0) {
        for (let i = 0; i < rawRows.length; i++) {
            let regRaw = rawRows[i] || {};
            let t = parseFloat(regRaw['TOTAL'] || regRaw['total'] || 0) || 0;
            let c = parseFloat(regRaw['CONSUMO'] || regRaw['consumo'] || 0) || 0;
            let s = parseFloat(regRaw['tot_anti'] || regRaw['TOT_ANTI'] || 0) || 0;
            if (atpEstKardexRow(regRaw) !== 'I') {
                sumT += t;
                sumC += c;
            }
            lastSaldo = s;
        }
    } else {
        for (let i = 0; i < ids.length; i++) {
            let reg = $('#searchGrid').jqGrid('getRowData', ids[i]);
            let t = parseFloat(String(reg['TOTAL'] || '0').replace(/[^0-9.-]/g, '')) || 0;
            let c = parseFloat(String(reg['CONSUMO'] || '0').replace(/[^0-9.-]/g, '')) || 0;
            let s = parseFloat(String(reg['tot_anti'] || '0').replace(/[^0-9.-]/g, '')) || 0;
            if (String(reg['Atp_Est'] || '').trim().toUpperCase() !== 'I') {
                sumT += t;
                sumC += c;
            }
            lastSaldo = s;
        }
    }
    $('#searchGrid').jqGrid('footerData', 'set', {
        Tipo_Linea: "<div style='text-align:right;'>TOTALES:</div>",
        TOTAL: sumT.toFixed(2),
        CONSUMO: sumC.toFixed(2),
        tot_anti: lastSaldo.toFixed(2)
    }, true);
}

function calCheFooter() {
    //showPagosChe
    let ids = $('#showPagosChe').jqGrid('getDataIDs');
    var valor = 0;
    //console.log(ids.length);
    for (let j = 0; j < ids.length; j++) {
        let reg_pagoC = $('#showPagosChe').jqGrid('getRowData', ids[j]);
        //console.log(reg_pagoC);
        valor = valor + (reg_pagoC['Che_Val'] * 1);
    }
    $('#showPagosChe').jqGrid('footerData', 'set', { Che_Obs: "<div style='text-align:right;'>TOTALES:</div>" });
    $('#showPagosChe').jqGrid('footerData', 'set', { Che_Val: "" + valor });
}

function setColorGrid() {
    let ids = $('#showPagosChe').jqGrid('getDataIDs');
    for (let j = 0; j < ids.length; j++) {
        let getRow = $('#showPagosChe').jqGrid('getRowData', ids[j]);
        if (getRow['Che_Est'] === 'P') {
            $('#showPagosChe').find("tr#" + (j + 1) + ' td:not(.jqgrid-rownum)').addClass('cellRed2');
        }
    }
}

function calSumFooter() {
    let ids = $('#showPagosAsi').jqGrid('getDataIDs');
    var valorDebe = 0,
        valorHaber = 0;
    for (let i = 0; i < ids.length; i++) {
        let reg_pago = $('#showPagosAsi').jqGrid('getRowData', ids[i]);
        //console.log(reg_pago);
        valorDebe = valorDebe + (reg_pago['Debe'] * 1);
        valorHaber = valorHaber + (reg_pago['Haber'] * 1);
    }
    $('#showPagosAsi').jqGrid('footerData', 'set', { Asi_Glo: "<div style='text-align:right;'>TOTALES:</div>" });
    $('#showPagosAsi').jqGrid('footerData', 'set', { Debe: "" + valorDebe });
    $('#showPagosAsi').jqGrid('footerData', 'set', { Haber: "" + valorHaber });

}

function calFooterSubGrid() {
    let ids = $('#showSubGridAsi').jqGrid('getDataIDs');
    var valorDebe = 0,
        valorHaber = 0;
    for (let i = 0; i < ids.length; i++) {
        let reg_pago = $('#showSubGridAsi').jqGrid('getRowData', ids[i]);
        //console.log(reg_pago);
        valorDebe = valorDebe + (reg_pago['Debe'] * 1);
        valorHaber = valorHaber + (reg_pago['Haber'] * 1);
    }
    $('#showSubGridAsi').jqGrid('footerData', 'set', { Asi_Glo: "<div style='text-align:right;'>TOTALES:</div>" });
    $('#showSubGridAsi').jqGrid('footerData', 'set', { Debe: "" + valorDebe });
    $('#showSubGridAsi').jqGrid('footerData', 'set', { Haber: "" + valorHaber });
}

function calFooterSubAntConsumidos(dataRows) {
    let rows = $.isArray(dataRows) ? dataRows : [];
    let saldoFinalHoy = 0;
    let totalValorAnticipo = 0;
    let totalConsumido = 0;
    for (let i = 0; i < rows.length; i++) {
        let va = parseFloat(rows[i]['valor_anticipo'] || rows[i]['VALOR_ANTICIPO'] || 0);
        let vc = parseFloat(rows[i]['valor_consumido'] || rows[i]['VALOR_CONSUMIDO'] || 0);
        let sf = parseFloat(rows[i]['saldo_final_hoy'] || rows[i]['SALDO_FINAL_HOY'] || 0);
        if (!isFinite(va)) va = 0;
        if (!isFinite(vc)) vc = 0;
        if (!isFinite(sf)) sf = 0;
        totalValorAnticipo += va;
        totalConsumido += vc;
        saldoFinalHoy = sf;
    }
    $('#showSubAntConsumidos').jqGrid('footerData', 'set', {
        asiento_anticipo: "<div style='text-align:right;'>TOTALES:</div>",
        valor_anticipo: totalValorAnticipo.toFixed(2),
        consumo_mov: totalConsumido.toFixed(2)
    }, true);
    $('#subAntConsumoSaldoFinalVal').text(formatMoney(saldoFinalHoy));
}

function dacCodDesdeRowIdConsumo(row) {
    if (!row) return '';
    let rid = String(row.row_id || row.ROW_ID || row.rowId || '');
    let m = rid.match(/^C(\d+)$/i);
    if (m) return m[1];
    let dc = row.Dac_Cod || row.DAC_COD;
    if (dc != null && String(dc) !== '') return String(dc);
    return '';
}

function resaltarAnticipoConsumidoSubGrid(atpCodSeleccionado, dacCodSeleccionado) {
    let ids = $('#showSubAntConsumidos').jqGrid('getDataIDs');
    let objetivoAtp = String(atpCodSeleccionado || '');
    let objetivoDac = String(dacCodSeleccionado || '');
    for (let i = 0; i < ids.length; i++) {
        let rowId = ids[i];
        let row = $('#showSubAntConsumidos').jqGrid('getRowData', rowId) || {};
        let atp = String(row['Atp_Cod'] || '');
        let dac = String(row['Dac_Cod'] || row['DAC_COD'] || '');
        let $tds = $('#showSubAntConsumidos').find("tr#" + rowId + " td:not(.jqgrid-rownum)");
        $tds.css('background-color', '');
        let ok = false;
        if (objetivoDac !== '' && dac !== '' && dac === objetivoDac) {
            ok = true;
        } else if (objetivoDac === '' && objetivoAtp !== '' && atp === objetivoAtp) {
            ok = true;
        }
        if (ok) {
            $tds.css('background-color', '#fff3a6');
        }
    }
}



function verMovimiento(params) {
    consumoDetalleActual = (params && params[0]) ? params[0] : null;
    $("#sub_prov_show").val(params[0].nombre || '');
    $("#sub_ruc_show").val(params[0].Prs_Ced || '');
    $("#sub_compr_show").val(params[0].codigoCompra || '');
    $("#sub_fec_show").val(params[0].Fecha_Mov || params[0].Atp_Fec || '');
    $("#sub_obs_show").val(params[0].Glosa || params[0].Atp_Obs || '');
    $("#sub_usuario_show").html((params[0].usuario || '') + (params[0].usuario ? ' -' : ''));
    $("#sub_com_sys_show").html(params[0].Com_Sys || '');
    $("#sub_ant_detasi").show();
    $("#sub_ant_detcons").hide();
    $("#sub_ant_detasi").children("a").trigger("click");
    $('#showSubGridAsi').clearGrid(true);
    $('#showSubAntConsumidos').clearGrid(true);
    const getDataByDet = async () => {
        arrayDetAsiento.length = 0;
        arrayDetAsiento = await asientoSubGridAsync(params[0]);
    }
    const getAntConsumidos = async () => {
        arraySubAntConsumidos.length = 0;
        arraySubAntConsumidos = await anticiposConsumidosAsync(params[0]);
    }
    getDataByDet().then(() => {
        $('#showSubGridAsi').setRows(arrayDetAsiento);
        calFooterSubGrid();
    });
    getAntConsumidos().then(() => {
        if (arraySubAntConsumidos.length === 0) {
            $("#sub_ant_detcons").hide();
            $('#subAntConsumoSaldoFinalVal').text(formatMoney(0));
        } else {
            $("#sub_ant_detcons").show();
            arraySubAntConsumidos = $.map(arraySubAntConsumidos, function (r) {
                return $.extend({}, r, {
                    consumo_mov: r.valor_consumido
                });
            });
            $('#showSubAntConsumidos').setRows(arraySubAntConsumidos);
            calFooterSubAntConsumidos(arraySubAntConsumidos);
            resaltarAnticipoConsumidoSubGrid(params[0].Atp_Cod, dacCodDesdeRowIdConsumo(params[0]));
        }
        $('#tabs_sub_ant_det').tabs('refresh');
        var $tabsSub = $('#tabs_sub_ant_det');
        var idxSub = $tabsSub.tabs('option', 'active');
        var $activeLiSub = $tabsSub.find('.ui-tabs-nav li').eq(idxSub);
        if ($activeLiSub.length && !$activeLiSub.is(':visible')) {
            $tabsSub.find('.ui-tabs-nav li:visible:first a').trigger('click');
        }
    });

    $('#verAsientoDialogMod').dialog('open');
}

function verAnticipo(params) {
    anticipoDetalleActual = (params && params[0]) ? params[0] : null;
    $("#showPagosAsi").updateGridsSizes();
    $("#showPagosChe").updateGridsSizes();
    $("#showAntConsumos").updateGridsSizes();

    $("#ant_detasi").children("a").trigger("click");
    $("#ant_detche").hide();
    $("#ant_detcons").hide();

    $('#showPagosAsi').clearGrid(true);
    $('#showPagosChe').clearGrid(true);
    $('#showAntConsumos').clearGrid(true);
    $('#showPagosAsi').trigger("reloadGrid");
    $('#showPagosChe').trigger("reloadGrid");
    $('#showAntConsumos').trigger("reloadGrid");

    $("#prov_show").val(params[0].nombre);
    $("#ruc_show").val(params[0].Prs_Ced);
    $("#compr_show").val(params[0].codigoCompra);
    $("#fec_show").val(params[0].Atp_Fec);
    $("#obs_show").val(params[0].Atp_Obs);
    $("#usuario").html(params[0].usuario + '-');
    $("#Com_Sys").html(params[0].Com_Sys);

    const getDataAsiento = async () => {
        arrayAsiento.length = 0;
        arrayAsiento = await asientoAsync(params[0]);
    };
    const getDataCheque = async () => {
        arrayCheques.length = 0;
        arrayCheques = await chequesAsync(params[0]);
    };
    const getDataConsumos = async () => {
        arrayConsumos.length = 0;
        arrayConsumos = await consumosAnticipoAsync(params[0]);
    };

    // Abrir diálogo de inmediato, pero resolver visibilidad de tabs en un solo paso.
    $('#verPagosDialogMod').dialog('open');

    Promise.allSettled([getDataAsiento(), getDataCheque(), getDataConsumos()]).then(function (results) {
        if (results[0].status !== 'fulfilled') {
            arrayAsiento = [];
        }
        if (results[1].status !== 'fulfilled') {
            arrayCheques = [];
        }
        if (results[2].status !== 'fulfilled') {
            arrayConsumos = [];
        }

        $('#showPagosAsi').setRows(arrayAsiento);
        calSumFooter();

        if (arrayCheques.length > 0) {
            $("#ant_detche").show();
            $('#showPagosChe').setRows(arrayCheques);
            calCheFooter();
            setColorGrid();
        } else {
            $("#ant_detche").hide();
        }

        let acumuladoConsumo = 0;
        arrayConsumos = $.map(arrayConsumos, function (row) {
            let valorAnticipo = parseFloat(row.valor_anticipo || 0) || 0;
            let valorConsumo = parseFloat(row.valor_consumo || 0) || 0;
            acumuladoConsumo += valorConsumo;
            return $.extend({}, row, {
                saldo_linea: (valorAnticipo - acumuladoConsumo).toFixed(4)
            });
        });
        if (arrayConsumos.length > 0) {
            $("#ant_detcons").show();
            $('#showAntConsumos').setRows(arrayConsumos);
        } else {
            $("#ant_detcons").hide();
        }

        $('#tabs_ant_det').tabs('refresh');
        var $tabs = $('#tabs_ant_det');
        var idx = $tabs.tabs('option', 'active');
        var $activeLi = $tabs.find('.ui-tabs-nav li').eq(idx);
        if ($activeLi.length && !$activeLi.is(':visible')) {
            $tabs.find('.ui-tabs-nav li:visible:first a').trigger('click');
        }
    });

    $('div#verPagosDialog').siblings('.ui-dialog-titlebar').children('span').text(params[0].nombre);
    //console.log(params);
}

function imprimirAnticipoActual() {
    if (!anticipoDetalleActual) {
        $.alert('No hay un anticipo seleccionado para imprimir.');
        return;
    }
    imprimirDetalleAnticipoCompleto();
}

function imprimirConsumoActual() {
    if (!consumoDetalleActual) {
        $.alert('No hay un consumo seleccionado para imprimir.');
        return;
    }
    imprimirDetalleConsumoCompleto();
}

async function imprimirDetalleConsumoCompleto() {
    if (!consumoDetalleActual) return;
    var row = consumoDetalleActual;
    var esc = function (v) {
        return String(v == null ? '' : v)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    };
    var money = function (v) {
        var n = parseFloat(v || 0);
        if (!isFinite(n)) n = 0;
        return formatMoney(n);
    };
    var dacObj = dacCodDesdeRowIdConsumo(row);

    var asientos = [];
    var consumidos = [];
    try {
        asientos = await asientoSubGridAsync(row);
    } catch (e) {
        asientos = [];
    }
    try {
        consumidos = await anticiposConsumidosAsync(row);
    } catch (e) {
        consumidos = [];
    }

    var html = '';
    html += '<h3 style="margin:0 0 8px 0;">Detalle del consumo</h3>';
    html += '<table style="width:100%;border-collapse:collapse;font-size:12px;margin-bottom:12px;">';
    html += '<tr><td><b>Proveedor:</b> ' + esc(row.nombre || '') + '</td><td><b>Cédula/RUC:</b> ' + esc(row.Prs_Ced || '') + '</td></tr>';
    html += '<tr><td><b>No. Compr.:</b> ' + esc(row.codigoCompra || '') + '</td><td><b>Fecha:</b> ' + esc(row.Fecha_Mov || row.Atp_Fec || '') + '</td></tr>';
    html += '<tr><td colspan="2"><b>Observación:</b> ' + esc(row.Glosa || row.Atp_Obs || '') + '</td></tr>';
    html += '</table>';

    if ($.isArray(asientos) && asientos.length) {
        html += '<h4 style="margin:12px 0 6px 0;">Asiento</h4>';
        html += '<table style="width:100%;border-collapse:collapse;font-size:12px;" border="1" cellpadding="4">';
        html += '<tr><th>Código</th><th>Cuenta</th><th>Glosa</th><th>Debe</th><th>Haber</th></tr>';
        $.each(asientos, function (_, r) {
            html += '<tr><td>' + esc(r.Pld_Cdc || '') + '</td><td>' + esc(r.Pld_Des || '') + '</td><td>' + esc(r.Asi_Glo || '') + '</td><td style="text-align:right;">' + money(r.Debe) + '</td><td style="text-align:right;">' + money(r.Haber) + '</td></tr>';
        });
        html += '</table>';
    }

    if ($.isArray(consumidos) && consumidos.length) {
        let totalVa = 0;
        let totalVc = 0;
        let saldoHoy = 0;
        $.each(consumidos, function (_, r) {
            let va = parseFloat(r.valor_anticipo || r.VALOR_ANTICIPO || 0) || 0;
            let vc = parseFloat(r.valor_consumido || r.VALOR_CONSUMIDO || 0) || 0;
            let sf = parseFloat(r.saldo_final_hoy || r.SALDO_FINAL_HOY || 0) || 0;
            totalVa += va;
            totalVc += vc;
            saldoHoy = sf;
        });
        html += '<h4 style="margin:12px 0 6px 0;">Anticipos consumidos</h4>';
        html += '<table style="width:100%;border-collapse:collapse;font-size:12px;" border="1" cellpadding="4">';
        html += '<tr><th>ID Anticipo</th><th>Asiento Anticipo</th><th>Valor Anticipo</th><th>Consumo</th><th>Saldo (momento)</th><th>Saldo final (hoy)</th></tr>';
        $.each(consumidos, function (_, r) {
            let bg = '';
            let dacR = String(r.Dac_Cod || r.DAC_COD || '');
            if (dacObj !== '' && dacR !== '' && dacR === String(dacObj)) {
                bg = ' style="background:#fff3a6;"';
            }
            html += '<tr' + bg + '><td>' + esc(r.Atp_Cod || '') + '</td><td>' + esc(r.asiento_anticipo || '') + '</td><td style="text-align:right;">' + money(r.valor_anticipo) + '</td><td style="text-align:right;">' + money(r.valor_consumido) + '</td><td style="text-align:right;">' + money(r.saldo_momento) + '</td><td style="text-align:right;">' + money(r.saldo_final_hoy) + '</td></tr>';
        });
        html += '<tr><td colspan="2" style="text-align:right;"><b>TOTALES</b></td><td style="text-align:right;"><b>' + money(totalVa) + '</b></td><td style="text-align:right;"><b>' + money(totalVc) + '</b></td><td></td><td style="text-align:right;"><b>' + money(saldoHoy) + '</b></td></tr>';
        html += '</table>';
    }

    var w = window.open('', '_blank');
    if (!w) {
        $.alert('El navegador bloqueó la ventana de impresión.');
        return;
    }
    w.document.write('<html><head><title>Impresión detalle consumo</title></head><body style="font-family:Arial,sans-serif;padding:12px;">' + html + '</body></html>');
    w.document.close();
    w.focus();
    w.print();
}

async function imprimirDetalleAnticipoCompleto() {
    if (!anticipoDetalleActual) return;
    var esc = function (v) {
        return String(v == null ? '' : v)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    };
    var money = function (v) {
        var n = parseFloat(v || 0);
        if (!isFinite(n)) n = 0;
        return formatMoney(n);
    };

    var row = anticipoDetalleActual;
    var asientos = [];
    var cheques = [];
    var consumos = [];
    try {
        asientos = await asientoAsync(row);
    } catch (e) {
        asientos = [];
    }
    try {
        cheques = await chequesAsync(row);
    } catch (e) {
        cheques = [];
    }
    try {
        consumos = await consumosAnticipoAsync(row);
    } catch (e) {
        consumos = [];
    }
    if ($.isArray(consumos) && consumos.length) {
        let acumuladoConsumo = 0;
        consumos = $.map(consumos, function (r) {
            let valorAnticipo = parseFloat(r.valor_anticipo || 0) || 0;
            let valorConsumo = parseFloat(r.valor_consumo || 0) || 0;
            acumuladoConsumo += valorConsumo;
            return $.extend({}, r, {
                saldo_linea: (valorAnticipo - acumuladoConsumo).toFixed(4)
            });
        });
    }

    var html = '';
    html += '<h3 style="margin:0 0 8px 0;">Detalle de anticipo</h3>';
    html += '<table style="width:100%;border-collapse:collapse;font-size:12px;margin-bottom:12px;">';
    html += '<tr><td><b>Proveedor:</b> ' + esc(row.nombre || '') + '</td><td><b>Cédula/RUC:</b> ' + esc(row.Prs_Ced || '') + '</td></tr>';
    html += '<tr><td><b>No. Compr.:</b> ' + esc(row.codigoCompra || '') + '</td><td><b>Fecha:</b> ' + esc(row.Atp_Fec || '') + '</td></tr>';
    html += '<tr><td colspan="2"><b>Observación:</b> ' + esc(row.Atp_Obs || '') + '</td></tr>';
    html += '</table>';

    if ($.isArray(asientos) && asientos.length) {
        html += '<h4 style="margin:12px 0 6px 0;">Asientos</h4>';
        html += '<table style="width:100%;border-collapse:collapse;font-size:12px;" border="1" cellpadding="4">';
        html += '<tr><th>Código</th><th>Cuenta</th><th>Glosa</th><th>Debe</th><th>Haber</th></tr>';
        $.each(asientos, function (_, r) {
            html += '<tr><td>' + esc(r.Pld_Cdc || '') + '</td><td>' + esc(r.Pld_Des || '') + '</td><td>' + esc(r.Asi_Glo || '') + '</td><td style="text-align:right;">' + money(r.Debe) + '</td><td style="text-align:right;">' + money(r.Haber) + '</td></tr>';
        });
        html += '</table>';
    }

    if ($.isArray(consumos) && consumos.length) {
        html += '<h4 style="margin:12px 0 6px 0;">Consumos</h4>';
        html += '<table style="width:100%;border-collapse:collapse;font-size:12px;" border="1" cellpadding="4">';
        html += '<tr><th>No. Compr.</th><th>Fecha</th><th>Concepto</th><th>Valor Anticipo</th><th>Consumido</th><th>Saldo</th></tr>';
        $.each(consumos, function (_, r) {
            html += '<tr><td>' + esc(r.codigo_consumo || '') + '</td><td>' + esc(r.fecha_consumo || '') + '</td><td>' + esc(r.glosa_consumo || '') + '</td><td style="text-align:right;">' + money(r.valor_anticipo) + '</td><td style="text-align:right;">' + money(r.valor_consumo) + '</td><td style="text-align:right;">' + money(r.saldo_linea || r.saldo_anticipo) + '</td></tr>';
        });
        html += '</table>';
    }

    if ($.isArray(cheques) && cheques.length) {
        html += '<h4 style="margin:12px 0 6px 0;">Cheques</h4>';
        html += '<table style="width:100%;border-collapse:collapse;font-size:12px;" border="1" cellpadding="4">';
        html += '<tr><th>No. Cheque</th><th>Fecha</th><th>Observación</th><th>Valor</th><th>Estado</th></tr>';
        $.each(cheques, function (_, r) {
            html += '<tr><td>' + esc(r.Che_Num || '') + '</td><td>' + esc(r.Che_Fec || '') + '</td><td>' + esc(r.Che_Obs || '') + '</td><td style="text-align:right;">' + money(r.Che_Val) + '</td><td>' + esc(r.estado || '') + '</td></tr>';
        });
        html += '</table>';
    }

    var w = window.open('', '_blank');
    if (!w) {
        $.alert('El navegador bloqueó la ventana de impresión.');
        return;
    }
    w.document.write('<html><head><title>Impresión detalle anticipo</title></head><body style="font-family:Arial,sans-serif;padding:12px;">' + html + '</body></html>');
    w.document.close();
    w.focus();
    w.print();
}

function modificarAnticipo(parm_mod) {
    perCodAct = 0;
    //console.log(parm_mod);
    //console.log(peridodo);
    arrayModAsiento.length = 0;
    const getDataAsiMod = async () => { arrayModAsiento = await asientoAsync(parm_mod[0]); }
    getDataAsiMod().then(() => {
        $('#anticipoPrvForm').setData(parm_mod[0]);
        $("#Tia_Cod_temp").val(parm_mod[0].Tia_Cod);
        let f_Act = $('#anticipoPrvForm').find('#Atp_Fec').val();
        peridodo.forEach(per => { if (f_Act > per.Pec_Fei && f_Act < per.Pec_Fef) { $("#Atp_Fec").dateLimits(per.Pec_Fei, per.Pec_Fef); } });
        moveToUpdate();
        //llenar negociacion en caso de existir
        llenarNego(parm_mod[0]['Atp_Cod']);
        llenarModAsient(arrayModAsiento);
        verificaChequeEstado();
        perCodAct = parm_mod[0]['Pec_Cod'];
        //console.log(perCodAct);
    });
}

function llenarModAsient(data) {
    // console.log(data);
    $('#pagos').clearGrid(true);
    $("#loader").show();
    let lengthDatos = data.length;
    var next = 0;
    let tipoData = '';
    data.forEach(respuesta => {
        //console.log(respuesta);
        if (respuesta['Asi_Deh'] === 'D') { tipoData = 'inicial'; } else { tipoData = 'pago'; }
        next = $("#pagos").jqGrid('getCol', 'index', false, 'max');
        next = (isNaN(next) ? 1 : next + 1);
        $("#pagos").jqGrid('addRowData', next, $.extend(respuesta, { index: next, grid_tipp: tipoData, Asi_Cod: respuesta['Asi_Cod'], Che_Cod: respuesta['Che_Cod'], Pap_Cod: respuesta['Pap_Cod'], Pag_Cod: respuesta['Pag_Cod'], Pag_Abr: respuesta['Pag_Abr'], Pag_Des: respuesta['Pag_Des'], Pld_Des: respuesta['Pld_Des'], Pld_Cdc: respuesta['Pld_Cdc'], Pap_Ctd: respuesta['Pap_Ctd'], Ban_Cod: respuesta['Ban_Cod'], Che_Est: respuesta['Che_Est'], Che_Num: respuesta['Che_Num'], Che_Fec: respuesta['Che_Fec'], Pap_Cto: respuesta['Pap_Cto'], Pld_Cod: respuesta['Pld_Cod'], Det_Tip: respuesta['Det_Tip'], Glosa: respuesta['Glosa'], Debe: respuesta['Debe'], Haber: respuesta['Haber'] }), 'last');


        $("#pagos").find('#' + next + '_Haber').on('change', function () {
            if ($(this).val() > 0) {
                //console.log($(this).val());
                let tnmGrid = $('#pagos').getGridBatch();
                reCalculateHaber(tnmGrid.length);
                $("#_Haber").attr("readonly", "");
                $("#" + next + "_Haber").css('text-align', 'right');

                formaterFotter(next);
            } else {
                $("#" + next + "_Debe").css('text-align', 'right');
                $("#" + next + "_Haber").attr("readonly", "");
            }

        }).trigger('change');

    });
    formaterFotter(next);
    if (data.length > 0) { $("#loader").hide(); }
}

function reCalculateHaber(tmano) {
    let grid = $("#pagos"),
        valDebe = 0,
        inxD = 0,
        indxH = 0,
        valHaber = 0;
    let tnmGrid = grid.getGridBatch();
    if (tmano === tnmGrid.length) {
        tnmGrid.forEach(gridTam => {
            //console.log(gridTam);
            if ((gridTam['Debe'] * 1) > 0 || gridTam['grid_tipp'] === 'inicial') {
                valDebe += (gridTam['Debe'] * 1);
                inxD = (gridTam['index'] * 1);
            }
            if ((gridTam['Haber'] * 1) > 0 || gridTam['grid_tipp'] === 'pago') {
                valHaber += (gridTam['Haber'] * 1);
                indxH = (gridTam['index'] * 1);
            }
        });
        if (valDebe !== valHaber) { grid.find('#' + inxD + '_Debe').val((valHaber * 1).toFixed(4)); }

        grid.jqGrid('footerData', 'set', { Asi_Glo: "<div style='text-align:right;'>TOTALES:</div>" });
        grid.jqGrid('footerData', 'set', { Debe: "" + formatMoney((valHaber * 1).toFixed(4)) });
        grid.jqGrid('footerData', 'set', { Haber: "" + formatMoney((valHaber * 1).toFixed(4)) });
        $('#totalFinal').val('' + valHaber);
    }


}



function createGridShowAsiDetalle() {
    $('#showSubGridAsi').createGrid({
        viewrecords: false,
        caption: "<center>Detalle del anticipo</center>",
        data: [],
        rowNum: 100,
        height: 120,
        width: 650,
        footerrow: true,
        responsive: false,
        onSelectRow: function (rowid, e) { $(this).resetSelection(); },
        colModel: [
            { label: '', name: 'Pap_Est', hidden: true },
            { label: 'index', name: 'index', hidden: true, classes: 'bgNoRight' },
            { label: 'Codigo', name: 'Pld_Cdc', width: 10, align: "left" },
            { label: 'Cuenta', name: 'Pld_Des', width: 30, align: "left" },
            { label: 'Glosa', name: 'Asi_Glo', width: 25, align: "left" },
            { label: 'Debe', name: 'Debe', width: 10, align: 'right', formatter: 'currency', editable: true, formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '' } },
            { label: 'Haber', name: 'Haber', width: 10, align: 'right', formatter: 'currency', editable: true, formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '' } }
        ]
    }, true, '', { view: false });
}

function createPagosModGrid() {
    $('#pagos').createGrid({
        viewrecords: false,
        data: [],
        rowNum: 100,
        height: 150,
        footerrow: true,
        onSelectRow: function (rowid, e) { $(this).resetSelection(); },
        colModel: [
            { label: ' ', name: 'Det_Tip', hidden: true },
            { label: ' ', name: 'grid_tipp', hidden: true },
            { label: ' ', name: 'Che_Cod', hidden: true },
            { label: 'index', name: 'index', hidden: true, classes: 'bgNoRight' },
            { label: 'Asi_Cod', name: 'Asi_Cod', hidden: true, classes: 'bgNoRight' },
            { label: 'Pap_Cod', name: 'Pap_Cod', hidden: true, classes: 'bgNoRight' },
            { label: 'Pag_Cod', name: 'Pag_Cod', hidden: true, classes: 'bgNoRight' },
            { label: 'Pag_Abr', name: 'Pag_Abr', hidden: true, classes: 'bgNoRight' },
            { label: 'Tipo', name: 'Pag_Des', width: 10, align: "center", classes: 'bgNoRight', formatter: function (cellvalue, options, rowObject) { if (rowObject.Asi_Deh === 'D') { return "-"; } else { return rowObject.Pag_Des } } },
            { label: 'Ban_Cod', name: 'Ban_Cod', hidden: true, classes: 'bgNoRight' },
            { label: 'Che_Num', name: 'Che_Num', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Che_Est', hidden: true },
            { label: 'Che_Fec', name: 'Che_Fec', hidden: true, classes: 'bgNoRight' },
            { label: 'Pap_Cto', name: 'Pap_Cto', hidden: true, classes: 'bgNoRight' },
            { label: 'Pap_Ctd', name: 'Pap_Ctd', hidden: true, classes: 'bgNoRight' },
            { label: 'Cuenta_Pld', name: 'Pld_Cod', width: 30, hidden: true, classes: 'bgNoRight' },
            { label: 'C&oacute;digo', name: 'Pld_Cdc', width: 10, classes: 'bgNoRight' },
            { label: 'Cuenta', name: 'Pld_Des', width: 30, classes: 'bgNoRight' },
            { label: 'Glosa', name: 'Asi_Glo', width: 20, editable: true },
            { label: 'Debe', name: 'Debe', width: 10, align: 'right', formatter: 'textboxExa', formatoptions: { attr: { readonly: 'readonly' } } },
            { label: 'Haber', name: 'Haber', width: 10, align: 'right', formatter: 'textboxExa' },
            //{ label: 'Debe', name: 'Debe', width: 10, align: 'right', formatter: 'currency', editable: true, formatoptions: { defaultValue: '' }, editoptions: { dataInit: function(element) { $(this).createInputDiario3(element, "D", "Det_Tip"); } } },
            {
                label: '<center><i class="ui-icon ui-icon-gear"></i></center>',
                name: 'Pag_Item',
                width: 10,
                align: 'center',
                viewable: false,
                formatter: function (cellvalue, options, rowObject) {
                    if (rowObject.Asi_Deh === 'D') {
                        return "-";
                    } else if (rowObject.Che_Est === 'C' || rowObject.Che_Est === 'U') {
                        return $.createIcon('lock orange', false, 'title="Cheque usado."');
                    } else {
                        return $.getGridButton(borrarPago, rowObject, 'Borrar pago', 'remove', '', 'danger');
                    }

                },
                title: false
            }

        ],
        loadComplete: function () {
            //verificaChequeEstado();
        }


    },
        true,
        'pagosPager', { view: false }).gridButtonsAdd([{
            id: 'btn_mod_agr',
            caption: 'Agregar Pago',
            buttonicon: 'glyphicon glyphicon-plus',
            onClickButton: function () { /*agregarFila(1);*/ openDialogPagos(); }
        }]);

}


function crearGridShowPagosAsi() {
    $('#showPagosAsi').createGrid({
        viewrecords: false,
        caption: "<center>Detalle del anticipo</center>",
        data: [],
        rowNum: 100,
        height: 100,
        width: 650,
        footerrow: true,
        responsive: false,
        onSelectRow: function (rowid, e) { $(this).resetSelection(); },
        colModel: [
            { label: 'index', name: 'index', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Pap_Est', hidden: true },
            { label: 'Codigo', name: 'Pld_Cdc', width: 10, align: "left" },
            { label: 'Cuenta', name: 'Pld_Des', width: 30, align: "left" },
            { label: 'Glosa', name: 'Asi_Glo', width: 25, align: "left" },
            { label: 'Debe', name: 'Debe', width: 10, align: 'right', formatter: 'currency', editable: true, formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '' } },
            { label: 'Haber', name: 'Haber', width: 10, align: 'right', formatter: 'currency', editable: true, formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '' } }
        ]
    }, true, '', { view: false });
}

function crearGridshowPagosChe() {
    $('#showPagosChe').createGrid({
        viewrecords: false,
        caption: "<center>Cheques emitidos en este anticipo</center>",
        data: [],
        rowNum: 100,
        height: 100,
        width: 650,
        footerrow: true,
        responsive: false,
        onSelectRow: function (rowid, e) { $(this).resetSelection(); },
        colModel: [
            { label: 'index', key: true, name: 'index', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Pap_Cod', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Atp_Cod', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Atp_Val', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Com_Cod', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Tia_Cod', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Pld_Cod', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Atp_Fec', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Che_Cod', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Prv_Cod', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Ban_Cod', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Asi_Cod', hidden: true, classes: 'bgNoRight' },
            { label: 'No. Che.', name: 'Che_Num', width: 15, align: "left" },
            { label: 'Fecha', name: 'Che_Fec', width: 15, align: "left" },
            { label: 'Observaci&oacute;n', name: 'Che_Obs', width: 25, align: "left" },
            {
                label: 'Valor',
                name: 'Che_Val',
                width: 15,
                align: 'right',
                formatter: 'currency',
                editable: true,
                formatoptions: {
                    prefix: '$ ',
                    thousandsSeparator: ',',
                    decimalSeparator: '.',
                    defaultValue: ''
                }
            },
            { label: '', name: 'Che_Est', hidden: true, width: 15, align: "left" },
            { label: 'Estado', name: 'estado', width: 15, align: "center" },
            {
                label: '<center><i class="ui-icon ui-icon-gear"></i></center>',
                name: 'btns_anti',
                width: 10,
                align: 'center',
                viewable: false,
                formatter: function (cellvalue, options, rowObject) {
                    //console.log(rowObject);
                    if (rowObject.Che_Est === 'P') {
                        return "-";
                    } else {
                        if (rowObject.Che_Est === 'A') {
                            if (rowObject.Pap_Est === 'A') {
                                return $.getGridButton(preProtestarCheque, rowObject, 'Marcar como protestado', 'ban-circle', '', 'danger');
                            } else {
                                return $.createIcon('lock orange', false, 'title="El Cheque esta siendo usado!"');
                            }

                        } else {
                            return $.createIcon('lock orange', false, 'title="Esta siendo usado!"');
                        }
                    }
                }
            }
        ]
    }, true, '', { view: false });
}

function crearGridConsumosAnticipo() {
    $('#showAntConsumos').createGrid({
        viewrecords: false,
        caption: "<center>Consumos aplicados al anticipo</center>",
        data: [],
        rowNum: 100,
        height: 100,
        width: 650,
        footerrow: false,
        responsive: false,
        onSelectRow: function (rowid, e) { $(this).resetSelection(); },
        colModel: [
            { label: 'No. Compr.', name: 'codigo_consumo', width: 25, align: "left" },
            { label: 'Fecha', name: 'fecha_consumo', width: 15, align: "left" },
            { label: 'Concepto / obs.', name: 'glosa_consumo', width: 40, align: "left" },
            { label: 'Valor Anticipo', name: 'valor_anticipo', width: 20, align: 'right', formatter: 'currency', formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '$ 0.00' } },
            { label: 'Consumido', name: 'valor_consumo', width: 20, align: 'right', formatter: 'currency', formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '$ 0.00' } },
            { label: 'Saldo', name: 'saldo_linea', width: 20, align: 'right', formatter: 'currency', formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '$ 0.00' } }
        ]
    }, true, '', { view: false });
}

function createGridSubAntConsumidos() {
    $('#showSubAntConsumidos').createGrid({
        viewrecords: false,
        caption: "<center>Anticipos consumidos en este asiento</center>",
        data: [],
        rowNum: 100,
        height: 'auto',
        width: 650,
        footerrow: true,
        responsive: false,
        onSelectRow: function (rowid, e) { $(this).resetSelection(); },
        colModel: [
            { label: 'ID Anticipo', name: 'Atp_Cod', width: 15, align: "left" },
            { label: 'Asiento Anticipo', name: 'asiento_anticipo', width: 30, align: "left" },
            { label: 'Valor Anticipo', name: 'valor_anticipo', width: 18, align: 'right', formatter: 'currency', formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '$ 0.00' } },
            { label: 'Consumo', name: 'consumo_mov', width: 18, align: 'right', formatter: 'currency', formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '$ 0.00' } },
            { label: 'Saldo (momento)', name: 'saldo_momento', width: 18, align: 'right', formatter: 'currency', formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '$ 0.00' } },
            { label: '', name: 'Dac_Cod', hidden: true },
            { label: '', name: 'saldo_final_hoy', hidden: true }
        ]
    }, true, '', { view: false });
}

function imprimirAsiento(params) {
    $.saveDataJson("", { impAsiento: true, params: params }, function (responce) {
        if (responce['success']) {
            window.open(responce['link']);
            return false;
        }
    });
}

function preanularAnticipo(params) {
    //console.log(params);
    $.createDialogConfirm('�Est&aacute; seguro que desea anular este anticipo?', null, function () {
        $.saveDataJson("", { anularAnticipo: true, data: params }, function (responce) {
            if (responce['success']) {
                $("#searchGrid").trigger("reloadGrid");
                return false;
            }
        });
    });
}


function preProtestarCheque(row) {
    //console.log(row);
    let shChq = false,
        i = 0;
    row['fechaActual'] = new Date();
    $.createDialogConfirm('¿Est&aacute; seguro que desea marcar como protestado este cheque?', null, function () {
        //console.log(row);
        $.saveDataJson("", { protestarCheq: true, row: row }, function (responce) {
            if (responce['success']) {

                $("#searchGrid").trigger("reloadGrid");
                $('#verPagosDialogMod').dialog('close');
                $('#impCompr').attr('href', responce['link']);
                $.alert('La transacci&oacute;n se realizo con exito.');
                // Verifica chueques
                $("#Che_imp option").remove();
                const vrchq = async () => {
                    arrayCheques.length = 0;
                    arrayCheques = await chequesAsync(row);
                }
                vrchq().then(() => {
                    console.log(arrayCheques);
                    arrayCheques.forEach((data) => {
                        //console.log(data);
                        if (data['Che_Est'] === "A") {
                            i++;
                            shChq = true;
                            $("#Che_imp").append("<option value='" + i + "' data-link='?codigo2=" + data['Che_Cod'] + "&asi=" + data['Asi_Cod'] + "&ban=" + data['Ban_Cod'] + "&pro=" + data['Prv_Cod'] + "'>No.:" + data['Che_Num'] + " - Valor:" + data['Che_Val'] + "</option>");
                            cambiarChek();
                        }

                    });
                    if (shChq) {
                        $("#successDialog").dialog({ width: 500, height: 355 });
                        $("#siche").removeAttr("hidden");
                    } else {
                        $("#successDialog").dialog({ width: 500, height: 200 });
                        $("#siche").attr("hidden", "");
                    }
                    $('#successDialog').dialog('open');

                });
                return false;
            }
        });
    });

}


function busquedaAjax() {
    //anticiposAjax
    //searchGrid
    $('#searchGrid').Search('#searchAnticipos', 'anticiposAjax');
}

async function asientoSubGridAsync(row) {
    let resultado = await getDataSubGridAsientoProm(row);
    return resultado;
}
async function asientoAsync(row) {
    let result = await getDataAsientosProm(row);
    return result;
}

async function chequesAsync(row) {
    let chq = await getDataChequesProm(row);
    return chq;
}
async function consumosAnticipoAsync(row) {
    let cons = await getConsumosAnticipoProm(row);
    return cons;
}
async function anticiposConsumidosAsync(row) {
    let ant = await getAnticiposConsumidosProm(row);
    return ant;
}

async function planCuentaWithNum(tipoAbr, pecCod) {
    let pln = await getNumCWithPlan(tipoAbr, pecCod);
    return pln;
}

async function getCheqNum(valor, banCod) {
    let numChq = await getNumCheque(valor, banCod);
    return numChq;
}


//Promesa plan de cuentas y No. Cuenta del banco para anticipos con cheques
function getNumCWithPlan(abr, pecCod) {
    return new Promise((resolve, reject) => {
        $.getDataJson("", { getPlanCuentasCheq: true, Ban_Tip: abr, Pec_Cod: pecCod }, (result) => {
            resolve(result.getData);
        }, (err) => {
            reject(err);
        })
    });
}

//Promesa asiento detalle
function getDataSubGridAsientoProm(data) {
    //console.log(data);
    return new Promise((resolve, reject) => {
        $.getDataJson("", { getSubGridAsient: true, Com_Cod: data['Com_Cod'] }, (result) => {
            resolve(result.dataSubAsiento);
        }, (err) => {
            reject(err);
        });
    });
}


// Promesa de asientos
function getDataAsientosProm(data) {
    //console.log(data);
    return new Promise((resolve, reject) => {
        $.getDataJson("", { getAsientos: true, Com_Cod: data['Com_Cod'] }, (result) => {
            resolve(result.dataASiento);
        }, (err) => {
            reject(err);
        });
    });

}
//Promesa de cheques
function getDataChequesProm(data) {
    //console.log(data);
    return new Promise((resolve, reject) => {
        $.getDataJson("", { getCheques: true, Atp_Cod: data['Atp_Cod'], Prv_Cod: data['Prv_Cod'] }, (result) => {
            resolve(result.dataCheque);
        }, (err) => {
            reject(err);
        });
    });
}
function getConsumosAnticipoProm(data) {
    return new Promise((resolve, reject) => {
        $.getDataJson("", { getConsumosAnticipo: true, Atp_Cod: data['Atp_Cod'] }, (result) => {
            resolve((result && result.rows) ? result.rows : []);
        }, (err) => {
            reject(err);
        });
    });
}
function getAnticiposConsumidosProm(data) {
    return new Promise((resolve, reject) => {
        $.getDataJson("", { getAnticiposConsumidos: true, Com_Cod: data['Com_Cod'] }, (result) => {
            resolve((result && result.rows) ? result.rows : []);
        }, (err) => {
            reject(err);
        });
    });
}
//Promesa num cheque
function getNumCheque(valor, banCod) {
    return new Promise((resolve, reject) => {
        $.getDataJson('', { verificaCheque: true, Che_Num: valor, Ban_Cod: banCod }, (resultado) => {
            resolve(resultado.numCheque);
        }, (err) => {
            reject(err);
        });
    });
}

//moverse a editar anticipos
function moveToUpdate() {
    $("#documentoSearch").moveComp("#documentoUpdate").updateGridsSizes();
}
//moverse a el principal
function moveToMain() {
    $("#documentoUpdate").moveComp("#documentoSearch").updateGridsSizes();
    $("#searchGrid").trigger("reloadGrid");
}

function limpiarFormAnticipos() {
    console.log('lolga');
    $("#pagos").jqGrid("clearGridData").trigger("reloadGrid");
    $('#anticipoPrvForm').setData({});
}

function openDialogPagos() {
    $('#Pag_Cod').trigger('change');
    $('#pagosDialog').dialog('open');
}

$('#provDialog').createDialog({
    // icon:'search',
    width:500, 
    height:430,
    autoOpen: false,
    modal: true,
});

$(function(){
    $.createSearchDialog('provDialog',[
        { label: 'C&oacute;d.Int.', name: 'Prv_Cod', key: true, width: 15, align:"center", hidden:true },
        { label: 'C&eacute;dula/RUC', name: 'Prs_Ced', width: 50 },
        { label: 'Proveedor', name: 'nombre', width: 100},
        { label: 'Direcc.', name: 'Prs_Dir', width: 60 },
        { label:'&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false, formatter:'gridButton',
            formatoptions:{
                action:selectProveedor
            }
        }
    ],null,null,null,{ headertitles:true },
    { title:'Proveedor', text:'searchPrv' });
});

/**
 * cargar clientes
 * @param  {object} proveedor row seleccionada del dialogo de proveedores
 * @return {void}
 */
function selectProveedor(proveedor){
    if (window._provPickerTarget === 'busqueda') {
        $('#busq_Prv_Cod').val(proveedor.Prv_Cod || '');
        $('#busq_Prs_Ced').val(proveedor.Prs_Ced || '');
        $('#busq_nombre').val(proveedor.nombre || '');
        $('#busq_Prs_Dir').val(proveedor.Prs_Dir || '');
        actualizarVisibilidadColumnaProveedorKardex();
        $('#provDialog').dialog('close');
        busquedaAjax();
        return;
    }
    $("#bandera_prov").val("sel");
    $("#Atp_Obs").val("ANTICIPO A PROVEEDOR - " + proveedor.nombre);
    $('#anticipoPrvForm').setData($.extend(proveedor,{op_opciones:'c'}),false);
    $('#provDialog').dialog('close');
}

function limpiarProveedorBusqueda() {
    $('#busq_Prv_Cod').val('');
    $('#busq_Prs_Ced').val('');
    $('#busq_nombre').val('');
    $('#busq_Prs_Dir').val('');
    actualizarVisibilidadColumnaProveedorKardex();
    if ($('#searchGrid').length && $('#searchGrid').data('jqGrid')) {
        $('#searchGrid').trigger('reloadGrid');
    } else {
        busquedaAjax();
    }
}

function cambiarCamposPagos(tipoPago, tipoAbr) {
    //console.log(tipoPago, tipoAbr);
    $("#Pap_Ctd").val("");
    $("#Che_Num").val("");
    $("#Pap_Val").val("");
    $("#indicadorChe").removeClass("red glyphicon glyphicon-remove");
    $("#indicadorChe").removeClass("green glyphicon glyphicon-ok");
    $("#Che_Fec").dateLimits($("#Atp_Fec").val(), $("#Pec_Cod option:selected").attr("data-fin"));
    const getCuentaPlan = async () => {
        arrayCuentasPlan.length = 0;
        arrayCuentasPlan = await planCuentaWithNum(tipoAbr, perCodAct);
    }

    getCuentaPlan().then(() => {
        //console.log(arrayCuentasPlan);
        $("#Ban_Cod option").remove();
        for (let i = 0; i < arrayCuentasPlan.length; i++) {
            if (tipoAbr == "EFE" || tipoAbr == "DEP") {
                $("#Ban_Cod").append("<option value='" + arrayCuentasPlan[i].Ban_Cod + "' data-pla='" + arrayCuentasPlan[i].Pld_Cod + "' data-cdc='" + arrayCuentasPlan[i].Pld_Cdc + "' data-cue='" + arrayCuentasPlan[i].Ban_Cue + "' data-des='" + arrayCuentasPlan[i].Pld_Des + "'>" + arrayCuentasPlan[i].Pld_Des + "</option>");
            } else {
                $("#Ban_Cod").append("<option value='" + arrayCuentasPlan[i].Ban_Cod + "' data-pla='" + arrayCuentasPlan[i].Pld_Cod + "' data-cdc='" + arrayCuentasPlan[i].Pld_Cdc + "' data-cue='" + arrayCuentasPlan[i].Ban_Cue + "' data-des='" + arrayCuentasPlan[i].Pld_Des + "'>" + arrayCuentasPlan[i].Pld_Des + " - " + arrayCuentasPlan[i].Ban_Cue + "</option>");
            }
        }
        $('#pagosForm').children().not(':first,:last').addClass('hidden');
        $('#pagosForm').find('.' + tipoPago).removeClass('hidden');
        $('#pagosForm').find('.' + tipoPago).find('.form-control').prop('required', true);
    });


}

function borrarPago(row) {
    //console.log('borrar', row);
    let editar = true;
    $('#pagos').jqGrid('delRowData', row.index);
    //console.log(arrayModAsiento.length);
    arrayModAsiento.length = 0;
    arrayModAsiento = $('#pagos').getGridBatch();
    reCalculateHaber(arrayModAsiento.length);
    formaterFotter(row.index);


}

function formaterFotter(indice) {
    $("#" + indice + "_Haber").css('text-align', 'right');
    $("#_Haber").attr("readonly", "");
    $("#_Haber").css('text-align', 'right');
    $("#_Debe").css('text-align', 'right');
}

function agregarFila(aux) {
    changeValueInPago();
    var glosaDes = '',
        papCto = '';
    var tipoAbrv = $("#Pag_Cod option:selected").attr("data-abr");
    if (verficaPago()) {
        if (!verifyChequeInGrid()) {
            if (!existeCheq) {
                //console.log(aux);
                //console.log(tipoAbrv);
                if (tipoAbrv === 'CHE') {
                    glosaDes = $("#Pag_Cod option:selected").text() + " NO. " + $("#Che_Num").val();
                    papCto = $("#Ban_Cod option:selected").attr("data-cue");
                } else { glosaDes = 'Ant. prov. ' + $("#Pag_Cod option:selected").text(); }
                var $this = $('#pagos');
                var campoGrid = '_Haber';
                var id = $this.nextIndex();
                $this.jqGrid('addRowData', id, { index: id, grid_tipp: 'pago', Che_Cod: '', Asi_Cod: '', Pap_Cod: '', Pag_Cod: $("#Pag_Cod").val(), Pag_Abr: $("#Pag_Cod option:selected").attr("data-abr"), Pag_Des: $("#Pag_Cod option:selected").text(), Pld_Des: $("#Ban_Cod option:selected").attr("data-des"), Pld_Cdc: $("#Ban_Cod option:selected").attr("data-cdc"), Pap_Ctd: $("#Pap_Ctd").val(), Ban_Cod: $("#Ban_Cod option:selected").attr("value"), Che_Num: $("#Che_Num").val(), Che_Fec: $("#Che_Fec").val(), Pap_Cto: papCto, Pld_Cod: $("#Ban_Cod option:selected").attr("data-pla"), Det_Tip: 'H', Glosa: glosaDes, Debe: '', Haber: parseFloat($("#Pap_Val").val()), Pag_Item: "" }, 'last');
                $this.jqGrid('editRow', id);
                $this.find('#' + id + "_Asi_Glo").val(glosaDes);
                $this.find('#' + id + "_Haber").val(($("#Pap_Val").val() * 1).toFixed(4));
                //$("#Pap_Val").val() 5_Haber
                $this.find('tr#' + id).find('#' + id + campoGrid).on('change', function () {
                    //console.log('LOLA', $(this).val());
                    let tnmGrid = $('#pagos').getGridBatch();
                    reCalculateHaber(tnmGrid.length);
                    formaterFotter(id);
                }).trigger('change');
                let tnmGrid = $this.getGridBatch();
                //console.log(tnmGrid);
                reCalculateHaber(tnmGrid.length);
                formaterFotter(id);
                glosaDes = '';
                papCto = '';
                tipoAbrv = '';
                clearModalPago();
            } else {
                $.alert("Ya existe el registro de pago con el mismo n&uacute;mero de cheque");
            }

        } else {
            clearModalPago();
            $.alert("No puede ingresar dos pagos con el mismo n&uacute;mero de cheque");
        }

    } else {
        $.alert("Complete todos los campos");
        $('#btnGuardar').attr('disabled', '');
    }


}

function clearModalPago() {
    $("#Che_Num").val("");
    $("#Pap_Val").val("");
    $("#indicadorChe").removeClass("red glyphicon glyphicon-remove");
    $("#indicadorChe").removeClass("green glyphicon glyphicon-ok");
}

function verFCheque() {
    var tipoAbrv = $("#Pag_Cod option:selected").attr("data-abr");
    let banCod = $("#Ban_Cod option:selected").attr("value");
    let numAct = $("#Che_Num").val();
    let arrayVerifiCh = [];
    if (tipoAbrv === 'CHE') {
        const vC = async () => {
            arrayVerifiCh.length = 0;
            arrayVerifiCh = await getCheqNum(numAct, banCod);
        }
        vC().then(() => {
            if (arrayVerifiCh.length > 0) {
                existeCheq = true;
            } else {
                existeCheq = false;
            }
        });
    }


}


function verificarNoCheque(valor) {

    $('#btnGuardar').attr('disabled', 'disabled');
    //console.log(valor);
    let arrayVerifiCh = [];
    let banCod = $("#Ban_Cod option:selected").attr("value");
    //console.log(banCod);

    const cheque = async () => {
        arrayVerifiCh.length = 0;
        arrayVerifiCh = await getCheqNum(valor, banCod);
    }
    if (valor > 0 && banCod > 0) {
        cheque().then(() => {
            //console.log(arrayVerifiCh.length);
            if (arrayVerifiCh.length > 0) {
                //console.log('si');

                $("#indicadorChe").removeClass("green glyphicon glyphicon-ok");
                $("#indicadorChe").removeClass("red glyphicon glyphicon-remove");
                $("#indicadorChe").addClass("red glyphicon glyphicon-remove");
                $('span.validate').attr('title', 'El <u> CHEQUE No. <strong>' + valor + '</strong></u> ya se encuentra registrado');
                $('#btnGuardar').attr('disabled', '');
                existeCheq = true;
            } else {
                //console.log('no');
                $('span.validate').attr('title', '');
                $("#indicadorChe").removeClass("red glyphicon glyphicon-remove");
                $("#indicadorChe").removeClass("green glyphicon glyphicon-ok");
                $("#indicadorChe").addClass("green glyphicon glyphicon-ok");
                existeCheq = false;
                if (!verifyChequeInGrid()) { $('#btnGuardar').removeAttr('disabled'); }
            }
        });

    }

}
//Verifica en grid el n�mero de cheque
function verifyChequeInGrid() {
    let stado = false;
    let numAct = $("#Che_Num").val();
    let getData = $('#pagos').jqGrid("getCol", "Che_Num", false);
    if (numAct !== '') {
        for (let i = 0; i < getData.length; i++) {
            if (numAct === getData[i]) {
                stado = true;
                break;
            }
        }
    }

    return stado;
}
//Guardar Anticipo
function updateData(formulario, accion, dialogo) {

    var data = $('#' + formulario).getData('save');
    let ids = verficaDataInGrid();
    $.createDialogConfirm(`�Est&aacute; seguro que desea guardar los datos del anticipo proveedor?`, null, function () {
        if (ids.length > 1) {
            //console.log('si');
            //data = $('#AnticipoPrvForm').serializeObject();
            data['anticipoGrid'] = $('#pagos').getGridBatch();
            $.arraySpliceFields(data['anticipoGrid'], ['index', 'Pag_Item', 'false']);
            data[accion] = true;
            $.saveDataJson('', data, function (resp) {
                if (resp['success']) {
                    $('#' + formulario)[0].reset();
                    $('#pagos').clearGrid('true');
                    if (resp['isCheque']) {
                        $("#successDialog").dialog({ width: 500, height: 355 });
                        $("#siche").removeAttr("hidden");
                        $("#Che_imp option").remove();
                        for (let i = 0; i < resp['arrayCheques'].length; i++) {
                            $("#Che_imp").append("<option value='" + i + "' data-link='" + resp['arrayCheques'][i].link + "'>" + resp['arrayCheques'][i].che + "</option>");
                        }
                        $("#Che_imp").trigger("onchange");
                    } else {
                        $("#successDialog").dialog({ width: 500, height: 200 });
                        $("#siche").attr("hidden", "");
                    }
                    $('#impCompr').attr('href', resp['link']);
                    $('#successDialog').dialog('open');

                    encerarFotter();
                    moveToMain();
                    $.alert('La transacci&oacute;n se realizo con exito.');
                    return false;
                }
            });
        } else {
            $.alert("Debe agregar al menos un pago");
        }


    });
}

function cambiarChek() {
    $("#impchetd td").each(function () {
        $(this).children("a").attr("href", $(this).children("a").attr("data-ruta") + "" + $("#Che_imp option:selected").attr("data-link"));
    });
}

function encerarFotter() {
    $('#_Debe').val('0.00');
    $('#_Haber').val('0.00');
}

function verficaDataInGrid() {
    var ids = $('#pagos').jqGrid('getDataIDs');
    return ids;
}

function verficaPago() {
    let verifica = false;
    let tipoSelect = $("#Pag_Cod option:selected").text();
    if (tipoSelect === 'Efectivo') {
        if ($("#Pap_Val").val() !== "") {
            verifica = true;
        }
    } else if (tipoSelect === 'Deposito' || tipoSelect === "Transferencia") {
        if ($("#Pap_Val").val() !== "" && $("#Pap_Ctd").val() !== "") {
            verifica = true;
        }
    } else {
        if ($("#Pap_Val").val() !== "" && $("#Che_Num").val() !== "") {
            verifica = true;
        }
    }
    return verifica;

}

function changeCuentaCod() {
    $('#Ban_Cod').on('change', function () {
        clearModalPago();
    }).trigger('change');
}

function changeValueInPago() {
    $('#Pap_Val').on('change', function () {
        if ($(this).val() * 1 > 0) {
            if (verficaPago()) {
                verFCheque();
                $('#btnGuardar').removeAttr('disabled');
            }
        } else {
            $.alert('El Valor ingresado del Cheque debe superior a 0');
            $(this).val('');

        }

    }).trigger('change');
}

//permite el ingreso unicamente de numeros
function soloNumeros(e) {
    // valor = valor.replace(/[^0-9]/g,'');
    var key = window.Event ? e.which : e.keyCode
    return (key >= 48 && key <= 57)
}

function formatMoney(number, places, symbol, thousand, decimal) {
    number = number || 0;
    places = !isNaN(places = Math.abs(places)) ? places : 2;
    symbol = symbol !== undefined ? symbol : "$";
    thousand = thousand || ",";
    decimal = decimal || ".";
    var negative = number < 0 ? "-" : "",
        i = parseInt(number = Math.abs(+number || 0).toFixed(places), 10) + "",
        j = (j = i.length) > 3 ? j % 3 : 0;
    return symbol + negative + (j ? i.substr(0, j) + thousand : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousand) + (places ? decimal + Math.abs(number - i).toFixed(places).slice(2) : "");
}

//Obtener negociaciones
function llenarNego(param) {
    fetch('', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: $.param({ searchNegAntAjax: true, Atp_Cod: param })
    }).then(response => response.json()).then(responce => {
        if (responce && responce.data) {
            $("#Num_Neg").val(responce.data['Num_Neg']);
            $("#Cod_Neg").val(responce.data['Cod_Neg']);
            $("#Cod_Nd").val(responce.data['Cod_Nd']);
        } else { $("#Num_Neg, #Cod_Neg, #Cod_Nd").val(''); }
    }).catch(() => { $("#Num_Neg, #Cod_Neg, #Cod_Nd").val(''); });
}

function limpiarCamposNego1() {
	document.querySelector("#Num_Neg").value = "";
	document.querySelector("#Cod_Neg").value = "";
}