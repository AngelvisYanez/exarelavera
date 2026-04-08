/*
	Autor : Erik Niebla A.
	Mail: 	ep_niebla@hotmail.com,ep.niebla@gmail.com
	Fecha: 	08/08/2015
*/
function getDate(){
	var date = new Date();var day = date.getDate().toString();var monthIndex = date.getMonth().toString();var year = date.getFullYear();
	if(day.length===1){day="0"+day;}if(monthIndex.length===1){monthIndex="0"+monthIndex;}
	return day+'-'+monthIndex+'-'+year;
}
function createPrintWindow(title) {
	var disp_setting = "toolbar=yes,location=no,directories=yes,menubar=yes,scrollbars=yes,";
	var printWindow = window.open("","_blank",disp_setting);//var printWindow = window.open("","_blank");
	printWindow.document.open(); 
	printWindow.document.write('<html><head><title>' + title + '</title>');	
	printWindow.document.write('</head><body onLoad="self.print()" id="body" style="padding:10px;">');	
	return printWindow;
}

function closePrintWindow(printWindow) {
	printWindow.document.write('</body></html>');    
	printWindow.document.close();
	printWindow.focus();
}

function printReport(table, title) {	
	var printWindow = createPrintWindow(title);
	printWindow.document.write(document.getElementById(table).innerHTML);	
	closePrintWindow(printWindow);
}
function printPage(page, title) {	
	var disp_setting = "toolbar=yes,location=no,directories=yes,menubar=yes,scrollbars=yes,";
	var printWindow = window.open("","",disp_setting);
	printWindow.document.open();
	printWindow.document.write(page);	
	printWindow.document.close();
	printWindow.document.onLoad=function(){self.print();}
	printWindow.focus();
}
function exportarExcel(table){
   window.open("data:application/vnd.ms-excel,"+escape(document.getElementById(table).innerHTML));
} 

var exportarExcelUri = (function() {
          var uri = 'data:application/vnd.ms-excel;base64,'
            , template = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><meta http-equiv="content-type" content="application/vnd.ms-excel; charset=UTF-8"><head><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>{worksheet}</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head><body>{table}</body></html>'
            , base64 = function(s) { return window.btoa(unescape(encodeURIComponent(s))) }
            , format = function(s, c) { return s.replace(/{(\w+)}/g, function(m, p) { return c[p]; }) }
          return function(table, name) {
            if (!table.nodeType) table = document.getElementById(table)
            var ctx = {worksheet: name || 'Reporte', table: table.innerHTML}
            window.location.href = uri + base64(format(template, ctx))
          }
})()	
	
var exportarExcelBlob = (function () {
    var template = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><meta http-equiv="content-type" content="application/vnd.ms-excel; charset=UTF-8"><head><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>{worksheet}</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head><body>{table}</body></html>'
		,format = function (s, c) {return s.replace(/{(\w+)}/g, function (m, p) {return c[p];})}
        return function (table, name) {
            if (!table.nodeType) table = document.getElementById(table)
            var ctx = {worksheet: name || 'Reporte',table: table.innerHTML}
            var blob = new Blob([format(template, ctx)]);//var blobURL = window.URL.createObjectURL(blob);            
            return blob;
        }
})()

function downloadFile(contenido, nombreArchivo) {
  if(typeof contenido == 'string')contenido=new Blob([contenido], {type: 'text/plain'}); 
  nombreArchivo=nombreArchivo || 'archivo.dat'; // declaraciones
  var reader = new FileReader(); //creamos un FileReader para leer el Blob
  reader.onload = function (event) {//Definimos la función que manejará el archivo //una vez haya terminado de leerlo
    var save = document.createElement('a');//Usaremos un link para iniciar la descarga 
    save.href = event.target.result;save.target = '_blank';save.download = nombreArchivo; // llenado de atributos
    var clicEvent = new MouseEvent('click', {'view': window,'bubbles': true,'cancelable': true}); //creacion del evento clic
	save.dispatchEvent(clicEvent); //Simulamos un clic del usuario //no es necesario agregar el link al DOM.
    (window.URL || window.webkitURL).revokeObjectURL(save.href);//Y liberamos recursos...
  };  reader.readAsDataURL(contenido);//Leemos el blob y esperamos a que dispare el evento "load"
};