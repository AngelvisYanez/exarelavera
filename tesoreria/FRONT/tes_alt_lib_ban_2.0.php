<?php	
/**
* @abstract Permite realizar movimientos de inventario
* @author Erik Niebla
* @version 1.0
* Fecha de creaci?n  2015-07-22
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_cheque_2.0.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Che($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Che;

$hoy = date("Y-m-d");
$mes = date("m");

//if(isset($saveBene)){      
//        $responce['id']='';
//        $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
//        $obBD_con1->grabarv_registros(sentencias_che(363,$obBD_con1->parametros($apel.'*'.$nomb)),$obBD_conexion->conexion);
//        $ultimo = $obBD_con1->insercionid ($obBD_conexion->conexion);
//        $obBD_con1->grabarv_registros(sentencias_che(364,$obBD_con1->parametros($ultimo.'*'.$Ses_Emp_Cod)),$obBD_conexion->conexion);
//        $responce['id'] = $obBD_con1->insercionid ($obBD_conexion->conexion);
//        $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);       
//        if($obBD_con1->Error==0) $responce['success']=true; else $responce['success']=false; $responce['message']=$obBD_con1->MsgError;
//	echo json_encode($responce);exit();
//}

if(isset($valChe)){ 
    $conteo = $obBD_con1->getRowConsulta(405, $Ban_Cod.'*'.$valChe, $obBD_conexion);
    $contar = $obBD_con1->getRowConsulta(360, $Ban_Cod, $obBD_conexion);
    $contar['success']=true;
    if($conteo['conteo']==0)$contar['valid']=true; else $contar['valid']=false;
    echo json_encode($contar); exit();
}
if(isset($cheNum)){ 
    $contar = $obBD_con1->getRowConsulta(360, $Ban_Cod, $obBD_conexion);
    $contar['success']=true; 
    echo json_encode($contar); exit();
}
if(isset($term)){ $contar = $obBD_con1->getArrayConsulta(365, $Ses_Emp_Cod.'*'.$term, $obBD_conexion);  echo json_encode($contar);exit(); }
if(isset($cuenAjax)){ 
    $obBD_con1->getPageGridJson(352, $search.'*'.$Ses_Emp_Cod.'*'.(isset($Pec_Cod)?$Pec_Cod:'').'*'.$op_opciones, $obBD_conexion, $page, $rows);
}
if(isset($clieAjax)){ 
    $obBD_con1->getPageGridJson(359, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones, $obBD_conexion, $page, $rows);    
}
if(isset($provAjax)){ 
    $obBD_con1->getPageGridJson(351, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones, $obBD_conexion, $page, $rows);     
}
//if(isset($beneAjax)){ 
//    $obBD_con1->getPageGridJson(351, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones, $obBD_conexion, $page, $rows);        
//}
if(isset($getBancos)){ 
    $responce=array('success'=>true, 'options'=>"<option value=''>Seleccione...</option>");
    $bancos = $obBD_con1->getArrayConsulta(386, $Pec_Cod, $obBD_conexion);
    //var_dump($bancos);
    foreach ($bancos as $v) {
        $responce['options']=$responce['options']."<option value='$v[Ban_Cod]*$v[Pld_Cod]' data--pld_-cod='$v[Pld_Cod]' data--ban_-cod='$v[Ban_Cod]' data--ban_-cue='$v[Ban_Cue]' data--pld_-cdc='$v[Pld_Cdc]' data--pld_-des='".str_replace ("'",'',$v['Pld_Des'])."'>$v[Pld_Des] (Cta.#: $v[Ban_Cue])</option>";
    } 
    utf8_encode_deep($responce['options']); echo json_encode($responce); exit();
}
if(isset($save)){	
    $obBD_con1->validaCierrePeriodo('comprobantes','Com_Fec','Com_Cod',$Com_Fec,null,$obBD_conexion);
	if(!isset($Che_Fec)) $Che_Fec =$Com_Fec;
    if ($op=="I")$var="D";else $var='H'; 
    $rs_buscar =  $obBD_con1->getArrayConsulta(406, $Num_Doc."*".$Ses_Emp_Cod.'*'.$Pld_Cod.'*'.$var, $obBD_conexion);
    if((count($rs_buscar)==0 || $op=='E') || $Num_Doc==''){
        $responce['cheque']="";
        /* Inicio de la transaccion */
        $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
        /* Mes del comprobante */
        $var_mes = explode('-', $Com_Fec);
        $Com_Num = $obBD_con1->codigoComprAuto($Tia_Cod, $Pec_Cod, $var_mes[1], $obBD_conexion);		
        /* Insercion del Comprobante */
        $Num_Doc2=$Num_Doc;
        if ($op=="E"){$Num_Doc2='';}
        if ($op=="I") { $tabla="cliente"; $campo="Cli_Cod"; } if ($op=="E" || $op=="D") { $tabla="proveedore"; $campo="Prv_Cod"; }
        $obBD_con1->grabarv_registros(sentencias_che(356,$obBD_con1->parametros($Pec_Cod.'*'.$Codigo.'*'.$Com_Num.'*'.$Com_Fec.'*'.$Com_Con.'*'.$Tia_Cod.'*'.$Com_Val.'*'.$Com_Obs.'*'.(isset($Com_Tipo)?$Com_Tipo:'').'*'.$campo.'*'.$Num_Doc2.'*LB')),$obBD_conexion->conexion); // originalmente estaba '*"LB"'
        $ultimo = $obBD_con1->insercionid ($obBD_conexion->conexion);
        $ultimo2=0;
        /* Recorre el arreglo de los datos de las cuentas seleccionadas */
        foreach ($save as $row){                    
            $obBD_con1->grabarv_registros(sentencias_che(357,$obBD_con1->parametros($ultimo.'*'.$row['Det_Tip'].'*'.($row['Det_Tip']=='D'?$row['Debe']:$row['Haber']).'*'.$row['Pld_Des'].'*'.$row['Glosa'].'*'.$row['Pld_Cod'])),$obBD_conexion->conexion);
            if($row['Pld_Cod']==$Pld_Cod){ $ultimo2 = $obBD_con1->insercionid ($obBD_conexion->conexion); }
        }
        if ($op=="E"){
            if(isset($Num_Doc)&&$Num_Doc!=''&&$Num_Doc!='0'&&$Num_Doc!=0){
//                if(!isset($Bene_Id)||$Bene_Id=='')
//                {   $obBD_con1->grabarv_registros(sentencias_che(363,$obBD_con1->parametros($apellido.'*'.$nombre)),$obBD_conexion->conexion);
//                    $ultimo3 = $obBD_con1->insercionid ($obBD_conexion->conexion);
//                    $obBD_con1->grabarv_registros(sentencias_che(364,$obBD_con1->parametros($ultimo3.'*'.$Ses_Emp_Cod)),$obBD_conexion->conexion);
//                    $ultimo4 = $obBD_con1->insercionid ($obBD_conexion->conexion);
//                }else 
                    $ultimo4=$Bene_Id;
                $obBD_con1->grabarv_registros(sentencias_che(190,$obBD_con1->parametros($ultimo4.'*'.$Ban_Cod.'*'.$ultimo2.'*'.$Num_Doc.'**'.$Com_Val.'*'.$Com_Obs.'*'.$Che_Fec.'*1*'."$apellido $nombre")),$obBD_conexion->conexion);
                $obBD_con1->grabarv_registros(sentencias_che(385,$obBD_con1->parametros($ultimo)),$obBD_conexion->conexion);
                $responce['cheque']="?codigo2=1&asi=$ultimo2&ban=$Ban_Cod&pro=$ultimo4";
            }
        }
        /* Finaliza la transacci?n 		*/                
        $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
        if($obBD_con1->Error==0){
            $responce['link']="../../contabilidad/FRONT/con_pri_compr_2.1.php?codigo=$ultimo&tabla=$tabla&campo=$campo&tipo=$Tia_Cod&Pec_Cod=$Pec_Cod";
            $responce['success']=true;
        }else{ $responce['success']=false; $responce['error']=$obBD_con1->MsgError; }
        //$responce['message']=$obBD_con1->MsgError;
    }else{
        $responce['success']=false;
        $responce['message']="El Documento Bancario ya esta Registrado!";
    }
    echo json_encode($responce); exit();
}
?>
<!DOCTYPE html>
<HTML>
    <HEAD>		
        <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
        <TITLE><?Php echo "Mov.Banco Libro [EXA]"; ?></TITLE>
        <meta charset= "UTF-8"> 
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>  
        <style>#tabs.ui-widget-content{background:none !important;} .ui-tabs-panel{padding-bottom: 0 !important;}.ui-tabs-nav{padding-top: 0 !important;}
        </style>
    </HEAD>
<BODY>
 
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Registrar Libro Bancos</h3></div>        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            
            <div>
                <div class="row">  
                    <div class="col-sm-12 form-horizontal normal">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Periodo/Banco</legend>
                            <div class="form-group">
                                <label class="col-xs-1 control-label label-xs required">Seleccione Periodo:</label> 
                                <div class="col-xs-2">
                                    <select name="perio_cont" id="perio_cont" onchange="setPeriodo()"  class="form-control input-sm">
                                        <option value="">Seleccione...</option>
<?php $row_rs_periodos = $obBD_con1->getArrayConsulta(214/*339*/, $Ses_Emp_Cod, $obBD_conexion);
  if (count($row_rs_periodos) > 0){ $periodo = current($row_rs_periodos);
      foreach ($row_rs_periodos as $row){ 
          echo "<option value='$row[Pec_Cod]' data--pec_-cod='$row[Pec_Cod]' data--pla_-cod='$row[Pla_Cod]' data--pec_-fei='$row[Pec_Fei]' data--pec_-fef='$row[Pec_Fef]'  data-periodo='$row[Periodo]'>$row[Periodo]</option>";
      }    
  } ?>
                                    </select>   
                                </div>
                                <label class="col-xs-1 control-label label-xs required">Seleccione Banco:</label> 
                                <div class="col-xs-4">
                                    <select name="bancos" id="bancos" onchange="setBanco()"  class="form-control input-sm readOnly perio_cont" disabled="">
                                        <option value="">Seleccione...</option>
                                    </select>  
                                </div> 
                            </div>    
                        </fieldset>
                    </div>
                </div>   
                <script>   
                    var gridComp, valor=0, numChe=0, glosa="", tipo="Ingresos", dataFromRow=[];
                    function setPeriodo(){
                        $('#cuenDialog').getDialogGrid().clearGrid();
                        var perio_cont=getPeriodo();                      
                        $("input[name='Pec_Cod']").val(perio_cont["Pec_Cod"]);
                        $("input[name='periodo']").val(perio_cont["periodo"]);                          
                        $('#bancos').val('');
                        if(perio_cont['Pec_Cod']===null){                            
                            $('.perio_cont').attr('disabled','disabled');
                        }else{
                            $("input[name='Com_Fec']").dateLimits(perio_cont["Pec_Fei"],perio_cont["Pec_Fef"]);
                            $('.perio_cont').removeAttr('disabled');
                            $.get('',{getBancos:true, Pec_Cod:perio_cont["Pec_Cod"]},function(r){
                                if(r['success']===true) $('#bancos').html(r['options']).val('');
                                else{ $('#bancos').html('<option value="">Seleccione..</option>').val(''); $.alert('No se logro obtener los bancos para este periodo!'); }
                            },'json').fail(function(){ $('#perio_cont').val('').trigger('change'); $.alert(); });
                        } resetForm();
                    }
                    function setBanco(){ 
                        var banco=getBanco();
                        $("input[name='Pld_Cod']").val(banco["Pld_Cod"]);
                        $("input[name='Ban_Cue']").val(banco["Ban_Cue"]);
                        $("input[name='Ban_Cod']").val(banco["Ban_Cod"]);
                        gridComp.clearGrid().updateGridDiario();
                        if(banco["Pld_Cod"]!==null){ if(tipo==="Ingresos") addFilaCuenta(banco,"D"); else addFilaCuenta(banco,"H"); } //resetForm();
                    }
                    function getBanco(){ return $('#bancos').val()===''?{Pld_Cod:null}:$('#bancos option:selected').data(); }        
                    function getPeriodo(){ return $('#perio_cont').val()===''?{Pec_Cod:null}:$('#perio_cont option:selected').data(); }        
                    var chequeHtml='';
                </script> 
                <form id="periodoForm" class="hidden">
                    <input name="periodo" type="text">
                    <input type="text" name="Pec_Cod" />
                    <input type="text" name="Pld_Cod" />
                    <input type="text" name="Ban_Cod" />
                    <input type="text" name="Ban_Cue" />
                </form>                
                <div class="row">  
                    <div class="col-sm-12">
                        <div id="tabs" class="ui-tab-fix">
                            <ul>
                                <li><a href="#tabs-1">Ingresos</a></li>
                                <li><a href="#tabs-2">Egresos</a></li>
                                <li><a href="#tabs-3">Diario</a></li>
                                <!--<li><div><select><option value="">Ber</option><</select></div></li>-->
                            </ul>
                            <div class="panels-area form-horizontal normal ">
<!-- FORMULARIO COMPROBANTE DE INGRESO -->
    <div id="tabs-1">
        <div class="row">
        <fieldset class="exa-fieldset">
            <legend class="Titulos2">Comprobante de Ingreso</legend>	
            <form name="formComp" id="formIngreso" method="post" action="javascript:validaIngreso()" class="">                
                    <div class="col-xs-6">
                        <div class="form-group">
                            <label class="col-xs-4 control-label label-xs required">Tipo&nbsp;Comprobante:</label> 
                            <div class="col-xs-8">
                                <select class="form-control input-xs"  name="Tia_Cod"  class="isSelectMenu" required>
                                    <option value="">Seleccione...</option>
                                        <?Php $row_rs_tipo_asien1 = $obBD_con1->getArrayConsulta(373, "B*I", $obBD_conexion);
                                        foreach ($row_rs_tipo_asien1 as $row)  echo "<option value='$row[Tia_Cod]'>$row[Tia_Des]</option>"; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-4 control-label label-xs required">Cliente:</label> 
                            <div class="col-xs-8"> 
                                <input type="hidden" id="cod_cli" name="Codigo" value="" data-name='Cli_Cod' /> 
                                <div class="input-group input-group-xs">
                                    <input id="lblClie" name="clientes" data-name="clientes" onkeydown='if (event.keyCode === 13) buscaCliente();' onchange="if($('#lblClie').val()==='')$('#cod_cli').val('');" class="form-control varios clearable" placeholder="Ingrese Cliente"  />
                                    <span class="input-group-btn"><a onclick="$('#clieDialog').dialog('open')" title="B&uacute;squeda de Clientes" class="btn btn-success btn-mini"><i class="glyphicon glyphicon-check"></i></a></span>
                                </div><!-- /input-group -->  
                            </div>
                        </div> 
                        <div class="form-group">
                            <label class="col-xs-4 control-label label-xs">Concepto:</label> 
                            <div class="col-xs-8"><textarea class="form-control input-xs" name="Com_Con" id="ConIngreso" onchange=" updateGlosa()" cols="73" style="text-transform:uppercase" onkeypress="return  validar_injections(event)" required></textarea></div>
                        </div> 
                        <div class="form-group">
                            <label class="col-xs-4 control-label label-xs">Observaci&oacuten:</label> 
                            <div class="col-xs-8"><textarea class="form-control input-xs" name="Com_Obs" cols="73" style="text-transform:uppercase" onkeypress="return  validar_injections(event)"></textarea></div>
                        </div> 
                    </div>
                    <div class="col-xs-6">
                        <div class="form-group">
                            <label class="col-xs-2 control-label label-xs required">Fecha:</label> 
                            <div class="col-xs-5"><input name="Com_Fec" type="text" style="text-align: center" size="10" maxlength="10" class="form-control input-xs" required /></div>
                        </div>    
                        <div class="form-group">
                            <label class="col-xs-2 control-label label-xs required">No.&nbsp;Externo:</label> 
                            <div class="col-xs-5"><input class="form-control input-xs" style="text-align: center" name="Num_Doc" type="text" size="10" onkeypress="return  validar_numeric(event)" /></div>
                        </div> 
                        <div class="form-group">
                            <label class="col-xs-2 control-label label-xs required">Valor:</label> 
                            <div class="col-xs-5">
                                <div class="input-group input-group-xs"><span class="input-group-addon">$</span><input class="form-control input-xs" name="Com_Val" id="Com_Val_Ingre"  onchange=" updateValores()" type="text" size="10" maxlength="12" style="text-align:right" onkeypress="return  validar_decimal(event)" required placeholder="0.00" /></div></div>
                        </div>                        
                    </div>
            </form>
        </fieldset>
        </div>    
    </div>
<!-- FIN FORMULARIO COMPROBANTE DE INGRESO -->
<!-- FORMULARIO COMPROBANTE DE EGRESO -->
    <div id="tabs-2">
        <div class="row">
        <fieldset class="exa-fieldset">
            <legend class="Titulos2">Comprobante de Egreso</legend>	
            <form name="formComp" id="formComp" method="post" action="javascript:validaEgreso()">                
                    <div class="col-xs-6">
                        <div class="form-group">
                            <label class="col-xs-4 control-label label-xs required">Tipo&nbsp;Comprobante:</label> 
                            <div class="col-xs-8">
                                <select class="form-control input-xs"  name="Tia_Cod"  class="isSelectMenu" required>
                                    <option value="">Seleccione...</option>
                                        <?Php $row_rs_tipo_asien2 = $obBD_con1->getArrayConsulta(373, "*E", $obBD_conexion);
                                        foreach ($row_rs_tipo_asien2 as $row)  echo "<option value='$row[Tia_Cod]'>$row[Tia_Des]</option>"; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-4 control-label label-xs required">Proveedor:</label> 
                            <div class="col-xs-8">
                                <input type="hidden" id="cod_pvr" name="Codigo" value="" data-name="Prv_Cod" /> 
                                <div class="input-group input-group-xs">
                                    <input id="lblProvee" name="proveedor" data-name="proveedor" onkeydown='if (event.keyCode === 13) buscaProveeIngreso();' onchange="if($('#lblProvee').val()==='')$('#cod_pvr').val('');" class="form-control varios clearable" placeholder="Ingrese Proveedor"  />
                                    <span class="input-group-btn"><a onclick="$('#provDialog').dialog('open')" title="B&uacute;squeda de Proveedores" class="btn btn-success btn-mini"><i class="glyphicon glyphicon-check"></i></a></span>
                                </div><!-- /input-group -->  
                            </div>
                        </div> 
                        <div class="form-group">
                            <label class="col-xs-4 control-label label-xs">Concepto:</label> 
                            <div class="col-xs-8"><textarea class="form-control input-xs" name="Com_Con" id="ConEgreso" onchange=" updateGlosa()" cols="73" style="text-transform:uppercase" onkeypress="return  validar_injections(event)" required></textarea></div>
                        </div> 
                        <div class="form-group">
                            <label class="col-xs-4 control-label label-xs">Observaci&oacuten:</label>  
                            <div class="col-xs-8"><textarea class="form-control input-xs" name="Com_Obs" cols="73" style="text-transform:uppercase" onkeypress="return  validar_injections(event)"></textarea></div>
                        </div> 
                    </div>
                    <div class="col-xs-6">
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs required">Fecha&nbsp;Comp. :</label> 
                            <div class="col-xs-5"><input id="confec" name="Com_Fec" type="text" style="text-align: center" size="10" maxlength="10" onchange="$('#chefec').val($('#confec').val())" class="form-control input-xs" required /></div>
                            <div class="col-xs-4"><div class="checkbox check-big input-xs"><label><input onchange="setCheque();" id="es_cheque" checked type="checkbox" value="true" offval="false" />Cheque</label></div></div>    
                        </div>  
                        <div class="form-group es_cheque">
                            <label class="col-xs-3 control-label label-xs required">Fecha&nbsp;Cheq.:</label> 
                            <div class="col-xs-5" ><input id="chefec" name="Che_Fec" type="text" style="text-align: center" size="10" maxlength="10" class="form-control input-xs" required disabled="" /></div>
                            <div class="col-xs-4"><div class="checkbox check-big input-xs"><label><input onchange="setPosfecha()" id="postfecha" type="checkbox" value="true" offval="false" />Postfechado</label></div></div>    
                        </div> 
                        <div class="form-group es_cheque">
                            <label class="col-xs-3 control-label label-xs required">No.&nbsp;Cheque:</label> 
                            <div class="col-xs-5"><input  class="form-control input-xs" style="text-align: center" name="Num_Doc" id="NumChe" type="text" size="10" onkeypress="return  validar_numeric(event)" onChange="validaCheque();" required /></div>
                            <div class="col-xs-4 msgDiv"><img class="imgMsg" /><label class="lblMsg"></label></div>
                        </div> 
                        <div class="form-group es_cheque">
                            <label class="col-xs-3 control-label label-xs required">Beneficiario:</label> 
                            <div class="col-xs-9"> <input id="Bene_Id" name="Bene_Id" type="hidden" data-name="Prv_Cod" data-namebene="Prv_Cod" />
                                <div class="input-group input-group-xs"  id="Besnediv">
                                    <input id="apellido" name="apellido" data-name="Prs_Ape" data-namebene="Prs_Ape" class="form-control" type="text" size="32" placeholder="Apellidos" style="text-transform:uppercase"  /><span class="input-group-addon input-group-addon-sep"></span>
                                    <input  id="nombre" name="nombre" data-name="Prs_Nom" data-namebene="Prs_Nom" class="form-control" type="text" size="32" placeholder="Nombres" style="text-transform:uppercase"  />
                                    <!--<span class="input-group-btn"><button type="button" onclick="$('#beneDialog').dialog('open');" title="Seleccionar Beneficiario" class="btn btn-success btn-mini"><i class="glyphicon glyphicon-check"></i></button></span>-->
                                </div>
                            </div>
                        </div> 
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs required">Valor:</label> 
                            <div class="col-xs-4"><div class="input-group input-group-xs"><span class="input-group-addon">$</span><input  class="form-control input-xs" name="Com_Val" id="Com_Val_Egre" onchange=" updateValores()" type="text" size="10" maxlength="12" style="text-align:right" onkeypress="return  validar_decimal(event)" required placeholder="0.00" /></div></div>
                        </div>                         
                    </div>
            </form>
        </fieldset>
        </div>    
    </div>
<!-- FIN FORMULARIO COMPROBANTE DE EGRESO -->
<!-- FORMULARIO COMPROBANTE DE DIARIO -->
    <div id="tabs-3">
        <div class="row">
        <fieldset class="exa-fieldset">
            <legend class="Titulos2">Comprobante de Diario</legend>	
            <form name="formComp" id="formDiario" method="post" action="javascript:validaDiario()">                
                    <div class="col-xs-6">
                        <div class="form-group">
                            <label class="col-xs-4 control-label label-xs required">Tipo&nbsp;Comprobante:</label> 
                            <div class="col-xs-8">
                                <select class="form-control input-xs"  name="Tia_Cod"  class="isSelectMenu" required>
                                    <option value="">Seleccione...</option>
                                        <?Php $row_rs_tipo_asien3 = $obBD_con1->getArrayConsulta(373, "*D", $obBD_conexion);
                                        foreach ($row_rs_tipo_asien3 as $row)  echo "<option value='{$row['Tia_Cod']}'>" . mb_convert_encoding($row['Tia_Des'], 'UTF-8', 'ISO-8859-1') . "</option>";?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-4 control-label label-xs required">Proveedor:</label> 
                            <div class="col-xs-8">
                                <input type="hidden" id="cod_pvr2" name="Codigo" value="" data-name="Prv_Cod" /> 
                                <div class="input-group input-group-xs">
                                    <input id="lblProvee2" name="proveedor"  data-name="proveedor" onkeydown='if (event.keyCode === 13) buscaProveeDiario();' onchange="if($('#lblProvee2').val()==='')$('#cod_pvr2').val('');" class="form-control varios clearable" placeholder="Ingrese Proveedor"  />
                                    <span class="input-group-btn"><a onclick="$('#provDialog').dialog('open')" title="B&uacute;squeda de Proveedores" class="btn btn-success btn-mini"><i class="glyphicon glyphicon-check"></i></a></span>
                                </div><!-- /input-group -->  
                            </div>
                        </div> 
                        <div class="form-group">
                            <label class="col-xs-4 control-label label-xs">Concepto:</label> 
                            <div class="col-xs-8"><textarea class="form-control input-xs" name="Com_Con" id="ConDiario" onchange=" updateGlosa()" cols="73" style="text-transform:uppercase" onkeypress="return  validar_injections(event)" required></textarea></div>
                        </div> 
                        <div class="form-group">
                            <label class="col-xs-4 control-label label-xs">Observaci&oacuten:</label>  
                            <div class="col-xs-8"><textarea class="form-control input-xs" name="Com_Obs" cols="73" style="text-transform:uppercase" onkeypress="return  validar_injections(event)"></textarea></div>
                        </div> 
                    </div>
                    <div class="col-xs-6">
                        <div class="form-group">
                            <label class="col-xs-2 control-label label-xs required">Fecha:</label> 
                            <div class="col-xs-5"><input name="Com_Fec" type="text" style="text-align: center" size="10" maxlength="10" class="form-control input-xs" required /></div>
                        </div>    
                        <div class="form-group">
                            <label class="col-xs-2 control-label label-xs required">No.&nbsp;Externo:</label> 
                            <div class="col-xs-5"><input  class="form-control input-xs" style="text-align: center" name="Num_Doc" type="text" size="10" onkeypress="return  validar_numeric(event)" /></div>
                        </div> 
                        <div class="form-group">
                            <label class="col-xs-2 control-label label-xs required">Valor:</label> 
                            <div class="col-xs-5">
                                <div class="input-group input-group-xs"><span class="input-group-addon">$</span><input  class="form-control input-xs" name="Com_Val" id="Com_Val_Diario" onchange=" updateValores()" type="text" size="10" maxlength="12" style="text-align:right" onkeypress="return  validar_decimal(event)" required placeholder="0.00" /></div></div>
                        </div> 
                    </div>
            </form>
        </fieldset>
        </div>    
    </div>
<!-- FIN FORMULARIO COMPROBANTE DE DIARIO -->
                            </div>   
                        </div>    
                    </div>     
                </div> 
                
                <div class="row">   
                    <div class="col-sm-12">
                        <div id="compGrilla" style="padding-top: 6px;">   
                            <table id="comp"></table>
                            <div id="compPager"></div>
                        </div>
                    </div>
                    <div class="col-sm-12" style="padding-top: 6px;">                    
                        <button onclick="if($('bancos').val()===''){$.alert('Seleccione el Banco');}else{if(tipo==='Ingresos')$('#formIngreso').formSubmit();else if(tipo==='Egresos') $('#formComp').formSubmit(); else $('#formDiario').formSubmit(); }" title="Guardar Comprobante" type="button" class="btn btn-primary start" ><i class="glyphicon glyphicon-floppy-disk"></i><span> Guardar</span></button><span style="width: 15px;"></span>                        
                    </div>
                    <div class="col-sm-12 Titulos2"><hr><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco ( &nbsp;<span class="required"></span> ) son campos obligatorios.</div>
                    
                    <script type="text/javascript">
                        $(document).ready(function () {  
                            $('#Che_Fec').toggleClass('disabled').find('input').toggleAttr('disabled');
                            gridComp=$("#comp");
                            gridComp.createGrid({                               
                                colModel: [
                                    { label:'&nbsp;', name: 'act0', width: 30, align: 'center',
                                        formatter:function (cellvalue, options, rowObject) { return (rowObject.Index*1===1?'':  '<span class="btn btn-success btn-xs" title="Cambiar" onclick="$(\'#cuenDialog\').dialog(\'open\');$(\'#Index\').val(\''+rowObject.Index+'\');"><i class="glyphicon glyphicon-check"></i></span>');  }
                                    },
                                    { label: 'Cónt.', name: 'Index', key: true, width: 15, align:"center", hidden:true },
                                    { label: 'Cónt.', name: 'Pld_Cod', width: 20, align:"center", hidden:false },  
                                    { label: 'Tipo', name: 'Det_Tip', hidden:true },
                                    { label: 'Codigo', name: 'Pld_Cdc', width: 45 },                      
                                    { label: 'Cuenta', name: 'Pld_Des', width: 150  },
                                    { label: 'Glosa', name: 'Glosa', width: 150,editable:true },
                                    { label: 'Debe', name: 'Debe', width: 50, align: 'right', formatter:'currency', editable:true, 
                                         formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'}, summaryTpl: "Total: <b>{0}<b/>", summaryType: "sum",
                                         editoptions: { dataInit: function (element) { gridComp.createInputDiario(element,"D","Det_Tip");} }
                                    },                                         
                                    { label: 'Haber', name: 'Haber', width: 50,align: 'right', formatter:'currency', editable:true,
                                         formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "Total: <b>{0}</b>", summaryType: "sum",
                                         editoptions: { dataInit: function (element) { gridComp.createInputDiario(element,"H","Det_Tip");} }
                                    },
                                    { label:'&nbsp;', name: 'act1', width: 30, align: 'center',viewable: false, formatter:function (cv, opts, rObj) { return (rObj.Index*1===1?'':$.getGridButton(deleteFilaCuenta,rObj.Index,'Quitar','remove',null,'danger')); } }
                                ], height: 'auto', caption:"Datos del Asiento Contable", footerrow: true, userDataOnFooter: false // set a footer row
                            },true, "#compPager", {view:false} ).gridButtonAdd({
                                        caption:"Agregar Cuenta", buttonicon:"glyphicon glyphicon-plus", title:'Agregar Cuenta', id: "add_cuenta", onClickButton: function (){ $('#Index').val(''); $('#cuenDialog').dialog('open'); }                
                                    }); $("#add_cuenta").attr('disabled','disabled').addClass('perio_cont');            
                            $.clearFooterDiario("#comp",true);
                        });
                        function deleteFilaCuenta(Index){ gridComp.jqGrid('delRowData',Index); resizeGridComp(); gridComp.updateGridDiario(); }
                        function addFilaCuenta(cuenta,tipo){                         
                            var setter={Index:$('#Index').val(), Glosa:glosa, Det_Tip:tipo, Debe:tipo==='D'?valor:0, Haber:tipo==='H'?valor:0 };
                            if(setter['Index']===''){
                                var max=gridComp.jqGrid('getCol','Index',false,'max'), next=(isNaN(max)?1:max+1); 
                                setter['Index']=next;
                                gridComp.jqGrid("addRowData", setter['Index'], $.extend(cuenta,setter), "last");
                                resizeGridComp();
                            }else{
                                gridComp.jqGrid('saveRow', setter['Index'], false, 'clientArray');
                                var old_data=gridComp.jqGrid('getRowData', setter['Index']);
                                setter['Glosa']=old_data['Glosa'];
                                setter[tipo==='D'?'Debe':'Haber']=old_data[old_data['Det_Tip']==='D'?'Debe':'Haber'];
                                gridComp.jqGrid('setRowData', setter['Index'], $.extend(cuenta,setter));  
                                $('#cuenDialog').dialog('close'); 
                            }
                            gridComp.jqGrid('editRow', setter['Index']);
                            gridComp.updateGridDiario();  
                        }  
                        function resizeGridComp(){ var w=$('#compGrilla').width(); if(gridComp.width()>(w+2)||gridComp.width()<(w-2)) gridComp.jqGrid('resizeGrid'); }
                        function updateValores(){                        
                            if(tipo==="Ingresos") valor=$("#Com_Val_Ingre").val();
                            else if(tipo==="Egresos")valor=$("#Com_Val_Egre").val();
                            else valor=$("#Com_Val_Diario").val();
                            $("input[name='Com_Val']").val($.toFixed(valor));
                            $("input[name='Haber']").val($.toFixed(valor));
                            $("input[name='Debe']").val($.toFixed(valor));
                            gridComp.updateGridDiario();
                        }
                        function updateGlosa(){                        
                            if(tipo==="Ingresos")glosa=$("#ConIngreso").val();
                            else if(tipo==="Egresos")glosa=$("#ConEgreso").val();
                            else glosa=$("#ConDiario").val();
                            $("input[name='Glosa']").val(glosa);
                        }
                        function validaCheque(){  
                            var numAnt=$("#NumChe").val();
                             if(tipo==="Egresos"&&$("#bancos").val()!==''){
                                $.get('<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',{'Ban_Cod':getBanco()["Ban_Cod"],'valChe': numAnt}, function(response){
                                    if(response['success']===true){
                                        if(response['valid']===false){
                                            numChe=(response['Che_Num']*1)+1;
                                            $("#NumChe").val(numChe).alertMsg('El Cheque <b>No. '+numAnt+'</b> ya existe.');
                                        }else {$("#NumChe").alertMsg();}
                                    }else {numChe=0;$("#NumChe").val(numChe);$.alert("No se logro obtener n&uacutemero del cheque");}                                
                                },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!");});;        
                            }  
                        }
                        function resetForm(){
                            gridComp.clearGrid().updateGridDiario();
                            var dat_reset={};
                            $('#formIngreso').setData(dat_reset);
                            $('#formComp').setData(dat_reset);
                            $('#formDiario').setData(dat_reset);
                            $('#es_cheque').prop("checked",false).trigger('change');
                            setChequeNum();
                            valor=0;glosa="";
                            if($("#bancos").val()!==''){ if(tipo==="Ingresos") addFilaCuenta(getBanco(),"D"); else addFilaCuenta(getBanco(),"H"); } 
                            return false;
                        }
                        function validaGrid(Com_val){
                            var batch = gridComp.getGridBatch(), ban=true, msg='';                           
                            var     tot = $.round($(Com_val).val()),
                                    deb = $.round(gridComp.jqGrid("getCol", "Debe", false, "sum")),
                                    hab = $.round(gridComp.jqGrid("getCol", "Haber", false, "sum")); 
                            gridComp.startGridEdit();
                            if((deb===hab&&deb===0)||batch.length===0){ msg=("El comprobante no puede tener valor <i>cero</i>!"); ban=false; }
                            if(!(deb===hab&&tot===deb)){ msg=("Los Totales no Coinciden!"); ban=false; } 
                            $.each(batch,function (i,v){
                               if(('0'+v[v['Det_Tip']==='D'?'Debe':'Haber'])*1===0) { msg=("El valor de la cuenta <u>No. "+(i+1)+": "+v['Pld_Des']+"</u> no puede ser cero!");  ban=false; return ban; } 
                            });
                            if(ban===false){ $.alert(msg); return ban; }
                            return batch;
                        } 
                        function saveComprobante(data){ 
                            $.saveDataJson("<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>",data, function( r ) {                                
                                $('#impCompr').attr('href',r['link']);
                                if(typeof r['cheque']==='undefined' || r['cheque']===''){
                                    $('#successDialog').dialog("option", "height", 150);
                                    $('#printCheque').hide();
                                }else{
                                    $('#printCheque').show();
                                    $('#successDialog').dialog("option", "height", 250);
                                    var html=$('#modelo').html();
                                    html = html.replace(/{banco}/g, $('#bancos').find('option:selected').text());
                                    html = html.replace(/{link}/g, r['cheque'] );
                                    $('#printCheque').html(html);
                                }
                                $('#successDialog').dialog('open');
                                return resetForm();
                            });
                        }
                        function validaIngreso(){                            
                            if($('#cod_cli').val()===''){ $.alert("Seleccione El Cliente"); return; } 
                            var batch = validaGrid("#Com_Val_Ingre"); if(batch===false) return; 
                            var data=$.extend($('#formIngreso').serializeObject(),{op:'I', save:batch}, $('#periodoForm').serializeObject() ); 
                            $.createDialogConfirm('?st&aacute; seguro que desea guardar los datos?',data,saveComprobante);
                        }
                        function validaEgreso(){   
                            if($('#cod_pvr').val()===''){ $.alert("Seleccione El Proveedor"); return; }
                            var batch = validaGrid("#Com_Val_Egre"); if(batch===false) return;
                            var data=$.extend($('#formComp').serializeObject(),{op:'E', save:batch}, $('#periodoForm').serializeObject() );
                            $.createDialogConfirm('?st&aacute; seguro que desea guardar los datos?',data,saveComprobante);
                        }
                        function validaDiario(){
                            if($('#cod_pvr2').val()===''){ $.alert("Seleccione El Proveedor"); return; } 
                            var batch = validaGrid("#Com_Val_Diario"); if(batch===false) return; 
                            var data=$.extend($('#formDiario').serializeObject(),{op:'D', save:batch}, $('#periodoForm').serializeObject() );                            
                            $.createDialogConfirm('?st&aacute; seguro que desea guardar los datos?',data,saveComprobante);
                        }
                    </script>
                </div>   
            </div>
        </div>
    </div>
    <script type="text/javascript">        
        function setPosfecha(){ $('#chefec')[$('#postfecha').is(':checked')?'removeAttr':'attr']('disabled','disabled').val($('#confec').val()); }
        function setCheque(){        
            $('.es_cheque').find(':input')[$('#es_cheque').is(':checked')?'removeAttr':'attr']('disabled','disabled');
            $('#postfecha').prop('checked',false).trigger('change');
            setChequeNum();
        }
        function setChequeNum(){
            if(tipo==="Egresos"&&$("#bancos").val()!==''&&$('#es_cheque').is(':checked')){
                $.get('<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',{'Ban_Cod':getBanco()["Ban_Cod"],'cheNum':true}, function(response){
                    if(response['success']===true){
                        numChe=(response['Che_Num']*1)+1;
                        $("#NumChe").val(numChe).alertMsg();                                  
                    }else {numChe=0;$("#NumChe").val(numChe);$.alert("No se logro obtener n&uacutemero del cheque");}                                
                },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!");});;        
            }else{ $("#NumChe").val(0).parent().find('.lblMsg').html('').end().find('.imgMsg').removeAttr('src'); }    
        }
        function saveBene() {
             $.post( "<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>",{saveBene:true,apel:$('#beneApe').val(),nomb:$('#beneNom').val()}, function( response ) {
                if(response['success']===true){
                    $('#apellido').val($('#beneApe').val());$('#nombre').val($('#beneNom').val());$('#Bene_Id').val(response['id']);
                    $('#addBenef').dialog('close');$('#beneDialog').dialog('close');
                }else{$.alert(response['message']);}                                   
             },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!"); });
        }
        $( "#tabs" ).tabs({activate: function(event ,ui){
            tipo=ui.newTab[0].getElementsByTagName("a")[0].innerHTML;           
            $(ui.newTab[0].getElementsByTagName("a")[0].hash).find('div.row:first-child').effect("highlight",{},500);
            resetForm();  
        }});
        $(document).ready(function() {  
//            $.createDialog('#addBenef',150,550,null,null,'plus');
            $.createDialog('#successDialog',150,550,null,null,'ok');
            // DIALOG BUSCAR CUENTAS
            $.createSearchDialog('cuenDialog',[
                    { label: 'C&oacute;d.Int.', name: 'Pld_Cod', key: true, width: 15,align:"center",hidden:true },                                
                    { label: 'Codigo', name: 'Pld_Cdc', width: 45 },                      
                    { label: 'Cuenta', name: 'Pld_Des', width: 80, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},
                    { label: 'Grupo', name: 'Pld_Grupo', width: 110, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},
                    { label: 'Tipo', name: 'Pld_Tip', width: 30,align:"center" },
                    { label: 'Estado', name: 'Pld_Est', width: 30,align:"center"}, 
                        { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 30, align: 'center',viewable: false,
                            formatter:function (cv, opts, rObj) { return  $.getGridButton(SelectCta,{Pld_Cod:rObj.Pld_Cod, tipo:'D'},'Seleccione Cuentas'/*,'','D'*/) /*+ '&nbsp;' + $.getGridButton(SelectCta,{Pld_Cod:rObj.Pld_Cod, tipo:'H'},'Seleccione Cuentas','','H')*/; }
                        }
                ],null,null,null,null,{ title:'Cuenta', options:[{label:'&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;',value:'d'},{label:'&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;',value:'c'}] })
                .find('.form-group-options').append('<div class="col-md-4"> <label class="control-label label-xs">Plan de Cuentas:&nbsp;</label><input id="Index" name="Index" type="hidden" /><input name="Pec_Cod" type="hidden" /><input name="periodo" type="text" size="6" readonly style="text-align: center;display: inline-block;width: auto;" class="form-control input-xs" /></div>'); 
            // DIALOG BUSCAR PROVEEDOR    
            $.createSearchDialog('provDialog',[
                   { label: 'C&oacute;d.Int.', name: 'Prv_Cod', key: true, width: 20,align:"center",hidden:true,viewable: true },                                
                   { label: 'C&eacute;dula/R.U.C.', name: 'Prs_Ced', width: 50 },                      
                   { label: 'Proveedor', name: 'proveedor', width: 190, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},                   
                   { label: 'Apellidos', name: 'Prs_Ape',hidden:true},
                   { label: 'Nombres', name: 'Prs_Nom',hidden:true},
                   { label: 'Direcci&oacute;n', name: 'Prs_Dir',hidden:true,viewable: true },                      
                   { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center',viewable: false, formatter:function (cv, opts, rObj) { return $.getGridButton(selectProv,rObj,'Seleccione Proveedor'); } }
               ],null,null,null,null,{title:'Proveedor'});  
            // DIALOG BUSCAR BENEFICIARIO    
//            $.createSearchDialog('beneDialog',[
//                    { label: 'C&oacute;d.Int.', name: 'Prv_Cod', key: true, width: 20,align:"center",hidden:true,viewable: true },                                
//                    { label: 'C&eacute;dula/R.U.C.', name: 'Prs_Ced', width: 50 },                      
//                    { label: 'Beneficiario', name: 'proveedor', width: 190, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},                   
//                    { label: 'Apellidos', name: 'Prs_Ape',hidden:true},
//                    { label: 'Nombres', name: 'Prs_Nom',hidden:true},                                        
//                    { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center',viewable: false, formatter:function (cv, opts, rObj) { return $.getGridButton(selectBene,{Prv_Cod:rObj.Prv_Cod,Prs_Nom:rObj.Prs_Nom,Prs_Ape:rObj.Prs_Ape},'Seleccione Beneficiario'); } }
//                ],null,null,null,null,{title:'Beneficiario'}); 
//            $('#beneForm .form-group-search-btn').append('<a onclick="$(\'#addBenefForm\').setData({}); $(\'#addBenef\').dialog(\'open\');" title="Registrar Beneficiario" class="btn btn-primary btn-sm"><i class="glyphicon glyphicon-plus"></i></a>');
            // DIALOG BUSCAR CLIENTE    
            $.createSearchDialog('clieDialog',[
                    { label: 'C&oacute;d.Int.', name: 'Cli_Cod', key: true, width: 20,align:"center",hidden:true,viewable: true },                                
                    { label: 'C&eacute;dula/R.U.C.', name: 'Prs_Ced', width: 50 },                      
                    { label: 'Cliente', name: 'clientes', width: 190, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},                   
                    { label: 'Direcci&oacute;n', name: 'Prs_Dir',hidden:true,viewable: true },                      
                    { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center',viewable: false, formatter:function (cv, opts, rObj) {  return $.getGridButton(selectClie,{Cli_Cod:rObj.Cli_Cod,clientes:rObj.clientes},'Seleccione Cliente'); } }
                ],null,null,null,null,{title:'Cliente'}); 
            $("input[name='Com_Fec']").createDatePickers({checkAvailability:true});
            $.createDatePickers("input[name='Che_Fec']");    
        });  
        function SelectCta(cta){ if(!gridComp.existsId(cta['Pld_Cod'])) addFilaCuenta($.getDialogGrid("#cuenDialog").jqGrid('getRowData', cta['Pld_Cod']),tipo==='Ingresos'?'H':'D'/* cta['tipo'] */);  } 
        function selectProv(prov){ if(tipo==="Egresos"){ $('#formComp').setData(prov,null,'name'); }else if(tipo==="Diario") $('#formDiario').setData(prov,null,'name'); $("#provDialog").dialog("close"); }
//        function selectBene(bene){ $('#formComp').setData(bene,null,'namebene'); $( "#beneDialog" ).dialog("close"); }
        function selectClie(clie){ $('#formIngreso').setData(clie,null,'name'); $("#clieDialog").dialog("close"); }   
        function buscaCliente(){ $.SearchOrDialogArray("#clieDialog",selectClie, {'search':$('#lblClie').val(),'op_opciones':'c'}); selectClie({}); }  
        function buscaProveeIngreso(){ $.SearchOrDialogArray("#provDialog",selectProv,{'search':$('#lblProvee').val(),'op_opciones':'c'}); selectProv({}); } 
        function buscaProveeDiario(){ $.SearchOrDialogArray("#provDialog",selectProv,{'search':$('#lblProvee2').val(),'op_opciones':'c'}); selectProv({}); } 
        function resetForm2(option){ gridComp.clearGrid().updateGridDiario(); }
    </script>
    <!--INICIO DEL DIALOGO BUSCAR CUENTA--> 
    <div id="cuenDialog" title="B&uacute;squeda de Cuentas"></div>
    <!--INICIO DEL DIALOGO BUSCAR PROVEEDOR--> 
    <div id="provDialog"  title="B&uacute;squeda de Proveedores"></div>
    <!--INICIO DEL DIALOGO BUSCAR BEnfICIARIO--> 
    <!--<div id="beneDialog"  title="B&uacute;squeda de Beneficiarios"></div>-->
    <!--INICIO DEL DIALOGO BUSCAR CLIENTE--> 
    <div id="clieDialog"  title="B&uacute;squeda de Clientes"></div>
    <!-- CREA BEdefICIARIO DIALOG -->
<!--    <div id="addBenef"  title="Crear Beneficiario">
        <fieldset class="exa-fieldset">
            <legend class="Titulos2">Datos Beneficiario</legend>
            <form id="addBenefForm" class="form-horizontal normal" action="javascript:saveBene();"> 
                <div class="input-group input-group-sm" >
                    <input id="beneApe" name="apellido" class="form-control" type="text" size="32" placeholder="Apellidos" style="text-transform:uppercase" required autofocus/><span class="input-group-addon input-group-addon-sep"></span>
                    <input id="beneNom" name="nombre" class="form-control" type="text" size="32" placeholder="Nombres" style="text-transform:uppercase" />                    
                </div>            
                <div class="row" style="text-align: center; padding-top: 10px; padding-bottom: 5px;">
                    <button type="submit" class="btn btn-success" title="Guardar Proveedor" ><i class="glyphicon glyphicon-floppy-disk"></i><span> Guardar</span></button><span>&nbsp;</span>
                    <button type="button" onclick="$('#addBenef').dialog('close');" class="btn btn-inverse" title="Cancelar" ><i class="glyphicon glyphicon-remove"></i><span> Cancelar</span></button>            
                </div>
            </form> 
        </fieldset>
    </div> -->
    <!--INICIO DEL DIALOGO IMPRIMIR --> 
    <div id="successDialog"  title="Mensaje del Sistema">  
        <center><h4>El Comprobante se ha registrado con Exito!</h4></center>  
        <center id="printCheque"></center>
        <center> 
            <button type="button" onclick="$('#successDialog').dialog('close');" class="btn btn-inverse fileinput-button" style="display: inline;" ><i class="glyphicon glyphicon-remove"></i><span> Cerrar</span></button>            
            <a id="impCompr" target="_blank" href=""  style="display: inline;" title="Imprimir Comprobante"><span  class="btn btn-primary start"> <i class="glyphicon glyphicon-print"></i> <span> Imprimir</span></span> </a>               
        </center>        
    </div>    
    <?php $ruta='./'.(file_exists ('cheques/'.$Ses_Emp_Cod)?"cheques/$Ses_Emp_Cod/":''); ?>
    <div id="modelo" style="display:none;">
        <table style="margin-bottom:10px;" cellpadding="1" border="1">
            <tr><td align="center" class="ui-widget-header" colspan="6"><label autofocus> Imprimir Cheque </label></td></tr>
            <tr><td align="center" class="ui-widget-content" colspan="6"><b>&nbsp; {banco} &nbsp;</b></td></tr>
            <tr>
                <td align="center"><a href="<?php echo $ruta; ?>tes_pri_cheque_mac_1.0.php{link}" target="_blank" title="Banco de Machala"><img src="../../mascaras/model1/imagenes/32x32/banco_machala.jpg" width="22" height="35"/></a></td>
                <td align="center"><a href="<?php echo $ruta; ?>tes_pri_cheque_pac_1.0.php{link}" target="_blank" title="Banco del Pacifico"><img src="../../mascaras/model1/imagenes/32x32/banco_pacifico.jpg" width="24" height="23"/></a></td>
                <td align="center"><a href="<?php echo $ruta; ?>tes_pri_cheque_rum_1.0.php{link}" target="_blank" title="Banco del Rumiñahui"><img src="../../mascaras/model1/imagenes/32x32/banco_ruminahui.jpg" width="30" height="15"/></a></td>
                <td align="center"><a href="<?php echo $ruta; ?>tes_pri_cheque_gua_1.0.php{link}" target="_blank" title="Banco del Guayaquil"><img src="../../mascaras/model1/imagenes/32x32/banco_guayaquil.JPG" width="36" height="18"/></a></td>
                <td align="center"><a href="<?php echo $ruta; ?>tes_pri_cheque_pch_1.0.php{link}" target="_blank" title="Banco del Pichincha"><img src="../../mascaras/model1/imagenes/32x32/banco_pichincha.JPG" width="36" height="30"/></a></td>
                <td align="center"><a href="<?php echo $ruta; ?>tes_pri_cheque_int_1.0.php{link}" target="_blank" title="Banco Internacional"><img src="../../mascaras/model1/imagenes/32x32/ban_int.jpg" width="32" height="32"/></a></td>
                <!-- <td align="center"><a href="<?php echo $ruta; ?>tes_pri_cheque_int_1.0.php{link}" target="_blank" title="Banco de Loja"><img src="../../mascaras/model1/imagenes/32x32/banco_loja.jpg" width="32" height="32"/></a></td> -->
                <td align="center"><a href="<?php echo $ruta; ?>cheques/1/tes_pri_cheque_loj_1.0.php{link}" target="_blank" title="Banco de Loja"><img src="../../mascaras/model1/imagenes/32x32/banco_loja.jpg" width="32" height="32"/></a></td>
            </tr>
        </table>
    </div> 
</BODY>
</HTML>