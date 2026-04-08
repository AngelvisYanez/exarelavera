<?php
/**
* Descripci�n: Permite generar archivo XML del formulario 104
* Fecha de creaci�n:	2015-05-21
* Desarrollador:	Lewis Chimarro
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_104.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
 

/**
* Incrementa la capacidad de espacio reservado en la memoria ram para este script 
*/
ini_set("memory_limit" , "32M") ;

if (isset($html))
{
	/**
	* Creacion del Objeto de conexion 
	*/
	$obBD_conexion = new Class_Log_Conexion_Anx($Ses_Dat_Dis);
	/**
	* Creacion del objeto mysql para las consultas 
	*/
	$obBD_con1 =  new Class_Log_Datos_Anx; 	

    if($chk_fechas){
        $ini = $Ats_Fec_Ini .' 00:00:00';
        $fin = $Ats_Fec_Fin .' 23:59:59';
        $anio = substr($fin, 0, 4);
        $mes = substr($fin, 5, 2);
    } else{
    	$ini = $anio.'-'.$mes.'-'.'01 00:00:00';
    	$fin = $anio.'-'.$mes.'-'.ultimoDia($mes,$anio).' 23:59:59';
    }

    $iva=12;
    $noiva=0;

	/**
	* Identificaci�n 
	* Esta consulta nos permite obtener la informacion de la Empresa(Ruc, Nombre, etc) para llenar el encabezado del
	* archivo XML a generar
	*/       
        
        $row_identifica = $obBD_con1->getRowConsulta(1,$Ses_Emp_Cod, $obBD_conexion);
        //Codigos Retencion
        $rs_DatosCodigo=$obBD_con1->getArrayConsulta(16,$Ses_Emp_Cod,$obBD_conexion);
        $totRegistro=0;
		$totBase=0;
		$totRet=0;
        $optest='A';
		$arrayAgrupado = array(); // nuevo array agrupado

        for($i=0;$i<count($rs_DatosCodigo);$i++){ 	
		   $renSri = $rs_DatosCodigo[$i]['Ren_Sri'];
           if($rs_DatosCodigo[$i]['Ren_Sri']!='332'){
                $rs_DatosCompra=$obBD_con1->getArrayConsulta(17,$Ses_Emp_Cod.'*'.$rs_DatosCodigo[$i]['Ren_Sri'].'*'.$ini.'*'.$fin.'*'.$optest.'*'.$rs_DatosCodigo[$i]['Ren_Por'],$obBD_conexion);
           }else{
                $rs_DatosCompra=$obBD_con1->getArrayConsulta(18,$Ses_Emp_Cod.'*'.$ini.'*'.$fin.'*'.$optest,$obBD_conexion);			
           }//fin if($codigo['Ren_Sri']=='332')
		    $rs_DatosCompra_total=count($rs_DatosCompra);
           
                $totRegistro=$totRegistro+$rs_DatosCompra_total;
                $totBase=0;	   
                $totRet=0;
		    // agrupar por Ren_Sri
            if(!isset($arrayAgrupado[$renSri])){
                $arrayAgrupado[$renSri] = array(
                    'Ren_Sri' => $renSri,
                    'totBase' => 0,
                    'totRet'  => 0
                );
            }
            foreach($rs_DatosCompra as $compra){
                    $nomDocComp=substr($compra['Tic_Des'],0,19);
                    $totBase=$totBase+$compra['Ret_Bas'];	
                    $totRet=$totRet+$compra['Ren_Ret'];
            }
            //$rs_DatosCodigo[$i]['totBase']=$totBase;
            //$rs_DatosCodigo[$i]['totRet']=$totRet;
            $arrayAgrupado[$renSri]['totBase'] += $totBase;
            $arrayAgrupado[$renSri]['totRet']  += $totRet;
           
        }
        //die(var_dump($arrayAgrupado));
        
        //INICIO DEL FORMULARIO 103       
        $form['{101}']=$mes;
        $form['{102}']=$anio;       
        $form['{201}']=$row_identifica['Emp_Ruc'];
        $form['{202}']=$row_identifica['Emp_Nom'];
        foreach($arrayAgrupado as $codigo){    

            if(strlen($codigo['Ren_Sri'])>3){$CodSri=substr($codigo['Ren_Sri'], 0, 3);}else{$CodSri=$codigo['Ren_Sri'];}            
            
            if(!isset($form['{'.$CodSri.'}'])){
                if(strlen($codigo['Ren_Sri'])>3 && ($codigo['Ren_Sri']=='312A' || $codigo['Ren_Sri']=='3440') ){
                    $form['{'.$CodSri.'0}']=0;
                    $form['{'.(($CodSri*1)+50).'0}']=0;
                }
                else{
                    $form['{'.$CodSri.'}']=0;
                    $form['{'.(($CodSri*1)+50).'}']=0;
                } 
            }
             
            if(strlen($codigo['Ren_Sri'])>3 && ($codigo['Ren_Sri']=='312A' || $codigo['Ren_Sri']=='3440')){
                $form['{'.$CodSri.'0}']=formato_numero(($form['{'.$CodSri.'0}']*1)+$codigo['totBase'],2,1);
            }
            else{
                $form['{'.$CodSri.'}']=formato_numero(($form['{'.$CodSri.'}']*1)+$codigo['totBase'],2,1);
            }

            if(strlen($codigo['Ren_Sri'])>3 && ($codigo['Ren_Sri']=='312A' || $codigo['Ren_Sri']=='3440')){
                  $form['{'.(($CodSri*1)+50).'0}']=formato_numero(($form['{'.(($CodSri*1)+50).'0}']*1)+$codigo['totRet'],2,1); 
            }
            else{
                $form['{'.(($CodSri*1)+50).'}']=formato_numero(($form['{'.(($CodSri*1)+50).'}']*1)+$codigo['totRet'],2,1); 
            }                     
        }

        //Agregar valor del 351 al codigo 346
        $form['{346}'] =  $form['{346}'] +($form['{351}']*1);
        $form['{396}'] =  $form['{396}'] +($form['{401}']*1);

        $form['{349}']=0;$form['{399}']=0;
        $form['{497}']=0;$form['{498}']=0;
        for($i=302;$i<=346;$i++){
            if(isset($form['{'.$i.'}'])){
                $form['{349}']= $form['{349}']+($form['{'.$i.'}']*1);
                $form['{399}']= $form['{399}']+($form['{'.($i+50).'}']*1);
            }
        }
        for($i=401;$i<=440;$i++){
            if(isset($form['{'.$i.'}'])){
                $form['{497}']= $form['{497}']+($form['{'.$i.'}']*1);
                $form['{498}']= $form['{498}']+($form['{'.($i+50).'}']*1);
            }
        }

        $form['{349}']= $form['{349}'] + ($form['{3120}']*1) + ($form['{3440}']*1);
        $form['{399}']= $form['{399}'] + ($form['{3620}']*1) + ($form['{3940}']*1);


        $form['{890}']=formato_numero($form['{399}']+$form['{498}'],2,1);
        $form['{902}']=$form['{890}'];
        $form['{349}']=formato_numero($form['{349}'],2,1);$form['{399}']=formato_numero($form['{399}'],2,1);
        $form['{497}']=formato_numero($form['{497}'],2,1);$form['{498}']=formato_numero($form['{498}'],2,1);
        
        $buffer=reporteHtml($form,'tes_pri_html_103.html');
        $responce['html_103']=preg_replace('/{(.+)}/', '', $buffer);

        $responce['success']=true;
        utf8_encode_deep($responce);
        echo json_encode($responce);exit();
}

?>
<HTML>
	<HEAD>
		<!--TITLE><?php echo $Ses_Sys_Nom; ?></TITLE-->
        <TITLE><?php echo "Formulario 103 [EXA]"; ?></TITLE>
        <meta charset= "UTF-8">
		<?php require_once("../../mascaras/model1/estilos/estilos.php")?>        
		<script type="text/javascript" src="../../Librerias/validaciones/validacion.js?a=1"></script>	
        <!--Librerias para interfaz -->               
	    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
		<script type="text/javascript">$(function() {$('#set1 *').tooltip({showURL: false});});</script>        
	<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
	</HEAD>
<BODY>
<div id="set1">
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
	<tr class="BarraTitulo">
	  <td height="10">&raquo; Formulario 103</td>
  </tr>
  <tr>
      <td height="389" align="left" valign="top">
    	  <form action="javascript:generaHtml()" method="post" id= "form1">
		   <?php echo mensaje_requerido(); ?>
			<FIELDSET>
			<LEGEND>
			<label class="Titulos2">Generar Vista Previa:</label>
			</LEGEND>		  
    	    <table width="57%" border="0">

              <tr>
                <td width="13%" class="Etiqueta1"><span class="Asterisco">* </span>A&ntilde;o:&nbsp; </td>
                <td width="87%">
				<select name="anio" id="anio" onchange="generaHtml();">                  
                  <?php	//Presentamos los dos ultimos a�os para generar el XML  
						for ($i=date("Y"); $i>= date("Y")-4; $i--)
						{
				  ?>
                  <option <?php if($anio==$i){ echo "selected";}?> value="<?php echo $i; ?>"><?php echo $i; ?></option>				  
                  <?php
						}
				  ?>
                </select></td>
              </tr>
              <tr>
                <td class="Etiqueta1"><span class="Asterisco">* </span>Mes:&nbsp;</td>
                <td>
                    <select name="mes" id="mes" onchange="generaHtml();" >					
					<option <?php if($mes=="01"){ echo "selected";}?> value="01">Enero</option>
					<option <?php if($mes=="02"){ echo "selected";}?> value="02">Febrero</option>
					<option <?php if($mes=="03"){ echo "selected";}?> value="03">Marzo</option>
					<option <?php if($mes=="04"){ echo "selected";}?> value="04">Abril</option>
					<option <?php if($mes=="05"){ echo "selected";}?> value="05">Mayo</option>
					<option <?php if($mes=="06"){ echo "selected";}?> value="06">Junio</option>
					<option <?php if($mes=="07"){ echo "selected";}?> value="07">Julio</option>
					<option <?php if($mes=="08"){ echo "selected";}?> value="08">Agosto</option>
					<option <?php if($mes=="09"){ echo "selected";}?> value="09">Septiembre</option>
					<option <?php if($mes=="10"){ echo "selected";}?> value="10">Octubre</option>																																								
					<option <?php if($mes=="11"){ echo "selected";}?> value="11">Noviembre</option>																																								
					<option <?php if($mes=="12"){ echo "selected";}?> value="12">Diciembre</option>																																																																																															
                    </select>    <input type="text" value="" name="html" style="display:none" />            
				</td>
              </tr>

            <tr>
                <td class="Etiqueta1"> Fechas <input type="checkbox" id="chk_fechas" name="chk_fechas" value="first_checkbox" onclick="filtros();"></td>
            </tr>

              <tr>
                <td class="Etiqueta1"><span class="Asterisco">* </span>Inicio:&nbsp;</td>
                <td>                       
                  <input id="Ats_Fec_Ini" name="Ats_Fec_Ini" type="date" data-date="" data-date-format="DD MMMM YYYY" class="form-control input-xs datepickers" tabindex="8" required="" disabled="true" />
                  <span class="input-group-addon input-xs" title="Fecha de inicio"><i class="glyphicon glyphicon-info-sign blue"></i></span>
                </td>
              </tr>

              <tr>
                <td class="Etiqueta1"><span class="Asterisco">* </span>Fin:&nbsp;</td>
                <td>                                         
                  <input id="Ats_Fec_Fin" name="Ats_Fec_Fin" type="date" data-date="" data-date-format="DD MMMM YYYY" class="form-control input-xs datepickers" tabindex="8" required="" disabled="true" />
                  <span class="input-group-addon input-xs" title="Fecha fin"><i class="glyphicon glyphicon-info-sign blue"></i></span>
                </td>
              </tr>

            <tr>
                <td>
                    <button type="submit" class="btn btn-primary">Actualizar</button>
                </td>
            </tr>

            </table>
			</FIELDSET>
			

			<FIELDSET>
			<LEGEND>
			<label class="Titulos2">Rubros a Generar:</label>
			</LEGEND>
			<table width="333" border="0">
				<tr>
					<td width="71" class="Etiqueta1">Tipo:</td>
					<td width="98" class="LetraNegra">&nbsp;
				    Original 
				    <input name="tipo" type="radio" id="radio" value="O" checked></td>
					<td width="150" class="LetraNegra">Sustitutiva
					  <input type="radio" name="tipo" id="radio2" value="S"></td>
				</tr>				
				<tr>
					<td class="Etiqueta1">&nbsp;</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
				</tr>				
			</table>
                            <div class="LetraNegra" id="Html103">
                                ver
                            </div>
			</FIELDSET>
            <br>
		    <table  border="0" cellpadding="0" cellspacing="0">
              <tr>
                <td  style="padding-left: 10px;">  
                    <button type="button" class="btn btn-primary start" title="Imprimir" onClick= "$('#Html103').printElement()" style="display: inline-block">
                            <i class="icon-print icon-white"></i>
                            <span>Imprimir Resumen</span>
                    </button>                                  
                </td>
              </tr>
            </table>
		    
          </form>
	  </td>
  </tr>
</table>
</div>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>    
    <script>

        function filtros(){
            if(document.getElementById("chk_fechas").checked){
                document.getElementById("Ats_Fec_Fin").disabled = false;
                document.getElementById("Ats_Fec_Ini").disabled = false;
                document.getElementById("mes").disabled = true;
                document.getElementById("anio").disabled = true;
            }
            else{
                document.getElementById("mes").removeAttribute("disabled");
                document.getElementById("anio").removeAttribute("disabled");
                document.getElementById("Ats_Fec_Fin").disabled = true;
                document.getElementById("Ats_Fec_Ini").disabled = true;
            }
        }

       function generaHtml(){
            $('#Html103').html('');
            $.get("<?php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",$("#form1").serializeArray(), function(response){	
                if(response['success']===true){$('#Html103').html(response['html_103']);}
		else{alert("No se logro generar el Xml!");}
            },'json').fail(function(error) {alert("El Servidor ha fallado en responder!");});
        }
        generaHtml();
    </script>
</BODY>
</HTML>
<?php
/* Cerrado de las conexiones 
 */
// $obBD_con1->liberar();
// $obBD_conexion->cerrar();
?>