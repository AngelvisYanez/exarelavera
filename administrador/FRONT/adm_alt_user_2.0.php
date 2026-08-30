<?php

/**
 * Permite registrar un nuevo Cliente ya sea Nacional(Cedula o Ruc) o Extranjero(Pasaporte)
 *
 * @author car.87cod :)
 * @version 1.0
 * Fecha de actualizacion:	2012-04-16
 * @author lewis.chimarro
 * @version 1.0
 * Fecha de actualizacion:	2014-05-21
 *
 * @package tesoreria.FRONT
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/adm_log_user_2.0.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* objeto para la conexion */
$obBD_conexion = new Class_Log_Conexion_Cli($Ses_Dat_Dis);
/* objeto para consultas */
$obBD_con1 =  new Class_Log_Datos_Cli;

    /* Valida si existe una Persona */
    if(isset($searchPersona)){
        $responce = $obBD_con1->getRowConsulta(17, $Prs_Ced, $obBD_conexion); // like persona        
        if (!(empty($responce))) {// si existe la persona
            $user = $obBD_con1->getRowConsulta(64, array('Prs_Cod'=>$responce['Prs_Cod'],'Emp_Cod'=>$Ses_Emp_Cod), $obBD_conexion);// like user
            if(!empty($user)) {// if exists as user
                $responce['existe']=1;
            } else {
                $responce['existe']=2;
            }
        } else {
            $responce['existe']=0;
        }
        $obBD_con1->echoJson($responce);
    }

    /* Guarda  */
    if(isset($guardar)) {
        //$obBD_con1->debug(true);
        $obBD_conexion_master = new Class_Log_Conexion_Cli();
        $data=$_GET;
        $obBD_con1->inicio_transaccion($obBD_conexion);

        // $Prs_Cod = $data['data']['Prs_Cod'];
        // Asegurar que Prs_Cod sea un valor simple, no un array
        $Prs_Cod = is_array($data['data']['Prs_Cod']) ? (isset($data['data']['Prs_Cod'][0]) ? $data['data']['Prs_Cod'][0] : '') : $data['data']['Prs_Cod'];

		if ($data['flag'] == 0) {// crea Persona, Usuario, Vendedor
            $obBD_con1->operacionobBD(29,$data['data'],$obBD_conexion); //save person
			$Prs_Cod = $obBD_con1->insercionid($obBD_conexion);
        }

        if (!is_array($data['data']['Suc_Cod']))
            $data['data']['Suc_Cod']=array($data['data']['Suc_Cod']);
            $usuarios_cod = array(); // Array para almacenar los Usu_Cod creados
        
        // PRIMERO: Crear todos los usuarios para cada sucursal
        foreach ($data['data']['Suc_Cod'] as $Suc_Cod){
            // Asegurar que Prs_Cod sea un valor numérico simple
            $Prs_Cod_Simple = is_numeric($Prs_Cod) ? intval($Prs_Cod) : $Prs_Cod;

            $obBD_con1->operacionobBD(30,array("Prs_Cod" => $Prs_Cod, "Suc_Cod" =>$Suc_Cod ,"Usu_Ced" => $data['data']['Usu_Ced'], "Usu_Pal" => $data['data']['Usu_Pal']),$obBD_conexion); // save user
            $Usu_Cod = $obBD_con1->insercionid($obBD_conexion); // get Usu_Cod
            $usuarios_cod[] = $Usu_Cod; // Guardar el Usu_Cod

            // save perfiles
            if (!is_array($data['data']['Per_Cod']))
                $data['data']['Per_Cod']=array($data['data']['Per_Cod']);
            foreach ($data['data']['Per_Cod'] as $p)
                $obBD_con1->operacionobBD(31,array("Usu_Cod" => $Usu_Cod, "Per_Cod"=>$p),$obBD_conexion); //save perfil
            // save vendedor - punto de impresion
            if (!empty($data['data']['Pun_Des'])){
                $obBD_con1->operacionobBD(35,array("Suc_Cod" => $Suc_Cod, "Pun_Des"=> $data['data']['Pun_Des'] ),$obBD_conexion);//save punto impresion
                $Pun_Cod = $obBD_con1->insercionid($obBD_conexion); // get Pun_Cod
                $obBD_con1->operacionobBD(36,array("Pun_Cod" => $Pun_Cod, "Prs_Cod"=> $Prs_Cod ),$obBD_conexion);//save vendedor
            }
        }

        // SEGUNDO: Después de crear los usuarios, insertar en manifiesto_usuario si hay cliente seleccionado
        // Ambos parámetros Usu_Cod y Cli_Cod son requeridos para la consulta 56
        if (!empty($data['data']['Cli_Cod']) && !empty($usuarios_cod)) {
            // Usu_Cod: Usar el primer Usu_Cod creado (ya está disponible después del foreach)
            $Usu_Cod = $usuarios_cod[0];
            // Cli_Cod: Obtenido del formulario (enviado desde el frontend)
            $Cli_Cod = $data['data']['Cli_Cod'];
            $Pla_Cod = $data['data']['Pla_Cod'];
            
            // Insertar en la tabla manifiesto_usuario con ambos parámetros requeridos
            $obBD_con1->operacionobBD(56, array("Usu_Cod" => $Usu_Cod, "Cli_Cod" => $Cli_Cod, "Pla_Cod" => $Pla_Cod), $obBD_conexion);
        }

        if ($obBD_con1->fin_transaccion_nomsn($obBD_conexion)) {
            $Dat_Cod = $obBD_con1->getRowConsulta(45,$Ses_Emp_Cod,$obBD_conexion_master); //get Prs_Cod
            foreach ($data['data']['Suc_Cod'] as $Suc_Cod){
            $obBD_con1->operacionobBD(46,array("Suc_Cod" => $Suc_Cod, "Dat_Cod"=> $Dat_Cod['Dat_Cod'], "Acc_Usr"=> $data['data']['Prs_Ced']),$obBD_conexion_master);//save exa
            }

            $responce['success'] = true;
        } else {
            $responce['success'] = false;
            $responce['message'] = "No se ha logrado realizar la transaccion";
        }
        $obBD_con1->echoJson($responce);
    }

    /* Modifica */
    if(isset($modificar)) {
        $obBD_conexion_master = new Class_Log_Conexion_Cli();
        //$obBD_con1->debug(true);
        $data = $_GET;
        $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
        //inactiva todos los usuarios por persona y empresa
        $obBD_con1->operacionobBD(50,array("Prs_Cod"=> $data['data']['Prs_Cod']),$obBD_conexion);//modifica new punto
        $user_general = $obBD_con1->getRowConsulta(48, array("Prs_Cod"=> $data['data']['Prs_Cod'],"Sin_Est"=>true), $obBD_conexion);
        // activa o crea usuarios para sucursales de empresas
        if(isset($data['data']['Suc_Cod_m'])){
        if(!is_array($data['data']['Suc_Cod_m']))
            $data['data']['Suc_Cod_m']=array($data['data']['Suc_Cod_m']);
        } else {
            $data['data']['Suc_Cod_m']=array();
        }

        if(isset($data['data']['Per_Cod_m'])){
        if (!is_array($data['data']['Per_Cod_m']))
            $data['data']['Per_Cod_m']=array($data['data']['Per_Cod_m']);
        }else{
            $data['data']['Per_Cod_m']=array();
        }

        foreach ($data['data']['Suc_Cod_m'] as $Suc_Cod){
            $Usu_Cod=0;
            $row_user = $obBD_con1->getArrayConsulta(51,array("Prs_Cod"=> $data['data']['Prs_Cod'],"Suc_Cod"=>$Suc_Cod),$obBD_conexion); //get Prs_Cod
            $obBD_con1->echoLog(count($row_user));

            if(count($row_user) > 0){
                $row_user=$row_user[0];
                //si encontro usuarios asignados a sucursales
                //actualizando estado de usuarios
                $Usu_Cod=$row_user['Usu_Cod'];
                $obBD_con1->operacionobBD(52,array("Usu_Cod"=> $row_user['Usu_Cod'],"Suc_Cod"=>$Suc_Cod),$obBD_conexion);
                //Borrando perfiles de Usuario
                $obBD_con1->operacionobBD(43,array("Usu_Cod"=> $row_user['Usu_Cod']),$obBD_conexion);
            }else{//crea usuarios en sucursales
                $obBD_con1->operacionobBD(30,array("Prs_Cod" => $data['data']['Prs_Cod'], "Suc_Cod" =>$Suc_Cod ,"Usu_Ced" => $user_general['Usu_Ced'], "Usu_Pal_C" => $user_general['Usu_Pal']),$obBD_conexion); // save user
                $Usu_Cod = $obBD_con1->insercionid($obBD_conexion); // get Usu_Cod
                // save puntos de impresion y vendedor
            }
            //consultar si tiene vendedor
            if($data['data']['Pun_Des_m']!==""){
                $row_vendedor=$obBD_con1->getArrayConsulta(53,array("Prs_Cod" => $data['data']['Prs_Cod'], "Suc_Cod" =>$Suc_Cod),$obBD_conexion);
                $obBD_con1->echoLog(count($row_vendedor));
                if(count($row_vendedor) <= 0){ //si no tiene registro en vendedor
                    $obBD_con1->operacionobBD(35,array("Pun_Des"=> $data['data']['Pun_Des_m'], "Suc_Cod" => $Suc_Cod ),$obBD_conexion);//save new punto
                    $Pun_Cod = $obBD_con1->insercionid($obBD_conexion); // get Pun_Cod
                    $obBD_con1->operacionobBD(36,array("Pun_Cod" => $Pun_Cod, "Prs_Cod"=> $data['data']['Prs_Cod'] ),$obBD_conexion);//save vendedor
                }
            }
            foreach ($data['data']['Per_Cod_m'] as $p) {  //save perfiles
                $obBD_con1->operacionobBD(31,array("Usu_Cod" => $Usu_Cod, "Per_Cod"=>$p),$obBD_conexion);
            }
        }

        // modifica descripcion de  puntos de impresion
        $obBD_con1->operacionobBD(42,array("Pun_Des"=> $data['data']['Pun_Des_m'], "Prs_Cod" => $data['data']['Prs_Cod']),$obBD_conexion);//modifica new puntos

        if($data['data']['Usu_Pal_m'] !== "") {
            $obBD_con1->operacionobBD(41,array("Usu_Cod"=> $data['data']['Usu_Cod'], "Suc_Cod" => $Ses_Suc_Cod, "Usu_Pal" => $data['data']['Usu_Pal_m']),$obBD_conexion);//save new perfil usuario
        }
        
        // Manejar cliente asignado en manifiesto_usuario
        if(!empty($data['data']['Usu_Cod']) && !empty($data['data']['Cli_Cod_Aux'])) {
            $Usu_Cod_Manifiesto = $data['data']['Usu_Cod'];
            $Cli_Cod = $data['data']['Cli_Cod_Aux'];
            $Pla_Cod = isset($data['data']['Pla_Cod_m']) ? $data['data']['Pla_Cod_m'] : '';
            // Verificar si ya existe un registro para este usuario
            $existe_registro = $obBD_con1->getRowConsulta(61, array("Usu_Cod" => $Usu_Cod_Manifiesto), $obBD_conexion);
            
            if($existe_registro && $existe_registro['existe'] > 0) {
                // Si existe, actualizar el Cli_Cod y Pla_Cod
                $obBD_con1->operacionobBD(62, array("Usu_Cod" => $Usu_Cod_Manifiesto, "Cli_Cod" => $Cli_Cod, "Pla_Cod" => $Pla_Cod), $obBD_conexion);
            } else {
                // Si no existe, insertar nuevo registro
                $obBD_con1->operacionobBD(56, array("Usu_Cod" => $Usu_Cod_Manifiesto, "Cli_Cod" => $Cli_Cod, "Pla_Cod" => $Pla_Cod), $obBD_conexion);
            }
        } else if(!empty($data['data']['Usu_Cod']) && empty($data['data']['Cli_Cod_Aux'])) {
            // Si hay Usu_Cod pero no Cli_Cod_Aux, eliminar el registro si existe (para limpiar asignación)
            $Usu_Cod_Manifiesto = $data['data']['Usu_Cod'];
            $obBD_con1->operacionobBD(58, array("Usu_Cod" => $Usu_Cod_Manifiesto), $obBD_conexion);
        }
        // Solo procesar si hay Usu_Cod y Cli_Cod_Aux válidos
        if ($obBD_con1->fin_transaccion_nomsn($obBD_conexion)) {
            $Dat_Cod = $obBD_con1->getRowConsulta(45,$Ses_Emp_Cod,$obBD_conexion_master); //get DataBase
            $obBD_con1->operacionobBD(54,array("Dat_Cod"=> $Dat_Cod['Dat_Cod'], "Acc_Usr"=> $data['data']['Prs_Ced_m']),$obBD_conexion_master);
            foreach ($data['data']['Suc_Cod_m'] as $Suc_Cod){
            $obBD_con1->operacionobBD(46,array("Suc_Cod" => $Suc_Cod, "Dat_Cod"=> $Dat_Cod['Dat_Cod'], "Acc_Usr"=> $data['data']['Prs_Ced_m']),$obBD_conexion_master);//save exa
            }
            $responce['success'] = true;
            $responce['message'] = "Usuario modificado correctamente";
        } else {
            $responce['success'] = false;
            $responce['message'] = "No se ha logrado realizar la transaccion";
        }
        $obBD_con1->echoJson($responce);
    }

    /* VALIDA SI EXISTE LA DESCRIPCION DEL PUNTO */
    if(isset($validatePunto)) {
        $response = $obBD_con1->getRowConsulta(34, array("Pun_Des" => $Pun_Des, "Suc_Cod" => $Ses_Suc_Cod), $obBD_conexion); // like persona
        if ($response['punto'] == 0) {// existe el punto
            $response['success'] = true;
        } else {
            $response['success'] = false;
            $response['message'] = "El punto ya existe";
        }
        $obBD_con1->echoJson($response);
    }

    /* Busqueda por filtros */
    function setDataUsers(&$arr,$obBD_con1,$obBD_conexion){
        foreach($arr as &$v){
                $perfiles = $obBD_con1->getArrayConsulta(40, array('Usu_Cod'=>$v['Usu_Cod'],'Prs_Cod'=>$v['Prs_Cod']), $obBD_conexion);
                $v['Perfiles']=is_array($perfiles)&&!empty($perfiles)?array_map(function($e){ return $e['Per_Des']; }, $perfiles):'';
                $sucursales = $obBD_con1->getArrayConsulta(48, array('Prs_Cod'=>$v['Prs_Cod']), $obBD_conexion);
                $v['Sucursales']=is_array($sucursales)&&!empty($sucursales)?array_map(function($e){ return $e['Suc_Des']; }, $sucursales):'';
        } unset($v);
    }

    if(isset($searchFiltro)) {
        //$obBD_con1->debug(true);
        $data = $obBD_con1->getArrayConsulta(38, array("filtro" => $_GET['filtro'], "dato" => $_GET['dato'],"Suc_Cod" => $Ses_Suc_Cod), $obBD_conexion);
        setDataUsers($data, $obBD_con1, $obBD_conexion);
        // Grid necesita este array
        $obBD_con1->echoJson(array(
            'rows'=>$data,
            'total'=>1,
            'records'=>count($data),
            'success'=>true
        ));
    }

   /* Busqueda all Users */
    if(isset($searchAll)) {
       //$obBD_con1->debug(true);
        $data = $obBD_con1->getArrayConsulta(39, $Ses_Suc_Cod, $obBD_conexion);
        setDataUsers($data, $obBD_con1, $obBD_conexion);
       // Grid necesita este array
        $obBD_con1->echoJson(array(
            'rows'=>$data,
            'total'=>1,
            'records'=>count($data),
            'success'=>true
        ));
    }

   /* Busqueda all Perfiles */
    if(isset($getPerfil)) {
        $data = $obBD_con1->getArrayConsulta(28, $Ses_Emp_Cod, $obBD_conexion);
        // Grid necesita este array
        $obBD_con1->echoJson(array(
            'rows'=>$data,
            'success'=>true
        ));
    }

    /* Busqueda all Perfiles by User */
    if(isset($getPerfilByUser)) {
        $perfiles = $obBD_con1->getArrayConsulta(40, $_GET, $obBD_conexion);
        $sucursales = $obBD_con1->getArrayConsulta(48, $_GET, $obBD_conexion);
        // Grid necesita este array
        $obBD_con1->echoJson(array(
            'perfiles'=>$perfiles,
            'sucursales'=>$sucursales,
            'success'=>true
        ));
    }

    /* Obtener cliente asignado a un usuario desde manifiesto_usuario */
    if(isset($getClienteByUser)) {
        $cliente = $obBD_con1->getRowConsulta(57, $_GET, $obBD_conexion);
        // Grid necesita este array
        $obBD_con1->echoJson(array(
            'cliente'=>$cliente,
            'success'=>true
        ));
    }

    /* seccion para obtener los clientes registrados en la empresa nuevo 03-12-25 */
    if (isset($clientesAjax)) {
            
        $data = filter_input_array(INPUT_GET);
        $data['Emp_Cod'] = $Ses_Emp_Cod;
        
        // Primero contar los registros (sin limits)
        $contar = $obBD_con1->getRowConsulta(55, $data, $obBD_conexion);
        
        // Calcular la paginación
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $rows = isset($_GET['rows']) ? intval($_GET['rows']) : 50;
        $pagination = pages($contar['total'], $page, $rows);
        
        $responce = $pagination['data'];
        $data['limits'] = $pagination['limits'];
        
        // Obtener los datos con paginación
        if ($contar['total'] > 0) {
            $responce['rows'] = $obBD_con1->getArrayConsulta(55, $data, $obBD_conexion);
        } else {
            $responce['rows'] = array();
        }
        
        utf8_encode_deep($responce);
        echo json_encode($responce);
        exit();
    }

    if(isset($AjaxPlanta)) {
        $Cli_Cod = $_GET['Cli_Cod'];
        $data = $obBD_con1->getArrayConsulta(63, array("Cli_Cod" => $Cli_Cod), $obBD_conexion);
        $obBD_con1->echoJson(array('rows'=>$data,'success'=>true));
    }

    /* Actualizar solo cliente y planta de manifiesto_usuario (modal rápido desde control usuarios) */
    if(isset($actualizarClientePlantaAjax)) {
        $Usu_Cod = intval($_GET['Usu_Cod']);
        $Cli_Cod = (isset($_GET['Cli_Cod']) && $_GET['Cli_Cod'] !== '') ? intval($_GET['Cli_Cod']) : '';
        $Pla_Cod = isset($_GET['Pla_Cod']) ? $_GET['Pla_Cod'] : '';
        $obBD_con1->inicio_transaccion($obBD_conexion);
        try {
            if(!empty($Usu_Cod) && !empty($Cli_Cod)) {
                $existe = $obBD_con1->getRowConsulta(61, array("Usu_Cod" => $Usu_Cod), $obBD_conexion);
                if($existe && $existe['existe'] > 0) {
                    $obBD_con1->operacionobBD(62, array("Usu_Cod" => $Usu_Cod, "Cli_Cod" => $Cli_Cod, "Pla_Cod" => $Pla_Cod), $obBD_conexion);
                } else {
                    $obBD_con1->operacionobBD(56, array("Usu_Cod" => $Usu_Cod, "Cli_Cod" => $Cli_Cod, "Pla_Cod" => $Pla_Cod), $obBD_conexion);
                }
            } else if(!empty($Usu_Cod)) {
                $obBD_con1->operacionobBD(58, array("Usu_Cod" => $Usu_Cod), $obBD_conexion);
            }
        } catch(Exception $e) {
            $obBD_con1->rollBackNomsn($e->getMessage(), $responce);
            $obBD_con1->echoJson($responce);
        }
        if($obBD_con1->fin_transaccion_nomsn($obBD_conexion)) {
            $responce['success'] = true;
            $responce['message'] = "Actualizado correctamente";
        } else {
            $responce['success'] = false;
            $responce['message'] = "Error al actualizar";
        }
        $obBD_con1->echoJson($responce);
    }

    $perfil = $obBD_con1->getArrayConsulta(28, $Ses_Emp_Cod, $obBD_conexion);// Get Perfiles
    $rs_ciudad = $obBD_con1->getArrayConsulta(15,'',$obBD_conexion);
    $sucursales = $obBD_con1->getArrayConsulta(47,$Ses_Emp_Cod,$obBD_conexion);
?>

<!DOCTYPE html>
<HTML>
    <HEAD>
        <!--TITLE><?php echo $Ses_Sys_Nom; ?></TITLE-->
        <TITLE><?php echo "Usuarios Regitrar/ Modif. [EXA]"; ?></TITLE>
        <meta charset= "UTF-8">
        <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
        <?php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
        <script type="text/javascript" src="../VALIDACIONES/adm_val_user_2.0.js?A=25"></script>
    </HEAD>
    <BODY>
        <div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  USUARIO</h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div class="row">
                    <div class="col-sm-12">
                        <div id="tabsUser" class="ui-tab-fix ui-tabs noPaddingH">
                            <ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix">
                                <li><a href="#tabs-1">Registrar usuario</a></li>
                                <li><a href="#tabs-2">Modificar usuario</a></li>
                            </ul>

                                <!-- CREAR TAB !-->
                                <div id="tabs-1"class="ui-tabs-panel ui-widget-content ui-corner-bottom" style="display: none;"  >
                                    <div class="row">
                                        <div class="col-md-6 col-sm-8 col-md-offset-3">
                                            <form class="form-horizontal normal" id="frmUser" name="frmUser" autocomplete="off">
                                                <input name="Prs_Cod" type="text" class="hidden" />
                                                <fieldset class="exa-fieldset" >
                                                    <legend class="Titulos2">Datos del usuario</legend>

                                                    <!-- Cedula -->
                                                    <div class="form-group">
                                                        <label class="col-xs-3 control-label label-xs required">C&eacute;dula/RUC:</label>
                                                        <div class="col-xs-5" >
                                                            <div class="input-group input-group-xs">
                                                                <input id="Prs_Ced" name="Prs_Ced" type="text" class="form-control input-xs" onchange="validar(1)" onkeypress="return validar_numeric(event)" maxlength="13" required />
                                                                <span class="input-group-addon validate" ><i></i></span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Nombre -->
                                                    <div class="form-group">
                                                        <label class="col-xs-3 control-label label-xs required"><span class='natural'>Nombres:</span><span class='juridico' style="display: none;">Nomb.Comerc.:</span></label>
                                                        <div class="col-xs-6" ><input id="Prs_Nom" name="Prs_Nom" type="text" class="form-control input-xs" required="" /></div>
                                                    </div>

                                                    <!-- Apellido -->
                                                    <div class="form-group">
                                                        <label class="col-xs-3 control-label label-xs required"><span class='natural'>Apellidos:</span><span class='juridico' style="display: none;">Razón Social:</span></label>
                                                        <div class="col-xs-6" ><input id="Prs_Ape" name="Prs_Ape" type="text" class="form-control input-xs" required="" /></div>
                                                    </div>

                                                    <!-- Ciudad -->
                                                    <div class="form-group">
                                                        <label class="col-xs-3 control-label label-xs required">Ciudad:</label>
                                                        <div class="col-xs-5" >
                                                            <?php  ?>
                                                            <select id="Ciu_Cod" name="Ciu_Cod" class="form-control input-xs" data-placeholder="Seleccione una ciudad" required="" >
                                                                <option value=""></option>
                                                                <?php  foreach($rs_ciudad as $row){ echo "<option value='$row[Ciu_Cod]' data-prov='$row[Pro_Nom]' data-pais='$row[Pas_Nom]'>$row[Ciu_Des]</option>"; } ?>
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <!-- Genero -->
                                                    <div class="form-group natural">
                                                        <label class="col-xs-3 control-label label-xs required">Genero:</label>
                                                        <div class="col-xs-5" >
                                                            <select id="Prs_Sex" name="Prs_Sex" class="form-control input-xs">
                                                                <option value = "M" >MASCULINO</option>
                                                                <option value = "F" >FEMENINO</option>
                                                            </select>
                                                        </div>
                                                    </div>

                                                </fieldset>
                                                <!-- LOGUEO -->
                                                <fieldset class="exa-fieldset" >
                                                    <legend class="Titulos2">Datos Logueo</legend>

                                                    <!-- Usuario -->
                                                    <div class="form-group">
                                                        <label class="col-xs-3 control-label label-xs required">Usuario:</label>
                                                        <div class="col-xs-5" ><input id="Usu_Ced" name="Usu_Ced" type="text" class="form-control input-xs" required="" /></div>
                                                    </div>

                                                    <!-- Clave -->
                                                    <div class="form-group">
                                                        <label class="col-xs-3 control-label label-xs required">Clave:</label>
                                                        <div class="col-xs-5" >
                                                            <div class="input-group input-group-xs">
                                                                <input id="Usu_Pal" name="Usu_Pal" type="password" class="form-control input-xs" required="" />
                                                                <span class="input-group-addon validate" ><i></i></span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Confirmar Clave -->
                                                    <div class="form-group">
                                                        <label class="col-xs-3 control-label label-xs required">Confirmar clave:</label>
                                                        <div class="col-xs-5" >
                                                            <div class="input-group input-group-xs">
                                                                <input id="Usu_Pal_C" name="Usu_Pal_C" type="password" class="form-control input-xs" required="" />
                                                                <span class="input-group-addon validate" ><i></i></span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Asignacion de Sucursal -->
                                                    <div class="form-group">
                                                        <label class="col-xs-3 control-label label-sm required">Sucursal:</label>
                                                        <div class="col-xs-5" >
                                                            <select multiple id="Suc_Cod" name="Suc_Cod" class="form-control input-sm" data-placeholder="Sucursales a Asignar" required="">
                                                                <?php foreach ($sucursales as $s) echo "<option value={$s['Suc_Cod']}>{$s['Suc_Des']}</option>"; ?>
                                                            </select>
                                                        </div>
                                                    </div>


                                                    <!-- Perfil por Empresa -->
                                                    <div class="form-group">
                                                        <label class="col-xs-3 control-label label-sm required">Perfil:</label>
                                                        <div class="col-xs-5" >
                                                            <select multiple id="Per_Cod" name="Per_Cod" class="form-control input-sm" data-placeholder="Elija los perfiles" required="">
                                                                <?php foreach ($perfil as $p) echo "<option value={$p['Per_Cod']}>{$p['Per_Des']}</option>"; ?>
                                                            </select>
                                                        </div>
                                                    </div>

                                                </fieldset>

                                                <!-- VENDEDOR -->
                                                <fieldset class="exa-fieldset">
                                                    <legend class="Titulos2">Datos Vendedor <input id="chkVen" type="checkbox"></legend>

                                                    <!-- Punto de Impresion -->
                                                    <div class="form-group">
                                                        <label class="col-xs-3 control-label label-xs required">Punto de Impresi&oacute;n:</label>
                                                        <div class="col-xs-5">
                                                            <div class="input-group input-group-xs" >
                                                                <input id="Pun_Des" name="Pun_Des" type="text" class="form-control input-xs" onchange="validatePunto()" disabled="" />
                                                                <span class="input-group-addon validate" ><i></i></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </fieldset> 

                                                <!-- MANIFIESTO -->
                                                <fieldset id="fieldsetManifiesto" class="exa-fieldset">
                                                    <legend class="Titulos2">Datos del Manifiesto <input id="chkVen" type="checkbox"></legend>

                                                    <!-- Hidden fields -->
                                                    <!-- <input name="bandera_prov" id="bandera_prov" type="hidden" value="nosel" />
                                                    <input name="Prs_Cod" id="Prs_Cod" type="hidden" />
                                                    <input name="Cli_Cod" id="Cli_Cod" type="hidden" />
                                                    <input name="Usu_Cod" id="Usu_Cod" type="hidden" />
                                                    <input name="save_bnd" id="save_bnd" type="hidden" value="n" /> -->

                                                    <!-- Cédula/RUC -->
                                                    <!-- <div class="form-group" style="margin-top: 10px;">
                                                        <label class="col-sm-2 control-label label-sm" >C&eacute;dula/RUC:</label>
                                                        <div class="col-sm-10">
                                                            <div id="CedCliMan" class="input-group input-group-sm" style="width: 180px;" disabled>
                                                                <input name="Prs_Ced_Aux" id="Prs_Ced_Aux" type="text" placeholder="C.I/RUC del Cliente..." class="form-control input-sm" tabindex="1" required readonly />
                                                            </div>
                                                        </div>
                                                    </div> -->

                                                    <!-- Cliente -->
                                                    <!-- <div class="form-group">
                                                        <label class="col-sm-2 control-label label-sm">Cliente:</label>
                                                        <div class="col-sm-10">
                                                            <div class="input-group input-group-sm" style="width: 360px;">
                                                                <input name="nombre" id="nombre" class="form-control input-sm databind datatitle" placeholder="Nombre del Cliente..." readonly />
                                                                <span class="input-group-btn">
                                                                    <button type="button" id="btnBusCLi" placeholder="Buscar Cliente..." onclick="$('#clientesDialog').dialog('open');" class="btn btn-success btn-sm" title="Buscar Cliente" tabindex="2" style="display: none;">
                                                                        <span class="glyphicon glyphicon-search"></span>
                                                                    </button>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div> -->

                                                    <!-- Código de Planta debajo de Cédula y Cliente -->
                                                    <!-- <div class="form-group">
                                                        <label class="col-sm-2 control-label label-sm ">Código de Planta:</label>
                                                        <div class="col-sm-6">
                                                            <select id="Pla_Cod" name="Pla_Cod" class="form-control input-xs" style="height: 30px; margin-top: 5px;" data-placeholder="Seleccione una planta" required="">
                                                                <option value=""></option>
                                                            </select>
                                                        </div>
                                                    </div> -->

                                                    <!-- Punto de Impresion -->
                                                    <div class="form-group" style="margin-top: 10px;">
                                                        <label class="col-sm-2 control-label label-sm" >C&eacute;dula/RUC:</label><!-- required -->
                                                        <div class="col-sm-10" style="display:flex; align-items:center; gap: 10px;">
                                                            <!-- Hidden fields -->
                                                            <input name="bandera_prov" id="bandera_prov" type="hidden" value="nosel" />
                                                            <input name="Prs_Cod" id="Prs_Cod" type="hidden" />
                                                            <input name="Cli_Cod" id="Cli_Cod" type="hidden" />
                                                            <input name="Usu_Cod" id="Usu_Cod" type="hidden" />
                                                            <input name="save_bnd" id="save_bnd" type="hidden" value="n" />

                                                            <div id="CedCliMan" class="input-group input-group-sm" style="width: 220px;" disabled>
                                                                <input name="Prs_Ced_Aux" id="Prs_Ced_Aux" type="text" style="margin-left: -20px;" placeholder="C.I/RUC del Cliente..." class="form-control input-sm" tabindex="1" required readonly />
                                                            </div>

                                                            <label class="control-label label-sm" style="margin-top: -4px;">Cliente:</label>
                                                            <div class="input-group input-group-sm" style="width: 360px;">
                                                                <input name="nombre" id="nombre" class="form-control input-sm databind datatitle" readonly />
                                                                <span class="input-group-btn">
                                                                    <button type="button" id="btnBusCLi" placeholder="Ingrese nombre del Cliente..." onclick="$('#clientesDialog').dialog('open');" class="btn btn-success btn-sm" title="Buscar Cliente" tabindex="2" style="display: none;">
                                                                        <span class="glyphicon glyphicon-search"></span>
                                                                    </button>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Código de Planta debajo de Cédula y Cliente -->
                                                    <div class="form-group">
                                                        <label class="col-sm-2 control-label label-sm ">Código de Planta:</label>
                                                        <div class="col-sm-6">
                                                            <select id="Pla_Cod" name="Pla_Cod" class="form-control input-xs" style="height: 30px; margin-top: 5px; margin-left: -20px;" data-placeholder="Seleccione una planta" required="">
                                                                <option value=""></option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </fieldset>

                                                <div class="center">
                                                    <button type="button" class="btn btn-sm btn-primary no" id="btnGuardar"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                                                </div>
                                                <div class="form-group Titulos2">

                                                        <div class="col-sm-12"><hr/><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco ( <span class="required"></span> ) son campos obligatorios.<hr/></div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- MODIFICAR TAB !-->
                                <div id="tabs-2" class="ui-tabs-panel ui-widget-content ui-corner-bottom" >
                                    <!--<div class="row">-->
                                        <form id="frm_bus" name="frm_bus" class="form-horizontal normal" action="javascript:">
                                            <fieldset class="exa-fieldset">
                                                <legend class="Titulos2">B&uacute;squeda de usuarios</legend>
                                                <div class="form-group">
                                                    <label class="col-sm-2 control-label label-xs">Filtrar por:</label>
                                                    <div class="col-sm-5 radioset">
                                                        <input id="rad_ba1" name="op_opciones" type="radio" value="c" checked="" onclick="setfocus(this.form.search)"/><label for="rad_ba1">&nbsp;&nbsp;C&eacute;dula/R.U.C.&nbsp;&nbsp;</label>
                                                        <input id="rad_ba2" name="op_opciones" type="radio" value="d" onclick="setfocus(this.form.search)"/><label for="rad_ba2">&nbsp;&nbsp;Apellido&nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-sm-2 control-label label-xs">B&uacute;squeda:</label>
                                                    <div class="col-sm-5">
                                                        <div class="input-group">
                                                            <input type="text" id="search" name="search" class="form-control input-xs" placeholder="Ingrese &iacute;ndice de b&uacute;squeda" autofocus="">
                                                            <span class="input-group-btn">
                                                                <button id="btnSearch" onclick="" class="btn btn-success btn-xs" type="button" title="Buscar cliente"><span class="glyphicon glyphicon-search"></span> Buscar</button>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </fieldset>
                                        </form>
                                        <div style="min-height: 350px;">
                                            <table id="tableResult"></table>
                                            <div id="tableResultPager"></div>
                                        </div>
                                    <!--</div>-->
                                </div>

                        <!-- Inicio del diálogo para buscar Clientes -->
                        <div id="clientesDialog" title="B&uacute;squeda de Clientes">
                            <form id="clienForm" class="form-horizontal normal"></form>
                        </div>

                        <!-- Inicio del diálogo para buscar Clientes - Modificar-->
                        <div id="CliDialog" title="B&uacute;squeda de Clientes">
                            <form id="clienForm1" class="form-horizontal normal"></form>
                        </div>

                        <!-- dialogo de registro de pagos de anticipo -->
                        <div id="pagosDialog" title="Agregar Pagos" style="display: none;">
                            <form id="pagosForm" class="form-horizontal normal">
                                <div class="row">
                                    <div class="col-sm-12">
                                        <fieldset class="exa-fieldset">
                                            <legend class="Titulos2">Datos Empresa/Cliente</legend>
                                            <div class="form-group">
                                                <label class="col-sm-2 control-label label-sm" >C&eacute;dula/RUC:</label><!-- required -->
                                                <div class="col-sm-10" style="display:flex; align-items:center; gap: 10px;">
                                                    <!-- Hidden fields -->
                                                    <input name="bandera_prov" id="bandera_prov" type="hidden" value="nosel" />
                                                    <input name="Prs_Cod" id="Prs_Cod" type="hidden" />
                                                    <input name="Cli_Cod" id="Cli_Cod" type="hidden" />
                                                    <input name="Usu_Cod" id="Usu_Cod" type="hidden" />
                                                    <input name="save_bnd" id="save_bnd" type="hidden" value="n" />
                                                    <input name="Ant_Val" id="Ant_Val" type="hidden" value="0.00" />
                                                    <input name="Ama_Cod" id="Ama_Cod" type="hidden" value="" />

                                                    <div class="input-group input-group-sm" style="width: 180px;">
                                                        <input name="Prs_Ced" id="Prs_Ced" type="text" style="margin-left: -20px;" placeholder="C.I/RUC del Cliente..." class="form-control input-sm" tabindex="1" required readonly />
                                                    </div>

                                                    <label class="control-label label-sm" style="margin-top: -4px;">Cliente:</label>
                                                    <div class="input-group input-group-sm" style="width: 360px;">
                                                        <input name="nombre" id="nombre" class="form-control input-sm databind datatitle" placeholder="Nombre del Cliente..."  readonly />
                                                        <span class="input-group-btn">
                                                            <button type="button" id="btnBusCLi" placeholder="Buscar Cliente..." onclick="$('#clientesDialog').dialog('open');" class="btn btn-success btn-sm" title="Buscar Cliente" tabindex="2" style="display: none;">
                                                                <span class="glyphicon glyphicon-search"></span>
                                                            </button>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </fieldset>
                                    </div>
                                </div>

                                <fieldset class="exa-fieldset" id="detPagos">
                                    <legend class="Titulos2">Datos del Pago</legend>

                                    <div class="form-group Transferencia Deposito">
                                        <label class="col-xs-3 control-label label-xs required">Fecha:</label>
                                        <div class="col-xs-3" style="width: 150px;">
                                            <input id="Ama_Fec" name="Ama_Fec" type="text" size="10" class="form-control input-xs datepicker" required="" style="text-align: center"/>
                                        </div>
                                    <!-- </div>

                                    <div class="form-group"> -->
                                        <label class="col-xs-2 control-label label-xs required">Tipo:</label>
                                        <div class="col-xs-3" style="width: 150px; margin-left: -12px;">
                                            <select id="Pag_Cod" name="Ama_Tde" class="form-control input-xs readOnly" style="text-align: center" onchange="cambiarCamposPagos($(this).find(':selected').data().class, $('#Pag_Cod option:selected').attr('data-abr'))"
                                                required="">
                                                <?php $rows_tipo_pago = $obBD_con1->getArrayConsulta(2, "", $obBD_conexion);
                                                if (count($rows_tipo_pago) > 0) {
                                                    foreach ($rows_tipo_pago as $row) {
                                                        echo "<option value='$row[Pag_Cod]' data-abr='$row[Pag_Abr]' data-class='$row[Pag_Des]' >$row[Pag_Des]</option>";
                                                    }
                                                } ?>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Bancos de DataBase -->
                                    <div class="form-group Transferencia Deposito">
                                        <label class="col-xs-3 control-label label-xs required">Acreditar a:</label>
                                        <div class="col-xs-8">
                                            <select id="Ban_Cod" name="Ban_Cod" class="form-control input-xs readOnly" required="">
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group Transferencia">
                                        <label class="col-xs-3 control-label label-xs required">Banco Origen:</label>
                                        <div class="col-xs-8">
                                            <select id="Bak_Cod" name="Bak_Cod" class="form-control input-xs readOnly" required="">
                                                <?php $rows_bankos = $obBD_con1->getArrayConsulta(9, "", $obBD_conexion);
                                                if (count($rows_bankos) > 0) {
                                                    foreach ($rows_bankos as $row) {
                                                        echo "<option value='$row[Bak_Cod]'>$row[Bak_Des]</option>";
                                                    }
                                                } ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group Deposito">
                                        <label class="col-xs-3 control-label label-xs">Observaci&oacute;n:</label>
                                        <div class="col-xs-8">
                                            <textarea id="Ama_Obs" name="Ama_Obs" class="form-control input-xs"></textarea>
                                        </div>
                                    </div>

                                    <div class="form-group" style="display: none;">
                                        <label class="col-xs-3 control-label label-xs required">Estado</label>

                                    </div>

                                    <div class="form-group Transferencia Deposito">
                                        <label class="col-xs-3 control-label label-xs" id="doc">No. Docum.:</label>
                                        <div class="col-xs-4">
                                            <input type="text" id="Ama_Doc" name="Ama_Doc" onchange="" class="form-control input-xs" onchange="soloNumeros($(this));">
                                        </div>
                                    <!-- </div>

                                    <div class="form-group Transferencia Deposito"> -->
                                        <label class="col-xs-1 control-label label-sm required" style="margin-top: -3px;">Valor:</label>
                                        <div class="col-xs-3">
                                            <div class="input-group input-group-xs">
                                                <span class="input-group-addon">
                                                    <i id="indicadorChe" class="glyphicon glyphicon-usd"></i>
                                                </span>
                                                <input name="Ama_Val" type="text" id="Pac_Val" size="10" class="form-control input-xs" required="" autocomplete="off" onchange="cambioValPago($(this));"
                                                    onkeypress="return  validar_decimal(event)" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group center Trasferencia Deposito">
                                        </br>
                                        <a class="btn btn-sm btn-primary" onclick="AgregarPago()">
                                            <i class="glyphicon glyphicon-floppy-disk"></i> Agregar
                                        </a>
                                    </div>
                                </fieldset>
                            </form>
                        </div>

                        </div>

                        <!-- MODAL MODIFICAR-->
                        <div id="editDialog" title="Editar Usuario" style="display: none;">
                            <form id ="formDialog" name="formDialog" class="form-horizontal" autocomplete="off">
                                <div class="form-group Titulos2">
                                    <div class="col-sm-12"><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco ( <span class="required"></span>) son campos obligatorios.<hr/></div>
                                </div>
                                <div class="col-xs-10 col-xs-offset-1">
                                    <fieldset>

                                        <!-- Prs_Cod-->
                                        <div>
                                            <input type="text" id="Prs_Cod" name="Prs_Cod" hidden="true">
                                        </div>

                                        <!-- Cod Usu_Cod-->
                                        <div>
                                            <input type="text" id="Usu_Cod" name="Usu_Cod" hidden="true">
                                        </div>

                                        <!-- Cod Pun_Cod-->
                                        <div>
                                            <input type="text" id="Pun_Cod" name="Pun_Cod" hidden="true">
                                        </div>

                                        <!-- Cedula -->
                                        <div class="form-group">
                                            <label class="control-label col-xs-4 label-xs required">C&eacute;dula:</label>
                                            <div class="col-xs-8" >
                                                <input id="Prs_Ced_m" name="Prs_Ced_m" class="form-control input-xs readOnly" readOnly=""> </input>
                                            </div>
                                        </div>

                                        <!-- Usuario -->
                                        <div class="form-group">
                                            <label class="col-xs-4 control-label label-xs required">Usuario:</label>
                                            <div class="col-xs-8" >
                                                <input id="persona" name="persona" class="form-control input-xs readOnly" readOnly=""> </input>
                                            </div>
                                        </div>

                                        <!-- Clave -->
                                        <div class="form-group">
                                            <label class="col-xs-4 control-label label-xs">Clave:</label>
                                            <div class="col-xs-8" >
                                                <div class="input-group input-group-xs">
                                                    <input id="Usu_Pal_m" name="Usu_Pal_m" type="password" class="form-control input-xs" />
                                                    <span class="input-group-addon validate" ><i></i></span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Confirmar Clave -->
                                        <div class="form-group">
                                            <label class="col-xs-4 control-label label-xs">Confirmar&nbsp;Clave:</label>
                                            <div class="col-xs-8" >
                                                <div class="input-group input-group-xs">
                                                    <input id="Usu_Pal_Cm" name="Usu_Pal_Cm" type="password" class="form-control input-xs" />
                                                    <span class="input-group-addon validate" ><i></i></span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Asignacion de Sucursal -->
                                        <div class="form-group">
                                            <label class="col-xs-4 control-label label-xs required">Sucursal:</label>
                                            <div class="col-xs-8" >
                                                <select multiple id="Suc_Cod_m" name="Suc_Cod_m" class="form-control input-sm" data-placeholder="Sucursales a asignar" required="">
                                                    <?php foreach ($sucursales as $s) echo "<option value={$s['Suc_Cod']}>{$s['Suc_Des']}</option>"; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-xs-4 control-label label-xs">Perfil:</label>
                                            <div class="col-xs-8" >
                                                <select multiple id="Per_Cod_m" name="Per_Cod_m" class="form-control input-sm" data-placeholder="Perfiles ...">
                                                    <?php foreach ($perfil as $p) echo "<option value={$p['Per_Cod']}>{$p['Per_Des']}</option>"; ?>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- PTO. IMPRESION -->
                                        <div class="form-group">
                                            <label class="col-xs-4 control-label label-xs">Pto. Impresi&oacute;n:</label>
                                            <div class="col-xs-8" ><input id="Pun_Des_m" name="Pun_Des_m" type="text" class="form-control input-xs" required="" /></div>
                                        </div>

                                        <div class="form-group" style="margin-top: 10px;">
                                            <fieldset class="exa-fieldset" id="fieldsetModCli">
                                                <legend class="Titulos2">Datos Empresa/Cliente</legend>
                                                
                                                <!-- Hidden fields -->
                                                <input name="bandera_prov1" id="bandera_prov1" type="hidden" value="nosel" />
                                                <input name="Prs_Cod_Aux" id="Prs_Cod_Aux" type="hidden" />
                                                <input name="Cli_Cod_Aux" id="Cli_Cod_Aux" type="hidden" />
                                                <input name="Usu_Cod_Aux" id="Usu_Cod_Aux" type="hidden" />
                                                <input name="save_bnd_Aux" id="save_bnd_Aux" type="hidden" value="n" />
                                                <input name="Ant_Val_Aux" id="Ant_Val_Aux" type="hidden" value="0.00" />
                                                <input name="Ama_Cod_Aux" id="Ama_Cod_Aux" type="hidden" value="" />

                                                <!-- Cédula/RUC -->
                                                <div class="form-group">
                                                    <label class="col-sm-2 control-label label-sm" >C.I/Ruc:</label>
                                                    <div class="col-sm-10">
                                                        <div class="input-group input-group-sm" style="width: 180px;">
                                                            <input name="Prs_Ced_Aux" id="Prs_Ced_Aux" type="text" placeholder="C.I/RUC del Cliente..." class="form-control input-sm" tabindex="1" required readonly />
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Cliente -->
                                                <div class="form-group">
                                                    <label class="col-sm-2 control-label label-sm">Cliente:</label>
                                                    <div class="col-sm-10">
                                                        <div class="input-group input-group-sm" style="width: 320px;">
                                                            <input name="CliNom" id="CliNom" placeholder="Nombre del Cliente..." class="form-control input-sm databind datatitle" readonly />
                                                            <span class="input-group-btn">
                                                                <button type="button" id="btnBusCLi1" onclick="$('#CliDialog').dialog('open');" class="btn btn-success btn-sm" title="Buscar Cliente" tabindex="2">
                                                                    <span class="glyphicon glyphicon-transfer"></span>
                                                                </button>
                                                            </span>
                                                            <span class="input-group-btn">
                                                                <button type="button" id="btnEliminar" onclick="limpiarCamposCliente()" class="btn btn-danger btn-sm" title="Eliminar Cliente" tabindex="2">
                                                                    <span class="glyphicon glyphicon-trash"></span>
                                                                </button>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Código de Planta debajo de Cédula y Cliente -->
                                                <div class="form-group" style="margin-top: -20px;">
                                                    <label class="col-sm-3 control-label label-sm " style="margin-left: -20px;">Código de Planta:</label>
                                                    <div class="col-sm-6">
                                                        <select id="Pla_Cod_m" name="Pla_Cod_m" class="form-control input-xs" style="height: 30px; margin-top: 5px; margin-left: -15px;" data-placeholder="Seleccione una planta" required="">
                                                            <option value=""></option>
                                                        </select>
                                                    </div>
                                                </div>

                                            </fieldset>
                                        </div>

                                        </br>
                                        <!-- Buttons -->
                                        <div class="form-group">
                                            <label class="col-md-4 control-label" for="btnModificar"></label>
                                            <div class="col-md-8">
                                                <button type="button" id="btnModificar" name="btnModificar" class="btn btn-sm btn-primary"><i class="glyphicon glyphicon-floppy-disk"></i>     Modificar</button>
                                            </div>
                                        </div>

                                    </fieldset>
                                </div>
                            </form>
                        </div>
                        <!-- END MODAL MODIFICAR-->
                    </div>
                </div>
            </div>
        </div>

        <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
        <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
        <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
        <script type="text/javascript" src="../../framework/jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
        <link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.min.css" />
        <script type="text/javascript" src="../../framework/jquery/bootstrap/popover/jquery.flyout.min.js"></script>
        <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
        <link type="text/css" rel="stylesheet" href="../../mascaras/model1/estilos/print.css" media="print" />

    </BODY>
</HTML>

