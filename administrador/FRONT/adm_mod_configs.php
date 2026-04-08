<?php

/**
 * Created by PhpStorm.
 * User: jorge
 * Date: 4/13/2018
 * Time: 3:16 PM
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/adm_log_configs.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Config($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Config;
$obBD_con1->debug(true);

$hoy = date("Y-m-d");
$mes = date("m");

function rmDir_rf($carpeta)
{
    foreach (glob($carpeta . "/*") as $archivos_carpeta) {
        if (is_dir($archivos_carpeta)) {
            rmDir_rf($archivos_carpeta);
        } else {
            unlink($archivos_carpeta);
        }
    }
    rmdir($carpeta);
}

if (isset($saveData)) {
    $r = array('success' => false, 'message' => "No se logro guardar");
    $obBD_ins1 =  new Class_Log_Datos_Config;
    $obBD_conexionIns = new Class_Log_Conexion_Config($Ses_Dat_Dis);

    $obBD_ins1->debug(true);
    $obBD_ins1->inicio_transaccion($obBD_conexionIns);
    try {
        $data = $_POST;
        $obBD_con1->echoLog($data);
        $obBD_con1->echoLog($_FILES);

        if (isset($_FILES["Emp_Log"])  && $_FILES["Emp_Log"]['size'] > 0) {
            $obBD_con1->echoLog("1.-");
            $target_dir = "../../imagenes/$Ses_Emp_Cod/";
            $carpeta = $target_dir;
            if (file_exists($carpeta)) {
                $obBD_con1->echoLog("2.-");
                rmDir_rf($carpeta);
            } //borro archivos y carpeta
            $obBD_con1->echoLog("3.-");
            if (!file_exists($carpeta)) {
                $obBD_con1->echoLog("4.-");
                mkdir($carpeta, 0777, true);
            }
            $target_file = $carpeta . basename($_FILES["Emp_Log"]["name"]);
            $imageFileType = pathinfo($target_file, PATHINFO_EXTENSION);
            // Check if file already exists
            if (file_exists($target_file)) unlink($target_file);
            // Check file size
            if ($_FILES["Emp_Log"]["size"] > 500000) $obBD_con1->echoJson(array('success' => false, 'message' => 'El archivo es demasiado grande!'));
            // Allow certain file formats
            if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") $obBD_con1->echoJson(array('success' => false, 'message' => 'Lo sentimos, solo archivos JPG, JPEG, PNG & GIF son permitidos.'));
            // if everything is ok, try to upload file


          //  $obBD_con1->echoJson(array('success' => false, 'message' => $target_file . "  " . $_FILES["Emp_Log"]["tmp_name"]));

            if (file_exists($_FILES["Emp_Log"]["tmp_name"])) {
                if (move_uploaded_file($_FILES["Emp_Log"]["tmp_name"], $target_file)) {
                    $dataImg = array('Emp_Log' => $target_file);
                    $obBD_ins1->operacionobBD(7, $dataImg, $obBD_conexionIns); //registra imagen

                } else {
                    $obBD_con1->echoJson(array('success' => false, 'message' => 'No se pudo subir el archivo principal!'));
                }
            } else {
                $obBD_con1->echoJson(array('success' => false, 'message' => 'Archivo temporal no encontrado.'));
            }
        }

        //Cargar logo para sucursales

        if (isset($_FILES["Suc_Log"])  && $_FILES["Suc_Log"]['size'] > 0) {
            $obBD_con1->echoLog("1.-");
            $target_dir = "../../imagenes/imagenes_sucursal/$Ses_Emp_Cod/";
            $carpeta = $target_dir;
            $obBD_con1->echoLog("3.-");
            if (!file_exists($carpeta)) {
                $obBD_con1->echoLog("4.-");
                mkdir($carpeta, 0777, true);
            }

            $target_file = $carpeta . basename($_FILES["Suc_Log"]["name"]);
            $imageFileType = pathinfo($target_file, PATHINFO_EXTENSION);
            if (file_exists($target_file)) unlink($target_file);
            if ($_FILES["Suc_Log"]["size"] > 500000) $obBD_con1->echoJson(array('success' => false, 'message' => 'El archivo es demasiado grande!'));
            // Allow certain file formats
            if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") $obBD_con1->echoJson(array('success' => false, 'message' => 'Lo sentimos, sólo archivos JPG, JPEG, PNG & GIF  son permitidos.'));

            if (move_uploaded_file($_FILES["Suc_Log"]["tmp_name"], $target_file)) {
                $dataImg = array('Suc_Log' => $target_file, 'Suc_Cod' => $_POST['Suc_Cod']); //me falta el codigo del usuario
                $obBD_ins1->operacionobBD(8, $dataImg, $obBD_conexionIns); //registra imagen
            } else $obBD_con1->echoJson(array('success' => false, 'message' => 'No se pudo subir el archivo!'));
        }


        $obBD_ins1->operacionobBD(2, $_POST, $obBD_conexionIns);
        $obBD_ins1->operacionobBD(3, $_POST, $obBD_conexionIns);
        $obBD_ins1->operacionobBD(5, $data, $obBD_conexionIns);
    } catch (Exception $e) {
        $obBD_ins1->rollBack_nomsn($obBD_conexionIns);
        $r['error'] = $e->getMessage();
        $obBD_con1->echoJson($r);
    }
    if ($obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns)) {
        $r = array('success' => true);
    }
    $obBD_con1->echoJson($r);
}
$empresa = $obBD_con1->getRowConsulta(1, $Ses_Emp_Cod, $obBD_conexion);
$sucursales = $obBD_con1->getArrayConsulta(4,  $Ses_Emp_Cod, $obBD_conexion);
$ciudades = $obBD_con1->getArrayConsulta(6, '', $obBD_conexion);
//$obBD_con1->echoLog($empresa);


if (isset($guardarReporte)) {
    $resp = array();
    $oBdSet = new MysqlDatos(true);
    //$oBdSet->debug(true);
    $oBdSet->beginTrans();
    try {
        foreach ($pendientes as $v) {
            if ($v['Pcs_Cod'] == 516) {
                $oBdSet->operation('reportes.insert', array('Pcs_Cod' => $v['Pcs_Cod'], 'Rep_Req' => 244, 'Rep_Ord' => 2, 'Emp_Cod' => $_SESSION['Ses_Emp_Cod']));
            } else {
                $oBdSet->operation('reportes.insert', array('Pcs_Cod' => $v['Pcs_Cod'], 'Rep_Req' => 244, 'Rep_Ord' => 1, 'Emp_Cod' => $_SESSION['Ses_Emp_Cod']));
            }
        }

        //$oBdSet->truncateTrans(); //si se guardo bien detengo el commit
        $oBdSet->endTrans($resp);
    } catch (Exception $e) {
        $oBdSet->revertTrans($e->getMessage(), $resp);
    }
    $oBdSet->echoJson($resp);
}

//Verifica procesos que no estan agregados en reportes
$ids_proc = array(494, 496, 516, 130, 137);
$pendientes = array();
foreach ($ids_proc as $id) {
    $proc = $obBD_con1->getRowConsulta('procesos', array('where' => array('Pcs_Est' => 'A', 'Pcs_Cod' => $id)), $obBD_conexion);
    if (!empty($proc)) {
        $rep = $obBD_con1->getRowConsulta('reportes', array('where' => array('Pcs_Cod' => $proc['Pcs_Cod'], 'Rep_Req' => 244, 'Emp_Cod' => $_SESSION['Ses_Emp_Cod'])), $obBD_conexion);
        if (empty($rep))
            array_push($pendientes, $proc);
    }
}
//$obBD_con1->echoLog($pendientes);

?>
<!DOCTYPE html>
<HTML>

<HEAD>
    <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
    <TITLE><?Php echo "Configuración General [EXA]"; ?></TITLE>
    <meta charset="UTF-8">
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script>
        var sucursalesEmpresa = <?php echo json_encode($sucursales) ?>;
    </script>
    <!-- <meta charset="utf-8"> -->
</HEAD>

<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Configuraciones de Empresa</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="row">
                <div class="col-xs-12">
                    <fieldset class="exa-fieldset">
                        <legend class="Titulos2">Formulario de Configuracion de Proyecto</legend>
                        <form id="form1" name="form1" class="form-horizontal normal formulario" action="javascript:validar()" method="post" enctype="multipart/form-data">
                            <input type='hidden' name='saveData' value='true' />
                            <div class="col-xs-4">
                                <!-- Form Name -->

                                <!-- Text input-->
                                <div class="form-group">
                                    <label class="col-md-4 control-label" style="text-align: right;font-size: 10px;padding-top: 2px;">Tipo de Ambiente: </label>
                                    <div class="col-md-5">
                                        <div class="radioset">
                                            <label for="radio-tipo-ambiente-2">Prueba</label>
                                            <input type="radio" name="Cof_Fac" value="1" id="radio-tipo-ambiente-2" <?php if ($empresa['Cof_Fac'] == '1') echo "checked"; ?>>
                                            <label for="radio-tipo-ambiente-1">Producción</label>
                                            <input type="radio" name="Cof_Fac" value="2" id="radio-tipo-ambiente-1" <?php if ($empresa['Cof_Fac'] == '2') echo "checked"; ?>>
                                        </div>
                                    </div>
                                </div>

                                <!-- Text input-->
                                <div class="form-group">
                                    <label class="col-md-4 control-label" style="text-align: right;font-size: 10px;padding-top: 2px;">Generar Factura Electrónica:</label>
                                    <div class="col-md-3">
                                        <div class="radioset">
                                            <input type="radio" name="Cof_Gce" id="radiofact1" value="S" autocomplete=off" <?php if ($empresa['Cof_Gce'] == 'S') echo "checked"; ?>>
                                            <label for="radiofact1">Si
                                            </label>
                                            <input type="radio" name="Cof_Gce" id="radiofact2" value="N" autocomplete="off" <?php if ($empresa['Cof_Gce'] == 'N') echo "checked"; ?>>
                                            <label for="radiofact2">No
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Text input-->
                                <div class="form-group">
                                    <label class="col-md-4 control-label" style="text-align: right;font-size: 10px;padding-top: 2px;">Generar Contabilidad:</label>
                                    <div class="col-md-3">
                                        <div class="radioset">
                                            <input type="radio" name="Cof_Con" id="radio-genContabilidad-1" value="S" autocomplete="off" <?php if ($empresa['Cof_Con'] == 'S') echo "checked"; ?>>
                                            <label for="radio-genContabilidad-1">Si
                                            </label>
                                            <input type="radio" name="Cof_Con" id="radio-genContabilidad-2" value="N" autocomplete="off" <?php if ($empresa['Cof_Con'] == 'N') echo "checked"; ?>>
                                            <label for="radio-genContabilidad-2">No
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Text input-->
                                <div class="form-group">
                                    <label class="col-md-4 control-label" style="text-align: right;font-size: 10px;padding-top: 2px;">Obligado a Llevar Contabilidad:</label>
                                    <div class="col-md-3">
                                        <div class="radioset">
                                            <input type="radio" name="Emp_Cnt" id="radio-llevarContabilidad-1" value="S" autocomplete="off" <?php if ($empresa['Emp_Cnt'] == 'S') echo "checked"; ?>>
                                            <label for="radio-llevarContabilidad-1">Si
                                            </label>
                                            <input type="radio" name="Emp_Cnt" id="radio-llevarContabilidad-2" value="N" autocomplete="off" <?php if ($empresa['Emp_Cnt'] == 'N') echo "checked"; ?>>
                                            <label for="radio-llevarContabilidad-2">No
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Text input-->
                                <div class="form-group">
                                    <label class="col-md-4 control-label" style="text-align: right;font-size: 10px;padding-top: 2px;">Contribuyente Especial:</label>
                                    <div class="col-md-3">
                                        <div class="radioset">
                                            <input type="radio" name="Emp_Reg_Choice" id="radio-contribuyentes-1" value="S" onchange="toggleContribuyenteNum(event)" autocomplete="off" <?php if (!empty($empresa['Emp_Reg'])) echo "checked"; ?>>
                                            <label for="radio-contribuyentes-1">Si
                                            </label>
                                            <input type="radio" name="Emp_Reg_Choice" id="radio-contribuyentes-2" value="N" onchange="toggleContribuyenteNum(event)" autocomplete="off" <?php if (empty($empresa['Emp_Reg'])) echo "checked"; ?>>
                                            <label for="radio-contribuyentes-2">No
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-xs-6">
                                        <div class="row" id="Emp_Reg_Row" <?php if (empty($empresa['Emp_Reg'])) echo 'style="display:none"'; ?>>
                                            <label for="Emp_Reg" class="col-xs-2">Nmero:</label>
                                            <div class="col-xs-9">
                                                <input class="form-control input-xs" type="text" name="Emp_Reg" id="Emp_Reg" value="<?php echo $empresa['Emp_Reg']; ?>" />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Text input-->
                                <div class="form-group">
                                    <label class="col-md-4 control-label" style="text-align: right;font-size: 10px;padding-top: 2px;">Venta(Recibo) afecta stock:</label>
                                    <div class="col-md-3">
                                        <div class="radioset">
                                            <input type="radio" name="Cof_Stk" id="radio-afecta-1" value="S" autocomplete="off" <?php if ($empresa['Cof_Stk'] == 'S') echo "checked"; ?>>
                                            <label for="radio-afecta-1">Si
                                            </label>
                                            <input type="radio" name="Cof_Stk" id="radio-afecta-2" value="N" autocomplete="off" <?php if ($empresa['Cof_Stk'] == 'N') echo "checked"; ?>>
                                            <label for="radio-afecta-2">No
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <!-- Text input-->
                                <div class="form-group">
                                    <label class="col-md-4 control-label" style="text-align: right;font-size: 10px;padding-top: 2px;">Venta(Recibo) Generar Asiento:</label>
                                    <div class="col-md-3">
                                        <div class="radioset">
                                            <input type="radio" name="Cof_Gcr" id="rad-afecta-1" value="S" autocomplete="off" <?php if ($empresa['Cof_Gcr'] == 'S') echo "checked"; ?>>
                                            <label for="rad-afecta-1">Si
                                            </label>
                                            <input type="radio" name="Cof_Gcr" id="rad-afecta-2" value="N" autocomplete="off" <?php if ($empresa['Cof_Gcr'] == 'N') echo "checked"; ?>>
                                            <label for="rad-afecta-2">No
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Ventas con stock negativo -->
                                <div class="form-group">
                                    <label class="col-md-4 control-label" style="text-align: right;font-size: 10px;padding-top: 2px;">Vender con Stock Negativo:</label>
                                    <div class="col-md-3">
                                        <div class="radioset">
                                            <input type="radio" name="Cof_Stk_Neg" id="rad-stk-neg-1" value="S" autocomplete="off" <?php if ($empresa['Cof_Stk_Neg'] == 'S') echo "checked"; ?>>
                                            <label for="rad-stk-neg-1">Si
                                            </label>
                                            <input type="radio" name="Cof_Stk_Neg" id="rad-stk-neg-2" value="N" autocomplete="off" <?php if ($empresa['Cof_Stk_Neg'] == 'N') echo "checked"; ?>>
                                            <label for="rad-stk-neg-2">No
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- MICROEMPRESAS -->
                                <div class="form-group">
                                    <label class="col-md-4 control-label" style="text-align: right;font-size: 10px;padding-top: 2px;">Microempresa:</label>
                                    <div class="col-md-3">
                                        <div class="radioset">
                                            <input type="radio" name="Cof_Micro" id="rad-micro-1" value="S" autocomplete="off" <?php if ($empresa['Cof_Micro'] == 'S') echo "checked"; ?>>
                                            <label for="rad-micro-1">Si
                                            </label>
                                            <input type="radio" name="Cof_Micro" id="rad-micro-2" value="N" autocomplete="off" <?php if ($empresa['Cof_Micro'] == 'N') echo "checked"; ?>>
                                            <label for="rad-micro-2">No
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!--  Código de Artesano -->
                                <div class="form-group">
                                    <label class="col-md-4 control-label" style="text-align: right; font-size: 10px; padding-top: 10px;">Artesano Calificado:</label>
                                    <div class="col-md-8" style="display: flex; align-items: center; padding-top: 5px;">
                                        <!-- Radio buttons -->
                                        <div class="radioset" style="margin-right: 15px;">
                                            <input type="radio" name="Art_Calif_Choice" id="radio-artesano-1" value="S" onchange="toggleArtCalifNum(event)" autocomplete="off" <?php if (!empty($empresa['Art_Calif'])) echo "checked"; ?>>
                                            <label for="radio-artesano-1">Si</label>
                                            <input type="radio" name="Art_Calif_Choice" id="radio-artesano-2" value="N" onchange="toggleArtCalifNum(event)" autocomplete="off" <?php if (empty($empresa['Art_Calif'])) echo "checked"; ?>>
                                            <label for="radio-artesano-2">No</label>
                                        </div>
                                        <!-- Input field for Art_Calif -->
                                        <div id="Art_Calif_Row" style="<?php if (empty($empresa['Art_Calif'])) echo 'display:none;'; ?> margin-left: 10px;">
                                            <div style="display: flex; align-items: center;">
                                                <label for="Art_Calif" style="margin: 0 10px 0 20px;">Número: </label>
                                                <input class="form-control input-xs" type="text" name="Art_Calif" id="Art_Calif" value="<?php echo $empresa['Art_Calif']; ?>" style="width: 100px;"/>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- AGENTE DE RETENCION -->
                                <div class="form-group">
                                    <label class="col-md-4 control-label" style="text-align: right;font-size: 10px;padding-top: 5px;">Agente Retencion:</label>
                                    <div class="col-md-3">
                                        <div class="radioset">
                                            <input type="radio" name="Cof_Age" id="rad-age-1" value="S" autocomplete="off" <?php if ($empresa['Cof_Age'] == 'S') echo "checked"; ?>>
                                            <label for="rad-age-1">Si
                                            </label>
                                            <input type="radio" name="Cof_Age" id="rad-age-2" value="N" autocomplete="off" <?php if ($empresa['Cof_Age'] == 'N') echo "checked"; ?>>
                                            <label for="rad-age-2">No
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- ASUMIR RETENCION SIN GENERAR EL COMPROBANTE -->
                                <div class="form-group">
                                    <label class="col-md-4 control-label" style="text-align: right;font-size: 10px;padding-top: 5px;">Retención sin comprobante:</label>
                                    <div class="col-md-3">
                                        <div class="radioset">
                                            <input type="radio" name="Ret_Scom" id="rad-ret-1" value="S" autocomplete="off" <?php if ($empresa['Ret_Scom'] == 'S') echo "checked"; ?>>
                                            <label for="rad-ret-1">Si
                                            </label>
                                            <input type="radio" name="Ret_Scom" id="rad-ret-2" value="N" autocomplete="off" <?php if ($empresa['Ret_Scom'] == 'N') echo "checked"; ?>>
                                            <label for="rad-ret-2">No</label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Funciones de la camaronera -->
                                <div class="form-group">
                                    <label class="col-md-4 control-label" style="text-align: right;font-size: 10px;padding-top: 2px;">Actividad Camaronera:</label>
                                    <div class="col-md-3">
                                        <div class="radioset">
                                            <input type="radio" name="Cof_NegCam" id="rad-cam-1" value="S" autocomplete="off" <?php if ($empresa['Cof_NegCam'] == 'S') echo "checked"; ?>>
                                            <label for="rad-cam-1">Si</label>
                                            <input type="radio" name="Cof_NegCam" id="rad-cam-2" value="N" autocomplete="off" <?php if ($empresa['Cof_NegCam'] == 'N') echo "checked"; ?>>
                                            <label for="rad-cam-2">No</label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Funciones de CCxPP y CCxCC -->
                                <div class="form-group">
                                    <label class="col-md-4 control-label" style="text-align: right;font-size: 10px;padding-top: 2px;">Ver saldos en Compras/Ventas:</label>
                                    <div class="col-md-3">
                                        <div class="radioset">
                                            <input type="radio" name="Cof_Sld" id="rad-sld-1" value="S" autocomplete="off" <?php if ($empresa['Cof_Sld'] == 'S') echo "checked"; ?>>
                                            <label for="rad-sld-1">Si</label>
                                            <input type="radio" name="Cof_Sld" id="rad-sld-2" value="N" autocomplete="off" <?php if ($empresa['Cof_Sld'] == 'N') echo "checked"; ?>>
                                            <label for="rad-sld-2">No</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-md-4 control-label" style="text-align: right;font-size: 10px; padding-top: 5px;">Tipo Regimen:</label>
                                    <div class="col-md-5">
                                        <select name="Cof_Rim" type="text" class="form-control input-xs" required>
                                            <option value="N" <?php if ($empresa['Cof_Rim'] == 'N') echo "Selected"; ?>>Regimen General</option>
                                            <option value="NP" <?php if ($empresa['Cof_Rim'] == 'NP') echo "Selected"; ?>>Rimpe Negocio Popular</option>
                                            <option value="EM" <?php if ($empresa['Cof_Rim'] == 'EM') echo "Selected"; ?>>Rimpe Emprendedor</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-md-4 control-label" style="text-align: right;font-size: 10px; padding-top: 5px;">Version Retencion:</label>
                                    <div class="col-md-2">
                                        <select name="Cof_Ret" type="text" class="form-control input-xs" required>
                                            <option value="1.0" <?php if ($empresa['Cof_Ret'] == '1.0') echo "Selected"; ?>>1.0</option>
                                            <option value="2.0" <?php if ($empresa['Cof_Ret'] == '2.0') echo "Selected"; ?>>2.0</option>
                                        </select>
                                    </div>
                                </div>

                            </div>
                            <div class="col-xs-4">
                                <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Datos de la Empresa</legend>
                                    <div class="form-group">
                                        <label for="" class="col-md-4 control-label">Repres. Legal:</label>
                                        <div class="col-md-8">
                                            <input type="text" name="Emp_Rep" class="form-control input-xs" id="Emp_Rep" value="<?php echo $empresa['Emp_Rep']; ?>">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="" class="col-md-4 control-label">C.I. Repres. Legal:</label>
                                        <div class="col-md-8">
                                            <input type="text" name="Emp_Rre" class="form-control input-xs" id="Emp_Rre" value="<?php echo $empresa['Emp_Rre']; ?>">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="" class="col-md-4 control-label">Contador:</label>
                                        <div class="col-md-8">
                                            <input type="text" name="Emp_Con" class="form-control input-xs" id="Emp_Con" value="<?php echo $empresa['Emp_Con']; ?>">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="" class="col-md-4 control-label">C.I. Contador:</label>
                                        <div class="col-md-8">
                                            <input type="text" name="Emp_Rco" class="form-control input-xs" id="Emp_Rco" value="<?php echo $empresa['Emp_Rco']; ?>">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="Emp_Nom" class="col-md-4 control-label">Nombre de la Empresa:</label>
                                        <div class="col-md-8">
                                            <input type="text" name="Emp_Nom" class="form-control input-xs" id="Emp_Nom" value="<?php echo $empresa['Emp_Nom']; ?>">
                                        </div>
                                    </div>

                                    <!--div class="form-group">
                                        <label for="Emp_Log" class="col-md-4 control-label">Logo Empresa:</label>
                                        <div class="col-md-8">
                                            <input id="Emp_Log" name="Emp_Log"  size="150" accept=".jpg,.JPG, .PNG, .png" type="file" value="" class="form-control input-sm" multiple >
                                        </div>
                                    </div-->
                                    
                                    <div class="form-group">
                                        <label for="Emp_Log" class="col-md-4 control-label">Logo Empresa:</label>
                                        <div class="col-md-8">
                                            <div class="card text-center" style="width: 100px; height: 100px; border: 1px solid #b5b5b5; display: flex; align-items: center; justify-content: center;">
                                                <div>
                                                    <img id="imagen_preview_empresa" src="<?php echo $empresa['Emp_Log']; ?>" alt="" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                                </div>
                                            </div>
                                            <input id="Emp_Log" name="Emp_Log" size="150" accept=".jpg,.JPG, .PNG, .png" type="file" value="" class="form-control input-sm" multiple>
                                        </div>
                                    </div>
                                </fieldset>
                            </div>

                            <div class="col-xs-4">
                                <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Informacion Adicional</legend>
                                    <div class="form-group">
                                        <label for="" class="col-md-4 control-label">Sucursal:</label>
                                        <div class="col-md-6">
                                            <select id="Suc_Cod" name="Suc_Cod" onchange="" class="form-control input-xs select_sucursal">
                                                <?php
                                                foreach ($sucursales as $s) {
                                                    echo "<option data-description='$s[Suc_Des]' data-sri='$s[Suc_Sri]' data-t1='$s[Suc_Te1]' data--suc-cod='$s[Suc_Cod]' value='$s[Suc_Cod]'>$s[Suc_Sri].$s[Suc_Des]</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="" class="col-md-4 control-label">Ciudad:</label>
                                        <div class="col-md-6">
                                            <select id="Ciu_Cod" name="Ciu_Cod" onchange="" class="form-control input-xs select_sucursal">
                                                <?php
                                                foreach ($ciudades as $s) {
                                                    echo "<option data-description='$s[Ciu_Des]' data-sri='$s[Ciu_Est]' data-t1='$s[Pas_Cod]' data--ciu-cod='$s[Ciu_Cod]' value='$s[Ciu_Cod]'>$s[Ciu_Des]</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="" class="col-md-4 control-label">SRI:</label>
                                        <div class="col-md-3">
                                            <input type="text" name="Suc_Sri" class="form-control input-xs" id="Suc_Sri">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="" class="col-md-4 control-label">Tipo:</label>
                                        <div class="col-md-6">
                                            <input type="text" name="Suc_Des" class="form-control input-xs" id="Suc_Des">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="" class="col-md-4 control-label">Direcci&oacute;n:</label>
                                        <div class="col-md-6">
                                            <input type="text" name="Suc_Dir" class="form-control input-xs" id="Suc_Dir">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="" class="col-md-4 control-label">Correo:</label>
                                        <div class="col-md-6">
                                            <input type="text" name="Suc_Cor" class="form-control input-xs" id="Suc_Cor">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="" class="col-md-4 control-label">Tel&eacute;fono #1:</label>
                                        <div class="col-md-6">
                                            <input type="text" name="Suc_Te1" class="form-control input-xs" id="Suc_Te1">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="" class="col-md-4 control-label">Tel&eacute;fono #2:</label>
                                        <div class="col-md-6">
                                            <input type="text" name="Suc_Te2" class="form-control input-xs" id="Suc_Te2">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="" class="col-md-4 control-label">Logo sucursal:</label>
                                        <div class="col-md-6">
                                            <div class="card text-center" style="width: 100px; height: 100px; border: 1px solid #b5b5b5; display: flex; align-items: center; justify-content: center;">
                                                <div>
                                                    <img id="imagen_preview" src="" alt="" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                                </div>
                                            </div>
                                            <input id="Suc_Log" name="Suc_Log" size="150" accept=".jpg,.JPG, .PNG, .png" type="file" value="" class="form-control input-sm" multiple>
                                        </div>
                                    </div>
                                </fieldset>
                            </div>

                            <div class="col-xs-12 text-center">
                                <br>
                                <button type="submit" value="Guardar" class="btn btn-success">
                                    <i class="glyphicon glyphicon-floppy-disk"></i> Guardar
                                </button>
                            </div>
                            <?php if (count($pendientes) > 0) { ?>
                                <div class="col-xs-12 text-center">
                                    <br>
                                    <button id="btnReport" type="button" onclick="saveReporte()" class="btn btn-xs btn-info no" data-reporte="<?php echo htmlentities(json_encode($pendientes)) ?>">
                                        <i class="glyphicon glyphicon-tent"></i> Reportes
                                    </button>
                                </div>
                            <?php } ?>

                        </form>
                    </fieldset>
                </div>
            </div>
        </div>
    </div>
    <script>
        //sucursalesEmpresa
        var archSub = "";
        $(function() {
            var fileExtension = "";

            console.log(sucursalesEmpresa);
            asignarDataSucursal();
            $(':file').on('change', function() {
                //obtenemos un array con los datos del archivo
                //var file = $("#Emp_Log")[0].files[0];
                //archSub = file;
                //obtenemos el nombre del archivo
                //var fileName = file.name;
                //obtenemos la extensin del archivo
                //fileExtension = fileName.substring(fileName.lastIndexOf('.') + 1);
                //obtenemos el tamao del archivo
                //var fileSize = file.size;
                //obtenemos el tipo de archivo image/png ejemplo
                //var fileType = file.type;
                //Prueba
                //var inputFileImage = document.getElementById("Emp_Log");
                //var file = inputFileImage.files[0];
                //var data = new FormData();
                //data.append('Emp_Log',file);
                //console.log(data);

            });
        });

        function asignarDataSucursal() {
            $('#Suc_Cod').on('change', function() {
                var sel_suc = $(this).find('option:selected');
                asignarValores(sel_suc.val());
            }).trigger('change');

        }

        function asignarValores(Suc_Cod) {
            var datoBusq;
            for (var i = 0; i < sucursalesEmpresa.length; i++) {
                if (sucursalesEmpresa[i]['Suc_Cod'] === Suc_Cod) {
                    datoBusq = sucursalesEmpresa[i];
                }
            }
            valorAlHtml(datoBusq);
        }

        function valorAlHtml(objeto) {
            console.log(objeto);
            $('#Suc_Sri').val(objeto['Suc_Sri']);
            $('#Suc_Des').val(objeto['Suc_Des']);
            $('#Suc_Dir').val(objeto['Suc_Dir']);
            $('#Suc_Cor').val(objeto['Suc_Cor']);
            $('#Suc_Te1').val(objeto['Suc_Te1']);
            $('#Suc_Te2').val(objeto['Suc_Te2']);
            $('#Ciu_Cod').val(objeto['Ciu_Cod']);
            var urlImagen = objeto['Suc_Log'];
            $('#imagen_preview').attr('src', urlImagen);
        }

        function guardar(data) {
            $.saveDataJson("", data, function(response) {
                $.alert(response.message);
                return false;
            });
        }

        function validar() {
            // Faltan Validaciones
            if ($("#radio-contribuyentes-2").is(":checked")) $("#Emp_Reg").val("");
            console.log(archSub);
            //var data = $("#form1").getData("saveData");
            //$.createDialogConfirm("Esta seguro que desea guardar los datos", data, guardar);
            console.log(sucursalesEmpresa);
            var formData = new FormData($('#form1')[0]);
            $('#loader').show();
            $.ajax({
                url: window.location.pathname,
                type: 'POST',
                data: formData,
                dataType: "json",
                async: true,
                cache: false,
                contentType: false,
                processData: false
            }).done(function(re) {
                console.log(re);
                if (re.success === true) {
                    $.alert('Se ha registrado con Exito!');
                } else {
                    $.alert('No se pudo realizar la accion!');
                }
            }).fail(function() {
                $.alert();
            }).always(function() {
                $('#loader').hide();
            });
        }

        function toggleContribuyenteNum(event) {
            if ($(event.currentTarget).attr("id") == "radio-contribuyentes-1") {
                $("#Emp_Reg_Row").show();
            }
            if ($(event.currentTarget).attr("id") == "radio-contribuyentes-2") {
                $("#Emp_Reg_Row").hide();
            }
        }

        //Artesano calificado
        function toggleArtCalifNum(event) {
            if ($(event.currentTarget).attr("id") === "radio-artesano-1") {
                $("#Art_Calif_Row").show();
            } else if ($(event.currentTarget).attr("id") === "radio-artesano-2") {
                $("#Art_Calif_Row").hide();
            }
        }

        function saveReporte() {
            var data = {
                guardarReporte: true,
                pendientes: $('#btnReport').data("reporte")
            };
            $.createDialogConfirm('Esta seguro que desea <b class="green">GUARDAR</b>?', data, function() {
                $.saveDataJson('', data, function(resp) {
                    $('#btnReport').hide();
                });
            });
        }


        //Previsualizar la imagen antes de registrarla para  las sucursales
        $('#Suc_Log').change(function() {
            var imagenPreview = $('#imagen_preview');
            var inputFile = $(this)[0];
            if (inputFile.files && inputFile.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    imagenPreview.attr('src', e.target.result);
                };
                reader.readAsDataURL(inputFile.files[0]);
            } else {
                imagenPreview.attr('src', '');
            }
        });

        // Previsualizar imagenes para empresa
        $('#Emp_Log').change(function() {
            var imagenPreview = $('#imagen_preview_empresa');
            var inputFile = $(this)[0];
            if (inputFile.files && inputFile.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    imagenPreview.attr('src', e.target.result);
                };
                reader.readAsDataURL(inputFile.files[0]);
            } else {
                imagenPreview.attr('src', '');
            }
        });
    </script>
</BODY>

</HTML>