/*
	Autor : Erik Niebla A.
	Mail: 	ep_niebla@hotmail.com,ep.niebla@gmail.com
	Fecha: 	08/08/2015
*/
$(document).ready(function() {   
		$(document).tooltip({track: true});		
        //$(".isSelectMenu").selectmenu();//$(".isMoney").MoneyFormat("33334");             
});
// FUNCIONES JQGRID 4.8 JQUERY
$.fn.existsId = function(id) {var ids = this.jqGrid('getDataIDs');for (var i = 0; i < ids.length; i++){if(ids[i]===id) return true;}return false;};//Return boolean
$.fn.clearGrid = function() {this.jqGrid('setGridParam',{datatype:'local'}); this.jqGrid("clearGridData"); this.jqGrid('setGridParam', {data:[]}).trigger('reloadGrid', [{ page: 1 }]);};
$.fn.getColumnIndexByName = function (columnName) {var cm = this.jqGrid('getGridParam', 'colModel'), i, l; for (i = 0, l = cm.length; i < l; i += 1) {if (cm[i].name === columnName) {return i;}};return -1;};
$.fn.selectAllByComlumn = function (columnName,marca) {
	marca=(typeof marca == 'undefined' ? true : marca);var ids = this.jqGrid('getDataIDs'),ban="";if(marca){ban="Yes";}else{ban="No";}; for (var i = (ids.length-1); i >=0 ; i--) {this.jqGrid('setCell',ids[i],columnName,ban);};
};
$.fn.startGridEdit = function() {        
    var ids = this.jqGrid('getDataIDs'); for (var i = (ids.length-1); i >=0 ; i--) {this.jqGrid('editRow',ids[i]);}//for (var i = 0; i < ids.length; i++) //Cambio	
};
$.fn.stopGridEdit = function() {        
    var ids = this.jqGrid('getDataIDs'); for (var i = 0; i < ids.length; i++){this.jqGrid('saveRow', ids[i], false, 'clientArray');}
};
$.fn.getGridBatch = function() {        
	var batch = new Array(),ids = this.jqGrid('getDataIDs');for (var i = 0; i < ids.length; i++){this.jqGrid('saveRow', ids[i], false, 'clientArray');batch.push(this.jqGrid('getRowData', ids[i]));}
	return batch;
};
// FUNCIONES GENERALES JQUERY
$.fn.getData = function(tipo){var data=this.serializeObject();data[tipo]=true;return data;}
$.fn.Search = function(form,tipo){		
	this.setGridParam({postData: null});var jform=$(form);		
	this.jqGrid('setGridParam',{datatype:'json',postData: jform.getData(tipo)}).trigger("reloadGrid", [{ page: 1 }]);
	jform.effect("highlight",{},500);		
}
$.fn.SearchOrDialog = function(form,tipo,dialog,callback){
	var postdata=$(form).getData(tipo),jgrid=this;postdata['page']=1;postdata['rows']=2;
	$(form).effect("highlight",{},500);
	$.get(this[0].p.url,postdata, function(response){		
		if(parseInt(response['records'])===1){callback(response['rows'][0]);}
		else{jgrid.Search(form,tipo);$(dialog).dialog("open");}
	},'json');	
}
$.fn.SearchOrDialogArray = function(array,tipo,dialog,callback){
	var postdata=array,jgrid=this;postdata['page']=1;postdata['rows']=2;postdata[tipo]=true;
	$(form).effect("highlight",{},500);
	$.get(this[0].p.url,postdata, function(response){		
		if(parseInt(response['records'])===1){callback(response['rows'][0]);}
		else{jgrid.Search(form,tipo);$(dialog).dialog("open");}
	},'json');	
}
$.fn.prev = function(comp){$('#loader').show();this.hide();$(comp).show();$('#loader').fadeOut('slow');} 
$.fn.next = function(comp){$('#loader').show();this.hide();$(comp).show();$('#loader').fadeOut('slow');}
$.fn.dateLimits = function(minDate,maxDate){this.datepicker( "option", "minDate", minDate );this.datepicker( "option", "maxDate", maxDate );}
$.fn.formSubmit = function(){$('<input type=\'submit\'>').hide().appendTo(this).click().remove();}
// FUNCIONES FORMATO DINERO - Requiere lobreria jquery.maskmoney.js
/*$.fn.MoneyFormat = function(val) {
	var input=$('<input style="display:none" type="text" value="'+val+'" />');//$("body").append(input);
	input.maskMoney({prefix:'$ ', thousands:',', decimal:'.', affixesStay: true,allowNegative: true,allowZero:true});input.maskMoney('mask');$(this).html($(input).val());input.remove();
}
$.MoneySet = function(id){$(id).maskMoney({prefix:'$ ', thousands:',', decimal:'.',precision:4, affixesStay: true,allowNegative: true,allowZero:true});$(id).maskMoney('mask');}
$.MoneyUnSet = function(id){$(id).maskMoney('destroy');}
*/
$.createDatePickers= function(id){$(id).datepicker({changeMonth: true,changeYear: true, dateFormat: 'yy-mm-dd',firstDay: 1}).datepicker("setDate", new Date());$(id).datepicker( "option", "showAnim", "blind" );} 
$.createDateRange= function(fromDate,toDate){
	var today = new Date();
	$( fromDate ).datepicker({
      changeMonth: true,changeYear: true, dateFormat: 'yy-mm-dd',firstDay: 1,
      onClose: function( selectedDate ) {
        $( toDate ).datepicker( "option", "minDate", selectedDate );
      }
    }).datepicker("setDate", new Date(today.getTime() - (30 * 24 * 3600 * 1000)));
    $( toDate ).datepicker({
      changeMonth: true,changeYear: true, dateFormat: 'yy-mm-dd',firstDay: 1,
      onClose: function( selectedDate ) {
        $( fromDate ).datepicker( "option", "maxDate", selectedDate );
      }
    }).datepicker("setDate", today);//$(fromDate).datepicker( "option", "showAnim", "blind" );$(toDate).datepicker( "option", "showAnim", "blind" );	
} 
$.createDialog = function(comp,height,width,noTitleStuff){
	var dialogClass="TitleStuff";
	noTitleStuff=(typeof noTitleStuff == 'undefined' ? true : noTitleStuff);
	if(!noTitleStuff) dialogClass="noTitleStuff";
    $(comp).dialog({            
		height: height, width: width, dialogClass: dialogClass,closeText:"Cerrar Ventana",             
		modal: true,autoOpen: false,resizable: false, position:{my: "center",at: "center",of: $('body')},
		close: function(){$('.ui-widget-overlay').unbind('click');},
		open: function(){$('.ui-widget-overlay').bind('click', function () { $(this).siblings('.ui-dialog').find('.ui-dialog-content').dialog('close'); });},
		show: {effect: "fade",duration: 500},hide: {effect: "fade",duration: 200}
	});
}
$.createDialogConfirm = function(message,data,accion){	
	message=message||'Esta seguro que desea realizar esta <b>acci&oacute;n</b>.';
	var dialog='<div id="dialog-confirm" title="CONFIRMAR ACCI&Oacute;N">'+
			'<div style="font-size:14px;"><center>'+message+'</center></div></div>';//$("body").append(dialog);		
	$(dialog).dialog({
	  dialogClass: 'dialog-confirm-test',closeText:"Cerrar y Cancelar",modal: true,autoOpen: false,resizable: false,
      buttons: {
        "Aceptar": function() {$( this ).dialog( "close" );if(data!==null){accion(data);}else{accion();}},
        "Cancelar": function() {$( this ).dialog( "close" );}
      }, position:{my: "center",at: "center",of: $('body')},
	  close: function(){$('.ui-widget-overlay').unbind('click');$( this ).remove();},show: {effect: "fade",duration: 500},
	  open: function(){$('.ui-widget-overlay').bind('click', function () { $(this).siblings('.ui-dialog').find('.ui-dialog-content').dialog('close'); });}	  
    });$("#dialog-confirm .dialog-confirm-test").children(".ui-dialog-titlebar").prepend('<span class="ui-icon ui-icon-alert" style="float:left; margin:2px 8px 0 0;"></span>');
	$("#dialog-confirm").dialog('open');
}
$.createSearchDialog = function(name,colModel,height,width,noTitleStuff){
	name=name.replace("#","");var id=name.replace("Dialog",""),container=$('<div style="padding-top:7px;" class="condensed"><table id="'+id+'Grid"></table><div id="'+id+'Pager"></div></div>');
	height=height||400;width=width||700;noTitleStuff=(typeof noTitleStuff == 'undefined' ? true : noTitleStuff);
	$.createDialog("#"+name,height,width,noTitleStuff);
    $("#"+name).append(container).find("form").attr('id',id+"Form").attr("action","javascript:$.Search('"+id+"')").append('<input type="text" style="display:none"/>')
		.find("input[name='search']").addClass("clearable").addClass("submit");
	var jgrid=$("#"+id+"Grid"), jform=$("#"+id+"Form"),formHeight=jform.actual( 'outerHeight', { includeMargin : true }),gridHeight=0;;
	if(noTitleStuff){gridHeight=height-formHeight-122}else{gridHeight=height-formHeight-90}	
	jgrid.jqGrid({
                url: '',mtype: "GET", datatype: "json", regional : 'es',//ajaxRowOptions: { async: true },                             
                width : (width-25),height:gridHeight,postData: jform.getData(id+"Ajax"),
                cmTemplate: {sortable:false},colModel: colModel,                                                                                       
                rowNum: 10,rowList:[10,20,50], pager: "#"+id+"Pager", gridview: true, rownumbers: true, viewrecords: true, altRows: true, altclass: "myAltRowClass"
            });                        
            jgrid.navGrid('#'+id+'Pager',{ edit: false, add: false, del: false, search: false, refresh: true, view: true, position: "left", cloneToTop: false });
            jgrid.jqGrid('bindKeys');
}
$.Search = function(id){	
    var jgrid=$("#"+id+"Grid"),jform=$("#"+id+"Form");	jgrid.setGridParam({postData: null});		
	jgrid.jqGrid('setGridParam',{datatype:'json',postData: jform.getData(id+"Ajax")}).trigger("reloadGrid", [{ page: 1 }]);
	jform.effect("highlight",{},500);		
}
$.SearchOrDialog = function(name,callback,otherForm){
	name=name.replace("#","");var id=name.replace("Dialog","");	
	otherForm=otherForm||("#"+id+"FormTemp");
	var jgrid=$("#"+id+"Grid"),jformtemp=$(otherForm),postdata=jformtemp.getData(id+"Ajax");postdata['page']=1;postdata['rows']=2;
	jformtemp.effect("highlight",{},500);	
	$.get(jgrid[0].p.url,postdata, function(response){	
		if(parseInt(response['records'])===1){callback(response['rows'][0]);}
		else{$("#"+id+"Form").find("input[name='search']").val(jformtemp.find("input[name='search']").val());$.Search(id);$("#"+name).dialog("open");}
	},'json');	
}
$.SearchOrDialogArray = function(name,callback,array){
	name=name.replace("#","");var id=name.replace("Dialog","");		
	var jgrid=$("#"+id+"Grid"),postdata=array;postdata[id+"Ajax"]=true;postdata['page']=1;postdata['rows']=2;	
	$.get(jgrid[0].p.url,postdata, function(response){	
		if(parseInt(response['records'])===1){callback(response['rows'][0]);}
		else{$("#"+id+"Form").find("input[name='search']").val(array['search']);$.Search(id);$("#"+name).dialog("open");}
	},'json');	
}
$.getDialogGrid = function(name){var id=name.replace("Dialog","");return $(id+"Grid");}
$.alert = function(message){
	message=message||'El Servidor ha fallado en responder!';
	var dialog='<div id="dialog-alert" title="MENSAJE DEL SISTEMA">'+
			'<div style="font-size:14px;"><center><b>'+message+'</b></center></div></div>';
	//$("body").append(dialog);		
	$(dialog).dialog({
	  dialogClass: 'dialog-alert-test',   closeText:"Cerrar Mensaje", modal: true,autoOpen: true,resizable: false,     
      buttons: {"Aceptar": function() {$( this ).dialog( "close" );}}, position:{my: "center",at: "center",of: $('body')},
	  close: function(){$('.ui-widget-overlay').unbind('click');$( this ).remove();},show: {effect: "fade",duration: 500},
	  open: function(){$('.ui-widget-overlay').bind('click', function () { $(this).siblings('.ui-dialog').find('.ui-dialog-content').dialog('close'); });}	  
    });$("#dialog-alert .dialog-alert-test").children(".ui-dialog-titlebar").prepend('<span class="ui-icon ui-icon-info" style="float:left; margin:2px 8px 0 0;"></span>');		
}
$.fn.alertMsg= function(message){
	if(typeof message == 'undefined'){
		this.parent().find('.lblMsg').html('');
		this.parent().find('.imgMsg').attr('src','../../mascaras/model1/imagenes/ok-s.gif');
	} else{
			this.parent().find('.lblMsg').html(message);
			this.parent().find('.imgMsg').attr('src','../../mascaras/model1/imagenes/32x32/cancel.gif');
		}	
	
}
//FUNCIONES GENERALES
function setfocus(campo){if (campo){campo.focus();}}
function validar_injections(e) { 
    tecla = (document.all) ? e.keyCode : e.which; 
    if (tecla==13 || tecla==8) return true; 
    patron=/[A-Za-zï¿½ï¿½0-9\_\-\.\:\$\%\ \[\]\(\)]/
    te = String.fromCharCode(tecla); 	
    return patron.test(te); 
}
function validar_numeric(e) { 
    tecla = (document.all) ? e.keyCode : e.which; 
    if (tecla==13 || tecla==8) return true; 
    patron =/^[0-9]*$/; // 4
    te = String.fromCharCode(tecla); 	
    return patron.test(te); 
}
function validar_decimal(e) { 
    tecla = (document.all) ? e.keyCode : e.which;  
    if (tecla==13 || tecla==8) return true; 
    patron =/^[0-9\.]*$/; // 4
    te = String.fromCharCode(tecla); 	
    return patron.test(te); 
}







// CREDITO DEBITO DIARIO
$.fn.updateGridDiario = function() { 
	    var total_haber=0,total_debe=0;
		var rows= this.jqGrid('getRowData');
		for(var i=0;i<rows.length;i++){
			var row=rows[i],monto;
			if(row['Det_Tip']==='D') {monto=$('#'+row['Pld_Cod']+'_Debe');total_debe +=Number(monto.val());}
			else {monto=$('#'+row['Pld_Cod']+'_Haber');total_haber +=Number(monto.val());}
			if(!parseFloat(monto.val())){monto.val("");}
		};this.jqGrid("footerData", "set", {Glosa: "<div style='text-align:right;'>TOTALES:</div>", Debe:total_debe, Haber:total_haber});
};
$.fn.createInputDiario = function(element,tipo){
	var jgrid=this,rowId=$(element).closest('tr.jqgrow').attr('id'),dataFromTheRow = jgrid.jqGrid ('getRowData',rowId);  
	$(element).parent().removeAttr("title"); 
	if(dataFromTheRow["Det_Tip"]===tipo){ 
		$(element).attr('onkeypress','return  validar_decimal(event)');
		if(parseFloat($(element).val())===0) $(element).val("");
		$(element).change(function() {jgrid.updateGridDiario();});$(element).css('text-align', 'right');  
		$(element).focus();
	}else{$(element).parent().html("");};     
}; 
$.clearFooterDiario = function(grid){ 
	grid=grid.replace("#",""); var $footRow = $("#gbox_"+grid+" #gview_"+grid+" .ui-jqgrid-sdiv .footrow");
	$footRow.find('>td[aria-describedby="'+grid+'_Pld_Cdc"]').css("border-right-color", "transparent");                            
	$footRow.find('>td[aria-describedby="'+grid+'_Pld_Des"]').css("border-right-color", "transparent");     
	$footRow.find('>td[aria-describedby="'+grid+'_Pld_Cdc"]').css("background-color", "white");
	$footRow.find('>td[aria-describedby="'+grid+'_Pld_Des"]').css("background-color", "white");
	$footRow.find('>td[aria-describedby="'+grid+'_Glosa"]').css("background-color", "white");
	$footRow.find('>td[aria-describedby="'+grid+'_act1"]').css("background-color", "white");
};



/*! Copyright 2012, Ben Lin (http://dreamerslab.com/), Licensed under the MIT License (LICENSE.txt).
 * http://dreamerslab.com/blog/en/get-hidden-elements-width-and-height-with-jquery/
 * Version: 1.0.16
 * Requires: jQuery >= 1.2.3
 */
(function(a){a.fn.addBack=a.fn.addBack||a.fn.andSelf;
a.fn.extend({actual:function(b,l){if(!this[b]){throw'$.actual => The jQuery method "'+b+'" you called does not exist';}var f={absolute:false,clone:false,includeMargin:false};
var i=a.extend(f,l);var e=this.eq(0);var h,j;if(i.clone===true){h=function(){var m="position: absolute !important; top: -1000 !important; ";e=e.clone().attr("style",m).appendTo("body");
};j=function(){e.remove();};}else{var g=[];var d="";var c;h=function(){c=e.parents().addBack().filter(":hidden");d+="visibility: hidden !important; display: block !important; ";
if(i.absolute===true){d+="position: absolute !important; ";}c.each(function(){var m=a(this);var n=m.attr("style");g.push(n);m.attr("style",n?n+";"+d:d);
});};j=function(){c.each(function(m){var o=a(this);var n=g[m];if(n===undefined){o.removeAttr("style");}else{o.attr("style",n);}});};}h();var k=/(outer)/.test(b)?e[b](i.includeMargin):e[b]();
j();return k;}});})(jQuery);
  // CLEARABLE INPUT - Pone un boton X para limpiar un campo de busqueda
jQuery(function($) {
  function tog(v){return v?'addClass':'removeClass';} 
  $(document).on('input', '.clearable', function(){$(this)[tog(this.value)]('x');})
  .on('mousemove', '.x', function( e ){$(this)[tog(this.offsetWidth-18 < e.clientX-this.getBoundingClientRect().left)]('onX');})
  .on('touchstart click', '.onX', function( ev ){ev.preventDefault();$(this).removeClass('x onX').val('').change();if($(this).hasClass("submit")){this.form.submit();}});
});
 // Convierte un form en un objeto array
$.fn.serializeObject = function(){
	var o = {}, a = this.serializeArray();	
	$.each(a, function() {
		if (o[this.name] !== undefined) {
			if (!o[this.name].push) {o[this.name] = [o[this.name]];}
			o[this.name].push(this.value || '');
		} else {o[this.name] = this.value || '';}
	});return o;
};
// TOGGLE ATTRIBUTE
$.fn.toggleAttr = function(attr) {var $this = $(this);$this.attr(attr) ? $this.removeAttr(attr) : $this.attr(attr, attr);};