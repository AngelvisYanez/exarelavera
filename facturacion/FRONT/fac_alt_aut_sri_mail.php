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
ini_set('max_execution_time', 600);
set_time_limit(0);
$send_mail=false;

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
    $ventas=$all||$type=='VENTAS'?$obBD_con1->getArrayConsulta(9, $Ses_Emp_Cod.'*Tic_Sri!=4 AND Tic_Sri!=5'.'*'.$search, $obBD_conexion):array();
    $notasc=$all||$type=='NOTASC'?$obBD_con1->getArrayConsulta(9, $Ses_Emp_Cod.'*Tic_Sri=4'.'*'.$search, $obBD_conexion):array();
    $retenc=$all||$type=='RETENC'?$obBD_con1->getArrayConsulta(10, $Ses_Emp_Cod.'*'.$search, $obBD_conexion):array();
    $guiasr=$all||$type=='GUIAS'?$obBD_con1->getArrayConsulta(11, $Ses_Emp_Cod.'*'.$search, $obBD_conexion):array();
    $notasd=$all||$type=='NOTASD'?$obBD_con1->getArrayConsulta(9, $Ses_Emp_Cod.'*Tic_Sri=5'.'*'.$search, $obBD_conexion):array();
   
    //$resp['rows']=array_merge($retenc, array_merge($ventas, array_merge($notasc, /*array_merge(*/$notasd/*, $guiasr)*/ ) ) );
     $resp['rows']=array_merge($retenc, array_merge($ventas, array_merge($notasc, array_merge($notasd, $guiasr))));
    foreach($resp['rows'] AS &$r){  
        $xml=$ruta_xmls.$r['Doc_Xml'];      
        if( $r['Doc_Aut']=='S' || is_readable($xml."_A.xml")){            
            $r['Doc_Aut']='S';
        }
        if($r['Doc_Aut']=='S'){
            if(is_readable($xml.".xml")) unlink($xml.".xml");
            if(is_readable($xml."_F.xml")) unlink($xml."_F.xml");
            //if(is_readable($xml."_A.xml")) unlink($xml."_A.xml");            
        }
        $r['Doc_Secuencia']=substr($r['Doc_Xml'], 24,3).'-'.substr($r['Doc_Xml'], 27,3).'-'.substr($r['Doc_Xml'], 30,9);
    } unset($r);
    $obBD_con1->echoJson($resp);
}
if(isset($sendMail)){
    $resp=array('success'=>false);
    //if($tabla=='guias_remis'){ $resp['message']='EXA No envia mail a <b class="green">GUIAS DE REMISION</b>!'; $obBD_con1->echoJson($resp); }
    if(!empty($Email)&&trim($Email)!=''&&trim($Email)!='-'&&trim($Email)!='0'){
        require_once('../LOGICA/fac_log_electronica.php');
        //$obBD_elect=($tabla=="retencion"?new Class_Log_Datos_Retencion_Elect:new Class_Log_Datos_Factura_Elect);        
        $obBD_elect=getClassElect($Type);
        $resp['success']=$obBD_elect->sendMailDoc($Doc_Cod,$Email,NULL,$obBD_conexion,true);
        if($resp['success']==false) $resp['message']="<span>Error al enviar el email!<br/>[<i style='color:red;'>No se pudo enviar el mail a $Email</i>]</span>";        
    }else $resp['message']="<span>Error al enviar el email!<br/>[<i style='color:red;'>No se registro ningun email para enviar el documento</i>]</span>";
    $obBD_con1->echoJson($resp);
}
$config=$obBD_con1->getRowConsulta(7, $Ses_Emp_Cod, $obBD_conexion);
?>
<!DOCTYPE html>
<HTML>
<HEAD>		
    <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
    <TITLE><?Php echo " Reenviar Mail [EXA]"; ?></TITLE>
    <meta charset="UTF-8">
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?> 
    <style>  
    </style>
</HEAD>
<BODY> 
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Reenviar Notificacion Por Mail Docs. Electronicos - <?php echo ($config['Cof_Fac']*1==2)?'PRODUCCIÓN':'PRUEBAS'; ?></h3></div>        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div class="row">   
                    <form id="formDocsSearch" action="javascript:setDocs();">
                    <div class="col-xs-6">  
                    <fieldset class="exa-fieldset ">                           
                        <legend class="Titulos2">Numero</legend>
                        <div class="form-horizontal normal">
                            <div class="form-group">
                                <label class="col-sm-2 control-label label-sm">Numero:</label>
                                <div class="col-sm-8">
                                    <div class="input-group">
                                        <input type="text" id="search" name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" class="form-control input-sm clearable" placeholder="Ingrese Numero de Documento" autofocus="">
                                        <span class="input-group-btn">
                                            <button class="btn btn-success btn-sm" type="button" title="Buscar Cliente" onclick="this.form.submit()"><span class="glyphicon glyphicon-search"></span> Buscar</button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                    </div> 
                    <div class="col-xs-6">  
                        <fieldset class="exa-fieldset ">                           
                            <legend class="Titulos2">Tipos</legend>
                            <div class="form-horizontal normal">
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-sm required">Tipo Documento:</label>  
                                    <div class="col-xs-9"> 
                                        <select id="type" name="type" class="form-control input-sm" onchange="setDocs();" required="">
                                            <option value="TODOS">Todos</option>
                                            <option value="VENTAS">Ventas</option>
                                            <option value="NOTASC">Notas de Crédito</option>
                                            <option value="RETENC">Retenciones</option>
                                            <option value="GUIAS">Guias de Remisión</option>
                                            <option value="NOTASD">Notas de Débito</option>
                                        </select>
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
        </div>
    </div>
    <div id="sendMail" title="Reenviar Mail" style="display:none;">
        <form class="form-horizontal normal" id="sendMailForm" action="javascript:reenviarMail()">
            <input name="Doc_Cod" data-name="Doc_Cod" type="text" class="hidden" />
            <input name="tabla" data-name="tabla" type="text" class="hidden" />   
            <input name="Type" data-name="Type" type="text" class="hidden" />               
            <fieldset class="exa-fieldset" >
                <legend class="Titulos2">Datos del Doc.</legend>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Tipo:</label>  
                    <div class="col-xs-5"><span class="form-control input-xs" data-name="Tipo"></span></div>
                </div>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Numero:</label>  
                    <div class="col-xs-5"><span class="form-control input-xs" data-name="Doc_Secuencia"></span></div>
                </div>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Mail:</label>  
                    <div class="col-xs-9">
                        <div class="input-group input-group-sm">  
                            <span class="input-group-addon alert-info">
                                <input id="chkMail" type="radio" name="mailType" data-name="mailType" value="default" class="radio-big datatrigger" onchange="setMailInput();" checked="">
                            </span>
                            <span class="form-control input-sm databind" name="Email" data-name="Email"></span>
                        </div>    
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Mail:</label>  
                    <div class="col-xs-9">
                        <div class="input-group input-group-sm">  
                            <span class="input-group-addon alert-info">
                                <input type="radio" name="mailType" data-name="mailType"  value="other" data-name="mailType" class="radio-big datatrigger" onchange="setMailInput();">
                            </span>
                            <input id="Email" type="email" multiple="" name="newEmail" data-name="newEmail" class="form-control input-sm" required="">                             
                        </div>                        
                    </div>
                </div>
                <div class="form-group center">
                    <div class="separator"></div>
                    <button type="submit" class="btn btn-sm btn-success"><i class="glyphicon glyphicon-envelope"></i> Reenviar Mail</button>
                </div>
            </fieldset>
        </form>
    </div>    
    <script type="text/javascript">
        var docs, conteoMsg=0, msg=0;
        $(function() {
            docs=$('#documentos');
            docs.createGrid({
                caption:'Documentos Autorizados', height: 270,grouping:true,
                groupingView : { groupField : ['Tipo'], groupOrder: ['desc'] },
                colModel: [ 
                    { label: 'Cód. Int.', name: 'Doc_Cod', width: 15 ,align:"center", hidden:true },  
                    { label: 'id', name: 'id', width: 30 ,align:"center", key:true, hidden:true}, 
                    { label: 'Tipo', name: 'Tipo', width: 30 ,align:"center"}, 
                    { label: 'Fecha', name: 'Doc_Fec', width: 30 ,align:"center"}, 
                    { label: 'Numero', name: 'Doc_Num', width: 30 ,align:"center"}, 
                    { label: 'Doc.', name: 'Doc_Secuencia', width: 50 ,align:"center"},                     
                    { label: 'Archivo', name: 'Doc_Xml', width: 150, formatter:'docXml', title:false  }, 
                    { label: 'Email', name: 'Email', width: 40 }, 
                    { label: 'Obs.', name: 'Info_Adi', width: 50 },
                    { label: 'Autorizado', name: 'Doc_Aut', width: 20,  align:"center", formatter:'truefalse', formatoptions:{yesMsg:'Autorizado',noMsg:'No Autorizado'}, title:false},                    
                    { label: '&nbsp;', name: 'act1', width: 30, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{action:'dialogMail', data:['Tipo','Type','tabla','Doc_Cod','Email','Doc_Secuencia'], conditional:function(o){ var m=o.Doc_Mail; return $.isText(m)&&m.trim()!==''&&m.trim()!=='-'&&m.trim()!=='0'; }, icon2:'envelope' }, title:false }
                ] 
            },true,'#documentosPager'); 
            $('#sendMail').createDialog({width:500,height:235,icon:'envelope'});
        });
        $.fn.fmatter.docXml=function(cv,opts,cObjt){ return '<div class="other-title" title="'+cv+'" data-originaldata="'+cv+'">&nbsp;<i class="fa fa-file-code-o blue" style="font-size:14px;"></i>&nbsp;&nbsp;&nbsp;'+cv+'.xml</div>'; };
        $.fn.fmatter.docXml.unformat=function(cv,opts,el){ return $(el).find('div').data('originaldata'); };
        function setMailInput(){ $('#Email').val('').prop('disabled',$('#chkMail').is(':checked')); }
        function setDocs(){ $.getDataJson('',$('#formDocsSearch').getData('docsAjax'),function(r){ docs.setRowsByIndex(r['rows'],'id'); }); }
        function reenviarMail(){
            var data=$('#sendMail').getData('sendMail');
            if(data['mailType']==="other") data['Email']=data['newEmail'];
            delete(data['newEmail']);delete(data['mailType']);
            //console.log(data);
            if(data['Email'].trim()==='') return $.alert('El Email no puede estar en blanco!');
            $.createDialogConfirm('¿Est&aacute; seguro que desea reenviar el mail?',data,function(d){
                $.saveDataJson('',d,function(r){
                    $('#sendMail').dialog('close');  
                    return $.alert("El Email a <b class='green'>"+d['Email']+"</b> se ha enviado correctamente!");
                });
            });
        }
        function dialogMail(data){
            data['mailType']='default';
            $('#sendMailForm').setData(data,'name');
            setMailInput();
            $('#sendMail').dialog('open');           
        }
    </script>
</BODY>
</HTML>