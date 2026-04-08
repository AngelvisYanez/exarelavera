<?php
/**
 * @abstract Permite realizar la baja de ventas
 * @author Cesar Bermeo
 * @version 2.0
 * Fecha de creaci�n 23-11-2018
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_not_ent_2.0.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_Datos_Vent;
//$obBD_con1->debugLogs(false);
$hoy = date("Y-m-d");
$hora = date("H:i:s");
$mes = date("m");

/**
 * Para saber si lleva contabilidad
 */
$configs = $obBD_con1->getRowConsulta('confi_fact.selectWhere', array('setWhere' => array('setEmpCod')), $obBD_conexion);
$obBD_con1->echoLog($configs);

/**
 * Busqueda de toas las ventas
 */
if (isset($searchAllVentas)) {
    $data=$_GET; $data['Emp_Cod']=$Ses_Emp_Cod;
    $responce=$obBD_con1->getPageGrid(3, $data, $obBD_conexion);
    if($responce['total']>0){
        foreach($responce['rows'] AS &$row){
            $row['Cpc_Edit']='S';
            $row['Cpc_Min']=0;
            if(!empty($row['Cpc_Cod'])){
                $Pagos1=$obBD_con1->getRowConsulta(57, $row['Cpc_Cod'].'*'.'A', $obBD_conexion);
                if($Pagos1['total']*1>0){
                    $row['Cpc_Det']='S'; //tiene pagos activos
                    $Pagos1=$obBD_con1->getRowConsulta(57, $row['Cpc_Cod'].'*'.'A'.'*'.'SUM', $obBD_conexion);
                    $row['Cpc_Min']=round($Pagos1['total']*1, 2);
                }
                $Pagos2=$obBD_con1->getRowConsulta(57, $row['Cpc_Cod'].'*'.'A', $obBD_conexion);
                if($Pagos2['total']*1>0) $row['Cpc_Edit']='N'; //tiene algun pago vinculado
            }
            if($configs['Cof_Con']=='S'&&!empty($row['Com_Cod'])){
                $cuentas = $obBD_con1->getRowConsulta(39, $row['Com_Cod'], $obBD_conexion);
                $row['Pld_Cod_Pag']=$cuentas['Pld_Cod'];
                $otras_comp = $obBD_con1->getRowConsulta(65, $row['Com_Cod'], $obBD_conexion);
                if($otras_comp['total']*1>1) $row['Com_Edit']='N';
            }

        }unset($row);
    }
    $obBD_con1->echoJson($responce);
}

/**
 * Actualizar el estado de un despacho
 */
if(isset($inactivar)){
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
    
    if($Tar_Validar){
        $obBD_con1->operacionobBD(4,array('Vet_Cod' => $Vet_Cod, 'Ses_Emp_Cod' => $Ses_Emp_Cod, 'Vet_Est' => 'I'),$obBD_conexion);
		//Tambien se debe anular el comprobante
		$obBD_con1->operacionobBD(5,array('Vet_Cod' => $Vet_Cod, 'Ses_Emp_Cod' => $Ses_Emp_Cod, 'Com_Est' => 'I'),$obBD_conexion);

    }
    if ($obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion))
    {
        $response['success'] = true;
        $response['message'] = "Transaccion exitosa";
    }else{ 
        $response['success'] = false; 
        $response['message'] = "No se ha logrado realizar la Transaccion";
    }
    $obBD_con1->echoJson($response);
    exit(); 
}

$periodos = $obBD_con1->getArrayConsulta('perio_cont.selectWhere', array('perio_cont.Pec_Est' => 'A', 'setWhere' => array('setEmpCod'), 'order' => 'perio_cont.Pec_Fei DESC'), $obBD_conexion);
?>
<!DOCTYPE html>
<HTML>

<HEAD>
	<TITLE>
		<?Php echo $Ses_Sys_Nom; ?>
	</TITLE>
	<link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.css">
	<link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
	<?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        <script src="../../framework/jquery/bootstrap/popover/jquery.flyout.js"></script>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
        <script></script>
        <style></style>
    </HEAD>
    <BODY>
        <div class="panel panel-main" id="formFinal">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Anular Despachos</h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="panels-area form-horizontal normal ">

                            <form id="frm_venta" name="frm_venta" class="form-horizontal normal" action="javascript:$('#tableVentas').Search('#frm_venta','searchAllVentas');">
                                <div class="row">
                                    <input name="fecha_inicio" type="hidden" value=" <?php echo $periodos[0]['Pec_Fei'] ?>" />
	<input name="fecha_fin" type="hidden" value=" <?php echo $periodos[0]['Pec_Fef'] ?> "
	/>
	<div class="col-xs-6">
		<fieldset class="exa-fieldset">
			<legend class="Titulos2">B&uacute;squeda de Ventas</legend>
			<div class="form-group">
				<label class="col-sm-2 control-label label-xs">Filtrar por:</label>
				<div class="col-sm-10 radioset">
					<input id="rad_ba1" name="op_opciones" type="radio" value="c" checked="" onclick="setfocus(this.form.search)" />
					<label for="rad_ba1">&nbsp;&nbsp;C&eacute;dula/RUC&nbsp;&nbsp;</label>
					<input id="rad_ba2" name="op_opciones" type="radio" value="b" onclick="setfocus(this.form.search)" />
					<label for="rad_ba2">&nbsp;&nbsp;Cliente&nbsp;&nbsp;</label>
					<input id="rad_ba3" name="op_opciones" type="radio" value="d" onclick="setfocus(this.form.search)" />
					<label for="rad_ba3">&nbsp;&nbsp;No. Documento&nbsp;&nbsp;</label>
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label label-xs">B&uacute;squeda:</label>
				<div class="col-sm-7">
					<div class="input-group">
						<input type="text" id="search" name="search" class="form-control input-xs" placeholder="Ingrese &iacute;ndice de b&uacute;squeda"
						 autofocus="">
						<span class="input-group-btn">
							<button id="btnSearch" onclick="this.form.submit()" class="btn btn-success btn-xs" type="button" title="Buscar Venta">
								<span class="glyphicon glyphicon-search"></span> Buscar</button>
						</span>
					</div>
				</div>
			</div>
		</fieldset>

	</div>
	<div class="col-xs-6">
		<fieldset class="exa-fieldset">
			<legend class="Titulos2">Filtros</legend>
			<div class="form-group">
				<label class="col-xs-3 control-label label-xs">Periodo:</label>
				<div class="col-xs-3">
					<select id="Pec_Cod" name="Pec_Cod" onchange="" class="form-control input-xs search_pec getData">

						<?php
                                                        foreach ($periodos as $p) {
                                                            echo "<option data--year='$p[Year]' data-inicio='$p[Pec_Fei]' data-fin='$p[Pec_Fef]' data--pec-cod='$p[Pec_Cod]' value='$p[Pec_Cod]'>Periodo $p[Year]</option>";
                                                        }
                                                        ?>
					</select>
				</div>
				<label class="col-xs-2 control-label label-xs">Mes:</label>
				<div class="col-xs-3">
					<select id="Cmb_Mes" name="Cmb_Mes" class="form-control input-xs search_pec">
						<option value="">
							<< TODOS>></option>
						<?Php for ($i = 1; $i <= 12; $i++) { ?>
						<option <?php if ($i==$mes) { echo "selected=''"; } ?> value="
							<?Php echo $i; ?>">
							<?php echo mes($i, 1); ?>
						</option>
						<?Php } ?>
					</select>
				</div>
			</div>
		</fieldset>
	</div>

	</div>

	</form>
	<div class="col-sm-12" style="min-height: 200px; padding-bottom: 5px;">
		<table id="tableVentas"></table>
		<div id="tableVentasPager"></div>
	</div>

	</div>
	</div>
	</div>
	</div>
	</div>
	<script src="../VALIDACIONES/fac_val_not_ent_2.0.js?k=112"></script>
	<script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
	<script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=2"></script>
	<script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
	<script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
	</BODY>

</HTML>