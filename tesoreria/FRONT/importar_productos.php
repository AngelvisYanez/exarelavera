<?php
include 'vendor/php-excel-reader/excel_reader2.php' ;
include 'vendor/SpreadsheetReader.php';
require_once("../../administrador/LOGICA/seguridad.php");

require_once('../../DATA/MysqlConexion.php');

require_once('../../facturacion/LOGICA/fac_log_producto_1.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php'); 

function reverse_number($number){    
    $arr = str_split($number);   
    $rev_arr = array_reverse($arr);   
    $rev = implode("",$rev_arr);
   return $rev;
}

if (isset($_POST["import"]))
{
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
                $data['Ice_Int'] = NULL;
                $data['Stk_Min'] = 10;
                $data['Stk_Max'] = 1000;
                $data['Pro_Uni'] = 1;
                $data['ChkNet'] = NULL;
                $data['Pro_Gen'] = 1;
                $data['Pro_Bar'] = NULL;
                $data['Pro_Dsc'] = 0;
                $data['Lin_Cod'] = 'NULL';
                $data['Uni_Cod'] = 1; 

                //valores que deben poner en el excel conformo a la bd 
                $data['Mar_Cod'] = null; //marca
                if(isset($Row[0]) and ($Row[0] != "") ) {
                    $data['Mar_Cod'] = mysqli_real_escape_string($conexion,$Row[0]);
                }else{ $validar = false; }

                $data['Adq_Cod'] = null; //adquisicion
                if(isset($Row[1]) and ($Row[1] != "")) {
                    $data['Adq_Cod'] = mysqli_real_escape_string($conexion,$Row[1]);
                }else{ $validar = false; }

                $data['Iva_Cod'] = null; //iva
                if(isset($Row[2]) and ($Row[2] != "")) {
                    $data['Iva_Cod'] = mysqli_real_escape_string($conexion,$Row[2]);
                }else{ $validar = false; }

                $data['Pre_Cod'] = null; //presentacion
                if(isset($Row[3]) and ($Row[3] != "")) {
                    $data['Pre_Cod'] = mysqli_real_escape_string($conexion,$Row[3]);
                }else{ $validar = false; }

                $data['Ubi_Cod'] = null; //ubicacion
                if(isset($Row[4]) and ($Row[4] != "")) {
                    $data['Ubi_Cod'] = mysqli_real_escape_string($conexion,$Row[4]);
                }else{ $validar = false; }

                $data['Cat_Cod'] = null; //categoria
                if(isset($Row[5]) and ($Row[5] != "")) {
                    $data['Cat_Cod'] = mysqli_real_escape_string($conexion,$Row[5]);
                }else{ $validar = false; }


                //valores sin relacion con la bd 
                $data['Ite_Lar'] = null; //descripcion larga
                if(isset($Row[6]) and ($Row[6] != "")) {
                    $data['Ite_Lar'] = mysqli_real_escape_string($conexion,$Row[6]);
                }else{ $validar = false; }

                $data['Ite_Cor'] = null; //descripcion corta
                if(isset($Row[7]) and ($Row[7] != "")) {
                    $data['Ite_Cor'] = mysqli_real_escape_string($conexion,$Row[7]);
                }else{ 
                    $data['Ite_Cor'] = " ";
                }

                $data['Pro_Obs'] = null; //detalle del producto
                if(isset($Row[8]) and ($Row[8] != "")) {
                    $data['Pro_Obs'] = mysqli_real_escape_string($conexion,$Row[8]);
                }else{ 
                    $data['Pro_Obs'] = " ";
                }

                $data['Stk_Can'] = 0; //cantidad stock producto
                if(isset($Row[9]) and ($Row[9] != "")) {
                    $data['Stk_Can'] = mysqli_real_escape_string($conexion,$Row[9]);
                }else{ $validar = false; }

                $data['Stk_Prp'] = null; //precio compra
                if(isset($Row[10]) and ($Row[10] != "")) {
                    $data['Stk_Prp'] = mysqli_real_escape_string($conexion,$Row[10]);
                }else{ $validar = false; }

                $data['Pre_Pvp'] = null; //precio venta
                if(isset($Row[11]) and ($Row[11] != "")) {
                    $data['Pre_Pvp'] = mysqli_real_escape_string($conexion,$Row[11]);
                }else{ $validar = false; }

                // $data['PreNet'] = null;//precio venta iva
                // if(isset($Row[12])) {
                //     $data['PreNet'] = mysqli_real_escape_string($conexion,$Row[12]);
                // }else{ $validar = false; }


                //SI ESTAN COMPLETOS LOS CAMPOS NECESARIOS
                if ($validar) 
                {
                    //$obBD_con1->debug(true);   
                        if ($data['Pro_Uni']=="") $data['Pro_Uni']=1;
                        if ($data['Pro_Dsc']=="") $data['Pro_Dsc']=0;
                        
                        // secuancia en caso de categorias
                        $row_rs_con_sec= $obBD_con1->getRowConsulta(8,$data['Cat_Cod'].'*'.$Ses_Emp_Cod,$obBD_conexion);      
                        $data['Pro_Cdc']=$row_rs_con_sec['Cat_Cdc'].'.1';
                        $data['Pro_Sec']=1;

                        // Identificador en caso de lineas
                        $data['Pro_Ide']='NULL';

                        // Ahora por categoria
                        $row_rs_con_ide= $obBD_con1->getRowConsulta(19,$data['Cat_Cod'].'*'.$Ses_Emp_Cod,$obBD_conexion); 
                        $data['Pro_Ide']=$row_rs_con_ide['siguiente'];
                        if($row_rs_con_ide['siguiente']==NULL || $row_rs_con_ide['siguiente']=='') $data['Pro_Ide']=1;    
                        change_string_deep('utf8_decode',$data);  
                        $row_rs_sucur= $obBD_con1->getArrayConsulta(17,$Ses_Emp_Cod,$obBD_conexion);

                        $obBD_con1->inicio_transaccion($obBD_conexion->conexion);

                            /** Insetar el item */
                            $obBD_con1->operacionobBD(10,$data, $obBD_conexion);
                            $data['Ite_Cod'] = $obBD_con1->insercionid ($obBD_conexion->conexion);

                            /* Insetar el producto */
                            $obBD_con1->operacionobBD(11,$data, $obBD_conexion);
                            $data['Pro_Cod'] = $obBD_con1->insercionid ($obBD_conexion->conexion);
                            $Pro_Cod=$data['Pro_Cod'];

                            /* Genera el Codigo de Barra senececitan 12 caracteres para generar */ 
                            if($data['Pro_Gen']==1){
                                $numeroP= 1000;
                                $numRever = reverse_number($Pro_Cod);
                                $obBD_con1->echoLog($numRever);
                                if($numRever <= 1){
                                    $obBD_con1->echoLog('SI');
                                    $valPuro=str_pad($Pro_Cod, 12, "0");//$Pro_var;
                                    $genNum = mt_rand(1,19);
                                    $obBD_con1->echoLog($genNum);
                                    $newNumGen = $valPuro + $genNum;
                                    $obBD_con1->echoLog($newNumGen);
                                    $data['Pro_Bar'] = $newNumGen;
                                }else{
                                    $obBD_con1->echoLog('NO');
                                    $data['Pro_Bar'] = str_pad($Pro_Cod,12,"0");
                                }           
                                $Pro_varg='G';                      
                            }else{
                                $Pro_varg='M';
                            }

                           /* Actualiza el codigo de barras en el producto insertado */
                           $obBD_con1->operacionobBD(12,$Pro_Cod.'*'.$data['Pro_Bar'].'*'.$Pro_varg,$obBD_conexion);
                           
                           /* Guardo en la tabla Stock */
                            $data['Suc_Cod']=$Ses_Suc_Cod;        
                            $obBD_con1->operacionobBD(13,$data,$obBD_conexion);   
                                    
                            /* Consulta el tipo de precio */
                            $row_rs_con_tp= $obBD_con1->getRowConsulta(9,'D*'.$Ses_Suc_Cod,$obBD_conexion);  
                            $Tpv_Cod=($row_rs_con_tp['Tpv_Cod'] > 0)?$row_rs_con_tp['Tpv_Cod']:0;

                            /* Inserta un precio por defecto */
                            $obBD_con1->operacionobBD(14,$Pro_Cod.'*'.$data['Pre_Pvp'].'*'.'Precio 1'.'*'.$Ses_Suc_Cod.'*'.$Tpv_Cod,$obBD_conexion);    
                            $Pre_Cod = $obBD_con1->insercionid ($obBD_conexion->conexion);
                            
                        $responce['success']=$obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);

                        //$responce['message']=$obBD_con1->MsgError;    
                        //$obBD_con1->echoJson($responce);
                        //echo json_encode($responce);
                        //exit();
                        if($responce['success']==false){
                            $filasNoInsertadas = $filasNoInsertadas . " " . $contador . ",";
                            $noInserto = true;
                        }
                }
                else{
                    $filasNoInsertadas = $filasNoInsertadas . " " . $contador . ",";
                    $noInserto = true;
                }

            } //If valida el encabezado
        } //If del foreach

        if ($noInserto == false) 
        {
            $type = "success";
            $message = "Se importaron los datos correctamente!";
        } 
        else 
        {
            $type = "error";
            $message =  $filasNoInsertadas;
        }
	
}//If del post


?>

<!DOCTYPE html>
<html>    
<head>
 <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
 <script src="https://code.jquery.com/jquery-3.1.1.slim.min.js" integrity="sha384-A7FZj7v+d/sdmMqp/nOQwliLvUsJfDHW+k9Omg/a/EheAdgtzNs3hpfag6Ed950n" crossorigin="anonymous"></script>

<style>    

.success {
    background: #c7efd9;
    border: #bbe2cd 1px solid;
}

.error {
    background: #fbcfcf;
    border: #f3c6c7 1px solid;
}

div#response.display-block {
    display: block;
}

.custom-file-input ~ .custom-file-label::after {
    content: "Seleccionar";
}

</style>

<title><?Php echo $Ses_Sys_Nom; ?></title>
<?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
<script type="text/javascript" src="../../framework/jquery/MonthPicker/jquery.mtz.monthpicker.min.js"></script>

</head>

<body>
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo;  Importar productos</h3>
        </div>

            <div class="row">
                <div class=" col-xs-6" style="margin-left: 10px; margin-top: 10px;">

                    <form action="" method="post"
                        name="frmExcelImport" id="frmExcelImport" enctype="multipart/form-data">
                        <div class="text-center custom-file"> 
                            <input class="custom-file-input" type="file" name="file" id="file" accept=".xls,.xlsx,.xlsm" lang="es">
                            <label class="custom-file-label" for="file">Elegir archivo .xls .xlsx .xlsm</label>
                         </div>

                         <div class="text-center mt-2">
                            <button type="submit" id="submit" name="import"
                                class="btn btn-md btn-success btn-submit">Importar</button>
                        </div>
                    </form> 

                     <div class="text-center mt-2" style="margin-bottom: 10px;">
                        <a href="formatoProductos.xlsx" download="formatoProductos.xlsx"> 
                            <button  class="btn btn-sm btn-info" type="button" title="Descargar formato"><i class="glyphicon glyphicon-download-alt"></i> Formato </button> 
                        </a> 
                     </div> 

                    <script>
                        $('#file').on('change',function(){
                            //get the file name
                            fileName = $(this).val().replace('C:\\fakepath\\', " ");
                            //replace the "Choose a file" label
                            $(this).next('.custom-file-label').html(fileName);
                        })
                    </script>  

            </div>
        </div>
    </div>

     <div class="container">
        <div id="response" class="mx-auto col-md-6 mt-2 <?php if(!empty($type)) { echo $type . " display-block"; } ?>"><?php if(!empty($message)) { echo $message; } ?>  
        </div>
    </div>

</body>
</html>