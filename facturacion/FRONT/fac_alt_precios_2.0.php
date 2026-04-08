<?php

include 'vendor/php-excel-reader/excel_reader2.php' ;
include 'vendor/SpreadsheetReader.php';

/**
 * @abstract Permite realizar la modificacion de productos
 * @author Erick Cordova
 * @version 1.0
 * Fecha de creacion  2017-11-21
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_producto_mod.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');


//require_once('../../Librerias/postclass.php');
/**
 * Creacion del Objeto de conexion
 */

$obBD_conexion = new Class_Log_Conexion_Pro($Ses_Dat_Dis);

/**
 * Creacion del Objeto para consultas
 */

$obBD_con1 = new Class_Log_Datos_Pro;

function reverse_number($number){
    /* Convert them into an array. */
    $arr = str_split($number);
    /* Reverse the array. */
    $rev_arr = array_reverse($arr);
    /* Implode them. */
    $rev = implode("",$rev_arr);
   return $rev;
}

if(isset($CatSelect)){
    $rs_tpaj= $obBD_con1->getArrayConsulta(41, $Ses_Emp_Cod.'*'.$CatSelect, $obBD_conexion);
    $Cat_Cod=$CatSelect;
    echo "<option value=''>Todas</option>";
    foreach ($rs_tpaj as $row) 
        echo utf8_encode("<option value='$row[Cat_Cod]'>$row[Cat_Des]</option>");        
    exit();
}

if (isset($prodAjax)) {
    // $obBD_con1->debug(true);
    $data = $_GET;
    $data['Suc_Cod'] = $Ses_Suc_Cod;
    $data['Emp_Cod'] = $Ses_Emp_Cod;
    if(!isset($data['Filtros'])) $data['Filtros']='';


    $data['Order'] = " ORDER BY " . ($grupo == 'clear' ? 'Ite_Lar' : $grupo);
    if ($letra == 'TODOS') {
        $search=""; 
        $array=explode(" ",strtoupper($txt_busqueda));
        foreach($array as $ar){
            if(!empty($ar) && $ar!='') $search.=(($search!=''?" AND ":"")."CAST(UPPER(CONCAT(Ite_Lar,Pro_Obs)) AS CHAR)LIKE '%$ar%'");                    
        }
        if($search=='') $search="1=1";
            
        $data['Filtros'] = $data['Filtros'] . " sucursal.Suc_Cod=". $Ses_Suc_Cod." AND ".$search;
    } else {
        $data['Filtros'] = "Ite_Lar LIKE '$letra%' ";
    }
    
    if ($Cate_Cod != '' and $Sub_Cod == '') {
            $data['Filtros'] = $data['Filtros'] . " AND categorias.Cat_Rec=$Cate_Cod ";
        }
        if ($Cate_Cod != '' and $Sub_Cod != ''){
            $data['Filtros'] = $data['Filtros'] . " AND item.Cat_Cod=$Sub_Cod ";
        }
        /*if ($Ubi_Cod != '') {
        $data['Filtros'] = $data['Filtros'] . " AND sucursal.Suc_Cod=". $Ses_Suc_Cod." AND producto.Ubi_Cod=$Ubi_Cod ";
    }
    if ($Lin_Cod != '') {
        $data['Filtros'] = $data['Filtros'] . " AND sucursal.Suc_Cod=". $Ses_Suc_Cod." AND producto.Lin_Cod=$Lin_Cod ";
    } *///else{
    //$data['Filtros']=$data['Filtros']." AND producto.Lin_Cod IS NULL ";
    // }
    $data['Filtros'] = $data['Filtros'] . " AND sucursal.Suc_Cod=". $Ses_Suc_Cod;
    $datos = $obBD_con1->getArrayConsulta(18, $data, $obBD_conexion);

    foreach ($datos as $key => $value) {
        $cod = $value['Pro_Cod'];
        $fila = $obBD_con1->getArrayConsulta(36, $cod, $obBD_conexion);

        $i = 1;
        foreach ($fila as $llave => $valor){
            $nombre=$valor['Tpv_Cod'];
            $datos[$key][$nombre]=$valor['Pre_Pvp'];
            $i++;
        }
    }

    $pagination = pages(count($datos), $page, $rows);
    $responce= $pagination['data'];
    $responce['rows'] = $datos;
    $responce['success'] = true;
    utf8_encode_deep($responce['rows']); echo json_encode($responce);exit();
}

if(isset($validaIteLar)){
    // $obBD_con1->debug(true);
    $conteo= $obBD_con1->getRowConsulta(26,$Ses_Emp_Cod.'*'.trim($Ite_Lar).'*'.$Pro_Cod,$obBD_conexion);  
    $resp=array('success'=>'');
    if($conteo['total']*1>0) {$resp=array('success'=>false, 'state'=>'warning', 'message'=>"Ya existe un producto con el nombre \"$Ite_Lar\".");}
    else{
        $resp=array('success'=>true);
    }
    echo json_encode($resp);exit();
}

if(isset($tipo_precio)){
    $respon['rows'] = $obBD_con1->getArrayConsulta(34, $Ses_Suc_Cod, $obBD_conexion);
    if($obBD_con1->Error==0){ 
        $respon['success']=true;
    }else{ 
        $respon=array('success'=>false,'message'=>"No se ha logrado realizar la Transaccion",'error'=>$obBD_con1->MsgError);
    }
    utf8_encode_deep($respon);
    echo json_encode($respon);
    exit();
}

if(isset($nameSave)){
    $resp=$_POST;
    $resp['Suc_Cod']=$Ses_Suc_Cod;    
    $sql=0;
    switch($select) {
        case 'Lin': $sql=20; break;
        case 'Mar': $sql=21; break;
        case 'Ubi': $sql=22; break;
        case 'Tpv': $sql=31; break;
        case 'Cat': 
            $sql=23; $resp['Cat_Tip']='D'; 
            $siguiente= $obBD_con1->getRowConsulta(24,$resp['Cat_Rec'],$obBD_conexion);   
            $resp['Cat_Cdc']=$resp['Rec_Cdc'].'.'.$siguiente['next'];
            break;    
    }
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);    
        $obBD_con1->operacionobBD($sql,$resp, $obBD_conexion);
        $resp[$resp['select'].'_Cod'] = $obBD_con1->insercionid ($obBD_conexion->conexion);
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);    
    if($obBD_con1->Error==0){ $resp['success']=true; }else{$resp=array('success'=>false,'message'=>"No se ha logrado realizar la Transaccion",'error'=>$obBD_con1->MsgError);}  
    utf8_encode_deep($resp); echo json_encode($resp); exit();
}

if(isset($updateProd)){
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
        $data=$_POST;
        $obBD_con1->echoLog($data);
        if($Pro_Gen == 'G'){
            $Pro_Cod=$data['Pro_Cod'];
            $numRever = reverse_number($Pro_Cod);
            if($numRever <= 1){
                $valPuro=str_pad($Pro_Cod, 12, "0");
                $genNum = mt_rand(1,19);
                $newNumGen = $valPuro + $genNum;
                $data['Pro_Bar'] = $newNumGen;
            }else{
                $valPuro=str_pad($data['Pro_Cod'], 12, "0");
                $data['Pro_Bar'] = $valPuro;
            }
        }
        $obBD_con1->echoLog($Pro_Gen);
        $update_cont=$obBD_con1->operacionobBD(27,$data,$obBD_conexion);
        $update_cont=$obBD_con1->operacionobBD(28,$data,$obBD_conexion);
        $update_cont=$obBD_con1->operacionobBD(29,$data,$obBD_conexion);
        $response['success']=true;
        $response['message']='Producto actualizado con &Eacutexito';
    } catch (Exception $ex) {
        $response['success']=false;
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $response['message']=$ex->getMessage();
    }
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    $obBD_con1->echoJson($response); 
}

if(isset($precios)){
    $precios['sucursal']=$Ses_Suc_Cod;
    $resp['rows'] = $obBD_con1->getArrayConsulta(467, $precios, $obBD_conexion);
     if($obBD_con1->Error==0){ $resp['success']=true; }else{$resp=array('success'=>false,'message'=>"No se ha logrado realizar la Transaccion",'error'=>$obBD_con1->MsgError);} 

    utf8_encode_deep($resp); 
    echo json_encode($resp); 
    exit();
}

if (isset($guardar)){
    $precios['sucursal']=$Ses_Suc_Cod;

    foreach ($guardar_precios as $key => $value)
    {
        if(empty($value['Pre_Cod']))
        {
            $obBD_con1->operacionobBD(32, array($value['Pro_Cod'], $value['Pre_Com'], $value['Pre_Fec'], $value['Pre_Fin'], $value['Pre_Ini'], $value['Pre_Por'], $value['Pre_Pvp'], $value['Pre_Uti'], $Ses_Suc_Cod, $value['Tpv_Cod'], $value['Tpv_Des']), $obBD_conexion);
            //ChromePhp::log($obBD_con1);
        }
        else{
            $obBD_con1->operacionobBD(322, array($value['Pro_Cod'], $value['Pre_Com'], $value['Pre_Fec'], $value['Pre_Fin'], $value['Pre_Ini'], $value['Pre_Por'], $value['Pre_Pvp'], $value['Pre_Uti'], $Ses_Suc_Cod, $value['Tpv_Cod'], $value['Tpv_Des'], $value['Pre_Cod']), $obBD_conexion);
            //ChromePhp::log($obBD_con1);
        }
    }

    if($obBD_con1->Error==0){ 
        $resp['success']=true; 
    }
    else{
        $resp=array('success'=>false,'message'=>"No se ha logrado realizar la Transaccion",'error'=>$obBD_con1->MsgError);
    } 
    //ChromePhp::log($obBD_con1->MsgError);
    utf8_encode_deep($resp); 
    echo json_encode($resp); 
    exit();
}


if (isset($anular)) {
    $obBD_con1->operacionobBD(33, $Pre_Cod, $obBD_conexion);

     if($obBD_con1->Error==0){ $resp['success']=true; }else{$resp=array('success'=>false,'message'=>"No se ha logrado realizar la Transaccion",'error'=>$obBD_con1->MsgError);} 

    utf8_encode_deep($resp); 
    echo json_encode($resp); 
    exit();
}

if (isset($typesPrecio)) {
    $resp = $obBD_con1->getArrayConsulta(37, $Ses_Suc_Cod, $obBD_conexion);
    utf8_encode_deep($resp); 
    echo json_encode($resp); 
    exit();
}

//Importar el archivo excel
if(isset($_POST["import"])){
     /* Creacion del Objeto de conexion */
    $conn = new Class_Log_Conexion_Global($Ses_Dat_Dis);
    $conexion = $conn->conectar();

    $obBD_conexion = new Class_Log_Conexion_Pro($Ses_Dat_Dis);
    $obBD_con1 =  new Class_Log_Datos_Pro;

    //Carga el archivo de excel para poder recorrerlo
    $targetPath = 'uploads/'.$_FILES['file']['name'];
    move_uploaded_file($_FILES['file']['tmp_name'], $targetPath);
    $Reader = new SpreadsheetReader($targetPath);
    
    $sheetCount = count($Reader->sheets()); 
    $Reader->ChangeSheet(0);
    $contador = 0;

    $filasNoInsertadas = "Filas no insertadas: ";
    $noInserto = false;

    foreach ($Reader as $Row)
    {
        $contador++;

        //COMIENZO DESPUES DEL 1 POR EL ENCABEZADO DEL DOCUMENTO
        if(($contador > 1) and ($Row[0]!=""))
        {
            $data = array();
            $validar = true;
            //valores por defecto 
            $data['Suc_Cod'] = $Ses_Suc_Cod;
            $data['Pro_Cod'] = 10;
            $data['Pre_Pvp'] = 1000;
            $data['Pre_Des'] = 1;
            $data['Pre_Est'] = NULL;
            $data['Tpv_Cod'] = 1;

            $data['Pro_Cod'] = null; //codigo producto

            if(isset($Row[0]) and ($Row[0] != "")) {
                $data['Pro_Cod'] = mysqli_real_escape_string($conexion,$Row[0]);
                $Pro_Cod = $data['Pro_Cod'];
            }else{ $validar = false; }

            $data['Pre_Pvp'] = null; //precio venta publico
            if(isset($Row[1]) and ($Row[1] != "")) {
                $data['Pre_Pvp'] = mysqli_real_escape_string($conexion,$Row[1]);
            }else{ $validar = false; }

            $data['Pre_Des'] = null; //descripcion precio
            if(isset($Row[2]) and ($Row[2] != "")) {
                $data['Pre_Des'] = mysqli_real_escape_string($conexion,$Row[2]);
            }else{ $validar = false; }

            $data['Pre_Est'] = 'A';//estado del precio

            $data['Tpv_Cod'] = null; //codigo del tipo de precio
            if(isset($Row[3]) and ($Row[3] != "")) {
                $data['Tpv_Cod'] = mysqli_real_escape_string($conexion,$Row[3]);
                $Tpv_Cod = $data['Tpv_Cod'];
            }else{ $validar = false; }

            //SI ESTAN COMPLETOS LOS CAMPOS NECESARIOS
            if ($validar) 
            {
                $datos = $obBD_con1->getArrayConsulta(40, array($Pro_Cod, $Tpv_Cod, $Ses_Suc_Cod), $obBD_conexion);
               
                if(!empty($datos))
                {
                    $obBD_con1->operacionobBD(39, array($Ses_Suc_Cod, $Pro_Cod, $data['Tpv_Cod'], $data['Pre_Pvp']), $obBD_conexion);
                    
                }
                else
                {
                    $obBD_con1->operacionobBD(38, $data, $obBD_conexion);
                }
            }
            else
            {
                $filasNoInsertadas = $filasNoInsertadas . " " . $contador . ",";
                $noInserto = true;
            }
        }
    } 
} 


?>
<!DOCTYPE html>
<HTML>
    <HEAD>      
        <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
        <TITLE><?Php echo "Producto Precio [EXA]"; ?></TITLE>
        <meta charset="UTF-8">
        <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
<?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>                   
        <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
        <script language="javascript" src="../VALIDACIONES/fac_val_precios_2.0.js"></script>
        <style type="text/css">                     
            .pagination>li>a, .pagination>li>span {padding: 4px 2px;}
            .pagination {/*display: block;*/margin:0;padding: 0;}
            .chosen-default span,.chosen-single span{color:#555;}
            .chosen-single span{padding-left: 5px;}
        </style>
    </HEAD>
    <BODY>

        <div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Registrar precios</h3></div>

                <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                    <div id='search_producto'>
                        <form id="formProduct" class="form-horizontal normal"  action="javascript:"  >
                            <div class="row">
                                <div class="col-sm-8">
                                    <fieldset class="exa-fieldset">                           
                                        <legend class="Titulos2">Filtros:</legend> <!-- Form Name -->
                                        <!-- Text input-->
                                        <div class="form-group">
                                            <label class="col-sm-2 control-label label-sm " for="Cop_Fec">Buscar:</label>  
                                            <div class="col-sm-5">                                    
                                                <input type="text" name="txt_busqueda" id="txt_busqueda" class="form-control input-sm text clearable" >
                                            </div>
                                            <div class="col-sm-3">
                                                <button type="button" class="btn btn-sm btn-success" onclick="loadData();">
                                                    <i class="glyphicon glyphicon-search"></i>&nbsp;&nbsp;&nbsp;Buscar
                                                </button>
                                            </div>
                                        </div>                           
                                    </fieldset>
                                    <!-- Text input-->
                                    <div class="form-group">                               
                                        <div class="col-sm-8">  
                                            <input type="hidden" id="letra" name="letra" value="TODOS"  /> 
                                            <nav>
                                                <?php $Letras = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "TODOS"); ?>
                                                <ul class="pagination pagination-centered"> 
                                                    <?php foreach ($Letras as $letra) { ?>
                                                        <li <?php if ($letra == 'TODOS') echo 'class="active"'; ?>><a><?php echo $letra; ?></a></li>
                                                    <?php } ?>                                    
                                                </ul>

                                            </nav>
                                        </div>
                                    </div>
                                </div>    
                                <div class="col-sm-4">
                                    <fieldset class="exa-fieldset">                           
                                        <legend class="Titulos2">Filtros:</legend> <!-- Form Name -->
                                        <!-- Text input-->
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label label-xs " for="Cate_Cod">Categoría:</label>
                                            <div class="col-sm-6">
                                                <?php $row_rs_categ = $obBD_con1->getArrayConsulta(1, $Ses_Emp_Cod, $obBD_conexion); ?>
                                                <select name="Cate_Cod" id="Cate_Cod" class="form-control input-xs" data-placeholder="Todas">
                                                    <option value="">Todas</option>
                                                    <?Php foreach ($row_rs_categ as $row) { ?><option value="<?Php echo $row['Cat_Cod']; ?>"><?Php echo /* strtoupper($row['Par_Cat_Des']).' � '. */$row['Cat_Des']; ?></option><?Php } ?>
                                                </select>
                                            </div>
                                         </div>

                                         <div class="form-group">
                                            <label class="col-sm-3 control-label label-xs " for="Sub_Cod">Subcategoría:</label>
                                            <div class="col-sm-6">
                                                <select name="Sub_Cod" id="Sub_Cod" class="form-control input-xs" data-placeholder="Todas">
                                                    <option value=''>Todas</option>
                                                </select>
                                            </div>
                                        </div>
                                        <!-- Text input-->
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label label-xs " for="Ubi_Cod">Ubicaci&oacute;n:</label>  
                                            <div class="col-sm-3">
                                                <?php $rs_ubicacion = $obBD_con1->getArrayConsulta(5, $Ses_Emp_Cod, $obBD_conexion); ?>
                                                <select name="Ubi_Cod" id="Ubi_Cod" class="form-control input-xs">
                                                    <option value="">Todas</option> 
                                                    <?Php
                                                    foreach ($rs_ubicacion as $row) {
                                                        ?>
                                                        <option value="<?Php echo $row['Ubi_Cod']; ?>" ><?Php echo $row['Ubi_Des']; ?></option>
                                                        <?Php }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <!-- Text input-->
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label label-xs " for="Lin_Cod">L&iacute;nea:</label>  
                                            <div class="col-sm-3">
                                                <?php $rs_linea = $obBD_con1->getArrayConsulta(15, $Ses_Emp_Cod, $obBD_conexion); ?>
                                                <select name="Lin_Cod" id="Lin_Cod" class="form-control input-xs">
                                                    <option value="">Todas</option> 
                                                    <?Php
                                                    foreach ($rs_linea as $row) {
                                                        ?>
                                                        <option value="<?Php echo $row['Lin_Cod']; ?>" ><?Php echo $row['Lin_Des']; ?></option>
                                                        <?Php }
                                                    ?>
                                                </select>
                                            </div>
                                            <label class="col-sm-3 control-label label-xs " for="Lin_Cod">Agrupar por :</label> 
                                            <div class="col-sm-3">                                     
                                                <select name="grupo" id="grupo" class="form-control input-xs">
                                                    <option value="clear">No Agrupar</option>
                                                    <option value="Cat_Des">Categoria</option>
                                                    <option value="Ubi_Des">Bodega</option>
                                                    <option value="Lin_Des">Linea</option>
                                                </select>
                                            </div>
                                        </div> 
                                    </fieldset>    
                                </div> 
                                <div class="col-sm-12" style="min-height: 270px">
                                    <table id="grid"></table>
                                    <div id="gridPager"></div>
                                </div>   
                            </div>

                            <br>
                            <div>
                                <button class="btn btn-sm btn-info" type="button" name="upload" onclick="$('#uploadFile').setData({}); $('#uploadFile').dialog('open');" title="Importar excel">
                                    <span class="glyphicon glyphicon-upload"></span> Importar
                                </button>
                            
                                <a href="formatoPrecios.xlsx" download="formatoPrecios.xlsx"> 
                                <button  class="btn btn-sm btn-danger" type="button" title="Descargar formato">
                                    <span class="glyphicon glyphicon-download-alt"></span> Formato </button>
                                </a>
                            </div>  

                        </form>
                    </div>
                    <div id='modif_producto'style="visibility: hidden;">
                        <form id="formProductMod" class="form-horizontal normal" >
                            <div class="row">
                                <div class="col-sm-6">  
                                    <fieldset class="exa-fieldset">                           
                                    <legend class="Titulos2">Datos Generales</legend> <!-- Form Name -->
                                    <input type="text" id="Pro_Cod" name="Pro_Cod" hidden="true" />
                                            <!-- Text input-->
                                            <div class="form-group">
                                            <label class="col-sm-4 control-label label-sm " for="Cat_Cod">Categor&iacute;a:</label>  
                                            <div class="col-sm-8">
                                                <div class="input-group input-group-sm">
                                                    <?php $row_rs_categ = $obBD_con1->getArrayConsulta(42, $Ses_Emp_Cod, $obBD_conexion); ?>
                                                    <select name="Cat_Cod" id="Cat_Cod" readonly="" class="form-control">
                                                        <?Php foreach($row_rs_categ as $row){ echo "<option value='$row[Cat_Cod]'>$row[Cat_Des]</option>";} ?>
                                                    </select>
                                                </div>    
                                            </div>
                                            </div>
                                            <!-- Text input-->
                                            <div class="form-group">
                                            <label class="col-sm-4 control-label label-sm " for="Ite_Lar">Descripci&oacute;n Larga:</label>  
                                            <div class="col-sm-6"> 
                                                <div class="input-group input-group-sm">
                                                    <input type="text" id="Ite_Lar" name="Ite_Lar" class="form-control input-sm text" placeholder="Nombre del Producto" required=""  maxlength="250" readonly="readonly" />
                                                    <span class="input-group-addon validate"><i></i></span>
                                                </div>  
                                            </div>                                 
                                            </div>
                                            <!-- Text input-->
                                            <div class="form-group">
                                            <label class="col-sm-4 control-label label-sm " for="Ite_Cor">Descripci&oacute;n Corta:</label>  
                                            <div class="col-sm-3">                                    
                                                <input type="text" readonly="readonly" id="Ite_Cor" name="Ite_Cor" class="form-control input-sm text" placeholder="Abre. del Nombre" required="" maxlength="50" />                                                                                                                
                                            </div>                                 
                                            </div>
                                            <!-- Text input-->
                                            <div class="form-group">
                                            <label class="col-sm-4 control-label label-sm " for="Mar_Cod">Marca:</label>  
                                            <div class="col-sm-3">
                                                <div class="input-group input-group-sm">
                                                    <?php $rs_marca = $obBD_con1->getArrayConsulta(2, $Ses_Emp_Cod, $obBD_conexion);    ?>
                                                    <select name="Mar_Cod" id="Mar_Cod" readonly="readonly" class="form-control">
                                                    <?Php foreach($rs_marca as $row){ echo"<option value='$row[Mar_Cod]' ".(strtoupper($row['Mar_Des'])=='NINGUNA'?'selected':'').">$row[Mar_Des]</option>"; } ?>
                                                    </select>
                                                    
                                                </div>    
                                            </div>
                                            </div> 
                                            <!-- Text input-->
                                            <div class="form-group">
                                            <label class="col-sm-4 control-label label-sm " for="Adq_Cod">Adquisici&oacute;n:</label>  
                                            <div class="col-sm-3">
                                                <?php $rs_adq = $obBD_con1->getArrayConsulta(3, $Ses_Emp_Cod, $obBD_conexion); ?>
                                                <select name="Adq_Cod" id="Adq_Cod" readonly="readonly" class="form-control input-sm" >
                                                    <option value="">Seleccione..</option>  
                                                    <?Php foreach($rs_adq as $row){ echo "<option value='$row[Adq_Cod]'>$row[Adq_Des]</option>"; } ?>
                                                </select>
                                            </div>
                                            </div> 
                                            <!-- Text input-->
                                            <div class="form-group">
                                            <label class="col-sm-4 control-label label-sm " for="Iva_Cod">I.V.A.:</label>  
                                            <div class="col-sm-3">
                                                <?php $row_rs_iva = $obBD_con1->getArrayConsulta(4, $Ses_Emp_Cod, $obBD_conexion); ?>
                                                <select name="Iva_Cod" id="Iva_Cod" readonly="readonly" onchange="changeIva()" class="form-control input-sm" required>
                                                    <?Php foreach($row_rs_iva as $row){ echo "<option value='$row[Iva_Cod]' >$row[Iva_Por]%</option>"; } ?>
                                                </select>
                                            </div>  
                                            <label class="col-sm-1 control-label label-sm" for="Ice_Cod">I.C.E.:</label>  
                                            <div class="col-sm-3">
                                                <?php $row_rs_ice = $obBD_con1->getArrayConsulta(25, '', $obBD_conexion); ?>
                                                <select name="Ice_Int" id="Ice_Int" readonly="readonly" class="form-control input-sm" >
                                                    <option value="">NINGUNO</option>
                                                    <?Php foreach($row_rs_ice as $row){ echo "<option value='$row[Ice_Int]' >$row[Ice_Por]% - $row[Ice_Des]</option>"; } ?>
                                                </select>
                                            </div>
                                            </div>                                 
                                </fieldset>
                                </div>

                                <div class="col-sm-6">  
                                    <fieldset class="exa-fieldset">                           
                                    <legend class="Titulos2">Agregar Precio</legend>

                                        <form id="formPrecios" name="formPrecios" action="" >
                                            <div class="form-group">
                                                <input type="text" id="editar" hidden="true">
                                                <label class="col-sm-3 control-label label-sm">Precio de compra:</label>  
                                                <div class="col-sm-2" >
                                                    <input type="text" onKeyUp="calcular()" class="form-control input-sm" onKeyPress="return validar_decimal(event)"  id="Pre_Com" name="Pre_Com" size="5" maxlength="15" style="text-align:right">
                                                </div>
                                                <label class="col-sm-3 control-label label-sm"> Fecha inicio:</label> 
                                                <div class="col-sm-4">
                                                    <input name="Fec_Ini" id="Fec_Ini" type="date" class="form-control input-sm">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-sm-3 control-label label-sm">Utilidad (%):</label>  
                                                <div class="col-sm-2" >
                                                    <input type="text" class="form-control input-sm"  onKeyUp="calcular()" onKeyPress="return validar_decimal(event)" name="Pre_Por" id="Pre_Por" size="5" maxlength="15" style="text-align:right"/>
                                                </div>
                                                <label class="col-sm-3 control-label label-sm"> Fecha fin:</label> 
                                                <div class="col-sm-4">
                                                    <input name="Fec_Fin" id="Fec_Fin" type="date" class="form-control input-sm">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-sm-3 control-label label-sm"> Total de utilidad:</label>                            
                                                <div class="col-sm-2" >
                                                    <input type="text" class="form-control input-sm" value="0" onKeyPress="return validar_decimal(event)" id="Pre_Uti" name="Pre_Uti" size="5" maxlength="6" style="text-align:right; background:none" class="Cabecera1" readonly>
                                                </div>
                                                <label class="col-sm-3 control-label label-sm required"> Tipo de precio:</label>   
                                                <div class="col-sm-4">
                                                    <div class="input-group input-group-sm">
                                                        <span></span>
                                                        <?php $tpvCodigos = ""; $Arr_Tipo_precio = $obBD_con1->getArrayConsulta(30, $Ses_Suc_Cod, $obBD_conexion); ?>
                                                        
                                                        <select name="Tpv_Des" id="Tpv_Des" class="form-control" required="true" autofocus="true">
                                                            <option value="">Seleccione...</option>
                                                        <?php 
                                                            foreach($Arr_Tipo_precio as $row_rs_tprecio){  
                                                        ?>
                                                                <option value="<?Php echo $row_rs_tprecio['Tpv_Cod']; ?>">
                                                                    <?php echo $row_rs_tprecio['Tpv_Des']; ?>
                                                                </option>
                                                        <?php } ?>
                                                        </select>

                                                        <span id="codigosTipo" class="input-group-addon input-xs" title="">
                                                            <i class="glyphicon glyphicon-info-sign blue"></i>
                                                        </span>

                                                    </div>    
                                                </div>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label class="col-sm-3 control-label label-sm required">P.V.P.:</label>  
                                                <div class="col-sm-2" >
                                                    <input type="text" class="form-control input-sm"onKeyUp="calcular2()" onKeyPress="return validar_decimal(event)" name="Pre_Pvp1" id="Pre_Pvp1" size="6" value="0" maxlength="15" style="text-align:right" required autofocus="autofocus">
                                                </div>
                                            </div>   
                                            <div class="form-group">
                                                <label class="col-sm-3 control-label label-sm">Descuento (%):</label>  
                                                <div class="col-sm-2" >
                                                    <input type="text" class="form-control input-sm" onKeyUp="calcular()" onKeyPress="return validar_decimal(event)" name="Pre_Dcs"  id="Pre_Dcs"size="5" maxlength="6" style="text-align:right"/>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-sm-3 control-label label-sm"> Total de descuento:</label>                            
                                                <div class="col-sm-2" >
                                                    <input type="text" class="form-control input-sm" onKeyUp="calcular()" name="Pre_Dct" id="Pre_Dct"  onKeyPress="return validar_decimal(event)" value="0" size="6" maxlength="5" style="text-align:right; background:none" class="Cabecera1" readonly/>
                                                </div>
                                            </div>   
                                            <div class="form-group">
                                                <label class="col-sm-3 control-label label-sm"> TOTAL:</label>                            
                                                <div class="col-sm-2" >
                                                    <input type="text" onKeyUp="calcular()" name="Pre_Tot" id="Pre_Tot" onKeyPress="return validar_decimal(event)" value="0" size="6" maxlength="5" style="text-align:right; background:none" class="form-control input-sm" readonly/>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-sm-3 control-label label-sm"> Ganacia:</label>                            
                                                <div class="col-sm-2" >
                                                    <input type="text" class="form-control input-sm" onKeyUp="calcular()" id="Pre_Gan" onKeyPress="return validar_decimal(event)" value="0" size="6" maxlength="5" onChange="ColorGanancia();" style="text-align:right; background:none" class="Cabecera1" readonly/>
                                                </div>
                                            </div>
                                       
                                            <div class="col-sm-12">
                                                <div class="form-group">
                                                    <div class="col-sm-12 center" >
                                                        <button id="addPrice" class="btn btn-success" type="button" onclick="addrow();">Agregar precio 
                                                            <i class="glyphicon glyphicon-plus"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                        
                                    </fieldset>
                                </div>
                                
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <div class="col-sm-12 center">
                                            <table id="prods"></table>
                                            <div id="prodsPager"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-sm-12">
                                    <div class="form-group">  
                                        <div class="col-sm-12 center">
                                            <button id="btn_atras" class="btn btn-primary btn-form black" onclick="loadData();"><span class="fa fa-long-arrow-left"></span> Atr&aacute;s </button>
                                            <button type="button" class="btn btn-primary btn-form" onclick="save(); "><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
                                            <!--  <button type="reset" onclick=""  class="btn btn-danger btn-form"><span class="glyphicon glyphicon-remove"></span> Cancelar</button>                                -->
                                         </div>
                                    </div>
                                </div>
                            </div>    
                        </form>   
                    </div>

                    <div id="uploadFile" title="Importar archivo"> 
                        <div class="row">
                            <div class="col-md-12" >                
                                 <form action="" method="post" name="uploadFile" id="uploadFile" enctype="multipart/form-data">
                                    <input type="text" name="import" hidden="true">
                                    <div class="text-center custom-file"> 
                                        <input class="form-control" type="file" name="file" id="file" accept=".xls,.xlsx,.xlsm" lang="es" required="true">
                                        <label class="custom-file-label" for="file">Elegir archivo .xls .xlsx .xlsm</label>
                                    </div>
                                    <div class="text-center">
                                        <button type="submit" class="btn btn-success btn-form"><span class="glyphicon glyphicon-ok"></span> Subir</button>
                                        <button type="button" class="btn btn-danger btn-form" onclick="$('#uploadFile').dialog('close');"><span class="glyphicon glyphicon-remove"></span> Cancelar</button>
                                    </div>                                   
                                </form> 
                            </div>
                        </div>
                    </div>
        </div>    
        <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
        <link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.min.css" />
   <script type="text/javascript" src="../../framework/jquery/bootstrap/popover/jquery.flyout.min.js"></script>
   <script type="text/javascript">

    $("#confirm").click(function(){

    alert("se elimino correctamente");

  });

    function deletePrice(data){
        var rowid = data;
        var verificar = false;
        //en el caso que no este registrado en la base de datos
        if (!data['Pre_Cod']){
            $('#prods').jqGrid('delRowData', rowid['Tpv_Des']);
            $('#prods').trigger('reloadGrid');
            verificar = true;
        }
        else{
            var pre = $('#prods').jqGrid('getGridParam','data');
            $.post("",{anular:true, 'Pre_Cod': rowid['Pre_Cod']},
                function(responce)
                {
                    if(responce['success'])
                    {
                        $('#prods').jqGrid('delRowData', rowid['Tpv_Des']);
                        $('#prods').trigger('reloadGrid');
                        verificar = true;
                        $.alert('Acción realizada correctamente');
                    }
                    else
                    {
                        $.alert('No se pudo realizar la acción');
                    }
                }
            ,'json').fail(function () {
                $.alert('Response ');
            });
        }
    }

     $('#Cate_Cod').change(function(){
                var cod=$('#Cate_Cod').val();
                $('#Sub_Cod').html('');
                $.get("",{CatSelect:cod}, function( response ) {
                $('#Sub_Cod').html(response);
                //$('#proGrid').trigger('reloadGrid', [{ page: 1 }]);
                // Grid.clearGrid();
                })  
               });

    if($('#prods').length===1){
        docuView=$("#prods");
        docuView.createGrid({
            height: 155,caption:'Precios de productos', cmTemplate: {sortable:false,title: false},
            colModel: [
                { label: 'Cód. Int.',  name: 'Pre_Cod',  width: 25, align:"center" },  
                { label: 'Precio', name: 'Pre_Pvp',  width: 40, align:"center" },
                { label: 'Tipo', name: 'Tpv_Des', key: true, width: 40, align:"center"},
                { label: 'Fecha Inicio', name: 'Pre_Ini',  width: 50, align:"right" },  
                { label: 'Fecha Fin', name: 'Pre_Fin',  width: 50, align:"right"},
                { label: 'Fecha', name: 'Pre_Fec',  width: 50, align:"right"},                
                { label: 'Cód.Int.', name: 'Tpv_Cod', width: 15, align:"center", hidden:true },  
                { label: 'Cód.Int.', name: 'Pre_Com', width: 15, align:"center", hidden:true },
                { label: 'Cód.Int.', name: 'Pre_Por', width: 15, align:"center", hidden:true },  
                { label: 'Cód.Int.', name: 'Pre_Uti', width: 15, align:"center", hidden:true },
                { label: 'Cód.Int.', name: 'Suc_Cod', width: 15, align:"center", hidden:true },
                { label: 'Cód.Int.', name: 'Pro_Cod', width: 15, align:"center", hidden:true },
                { label:'&nbsp;', name: 'act1', width: 8, align: 'center',viewable: false,
                    formatter:function (cellvalue, options, rowObject) {
                        if(rowObject.Tpv_Des != 'Standar'){
                             return $.getGridButton(deletePrice, rowObject,'Eliminar','trash',null,'danger');
                        }
                        else{
                            return "";
                        }
                    }
                },
                { label:'&nbsp;', name: 'act1', width: 8, align: 'center',viewable: false,
                    formatter:function (cellvalue, options, rowObject) { 
                        return $.getGridButton(editPrice, rowObject,'Editar','pencil',null,'success');
                    }
                }
            ]
        },true,"#prodsPager",{view:false,refresh:false});
    }

    function editPrice(data){
        var rowid = data;
        $('#prods').jqGrid('getRowData', rowid['Tpv_Cod']);
        $("#editar").val(rowid['Tpv_Des']);
        $("#addPrice").text("Modificar Precio");

        $("#Pre_Com").val(rowid['Pre_Com']);
        $("#Pre_Por").val(rowid['Pre_Por']);
        $("#Pre_Uti").val(rowid['Pro_Uti']);
        $("#Pre_Pvp1").val(rowid['Pre_Pvp']);
        $("#Pre_Dcs").val(rowid['Pre_Dcs']);
        $("#Pre_Dct").val(rowid['Pre_Dct']);
        $("#Pre_Dcs").val(rowid['']);
        $("#Pre_Tot").val(rowid['Pre_Tot']);

        var gan = rowid['Pro_Uti'];
        $("#Pre_Gan").val(gan);
        $("#Fec_Ini").val(rowid['Pre_Ini']);
        $("#Fec_Fin").val(rowid['Pre_Fin']);
        document.getElementById('Tpv_Des').value = rowid['Tpv_Cod'];

        calcular();
    }

    function addrow()
    {
        var cd_pre = $('#Pre_Cod').val();
        var pre = $('#Pre_Tot').val();
        var tip = $('#Tpv_Des').find('option:selected').text().trim();
        var ini = $('#Fec_Ini').val();
        var fin = $('#Fec_Fin').val();
        var pr_com = $('#Pre_Com').val();
        var tp_cod = $('#Tpv_Des').val();
        var pr_por = $('#Pre_Por').val();
        var pr_uti = $('#Pre_Uti').val();
        var pr_cod = $('#Pro_Cod').val();
        var date = new Date();
        var month = date.getMonth()+1;
        var day = date.getDate();
        var pvp = $('#Pre_Pvp1').val();
        var hoy = date.getFullYear() + '-' +
            (month<10 ? '0' : '') + month + '-' +
            (day<10 ? '0' : '') + day;

        var validar = validarAddRow(pvp,tip);

        var edit = $('#editar').val();
        var myfirstrow = { Pro_Cod: pr_cod,Pre_Pvp: pre, Tpv_Des: tip, Pre_Ini: ini, Pre_Fin: fin, 
                            Pre_Fec: hoy, Pre_Com: pr_com,Pre_Por: pr_por,Pre_Uti: pr_uti,Tpv_Cod: tp_cod
                         };
        if (validar == 1)
        {
            var rowEdit = $('#prods').jqGrid('getRowData', edit);
            $("#prods").jqGrid("addRowData", tip, myfirstrow);
        }
        else
        {
            if(validar == 0){
                $.alert("Ingrese todos los campos necesarios!");
            }
            if(validar == 2){
                if(edit != ""){
                    $('#prods').jqGrid('setRowData', edit, myfirstrow);
                    $('#editar').val("");
                    $("#addPrice").text("Agregar Precio");
                }
                else{
                    $.alert("Ya existe el tipo de precio!");
                }  
            }
        }
    }

    function validarAddRow(pvp, tip)
    {
        var validar = 1;
        if(pvp=='' || tip == 'Seleccione...'){
            validar = 0;
        }
        $("td[aria-describedby='prods_Tpv_Des']").each(function(indice, tipo){
            if ($(tipo).text()==tip){
                validar = 2;
            }
        });
        return validar;
    }

    function save(){
        var pre = $('#prods').jqGrid('getGridParam','data');
        $.post("",{guardar:true, 'guardar_precios': pre},
            function(responce){
                if(responce['success']){
                    $.alert('Precio guardado exitosamente');
                }else{
                    $.alert('No se pudo realizar la acción');
                }
            }
        ,'json').fail(function () {
            $.alert('Response ');
        });
    }

    $( "#Tpv_Des" ).change(function() {
        var codigo =  $( "#Tpv_Des" ).val();
        $('#codigosTipo').prop('title', codigo);
    });

   </script>
    </BODY>
</HTML>