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
					{ label: '<i class="glyphicon glyphicon-remove"></i>', name: 'btn_remover', width: 20, align: 'center', formatter: 'gridButton',
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
					case 'TARJETA DE CREDITO':
						// $('.banco,.cuen_ban,.info-tarjeta').show().find(':input').attr('required', 'required');
						// $('.cuenta_pago').hide().removeAttr('disabled');
						$('.banco,.cuen_ban,.info-tarjeta').show();
						$('.banco,.cuen_ban').find(':input').attr('required', 'required');
						$('.info-tarjeta').find(':input').removeAttr('required');
						$('.cuenta_pago').hide().removeAttr('disabled');
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
				{ label: 'Forma', name: 'For_Cod', width: 30, classes: 'bgNoRight',
					formatter: function (cv, opts, rObj) {
						return '<div data-val="' + cv + '">' + $('#For_Cod option[value="' + cv + '"]').text() + '</div>';
					}, unformat: function (el, opts, cell) {
						return $('div', cell).data('val');
					}
				},
				{ label: 'Forma_Cod', name: 'Forma_Cod', width: 30, hidden: true, classes: 'bgNoRight' },
				{ label: 'Fec_che', name: 'Fec_che', width: 30, hidden: true, classes: 'bgNoRight' },
				{ label: 'Bak_Cod', name: 'Bak_Cod', width: 30, hidden: true, classes: 'bgNoRight' },
				{ label: 'Tipo', name: 'Pag_Cod', width: 30, classes: 'bgNoRight',
					formatter: function (cv, opts, rObj) {
						return $('#Pag_Cod option[value="' + cv + '"]').text();
					}
				},
				{ label: 'Tipo_Cod', name: 'Tipo_Cod', width: 30, hidden: true, classes: 'bgNoRight',
					formatter: function (cv, opts, rObj) {
						return $('#Pag_Cod option[value="' + cv + '"]').val();
					}
				},
				{ label: 'Pag_Pld', name: 'Pag_Pld', width: 30, hidden: true, classes: 'bgNoRight' },
				{ label: 'Banco', name: 'Vet_Ban', width: 50, align: 'center', classes: 'bgNoRight',
					formatter: function (cv, opts, rObj) {
						var ban = $.varValid(rObj['Ban_Cod']) && rObj['Ban_Cod'].length > 0 ? 'Ban_Cod' : ($.varValid(rObj['Bak_Cod']) && rObj['Bak_Cod'].length > 0 ? 'Bak_Cod' : null);
						if ($.varValid(ban)){
							return $('#' + ban + ' option[value="' + rObj[ban] + '"]').text(); 
						}else{
							return '';
						}
					}
				},
				{ label: 'Cta. Banco', name: 'Vet_Cue', width: 50, align: 'center', classes: 'bgNoRight' },
				{ label: 'Doc./Cheque', name: 'Vet_Che', width: 50, align: 'center' },
				// dinero ingresado
				{ label: 'Monto Ing.', name: 'Vet_Mon', width: 40, align: 'center', formatter: 'currency'},
				{ label: 'Cambio', name: 'Vet_Cam', width: 40, align: 'center', formatter: 'currency' },
				{ label: 'Monto', name: 'Vet_Tot', width: 40, align: 'right', formatter: 'currency', classes: 'bgNoColor' },
				// dias de plazo para credito
				{ label: 'Dias. Plazo', name: 'Vet_Plz', width: 50, align: 'center'/*, hidden: true*/ },
				{ label: '<i class="glyphicon glyphicon-remove"></i>', name: 'btn_remover', width: 20, align: 'center', formatter: 'gridButton',
					formatoptions: {
						action: deletePago, data: function (o) { return o.Vet_Num; },
						icon: 'remove', type: 'danger'
					}
				}
			], loadComplete: function () { $(this).setGridSummary(['Vet_Tot'], { Vet_Che: '<div style="text-align:right;">TOTAL:</div>' }); }
		}, true, 'pagosPager', { view: false }).gridButtonsAdd([
			// Seccion de botones para la forma de pago 
			{ caption: 'Agregar', buttonicon: 'glyphicon glyphicon-plus', class: 'a',
				onClickButton: function () {
					if ($('#Val_Pcc_2').val() * 1 <= 0) {
						$.alert('El saldo a cobrar es cero!'); return;
					} registarPagos();
				}
			}, {},
			{ caption: 'Al Contado', buttonicon: 'glyphicon glyphicon-usd',
				onClickButton: function () {
					if ($('#Val_Pcc_2').val() * 1 <= 0) {
						$.alert('El saldo a cobrar es cero!'); return;
					} alContado();
				}
			},
			{ caption: 'A Cr&eacute;dito', buttonicon: 'glyphicon glyphicon-credit-card',
				onClickButton: function () {
					if ($('#Val_Pcc_2').val() * 1 <= 0) {
						$.alert('El saldo a cobrar es cero!'); return;
					} aCredito();
				}
			}
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
				{ name: 'Vet_Dec', label: 'Descuen.', labelLong: 'Descuento', align: 'right', width: 20 },
				{ name: 'Vet_Pru', label: 'P. Unitario', labelLong: 'Precio Unitario', width: 60, align: 'right', title: false/*, summaryRound: 8,formatter:"currency",formatoptions: {prefix:'', thousandsSeparator:',',decimalSeparator:'.',decimalPlaces: 8, defaultValue: ''}*/, editable: (edit_reten ? false : true), editoptions: { dataInit: stylePru } },
				{ name: 'Vet_Imp', label: 'Importe', width: 70, align: 'right', summaryRound: 4, formatter: dynamicFormatter, formatoptions: { prefix: '', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '0.00' }, classes: 'columnHighlight1' },
				{ name: 'Iva_Cod', label: 'CodIva', width: 20, hidden: true },
				//{name:'Iva_Por',label:'IVA', width:15,align:'center', formatter:'truefalse', formatoptions:{yesMsg:'Grava IVA',noMsg:'No Grava IVA'}, title:false, resizable: false },
				//CAMPO NUEVO
				{ name: 'Iva_Por', label: 'IVA(%)', labelLong: 'Porcentaje IVA', width: 35, align: 'center', title: false, resizable: false },
				{ name: 'Ice_Int', label: 'CodIce', width: 20, hidden: true },
				{ name: 'Ice_Por', label: 'ICE %', width: 20, align: 'right', title: false, resizable: false },
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
			var mon = $('#monto_pago').val(), sal = $('#saldo_pago').val(), cam = (!isNaN(mon) && !isNaN(sal) ? $.round(mon) - $.round(sal) : 0);
			$('#cambio_pago').val($.toFixed(cam));
			$('#cam_sal').removeClass('alert-danger alert-success').addClass(cam < 0 ? 'alert-danger' : 'alert-success').find('b').html(cam < 0 ? 'Por Cobrar' : 'Cambio');
		}).on('change', function () {
			var monto = $(this).attr('id') === 'monto_pago', val = $(this).val(), sal = $('#Val_Pcc').val() * 1;
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
function cambioTipoDoc(i=null){
	var op= $('input:radio[name=Vet_Ide]:checked').val();
    if($('input[name="Prs_Ced"]').val()!==''){            
            
            if(op =='4')if($('input[name="Prs_Ced"]').val().length<13) $('input[name="Prs_Ced"]').val($('input[name="Prs_Ced"]').val()+'001');                            
            if(op =='5')if($('input[name="Prs_Ced"]').val().length>10) $('input[name="Prs_Ced"]').val($('input[name="Prs_Ced"]').val().substring(0,10));                
			if(op =='6')if($('input[name="Prs_Ced"]').val().length>13) $('input[name="Prs_Ced"]').val($('input[name="Prs_Ced"]').val().substring(0,13));                           
    }
}

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

var num_old = 0;
var editDoc = false; // cambia a true si estás editando un documento existente

function validarNum(vet_num_ant) {
    if ($('#Tic_Cod').val() * 1 > 0) {
        var sel_tcompr = $('#Tic_Cod').find('option:selected').data();
        var documento_sel = $('#Tic_Cod').find('option:selected').text().split('-')[1];

        $.post('fac_alt_vent_dup_1.0.php', { // ajusta el archivo PHP
            numeroSec: true,
            Tic_Cod: sel_tcompr['ticcod'],
            Aut_Sri: sel_tcompr['autsri'],
            Pun_Sri: sel_tcompr['punsri'],
            Aut_Cod: sel_tcompr['autcod'],
            Aut_Ini: sel_tcompr['autini'],
            Aut_Fin: sel_tcompr['autfin']
        }, function (response) {
            if (!response || !response['Vet_Num']) {
                $('#Vet_Num').fieldValid('warning', 'No se pudo obtener el número de secuencia.');
                return;
            }

            // Si es edición, mantiene el número anterior
            var vnum = (editDoc && vet_num_ant > 0) ? vet_num_ant : response['Vet_Num'];

            // Setea los datos en el formulario
            $('#formDocumento').setData({
                'Pun_Sri': sel_tcompr['sucsri'] + '-' + sel_tcompr['punsri'] + '-',
                'Vet_Num': vnum,
                'Aut_Sri': (sel_tcompr['auttem'] === 'E' ? 'Electrónica' : sel_tcompr['autsri'])
            }, false);

            // Calcula documentos restantes
            var doc_disponibles = (sel_tcompr['autfin'] * 1 - sel_tcompr['autini'] * 1) - response['contador'];
            if (doc_disponibles <= sel_tcompr['autads'] * 1 && !window.Nota_CreDeb)
                alertaAuto('Quedan <b>' + doc_disponibles + ' ' + documento_sel + 'S</b> disponibles', '#Vet_Num', 'right');

            validarTic_Cod(true);
            num_old = response['Vet_Num'];
        }, 'json').fail(function () {
            $.alert('Error al consultar número de secuencia');
        });
    }
}

// Ejecutar al cambiar tipo de comprobante
$('#Tic_Cod').change(function() {
    validarNum();
});


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
	$('#Tic_Cod').trigger('change');
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
	number = number.trim();
	var digitos = number.split(""), dto = digitos.length, acu = 0, resp = { success: false, message: '', doc_num: number },
		coef = { 'NA': [2, 1, 2, 1, 2, 1, 2, 1, 2], 'PU': [3, 2, 7, 6, 5, 4, 3, 2, 0], 'PR': [4, 3, 2, 7, 6, 5, 4, 3, 2] }, modulo, acum = 0;
	if (dto === 0) {
		resp['message'] = 'No has ingresado ning\u00fan dato!';
	} else {
		for (var i = 0; i < dto; i++) if (!isNaN(digitos[i])) { digitos[i] = digitos[i] * 1; acu = acu + 1; }
			if (acu === dto) {
				var tipo = digitos[2], prov = number.substring(0, 2) * 1;
				tercer_digito = tipo;
				if (tipo === 7 || tipo === 8) resp['message'] = '"El tercer d\u00edgito ingresado es inv\u00e1lido"';
				else {
					tipo = (tipo < 6 ? 'NA' : (tipo === 6 ? 'PU' : (tipo === 9 ? 'PR' : '')));
					if ((tipo === 'PU' && dto === 10) || (tipo === 'PU' && dto === 13 && number.substring(9, 13) !== '0001')) tipo = 'NA';
					modulo = (tipo === 'NA' ? 10 : 11); resp['tipo_abrev'] = tipo;
					resp['tipo'] = (tipo === 'NA' ? 'Natural' : (tipo === 'PR' ? 'Privada' : (tipo === 'PU' ? 'P\u00fablica' : '')));
				}
				if (dto !== 10 && dto !== 13) { resp['message'] = 'La cantidad de d\u00EDgitos deben ser 10 o 13'; return resp; } else { resp['doc_abr'] = (dto === 10 ? 'C' : (dto === 13 ? 'R' : '')); resp['doc'] = (dto === 10 ? 'C\u00E9dula' : (dto === 13 ? 'R.U.C.' : '')); }
				if (prov < 1 && prov > 24 && prov != 30) resp['message'] = 'Los dos primeros d\u00EDgitos no pueden ser mayores a 24, o diferente a 30.';
				if (dto === 13) {
					if (number.substring(10, 13) !== '001') resp['message'] = 'Los tres \u00faltimos d\u00EDgitos no tienen el c\u00F3digo del RUC 001.';
					if (tipo === 'PU' && number.substring(9, 13) !== '0001') resp['message'] = 'El R.U.C. de la empresa del sector p\u00fablico debe terminar con 0001';
				} else if ((tipo === 'PU' || tipo === 'PR')) resp['message'] = 'El R.U.C. de las empresas ' + resp['tipo'] + 's deben tener 13 digitos!';

				if (resp['message'].length > 0) { return resp };

				for (var a = 0; a < 9; a++) {
					var resul = digitos[a] * coef[tipo][a];
					acum += (resul - (tipo === 'NA' && resul >= 10 ? 9 : 0));
				}
				var residuo = acum % modulo, digitoVerificador = residuo === 0 ? 0 : modulo - residuo;
				if (digitos[(tipo === 'PU' ? 8 : 9)] !== digitoVerificador) resp['message'] = 'El n\u00famero de ' + resp['doc'] + ' de la ' + (tipo === 'NA' ? 'Persona Natural' : 'Empresa ' + resp['tipo']) + ' ingresado es inv\u00E1lido!';

				// if(resp['message'].length===0) resp['success']=true;

				if (resp['message'].length === 0) {
					resp['success'] = true
				} else if (tipo == "PR" && tercer_digito == 9) {//Validar RUC privado
					resp['success'] = true;
				}
			} else {
				resp['message'] = "ERROR: Solo debe contener d\u00EDgitos!";
			}
	}
	return resp;
}

// nuevo bloque de codigo 08/10/2025
$(document).ready(function() {
    var validandoDiaCred = false; // bandera global de control

    $("#Dia_Cred").on("blur", function() {
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
            success: function(response) {
				console.log("data:", response)
                if (response.success && response.data) {
                    var diaCreditoMax = parseInt(response.data.Dia_Cred, 10);
					// console.log("Valor ingresado:", diasIngresados, " | Máximo permitido:", response.data.Dia_Cred);
                    if (diasIngresados > diaCreditoMax) {
                        $.alert("No se puede exceder los días de crédito para este cliente (" + diaCreditoMax + " días máximo).");
                        $("#Dia_Cred").val("0");
                        setTimeout(function() { $("#Dia_Cred").focus(); }, 300);
                    }
                } else {
                    $.alert("No se pudo obtener el cupo de crédito del cliente.");
                    $("#Dia_Cred").val("0");
                }
            },
            error: function() {
                $.alert("Error al consultar el cupo de crédito.");
                $("#Dia_Cred").val("0");
            },
            complete: function() {
                // Restablecer bandera al terminar la validación
                validandoDiaCred = false;
            }
        });
    });
});
// fin de nuevo bloque 08/10/2025

function searchCliente(ced) {
	// (tipo === 'ec') ? ced = ced.substring(0, 10) : ced;
	$.post('', { provAjax2: true, Prs_Ced: ced.substring(0, 10) }, function (response) {
		console.log(response);

		if (!$.varValid(response['rows'][0]['Cli_Cod']) || response['rows'][0]['Cli_Cod'].length === 0) {
			$('#clieCreateForm').setData(response['rows'][0]);

		} else {
			selectCliente(response['rows'][0]);
			$('#clieCreateDialog').dialog('close');
		}

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

	if (!carga_inicial) {
		// Calcular días de plazo entre Cpc_Ven y la fecha actual
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

// Valida Todo antes de guardar
function validaDocument(editaDocument = false) {
	if ($('#t_descuento').val() * 1 === 0) $('#Vet_Des').val(0);
	// if ($('#Tpc_Cod').val() == 0) { $.alert('Debe seleccionar un Tipo de Comprobante - Pago SRI'); return; }//validacion de no dejar en blanco este campo
	if (($('#Val_Pcc_2').val()) * 1 < 0) { $.alert('El <i><u>total de pagos</u></i> no puede Exceder al <i><u>Monto a Pagar</u></i>!<br/>Revise los Datos.', null, 'remove'); return; } //validador para no exceder el monto a pagar
	if (($('#Val_Pcc_2').val()) * 1 > 0) { $.alert('Aun queda saldo pendiente por cobrar!', null, 'remove'); return; }

	var docu = $('.formDatos').getData('saveDocument'), Tic = $('#Tic_Cod option:selected');
	docu['items'] = items.getGridBatch();
	for (var i = 0; i < docu['items'].length; i++) {
		if (Tic.data('ticsri') === 0 || Tic.data('ticsri') === 9 || Tic.data('ticsri') == 2) {
			eliminaRetencion({ 'index': docu['items'][i]['index'], 'tipo': 'R' });
			eliminaRetencion({ 'index': docu['items'][i]['index'], 'tipo': 'I' });
		}
	}

	docu['items'] = items.getGridBatch();
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
	if (!$.isUnd(reembolsos) && reembolsos.length === 1 && $('#Vet_Rem').is(':checked')) {
		if (("0" + $('#t_rubros').val()) * 1 !== $.round(reembolsos.jqGrid('getCol', 'Total', true, 'sum'))) {
			return $.alert('El total de las <u class="blue">Compras de Reembolso</u> no es igual al <u class="blue">Total de Venta</u>!', null, 'alert');
		} else
			docu['reembolsos'] = reembolsos.getGridColumn('Cop_Cod');
	}
	console.log(docu);
	//return $.alert('OK');
	$.createDialogConfirm((editaDocument ? '¿Est&aacute; seguro de editar el Documento?' : '¿Est&aacute; seguro de guardar el Documento?'), docu, saveDocument);
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
	setTimeout(function () {
		var ite_pagos = [
			$.extend($('#pagosForm').getData(),
				{
					'Tipo_Cod': $('#Pag_Cod option:selected').val(),
					'Forma_Cod': $('#For_Cod option:selected').val(),
					'Vet_Num': 1, 'Vet_Tot': $('#Val_Pcc').val(), 'Pag_Pld': $('#Pag_Pld').val()
				})];
		pagos.setRows(ite_pagos);
		updateDocument();
	}, 1000);
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
		if (cObjt['Com_Edit'] === 'N') return '<i title="El comprobante contable es formato anterior" class="glyphicon glyphicon-lock orange"></i>';
		// if (cObjt['Vet_Aut'] === 'S')
		// 	return (cObjt['Pago'] === 'Credito' || cObjt['Pago'] === 'Contado') && (cObjt['Cpp_Det'] !== 'S' || cObjt['onlyRetencion'] == true) ? $.getGridButton('cambiarPago', cObjt, "Cambiar Forma de Pago", null, null, 'warning') : '<i title="Retencion Electronica Validada" class="fa fa-globe green"></i>';
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
	items.jqGrid('addRowData', next, $.extend(item, { index: next, Vet_Can: Vet_Can, Vet_Pru: Vet_Pru }), 'last');
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
					t_rubros: 0 },
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
				if(row['Iva_Sri'] == 6){ // nueva validacion para no objeto iva
					tot['t_noiva'] = tot['t_noiva'] + row['Vet_Imp']; //nuevo campo para total sin iva
				}else{
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
	//Pasando el código del cliente del formulario principal al modal
	var cliCod = $("#formDocumento input[name='Cli_Cod']").val();
    $("#pagosForm input[name='aux_Cli_Cod']").val(cliCod);

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
			// Si es crédito, seleccionar la primera opción visible en aux_Pag_Cod
			if (Pag_Cod == 2) {
				var firstVisible = $('#aux_Pag_Cod option:not([hidden])').first();
				if (firstVisible.length) {
					$('#aux_Pag_Cod').val(firstVisible.val());
				}
			}
		} else {
			$('#Pag_Pld2').val('').html(''); $.alert('Error al buscar la cuenta pago para la fecha indicada');
		}
	}, function () { return $.alert('Error al Buscar las cuentas Pago'); }, function () { return $.alert('Error al Buscar las cuentas Pago'); },
		function () { });
}

function cambiarPago(data) {
	// Asegurarse de que los datos del registro seleccionado se obtienen correctamente
	// Si data es solo el id, obtener el registro completo antes de continuar
	if (typeof data === 'string' || typeof data === 'number') {
		// Suponiendo que doc_ventas es el grid, obtener el registro por id
		var registro = doc_ventas.jqGrid('getRowData', data);
		if ($.isEmptyObject(registro)) {
			$.alert('No se encontró el registro seleccionado.');
			return;
		}
		data = registro;
	}

	$('#For_Cod2').data(data);
	data['For_Cod'] = data['Pago'] === 'Contado' ? 1 : 2;
	$('#changePagoForm').setData(data);
	console.log("cambiarPago: ",data);
	$('#asigNegCamForm').setData(data);//para la negociacion
	$("#Cpc_Ven2").datepicker("option", "minDate", data['Vet_Fec']);
	if ($.isEmpty(data['Cpc_Ven'])) {
		var d = new Date(data['Vet_Fec']); d.setDate(d.getDate() + 15); $('#Cpc_Ven2').datepicker("setDate", d);
	} else {
		$('#Cpc_Ven2').datepicker("setDate", data['Cpc_Ven']);
	}
	$('#changePagoDialog').dialog('open');
}

// function checkPago() {
// 	var tipo_pago = $('#aux_Pag_Cod').val();
// 	$(".pagoTransf").toggle(tipo_pago == 8); // Mostrar input que se usan cuando es pago es por transferencia o cheque
// 	$(".pagoCheque").toggle(tipo_pago == 3); // Mostrar input que se usan cuando es pago es por cheque
// 	$(".pagoDepCred").toggle(tipo_pago == 2 || tipo_pago == 9); // Mostrar input que se usan cuando es pago es por deposito a credito
// }

function checkPago() {
    var tipo_pago = $('#aux_Pag_Cod').val();

    // Actualizar el input oculto
    $('#hidden_aux_Pag_Cod').val(tipo_pago);

    // Mostrar/ocultar bloques
    $(".pagoTransf").toggle(tipo_pago == 8); 
    $(".pagoCheque").toggle(tipo_pago == 3); 
    $(".pagoDepCred").toggle(tipo_pago == 2 || tipo_pago == 9); 

    // Ejecutar también el cambio de select de cuentas
    toggleCuentaTransfer();
}

function toggleCuentaTransfer() {
    var tipo_pago = $('#hidden_aux_Pag_Cod').val(); // leer del input oculto
    var pagPld      = document.getElementById('Pag_Pld2');
    var bakTransfer = document.getElementById('Bak_Cod_transfer');

    if (pagPld && bakTransfer) {
        if (tipo_pago == '8') { // Transferencia
            pagPld.style.display = 'none';
            bakTransfer.style.display = '';
        } else {
            pagPld.style.display = '';
            bakTransfer.style.display = 'none';
        }
    }
}

document.addEventListener('DOMContentLoaded', function () {
    var pagCod = document.getElementById('aux_Pag_Cod');
    if (pagCod) {
        // Enganchar change al select de tipos
        pagCod.addEventListener('change', checkPago);

        // Ejecutar al cargar
        checkPago();
    }
});


function saveChangePago() {
	var data = $('#changePagoForm').getData('saveChangePago');
	$.createDialogConfirm('¿Est&aacute; seguro que desea guardar los cambios?', data, function (d) {
		$.saveDataJson('', d, function (r) {
			$('#searchGrid').trigger('reloadGrid', []);
			$('#changePagoDialog').dialog('close');
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
		{ label: '&nbsp;', name: 'act1', width: 30, align: 'center', viewable: false,
			formatter: function (cellvalue, options, rowObject) {
				return "<button  style='border: 1px solid #285e8e; border-radius: 2px;' class='icon-btn btn-primary' data-row-id='" + options.rowId + "'><i class='fa fa-edit'></i></button>";
			}
		},
		{ label: '&nbsp;', name: 'act3', width: 30, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: selectItemExt, } },
		{ label: '&nbsp;', name: 'act2', width: 30, align: 'center', viewable: false,
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
