<?php

/**
 * @abstract Permite descargar un archivo de texto de los aporte extras realizados por semana para cargar en el iess
 * @author Santiago Ruiz 
 * @version 1.0
 * Actualizacion: Wilson Belduma
 * Fecha de creación  2023-07-24
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/rhu_log_roles.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Rol($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Rol;
$hoy = date("Y-m-d");
$mes = date("m");

if (isset($getDefaults)) {
    $obBD_con1->getRolDefaults($_GET, $obBD_conexion);
}



if (isset($aportesAjax)) {  
    $data = $_GET;
    $archivosgenerados = $obBD_con1->getArrayConsulta(67, $data, $obBD_conexion);
    $responce = array(
        'success' => true,
        'rows' =>  $responce['rows'] = $obBD_con1->getArrayConsulta(68, $data, $obBD_conexion),
        'defaults' => $archivosgenerados
    );
    $obBD_con1->echoJson($responce);
}


if (isset($saveAportes)) {
    $file = "test.txt";
    try {
        //$countdata = count($data);
        $codigo = $obBD_con1->getArrayConsulta(67, $rol, $obBD_conexion);
        $Codigo = $codigo['REPORTE_COD'][0];
        $aportes = '';
        $sb = '';
        $obBD_ins1 =  new Class_Log_Datos_Rol;
        $obBD_conexionIns = new Class_Log_Conexion_Rol($Ses_Dat_Dis);
        $obBD_ins1->inicio_transaccion($obBD_conexionIns);
        $obBD_ins1->operacionobBD(66, $rol, $obBD_conexionIns); //inserta consulta general de reporte          
        foreach ($data as $d) {
            $aportes = $d['EMP_RUC'] . ";" . $d['SUCURSAL_IESS'] . ";" . $d['ANIO'] . ";" . $d['PERIODO'] . ";" . $d['TIPO'] . ";" . $d['PRS_CED'] . ";" . $d['ROL_VAL'] . ";" . $d['O'] . "\n";
            $sb .= $aportes;
        }
        $txt = fopen($file, "w") or die("Unable to open file!");
        fwrite($txt, $sb);
        fclose($txt);
        $obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns);
    } catch (Exception $e) {
        $obBD_ins1->rollBack_nomsn($obBD_conexionIns);
        $responce = array('success' => false, 'message' => 'No se logro generar el archivo <br/><span style="color:red;">' . $e->getMessage() . '</span>', 'error' => $e->getMessage());
        $obBD_con1->echoJson($responce);
    }
    if ($obBD_ins1->Error == 0) {
        $responce = array('success' => true);
    } else {
        $responce = array('success' => false, 'message' => "No se ha logrado realizar la Transaccion", 'error' => $obBD_ins1->MsgError);
    }
    $obBD_con1->echoJson($responce);
}

?>
<!DOCTYPE html>
<HTML>

<HEAD>
    <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
    <TITLE><?Php echo "Reporte Décimo tercero [EXA]"; ?></TITLE>
    <meta charset="UTF-8">
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script type="text/javascript" src="../../framework/jquery/MonthPicker/jquery.mtz.monthpicker.js"></script>
    <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
    <!--script type="text/ecmascript" src="../VALIDACIONES/rhu_val_roles.js?x=600"></script-->
    <script type="text/ecmascript" src="../VALIDACIONES/rhu_val_roles_decimo.js?x=600"></script>
    
    <style>
        @media print {
            #tablaReporte {
                width: 100%;
                font-size: 10pt;
            }

            #tablaReporte td,
            #tablaReporte th {
                word-wrap: break-word;
                white-space: normal;
            }
        }
    </style>
</HEAD>

<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Reporte décimo tercero</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div>
                <div class="row">
                    <form id="formRol" action="javascript:searchExtra();">
                        <div class="col-xs-3">
                            <fieldset class="exa-fieldset ">
                                <legend class="Titulos2">Plantilla Rol</legend>
                                <div class="form-horizontal normal">
                                    <div class="form-group">
                                        <label class="col-xs-3 control-label label-xs ">Area:</label>
                                        <div class="col-xs-9">
                                            <select id="Are_Cod" name="Are_Cod" class="form-control input-xs">
                                                <option value="1">TODAS</option>
                                                <?php $rs_area = $obBD_con1->getArrayConsulta(11, $Ses_Emp_Cod, $obBD_conexion);
                                                foreach ($rs_area as $row) {
                                                ?><option value="<?php echo $row['Are_Cod']; ?>"><?php echo $row['Are_Des']; ?></option><?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-xs-3 control-label label-xs ">Plantilla:</label>
                                        <div class="col-xs-9">
                                            <select id="Map_Cod" name="Map_Cod" class="form-control input-xs">
                                                <?php $rs_maps = $obBD_con1->getArrayConsulta(10, $Ses_Emp_Cod, $obBD_conexion);
                                                foreach ($rs_maps as $row) {
                                                ?><option value="<?php echo $row['Map_Cod']; ?>"><?php echo $row['Map_Des']; ?></option><?php  }    ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>
                        </div>
                        <div class="col-xs-3">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Datos Generales</legend>
                                <div class="form-horizontal normal">
                                    <div class="form-group">
                                        <label class="col-xs-3 control-label label-xs ">Periodo:</label>
                                        <div class="col-xs-9">
                                            <select id="Pec_Cod" name="Pec_Cod" class="form-control input-xs" onchange="setDecimo();">
                                                <option value="0">Seleccione...</option>
                                                <?php $rs_perio = $obBD_con1->getArrayConsulta(12, $Ses_Emp_Cod, $obBD_conexion);
                                                foreach ($rs_perio as $row) {
                                                ?><option value="<?php echo $row['Pec_Cod']; ?>" data-year="<?php echo $row['Periodo']; ?>">Periodo <?php echo $row['Periodo']; ?></option><?php
                                                                                                                                                                                        }
                                                                                                                                                                                            ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group date-ranges" style="display: none;">
                                        <label class="col-xs-3 control-label label-xs required">Tipo:</label>
                                        <div class="col-xs-9">
                                            <select id="Rol_Tip" name="Rol_Tip" class="form-control input-xs readOnly datatrigger">
                                                <option value="S" data-dias="7">Semanal</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group ranges M Q" hidden>
                                        <label class="col-xs-3 control-label label-xs ">Mes:</label>
                                        <div class="col-xs-9">
                                            <div class="input-group input-group-xs">
                                                <input id="Month" name="Month" type="hidden">
                                                <span id="Mes" class="form-control"></span>
                                                <span class="input-group-btn">
                                                    <button id="MonthButton" onclick="$('#Month').monthpicker('show','#Mes');" class="btn btn-success" type="button"><span class="glyphicon glyphicon-calendar" title="Seleccione Mes"></span></button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>
                        </div>
                        <div class="col-xs-3" hidden>
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Rango</legend>
                                <div class="form-horizontal normal">
                                    <div class="form-group ranges S BS">
                                        <label class="col-xs-3 control-label label-xs ">Inicio:</label>
                                        <div class="col-xs-9">
                                            <select id="Rol_S" class="form-control input-xs datatrigger" onclick="setSemana();"></select>
                                        </div>
                                    </div>
                                    <div class="form-group ranges S BS">
                                        <label class="col-xs-3 control-label label-xs ">Fin:</label>
                                        <div class="col-xs-9">
                                            <select id="Rol_F" class="form-control input-xs datatrigger" onclick="setSemanaF();"></select>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>
                        </div>

                        <div class="col-xs-3" style="display: BLOCK;">
                            <fieldset class="exa-fieldset Rol_Range">
                                <legend class="Titulos2">Rol</legend>
                                <div class="form-horizontal normal">
                                    <div class="form-group" hidden>
                                        <label class="col-xs-3 control-label label-xs required">Numero:</label>
                                        <div class="col-xs-9">
                                            <input type="number" id="Rol_Num" name="Rol_Num" class="form-control input-xs" readonly="" style="text-align: right;" value="1" min="1" step="1" required="" />
                                        </div>
                                    </div>
                                    <div class="form-group" hidden>
                                        <div class="col-xs-12">
                                            <div class="input-group input-group-xs">
                                                <span class="input-group-addon bold alert-info">Desde:</span>
                                                <input id="Rol_Fei" name="Rol_Fei" type="text" class="form-control span" style="text-align: right;">
                                                <span class="input-group-addon bold alert-info">Hasta:</span>
                                                <input name="Rol_Fef" type="text" class="form-control span" style="text-align: right;">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-xs-12">
                                            <div class="input-group input-group-xs">
                                                <span class="input-group-addon bold alert-info">Desde:</span>
                                                <input type="text" id="dateRolini" name="dateRolini" class="form-control input-xs" readonly>
                                                <span class="input-group-addon bold alert-info">Hasta:</span>
                                                <input type="text" id="dateRolfin" name="dateRolfin" class="form-control input-xs" readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>
                        </div>

                        <div class="col-xs-3" style="display: none;">
                            <fieldset class="exa-fieldset Rol_RangeF">
                                <legend class="Titulos2">Rol</legend>
                                <div class="form-horizontal normal">
                                    <div class="form-group">
                                        <label class="col-xs-3 control-label label-xs required">Numero:</label>
                                        <div class="col-xs-9">
                                            <input type="number" id="Rol_Num" name="Rol_Num" class="form-control input-xs" readonly="" style="text-align: right;" value="1" min="1" step="1" required="" />
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-xs-12">
                                            <div class="input-group input-group-xs">
                                                <span class="input-group-addon bold alert-info">Desde:</span>
                                                <input id="Rol_FeiF" name="Rol_Fei" type="text" class="form-control span" style="text-align: right;" readonly="" tabindex="-1">
                                                <span class="input-group-addon bold alert-info">Hasta:</span>
                                                <input name="Rol_FefF" type="text" class="form-control span" style="text-align: right;" readonly="" tabindex="-1">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>
                        </div>
                        <div class="col-xs-2 center vcenter" style="height: 70px;"><button type="submit" class="btn btn-success"><i class="glyphicon glyphicon-search"></i> Buscar</button></div>
                    </form>
                    <div class="col-xs-12" style="min-height: 250px;">
                        <table id="compextra"></table>
                        <div id="listPager"></div>

                    </div>
                </div>
                <!--div class="row">
                    <div class="col-xs-12">
                        <button id="btnGuardar" type="button" onclick="validarAportExtra();" class="btn btn-primary btn-save"><span class="glyphicon glyphicon-floppy-disk"></span> Generar Archivo</button>
                    </div>
                </div-->
            </div>

        </div>
    </div>


    <div id="imprimir" style="display: none;">
        <div style="width: 1030px;">
            <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REPORTE DECIMO TERCERO', '<span class="subtitle"></span>', $obBD_conexion) ?>
            <table id="tablaReporte" cellspacing="0" cellpadding="0" style="width: 700px; border-collapse: collapse;table-layout:auto  ;font-size:12px;"></table>
            <?php echo $obBD_con1->getReportFooter($Ses_Suc_Cod, $Ses_Usu_Cod, $obBD_conexion); ?>
        </div>
    </div>



    <script type="text/javascript">
        function download() {
            window.location.href = '../../rrhh/FRONT/download.php?file==<?php echo $file; ?>';
        }

        $(document).ready(function() {
            createSearchExtra([]);
        });

        $(function() {
            $('#Month').attr('data-monthplacer', '#Mes').createMonthPicker({
                showYear: false,
                prepend: 'Seleccione Mes',
                openOnFocus: false
            }, setRange).monthpicker('setMonthActive', 0);;

        });
    </script>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
</BODY>

</HTML>