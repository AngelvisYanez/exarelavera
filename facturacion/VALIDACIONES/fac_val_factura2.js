var editDoc = false, AutCod = '', TicCod = '', Com_Asoc = 0;
var Nota_CreDeb = false, Mod_Nota_CreDeb = false;
var pago_min = 0;
var edit_reten = false;
var doc_ventas = '';
var init_load = false;
const ret_banano = [338];
var list_printers = $.getLocalStore('printers') || {}, printers = list_printers['has_printers'] === true;

var lista_datos_extra;
$(function () {
	lista_datos_extra = $("#lista_datos_extra");
	/*try {*/ load_datos_extras(); /*} catch (e) { 
	console.log(e);
	}*/
});

function inicializarDocVenta(nuevo_doc = true) {
	$(function () {

		if ($('#changePagoDialog').length > 0) $('#changePagoDialog').createDialog({ icon: 'transfer', width: 600, height: 350 });

		//Seleccion automatica de la primera opcion
		$("#Tpc_Cod").prop("selectedIndex", 1);

		$("#radioec").change(function () {
			$('#Prs_Ced').attr('onchange', 'validar(1)');
			habilitar('ec', 1);
			$('#lb_ec').attr('class', 'btn btn-success btn-xs');
			$('#lb_ex').attr('class', 'btn btn-default btn-xs');
			$('#spanec').show(); $('#spanex').hide(); clear();
		});

		$("#radioex").change(function () {
			clear();
			habilitar('ex', 7);
			$('#Prs_Ced').attr('onchange', 'validar(2)');
			$('#lb_ex').attr('class', 'btn btn-success btn-xs');
			$('#lb_ec').attr('class', 'btn btn-default btn-xs');
			$('#spanex').show(); $('#spanec').hide();
		});

		$('#Ide_Cod').change(function () {
			$('#Prs_Ced').val('').focus();
			if (this.value * 1 === 1) {
				$('#Prs_Ced').attr('onchange', 'validar(2)');
			} else {
				$('#Prs_Ced').attr('onchange', 'validar(3)');
			}
			habilitar('ex', this.value);
		});

		$('#Tic_Cod').on('change', verifyButtonAutExtern);
		let element = $('input[name="input_autorizacion"]');
		element.toggle();
		// agregando opcion de edicion de autorizacion a modo manual
		$('#btnAddAut').on('click', { 'elem': element }, showIntutAut);

		$('.datepickers').createDatePickers({ checkAvailability: true, hideMsg: false }).mask('9999-99-99', { placeholder: '_' });
		$('#pagosDialog').createDialog({ height: 325, icon: 'usd' });
		$('#changeReteDialog').createDialog({ height: 128, width: 300, icon: 'usd' });
		// $.createDatePickers('.datepicker');

		$('#For_Cod').on('change', function () {
			$('.pagoCredito')[this.value * 1 === 2 ? 'show' : 'hide']();
			(('0' + this.value) * 1 === 2 ? $('#Cpp_Ven').attr('required', 'required') : $('#Cpp_Ven').removeAttr('required'));
		});

		$('#OrderBy').on('change', function () {
			$('input[name=order]').val($(this).val());
			$('#serachDocDorm').formSubmit();
		});

		// cargar Tpc_Cod por documentos en ultima version de Facturacion electronica
		if (Mod_Nota_CreDeb) {
			cargar_Documentos([4, 5]);
		}


		if (Nota_CreDeb) {
			$('#Pec_Cod,#For_Cod_Nota').on('change', function () {
				var ventasIds = doc_ventas.getDataIDs();
				if ($('#For_Cod_Nota').val() * 1 === 1 && ventasIds.length >= 1) {
					$.each(ventasIds, function (index, valor) {
						if (index !== 0) {
							doc_ventas.delRowData(valor);
						}
					});
				} else {
					doc_ventas.clearGridData();
				}
				doc_ventas.trigger('reloadGrid');
			});

			$('#For_Cod_Nota').on('change', function () {
				filtrarCuentasFormasPago($(this).find(':selected').data());
				$('#Forma_Cod').val($(this).find(':selected').data('For_Cod'));
			});


			cargarFormasPago();

			doc_ventas = $('#ventas');
			doc_ventas.createGrid({
				data: [], rowNum: 10000000, height: 'auto', footerrow: true,
				colModel: [
					{ label: 'C&oacute;d.Int.', name: 'Vet_Cod', key: true, width: 20, align: 'center', hidden: false, classes: 'bgNoRight' },
					{ label: 'N./Venta.', name: 'Vet_Num', width: 30, align: 'center', hidden: true, classes: 'bgNoRight' },
					{ label: 'Numero.', name: 'Vet_Num_Asoc', width: 70, align: 'center', classes: 'bgNoRight' },
					{ label: 'C*pagar', name: 'Cpc_Cod', width: 70, align: 'center', hidden: true, classes: 'bgNoRight' },
					{ label: 'Fecha/Venta.', name: 'Caj_Fec', width: 40, align: 'center', classes: 'bgNoRight' },
					{ name: 'Tic_Des', hidden: true },
					{ label: 'Pagos', name: 'pagos', width: 30, classes: 'bgNoRight', align: 'center', hidden: true, formatter: function (cv, opc, rObj) { return $('<div></div>').attr('data--arreglo', $.jsonParser(rObj.pagos)).prop('outerHTML'); }, unformat: function (el, opts, cell) { return $('div', cell).data('Arreglo'); } },
					{ label: 'Documento', name: 'Tic_Cod', width: 30, classes: 'bgNoRight', align: 'center', formatter: function (cv, opc, rObj) { return $('<div></div>').append(rObj.Tic_Des).attr('data--Tic_-Cod', cv).prop('outerHTML'); }, unformat: function (el, opts, cell) { return $('div', cell).data('Tic_Cod'); } },
					{ label: 'Tipo/Pago', name: 'For_Des', width: 30, classes: 'bgNoRight' },
					{ label: 'Cod_Pago', name: 'For_Cod', width: 30, classes: 'bgNoRight', hidden: true },
					{ label: 'Total', name: 'Vet_Total', width: 30, classes: 'bgNoRight', formatter: function (cv, opts, rObj) { return $.toFixed(cv); } },
					{ label: 'Pagos', name: 'Vet_Abonos', width: 30, formatter: function (cv, opts, rObj) { if (!$.varValid(cv)) { cv = 0.00 }; return ($.isNumeric(cv) ? $.toFixed(cv) : cv); } },
					{ label: 'Saldo', name: 'Vet_Saldo', width: 30, align: 'right', formatter: 'currency', classes: 'bgNoColor' },
					{
						label: '<i class="glyphicon glyphicon-remove"></i>', name: 'btn_remover', width: 20, align: 'center', formatter: 'gridButton',
						formatoptions: {
							action: deleteDocVenta, data: function (o) { return o.Vet_Cod; },
							icon: 'remove', type: 'danger'
						}
					}
				], loadComplete: function () { $(this).setGridSummary(['Vet_Saldo'], { Vet_Abonos: '<div style="text-align:right;">TOTAL:</div>' }); }
			}, true, 'ventas1Pager', { view: false }).gridButtonsAdd([
				{ caption: 'Agregar', buttonicon: 'glyphicon glyphicon-plus', class: 'a', onClickButton: function () { mostrarVentas(); } }
			]);


			$.createSearchDialog('ventasDialog', [
				{ label: 'C&oacute;d.Ven.', name: 'Vet_Cod', key: true, width: 15, align: 'center', hidden: true },
				{ label: 'Vet./Num.', name: 'Vet_Num', width: 50 },
				{ label: 'Cliente.', name: 'cliente', width: 250 },
				{ label: 'Fecha/Venta.', name: 'Caj_Fec', width: 90, align: 'center' },
				{ label: 'T.Pago', name: 'For_Des', width: 80 },
				{ label: 'Tipo/Doc.', name: 'Tic_Cod', width: 60, formatter: function (cv, opc, rObj) { return $('<div></div>').append(rObj.Tic_Des).attr('data--Tic_-Cod', cv).prop('outerHTML'); }, unformat: function (el, opts, cell) { return $('div', cell).data('Tic_Cod'); } },
				{ label: '&nbsp;', name: 'act1', width: 30, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: selectVent } }
			], null, null, null, null, { title: 'N&uacute;mero de Venta', label: 'Num. Doc.', options: [] });
		}

		//Change para el cambio de periodo
		$('#Pec_Cod').change(function () {
			var sel_fecha = $(this).find('option:selected');
			//console.log(sel_fecha.data('inicio')+'*'+sel_fecha.data('fin')+'*'+sel_fecha.data('placod'));

			// Validar que el periodo esté en vigencia
			if (!validarPeriodoVigente()) {
				return;
			}

			fechas(sel_fecha.data('inicio'), sel_fecha.data('fin'), sel_fecha.data('placod'));
			cuentas(sel_fecha.data('placod'));
			$('#Caj_Fec').trigger('change');
		});

		if (nuevo_doc) {
			$('#Pec_Cod').trigger('change');
		}

		$('#cambiarAut').on('click', function () {
			$('#autorizaForm').setData({
				'Tic_Cod': $('#Tic_Cod').find('option:selected').data('ticcod'),
				'Pun_Cod': $('#Tic_Cod').find('option:selected').data('puncod')
			});
			$('#autorizaDialog').dialog('open');
			$.Search('autoriza');
			//$('#autorizaGrid').trigger( 'reloadGrid' );

		});

		$('#Pag_Cod').on('change', function () {
			var text = $(this).find('option:selected').text().toUpperCase();
			$('.cuen_ban,.banco,.bancos,.obs_credito,.info-tarjeta').find(':input').removeAttr('required').end().hide().setData({});
			$('.cuenta_pago').show().find(':input').attr('required', 'required');
			$('.fecha_cheque').hide().removeAttr('disabled');
			switch (text) {
				case 'DEPOSITO':
					// $('.banco,.cuen_ban,.info-tarjeta').show().find(':input').attr('required', 'required');
					// $('.cuenta_pago').hide().removeAttr('disabled');
					$('.banco,.cuen_ban,.info-tarjeta').show();
					$('.banco,.cuen_ban').find(':input').attr('required', 'required');
					$('.info-tarjeta').find(':input').removeAttr('required');
					$('.cuenta_pago').hide().removeAttr('disabled');
					break;
				case 'TARJETA DE CREDITO':
					$('.bancos,.info-tarjeta').show();
					//$('#Pag_Pld opciont[´data-pag_abr=""]')
					break;
				case 'TARJETA DE DEBITO':
					$('.bancos,.info-tarjeta').show();
					//$('#Pag_Pld opciont[´data-pag_abr=""]')
					break;
				case 'TRANSFERENCIA':
					$('.banco,.cuen_ban').show().find(':input').attr('required', 'required');
					$('.cuenta_pago').hide().removeAttr('disabled');
					break;
				case 'CHEQUE':
					$('.bancos,.cuen_ban').show().find(':input').attr('required', 'required');
					$('.fecha_cheque').show().find(':input').attr('required', 'required');
					$('#Ban_Cod').trigger('change');
					$('#Vet_Cue').removeAttr('disabled');
					break;
				default:
					break;
			}
		}).trigger('change');

		$('#For_Cod').on('change', function () {
			var credi = ('0' + this.value) * 1 === 2, val = $('#Pag_Cod').find('option').hide().end().find('option[data-forcod="' + this.value + '"]').show()[0].value;
			$('#Pag_Cod').val(val).trigger('change');
			$('.pagoCredito')[credi ? 'show' : 'hide']();
			(credi ? $('#Cpc_Ven').attr('required', 'required') : $('#Cpc_Ven').removeAttr('required'));
			(credi ? $('#saldo_pago').attr('readonly', 'readonly') : $('#saldo_pago').removeAttr('readonly'));
			//backupHeader();
			var sinAcento = remove_accent($(this).find('option:selected').text());
			cargarCuentas(sinAcento, $('#Pag_Cod').find('option:selected').data('forcod'));
		}).trigger('change');

		//cargarCuenta();
		items = $('#items');
		pagos = $('#pagos');
		pagos.createGrid({
			data: [], caption: 'Pagos', rowNum: 10000000, height: 'auto', footerrow: true,
			colModel: [
				{ label: 'C&oacute;d.Int.', name: 'Vet_Num', key: true, width: 15, align: 'center', hidden: true },
				{ label: 'fecha_ven.', name: 'Cpc_Ven', width: 15, align: 'center', hidden: true },
				{ label: 'Vet_Nlt', name: 'Vet_Nlt', hidden: true },
				{ label: 'Vet_Nts', name: 'Vet_Nts', hidden: true },
				{ label: 'Vet_Nau', name: 'Vet_Nau', hidden: true },
				{ label: 'Ban_Cod.', name: 'Ban_Cod', width: 15, align: 'center', hidden: true },
				{ label: 'Forma', name: 'For_Cod', width: 30, classes: 'bgNoRight', formatter: function (cv, opts, rObj) { return '<div data-val="' + cv + '">' + $('#For_Cod option[value="' + cv + '"]').text() + '</div>'; }, unformat: function (el, opts, cell) { return $('div', cell).data('val'); } },
				{ label: 'Forma_Cod', name: 'Forma_Cod', width: 30, hidden: true, classes: 'bgNoRight' },

				{ label: 'Fec_che', name: 'Fec_che', width: 30, hidden: true, classes: 'bgNoRight' },
				{ label: 'Bak_Cod', name: 'Bak_Cod', width: 30, hidden: true, classes: 'bgNoRight' },

				{ label: 'Tipo', name: 'Pag_Cod', width: 30, classes: 'bgNoRight', formatter: function (cv, opts, rObj) { return $('#Pag_Cod option[value="' + cv + '"]').text(); } },
				{ label: 'Tipo_Cod', name: 'Tipo_Cod', width: 30, hidden: true, classes: 'bgNoRight', formatter: function (cv, opts, rObj) { return $('#Pag_Cod option[value="' + cv + '"]').val(); } },
				{ label: 'Pag_Pld', name: 'Pag_Pld', width: 30, hidden: true, classes: 'bgNoRight' },
				{ label: 'Banco', name: 'Vet_Ban', width: 50, align: 'center', classes: 'bgNoRight', formatter: function (cv, opts, rObj) { var ban = $.varValid(rObj['Ban_Cod']) && rObj['Ban_Cod'].length > 0 ? 'Ban_Cod' : ($.varValid(rObj['Bak_Cod']) && rObj['Bak_Cod'].length > 0 ? 'Bak_Cod' : null); if ($.varValid(ban)) return $('#' + ban + ' option[value="' + rObj[ban] + '"]').text(); else return ''; } },
				{ label: 'Cta. Banco', name: 'Vet_Cue', width: 50, align: 'center', classes: 'bgNoRight' },
				{ label: 'Doc./Cheque', name: 'Vet_Che', width: 50, align: 'center' },
				// dinero ingresado
				{ label: 'Monto Ing.', name: 'Vet_Mon', width: 40, align: 'center', formatter: 'currency' },
				{ label: 'Cambio', name: 'Vet_Cam', width: 40, align: 'center', formatter: 'currency' },
				{ label: 'Monto', name: 'Vet_Tot', width: 40, align: 'right', formatter: 'currency', classes: 'bgNoColor' },
				// dias de plazo para credito
				{ label: 'Dias. Plazo', name: 'Vet_Plz', width: 50, align: 'center'/*, hidden: true*/ },
				{
					label: '<i class="glyphicon glyphicon-remove"></i>', name: 'btn_remover', width: 20, align: 'center', formatter: 'gridButton',
					formatoptions: {
						action: deletePago, data: function (o) { return o.Vet_Num; },
						icon: 'remove', type: 'danger'
					}
				}
			], loadComplete: function () { $(this).setGridSummary(['Vet_Tot'], { Vet_Che: '<div style="text-align:right;">TOTAL:</div>' }); }
		}, true, 'pagosPager', { view: false }).gridButtonsAdd([
			// Seccion de botones para la forma de pago 
			{ caption: 'Agregar', buttonicon: 'glyphicon glyphicon-plus', class: 'a', onClickButton: function () { if ($('#Val_Pcc_2').val() * 1 <= 0) { $.alert('El saldo a cobrar es cero!'); return; } registarPagos(); } }, {},
			{ caption: 'Al Contado', buttonicon: 'glyphicon glyphicon-usd', onClickButton: function () { if ($('#Val_Pcc_2').val() * 1 <= 0) { $.alert('El saldo a cobrar es cero!'); return; } alContado(); } },
			{ caption: 'A Cr&eacute;dito', buttonicon: 'glyphicon glyphicon-credit-card', onClickButton: function () { if ($('#Val_Pcc_2').val() * 1 <= 0) { $.alert('El saldo a cobrar es cero!'); return; } aCredito(); } }
		]);
		$('#documentosPager_center').css('width', '0px');

		$('#copresult').createGrid({
			height: 75, postData: { CheListAjax: true }, caption: 'Detalle Venta <button id="btnVentaPrint" onclick="$.imprimirUrl($(this).data(\'url\'))" class="btn btn-success btn-xs pull-right hidden" style="margin-top: -2px;"><i class="glyphicon glyphicon-print "></i> Imprimir</button>',
			rowNum: 10000,
			colModel: [
				{ label: 'C&oacute;d.Int.', name: 'Vet_Int', key: true, width: 15, align: 'center', hidden: true },
				{ label: 'Cantidad ', name: 'Vet_Can', width: 45, align: 'right' },
				{ label: 'Item', name: 'Ite_Lar', width: 130 },
				{ label: 'P. Unit.', name: 'Vet_Pru', width: 130, align: 'right' },
				{ label: 'Importe', name: 'Vet_Imp', width: 65, align: 'right', formatter: 'currency', formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '' }, summaryType: 'sum' }
			],
			loadComplete: function () { $(this).setGridSummary(['Debe', 'Haber'], { Glosa: '<div style=\'text-align:right;\'>TOTALES:</div>' }); }
		}, true);

		$.createSearchDialog('autorizaDialog', [
			{ label: 'C&oacute;d.Aut.', name: 'Aut_Cod', key: true, width: 15, align: 'center', hidden: true },
			{ label: 'Pnto/Imp', name: 'Pun_Sri', hidden: false, align: 'center', width: 60 },
			{ label: 'Autoriza.', name: 'AutSri', width: 70 },
			{ label: 'Items.', name: 'Aut_Ima', width: 40 },
			{ label: 'Inicio', name: 'Aut_Fci', width: 68 },
			{ label: 'Caduca', name: 'Aut_Cad', width: 68 },
			{ label: 'Desde', name: 'Aut_Ini', width: 42 },
			{ label: 'Hasta', name: 'Aut_Fin', width: 42 },
			{ label: 'Tipo/Doc.', name: 'Tic_Des', width: 60 },
			{ label: 'Transport.', name: 'Ext_Nom', width: 90 },
			{ label: '&nbsp;', name: 'Aut_Estado', align: 'center', formatter: 'truefalse', formatoptions: { yesMsg: 'Activo', noMsg: 'Inactivo' }, width: 30 },
			{ label: '&nbsp;', name: 'act1', width: 30, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: selectAut } }
		], null, null, null, null, { title: 'B&uacute;squeda', options: [] });


		//ITEMS CON DESCRIPCION ADICIONAL

		items.createGrid({
			caption: (Nota_CreDeb === true ? '<div class="pull-right" formDatos><span>Afecta Inventario:&nbsp;</span><input id="afecta_inventario" name="Cal_Inv" type="checkbox" class="check-big"/>&nbsp;</div>' : ''),
			data: [],
			rowNum: 10000000, height: 'auto', footerrow: true, headertitles: true, selectGridRows: false,
			colModel: [
				{ name: 'select', label: '<i class="glyphicon glyphicon-check"></i>', width: 30, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: edit_reten ? '' : openItemSelector, icon: 'check', title: (edit_reten ? 'No es posible Cambiar el Item' : 'Seleccionar Item'), data: function (o) { return o.index; }, conditional: function (o) { return !$.isArray(o.Viajes); }, caseFalse: function (o) { return $.isArray(o.Viajes) ? $.createIcon('fa-truck grey') : ''; } }, resizable: false },
				{ name: 'Vet_Index', label: 'Vet_Index', width: 40, align: 'center', hidden: true },
				{ name: 'Viajes', label: 'Viajes', width: 40, align: 'center', formatter: 'json', hidden: true },
				{ name: 'index', label: 'Index', width: 20, sorttype: 'int', align: 'center', key: true, hidden: true },
				{ name: 'Pro_Cod', label: 'C&oacute;d.Int.', width: 20, sorttype: 'int', align: 'center', hidden: true },
				{ name: 'Vet_Can', label: 'Cant.', labelLong: 'Cantidad', width: 40, align: 'right', title: false, editable: (edit_reten ? false : true), editoptions: { dataInit: styleCant } },
				{ name: 'Uni_Des', label: 'Uni.', labelLong: 'Unidad', width: 25, resizable: false },

				{ name: 'Ite_Lar', label: 'Descripci&oacute;n', width: 150 },
				{ name: 'Des_Adi', label: 'Des. Adicional', width: 75, editable: true },
				{ name: 'add_imei', label: 'IMEI', labelLong: 'Seleccionar IMEI', hidden: (Nota_CreDeb ? true : false), width: 70, align: 'center', title: false, formatter: 'imeiSelector', resizable: false },
				{ name: 'Ime_Cod', label: 'Ime_Cod', width: 20, hidden: true },
				{ name: 'Ime_Num', label: 'Ime_Num', width: 20, hidden: true },
				{ name: 'Vet_Dec', label: 'Descuen.', labelLong: 'Descuento', align: 'right', width: 20 },
				{ name: 'Vet_Pru', label: 'P. Unitario', labelLong: 'Precio Unitario', width: 60, align: 'right', title: false/*, summaryRound: 8,formatter:"currency",formatoptions: {prefix:'', thousandsSeparator:',',decimalSeparator:'.',decimalPlaces: 8, defaultValue: ''}*/, editable: (edit_reten ? false : true), editoptions: { dataInit: stylePru } },
				{ name: 'Vet_Imp', label: 'Importe', width: 70, align: 'right', summaryRound: 4, formatter: dynamicFormatter, formatoptions: { prefix: '', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '0.00' }, classes: 'columnHighlight1' },
				{ name: 'Iva_Cod', label: 'CodIva', width: 20, hidden: true },
				//{name:'Iva_Por',label:'IVA', width:15,align:'center', formatter:'truefalse', formatoptions:{yesMsg:'Grava IVA',noMsg:'No Grava IVA'}, title:false, resizable: false },
				//CAMPO NUEVO
				{ name: 'Iva_Por', label: 'IVA(%)', labelLong: 'Porcentaje IVA', width: 35, align: 'center', title: false, resizable: false },
				{ name: 'Ice_Int', label: 'CodIce', width: 20, hidden: true },
				{ name: 'Ice_Por', label: 'ICE %', width: 20, align: 'right', title: false, resizable: false },
				{
					name: 'Vet_Cre', label: 'C.T.', labelLong: 'Credito Tributario', width: 16, align: 'center', title: false, resizable: false,
					formatter: function (cv, opts, row) {
						var checked = (cv === true || cv === 'S' || cv === '1' || cv === 1) ? 'checked' : '';
						// permitir activar el checkbox solo cuando Iva_Por es 0
						var ivaPor = ('0' + row.Iva_Por) * 1;
						var disabled = ivaPor !== 0 ? 'disabled' : '';
						var title = ivaPor !== 0 ? 'title="Solo se permite activar cuando IVA = 0"' : '';
						return '<input type="checkbox" class="item-checkbox" ' + checked + ' ' + disabled + ' ' + title + ' data-row-id="' + row.index + '" tabindex="-1">';
					},
					unformat: function (el, opts, cell) {
						var $chk = $('input[type="checkbox"]', cell);
						// si está deshabilitado no se considera activo
						if ($chk.is(':disabled')) return 'N';
						return $chk.is(':checked') ? 'S' : 'N';
					}
				},
				{ name: 'Ret_Mod', label: 'Ret Mod.', width: 20, hidden: true, formatter: 'truefalse', title: false, resizable: false },
				{ name: 'Ret_Ren_Sri', label: 'I. Renta', labelLong: 'Impuesto a la Renta', hidden: (Nota_CreDeb ? true : false), width: 35, align: 'center', title: false, formatter: 'impRenta', resizable: false },
				{ name: 'Ret_Ren_Cod', label: 'Ret Ren_Cod', width: 20, hidden: true },
				{ name: 'Ret_Ren_Por', label: 'Ret Ren_Por', width: 20, hidden: true },
				{ name: 'Ret_Ren_Con', label: 'Ret Ren_Con', width: 20, hidden: true },
				{ name: 'Iva_Ren_Sri', label: 'Ret. IVA', labelLong: 'Retenci&oacute;n del IVA', hidden: (Nota_CreDeb ? true : false), width: 35, align: 'center', title: false, formatter: 'retIva', resizable: false },
				{ name: 'Iva_Ren_Cod', label: 'Iva Ren_Cod', width: 20, hidden: true },
				{ name: 'Iva_Ren_Por', label: 'Iva Ren_Por', width: 20, hidden: true },
				{ name: 'Iva_Ren_Con', label: 'Iva Ren_Con', width: 20, hidden: true },
				{ name: 'Pld_Cod', label: 'Pld_Cod', width: 20, hidden: true },
				{ name: 'Pld_Cdc', label: 'Cuenta', width: 50, align: 'center', formatter: 'title', formatoptions: { title: function (o) { return o['Pld_Cdc'] + ' - ' + o['Pld_Des']; } }, title: false },
				{ name: 'Pld_Des', label: 'Pld_Des', width: 20, hidden: true },
				{ name: 'Adq_Cod', label: 'CodAdq', width: 20, hidden: true },
				{ name: 'Adq_Cor', label: 'Adq.', labelLong: 'Adquisiciones', width: 20, align: 'center', title: false, formatter: 'title', formatoptions: { title: function (o) { return o['Adq_Des']; } }, resizable: false },
				{ name: 'delete', label: '<i class="glyphicon glyphicon-remove icon-grey"></i>', width: 30, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: (edit_reten ? '' : deleteItem), icon: 'remove', title: (edit_reten ? 'No es posible Eliminar Item' : 'Eliminar Item'), type: 'danger', data: function (o) { return o.index; }, attr: { 'tabindex': '-1' }, conditional: function (o) { return !(!$.varValid(o['Pro_Cod']) || o['Pro_Cod'] === '') && !$.isArray(o.Viajes); }, caseFalse: function (o) { return !$.varValid(o['Pro_Cod']) || o['Pro_Cod'] === '' ? ' ' : ($.isArray(o.Viajes) ? $.createIcon('remove grey') : ''); } }, resizable: false }
			]
		}, true, 'itemsPager', { view: false }).gridButtonsAdd([
			{ caption: 'Agregar Productos', buttonicon: 'plus', onClickButton: function () { if (edit_reten) return; if (!available()) { $.alert('No hay espacio para mas items en este documento!'); return; } index = 0; $('#proDialog').dialog('open'); $.Search('pro'); } },
			{ caption: 'Remover Todos', buttonicon: 'remove', onClickButton: function () { if (edit_reten) return; items.clearGrid(); $('#viajesSelectedGrid').clearGrid(); changeIvas(); addItem({}); } },
			{ caption: 'Agregar Destinatario', buttonicon: 'plane', onClickButton: function () { if (edit_reten) return; if (!available()) { $.alert('No hay espacio para mas items en este documento!'); return; } index = 0; $('#lista_datos_extra').Search('#ExtinfoLoadForm', 'LoadExtRutAjax'); $('#destinoCreateDialog').dialog('open'); $.Search('pro'); } },
			{ caption: 'Viajes', buttonicon: 'fa-truck', onClickButton: function () { if (edit_reten) return; $('#viajesSelectedDialog').dialog('open'); }, id: 'viajeSel', classes: 'viajes', css: { display: 'none' } }
		]);

		// Ajustar visibilidad de columnas según código de empresa
		window.ajustarColumnasPorEmpresa = function () {
			var Emp_Cod = $("#Emp_Cod").val();
			if (!Emp_Cod || !items || !items.jqGrid) return;

			if (Emp_Cod == 531 || Emp_Cod == 503) {
				// Empresa 531: mostrar add_imei, ocultar Ice_Por
				if (!Nota_CreDeb) {
					items.jqGrid('showCol', 'add_imei');
				}
				items.jqGrid('hideCol', 'Ice_Por');
			} else {
				// Otras empresas: ocultar add_imei, mostrar Ice_Por
				items.jqGrid('hideCol', 'add_imei');
				items.jqGrid('showCol', 'Ice_Por');
			}
		};

		// Ejecutar ajuste después de inicializar el grid
		setTimeout(function () {
			ajustarColumnasPorEmpresa();
		}, 100);

		// Ajustar cuando cambie el código de empresa
		$("#Emp_Cod").off('change.ajustarColumnas').on('change.ajustarColumnas', function () {
			ajustarColumnasPorEmpresa();
		});

		items.getFootRow(true);
		items.jqGrid('footerData', 'set', {
			//Ite_Lar:'<div class="footerFact formDatos" class="formDatos"><label style="position:relative;text-align: left;">Observaci&oacute;n:</label><textarea id="Vet_Obs" name="Vet_Obs" tabindex="12" class="text" onchange=""></textarea></div><div>&nbsp;</div><div>&nbsp;</div>',
			Ite_Lar: '<div class="footerFact formDatos" class="formDatos"><label style="position:relative;text-align: left;">Observaci&oacute;n:(<span id="contador">m&aacute;ximo 300 caracteres</span>)</label><textarea id="Vet_Obs" name="Vet_Obs" tabindex="12" class="text" onchange="" oninput="contarCaracteres(this)"></textarea></div><div>&nbsp;</div><div>&nbsp;</div>',
			/*	Vet_Pru:'<div class="footerFact"><label>SUBTOTAL:</label><label>TARIFA 0%:</label><label>TARIFA <span class="iva_por"></span>%:</label><label><span class="iva_por"></span>% IVA:</label><label>ICE:</label><label>DESCUENTO:</label><label class="total">TOTAL:</label></div>',
				Vet_Imp:'<div class="footerFact formDatos" id="formTotales"><input id="t_subtotal" name="t_subtotal" type="text" readonly/><input name="t_iva0" type="text" readonly/><input name="t_iva12" type="text" readonly/><input id="t_iva" name="t_iva" type="text" readonly/><input name="t_ice" type="text" readonly/><input id="t_descuento" name="t_descuento" type="text" onchange="updateDocument();" class="text" /><input id="t_rubros" name="t_rubros" type="text"  class="total" readonly/></div>',
				Iva_Por:'<div class="footerFact formDatos"><div style="height:56px;"></div><div style="position:absolute;text-align: left;"><select id="Iva_Cod" name="Iva_Cod" style="max-width:100%;" onchange="changeIvas();" class="text">'+$('#Def_Ivas').html()+'</select></div><div style="height:75px;padding-top:38px;text-align: left;"><input id="Vet_Des" name="Vet_Des" style="height:19px;position:absolute;display:none;" /></div>'
			*/
			Vet_Pru: '<div class="footerFact">' +
				'<label>SUBTOTAL:</label>' +
				'<label>NO OBJ. IVA:</label>' +
				'<label>TARIFA 0%:</label>' +
				'<label>TARIFA <span class="iva_por_5">5%</span>:</label> ' +
				'<label>TARIFA <span class="iva_por">15</span>:</label> ' +
				'<label><span class="iva_por_total"></span>TOTAL IVA:</label><label>ICE:</label>' +
				'<label>DESCUENTO:</label>' +
				/* Casilleros adicionales */
				'<label><input type="checkbox" style="cursor: pointer;transform: scale(1.2);" id="ch_prop" name="ch_prop" onchange="if(this.checked){ if(isNaN(this.value)&&this.value*1===0) this.value=\'0.00\'; } else { $(\'#t_prop\').val(0); } updateDocument();" class="text"> Propina 10%:</label>' +
				'<label class="total">TOTAL:</label>' +
				'</div>',
			Vet_Imp: '<div class="footerFact formDatos" id="formTotales">' +
				'<input id="t_subtotal" name="t_subtotal" type="text" readonly/>' +
				'<input name="t_noiva" type="text" readonly/>' +
				'<input name="t_iva0" type="text" readonly/>' +
				'<input name="t_iva5" type="text" readonly/>' +
				'<input name="t_iva15" type="text" readonly/>' +
				'<input id="t_iva" name="t_iva" type="text" readonly/>' +
				'<input name="t_ice" type="text" readonly/>' +
				'<input id="t_descuento" name="t_descuento" type="text" onchange="updateDocument();" class="text" />' +
				/* Casilleros adicionales */
				'<input id="t_prop" name="t_prop" type="text" onchange="if(isNaN(this.value)&&this.value*1===0) this.value=\'0.00\';"  class="text"  />' +
				'<input id="t_rubros" name="t_rubros" type="text"  class="total" readonly/>' +
				'</div>',
			Iva_Por: '<div class="footerFact formDatos">' +
				'<div style="height:56px;"></div>' +
				'<div style="position:absolute;text-align: left;">' +
				'<select id="Iva_Cod" name="Iva_Cod" style="max-width:100%;" onchange="changeIvas();" class="text">' + $('#Def_Ivas').html() + '</select>' +
				'</div><div style="height:75px;padding-top:38px;text-align: left;">' +
				'<input id="Vet_Des" name="Vet_Des" style="height:19px;position:absolute;display:none;" />' +
				'</div>'
		}, false);

		$.fn.fmatter.impRenta = function (cv, opts, cObjt) {
			if (!$.varValid(cObjt['Pro_Cod']) || cObjt['Pro_Cod'] === '') return '';
			return getRentaButton(cv, { tipo: 'R', index: cObjt['index'] }, cObjt);
		};
		$.fn.fmatter.impRenta.unformat = $.unformatCellHtml;
		$.fn.fmatter.imeiSelector = function (cv, opts, cObjt) {
			if (!cObjt || !$.varValid(cObjt['Pro_Cod']) || cObjt['Pro_Cod'] === '' || cObjt['Pro_Cod'] === '0') return '';

			var valid = (cObjt['Ime_Cod'] !== undefined && cObjt['Ime_Cod'] !== null && cObjt['Ime_Cod'] !== '' && cObjt['Ime_Cod'] !== '0' && cObjt['Ime_Cod'] !== 'undefined');
			var rowId = (cObjt['index'] !== undefined && cObjt['index'] !== null) ? cObjt['index'] : (opts && opts.rowId ? opts.rowId : '');
			var Pro_Cod = (cObjt['Pro_Cod'] !== undefined && cObjt['Pro_Cod'] !== null) ? String(cObjt['Pro_Cod']) : '';
			var Ime_Num = (cObjt['Ime_Num'] !== undefined && cObjt['Ime_Num'] !== null && cObjt['Ime_Num'] !== '' && cObjt['Ime_Num'] !== 'undefined') ? String(cObjt['Ime_Num']) : '';

			// Botón siempre activo para abrir el modal
			var obj = $('<div class="input-group input-group-xs imei-selector-container" data-pro-cod="' + Pro_Cod + '" data-row-id="' + rowId + '">' +
				'<span type="text" id="imei_num_' + rowId + '" class="form-control center" title="' + (valid && Ime_Num ? Ime_Num : 'Sin IMEI seleccionado') + '">' + (valid && Ime_Num ? Ime_Num : '') + '</span>' +
				'<span class="input-group-btn">' +
				'<button type="button" onclick="abrirModalImei(' + rowId + ');" class="btn btn-' + (valid ? 'warning' : 'info') + ' btn-imei-selector" title="' + (valid ? 'Cambiar' : 'Seleccionar') + ' IMEI" tabindex="-1">' +
				'<i class="glyphicon glyphicon-' + (valid ? 'edit' : 'plus') + '"></i>' +
				'</button>' +
				'</span>' +
				'</div>');

			return obj.prop('outerHTML');
		};
		$.fn.fmatter.imeiSelector.unformat = $.unformatCellHtml;
		$.fn.fmatter.retIva = function (cv, opts, cObjt) { if (!$.varValid(cObjt['Pro_Cod']) || cObjt['Pro_Cod'] === '') return ''; if (cObjt['Iva_Por'] * 1 === 0) return ''; return getRentaButton(cv, { tipo: 'I', index: cObjt['index'] }, cObjt); };
		$.fn.fmatter.retIva.unformat = $.unformatCellHtml;
		addItem({});
		changeIvas();
		var cont = 0;

		$('#Cli_Btn, #Rgt_Btn').on('click', function () {
			$('#Prf_Num').text('');
			$('#Prf_Cod').text('');
			$('#FPrfNum').hide();
			$('#items').clearGrid(); //Esto es lo que limpia la factura al editar
			updateDocument();
			totalAndDescuentoACero();
			console.log(cont); if (cont <= 1) { addItem({}); cont++; } else { addItem({}); cont = 0; }
		});
		$('#prof_btn').on('click', function () { $('#FPrfNum').removeAttr("style"); });
		//$('#Prf_Num').text(prf['Prf_Num']);
		//$('#Prf_Cod').text(prf['Prf_Cod']);
		$('#monto_pago,#saldo_pago').on('keyup', function () {
			// var mon = $('#monto_pago').val(), sal = $('#saldo_pago').val(), cam = (!isNaN(mon) && !isNaN(sal) ? $.round(mon) - $.round(sal) : 0);
			var monStr = $('#monto_pago').val(), salStr = $('#saldo_pago').val();
			var mon = (monStr === '' || isNaN(monStr)) ? 0 : parseFloat(monStr);
			var sal = (salStr === '' || isNaN(salStr)) ? 0 : parseFloat(salStr);

			// Si se está digitando el saldo_pago, auto-completar el monto_pago con el mismo valor
			if ($(this).attr('id') === 'saldo_pago' && sal > 0) {
				$('#monto_pago').val($.toFixed(sal));
				mon = sal;
			}

			var cam = (sal > 0 && mon > 0) ? ($.round(mon) - $.round(sal)) : 0;
			// Asegurar que si ambos valores son iguales, el cambio sea 0
			if (Math.abs(cam) < 0.01) cam = 0;
			$('#cambio_pago').val($.toFixed(cam));
			$('#cam_sal').removeClass('alert-danger alert-success').addClass(cam < 0 ? 'alert-danger' : 'alert-success').find('b').html(cam < 0 ? 'Por Cobrar' : 'Cambio');
		}).on('change', function () {
			var monto = $(this).attr('id') === 'monto_pago', val = $(this).val(), sal = $('#Val_Pcc').val() * 1;
			// Si se está cambiando el saldo_pago, actualizar el monto_pago al mismo valor
			if (!monto && $(this).attr('id') === 'saldo_pago') {
				var salVal = (isNaN(val) || val === '') ? 0 : parseFloat(val);
				if (salVal > 0) {
					$('#monto_pago').val($.toFixed(salVal));
				}
			}
			$(this).val(isNaN(val) || val === '' ?
				(monto ? '' : $.toFixed(sal)) :
				(monto ?
					$.toFixed(val) :
					(val > sal ?
						$.toFixed(sal) :
						$.toFixed(val)
					)
				)
			).trigger('keyup');
		});

		//VALIDAR FORMATO PARA VARIOS DECIMALES
		function dynamicFormatter(cellValue, options, rowObject) {
			//console.log("valor: Emp_Cod:"+$("#Emp_Cod").val());
			if (cellValue == null) {
				cellValue = 0; // Valor predeterminado para celdas vacías o nulas
			}
			var useCurrencyFormat = true; // Cambia esta variable según tu lógica
			var Emp_Cod = $("#Emp_Cod").val();
			if (Emp_Cod == 534 || Emp_Cod == 531 || Emp_Cod == 44 || Emp_Cod == 340 || Emp_Cod == 554) {
				useCurrencyFormat = false;
			}
			if (useCurrencyFormat) {
				var formattedValue = parseFloat(cellValue).toFixed(2); // Ajusta el número de decimales si es necesario
				return formattedValue; // Devuelve el valor con dos decimales, sin símbolo de moneda
			} else {
				var numberOfDecimals = 5; // Cambia este valor según el número de decimales que necesitas
				var formattedValue = parseFloat(cellValue).toFixed(numberOfDecimals); // Ajusta los decimales
				return formattedValue; // Añade separadores de miles
			}
		}

		//Change para obtener el número de secuencia y validar el tamanio del documento
		$('#Tic_Cod').change(function () {
			if (Nota_CreDeb || Mod_Nota_CreDeb) {
				doc_ventas.clearGrid();
			}
			var Tic_Sri = $('#Tic_Cod').find('option:selected').data('ticsri') * 1, rise = (Tic_Sri === 2 || Tic_Sri === 9);
			var max = $('#Tic_Cod').find('option:selected').data('autima'), its = items.jqGrid('getDataIDs').length;
			var width = items.jqGrid('getGridParam', 'width');

			if (Tic_Sri === 0 && pago_min > 0 && !init_load) {
				$('#Tic_Cod').val(Tic_Cod_Previo).trigger('change');
			}

			if ($(this).val() * 1 > 0) {
				var tic_cod_sel = $(this).find('option:selected');
				if (tic_cod_sel.data('ticsri') * 1 === 0 && Cof_Con === 'S') {
					$('#div_check_comp').removeClass('hidden');
				} else {
					$('#div_check_comp').addClass('hidden');
				}

				var dias_aviso = tic_cod_sel.data('autadv') * 1;
				var fecha_caduca = moment(tic_cod_sel.data('autcad'));
				var numero_aviso = tic_cod_sel.data('autads') * 1;
				$.post('', { 'getDateServ': true }, function (response) {
					var dias_dif = fecha_caduca.diff(response['hoy'], 'days');
					var documento_sel = tic_cod_sel.text().split('-')[1];
					if (dias_dif <= dias_aviso && Nota_CreDeb === false) {//dias de aviso
						alertaAuto(`Su bloc de <b> ${documento_sel}S</b> caduca en <b>${dias_dif} dias </b> `, '#Tic_Cod', 'left_top');
					}
				}, 'json').fail(function () { $.alert(); });

			} else {
				$('#div_check_comp').addClass('hidden');
			}

			if (rise || Tic_Sri === 0 || Tic_Sri === 4 || Tic_Sri === 5) {
				items.jqGrid('hideCol', 'Ret_Ren_Sri');
				items.jqGrid('hideCol', 'Iva_Ren_Sri');
			} else {
				items.jqGrid('showCol', 'Ret_Ren_Sri');
				items.jqGrid('showCol', 'Iva_Ren_Sri');
			}
			items.jqGrid('setGridWidth', width, true);
			if (!available()) {
				if (its !== max) {
					$.createDialogConfirm('Se eliminar&aacute;n los items excedentes en este tipo de documento, �Desea cambiar el Tipo de Documento?', null,
						function () {
							var dataIDs = items.getDataIDs();
							for (var i = max; i <= its; i++) { items.jqGrid('delRowData', dataIDs[i]); }
						},
						function () {
							$('#Tic_Cod').val(Tic_Cod_Previo).trigger('change');
						});
				}
			} else {
				Tic_Cod_Previo = $('#Tic_Cod').val();
			}
			validarNum(vet_num_ant);
			//changeIvas(); //Verificar si se debe activar este metodo
			updateDocument();
		});

		$('.search_pec[name=Pec_Cod]').on('change', function () { $('#serachDocDorm').find('[name=fecha_inicio]').val($(this).find('option:selected').data('inicio')); $('#serachDocDorm').find('[name=fecha_fin]').val($(this).find('option:selected').data('fin'));/*$('input[name=order]').val($(this).val());*/ });
		$('#Caj_Fec').change(function () {
			cargarDocumentos();
			setDefaultIva();
			$('#Cpc_Ven').datepicker('option', 'minDate', $('#Caj_Fec').val());
		});
		$('.datepickers').createDatePickers({ checkAvailability: true, hideMsg: false }).mask('9999-99-99', { placeholder: '_' });
		getDataVendedor();
		$('#Ret_Num').mask('999-999-999999999');
		$('#Ret_Num').change(function () {
			if ($(this).val() * 1 <= 0)
				$('#Ret_Num').fieldValid();
			else
				$('#Ret_Num').fieldValid(true);
		});
	});
}
var Tic_Cod_Previo;
//Función para setear el datepicker al periodo seleccionado
function fechas(inicio, fin, placod) {
	$('#Caj_Fec').dateLimits(inicio, fin);
	$('.placod').val(placod);
	cargarDocumentos();
}
function cambioTipoDoc(i = null) {
	var op = $('input:radio[name=Vet_Ide]:checked').val();
	if ($('input[name="Prs_Ced"]').val() !== '') {

		if (op == '4') if ($('input[name="Prs_Ced"]').val().length < 13) $('input[name="Prs_Ced"]').val($('input[name="Prs_Ced"]').val() + '001');
		if (op == '5') if ($('input[name="Prs_Ced"]').val().length > 10) $('input[name="Prs_Ced"]').val($('input[name="Prs_Ced"]').val().substring(0, 10));
		if (op == '6' || op == '8') if ($('input[name="Prs_Ced"]').val().length > 13) $('input[name="Prs_Ced"]').val($('input[name="Prs_Ced"]').val().substring(0, 13));
	}
}
// function habilitar(op, val) {
// 	var lon_ced = $('#Prs_Ced').val().length; $('#Prs_Ced').fieldValid('');
// 	if (op === 'ec') {
// 		$('#Ide_Cod').find('option').show();
// 		$('#Ide_Cod').attr('disabled', true);
// 		$('#Ide_Cod').val(lon_ced === 10 ? 2 : 1);
// 	} else {
// 		$('#Ide_Cod').find('option').hide().end().find('option[data-tipo="Ex"]').show();
// 		$('#Ide_Cod').val(val);
// 		$('#Ide_Cod').attr('disabled', false);
// 	}
// }

// var err = 0;
// function validar(op) {
// 	var cedula = $('#Prs_Ced').val();
// 	switch (op) {
// 		case 1:
// 			if (ValidacionCedulaRucService.esIdentificacionValida(cedula)['success'] && ValidacionCedulaRucService.esIdentificacionValida(this.value)['tipo_abrev'] !== 'PA') { err = 0; $('#Ide_Cod').val(cedula.length === 10 ? 2 : 1); $('#Prs_Ced').fieldValid(true); searchCliente(cedula, 'ec'); } else { searchCliente(cedula, 'ex'); $('#Ide_Cod').val(3); }
// 			break;
// 	}
// }

// function validaNoIdentif(number) {
// 	var digitos = number.split(""), dto = digitos.length, acu = 0, resp = { success: false, message: '' },
// 		coef = { 'NA': [2, 1, 2, 1, 2, 1, 2, 1, 2], 'PU': [3, 2, 7, 6, 5, 4, 3, 2, 0], 'PR': [4, 3, 2, 7, 6, 5, 4, 3, 2] }, modulo, acum = 0;
// 	if (dto === 0) resp['message'] = 'No has ingresado ning\u00fan dato!';
// 	else {
// 		for (var i = 0; i < dto; i++) if (!isNaN(digitos[i])) { digitos[i] = digitos[i] * 1; acu = acu + 1; }
// 		if (acu === dto) {
// 			var tipo = digitos[2];
// 			if (tipo === 7 || tipo === 8) resp['message'] = '"El tercer d\u00edgito ingresado es inv\u00e1lido"'; else { tipo = (tipo <= 6 ? 'NA' : (tipo === 6 ? 'PU' : (tipo === 9 ? 'PR' : ''))); modulo = (tipo === 'NA' ? 10 : 11); resp['tipo_abrev'] = tipo; resp['tipo'] = (tipo === 'NA' ? 'Natural' : (tipo === 'PR' ? 'Privada' : (tipo === 'PU' ? 'P\u00fablica' : ''))); }
// 			if (dto !== 10 && dto !== 13) { resp['message'] = 'La cantidad de d\u00EDgitos deben ser 10 o 13'; return resp; } else { resp['doc_abr'] = (dto === 10 ? 'C' : (dto === 13 ? 'R' : '')); resp['doc'] = (dto === 10 ? 'C\u00E9dula' : (dto === 13 ? 'R.U.C.' : '')); }
// 			if (number.substring(0, 2) * 1 > 24) resp['message'] = 'Los dos primeros d\u00EDgitos no pueden ser mayores a 24.';
// 			if (dto === 13) {
// 				if (number.substring(10, 13) !== '001') resp['message'] = 'Los tres \u00faltimos d\u00EDgitos no tienen el c\u00F3digo del RUC 001.';
// 				if (tipo === 'PU' && number.substring(9, 13) !== '0001') resp['message'] = 'El R.U.C. de la empresa del sector p\u00fablico debe terminar con 0001';
// 			} else if ((tipo === 'PU' || tipo === 'PR')) resp['message'] = 'El R.U.C. de las empresas ' + resp['tipo'] + 's deben tener 13 digitos!';
// 			if (resp['message'].length > 0) return resp;

// 			for (var a = 0; a < 9; a++) {
// 				var resul = digitos[a] * coef[tipo][a];
// 				acum += (resul - (tipo === 'NA' && resul >= 10 ? 9 : 0));
// 			}
// 			var residuo = acum % modulo, digitoVerificador = residuo === 0 ? 0 : modulo - residuo;
// 			if (digitos[(tipo === 'PU' ? 8 : 9)] !== digitoVerificador) resp['message'] = 'El n\u00famero de ' + resp['doc'] + ' de la ' + (tipo === 'NA' ? 'Persona Natural' : 'Empresa ' + resp['tipo']) + ' ingresado es inv\u00E1lido!';

// 			if (resp['message'].length === 0) resp['success'] = true;
// 		} else resp['message'] = "ERROR: Solo debe contener d\u00EDgitos!";
// 	}
// 	return resp;
// }

// function clear() {
// 	$('#clieCreateForm').setData({ Cli_Tic: 'N', Prs_Ciu: 'Ec', Prs_Sex: 'M' });
// 	$('#Prs_Ced').val('').focus();
// 	$('.juridico').hide(); $('.natural').show();
// }

$(function () {
	if ($('#prfDialog').length > 0)
		$.createSearchDialog('prfDialog', [
			{ label: 'Cod.Int', name: "Prf_Cod", align: "center", hidden: false, key: true, width: 2 },
			{ label: 'Fecha', name: 'Prf_Fec', align: "center", width: 4 },
			{ label: 'Proform.', name: 'Prf_Num', align: "center", width: 3 },
			{ label: 'CI/RUC', name: 'Prs_Ced', align: "center", width: 6 },
			{ label: 'Cliente', name: 'Cliente', align: "left", width: 17 },
			{ label: 'Obser.', name: 'Prf_Obs', hidden: true, align: "center", width: 10 },
			{ label: 'Estado', name: 'Prf_Est', align: "center", width: 5, hidden: true, title: true },
			{ label: 'Vendedor', name: 'Vnd_Cod', align: "center", width: 5, hidden: true, title: false },
			{ label: $.createIcon('home'), name: 'actReg', align: "center", width: 2, formatter: 'gridButton', formatoptions: { action: verProforma, conditional: function (o) { return o.Prf_Est !== 'Inactiva'; }, icon: 'arrow-right', type: 'success', title: 'Ver proforma' } },
		], null, null, null, { headertitles: true }, { title: 'Proforma' });
});

function verProforma(row) {
	$('#clieFormTemp').setData($.extend(row, { op_opciones: 'c' }));
	$('#Cliente').text(row['Cliente']);
	$('#prfDialog').dialog('close');
	$('input:text[name=t_descuento]').val(parseFloat(row['Prf_Des']).toFixed(2));
	obtenerDetalle(row);
}

//items.jqGrid('addRowData',next,$.extend(item,{index:next,Vet_Can:Vet_Can,Vet_Pru:Vet_Pru}),'last');
function totalAndDescuentoACero() {
	$('input:text[name=t_descuento], input:text[name=t_rubros]').val(parseFloat('0.00').toFixed(2));
	$('#t_subtotal').html(parseFloat('0.00').toFixed(2));
	$('#Vet_Obs').html('');
}
function obtenerDetalle(prf) {
	console.log(prf);
	$('#items').clearGrid();
	var valorSubTotal = 0;
	var porcentajePrf = 0;
	var next = $("#items").jqGrid('getCol', 'index', false, 'max');
	next = (isNaN(next) ? 1 : next + 1);
	var campoInf = '';
	if (Cof_Con === 'S') {
		$.getDataJson("", { profDetalleAjax: true, Prf_Cod: prf.Prf_Cod, Vnd_Cod: prf.Vnd_Cod }, function (resProformas) {
			resProformas.todasPrf.forEach(function (valor) {
				console.log(valor['Pld_Cod']);
				if (valor['Pld_Cod'] != null && valor['Pld_Cod'] > 0) {
					console.log(valor);
					$("#items").jqGrid('addRowData', next, $.extend(valor, { index: next, Des_Adi: valor['Prf_Adi'], Vet_Can: valor['Prf_Cant'], Ite_Lar: valor['Ite_Lar'], Vet_Pru: valor['Prf_Pru'], Vet_Imp: parseFloat(valor['Prf_Imp']).toFixed(2), Pld_Cdc: valor['Pld_Cdc'], Adq_Cor: valor['Adq_Cor'] }), 'last');
				}
				else {
					campoInf = campoInf + '<li>' + valor['Ite_Lar'] + '</li>';
					//$.alert('El producto <u>' + valor['Ite_Lar'] + '</u> no tiene asignada <i>una cuenta contable</i>!', null, 'remove');
					//return;
				}
				next++;
			});
			var valorTotal = (valorSubTotal - parseFloat(prf['Prf_Des'])).toFixed(2);
			if (campoInf != "") {
				$.alert('Los productos <u>' + campoInf + '</u> no tiene asignada <i>una cuenta contable</i>!', null, 'remove');
			}
			//$('#t_subtotal').html(valorSubTotal.toFixed(2));
			//$('#t_rubros').html(valorTotal);
			$('#Vet_Obs').html(prf['Prf_Obs']);
			$('#Prf_Num').text(prf['Prf_Num']);
			$('#Prf_Cod').text(prf['Prf_Cod']);
			//$('.iva_por').html(porcentajePrf);
			$('#items').startGridEdit();
			addItem({});
			updateDocument();
		});
	} else {
		if (Cof_Con === 'N') {
			$.getDataJson("", { profDetalleAjaxSC: true, Prf_Cod: prf.Prf_Cod }, function (resProformas) {
				resProformas.prfSinCuenta.forEach(function (valor) {
					console.log(Cof_Con);
					console.log(valor);
					$("#items").jqGrid('addRowData', next, $.extend(valor, { index: next, Des_Adi: valor['Prf_Adi'], Vet_Can: valor['Prf_Cant'], Ite_Lar: valor['Ite_Lar'], Vet_Pru: valor['Prf_Pru'], Vet_Imp: parseFloat(valor['Prf_Imp']).toFixed(2) }), 'last');
				});
				var valorTotal = (valorSubTotal - parseFloat(prf['Prf_Des'])).toFixed(2);
				//$('#t_subtotal').html(valorSubTotal.toFixed(2));
				//$('#t_rubros').html(valorTotal);
				$('#Vet_Obs').html(prf['Prf_Obs']);
				$('#Prf_Num').text(prf['Prf_Num']);
				$('#Prf_Cod').text(prf['Prf_Cod']);
				//$('.iva_por').html(porcentajePrf);
				$('#items').startGridEdit();
				addItem({});
				updateDocument();
			});
		}
	}
}

function pruebaCallBack(callbackObtener) {
	callbackObtener();
}

function obtenerOtrosPrf() {
	var otros = detCall;
	$.getDataJson("", { prfAdicionalAjax: true, Pro_Cod: otros.Pro_Cod }, function (detCargaPrf) {
		detCargaPrf.otroDetalle.forEach(function (detal) {
			$("#items").changeRow();
		});
	});

}

function mostrarVentas() {
	var exi_contado = false;
	$.each(doc_ventas.getCol('For_Cod'), function (index, forma_codigo) {
		//console.log('forma_recuperado',forma_codigo);
		if (forma_codigo * 1 === 1 || $('#Tic_Cod').val() * 1 === 5) {
			$.alert('Solo un Documento de Este Tipo');
			exi_contado = true;
		}
	});
	if (!exi_contado) {
		$('#Caja_Fecha').val($('#Caj_Fec').val());
		$('#ventasDialog').dialog('open');
		$.Search('ventas');
	}
}

function cargarDocumentos(ticod) {
	var html, fecha = $('#Caj_Fec').val();
	$('#formDocumento').setData({ Pun_Sri: '', Vet_Num: '', Aut_Sri: '' }, false);
	$('#trans_carga').css('display', 'none');
	let tico_valor = $('#Tic_Cod').val() * 1;
	if (editDoc === false) {
		$('#formDocumento').setData({ Pun_Sri: '', Vet_Num: '', Aut_Sri: '' }, false);
		html += '<option value="">Seleccione...</option>';
		$.each(array_documentos, function (i, v) {
			if (fecha >= v['Aut_Fci'] && fecha <= v['Aut_Cad']) {
				html += '<option value=' + v['Tic_Cod'] + ' data-autads=' + v['Aut_Ads'] + ' data-autadv=' + v['Aut_Adv'] + ' data-ticcod=' + v['Tic_Cod'] + ' data-ticsri=' + v['Tic_Sri'] + ' data-puncod=' + v['Pun_Cod'] + ' data-autcod=' + v['Aut_Cod'] + ' data-autsri=' + v['Aut_Sri'] + ' data-auttem=' + v['Aut_Tem'] + ' data-autima=' + v['Aut_Ima'] + ' data-punsri=' + v['Pun_Sri'] + ' data-sucsri=' + v['Suc_Sri'] + ' data-autini=' + v['Aut_Ini'] + ' data-autfin=' + v['Aut_Fin'] + ' data-autfci=' + v['Aut_Fci'] + ' data-ticdes="' + v['Tic_Des'] + '" data-autcad=' + v['Aut_Cad'] + '>' + v['Tic_Sri'] + ' - ' + v['Tic_Des'] + '</option>';
			}
		});
		$('#Tic_Cod').html(html);
		if (tico_valor > 0) $('#Tic_Cod').val(tico_valor).trigger('change');
	}
	else {

		$.post('', { 'Aut_Cod': AutCod, 'edit_doc': editDoc, 'Tic_Cod': TicCod, cargarDocumentos: true }, function (response) {
			html += '<option value="">Seleccione...</option>';
			$.each(response, function (i, v) {
				if (fecha >= v['Aut_Fci'] && fecha <= v['Aut_Cad']) {
					html += '<option value=' + v['Tic_Cod'] + ' data-autads=' + v['Aut_Ads'] + ' data-autadv=' + v['Aut_Adv'] + ' data-ticcod=' + v['Tic_Cod'] + ' data-ticsri=' + v['Tic_Sri'] + ' data-puncod=' + v['Pun_Cod'] + ' data-autcod=' + v['Aut_Cod'] + ' data-autsri=' + v['Aut_Sri'] + ' data-auttem=' + v['Aut_Tem'] + ' data-autima=' + v['Aut_Ima'] + ' data-punsri=' + v['Pun_Sri'] + ' data-sucsri=' + v['Suc_Sri'] + ' data-autini=' + v['Aut_Ini'] + ' data-autfin=' + v['Aut_Fin'] + ' data-autfci=' + v['Aut_Fci'] + 'data-ticdes="' + v['Tic_Des'] + '" data-autcad=' + v['Aut_Cad'] + '>' + v['Tic_Sri'] + ' - ' + v['Tic_Des'] + '</option>';
				}
			});
			$('#Tic_Cod').html(html);
			if (tico_valor > 0) $('#Tic_Cod').val(tico_valor).trigger('change');
			if ($.varValid(ticod)) {
				$('#Tic_Cod').val(ticod).trigger('change');
			}
		}, 'json').fail(function () { $.alert(); });
	}
}

//Función para obtener el número de secuencia y validar el mismo
var num_old;
function validarNum(vet_num_ant) {
	if ($('#Tic_Cod').val() * 1 > 0) {
		var vet_num = $('#Vet_Num').val(), sel_tcompr = $('#Tic_Cod').find('option:selected').data();
		var documento_sel = $('#Tic_Cod').find('option:selected').text().split('-')[1];
		$.post('', { 'Tic_Cod': sel_tcompr['ticcod'], 'Aut_Sri': sel_tcompr['autsri'], 'Pun_Sri': sel_tcompr['punsri'], 'Aut_Cod': sel_tcompr['autcod'], 'numeroSec': true }, function (response) {
			var vnum = ((editDoc) ? (!$.vv(response['Aut_Cod']) || AutCod * 1 === response['Aut_Cod'] * 1 ? vet_num_ant : response['Vet_Num']) : response['Vet_Num']);
			$('#formDocumento').setData({ 'Pun_Sri': sel_tcompr['sucsri'] + '-' + sel_tcompr['punsri'] + '-', 'Vet_Num': vnum, 'Aut_Sri': (sel_tcompr['auttem'] === 'E' ? 'Electr&oacute;nica' : sel_tcompr['autsri']) }, false);
			//$('#formDocumento').setData({'Pun_Sri':sel_tcompr['sucsri']+'-'+sel_tcompr['punsri']+'-','Vet_Num':((editDoc)?vet_num_ant:response['Vet_Num']),'Aut_Sri':(sel_tcompr['auttem']==='E'?'Electr&oacute;nica':sel_tcompr['autsri'])},false);
			var doc_disponibles = (sel_tcompr['autfin'] * 1 - sel_tcompr['autini'] * 1) - response['contador'];
			if (doc_disponibles <= sel_tcompr['autads'] * 1 && Nota_CreDeb === false)
				alertaAuto(`Quedan <b>${doc_disponibles} ${documento_sel}S</b> disponibles`, '#Vet_Num', 'right');
			if (!$.isEmpty(response['Veh_Cod'])) {
				let $vehi = $('#Veh_Cod'); $vehi.empty();
				var nom;
				$('#trans_carga').show();
				$.each(response['Veh_Cod'], function (index, row) {
					$vehi.append($('<option>', { value: row.Veh_Cod, text: 'Placa: ' + row.Veh_Pla, }));
					nom = row.Ext_Nom;
				});
				$('#Ext_Nom').html(nom);
			} else $('#trans_carga').css('display', 'none');
			validarTic_Cod(true);
			num_old = response.Vet_Num;
		}, 'json').fail(function () { $.alert(); });
	}
}

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


function validarTic_Cod(generado = false) {
	var vet_num = $('#Vet_Num').val(), sel_tcompr = $('#Tic_Cod').find('option:selected').data();
	var valid = true;
	if ((vet_num) !== '') {
		if (vet_num < sel_tcompr['autini'] || vet_num > sel_tcompr['autfin']) {
			if (!generado) {
				$('#Vet_Num').fieldValid(false, 'El n&uacute;mero ' + vet_num + ' no esta en el rango (' + sel_tcompr['autini'] + ' - ' + sel_tcompr['autfin'] + ')');
				valid = false;
			} else {
				$('#Vet_Num').fieldValid('warning', 'No tiene mas documentos de este tipo');
				$('#Vet_Num').val('');
				valid = false;
			}
			return false;
		} else {
			$.post('', { 'Aut_Sri': sel_tcompr['autsri'], 'Vet_Num': vet_num, 'Pun_Sri': sel_tcompr['punsri'], 'existeNumdoc': true }, function (response) {
				if (response['existe'] === true && vet_num !== vet_num_ant) {
					$('#Vet_Num').fieldValid(false, 'El n&uacute;mero ' + vet_num + ' ya se encuentra registrado');
					valid = false;
				} else {
					$('#Vet_Num').fieldValid(true);
					valid = true;
				}
			}, 'json').fail(function () { $.alert(); });
		}
	} else {
		$('#Vet_Num').fieldValid(false, 'Escriba un Numero de Documento');
		valid = false;
	}
	return valid;
}


var array_contado = [], array_credito = [];
function cuentas(Pla_Cod) {
	$.post('', { 'Pla_Cod': Pla_Cod, buscarCuentas: true }, function (response) {
		array_contado = response['contado'];
		array_credito = response['credito'];
	}, 'json').fail(function () { $.alert(); });
}

function checkCuentaPago(Pld_Cod) {
	if ($('#Cop_Fec').val() === '' || $('#For_Cod').val() === '' || Cof_Con === 'N') return;
	$('#Pag_Pld').attr('disabled', 'disabled');
	$.post('', { cuentasPago: true, For_Cod: $('#For_Cod').val(), Cop_Fec: $('#Cop_Fec').val(), Pld_Cod: Pld_Cod }, function (response) {
		if (response['success'] === true) {
			if (response['total'] > 0) {
				$('#Pag_Pld').html(response['cuentas']);
			} else { $('#Pag_Pld').val('').html(''); $.alert('Error al buscar la cuenta pago para la fecha indicada'); }
		}
	}, 'json').fail(function () { $.alert('Error al buscar el IVA para la fecha indicada'); })
		.always(function () { if (!$.varValid($('#Pag_Pld').data('disabled')) || $('#Pag_Pld').data('disabled') === false) $('#Pag_Pld').removeAttr('disabled'); });
}

// Selecciona Producto
// function selectItem(item){
// 	var lastId=gridFact.jqGrid('getCol','index',false,'max'), close=true;
// 	if(index===0){ index=lastId; close=false; }
// 	gridFact.changeRow(index,$.extend(item,item['Iva_Por']*1>0?{Iva_Cod:$('#Iva_Cod').val(), Iva_Por:$('#Iva_Cod option:selected').data('ivapor'),Cop_Ice:null}:{Iva_Ren_Cod:'',Iva_Ren_Con:'',Iva_Ren_Por:'',Iva_Ren_Sri:'',Cop_Ice:null}));
// 	var last=gridFact.jqGrid('getRowData',lastId);
// 	if(last['Pro_Cod']!=='') addItem({});
// 	if(close){ $('#proDialog').dialog('close'); setTimeout(function (){ $('#'+(index)+'_Cop_Can').focus(); },0); }else index=0;
// 	updateDocument();
// }
function deletePago(Vet_Num) {
	pagos.jqGrid('delRowData', Vet_Num);
	pagos.trigger('reloadGrid');
	updateDocument();
}

function deleteDocVenta(Vet_Cod) {
	doc_ventas.jqGrid('delRowData', Vet_Cod);
	doc_ventas.trigger('reloadGrid');
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
	$('#Vet_Obs').val("");
	$('#ch_prop').prop('checked', false);
	$('#t_prop').val("0.00");
	$('#t_noiva').val("0.00");
}

// Guardar un cliente
function guardaCliente() {
	$.saveDataJson('', $('#clieCreateForm').getData('guardaClieAjax'), function (resp) {
		// $('#Ciu_Cod').val(3);
		selectCliente(resp['clie'])
		$('#clieCreateDialog').dialog('close');
		return false;
	});
}


// Valida cedula
function validaNoIdentif(number) {
	var digitos = number.split(""), dto = digitos.length, acu = 0, resp = { success: false, message: '' },
		coef = { 'NA': [2, 1, 2, 1, 2, 1, 2, 1, 2], 'PU': [3, 2, 7, 6, 5, 4, 3, 2, 0], 'PR': [4, 3, 2, 7, 6, 5, 4, 3, 2] }, modulo, acum = 0;
	if (dto === 0) resp['message'] = 'No has ingresado ning\u00fan dato!';
	else {
		for (var i = 0; i < dto; i++) if (!isNaN(digitos[i])) { digitos[i] = digitos[i] * 1; acu = acu + 1; }
		if (acu === dto) {
			var tipo = digitos[2];
			var tercer_digito = tipo;
			if (tipo === 7 || tipo === 8) resp['message'] = '"El tercer d\u00edgito ingresado es inv\u00e1lido"'; else { tipo = (tipo <= 6 ? 'NA' : (tipo === 6 ? 'PU' : (tipo === 9 ? 'PR' : ''))); modulo = (tipo === 'NA' ? 10 : 11); resp['tipo_abrev'] = tipo; resp['tipo'] = (tipo === 'NA' ? 'Natural' : (tipo === 'PR' ? 'Privada' : (tipo === 'PU' ? 'P\u00fablica' : ''))); }
			if (dto !== 10 && dto !== 13) { resp['message'] = 'La cantidad de d\u00EDgitos deben ser 10 o 13'; return resp; } else { resp['doc_abr'] = (dto === 10 ? 'C' : (dto === 13 ? 'R' : '')); resp['doc'] = (dto === 10 ? 'C\u00E9dula' : (dto === 13 ? 'R.U.C.' : '')); }
			if (number.substring(0, 2) * 1 > 24) resp['message'] = 'Los dos primeros d\u00EDgitos no pueden ser mayores a 24.';
			if (dto === 13) {
				if (number.substring(10, 13) !== '001') resp['message'] = 'Los tres \u00faltimos d\u00EDgitos no tienen el c\u00F3digo del RUC 001.';
				if (tipo === 'PU' && number.substring(9, 13) !== '0001') resp['message'] = 'El R.U.C. de la empresa del sector p\u00fablico debe terminar con 0001';
			} else if ((tipo === 'PU' || tipo === 'PR')) resp['message'] = 'El R.U.C. de las empresas ' + resp['tipo'] + 's deben tener 13 digitos!';
			if (resp['message'].length > 0) return resp;

			for (var a = 0; a < 9; a++) {
				var resul = digitos[a] * coef[tipo][a];
				acum += (resul - (tipo === 'NA' && resul >= 10 ? 9 : 0));
			}
			var residuo = acum % modulo, digitoVerificador = residuo === 0 ? 0 : modulo - residuo;
			if (digitos[(tipo === 'PU' ? 8 : 9)] !== digitoVerificador) resp['message'] = 'El n\u00famero de ' + resp['doc'] + ' de la ' + (tipo === 'NA' ? 'Persona Natural' : 'Empresa ' + resp['tipo']) + ' ingresado es inv\u00E1lido!';

			if (resp['message'].length === 0) {
				resp['success'] = true
			} else if ((tipo == "PR" || (tipo == "PU" || tipo == "NA")) && (tercer_digito == 9 || tercer_digito == 6)) {//Validar RUC privado
				resp['success'] = true;
			}

			//if(resp['message'].length===0) resp['success']=true;
		} else resp['message'] = "ERROR: Solo debe contener d\u00EDgitos!";
	}
	return resp;
}
/*
function validaNoIdentif(number) {
	var digitos = number.split(''), dto = digitos.length, acu = 0, resp = { success: false, message: '' },
		coef = { 'NA': [2, 1, 2, 1, 2, 1, 2, 1, 2], 'PU': [3, 2, 7, 6, 5, 4, 3, 2, 0], 'PR': [4, 3, 2, 7, 6, 5, 4, 3, 2] }, modulo, acum = 0;
	if (dto === 0) resp['message'] = 'No has ingresado ning\u00fan dato!';
	else {
		for (var i = 0; i < dto; i++) if (!isNaN(digitos[i])) { digitos[i] = digitos[i] * 1; acu = acu + 1; }
		if (acu === dto) {
			var tipo = digitos[2];
			if (tipo === 7 || tipo === 8) resp['message'] = '"El tercer d\u00edgito ingresado es inv\u00e1lido"'; else { tipo = (tipo < 6 ? 'NA' : (tipo === 6 ? 'PU' : (tipo === 9 ? 'PR' : ''))); modulo = (tipo === 'NA' ? 10 : 11); resp['tipo_abrev'] = tipo; resp['tipo'] = (tipo === 'NA' ? 'Natural' : (tipo === 'PR' ? 'Privada' : (tipo === 'PU' ? 'P\u00fablica' : ''))); }
			if (dto !== 10 && dto !== 13) { resp['message'] = 'La cantidad de d\u00EDgitos deben ser 10 o 13'; return resp; } else { resp['doc_abr'] = (dto === 10 ? 'C' : (dto === 13 ? 'R' : '')); resp['doc'] = (dto === 10 ? 'C\u00E9dula' : (dto === 13 ? 'R.U.C.' : '')); }
			if (number.substring(0, 2) * 1 > 24) resp['message'] = 'Los dos primeros d\u00EDgitos no pueden ser mayores a 24.';
			if (dto === 13) {
				if (number.substring(10, 13) !== '001') resp['message'] = 'Los tres \u00faltimos d\u00EDgitos no tienen el c\u00F3digo del RUC 001.';
				if (tipo === 'PU' && number.substring(9, 13) !== '0001') resp['message'] = 'El R.U.C. de la empresa del sector p\u00fablico debe terminar con 0001';
			} else if ((tipo === 'PU' || tipo === 'PR')) resp['message'] = 'El R.U.C. de las empresas ' + resp['tipo'] + 's deben tener 13 digitos!';
			if (resp['message'].length > 0) return resp;

			for (var a = 0; a < 9; a++) {
				var resul = digitos[a] * coef[tipo][a];
				acum += (resul - (tipo === 'NA' && resul >= 10 ? 9 : 0));
			}
			var residuo = acum % modulo, digitoVerificador = residuo === 0 ? 0 : modulo - residuo;
			if (digitos[(tipo === 'PU' ? 8 : 9)] !== digitoVerificador) resp['message'] = 'El n\u00famero de ' + resp['doc'] + ' de la ' + (tipo === 'NA' ? 'Persona Natural' : 'Empresa ' + resp['tipo']) + ' ingresado es inv\u00E1lido!';

			if (resp['message'].length === 0) resp['success'] = true;
			
		} else resp['message'] = 'ERROR: Solo debe contener d\u00EDgitos!';
	}
	return resp;
}*/

// nuevo bloque de codigo 08/10/2025
$(document).ready(function () {
	var validandoDiaCred = false; // bandera global de control

	$("#Dia_Cred").on("blur", function () {
		// Si ya se está validando, no repetir
		if (validandoDiaCred) return;

		var diasIngresados = parseInt($(this).val(), 10);
		var aux_Cli_Cod = $("input[name='aux_Cli_Cod']").val();

		if (!aux_Cli_Cod) {
			$.alert("Primero debe seleccionar un cliente.");
			$(this).val("0");
			return;
		}

		if (isNaN(diasIngresados) || diasIngresados < 0) {
			$.alert("Debe ingresar un número válido de días.");
			$(this).val("0").focus();
			return;
		}

		// Marcar que empieza la validación
		validandoDiaCred = true;

		$.ajax({
			url: "fac_alt_fac_ven_3.2.php",
			type: "POST",
			dataType: "json",
			data: {
				cupoCredito: true,
				aux_Cli_Cod: aux_Cli_Cod
			},
			success: function (response) {
				console.log("data:", response)
				if (response.success && response.data) {
					var diaCreditoMax = parseInt(response.data.Dia_Cred, 10);
					// console.log("Valor ingresado:", diasIngresados, " | Máximo permitido:", response.data.Dia_Cred);
					if (diasIngresados > diaCreditoMax) {
						$.alert("No se puede exceder los días de crédito para este cliente (" + diaCreditoMax + " días máximo).");
						$("#Dia_Cred").val("0");
						setTimeout(function () { $("#Dia_Cred").focus(); }, 300);
					}
				} else {
					$.alert("No se pudo obtener el cupo de crédito del cliente.");
					$("#Dia_Cred").val("0");
				}
			},
			error: function () {
				$.alert("Error al consultar el cupo de crédito.");
				$("#Dia_Cred").val("0");
			},
			complete: function () {
				// Restablecer bandera al terminar la validación
				validandoDiaCred = false;
			}
		});
	});
});

function searchCliente(ced) {
	// (tipo === 'ec') ? ced = ced.substring(0, 10) : ced;
	$.post('', { provAjax2: true, Prs_Ced: ced.substring(0, 10) }, function (response) {
		console.log(response)
		//if (response['total'] * 1 === 1) {
		//if (response['total'] * 1 == 0) {
		if (!$.varValid(response['rows'][0]['Cli_Cod']) || response['rows'][0]['Cli_Cod'].length === 0) {
			$('#clieCreateForm').setData(response['rows'][0]);

		} else {
			selectCliente(response['rows'][0]);
			$('#clieCreateDialog').dialog('close');
		}
		/*} else {
			$.alert('El cliente ' + ced + ' ya se encuentra registrado..!!');
			clear();
		}*/

	}, 'json').fail(function () { $('#clieCreateForm').setData({}); }).always(function () { });
}

function clear() {
	$('#clieCreateForm').setData({ Cli_Tic: 'N', Prs_Ciu: 'Ec', Prs_Sex: 'M' });
	$('#Prs_Ced').val('').focus();
	$('.juridico').hide(); $('.natural').show();
}

function agregaRetencion(data) {
	var form = $('#codiForm').getData(), ret = {};
	$.each(data, function (k, v) {
		ret[(form['tipo'] === 'R' ? 'Ret_' : 'Iva_') + k] = v;
	});
	ret['select'] = '';
	if (form['checkRentaIva'] === 'N')
		items.changeRow(form['index'], ret);
	else {
		//falla con la ultima fila en edicion de documentos de ventas
		var ids = items.jqGrid('getDataIDs');
		//se eliminó el -1 en la linea z=ids.length
		for (var i = 0, z = ids.length - 1; i < z; i++)
			items.changeRow(ids[i], ret);
	}
	updateDocument();
	calculaRetencion();
	$('#codiDialog').dialog('close');
}

// function agregaRetencion2(data) {
// 	var form = $('#codiForm').getData(), ret = {};
// 	$.each(data, function (k, v) {
// 		ret[(form['tipo'] === 'R' ? 'Ret_' : 'Iva_') + k] = v;
// 	});
// 	ret['select'] = '';
// 	if (form['checkRentaIva'] === 'N')
// 		items.changeRow(form['index'], ret);
// 	else {
// 		//falla con la ultima fila en edicion de documentos de ventas
// 		var ids = items.jqGrid('getDataIDs');
// 		//se eliminó el -1 en la linea z=ids.length
// 		for (var i = 0, z = ids.length; i < z; i++)
// 			items.changeRow(ids[i], ret);
// 	}
// 	updateDocument();
// 	calculaRetencion();
// 	$('#codiDialog').dialog('close');
// }

function remove_accent(str) {
	var accents = '????????????????????????????????SsY??Zz';
	var accentsOut = "AAAAAAaaaaaaOOOOOOOooooooEEEEeeeeeCcDIIIIiiiiUUUUuuuuNnSsYyyZz";
	str = str.split('');
	var strLen = str.length;
	var i, x;
	for (i = 0; i < strLen; i++) {
		if ((x = accents.indexOf(str[i])) != -1) {
			str[i] = accentsOut[x];
		}
	}
	return str.join('');
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
	// nuevo bloque de calculo de dias de plazo cuando es credito
	if (!carga_inicial) {
		var fechaVencimiento = pago['Cpc_Ven'] || $('#Cpc_Ven').val();
		if (fechaVencimiento) {
			var hoy = moment().startOf('day');
			var venc = moment(fechaVencimiento, 'YYYY-MM-DD').startOf('day');
			var diasPlazo = venc.diff(hoy, 'days');
			pago['Vet_Plz'] = diasPlazo;
		} else {
			pago['Vet_Plz'] = '';
		}
	}

	if (carga_inicial && pago['Pag_Pld'] * 1 <= 0) {
		pago['Pag_Pld'] = $('#Pag_Pld').val();
	}
	pagos.jqGrid('addRowData', next, pago);
	pagos.trigger('reloadGrid');
	$('#pagosDialog').dialog('close');
	var pagos_tot = pagos.jqGrid('getCol', 'Vet_Tot', false, 'sum');
	//updateDocument();
	$('#For_Cod').val(1).trigger('change');
	$('.porCobrar').setData({ 'Val_Pcc_2': $.toFixed($('#Val_Pcc').val() - pagos_tot) });

}

function cargarCuentas(txt, pagcod) {
	var selector = $('#Pag_Pld').empty();
	$.post('', { 'buscarCuentas': true, 'Pla_Cod': $('#Pec_Cod').find(':selected').data('placod'), 'Pag_Cod': pagcod }, function (resp) {
		if (resp[txt]) {
			var cuentas = resp[txt];
			$.each(cuentas, function (x, y) {
				if (y['banco'] == 'no') {
					if (($('#Pag_Cod').val() * 1) === (y['Pag_Cod'] * 1))
						selector.append($('<option value=' + y['Pld_Cod'] + ' data-pag_cod=\'' + y['Pag_Cod'] + '\'>' + y['Pld_Des'] + '</option>'));
				}
				if (txt === 'Credito') {
					selector.append($('<option value=' + y['Pld_Cod'] + ' data-pag_cod=\'' + y['Pag_Cod'] + '\'>' + y['Pld_Des'] + '</option>'));
				}
			});
		} else {
			$.alert('No hay cuentas parametrizadas');
		}
	}, 'json').fail(function () { $.alert('error inesperado'); }).always(function () { });
}


function getMayorIvaDoc(items) {
	var ivaPorMayor = 0;
	$.each(items, function (x, row) {
		if (row['Iva_Por'] > ivaPorMayor)
			ivaPorMayor = row['Iva_Por'];
	});
	return ivaPorMayor;
}

// Función para validar que el periodo esté en vigencia
// function validarPeriodoVigente() {
// 	var pecCodSelect = $('#Pec_Cod');
// 	if (pecCodSelect.length === 0 || !pecCodSelect.val()) {
// 		$.alert('¡WARNING...!<br/>Usted no tiene Periodo activo<br/>Por Favor contacte al administrador para su apertura', null, 'alert');
// 		return false;
// 	}

// 	var selectedOption = pecCodSelect.find('option:selected');
// 	if (selectedOption.length === 0) {
// 		$.alert('¡WARNING...!<br/>Usted no tiene Periodo activo<br/>Por Favor contacte al administrador para su apertura', null, 'alert');
// 		return false;
// 	}

// 	var fechaInicio = selectedOption.data('inicio');
// 	var fechaFin = selectedOption.data('fin');

// 	if (!fechaInicio || !fechaFin) {
// 		$.alert('¡WARNING...!<br/>Usted no tiene Periodo activo<br/>Por Favor contacte al administrador para su apertura', null, 'alert');
// 		return false;
// 	}

// 	// Convertir fechas a objetos Date para comparación
// 	var hoy = new Date();
// 	hoy.setHours(0, 0, 0, 0); // Resetear horas para comparar solo fechas

// 	var inicio = new Date(fechaInicio);
// 	inicio.setHours(0, 0, 0, 0);

// 	var fin = new Date(fechaFin);
// 	fin.setHours(23, 59, 59, 999); // Incluir el día completo

// 	// Verificar que la fecha actual esté entre la fecha de inicio y fin
// 	if (hoy < inicio || hoy > fin) {
// 		$.alert('¡WARNING...!<br/>Usted no tiene Periodo activo<br/>Por Favor contacte al administrador para su apertura', null, 'alert');
// 		return false;
// 	}

// 	return true;
// }

function validarPeriodoVigente() {
	var pecCodSelect = $('#Pec_Cod');
	if (pecCodSelect.length === 0 || !pecCodSelect.val()) {
		$.alert('¡WARNING...!<br/>Usted no tiene Periodo activo<br/>Por Favor contacte al administrador para su apertura', null, 'alert');
		return false;
	}
	
	var selectedOption = pecCodSelect.find('option:selected');
	if (selectedOption.length === 0) {
		$.alert('¡WARNING...!<br/>Usted no tiene Periodo activo<br/>Por Favor contacte al administrador para su apertura', null, 'alert');
		return false;
	}
	
	// Verificar si el año actual está disponible en las opciones del select
	var anioActual = new Date().getFullYear();
	var anioActualDisponible = false;
	
	pecCodSelect.find('option').each(function() {
		var textoOpcion = $(this).text().trim();
		var anioOpcion = parseInt(textoOpcion);
		if (anioOpcion === anioActual) {
			anioActualDisponible = true;
			return false; // Salir del each
		}
	});
	
	// Solo mostrar advertencia si el año actual NO está disponible en las opciones
	if (!anioActualDisponible) {
		$.alert('¡WARNING...!<br/>El periodo seleccionado debe ser del año actual (' + anioActual + ')<br/>Por favor seleccione el periodo correspondiente al año ' + anioActual, null, 'alert');
		return false;
	}
	
	return true;
}

// Valida Todo antes de guardar
function validaDocument(editaDocument = false) {
	// Validar periodo activo básico
	var pecCodSelect = $('#Pec_Cod');
	if (pecCodSelect.length === 0 || !pecCodSelect.val()) {
		$.alert('¡WARNING...!<br/>Usted no tiene Periodo activo<br/>Por Favor contacte al administrador para su apertura', null, 'alert');
		return;
	}
	
	var selectedOption = pecCodSelect.find('option:selected');
	if (selectedOption.length === 0) {
		$.alert('¡WARNING...!<br/>Usted no tiene Periodo activo<br/>Por Favor contacte al administrador para su apertura', null, 'alert');
		return;
	}
	
	// Verificar si el año actual está disponible en las opciones del select
	var anioActual = new Date().getFullYear();
	var anioActualDisponible = false;
	
	pecCodSelect.find('option').each(function() {
		var textoOpcion = $(this).text().trim();
		var anioOpcion = parseInt(textoOpcion);
		if (anioOpcion === anioActual) {
			anioActualDisponible = true;
			return false; // Salir del each
		}
	});
	
	// Solo mostrar advertencia si el año actual NO está disponible en las opciones
	if (!anioActualDisponible) {
		$.alert('¡WARNING...!<br/>El periodo seleccionado debe ser del año actual (' + anioActual + ')<br/>Por favor seleccione el periodo correspondiente al año ' + anioActual, null, 'alert');
		return;
	}

	if ($('#t_descuento').val() * 1 === 0) $('#Vet_Des').val(0);
	// if ($('#Tpc_Cod').val() == 0) { $.alert('Debe seleccionar un Tipo de Comprobante - Pago SRI'); return; }//validacion de no dejar en blanco este campo
	if (($('#Val_Pcc_2').val()) * 1 < 0) { $.alert('El <i><u>total de pagos</u></i> no puede Exceder al <i><u>Monto a Pagar</u></i>!<br/>Revise los Datos.', null, 'remove'); return; } //validador para no exceder el monto a pagar
	if (($('#Val_Pcc_2').val()) * 1 > 0) { $.alert('Aun queda saldo pendiente por cobrar!', null, 'remove'); return; }

	// nueva validacion de datos de retencion
	if (!$('#Ret_Num').is(':disabled') && !$('#Ret_Aut_Sri').is(':disabled')) {
		console.log("Esta Habilitado los campos");
		if ($('#Ret_Fec').val() === '' || $('#Ret_Aut_Sri').val() === '' || $('#Ret_Num').val() === '') {
			$.alert('Los datos de la retencion deben ser llenados(Número, Autorización y Fecha)', null, 'remove');
			return;
	 	}
	 }

	var docu = $('.formDatos').getData('saveDocument'), Tic = $('#Tic_Cod option:selected');
	docu['items'] = items.getGridBatch();
	for (var i = 0; i < docu['items'].length; i++) {
		if (Tic.data('ticsri') === 0 || Tic.data('ticsri') === 9 || Tic.data('ticsri') == 2) {
			eliminaRetencion({ 'index': docu['items'][i]['index'], 'tipo': 'R' });
			eliminaRetencion({ 'index': docu['items'][i]['index'], 'tipo': 'I' });
		}
	}



// Obtener items con todos los campos (incluyendo Tickets y Viajes que no están en colModel)
	var allItems = [];
	var itemIds = items.jqGrid('getDataIDs');
	$.each(itemIds, function (idx, rowId) {
		if (items.find('tr#' + rowId).attr('editable') * 1) {
			items.jqGrid('saveRow', rowId, false, 'clientArray');
		}
		var rowData1 = items.jqGrid('getRowData', rowId, true); // true para obtener datos originales
		var rowData2 = items.jqGrid('getLocalRow', rowId); // Obtener desde datos locales si está disponible
		var rowData3 = null;
		try {
			var allData = items.jqGrid('getGridParam', 'data');
			if (allData && $.isArray(allData)) {
				for (var j = 0; j < allData.length; j++) {
					if (allData[j] && (allData[j].index == rowId || allData[j][items.jqGrid('getGridParam', 'keyName') || 'index'] == rowId)) {
						rowData3 = allData[j];
						break;
					}
				}
			}
		} catch (e) {
			console.log('Error obteniendo datos internos del grid:', e);
		}
		// También intentar obtener desde el objeto interno del grid usando el método interno
		if (!rowData3) {
			try {
				var internalData = items.jqGrid('getGridParam', '_index');
				if (internalData && internalData[rowId]) {
					rowData3 = internalData[rowId];
				}
			} catch (e) {
				console.log('Error obteniendo datos desde _index:', e);
			}
		}
		var rowData = $.extend(true, {}, rowData1 || {}, rowData2 || {}, rowData3 || {});
		if (rowData && rowData.Pro_Cod) {
			if (!rowData.Tickets && rowData.Tickets_Originales) {
				rowData.Tickets = rowData.Tickets_Originales;
			}
			if (rowData.Tickets_Originales && !rowData.Tickets) {
				rowData.Tickets = rowData.Tickets_Originales;
			}
			/*if (rowData.Tickets || rowData.Tickets_Originales) {
				console.log('Item con Tickets encontrado al guardar:', rowId, rowData.Tickets || rowData.Tickets_Originales);
				console.log('Datos completos del item al guardar:', rowData);
			} else {
				console.log('ADVERTENCIA: Item sin Tickets al guardar. rowId:', rowId, 'Keys:', Object.keys(rowData));
				console.log('rowData1:', rowData1);
				console.log('rowData2:', rowData2);
				console.log('rowData3:', rowData3);
			}*/
			allItems.push(rowData);
		}
	});

	docu['items'] = allItems;
	//docu['items'] = items.getGridBatch();
	var cant_items = items.jqGrid('getDataIDs').length;
	if (cant_items < 1 || (cant_items <= 1 && !editaDocument)) {
		$.alert('Debe seleccionar al menos un <u>Item</u>!', null, 'remove');
		return;
	}

	items.startGridEdit();
	docu['Tic_Txt'] = $('#Tic_Cod option:selected').text();
	(isNaN(parseInt(docu['items'][docu['items'].length - 1]['Pro_Cod'])) ? docu['items'].splice(docu['items'].length - 1, 1) : '');
	for (var i = 0; i < docu['items'].length; i++) {
		if (docu['items'][i]['Vet_Imp'] * 1 <= 0) {
			$.alert('El producto <u>' + docu['items'][i]['Ite_Lar'] + '</u> no puede tener <i>Importe cero</i>!', null, 'remove');
			return;
		}
	}

	if (editaDocument) {
		docu['Vet_Num_Ant'] = vet_num_ant;
		docu['editDoc'] = $('#editDoc').getData();
		if (pago_min > ($('#Val_Pcc').val() * 1)) { // Validar el que no existan pagos pendientes 
			$('.porCobrar').find('span.input-group-btn').flyout('show').focus();
			$.alert('Existen Pagos Activos por <i class="glyphicon glyphicon-usd">' + pago_min + '</i> revise el valor del documento');
			return;
		}
	}

	if ($('#Ret_Fec').val() == '')
		$('#Ret_Fec').val($('#Caj_Fec').val());
	docu['Veh_Cod'] = $('#Veh_Cod').val();
	docu['Ret_Aut_Sri'] = $('[name=Ret_Aut_Sri]').val();
	docu['Ret_Ren_Tot'] = $('[name=Ret_Ren_Tot]').val();
	docu['rets'] = $('#retencion').getGridBatch();
	docu['Plan_Cod'] = $('#Pec_Cod option:selected').data('placod');
	docu['cliente'] = $('#clieFormTemp [name=cliente]').text();
	docu['pagos'] = pagos.getGridBatch();
	docu['Tic_Sri'] = Tic.data('ticsri');
	docu['Aut_Cod'] = Tic.data('autcod');
	docu['Tpc_Cod'] = $('#Tpc_Cod').val();
	docu['Aut_Tem'] = Tic.data('auttem');
	docu['Pun_Sri'] = Tic.data('punsri');
	docu['Aut_Sri'] = Tic.data('autsri');
	docu['editDocument'] = editaDocument;
	docu['reembolsos'] = null;
	docu['Vet_Ide'] = $('input[name="Vet_Ide"]:checked').val();
	if (!$.isUnd(reembolsos) && reembolsos.length === 1 && $('#Vet_Rem').is(':checked')) {
		if (("0" + $('#t_rubros').val()) * 1 !== $.round(reembolsos.jqGrid('getCol', 'Total', true, 'sum'))) {
			return $.alert('El total de las <u class="blue">Compras de Reembolso</u> no es igual al <u class="blue">Total de Venta</u>!', null, 'alert');
		} else
			docu['reembolsos'] = reembolsos.getGridColumn('Cop_Cod');
	}
	console.log(docu);
	//return $.alert('OK');
    // Advertencia: factura > 500 USD y pago 100% al contado (alta y modificar ventas)
	var totalFactura = parseFloat($('#t_rubros').val()) || 0;
	var pagosList = docu['pagos'] || [];
	var formaContado = function (p) {
		var cod = (p.Forma_Cod !== undefined && String(p.Forma_Cod).trim() !== '') ? (p.Forma_Cod * 1) : (p.For_Cod * 1);
		return cod === 1;
	};
	var todoContado = pagosList.length > 0 && pagosList.every(formaContado);
	var msgConfirm = (editaDocument ? '?Est&aacute;? seguro de editar el Documento?' : '?Est&aacute;? seguro de guardar el Documento?');
	var continuar = function () {
		$.createDialogConfirm(msgConfirm, docu, saveDocument);
	};
	if (totalFactura > 500 && todoContado) {
		$.alert('Si el valor de la factura supera los USD 500, el pago debe realizarse mediante un medio bancarizado para efectos de deducibilidad y cr&eacute;dito tributario. En caso de efectuarse en efectivo, la responsabilidad por posibles sanciones recae en el contribuyente.', continuar, 'warning');
	} else {
		continuar();
	}
	//$.createDialogConfirm((editaDocument ? '?Est&aacute;? seguro de editar el Documento?' : '?Est&aacute;? seguro de guardar el Documento?'), docu, saveDocument);
}

function setDefaultIva() {
	if (ivas_venta.length) {
		var iva_sel = ivas_venta[0]['Iva_Por'];

		var fecha_sel = $('#Caj_Fec').val();
		$.each(ivas_venta, function (i, iva) {
			if (!fechaMayorQue(fecha_sel, iva['Iva_Fin'])) {
				if (!fechaMayorQue(iva['Iva_Ini'], fecha_sel))
					iva_sel = iva['Iva_Por'];
			}
		});

		$('#Iva_Cod').val($('*[data-ivapor="' + iva_sel + '"]').val());
		return iva_sel;
	}
}
// Guardar documento
//data.items=$.jsonParser;
//data.pagos=$.jsonParser;
function saveDocument(data) {
	$.arraySpliceFields(data.items, ['select', 'delete', 'Vet_Index', 'Uni_Des']);
	data.items = $.jsonParser(data.items);
	$.saveDataJson('', data,
		function (resp) {
			if (resp['success']) {
				$('#btnVentaPrint').data('url', resp['Vet_Link']);
				$('#Vet_Impr').data('url', resp['Vet_Impr']);
				$('#resultContent').setData(resp, 'name');
				$('#copForm').setData(resp['Vet_Data']);
				$('#copresult').setRows(resp['Vet_Rows']);

				$('#compForm').setData(resp['Com_Data']);
				$('#btnComPrint').data('url', resp['Com_Link']);
				$('#asiento').setRows(resp['Com_Rows']);

				// $('#compFormRet').setData(resp['Com_Data_Ret']);
				// $('#btnComPrintRet').data('url', resp['Com_Link_Ret']);
				// $('#asientoRet').setRows(resp['Com_Rows_Ret']);

				if ($.vv(resp['Xml_Path'])) {



					$('#frm_pdf_btn').show();
					if (printers) $('.frm_ticket_btn').show();
					$('#urlXml').val(resp['Xml_Path']);
					$('.frm_ticket_btn').data({ xml: resp['xml'], telefonos: '' });



				} else {
					$('#frm_pdf_btn').hide();
					$('.frm_ticket_btn').hide();
				}

				if (!$.varValid(resp['Com_Rows'])) {
					$('#compForm').addClass('hidden');
				} else {
					$('#compForm').removeClass('hidden');
				}

				if ($.varValid(resp['Ret_Rows'])) {
					$('#retForm').removeClass('hidden');
				} else {
					$('#retForm').addClass('hidden');
				}
				$('#retForm').setData(resp['Ret_Data']);
				$('#reteresult').setRows(resp['Ret_Rows']);
				if (data['editDocument']) {
					$('#documentoMain').moveComp('#documentoResult').updateGridsSizes();
					$('select[name=Tic_Cod]').attr('disabled', false);
					$('select[name=Pec_Cod]').attr('disabled', false);
					$('select[name=Cmb_Mes]').attr('disabled', false);
				} else {
					$('.validate').find('i').removeAttr('class');
					$('#Tic_Cod').trigger('change');
					$('#factura').moveComp('#documentoResult').updateGridsSizes();
				}
				// $("#anticipo").css("display", "none");
			}

		});
}








function alContado() {
	$('#For_Cod').val(1).trigger('change');
	// setTimeout(function () {
	// Función para obtener Pag_Pld de forma segura
	var obtenerPagPld = function() {
		var pagPld = '';
		var text = $('#Pag_Cod').find('option:selected').text().toUpperCase();
		
		// Si es TRANSFERENCIA o DEPOSITO, obtener de Ban_Cod
		if (text === 'TRANSFERENCIA' || text === 'DEPOSITO') {
			var banCodSelected = $('#Ban_Cod option:selected');
			if (banCodSelected.length > 0 && banCodSelected.data('pldcod')) {
				pagPld = banCodSelected.data('pldcod');
			}
		}
		
		// Si no se obtuvo de Ban_Cod, obtener de Pag_Pld
		if (!pagPld || pagPld === '') {
			pagPld = $('#Pag_Pld').val();
		}
		
		return pagPld || '';
	};
	
	// Esperar a que las cuentas se carguen
	var intentos = 0;
	var maxIntentos = 20; // Máximo 2 segundos (20 * 100ms)
	
	var verificarYAgregar = function() {
		var pagPld = obtenerPagPld();
		
		// Si Pag_Pld está vacío y aún hay intentos, esperar un poco más
		if ((!pagPld || pagPld === '') && intentos < maxIntentos) {
			intentos++;
			setTimeout(verificarYAgregar, 100);
			return;
		}
		
		// Si después de todos los intentos no hay Pag_Pld, usar el primero disponible o mostrar error
		if (!pagPld || pagPld === '') {
			var primeraOpcion = $('#Pag_Pld option:first').val();
			if (primeraOpcion) {
				pagPld = primeraOpcion;
				$('#Pag_Pld').val(primeraOpcion);
			} else {
				$.alert('Error: No se pudo obtener la cuenta de pago. Por favor, seleccione manualmente el tipo de pago.', null, 'alert');
				return;
			}
		}
		var ite_pagos = [
			$.extend($('#pagosForm').getData(),
				{
					'Tipo_Cod': $('#Pag_Cod option:selected').val(),
					'Forma_Cod': $('#For_Cod option:selected').val(),
					// 'Vet_Num': 1, 'Vet_Tot': $('#Val_Pcc').val(), 'Pag_Pld': $('#Pag_Pld').val()
					'Vet_Num': 1, 
					'Vet_Tot': $('#Val_Pcc').val(), 
					'Pag_Pld': pagPld
				})];
		pagos.setRows(ite_pagos);
		updateDocument();
	};
	// Iniciar verificación después de un pequeño delay para permitir que el trigger('change') se procese
	setTimeout(verificarYAgregar, 200);
}

function aCredito() {
	$('#For_Cod').val(2).trigger('change').attr('disabled', 'disabled');
	pagos.clearGrid();
	updateDocument();
	$('#pagosDialog').dialog('open');
	$('.saldos').setData({ Vet_Tot: $('#Val_Pcc_2').val() });
}

function fechaMayorQue(fechaInicial, fechaFinal) {
	valuesStart = fechaInicial.split('-');
	valuesEnd = fechaFinal.split('-');
	// Verificamos que la fecha no sea posterior a la fecha final
	var dateStart = new Date(valuesStart[0], (valuesStart[1] - 1), valuesStart[2]);
	var dateEnd = new Date(valuesEnd[0], (valuesEnd[1] - 1), valuesEnd[2]);
	if (dateStart >= dateEnd) {
		return 1;
	}
	return 0;
}
$.fn.fmatter.ice = function (cv, opts, cObjt) { var ice_por = cObjt['Cop_Ice'] || cObjt['Ice_Por']; if ($.varValid(ice_por) && ice_por !== '' && !isNaN(ice_por) && ice_por * 1 > 0) return ice_por + ' %'; else return ''; };
$.fn.fmatter.ice.unformat = function (cv, opts, cObjt) { return cv.replace(' %', ''); };
$.fn.fmatter.impRenta = function (cv, opts, cObjt) { if (!$.varValid(cObjt['Pro_Cod']) || cObjt['Pro_Cod'] === '') return ''; return getRentaButton(cv, { tipo: 'R', index: cObjt['index'] }, cObjt); };
$.fn.fmatter.impRenta.unformat = $.unformatCellHtml;
$.fn.fmatter.retIva = function (cv, opts, cObjt) { if (!$.varValid(cObjt['Pro_Cod']) || cObjt['Pro_Cod'] === '') return ''; if (cObjt['Iva_Por'] * 1 === 0) return ''; return getRentaButton(cv, { tipo: 'I', index: cObjt['index'] }, cObjt); };
$.fn.fmatter.retIva.unformat = $.unformatCellHtml;
$.fn.fmatter.edicion = function (cv, opts, cObjt) {
	if ($.varValid(edicion_ventas)) {
		/*	if (cObjt['Com_Edit'] === 'N') return '<i title="El comprobante contable es formato anterior" class="glyphicon glyphicon-lock orange"></i>';
			if (cObjt['Vet_Est'] !== 'A') return '<i title="Registro Anulado/Inactivo" class="glyphicon glyphicon-remove red"></i>';
			//if(cObjt['Cpc_Edit']==='N') return '<i title="Contiene Pagos Activos" class="fa fa-money green"></i>';
			//if(cObjt['Vet_Aut']==='S' && edit_reten===false) return '<i title="Documento Autorizado por SRI" class="fa fa-globe green"></i>'
		/*	if (cObjt['Vet_Aut'] === 'S' && edit_reten === false)
				return '<i title="Documento Autorizado por SRI" class="fa fa-globe green"></i>' /* ? $.getGridButton('cambiarPago', cObjt, "Cambiar Forma de Pago", null, null, 'warning') : '<i title="Documento Autorizado por SRI" class="fa fa-globe green"></i>';*/


		if (cObjt['Com_Edit'] === 'N') return '<i title="El comprobante contable es formato anterior" class="glyphicon glyphicon-lock orange"></i>';
		if (cObjt['Vet_Aut'] === 'S')
			return (cObjt['Pago'] === 'Credito' || cObjt['Pago'] === 'Contado') && (cObjt['Cpp_Det'] !== 'S' || cObjt['onlyRetencion'] == true) ? $.getGridButton('cambiarPago', cObjt, "Cambiar Forma de Pago", null, null, 'warning') : '<i title="Retencion Electronica Validada" class="fa fa-globe green"></i>';
		if (cObjt['Vet_Est'] !== 'A') return '<i title="Registro Anulado/Inactivo" class="glyphicon glyphicon-remove red"></i>';


	}
	return $.getGridButton(cargarDoc, cObjt);
};
// Abre dialogo producto para cambiar item
function openItemSelector(id) {

	index = id; $('#proDialog').dialog().dialog('open');
	$.Search('pro');
}

// Añade un item al documento
function addItem(item, Vet_Can = 1, Vet_Pru = '') {
	var next = items.jqGrid('getCol', 'index', false, 'max');
	next = (isNaN(next) ? 1 : next + 1);
	items.jqGrid('addRowData', next, $.extend({ Ime_Cod: '', Ime_Num: '' }, item, { index: next, Vet_Can: Vet_Can, Vet_Pru: Vet_Pru }), 'last');
	items.jqGrid('editRow', next);
	return next;
}

function available() {
	var its = items.jqGrid('getDataIDs').length, max = $('#Tic_Cod').find('option:selected').data('autima'), seguir = true;
	seguir = ($.isNumeric(max) ? (its < max) ? true : false : true);
	return seguir;

}
function selectItem(item) {
	var lastId = items.jqGrid('getCol', 'index', false, 'max'), close = true, its = items.jqGrid('getDataIDs').length, full = !available();
	if (its === 0) {
		addItem({});
		lastId = 1;
	} else if (!full && items.jqGrid('getRowData', lastId)['Pro_Cod'] !== '') { addItem({}); lastId = lastId * 1 + 1; }
	if (index === 0) { index = lastId; close = false; }
	var new_item = $.extend(item, item['Iva_Por'] * 1 > 0 ? {
		//'Iva_Cod': $('#Iva_Cod').val(),
		'Iva_Cod': item.Iva_Cod,
		'Iva_Por': item.Iva_Por
		//Obtiene el IVA SELECCIONADO
		//'Iva_Por':$('#Iva_Cod option:selected').data('ivapor')
	} : {
		'Iva_Ren_Cod': '',

		'Iva_Ren_Con': '',
		'Iva_Ren_Por': '',
		'Iva_Ren_Sri': ''
	});

	var precio = $.arrayGetItem(item['Precios'], 'Tpv_Des', 'Standar', 'pre_index') || {};
	items.changeRow(index, new_item, null, { Vet_Pru: precio['Pre_Pvp'] });
	updateRowItem({ rowId: index });
	var last = items.jqGrid('getRowData', lastId);
	if (last['Pro_Cod'] !== '' && available()) {
		addItem({});
	}
	if (full) { $('#proDialog').dialog('close'); return; }
	if (close) { $('#proDialog').dialog('close'); setTimeout(function () { $('#' + (index) + '_Vet_Can').focus(); }, 0); } else if (available()) index = 0; else index = lastId * 1 + 1;
	updateDocument();
}

// Elimina item
function deleteItem(index) {
	var row = items.jqGrid('getRowData', index), lastId = items.jqGrid('getCol', 'index', false, 'max');
	if (row['Pro_Cod'] !== '') {
		items.jqGrid('delRowData', index);
		//if(items.jqGrid('getRowData',lastId)['Pro_Cod']!=='') addItem({});
		updateDocument();
	}
}

//Estilo para cantidad
function styleCant(e, obj, opt) {

	e.style.textAlign = 'right'; e.placeholder = '0';
	$(e).on('keyup', function () {
		if (isNaN(this.value)) { $(this).val('1').focus(); }
		else if (this.value % 1 !== 0) { var dec = String(this.value).split('.'); if (typeof dec[1] !== 'undefined' && dec[1].length > 5) this.value = $.toFixed(this.value, 5); }
		updateRowItem(obj);
	});
}

//Estilo para precio unitario
function stylePru(e, obj, opt) {
	e.style.textAlign = 'right'; e.placeholder = '0.00';
	$(e).on('keyup', function () {
		this.value = this.value.trim();
		if (isNaN(this.value)/*||this.value===''||(!isNaN(this.value)&&this.value*1===0)*/) { $(this).val('').focus(); }
		else if (this.value % 1 !== 0) { var dec = String(this.value).split('.'); if (typeof dec[1] !== 'undefined' && dec[1].length > 8) this.value = $.toFixed(this.value, 8); }
		updateRowItem(obj);
	});
}

//Actualiza los valores de la fila
function updateRowItem(obj) {
	var datosa = items.jqGrid('getRowData', obj['rowId']);
	var datosb = items.find('tr#' + obj['rowId']).getDataForced();
	var row = $.extend({}, datosa, datosb);

	row['Vet_Imp'] = row['Vet_Can'] * (0 + row['Vet_Pru']) * 1;


	// console.log("datos row:" + parseFloat(row['Vet_Can'] * (0 + row['Vet_Pru']) * 1).toFixed(2));

	row['Vet_Imp'] = row['Vet_Imp'] - (('0' + row['Vet_Dec']) * 1 > 0 ? row['Vet_Imp'] * row['Vet_Dec'] / 100 : 0);
	items.changeRow(obj['rowId'], row);
	updateDocument();
}

function updateDocument() {
	var filaCalc = addItem({});
	/*var rows = items.jqGrid('getRowData'),des_val=$('#t_descuento').val(), des_por=$('#Vet_Des').val(), tot={t_subtotal:0,t_iva0:0,t_iva12:0,t_iva:0,t_ice:0,t_descuento:(isNaN(des_val)?0:des_val*1),Vet_Des:(isNaN(des_por)||des_por*1===0?0:des_por*1),t_rubros:0},
		Tic_Sri=$('#Tic_Cod').find('option:selected').data('ticsri')*1, rise=(Tic_Sri===2||Tic_Sri===9);

	for (var i=0, z=rows.length; i<z ; i++){
		var row=rows[i];
		if (row['Pro_Cod']!=='') {
			row['Vet_Imp']=(row['Vet_Imp']*1);
			row['Iva_Por']=rise?0:('0'+row['Iva_Por'])*1;
			row['Ice_Por']=('0'+row['Ice_Por'])*1;
			tot['t_subtotal']=tot['t_subtotal']+row['Vet_Imp'];
			if(row['Iva_Por']===0||rise) tot['t_iva0']=tot['t_iva0']+row['Vet_Imp'];
			else tot['t_iva12']=tot['t_iva12']+row['Vet_Imp'];
		}
	}*/
	var rows = items.jqGrid('getRowData'),
		des_val = $('#t_descuento').val(),
		prop = $('#t_prop').val(),
		des_por = $('#Vet_Des').val(), tot = {
			t_subtotal: 0,
			t_noiva: 0, //nuevo campo
			t_iva0: 0,
			t_iva5: 0,
			t_iva12: 0,
			t_iva15: 0,
			t_iva: 0,
			t_ice: 0,
			t_descuento: (isNaN(des_val) ? 0 : des_val * 1),
			Vet_Des: (isNaN(des_por) || des_por * 1 === 0 ? 0 : des_por * 1),
			t_prop: (!isNaN(prop) ? prop * 1 : 0),
			t_rubros: 0
		},
		Tic_Sri = $('#Tic_Cod').find('option:selected').data('ticsri') * 1,
		rise = (Tic_Sri === 2 || Tic_Sri === 9);

	for (var i = 0, z = rows.length; i < z; i++) {
		var row = rows[i];
		if (row['Pro_Cod'] !== '') {
			row['Vet_Imp'] = (row['Vet_Imp'] * 1);
			row['Iva_Por'] = rise ? 0 : ('0' + row['Iva_Por']) * 1;//captura porcentaje del iva
			row['Ice_Por'] = ('0' + row['Ice_Por']) * 1;
			tot['t_subtotal'] = tot['t_subtotal'] + row['Vet_Imp'];

			if (row['Iva_Por'] === 0 || rise) {
				// tot['t_iva0'] = tot['t_iva0'] + row['Vet_Imp']; //0%
				if (row['Iva_Sri'] == 6) { // nueva validacion para no objeto iva
					tot['t_noiva'] = tot['t_noiva'] + row['Vet_Imp']; //nuevo campo para total sin iva
				} else {
					tot['t_iva0'] = tot['t_iva0'] + row['Vet_Imp']; //0%
				}
			} else if (row['Iva_Por'] === 12 || rise) {
				tot['t_iva12'] = tot['t_iva12'] + row['Vet_Imp'];//12%
			} else if (row['Iva_Por'] === 5 || rise) {
				tot['t_iva5'] = tot['t_iva5'] + row['Vet_Imp'];//5%
			} else {
				tot['t_iva15'] = tot['t_iva15'] + row['Vet_Imp'];//15%
			}
		}
	}

	if ($('#ch_prop').is(':checked')) {
		tot['t_prop'] = tot['t_subtotal'] * 0.10;
	}

	tot['Vet_Des'] = (tot['t_descuento'] > 0 ? (tot['t_subtotal'] >= tot['t_descuento'] ? tot['t_descuento'] * 100 / tot['t_subtotal'] : 100) : tot['t_descuento'] * 1);

	for (var i = 0, z = rows.length; i < z; i++) {
		var row = rows[i], des_glob = (tot['Vet_Des'] > 0 ? row['Vet_Imp'] * tot['Vet_Des'] / 100 : 0), ice = (row['Ice_Por'] > 0 ? (row['Vet_Imp'] - des_glob) * row['Ice_Por'] / 100 : 0);
		if (row['Pro_Cod'] !== '') {
			if (row['Iva_Por'] > 0 && !rise) {
				tot['t_ice'] = tot['t_ice'] + ice;
				tot['t_iva'] = tot['t_iva'] + (row['Vet_Imp'] + ice - des_glob) * row['Iva_Por'] / 100;
			}
		}
	}
	tot['t_iva'] = $.round(tot['t_iva']); tot['t_ice'] = $.round(tot['t_ice']);
	tot['t_rubros'] = tot['t_subtotal'] + tot['t_iva'] + tot['t_ice'] - tot['t_descuento'] + tot['t_prop'];

	var pagos_tot = pagos.jqGrid('getCol', 'Vet_Tot', false, 'sum');
	$('#Val_Pcc').val($.toFixed(tot['t_rubros'] - pagos_tot));

	// var opcionDeshabilitar = "01 - SIN UTILIZACION DEL SISTEMA FINANCIERO";
	// if (tot['t_rubros'] >= 500) {
	// 	$("#Tpc_Cod option:contains('" + opcionDeshabilitar + "')").prop("disabled", true);
	// 	$("#Tpc_Cod").prop("selectedIndex", 0);
	// }
	// else {
	// 	$("#Tpc_Cod option:contains('" + opcionDeshabilitar + "')").prop("disabled", false);
	// }

	var opcionDeshabilitar = "01 - SIN UTILIZACION DEL SISTEMA FINANCIERO";
	var tpcCodSelect = $('#Tpc_Cod');
	var totalRubros = tot['t_rubros'] * 1;

	// Función para establecer el valor del select
	function establecerTpcCod(valor) {
		if (tpcCodSelect.length > 0) {
			var option = tpcCodSelect.find('option[value="' + valor + '"]');
			if (option.length > 0) {
				// Remover selección de todas las opciones
				tpcCodSelect.find('option').prop('selected', false);
				// Seleccionar la opción deseada
				option.prop('selected', true);
				// Establecer el valor del select
				tpcCodSelect.val(valor);
				// Forzar el cambio si es necesario
				if (tpcCodSelect.val() != valor) {
					tpcCodSelect.val(valor);
				}
				// Disparar el evento change
				// tpcCodSelect.trigger('change');
			}
		}
	}

	setTimeout(function () {
		if (totalRubros >= 500) {
			$("#Tpc_Cod option:contains('" + opcionDeshabilitar + "')").prop("disabled", true);
			// Si el total es >= 500, forzamos la opción 20
			if (tpcCodSelect.val() != '20') {
				establecerTpcCod('20');
			}
		} else {
			$("#Tpc_Cod option:contains('" + opcionDeshabilitar + "')").prop("disabled", false);
		}
	}, 100);

	$.each(tot, function (k, v) {
		tot[k] = $.toFixed(v, k !== 'Vet_Des' ? 2 : 10);
	});
	$('#formTotales').setData(tot);
	$('#Vet_Des').val(tot['Vet_Des']);
	calculaRetencion();
	items.jqGrid('delRowData', filaCalc);
	return tot;

}


// cambia los ivas de los items
function changeIvas() {
	var ids = items.jqGrid('getDataIDs'),
		iva = {
			Iva_Cod: $('#Iva_Cod').val(),
			Iva_Por: $('#Iva_Cod option:selected').data('ivapor')
		};

	$('.iva_por').html(iva['Iva_Por']);
	for (var i = 0; i < ids.length; i++) {
		if ('0' + items.jqGrid('getCell', ids[i], 'Iva_Por') * 1 > 0)
			items.changeRow(ids[i], iva);
	}
	updateDocument();
}

$('#Val_Pcc_2').on('change', function () {

});

function registarPagos() {
	if (pagos.jqGrid('getDataIDs').length > 0) $('#For_Cod').attr('disabled', 'disabled');
	else $('#For_Cod').removeAttr('disabled');
	$('#For_Cod').val(1).trigger('change');
	$('#pagosDialog').dialog('open');
	$('.saldos').setData({ Vet_Tot: $('#Val_Pcc_2').val() });

}


//Estilo para precio unitario
function styleMonto(e, obj, opt) {
	var monto = $('#Val_Pcc').val() * 1 - total_pagos;
	$(e).val(monto); e.style.textAlign = 'right'; e.placeholder = '0.00';
	$(e).on('change', function () {
		if (isNaN(this.value)) { $(this).val('').focus(); }
		else if (this.value % 1 !== 0) { var dec = String(this.value).split('.'); if (typeof dec[1] !== 'undefined' && dec[1].length > 8) this.value = $.toFixed(this.value, 8); }
		montoTotal(obj);
	}).trigger('change');
}

var total_pagos = 0;
function montoTotal(obj) {
	var error = false, totalpagar = $('#Val_Pcc').val(), row = pagos.jqGrid('getDataIDs'); total_pagos = 0;
	$.each(row, function (i, v) {
		total_pagos = total_pagos * 1 + $('#' + v + '_Vet_Tot').val() * 1;
		if (total_pagos > totalpagar) { error = true; }
	});
	if (error) {
		var aux = total_pagos - $('#' + obj['id']).val();
		var val = totalpagar - aux;
		$('#' + obj['id']).val(val);
		total_pagos = aux * 1 + val * 1;
		$.alert('El total de pagos no puede ser mayor que el valor total a pagar..!!');
	}
	pagos.jqGrid('footerData', 'set', { 'Vet_Tot': total_pagos });
}

// retenciones
function calculaRetencion() {
	var filaCalc = addItem({});
	var pagos_tot = pagos.jqGrid('getCol', 'Vet_Tot', false, 'sum');
	var ids = items.jqGrid('getDataIDs'), rets = [], tot = { 'Ret_Ren_Tot': 0, 'Iva_Ren_Tot': 0, 'Ren_Tot': 0, 'Val_Pcc': 0, 'Ret_Uca': 0, 'Ret_Pca': 0, 'Ret_Ica': 0 },
		Tic_Sri = $('#Tic_Cod').find('option:selected').data('ticsri') * 1, rise = (Tic_Sri === 2 || Tic_Sri === 9), Vet_Des = $('#Vet_Des').val() * 1;

	if (ids.length < 1) {
		$('#retencion').clearGrid();
		$('.reteTot').setData({ Val_Pcc: '0.00' });
		return;
	}
	for (var i = 0, z = ids.length - 1; i < z; i++) {
		var row = items.jqGrid('getLocalRow', ids[i]), row_Imp = ((row['Vet_Imp'] * 1) - (Vet_Des > 0 ? (row['Vet_Imp'] * Vet_Des / 100) : 0));
		if ($.varValid(row['Ret_Ren_Cod']) && row['Ret_Ren_Cod'].length > 0 && !rise) {
			var add = true, ret = { 'Ren_Ret': 'R', 'Ren_Rete': 'RENTA', 'Ren_Cod': row['Ret_Ren_Cod'], 'Ren_Por': row['Ret_Ren_Por'], 'Ren_Sri': row['Ret_Ren_Sri'], 'Ren_Con': row['Ret_Ren_Con'], 'Ren_Imp': row_Imp };
			$.each(rets, function (i, v) {
				if (ret['Ren_Cod'] === v['Ren_Cod'] && ret['Ren_Por'] === v['Ren_Por']) {
					rets[i]['Ren_Imp'] += ret['Ren_Imp'];
					add = false;
				}
			});
			if (add) rets.push(ret);
			//if(String(ret['Ren_Sri'])===String(cod_banano)){ tot['Ret_Uca']+=row['Cop_Can']*1;tot['Ret_Ica']+=row_Imp; }
		}
		if ($.varValid(row['Iva_Ren_Cod']) && row['Iva_Ren_Cod'].length > 0 && !rise) {
			var ice_por = ('0' + row['Ice_Por']) * 1, ice = (ice_por > 0 ? row_Imp * ice_por / 100 : 0);
			var add = true, ret = { Ren_Ret: 'I', Ren_Rete: 'IVA', Ren_Cod: row['Iva_Ren_Cod'], Ren_Por: row['Iva_Ren_Por'], Ren_Sri: row['Iva_Ren_Sri'], Ren_Con: row['Iva_Ren_Con'], Ren_Imp: (row_Imp + ice) * (row['Iva_Por'] / 100) };
			$.each(rets, function (i, v) { if (ret['Ren_Cod'] === v['Ren_Cod']) { rets[i]['Ren_Imp'] += ret['Ren_Imp']; add = false; } });
			if (add) rets.push(ret);
		}
	}
	$.each(rets, function (i, v) {

		rets[i]['Ren_Val'] = $.round(v['Ren_Imp'] * v['Ren_Por'] / 100);
		//rets[i]['Ren_Val']=$.round(v['Ren_Imp'])*v['Ren_Por']/100;
		tot[(v['Ren_Ret'] === 'R' ? 'Ret' : 'Iva') + '_Ren_Tot'] += rets[i]['Ren_Val'];
	});
	tot['Ren_Tot'] = tot['Ret_Ren_Tot'] + tot['Iva_Ren_Tot'];
	tot['Val_Pcc'] = $('#t_rubros').val() * 1 - ($('#Ret_Asu').prop('checked') ? 0 : tot['Ren_Tot']);
	(tot['Ren_Tot'] > 0 ? $('.ret_field').removeAttr('disabled') : $('.ret_field').val('').attr('disabled', 'disabled'));
	$.each(tot, function (k, v) { tot[k] = $.toFixed(v); });

	if (tot['Ret_Uca'] * 1 > 0 && tot['Ret_Ica'] * 1 > 0) { tot['Ret_Pca'] = $.round(tot['Ret_Ica'] / tot['Ret_Uca'], 8); tot['Ret_Uca'] = $.round(tot['Ret_Uca'], 0); $('.cod_banano').show().find('input').attr('required', 'required'); } else { tot['Ret_Uca'] = tot['Ret_Pca'] = tot['Ret_Ica'] = ''; $('.cod_banano').hide().find('input').removeAttr('required'); }

	$('.reteTot').setData(tot);
	var pagos_mod = pagos.getDataIDs();

	$('.porCobrar').setData({ 'Val_Pcc_2': $.toFixed(tot['Val_Pcc'] - pagos_tot) });
	if (pagos_mod.length === 1 && $('#Val_Pcc').val() * 1 > 0) {
		pagos.jqGrid('setCell', pagos_mod[0], 'Vet_Tot', $('#Val_Pcc').val() * 1);
		pagos.trigger('reloadGrid');
		$('.porCobrar').setData({ 'Val_Pcc_2': $.toFixed(0) });
	}

	igualarPagos();



	$('#retencion').setRows(rets);
	items.jqGrid('delRowData', filaCalc);
	return $.toFixed(tot['Val_Pcc'] - pagos_tot);
}

function seleccionaRetencion(data) {
	$('#codiForm').setData(data, false).formSubmit();
	$('#codiDialog').dialog('open');
}
function eliminaRetencion(form) {
	var retBasic = { Ren_Cod: '', Ren_Sri: '', Ren_Por: '', Ren_Con: '' }, ret = {};
	$.each(retBasic, function (k, v) {
		ret[(form['tipo'] === 'R' ? 'Ret_' : 'Iva_') + k] = v;
	});
	items.changeRow(form['index'], ret);
	calculaRetencion();
}

function seleccionaImei(data) {
	$('#imeiForm').setData(data, false).formSubmit();
	$('#imeiDialog').dialog('open');
}

// Función para verificar disponibilidad de IMEI y actualizar el botón
function verificarDisponibilidadImei(Pro_Cod, rowId) {
	if (!Pro_Cod || Pro_Cod === '') return;

	// Hacer petición para verificar disponibilidad
	$.post('', { 'imeiAjax': true, 'Pro_Cod': Pro_Cod, 'rows': 1, 'page': 1 }, function (response) {
		if (response && response.records && response.records > 0) {
			// Hay IMEI disponibles, mantener el botón activo
			var btn = $('.imei-selector-container[data-row-id="' + rowId + '"] .btn-imei-selector');
			btn.removeClass('disabled').prop('disabled', false);
			btn.find('i').removeClass('glyphicon-lock').addClass('glyphicon-plus');
			btn.removeClass('btn-default').addClass('btn-info');
			btn.attr('title', 'Seleccionar IMEI');
		} else {
			// No hay IMEI disponibles, mostrar candado
			var btn = $('.imei-selector-container[data-row-id="' + rowId + '"] .btn-imei-selector');
			btn.addClass('disabled').prop('disabled', true);
			btn.find('i').removeClass('glyphicon-plus glyphicon-edit').addClass('glyphicon-lock');
			btn.removeClass('btn-info btn-warning').addClass('btn-default');
			btn.attr('title', 'No hay IMEI disponibles para este producto');
		}
	}, 'json').fail(function () {
		// En caso de error, mantener el botón pero deshabilitado
		var btn = $('.imei-selector-container[data-row-id="' + rowId + '"] .btn-imei-selector');
		btn.addClass('disabled').prop('disabled', true);
		btn.find('i').removeClass('glyphicon-plus glyphicon-edit').addClass('glyphicon-lock');
		btn.removeClass('btn-info btn-warning').addClass('btn-default');
		btn.attr('title', 'Error al verificar disponibilidad');
	});
}

// Función para abrir el modal de IMEI
function abrirModalImei(itemIndex) {
	var rowData = items.jqGrid('getRowData', itemIndex);
	var Pro_Cod = rowData['Pro_Cod'];

	if (!Pro_Cod || Pro_Cod === '' || Pro_Cod === '0' || Pro_Cod === 0) {
		$.alert('Debe seleccionar un producto primero para poder asignar un IMEI');
		return;
	}

	// Pasar Pro_Cod al formulario del modal y abrir directamente
	$('#imeiForm').setData({ 'Pro_Cod': Pro_Cod, 'index': itemIndex }, false);
	$('#imeiGrid').jqGrid('setGridParam', { postData: { 'Pro_Cod': Pro_Cod, 'Ime_Tip': $('#imeiForm input[name="imei_tipo"]:checked').val() || '' } }).trigger('reloadGrid');
	$('#imeiDialog').dialog('open');
}

// Función para seleccionar IMEI desde el modal
function selectImei(data) {
	// Obtener el índice del item desde el formulario oculto
	var itemIndex = $('#imeiForm input[name="index"]').val();

	if (!itemIndex) {
		$.alert('Error: No se pudo identificar el item a actualizar.');
		return;
	}

	// Validar que los datos del IMEI sean válidos
	if (!data || !data.Ime_Cod || !data.Ime_Num) {
		$.alert('Error: Los datos del IMEI seleccionado no son válidos.');
		return;
	}

	// Verificar que el IMEI no esté ya seleccionado en otro producto
	var ids = items.jqGrid('getDataIDs');
	for (var i = 0; i < ids.length; i++) {
		var id = ids[i];
		if (id != itemIndex) {
			var rowData = items.jqGrid('getRowData', id);
			if (rowData.Ime_Num == data.Ime_Num) {
				$.alert('El IMEI ya está seleccionado en otro producto.');
				return;
			}
		}
	}

	// Actualizar la fila del grid de items con el IMEI seleccionado
	items.jqGrid('setRowData', itemIndex, {
		'Ime_Cod': data.Ime_Cod,
		'Ime_Num': data.Ime_Num
	});

	// Actualizar el span visible del IMEI en el grid
	var $imeiSpan = $('#imei_num_' + itemIndex);
	if ($imeiSpan.length) {
		$imeiSpan.text(data.Ime_Num).attr('title', data.Ime_Num);
	}

	// Actualizar el botón para cambiar su estado visual (de info a warning)
	var $imeiButton = $imeiSpan.closest('.imei-selector-container').find('.btn-imei-selector');
	if ($imeiButton.length) {
		$imeiButton.removeClass('btn-info').addClass('btn-warning')
			.attr('title', 'Cambiar IMEI')
			.find('i').removeClass('glyphicon-plus').addClass('glyphicon-edit');
	}

	// Actualizar el documento para reflejar los cambios
	updateDocument();

	// Cerrar el modal
	$('#imeiDialog').dialog('close');
}

function getRentaButton(cv, data, cObjt) {
	//console.log('renta seleccion',cObjt.Ret_Ren_Sri);
	//console.log('renta constante',ret_banano);

	//console.log('agregar banano',$.inArray(cObjt.Ret_Ren_Sri,ret_banano));
	var obj, valid = ($.varValid(cv) && cv !== '');
	obj = $('<div class="input-group input-group-xs ret">' +
		'<span type="text" class="form-control center" title="' + (valid ? cObjt[(data['tipo'] === 'R' ? 'Ret_' : 'Iva_') + 'Ren_Por'] + '% - ' : '') + (valid ? cObjt[(data['tipo'] === 'R' ? 'Ret_' : 'Iva_') + 'Ren_Con'] : '') + '">' + (valid ? cv : '') + '</span>' +
		'<span class="input-group-btn">' +
		'<button type="button" onclick="' + (valid ? 'elimina' : 'selecciona') + 'Retencion($(this).parent().data(\'originaldata\'));" class="btn btn-' + (valid ? 'warning' : 'info') + '" title="' + (valid ? 'Quitar' : 'Agregar') + ' ' + (data['tipo'] === 'R' ? 'Imp. a la Renta' : 'Ret. del Iva') + '" tabindex="-1">' +
		'<i class="glyphicon glyphicon-' + (valid ? 'minus' : 'plus') + '"></i>' +
		'</button>' + (($.inArray(cObjt.Ret_Ren_Sri * 1, ret_banano) >= 0 && data['tipo'] === 'R') ?
			'<button type="button" onclick="EditaRetencion($(this).parent().data(\'originaldata\'),' + cObjt.Ret_Ren_Por + ');" class="btn btn-info" title="editar" tabindex="-1">' +
			'<i class="glyphicon glyphicon-pencil"></i>' +
			'</button>' :
			'') +
		'</span>' +
		'</div>');
	obj.find('.input-group-btn').attr('data-originaldata', $.jsonParser($.extend(data, valid ? {} : { search: '', op_opciones: 'p', checkRentaIva: 'N', Cop_Fec: $('#Cop_Fec').val() })));
	return obj.prop('outerHTML');
}

function selectAut(auto) {
	var sel_documento = $('#Tic_Cod').find('option:selected').data();
	if ($('#Tic_Cod').val() * 1 > 0) {
		$.each(array_documentos, function (i, v) {
			if (sel_documento['ticcod'] * 1 === v['Tic_Cod'] * 1) {
				array_documentos[i] = auto;
			}
		});
	}

	$('#Tic_Cod').find('option:selected').data({ 'autcod': auto['Aut_Cod'] * 1, 'autads': auto['Aut_Ads'] * 1, 'autadv': auto['Aut_Adv'] * 1, 'puncod': auto['Pun_Cod'] * 1, 'ticcod': auto['Tic_Cod'] * 1, 'autsri': auto['Aut_Sri'] + '', 'punsri': auto['Pun_Sri'], 'autfci': auto['Aut_Fci'], 'autcad': auto['Aut_Cad'], 'autini': auto['Aut_Ini'] * 1, 'autfin': auto['Aut_Fin'] * 1, 'auttem': auto['Aut_Tem'], 'autima': ((auto['Aut_Ima'] * 1) === 0 ? null : auto['Aut_Ima'] * 1), 'ticsri': auto['Tic_Sri'] * 1, 'ticdes': auto['Tic_Des'], 'sucsri': auto['Suc_Sri'] });
	$('#Tic_Cod').trigger('change');
	$('#autorizaDialog').dialog('close');
}

function getDataVendedor() {
	$.post('', { 'getDataPunto': true }, function (response) {

		let html_text = '-';
		if ($.varValid(response)) {
			html_text = response['Pun_Des'];
		}

		$('#cabeceraPuntoImp').html(html_text);
	}, 'json').fail(function () { $.alert(); });
}

function alertaAuto(mensaje, componente, direccion) {
	$(componente).flyout('hide');
	$(componente).createFlyout(mensaje, { icon: 'exclamation', placement: direccion, timeDismis: 6000 });
	$(componente).flyout('show');
}

function igualarPagos() {
	var pagos_mod = pagos.getDataIDs();
	var pagos_tot = pagos.jqGrid('getCol', 'Vet_Tot', false, 'sum');
	if (pagos_mod.length > 1 && $('#Val_Pcc').val() * 1 > 0) {
		for (var i = 0, max = pagos_mod.length; i < max; i++) {
			if ($('#Val_Pcc_2').val() * 1 !== 0) {
				var pago_cal = pagos.jqGrid('getCell', pagos_mod[i], 'Vet_Tot') * 1;
				pago_cal = pago_cal + (pago_cal / pagos_tot * $('#Val_Pcc_2').val() * 1);
				pagos.jqGrid('setCell', pagos_mod[i], 'Vet_Tot', pago_cal);
			}

		}
		pagos.trigger('reloadGrid');
		var pagos_tot = pagos.jqGrid('getCol', 'Vet_Tot', false, 'sum');
		$('.porCobrar').setData({ 'Val_Pcc_2': $.toFixed($('#Val_Pcc').val() - pagos_tot) });
	}
}

function selectVent(venta) {
	//console.log(venta);

	if ((doc_ventas.getGridBatch().length > 0 && venta.For_Cod * 1 === 1) || (doc_ventas.getGridBatch().length > 0 && $('#Tic_Cod').val() * 1 === 5)) {
		$.alert('S&oacute;lo se puede asociar una venta de este tipo');
	} else {
		if (doc_ventas.getInd(venta.Vet_Cod) <= 0) {
			$.getDataJson('', { 'Vet_Cod': venta.Vet_Cod, 'Com_Asoc': Com_Asoc, 'getValoresVenta': true }, function (result) {


				// result['Vet_Total'] = result['Vet_Total'] ? result['Vet_Total'] : result['Vet_Tot_Sin_Compr'];

				venta['Vet_Saldo'] = result['Vet_Total'] - result['Vet_Abonos'];
				venta['Vet_Total'] = result['Vet_Total'];
				venta['Vet_Abonos'] = result['Vet_Abonos'];
				venta['Vet_Num_Asoc'] = venta['Suc_Sri'] + '-' + venta['Pun_Sri'] + '-' + (venta['Vet_Num'] * 1).padLeft(9);
				venta['pagos'] = [];
				$.each(result['pagos'], function (index, pago) {
					venta['pagos'].push($.extend(pago, { 'cubrir': pago.Vet_Tot / venta['Vet_Total'] * 1 }));
				});
				//venta['pagos']=result['pagos'];

				doc_ventas.addRowData('Vet_Cod', venta, 'last');
				doc_ventas.trigger('reloadGrid');
			});
		}
	}
}

var saldo_ventas = () => doc_ventas.jqGrid('getCol', 'Vet_Saldo', false, 'sum');

function validaNotaCreDeb() {
	Tic = $('#Tic_Cod option:selected');
	//console.log($('#t_rubros').val(),saldo_ventas());
	if ($('#t_rubros').val() <= saldo_ventas() || $('#Tic_Cod').val() * 1 === 5) {
		//console.log(validarTic_Cod());
		if (!validarTic_Cod()) {
			$.alert('Revise el n&uacute;mero de Documento');
		} else {

			var docu = $('.formDatos').getData('saveDocument');
			docu['editDocument'] = Mod_Nota_CreDeb;
			var det_items = items.getGridBatch();

			docu['items'] = [];
			for (var i = 0, max = det_items.length; i < max; i++) {
				if (det_items[i].Pro_Cod * 1 > 0) {
					docu['items'].push(det_items[i]);
				}
			}
			if (docu['items'].length <= 0) {
				$.alert('Seleccione almenos un producto');
				items.startGridEdit();
				doc_ventas.startGridEdit();
				return;
			}
			docu['documento'] = $('#Tic_Cod').find(':selected').data();
			docu['For_Cod_Nota'] = $('#For_Cod_Nota').find(':selected').data();
			docu['Pld_Cod_Not'] = $('#Pag_Pld_Nota').find(':selected').data('Pld_Cod');
			docu['ventas'] = doc_ventas.getGridBatch();
			if (docu['ventas'].length <= 0) {
				$.alert('Seleccione almenos un documento de Venta');
				doc_ventas.startGridEdit();
				items.startGridEdit();
				return;
			}
			docu['Tic_Sri'] = Tic.data('ticsri');
			docu['Aut_Cod'] = Tic.data('autcod');
			docu['Tpc_Cod'] = $('#Tpc_Cod').val();
			docu['Aut_Tem'] = Tic.data('auttem');
			docu['Tic_Des'] = Tic.data('ticdes');
			docu['Pun_Sri'] = Tic.data('punsri');
			docu['Aut_Sri'] = Tic.data('autsri');
			docu['Plan_Cod'] = $('#Pec_Cod').find(':selected').data('placod');
			docu['Cal_Inv'] = $('#afecta_inventario').is(':checked');
			//console.log(docu);
			$.createDialogConfirm('¿Est&aacute; seguro de guardar el Documento?', docu, saveDocument, function () {
				doc_ventas.startGridEdit();
				items.startGridEdit();
			});
		}
	} else {
		$.alert('No puede exceder al saldo de las ventas asociadas');
	}
}


function cargar_Documentos(Tic_Sri_Array) {
	$.getDataJson('', { 'get_documentos': true }, function (result) {
		if (result['success']) {
			$('#Tic_Cod_Search').empty();
			$('#Tic_Cod_Search').append('<option value=""><< TODOS >></option>');
			$.each(result['data'], function (index, doc) {
				if ($.inArray(doc.Tic_Sri * 1, Tic_Sri_Array) >= 0) {
					var option = $('<option></option>');
					option.text(doc.Tic_Des).val(doc.Tic_Sri).data(doc);
					$('#Tic_Cod_Search').append(option);
				}
			});
		}

	}, function (err) {
		//console.log(err);
	});
}

function ImpDoc(rowVenta) {
	//console.log(rowVenta);
	$.getDataJson('', { 'cargarReportes': true }, function (res) {
		var reportes = res['reportes'];
		//console.log(reportes[1]);

		$.varValid(reportes[1]) ? $.imprimirUrl(reportes[1] + '?Vet_Cod=' + rowVenta.Vet_Cod) : $.alert('Sin Reportes Asociados');
	}, function (err) {
		console.log(err['message']);
	});
}
function ImpCom(rowVenta) {
	$.getDataJson('', { 'cargarReportes': true }, function (res) {
		var reportes = res['reportes'];
		$.varValid(reportes[2]) ? $.imprimirUrl(reportes[2] + '?codigo=' + rowVenta.Com_Cod) : $.alert('Sin Reportes Asociados');
	}, function (err) {
		console.log(err['message']);
	});
}

var disabledComponentes = (name) => { $(`[name=${name}]`).attr('disabled', 'disabled'); };
$.fn.fmatter.edicion.unformat = $.unformatCellHtml;

function EditaRetencion(valor, porcentaje) {
	valor['Ret_Ren_Por'] = porcentaje;
	//console.log('editando_abrir',valor);
	$('#changeReteDialog').setData(valor).dialog('open');
}

function CambiarRetencion(e) {
	let form_rete = $('#form_change_rete').getData();
	//console.log(form_rete);
	items.changeRow(form_rete['index'], $.extend(form_rete, { 'Ret_Mod': 1 }));
	$('#changeReteDialog').dialog('close');
	calculaRetencion();
}



function calcularPorcentaje(e) {
	let form_rete = $('#changeReteDialog').getData();
	//console.log({'Ret_Ren_Por':$(e).val()*100/items.getRowData(form_rete.index)['Vet_Imp']*1});
	items.stopGridEdit();
	$('#form_change_rete').setData({ 'Ret_Ren_Por': (($(e).val() * 100)) / ((items.getRowData(form_rete.index)['Vet_Imp'] * 1) - $('#t_descuento').val()) }, false);
	items.startGridEdit();
}

function viewInfo(doc) {
	$('#docDetaDialog').setData(doc);
	$('#RetenViewGrid')[$.varValid(doc['Com_Cod']) && doc['Ret_Exi'] === 'S' ? 'show' : 'hide']();
	$('#items').jqGrid('clearGridData');
	$.post('', { 'docDetalle': true, 'Vet_Cod': doc['Vet_Cod'], 'Com_Cod': doc['Com_Cod'] }, function (resp) {
		$('#detaDocu').setRows(resp['Vet_items']);
		$.each(resp['Vet_items'], function (x, item) {
			addItem(item, item['Vet_Can'], item['Vet_Pru']);
		});
		updateDocument();
		$('#detaReten').setRows($('#retencion').getGridBatch());
		$('#docDetaDialog').dialog('open').updateGridsSizes();
	}, 'json').fail(function () { $.alert('error inesperado'); });
}

// var metodo = 0;
// function editObservacion(doc) {
// 	$('#docDetaObservacion').setData(doc);
// 	$('#docDetaObservacion').dialog('open').updateGridsSizes();
// 	var codigo = document.getElementById('Vet_Codigo').value;
// 	if (metodo == 0) {
// 		document.getElementById('btnEditarObservacion').addEventListener('click', function () {
// 			guardarObservacion(doc.Vet_Obs, codigo)
// 		});
// 		metodo++;
// 	}

// }

// function guardarObservacion(obser, codigo) {
// 	var observacion = document.getElementById('Vet_Observacion').value;
// 	obser = observacion;
// 	$.post("", { editarObservacion: true, Vet_Observacion: observacion, Vet_Codigo: codigo }, function (response) {
// 		if (response['success'] == true) {
// 			$('#docDetaObservacion').dialog('close');
// 			$('#searchGrid').Search('#serachDocDorm', 'searchDocument');
// 		}
// 	}, 'json');
// }

function viewPdfVenta(doc) {
	window.open('../COMPONENTES/tesPdfElectronicos.php?type=VENTAS' + '&Doc_Cod=' + doc['Vet_Cod']);
}

function descargar(doc) {
	var save = document.createElement('a'), clicEvent = new MouseEvent('click', { 'view': window, 'bubbles': true, 'cancelable': true });
	save.href = "../FRONT/" + doc['Emp_Cod'] + "/" + doc['Vet_Xml'] + "_A.xml";
	//console.log(save.href);
	save.target = '_blank';
	save.download = doc['Vet_Xml'] + '.xml';
	save.dispatchEvent(clicEvent);
	//window.open(data['file_xml']);
	//console.log(data);
}

function viewPdf(doc) {
	window.open('../COMPONENTES/tesPdfElectronicos.php?type=' + (doc['Tic_Sri'] == '4' ? 'NOTASC' : 'NOTASD') + '&Doc_Cod=' + doc['Vet_Cod']);
}
function viewXml(doc) {
	window.open('?doc_xml=' + doc['Vet_Xml']);
}

function showIntutAut(event) {
	let input_aut = event.data.elem;
	input_aut.toggle({
		'effect': 'slide', 'complete': function () {
			if (input_aut.is(":visible")) {
				input_aut.prop('required', true);
			} else {
				input_aut.prop('required', false);
				$('input[name="input_autorizacion"]').val('');
			}
		}
	}
	);
}

function verifyButtonAutExtern(e) {
	//console.log(e);
	$('input[name="input_autorizacion"]').val('');
	if ($('#Tic_Cod').val() != null) {
		let tic_selected = $('#Tic_Cod').find('option:selected').data();
		if (tic_selected.auttem === 'E') {
			$('.addAutorizacion').removeClass('hidden');
		} else {
			$('.addAutorizacion').addClass('hidden');
		}
	}
}

/* REEMBOLSOS */
var reembolsos;
$(function () {
	reembolsos = $('#reembolsos');
	if (reembolsos.length > 0) {
		reembolsos.createGrid({
			height: 'auto',
			footerrow: true, totalCols: ['Total'], totalDefault: { Prs_Ced: '<div class="txtRight">TOTAL:<div>' },
			colModel: [
				{ label: 'C?. Int.', name: 'Cop_Cod', width: 30, align: "center", key: true, hidden: true },
				{ label: 'Tipo Documento', name: 'Tic_Sri', width: 20, align: "center", classes: 'bgNoRight' },
				{ label: 'No. Documento', name: 'Cop_Num', width: 80, align: "center", classes: 'bgNoRight' },
				{ label: 'Fecha', name: 'Cop_Fec', width: 45, align: "center", classes: 'bgNoRight' },
				{ label: 'RUC/Cedula', name: 'Prs_Ced', width: 90, align: "center" },
				{ label: 'Total', name: 'Total', width: 50, align: 'right', formatter: 'number', summaryType: 'sum', summaryRound: 2, summaryRoundType: 'round', classes: 'bgNoColor' },
				{ name: 'delete', label: $.createIcon('remove'), width: 30, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: 'RemoveCompra', icon: 'remove', title: 'Remover Compra', type: 'danger', data: 'Cop_Cod' }, resizable: false }
			]
		}, true, 'reembolsosPager').gridButtonsAdd([
			null, {
				caption: 'Agregar Compra', buttonicon: 'glyphicon glyphicon-plus', onClickButton: function () {
					$('#comprasReembolsoDialog').dialog('open');
				}
			}
		]);
		$('#gridReembolsos').hide();

		$('#comprasReembolsoDialog').createSearchDialog({
			datatype: 'local',
			colModel: [
				{ label: 'C?. Int.', name: 'Cop_Cod', width: 30, align: "center", key: true, hidden: false },
				{ label: 'Tipo Documento', name: 'Tic_Des', width: 50 },
				{ label: 'No. Documento', name: 'Cop_Num', width: 80, align: "center" },
				{ label: 'Fecha', name: 'Cop_Fec', width: 45, align: "center" },
				{ label: 'RUC/Cedula', name: 'Prs_Ced', width: 90, align: "center" },
				{ label: 'Proveedor', name: 'Proveedor', width: 75 },
				{ label: 'Total', name: 'Total', width: 50, align: 'right', formatter: 'number' },
				{ label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 30, align: 'center', viewable: false, formatter: function (cv, opts, rObj) { return $.getGridButton(SelectCompra, rObj, 'Seleccionar Compra'); } }
			]
		}, { title: 'Facturas', options: [{ label: 'Proveedor', value: 'p' }, { label: ' C?ula/Ruc ', value: 'c' }, { label: ' No. Documento ', value: 'd' }] });
	}
	if (printers)
		$.each(list_printers['printers'], function (i, v) {
			$('#select_printer').append('<option value="' + v + '">' + v + '</option>');
		});
});
function RemoveCompra(Cop_Cod) { reembolsos.delRowData(Cop_Cod); reembolsos.gridUpdate(); }
function SelectCompra(data) {
	if (reembolsos.existsValByCol('Cop_Cod', data['Cop_Cod']))
		return $.alert("La <u>Compra</u> ya se encuentra en el listado!");

	reembolsos.setRow(data).gridUpdate();
}

function setReembolsosGrid(el) {
	if (el.is(':checked')) {
		$('#gridReembolsos').show().updateGridsSizes();
	} else {
		reembolsos.clearGrid();
		$('#gridReembolsos').hide();
	}
}
function sendToPrinter() {
	//list_printers['port_printers']=8081;
	var data = $('.frm_ticket_btn').data();
	//data['printer']='EPSON UB-E04';
	data['printer'] = $('#select_printer').val();
	var link = "http://" + list_printers['ip_printers'] + ":" + list_printers['port_printers'] + "/exa" + "/printers/";

	$.postDataJson(link + "printFactura.php", data, function (r) {
		$('#imprimirLabel').dialog('close');
		return false;
	});
}
/* AGREGADO POR VIAJES */
function updateViajeProductos() {
	var pds = [], rs = $('#viajesSelectedGrid').getCol('Rows');
	$.each(rs, function (i, r) {
		var add = true;
		$.each(pds, function (j, p) {
			if (p.Pro_Cod === r.Pro_Cod && (p.Ori_Cod === r.Ori_Cod && p.Des_Cod === r.Des_Cod)) {
				if ($.vv(r.Via_Con) && r.Via_Con.trim() !== '') p.Contenedores.push(r.Via_Con);
				p.Viajes.push(r.Via_Cod);
				p.Vet_Can += r.Via_Can.toNum();
				p.Vet_Imp = $.toFixed(p.Vet_Imp.toNum() + ((r.Via_Can.toNum() * r.Via_Pru.toNum())));
				p.Vet_Pru = p.Vet_Imp.toNum() / p.Vet_Can;
				add = false; return add;
			}
		});
		if (add) {
			var new_item = { Viajes: [r.Via_Cod], Ite_Lar: `${r.Producto.Ite_Lar}` + ($.vv(r.Ori_Aco) ? `, Desde ${r.Ori_Aco} Hasta ${r.Des_Aco} ` : ''), Contenedores: ($.vv(r.Via_Con) && r.Via_Con.trim() !== '' ? [r.Via_Con] : []), Ori_Cod: r.Ori_Cod, Des_Cod: r.Des_Cod, Vet_Can: r.Via_Can.toNum(), Vet_Pru: r.Via_Pru.toNum(), Vet_Imp: $.toFixed(r.Via_Can.toNum() * r.Via_Pru.toNum()), Precios: [{ Tpv_Des: 'Standar', Pre_Pvp: r.Via_Pru.toNum() }] };
			pds.push($.extend(true, {}, r.Producto, new_item));
		}
	}); //console.log(pds);
	$.each(items.getGridBatch(function (o) { console.log(o); return $.isArray(o.Viajes) || o.Pro_Cod === ''; }), function () { items.delRowData(this.index); });
	items.startGridEdit();
	var next = $("#items").jqGrid('getCol', 'index', false, 'max');
	$.each(pds, function () {
		next = (isNaN(next) ? 1 : next + 1);
		this.Ite_Lar += (this.Contenedores.length ? ", Conten.( " + this.Contenedores.join('/ ') + ")" : '');
		addItem(this, this.Vet_Can, this.Vet_Pru);
		updateRowItem({ rowId: next });
		items.find('tr#' + next).find('td[aria-describedby="items_select"],td[aria-describedby="items_delete"]').find('button').prop('disabled', true);
		//selectItem(this);
	});
	addItem({});
	updateDocument();
	return pds;
}
function removeViaje(Via_Cod) {
	$('#viajesSelectedGrid').delRowData(Via_Cod);
	updateViajeProductos();
}
function selectViaje(data) { //console.log(data);
	if (!$.vv(data.Producto)) return $.alert("No se puede agregar el <b class='green'>VIAJE</b>, porque el <b class='blue'>PRODUCTO</b> pertenece a otra sucursal!");
	$('#viajesSelectedGrid').setRow(data);
	$('#viajesGrid').delRowData(data.Via_Cod);
	updateViajeProductos();
}


/* AGREGADO POR TICKETS */
function updateTicketProductos() {
	var pds = [], rs = $('#ticketsSelectedGrid').getCol('Rows');
	// Si hay tickets seleccionados, agrupar TODOS en una sola fila de producto, acumulando valores
	if (rs && rs.length > 0) {
		var totalCan = 0;
		var totalImp = 0;
		var allTicketNums = [];
		var allTickets = [];
		var firstProduct = null;
		// Recopilar todos los productos de todos los tickets y acumular valores
		$.each(rs, function (i, r) {
			allTicketNums.push(r.Tck_Num || r.Tck_Cod);
			allTickets.push(r.Tck_Cod);
			// Cada ticket puede tener múltiples productos
			if ($.vv(r.Productos) && $.isArray(r.Productos)) {
				$.each(r.Productos, function (j, prod_tick) {
					if (!$.vv(prod_tick.Producto)) return;
					// Guardar el primer producto que encontremos
					if (!firstProduct) {
						firstProduct = prod_tick.Producto;
					}
					// Acumular cantidades y totales - convertir a número de manera segura
					var cantidad = 0;
					var importe = 0;
					if (typeof prod_tick.Tck_Can === 'number') {
						cantidad = prod_tick.Tck_Can;
					} else if (typeof prod_tick.Tck_Can === 'string') {
						cantidad = parseFloat(prod_tick.Tck_Can) || 0;
					} else if (prod_tick.Tck_Can && typeof prod_tick.Tck_Can.toNum === 'function') {
						cantidad = prod_tick.Tck_Can.toNum();
					}

					if (typeof prod_tick.Tck_Imp === 'number') {
						importe = prod_tick.Tck_Imp;
					} else if (typeof prod_tick.Tck_Imp === 'string') {
						importe = parseFloat(prod_tick.Tck_Imp) || 0;
					} else if (prod_tick.Tck_Imp && typeof prod_tick.Tck_Imp.toNum === 'function') {
						importe = prod_tick.Tck_Imp.toNum();
					}
					totalCan += cantidad;
					totalImp += importe;
				});
			}
		});
		// Si tenemos al menos un producto, crear una sola fila con todos los tickets agrupados
		if (firstProduct && totalCan > 0) {
			var precioUnitario = totalImp / totalCan;
			// Si el ticket tiene Tickets_Originales, usar esos códigos para poder actualizar todos los tickets al guardar
			var ticketsToUpdate = allTickets;
			if (rs && rs.length > 0 && rs[0].Tickets_Originales && $.isArray(rs[0].Tickets_Originales)) {
				ticketsToUpdate = rs[0].Tickets_Originales;
			}
			var new_item = {
				Tickets: ticketsToUpdate,
				Tickets_Originales: ticketsToUpdate, // También guardar aquí para asegurar que esté presente
				Ite_Lar: firstProduct.Ite_Lar  /* + (allTicketNums.length > 0 ? ' (Tickets: #' + allTicketNums.join(', #') + ')' : '' )*/,
				Vet_Can: totalCan,
				Vet_Pru: precioUnitario,
				Vet_Imp: $.toFixed(totalImp),
				Precios: [{ Tpv_Des: 'Standar', Pre_Pvp: precioUnitario }]
			};
			var finalItem = $.extend(true, {}, firstProduct, new_item);
			pds.push(finalItem);
		}
	}
	// Eliminar items de tickets anteriores
	$.each(items.getGridBatch(function (o) { return $.isArray(o.Tickets) || o.Pro_Cod === ''; }), function () { items.delRowData(this.index); });
	items.startGridEdit();
	var next = $("#items").jqGrid('getCol', 'index', false, 'max');
	$.each(pds, function () {
		next = (isNaN(next) ? 1 : next + 1);
		// Usar addRowData directamente para preservar todos los campos
		// Asegurar que Tickets y Tickets_Originales estén presentes
		var itemData = $.extend(true, {}, this, {
			index: next,
			Vet_Can: this.Vet_Can || 1,
			Vet_Pru: this.Vet_Pru || '',
			Tickets: this.Tickets || this.Tickets_Originales || null,
			Tickets_Originales: this.Tickets_Originales || this.Tickets || null
		});
		// Agregar el item al grid
		items.jqGrid('addRowData', next, itemData, 'last');
		// Asegurar que Tickets se preserve en los datos internos del grid
		// Acceder directamente a los datos internos para preservar Tickets
		try {
			var gridData = items.jqGrid('getGridParam', 'data');
			if (gridData && $.isArray(gridData)) {
				for (var k = 0; k < gridData.length; k++) {
					if (gridData[k] && (gridData[k].index == next || gridData[k][items.jqGrid('getGridParam', 'keyName') || 'index'] == next)) {
						// Asegurar que Tickets esté presente en los datos internos
						if (itemData.Tickets && !gridData[k].Tickets) {
							gridData[k].Tickets = itemData.Tickets;
						}
						if (itemData.Tickets_Originales && !gridData[k].Tickets_Originales) {
							gridData[k].Tickets_Originales = itemData.Tickets_Originales;
						}
						break;
					}
				}
			}
		} catch (e) {
			console.log('Error preservando Tickets en datos internos:', e);
		}
		items.jqGrid('editRow', next);
		updateRowItem({ rowId: next });
		items.find('tr#' + next).find('td[aria-describedby="items_select"],td[aria-describedby="items_delete"]').find('button').prop('disabled', true);
	});
	addItem({});
	updateDocument();
	return pds;
}

function removeTicket(Tck_Cod) {
	$('#ticketsSelectedGrid').delRowData(Tck_Cod);
	// Desmarcar el checkbox
	$('.ticket-row-select[data-tck-cod="' + Tck_Cod + '"]').prop('checked', false);
	// Actualizar el checkbox de "seleccionar todos"
	var totalTickets = $('.ticket-row-select').length;
	var checkedTickets = $('.ticket-row-select:checked').length;
	$('#select_all_tickets').prop('checked', totalTickets > 0 && checkedTickets === totalTickets);
	updateTicketProductos();
}

function selectTicket(data) {
	// Si data no tiene Productos, intentar obtenerlos del grid usando getRowData con usedata:true
	if ((!$.vv(data.Productos) || !$.isArray(data.Productos) || data.Productos.length === 0) && data.Tck_Cod) {
		// Buscar el rowId en el grid usando Tck_Cod
		var grid = $('#ticketsGrid');
		var allRowIds = grid.jqGrid('getDataIDs');
		var found = false;
		$.each(allRowIds, function (i, rowId) {
			if (found) return false; // Salir del loop si ya se encontró
			// Usar getRowData con usedata:true para obtener los datos originales
			var rowDataFromGrid = grid.jqGrid('getRowData', rowId, true);
			if (rowDataFromGrid && rowDataFromGrid.Tck_Cod == data.Tck_Cod) {
				// Si tiene Productos directamente, usarlos
				if ($.vv(rowDataFromGrid.Productos) && $.isArray(rowDataFromGrid.Productos)) {
					data.Productos = rowDataFromGrid.Productos;
					found = true;
					return false;
				}
				// Si tiene Rows, intentar obtener desde ahí
				if ($.vv(rowDataFromGrid.Rows)) {
					try {
						var rowsDataFromGrid = typeof rowDataFromGrid.Rows === 'string' ? JSON.parse(rowDataFromGrid.Rows) : rowDataFromGrid.Rows;
						if ($.vv(rowsDataFromGrid) && $.vv(rowsDataFromGrid.Productos)) {
							data.Productos = rowsDataFromGrid.Productos;
							found = true;
							return false;
						} else if ($.vv(rowsDataFromGrid) && typeof rowsDataFromGrid === 'object') {
							// Si Rows contiene los datos directamente, usarlos
							data = $.extend({}, data, rowsDataFromGrid);
							if ($.vv(data.Productos)) {
								found = true;
								return false;
							}
						}
					} catch (e) {
						console.log('Error parsing Rows data from grid:', e);
					}
				}
			}
		});
	}
	// También intentar desde data.Rows directamente si aún no se tiene
	if ((!$.vv(data.Productos) || !$.isArray(data.Productos) || data.Productos.length === 0) && $.vv(data.Rows)) {
		try {
			var rowsData = typeof data.Rows === 'string' ? JSON.parse(data.Rows) : data.Rows;
			if ($.vv(rowsData) && $.vv(rowsData.Productos)) {
				data.Productos = rowsData.Productos;
			} else if ($.vv(rowsData) && typeof rowsData === 'object') {
				// Si Rows contiene los datos directamente, usarlos
				data = $.extend({}, data, rowsData);
			}
		} catch (e) {
			console.log('Error parsing Rows data:', e);
		}
	}

	if (!$.vv(data.Productos) || !$.isArray(data.Productos) || data.Productos.length === 0) {
		return $.alert("El ticket no tiene productos asociados!");
	}
	// Verificar que todos los productos pertenezcan a la sucursal
	var productosValidos = true;
	$.each(data.Productos, function (i, prod_tick) {
		if (!$.vv(prod_tick.Producto)) {
			productosValidos = false;
			return false;
		}
	});
	if (!productosValidos) {
		return $.alert("No se puede agregar el <b class='green'>TICKET</b>, porque algunos <b class='blue'>PRODUCTOS</b> pertenecen a otra sucursal!");
	}
	// Verificar si el ticket ya está seleccionado
	var existingRow = $('#ticketsSelectedGrid').jqGrid('getRowData', data.Tck_Cod);
	if (existingRow && existingRow.Tck_Cod) {
		return; // Ya está seleccionado
	}
	$('#ticketsSelectedGrid').setRow(data);
	$('#ticketsGrid').delRowData(data.Tck_Cod);
	// Marcar el checkbox
	$('.ticket-row-select[data-tck-cod="' + data.Tck_Cod + '"]').prop('checked', true);
	updateTicketProductos();
}


/*
function checkCuentaPago2() {
	var data = $('#For_Cod2').data();
	$.postDataJson("", { cuentasPago: true, For_Cod: $('#For_Cod2').val(), Vet_Fec: data['Vet_Fec'], Pld_Cod: data['Pld_Cod_Pag'] }, function (r) {
		if (r['total'] > 0) {
			$('#Pag_Pld2').html(r['cuentas']);
		} else {
			$('#Pag_Pld2').val('').html(''); $.alert('Error al buscar la cuenta pago para la fecha indicada');
		}
	}, function () { return $.alert('Error al Buscar las cuentas Pago'); }, function () { return $.alert('Error al Buscar las cuentas Pago'); },
		function () { });
}*/


function checkCuentaPago2() {
	var data = $('#For_Cod2').data();
	var Pag_Cod = $('#For_Cod2').val(); //2 Credito 1 Contado
	$.postDataJson("", { cuentasPago: true, For_Cod: $('#For_Cod2').val(), Vet_Fec: data['Vet_Fec'], Pld_Cod: data['Pld_Cod_Pag'] }, function (r) {
		$(".pagoCredito2").toggle(Pag_Cod == 2); // Ocultar input que se usan cuando es pago es a crédito
		if (r['total'] > 0) {
			$('#Pag_Pld2').html(r['cuentas']);
			$('#aux_Pag_Cod option').each(function () {
				const isCredito = Pag_Cod == 2;
				$(this).prop('hidden', isCredito ? $(this).val() != 4 : $(this).val() == 4 || $(this).val() == 7);
			});
		} else {
			$('#Pag_Pld2').val('').html(''); $.alert('Error al buscar la cuenta pago para la fecha indicada');
		}
	}, function () { return $.alert('Error al Buscar las cuentas Pago'); }, function () { return $.alert('Error al Buscar las cuentas Pago'); },
		function () { });
}

function cambiarPago(data) {
	$('#For_Cod2').data(data);
	data['For_Cod'] = data['Pago'] === 'Contado' ? 1 : 2;
	$('#changePagoForm').setData(data);
	$('#asigNegCamForm').setData(data);//para la negociacion
	$("#Cpc_Ven2").datepicker("option", "minDate", data['Vet_Fec']);
	if ($.isEmpty(data['Cpc_Ven'])) {
		var d = new Date(data['Vet_Fec']); d.setDate(d.getDate() + 15); $('#Cpc_Ven2').datepicker("setDate", d);
	}

	// Limpiar estado de los campos de clave cuando se abre el modal
	$('#empresaClaveInput').val('').prop('disabled', false);
	$('#validarClaveBtn').prop('disabled', false);
	$('#empresaClaveValidada').val('');
	$('#claveInputFeedback').text('').css('color', '');
	$('#claveInputSection').hide();
	$('#guardarBtn').hide();

	// Remover required de campos ocultos antes de abrir el modal
	$(".pagoTransf input, .pagoTransf select").removeAttr('required');
	$(".pagoCheque input, .pagoCheque select").removeAttr('required');
	$(".pagoCredito2 input, .pagoCredito2 textarea").removeAttr('required');

	$('#changePagoDialog').dialog('open');
	// Inicializar checkPago después de abrir el modal para establecer correctamente los required
	setTimeout(function () { checkPago(); }, 100);
}

/*
function cambiarPago(data) {
	$('#For_Cod2').data(data);
	data['For_Cod'] = data['Pago'] === 'Contado' ? 1 : 2;
	$('#changePagoForm').setData(data);
	$('#asigNegCamForm').setData(data);//para la negociacion
	$("#Cpc_Ven2").datepicker("option", "minDate", data['Vet_Fec']);
	if ($.isEmpty(data['Cpc_Ven'])) {
		var d = new Date(data['Vet_Fec']); d.setDate(d.getDate() + 15); $('#Cpc_Ven2').datepicker("setDate", d);
	}
	$('#changePagoDialog').dialog('open');
}*/

function checkPago() {
	var tipo_pago = $('#aux_Pag_Cod').val();
	var mostrarTransf = (tipo_pago == 3 || tipo_pago == 8);
	var mostrarCheque = (tipo_pago == 3);
	// Remover required de todos los campos primero
	$(".pagoTransf input, .pagoTransf select").removeAttr('required');
	$(".pagoCheque input, .pagoCheque select").removeAttr('required');
	// Mostrar/ocultar divs
	$(".pagoTransf").toggle(mostrarTransf);
	$(".pagoCheque").toggle(mostrarCheque);
	// Inicializar datepicker solo en campos visibles
	if (mostrarCheque) {
		// Inicializar datepicker en Fec_che solo cuando es visible
		if ($('#Fec_che').hasClass('hasDatepicker')) {
			$('#Fec_che').datepicker('destroy');
		}
		$('#Fec_che').addClass('datepickers').createDatePickers({ checkAvailability: true, hideMsg: false }).mask('9999-99-99', { placeholder: '_' });
		$(".pagoCheque input[type='text']:visible, .pagoCheque select:visible").attr('required', 'required');
	} else {
		// Destruir datepicker si el campo está oculto
		if ($('#Fec_che').hasClass('hasDatepicker')) {
			$('#Fec_che').datepicker('destroy').removeClass('datepickers');
		}
	}
	// Agregar required solo a campos visibles de transferencia
	if (mostrarTransf) {
		$(".pagoTransf input[type='text']:visible, .pagoTransf select:visible").attr('required', 'required');
	}
}
/*
function checkPago() {
	var tipo_pago = $('#aux_Pag_Cod').val();
	$(".pagoTransf").toggle(tipo_pago == 3 || tipo_pago == 8); // Mostrar input que se usan cuando es pago es por transferencia o cheque
	$(".pagoCheque").toggle(tipo_pago == 3); // Mostrar input que se usan cuando es pago es por cheque
	// $('.datepickers').createDatePickers({ checkAvailability: true, hideMsg: false }).mask('9999-99-99', { placeholder: '_' });
}*/
/*
function saveChangePago() {
	var data = $('#changePagoForm').getData('saveChangePago');
	$.createDialogConfirm('?Est&aacute; seguro que desea guardar los cambios?', data, function (d) {
		$.saveDataJson('', d, function (r) {
			$('#searchGrid').trigger('reloadGrid', []);
			$('#changePagoDialog').dialog('close');
		});
	});
}*/
function saveChangePago() {
	// Remover required de campos ocultos antes de validar el formulario
	$(".pagoTransf:hidden input, .pagoTransf:hidden select").removeAttr('required');
	$(".pagoCheque:hidden input, .pagoCheque:hidden select").removeAttr('required');
	$(".pagoCredito2:hidden input, .pagoCredito2:hidden textarea").removeAttr('required');
	// Asegurarse de que Fec_che no tenga required si está oculto
	if ($('.pagoCheque').is(':hidden')) {
		$('#Fec_che').removeAttr('required');
	}
	var data = $('#changePagoForm').getData('saveChangePago');
	// Incluir la clave validada si existe
	var claveValidada = $('#empresaClaveValidada').val();
	if (claveValidada) {
		data['empresaClaveValidada'] = claveValidada;
	}
	$.createDialogConfirm('¿Est&aacute; seguro que desea guardar los cambios?', data, function (d) {
		$.saveDataJson('', d, function (r) {
			$('#searchGrid').trigger('reloadGrid', []);
			$('#changePagoDialog').dialog('close');
			// Limpiar estado de los campos de clave después de guardar
			$('#empresaClaveInput').val('').prop('disabled', false);
			$('#validarClaveBtn').prop('disabled', false);
			$('#empresaClaveValidada').val('');
			$('#claveInputFeedback').text('').css('color', '');
			$('#claveInputSection').hide();
			$('#guardarBtn').hide();
		});
	});
}
function sumTot(v, f, rc) { var vd = (($.isUnd(v) || v === 0 || v === "") && !$.isUnd(rc.Via_Can) ? rc.Via_Can * rc.Via_Pru : v || 0); return vd; };
$(function () {
	// viajes
	var viajesCods = $('#viajesDialog');
	if (viajesCods.length === 1) {
		let cmVia = [
			{ label: 'C&oacute;d.Int.', name: 'Via_Cod', key: true, width: 15, align: "center", hidden: true },
			{ label: 'Fecha', name: 'Via_Fec', width: 25, align: "center" },
			{ label: 'Semana', name: 'Via_Sem', width: 15, align: "center" },
			{ label: 'Dia', name: 'Via_Dia', width: 20, align: "center", formatter: 'estado', formatoptions: { full: true, types: $.datepicker.regional['es'].dayNames }, classes: 'columnHighlight1' },
			{ label: 'Origen', name: 'Ori_Aco', width: 30, formatter: 'union', formatoptions: { sep: ' - ', cols: ['Ori_Zon'] } },
			{ label: 'Destino', name: 'Des_Aco', width: 30, formatter: 'union', formatoptions: { sep: ' - ', cols: ['Des_Zon'] } },
			{ label: 'Cant.', name: 'Via_Can', width: 20, formatter: 'number' },
			{ label: 'P.Unit.', name: 'Via_Pru', width: 30, formatter: 'number' }
		];
		$.createDateRange('#txt_fec_ini', '#txt_fec_fin', 7);
		$('#viajesSelectedDialog').createDialogDetail({
			totalCols: ['Via_Can', 'Via_Tot'], totalDefault: { Des_Aco: $.fieldSummarys() }, clearFootRow: true, footerrow: true, userDataOnFooter: false,
			colModel: cmVia.concat([
				{ label: 'Total', name: 'Via_Tot', width: 25, summaryType: 'sumTot', classes: 'bgNoColor', formatter: 'function', formatoptions: { formatter: 'number', noGroupFormat: true, data: function (o) { return o.Via_Can * o.Via_Pru; } } },
				{ label: 'ROWS', name: 'Rows', width: 25, align: "center", formatter: 'json', formatoptions: { full: true }, hidden: true },
				{ label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: "removeViaje", data: 'Via_Cod', type: 'danger', title: 'Remover Viaje', icon: 'remove' } }
			])
		});
		viajesCods.createSearchDialog({
			postExtra: {
				Via_Cod_Used: function () { return $('#viajesSelectedGrid').getCol('Via_Cod'); },
				Pla_Cod: function () { return $('#Pec_Cod').find(':selected').data('placod'); },
				Vet_Cod: function () { var inp = $('#editDoc').find('input[name=Vet_Cod]'); return inp.length === 1 ? inp.val() : undefined; }
			},
			colModel: cmVia.concat([
				{ label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: 'selectViaje' } }
			])
		});
	}






	// tickets
	var ticketsCods = $('#ticketsDialog');
	if (ticketsCods.length === 1) {
		// Función para convertir código de tipo de vehículo a descripción
		function getTipoVehiculoDesc(codigo) {
			var tipos = { 'V': 'Volqueta Sencilla', 'VM': 'Volqueta Mula', 'VB': 'Volqueta Bañera', 'D': 'TIPO DUMPER', 'B': 'Bus', 'C': 'CAMION', 'T': 'Tractor', 'M': 'Moto', 'O': 'Otro', 'F': 'Furgón' };
			// Convertir a string y hacer trim para asegurar la comparación
			var codigoStr = String(codigo || '').trim();
			return tipos[codigoStr] || codigoStr || '';
		}

		let cmTicket = [
			{ label: 'C&oacute;d.Int.', name: 'Tck_Cod', key: true, width: 15, align: "center", hidden: true },
			{ label: 'N&uacute;mero', name: 'Tck_Num', width: 20, align: "center" },
			{ label: 'Fecha', name: 'Tck_Fec', width: 25, align: "center", formatter: 'date' },
			{ label: 'Veh&iacute;culo', name: 'Veh_Pla', width: 30, align: "center" },
			{
				label: 'Tipo Veh.', name: 'Veh_Tip', width: 40, align: "center", formatter: 'function', formatoptions: {
					formatter: function (cellvalue) {
						return getTipoVehiculoDesc(cellvalue);
					}
				}
			},
			{
				label: 'Estado', name: 'Tck_Tip', width: 30, align: "center", formatter: function (cellvalue, options, rowObject) {
					// Obtener el valor de Tck_Tip desde rowObject si cellvalue no está disponible
					var tckTip = cellvalue || (rowObject && rowObject.Tck_Tip) || '';
					// Convertir a string y comparar
					tckTip = String(tckTip).trim();
					if (tckTip === 'A') return 'Activo';
					if (tckTip === 'F') return 'Facturado';
					return tckTip || '';
				}
			},
			{ label: 'Total', name: 'Tck_Tot', width: 30, formatter: 'number', align: "right" },
			{ label: 'Productos', name: 'Productos', width: 1, align: "center", hidden: true, formatter: 'json', formatoptions: { full: true } },
			{ label: 'ROWS', name: 'Rows', width: 25, align: "center", formatter: 'json', formatoptions: { full: true }, hidden: true },
			{
				label: '<input type="checkbox" id="select_all_tickets" title="Seleccionar todos" onclick="selectAllTickets(this);">', name: 'sel', width: 25, align: 'center', viewable: true, sortable: false, title: false, formatter: function (cellvalue, options, rowObject) {
					// Usar rowObject que contiene todos los datos de la fila
					var tckTip = (rowObject && rowObject.Tck_Tip) ? String(rowObject.Tck_Tip).trim() : '';
					var isFacturado = tckTip === 'F';
					// Si está facturado, no mostrar el checkbox
					if (isFacturado) return '';
					return '<input type="checkbox" class="ticket-row-select" data-row-id="' + options.rowId + '">';
				}
			}
		];

		$.createDateRange('#txt_ticket_fec_ini', '#txt_ticket_fec_fin', 7);
		$('#ticketsSelectedDialog').createDialogDetail({
			totalCols: ['Tck_Tot'], totalDefault: {}, clearFootRow: true, footerrow: true, userDataOnFooter: false,
			colModel: cmTicket.filter(function (col) { return col.name !== 'sel'; }).concat([
				{ label: 'ROWS', name: 'Rows', width: 25, align: "center", formatter: 'json', formatoptions: { full: true }, hidden: true },
				{ label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: "removeTicket", data: 'Tck_Cod', type: 'danger', title: 'Remover Ticket', icon: 'remove' } }
			])
		});
		var ticketsGridOptions = {
			datatype: 'json', // Cambiar a 'json' para que cargue automáticamente
			postExtra: {
				Tck_Cod_Used: function () { return $('#ticketsSelectedGrid').getCol('Tck_Cod'); },
				Pla_Cod: function () { return $('#Pec_Cod').find(':selected').data('placod'); },
				Vet_Cod: function () { var inp = $('#editDoc').find('input[name=Vet_Cod]'); return inp.length === 1 ? inp.val() : undefined; },
				Tck_Tip: function () { return $('#ticketsEstado').val() || 'A'; }
			},
			footerrow: true,
			userDataOnFooter: false,
			rowattr: function (rowData) {
				// Cambiar el color de fondo de las filas con Tck_Tip='F'
				if (rowData && rowData.Tck_Tip === 'F') {
					return { 'style': 'background-color: #d3d3d3 !important; color: #666;' };
				}
				return {};
			},
			colModel: cmTicket.concat([]),
			loadComplete: function (data) {
				// Actualizar los checkboxes con el Tck_Cod correcto después de cargar los datos
				// Solo procesar tickets que no estén facturados (los facturados no tienen checkbox)
				var grid = $('#ticketsGrid');
				var allRowIds = grid.jqGrid('getDataIDs');
				$.each(allRowIds, function (i, rowId) {
					var rowData = grid.jqGrid('getRowData', rowId, true);
					if (rowData && rowData.Tck_Cod) {
						var isFacturado = rowData.Tck_Tip === 'F' || String(rowData.Tck_Tip).trim() === 'F';
						// Solo procesar si no está facturado (los facturados no tienen checkbox)
						if (!isFacturado) {
							var checkbox = grid.find('tr#' + $.jgrid.jqID(rowId) + ' .ticket-row-select');
							if (checkbox.length > 0) {
								checkbox.attr('data-tck-cod', rowData.Tck_Cod);
								// Verificar si el ticket ya está seleccionado
								var existingRow = $('#ticketsSelectedGrid').jqGrid('getRowData', rowData.Tck_Cod);
								if (existingRow && existingRow.Tck_Cod) {
									checkbox.prop('checked', true);
								}
							}
						}
					}
				});

				// Calcular y mostrar el total en el footer
				if (data && data.rows) {
					var total = 0;
					for (var i = 0; i < data.rows.length; i++) {
						var tckTot = parseFloat(data.rows[i]['Tck_Tot'] || 0);
						if (!isNaN(tckTot)) {
							total += tckTot;
						}
					}
					grid.jqGrid('footerData', 'set', {
						Tck_Num: 'TOTAL:',
						Tck_Tot: total.toFixed(2)
					});
				}
			}
		};
		ticketsCods.createSearchDialog(ticketsGridOptions);
		// Configurar altura del diálogo de tickets
		$('#ticketsDialog').dialog('option', 'height', 430);
		// Asegurar que el selector tenga el valor por defecto 'A' y cargar datos al abrir el modal
		$('#ticketsDialog').on('dialogopen', function () {
			// Configurar altura del diálogo cada vez que se abre
			$(this).dialog('option', 'height', 430);
			$('#ticketsEstado').val('A');
		});
		// Evento para recargar el grid cuando cambie el estado
		$('#ticketsEstado').on('change', function () {
			$('#ticketsGrid').trigger('reloadGrid');
		});
		// Función para seleccionar/deseleccionar todos los tickets - funciona como toggle
		window.selectAllTickets = function (checkbox) {
			// Obtener todos los checkboxes habilitados
			var $checkboxes = $('.ticket-row-select:not(:disabled)');
			var totalCheckboxes = $checkboxes.length;
			if (totalCheckboxes === 0) {
				return;
			}
			// Contar cuántos están seleccionados
			var checkedCount = $checkboxes.filter(':checked').length;
			var newState = checkedCount < totalCheckboxes;
			checkbox.checked = newState;
			$checkboxes.each(function () {
				this.checked = newState;
				// Actualizar data-tck-cod si está marcado
				if (newState) {
					var rowId = $(this).data('row-id') || $(this).closest('tr').attr('id');
					if (rowId) {
						var rowData = $('#ticketsGrid').jqGrid('getRowData', rowId, true);
						if (rowData && rowData.Tck_Cod && rowData.Tck_Tip !== 'F') {
							$(this).attr('data-tck-cod', rowData.Tck_Cod);
						}
					}
				} else {
					// Si se deselecciona, limpiar el atributo data-tck-cod
					$(this).removeAttr('data-tck-cod');
				}
			});
		};
		// Función antigua - mantener para compatibilidad pero ya no se usa
		window.selectAllTicketsOld = function (checkbox) {
			var allChecked = checkbox.checked;
			var grid = $('#ticketsGrid');
			var allRowIds = grid.jqGrid('getDataIDs');
			if (allChecked) {
				// Seleccionar todos los tickets
				$.each(allRowIds, function (i, rowId) {
					// Usar getRowData con usedata:true para obtener los datos originales completos
					var rowData = grid.jqGrid('getRowData', rowId, true);
					if (rowData && rowData.Tck_Cod) {
						// Si no tiene Productos, intentar desde Rows
						if ((!$.vv(rowData.Productos) || !$.isArray(rowData.Productos)) && $.vv(rowData.Rows)) {
							try {
								var rowsData = typeof rowData.Rows === 'string' ? JSON.parse(rowData.Rows) : rowData.Rows;
								if ($.vv(rowsData) && $.vv(rowsData.Productos)) {
									rowData.Productos = rowsData.Productos;
								} else if ($.vv(rowsData)) {
									rowData = $.extend({}, rowData, rowsData);
								}
							} catch (e) {
								console.log('Error parsing Rows data:', e);
							}
						}
						// Verificar si ya está seleccionado
						var existingRow = $('#ticketsSelectedGrid').jqGrid('getRowData', rowData.Tck_Cod);
						if (!existingRow || !existingRow.Tck_Cod) {
							selectTicket(rowData);
						} else {
							// Solo marcar el checkbox si ya está seleccionado
							$('.ticket-row-select[data-tck-cod="' + rowData.Tck_Cod + '"]').prop('checked', true);
						}
					}
				});
			} else {
				// Deseleccionar todos los tickets
				var selectedGrid = $('#ticketsSelectedGrid');
				var selectedRowIds = selectedGrid.jqGrid('getDataIDs');
				$.each(selectedRowIds, function (i, rowId) {
					var rowData = selectedGrid.jqGrid('getRowData', rowId);
					if (rowData && rowData.Tck_Cod) {
						selectedGrid.jqGrid('delRowData', rowData.Tck_Cod);
						$('.ticket-row-select[data-tck-cod="' + rowData.Tck_Cod + '"]').prop('checked', false);
					}
				});
				updateTicketProductos();
			}
		};

		// Evento para cuando se marca/desmarca un checkbox individual - solo marca/desmarca, no selecciona
		$(document).on('change', '.ticket-row-select', function () {
			var rowId = $(this).data('row-id') || $(this).closest('tr').attr('id');
			var grid = $('#ticketsGrid');
			// Solo actualizar el data-tck-cod si está marcado
			if (this.checked && rowId) {
				var rowData = grid.jqGrid('getRowData', rowId, true);
				if (rowData && rowData.Tck_Cod) {
					$(this).attr('data-tck-cod', rowData.Tck_Cod);
				}
			}
			// Actualizar el checkbox de "seleccionar todos"
			var totalTickets = $('.ticket-row-select').length;
			var checkedTickets = $('.ticket-row-select:checked').length;
			$('#select_all_tickets').prop('checked', totalTickets > 0 && checkedTickets === totalTickets);
		});
		window.seleccionarTicketsMarcados = function () {
			var grid = $('#ticketsGrid');
			var checkboxesMarcados = $('.ticket-row-select:checked');
			if (checkboxesMarcados.length === 0) {
				return $.alert("Debe seleccionar al menos un ticket marcando el checkbox.");
			}
			// Recopilar todos los datos de los tickets marcados
			var allTicketsData = [];
			var totalCan = 0;
			var totalImp = 0;
			var totalIva = 0;
			var totalTot = 0;
			var allTicketNums = [];
			var firstTicketData = null;
			var firstProduct = null;
			var allProducts = {};
			checkboxesMarcados.each(function () {
				var tckCod = $(this).data('tck-cod');
				var rowId = $(this).data('row-id') || $(this).closest('tr').attr('id');
				if (tckCod && rowId) {
					// Obtener los datos completos de la fila
					var rowData = grid.jqGrid('getRowData', rowId, true);
					if (rowData && rowData.Tck_Cod) {
						// Si no tiene Productos, intentar desde Rows
						if ((!$.vv(rowData.Productos) || !$.isArray(rowData.Productos)) && $.vv(rowData.Rows)) {
							try {
								var rowsData = typeof rowData.Rows === 'string' ? JSON.parse(rowData.Rows) : rowData.Rows;
								if ($.vv(rowsData) && $.vv(rowsData.Productos)) {
									rowData.Productos = rowsData.Productos;
								} else if ($.vv(rowsData)) {
									rowData = $.extend({}, rowData, rowsData);
								}
							} catch (e) {
								console.log('Error parsing Rows data:', e);
							}
						}
						if (rowData) {
							allTicketsData.push(rowData);
							allTicketNums.push(rowData.Tck_Num || rowData.Tck_Cod);
							// Guardar el primer ticket como base
							if (!firstTicketData) {
								firstTicketData = rowData;
							}
							// Acumular totales del ticket
							if (rowData.Tck_Val) totalImp += parseFloat(rowData.Tck_Val) || 0;
							if (rowData.Tck_IvA) totalIva += parseFloat(rowData.Tck_IvA) || 0;
							if (rowData.Tck_Tot) totalTot += parseFloat(rowData.Tck_Tot) || 0;
							// Acumular productos y cantidades
							if ($.vv(rowData.Productos) && $.isArray(rowData.Productos)) {
								$.each(rowData.Productos, function (j, prod_tick) {
									if (!$.vv(prod_tick.Producto)) return;
									if (!firstProduct) {
										firstProduct = prod_tick.Producto;
									}
									var proCod = prod_tick.Producto.Pro_Cod;
									if (!allProducts[proCod]) {
										allProducts[proCod] = { Producto: prod_tick.Producto, Tck_Can: 0, Tck_Imp: 0 };
									}
									// Convertir a número de manera segura
									var cantidad = typeof prod_tick.Tck_Can === 'number' ? prod_tick.Tck_Can : (parseFloat(prod_tick.Tck_Can) || 0);
									// Usar Tck_Imp del producto, o si no existe, calcular desde Tck_Tot o usar el valor del ticket
									var importe = 0;
									if (typeof prod_tick.Tck_Imp === 'number') {
										importe = prod_tick.Tck_Imp;
									} else if (typeof prod_tick.Tck_Imp === 'string') {
										importe = parseFloat(prod_tick.Tck_Imp) || 0;
									} else if (prod_tick.Tck_Tot) {
										importe = typeof prod_tick.Tck_Tot === 'number' ? prod_tick.Tck_Tot : (parseFloat(prod_tick.Tck_Tot) || 0);
									}
									// Si aún no hay importe, usar el total del ticket dividido entre productos
									if (importe === 0 && rowData.Tck_Tot && rowData.Productos && rowData.Productos.length > 0) {
										importe = (parseFloat(rowData.Tck_Tot) || 0) / rowData.Productos.length;
									}
									allProducts[proCod].Tck_Can += cantidad;
									allProducts[proCod].Tck_Imp += importe;
									totalCan += cantidad;
									totalImp += importe; // Acumular también en totalImp desde productos
								});
							}
						}
					}
				}
			});
			if (allTicketsData.length === 0) {
				return $.alert("No se pudieron cargar los datos de los tickets seleccionados.");
			}
			// Recopilar todos los Tck_Cod originales
			var allTckCods = [];
			$.each(allTicketsData, function (i, t) {
				if (t.Tck_Cod) {
					allTckCods.push(t.Tck_Cod);
				}
			});
			// Crear un único ticket consolidado con los valores acumulados
			var ticketConsolidado = $.extend(true, {}, firstTicketData);
			// Guardar los códigos originales de todos los tickets para poder actualizarlos al guardar
			ticketConsolidado.Tickets_Originales = allTckCods;
			ticketConsolidado.Tickets = allTckCods; // También guardar en Tickets para compatibilidad
			// Actualizar valores acumulados
			// Si totalImp es 0 pero totalTot tiene valor, calcular desde totalTot
			var valorNetoFinal = totalImp > 0 ? totalImp : (totalTot > 0 ? (totalTot - totalIva) : 0);
			var totalFinal = totalTot > 0 ? totalTot : (totalImp + totalIva);
			ticketConsolidado.Tck_Val = valorNetoFinal.toFixed(4);
			ticketConsolidado.Tck_IvA = totalIva.toFixed(4);
			ticketConsolidado.Tck_Tot = totalFinal.toFixed(4);
			// Actualizar número de ticket para mostrar todos los números
			if (allTicketNums.length > 1) {
				ticketConsolidado.Tck_Num_Display = 'Consolidado: #' + allTicketNums.join(', #');
			}
			var importeParaProducto = totalImp > 0 ? totalImp : totalFinal;
			if (firstProduct) {
				var precioUnitario = 0;
				var cantidadFinal = totalCan;
				var importeFinal = importeParaProducto;
				if (totalCan === 0 && totalFinal > 0) {
					cantidadFinal = 1;
					precioUnitario = importeFinal;
				} else if (totalCan > 0) {
					precioUnitario = importeFinal / totalCan;
				} else if (firstTicketData.Productos && firstTicketData.Productos.length > 0) {
					var primerProd = firstTicketData.Productos[0];
					cantidadFinal = primerProd.Tck_Can || 1;
					importeFinal = primerProd.Tck_Imp || primerProd.Tck_Tot || importeFinal;
					precioUnitario = cantidadFinal > 0 ? importeFinal / cantidadFinal : importeFinal;
				}
				var productosConsolidados = [{ Producto: firstProduct, Tck_Can: cantidadFinal, Tck_Pru: precioUnitario, Tck_Imp: importeFinal }];
				ticketConsolidado.Productos = productosConsolidados;
			}
			// Usar el primer Tck_Cod como ID para el grid, pero guardar todos los originales
			var ticketId = firstTicketData.Tck_Cod || 'CONSOLIDADO_' + Date.now();
			// Limpiar el grid de tickets seleccionados antes de agregar el consolidado
			$('#ticketsSelectedGrid').clearGridData();
			// Agregar solo este ticket consolidado
			$('#ticketsSelectedGrid').setRow(ticketConsolidado, ticketId);
			// Remover todos los tickets del grid de búsqueda
			$.each(allTckCods, function (i, tckCod) {
				$('#ticketsGrid').delRowData(tckCod);
			});
			// Actualizar productos en la factura
			updateTicketProductos();
			// Desmarcar todos los checkboxes después de seleccionar
			$('.ticket-row-select').prop('checked', false);
			$('#select_all_tickets').prop('checked', false);
			$.alert(allTicketsData.length + " ticket(s) consolidado(s) en una sola factura.");
			$('#ticketsDialog').dialog('close');
		};
	}

});

//Contar caracteres
function contarCaracteres(elemento) {
	// Obtener el texto del textarea
	var texto = elemento.value;
	var longitud = texto.length;
	var caracteresRestantes = 300 - longitud;
	if (longitud > 300) {
		elemento.value = texto.slice(0, 300); // Recortar el texto a 300 caracteres
	}
	var caracteresRestantes = 300 - elemento.value.length;
	document.getElementById('contador').innerText = caracteresRestantes + ' caracteres restantes';
}

//Nuevo metodo CRUD para datos extras
// Guardar info extra
function addInfoExtra() {
	$.saveDataJson('',
		$('#ExtinfoCreateForm').getData('saveExtRutAjax'),
		function (resp) {
			load_datos_extras();
			return false;
		});
}

function load_datos_extras() {
	lista_datos_extra.createGrid({
		width: '100%', height: 160, mtype: "GET", datatype: "json", regional: 'es', responsive: false, autowidth: true, shrinkToFit: true,
		cmTemplate: { sortable: false },
		colModel: [{ label: 'Cod.Int.', name: 'Ext_Cod', width: 70, key: true, align: "left", hidden: true },
		{ label: 'Nombre', name: 'Ext_Nom', width: 80, align: "left" },
		{ label: 'Ciudad', name: 'Ext_Ciu', width: 80, align: "left" },
		{ label: 'Destino', name: 'Ext_Dest', width: 80, align: "left" },
		{ label: 'Telf', name: 'Ext_Telf', width: 50, align: "left" },
		{ label: 'Ruta', name: 'Ext_Ruta', width: 100, align: "left" },
		{ label: 'Fecha', name: 'Ext_Fec', width: 80, align: "left" },
		{
			label: '&nbsp;', name: 'act1', width: 30, align: 'center', viewable: false,
			formatter: function (cellvalue, options, rowObject) {
				return "<button  style='border: 1px solid #285e8e; border-radius: 2px;' class='icon-btn btn-primary' data-row-id='" + options.rowId + "'><i class='fa fa-edit'></i></button>";
			}
		},
		{ label: '&nbsp;', name: 'act3', width: 30, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: selectItemExt, } },
		{
			label: '&nbsp;', name: 'act2', width: 30, align: 'center', viewable: false,
			formatter: function (cellvalue, options, rowObject) {
				return "<button style='border: 1px solid #c63632; border-radius: 2px;' class='delete-btn btn-danger p-0' data-row-id='" + options.rowId + "'><i class='fa fa-trash'></i></button>";
			}
		}
		],
		pager: "#Datos_Ext", rownumbers: true, rowNum: 10, gridview: true, viewrecords: true,
		loadComplete: function () {
		}
	}, false, "");
}

function selectItemExt(rowid) {
	var descripcion = "";
	if (rowid['Ext_Nom']) {
		descripcion += "Destinatario: " + rowid['Ext_Nom'] + "\n";
	}
	if (rowid['Ext_Ruta']) {
		descripcion += "Ruta: " + rowid['Ext_Ruta'] + "\n";
	}
	if (rowid['Ext_Dest']) {
		descripcion += "Destino: " + rowid['Ext_Dest'] + "\n";
	}
	if (rowid['Ext_Telf']) {
		descripcion += "Celular: " + rowid['Ext_Telf'] + "\n";
	}
	if (rowid['Ext_Fec']) {
		descripcion += "Fecha: " + rowid['Ext_Fec'] + "\n";
	}
	// Si la descripción no está vacía, la establece en el campo
	if (descripcion) {
		$("#Vet_Obs").val(descripcion.trim());
	}
	var textarea = document.getElementById('Vet_Obs');
	contarCaracteres(textarea);
}

function deleteItemExt(rowid) {
	var userConfirmed = window.confirm("¿Estás seguro de que deseas eliminar este item?");
	if (userConfirmed) {
		$.getDataJson("", { detelteExtAjax: true, Ext_Cod: rowid.Ext_Cod }, function (resProformas) {
			$.alert(resProformas.message, null, 'remove');
		});
	}
}

function updateItemExt(rowid) {
	$("#Ext_Nom").val(rowid['Ext_Nom']);
	$("#Ext_Ruta").val(rowid['Ext_Ruta']);
	$("#Ext_Dest").val(rowid['Ext_Dest']);
	$("#Ext_Fec").val(rowid['Ext_Fec']);
	$("#Ext_Ciu").val(rowid['Ext_Ciu']);
	$("#Ext_Cod").val(rowid['Ext_Cod']);
	$("#Ext_Telf").val(rowid['Ext_Telf']);
}
// Asigna el evento click al botón
$(document).ready(function () {
	$("#lista_datos_extra").on("click", ".icon-btn", function () {
		var rowId = $(this).data("row-id");
		var rowData = $("#lista_datos_extra").jqGrid('getRowData', rowId);
		updateItemExt(rowData);
	});
	// Asigna el evento click para eliminar
	$("#lista_datos_extra").on("click", ".delete-btn", function () {
		var rowId = $(this).data("row-id");
		var rowData = $("#lista_datos_extra").jqGrid('getRowData', rowId);
		deleteItemExt(rowData);
	});
});
//LISTA DE NEGOCIACIÓN
function armargrid() {
	containerNegoci.createGrid({
		width: 260, height: 140,
		colModel: [{ label: 'Cod.Cop', name: 'Cod_Neg', width: 30 },
		{ label: 'Num.Agu', name: 'Num_Neg', width: 80 },
		{ label: '&nbsp;', name: 'act1', width: 30, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: selectNego } },
		],
		jsonReader: { root: "response", repeatitems: false },
		datatype: "local",
		footerrow: false,
	});
}

function selectNego(data) {
	var isFacAut = $("#isUpdateNego").val();
	if (isFacAut == "facIsAut") {
		$('#asigNegCamForm #Num_Neg').val(data['Num_Neg']);
		$('#asigNegCamForm #Cod_Neg').val(data['Cod_Neg']);

	} else {
		$('#formDocumento #Num_Neg').val(data['Num_Neg']);
		$('#formDocumento #Cod_Neg').val(data['Cod_Neg']);
	}
	$('#negDialog').dialog('close');
}

//Formulario para asignar negociación 
function saveNegCamPago() {
	var data = $('#asigNegCamForm').getData('saveChangeCamNeg');
	$.createDialogConfirm('¿Est&aacute; seguro que desea guardar los cambios?', data, function (d) {
		$.saveDataJson('', d, function (r) {
			$('#searchGrid').trigger('reloadGrid', []);
			$('#changePagoDialog').dialog('close');
		});
	});
}
//Limpiar campos de la negociacion
function limpiarCamposNego() {
	document.getElementById("Num_Neg").value = "";
	document.getElementById("Cod_Neg").value = "";
}
//Limpiar campos del modal negociacion
function limpiarCamposNego1() {
	document.querySelector("#asigNegCamForm #Num_Neg").value = "";
	document.querySelector("#asigNegCamForm #Cod_Neg").value = "";
}
