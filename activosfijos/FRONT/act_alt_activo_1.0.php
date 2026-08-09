<?php	
/**
* @abstract Permite realizar el registro de activo
* @author Jos� Ambulud�
* @version 1.0
* Fecha de creaci�n  2016-06-14
* @author Jos� Ambulud�
* Fecha de modificaci�n  2016-07-05
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/act_log_activo.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php'); 

/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Activo($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Activo;
/**
* Evita el reenvio 
*/
$thisPost = new Post_Block;

/*Secci�n para cargar datos en el Jqgrid referente a los productos con factura de compra y sin la misma*/
if(isset($productoCAjax)||isset($productoSAjax))
{
    $data=filter_input_array(INPUT_GET);
    if(isset($productoSAjax)){$data["Tipo"]="SFC";}else{$data["Tipo"]="CFC";}
    $data["Suc_Cod"]=$Ses_Suc_Cod;
    $responce['rows']=$obBD_con1->getArrayConsulta(622,$data,$obBD_conexion);
    echo json_encode($responce);
    exit();
}
/*Secci�n para extraer c�digo de proveedor=VARIOS EGRESOS, puesto que se registrar� un activo sin factura de compra*/
if(isset($compra_prov))
{
    $response["row"]=$obBD_con1->getRowConsulta(630,$Ses_Emp_Cod,$obBD_conexion);
    echo json_encode($response);
    exit();
}
/*Secci�n para cargar datos en el Jqgrid referente a los peritos*/
if(isset($peritoAjax)){ 
   $data=filter_input_array(INPUT_GET);
   $data["Emp_Cod"]=$Ses_Emp_Cod;   
   $contar = $obBD_con1->getRowConsulta(620, $data, $obBD_conexion);	      
   $pagination= pages($contar['total'], $page, $rows);
   $responce=$pagination['data'];
   $data["limits"]=$pagination['limits'];
   if($contar['total']>0)
   {$responce['rows'] =  $obBD_con1->getArrayConsulta(620, $data, $obBD_conexion);}
   echo json_encode($responce);exit();
}
/*Secci�n para listar el plan de cuentas*/
if(isset($cuentaAjax)){ 
    $contar = $obBD_con1->getRowConsulta(623, $search.'*'.$Ses_Emp_Cod.'*'.$Pec_Cod.'*'.$op_opciones.'*', $obBD_conexion);	      
    $pagination= pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    $responce['rows']=  $obBD_con1->getArrayConsulta(623, $search.'*'.$Ses_Emp_Cod.'*'.$Pec_Cod.'*'.$op_opciones.'*'.$pagination['limits'], $obBD_conexion);	    
    echo json_encode($responce);exit();
}
/*Secci�n para listar los campos pertenecientes a un tipo de activo*/
if(isset($buscarCampos)){ 
    $response = $obBD_con1->getArrayConsulta(616,$Tia_Cod, $obBD_conexion);
	utf8_encode_deep($response);
    echo json_encode($response);exit();
}

/*Secci�n para buscar el porcentaje de la categoria*/
if(isset($buscarPorcentaje)){ 
    $response = $obBD_con1->getRowConsulta(709,$Tia_Cod, $obBD_conexion);
    utf8_encode_deep($response);
    echo json_encode($response);exit();
}

/*Secci�n para buscar la cuenta del departamento*/
if(isset($buscarCuentaDep)){ 
    $response = $obBD_con1->getRowConsulta(708,$Dep_Cod, $obBD_conexion);
    utf8_encode_deep($response);
    echo json_encode($response);
    exit();
}

/*Lista los tipos de activos existentes*/
if(isset($tipoactivAjax)){ 
    $responce = $obBD_con1->getArrayConsulta(608,$Ses_Emp_Cod."*", $obBD_conexion);
    echo json_encode($responce);exit();
}

/*Secci�n ajax para guardar un nuevo activo*/
if(isset($uploadfoto))
{ 
    $cont=1;$aux=0;
    $codigos='';

    /*Secci�n para guaradar los datos en la tabla activo*/
    $responce['success']=false;$responce['message']="No se ha logrado realizar la Transaccion"; 
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
    if($Pri_Cod==''){
        $row_Pri_Cod=$obBD_con1->getRowConsulta(627,'', $obBD_conexion);
        $Pri_Cod=$row_Pri_Cod['Pri_Cod'];
    }

    $cop_can = explode("*",$Cop_Int);

    //SECCION PARA CREAR UNA NUEVA ASIGNACION EN EL REGISTRO 
        $mayor = $obBD_con1->getRowConsulta(641, "", $obBD_conexion);
        $nuevo_Aca_Num = $mayor['Aca_Num'] + 1;
        //Insert en la tabla acta_activo
        $obBD_con1->operacionobBD(642, $nuevo_Aca_Num . '*' . $Act_Fec . '*' . date("H:i:s"), $obBD_conexion);
        //Se obtiene el Aca_Cod de la tabla acta_activo
        $Aca_Cod = $obBD_con1->insercionid($obBD_conexion->conexion);

        $asignacion = array(
                                "Asg_Cod"=>$Are_Cod, 
                                "Asg_Typ"=>"D", 
                                "Aca_Cod"=>$Aca_Cod, 
                                "Asg_Fec"=>$Act_Fec, 
                                "Asg_Hor"=>date("H:i:s"),
                                "Asg_Fas"=>$Act_Fec,
                                "Asg_Raz"=>"Primera asignacion correspondiente al registro del activo fijo", 
                                "Asg_Con"=>"C", 
                            );
    //FIN DE SECCION PARA CREAR UNA NUEVA ASIGNACION

    for($i=0;$i<$Act_Can;$i++)
    {  

        $obBD_con1->operacionobBD(601, $Tia_Cod.'*'.$Pri_Cod.'*'.$Ses_Suc_Cod.'*'.$Est_Cod.'*'.$Prv_Cod.'*'.$Act_Des.'*'.$Act_Obs.'*'.$Act_Cdc.'*1*'.$Act_Bar.'*'.$Act_Gen.'*'.$Act_Val.'*'.$Act_Pde.'*'.$Act_Res.'*'.$Act_Ann.'*'.$Act_Fec.'*'.$Act_Ffd.'*'.$Act_Gar.'*'.$ruta.'*'.$Act_Dac, $obBD_conexion);

        /* Secci�n para actualizar el campo de foto del activo primero obtenemos el �ltimo c�digo de activo*/
        $Act_Cod = $obBD_con1->insercionid($obBD_conexion->conexion);
        $codigos=$Act_Cod."*".$codigos;

        /* Secci�n para registrar una ASIGNACION del activo */
        $asignacion["Act_Cod"] = $Act_Cod;
        $obBD_con1->operacionobBD(712, $asignacion, $obBD_conexion);

        /* Secci�n para insertar datos en la tabla det_activo*/
        if($Tia_Cod>0){
            $campos = $obBD_con1->getArrayConsulta(616,$Tia_Cod, $obBD_conexion);
            foreach($campos as $valor)
            {
                $obBD_con1->operacionobBD(617, $Act_Cod.'*'.$valor['Cam_Cod'].'*'.$_POST[$valor['Cam_Cod']], $obBD_conexion);
            }
        }

	   /* Genera el Codigo de Barra se nececitan 12 caracteres para generar*/
        $Act_Var='';
        $Act_Gen='';
        if($Act_Bar1==1)
        {
            $Act_Var = str_pad($Act_Cod, 12, '0', STR_PAD_RIGHT);
            $Act_Bar=$Act_Var;
            $Act_Gen='G';

            //Update para agregar el c�digo de barras
            $obBD_con1->operacionobBD(603, $Act_Cod.'*'.$Act_Bar.'*'.$Act_Gen, $obBD_conexion);
        }

        //Insert en la tabla activo_compra
        if($Cop_Int==""){
            $obBD_con1->operacionobBD(625, $Act_Cod.'*'.$Cop_Cod.'*'.$Cop_Int.'*'.$Pro_Cod, $obBD_conexion);
        }
        else
        {
            if($cop_can[$aux]!=""){
            $cop_int=explode("/",$cop_can[$aux]);
            $obBD_con1->operacionobBD(625, $Act_Cod.'*'.$Cop_Cod.'*'.$cop_int[0].'*'.$Pro_Cod, $obBD_conexion);
            if($cont==$cop_int[1]){$aux++;$cont=1;}else{$cont++;}
            }
        }
        //Insert dentro de la tabla activo_ccontable
        $obBD_con1->operacionobBD(626, $Act_Cod.'*'.$Cod_Dep.'*DE', $obBD_conexion);
        $obBD_con1->operacionobBD(626, $Act_Cod.'*'.$Cod_Dea.'*DA', $obBD_conexion);
    }

    $codigos = substr($codigos, 0, -1);
    $responce['Act_Cod']=$codigos;

    //<<<<<<<<<<<<<<<<INGRESO DE LAS FOTOS>>>>>>>>>>>>>>>>>
        $carpeta = "../../imagenes/".$Ses_Emp_Cod.'/Activos';
        if (!file_exists($carpeta)) {
            mkdir($carpeta, 0777, true);
        }
        $archivo = $_FILES['file5']['name'];
        $nombre=explode('.',$archivo);  
        $last=count($nombre)-1;
        if($nombre[$last]!="")
        {
            $act_cod=explode("*",$Act_Cod);
            foreach($act_cod as $row){
                $ruta="";
                $row = trim($row);
                $ruta='img_activo_'.$row.'_'.$file_id.'.'.$nombre[$last];
                copy($_FILES['file5']['tmp_name'],$carpeta.'\\'.$ruta);
                $obBD_con1->operacionobBD(602, $row.'*'.$ruta, $obBD_conexion);
            }
        }
    //<<<<<<<<<<FIN DE INGRESO DE LAS FOTOS>>>>>>>>>

    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
    if($obBD_con1->Error==0){ $responce['success']=true; }else{$responce['message']=$obBD_con1->MsgError;}  
    echo json_encode($responce);
    exit();
}

 $periodoContable =$obBD_con1->getRowConsulta(705, $Ses_Emp_Cod, $obBD_conexion);

?>

<!DOCTYPE html>
<HTML>
    <HEAD>		
        <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
        <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
        <link href="../../framework/jquery/bootstrap/bootstrap-fileinput/css/fileinput.css" media="all" rel="stylesheet" type="text/css" />
        <link rel="stylesheet" href="../../framework/jquery/jquery.jstree/themes/default/style.min.css" />
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>
        <script src="../../framework/jquery/jquery.jstree/jstree.min.js"></script>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
        <script src="../../framework/jquery/bootstrap/bootstrap-fileinput/js/fileinput.js" type="text/javascript"></script>
        <style type="text/css" media="screen">
            th.ui-th-column div{
                white-space:normal !important;
                height:auto !important;
                padding:2px;
            }
        </style>
    </HEAD>
<BODY>
    <div class="panel panel-main">
    	<div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Registrar Activos</h3></div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="row">
                <div class="col-sm-12">
                    <form id="formInfo" class="form-horizontal normal">
                    <fieldset class="exa-fieldset">
                        <legend class="Titulos2">Informaci&oacute;n General</legend>
                        <div class="form-group Titulos2">
                            <div class="col-sm-12"><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.<hr/></div>
                        </div>
                        <div class="col-sm-8 col-md-7">
                            <div class="form-group">
                                <label class="col-md-3 control-label label-xs" for="Ite_Lar">Producto:</label>
                                <div class="col-md-9">
                                    <div class="input-group input-group-xs">
                                        <input type="text" name="Ite_Lar" id="Ite_Lar" class="form-control input-xs" placeholder="Seleccione producto" readonly>
                                        <span class="input-group-btn">
                                            <button class="btn btn-success" onclick="$('#productoCDialog').dialog('open');" type="button"><span class="glyphicon glyphicon-zoom-in" title="Productos con Factura"></span></button>
                                            <button class="btn btn-success" onclick="$('#productoSDialog').dialog('open');" type="button"><span class="glyphicon glyphicon-zoom-out" title="Productos sin Factura"></span></button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="div_proveedor" class="form-group">
                                <label class="col-md-3 control-label label-xs" for="Prv_Nom">Proveedor:</label>  
                                <div class="col-md-9">                                    
                                    <input id="Prv_Nom" name="Prv_Nom" type="text" class="form-control input-xs" readonly />
                                </div>                                  
                            </div>
                            
                            <div id="div_fcompra" class="form-group">
                                <label class="col-md-3 control-label label-xs" for="Cop_Num">Factura Compra:</label>
                                <div class="col-md-9">
                                    <input type="text" name="Cop_Num" id="Cop_Num" class="form-control input-xs" readonly>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-md-3 control-label label-xs" for="cuenta">Cuenta Contable:</label>
                                <div class="col-md-9">
                                    <input type="text" name="cuenta" id="cuenta" class="form-control input-xs" readonly>
                                </div>
                            </div>
                            
                            <div id="div_scontable" class="form-group">
                                <label class="col-md-3 control-label label-xs" for="Cop_Num">Sustento Compra:</label>
                                <div class="col-md-9">
                                    <textarea name="Tri_Des" id="Tri_Des" class="form-control input-xs" readonly=""></textarea>
                                </div>
                            </div>
                            
                            <div id="div_nofaccompra" class="form-group" style="display: none;">
                                <div class="col-md-3"></div>
                                <div class="col-md-9">
                                    <div class="alert alert-info">
                                        <span style="font-size: 12px;"><strong><u><span class="glyphicon glyphicon-info-sign"></span> NOTA:</u></strong> El producto no consta dentro de una factura de compra, por tal motivo se debe indicar una <i>Fecha de Inicio de Depreciaci&oacute;n</i> y un <i>Costo</i>.</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-sm-4 col-md-5">
                            <div id="div_fadquisicion" class="form-group">
                                <label class="col-md-5 control-label label-xs" for="Cop_Fec1">Fecha de Adquisici&oacute;n:</label>
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <span class="input-group-addon input-xs"><span class="glyphicon glyphicon-calendar"></span></span>
                                        <input type="text" name="Cop_Fec1" id="Cop_Fec1" class="form-control input-xs" readonly>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-md-5 control-label label-xs" for="Cop_Fec">Fecha Inicio Depreciaci�n:</label>
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <span class="input-group-addon input-xs"><span class="glyphicon glyphicon-calendar"></span></span>
                                        <input type="text" name="Cop_Fec" id="Cop_Fec" class="form-control input-xs" readonly>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-md-5 control-label label-xs" for="Cop_Pru">I.V.A. al Costo:</label>
                                <div class="col-md-4">
                                    <input type="text" name="Iva_Cos" id="Iva_Cos" class="form-control input-xs" readonly="">
                                    <select name="Iva_Cos_SFC" id="Iva_Cos_SFC" class="form-control input-xs" style="display: none;">
                                        <option value="N">NO</option>
                                        <option value="S">SI</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-md-5 control-label label-xs" for="Cop_Pru">Costo:</label>
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <span class="input-group-addon input-xs"><span class="glyphicon glyphicon-usd"></span></span>
                                        <input type="text" name="Cop_Pru" id="Cop_Pru" class="form-control input-xs" style="text-align: right;">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label id="porcentaje" class="col-md-5 control-label label-xs" for="Iva_Por">I.V.A.:</label>
                                <input type="hidden" name="iva" id="iva">
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <span class="input-group-addon input-xs"><span class="glyphicon glyphicon-usd"></span></span>
                                        <input type="text" name="Iva_Por" id="Iva_Por" class="form-control input-xs" readonly style="text-align: right;">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-md-5 control-label label-xs" for="subtotal">Total:</label>
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <span class="input-group-addon input-xs"><span class="glyphicon glyphicon-usd"></span></span>
                                        <input type="text" name="subtotal" id="subtotal" class="form-control input-xs" readonly style="text-align: right;">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-md-5 control-label label-xs" for="total">Valor a Depreciar:</label>
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <span class="input-group-addon input-xs"><span class="glyphicon glyphicon-usd"></span></span>
                                        <input type="text" name="total" id="total" class="form-control input-xs" readonly style="font-size: 18px; font-weight: bold; text-align: right;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                    </form>
                </div>
            </div>
            <div class="row">
                <div class="col-xs-12">
                    <div id="tabs" class="ui-tab-fix">
                        <ul style="font-size: 12px;">
                            <li><a href="#info_activo">Activo</a></li>
                            <li><a href="#detalle_activo">Detalle</a></li>
                            <li><a href="#cuenta_contable">Cuenta Contable</a></li>
                            <li><a href="#imagenes">Im&aacute;gen</a></li>
                            <li><a href="#depreciacion_activo">Depreciaci&oacute;n</a></li>
                        </ul>
                        <form id="formActivo" class="form-horizontal normal" enctype="multipart/form-data" action="javascript:saveForm();">
                        <div id="info_activo" style="min-height: 350px;">
                            <!-- Secci�n para ingresar los datos de registro del activo -->
                            <div class="row">
                            <div class="col-sm-12">
                                <fieldset class="exa-fieldset">                           
                                    <legend class="Titulos2">Formulario de Registro</legend>
                                    <!-- C�digo de Activo necesario para guardar las imagenes -->
                                    <input type="hidden" name="Act_Cod" id="Act_Cod" value="" />
                                    <!-- C�digo del Perito almacenado o asignado a la variable oculta Pri_Cod -->
                                    <input type="hidden" name="Pri_Cod" id="Pri_Cod" value=""/>
                                    <!-- C�digo del proveedor -->
                                    <input type="hidden" name="Prv_Cod" id="Prv_Cod" value=""/>
                                    <!-- C�digo de la factura de compra -->
                                    <input type="hidden" name="Cop_Cod" id="Cop_Cod" value=""/>
                                    <!-- C�digo del detalla de la factura de compra -->
                                    <input type="hidden" name="Cop_Int" id="Cop_Int" value=""/>
                                    <!-- C�digo de la cuenta depreciacion -->
                                    <input type="hidden" name="Cod_Dep" id="Cod_Dep" value=""/>
                                    <!-- C�digo de la cuenta depreciacion acumulada -->
                                    <input type="hidden" name="Cod_Dea" id="Cod_Dea" value=""/>
                                    <!-- C�digo del producto -->
                                    <input type="hidden" name="Pro_Cod" id="Pro_Cod" value=""/>
                                    <!-- Campo para guardar la fecha final de depreciaci�n -->
                                    <input type="hidden" name="Act_Ffd" id="Act_Ffd" value=""/>
                                            
                                    <div class="col-sm-8 col-md-7">
                                        <!-- Input group -->
                                        <div class="form-group">
                                            <label class="col-md-3 control-label label-xs" for="perito">Perito:</label>  
                                            <div class="col-md-9">                                    
                                                <div class="input-group input-group-xs">                                                  
                                                    <input id="perito" name="perito" type="text" class="form-control" placeholder="Seleccione un perito" readonly />
                                                    <span class="input-group-btn">
                                                        <button class="btn btn-success" onclick="$('#peritoDialog').dialog('open');" type="button"><span class="glyphicon glyphicon-search" title="Buscar perito"></span></button>
                                                        <button type="button" class="btn btn-success" onclick="$('#perito').val('');$('#Pri_Cod').val('');"><span class="glyphicon glyphicon-eject" title="Limpiar campo"></span></button>
                                                    </span>
                                                </div>                          
                                            </div>                                  
                                        </div>
                                        <!-- Select b�sico-->
                                        <div class="form-group">
                                            <label class="col-md-3 control-label label-xs required" for="des_padre">Categor&iacute;a:</label>  
                                            <div class="col-md-9">
                                                <?php $row_rs_tia_tip = $obBD_con1->getArrayConsulta(615, $Ses_Emp_Cod, $obBD_conexion);?>
                                                <select name="Tia_Cod" id="Tia_Cod" class="form-control input-xs required" data-placeholder="Seleccione una categor&iacute;a de activo">
                                                    <option value=""></option>
                                                    <?Php                                         
                                                    foreach($row_rs_tia_tip as $row)
                                                        { ?>
                                                        <optgroup label="<?Php echo mb_convert_encoding($row['descripcion'], 'ISO-8859-1', 'UTF-8'); ?>">
                                                            <?php $row_rs_tia_det = $obBD_con1->getArrayConsulta(645, $row['Tia_Cod'], $obBD_conexion);?>
                                                            <?Php                                         
                                                            foreach($row_rs_tia_det as $rows)
                                                            { ?>
                                                            <option value="<?php echo $rows['Tia_Cod'];?>"><?Php echo mb_convert_encoding($rows['descripcion'], 'ISO-8859-1', 'UTF-8'); ?></option>
                                                            <?Php } ?>
                                                        </optgroup>
                                                    <?Php } ?>
                                                </select>
                                            </div>
                                        </div>

                                         <!-- Select b�sico DEPARTAMENTOS-->
                                        <div class="form-group">
                                            <label class="col-md-3 control-label label-xs required" for="des_padre">Departamento:</label>  
                                            <div class="col-md-9">
                                                <!-- consultar las areas -->
                                                <?php $areas = $obBD_con1->getArrayConsulta(706, $Ses_Emp_Cod, $obBD_conexion);?>
                                                <select name="Are_Cod" id="Are_Cod" class="form-control input-xs required" data-placeholder="Seleccione una departamento para el activo">
                                                    <option value=""></option>
                                                    <?Php foreach($areas as $area)
                                                        { ?>
                                                        <optgroup label="<?Php echo mb_convert_encoding($area['Are_Des'], 'ISO-8859-1', 'UTF-8'); ?>">

                                                            <!-- Consultar departamentos de las areas -->
                                                            <?php $departamentos = $obBD_con1->getArrayConsulta(707, array('Are_Cod' => $area['Are_Cod'], 'Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);?>
                                                            <?Php                                         
                                                            foreach($departamentos as $departamento)
                                                            { ?>
                                                            <option value="<?php echo $departamento['Dep_Cod'];?>"><?Php echo mb_convert_encoding($departamento['Dep_Des'], 'ISO-8859-1', 'UTF-8'); ?></option>
                                                            <?Php } ?>
                                                        </optgroup>
                                                    <?Php } ?>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Text input-->
                                        <div class="form-group">
                                            <label class="col-md-3 control-label label-xs required" for="des_padre">C�d. Barras:</label>  
                                            <div class="col-md-7">
                                                <div class="input-group">
                                                    <input id="Act_Bar" name="Act_Bar" type="text" placeholder="" class="form-control input-sm" value="" required />
                                                    <span class="input-group-addon input-sm">
                                                        <input name="Act_Bar1" type="checkbox" id="Act_Bar1"  value="1" checked>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Muestra informaci�n cuando esta seleccionado el checkbox -->
                                        <div class="form-group">
                                            <label class="col-md-3 control-label label-xs"></label>
                                            <div id="mensaje_codigo" class="col-md-8" style="font-size:11px; font-weight:bold;">Generar c&oacute;digo autom&aacute;ticamente <span class="glyphicon glyphicon-ok"></span></div>
                                        </div>
                                        <!-- Textarea -->
                                        <div class="form-group">
                                            <label class="col-md-3 control-label required" for="Act_Des">Descripci�n:</label>
                                            <div class="col-md-9">                     
                                                <textarea class="form-control input-xs" id="Act_Des" name="Act_Des" required></textarea>
                                            </div>
                                        </div>
                                        <!-- Textarea -->
                                        <div class="form-group">
                                            <label class="col-md-3 control-label" for="Act_Obs">Observaci�n:</label>
                                            <div class="col-md-9">                     
                                                <textarea class="form-control input-xs" id="Act_Obs" name="Act_Obs"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                            
                                    <div class="col-sm-4 col-md-5">

                                        <div class="form-group">
                                            <label class="col-md-5 control-label label-xs required" for="Act_Dac">Depre. Acum.:</label>
                                            <div class="col-md-5">
                                                <div class="input-group">
                                                    <span class="input-group-addon input-xs"><span class="glyphicon glyphicon-usd"></span></span>
                                                    <input type="text" name="Act_Dac" id="Act_Dac" title="Valor de depreciacion acumulada anterior" class="form-control input-xs" required style="text-align: right;" onkeypress="return validar_decimal(event);">
                                                </div>
                                            </div>
                                        </div>  

                                        <div class="form-group">
                                            <label class="col-md-5 control-label label-xs">% Categor&iacute;a:</label>
                                            <div class="col-sm-5 radioset">
                                                <input id="rad_si" name="Cfg_Por" type="radio" value="S" checked="" /><label for="rad_si">&nbsp;&nbsp;SI&nbsp;&nbsp;</label>
                                                <input id="rad_no" name="Cfg_Por" type="radio" value="N"/><label for="rad_no">&nbsp;&nbsp;NO&nbsp;&nbsp;</label>
                                            </div>
                                        </div>


                                        <!-- Text input -->
                                        <div class="form-group">
                                            <label class="col-md-5 control-label label-xs required" for="Act_Ann">Vida &Uacute;til (a�os):</label>
                                            <div class="col-md-5">
                                                <input type="text" name="Act_Ann" id="Act_Ann" class="form-control input-xs" required style="text-align: right;" onkeypress="return validar_numeric(event);">
                                            </div>
                                        </div>
                                        <!-- Text input -->
                                        <div class="form-group">
                                            <label class="col-md-5 control-label label-xs required" for="Act_Res">Valor Residual:</label>
                                            <div class="col-md-5">
                                                <div class="input-group">
                                                    <span class="input-group-addon input-xs"><span class="glyphicon glyphicon-usd"></span></span>
                                                    <input type="text" name="Act_Res" id="Act_Res" class="form-control input-xs" required style="text-align: right;" onkeypress="return validar_numeric(event);">
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Text input -->
                                        <div class="form-group">
                                            <label class="col-md-5 control-label label-xs required" for="Act_Pde">Valor Residual:</label>
                                            <div class="col-md-5">
                                                <div class="input-group">
                                                    <span class="input-group-addon input-xs"><b>%</b></span>
                                                    <input type="text" name="Act_Pde" id="Act_Pde" class="form-control input-xs" style="text-align: right;" onkeypress="return validar_numeric(event);">
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Text input-->
                                        <div class="form-group">
                                            <label class="col-md-5 control-label label-xs required" for="Act_Cdc">C�digo Secuencia:</label>  
                                            <div class="col-md-5">
                                                <input id="Act_Cdc" name="Act_Cdc" type="text" placeholder="" class="form-control input-xs" value="" style="text-align: right;" required />
                                            </div>
                                        </div>
                                        <!-- Text input-->
                                        <div class="form-group">
                                            <label class="col-md-5 control-label label-xs required" for="Act_Can">Cantidad:</label>  
                                            <div class="col-md-5">
                                                <input id="Act_Can" name="Act_Can" type="text" placeholder="" class="form-control input-xs" value="" style="text-align: right;" onkeypress="return validar_numeric(event);" required readonly="" title="No Editable"/>
                                            </div>
                                        </div>
                                        <!-- Text input-->
                                        <div class="form-group">
                                            <label class="col-md-5 control-label label-xs required" for="Act_Gar">Garant�a (meses):</label>  
                                            <div class="col-md-5">
                                                <input id="Act_Gar" name="Act_Gar" type="text" placeholder="" class="form-control input-xs" value="" style="text-align: right;" onkeypress="return validar_numeric(event);" required/>
                                            </div>
                                        </div>
                                        <!-- Select Basic -->
                                        <div class="form-group">
                                            <label class="col-md-5 control-label label-xs required" for="Est_Cod">Estado:</label>
                                            <div class="col-md-5">
                                                <select name="Est_Cod" id="Est_Cod" class="form-control input-xs" required >
                                                    <option value="">Seleccione</option>
                                                    <?Php 
                                                    $rs_estados = $obBD_con1->getArrayConsulta(612,$Ses_Emp_Cod, $obBD_conexion);                               
                                                    if (count($rs_estados) > 0)
                                                    {
                                                        foreach($rs_estados as $row){
                                                        ?>
                                                            <option value="<?Php echo $row['Est_Cod']; ?>"><?Php echo $row['Est_Des']; ?></option>	
                                                        <?php		
                                                        }
                                                    }                              
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>      
                                </fieldset>
                                <!-- Boton para efectuar el guardado -->
                                <div class="form-group">
                                    <div class="col-sm-8">
                                        <button type="submit" name="btguardaractivo" id="btguardaractivo"  class="btn btn-primary btn-sm"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
                                    </div>
                                </div>
                            </div>
                            </div>
                            <!-- Fin de la secci�n para registrar los activos -->
                        </div>
                        
                        <div id="imagenes" style="min-height: 350px;">
                            <div class="row">   
                                <!-- Secci�n para seleccionar las foto(s) correspondientes a activos -->
                                <div class="col-xs-12">
                                    <fieldset class="exa-fieldset">                           
                                        <legend class="Titulos2">Foto</legend>
                                        <input id="file5" name="file5" class="file" type="file" multiple data-preview-file-type="any" />
                                    </fieldset>
                                </div>
                            </div>
                        </div>
                        
                        <div id="depreciacion_activo" style="min-height: 350px;">
                            <div class="row">
                                <div class="col-md-12">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">C&aacute;lculo Proyecci&oacute;n Depreciaci&oacute;n</legend>
                                    <div id="deprecia" style="display: none;">
                                    <div class="form-group">
                                        <label class="control-label label-sm required" for="tipo_dep">Depreciaci&oacute;n:</label>  
                                        <select id="tipo_dep" name="tipo_dep" class="form-control input-sm" style="text-align: center; display: inline-block; width: auto;">
                                            <option value="A">Anual</option>
                                            <option value="M">Mensual</option>
                                        </select>
                                        <button type="button" class="btn btn-sm btn-success" onclick="calcula_depreciacion();"><span class="glyphicon glyphicon-th"></span> Calcular</button>
                                    </div>
                                    <div class="row">
                                        <div id="d_a" class="col-sm-8 col-md-6">
                                            <table id="dep_anual"></table>
                                            <div id="list_da_Pager"></div>
                                            <div style="padding-top: 10px; padding-bottom: 0px;">
                                                <button type="button" onclick="$('#dep_anual').jqGrid('exportGridExcel',{nombre:'Depreciaci&oacute;n Anual',hoja:'Dep. Anual',caption:true});" class="btn btn-sm btn-primary start" title="Descargar archivo de Excel"> <i class="glyphicon glyphicon-download-alt"></i> <span>Excel</span></button>
                                            </div>
                                        </div>
                                        <div id="d_m" class="col-sm-8 col-md-6" style="display: none;">
                                            <table id="dep_mensual"></table>
                                            <div id="list_dm_Pager"></div>
                                            <div style="padding-top: 10px; padding-bottom: 0px;">    
                                                <button type="button" onclick="$('#dep_mensual').jqGrid('exportGridExcel',{nombre:'Depreciaci&oacute;n Mensual',hoja:'Dep. Mensual',caption:true});" class="btn btn-sm btn-primary start" title="Descargar archivo de Excel"> <i class="glyphicon glyphicon-download-alt"></i> <span>Excel</span></button>
                                            </div>
                                        </div>
                                        <div class="col-sm-4 col-md-6">
                                            <fieldset class="exa-fieldset">
                                                <legend class="Titulos2">Resultados Depreciaci&oacute;n</legend>
                                                <div class="form-group">
                                                    <label class="control-label col-md-6 col-lg-5 label-xs">Valor del Activo:</label>
                                                    <div class="col-md-4 col-lg-4">
                                                        <div class="input-group">
                                                            <span class="input-group-addon input-xs">$</span>
                                                            <input type="text" id="valor_activo" name="valor_activo" class="form-control input-xs" style="text-align: right;" readonly="">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-6 col-lg-5 label-xs">Valor Residual:</label>
                                                    <div class="col-md-4 col-lg-4">
                                                        <div class="input-group">
                                                            <span class="input-group-addon input-xs">$</span>
                                                            <input type="text" id="valor_residual" name="valor_residual" class="form-control input-xs" style="text-align: right;" readonly="">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-6 col-lg-5 label-xs">Depreciaci&oacute;n Acumulada:</label>
                                                    <div class="col-md-4 col-lg-4">
                                                        <div class="input-group">
                                                            <span class="input-group-addon input-xs">$</span>
                                                            <input type="text" id="depreciacion_acumulada" name="depreciacion_acumulada" class="form-control input-xs" style="text-align: right;" readonly="">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-6 col-lg-5 label-xs">Valor en Libros:</label>
                                                    <div class="col-md-4 col-lg-4">
                                                        <div class="input-group">
                                                            <span class="input-group-addon input-xs">$</span>
                                                            <input type="text" id="valor_libros" name="valor_libros" class="form-control input-xs" style="text-align: right;" readonly="">
                                                        </div>
                                                    </div>
                                                </div>
                                            </fieldset>
                                        </div>
                                    </div>
                                    </div>
                                    <div id="deprecia_mensaje">
                                        <div class="col-sm-4"></div>
                                        <div class="col-sm-4">
                                            <div class="alert alert-info" id="success-alert">
                                                <u><span class="glyphicon glyphicon-info-sign"></span><strong> NOTA: </strong></u>Debe ingresar la vida &uacute;til y valor residual del activo..!! 
                                            </div>
                                        </div>
                                    </div>
                                </fieldset>
                                </div>
                            </div>
                        </div>
                        </form>
                        
                        <div id="detalle_activo" style="min-height: 350px;">
                            <form id="formcampos" name="formcampos" class="form-horizontal normal" action="javascript:">
                                <!-- Secci�n para ingresar los datos de registro del activo -->
                                <div class="row">   
                                    <!-- Secci�n para presentar los campos del tipo de activo seleccionado -->
                                    <div class="col-xs-12">
                                        <fieldset class="exa-fieldset">                           
                                            <legend class="Titulos2">Campos de Tipo de Activo</legend>
                                            <div id="campos_nuevos" class="col-sm-12"></div>
                                        </fieldset>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <div id="cuenta_contable" style="min-height: 350px;">
                            <form id="formcuenta_Contable" name="formcuenta_Contable" class="form-horizontal normal" action="javascript:">
                                <!-- Secci�n para seleccionar las cuentas contables de depreciaci�n y depreciaci�n acumulada -->
                                <div class="row">
                                    <div class="col-sm-6">
                                        <fieldset class="exa-fieldset">                           
                                            <legend class="Titulos2">Asignaci&oacute;n de Cuenta Contable</legend>

                                            <div style="padding-bottom: 5px">
                                                <table id="depreciacion"></table>
                                                <div id="depreciacionPager"></div>
                                            </div>
                                            <div style="padding-bottom: 5px">                                           
                                            </div>

                                            <div style="padding-bottom: 5px">
                                                <table id="depreciacion_acum"></table>
                                                <div id="depreciacion_acumPager"></div>
                                            </div>
                                            <button id="btnDepAcum" name="btnDepAcum" onclick="tipo='DA';$('#cuentaDialog').dialog('open');" title="Buscar Cuenta" type="button" class="btn btn-success btn-xs"><i class="glyphicon glyphicon-list"></i><span> Seleccionar Cuenta</span></button>
                                        </fieldset>
                                    </div>
                                    
                                    <div class="col-sm-6">
                                        <div class="alert alert-info" id="alert_cc" style="display: none;">
                                            <span style="font-size: 12px;"><u><span class="glyphicon glyphicon-info-sign"></span> <strong>NOTA:</strong></u> Debe elegir una cuenta de depreciaci&oacute;n y depreciaci&oacute;n acumulada..!! </span>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Inicio del di�logo para buscar un producto en la tabla de compras -->
    <div id="productoCDialog" title="B&uacute;squeda de Productos con Factura de Compra">
        <form class="form-horizontal normal"></form>
    </div>
    
    <!-- Inicio del di�logo para buscar un productos que no se encuentran en una factura de compra -->
    <div id="productoSDialog" title="B&uacute;squeda de Productos sin Factura de Compra">
        <form class="form-horizontal normal"></form>
    </div>
    
    <!-- Inicio del di�logo para buscar un perito --> 
    <div id="peritoDialog" title="B&uacute;squeda de Perito">  
        <form class="form-horizontal normal"></form>    
    </div>
    
    <!-- Inicio del di�logo para buscar una cuenta contable -->
    <div id="cuentaDialog" title="B&uacute;squeda de Cuenta Contable">
        <form class="form-horizontal normal">
            <fieldset class="exa-fieldset">
                <legend class="Titulos2">Filtros</legend>
                <div class="form-group">
                    <label class="col-xs-2 control-label label-xs">Filtrar Por:</label>
                    <div class="col-xs-5 radioset">
                        <input id="rad7" name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)"/><label for="rad7">&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;</label>
                        <input id="rad8" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)"/><label for="rad8">&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;</label>
                    </div>
                    <div class="col-xs-4">
                        <label class="control-label label-xs">Plan de Cuentas:</label>
                        <input name="periodo" id="periodo" type="text" size="6" readonly style="text-align: center;display: inline-block;width: auto;" class="form-control input-xs" value=""/>
                        <input name="Pec_Cod" id="Pec_Cod" type="hidden" /> 
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-xs-2 control-label label-xs">B&uacute;squeda:</label>
                    <div class="col-xs-7">
                        <div class="input-group">
                            <input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese cuenta a buscar..." autofocus  class="form-control input-sm "/>
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-success btn-sm" onclick="this.form.submit()" title="Buscar Cuenta"><span class="glyphicon glyphicon-search"></span> <span> Buscar</span></button>
                            </span>
                        </div>                        
                    </div>
                </div>
            </fieldset>
        </form>
    </div>
    
    <script type="text/javascript">
    $('#btguardaractivo').prop('disabled',true);
    $('#Act_Ann').attr('readonly',true);
    $('#Act_Ann').attr('title','Valor autom�tico por categor�a');

    //Secci�n para asignar la funci�n de tabs a trav�s de jquery
    $("#tabs").tabs();
    $('#tabs').tabs({disabled:[0,1,2,3,4]});
    //Secci�n para declarar datepicker
    $.createDatePickers("#Cop_Fec");
    $("#Cop_Fec").val('');
    //Secci�n para calcular el porcentaje de depreciaci�n
    $(document).ready(function(){

        $('#rad_no').change(function(){
                    $('#Act_Ann').attr('readonly',false);
                    $('#Act_Ann').removeAttr('title');
                });

        $('#rad_si').change(function(){
                    $('#Act_Ann').attr('readonly',true);
                    $('#Act_Ann').attr('title','Valor autom�tico por categor�a');
        });


        $('#Act_Res').on('input',function(){
            var vre=$("#Act_Res").val();
            var vco=$("#total").val();
            var numerador=vre*100;
            var porcentaje=numerador/vco;
            $("#Act_Pde").val(porcentaje.toFixed(2));
        });
        
        $("#Act_Pde").on('input',function(){
            var vco=$("#total").val();
            var vde=$("#Act_Pde").val();
            var vre=(vco*vde)/100;
            $("#Act_Res").val(vre.toFixed(2));
        });
        
        $('#Act_Can').keyup(function(){
            if($('#Act_Can').val()>1){
                $.alert('Al ingresar una cantidad mayor a uno se asume que el conjunto de activos son de iguales caracter&iacute;sticas..!!');
            }
        });
    });
        
    $(document).ready(function(){
        //Asignamos la opci�n de datepicker
        $.createDateRange('#Act_Fec');
        //Asignamos la funci�n de Chosen
        $("#Tia_Cod").createChosen('input-xs',{allow_single_deselect: true});
        $("#Are_Cod").createChosen('input-xs',{allow_single_deselect: true});                 
    });
	
    //Seccion para cargar campos segun el tipo de activo seleccionado
    $(document).ready(function()
    {
        /*Secci�n para extraer los campos de un tipo de activo*/
        $('#Tia_Cod').on('change', function() {
            var id=this.value; 
            var codigo={Tia_Cod:id,buscarCampos:true};
            $.post('<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',codigo, function( response ){

                if($('#rad_si').is(':checked')){
                    var porcentaje={Tia_Cod:id,buscarPorcentaje:true};
                    $.get('<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',porcentaje, function(response){
                        if(response){
                            var anios = Math.round(100/parseFloat(response['Apr_Por']));
                            $("#Act_Ann").val(anios);
                        }
                        else{
                            $("#Act_Ann").val('');
                        }
                    },'json');
                }
                campos_nuevos=response;
                addcampos();
            },'json');
        });
    });

    //Seccion para cargar cuenta contable depreciacion segun el departamento
    $(document).ready(function()
    {
        $('#Are_Cod').on('change', function() {
            var id = this.value; 
            var codigo={Dep_Cod:id,buscarCuentaDep:true};
            $.get('<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',codigo, function(response){
                //Buscar y setear la cuenta del departamento en la tabla 
                $("#depreciacion").jqGrid("addRowData", 0, response);
                $("#Cod_Dep").val(response['Pld_Cod']);
            },'json');
        });
    });
    
    //Funci�n para aderir campos del tipo de activo
    function addcampos(){
        $("#campos_nuevos").html("");
        for(var i=0;i<campos_nuevos.length;i++){
            campo = '<div class="col-sm-5 col-md-5"><div class="form-group">\n\
                     <label class="col-md-5 control-label required">'+campos_nuevos[i]['Cam_Lar']+':</label>\n\
                     <div class="col-md-7">\n\
                     <input type="text" class="form-control input-xs" id="CAM_'+campos_nuevos[i]['Cam_Cod']+ '" name="'+campos_nuevos[i]['Cam_Cod']+'" '+(campos_nuevos[i]['Cam_Req']==='S'?'required':'')+'/>\n\
                     </div>\n\
                     </div></div>';
            $("#campos_nuevos").append(campo);
        }
    }
    
    //Secci�n para comboBox de periodo
    $(document).ready(function(){
        $("#Pec_Cod").val(<?php echo $periodoContable['Pec_Cod']?>);
        $("#periodo").val(<?php echo $periodoContable['Periodo']?>);

    });
    
    //Secci�n para bloquear el ingreso de c�digo de barras cuando este marcado el checkbox	
    $(document).ready(function(e) {
        $('#Act_Bar').attr('readonly',true);
        $('#Act_Bar1').click(function(e) {
            if($('#Act_Bar1').is(':checked'))
            {
                $('#Act_Bar').attr('readonly',true);
                $('#Act_Bar').val('');
                $('#mensaje_codigo').html('Generar c�digo automaticamente <span class="glyphicon glyphicon-ok"></span>');
            }
            else
            {
                $('#mensaje_codigo').html('Dig�te c�digo manualmente.');
                $('#Act_Bar').attr('readonly',false);
            }
        });
    });
    
    //Secci�n para realizar el calculo de la depreciaci�n anual dentro del tab=depreciacion
    //Funci�n para obtener el �ltimo d�a del mes 
    function daysInMonth(month, year) {
        var last_day=new Date(year || new Date().getFullYear(), month, 0).getDate();
        if(parseInt(month)===02){last_day=28;}
        month=('0'+month).slice(-2);
        var last_date=year+'-'+month+'-'+last_day;
        return [last_day,last_date];
    }
    
    //Funci�n para calcular la depreciaci�n segun el n�mero de d�as
    //val_com=valor de compra
    //val_res=valor residual
    //vid_uti=vida util del activo
    //dias=n�mero de d�as a depreciar
    function depreciacion(val_com,val_res,vid_uti,dias){
        var resultado=((val_com-val_res)/(365*vid_uti))*dias;
        return resultado;
    }
    
    function calcula_depreciacion(){
        var cop_fec=$("#Cop_Fec").val();
        var vid_uti=$("#Act_Ann").val();
        var por_dep=$("#Act_Pde").val();
        var cop_pru=$("#total").val();
        var act_res=$("#Act_Res").val();

        if(($("#Act_Ann").val()!=='')&&($("#Act_Res").val()!=='')){$('#deprecia').show();$('#deprecia_mensaje').hide();}
        else{$('#deprecia').hide();$('#deprecia_mensaje').show();}

        /*�NICA Y EXCLUSIVAMENTE CUANDO LA FECHA DE COMPRA SEA EL 29 DE FEBRERO*/
        var febrero=cop_fec.split('-');if(febrero[1]==='02'){cop_fec=febrero[0]+'-02-28';}

        /*** CALCULO DE LA DEPRECIACI�N ANUAL Y MENSUAL ***/
        //Secci�n para autoajuste del jqgrid
        if($('#dep_anual').actual( 'outerWidth', { includeMargin : true })<300){ $('#dep_anual').trigger('resize');}

        //Se limpia el jqGrid de la depreciaci�n anual 
        $("#dep_anual").jqGrid('clearGridData',true).trigger('reloadGrid');

        //Calculo para obtener el valor a depreciar anualmente
        var datos=[],dep_acum=0,val_res=0,dep_acum_men=0,val_libros=cop_pru;
        var fecha=cop_fec.split('-');//Se descompone la fecha de compra
        var anio=fecha[0];var aux_mes=fecha[1];var mes=fecha[1];var dia=fecha[2];var meses=12;var dep_mensual=0;var i=0;var Act_Ffd='';
        var anio_fin_dep=parseInt(anio)+parseInt(vid_uti);//Se establece el �ltimo a�o de depreciaci�n
        var fecha_compra=new Date(anio,mes-1,dia);
        var fecha_fin_anio=new Date(anio,'11','31');
        var diferencia=fecha_fin_anio-fecha_compra;if(diferencia===0){diferencia=1;}//Se resta la fecha de fin de a�o con la fecha de compra 

        //Resultado en milisegundos, por tal raz�n se debe convertir a d�as
        //se suma un d�a pues se desea iniciar la depreciaci�n desde la fecha de compra 
        var dias=(Math.floor(diferencia / (1000 * 60 * 60 * 24)));
        if(dias<365){dias=dias+1;}else{i=1;}

        var dep_anual=depreciacion(cop_pru,act_res,vid_uti,dias);

        //Calculo depreciaci�n mensual
        var ult_dia=daysInMonth(aux_mes,anio);var dias_dep=(ult_dia[0]-dia)+1;

        $("#dep_anual").jqGrid('addRowData',0,{"periodo":"Apertura","Val_Res":cop_pru});

        for(i; i<=vid_uti; i++){
            if(anio===anio_fin_dep){
                dias=365-dias;
                dep_anual=depreciacion(cop_pru,act_res,vid_uti,dias);
                if(dia==='01'){mes=mes-1;}//Esta condici�n es con el prop�sito de que no calcule la depreciaci�n cuando la fecha de ingreso sea 01
                meses=mes;
                var fin_dep_men=1;
            }
            while(aux_mes<=meses){
                var f_inicio=cop_fec.split('-');
                var g=new Date(f_inicio[0],f_inicio[1]-1,f_inicio[2]); 

                ult_dia=daysInMonth(aux_mes,anio);
                var f_fin=ult_dia[1].split('-');
                var f=new Date(f_fin[0],f_fin[1]-1,f_fin[2]);

                var f_inicio=f-g;
                var diass=(Math.floor(f_inicio / (1000 * 60 * 60 * 24)))+1;

                if((fin_dep_men===1)&&(aux_mes===mes)){diass=diass-dias_dep;ult_dia[1]=f_fin[0]+'-'+f_fin[1]+'-'+diass;}
                dep_mensual=depreciacion(cop_pru,act_res,vid_uti,diass);
                dep_acum_men=parseFloat(dep_acum_men)+parseFloat(dep_mensual);
                var val_res_men=val_libros-dep_mensual;
                val_libros=val_res_men;
                var Act_Ffd=ult_dia[1];
                datos.push({"anio":anio,"fec_ini":cop_fec,"fec_fin":ult_dia[1],"Val_Dep":dep_mensual.toFixed(2),"Dep_Acu":dep_acum_men.toFixed(2),"Val_Res":val_res_men.toFixed(2)});
                aux_mes++;
                aux_mes=('0'+aux_mes).slice(-2);
                cop_fec=anio+'-'+aux_mes+'-01';
            }
            dep_acum=dep_acum+dep_anual;
            val_res=cop_pru-dep_acum;
            $("#dep_anual").jqGrid('addRowData', i, {"periodo":anio+'-'+mes,"porcentaje": por_dep,"Val_Act":cop_pru,"Val_Dep":dep_anual.toFixed(2),"Dep_Acu":dep_acum.toFixed(2),"Val_Res":val_res.toFixed(2)});
            dep_anual=depreciacion(cop_pru,act_res,vid_uti,365);
            anio++;
            aux_mes=1;
            aux_mes=('0'+aux_mes).slice(-2);
            cop_fec=anio+'-'+aux_mes+'-01';
        }
        $("#dep_mensual").clearGridData();$("#dep_mensual").setRows(datos);
        //Cargando datos a la leyenda de depreciacion
        $("#valor_activo").val(cop_pru);
        $("#valor_residual").val(val_res.toFixed(2));
        $("#depreciacion_acumulada").val(dep_acum.toFixed(2));
        $("#valor_libros").val(val_res.toFixed(2));
        $("#Act_Ffd").val(Act_Ffd);
    }
    
    $(document).ready(function(){
        $('#tipo_dep').on('change',function(){
            ($('#tipo_dep').val()==='M') ? ($('#d_a').hide(),$('#d_m').show()):($('#d_a').show(),$('#d_m').hide());
        });
        $("#tabs").tabs({
            activate:function (event, ui) {
                var $activeTab = $('#tabs').tabs('option','active');
                if($activeTab === 4){calcula_depreciacion();}
            }
        });
    });
    
    //Inicio del di�logo producto 
    $(document).ready(function (){
        $.createSearchDialog('#productoCDialog',[
            {label:'Llave',name:'llave',key:true,hidden:true},
            {label:'Cod.Int.',name:'Cop_Cod',hidden:true},
            {label:'Num. Factura Compra',name:'Cop_Num',width: 80},
            {label:'Fecha',name:'Cop_Fec',width: 40},
            {label:'Item',name:'Cop_Int',width:30,align:'center'},
            {label:'Activo',name:'Ite_Lar',width: 150},
            {label:'Cantidad',name:'Cop_Can',width:35,align:'center'},
            {label:'Iva al Costo',name:'Iva_Cos',width:45,align:'center'},
            {label:'P.Unitario',name:'Cop_Pru',width:45,align:'right'},
            {label:'<center><i class="ui-icon ui-icon-circle-check"></i></center>', name: 'act', width: 18, align: 'center',viewable: false, 
                formatter: function(cellvalue, options, rowObject)
                {
                    var $input=$('<input id="Chk_'+rowObject.llave+'" type="checkbox"'+(cellvalue?'checked="checked"':'')+' onclick="cargar($(this).data(\'orig\'))" />');                    
                    return $('<div/>').append($input.attr('data-orig',$.jsonParser(rowObject))).html();
                }   
            }
        ],null,800,null,{
            grouping:true,
            groupingView:{
                groupField:['Cop_Num'],
                groupText:['<span style="float:left;"><b>{0} - {1} Registro(s)</b></span> <span style="float:right"><button name="bt_agregar" id="bt_agregar" class="btn btn-success btn-xs" onclick="cargarProducto();"><span class="glyphicon glyphicon-check" title="Agregar"></span></button></span>'],
                groupCollapse:true,
                groupOrder:['asc']
            }
        },{title:'Producto',options:[{label:'&nbsp;&nbsp;Producto&nbsp;&nbsp;',value:'d'},
          {label:'&nbsp;&nbsp;Num. Factura&nbsp;&nbsp;',value:'c'}]}).getDialogGrid().setGridNoPager();
    });
    var items=[];
    function cargar(producto){
        limpiar();
        $("#div_proveedor").show();
        $("#div_fcompra").show();
        $("#div_scontable").show();
        $("#div_fadquisicion").show();
        $("#div_nofaccompra").hide();
        if($('#Chk_'+producto.llave).is(':checked')) { 
            items.push(producto);
            if((items[0]['Cop_Cod']!==producto.Cop_Cod)||(items[0]['Iva_Cos']!==producto.Iva_Cos))
            {
                $.alert('Los productos deben ser de iguales caracter&iacute;sticas para su agrupamiento..!!');
                var indice=buscarIndex(items,"llave",producto.llave);
                items.splice(indice,1);
                $('#Chk_'+producto.llave).prop("checked", "");
            }else{
                
            }
        } else {  
            var indice=buscarIndex(items,"llave",producto.llave);
            items.splice(indice,1); 
        }
    }
    
    function buscarIndex(arreglo, campo, valor) {
        for (var i = 0; i < arreglo.length; i++){
            if (arreglo[i][campo] === valor){return i;}
        }
        return null;
    }
        
    //Funci�n para cargar valores del producto seleccionado
    var iva_por=0;
    function cargarProducto(){
        var acum=0,cant=0,cop_int='',iva=0,total=0;
        $("#productoCDialog").dialog("close");
        if(items.length>1)
        {
            for(var i=0; i<items.length; i++){
                acum=parseFloat(acum)+parseFloat(items[i]['Cop_Pru']);
                cant=parseInt(cant)+parseInt(items[i]['Cop_Can']);
                cop_int=items[i]['Cop_Int']+'/'+items[i]['Cop_Can']+'*'+cop_int;
            }
            cop_int=cop_int.slice('*',-1);
            acum=acum/cant;//Se divide la sumatoria de los Precios de los productos seleccionados para la cantidad de los mismos
        }else{cant=items[0]['Cop_Can'];cop_int=items[0]['Cop_Int']+'/'+items[0]['Cop_Can']+'*';acum=items[0]['Cop_Pru'];}
        $("#Cop_Cod").val(items[0]['Cop_Cod']);
        $("#Cop_Int").val(cop_int);
        $("#Pro_Cod").val(items[0]['Pro_Cod']);
        $("#Ite_Lar").val(items[0]['Ite_Lar']);
        $("#Cop_Num").val(items[0]['Cop_Num']);
        $("#cuenta").val(items[0]['cuenta']);
        $("#Cop_Fec").val(items[0]['Cop_Fec']);
        $("#Cop_Fec1").val(items[0]['Cop_Fec']);
        $("#Cop_Pru").val(acum);
        $("#Act_Can").val(cant);
        $("#Prv_Cod").val(items[0]['Prv_Cod']);
        $("#Prv_Nom").val(items[0]['proveedor']);
        $("#Tri_Des").val(items[0]['Tri_Des']);
        $("#Iva_Cos").val(items[0]['Iva_Cos']);
        iva_por=items[0]['Iva_Por'];
        $("#porcentaje").html('I.V.A. ('+iva_por+'%)');
        iva=(acum*iva_por)/100;
        total=parseFloat(acum)+parseFloat(iva);
        $("#Iva_Por").val(iva.toFixed(2));
        $("#iva").val(iva);
        $('#subtotal').val(total.toFixed(2)); 
        if(items[0]['Iva_Cos']==='SI'){$('#total').val(total.toFixed(2));}else{$('#total').val(acum);}
        $('#Act_Bar').prop('readonly',true);
        $('#tabs').tabs({disabled:false});$('#btguardaractivo').prop('disabled',false);
        inicializar_input_file();
        $("#Iva_Cos").show();
        $("#Iva_Cos_SFC").hide();
        $('#Act_Can').attr('readonly',true);
        $('#Act_Can').attr('title','No Editable');
    }
    
    //Secci�n para recalcular iva,total y valor a depreciar
    $('#Cop_Pru').on('input',function (){
        var iva=($('#Cop_Pru').val()*iva_por)/100;
        $("#Iva_Por").val(iva.toFixed(2));
        var total=parseFloat($('#Cop_Pru').val())+parseFloat(iva);
        $('#subtotal').val(total.toFixed(2)); 
        if(($('#Iva_Cos').val()==='SI')||($('#Iva_Cos_SFC').val()==='S')){$('#total').val(total);}else{$('#total').val($('#Cop_Pru').val());}
    });
    
    //Inicio del di�logo para presentar productos sin factura de compra
    $(document).ready(function() {               
        $.createSearchDialog('#productoSDialog',[
            { label: 'C�d.Int.', name: 'Pro_Cod', key: true,hidden:false,viewable: true,width: 15,align:'center' },                                
            { label: 'Producto', name: 'Ite_Lar', width: 70 },
            { label: 'P.Unitario', name: 'Pre_Pvp', width: 30,align:'right' }, 
            { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center',viewable: false,
                formatter:function (cellvalue, options, rowObject) { 
                    return $.getGridButton(cargarProductoSFC,rowObject);
                }
            }
        ],null,null,null,null,{title:'Producto',options:[{label:'&nbsp;&nbsp;Producto&nbsp;&nbsp;',value:'d'},
          {label:'&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;',value:'c'}]});  						 
    });
    
    function cargarProductoSFC(producto)
    {
        $("#productoSDialog").dialog("close");
        limpiar();$('#tabs').tabs({disabled:false});$('#btguardaractivo').prop('disabled',false);
        $("#div_proveedor").hide();
        $("#div_fcompra").hide();
        $("#div_scontable").hide();
        $("#div_fadquisicion").hide();
        $("#div_nofaccompra").show();
        var precio=producto.Pre_Pvp*1;
        iva_por=producto.Iva_Por;
        $("#Pro_Cod").val(producto.Pro_Cod);
        $("#Ite_Lar").val(producto.Ite_Lar);
        $("#cuenta").val(producto.cuenta);
        $("#Cop_Pru").val(precio.toFixed(2));
        var f = new Date();
        $("#Cop_Fec").val(f.getFullYear() + "-" + ('0'+(f.getMonth() +1)).slice(-2) + "-" + f.getDate());
        $("#Iva_Cos").hide();
        $("#Iva_Cos_SFC").show();
        $("#porcentaje").html('I.V.A. ('+producto.Iva_Por+'%)');
        var iva=(producto.Pre_Pvp*producto.Iva_Por)/100;
        var total=parseFloat(producto.Pre_Pvp)+parseFloat(iva);
        $("#Iva_Por").val(iva.toFixed(2));
        $("#iva").val(iva);
        $('#subtotal').val(total.toFixed(2)); 
        $('#total').val(precio.toFixed(2));
        $('#Act_Can').attr('readonly',false);$('#Act_Can').attr('title','');
        var data={compra_prov:true};
        $.post( "<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>",data, function(response) 
        {
            $("#Prv_Cod").val(response['row']['Prv_Cod']);  
        },'json').fail(function(error) { $.alert();}); 
    }
    
    $('#Iva_Cos_SFC').change(function (){
        if($('#Iva_Cos_SFC').val()==='S'){
            $('#total').val($('#subtotal').val());
        }else{$('#total').val($('#Cop_Pru').val());}
    });
    
    //Inicio del di�logo perito 
    $(document).ready(function() {               
        $.createSearchDialog('#peritoDialog',[
            { label: 'C�d.Int.', name: 'Pri_Cod', key: true,hidden:true,viewable: true },                                
            { label: 'C�dula/R.U.C.', name: 'Prs_Ced', width: 50 },                      
            { label: 'Perito', name: 'perito', width: 190, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},                            
            { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center',viewable: false,
                formatter:function (cellvalue, options, rowObject) { 
                    var clic='$("#perito").val("'+rowObject.perito+'");$("#Pri_Cod").val("'+rowObject.Pri_Cod+'");$("#peritoDialog").dialog("close");';
                    return  '<span class="btn btn-success btn-xs" title="Seleccionar" onclick=\''+clic+'\'><i class="glyphicon glyphicon-arrow-right"></span>'; 
                }
            }
        ],null,null,null,null,{title:'Perito',options:[{label:'&nbsp;&nbsp;Apellido&nbsp;&nbsp;',value:'d'},
          {label:'&nbsp;&nbsp;C&eacute;dula/R.U.C.&nbsp;&nbsp;',value:'c'}]});  						 
    }); 
    
    //Inicio de di�logo para buscar cuentas
    $(document).ready(function () { 
        $.createSearchDialog('cuentaDialog',[
            { label: 'C&oacute;d.Int.', name: 'Pld_Cod', key: true, width: 15,align:"center",hidden:true },                                
            { label: 'Codigo', name: 'Pld_Cdc', width: 45 },                      
            { label: 'Cuenta', name: 'Pld_Des', width: 80, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},
            { label: 'Grupo', name: 'Pld_Grupo', width: 110, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},
            { label: 'Tipo', name: 'Pld_Tip', width: 30,align:"center" },
            { label: 'Estado', name: 'Pld_Est', width: 30,align:"center"}, 
            { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 30, align: 'center',viewable: false,
                formatter:function (cellvalue, options, rowObject) { 
                    return  '<span class="btn btn-success btn-xs" title="Enviar al D&eacute;bito" onclick="addCuenta(\''+rowObject.Pld_Cod+'\',\''+rowObject.Pld_Cdc+'\',\''+rowObject.Pld_Des+'\');"><i class="glyphicon glyphicon-arrow-right"></i></span>&nbsp;'; 
                }
            }
        ],null,null,null,null,null,null);   
    });
    
    //Funci�n para agregar cuenta contable al jqgrid 
    function addCuenta(Pld_Cod,Pld_Cdc,Pld_Des)
    {
        //Se cierra el di�logo de las cuentas
        $('#cuentaDialog').dialog('close');
        if(tipo==='D')
        {
            //Se carga el codigo de la cuenta depreciacion al campo oculto Cod_Dep
            $("#Cod_Dep").val(Pld_Cod);
            //Se carga los valores seleccionados a los grid de las cuentas de depreciaci�n
            $("#depreciacion").jqGrid('addRowData', Pld_Cod, {"Pld_Cod":Pld_Cod,"Pld_Cdc": Pld_Cdc,"Pld_Des": Pld_Des});
        }else{
            //Se carga el codigo de la cuenta depreciacion acumulada al campo oculto Cod_Dea
            $("#Cod_Dea").val(Pld_Cod);
            //Se carga los valores seleccionados a los grid de las cuentas de depreciaci�n
            $("#depreciacion_acum").jqGrid('addRowData', Pld_Cod, {"Pld_Cod":Pld_Cod,"Pld_Cdc": Pld_Cdc,"Pld_Des": Pld_Des});
            //Deshabilito el boton para agregar cuenta
            $("#btnDepAcum").attr('disabled','disabled');
        }
    }
    
    //Inicio de di�logo para presentar la cuenta de depreciaci�n
    $(document).ready(function() { 
        var Tipo='D';
        $("#depreciacion").jqGrid({
            url:'<?PHP echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8')?>',
            mtype:'GET',
            datatype:'local',
            regional:'es',
            autowidth:true,
            shrinkToFit:true,
            height:40,
            cmTemplate:{sortable:false},
            caption:'DEPRECIACI&Oacute;N &raquo; Cuenta Contable &raquo; Departamento',
            colModel:[
                {label:'C&oacute;digo',key:true,name:'Pld_Cod',width:80},
                {label:'Cuenta',name:'Pld_Cdc',width:110},
                {label:'Descripci&oacute;n',name:'Pld_Des',width:280},
                {label:'<center><i class="ui-icon ui-icon-gear"></i></center>',name:'accion',width:40,align:'center'}
            ],
            rowNum: 20, pager: "", gridview: true, rownumbers: true, viewrecords: true, altRows: true, altclass: "myAltRowClass"
        });
    });
    
    //Inicio de di�logo para presentar la cuenta de depreciaci�n acumulada
    $(document).ready(function(){
        var Tipo='DA';
        $("#depreciacion_acum").jqGrid({
            url:'<?PHP echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8')?>',
            mtype:'GET',
            datatype:'local',
            regional:'es',
            autowidth:true,
            shrinkToFit:true,
            height:40,
            cmTemplate:{sortable:false},
            caption:'DEPRECIACI&Oacute;N ACUMULADA &raquo; Cuenta Contable',
            colModel:[
                {label:'C&oacute;digo',key:true,name:'Pld_Cod',width:80},
                {label:'Cuenta',name:'Pld_Cdc',width:110},
                {label:'Descripci&oacute;n',name:'Pld_Des',width:280},
                {label:'<center><i class="ui-icon ui-icon-gear"></i></center>',name:'accion',width:40,align:'center',
                    formatter:function(cellvalue,options,rowObject){
                        return '<span class="btn btn-danger btn-xs" title="Eliminar" type="button" onclick="eliminarCuenta(\''+rowObject.Pld_Cod+'\',\''+Tipo+'\');"><i class="glyphicon glyphicon-remove"></i></span>';
                    }
                }
            ],
            rowNum:20,pager:"",gridview:true,rownumbers:true,viewrecords:true,altRows:true,altclass:"myAltRowClass"
        });
    });
    
    //Elaboraci�n de jqGrid para presentar el calculo de la depreciaci�n anual del activo
    $(document).ready(function(){
        $("#dep_anual").jqGrid({
            mtype:'GET',
            datatype:'local',
            regional:'es',
            autowidth:true,
            shrinkToFit: true,
            hidegrid:false,
            responsive:true,
            height:230,
            cmTemplate:{sortable:false},
            caption:'DEPRECIACI&Oacute;N ANUAL',
            colModel:[
                {label:'Peri&oacute;do',align:'center',name:'periodo',width:80},
                {label:'Valor Depreciaci&oacute;n',align:'center',name:'Val_Dep',width:130},
                {label:'Depreciaci&oacute;n Acumulada',align:'center',name:'Dep_Acu',width:130},
                {label:'Valor en Libros',align:'center',name:'Val_Res',width:130}
            ],
            rowNum:10000,pager:"list_da_Pager",gridview:true,rownumbers:true,viewrecords:true,pgbuttons: false,pgtext: null
        });
        
        $("#dep_mensual").jqGrid({
            mtype: 'GET',
            datatype:'local',
            regional:'es',
            autowidth:true,
            shrinkToFit:true,
            hidegrid:false,
            responsive:true,
            height:230,
            cmTemplate:{sortable:false},
            caption:'DEPRECIACI&Oacute;N MENSUAL',
            colModel:[
                {label:'Anio',name:'anio',width:80},
                {label:'Fecha inicio',align:"center",name:'fec_ini',width:90},
                {label:'Fecha fin',align:"center",name:'fec_fin',width:90},
                {label:'Valor Depreciaci&oacute;n',align:"center",name:'Val_Dep',width:105},
                {label:'Depreciaci&oacute;n Acumulada',align:"center",name:'Dep_Acu',width:110},
                {label:'Valor en Libros',align:"center",name:'Val_Res',width:110}
            ],
            rowNum:10000,pager:'list_dm_Pager',viewrecords:true,pgbuttons:false,pgtext:null,
            sortname:'fec_ini',
            sortorder:'asc',
            grouping:true,
            groupingView:{
                groupField:['anio'],
                groupColumnShow:[false],
                groupText:['<span style="float:left;"><b>{0} - {1} Registro(s)</b></span>'],
                groupCollapse:true,
                groupOrder:['asc']
            }
        });
    });
    
    //Funcion para eliminar una cuenta contable dentro del tab cuenta contable
    function eliminarCuenta(Pld_Cod,Tipo)
    {
        if(Tipo==='D'){
            $('#depreciacion').jqGrid('delRowData',Pld_Cod);
        }
        else{
            $('#depreciacion_acum').jqGrid('delRowData',Pld_Cod);
            $("#btnDepAcum").attr('disabled',false);
        }
    }
    
    function inicializar_input_file(){
        $("#file5").fileinput({
            uploadUrl: '<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',
            showCaption: false,
            showRemove: false,
            showCancel: false,
            browseClass: "btn btn-success btn-sm",
            browseLabel: 'Buscar Imagen',
            uploadClass: 'btn btn-success btn-sm hide',
            allowedFileExtensions : ['jpg', 'png','gif'],
            overwriteInitial: false,
            maxFileSize: 2000,
            msgSizeTooLarge: 'Archivo: "{name}" (<b>{size} KB</b>) excede el tama�o m�ximo permitido de <b>{maxSize} KB</b>.',
            dropZoneTitle:'Arrastrar y Soltar Im�genes Aqu�...',
            maxFileCount: 2,
            msgFilesTooMany: 'N�mero de im�genes permitidas 2.',
            validateInitialCount: true,
            disable: function () {
                var self = this;
                self.isDisabled = false;
                self._raise('fileenabled');
                self.$element.removeAttr('disabled');
                self.$container.find(".kv-fileinput-caption").removeClass("file-caption-disabled");
                self.$container.find(
                    ".btn-file, .fileinput-remove, .fileinput-upload, .file-preview-frame button").removeAttr("disabled");
                self._initDragDrop();
                return self.$element;
            },
            slugCallback: function(filename) {
                return filename.replace('(', '_').replace(']', '_');
            }
	   });
    }
    
    /*Funci�n para guardar un nuevo activo se lo efectua con formData, puesto que se esta enviando imagenes*/
    function saveForm(){ 
        calcula_depreciacion();
        //Seccion para validar los campos del tab detalle que por cierto se encuentran en otro formulario
        for(var i=0;i<campos_nuevos.length;i++){
            var name_campo=campos_nuevos[i]['Cam_Cod'];
            if($('#CAM_'+name_campo).val()===''){
                setTimeout(function(){$('#formcampos').formSubmit();},200);
                $('#tabs').tabs('option','active',1);
                return;
            }
        }
        //Secci�n para validar que elija una cuenta contable
        if(($('#Cod_Dep').val()==='')||($('#Cod_Dea').val()==='')){
            setTimeout(function (){$('#formcuenta_Contable').formSubmit();},200);
            $('#tabs').tabs('option','active',2);
            if($('#periodo_c').val()!==''){showAlert();}
            return;
        }
        $('#file5').parent().parent().find('.fileinput-upload-button').hide();
        $('#file5').parent().parent().find('.kv-upload-progress').hide();
          
        var formData = new FormData(document.getElementById("formActivo"));
        formData.append("uploadfoto", true); 
        formData.append("Act_Val", $('#Cop_Pru').val());
        formData.append("Act_Fec", $('#Cop_Fec').val());
        var detalle=$(document.forms['formcampos']).serializeArray();
        for (var i=0; i<detalle.length; i++){
            formData.append(detalle[i].name, detalle[i].value);
        }
        $.ajax({
            url: '<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',
            type: "post",
            dataType: "json",
            data: formData,
            cache: false,
            contentType: false,
            processData: false
        })
        .done(function(responce){
            if(responce.success===true){
                $('#Act_Cod').val(responce.Act_Cod); 
                $('#file5').parent().parent().find('.fileinput-upload-button').trigger('click');  
                $.alert("Transaccion Realizada con &Eacute;xito!");
                limpiar();
                $('#file5').fileinput('clear');
                $('#tabs').tabs({disabled:[0,1,2,3,4]});$('#btguardaractivo').prop('disabled',true);  
                $('#productoCDialog').getDialogGrid().trigger('reloadGrid',[{page:1}]);
                items=[];
            }else{$.alert(responce.message);}
        });  
    };
    function limpiar(){
        /*Secci�n para limpiar el chosen*/
        $('#Tia_Cod').val('').trigger('chosen:updated');
        $('#Are_Cod').val('').trigger('chosen:updated');
        /*Secci�n para limpiar formularios*/ 
        $('#formInfo')[0].reset();
        $('#formActivo')[0].reset();
        $('#depreciacion').jqGrid('clearGridData',true).trigger('reloadGrid');
        $('#depreciacion_acum').jqGrid('clearGridData',true).trigger('reloadGrid');
        $('#dep_anual').jqGrid('clearGridData',true).trigger('reloadGrid');
        $('#dep_mensual').jqGrid('clearGridData',true).trigger('reloadGrid');
        $('#campos_nuevos').html("<div class='col-sm-4'></div><div class='alert alert-info col-sm-4' role='alert'><u><span class='glyphicon glyphicon-info-sign'></span><b> NOTA: </b></u> No se ha elegido una categor&iacute;a para el activo..!!</div>");
        $('#datosPerito').html('Sin �ndice de b�squeda');
        $('#Tia_Des').val('Seleccione un Tipo de Activo'); 
        inicializar_input_file();
        $('#periodo_c').val('');
    }
    /*Funci�n para visiaulizar mensaje de alerta dentro del tab cuenta contable*/
    function showAlert(){
        $("#alert_cc").show();$("#alert_cc").alert();
        $("#alert_cc").fadeTo(7000, 500).slideUp(500, function(){$("#alert_cc").slideUp(500);});   
    }
   </script>
   <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
</BODY>
</HTML>