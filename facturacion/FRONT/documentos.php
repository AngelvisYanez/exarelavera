<?php	
/**
* @abstract Permite
* @author Erik Niebla
* @version 1.0
* Fecha de creación  2016-11-24
*/
require_once('../../Librerias/procedimientos/almacenados_standar.php'); 
require_once('../../Librerias/config.php/register_globals.php');
require_once('../LOGICA/fac_log_electronica.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Elect($Ses_Cli_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Elect;

$hoy = date("Y-m-d");
$mes = date("m");
$ruta="/FRONT/$Ses_Cli_Emp_Cod/";
$ruta_xmls=$APP_REAL_PATH."/facturacion".$ruta;

if(isset($docsAjax)){
    //$obBD_con1->echoLog($_SESSION);
    $resp=array('success'=>true);
    $all=(!isset($type)||empty($type)||$type=='TODOS');
    $ventas=$all||$type=='VENTAS'?$obBD_con1->getArrayConsulta(3, $Ses_Cli_Emp_Cod.'*'.$Ses_Cli_Cod.'*Tic_Sri!=4', $obBD_conexion):array();
    $notasc=$all||$type=='NOTASC'?$obBD_con1->getArrayConsulta(3, $Ses_Cli_Emp_Cod.'*'.$Ses_Cli_Cod.'*Tic_Sri=4', $obBD_conexion):array();
    $retenc=$all||$type=='RETENC'?$obBD_con1->getArrayConsulta(4, $Ses_Cli_Emp_Cod.'*'.$Ses_Prv_Cod, $obBD_conexion):array();
   
    $resp['rows']=array_merge($retenc,array_merge($ventas,$notasc));
    
    foreach($resp['rows'] AS &$r){  
        $xml=$ruta_xmls.$r['Doc_Xml'];
        
        if(is_readable($xml."_A.xml")){
            $r['file_xml']='..'.$ruta.$r['Doc_Xml']."_A.xml";
        } 
		
    } unset($r);
    //$resp['rows']=array();
    $obBD_con1->echoJson($resp);
}
$Doc_Tip=isset($type)?$type:'ALL';

?>
<!DOCTYPE html>
<HTML>
<HEAD>		
    <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?> 
    <style>  
    </style>
</HEAD>
<BODY> 
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Consultar Documentos Electronicos </h3></div>        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            
           
                <div class="row">   
                    <form id="formDocsSearch">
                    <div class="col-xs-6">  
                        <fieldset class="exa-fieldset ">                           
                            <legend class="Titulos2">Filtros</legend>
                            <div class="form-horizontal normal">
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-sm required">Tipo Documento:</label>  
                                    <div class="col-xs-9"> 
                                        <select id="type" name="type" class="form-control input-sm readOnly" onchange="setDocs();" required="" disabled="">
                                            <option value="TODOS" <?php if($Doc_Tip=='ALL') echo 'selected' ?>>Todos</option>
                                            <option value="VENTAS" <?php if($Doc_Tip=='VENTAS') echo 'selected' ?>>Facturas</option>                                            
                                            <option value="RETENC" <?php if($Doc_Tip=='RETENC') echo 'selected' ?>>Retenciones</option>
                                            <option value="NOTASC" <?php if($Doc_Tip=='NOTASC') echo 'selected' ?>>Notas de Crédito</option>
                                            <option value="NOTASD" <?php if($Doc_Tip=='NOTASD') echo 'selected' ?>>Notas de Debito</option>
                                            <option value="GUIAS" <?php if($Doc_Tip=='GUIAS') echo 'selected' ?>>Guias de Remisión</option>
                                        </select>
                                    </div>                                  
                                </div>                                                                 
                            </div>
                        </fieldset>
                    </div> 
                    <div class="col-xs-6">  
                    
                    </div> 
                    </form>
                    <div class="col-xs-12" id="gridContainer" style="padding-bottom: 8px; min-height: 300px;">
                        <table id="documentos"></table><div id="documentosPager"></div>
                    </div>    
                </div>
            
        </div>
    </div>
    <script type="text/javascript">
        var docs,mydata ;
        $(function() {
            docs=$('#documentos');
            docs.createGrid({
                caption:'Documentos Electronicos', height: 270,grouping:true,datatype: 'local',data:[],
                groupingView : { groupField : ['Tipo'], groupOrder: ['desc'] },
                colModel: [                     
                    { label: 'Cód. Int.', name: 'Doc_Cod', width: 20 ,align:"center" },  
                    { label: 'id', name: 'id', width: 30 ,align:"center", key:true, hidden:true}, 
                    { label: 'Tipo', name: 'Tipo', width: 30 ,align:"center"}, 
                    { label: 'Fecha', name: 'Doc_Fec', width: 30 ,align:"center"},
                    { label: 'Serie', name: 'Doc_Ser', width: 30 ,align:"center"}, 
                    { label: 'Numero', name: 'Doc_Num', width: 30 ,align:"center"}, 
                    { label: 'Archivo', name: 'Doc_Xml', width: 150, formatter:'docXml', title:false  },                     
                    { label: 'Autorizado', name: 'Doc_Aut', width: 20,  align:"center", formatter:'truefalse', formatoptions:{yesMsg:'Autorizado',noMsg:'No Autorizado'}, title:false},                     
                    { label: 'Estado', name: 'Doc_Aut', width: 30,  align:"center", formatter:'estado', formatoptions:{full:true,types:{S:'Autorizado',N:'Pendiente'}}, title:false  }, 
                    { label: 'Tabla', name: 'tabla', hidden:true}, 
                    { label: 'Campo Sri', name: 'campo1', hidden:true}, 
                    { label: 'Campo Aut', name: 'campo2', hidden:true}, 
                    { label: 'Campo Id', name: 'cod', hidden:true}, 
					{ label: '&nbsp;', name: 'act1', width: 15, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{action:ver, icon:'fa-eye', title:'Ver PDF', conditional:function(o){ return o.Doc_Aut==='S'; } }, title:false },
                    { label: '&nbsp;', name: 'act2', width: 15, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{action:descargar, icon:'fa-download', title:'Descargar', conditional:function(o){ return o.Doc_Aut==='S'; } }, title:false }
                ] 
            },false,'#documentosPager');
            setDocs();
        });
        $.fn.fmatter.docXml=function(cv,opts,cObjt){ return '<div class="other-title" title="'+cv+'" data-originaldata="'+cv+'">&nbsp;<i class="fa fa-file-code-o blue" style="font-size:14px;"></i>&nbsp;&nbsp;&nbsp;'+cv+'.xml</div>'; };
        $.fn.fmatter.docXml.unformat=function(cv,opts,el){ return $(el).find('div').data('originaldata'); };
        function setDocs(){ $.getDataJson('',$('#formDocsSearch').getData('docsAjax'),function(r){  docs.jqGrid('setGridParam',{ datatype: 'local',data:r['rows']})
    .trigger("reloadGrid") }); }   
        function descargar(data){
			var save=document.createElement('a'), clicEvent=new MouseEvent('click',{'view':window,'bubbles':true,'cancelable':true});
			save.href=data['file_xml'];
			save.target='_blank';
			save.download=data['Doc_Xml']+'.xml';
			save.dispatchEvent(clicEvent); 
			//window.open(data['file_xml']);
            //console.log(data);
        }
		function ver(data){
			var rutas=<?php echo json_encode($obBD_con1->xml_tag); ?>;
			window.open('../COMPONENTES/'+rutas[data['type']]['pdf']+"?urlXml="+data['file_xml']+"&op=I&logoUrl=<?php echo $Ses_Cli_Emp_Log; ?>");
            //console.log(data);			
        }
    </script>
</BODY>
</HTML>