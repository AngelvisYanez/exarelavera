/*
	Autor : Erik Niebla A.
	Mail: 	ep_niebla@hotmail.com,ep.niebla@gmail.com
	Fecha: 	08/08/2015
	Requiere:	jqGrid,Jquery
*/
var creatorExcel="Erik Niebla A.";
var excelFormats={// Add if is necesary
	currency:'mso-number-format:&#39;$ #,##0.00_);[red]($ #,##0.00)&#39;;', // Dolar
	text	:'mso-number-format:&#39;@&#39;;', // Solo Texto
	interger:'mso-number-format:&#39;0&#39;;', // Sin Decimales
	decimal	:'mso-number-format:&#39;0.00&#39;;', // Dos Decimales
	fraction:'mso-number-format:&#39;# ???/???&#39;;',
	percent	:'mso-number-format:&#39;Percent&#39;;',
	thousand:'mso-number-format:&#39;#,##0.000&#39;;', //Separador de miles y tres decimales
	date	:'mso-number-format:&#39;mm/dd/yy&#39;;',
	longDate:'mso-number-format:&#39;mmmm d, yyyy&#39;;',
	shortTime:'mso-number-format:&#39;Short Date&#39;;'
};
(function($) {
    $.jgrid.extend({
        exportGridExcel: function(o) {
			o=(typeof o == 'undefined' ? new Array() : o);o.caption=(typeof o.caption == 'undefined' ? true : o.caption);	
			o.nombre=(typeof o.nombre == 'undefined' ? 'archivo-'+$.getDate()+'.xls' : o.nombre+"-"+$.getDate()+'.xls' );o.hoja=(typeof o.hoja == 'undefined' ? 'Hoja 1' : o.hoja);
			o.footer=(typeof o.footer == 'undefined' ? false : o.footer);o.generated=(typeof o.generated == 'undefined' ? true : o.generated);			
			o.download=(typeof o.download == 'undefined' ? true : o.download);
			var $table=$('<table cellspacing="0" cellpadding="0" border="0" style="table-layout:fixed;"></table>'),colLength=this.find('.jqgfirstrow td').length;			
			$table.append(this.parent().parent().parent().find('.ui-jqgrid-htable').html());
			if(o.caption){$table.find('thead').prepend('<tr><th style="text-align:center;" colspan="'+colLength+'">'+this.parent().parent().parent().find('.ui-jqgrid-title').html()+'</th></tr>');}
			$table.append(this.html());			
			if(o.footer)$table.append(this.parent().parent().parent().find('.ui-jqgrid-ftable').html());
			if(o.generated)$table.append('<tbody><tr><td style="text-align:right;" colspan="'+colLength+'">Generado el '+$.getDate()+' por '+creatorExcel+'</td></tr></tbody>');
			$table.find('thead tr').removeAllAttr().find('th').removeAllAttr().clearCell().css('border','1px solid black');
			var $tbody=$table.find('tbody');
			$tbody.find('tr').find('td').removeAllAttr().addBorders().clearCell();
			if(o.footer)$tbody.find('tr.footrow td').removeAllAttr().clearCellBordered();
			$tbody.find('tr.jqfoot td').removeAllAttr().clearCellBordered();
			$tbody.find('tr.jqgroup td').removeAllAttr().clearCellBordered();
			$tbody.find('tr').removeAllAttr();
			var html=$table.html();
			if(o.download){$.downloadFile($table.exportarExcelBlob(o.hoja),o.nombre);}
			$table.remove();return html;
		}
	});
	$.fn.addBorders = function() {	  
		this.each(function() {
		  for (var i = this.attributes.length -1; i >= 0 ; i--) 
			 if(this.attributes[i].name==='style') 
				{this.attributes[i].value=this.attributes[i].value+"border:0.1pt solid black;padding-left:5px;padding-right:5px;white-space:nowrap;overflow:hidden;";break;}		  
		});return this;	 
	};
	$.fn.clearCell = function() {this.each(function() {$(this).html($(this).text().trim());});return this;};
	$.fn.clearCellBordered = function() {
		this.each(function() {
			if($(this).text().trim()!=="") $(this).css('font-weight','bold').css('border','1px solid black').css('padding-left','5px').css('padding-right','5px');
			else $(this).css('border','0');
		});return this;
	};
	$.fn.removeAllAttr = function() {	  
		this.each(function() {
		  for (var i = this.attributes.length -1; i >= 0 ; i--) {
			 if(this.attributes[i].name!=='colspan'&&this.attributes[i].name!=='rowspan'&&this.attributes[i].name!=='style')  $(this).removeAttr(this.attributes[i].name);
		  }
		});return this;	  
	};
	$.fn.exportarExcelBlob = (function () {
		var template = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><meta http-equiv="content-type" content="application/vnd.ms-excel; charset=UTF-8"><head><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>{worksheet}</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head><body><table cellspacing="0" cellpadding="0" border="0" style="table-layout:fixed;">{table}</table></body></html>'
			,format = function (s, c) {return s.replace(/{(\w+)}/g, function (m, p) {return c[p];})}
			return function (name) {			
				var ctx = {worksheet: name || 'Reporte',table: this.html()}
				var blob = new Blob([format(template, ctx)]);//var blobURL = window.URL.createObjectURL(blob);            
				return blob;
			}
	})();	
	$.downloadFile=function (contenido, nombreArchivo) {
		var reader = new FileReader(); 
		if(typeof contenido == 'string')contenido=new Blob([contenido], {type: 'text/plain'}); nombreArchivo=nombreArchivo || 'archivo-'+$.getDate+'.dat'; 
		reader.onload = function (event) {
			var save=document.createElement('a'), clicEvent=new MouseEvent('click',{'view':window,'bubbles':true,'cancelable':true});
			save.href=event.target.result;save.target='_blank';save.download=nombreArchivo;save.dispatchEvent(clicEvent); 
			(window.URL || window.webkitURL).revokeObjectURL(save.href);
		};  reader.readAsDataURL(contenido);
	};
	$.getDate=function(){
		var date = new Date();var day = date.getDate().toString();var monthIndex = (date.getMonth()+1).toString();var year = date.getFullYear();
		if(day.length===1){day="0"+day;}if(monthIndex.length===1){monthIndex="0"+monthIndex;} return day+'-'+monthIndex+'-'+year;
	}
})(jQuery);




