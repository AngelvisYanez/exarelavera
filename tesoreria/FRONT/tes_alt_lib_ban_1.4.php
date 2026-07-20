<?php	
/**
* @abstract Permite registrar los cheques 
* @author Erik Niebla
* @version 1.0
* Fecha de creaci�n  2015-07-08
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_cheque.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Che($Ses_Dat_Dis);
/** 
* Cracion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Che;

$hoy = date("Y-m-d");
$mes = date("m");

if(isset($saveBene)){      
        $responce['id']='';
        $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
        $obBD_con1->grabarv_registros(sentencias_che(363,$obBD_con1->parametros($apel.'*'.$nomb)),$obBD_conexion->conexion);
        $ultimo = $obBD_con1->insercionid ($obBD_conexion->conexion);
        $obBD_con1->grabarv_registros(sentencias_che(364,$obBD_con1->parametros($ultimo.'*'.$Ses_Emp_Cod)),$obBD_conexion->conexion);
        $responce['id'] = $obBD_con1->insercionid ($obBD_conexion->conexion);
        $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);       
        if($obBD_con1->Error==0) $responce['success']=true; else $responce['success']=false;$responce['message']=$obBD_con1->MsgError;
	echo json_encode($responce);exit();
}

if(isset($valChe)){ 
    $conteo = $obBD_con1->getRowConsulta(368, $Ban_Cod.'*'.$valChe, $obBD_conexion);
    $contar = $obBD_con1->getRowConsulta(360, $Ban_Cod, $obBD_conexion);
    $contar['success']=true;
    if($conteo['conteo']==0)$contar['valid']=true; else $contar['valid']=false;
    echo json_encode($contar);exit();
}
if(isset($cheNum)){ 
    $contar = $obBD_con1->getRowConsulta(360, $Ban_Cod, $obBD_conexion);
    $contar['success']=true;
    echo json_encode($contar);exit();
}
if(isset($term)){ 
    $contar = $obBD_con1->getArrayConsulta(365, $Ses_Emp_Cod.'*'.$term, $obBD_conexion);    
    echo json_encode($contar);exit();
}
if(isset($cuenAjax)){ 
    $contar = $obBD_con1->getRowConsulta(352, $search.'*'.$Ses_Emp_Cod.'*'.$Pec_Cod.'*'.$op_opciones.'*', $obBD_conexion);	      
    $pagination= pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    $responce['rows']=  $obBD_con1->getArrayConsulta(352, $search.'*'.$Ses_Emp_Cod.'*'.$Pec_Cod.'*'.$op_opciones.'*'.$pagination['limits'], $obBD_conexion);	    
    utf8_encode_deep($responce['rows']);echo json_encode($responce);exit();
}
if(isset($clieAjax)){ 
    $contar = $obBD_con1->getRowConsulta(359, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*', $obBD_conexion);	      
    $pagination= pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    $responce['rows'] =  $obBD_con1->getArrayConsulta(359, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*'.$pagination['limits'], $obBD_conexion);	    
    utf8_encode_deep($responce['rows']);echo json_encode($responce);exit();
}

if(isset($provAjax)){ 
    $contar = $obBD_con1->getRowConsulta(351, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*', $obBD_conexion);	      
    $pagination= pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    $responce['rows'] =  $obBD_con1->getArrayConsulta(351, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*'.$pagination['limits'], $obBD_conexion);	    
    utf8_encode_deep($responce['rows']);echo json_encode($responce);exit();
}
if(isset($beneAjax)){ 
    $contar = $obBD_con1->getRowConsulta(351, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*', $obBD_conexion);	      
    $pagination= pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    $responce['rows'] =  $obBD_con1->getArrayConsulta(351, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*'.$pagination['limits'], $obBD_conexion);	    
    utf8_encode_deep($responce['rows']);echo json_encode($responce);exit();
}
if(isset($save)){
	
    if(!isset($Che_Fec)) $Che_Fec =$Com_Fec;
    if ($op=="I")$var="D";else $var='H'; 
    $rs_buscar =  $obBD_con1->getArrayConsulta(361, $Num_Doc."*".$Ses_Emp_Cod.'*'.$Pld_Cod.'*'.$var, $obBD_conexion);
    if((count($rs_buscar)==0 || $op=='E') || $Num_Doc==''){
		$responce['cheque']="";
        /**
		* Inicio de la transaccion
		*/
		$obBD_con1->inicio_transaccion($obBD_conexion->conexion);
		/** 
		* Mes del comprobante 
        	*/
		$var_mes = explode('-', $Com_Fec);
		$Com_Num = $obBD_con1->codigoComprAuto($Tia_Cod, $Pec_Cod, $var_mes[1], $obBD_conexion);		
		/** 
		* Inserci�n del Comprobante 
		*/
                $Num_Doc2=$Num_Doc;
                if ($op=="E"){$Num_Doc2='';}
		if ($op=="I") { $tabla="cliente"; $campo="Cli_Cod"; } if ($op=="E" || $op=="D") { $tabla="proveedore"; $campo="Prv_Cod"; }
		$obBD_con1->grabarv_registros(sentencias_che(356,$obBD_con1->parametros($Pec_Cod.'*'.$Codigo.'*'.$Com_Num.'*'.$Com_Fec.'*'.$Com_Con.'*'.$Tia_Cod.'*'.$Com_Val.'*'.$Com_Obs.'*'.$Com_Tipo.'*'.$campo.'*'.$Num_Doc2)),$obBD_conexion->conexion);
		$ultimo = $obBD_con1->insercionid ($obBD_conexion->conexion);
                $ultimo2=0;
		/** 
		* Recorre el arreglo de los datos de las cuentas seleccionadas 
		*/
                foreach ($save as $row)
                {
                    if($row['Det_Tip']=='D') {$valor=$row['Debe'];}
                    else {$valor=$row['Haber'];}
                    $obBD_con1->grabarv_registros(sentencias_che(357,$obBD_con1->parametros($ultimo.'*'.$row['Det_Tip'].'*'.$valor.'*'.$row['Pld_Des'].'*'.$row['Glosa'].'*'.
											$row['Pld_Cod'])),$obBD_conexion->conexion);
                    if($row['Pld_Cod']==$Pld_Cod){
                        $ultimo2 = $obBD_con1->insercionid ($obBD_conexion->conexion);
                    }
                }
                if ($op=="E"){
                    if(isset($Num_Doc)&&$Num_Doc!=''&&$Num_Doc!='0'&&$Num_Doc!=0){
                        if(!isset($Bene_Id)||$Bene_Id=='')
                        {   $obBD_con1->grabarv_registros(sentencias_che(363,$obBD_con1->parametros($apellido.'*'.$nombre)),$obBD_conexion->conexion);
                            $ultimo3 = $obBD_con1->insercionid ($obBD_conexion->conexion);
                            $obBD_con1->grabarv_registros(sentencias_che(364,$obBD_con1->parametros($ultimo3.'*'.$Ses_Emp_Cod)),$obBD_conexion->conexion);
                            $ultimo4 = $obBD_con1->insercionid ($obBD_conexion->conexion);
                        }else $ultimo4=$Bene_Id;
                        $obBD_con1->grabarv_registros(sentencias_che(190,$obBD_con1->parametros($ultimo4.'*'.$Ban_Cod.'*'.$ultimo2.'*'.$Num_Doc.'* *'.$Com_Val.'*'.$Com_Obs.'*'.$Che_Fec.'*1')),$obBD_conexion->conexion);
						$obBD_con1->grabarv_registros(sentencias_che(385,$obBD_con1->parametros($ultimo)),$obBD_conexion->conexion);
                        $responce['cheque']="?codigo2=1&asi=$ultimo2&ban=$Ban_Cod&pro=$ultimo4";
                    }
                }
		/**
		* Finaliza la transacci�n
		*/
                $responce['link']="../../contabilidad/FRONT/con_pri_compr_1.1.php?codigo=$ultimo&tabla=$tabla&campo=$campo&tipo=$Tia_Cod&Pec_Cod=$Pec_Cod";
		$obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
		if($obBD_con1->Error==0)
		$responce['success']=true;
		else
		$responce['success']=false;
                $responce['message']=$obBD_con1->MsgError;
         
     }else{
                $responce['success']=false;
                $responce['message']="El Documento Bancario ya esta Registrado!";
     }
                echo json_encode($responce);
	exit();
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/basic.php"); ?>
                <?Php require_once("../../mascaras/model1/estilos/jqgrid.php")?> 
                <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
                <meta http-equiv="X-UA-Compatible" content="IE=edge" />
                <style>                   
                    
                </style>
	</HEAD>
<BODY>
<div id="set1">
    <table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
	<tr class="BarraTitulo">
	  <td height="10">&raquo; Registrar Libro Bancos</td>
        </tr>
      <tr>
      <td height="389" align="left" valign="top">
          
<!-- INICIO FORMULARIO BUSQUEDA -->
        <form enctype="multipart/form-data" action="<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" method="post" name= "form1" id= "form1">
            <table width="100%" border="0" cellpadding="0" cellspacing="0">
                <tr>  
                     <td width="350">
            <FIELDSET style="height: 50px;">
		<LEGEND>
                    <label class="Titulos2">Seleccione Periodo Contable</label>
		</LEGEND>
		<table width="100%" height="36" border="0" cellpadding="0" cellspacing="0" >
			<tr>
			  <td width="150" class="BarraBusqueda" style="border-right: 0px;"><div align="right" >Periodo Contable:</div></td>
			  <td class="BarraBusqueda" style="border-left: 0px;padding-left: 15px;">
                              <select name="periodos" id="periodos" onchange="setPeriodo()" >
                                  <option value="">Seleccione...</option>
<?php
    $row_rs_periodos = $obBD_con1->getArrayConsulta(339, $Ses_Emp_Cod, $obBD_conexion);
    if (count($row_rs_periodos) > 0) 
    { 
        $periodo = current($row_rs_periodos);
        foreach ($row_rs_periodos as $row){  
?>
                                  <option value="<?php echo $row['Pld_Cod'].'*'.$row['Pec_Cod']; ?>"><?php echo $row['Pld_Des']." (Cta.#: ".$row['Ban_Cue'].") - A&ntilde;o: ".$row['Periodo']." "; ?></option>
<?php
        }?>
<?php        
    }
?>
                              </select>
<script>   
    var periodos=<?php if (count($row_rs_periodos) > 0) echo json_encode($row_rs_periodos); else echo 'new Array()';?>;    
    var valor=0, numChe=0, glosa="";    
    var tipo="Ingresos";      
    var dataFromRow=[];
    function setPeriodo(){
        if(periodos.length>0&&$("#periodos").val()!==''){
            $("input[name='Pld_Cod']").val(getPeriodo()["Pld_Cod"]);
            $("input[name='Ban_Cue']").val(getPeriodo()["Ban_Cue"]);
            $("input[name='Pec_Cod']").val(getPeriodo()["Pec_Cod"]);
            $("input[name='periodo']").val(getPeriodo()["Periodo"]);  
            $("input[name='Ban_Cod']").val(getPeriodo()["Ban_Cod"]);
            $("input[name='Com_Fec']").dateLimits(getPeriodo()["Pec_Fei"],getPeriodo()["Pec_Fef"]);
            resetForm();
        }
    }
    function getPeriodo(){
        var aux;
        if(periodos.length===0&&$("#periodos").val()===''){return new Array();}
        aux=$("#periodos").val().split("*");;
        for(var i=0;i<periodos.length;i++)
            if(periodos[i]['Pld_Cod']+''===aux[0]&&periodos[i]['Pec_Cod']+''===aux[1])
                return periodos[i];
         
    }        
    var chequeHtml='';
</script>  
                          </td>			 
			</tr>
                 </table>
            </FIELDSET>
                    </td>                     
                </tr>
            </table>
        </form>
<!-- FIN FORMULARIO BUSQUEDA -->          
          
<!-- INICIO PANEL COMPROBANTE -->  

<div id="tabs">
        <ul>
            <li><a href="#tabs-1">Ingresos</a></li>
            <li><a href="#tabs-2">Egresos</a></li>
            <li><a href="#tabs-3">Diario</a></li>
        </ul>
        <div class="panels-area">
<!-- FORMULARIO COMPROBANTE DE INGRESO -->
            <div id="tabs-1">
                 <fieldset>
                    <legend>
                        <label class="Titulos2">Comprobante de Ingreso</label>
                    </legend>	
                    <form name="formComp" id="formIngreso" method="post" action="javascript:$.createDialogConfirm('¿Est&aacute; seguro que desea guardar los datos?',null,saveIngreso)">
                        <table  width="100%" border="0" cellpadding="0" cellspacing="0" style="table-layout:fixed;min-width: 450px;">
                        <tr><td valign="top">
                            <div class="row">
                                <div class="segmento required">Tipo Comprobante:</div>
                                <div  class="datasegmento">
                                    <select class="text ui-corner-all"  name="Tia_Cod"  class="isSelectMenu" required>
                                        <option value="">Seleccione...</option>
                                            <?Php 
                                            $row_rs_tipo_asien1 = $obBD_con1->getArrayConsulta(373, "B*I", $obBD_conexion);
                                            foreach ($row_rs_tipo_asien1 as $row) 
                                            { ?>
                                        <option value="<?php echo $row['Tia_Cod']; ?>"><?php echo $row['Tia_Des'] ?> </option>
                                            <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="segmento required">Cliente:</div>
                                <div  class="datasegmento"><input id="lblClie" onkeydown='if (event.keyCode === 13) buscaCliente();' onchange="if($('#lblClie').val()==='')$('#cod_cli').val('');" class="search clearable ui-corner-all" placeholder="Ingrese Cliente"  />
                                <a onclick="$('#clieDialog').dialog('open')" title="B&uacute;squeda de Clientes" class="btn btn-success btn-mini"><i class=" icon-check icon-white"></i></a></div>
                            </div>
                            <div class="row">
                                <div class="segmento required">Concepto:</div>
                                <div  class="datasegmento"><textarea class="text ui-corner-all" name="Com_Con" id="ConIngreso" onchange=" updateGlosa()" cols="73" style="text-transform:uppercase" onkeypress="return  validar_injections(event)" required></textarea></div>
                            </div>
                            <div class="row">
                                <div class="segmento">Observaci&oacute;n:</div>
                                <div  class="datasegmento"><textarea class="text ui-corner-all" name="Com_Obs" cols="73" style="text-transform:uppercase" onkeypress="return  validar_injections(event)"></textarea></div>
                            </div>
                            </td><td valign="top">
                            <div class="row">
                                <div class="segmento required">Fecha:</div>
                                <div  class="datasegmento"><input name="Com_Fec" type="text" style="text-align: center" size="10" maxlength="10" class="text small ui-corner-all" required /></div>
                            </div>
                            <div class="row">
                                <div class="segmento">No. Externo:</div>
                                <div  class="datasegmento"><input  class="text small ui-corner-all" style="text-align: center" name="Num_Doc" type="text" size="10" onkeypress="return  validar_numeric(event)" /></div>
                            </div>  
                            <div class="row">
                                <div class="segmento required">Valor:</div>
                                <div  class="datasegmento"><input  class="money text small ui-corner-all" name="Com_Val" id="Com_Val_Ingre"  onchange=" updateValores()" type="text" size="10" maxlength="12" style="text-align:right" onkeypress="return  validar_decimal(event)" required /></div>
                            </div>  
                            <div class="row">
                                <div class="segmento">Periodo Contable:</div>
                                <div  class="datasegmento">
                                    <input  class="text small ui-widget-content ui-corner-all" readOnly name="periodo" size="10" style="text-align: center" type="text" /> 
                                    <input type="hidden" name="op" value="I" />
                                    <input type="hidden" name="Pec_Cod" />
                                    <input type="hidden" name="Pld_Cod" />
                                    <input type="hidden" id="cod_cli" name="Codigo" value="" /> 
                                </div>
                            </div> 
                        </td></tr>
                        </table>    
                    </form>
                    </fieldset>
            </div>
<!-- FIN FORMULARIO COMPROBANTE DE INGRESO -->
<!-- FORMULARIO COMPROBANTE DE EGRESO -->
            <div id="tabs-2">
                <fieldset>
                    <legend>
                        <label class="Titulos2">Comprobante de Egreso</label>
                    </legend>	
                    <form name="formComp" id="formComp" method="post" action="javascript:$.createDialogConfirm('¿Est&aacute; seguro que desea guardar los datos?',null,saveComp)">
                        <table  width="100%" border="0" cellpadding="0" cellspacing="0" style="table-layout:fixed;min-width: 450px;">
                        <tr><td valign="top">
                            <div class="row">
                                <div class="segmento required">Tipo Comprobante:</div>
                                <div  class="datasegmento">
                                    <select class="text ui-corner-all"  name="Tia_Cod"  class="isSelectMenu" required>
                                        <option value="">Seleccione...</option>
                                            <?Php 
                                            $row_rs_tipo_asien2 = $obBD_con1->getArrayConsulta(373, "*E", $obBD_conexion);
                                            foreach ($row_rs_tipo_asien2 as $row) 
                                            { ?>
                                        <option value="<?php echo $row['Tia_Cod']; ?>"><?php echo $row['Tia_Des'] ?> </option>
                                            <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="segmento required">Proveedor:</div>
                                <div  class="datasegmento"><input id="lblProvee" onkeydown='if (event.keyCode === 13) buscaProveeIngreso();' onchange="if($('#lblProvee').val()==='')$('#cod_pvr').val('');" class="search clearable ui-corner-all" placeholder="Ingrese Proveedor"  />
                                <a onclick="$('#provDialog').dialog('open')" title="B&uacute;squeda de Proveedores" class="btn btn-success btn-mini"><i class=" icon-check icon-white"></i></a></div>
                            </div>
                            <div class="row">
                                <div class="segmento required">Concepto:</div>
                                <div  class="datasegmento"><textarea class="text ui-corner-all" name="Com_Con" id="ConEgreso" onchange=" updateGlosa()" cols="73" style="text-transform:uppercase" onkeypress="return  validar_injections(event)" required></textarea></div>
                            </div>
                            <div class="row">
                                <div class="segmento">Observaci&oacute;n:</div>
                                <div  class="datasegmento"><textarea class="text ui-corner-all" name="Com_Obs" cols="73" style="text-transform:uppercase" onkeypress="return  validar_injections(event)"></textarea></div>
                            </div>
                            </td><td valign="top">
                            <div class="row">
                                <div class="segmento required">Fecha Comprobante:</div>
                                <div  class="datasegmento">
                                    <input id="confec" name="Com_Fec" type="text" style="text-align: center" size="10" maxlength="10" onchange="$('#chefec').val($('#confec').val())" class="text small ui-corner-all" required />
                                    <input onchange="setCheque();" id="es_cheque" checked type="checkbox" value="true" offval="false" />Cheque
                                </div>                                
                            </div>
                            <div class="row es_cheque">
                                <div class="segmento required"  >Fecha Cheque:</div>
                                <div  class="datasegmento"><div id="Che_Fec" style="display: inline"><input id="chefec" name="Che_Fec" type="text" style="text-align: center" size="10" maxlength="10" class="text small ui-corner-all" required /></div>
                                    <input onchange="setPosfecha()" id="postfecha" type="checkbox" value="true" offval="false" />Postfechado
                                </div>
                            </div>
                            <div class="row es_cheque">
                                <div class="segmento required">No. Cheque:</div>
                                <div  class="datasegmento">
                                    <input  class="text small ui-corner-all" style="text-align: center" name="Num_Doc" id="NumChe" type="text" size="10" onkeypress="return  validar_numeric(event)" onChange="validaCheque();" required />                                    
                                    <img class="imgMsg" /><label class="lblMsg"></label>
                                </div>
                            </div>  
                            <div class="row es_cheque">
                                <div class="segmento required">Beneficiario:</div>
                                <div  class="datasegmento" id="Benediv"><input id="Bene_Id" name="Bene_Id" type="hidden" />
                                    <input id="apellido" name="apellido" class="text medium ui-corner-all" type="text" size="32" placeholder="Apellidos" style="width:40%;text-transform:uppercase" /><input  id="nombre" name="nombre" class="text medium ui-corner-all" type="text" size="32" placeholder="Nombres" style="width:40%;text-transform:uppercase" />
                                    <!--<a onclick="$('#Benediv').removeClass('disabled').find('input').removeAttr('readOnly').val('');$('#apellido').focus();" title="Quitar Beneficiario" class="btn btn-success btn-mini"><i class=" icon-eject icon-white"></i></a>-->
                                    <button type="button" onclick="$('#beneDialog').dialog('open');" title="Seleccionar Beneficiario" class="btn btn-success btn-mini"><i class=" icon-check icon-white"></i></button>
                                </div>
                            </div>
                            <div class="row">
                                <div class="segmento required">Valor Cheque:</div>
                                <div  class="datasegmento"><input  class="money text small ui-corner-all" name="Com_Val" id="Com_Val_Egre" onchange=" updateValores()" type="text" size="10" maxlength="12" style="text-align:right" onkeypress="return  validar_decimal(event)" required /></div>
                            </div> 
                            
                            <div class="row">
                                <div class="segmento">Periodo Contable:</div>
                                <div  class="datasegmento">
                                    <input  class="text small ui-widget-content ui-corner-all" readOnly name="periodo" size="10" style="text-align: center" type="text" /> 
                                    <input type="hidden" name="op" value="E" />
                                    <input type="hidden" name="Pec_Cod" />
                                    <input type="hidden" name="Pld_Cod" />
                                    <input type="hidden" name="Ban_Cod" />
                                    <input type="hidden" id="cod_pvr" name="Codigo" value="" />                                     
                                </div>
                            </div>                            
                        </td></tr>
                        </table>    
                    </form>
                    </fieldset>
            </div>
<!-- FIN FORMULARIO COMPROBANTE DE EGRESO -->
<!-- FORMULARIO COMPROBANTE DE DIARIO -->
            <div id="tabs-3">
                 <fieldset>
                    <legend>
                        <label class="Titulos2">Comprobante de Diario</label>
                    </legend>	
                    <form name="formComp" id="formDiario" method="post" action="javascript:$.createDialogConfirm('¿Est&aacute; seguro que desea guardar los datos?',null,saveDiario)">
                        <table  width="100%" border="0" cellpadding="0" cellspacing="0" style="table-layout:fixed;min-width: 450px;">
                        <tr><td valign="top">
                            <div class="row">
                                <div class="segmento required">Tipo Comprobante:</div>
                                <div  class="datasegmento">
                                    <select class="text ui-corner-all"  name="Tia_Cod"  class="isSelectMenu" required>
                                        <option value="">Seleccione...</option>
                                            <?Php 
                                            $row_rs_tipo_asien3 = $obBD_con1->getArrayConsulta(373, "*D", $obBD_conexion);
                                            foreach ($row_rs_tipo_asien3 as $row) 
                                            { ?>
                                        <option value="<?php echo $row['Tia_Cod']; ?>"><?php echo $row['Tia_Des'] ?> </option>
                                            <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="segmento required">Proveedor:</div>
                                <div  class="datasegmento"><input id="lblProvee2" onkeydown='if (event.keyCode === 13) buscaProveeDiario();' onchange="if($('#lblProvee2').val()==='')$('#cod_pvr2').val('');" class="search clearable ui-corner-all" placeholder="Ingrese Proveedor"  />
                                <a onclick="$('#provDialog').dialog('open')" title="B&uacute;squeda de Proveedores" class="btn btn-success btn-mini"><i class=" icon-check icon-white"></i></a></div>
                            </div>
                            <div class="row">
                                <div class="segmento required">Concepto:</div>
                                <div  class="datasegmento"><textarea class="text ui-corner-all" name="Com_Con" id="ConDiario" onchange=" updateGlosa()" cols="73" style="text-transform:uppercase" onkeypress="return  validar_injections(event)" required></textarea></div>
                            </div>
                            <div class="row">
                                <div class="segmento">Observaci&oacute;n:</div>
                                <div  class="datasegmento"><textarea class="text ui-corner-all" name="Com_Obs" cols="73" style="text-transform:uppercase" onkeypress="return  validar_injections(event)"></textarea></div>
                            </div>
                            </td><td valign="top">
                            <div class="row">
                                <div class="segmento required">Fecha:</div>
                                <div  class="datasegmento"><input name="Com_Fec" type="text" style="text-align: center" size="10" maxlength="10" class="text small ui-corner-all" required /></div>
                            </div>
                            <div class="row">
                                <div class="segmento">No. Externo:</div>
                                <div  class="datasegmento"><input  class="text small ui-corner-all" style="text-align: center" name="Num_Doc" type="text" size="10" onkeypress="return  validar_numeric(event)" /></div>
                            </div>  
                            <div class="row">
                                <div class="segmento required">Valor:</div>
                                <div  class="datasegmento"><input  class="money text small ui-corner-all" name="Com_Val" id="Com_Val_Diario" onchange=" updateValores()" type="text" size="10" maxlength="12" style="text-align:right" onkeypress="return  validar_decimal(event)" required /></div>
                            </div>  
                            <div class="row">
                                <div class="segmento">Periodo Contable:</div>
                                <div  class="datasegmento">
                                    <input  class="text small ui-widget-content ui-corner-all" readOnly name="periodo" size="10" style="text-align: center" type="text" /> 
                                    <input type="hidden" name="op" value="D" />
                                    <input type="hidden" name="Pec_Cod" />
                                    <input type="hidden" name="Pld_Cod" />
                                    <input type="hidden" id="cod_pvr2" name="Codigo" value="" /> 
                                </div>
                            </div> 
                        </td></tr>
                        </table>    
                    </form>
                    </fieldset>
            </div>
<!-- FIN FORMULARIO COMPROBANTE DE DIARIO -->
        </div>
    </div>





          <div id="comprobante" style="width: 100%;padding-top: 10px;">
               <FIELDSET>
		<LEGEND>
			<label class="Titulos2">Datos del Asiento Contable</label>
		</LEGEND>               
                <div id="compGrilla" style="padding-top: 6px;">   
                                    <table id="comp"></table>
                                    <div id="compPager"></div>
                </div>
                <script type="text/javascript">
                    function buscaCliente(){ 
                            $('#cod_cli').val('');
                             var array={'search':$('#lblClie').val(),'op_opciones':'c'};                           
                             $.SearchOrDialogArray("#clieDialog",function (data){$("#cod_cli").val(data['Cli_Cod']);$("#lblClie").val(data['clientes']);}
                             ,array);
                             $('#lblClie').val('');
                    }  
                    function buscaProveeIngreso(){ 
                            $('#cod_pvr').val('');
                             var array={'search':$('#lblProvee').val(),'op_opciones':'c'};                           
                             $.SearchOrDialogArray("#provDialog",function (data){$("#cod_pvr").val(data['Prv_Cod']);$("#lblProvee").val(data['proveedor']);}
                             ,array);
                             $('#lblProvee').val('');
                    } 
                    function buscaProveeDiario(){ 
                            $('#cod_pvr2').val('');
                             var array={'search':$('#lblProvee2').val(),'op_opciones':'c'};                           
                             $.SearchOrDialogArray("#provDialog",function (data){$("#cod_pvr2").val(data['Prv_Cod']);$("#lblProvee2").val(data['proveedor']);}
                             ,array);
                             $('#lblProvee2').val('');
                    } 
                    function updateValores(){                        
                        if(tipo==="Ingresos")valor=$("#Com_Val_Ingre").val();
                        else if(tipo==="Egresos")valor=$("#Com_Val_Egre").val();
                        else valor=$("#Com_Val_Diario").val();
                        $("input[name='Haber']").val(valor);
                        $("input[name='Debe']").val(valor);
                        $("#comp").updateGridDiario();
                    }
                    function updateGlosa(){                        
                        if(tipo==="Ingresos")glosa=$("#ConIngreso").val();
                        else if(tipo==="Egresos")glosa=$("#ConEgreso").val();
                        else glosa=$("#ConDiario").val();
                        $("input[name='Glosa']").val(glosa);
                    }
                    function validaCheque(){  
                        var numAnt=$("#NumChe").val();
                         if(tipo==="Egresos"&&$("#periodos").val()!==''){
                            $.get('<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',{'Ban_Cod':getPeriodo()["Ban_Cod"],'valChe': numAnt}, function(response){
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
                        $("#comp").clearGrid();
                        $("#comp").updateGridDiario();
                        $("select[name='Tia_Cod']").val('');//$('#Tia_Cod').selectmenu('refresh', true);                        
                        $("input[name='Com_Val']").val("");
                        $('#postfecha').removeAttr("checked");
                        $("input[name='Com_Fec']").datepicker("setDate", new Date());
                        $('#chefec').val($('#confec').val());
                        $('#Che_Fec').addClass('disabled').find('input').attr('disabled','disabled');
                        $('#Benediv').addClass('disabled').find('input').attr('readOnly','readOnly');
                        $("textarea[name='Com_Con']").val("");                       
                        $("textarea[name='Com_Obs']").val(""); 
                        $("input[name='Num_Doc']").val("");
                        $("#apellido").val("");
                        $("#nombre").val("");
                        $("#Bene_Id").val("");
                        setChequeNum();                           
                        $("#lblProvee").val("").removeClass('x onX');
                        $("#cod_pvr").val(""); 
                        $("#lblClie").val("").removeClass('x onX');
                        $("#cod_cli").val("");
                        $("#lblProvee2").val("").removeClass('x onX');
                        $("#cod_pvr2").val(""); 
                        valor=0;glosa="";
                        if($("#periodos").val()!==''){
                            if(tipo==="Ingresos")
                                addFilaCuenta(getPeriodo(),"D");
                            else
                                addFilaCuenta(getPeriodo(),"H");
                        }
                    }
                    function saveIngreso(){
                        var gridComp = $("#comp");
                        var batch = gridComp.getGridBatch();
                        gridComp.trigger('reloadGrid');
                        var     tot=parseFloat($("#Com_Val_Ingre").val()),
                                deb = gridComp.jqGrid("getCol", "Debe", false, "sum"),
                                hab = gridComp.jqGrid("getCol", "Haber", false, "sum");                       
                        if(deb.toFixed(2)===tot.toFixed(2)&&hab.toFixed(2)===tot.toFixed(2)){                            
                               if($('#cod_cli').val()!==''){                                    
                                    if(batch.length>0){ 
                                        var data=$('#formIngreso').serializeObject();
                                        data["save"]=batch;
                                        $.post( "<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>",data, function( response ) {
                                            if(response['success']===true){
                                                $('#impCompr').attr('href',response['link']);
                                                $('#successDialog').dialog("option", "height", 150);
                                                $('#printCheque').hide();
                                                $('#successDialog').dialog('open');
                                                resetForm();
                                            }else{$.alert(response['message']);}
                                         },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!");});
                                    }
                                }else{$.alert("Seleccione El Proveedor");}                                                
                        }else{$.alert("Los Totales no Coinciden!");}
                        gridComp.startGridEdit();
                    }
                    function saveComp(){
                        var gridComp = $("#comp");
                        var batch = gridComp.getGridBatch();
                        gridComp.trigger('reloadGrid');
                        var     tot=parseFloat($("#Com_Val_Egre").val()),
                                deb = gridComp.jqGrid("getCol", "Debe", false, "sum"),
                                hab = gridComp.jqGrid("getCol", "Haber", false, "sum");     
                        //alert(tot); alert(deb); alert(hab);
                        if(deb.toFixed(2)===tot.toFixed(2)&&hab.toFixed(2)===tot.toFixed(2)){                            
                               if($('#cod_pvr').val()!==''){                                    
                                    if(batch.length>0){ 
                                        var data=$('#formComp').serializeObject();
                                        data["save"]=batch;
                                        $.post( "<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>",data, function( response ) {
                                            if(response['success']===true){
                                                $('#impCompr').attr('href',response['link']);
                                                if(typeof response['cheque']==='undefined'||response['cheque']===''){
                                                    $('#successDialog').dialog("option", "height", 150);
                                                    $('#printCheque').hide();
                                                }else{
                                                    $('#printCheque').show();
                                                    $('#successDialog').dialog("option", "height", 250);
                                                    var html=$('#modelo').html();
                                                    html = html.replace(/{banco}/g, $('#periodos').find('option:selected').text());
                                                    html = html.replace(/{link}/g,response['cheque'] );
                                                    $('#printCheque').html(html);
                                                }
                                                $('#successDialog').dialog('open');
                                                resetForm();
                                            }else{$.alert(response['message']);}
                                         },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!");});
                                    }
                                }else{$.alert("Seleccione El Proveedor");}                                                
                        }else{$.alert("Los Totales no Coinciden!");}
                        gridComp.startGridEdit();
                    }
                    function saveDiario(){
                        var gridComp = $("#comp");
                        var batch = gridComp.getGridBatch();
                        gridComp.trigger('reloadGrid');
                        var     tot=parseFloat($("#Com_Val_Diario").val()),
                                deb = gridComp.jqGrid("getCol", "Debe", false, "sum"),
                                hab = gridComp.jqGrid("getCol", "Haber", false, "sum");                       
                        if(deb.toFixed(2)===tot.toFixed(2)&&hab.toFixed(2)===tot.toFixed(2)){                            
                               if($('#cod_pvr2').val()!==''){                                    
                                    if(batch.length>0){ 
                                        var data=$('#formDiario').serializeObject();
                                        data["save"]=batch;
                                        $.post( "<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>",data, function( response ) {
                                            if(response['success']===true){
                                                $('#impCompr').attr('href',response['link']);
                                                $('#successDialog').dialog("option", "height", 150);
                                                $('#printCheque').hide();
                                                $('#successDialog').dialog('open');
                                                resetForm();
                                            }else{$.alert(response['message']);}
                                         },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!");});
                                    }
                                }else{$.alert("Seleccione El Proveedor");}                                                
                        }else{$.alert("Los Totales no Coinciden!");}
                        gridComp.startGridEdit();
                    }
                    function addFilaCuenta(cuenta,tipo){                         
                        cuenta['Glosa']=glosa;
                        cuenta['Debe']=cuenta['Haber']=0;                        
                        cuenta['Det_Tip']=tipo; 
                        if(tipo==='D')
                        cuenta['Debe']=valor;   
                        if(tipo==='H')
                        cuenta['Haber']=valor;   
                        var grid=$("#comp");
                        grid.jqGrid("addRowData", cuenta["Pld_Cod"], cuenta, "last");        
                        grid.startGridEdit();
                        $("#comp").updateGridDiario();
                    }
                    function SelectCta(id,tipo){                        
                        if(!$("#comp").existsId(id)){
                            addFilaCuenta($.getDialogGrid("#cuenDialog").jqGrid('getRowData', id),tipo);                           
                        }
                    }                                        
                    $(document).ready(function () {  
                        $('#Che_Fec').toggleClass('disabled').find('input').toggleAttr('disabled');
                        var gridComp=$("#comp");
                        gridComp.jqGrid({
                            url: '<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',
                            mtype: "GET", datatype: "local", regional : 'es',//ajaxRowOptions: { async: true },                             
                            autowidth : true, shrinkToFit: true, height: 120,
                            cmTemplate: {sortable:false},
                            colModel: [
                                { label: 'Cód.Int.', name: 'Pld_Cod', key: true, width: 15,align:"center", hidden:true },  
                                { label: 'Tipo', name: 'Det_Tip', hidden:true },
                                { label: 'Codigo', name: 'Pld_Cdc', width: 45 },                      
                                { label: 'Cuenta', name: 'Pld_Des', width: 150  },
                                { label: 'Glosa', name: 'Glosa', width: 150,editable:true },
                                { label: 'Debe', name: 'Debe', width: 50, align: 'right', formatter:'currency',
                                     formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "Total: <b>{0}<b/>", summaryType: "sum",
                                     editoptions: {
                                        dataInit: function (element) {$("#comp").createInputDiario(element,"D","Det_Tip");}
                                    },editable:true 
                                },                                         
                                { label: 'Haber', name: 'Haber', width: 50,align: 'right', formatter:'currency', 
                                     formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "Total: <b>{0}</b>", summaryType: "sum",
                                     editoptions: {
                                        dataInit: function (element) {$("#comp").createInputDiario(element,"H","Det_Tip");}
                                    },editable:true 
                                },
                                    { label:'&nbsp;', name: 'act1', width: 30, align: 'center',viewable: false,
                                        formatter:function (cellvalue, options, rowObject) { 
                                            var aux=$("#periodos").val().split('*');
                                            if(rowObject.Pld_Cod!==aux[0])
                                            return  '<span class="btn btn-danger btn-mini" title="Eliminar" onclick="$(\'#comp\').jqGrid(\'delRowData\',\''+rowObject.Pld_Cod+'\');$(\'#comp\').updateGridDiario();"><i class="icon-remove icon-white"></i></span>'; 
                                           else return "";
                                        }
                                    }
                            ],
                            //caption:"Cuentas",hidegrid: false,     
                            footerrow: true, userDataOnFooter: false,// set a footer row                             
                            rowNum: 10000000, pager: "#compPager", gridview: true, rownumbers: true, viewrecords: true, altRows: true, altclass: "myAltRowClass",pgbuttons: false,pgtext: null
                        });                        
                        gridComp.navGrid('#compPager',{ edit: false, add: false, del: false, search: false, refresh: false, view: false, position: "left", cloneToTop: false });
                        gridComp.jqGrid('bindKeys');                                               
                        $.clearFooterDiario("#comp");
                    });  
                </script>  
                 <div style="padding-top: 6px;">
                     <button onclick="if($('#periodos').val()===''){$.alert('Seleccione el Banco');}else{if(tipo==='Ingresos')$('#formIngreso').formSubmit();else if(tipo==='Egresos') $('#formComp').formSubmit(); else $('#formDiario').formSubmit(); }" title="Guardar Comprobante" type="button" class="btn btn-primary start" ><i class="icon-book icon-white"></i><span>Guardar</span></button><span style="width: 15px;"></span>
                  <button onclick="$('#cuenDialog').dialog('open');" title="Buscar Cuentas" type="button" class="btn btn-success fileinput-button"><i class="icon-list-alt icon-white"></i><span>Agregar</span></button>
                 </div>
               </FIELDSET>
              
          </div>
<!-- FN PANEL COMPROBANTE -->
        </td>
      </tr>     
    </table>
   	
</div>	
<!--INICIO DEL DIALOGO BUSCAR CUENTA--> 
    <div id="cuenDialog" title="B&uacute;squeda de Cuentas">     
        <form>          	
                <FIELDSET>
                    <LEGEND>
                        <label class="Titulos2">B&uacute;squeda de Cuentas</label>
                    </LEGEND>
                    
                    <table border="0" cellpadding="0" cellspacing="0">
                            <tr>
                              <td width="156"><input name="op_opciones" type="radio" checked="checked" value="d" onclick="setfocus(this.form.search)" />
                                    <span class="LetraNegra"><strong>Descripci&oacute;n</strong></span></td>
                              <td width="156"><input name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" />
                                    <span class="LetraNegra"><strong>C&oacute;digo</strong></span></td>
                                <td>Plan de Cuentas:
                                    <input name="periodo" type="text" size="6" readonly style="text-align: center" class="text ui-corner-all ui-widget-content"/> 
                                   <input name="Pec_Cod" type="hidden" /> 
                                </td>
                            </tr>
                    </table>
                    <table height="36" border="0" cellpadding="0" cellspacing="0">
                        <tbody>
                      <tr>
                          <td width="80" height="28" class="BarraBusqueda" style="border-right: 0px;padding-right: 10px;padding-left: 10px;"><div align="right"><strong>B&uacute;squeda</strong></div></td>
                          <td width="387" class="BarraBusqueda" style="border-left: 0px;"><input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese cuenta a buscar..." autofocus /></td>
                          <td width="109" align="center">
                            <button type="button" onclick="this.form.submit()" class="btn btn-success fileinput-button" title="Buscar Cuenta" >
                           <i class="icon-search icon-white"></i>
                           <span>Buscar</span>
                           </button></td>
                      </tr>
                        </tbody>
                    </table>                                                  
                    </FIELDSET>              
            </form>
    </div>
<!-- FIN DEL DIALOGO CUENTAS-->

<!--INICIO DEL DIALOGO BUSCAR PROVEEDOR--> 
    <div id="provDialog"  title="B&uacute;squeda de Proveedores">  
       <form> 
        <fieldset>
		<legend>
                    <label class="Titulos2">B&uacute;squeda de Proveedor</label>
		</legend>
		<table border="0">
                    <tbody><tr>
			  <td width="205"><input name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)" alt="" />
			  <span class="LetraNegra"><strong>Apellido</strong></span></td>
			  <td width="266"><input name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" />
				<span class="LetraNegra"><strong>C&eacute;dula/R.U.C.</strong></span></td>
			</tr>
                    </tbody>
                </table>
                <table height="36" border="0" cellpadding="0" cellspacing="0">
                        <tbody>
                      <tr>
                          <td width="80" height="28" class="BarraBusqueda" style="border-right: 0px;padding-right: 10px;padding-left: 10px;"><div align="right"><strong>B&uacute;squeda</strong></div></td>
                          <td width="387" class="BarraBusqueda" style="border-left: 0px;"><input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50"  placeholder="Ingrese proveedor a buscar..." autofocus /></td>
                          <td width="109" align="center">
                              <button type="button" onclick="this.form.submit()" class="btn btn-success fileinput-button" title="Buscar Proveedor" >
                           <i class="icon-search icon-white"></i>
                           <span>Buscar</span>
                           </button></td>
                      </tr>
                        </tbody>
                </table>
        </fieldset>  
       </form>
    </div>
<!-- FIN DEL DIALOGO PROVEEDOR-->

<!--INICIO DEL DIALOGO BUSCAR BEnfICIARIO--> 
    <div id="beneDialog"  title="B&uacute;squeda de Beneficiarios">  
       <form> 
        <fieldset>
		<legend>
                    <label class="Titulos2">B&uacute;squeda de Beneficiarios</label>
		</legend>
		<table border="0">
                    <tbody><tr>
			  <td width="205"><input name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)" alt="" />
			  <span class="LetraNegra"><strong>Apellido</strong></span></td>
			  <td width="266"><input name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" />
				<span class="LetraNegra"><strong>C&eacute;dula/R.U.C.</strong></span></td>
			</tr>
                    </tbody>
                </table>
                <table height="36" border="0" cellpadding="0" cellspacing="0">
                        <tbody>
                      <tr>
                          <td width="80" height="28" class="BarraBusqueda" style="border-right: 0px;padding-right: 10px;padding-left: 10px;"><div align="right"><strong>B&uacute;squeda</strong></div></td>
                          <td width="387" class="BarraBusqueda" style="border-left: 0px;"><input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50"  placeholder="Ingrese proveedor a buscar..." autofocus /></td>
                          <td width="140" align="center">
                              <button type="button" onclick="this.form.submit()" class="btn btn-success fileinput-button" title="Buscar Proveedor" >
                           <i class="icon-search icon-white"></i>
                           <span>Buscar</span>
                           </button>
                           <a onclick="$('#beneApe').val('');$('#beneNom').val('');$('#addBenef').dialog('open');" title="Seleccionar Beneficiario" class="btn btn-success"><i class=" icon-plus icon-white" style="height: 16px;margin-top: 2px;"></i></a>
                          </td>
                      </tr>
                        </tbody>
                </table>
        </fieldset>  
       </form>
    </div>
<!-- FIN DEL DIALOGO  BUSCAR BEnfICIARIO-->

<!--INICIO DEL DIALOGO BUSCAR CLIENTE--> 
    <div id="clieDialog"  title="B&uacute;squeda de Clientes">  
       <form> 
        <fieldset>
		<legend>
                    <label class="Titulos2">B&uacute;squeda de Cliente</label>
		</legend>
		<table border="0">
                    <tbody><tr>
			  <td width="205"><input name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)" alt="" />
			  <span class="LetraNegra"><strong>Apellido</strong></span></td>
			  <td width="266"><input name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" />
				<span class="LetraNegra"><strong>C&eacute;dula/R.U.C.</strong></span></td>
			</tr>
                    </tbody>
                </table>
                <table height="36" border="0" cellpadding="0" cellspacing="0">
                        <tbody>
                      <tr>
                          <td width="80" height="28" class="BarraBusqueda" style="border-right: 0px;padding-right: 10px;padding-left: 10px;"><div align="right"><strong>B&uacute;squeda</strong></div></td>
                          <td width="387" class="BarraBusqueda" style="border-left: 0px;"><input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50"  placeholder="Ingrese Cliente a buscar..." autofocus /></td>
                          <td width="109" align="center">
                              <button type="button" onclick="this.form.submit()" class="btn btn-success fileinput-button" title="Buscar Proveedor" >
                           <i class="icon-search icon-white"></i>
                           <span>Buscar</span>
                           </button></td>
                      </tr>
                        </tbody>
                </table>
        </fieldset>  
       </form>
    </div>
<!-- FIN DEL DIALOGO CLIENTE-->


<!-- CREA BEdefICIARIO DIALOG -->
    <div id="addBenef"  title="Crear Beneficiario">  
        
        <fieldset>
		<legend>
                    <label class="Titulos2">Datos Beneficiario</label>
		</legend>
            <form action="javascript:saveBene();"> 
            <div class="row">
                <div class="segmento required">Beneficiario:</div>
                <div  class="datasegmento" id="Benediv">
                    <input id="beneApe" name="apellido" class="text medium ui-corner-all" type="text" size="32" placeholder="Apellidos" style="text-transform:uppercase" required autofocus/><input  id="beneNom" name="nombre" class="text medium ui-corner-all" type="text" size="32" placeholder="Nombres" style="text-transform:uppercase" />                   
                </div>
            </div>
            <div class="row" style="text-align: center;padding-top: 10px;">
                <button type="submit" class="btn btn-success fileinput-button" title="Guardar Proveedor" >
                    <i class="icon-book icon-white"></i>
                    <span>Guardar</span>
                </button><span>&nbsp;</span>
                <button type="button" onclick="$('#addBenef').dialog('close');" class="btn btn-inverse fileinput-button" title="Cancelar" >
                    <i class="icon-ban-circle icon-white"></i>
                    <span>Cancelar</span>
                </button>            
            </div>
            </form> 
        </fieldset>
        
    </div>   
<script type="text/javascript"> 
    function setPosfecha(){
        var checked=$('#postfecha').is(':checked');
        $('#Che_Fec')[(checked?'remove':'add')+'Class']('disabled').find('input')[checked?'removeAttr':'attr']('disabled','disabled');
        $('#chefec').val($('#confec').val());
    }
    function setCheque(){
        var checked=$('#es_cheque').is(':checked');
        $('#postfecha').prop('checked',false).trigger('change');
        $('.es_cheque').find(':input')[checked?'removeAttr':'attr']('disabled','disabled');
        setChequeNum();
    }
    function setChequeNum(){
        if(tipo==="Egresos"&&$("#periodos").val()!==''&&$('#es_cheque').is(':checked')){
            $.get('<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',{'Ban_Cod':getPeriodo()["Ban_Cod"],'cheNum':true}, function(response){
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
    $(document).ready(function() {       
        //$(".isSelectMenu").selectmenu();
        $.createDatePickers("input[name='Com_Fec']");
        $.createDatePickers("input[name='Che_Fec']");
        setPeriodo(); 
             $.createDialog('#addBenef',150,550);
             $.createDialog('#successDialog',150,550);
             
             //$('#successDialog').dialog('open');
            // DIALOG BUSCAR CUENTAS
             $.createSearchDialog('cuenDialog',[
                    { label: 'C&oacute;d.Int.', name: 'Pld_Cod', key: true, width: 15,align:"center",hidden:true },                                
                    { label: 'Codigo', name: 'Pld_Cdc', width: 45 },                      
                    { label: 'Cuenta', name: 'Pld_Des', width: 80, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},
                    { label: 'Grupo', name: 'Pld_Grupo', width: 110, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},
                    { label: 'Tipo', name: 'Pld_Tip', width: 30,align:"center" },
                    { label: 'Estado', name: 'Pld_Est', width: 30,align:"center"}, 
                        { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 30, align: 'center',viewable: false,
                            formatter:function (cellvalue, options, rowObject) { 
                                return  '<span class="btn btn-success btn-mini" title="Enviar al D&eacute;bito" onclick="SelectCta(\''+rowObject.Pld_Cod+'\',\'D\');"><b>D</b></span>&nbsp;'
                                        +'<span class="btn btn-success btn-mini" title="Enviar al Cr&eacute;dito" onclick="SelectCta(\''+rowObject.Pld_Cod+'\',\'H\');"><b>H</b></span>'; 
                            }
                        }
                ]); 
                
             // DIALOG BUSCAR PREOVEEDOR    
             $.createSearchDialog('provDialog',[
                    { label: 'C&oacute;d.Int.', name: 'Prv_Cod', key: true, width: 20,align:"center",hidden:true,viewable: true },                                
                    { label: 'C&eacute;dula/R.U.C.', name: 'Prs_Ced', width: 50 },                      
                    { label: 'Proveedor', name: 'proveedor', width: 190, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},                   
                    { label: 'Apellidos', name: 'Prs_Ape',hidden:true},
                    { label: 'Nombres', name: 'Prs_Nom',hidden:true},
                    { label: 'Direcci&oacute;n', name: 'Prs_Dir',hidden:true,viewable: true },                      
                        { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center',viewable: false,
                            formatter:function (cellvalue, options, rowObject) { 
                               var clic='if(tipo==="Egresos"){$("#cod_pvr").val("'+rowObject.Prv_Cod+'");$("#lblProvee").val("'+rowObject.proveedor+'");$("#apellido").val("'+rowObject.Prs_Ape+'");$("#nombre").val("'+rowObject.Prs_Nom+'");$("#Bene_Id").val("'+rowObject.Prv_Cod+'");$("#Benediv").addClass("disabled").find("input").attr("readOnly","readOnly");}';
                                  clic=clic+'if(tipo==="Diario"){$("#cod_pvr2").val("'+rowObject.Prv_Cod+'");$("#lblProvee2").val("'+rowObject.proveedor+'");}$("#provDialog").dialog("close");';
                                return  '<span class="btn btn-success btn-mini" title="Seleccionar" onclick=\''+clic+'\'><i class="icon-arrow-right icon-white"></span>'; 
                            }
                        }
                ]);  
                
            // DIALOG BUSCAR BENEFICIARIO    
             $.createSearchDialog('beneDialog',[
                    { label: 'C&oacute;d.Int.', name: 'Prv_Cod', key: true, width: 20,align:"center",hidden:true,viewable: true },                                
                    { label: 'C&eacute;dula/R.U.C.', name: 'Prs_Ced', width: 50 },                      
                    { label: 'Beneficiario', name: 'proveedor', width: 190, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},                   
                    { label: 'Apellidos', name: 'Prs_Ape',hidden:true},
                    { label: 'Nombres', name: 'Prs_Nom',hidden:true},                                        
                        { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center',viewable: false,
                            formatter:function (cellvalue, options, rowObject) { 
                               var clic='$( "#apellido" ).val("'+rowObject.Prs_Ape+'" );$( "#nombre" ).val( "'+rowObject.Prs_Nom+'" );$( "#Bene_Id" ).val( "'+rowObject.Prv_Cod+'" );$( "#beneDialog" ).dialog("close");';                               
                               return  '<span class="btn btn-success btn-mini" title="Seleccionar" onclick=\''+clic+'\'><i class="icon-arrow-right icon-white"></span>'; 
                            }
                        }
                ]);  
                
               
               // DIALOG BUSCAR CLIENTE    
             $.createSearchDialog('clieDialog',[
                    { label: 'C&oacute;d.Int.', name: 'Cli_Cod', key: true, width: 20,align:"center",hidden:true,viewable: true },                                
                    { label: 'C&eacute;dula/R.U.C.', name: 'Prs_Ced', width: 50 },                      
                    { label: 'Cliente', name: 'clientes', width: 190, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},                   
                    { label: 'Direcci&oacute;n', name: 'Prs_Dir',hidden:true,viewable: true },                      
                        { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center',viewable: false,
                            formatter:function (cellvalue, options, rowObject) { 
                               var clic='$("#cod_cli").val("'+rowObject.Cli_Cod+'");$("#lblClie").val("'+rowObject.clientes+'");$("#clieDialog").dialog("close");';
                                return  '<span class="btn btn-success btn-mini" title="Seleccionar" onclick=\''+clic+'\'><i class="icon-arrow-right icon-white"></span>'; 
                            }
                        }
                ]);   
               
              
    });     
    $( "#tabs" ).tabs({activate: function(event ,ui){
                             tipo=ui.newTab[0].getElementsByTagName("a")[0].innerHTML;
                             $(ui.newTab[0].getElementsByTagName("a")[0].hash).effect("highlight",{},100);
                             resetForm();
                            //console.log(ui.newTab[0].getElementsByTagName("a")[0].hash);
                            //alert(  ui.newTab.index());
                            //$.alert(tipo);
                             //alert( this.text);
                             //$("select[name='Tia_Cod']").val('');
                        }});
    function resetForm2(option){
        $("#comp").clearGrid();
        $("#comp").updateGridDiario();
    }
    </script>

<!--INICIO DEL DIALOGO IMPRIMIR --> 
    <div id="successDialog"  title="Mensaje del Sistema">  
        <center><h2>El Comprobante se ha registrado con Exito!</h2></center>  
        <center id="printCheque"></center>
        <center> 
            <button type="button" onclick="$('#successDialog').dialog('close');" class="btn btn-inverse fileinput-button" style="display: inline;" >
                    <i class="icon-ban-circle icon-white"></i>
                    <span>Cerrar</span>
             </button>            
            <a id="impCompr" target="_blank" href=""  style="display: inline;" title="Imprimir Comprobante"><span  class="btn btn-primary start"> <i class="icon-print icon-white"></i> <span>Imprimir</span></span> </a>
               
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
        </tr>
    </table>
</div>    
</BODY>
</HTML>