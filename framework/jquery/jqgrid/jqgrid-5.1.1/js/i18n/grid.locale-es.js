/**
 * jqGrid Spanish Translation
 * Traduccion jqGrid en Español por Yamil Bracho
 * Traduccion corregida y ampliada por Faserline, S.L. 
 * http://www.faserline.com
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
**/
/*global jQuery, define */
(function( factory ) {
	"use strict";
	if ( typeof define === "function" && define.amd ) {
		// AMD. Register as an anonymous module.
		define([
			"jquery",
			"../grid.base"
		], factory );
	} else {
		// Browser globals
		factory( jQuery );
	}
}(function( $ ) {

$.jgrid = $.jgrid || {};
if(!$.jgrid.hasOwnProperty("regional")) {
	$.jgrid.regional = [];
}
$.jgrid.regional["es"] = {
	defaults : {
		recordtext: "Mostrando {0} - {1} de {2}",
	    emptyrecords: "Sin Registros que Mostrar",
		loadtext: "Cargando...",
		savetext: "Guardando...",
		pgtext : "P\u00E1gina {0} de {1}",
		pgfirst : "Primera P\u00E1gina",
		pglast : "\u00DAltima P\u00E1gina",
		pgnext : "Siguiente P\u00E1gina",
		pgprev : "P\u00E1gina Anterior",
		pgrecs : "Registros por P\u00E1gina",
		showhide: "Expandir/Contraer Grid",
		// mobile
		pagerCaption : "Grid::Configurar Paginaci\u00F3n",
		pageText : "P\u00E1gina:",
		recordPage : "Registros por P\u00E1gina",
		nomorerecs : "No hay mas Registros...",
		scrollPullup: "Subir para Cargar m\u00E1s...",
		scrollPulldown : "Bajar para Actualizar...",
		scrollRefresh : "Soltar para Refrescar..."		
	},
	search : {
	    caption: "B\u00FAsqueda...",
	    Find: "Buscar",
	    Reset: "Limpiar",
	    odata: [{ oper:'eq', text:"igual "},{ oper:'ne', text:"no igual a"},{ oper:'lt', text:"menor que"},{ oper:'le', text:"menor o igual que"},{ oper:'gt', text:"mayor que"},{ oper:'ge', text:"mayor o igual a"},{ oper:'bw', text:"empiece por"},{ oper:'bn', text:"no empiece por"},{ oper:'in', text:"est\u00E1 en"},{ oper:'ni', text:"no est\u00E1 en"},{ oper:'ew', text:"termina por"},{ oper:'en', text:"no termina por"},{ oper:'cn', text:"contiene"},{ oper:'nc', text:"no contiene"},{ oper:'nu', text:'is null'},{ oper:'nn', text:'is not null'}],
	    groupOps: [	{ op: "AND", text: "todo" },	{ op: "OR",  text: "cualquier" }	],
		operandTitle : "Click para Seleccionar Operador.",
		resetTitle : "Limpiar B\u00FAsqueda"
	},
	edit : {
	    addCaption: "Agregar registro",
	    editCaption: "Modificar registro",
	    bSubmit: "Guardar",
	    bCancel: "Cancelar",
		bClose: "Cerrar",
		saveData: "Se han modificado los datos, ¿Guardar Cambios?",
		bYes : "Si",
		bNo : "No",
		bExit : "Cancelar",
	    msg: {
	        required:"Campo obligatorio",
	        number:"Introduzca un n\u00FAmero",
	        minValue:"El valor debe ser mayor o igual a ",
	        maxValue:"El valor debe ser menor o igual a ",
	        email: "no es una direcci\u00F3n de correo v\u00E1lida",
	        integer: "Introduzca un valor entero",
			date: "Introduza una fecha correcta ",
			url: "no es una URL v\u00E1lida. Prefijo requerido ('http://' or 'https://')",
			nodefined : " no est\u00E1 definido.",
			novalue : " valor de retorno es requerido.",
			customarray : "La funci\u00F3n personalizada debe devolver un array.",
			customfcheck : "La funci\u00F3n personalizada debe estar presente en el caso de validaci\u00F3n personalizada."
		}
	},
	view : {
	    caption: "Consultar registro",
	    bClose: "Cerrar"
	},
	del : {
	    caption: "Eliminar",
	    msg: "¿Desea eliminar los registros seleccionados?",
	    bSubmit: "Eliminar",
	    bCancel: "Cancelar"
	},
	nav : {
		edittext: " ",
	    edittitle: "Modificar fila seleccionada",
		addtext:" ",
	    addtitle: "Agregar nueva fila",
	    deltext: " ",
	    deltitle: "Eliminar fila seleccionada",
	    searchtext: " ",
	    searchtitle: "Buscar informaci\u00F3n",
	    refreshtext: "",
	    refreshtitle: "Recargar datos",
	    alertcap: "Aviso",
	    alerttext: "Seleccione una fila",
		viewtext: "",
		viewtitle: "Ver fila seleccionada",
		savetext: "",
		savetitle: "Guardar Fila",
		canceltext: "",
		canceltitle : "Cancelar la Edici\u00F3n",
		selectcaption : "Acciones..."
	},
	col : {
	    caption: "Mostrar/Ocultar columnas",
	    bSubmit: "Enviar",
	    bCancel: "Cancelar"	
	},
	errors : {
		errcap : "Error",
		nourl : "No se ha especificado una URL",
		norecords: "No hay datos para procesar",
	    model : "Las columnas de nombres son diferentes de las columnas de modelo"
	},
	formatter : {
		integer : {thousandsSeparator: ",", defaultValue: '0'},
		number : {decimalSeparator:".", thousandsSeparator: ",", decimalPlaces: 2, defaultValue: '0.00'},
		currency : {decimalSeparator:".", thousandsSeparator: ",", decimalPlaces: 2, prefix: "$ ", suffix:"", defaultValue: '0.00'},
		date : {
			dayNames:   [
				"Do", "Lu", "Ma", "Mi", "Ju", "Vi", "Sa",
				"Domingo", "Lunes", "Martes", "Mi\u00E9rcoles", "Jueves", "Viernes", "S\u00E1abado"
			],
			monthNames: [
				"Ene", "Feb", "Mar", "Abr", "May", "Jun", "Jul", "Ago", "Sep", "Oct", "Nov", "Dic",
				"Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"
			],
			AmPm : ["am","pm","AM","PM"],
			S: function (j) {return j < 11 || j > 13 ? ['st', 'nd', 'rd', 'th'][Math.min((j - 1) % 10, 3)] : 'th'},
			srcformat: 'Y-m-d',
			newformat: 'd-m-Y',
			parseRe : /[#%\\\/:_;.,\t\s-]/,
			masks : {
	            ISO8601Long:"Y-m-d H:i:s",
	            ISO8601Short:"Y-m-d",
	            ShortDate: "n/j/Y",
	            LongDate: "l, F d, Y",
	            FullDateTime: "l, F d, Y g:i:s A",
	            MonthDay: "F d",
	            ShortTime: "g:i A",
	            LongTime: "g:i:s A",
	            SortableDateTime: "Y-m-d\\TH:i:s",
	            UniversalSortableDateTime: "Y-m-d H:i:sO",
	            YearMonth: "F, Y"
	        },
	        reformatAfterEdit : false,
			userLocalTime : false
		},
		baseLinkUrl: '',
		showAction: '',
	    target: '',
	    checkbox : {disabled:true},
		idName : 'id'
	}
};
}));
