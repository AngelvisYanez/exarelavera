<?php	
/**
* @abstract Permite realizar el registro de activo
* @author José Ambuludí
* @version 1.0
* Fecha de creación  2016-06-14
* @author José Ambuludí
* Fecha de modificación  2016-07-05
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

/*Sección para cargar datos en el Jqgrid referente a los productos con factura de compra y sin la misma*/
if(isset($productoCAjax)||isset($productoSAjax))
{
    $data=filter_input_array(INPUT_GET);
    if(isset($productoSAjax)){$data["Tipo"]="SFC";}else{$data["Tipo"]="CFC";}
    $data["Emp_Cod"]=$Ses_Emp_Cod;
    $responce['rows']=$obBD_con1->getArrayConsulta(622,$data,$obBD_conexion);
    echo json_encode($responce);
    exit();
}
/*Sección para extraer código de proveedor=VARIOS EGRESOS, puesto que se registrará un activo sin factura de compra*/
if(isset($compra_prov))
{
    $response["row"]=$obBD_con1->getRowConsulta(630,$Ses_Emp_Cod,$obBD_conexion);
    echo json_encode($response);
    exit();
}
/*Sección para cargar datos en el Jqgrid referente a los peritos*/
if(isset($peritoAjax)){ 
   $data=filter_input_array(INPUT_GET);
   $data["Emp_Cod"]=$Ses_Emp_Cod;   
   $contar = $obBD_con1->getRowConsulta(620, $data, $obBD_conexion);	      
   $pagination= pages($contar['total'], $page, $rows);
   $responce=$pagination['data'];
   $data["limits"]=$pagination['limits'];
   if($contar['total']>0)
       $responce['rows'] =  $obBD_con1->getArrayConsulta(620, $data, $obBD_conexion);
   echo json_encode($responce);exit();
}
/*Sección para listar el plan de cuentas*/
if(isset($cuentaAjax)){ 
    $contar = $obBD_con1->getRowConsulta(623, $search_cuenta.'*'.$Ses_Emp_Cod.'*'.$Pec_Cod.'*'.$op_cuenta.'*', $obBD_conexion);	      
    $pagination= pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    $responce['rows']=  $obBD_con1->getArrayConsulta(623, $search_cuenta.'*'.$Ses_Emp_Cod.'*'.$Pec_Cod.'*'.$op_cuenta.'*'.$pagination['limits'], $obBD_conexion);	    
    echo json_encode($responce);exit();
}
/*Sección para listar los campos pertenecientes a un tipo de activo*/
if(isset($buscarCampos)){ 
    $response = $obBD_con1->getArrayConsulta(616,$Tia_Cod, $obBD_conexion);
    echo json_encode($response);exit();
}
/*Lista los tipos de activos existentes*/
if(isset($tipoactivAjax)){ 
    $responce = $obBD_con1->getArrayConsulta(608,$Ses_Emp_Cod."*", $obBD_conexion);
    echo json_encode($responce);exit();
}
//Sección para guardar las fotos del activo (Se actualiza el activo para poder registrar en el campo Act_Fot)
if(isset($uploadfoto2)){      
    //Se carga la ruta de donde se desea crear la carpeta que almacenará las imágenes
    $carpeta = "../../imagenes/".$Ses_Emp_Cod.'/Activos';
    //En caso de que la carpeta no exista se creará asignandole todos los permisos "0777"
    if (!file_exists($carpeta)) {
        mkdir($carpeta, 0777, true);
    }
    
    /*Se extrae la extensión de la foto jpg,png,etc*/
    $archivo = $_FILES['file5']['name'];
    $nombre=explode('.',$archivo);  
    $last=count($nombre)-1;
    
    /*Variable para almacenar la ruta de la imagen del activo*/
    if($nombre[$last]!="")
    {
        $act_cod=explode("*",$Act_Cod);
        foreach($act_cod as $row){
            $ruta="";
            $row = trim($row);
            //$ruta=contiene el nombre de la imagen;
            $ruta='img_activo_'.$row.'_'.$file_id.'.'.$nombre[$last];
            //Copiamos la imagen cargada a la carpeta con la direccion establecida
            copy($_FILES['file5']['tmp_name'],$carpeta.'\\'.$ruta);
            //Verificamos si el campo Act_Fot esta vacío o lleno(concatenar)
            $Act_Fot=$obBD_con1->getRowConsulta(621,$row,$obBD_conexion);
            if($Act_Fot['Act_Fot']=="")
            {
                //Ejecutamos un update sobre la tabla activo para agregar la imagen
                $obBD_con1->operacionobBD(602, $row.'*'.$ruta, $obBD_conexion);
            }else{
                //En caso de que ya existiese una imagen en el campo Act_Fot extraemos el dato y lo concatenamos con la nueva imagen
                $fotos=$Act_Fot['Act_Fot'].",".$ruta;
                //Ejecutamos un update sobre la tabla activo para agregar la imagen
                $obBD_con1->operacionobBD(602, $row.'*'.$fotos, $obBD_conexion);
            }
        }
    }
    echo json_encode(true);
    exit();
}
/*Sección ajax para guardar un nuevo activo*/
if(isset($uploadfoto)){ 
    $cont=1;$aux=0;
    $codigos="";//Variable para almacenar los Act_Cod que se registren para luego registrarlos con las fotos
    /*Sección para guaradar los datos en la tabla activo*/
    $responce['success']=false;$responce['message']="No se ha logrado realizar la Transaccion"; 
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
    if($Pri_Cod==''){
        $row_Pri_Cod=$obBD_con1->getRowConsulta(627,'', $obBD_conexion);
        $Pri_Cod=$row_Pri_Cod['Pri_Cod'];
    }
    $cop_can=  explode("*",$Cop_Int);
    for($i=0;$i<$Act_Can;$i++){
        $obBD_con1->operacionobBD(601, $Tia_Cod.'*'.$Pri_Cod.'*'.$Ses_Suc_Cod.'*'.$Est_Cod.'*'.$Prv_Cod.'*'.$Pro_Cod.'*'.$Act_Des.'*'.$Act_Obs.'*'.$Act_Cdc.'*1*'.$Act_Bar.'*'.$Act_Gen.'*'.$Act_Val.'*'.$Act_Pde.'*'.$Act_Res.'*'.$Act_Ann.'*'.$Act_Fec.'*'.$Act_Ffd.'*'.$Act_Gar.'*'.$ruta, $obBD_conexion);
        /*Sección para actualizar el campo de foto del activo primero obtenemos el último código de activo*/
        $Act_Cod = $obBD_con1->insercionid($obBD_conexion->conexion);
        $codigos=$Act_Cod."*".$codigos;
        /*Sección para insertar datos en la tabla det_activo*/
        if($Tia_Cod>0){
            $campos = $obBD_con1->getArrayConsulta(616,$Tia_Cod, $obBD_conexion);
            foreach($campos as $valor)
            {
                $obBD_con1->operacionobBD(617, $Act_Cod.'*'.$valor['Cam_Cod'].'*'.$_POST[$valor['Cam_Cod']], $obBD_conexion);
            }
        }
	/**
        * Genera el Codigo de Barra senececitan 12 caracteres para generar
        */
        $Act_Var='';/* esta variable crea una cadena del codigo de barra*/
        $Act_Gen='';
        if($Act_Bar1==1)
        {
            switch ( strlen($Act_Cod)) {
                case 1:
                 $Act_Var=$Act_Cod."00000000000";
                break;
                case 2:
                 $Act_Var=$Act_Cod."0000000000";
                break;
                case 3:
                 $Act_Var=$Act_Cod."000000000";
                break;
                case 4:
                 $Act_Var=$Act_Cod."00000000";
                break;
                case 5:
                 $Act_Var=$Act_Cod."0000000";
                break;
                case 6:
                 $Act_Var=$Act_Cod."000000";
                break;
                case 7:
                 $Act_Var=$Act_Cod."00000";
                break;
                case 8:
                 $Act_Var=$Act_Cod."0000";
                break;
                case 9:
                 $Act_Var=$Act_Cod."000";
                break;
                case 10:
                 $Act_Var=$Act_Cod."00";
                break;
                case 11:
                $Act_Var=$Act_Cod."0";
                break;
            }
            $Act_Bar=$Act_Var;
            $Act_Gen='G';
            //Update para agregar el código de barras
            $obBD_con1->operacionobBD(603, $Act_Cod.'*'.$Act_Bar.'*'.$Act_Gen, $obBD_conexion);
            //Insert en la tabla activo_compra
            if($Cop_Int==""){$obBD_con1->operacionobBD(625, $Act_Cod.'*'.$Cop_Cod.'*'.$Cop_Int.'*'.$Pro_Cod, $obBD_conexion);}
            else{
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
    }
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
    $codigos = substr($codigos, 0, -1);
    $responce['Act_Cod']=$codigos;
    if($obBD_con1->Error==0){ $responce['success']=true; }else{$responce['message']=$obBD_con1->MsgError;}  
    echo json_encode($responce);
    exit();
}
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
                                <label class="col-md-5 control-label label-xs" for="Cop_Fec">Fecha Inicio Depreciación:</label>
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
                    <div class="panel-group" id="accordion">
    <div class="panel panel-primary">
      <div class="panel-heading">
        <h4 class="panel-title">
          <a data-toggle="collapse" data-parent="#accordion" href="#collapse1">Registro Cabecera Activo</a>
        </h4>
      </div>
      <div id="collapse1" class="panel-collapse collapse in">
        <div class="panel-body">Lorem ipsum dolor sit amet, consectetur adipisicing elit,
        sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam,
        quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</div>
      </div>
    </div>
    <div class="panel panel-default">
      <div class="panel-heading">
        <h4 class="panel-title">
          <a data-toggle="collapse" data-parent="#accordion" href="#collapse2">Registro Detalle Activo</a>
        </h4>
      </div>
      <div id="collapse2" class="panel-collapse collapse">
        <div class="panel-body">Lorem ipsum dolor sit amet, consectetur adipisicing elit,
        sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam,
        quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</div>
      </div>
    </div>
    <div class="panel panel-default">
      <div class="panel-heading">
        <h4 class="panel-title">
          <a data-toggle="collapse" data-parent="#accordion" href="#collapse3">Elección Cuenta Contable</a>
        </h4>
      </div>
      <div id="collapse3" class="panel-collapse collapse">
        <div class="panel-body">Lorem ipsum dolor sit amet, consectetur adipisicing elit,
        sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam,
        quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</div>
      </div>
    </div>
    <div class="panel panel-default">
      <div class="panel-heading">
        <h4 class="panel-title">
          <a data-toggle="collapse" data-parent="#accordion" href="#collapse2">Elección Imágenes Activo</a>
        </h4>
      </div>
      <div id="collapse2" class="panel-collapse collapse">
        <div class="panel-body">Lorem ipsum dolor sit amet, consectetur adipisicing elit,
        sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam,
        quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</div>
      </div>
    </div>
    <div class="panel panel-default">
      <div class="panel-heading">
        <h4 class="panel-title">
          <a data-toggle="collapse" data-parent="#accordion" href="#collapse3">Proyección Depreciación Activo</a>
        </h4>
      </div>
      <div id="collapse3" class="panel-collapse collapse">
        <div class="panel-body">Lorem ipsum dolor sit amet, consectetur adipisicing elit,
        sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam,
        quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</div>
      </div>
    </div>
  </div>

                    <div id="tabs" class="ui-tab-fix">
                        <ul style="font-size: 12px;">
                            <li><a href="#info_activo">Activo</a></li>
                            <li><a href="#detalle_activo">Detalle</a></li>
                            <li><a href="#cuenta_contable">Cuenta Contable</a></li>
                            <li><a href="#imagenes">Im&aacute;genes</a></li>
                            <li><a href="#depreciacion_activo">Depreciaci&oacute;n</a></li>
                        </ul>
                        <form id="formActivo" class="form-horizontal normal" enctype="multipart/form-data" action="javascript:saveForm();">
                        <div id="info_activo">
                            <!-- Sección para ingresar los datos de registro del activo -->
                            <div class="row">
                            <div class="col-sm-12">
                                <fieldset class="exa-fieldset">                           
                                    <legend class="Titulos2">Formulario de Registro</legend>
                                    <!-- Código de Activo necesario para guardar las imagenes -->
                                    <input type="hidden" name="Act_Cod" id="Act_Cod" value="" />
                                    <!-- Código del Perito almacenado o asignado a la variable oculta Pri_Cod -->
                                    <input type="hidden" name="Pri_Cod" id="Pri_Cod" value=""/>
                                    <!-- Código del proveedor -->
                                    <input type="hidden" name="Prv_Cod" id="Prv_Cod" value=""/>
                                    <!-- Código de la factura de compra -->
                                    <input type="hidden" name="Cop_Cod" id="Cop_Cod" value=""/>
                                    <!-- Código del detalla de la factura de compra -->
                                    <input type="hidden" name="Cop_Int" id="Cop_Int" value=""/>
                                    <!-- Código de la cuenta depreciacion -->
                                    <input type="hidden" name="Cod_Dep" id="Cod_Dep" value=""/>
                                    <!-- Código de la cuenta depreciacion acumulada -->
                                    <input type="hidden" name="Cod_Dea" id="Cod_Dea" value=""/>
                                    <!-- Código del producto -->
                                    <input type="hidden" name="Pro_Cod" id="Pro_Cod" value=""/>
                                    <!-- Campo para guardar la fecha final de depreciación -->
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
                                        <!-- Select básico-->
                                        <div class="form-group">
                                            <label class="col-md-3 control-label label-xs required" for="des_padre">Categor&iacute;a:</label>  
                                            <div class="col-md-9">
                                                <?php $row_rs_tia_tip = $obBD_con1->getArrayConsulta(615, $Ses_Emp_Cod, $obBD_conexion);?>
                                                <select name="Tia_Cod" id="Tia_Cod" class="form-control input-xs required" data-placeholder="Seleccione una categor&iacute;a de activo">
                                                    <option value=""></option>
                                                    <?Php                                         
                                                    foreach($row_rs_tia_tip as $row)
                                                        { ?>
                                                    <option value="<?Php echo $row['Tia_Cod'];?>" ><?Php echo mb_convert_encoding($row['descripcion'], 'ISO-8859-1', 'UTF-8');?></option>
                                                    <?Php 
                                                        } ?>
                                                </select>
                                            </div>
                                        </div>
                                        <!-- Text input-->
                                        <div class="form-group">
                                            <label class="col-md-3 control-label label-xs required" for="des_padre">Cód. Barras:</label>  
                                            <div class="col-md-7">
                                                <div class="input-group">
                                                    <input id="Act_Bar" name="Act_Bar" type="text" placeholder="" class="form-control input-sm" value="" required />
                                                    <span class="input-group-addon input-sm">
                                                        <input name="Act_Bar1" type="checkbox" id="Act_Bar1"  value="1" checked>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Muestra información cuando esta seleccionado el checkbox -->
                                        <div class="form-group">
                                            <label class="col-md-3 control-label label-xs"></label>
                                            <div id="mensaje_codigo" class="col-md-8" style="font-size:11px; font-weight:bold;">Generar c&oacute;digo autom&aacute;ticamente <span class="glyphicon glyphicon-ok"></span></div>
                                        </div>
                                        <!-- Textarea -->
                                        <div class="form-group">
                                            <label class="col-md-3 control-label required" for="Act_Des">Descripción:</label>
                                            <div class="col-md-9">                     
                                                <textarea class="form-control input-xs" id="Act_Des" name="Act_Des" required></textarea>
                                            </div>
                                        </div>
                                        <!-- Textarea -->
                                        <div class="form-group">
                                            <label class="col-md-3 control-label" for="Act_Obs">Observación:</label>
                                            <div class="col-md-9">                     
                                                <textarea class="form-control input-xs" id="Act_Obs" name="Act_Obs"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                            
                                    <div class="col-sm-4 col-md-5">
                                        <!-- Text input -->
                                        <div class="form-group">
                                            <label class="col-md-5 control-label label-xs required" for="Act_Val">Vida &Uacute;til (años):</label>
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
                                            <label class="col-md-5 control-label label-xs required" for="des_padre">Código Secuencia:</label>  
                                            <div class="col-md-5">
                                                <input id="Act_Cdc" name="Act_Cdc" type="text" placeholder="" class="form-control input-xs" value="" style="text-align: right;" required />
                                            </div>
                                        </div>
                                        <!-- Text input-->
                                        <div class="form-group">
                                            <label class="col-md-5 control-label label-xs required" for="des_padre">Cantidad:</label>  
                                            <div class="col-md-5">
                                                <input id="Act_Can" name="Act_Can" type="text" placeholder="" class="form-control input-xs" value="" style="text-align: right;" onkeypress="return validar_numeric(event);" required readonly="" title="No Editable"/>
                                            </div>
                                        </div>
                                        <!-- Text input-->
                                        <div class="form-group">
                                            <label class="col-md-5 control-label label-xs required" for="des_padre">Garantía (meses):</label>  
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
                            <!-- Fin de la sección para registrar los activos -->
                        </div>
                        
                        <div id="imagenes">
                            <div class="row">   
                                <!-- Sección para seleccionar las foto(s) correspondientes a activos -->
                                <div class="col-xs-12">
                                    <fieldset class="exa-fieldset">                           
                                        <legend class="Titulos2">Foto</legend>
                                        <input id="file5" name="file5" class="" type="file" multiple data-preview-file-type="any" />
                                    </fieldset>
                                </div>
                            </div>
                        </div>
                        
                        <div id="depreciacion_activo">
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
                                        <div class="col-sm-4 col-md-5">
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
                        
                        <div id="detalle_activo">
                            <form id="formcampos" name="formcampos" class="form-horizontal normal" action="javascript:">
                                <!-- Sección para ingresar los datos de registro del activo -->
                                <div class="row">   
                                    <!-- Sección para presentar los campos del tipo de activo seleccionado -->
                                    <div class="col-xs-12">
                                        <fieldset class="exa-fieldset">                           
                                            <legend class="Titulos2">Campos de Tipo de Activo</legend>
                                            <div id="campos_nuevos" class="col-sm-12"></div>
                                        </fieldset>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <div id="cuenta_contable">
                            <form id="formcuenta_Contable" name="formcuenta_Contable" class="form-horizontal normal" action="javascript:">
                                <!-- Sección para seleccionar las cuentas contables de depreciación y depreciación acumulada -->
                                <div class="row">
                                    <div class="col-sm-6">
                                        <fieldset class="exa-fieldset">                           
                                            <legend class="Titulos2">Asignaci&oacute;n de Cuenta Contable</legend>

                                            <div class="form-group">
                                                <label class="control-label label-sm required">Peri&oacute;do contable:</label>
                                                <select name="periodo_c" id="periodo_c" class="form-control input-sm" style="text-align:center; display:inline-block; width: auto;" required>
                                                    <option value="">Seleccione</option>
                                                    <?PHP 
                                                        $rs_periodos=$obBD_con1->getArrayConsulta(624,$Ses_Emp_Cod, $obBD_conexion);
                                                        if(count($rs_periodos)>0){
                                                            foreach ($rs_periodos as $row)
                                                            { ?>
                                                                <option value="<?PHP echo $row['Pec_Cod'].'*'.$row['Periodo'];?>"><?PHP echo $row['Periodo'];?></option>
                                                            <?PHP }
                                                        }
                                                    ?>
                                                </select>
                                            </div>

                                            <div style="padding-bottom: 5px">
                                                <table id="depreciacion"></table>
                                                <div id="depreciacionPager"></div>
                                            </div>
                                            <div style="padding-bottom: 5px">
                                                <button id="btnDep" name="btnDep" onclick="tipo='D';$('#cuentaDialog').dialog('open');" title="Buscar Cuenta" type="button" class="btn btn-success btn-xs"><i class="glyphicon glyphicon-list"></i><span> Seleccionar Cuenta</span></button>                                            
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
    
    <!-- Inicio del diálogo para buscar un producto en la tabla de compras -->
    <div id="productoCDialog" title="B&uacute;squeda de Productos con Factura de Compra">
        <form class="form-horizontal normal">
            <fieldset class="exa-fieldset">
                <legend class="Titulos2">Filtros</legend>
                <div class="form-group">
                    <label class="col-md-2 control-label label-xs">Filtrar Por:</label>
                    <div class="col-md-5 radioset">
                        <input id="rad1" name="op_producto" type="radio" value="d" checked="" onclick="setfocus(this.form.search_producto)"/><label for="rad1">&nbsp;&nbsp;Producto&nbsp;&nbsp;</label>
                        <input id="rad2" name="op_producto" type="radio" value="c" onclick="setfocus(this.form.search_producto)"/><label for="rad2">&nbsp;&nbsp;Num. Factura&nbsp;&nbsp;</label>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-xs-2 control-label">B&uacute;squeda:</label>
                    <div class="col-md-7">
                        <div class="input-group">
                            <input name="search_producto" onkeydown="if(event.keyCode===13)this.form.submit()" type="text" size="50" placeholder="Ingrese producto a buscar..." autofocus class="form-control input-xs" />
                            <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-xs" title="Buscar producto"><span class="glyphicon glyphicon-search"></span> <span> Buscar</span></button></span> 
                        </div>
                    </div>
                </div>
            </fieldset>
        </form>
    </div>
    
    <!-- Inicio del diálogo para buscar un productos que no se encuentran en una factura de compra -->
    <div id="productoSDialog" title="B&uacute;squeda de Productos sin Factura de Compra">
        <form class="form-horizontal normal">
            <fieldset class="exa-fieldset">
                <legend class="Titulos2">Filtros</legend>
                <div class="form-group">
                    <label class="col-md-2 control-label label-xs">Filtrar Por:</label>
                    <div class="col-md-5 radioset">
                        <input id="rad11" name="op_producto1" type="radio" value="d" checked="" onclick="setfocus(this.form.search_producto1)"/><label for="rad11">&nbsp;&nbsp;Producto&nbsp;&nbsp;</label>
                        <input id="rad22" name="op_producto1" type="radio" value="c" onclick="setfocus(this.form.search_producto1)"/><label for="rad22">&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;</label>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-xs-2 control-label">B&uacute;squeda:</label>
                    <div class="col-md-7">
                        <div class="input-group">
                            <input name="search_producto1" onkeydown="if(event.keyCode===13)this.form.submit()" type="text" size="50" placeholder="Ingrese producto a buscar..." autofocus class="form-control input-xs" />
                            <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-xs" title="Buscar producto"><span class="glyphicon glyphicon-search"></span> <span> Buscar</span></button></span> 
                        </div>
                    </div>
                </div>
            </fieldset>
        </form>
    </div>
    
    <!-- Inicio del diálogo para buscar un perito --> 
    <div id="peritoDialog" title="B&uacute;squeda de Perito">  
        <form class="form-horizontal normal"> 
            <fieldset class="exa-fieldset">
                <legend class="Titulos2">Filtros</legend>
                <div class="form-group">
                    <label class="col-md-2 control-label label-xs">Filtrar Por:</label>  
                    <div class="col-md-8 radioset" >
                        <input id="rad3" name="op_perito" type="radio" value="d" checked="" onclick="setfocus(this.form.search_perito)" alt="" /><label for="rad3">&nbsp;&nbsp;Apellido&nbsp;&nbsp;</label>
                        <input id="rad4" name="op_perito" type="radio" value="c" onclick="setfocus(this.form.search_perito)" alt="" /><label for="rad4">&nbsp;&nbsp;Cédula/R.U.C.&nbsp;&nbsp;</label>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-2 control-label">B&uacute;squeda:</label>
                    <div class="col-md-7" >                 
                        <div class="input-group">                        
                            <input name="search_perito" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese perito a buscar..." autofocus class="form-control input-sm " /><input type="text" style="display:none"/>
                            <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar perito" ><span class="glyphicon glyphicon-search"></span> <span> Buscar</span></button></span>
                        </div>                         
                    </div>                    
                </div>
            </fieldset>  
        </form>    
    </div>
    
    <!-- Inicio del diálogo para buscar un proveedoor --> 
    <div id="provDialog" title="B&uacute;squeda de Proveedores">  
        <form class="form-horizontal normal"> 
            <fieldset class="exa-fieldset">
                <legend class="Titulos2">Filtros</legend>
                <div class="form-group">
                    <label class="col-md-2 control-label label-xs">Filtrar Por:</label>  
                    <div class="col-md-8 radioset">
                        <input id="rad5" name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="rad5">&nbsp;&nbsp;Apellido&nbsp;&nbsp;</label>
                        <input id="rad6" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" /><label for="rad6">&nbsp;&nbsp;Cédula/R.U.C.&nbsp;&nbsp;</label>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-2 control-label">B&uacute;squeda:</label>
                    <div class="col-md-7">                 
                        <div class="input-group">                        
                            <input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese proveedor a buscar..." autofocus class="form-control input-sm " /><input type="text" style="display:none"/>
                            <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar proveedor" ><span class="glyphicon glyphicon-search"></span> <span> Buscar</span></button></span>
                        </div>                         
                    </div>                    
                </div>
            </fieldset>  
        </form>    
    </div>
    
    <!-- Inicio del diálogo para buscar una cuenta contable -->
    <div id="cuentaDialog" title="B&uacute;squeda de Cuenta Contable">
        <form class="form-horizontal normal">
            <fieldset class="exa-fieldset">
                <legend class="Titulos2">Filtros</legend>
                <div class="form-group">
                    <label class="col-xs-2 control-label label-xs">Filtrar Por:</label>
                    <div class="col-xs-5 radioset">
                        <input id="rad7" name="op_cuenta" type="radio" value="d" checked="" onclick="setfocus(this.form.search_cuenta)"/><label for="rad7">&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;</label>
                        <input id="rad8" name="op_cuenta" type="radio" value="c" onclick="setfocus(this.form.search_cuenta)"/><label for="rad8">&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;</label>
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
                            <input name="search_cuenta" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese cuenta a buscar..." autofocus  class="form-control input-sm "/>
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
    //Sección para asignar la función de tabs a través de jquery
    $("#tabs").tabs();
    $('#tabs').tabs({disabled:[0,1,2,3,4]});
    //Sección para declarar datepicker
    $.createDatePickers("#Cop_Fec");
    $("#Cop_Fec").val('');
    //Sección para calcular el porcentaje de depreciación
    $(document).ready(function(){
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
        //Deshabilito botones de la pestaña cuenta contable
        $("#btnDep").attr('disabled',true);
        $("#btnDepAcum").attr('disabled',true);
        //Asignamos la opción de datepicker
        $.createDateRange('#Act_Fec');
        //Asignamos la función de Chosen
        $("#Tia_Cod").createChosen('input-xs',{allow_single_deselect: true});                
    });
	
    //Seccion para cargar campos segun el tipo de activo seleccionado
    $(document).ready(function()
    {
        /*Sección para extraer los campos de un tipo de activo*/
        $('#Tia_Cod').on('change', function() {
            var id=this.value; 
            var codigo={Tia_Cod:id,buscarCampos:true};
            $.post('<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',codigo, function( response ){
                campos_nuevos=response;
                /*Llama a la función addcampos para agregar los campos que pertenecen al tipo de activo seleccionado*/
                addcampos(); 
            },'json');
        });
    });
    
    //Función para aderir campos del tipo de activo
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
    
    //Sección para comboBox de periodo
    $(document).ready(function(){
        $('#periodo_c').on('change', function() {
            var resultado=$(this).val();
            if(resultado!==''){
                var per=resultado.split("*");
                $("#Pec_Cod").val(per['0']);
                $("#periodo").val(per['1']);
                $('#cuentaGrid').Search('#cuentaForm','cuentaAjax');
                $("#btnDep").attr('disabled',false);
                $("#btnDepAcum").attr('disabled',false);
            }
        });
    });
    
    //Sección para bloquear el ingreso de código de barras cuando este marcado el checkbox	
    $(document).ready(function(e) {
        $('#Act_Bar').attr('readonly',true);
        $('#Act_Bar1').click(function(e) {
            if($('#Act_Bar1').is(':checked'))
            {
                $('#Act_Bar').attr('readonly',true);
                $('#Act_Bar').val('');
                $('#mensaje_codigo').html('Generar código automaticamente <span class="glyphicon glyphicon-ok"></span>');
            }
            else
            {
                $('#mensaje_codigo').html('Digíte código manualmente.');
                $('#Act_Bar').attr('readonly',false);
            }
        });
    });
    
    //Sección para realizar el calculo de la depreciación anual dentro del tab=depreciacion
    //Función para obtener el último día del mes 
    function daysInMonth(month, year) {
        var last_day=new Date(year || new Date().getFullYear(), month, 0).getDate();
        if(parseInt(month)===02){last_day=28;}
        month=('0'+month).slice(-2);
        var last_date=year+'-'+month+'-'+last_day;
        return [last_day,last_date];
    }
    
    //Función para calcular la depreciación segun el número de días
    //val_com=valor de compra
    //val_res=valor residual
    //vid_uti=vida util del activo
    //dias=número de días a depreciar
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

        /*ÚNICA Y EXCLUSIVAMENTE CUANDO LA FECHA DE COMPRA SEA EL 29 DE FEBRERO*/
        var febrero=cop_fec.split('-');if(febrero[1]==='02'){cop_fec=febrero[0]+'-02-28';}

        /*** CALCULO DE LA DEPRECIACIÓN ANUAL Y MENSUAL ***/
        //Sección para autoajuste del jqgrid
        if($('#dep_anual').actual( 'outerWidth', { includeMargin : true })<300){ $('#dep_anual').trigger('resize');}

        //Se limpia el jqGrid de la depreciación anual 
        $("#dep_anual").jqGrid('clearGridData',true).trigger('reloadGrid');

        //Calculo para obtener el valor a depreciar anualmente
        var datos=[],dep_acum=0,val_res=0,dep_acum_men=0,val_libros=cop_pru;
        var fecha=cop_fec.split('-');//Se descompone la fecha de compra
        var anio=fecha[0];var aux_mes=fecha[1];var mes=fecha[1];var dia=fecha[2];var meses=12;var dep_mensual=0;var i=0;var Act_Ffd='';
        var anio_fin_dep=parseInt(anio)+parseInt(vid_uti);//Se establece el último año de depreciación
        var fecha_compra=new Date(anio,mes-1,dia);
        var fecha_fin_anio=new Date(anio,'11','31');
        var diferencia=fecha_fin_anio-fecha_compra;if(diferencia===0){diferencia=1;}//Se resta la fecha de fin de año con la fecha de compra 

        //Resultado en milisegundos, por tal razón se debe convertir a días
        //se suma un día pues se desea iniciar la depreciación desde la fecha de compra 
        var dias=(Math.floor(diferencia / (1000 * 60 * 60 * 24)));
        if(dias<365){dias=dias+1;}else{i=1;}

        var dep_anual=depreciacion(cop_pru,act_res,vid_uti,dias);

        //Calculo depreciación mensual
        var ult_dia=daysInMonth(aux_mes,anio);var dias_dep=(ult_dia[0]-dia)+1;

        $("#dep_anual").jqGrid('addRowData',0,{"periodo":"Apertura","Val_Res":cop_pru});

        for(i; i<=vid_uti; i++){
            if(anio===anio_fin_dep){
                dias=365-dias;
                dep_anual=depreciacion(cop_pru,act_res,vid_uti,dias);
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
                public $activeTab = $('#tabs').tabs('option','active');
                if($activeTab === 4){calcula_depreciacion();}
            }
        });
    });
    
    //Inicio del diálogo producto 
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
                    public $input=$('<input id="Chk_'+rowObject.llave+'" type="checkbox"'+(cellvalue?'checked="checked"':'')+' onclick="cargar($(this).data(\'orig\'))" />');                    
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
        }).getDialogGrid().setGridNoPager();
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
            if((items[0]['Cop_Cod']!==producto.Cop_Cod)||(items[0]['Iva_Cos']!==producto.Iva_Cos)||(items[0]['Pro_Cod']!==producto.Pro_Cod))
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
        console.log(items);
    }
    
    function buscarIndex(arreglo, campo, valor) {
        for (var i = 0; i < arreglo.length; i++){
            if (arreglo[i][campo] === valor){return i;}
        }
        return null;
    }
        
    //Función para cargar valores del producto seleccionado
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
        $('#Act_Can').attr('readonly',true);$('#Act_Can').attr('title','No Editable');
    }
    
    //Sección para recalcular iva,total y valor a depreciar
    $('#Cop_Pru').on('input',function (){
        var iva=($('#Cop_Pru').val()*iva_por)/100;
        $("#Iva_Por").val(iva.toFixed(2));
        var total=parseFloat($('#Cop_Pru').val())+parseFloat(iva);
        $('#subtotal').val(total.toFixed(2)); 
        if(($('#Iva_Cos').val()==='SI')||($('#Iva_Cos_SFC').val()==='S')){$('#total').val(total);}else{$('#total').val($('#Cop_Pru').val());}
    });
    
    //Inicio del diálogo para presentar productos sin factura de compra
    $(document).ready(function() {               
        $.createSearchDialog('#productoSDialog',[
            { label: 'Cód.Int.', name: 'Pro_Cod', key: true,hidden:true,viewable: true },                                
            { label: 'Producto', name: 'Ite_Lar', width: 50 },
            { label: 'P.Unitario', name: 'Pre_Pvp', width: 50 }, 
            { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center',viewable: false,
                formatter:function (cellvalue, options, rowObject) { 
                    return $.getGridButton(cargarProductoSFC,rowObject);
                }
            }
        ]);  						 
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
        $.post( "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",data, function(response) 
        {
            $("#Prv_Cod").val(response['row']['Prv_Cod']);  
        },'json').fail(function(error) { $.alert();}); 
    }
    
    $('#Iva_Cos_SFC').change(function (){
        if($('#Iva_Cos_SFC').val()==='S'){
            $('#total').val($('#subtotal').val());
        }else{$('#total').val($('#Cop_Pru').val());}
    });
    
    //Inicio del diálogo perito 
    $(document).ready(function() {               
        $.createSearchDialog('#peritoDialog',[
            { label: 'Cód.Int.', name: 'Pri_Cod', key: true,hidden:true,viewable: true },                                
            { label: 'Cédula/R.U.C.', name: 'Prs_Ced', width: 50 },                      
            { label: 'Perito', name: 'perito', width: 190, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},                            
            { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center',viewable: false,
                formatter:function (cellvalue, options, rowObject) { 
                    var clic='$("#perito").val("'+rowObject.perito+'");$("#Pri_Cod").val("'+rowObject.Pri_Cod+'");$("#peritoDialog").dialog("close");';
                    return  '<span class="btn btn-success btn-xs" title="Seleccionar" onclick=\''+clic+'\'><i class="glyphicon glyphicon-arrow-right"></span>'; 
                }
            }
        ]);  						 
    }); 
    
    //Inicio del diálogo proveedor 
    $(document).ready(function() {               
        $.createSearchDialog('#provDialog',[
            { label: 'Cód.Int.', name: 'Prv_Cod', key: true,hidden:true,viewable: true },                                
            { label: 'Cédula/R.U.C.', name: 'Prs_Ced', width: 50 },                      
            { label: 'Proveedor', name: 'proveedor', width: 190, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},                   
            { label: 'Direcci&oacute;n', name: 'Prs_Dir',hidden:true,viewable: true },                      
            { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center',viewable: false,
                formatter:function (cellvalue, options, rowObject) { 
                    var clic='$("#Prv_Nom").val("'+rowObject.proveedor+'");$("#Prv_Cod").val("'+rowObject.Prv_Cod+'");$("#provDialog").dialog("close");';
                    return  '<span class="btn btn-success btn-xs" title="Seleccionar" onclick=\''+clic+'\'><i class="glyphicon glyphicon-arrow-right"></span>'; 
                }
            }
        ]); 
    }); 
    
    //Inicio de diálogo para buscar cuentas
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
        ]);   
    });
    
    //Función para agregar cuenta contable al jqgrid 
    function addCuenta(Pld_Cod,Pld_Cdc,Pld_Des)
    {
        //Se cierra el diálogo de las cuentas
        $('#cuentaDialog').dialog('close');
        if(tipo==='D')
        {
            //Se carga el codigo de la cuenta depreciacion al campo oculto Cod_Dep
            $("#Cod_Dep").val(Pld_Cod);
            //Se carga los valores seleccionados a los grid de las cuentas de depreciación
            $("#depreciacion").jqGrid('addRowData', Pld_Cod, {"Pld_Cod":Pld_Cod,"Pld_Cdc": Pld_Cdc,"Pld_Des": Pld_Des});
            //Deshabilito el boton para agregar cuenta
            $("#btnDep").attr('disabled','disabled');
        }else{
            //Se carga el codigo de la cuenta depreciacion acumulada al campo oculto Cod_Dea
            $("#Cod_Dea").val(Pld_Cod);
            //Se carga los valores seleccionados a los grid de las cuentas de depreciación
            $("#depreciacion_acum").jqGrid('addRowData', Pld_Cod, {"Pld_Cod":Pld_Cod,"Pld_Cdc": Pld_Cdc,"Pld_Des": Pld_Des});
            //Deshabilito el boton para agregar cuenta
            $("#btnDepAcum").attr('disabled','disabled');
        }
    }
    
    //Inicio de diálogo para presentar la cuenta de depreciación
    $(document).ready(function() { 
        var Tipo='D';
        $("#depreciacion").jqGrid({
            url:'<?PHP echo filter_input(INPUT_SERVER,'PHP_SELF',FILTER_SANITIZE_STRING)?>',
            mtype:'GET',
            datatype:'local',
            regional:'es',
            autowidth:true,
            shrinkToFit:true,
            height:40,
            cmTemplate:{sortable:false},
            caption:'DEPRECIACI&Oacute;N &raquo; Cuenta Contable',
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
            rowNum: 20, pager: "", gridview: true, rownumbers: true, viewrecords: true, altRows: true, altclass: "myAltRowClass"
        });
    });
    
    //Inicio de diálogo para presentar la cuenta de depreciación acumulada
    $(document).ready(function(){
        var Tipo='DA';
        $("#depreciacion_acum").jqGrid({
            url:'<?PHP echo filter_input(INPUT_SERVER,'PHP_SELF',FILTER_SANITIZE_STRING)?>',
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
    
    //Elaboración de jqGrid para presentar el calculo de la depreciación anual del activo
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
                {label:'Peri&oacute;do',align:'center',name:'periodo'},
                {label:'Valor Depreciaci&oacute;n',align:'center',name:'Val_Dep'},
                {label:'Depreciaci&oacute;n Acumulada',align:'center',name:'Dep_Acu'},
                {label:'Valor en Libros',align:'center',name:'Val_Res'}
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
                {label:'Valor Depreciaci&oacute;n',align:"center",name:'Val_Dep',width:140},
                {label:'Depreciaci&oacute;n Acumulada',align:"center",name:'Dep_Acu',width:180},
                {label:'Valor en Libros',align:"center",name:'Val_Res',width:130}
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
        (Tipo==='D')?($('#depreciacion').jqGrid('delRowData',Pld_Cod),$("#btnDep").attr('disabled',false)):($('#depreciacion_acum').jqGrid('delRowData',Pld_Cod),$("#btnDepAcum").attr('disabled',false));
    }
    
    function inicializar_input_file(){
        $("#file5").fileinput({
            uploadUrl: '<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>', // you must set a valid URL here else you will get an error
            showCaption: false,
            showRemove: false,
            showCancel: false,
            browseClass: "btn btn-success btn-sm",
            browseLabel: 'Buscar Imagen',
            uploadClass: 'btn btn-success btn-sm hide',
            allowedFileExtensions : ['jpg', 'png','gif'],
            overwriteInitial: false,
            maxFileSize: 2000,
            msgSizeTooLarge: 'Archivo: "{name}" (<b>{size} KB</b>) excede el tamaño máximo permitido de <b>{maxSize} KB</b>.',
            dropZoneTitle:'Arrastrar y Soltar Imágenes Aquí...',
            maxFileCount: 2,
            msgFilesTooMany: 'Número de imágenes permitidas 2.',
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
            uploadExtraData: function(){
                return {uploadfoto2: true,Act_Cod:$('#Act_Cod').val()};
            },
            slugCallback: function(filename) {
                return filename.replace('(', '_').replace(']', '_');
            }
	});
    }
    
    /*Función para guardar un nuevo activo se lo efectua con formData, puesto que se esta enviando imagenes*/
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
        //Sección para validar que elija una cuenta contable
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
            url: '<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',
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
                $("#file5").fileinput({uploadExtraData: {uploadfoto2: true,Act_Codi:responce.Act_Cod}});
                $('#file5').parent().parent().find('.fileinput-upload-button').trigger('click');  
                $.alert("Transaccion Realizada con &Eacute;xito!");
                limpiar();
                $('#file5').fileinput('clear');
                $('#tabs').tabs({disabled:[0,1,2,3,4]});$('#btguardaractivo').prop('disabled',true);  
                //Sección para actualizar el GridDialog de productos con factura de compra
                $('#productoCDialog').getDialogGrid().trigger('reloadGrid',[{page:1}]);
                items=[];
            }else{$.alert(responce.message);}
        });  
    };
   
    function limpiar(){
        /*Sección para limpiar el chosen*/
        $('#Tia_Cod').val('').trigger('chosen:updated');
        /*Sección para limpiar formularios*/ 
        $('#formInfo')[0].reset();
        $('#formActivo')[0].reset();
        $('#depreciacion').jqGrid('clearGridData',true).trigger('reloadGrid');
        $('#depreciacion_acum').jqGrid('clearGridData',true).trigger('reloadGrid');
        $('#dep_anual').jqGrid('clearGridData',true).trigger('reloadGrid');
        $('#dep_mensual').jqGrid('clearGridData',true).trigger('reloadGrid');
        $('#campos_nuevos').html("<div class='col-sm-4'></div><div class='alert alert-info col-sm-4' role='alert'><u><span class='glyphicon glyphicon-info-sign'></span><b> NOTA: </b></u> No se ha elegido una categor&iacute;a para el activo..!!</div>");
        $('#datosPerito').html('Sin índice de búsqueda');
        $('#Tia_Des').val('Seleccione un Tipo de Activo'); 
        inicializar_input_file();
        $('#periodo_c').val('');
    }
    /*Función para visiaulizar mensaje de alerta dentro del tab cuenta contable*/
    function showAlert(){
        $("#alert_cc").show();$("#alert_cc").alert();
        $("#alert_cc").fadeTo(7000, 500).slideUp(500, function(){$("#alert_cc").slideUp(500);});   
    }
   </script>
   <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
</BODY>
</HTML>