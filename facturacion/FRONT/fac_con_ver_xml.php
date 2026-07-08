<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<HTML><HEAD>
		<!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
        <TITLE><?Php echo "Editar Xml [EXA]"; ?></TITLE>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />                
		<?Php require_once("../../mascaras/model1/estilos/basic.php"); ?>
                <?Php require_once("../../mascaras/model1/estilos/jqgrid.php")?>                 
                <script src="../../framework/plugins/ace-editor/ace-1.2/ace.js"></script>
                <script src="../../framework/plugins/ace-editor/vkbeautify-0.99.js"></script>
                <script language="JavaScript">
		</script>
	</HEAD>
<BODY>
<div id="set1">
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
<tr class="BarraTitulo">
	  <td height="10">&raquo; Edición de Archivos XML</td>
</tr>
<tr>
 <td align="left" valign="top" height="400">
    <form method="post" name="form3" id="form3" enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF'];?> ">
    <FIELDSET>
    <LEGEND>
    <label class="Titulos2">Ingrese el Archivo XML</label>
    </LEGEND>
        
     <table width="100%" border="0" cellpadding="0" cellspacing="0">
     <tr>	
       <td width="87" align="right" class="LetraNegra">Seleccione:</td> 
       <td width="345">&nbsp;<input type="file" name="archivoXML" id="archivoXML" value="" accept="text/xml" /></td>
       <td> 
         <button type="button" class="btn btn-primary start" onclick="loadXML();"><i class=" icon-ok-sign icon-white"></i> <span>Cargar</span> </button>
        
         </td>
     </tr>   
     </table>
       
    </FIELDSET> </form>   
    <FIELDSET>
        <LEGEND>
            <label class="Titulos2">Resultados</label>
        </LEGEND>
       <div> 
           <pre id="editor" style="height: 500px;width: 100%"></pre>
            <table id="list"></table>
            <div id="listPager"></div>
        </div> 
         <div style="padding-top:5px;">
                  <button onclick="$.downloadFile(vkbeautify.xmlmin(editor.getValue()),nameFile);" title="Exportar Excel" class="btn btn-primary start" > <i class="icon-share icon-white" ></i> <span>Descargar</span></button>               
              </div>
    </FIELDSET>    
    </td>
</tr>
</table>
    <script>	
    var editor, nameFile='ninguno.xml';
    function loadXML(){	 
        var reader = new FileReader();
         reader.onload = function(e) {
            nameFile=document.getElementById("archivoXML").value.replace(/.*[\/\\]/, '');
            editor.setValue(vkbeautify.xml(reader.result), -1);
         };
         reader.readAsText(document.getElementById("archivoXML").files[0]); 
    }   
    $(document).ready(function () {
        editor = ace.edit("editor");                        
        editor.setTheme("ace/theme/sqlserver");
        editor.session.setMode("ace/mode/xml");
		editor.$blockScrolling = Infinity;        
    });  
               </script>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
</BODY>
</HTML>
