<?php	


/**
* @abstract Permite realizar guias de remision
* @author Erik Niebla
* @version 1.0
* Fecha de creaci�n  2015-07-22
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_guia_remi.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');


if(isset($doc_xml)){   
    header('Location: '."../FRONT/$Ses_Emp_Cod/{$doc_xml}_A.xml");
}
/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_G_Remi($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_G_Remi;

$hoy = date("Y-m-d");
$mes = date("m");
/*
if($_SESSION['Ses_Usu_Cod']=='1993'){
	require_once('../LOGICA/fac_log_electronica.php');
	$obBD_elect =  new Class_Log_Datos_Retencion_Elect;
	$mail=$obBD_elect->sendMailDoc(53430, 'ep_niebla@hotmail.com', 'Erik Niebla', $obBD_conexion);	
	var_dump($mail);
	exit();
}
*/
if(isset($searchDocument)){
    $obBD_con1->getPageGridJson(16, $_GET, $obBD_conexion);
}
if(isset($docDetalle)){
    $rows=$obBD_con1->getArrayConsulta(18, $Gui_Cod, $obBD_conexion);
    $obBD_con1->echoJson(array('success'=>true,'rows'=>$rows));
}
if(isset($ajaxSubgrid)){    
    $obBD_con1->echoJson($obBD_con1->getPageGridFormat(19, str_replace('_','*',$ajaxSubgrid), $obBD_conexion));
}
if(isset($anulador)){
    $obBD_con1->inicio_transaccion($obBD_conexion); 
            $obBD_con1->operacionobBD(20, array_merge($_POST,array('Gui_Est'=>'I')),$obBD_conexion,true); 
    
    $obBD_con1->echoJson(array('success'=>$obBD_con1->fin_transaccion_nomsn($obBD_conexion),'Gui_Cod'=>$Gui_Cod));
}
$rs_periodo = $obBD_con1->getArrayConsulta(17, $Ses_Emp_Cod, $obBD_conexion); 
?>

<!DOCTYPE html>
<HTML>
    <HEAD>		
        <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
        <TITLE><?Php echo "Guias Anular [EXA]"; ?></TITLE>
        <meta charset="utf-8">
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>
        <script type="text/javascript">var eliminar=true;</script>
        <script type="text/javascript" src="../VALIDACIONES/fac_val_guia_remi_2.0.js?x=1"></script>        
        <style>

        </style>
    </HEAD>
<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Consultar Guias de Remisión</h3></div>
        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body"> 
            <div id="documentoSearch">
                <form id="serachDocDorm" class="form-horizontal normal" action="javascript:$('#searchGrid').Search('#serachDocDorm','searchDocument');" >
                    <div class="row">
                        <div class="col-xs-6">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Búsqueda</legend>
                                <div class="form-group">
                                <label class="col-xs-2 control-label label-xs">Filtrar Por:</label>  
                                <div class="col-xs-10 radioset opt_search">
                                      <input id="radsc1" name="op_opciones" type="radio" value="p" checked="" onclick="setOpt(this.value); setfocus(this.form.search)" alt="" /><label for="radsc1">&nbsp;&nbsp;&nbsp;Transportista&nbsp;&nbsp;&nbsp;</label>
                                      <input id="radsc2" name="op_opciones" type="radio" value="c" onclick="setOpt(this.value); setfocus(this.form.search)" alt="" /><label for="radsc2">&nbsp;&nbsp;&nbsp;C&eacute;dula/RUC&nbsp;&nbsp;&nbsp;</label>
                                      <input id="radsc3" name="op_opciones" type="radio" value="d" onclick="setOpt(this.value); setfocus(this.form.search)" alt="" /><label for="radsc3">&nbsp;&nbsp;No. Documento&nbsp;&nbsp;</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-2 control-label">B&uacute;squeda:</label>  
                                <div class="col-xs-7" >
                                    <div class="input-group">                        
                                    <input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese búsqueda..." autofocus  class="form-control input-sm clearable submit"/>
                                    <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar Documento"  tabindex="-1"><span class="glyphicon glyphicon-search"></span> <span>Buscar</span></button></span>
                                  </div><!-- /input-group --> 
                                </div><input type="text" tabindex="-1" style="display:none;" />                    
                            </div>
                            </fieldset>
                        </div>
                        <div class="col-xs-6">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Filtros</legend>
                                
                                <div class="form-group">
                                    <label class="col-xs-4 control-label label-xs">Periodo:</label>  
                                    <div class="col-xs-3" >
                                        <select name="Pec_Cod" class="form-control input-xs search_pec getData ins" onchange="if(this.value==='') $('#Cmb_Mes').attr('disabled','disabled'); else $('#Cmb_Mes').removeAttr('disabled'); ">
                                           <?php foreach($rs_periodo as $row){ echo "<option value='$row[Pec_Cod]' data--Year='$row[Periodo]'>$row[Periodo]</option>"; } ?>
                                            <option value=""><< TODOS >></option>
                                        </select>
                                    </div> 
                                    <label class="col-xs-2 control-label label-xs">Mes:</label>  
                                    <div class="col-xs-3" >
                                        <select id="Cmb_Mes" name="Cmb_Mes" class="form-control input-xs search_pec">
                                           <option value=""><< TODOS >></option>
                                           <?Php  for ($i=1;$i<=12;$i++){ ?><option <?php if ($i == $mes){ echo "selected=''"; } ?> value="<?Php echo $i; ?>"><?php echo mes($i, 1); ?></option><?Php } ?>
                                        </select>
                                    </div>                                    
                                </div> 
                            </fieldset>
                        </div>
                    </div>    
                </form> 
                <div style="min-height: 270px;">
                    <table id="searchGrid"></table>
                    <table id="searchGridPager"></table>
                    <div class="Titulos2"><span id="plan-footer"><strong>Leyenda:</strong> <span class="glyphicon glyphicon-remove red"></span> Anulados/Inactivos | <span class="glyphicon glyphicon-stop green"></span> Guia Remision Autorizada </div>
                </div>
                <script>                    
                    function verDocument(doc){   
                        $('#btnAnula').data('Gui_Cod',doc['Gui_Cod']);
                        $('#formGuiaConsult').setData(doc);
                        
                        $.getDataJson('',{docDetalle:true,Gui_Cod:doc['Gui_Cod']},function(resp){
                             $('#documentoSearch').moveComp('#documentoMain').updateGridsSizes();
                             $("#guiasConsult").setRows(resp['rows']);
                               
                        });
                    }                    
                </script>
            </div>  
            <div id="documentoMain" style="visibility: hidden;">
                <div id="formGuiaConsult" class="row form-horizontal normal">
                    <div class="col-xs-6">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Guia Remisión</legend>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Documento:</label>  
                                <div class="col-xs-6" ><span class="form-control input-xs">GUIA DE REMISIÓN</span></div>                                                                                
                            </div> 
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Numero:</label>  
                                <div class="col-xs-4" ><span name="Secuencia" class="form-control input-xs"></span></div>                                                                                
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Fec. Emision:</label>  
                                <div class="col-xs-4" ><span name="Gui_Fec" class="form-control input-xs" style="text-align: center;"></span></div>                                                                                
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Autorizacion:</label>  
                                <div class="col-xs-7" ><span name="Aut_Sri" class="form-control input-xs"></span></div> 
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Observacion:</label>  
                                <div class="col-xs-9" ><span name="Gui_Obs" class="form-control input-lg textarea"></span></div>                    
                            </div>
                        </fielset>
                    </div>    
                    <div class="col-xs-6">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Datos Transportista</legend>
                            <div class="form-group trasportista">
                                <label class="col-xs-3 control-label label-xs">Cédula/RUC:</label>  
                                <div class="col-xs-6"><span name="Prs_Ced" class="form-control input-xs"></span></div>                                        
                            </div>
                            <div class="form-group trasportista">
                                <label class="col-xs-3 control-label label-xs">R.Social:</label>  
                                <div class="col-xs-9" ><span name="transportista" class="form-control input-xs"></span></div>                                                                                
                            </div>
                            <div class="form-group trasportista">
                                <label class="col-xs-3 control-label label-xs">Placa:</label>  
                                <div class="col-xs-4" ><span name="Gui_Pla" class="form-control input-xs"></span></div>                                                                                
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Fechas:</label>  
                                <div class="col-xs-9" >
                                    <div class="input-group input-group-xs">
                                        <span class="input-group-addon bold">Salida:</span>
                                        <span name="Gui_Fei" class="form-control span" style="text-align: center;" ></span>
                                        <span class="input-group-addon bold">Arrivo:</span>
                                        <span name="Gui_Fef" class="form-control span" style="text-align: center;" ></span>
                                    </div>
                                </div>    
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Dir. Salida:</label>  
                                <div class="col-xs-9" ><span name="Gui_Dor" class="form-control input-lg textarea"></span></div>                    
                            </div>
                        </fielset>
                    </div>
                </div>
                <table id="guiasConsult"></table>
                <table id="guiasConsultPager"></table>
                <div style="padding-top: 10px;">
                    <button type="button" class="btn btn-inverse btn-sm" onclick="$('#documentoMain').moveComp('#documentoSearch').updateGridsSizes();" ><i class="glyphicon glyphicon-arrow-left"></i> Volver</button>
                    <button id="btnAnula" type="button" class="btn btn-danger btn-sm" onclick="$.createDialogConfirm('¿Esta seguro de <u>ANULAR</u> la Guia de Remision?',$(this).data('Gui_Cod'),anulaGuia);" ><i class="glyphicon glyphicon-remove"></i> Anular Guia de Remisión</button>
                </div>
            </div>
        </div>
    </div>
   <script type="text/javascript">  
        
   </script>
</BODY>
</HTML>