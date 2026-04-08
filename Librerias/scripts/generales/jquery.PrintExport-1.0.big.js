/*
	Autor : Erik Niebla A.
	Mail: 	ep_niebla@hotmail.com,ep.niebla@gmail.com
	Fecha: 	08/08/2015
	Requiere:	jqGrid,Jquery
*/
var creatorExcel="Erik Niebla A.",
excelFormats={// Add if is necesary
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
},urlPhpExcel='../../Librerias/exportar/ficheroExcel.php';
(function($) {
    if ( $.jgrid ) {
        $.jgrid.extend({
            exportGridExcel: function(o) {
                    o=(typeof o === 'undefined' ? new Array() : o);o.nombre=(typeof o.nombre === 'undefined' ? 'archivo-'+$.getDate()+'.xls' : o.nombre+"-"+$.getDate()+'.xls' );o.hoja=(typeof o.hoja === 'undefined' ? 'Hoja 1' : o.hoja);o.bodyBorder=false;o.print=true,o.removeHiddens=(typeof o.removeHiddens === 'undefined' ? true : o.removeHiddens);
                    //$.downloadFile(this.getTable(o).exportarExcelBlob(o.hoja),o.nombre);
                    o.url=typeof o.nombre === 'undefined' ? urlPhpExcel : o.url;
                    this.getTable(o).exportarExcelPhp(o.nombre,o.url,o.extra,o.hoja);
                    return this;
            },
            exportGridExcelPhp: function(o) {
                    o=(typeof o === 'undefined' ? new Array() : o);o.nombre=(typeof o.nombre === 'undefined' ? 'archivo-'+$.getDate() : o.nombre+"-"+$.getDate() );o.hoja=(typeof o.hoja === 'undefined' ? 'Hoja 1' : o.hoja);o.bodyBorder=false;o.print=true,o.removeHiddens=(typeof o.removeHiddens === 'undefined' ? true : o.removeHiddens);
                    this.getTable(o).exportarExcelPhp(o.nombre,o.url,o.extra,o.hoja);
                    return this;
            },
            printGrid: function(o) {
                    o=(typeof o === 'undefined' ? new Array() : o);o.nombre=(typeof o.nombre === 'undefined' ? 'DataGrid':o.nombre);o.removeHiddens=(typeof o.removeHiddens === 'undefined' ? true : o.removeHiddens);
                    this.getTable(o).printElement({pageTitle:o.nombre});
                    return this;
            },
            exportGridElement: function(o) {return this.getTable(o);},
            exportGridInnerHTML: function(o) {return this.getTable(o).html();},
            exportGridHTML: function(o) {return this.getTable(o).prop('outerHTML');}
        });
        $.fn.getTable = function(o) {
            o=$.extend({caption:true,bodyBorder:true,colorGroup:false,totalRows:true,footer:false,generated:true,print:true,removeCols:[],removeHiddens:false },o||{}); //o.caption=o.caption||true; o.bodyBorder=o.bodyBorder||true; o.colorGroup=o.colorGroup||false; o.totalRows=o.totalRows||false; o.footer=o.footer||false;o.generated=o.generated||true; o.print=o.print||true; o.removeCols=o.removeCols||[]; o.removeHiddens=o.removeHiddens||false;
            var $table=$('<table width="700" cellspacing="0" cellpadding="0" border="0" style="table-layout:fixed;">'+this.html()+'</table>');
            var colLength=$table.find('>tbody >.jqgfirstrow >td').length,colSpan=$table.find('>tbody >.jqgfirstrow >td').filter(function(){ return $(this).css("display")!=="none"; }).length;
            $table.prepend(this.parent().parent().parent().find('>.ui-jqgrid-hdiv .ui-jqgrid-htable').html());
            $table.find('>thead >tr >th').attr('bgcolor',(o.colorGroup?'#d1d9b3':''));
            if(!o.totalRows) $table.find(">tbody>tr.jqfoot").remove();
            if(o.footer)$table.append(this.parent().parent().parent().find('>.ui-jqgrid-sdiv .ui-jqgrid-ftable').html());
            var frow=this.find('>tbody >.jqgfirstrow').prop('outerHTML'); frow=frow.replace(/<td /g,"<th "); frow=frow.replace(/<\/td>/g,"</th>"); frow=$(frow);
            if(o.unHideAll){
                var frowTd=frow.find('td,th').filter(function(){ return $(this).css("display")==="none"; }); colSpan+=frowTd.length;
                frowTd.add($table.find('td,th').filter(function(){ return $(this).css("display")==="none"; })).each(function(){ $(this).attr('style',$(this).attr('style').replace(/disp(.*)none;/gm, "")); });
            }
            if(o.print&&o.removeHiddens){
                $table.find('td,th').filter(function(){ return $(this).css("display")==="none"; }).remove();
                frow.find('td,th').filter(function(){ return $(this).css("display")==="none"; }).remove();
//		$table.find("td[style*='display:none;']").remove();$table.find("td[style*='display: none;']").remove();
//		$table.find("th[style*='display:none;']").remove();$table.find("th[style*='display: none;']").remove();
//		frow.find("th[style*='display:none;']").remove();frow.find("th[style*='display: none;']").remove();
                /*var columnIndex=[],cm=this.jqGrid('getGridParam', 'colModel');
                for (var i = 0, l = cm.length; i < l; i += 1) {if (cm[i].hidden) {columnIndex.push(i);}}
                $table.tRemoveColumn(columnIndex);*/
                if(o.removeCols.length>0){
                        colSpan=colSpan-o.removeCols.length;
                        $table.tRemoveColumn(o.removeCols);
                        for(var i=0,l=o.removeCols.length;i<l;i++)
                                frow.find("th:eq("+o.removeCols[i]+")").remove();
                }
                /* agregado para que cuadre con la impresion */
                var max_l=0, max_p=1030;
                frow.find("th").each(function(){ max_l+=$(this).width(); } );
                if(max_l>max_p){
                    frow.find("th").each(function(i){ $(this).width(Math.round($(this).width()*max_p/max_l)); } );
                    $table.find('>tbody >tr >td, >thead >tr >th').css('width','');
                }
            }
            if(o.caption){ var cap=$('<div>'+this.parent().parent().parent().find('.ui-jqgrid-title').html()+'</div>'); cap.find('.pull-right').remove(); if(cap.text().trim()!=='')$table.find('thead').prepend('<tr><th style="text-align:center;" colspan="'+(o.print?colSpan:colLength)+'">'+cap.text()+'</th></tr>');}
            $table.find('>thead').prepend(frow);
            if(o.generated)$table.append('<tbody><tr><td style="text-align:right;" colspan="'+(o.print?colSpan:colLength)+'">Generado el '+$.getDate()+' por '+(typeof o.creator === 'undefined'?creatorExcel:o.creator)+'</td></tr></tbody>');
            $table.find('>thead >tr').removeAllAttr().find('>th').removeAllAttr().clearCell().css('border','1px solid black').css('white-space','nowrap').css('overflow','hidden');
            var $tbody=$table.find('tbody');
            $tbody.find('>tr').find('>td').not('.ui-sgcollapsed').clearCell();
            $tbody.find('>tr').css('display','');
            $tbody.find('>tr.jqgroup').find('>td[colspan]').attr('colspan',(o.print?colSpan:colLength)).attr('bgcolor',(o.colorGroup?'#FFFF99':''));
            $tbody.find('>tr.jqgroup + tr.jqgroup').find('>td[colspan]').prepend('<i>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</i>').attr('bgcolor',(o.colorGroup?'#8effe5':''));
            $tbody.find('>tr.jqgroup + tr.jqgroup + tr.jqgroup').find('>td[colspan]').prepend('<i>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</i>').attr('bgcolor',(o.colorGroup?'#ffd29a':''));
            $tbody.find('>tr').find('>td').not('.ui-sgcollapsed').removeAllAttr().addBorders((o.bodyBorder?'1px':'0.1pt'));
            if(o.footer){
                $tbody.find('>tr.footrow >td:not(:empty)').attr('bgcolor',(o.colorGroup?'#FFFF99':''));
                $tbody.find('>tr.footrow >td').removeAllAttr().clearCellBordered(colLength,colSpan,o.print);
            }
            $tbody.find('>tr.jqfoot >td:not(:empty)').attr('bgcolor',(o.colorGroup?'#8effe5':''));
            $tbody.find('>tr.jqfoot >td').removeAllAttr().clearCellBordered(colLength,colSpan,o.print);
            $tbody.find('>tr.jqgroup >td').removeAllAttr().clearCellBordered(colLength,colSpan,o.print);
            $tbody.find('>tr').removeAllAttr();
            if(typeof o['sepEnd']!=='undefined' && o.sepEnd) $table.append('<tbody><tr><td colspan="'+(o.print?colSpan:colLength)+'">&nbsp;</td></tr></tbody>');
            if(typeof o['addCols']!=='undefined' && o.addCols>0) $table.tAddColumn(o.addCols);
            return $table;
        };
        $.fn.addBorders = function(border) {
            this.each(function() {
              for (var i = this.attributes.length -1; i >= 0 ; i--)
                     if(this.attributes[i].name==='style')
                            {this.attributes[i].value=this.attributes[i].value+"border:"+border+" solid black;padding-left:5px;padding-right:5px;white-space:nowrap;overflow:hidden;";break;}
            });return this;
        };
        $.fn.clearCell=function() { this.each(function(){ var t=$(this),ta=t.find('table.ui-jqgrid-btable').first(); t.html(ta.length>0?'<div style="height:4px;"></div><table cellspacing="0" cellpadding="0" style="border-collapse: collapse;table-layout: fixed;">'+ta.jqGrid('exportGridInnerHTML',{generated:false,caption:false,footer:true,bodyBorder:false,removeHiddens:true})+'</table><div style="height:10px;"></div>':t.text().trim().replace(/\n/g, "<br/>"));}); return this;};
        $.fn.clearCellBordered = function(colLength,colSpan,print) {
            this.each(function() {
            if($(this).text().trim()!=="") {$(this).css('font-weight','bold').css('border','1px solid black').css('padding-left','5px').css('padding-right','5px');if($(this).attr('colspan')===colLength&&print)$(this).attr('colspan',colSpan);}
                    else $(this).css('border','0');
            });return this;
        };
        $.fn.removeAllAttr = function() {
            this.each(function() {
              for (var i = this.attributes.length -1; i >= 0 ; i--) {
                     if(this.attributes[i].name!=='colspan'&&this.attributes[i].name!=='rowspan'&&this.attributes[i].name!=='style'&&this.attributes[i].name!=='bgcolor')  $(this).removeAttr(this.attributes[i].name);
              }
            });return this;
        };
    }
})(jQuery);
var fileDownloadToken="",fileDownloadCheckTimer;
function finishDownload() {
    window.clearInterval(fileDownloadCheckTimer);
    $.removeCookie('fileDownloadToken'); //clears this cookie value
    $('#loader').fadeOut('slow');
}
(function($) {
    var ifr=$('<iframe name="dummy" style="display:none;" />');
    $(document.body).append(ifr);
    if(typeof $.fn.load=='undefined')
    ifr.on('load',function(){
        finishDownload();
        if ( this.contentDocument.readyState==="complete" ) {
            var ifTitle = this.contentDocument.title;
            if ( ifTitle.indexOf("404")>=0 ) {
                $.alert("No se logro cargar la informacion!");
            }
        }
    });
    else
    ifr.load(function(){
        finishDownload();
        if ( this.contentDocument.readyState==="complete" ) {
            var ifTitle = this.contentDocument.title;
            if ( ifTitle.indexOf("404")>=0 ) {
                $.alert("No se logro cargar la informacion!");
            }
        }
    });
    $.fn.exportarExcelPhp = (function (nombre,url,extra,worksheet) {
        $('#loader').show(); fileDownloadToken=nombre+"_"+new Date().getTime();
        var f=$('<form target="dummy" />').attr({'action':(typeof url==='undefined'?urlPhpExcel:url),'method':'post'}).css('display','none');
        if(typeof extra!=='undefined') f.append($('<input name="'+extra+'" value="" />'));
        if(typeof worksheet!=='undefined') f.append($('<input name="hoja" value="'+worksheet+'" />'));
        f.append($('<input name="new" value="" />')).append($('<input name="nombre" />').val(nombre)).append($('<input name="fileDownloadToken" />').val(fileDownloadToken)).append($('<textarea name="datos_a_enviar" />').val(this[0].outerHTML));
        $('body').append(f);
        fileDownloadCheckTimer = window.setInterval(function () {
            if ($.getCookie('fileDownloadToken') === fileDownloadToken) finishDownload();
        }, 100);
        f.submit(); f.remove();
    });
    $.fn.exportarExcelBlob = (function () { //debe ser un elemento $('<table></table>').
        var template = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><meta http-equiv="content-type" content="application/vnd.ms-excel; charset=UTF-8"><head><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>{worksheet}</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head><body><table cellspacing="0" cellpadding="0" border="0" style="table-layout:fixed;">{table}</table></body></html>'
            ,format = function (s, c) {return s.replace(/{(\w+)}/g, function (m, p) {return c[p];}); };
            return function (name) {
                var ctx = {worksheet: name || 'Reporte',table: this.html()};
                var blob = new Blob([format(template, ctx)]);//var blobURL = window.URL.createObjectURL(blob);
                return blob;
            };
    })();
    $.exportarExcelBlob = (function () { //todo el HTML incluyendo el tag <table></table>
        var template = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><meta http-equiv="content-type" content="application/vnd.ms-excel; charset=UTF-8"><head><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>{worksheet}</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head><body>{table}</body></html>'
            ,format = function (s, c) {return s.replace(/{(\w+)}/g, function (m, p) {return c[p];});};
            return function (html,name) {
                var ctx = {worksheet: name || 'Reporte',table: html};
                var blob = new Blob([format(template, ctx)]);//var blobURL = window.URL.createObjectURL(blob);
                return blob;
            };
    })();
    $.downloadFile=function (contenido, nombreArchivo) { // contenido debe ser string o blob
        var reader = new FileReader();
        if(typeof contenido === 'string')contenido=new Blob([contenido], {type: 'text/plain'}); nombreArchivo=nombreArchivo || 'archivo-'+$.getDate()+'.dat';
        reader.onload = function (event) {
            var save=document.createElement('a'), clicEvent=new MouseEvent('click',{'view':window,'bubbles':true,'cancelable':true});
            save.href=event.target.result;save.target='_blank';save.download=nombreArchivo;save.dispatchEvent(clicEvent);
            (window.URL || window.webkitURL).revokeObjectURL(save.href);
        };  reader.readAsDataURL(contenido);
    };
    $.getDate=function(){
        var date = new Date();var day = date.getDate().toString();var monthIndex = (date.getMonth()+1).toString();var year = date.getFullYear();
        if(day.length===1){day="0"+day;}if(monthIndex.length===1){monthIndex="0"+monthIndex;} return day+'-'+monthIndex+'-'+year;
    };
    $.fn.tRemoveColumn=function(columnIndex){ var index=[];
        if(Array.isArray(columnIndex)){index=columnIndex;index.sort(function(a, b){return b-a;});}else{index=[columnIndex];}
        for(var i=0;i<index.length;i++){
            this.find("tbody,thead").find("tr").find("td:eq("+index[i]+"):not(:eq(0)[colspan])").remove();
            this.find("thead").find("tr").find("th:eq("+index[i]+")").remove();
            //this.find("thead").find("tr").find("td:eq("+index[i]+")").remove();
        }return this;
    };
    $.fn.tAddColumn=function(cols){ var td='',th='';
        for(var i=0;i<cols;i++){ td+='<td></td>';th+='<th></th>'; }
        this.find("tbody").find("tr").prepend(td); this.find("thead").find("tr").prepend(th);
        return this;
    };
})(jQuery);
/*
* Print Element Plugin 1.2
* Copyright (c) 2010 Erik Zaadi
*/
(function (window, undefined) {
    var document = window["document"];
    var $ = window["jQuery"];
    $.fn["printElement"] = function (options) {
        var mainOptions = $.extend({}, $.fn["printElement"]["defaults"], options);
        //iframe mode is not supported for opera and chrome 3.0 (it prints the entire page). //http://www.google.com/support/forum/p/Webmasters/thread?tid=2cb0f08dce8821c3&hl=en alert(navigator.userAgent.toLowerCase());
        if (mainOptions["printMode"] === 'iframe') {
            if ($.browser.opera||$.browser.mozilla)mainOptions["printMode"]='popup';
			if ($.browser.chrome){var versionChrome=$.browser.version.split(".");if((versionChrome[0]*1)<=3)mainOptions["printMode"]='popup';}
        }
        $("[id^='printElement_']").remove();//Remove previously printed iframe if exists
        return this.each(function () {
            var opts = $.meta ? $.extend({}, mainOptions, $(this).data()) : mainOptions;//Support Metadata Plug-in if available
            _printElement($(this), opts);
        });
    };
    $.fn["printElement"]["defaults"] = {
        "printMode": 'iframe', //Usage : iframe / popup
        "pageTitle": '', //Print Page Title
        "overrideElementCSS": null,
        /* Can be one of the following 3 options:
        * 1 : boolean (pass true for stripping all css linked)
        * 2 : array of $.fn.printElement.cssElement (s)
        * 3 : array of strings with paths to alternate css files (optimized for print)
        */
        "printBodyOptions": {
            "styleToAdd": 'padding:10px;margin:10px;', //style attributes to add to the body of print document
            "classNameToAdd": '' //css class to add to the body of print document
        },
        "leaveOpen": true, // in case of popup, leave the print page open or not
        "iframeElementOptions": {
            "styleToAdd": 'border:none;position:absolute;width:1000px;height:0px;bottom:0px;left:0px;', //style attributes to add to the iframe element
            "classNameToAdd": '' //css class to add to the iframe element
        }
    };
    $.fn["printElement"]["cssElement"] = {"href": '',"media": ''};
    function _printElement(element, opts) {
        var html = _getMarkup(element, opts);//Create markup to be printed
        var popupOrIframe = null;
        var documentToWriteTo = null;
        if (opts["printMode"].toLowerCase() === 'popup') {
            popupOrIframe = window.open('about:blank', 'printElementWindow', 'width=650,height=440,scrollbars=yes');
            documentToWriteTo = popupOrIframe.document;
        }
        else {
            var printElementID = "printElement_" + (Math.round(Math.random() * 99999)).toString();//The random ID is to overcome a safari bug http://www.cjboco.com.sharedcopy.com/post.cfm/442dc92cd1c0ca10a5c35210b8166882.html
            var iframe = document.createElement('IFRAME');//Native creation of the element is faster..
            $(iframe).attr({
                style: opts["iframeElementOptions"]["styleToAdd"],
                id: printElementID,
                className: opts["iframeElementOptions"]["classNameToAdd"],
                frameBorder: 0,
                scrolling: 'no',
                src: 'about:blank'
            });
            document.body.appendChild(iframe);
            documentToWriteTo = (iframe.contentWindow || iframe.contentDocument);
            if (documentToWriteTo.document) documentToWriteTo = documentToWriteTo.document;
            iframe = document.frames ? document.frames[printElementID] : document.getElementById(printElementID);
            popupOrIframe = iframe.contentWindow || iframe;
        }
        focus();documentToWriteTo.open();documentToWriteTo.write(html);documentToWriteTo.close();//_callPrint(popupOrIframe);
    };
    /*function _callPrint(element) {
        if (element && element["printPage"]) element["printPage"]();
        else setTimeout(function () {_callPrint(element);}, 50);
    }*/
    function _getElementHTMLIncludingFormElements(element) {
        var $element = $(element);
        $(":checked", $element).each(function () {this.setAttribute('checked', 'checked');});//Radiobuttons and checkboxes
        $("input[type='text']", $element).each(function (){this.setAttribute('value', $(this).val());});//simple text inputs
        $("select", $element).each(function () {
            var $select = $(this);
            $("option", $select).each(function () {if ($select.val() === $(this).val()) this.setAttribute('selected', 'selected');});
        });
        $("textarea", $element).each(function () {
            var value = $(this).attr('value'); //Thanks http://blog.ekini.net/2009/02/24/jquery-getting-the-latest-textvalue-inside-a-textarea/
            if ($.browser.mozilla && this.firstChild) this.firstChild.textContent = value; else this.innerHTML = value;//fix for issue 7 (http://plugins.jquery.com/node/13503 and http://github.com/erikzaadi/jQueryPlugins/issues#issue/7)
        });
        return $('<div></div>').append($element.clone().css('display','').css('margin-top','').css('width','')).html(); //http://dbj.org/dbj/?p=91
    }
    function _getBaseHref() {
		var port = (window.location.port) ? ':' + window.location.port : '';
        return window.location.protocol + '//' + window.location.hostname + port + window.location.pathname;
    }
    function _getMarkup(element, opts) {
        var $element = $(element);
        var elementHtml = _getElementHTMLIncludingFormElements(element);
        var html = new Array();
        if (document.doctype) { var node = document.doctype; html.push("<!DOCTYPE "+node.name+(node.publicId ?' PUBLIC "'+node.publicId+'"':'')+(!node.publicId && node.systemId?' SYSTEM':'')+(node.systemId?' "'+node.systemId+'"':'')+'>'); }
        html.push('<html><head><title>' + opts["pageTitle"] + '</title> <meta charset="iso-8859-1"> <style type="text/css" media="print">/*@page{ size:auto; margin:0mm; }html{margin:10mm}*/</style>');
        if (opts["overrideElementCSS"]){
            if (opts["overrideElementCSS"].length > 0)
                for (var x = 0; x < opts["overrideElementCSS"].length; x++) {
                    var current = opts["overrideElementCSS"][x];
                    if (typeof (current) === 'string') html.push('<link type="text/css" rel="stylesheet" href="' + current + '" >');
                    else html.push('<link type="text/css" rel="stylesheet" href="' + current["href"] + '" media="' + current["media"] + '" >');
                }
        }else{
            $("link", document).filter(function () {return $(this).attr("rel").toLowerCase() === "stylesheet";})
				.each(function () {html.push('<link type="text/css" rel="stylesheet" href="' + $(this).attr("href") + '" media="' + $(this).attr('media') + '" >');});
        }
        //Ensure that relative links work
        var print_fun='function printAll(){focus();print();' + ((!$.browser.opera && !opts["leaveOpen"] && opts["printMode"].toLowerCase() === 'popup') ? 'close();' : '') + '}';
        html.push('<base href="' + _getBaseHref() + '" />');
        html.push('</head><body onload="printPage()" style="' + opts["printBodyOptions"]["styleToAdd"] + '" class="' + opts["printBodyOptions"]["classNameToAdd"] + '">');
        html.push('<div class="' + $element.attr('class') + '">' + elementHtml + '</div>');
        html.push('<script type="text/javascript">'+print_fun+' var imgs, len=0, counter=0; function incrementCounter(){ counter++; if ( counter===len ){ printAll(); } }  function printPage(){ if(typeof document!=="undefined"&&typeof document.images!=="undefined"&&document.images.length>0){ imgs=document.images, len=imgs.length, counter=0; [].forEach.call(imgs, function(img){ if(img.complete) incrementCounter(); else{ img.addEventListener("load", incrementCounter, false ); img.onerror = incrementCounter; }  } ); }else{ printAll(); } } </script>');
        html.push('</body></html>');
        return html.join('');
    };
})(window);

/* ARREGLO SI NO EXISTE $.browser*/
if ( !$.browser ) {
    $.uaMatch = function( ua ) {
        ua = ua.toLowerCase();
        var match = /(chrome)[ \/]([\w.]+)/.exec( ua ) ||/(webkit)[ \/]([\w.]+)/.exec( ua ) ||
                /(opera)(?:.*version|)[ \/]([\w.]+)/.exec( ua ) ||/(msie) ([\w.]+)/.exec( ua ) ||
                ua.indexOf("compatible") < 0 && /(mozilla)(?:.*? rv:([\w.]+)|)/.exec( ua ) ||[];
        return {browser: match[ 1 ] || "",version: match[ 2 ] || "0"};
    };
    var browser, matched = $.uaMatch( navigator.userAgent );browser = {};
    if ( matched.browser ) {browser[ matched.browser ] = true;browser.version = matched.version;}
    if ( browser.chrome ) {browser.webkit = true;} else if ( browser.webkit ) {browser.safari = true;}
    $.browser = browser;//console.log(browser);
}
$.imprimirUrl=function(url,css){ $('#loader').show(); $.get(url, {}, function(r){ var p1=/<body[^>]*>((.|[\n\r])*)<\/body>/im, p2=/<head[^>]*>((.|[\n\r])*)<\/head>/im, p3=/<style[^>]*>((.|[\n\r])[^"]*)<\/style>/gmi, b=r, body=p1.exec(b), s='', styles; if($.vv(body)){ var head=p2.exec(b); b=body[1]; if($.vv(head)) styles=head[1].match(p3); if($.isArray(styles)) $.each(styles,function(i,v){ s=s+v; }); } $('<div>'+s+b+'</div>').printElement({ pageTitle:'Reporte Exa', overrideElementCSS:[{ href:(css||'../../mascaras/model1/estilos/print.css'), media:'print'}]}); }).fail(function (){ $.alert(); }).always(function(){ $("#loader").fadeOut("slow"); }); };
$.fn.reportExa=function(inner,opts){ opts=opts||{}; inner=inner||'';
    if(typeof inner==='object'&&inner.length===1){ inner=inner.jqGrid('exportGridInnerHTML',$.extend({generated:true,caption:false,bodyBorder:false,footer:true,print:true,removeHiddens:true},opts['configs'])); }
    var file=opts['file']||this.data('file')||"Reporte", tabla=this.find('table.tablaReporte');
    if(!!opts['title']) this.find('.titleReporte').html(opts['title']); this.find('.subTitleReporte').html(opts['subTitle']||""); tabla.html(inner);
    if(opts['excel']){
        this.find('table.reporteClass').find('tr th[colspan]:first-child, tr td[colspan]:first-child').attr('colspan', tabla.find('thead:first-child tr:first-child th').length);
        $.downloadFile($.exportarExcelBlob(this.html(), file.upperFirstChar()), file+'_'+$.getDate()+'.xls');
    } else this.printElement({pageTitle:creatorExcel,printMode:'iframe',overrideElementCSS:[{ href:'../../mascaras/model1/estilos/print.css',media:'print'}]});
};
$.reportExa=function(inner,opts){
    opts=opts||{}; $(opts['excel']?'#exportarReporte':'#imprimirReporte').reportExa(inner,opts);
};
$.getReportsExa=function(title,subTitle){ if(!$('#imprimirReporte').length){ $.get('../../administrador/FRONT/home.php',{getReportsExa:true,title:title,subTitle:subTitle}).success(function(r){ $('body').append('<div class="reportsExa">'+r+'</div>'); }); } };
/* EJEMPLOS */
/*
$(grid).jqGrid('exportGridExcel');  //Exporta Grid en excel
$(grid).jqGrid('printGrid'); // Regresa un popup para imprimir
$(tabla).html($(grid).jqGrid('exportGridInnerHTML')); //El html interno de la grid
$(div).append($(grid).jqGrid('exportGridElement')); //Regresa la grid como elemento tabla en jquery

$(element).printElement();
$.downloadFile($(tabla).exportarExcelBlob('Hoja 1'),'archivo-'+$.getDate()+'.xls');  // descaga una tabla como archivo de excel
$.downloadFile($.exportarExcelBlob($(padreTabla).html(),'Hoja 1'),'archivo-'+$.getDate()+'.xls');  // descaga una tabla como archivo de excel
$.downloadFile(textString,'archivo-'+$.getDate()+'.xml');  // descaga texto como archivo con el formato indicado
$.getDate(); //Devuelve la fecha actual
*/
/* CONFIGURACIONES */
/*
$(grid).jqGrid('exportGridExcel'{nombre:'Reporte',hoja:'Hoja 1',caption:true,footer:true,generated:true});  //Exporta Grid en excel
$(grid).jqGrid('printGrid',{nombre:'Reporte de Datos',bodyBorder:false}); // Regresa un popup para imprimir
$(element).printElement({pageTitle:'Reporte',printMode:'iframe',leaveOpen: true, overrideElementCSS:[{ href:'css/print-grid.css',media:'print'}]});
*/
