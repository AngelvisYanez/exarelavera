<?php
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/rhu_log_ing_egr.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');

ChromePhp::log($Ses_Emp_Cod);
$obBD_conexion = new Class_Log_Conexion_rrhh_ing_egr($Ses_Dat_Dis);
/**
 * Creacion del objeto mysql para las consultas 
 */
$obBD_con1 = new Class_Log_Datos_rrhh_ing_egr;
$hoy = date("Y-m-d");
$mes = date("m");

$id_plantilla = $_POST['id_plantilla'];
	$data = '';
	$data .='<div class="col-xs-12">
                    		<div class="form-horizontal normal">
                    			<div class="form-group">
                    				<label class="col-xs-2 control-label label-xs">Ingresos:</label>  
		                            <div class="col-xs-10">
		                            	<div class="col-xs-9">';
		                            	$rscamps = $obBD_con1->getArrayConsulta(55, $id_plantilla, $obBD_conexion);
                                                foreach ($rscamps as $rowcamps){  
                                                     
                                                    $data.=' <input type="checkbox" checked id="chk_rol_'.$rowcamps['Cam_Var'].'" name="opciones[]" value="'.$rowcamps['Cam_Cod'].'" onclick="getDataGridFilter(1)">'.$rowcamps['Cam_Dec'];
                                                     
                                                }
		                            $data.='
                                    	</div> 
		                            </div>
		                            <label class="col-xs-2 control-label label-xs">Egresos:</label>  
		                            <div class="col-xs-10">
		                            	<div class="col-xs-9"> ';
		                            	$rscamps = $obBD_con1->getArrayConsulta(56, $id_plantilla, $obBD_conexion);
                                                foreach ($rscamps as $rowcamps){  
                                                     
                                                    $data.=' <input type="checkbox" checked id="chk_rol_'.$rowcamps['Cam_Var'].'" name="opciones[]" value="'.$rowcamps['Cam_Cod'].'" onclick="getDataGridFilter(1)">'.$rowcamps['Cam_Dec'];
                                                     
                                                }
		                            $data.='
                                        	
                                    	</div> 
		                            </div>
                    			</div>
                    		</div>
                    	</div>';

                    	echo $data;
?>