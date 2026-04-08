<?php	
/**
* @abstract Permite
* @author Erik Niebla
* @version 1.0
* Fecha de creación  2016-11-24
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_fac_elect.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_FacEle($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_FacEle;

$hoy = date("Y-m-d");
$mes = date("m");
$ruta_xmls=$APP_REAL_PATH."/facturacion/FRONT/$Ses_Emp_Cod/";

if(isset($docsAjax)){
    $resp=array('success'=>true);
    $all=(!isset($type)||empty($type)||$type=='TODOS');
    $ventas=$all||$type=='VENTAS'?$obBD_con1->getArrayConsulta(1, $Ses_Emp_Cod.'*Tic_Sri!=4', $obBD_conexion):array();
    $notasc=$all||$type=='NOTASC'?$obBD_con1->getArrayConsulta(1, $Ses_Emp_Cod.'*Tic_Sri=4', $obBD_conexion):array();
    $retenc=$all||$type=='RETENC'?$obBD_con1->getArrayConsulta(2, $Ses_Emp_Cod, $obBD_conexion):array();
   
    $resp['rows']=array_merge($retenc,array_merge($ventas,$notasc));
    
    foreach($resp['rows'] AS &$r){  
        $xml=$ruta_xmls.$r['Doc_Xml'];
        if(is_readable($xml."_F.xml"))
            $r['Doc_Fir']='S';        
        if( $r['Doc_Aut']=='S' || is_readable($xml."_A.xml")){
            $r['Doc_Fir']='S';
            $r['Doc_Env']='S';
            $r['Doc_Aut']='S';
        }
        if($r['Doc_Aut']=='S'){
            if(is_readable($xml.".xml")) unlink($xml.".xml");
            if(is_readable($xml."_F.xml")) unlink($xml."_F.xml");
            //if(is_readable($xml."_A.xml")) unlink($xml."_A.xml");
        }
    } unset($r);
    $obBD_con1->echoJson($resp);
}
$llave=$obBD_con1->getRowConsulta(5, $Ses_Emp_Cod, $obBD_conexion);
$config=$obBD_con1->getRowConsulta(7, $Ses_Emp_Cod, $obBD_conexion);
//$obBD_con1->echoLog($config);
if(isset($autorizaDocs)){
    $resp=array('success'=>true,'data'=>$data); 
    require_once('../../Librerias/FactElect/FirmaElectronica.php');
    $DocElect = new FirmaElectronica(); 
    $DocElect->setProduction(($config['Cof_Fac']*1==2));
    foreach($resp['data'] AS &$d){
        $xml=$ruta_xmls.$d['Doc_Xml'];
        $DocElect->setFileSignedPath($xml.'_F.xml');
        if($d['Doc_Fir']!='S'){
            if(is_readable($xml.".xml")){
                if(empty($llave['Lla_Rut'])||empty($llave['Lla_Cla'])) $obBD_con1->echoJson(array('success'=>false,'message'=>"No se ha especificado una firma digital!"));
            
                if(!$DocElect->isKeyActive())
                  if(!$DocElect->setKey($ruta_xmls.$llave['Lla_Rut'],$llave['Lla_Cla'])) $obBD_con1->echoJson(array('success'=>false,'message'=>"No se pudo leer la firma digital ".(!empty($llave['Lla_Rut'])?"<i class='green'>$llave[Lla_Rut]</i>":'')));
              
                $DocElect->setFileToSignPath($xml.'.xml'); 
                $doc=$DocElect->signXml();
                if($doc!==false){ 
                    $d['Doc_Fir']='S';                     
                }else $d['Error']='Error al Firmar el documento!'; 
            }else $d['Error']="Error no se encontro el <u>XML</u> de $d[Doc_Xml]!";
        }
        if($d['Doc_Fir']=='S' && $d['Doc_Env']!='S'){
            $result=$DocElect->sendToSri();
            if($result['success']==true){ 
                $d['Doc_Env']='S';
            }else $d['Error']="<span>Error al enviar el documento!<br/>[<i style='color:red;'>$result[message]</i>]".(!empty($result['informacionAdicional'])?"<br/>$result[informacionAdicional]</span>":'');
        }
        if($d['Doc_Fir']=='S' && $d['Doc_Env']=='S' && $d['Doc_Aut']!='S'){
            $DocElect->setFileAutorized($xml.'_A.xml'); 
            $result=$DocElect->autorizarSri($d['Doc_Xml']);
            if($result['success']==true){
                $d['Doc_Aut']='S';
                $d['Selection']='N';                
                $d['Error']='Se Autorizó Correctamente!';
                $d['numeroAutorizacion']=$result['numeroAutorizacion'];
                $obBD_con1->operacionobBD(6, $d, $obBD_conexion,true); 
                //if(is_readable($xml.".xml")) unlink($xml.".xml");
                //if(is_readable($xml."_F.xml")) unlink($xml."_F.xml"); 
            }else{
                $d['Error']="<span>Error al autorizar el documento!<br/>[<i style='color:red;'>$result[message]</i>]".(!empty($result['informacionAdicional'])?"<br/>$result[informacionAdicional]</span>":'');                        
            }
        }
    } unset($d);
    $obBD_con1->echoJson($resp);
}
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
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Autorizar Documentos Electronicos - <?php echo ($config['Cof_Fac']*1==2)?'PRODUCCIÓN':'PRUEBAS'; ?></h3></div>        
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
                                        <select id="type" name="type" class="form-control input-sm" onchange="setDocs();" required="">
                                            <option value="TODOS">Todos</option>
                                            <option value="VENTAS">Ventas</option>
                                            <option value="NOTASC">Notas de Crédito</option>
                                            <option value="RETENC">Retenciones</option>
                                        </select>
                                    </div>                                  
                                </div>                                                                 
                            </div>
                        </fieldset>
                    </div> 
                    <div class="col-xs-6">  
                    <fieldset class="exa-fieldset ">                           
                        <legend class="Titulos2">Llave</legend>
                        <div class="form-horizontal normal">
                            <div class="form-group">
                                <label class="col-xs-1 control-label label-sm">Llave:</label>  
                                <div class="col-xs-6"> 
                                    <span class="form-control input-sm"><?php echo $llave['Lla_Rut']; ?></span>
                                </div>
                                <label class="col-xs-1 control-label label-sm">Cad.:</label>  
                                <div class="col-xs-4"> 
                                    <span class="form-control input-sm"><?php echo $llave['Lla_Cad']; ?></span>
                                </div>
                            </div>                                                                 
                        </div>
                    </fieldset>
                    </div> 
                    </form>
                    <div class="col-xs-12" id="gridContainer" style="padding-bottom: 8px; min-height: 300px;">
                        <table id="documentos"></table><div id="documentosPager"></div>
                    </div>    
                </div>
                <div class="row">   
                    <div class="col-xs-12">
                        <button id="btnGuardar" type="button" onclick="autorizarTodo()" class="btn btn-primary btn-save"><span class="glyphicon glyphicon-floppy-disk"></span> Autorizar Seleccionados</button>                        
                    </div>                    
                </div>   
            
            
        </div>
    </div>
    <script type="text/javascript">
        var docs;
        $(function() {
            docs=$('#documentos');
            docs.createGrid({
                caption:'Documentos Pendientes', height: 270,grouping:true,
                groupingView : { groupField : ['Tipo'], groupOrder: ['desc'] },
                colModel: [ 
                    { label: '<i class="glyphicon glyphicon-check"></i>', name: 'Selection', width: 20 ,align:"center", formatter:'checkboxExa', formatoptions:{nullifField:'Doc_Aut',nullifValue:'N'}, viewable:false },  
                    { label: 'Cód. Int.', name: 'Doc_Cod', width: 20 ,align:"center" },  
                    { label: 'id', name: 'id', width: 30 ,align:"center", key:true, hidden:true}, 
                    { label: 'Tipo', name: 'Tipo', width: 30 ,align:"center"}, 
                    { label: 'Fecha', name: 'Doc_Fec', width: 30 ,align:"center"}, 
                    { label: 'Numero', name: 'Doc_Num', width: 30 ,align:"center"}, 
                    { label: 'Archivo', name: 'Doc_Xml', width: 150, formatter:'docXml', title:false  }, 
                    { label: 'Firmado', name: 'Doc_Fir', width: 20,  align:"center", formatter:'truefalse', formatoptions:{yesMsg:'Esta Firmado',noMsg:'Sin Firmar'}, title:false}, 
                    { label: 'Enviado', name: 'Doc_Env', width: 20,  align:"center", formatter:'truefalse', formatoptions:{yesMsg:'Enviado Al SRI',noMsg:'No Enviado'}, title:false}, 
                    { label: 'Autorizado', name: 'Doc_Aut', width: 20,  align:"center", formatter:'truefalse', formatoptions:{yesMsg:'Autorizado',noMsg:'No Autorizado'}, title:false},                     
                    { label: 'Estado', name: 'ErrorMsg', width: 20,  align:"center", formatter:'title', formatoptions:{title:'Error'}, title:false  }, 
                    { label: 'Tabla', name: 'tabla', hidden:true}, 
                    { label: 'Campo Sri', name: 'campo1', hidden:true}, 
                    { label: 'Campo Aut', name: 'campo2', hidden:true}, 
                    { label: 'Campo Id', name: 'cod', hidden:true}, 
                    { label: '&nbsp;', name: 'act1', width: 20, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{action:autorizarUno, conditional:function(o){ return o.Doc_Aut!=='S'; } }, title:false }
                ] 
            },true,'#documentosPager').gridButtonsAdd([
                {caption:'Seleccionar Todo', buttonicon:'check', onClickButton:function(){ docs.selectAllByComlumn('Selection','S',true); }},
                {caption:'Quitar Seleccion', buttonicon:'unchecked', onClickButton:function(){ docs.selectAllByComlumn('Selection','N',true); }}
            ]);
            setDocs();
        });
        $.fn.fmatter.docXml=function(cv,opts,cObjt){ return '<div class="other-title" title="'+cv+'" data-originaldata="'+cv+'">&nbsp;<i class="fa fa-file-code-o blue" style="font-size:14px;"></i>&nbsp;&nbsp;&nbsp;'+cv+'.xml</div>'; };
        $.fn.fmatter.docXml.unformat=function(cv,opts,el){ return $(el).find('div').data('originaldata'); };
        function setDocs(){ $.getDataJson('',$('#formDocsSearch').getData('docsAjax'),function(r){ docs.setRowsByIndex(r['rows'],'id'); }); }
        function limpiarMsgs(){ $.each(docs.getSeletedByComlumn('Selection','S'), function(i,v){ docs.changeRow(v[docs[0].p.jsonReader.id],{Error:'',ErrorMsg:''}); });  }
        function autorizarTodo(){
           var data=docs.getSeletedByComlumn('Selection','S');
           if(data.length===0) return $.alert('Debe seleccionar al menos un documento!');
           confirmaAutorizacion(data);           
        }
        function autorizarUno(data){           
            docs.changeRow(data[docs[0].p.jsonReader.id],{Selection:'S'});
            confirmaAutorizacion([$.extend({Selection:'S'},data)]);
        }
        function confirmaAutorizacion(data){ $.createDialogConfirm('¿Está seguro que desea autorizar el(los) documento(s)?',data,autorizarSri); }
        function autorizarSri(data){
            $.each(data, function(i,v){ docs.changeRow(v[docs[0].p.jsonReader.id],{ErrorMsg:'<i class="fa fa-spin fa-pulse fa-spinner grey" style="font-size: 15px;">&nbsp;</i>'}); }); 
            $.saveDataJson('',{autorizaDocs:true,data:data},function(re){                
                $.each(re['data'], function(i,v){                    
                    v['ErrorMsg']='<i class="glyphicon glyphicon-'+((v['Doc_Aut']==='S')?'ok green':'remove red')+'" style="font-size: 14px;">&nbsp;</i>'; 
                    docs.changeRow(v[docs[0].p.jsonReader.id],v);
                });
                $.alert('Los Documentos se enviaron para su autorización!<br><br><u style="color:red">NOTA:</u><i> Revise el estado.</i>');
                return false;
            },limpiarMsgs,limpiarMsgs);
        }
    </script>
</BODY>
</HTML>