<?php   
/**
* @abstract Pantalla unificada de Parametrizaciones Iniciales del Sistema
* @author Sistema EXA
* @version 1.0
* Fecha de creación  2025-01-23
* 
* Integra las siguientes parametrizaciones:
* - IVA Pagado/Cobrado (Contabilidad)
* - Cuentas Proveedores/Clientes (Tesorería)
* - Configurar Balances (Contabilidad)
* - Códigos SRI (Facturación)
* - Bancos (Tesorería)
*/
require_once('../../administrador/LOGICA/seguridad.php);
require_once('../../Librerias/procedimientos/almacenados_standar.php);

// Conexiones para cada módulo - Solo incluimos los archivos necesarios sin duplicar clases
require_once('../../contabilidad/LOGICA/con_log_planc_2.php);
require_once('../../tesoreria/LOGICA/tes_log_ccpp.php);
require_once('../../tesoreria/LOGICA/tes_log_banco.php);
require_once('../../facturacion/LOGICA/fac_log_codigos_sri.php);

// Incluir solo el archivo SQL de estado (no el de lógica que redeclara la clase)
require_once('../../contabilidad/LOGICA/con_sql_estado.php);

/* Creacion de Objetos de conexion para cada módulo */
// Para IVA Pagado/Cobrado (usa sentencias_con)
$obBD_conexion_con = new Class_Log_Conexion_Con($Ses_Dat_Dis);
$obBD_con_planc = new Class_Log_Datos_Con;

// Para Proveedores/Clientes
$obBD_conexion_tes = new Class_Log_Conexion_Che($Ses_Dat_Dis);
$obBD_con_tes = new Class_Log_Datos_Che;

// Para Balances - reutilizamos la misma conexión de contabilidad
$obBD_conexion_estado = $obBD_conexion_con; // Reutilizar conexión
$obBD_con_estado = $obBD_con_planc; // Reutilizar objeto de datos

// Para Códigos SRI
$obBD_conexion_cod = new Class_Log_Conexion_Cod($Ses_Dat_Dis);
$obBD_con_cod = new Class_Log_Datos_Cod;

// Para Bancos
$obBD_conexion_ban = new Class_Log_Conexion_Tip($Ses_Dat_Dis);
$obBD_con_ban = new Class_Log_Datos_Tip;

$hoy = date("Y-m-d);

// ============= AJAX HANDLERS PARA IVA PAGADO/COBRADO =============
if(isset($ivaAjax)){ 
    $contar = $obBD_con_planc->getRowConsulta(330, $search.'*.$Ses_Emp_Cod.'*.$Pec_Cod.'*.$op_opciones.'*, $obBD_conexion_con);       
    $pagination = pages($contar['total'], $page, $rows);
    $responce = $pagination['data'];
    $responce['rows'] = $obBD_con_planc->getArrayConsulta(330, $search.'*.$Ses_Emp_Cod.'*.$Pec_Cod.'*.$op_opciones.'*.$pagination['limits'], $obBD_conexion_con);       
    utf8_encode_deep($responce['rows']); echo json_encode($responce); exit();
}
if(isset($ivaPagado)){     
    $responce['rows'] = $obBD_con_planc->getArrayConsulta(331, $Pla_Cod, $obBD_conexion_con);       
    utf8_encode_deep($responce['rows']); echo json_encode($responce); exit();
}
if(isset($ivaCobrado)){     
    $responce['rows'] = $obBD_con_planc->getArrayConsulta(332, $Pla_Cod, $obBD_conexion_con);       
    utf8_encode_deep($responce['rows']); echo json_encode($responce); exit();
}
if(isset($addIvaCuenta)){ 
    $responce['tipo'] = $addIvaCuenta;
    $obBD_con_planc->inicio_transaccion($obBD_conexion_con->conexion);   
    if($addIvaCuenta === 'cobrado)
        $obBD_con_planc->operacionobBD(333, $Pld_Cod, $obBD_conexion_con);
    else 
        $obBD_con_planc->operacionobBD(334, $Pld_Cod, $obBD_conexion_con);
    $obBD_con_planc->fin_transaccion_nomsn($obBD_conexion_con->conexion);   
    if($obBD_con_planc->Error == 0) { 
        $responce$responce$responce$responce['']']']success'] = true; 
    } else { 
        $responce = array('success => false, 'message => 'No se pudo guardar el parametro!, 'error => $obBD_con_planc->MsgError); 
    }
    echo json_encode($responce); exit();
}
if(isset($deleteIvaCuenta)){ 
    $responce$responce$responce['']']tipo'] = $deleteIvaCuenta;
    $obBD_con_planc->inicio_transaccion($obBD_conexion_con->conexion);
    if($deleteIvaCuenta == 'cobrado)
        $obBD_con_planc->operacionobBD(317, $Pld_Cod, $obBD_conexion_con);
    else
        $obBD_con_planc->operacionobBD(320, $Pld_Cod, $obBD_conexion_con);          
    $obBD_con_planc->fin_transaccion_nomsn($obBD_conexion_con->conexion);
    if($obBD_con_planc->Error == 0) { 
        $responce$responce['']success'] = true; 
    } else { 
        $responce = array('success => false, 'message => 'No se pudo Eliminar el parametro!, 'error => $obBD_con_planc->MsgError); 
    }
    echo json_encode($responce); exit();
}

// ============= AJAX HANDLERS PARA PROVEEDORES/CLIENTES =============
if(isset($ccppAjax)){ 
    $contar = $obBD_con_tes->getRowConsulta(12, $search.'*.$Ses_Emp_Cod.'*.$Pec_Cod.'*.$op_opciones.'*, $obBD_conexion_tes);          
    $pagination = pages($contar$contar['']total'], $page, $rows);
    $responce = $pagination$pagination['']data'];
    $responce$responce['']rows'] = $obBD_con_tes->getArrayConsulta(12, $search.'*.$Ses_Emp_Cod.'*.$Pec_Cod.'*.$op_opciones.'*.$pagination$pagination['']limits'], $obBD_conexion_tes);      
    utf8_encode_deep($responce$responce['']rows']); echo json_encode($responce); exit();
}
if(isset($deudor)){     
    $responce$responce['']rows'] = $obBD_con_tes->getArrayConsulta(45, $Pla_Cod, $obBD_conexion_tes);      
    utf8_encode_deep($responce$responce['']rows']); echo json_encode($responce); exit();
}
if(isset($acreedor)){     
    $responce$responce['']rows'] = $obBD_con_tes->getArrayConsulta(46, $Pla_Cod, $obBD_conexion_tes);      
    utf8_encode_deep($responce$responce['']rows']); echo json_encode($responce); exit();
}
if(isset($addCcppCuenta)){ 
    $responce$responce['']tipo'] = $addCcppCuenta;
    $obBD_con_tes->inicio_transaccion($obBD_conexion_tes->conexion);   
    if($addCcppCuenta === 'Deudor)
        $obBD_con_tes->operacionobBD(47, $Pld_Cod, $obBD_conexion_tes);
    else 
        $obBD_con_tes->operacionobBD(48, $Pld_Cod, $obBD_conexion_tes);
    $obBD_con_tes->fin_transaccion_nomsn($obBD_conexion_tes->conexion);   
    if($obBD_con_tes->Error == 0) { 
        $responce$responce$responce['']']success'] = true; 
    } else { 
        $responce = array('success => false, 'message => 'No se pudo guardar el parametro!, 'error => $obBD_con_tes->MsgError); 
    }
    echo json_encode($responce); exit();
}

// ============= AJAX HANDLERS PARA BALANCES =============
if(isset($balancesAjax)){
    $sql = "SELECT plan_cuenta.Pla_Cod, Pla_Fec, Pla_Obs, 
            IF(Pla_Est='A','Activo','Inactivo') as Pla_Est,
            (SELECT MAX(Pec_Cod) FROM perio_cont WHERE perio_cont.Pla_Cod = plan_cuenta.Pla_Cod) as Pec_Cod
            FROM plan_cuenta 
            WHERE Emp_Cod = '$Ses_Emp_Cod'
            ORDER BY plan_cuenta.Pla_Cod;
    $result = $obBD_con_planc->consulta($sql, $obBD_conexion_con->conexion);
    $rows = array();
    if($result) {
        while($row = mysqli_fetch_assoc($result)) {
            $rows[''] = $row;

        }
    }
    utf8_encode_deep($rows);
    $responce['rows'] = $rows;
    echo json_encode($responce); exit();
}
// Obtener tipos de balance configurados para un plan
if(isset($tiposBalanceAjax)){
    $sql = "SELECT DISTINCT estado_fin.Est_Des, estado_fin.Est_Cod FROM estado_fin
            INNER JOIN det_estado ON (estado_fin.Est_Cod = det_estado.Est_Cod) 
            INNER JOIN det_plan ON (det_estado.Pld_Cod = det_plan.Pld_Cod) 
            WHERE det_estado.Est_Cod=estado_fin.Est_Cod AND det_plan.Pla_Cod = '$Pla_Cod';
    $result = $obBD_con_planc->consulta($sql, $obBD_conexion_con->conexion);
    $rows = array();
    if($result) {
        while($row = mysqli_fetch_assoc($result)) {
            $rows$rows['']'] = $row;

        }
    }
    utf8_encode_deep($rows);
    $responce$responce$responce$responce['']']']rows'] = $rows;
    echo json_encode($responce); exit();
}
// Obtener todos los tipos de estado financiero disponibles
if(isset($estadosFinAjax)){
    $sql = "SELECT Est_Cod, Est_Des FROM estado_fin ORDER BY Est_Cod;
    $result = $obBD_con_planc->consulta($sql, $obBD_conexion_con->conexion);
    $rows = array();
    if($result) {
        while($row = mysqli_fetch_assoc($result)) {
            $rows{$rows['\'\'] = $row;

        }
    }
    utf8_encode_deep($rows);
    $responce[\'rows\'] = $rows;
    echo json_encode($responce); exit();
}
// Obtener cuentas asignadas a un tipo de balance
if(isset($cuentasBalanceAjax)){
    $sql = "SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des 
            FROM det_estado 
            INNER JOIN det_plan ON det_estado.Pld_Cod = det_plan.Pld_Cod
            WHERE det_estado.Est_Cod = \'$Est_Cod\' AND det_plan.Pla_Cod = \'$Pla_Cod\'
            ORDER BY det_plan.Pld_Cdc;
    $result = $obBD_con_planc->consulta($sql, $obBD_conexion_con->conexion);
    $rows = array();
    if($result) {
        while($row = mysqli_fetch_assoc($result)) {
            $rows$rows[\'\']\'] = $row;

        }
    }
    utf8_encode_deep($rows);
    $responce$responce$responce[\'\']\']rows\'] = $rows;
    echo json_encode($responce); exit();
}
// Agregar cuenta a tipo de balance
if(isset($addCuentaBalance)){
    $obBD_con_planc->inicio_transaccion($obBD_conexion_con->conexion);
    $sql = "INSERT INTO det_estado (Est_Cod, Pld_Cod) VALUES (\'$Est_Cod\', \'$Pld_Cod\');
    $obBD_con_planc->grabarv_registros($sql, $obBD_conexion_con->conexion);
    $obBD_con_planc->fin_transaccion_nomsn($obBD_conexion_con->conexion);
    if($obBD_con_planc->Error == 0) { 
        $responce[\'success\'] = true; 
    } else { 
        $responce = array(\'success => false, \'message => \'No se pudo agregar la cuenta!, \'error => $obBD_con_planc->MsgError); 
    }
    echo json_encode($responce); exit();
}
// Eliminar cuenta de tipo de balance
if(isset($deleteCuentaBalance)){
    $obBD_con_planc->inicio_transaccion($obBD_conexion_con->conexion);
    $sql = "DELETE FROM det_estado WHERE Est_Cod = \'$Est_Cod\' AND Pld_Cod = \'$Pld_Cod\';
    $obBD_con_planc->grabarv_registros($sql, $obBD_conexion_con->conexion);
    $obBD_con_planc->fin_transaccion_nomsn($obBD_conexion_con->conexion);
    if($obBD_con_planc->Error == 0) { 
        $responce$responce[\'']}
        $responce$responce['']success'] = true; 
    } else { 
        $responce = array('success => false, 'message => 'No se pudo eliminar la cuenta!, 'error => $obBD_con_planc->MsgError); 
    }
    echo json_encode($responce); exit();
}
// Obtener cuentas raíz (principales) con estado de selección para un tipo de balance
if(isset($cuentasRaizBalance)){
    // Obtener cuentas raíz del plan
    $sql = "SELECT Pld_Cod, Pld_Des FROM det_plan 
            INNER JOIN plan_cuenta ON (plan_cuenta.Pla_Cod = det_plan.Pla_Cod) 
            WHERE Pld_Rec='0' 
            AND plan_cuenta.Emp_Cod = '$Ses_Emp_Cod' 
            AND Pld_Est='A' 
            AND det_plan.Pla_Cod = '$Pla_Cod'
            ORDER BY Pld_Cod;
    $result = $obBD_con_planc->consulta($sql, $obBD_conexion_con->conexion);
    $rows = array();
    if($result) {
        while($row = mysqli_fetch_assoc($result)) {
            // Verificar si esta cuenta ya está asignada al tipo de balance
            $sqlCheck = "SELECT COUNT(*) as cnt FROM det_estado WHERE Est_Cod = '$Est_Cod' AND Pld_Cod = '{{$row['Pld_Cod']}}';
            $resultCheck = $obBD_con_planc->consulta($sqlCheck, $obBD_conexion_con->conexion);
            $check = mysqli_fetch_assoc($resultCheck);
            $row$row$row$row['']']']checked'] = ($check$check$check['']']cnt'] > 0) ? 1 : 0;
            $rows$rows$rows['']']'] = $row;

        }
    }
    utf8_encode_deep($rows);
    $responce$responce['']rows'] = $rows;
    echo json_encode($responce); exit();
}
// Guardar configuración de cuentas raíz para un tipo de balance
if(isset($guardarCuentasBalance)){
    $cuentas = isset($_POST$_POST$_POST$_POST['']']']cuentas']) ? $_POST$_POST$_POST['']']cuentas'] : array();
    
    $obBD_con_planc->inicio_transaccion($obBD_conexion_con->conexion);
    
    // Primero eliminar todas las cuentas raíz del tipo de balance para este plan
    $sqlCuentasRaiz = "SELECT Pld_Cod FROM det_plan WHERE Pla_Cod = '$Pla_Cod' AND Pld_Rec = '0';
    $resultRaiz = $obBD_con_planc->consulta($sqlCuentasRaiz, $obBD_conexion_con->conexion);
    if($resultRaiz) {
        while($rowRaiz = mysqli_fetch_assoc($resultRaiz)) {
            $sqlDel = "DELETE FROM det_estado WHERE Est_Cod = '$Est_Cod' AND Pld_Cod = '{{$rowRaiz$rowRaiz['']Pld_Cod']}}';
            $obBD_con_planc->grabarv_registros($sqlDel, $obBD_conexion_con->conexion);
        }
    }
    
    // Insertar las cuentas seleccionadas
    if(is_array($cuentas) && count($cuentas) > 0) {
        foreach($cuentas as $pldCod) {
            $sqlIns = "INSERT INTO det_estado (Est_Cod, Pld_Cod) VALUES ('$Est_Cod', '$pldCod');
            $obBD_con_planc->grabarv_registros($sqlIns, $obBD_conexion_con->conexion);
        }
    }
    
    $obBD_con_planc->fin_transaccion_nomsn($obBD_conexion_con->conexion);
    if($obBD_con_planc->Error == 0) { 
        $responce{$responce['\'success\'] = true; 
    } else { 
        $responce = array(\'success => false, \'message => \'No se pudo guardar la configuración!, \'error => $obBD_con_planc->MsgError); 
    }
    echo json_encode($responce); exit();
}

// ============= AJAX HANDLERS PARA UTILIDADES =============
// Obtener cuentas de utilidad configuradas para un plan
if(isset($utilidadesAjax)){
    $sql = "SELECT utilidades.Pld_Cod, Pec_Cod, Uti_Val, Pla_Cod, Pld_Cdc, Pld_Des, Uti_Tip,
            CASE Uti_Tip 
                WHEN \'G\' THEN \'Ganancias\' 
                WHEN \'P\' THEN \'Pérdidas\' 
                WHEN \'I\' THEN \'Part. Impuestos\' 
            END as Tipo_Nombre
            FROM utilidades 
            JOIN det_plan ON utilidades.Pld_Cod = det_plan.Pld_Cod 
            WHERE Pla_Cod = \'$Pla_Cod\'
            ORDER BY Uti_Tip, Pld_Cdc;
    $result = $obBD_con_planc->consulta($sql, $obBD_conexion_con->conexion);
    $rows = array();
    if($result) {
        while($row = mysqli_fetch_assoc($result)) {
            $rows$rows[\'\']\'] = $row;

        }
    }
    utf8_encode_deep($rows);
    $responce$responce[\'']}once['']rows'] = $rows;
    echo json_encode($responce); exit();
}
// Agregar cuenta de utilidad
if(isset($addUtilidad)){
    // Verificar si ya existe una cuenta del tipo seleccionado
    $tipoNombre = ($Uti_Tip == 'G) ? 'Ganancias : (($Uti_Tip == 'P) ? 'Pérdidas : 'Participación e Impuestos);
    
    $sqlCheck = "SELECT COUNT(*) as cnt FROM utilidades u 
                 JOIN det_plan d ON u.Pld_Cod = d.Pld_Cod 
                 WHERE d.Pla_Cod = '$Pla_Cod' AND u.Uti_Tip = '$Uti_Tip';
    $resultCheck = $obBD_con_planc->consulta($sqlCheck, $obBD_conexion_con->conexion);
    $check = mysqli_fetch_assoc($resultCheck);
    
    if($check['cnt'] > 0) {
        $responce = array('success => false, 'message => 'Ya existe una cuenta parametrizada para  . $tipoNombre . '!);
        echo json_encode($responce); exit();
    }
    
    $obBD_con_planc->inicio_transaccion($obBD_conexion_con->conexion);
    $sql = "INSERT INTO utilidades SET Pld_Cod = '$Pld_Cod', Pec_Cod = '$Pec_Cod', Uti_Val = 0, Uti_Tip = '$Uti_Tip';
    $obBD_con_planc->grabarv_registros($sql, $obBD_conexion_con->conexion);
    $obBD_con_planc->fin_transaccion_nomsn($obBD_conexion_con->conexion);
    if($obBD_con_planc->Error == 0) { 
        $responce$responce['']success'] = true; 
    } else { 
        $responce = array('success => false, 'message => 'No se pudo agregar la cuenta!, 'error => $obBD_con_planc->MsgError); 
    }
    echo json_encode($responce); exit();
}
// Eliminar cuenta de utilidad
if(isset($deleteUtilidad)){
    $obBD_con_planc->inicio_transaccion($obBD_conexion_con->conexion);
    $sql = "DELETE FROM utilidades WHERE Pld_Cod = '$Pld_Cod' AND Pec_Cod = '$Pec_Cod' AND Uti_Tip = '$Uti_Tip';
    $obBD_con_planc->grabarv_registros($sql, $obBD_conexion_con->conexion);
    $obBD_con_planc->fin_transaccion_nomsn($obBD_conexion_con->conexion);
    if($obBD_con_planc->Error == 0) { 
        $responce['success'] = true; 
    } else { 
        $responce = array('success => false, 'message => 'No se pudo eliminar la cuenta!, 'error => $obBD_con_planc->MsgError); 
    }
    echo json_encode($responce); exit();
}
// Buscar cuentas para utilidad
if(isset($buscarCuentasUtilidad)){
    $searchTerm = mysqli_real_escape_string($obBD_conexion_con->conexion, isset($_POST['search']) ? $_POST['search'] : $search);
    $tipoBusqueda = isset($_POST['tipoBusqueda']) ? $_POST['tipoBusqueda'] : (isset($_REQUEST['tipoBusqueda']) ? $_REQUEST['tipoBusqueda'] : 'descripcion);
    
    // Obtener grupo padre para mostrar en resultados
    $sql = "SELECT d.Pld_Cod, d.Pld_Cdc, d.Pld_Des, d.Pld_Tip,
            IFNULL((SELECT Pld_Des FROM det_plan WHERE Pld_Cod = d.Pld_Rec), 'RAIZ') as Pld_Grupo
            FROM det_plan d 
            INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod = d.Pla_Cod
            WHERE plan_cuenta.Emp_Cod = '$Ses_Emp_Cod' 
            AND d.Pla_Cod = '$Pla_Cod'
            AND d.Pld_Est = 'A' ;
    
    // Filtro según tipo de búsqueda
    if($tipoBusqueda == 'descripcion) {
        $sql .= " AND d.Pld_Des LIKE '%$searchTerm%' AND (d.Pld_Tip = 'D' OR d.Pld_Tip = 'Detalle');
    } else if($tipoBusqueda == 'codigo) {
        $sql .= " AND d.Pld_Cdc LIKE '%$searchTerm%' AND (d.Pld_Tip = 'D' OR d.Pld_Tip = 'Detalle');
    } else if($tipoBusqueda == 'grupos) {
        // Solo grupos (no detalle) - puede ser 'G' o 'Grupo'
        $sql .= " AND d.Pld_Des LIKE '%$searchTerm%' AND (d.Pld_Tip = 'G' OR d.Pld_Tip = 'Grupo');
    }
    
    $sql .= " ORDER BY d.Pld_Cdc LIMIT 50;
    $result = $obBD_con_planc->consulta($sql, $obBD_conexion_con->conexion);
    $responce$responce$responce['']']total'] = 0;
    $rows = array();
    if($result) {
        while($row = mysqli_fetch_assoc($result)) {
            $rows$rows['']'] = $row;

        }
    }
    utf8_encode_deep($rows);
    $responce$responce$responce['']']rows'] = $rows;
    echo json_encode($responce); exit();
}

// ============= AJAX HANDLERS PARA CÓDIGOS SRI =============
if(isset($sriAjax)){ 
    $contar = $obBD_con_cod->getRowConsulta(18, $search.'*.$Ses_Emp_Cod.'*.$Pla_Cod.'*.$op_opciones.'*, $obBD_conexion_cod);          
    $pagination = pages($contar$contar['']total'], $page, $rows);
    $responce = $pagination$pagination$pagination['']']data'];
    $responce$responce['']rows'] = $obBD_con_cod->getArrayConsulta(18, $search.'*.$Ses_Emp_Cod.'*.$Pla_Cod.'*.$op_opciones.'*.$pagination$pagination$pagination['']']limits'], $obBD_conexion_cod);      
    utf8_encode_deep($responce$responce['']rows']); echo json_encode($responce); exit();
}
if(isset($codAjax)){ 
    $responce$responce$responce['']']rows'] = $obBD_con_cod->getArrayConsulta(9, $search, $obBD_conexion_cod);
    echo json_encode($responce); exit();
}
if(isset($saveCod)){    
    $obBD_con_cod->inicio_transaccion($obBD_conexion_cod->conexion);              
    $obBD_con_cod->operacionobBD(20, $Ren_Cod.'*.$Ren_Con.'*.$Ren_Ret.'*.$Adq_Cod, $obBD_conexion_cod);  
    $obBD_con_cod->fin_transaccion_nomsn($obBD_conexion_cod->conexion);   
    if($obBD_con_cod->Error == 0) { 
        $responce$responce['']success'] = true;
    } else {
        $responce$responce['']success'] = false;
        $responce$responce['']message'] = $obBD_con_cod->MsgError;
    }
    echo json_encode($responce); exit();
}
if(isset($addSriCuenta)){ 
    $responce$responce['']tipo'] = $addSriCuenta;
    $obBD_con_cod->inicio_transaccion($obBD_conexion_cod->conexion);              
    $obBD_con_cod->operacionobBD(19, $Ren_Cod.'*.$Pld_Cod.'*.$addSriCuenta, $obBD_conexion_cod);  
    $obBD_con_cod->fin_transaccion_nomsn($obBD_conexion_cod->conexion);   
    if($obBD_con_cod->Error == 0) { 
        $responce$responce['']success'] = true;
    } else {
        $responce$responce['']success'] = false;
        $responce$responce['']message'] = $obBD_con_cod->MsgError;
    }
    echo json_encode($responce); exit();
}
if(isset($deleteSriCuenta)){ 
    $responce$responce['']tipo'] = $deleteSriCuenta;
    $obBD_con_cod->inicio_transaccion($obBD_conexion_cod->conexion);              
    $obBD_con_cod->operacionobBD(17, $Ren_Cod.'*.$Pld_Cod.'*.$deleteSriCuenta, $obBD_conexion_cod);   
    $obBD_con_cod->fin_transaccion_nomsn($obBD_conexion_cod->conexion);   
    if($obBD_con_cod->Error == 0) { 
        $responce$responce['']success'] = true;
    } else {
        $responce$responce['']success'] = false;
        $responce$responce['']message'] = $obBD_con_cod->MsgError;
    }
    echo json_encode($responce); exit();
}
if(isset($listTipo)){ 
    $responce$responce['']rows'] = $obBD_con_cod->getArrayConsulta(12, $Ren_Cod.'*.$Ses_Emp_Cod.'*.$listTipo.'*.$Pla_Cod, $obBD_conexion_cod);
    echo json_encode($responce); exit();
}

// ============= AJAX HANDLERS PARA BANCOS =============
if(isset($bancosAjax)){ 
    $row_rs_bancos = $obBD_con_ban->getArrayConsulta(16, $Ses_Emp_Cod, $obBD_conexion_ban);
    $responce$responce['']rows'] = $row_rs_bancos;
    utf8_encode_deep($responce$responce$responce['']']rows']);
    echo json_encode($responce); exit();
}
if(isset($buscarCuentaBanco)){
    $Pla_Cod = isset($_REQUEST$_REQUEST['']Pla_Cod']) ? $_REQUEST$_REQUEST$_REQUEST{$_REQUEST['\'\']\']Pla_Cod\'] : \';
    $search_banco = isset($_REQUEST$_REQUEST[\'\']search_banco\']) ? $_REQUEST$_REQUEST[\'\']search_banco\'] : \';
    $op_busqueda = isset($_REQUEST$_REQUEST[\'\']op_busqueda\']) ? $_REQUEST$_REQUEST$_REQUEST[\'\']\']op_busqueda\'] : \'d;
    
    if ($op_busqueda == "d) {
        $row_rs_busq = $obBD_con_ban->getArrayConsulta(3, strtoupper($Pla_Cod.\'*.$search_banco), $obBD_conexion_ban);
    } else {
        $row_rs_busq = $obBD_con_ban->getArrayConsulta(4, strtoupper($Pla_Cod.\'*.$search_banco), $obBD_conexion_ban);
    }
    foreach($row_rs_busq as &$row) {
        $row_rs_existe = $obBD_con_ban->getArrayConsulta(5, $row[\'Pld_Cod\'], $obBD_conexion_ban);
        $row[\'existe\'] = (count($row_rs_existe) > 0);
    }
    utf8_encode_deep($row_rs_busq);
    $responce[\'rows\'] = $row_rs_busq;
    echo json_encode($responce); exit();
}
if(isset($saveBanco)){
    $Ban_Cod = isset($_REQUEST[\'Ban_Cod\']) ? $_REQUEST[\'Ban_Cod\'] : \';
    $Pld_Cod = isset($_REQUEST$_REQUEST[\'\']Pld_Cod\']) ? $_REQUEST$_REQUEST[\'\']Pld_Cod\'] : \';
    $Ban_Tip = isset($_REQUEST[\'Ban_Tip\']) ? $_REQUEST[\'Ban_Tip\'] : \';
    $Bac_Cue = isset($_REQUEST$_REQUEST[\'\']Bac_Cue\']) ? $_REQUEST$_REQUEST[\'\']Bac_Cue\'] : \';
    $Bac_Obs = isset($_REQUEST{$_REQUEST[\'\\'Bac_Obs\\']) ? $_REQUEST[\\'Bac_Obs\\'] : \\';
    $tipos_pago = isset($_REQUEST$_REQUEST['\\\'\\\'']tipos_pago\\']) ? $_REQUEST$_REQUEST[\\'\\']tipos_pago\\'] : \\';
    
    $obBD_con_ban->inicio_transaccion($obBD_conexion_ban->conexion);
    
    if(!empty($Ban_Cod)) {
        // Actualizar banco existente
        $obBD_con_ban->operacionobBD(6, $Bac_Obs.\\'*.$Bac_Cue.\\'*.$Pld_Cod.\\'*A*.$Ban_Tip.\\'*.$Ban_Cod, $obBD_conexion_ban);
        
        // Eliminar tipos de pago anteriores
        $sql_delete = "DELETE FROM pago_plan WHERE Ban_Cod = \\'$Ban_Cod\\';
        $obBD_con_ban->grabarv_registros($sql_delete, $obBD_conexion_ban->conexion);
    } else {
        // Insertar nuevo banco
        $obBD_con_ban->operacionobBD(2, $Pld_Cod.\\'*.$Bac_Cue.\\'*.$Bac_Obs.\\'*.$Ban_Tip, $obBD_conexion_ban);
        $Ban_Cod = $obBD_con_ban->insercionid($obBD_conexion_ban->conexion);
    }
    
    // Agregar tipos de pago
    if(!empty($tipos_pago)) {
        $tipos = explode(\\',, $tipos_pago);
        foreach($tipos as $tipo_id) {
            if(!empty($tipo_id)) {
                $obBD_con_ban->operacionobBD(11, $tipo_id.\\'*.$Ban_Cod, $obBD_conexion_ban);
            }
        }
    }
    
    $obBD_con_ban->fin_transaccion_nomsn($obBD_conexion_ban->conexion);
    if($obBD_con_ban->Error == 0) {
        $responce$responce['\\\'\\\'']success\\'] = true;
    } else {
        $responce$responce[\\'\\']success\\'] = false;
        $responce$responce['\\\'\\\'']message\\'] = $obBD_con_ban->MsgError;
    }
    echo json_encode($responce); exit();
}
if(isset($deleteBanco)){
    $Ban_Cod = isset($_REQUEST$_REQUEST['\\\'\\\'']Ban_Cod\\']) ? $_REQUEST$_REQUEST[\\'\\']Ban_Cod\\'] : \\';
    $obBD_con_ban->inicio_transaccion($obBD_conexion_ban->conexion);
    $sql = "UPDATE banco SET Ban_Est = \\'I\\' WHERE Ban_Cod = $Ban_Cod;
    $obBD_con_ban->grabarv_registros($sql, $obBD_conexion_ban->conexion);
    $obBD_con_ban->fin_transaccion_nomsn($obBD_conexion_ban->conexion);
    if($obBD_con_ban->Error == 0) {
        $responce[\\'success\\'] = true;
    } else {
        $responce[\\'success\\'] = false;
        $responce[\\'message\\'] = $obBD_con_ban->MsgError;
    }
    echo json_encode($responce); exit();
}
if(isset($getTiposPago)){
    $row_rs_tipo = $obBD_con_ban->getArrayConsulta(10,\\', $obBD_conexion_ban);
    $responce$responce[\\'\\']rows\\'] = $row_rs_tipo;
    utf8_encode_deep($responce$responce[\\'\']}etArrayConsulta(10,\', $obBD_conexion_ban);
    $responce$responce$responce[\'\']\']rows\'] = $row_rs_tipo;
    utf8_encode_deep($responce$responce[\'\']rows\']);
    echo json_encode($responce); exit();
}
if(isset($getPlanesCuenta)){
    $sql = "SELECT Pla_Cod, Pla_Obs FROM plan_cuenta WHERE Emp_Cod = \'$Ses_Emp_Cod\' AND Pla_Est = \'A\' ORDER BY Pla_Fec DESC;
    $result = $obBD_con_ban->consulta($sql, $obBD_conexion_ban->conexion);
    if($result) {
        while($row = mysqli_fetch_assoc($result)) {
            $responce[\'rows\'][] = $row;
        }
    }
    utf8_encode_deep($responce[\'rows\']);
    echo json_encode($responce); exit();
}
if(isset($getBanco)){
    $Ban_Cod = isset($_REQUEST[\'Ban_Cod\']) ? $_REQUEST{$_REQUEST[\'\\'Ban_Cod\\'] : \\';
    $row_rs_banco = $obBD_con_ban->getArrayConsulta(9, $Ban_Cod, $obBD_conexion_ban);
    if(count($row_rs_banco) > 0) {
        $banco = $row_rs_banco['$row_rs_banco[\\\'\\\']']];
        // Normalizar nombres de campos para JavaScript
        $banco['\\\'Bac_Cue\\\''] = $banco['\\\'Ban_Cue\\\'']; // El caso 9 devuelve Ban_Cue, lo mapeamos a Bac_Cue
        $banco['\\\'Bac_Obs\\\''] = $banco['\\\'Ban_Obs\\\''];
        // Obtener Pla_Cod del plan de cuenta
        $sql_pla = "SELECT Pla_Cod FROM plan_cuenta WHERE Pla_Obs = \\'.$banco[\\'Pla_Obs\\']."\\' AND Emp_Cod = \\'$Ses_Emp_Cod\\' LIMIT 1;
        $result_pla = $obBD_con_ban->consulta($sql_pla, $obBD_conexion_ban->conexion);
        if($result_pla && $row_pla = mysqli_fetch_assoc($result_pla)) {
            $banco$banco[\\'\\']Pla_Cod\\'] = $row_pla$row_pla[\\'\\\'Pla_Cod];
        }
        // Obtener tipos de pago del banco
        $sql_tipos = "SELECT Pag_Cod FROM pago_plan WHERE Ban_Cod = \\\'$Ban_Cod\\\' AND Pag_Est = \\\'A\\'];
        $result_tipos = $obBD_con_ban->consulta($sql_tipos, $obBD_conexion_ban->conexion);
        $tipos_seleccionados = array();
        if($result_tipos) {
            while($row = mysqli_fetch_assoc($result_tipos)) {
                $tipos_seleccionados['\\\'] = $row$row[\\\'\\\']Pag_Cod\\\''];

            }
        }
        $banco$banco['\\\'\\\'']tipos_pago\\'] = $tipos_seleccionados;
        utf8_encode_deep($banco);
        $responce = $banco;
    } else {
        $responce = array(\\'success => false, \\'message => \\'Banco no encontrado);
    }
    echo json_encode($responce); exit();
}

// Obtener periodos para los selectores
$periodos_iva = $obBD_con_planc->getArrayConsulta(329, $Ses_Emp_Cod, $obBD_conexion_con);
$periodo_iva = isset($periodos_iva[0]) ? $periodos_iva[0] : array(\\'Pla_Cod => \\', \\'Pec_Cod => \\', \\'Pla_Fec => \\');

$periodos_ccpp = $obBD_con_tes->getArrayConsulta(39, $Ses_Emp_Cod, $obBD_conexion_tes);
$periodo_ccpp = isset($periodos_ccpp[0]) ? $periodos_ccpp[0] : array(\\'Pla_Cod => \\', \\'Pec_Cod => \\', \\'Pla_Fec => \\');

$row_rs_planes = $obBD_con_estado->getArrayConsulta(302, $Ses_Emp_Cod, $obBD_conexion_estado);

$periodos_sri = $obBD_con_cod->getArrayConsulta(21, $Ses_Emp_Cod, $obBD_conexion_cod);
$periodo_sri = isset($periodos_sri[0]) ? $periodos_sri[0] : array(\\'Pla_Cod => \\', \\'Pec_Cod => \\', \\'Pla_Fec => \\');

$row_rs_adqui = $obBD_con_cod->getArrayConsulta(16, \\', $obBD_conexion_cod);
?>
<!DOCTYPE html>
<HTML>
<HEAD>      
    <TITLE><?php echo "Parametrizaciones Iniciales [\\'EXA\\']; ?></TITLE>
    <meta charset="UTF-8">
    <?php require_once("../../mascaras/model1/estilos/jqgrid5.php); ?>              
    <style>
        .nav-tabs > li > a {
            font-weight: bold;
            color: #555;
        }
        .nav-tabs > li.active > a,
        .nav-tabs > li.active > a:hover,
        .nav-tabs > li.active > a:focus {
            background-color: #5cb85c;
            color: #fff;
            border-color: #5cb85c;
        }
        .nav-tabs > li > a:hover {
            background-color: #eee;
        }
        .tab-content {
            padding: 20px 10px;
            border: 1px solid #ddd;
            border-top: none;
            background-color: #fff;
        }
        .tab-pane {
            min-height: 400px;
        }
        .panel-param {
            margin-bottom: 0;
        }
        .exa-fieldset {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 15px;
            background: #fafafa;
        }
        .exa-fieldset legend {
            width: auto;
            padding: 0 10px;
            margin-bottom: 10px;
            font-size: 14px;
            border: none;
        }
        .section-title {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 14px;
        }
    </style>
</HEAD>
<BODY>
    <div class="panel panel-main panel-param">
        <div class="panel-heading exa-header">
            <h3 class="panel-title"><i class="fa fa-cogs"></i> &raquo; Parametrizaciones Iniciales del Sistema</h3>
        </div>
        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <!-- Pestañas de navegación -->
            <ul class="nav nav-tabs" role="tablist" id="paramTabs">
                <li role="presentation" class="active">
                    <a href="#tab-iva" aria-controls="tab-iva" role="tab" data-toggle="tab">
                        <i class="fa fa-percent"></i> IVA Pagado/Cobrado
                    </a>
                </li>
                <li role="presentation">
                    <a href="#tab-ccpp" aria-controls="tab-ccpp" role="tab" data-toggle="tab">
                        <i class="fa fa-users"></i> Proveedores/Clientes
                    </a>
                </li>
                <li role="presentation">
                    <a href="#tab-balances" aria-controls="tab-balances" role="tab" data-toggle="tab">
                        <i class="fa fa-balance-scale"></i> Configurar Balances
                    </a>
                </li>
                <li role="presentation">
                    <a href="#tab-sri" aria-controls="tab-sri" role="tab" data-toggle="tab">
                        <i class="fa fa-file-text-o"></i> Códigos SRI
                    </a>
                </li>
                <li role="presentation">
                    <a href="#tab-bancos" aria-controls="tab-bancos" role="tab" data-toggle="tab">
                        <i class="fa fa-university"></i> Bancos
                    </a>
                </li>
            </ul>

            <!-- Contenido de las pestañas -->
            <div class="tab-content">
                
                <!-- =============== TAB 1: IVA PAGADO/COBRADO =============== -->
                <div role="tabpanel" class="tab-pane active" id="tab-iva">
                    <div class="section-title"><i class="fa fa-percent"></i> Parametrización de IVA Pagado/Cobrado (Periodo <span id="anio-iva"><?php echo $periodo_iva[\\'Pla_Fec] ?></span>)</div>
                    <div class="row">
                        <div class="col-sm-4 col-sm-offset-4">
                            <div class="form-horizontal normal">
                                <div class="form-group">
                                    <label class="col-sm-5 control-label label-sm">Periodo Contable:</label>
                                    <div class="col-sm-7">
                                        <select id="selectPecCod-iva" class="form-control input-sm">
                                            <?php foreach ($periodos_iva AS $row){ echo "<option value=\\'{$row$row['\\\'\\\'']Pec_Cod\\']}\\' data-placod=\\'{$row$row['\\\'\\\'']Pla_Cod\\']}\\' data-peccod=\\'{$row$row[\\'\\']Pec_Cod\\']}\\' data-anio=\\'{$row$row[\\'\\']Pla_Fec\\']}\\'>Periodo {$row['\\\'Pla_Fec\\\'']}</option>; } ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">  
                            <div style="min-height: 250px;">
                               <table id="list_iva_cobrado"></table>
                               <div id="listPager_iva_cobrado"></div>
                               <button id="BtnIvaCobrado" onclick="tipoIva=\\'cobrado\\';$(\\'#ivaDialog\\').dialog(\\'open\\');" title="Buscar Cuentas" type="button" class="btn btn-success btn-sm" style="margin-top: 10px;"><i class="glyphicon glyphicon-check"></i><span> Agregar Cuenta</span></button>
                            </div>
                        </div>
                        <div class="col-sm-6">                       
                            <div style="min-height: 250px;">                          
                               <table id="list_iva_pagado"></table>
                               <div id="listPager_iva_pagado"></div>
                               <button onclick="tipoIva=\\'pagado\\';$(\\'#ivaDialog\\').dialog(\\'open\\');" title="Buscar Cuentas" type="button" class="btn btn-success btn-sm" style="margin-top: 10px;"><i class="glyphicon glyphicon-check"></i><span> Agregar Cuenta</span></button>    
                           </div>                        
                        </div>
                    </div>
                    <div style="display: none">
                        <form id="formIva">
                            <input type="text" name="Pla_Cod" value="<?php echo $periodo_iva[\\'Pla_Cod]; ?>" />
                            <input type="text" name="Pec_Cod" value="<?php echo $periodo_iva[\\'Pec_Cod]; ?>" />
                        </form>
                    </div>
                </div>

                <!-- =============== TAB 2: PROVEEDORES/CLIENTES =============== -->
                <div role="tabpanel" class="tab-pane" id="tab-ccpp">
                    <div class="section-title"><i class="fa fa-users"></i> Parametrización de Cuentas Proveedores/Clientes (Periodo <span id="anio-ccpp"><?php echo $periodo_ccpp[\\'Pla_Fec] ?></span>)</div>
                    <div class="row">
                        <div class="col-sm-4 col-sm-offset-4">
                            <div class="form-horizontal normal">
                                <div class="form-group">
                                    <label class="col-sm-5 control-label label-sm">Periodo Contable:</label>
                                    <div class="col-sm-7">
                                        <select id="selectPecCod-ccpp" class="form-control input-sm">
                                            <?php foreach ($periodos_ccpp AS $row){ echo "<option value=\\'{$row$row['\\\'\\\'']Pec_Cod\\']}\\' data-placod=\\'{$row$row['\\\'\\\'']Pla_Cod\\']}\\' data-peccod=\\'{$row$row[\\'\\']Pec_Cod\\']}\\' data-anio=\\'{$row$row[\\'\']}table:</label>
                                    <div class="col-sm-7">
                                        <select id="selectPecCod-ccpp" class="form-control input-sm">
                                            <?php foreach ($periodos_ccpp AS $row){ echo "<option value=\\'{$row$row['\\\'\\\'']Pec_Cod\\']}\\' data-placod=\\'{$row$row[\\'\']row[\'\']Pla_Cod\']}\' data-peccod=\'{$row$row$row[\'\']\']Pec_Cod\']}\' data-anio=\'{$row$row[\'\']Pla_Fec\']}\'>Periodo {$row[\'Pla_Fec\']}</option>; } ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">  
                            <div style="min-height: 250px;">
                               <table id="list_proveedores"></table>
                               <div id="listPager_proveedores"></div>
                               <button onclick="tipoCcpp=\'Deudor\';$(\'#ccppDialog\').dialog(\'open\');" title="Buscar Cuentas" type="button" class="btn btn-success btn-sm" style="margin-top: 10px;"><i class="glyphicon glyphicon-check"></i><span> Agregar Cuenta</span></button>
                            </div>
                        </div>
                        <div class="col-sm-6">                       
                            <div style="min-height: 250px;">                          
                               <table id="list_clientes"></table>
                               <div id="listPager_clientes"></div>
                               <button onclick="tipoCcpp=\'Acreedor\';$(\'#ccppDialog\').dialog(\'open\');" title="Buscar Cuentas" type="button" class="btn btn-success btn-sm" style="margin-top: 10px;"><i class="glyphicon glyphicon-check"></i><span> Agregar Cuenta</span></button>    
                           </div>                        
                        </div>
                    </div>
                    <div style="display: none">
                        <form id="formCcpp">
                            <input type="text" name="Pla_Cod" value="<?php echo $periodo_ccpp{$periodo_ccpp[\'\\'Pla_Cod]; ?>" />
                            <input type="text" name="Pec_Cod" value="<?php echo $periodo_ccpp[\\'Pec_Cod]; ?>" />
                        </form>
                    </div>
                </div>

                <!-- =============== TAB 3: CONFIGURAR BALANCES =============== -->
                <div role="tabpanel" class="tab-pane" id="tab-balances">
                    <div class="section-title"><i class="fa fa-balance-scale"></i> Configurar Balances - Estados Financieros</div>
                    
                    <!-- Vista principal: Lista de planes -->
                    <div id="balances-lista" class="row">
                        <div class="col-sm-5">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2"><i class="fa fa-folder-open"></i> Planes de Cuenta</legend>
                                <div style="min-height: 200px;">
                                    <table id="grid_planes"></table>
                                    <div id="pager_planes"></div>
                                </div>
                            </fieldset>
                        </div>
                        <div class="col-sm-7">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2"><i class="fa fa-list-alt"></i> Tipos de Balance Configurados</legend>
                                <div id="info-plan-seleccionado" class="alert alert-info" style="display:none; margin-bottom: 5px; padding: 5px 10px;">
                                    <strong>Plan:</strong> <span id="nombre-plan-sel"></span>
                                </div>
                                <div style="min-height: 170px;">
                                    <table id="grid_tipos_balance"></table>
                                    <div id="pager_tipos_balance"></div>
                                </div>
                                <button id="btnAgregarTipoBalance" onclick="abrirConfigBalance();" title="Configurar Tipos de Balance" type="button" class="btn btn-success btn-sm" style="margin-top: 10px;" disabled>
                                    <i class="glyphicon glyphicon-cog"></i><span> Configurar Balance</span>
                                </button>
                            </fieldset>
                        </div>
                    </div>

                    <!-- Vista de configuración de un tipo de balance -->
                    <div id="balances-config" class="row" style="display: none;">
                        <!-- Sub-tabs para Balances y Utilidad -->
                        <div class="col-sm-12" style="margin-bottom: 5px;">
                            <ul class="nav nav-tabs" role="tablist" id="subTabsBalances">
                                <li role="presentation" class="active">
                                    <a href="#subtab-balances" aria-controls="subtab-balances" role="tab" data-toggle="tab">
                                        <i class="fa fa-balance-scale"></i> Balances
                                    </a>
                                </li>
                                <li role="presentation">
                                    <a href="#subtab-utilidad" aria-controls="subtab-utilidad" role="tab" data-toggle="tab">
                                        <i class="fa fa-money"></i> Utilidad
                                    </a>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="col-sm-12">
                            <div class="tab-content">
                                <!-- Sub-tab Balances -->
                                <div role="tabpanel" class="tab-pane active" id="subtab-balances">
                                    <div class="row">
                        <div class="col-sm-4">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2"><i class="fa fa-tags"></i> Tipos de Estado Financiero</legend>
                                <div style="min-height: 80px;">
                                    <table id="grid_estados_fin"></table>
                                    <div id="pager_estados_fin"></div>
                                </div>
                            </fieldset>
                        </div>
                        <div class="col-sm-8">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2"><i class="fa fa-list"></i> Datos a registrar:</legend>
                                <div class="alert alert-info" style="margin-bottom: 3px; padding: 3px 8px; font-size: 12px;">
                                    <strong>NOTA:</strong> Los campos marcados con (<span style="color:red;">*</span>) son obligatorios.
                                </div>
                                
                                <div id="panel-tipo-balance" style="display: none;">
                                    <div class="form-group" style="margin-bottom: 3px;">
                                        <label><span style="color:red;">*</span> Tipo de Balance: <strong id="label-tipo-balance"></strong></label>
                                    </div>
                                    
                                    <div id="container-cuentas-raiz" class="row" style="padding: 5px; background: #f9f9f9; border-radius: 5px; margin-bottom: 5px;">
                                        <!-- Aquí se cargarán los checkboxes dinámicamente -->
                                        <div class="col-sm-12 text-center" id="loading-cuentas">
                                            <i class="fa fa-spinner fa-spin"></i> Cargando cuentas...
                                        </div>
                                    </div>
                                    
                                    <div class="form-group" style="margin-bottom: 3px;">
                                        <label>
                                            <input type="checkbox" id="chkMarcarTodos" onchange="marcarDesmarcarTodos(this.checked);">
                                            Marcar/Desmarcar Todos
                                        </label>
                                    </div>
                                    
                                    <button type="button" class="btn btn-primary btn-sm" onclick="guardarConfigBalance();">
                                        <i class="glyphicon glyphicon-floppy-disk"></i> Guardar
                                    </button>
                                </div>
                                
                                <div id="msg-seleccione-tipo" class="text-center" style="padding: 50px;">
                                    <i class="fa fa-hand-pointer-o fa-3x text-muted"></i>
                                    <p class="text-muted" style="margin-top: 15px;">Seleccione un Tipo de Estado Financiero de la lista izquierda</p>
                                </div>
                            </fieldset>
                        </div>
                                    </div><!-- Fin row subtab-balances -->
                                </div><!-- Fin subtab-balances -->
                                
                                <!-- Sub-tab Utilidad -->
                                <div role="tabpanel" class="tab-pane" id="subtab-utilidad">
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <div class="alert alert-info" style="margin-bottom: 5px; padding: 8px 12px;">
                                                <strong>Configuración de Utilidades:</strong> Asigne las cuentas para Pérdidas, Ganancias y Participación de Impuestos.
                                                <span class="pull-right">
                                                    <button type="button" class="btn btn-primary btn-xs" onclick="abrirBusquedaUtilidad();">
                                                        <i class="glyphicon glyphicon-plus"></i> Agregar Cuenta
                                                    </button>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-sm-12">
                                            <fieldset class="exa-fieldset" style="margin-bottom: 5px;">
                                                <legend class="Titulos2"><i class="fa fa-list"></i> Cuentas de Utilidad Configuradas</legend>
                                                <div style="min-height: 120px;">
                                                    <table id="grid_utilidades"></table>
                                                    <div id="pager_utilidades"></div>
                                                </div>
                                            </fieldset>
                                        </div>
                                    </div>
                                </div><!-- Fin subtab-utilidad -->
                            </div><!-- Fin tab-content -->
                        </div><!-- Fin col-sm-12 -->
                        
                        <!-- Botón Volver en la parte inferior -->
                        <div class="col-sm-12" style="margin-top: 0px;">
                            <button type="button" class="btn btn-inverse btn-sm" onclick="volverListaBalances();">
                                <i class="glyphicon glyphicon-arrow-left"></i> Atrás
                            </button>
                        </div>
                    </div>

                    <!-- Formularios ocultos -->
                    <div style="display: none;">
                        <form id="formBalances">
                            <input type="hidden" name="Pla_Cod" id="balance_Pla_Cod" value="" />
                            <input type="hidden" name="Est_Cod" id="balance_Est_Cod" value="" />
                            <input type="hidden" name="Pec_Cod" id="balance_Pec_Cod" value="" />
                        </form>
                    </div>
                </div>

                <!-- =============== TAB 4: CÓDIGOS SRI =============== -->
                <div role="tabpanel" class="tab-pane" id="tab-sri">
                    <div class="section-title"><i class="fa fa-file-text-o"></i> Modificar Códigos SRI</div>
                    <div class="row">
                        <div id="grillaSri">
                            <div class="col-sm-12">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Buscar Código SRI</legend>
                                    <form id="sriForm" class="form-horizontal normal" action="javascript:$(\\'#list_sri\\').Search(\\'#sriForm\\',\\'codAjax\\');$(\\'#formularioSri\\').hide();"> 
                                        <div class="form-group">
                                            <label class="col-sm-1 control-label">Búsqueda</label>
                                            <div class="col-sm-4">
                                                <div class="input-group">                        
                                                    <input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" placeholder="Ingrese Código a buscar..." autofocus="" class="form-control input-sm clearable submit"><input type="text" style="display:none"/>
                                                    <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar"><span class="glyphicon glyphicon-search"></span> <span>Buscar</span></button></span>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </fieldset>        
                            </div>
                            
                            <div class="col-sm-12" style="min-height: 250px;">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Resultados de la búsqueda</legend>
                                    <table id="list_sri"></table>
                                    <div id="listPager_sri"></div>
                                </fieldset>
                            </div>
                        </div>
                        
                        <div id="formularioSri" style="display: none;">
                            <div class="col-sm-6">
                                <form id="codFormSri" class="form-horizontal normal" action="javascript:$.createDialogConfirm(null,null,saveSriCod)"> 
                                    <input id="Ren_Cod_Sri" name="Ren_Cod" type="hidden" />
                                    <fieldset class="exa-fieldset">
                                        <legend class="Titulos2">Datos del Código</legend> 
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label label-sm required">Código SRI:</label>
                                            <div class="col-sm-3">
                                                <span name="Ren_Sri" class="form-control input-sm"></span>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label label-sm required">Porcentaje(%):</label>
                                            <div class="col-sm-3">
                                                <span name="Ren_Por" class="form-control input-sm"></span>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label label-sm required">Bienes/Servicios:</label>
                                            <div class="col-sm-4">
                                                <select name="Adq_Cod" class="form-control input-sm">
                                                    <option value="">Seleccione...</option>                
                                                    <?php foreach($row_rs_adqui as $row){ ?>
                                                        <option value="<?php echo $row$row['\\\'\\\'']Adq_Cod\\']; ?>"><?php echo $row$row[\\'\\']Adq_Des\\'];?></option>                
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label label-sm required">Renta/IVA:</label>
                                            <div class="col-sm-3">
                                                <select name="Ren_Ret" class="form-control input-sm">
                                                    <option value="">Seleccione...</option>                
                                                    <option value="R">Renta</option>
                                                    <option value="I">IVA</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label label-sm required">Descripción:</label>
                                            <div class="col-sm-9">
                                                <input name="Ren_Con" type="text" class="form-control input-sm" value="" maxlength="200" style="text-transform:uppercase" />
                                            </div>
                                        </div>
                                    </fieldset>  
                                    <div>
                                        <a onclick="$(\\'#formularioSri\\').hide();$(\\'#grillaSri\\').show();" class="btn btn-inverse btn-sm" title="Volver Atrás"><i class="glyphicon glyphicon-arrow-left"></i><span>&nbsp;&nbsp;Atrás&nbsp;&nbsp;</span></a>
                                        <button type="submit" class="btn btn-success btn-sm" title="Guardar Cambios"><i class="glyphicon glyphicon-floppy-disk"></i> <span>Guardar</span></button>
                                    </div>
                                    <div class="Titulos2"><hr><b>NOTA:</b> Los campos marcados con asterisco (<span class="required"></span>) son obligatorios.</div>
                                </form>
                            </div>
                            <div class="col-sm-6">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Cuentas Contables (Periodo <span id="anio-sri"><?php echo $periodo_sri[\\'Pla_Fec] ?></span>)</legend> 
                                    <div class="form-horizontal normal">
                                        <div class="form-group">
                                            <label class="col-sm-8 control-label label-sm">Periodo Contable:</label>
                                            <div class="col-sm-4">
                                                <select id="selectPecCod-sri" class="form-control input-sm">
                                                    <?php foreach ($periodos_sri AS $row){ echo "<option value=\\'{$row$row['\\\'\\\'']Pec_Cod\\']}\\' data-placod=\\'{$row$row['\\\'\\\'']Pla_Cod\\']}\\' data-peccod=\\'{$row$row[\\'\\']Pec_Cod\\']}\\' data-anio=\\'{$row$row[\\'\']}}\' data-peccod=\'{$row$row$row[\'\']']}                 <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Cuentas Contables (Periodo <span id="anio-sri"><?php echo $periodo_sri[\\'Pla_Fec] ?></span>)</legend> 
                                    <div class="form-horizontal normal">
                                        <div class="form-group">
                                            <label class="col-sm-8 control-label label-sm">Periodo Contable:</label>
                                            <div class="col-sm-4">
                                                <select id="selectPecCod-sri" class="form-control input-sm">
                                                    <?php foreach ($periodos_sri AS $row){ echo "<option value=\\'{$row$row['\\\'\\\'']Pec_Cod\\']}\\' data-placod=\\'{$row$row['\\\'\\\'']Pla_Cod\\']}\\' data-peccod=\\'{$row$row[\\'\\']Pec_Cod\\']}\\' data-anio=\\'{$row$row[\\'\'']'{$row$row[\'']}}' data-peccod='{$row$row$row$row['']']']Pec_Cod']}' data-anio='{$row$row$row['']']Pla_Fec']}'>Periodo {$row$row['']Pla_Fec']}</option>; } ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div style="padding-bottom: 5px; min-height: 100px;">
                                        <table id="sri_compras"></table>                                
                                    </div>
                                    <button id="btnSriCompra" onclick="tipoSri='C';$('#sriCuenDialog').dialog('open');" title="Buscar Cuentas" type="button" class="btn btn-success btn-sm"><i class="glyphicon glyphicon-check"></i><span> Seleccionar Cuenta</span></button>
                                    <div style="height: 10px;"></div>
                                    <div style="padding-bottom: 5px; min-height: 100px;">
                                        <table id="sri_ventas"></table>                               
                                    </div>
                                    <button id="btnSriVenta" onclick="tipoSri='V';$('#sriCuenDialog').dialog('open');" title="Buscar Cuentas" type="button" class="btn btn-success btn-sm"><i class="glyphicon glyphicon-check"></i><span> Seleccionar Cuenta</span></button>
                                </fieldset>  
                            </div>
                        </div>
                    </div>
                    <div style="display: none">
                        <form id="formSri">
                            <input type="text" name="Pla_Cod" value="<?php echo $periodo_sri['Pla_Cod]; ?>" />
                            <input type="text" name="Pec_Cod" value="<?php echo $periodo_sri['Pec_Cod]; ?>" />
                        </form>
                    </div>
                </div>

                <!-- =============== TAB 5: BANCOS =============== -->
                <div role="tabpanel" class="tab-pane" id="tab-bancos">
                    <!-- PASO 1: Listado de Bancos -->
                    <div id="banco-paso1-listado">
                        <div class="section-title"><i class="fa fa-university"></i> Registro de Bancos</div>
                        <div class="row">
                            <div class="col-sm-12">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2"><i class="fa fa-list"></i> Bancos Registrados</legend>
                                    <div style="min-height: 250px;">
                                        <table id="grid_bancos"></table>
                                        <div id="pager_bancos"></div>
                                    </div>
                                    <button onclick="mostrarPaso2Banco();" title="Agregar Banco" type="button" class="btn btn-success btn-sm" style="margin-top: 10px;">
                                        <i class="glyphicon glyphicon-plus"></i><span> Registrar Nuevo Banco</span>
                                    </button>
                                </fieldset>
                            </div>
                        </div>
                    </div>

                    <!-- PASO 2: Formulario de Registro -->
                    <div id="banco-paso2-formulario" style="display:none;">
                        <div class="section-title"><i class="fa fa-plus-circle"></i> Registrar Nuevo Banco</div>
                        <div class="row">
                            <div class="col-sm-10 col-sm-offset-1">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2"><i class="fa fa-edit"></i> Datos del Banco</legend>
                                    
                                    <form id="formBanco" class="form-horizontal normal">
                                        <input type="hidden" name="Ban_Cod" id="Ban_Cod" value="">
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label"><span style="color:red;">*</span> Plan de Cuentas:</label>
                                            <div class="col-sm-7">
                                                <select name="PlaCod_banco" id="PlaCod_banco" class="form-control input-sm">
                                                    <option value="">Seleccione...</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label"><span style="color:red;">*</span> Cuenta Contable:</label>
                                            <div class="col-sm-7">
                                                <input type="hidden" name="PldCod_banco" id="PldCod_banco">
                                                <input type="text" name="PldDes_banco" id="PldDes_banco" readonly class="form-control input-sm" placeholder="Haga clic en 'Buscar Cuenta'">
                                            </div>
                                            <div class="col-sm-2">
                                                <button type="button" class="btn btn-success btn-sm" onclick="mostrarPaso3Banco();">
                                                    <i class="glyphicon glyphicon-search"></i> Buscar Cuenta
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label"><span style="color:red;">*</span> Tipo:</label>
                                            <div class="col-sm-7">
                                                <select name="BanTip" id="BanTip" class="form-control input-sm">
                                                    <option value="">Seleccione...</option>
                                                    <option value="C">Caja</option>
                                                    <option value="B">Banco</option>
                                                    <option value="O">Otros</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label"><span style="color:red;">*</span> # Cuenta Bancaria:</label>
                                            <div class="col-sm-7">
                                                <input type="text" name="BacCue" id="BacCue" class="form-control input-sm" maxlength="20" style="text-transform:uppercase" placeholder="Ej: 1234567890">
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label">Observaciones:</label>
                                            <div class="col-sm-7">
                                                <textarea name="BacObs" id="BacObs" class="form-control input-sm" rows="3" style="text-transform:uppercase" placeholder="Información adicional (opcional)"></textarea>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label"><span style="color:red;">*</span> Tipos de Pago:</label>
                                            <div class="col-sm-7">
                                                <div id="tiposPagoContainer" style="border: 1px solid #ddd; padding: 10px; display: flex; flex-wrap: wrap; gap: 10px;"></div>
                                            </div>
                                        </div>
                                    </form>
                                    
                                    <div style="text-align:center; margin-top: 20px; padding-top: 15px; border-top: 1px solid #ddd;">
                                        <button type="button" class="btn btn-success btn-lg" onclick="guardarBanco();">
                                            <i class="glyphicon glyphicon-floppy-disk"></i> Guardar Banco
                                        </button>
                                        <button type="button" class="btn btn-default btn-lg" onclick="volverPaso1Banco();">
                                            <i class="glyphicon glyphicon-remove"></i> Cancelar
                                        </button>
                                    </div>
                                </fieldset>
                            </div>
                        </div>
                    </div>

                    <!-- PASO 3: Búsqueda de Cuenta -->
                    <div id="banco-paso3-buscar" style="display:none;">
                        <div class="section-title"><i class="fa fa-search"></i> Buscar Cuenta Contable</div>
                        <div class="row">
                            <div class="col-sm-10 col-sm-offset-1">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2"><i class="fa fa-filter"></i> Filtros de Búsqueda</legend>
                                    
                                    <div class="form-horizontal normal">
                                        <div class="form-group">
                                            <label class="col-sm-2 control-label">Buscar por:</label>
                                            <div class="col-sm-4">
                                                <label style="margin-right: 15px;">
                                                    <input type="radio" name="op_busqueda_banco" value="d" checked> Descripción
                                                </label>
                                                <label>
                                                    <input type="radio" name="op_busqueda_banco" value="c"> Código
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="col-sm-2 control-label">Búsqueda:</label>
                                            <div class="col-sm-8">
                                                <div class="input-group">
                                                    <input type="text" id="searchCuentaBanco" class="form-control input-sm" placeholder="Ingrese texto a buscar..." onkeydown="if(event.keyCode===13){ejecutarBusquedaBanco();return false;}">
                                                    <span class="input-group-btn">
                                                        <button type="button" class="btn btn-success btn-sm" onclick="ejecutarBusquedaBanco();">
                                                            <i class="glyphicon glyphicon-search"></i> Buscar
                                                        </button>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div style="min-height: 300px; margin-top: 15px;">
                                        <table id="grid_buscar_cuenta_banco"></table>
                                    </div>
                                    
                                    <div style="text-align:left; margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
                                        <button type="button" class="btn btn-default" onclick="volverPaso2Banco();">
                                            <i class="glyphicon glyphicon-arrow-left"></i> Volver al Formulario
                                        </button>
                                    </div>
                                </fieldset>
                            </div>
                        </div>
                    </div>
                </div>

            </div><!-- /.tab-content -->
        </div>
    </div>

    <!-- ============= DIALOGOS ============= -->
    
    <!-- DIALOGO BUSCAR CUENTA IVA -->
    <div id="ivaDialog" title="Búsqueda de Cuentas - IVA">  
        <form class="form-horizontal normal">       
            <fieldset>
                <legend>Filtros</legend>
                <div class="form-group">
                    <label class="col-md-2 control-label label-xs">Filtrar Por:</label>  
                    <div class="col-md-5 radioset">
                        <input id="radIva1" name="op_opciones" type="radio" value="d" checked="" /><label for="radIva1">&nbsp;&nbsp;Descripción&nbsp;&nbsp;</label>
                        <input id="radIva2" name="op_opciones" type="radio" value="c" /><label for="radIva2">&nbsp;&nbsp;Código&nbsp;&nbsp;</label>                          
                    </div>                   
                    <div class="col-md-4">
                        <label class="control-label label-xs">Plan de Cuentas:</label>                       
                        <input name="periodo" type="text" size="6" value="<?php echo $periodo_iva['Pla_Fec]?>" readonly style="text-align: center;display: inline-block;width: auto;" class="form-control input-xs" /> 
                        <input name="Pec_Cod" type="hidden" value="<?php echo $periodo_iva['Pec_Cod]?>" /> 
                    </div>    
                </div>
                <div class="form-group">
                    <label class="col-md-2 control-label">Búsqueda:</label>  
                    <div class="col-md-7">
                        <div class="input-group">                        
                            <input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese cuenta a buscar..." autofocus class="form-control input-sm"/>
                            <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar cuenta"><span class="glyphicon glyphicon-search"></span> <span>Buscar</span></button></span>
                        </div>
                    </div>                    
                </div>
            </fieldset>  
        </form> 
    </div>

    <!-- DIALOGO BUSCAR CUENTA CCPP -->
    <div id="ccppDialog" title="Búsqueda de Cuentas - Prov./Clientes">  
        <form class="form-horizontal normal">       
            <fieldset>
                <legend>Filtros</legend>
                <div class="form-group">
                    <label class="col-md-2 control-label label-xs">Filtrar Por:</label>  
                    <div class="col-md-5 radioset">
                        <input id="radCcpp1" name="op_opciones" type="radio" value="d" checked="" /><label for="radCcpp1">&nbsp;&nbsp;Descripción&nbsp;&nbsp;</label>
                        <input id="radCcpp2" name="op_opciones" type="radio" value="c" /><label for="radCcpp2">&nbsp;&nbsp;Código&nbsp;&nbsp;</label>                          
                    </div>                   
                    <div class="col-md-4">
                        <label class="control-label label-xs">Plan de Cuentas:</label>                       
                        <input name="periodo" type="text" size="6" value="<?php echo $periodo_ccpp['Pla_Fec]?>" readonly style="text-align: center;display: inline-block;width: auto;" class="form-control input-xs" /> 
                        <input name="Pec_Cod" type="hidden" value="<?php echo $periodo_ccpp['Pec_Cod]?>" /> 
                    </div>    
                </div>
                <div class="form-group">
                    <label class="col-md-2 control-label">Búsqueda:</label>  
                    <div class="col-md-7">
                        <div class="input-group">                        
                            <input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese cuenta a buscar..." autofocus class="form-control input-sm"/>
                            <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar cuenta"><span class="glyphicon glyphicon-search"></span> <span>Buscar</span></button></span>
                        </div>
                    </div>                    
                </div>
            </fieldset>  
        </form> 
    </div>

    <!-- DIALOGO BUSCAR CUENTA SRI -->
    <div id="sriCuenDialog" title="Búsqueda de Cuentas - Códigos SRI">  
        <form class="form-horizontal normal">       
            <fieldset>
                <legend>Filtros</legend>
                <div class="form-group">
                    <label class="col-md-2 control-label label-xs">Filtrar Por:</label>  
                    <div class="col-md-5 radioset">
                        <input id="radSri1" name="op_opciones" type="radio" value="d" checked="" /><label for="radSri1">&nbsp;&nbsp;Descripción&nbsp;&nbsp;</label>
                        <input id="radSri2" name="op_opciones" type="radio" value="c" /><label for="radSri2">&nbsp;&nbsp;Código&nbsp;&nbsp;</label>                          
                    </div>                   
                    <div class="col-md-4">
                        <label class="control-label label-xs">Plan de Cuentas:</label>                       
                        <input name="periodo" type="text" size="6" value="<?php echo $periodo_sri['Pla_Fec]?>" readonly style="text-align: center;display: inline-block;width: auto;" class="form-control input-xs" /> 
                        <input name="Pec_Cod" type="hidden" value="<?php echo $periodo_sri['Pec_Cod]?>" /> 
                        <input name="Pla_Cod" type="hidden" value="<?php echo $periodo_sri['Pla_Cod]?>" />
                    </div>    
                </div>
                <div class="form-group">
                    <label class="col-md-2 control-label">Búsqueda:</label>  
                    <div class="col-md-7">
                        <div class="input-group">                        
                            <input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese cuenta a buscar..." autofocus class="form-control input-sm"/>
                            <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar cuenta"><span class="glyphicon glyphicon-search"></span> <span>Buscar</span></button></span>
                        </div>
                    </div>                    
                </div>
            </fieldset>  
        </form> 
    </div>

    <!-- DIALOGO BUSCAR CUENTA PARA BALANCE -->
    <div id="balanceDialog" title="Búsqueda de Cuentas (Grupos) para Balance" style="display:none;">  
        <form class="form-horizontal normal" id="formBuscarCuentaBalance">       
            <fieldset>
                <legend>Buscar Cuenta</legend>
                <div class="form-group">
                    <label class="col-md-2 control-label">Búsqueda:</label>  
                    <div class="col-md-8">
                        <div class="input-group">                        
                            <input name="search" id="searchCuentaBalance" onkeydown="if (event.keyCode === 13) { buscarCuentasBalance(); return false; }" type="text" size="50" maxlength="50" placeholder="Ingrese nombre o código de cuenta..." autofocus class="form-control input-sm"/>
                            <span class="input-group-btn"><button type="button" onclick="buscarCuentasBalance();" class="btn btn-success btn-sm" title="Buscar cuenta"><span class="glyphicon glyphicon-search"></span> <span>Buscar</span></button></span>
                        </div>
                    </div>                    
                </div>
            </fieldset>  
        </form>
        <div style="min-height: 200px; margin-top: 10px;">
            <table id="grid_buscar_cuentas_balance"></table>
        </div>
    </div>

    <!-- DIALOGO BUSCAR CUENTA PARA UTILIDAD -->
    <div id="utilidadDialog" title="Búsqueda de Cuentas para Utilidad" style="display:none;">  
        <fieldset>
            <legend>Búsqueda de Cuentas</legend>
            <div class="form-group" style="margin-bottom: 10px;">
                <label class="radio-inline">
                    <input type="radio" name="tipoBusquedaUtilidad" value="descripcion" checked> Descripción
                </label>
                <label class="radio-inline">
                    <input type="radio" name="tipoBusquedaUtilidad" value="codigo"> Código
                </label>
                <label class="radio-inline">
                    <input type="radio" name="tipoBusquedaUtilidad" value="grupos"> Grupos
                </label>
            </div>
            <div class="form-group">
                <label id="lblBusquedaUtilidad" style="display:inline-block; width: 90px;">Descripción:</label>
                <input type="text" id="searchUtilidad" class="form-control input-sm" style="display:inline-block; width: 350px;" placeholder="Ingrese texto a buscar..." onkeydown="if(event.keyCode===13){buscarCuentasUtilidad();return false;}">
                <button type="button" class="btn btn-success btn-sm" onclick="buscarCuentasUtilidad();">
                    <i class="glyphicon glyphicon-search"></i> Buscar
                </button>
            </div>
        </fieldset>
        <div style="min-height: 250px; margin-top: 10px;">
            <table id="grid_buscar_utilidad"></table>
        </div>
        <div id="infoResultadosUtilidad" class="text-muted" style="margin-top: 5px;"></div>
    </div>

    <!-- ============= SCRIPTS ============= -->
    <script>
        // Variables globales
        var tipoIva = '', tipoCcpp = '', tipoSri = '';
        var UrlSelf = "<?php echo filter_input(INPUT_SERVER, 'PHP_SELF, FILTER_SANITIZE_STRING); ?>";

        // Crear diálogos de búsqueda
        $.createSearchDialog('ivaDialog', [
            { label: 'Cód.Int.', name: 'Pld_Cod', key: true, width: 15, align: "center", hidden: true },                                
            { label: 'Codigo', name: 'Pld_Cdc', width: 45 },                      
            { label: 'Cuenta', name: 'Pld_Des', width: 80, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},
            { label: 'Grupo', name: 'Pld_Grupo', width: 110, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},
            { label: 'Tipo', name: 'Pld_Tip', width: 30, align: "center" },
            { label: 'Estado', name: 'Pld_Est', width: 30, align: "center"}, 
            { label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 30, align: 'center', viewable: false,
                formatter: function (cellvalue, options, rowObject) { return $.getGridButton(addIvaCuenta, rowObject.Pld_Cod, 'Agregar Cuenta'); }
            }
        ]);

        $.createSearchDialog('ccppDialog', [
            { label: 'Cód.Int.', name: 'Pld_Cod', key: true, width: 15, align: "center", hidden: true },                                
            { label: 'Codigo', name: 'Pld_Cdc', width: 45 },                      
            { label: 'Cuenta', name: 'Pld_Des', width: 80, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},
            { label: 'Grupo', name: 'Pld_Grupo', width: 110, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},
            { label: 'Tipo', name: 'Pld_Tip', width: 30, align: "center" },
            { label: 'Estado', name: 'Pld_Est', width: 30, align: "center"}, 
            { label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 30, align: 'center', viewable: false,
                formatter: function (cellvalue, options, rowObject) { return $.getGridButton(addCcppCuenta, rowObject.Pld_Cod, 'Agregar Cuenta'); }
            }
        ]);

        $.createSearchDialog('sriCuenDialog', [
            { label: 'Cód.Int.', name: 'Pld_Cod', key: true, width: 15, align: "center", hidden: true },                                
            { label: 'Codigo', name: 'Pld_Cdc', width: 45 },                      
            { label: 'Cuenta', name: 'Pld_Des', width: 80, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},
            { label: 'Grupo', name: 'Pld_Grupo', width: 110, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},
            { label: 'Tipo', name: 'Pld_Tip', width: 30, align: "center" },
            { label: 'Estado', name: 'Pld_Est', width: 30, align: "center"}, 
            { label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 30, align: 'center', viewable: false,
                formatter: function (cellvalue, options, rowObject) { return $.getGridButton(addSriCuenta, rowObject.Pld_Cod, 'Agregar Cuenta'); }
            }
        ]);

        // ============= FUNCIONES IVA =============
        function addIvaCuenta(a2) {       
            $('#ivaDialog').dialog('close');          
            if ((tipoIva === 'cobrado' && $("#list_iva_cobrado").existsId(a2)) || (tipoIva === 'pagado' && $("#list_iva_pagado").existsId(a2))) {
                $.alert('La Cuenta ya está Registrada!'); 
                return;
            }
            $.saveDataJson(UrlSelf, {Pld_Cod: a2, addIvaCuenta: tipoIva},
                function(r) { 
                    $("#list_iva_" + (r['tipo'] === 'cobrado' ? 'cobrado' : 'pagado')).jqGrid().trigger("reloadGrid", [{ page: 1 }]); 
                }
            );
        }

        function deleteIvaCuenta(data) {
            $.saveDataJson(UrlSelf, {Pld_Cod: data['Pld_Cod'], deleteIvaCuenta: data['tipo']}, 
                function(r) { 
                    $("#list_iva_" + (r['tipo'] === 'cobrado' ? 'cobrado' : 'pagado')).jqGrid().trigger("reloadGrid", [{ page: 1 }]);  
                }
            );
        }

        // ============= FUNCIONES CCPP =============
        function addCcppCuenta(a2) {
            $('#ccppDialog').dialog('close');
            if ((tipoCcpp === 'Deudor' && $("#list_proveedores").existsId(a2)) || (tipoCcpp === 'Acreedor' && $("#list_clientes").existsId(a2))) {
                $.alert('La Cuenta ya está Registrada!');
                return;
            }
            $.saveDataJson(UrlSelf, {Pld_Cod: a2, addCcppCuenta: tipoCcpp},
                function(r) {
                    $("#" + (r['tipo'] === 'Deudor' ? 'list_proveedores' : 'list_clientes')).jqGrid().trigger("reloadGrid", [{ page: 1 }]);
                }
            );
        }

        function deleteCcppCuenta(data) {
            $.alert('Para realizar cambios comuníquese con el administrador!');
        }

        // ============= FUNCIONES SRI =============
        function deleteSriCuenta(cuenta) {  
            $.saveDataJson(UrlSelf, $.extend({Ren_Cod: $('#Ren_Cod_Sri').val()}, cuenta), 
                function(r) { 
                    $("#" + (r['tipo'] === 'C' ? 'sri_compras' : 'sri_ventas')).jqGrid().trigger("reloadGrid", [{ page: 1 }]); 
                }
            );
        }

        function addSriCuenta(a2) {  
            $('#sriCuenDialog').dialog('close');
            $.saveDataJson(UrlSelf, {Ren_Cod: $('#Ren_Cod_Sri').val(), Pld_Cod: a2, addSriCuenta: tipoSri},
                function(r) { 
                    $("#" + (r['tipo'] === 'C' ? 'sri_compras' : 'sri_ventas')).jqGrid().trigger("reloadGrid", [{ page: 1 }]); 
                }
            );
        }

        function saveSriCod() {  
            $.saveDataJson(UrlSelf, $('#codFormSri').getData('saveCod'), 
                function(r) { 
                    $("#list_sri").jqGrid().trigger("reloadGrid", [{ page: 1 }]); 
                }
            );
        }

        // ============= FUNCIONES BANCOS (SISTEMA DE PASOS) =============
        function mostrarPaso2Banco() {
            $.post(UrlSelf, {getPlanesCuenta: true}, function(response) {
                var data = typeof response === 'string' ? JSON.parse(response) : response;
                var options = '<option value="">Seleccione...</option>';
                if (data.rows) {
                    data.rows.forEach(function(plan) {
                        options += '<option value="' + plan.Pla_Cod + '">' + plan.Pla_Obs + '</option>';
                    });
                }
                $('#PlaCod_banco').html(options);
            });
            $.post(UrlSelf, {getTiposPago: true}, function(response) {
                var data = typeof response === 'string' ? JSON.parse(response) : response;
                var html = '';
                if (data.rows) {
                    data.rows.forEach(function(tipo) {
                        html += '<label style="flex: 0 0 calc(33.333% - 7px); margin-bottom: 5px;"><input type="checkbox" name="tipos_pago[]" value="' + tipo.Pag_Cod + '"> ' + tipo.tipo + '</label>';
                    });
                }
                $('#tiposPagoContainer').html(html);
            });
            $('#formBanco')[0].reset();
            $('#Ban_Cod').val('');
            $('#PldCod_banco').val('');
            $('#PldDes_banco').val('');
            $('#banco-paso1-listado').hide();
            $('#banco-paso2-formulario').show();
            $('#banco-paso3-buscar').hide();
        }

        function modificarBanco(banCod) {
            $.post(UrlSelf, {getBanco: true, Ban_Cod: banCod}, function(response) {
                var data = typeof response === 'string' ? JSON.parse(response) : response;
                if (data.success === false) {
                    $.alert('Error: ' + (data.message || 'No se pudo cargar el banco'));
                    return;
                }
                $.post(UrlSelf, {getPlanesCuenta: true}, function(responsePlanes) {
                    var dataPlanes = typeof responsePlanes === 'string' ? JSON.parse(responsePlanes) : responsePlanes;
                    var options = '<option value="">Seleccione...</option>';
                    if (dataPlanes.rows) {
                        dataPlanes.rows.forEach(function(plan) {
                            var selected = (plan.Pla_Cod == data.Pla_Cod) ? 'selected' : '';
                            options += '<option value="' + plan.Pla_Cod + '" ' + selected + '>' + plan.Pla_Obs + '</option>';
                        });
                    }
                    $('#PlaCod_banco').html(options);
                });
                $.post(UrlSelf, {getTiposPago: true}, function(responseTipos) {
                    var dataTipos = typeof responseTipos === 'string' ? JSON.parse(responseTipos) : responseTipos;
                    var html = '';
                    if (dataTipos.rows) {
                        dataTipos.rows.forEach(function(tipo) {
                            var checked = (data.tipos_pago && data.tipos_pago.indexOf(tipo.Pag_Cod.toString()) !== -1) ? 'checked' : '';
                            html += '<label style="flex: 0 0 calc(33.333% - 7px); margin-bottom: 5px;"><input type="checkbox" name="tipos_pago[]" value="' + tipo.Pag_Cod + '" ' + checked + '> ' + tipo.tipo + '</label>';
                        });
                    }
                    $('#tiposPagoContainer').html(html);
                });
                $('#Ban_Cod').val(data.Ban_Cod);
                $('#PldCod_banco').val(data.Pld_Cod);
                $('#PldDes_banco').val(data.Pld_Des);
                $('#BanTip').val(data.Ban_Tip);
                $('#BacCue').val(data.Bac_Cue || data.Ban_Cue);
                $('#BacObs').val(data.Bac_Obs || data.Ban_Obs);
                $('#banco-paso1-listado').hide();
                $('#banco-paso2-formulario').show();
                $('#banco-paso3-buscar').hide();
            });
        }

        function mostrarPaso3Banco() {
            var plaCod = $('#PlaCod_banco').val();
            if (!plaCod) {
                $.alert('Primero seleccione un Plan de Cuentas');
                return;
            }
            $('#searchCuentaBanco').val('');
            $('#banco-paso1-listado').hide();
            $('#banco-paso2-formulario').hide();
            $('#banco-paso3-buscar').show();
            setTimeout(function() {
                var $grid = $("#grid_buscar_cuenta_banco");
                var containerWidth = $grid.closest('.exa-fieldset').width();
                $grid.jqGrid('setGridWidth', containerWidth - 40);
            }, 100);
        }

        function volverPaso1Banco() {
            $('#banco-paso1-listado').show();
            $('#banco-paso2-formulario').hide();
            $('#banco-paso3-buscar').hide();
            $('#grid_bancos').trigger('reloadGrid');
            setTimeout(function() {
                var $grid = $('#grid_bancos');
                if ($grid.length && $grid.closest('.ui-jqgrid').parent().width() > 0) {
                    try {
                        $(window).trigger('resize');
                        $grid.jqGrid('setGridWidth', $grid.closest('.ui-jqgrid').parent().width(), true);
                    } catch(e) {}
                }
            }, 100);
        }

        function volverPaso2Banco() {
            $('#banco-paso1-listado').hide();
            $('#banco-paso2-formulario').show();
            $('#banco-paso3-buscar').hide();
        }

        function ejecutarBusquedaBanco() {
            var plaCod = $('#PlaCod_banco').val();
            var search = $('#searchCuentaBanco').val();
            var op = $('input[name="op_busqueda_banco"]:checked').val();
            if (!search) {
                $.alert('Ingrese un criterio de búsqueda');
                return;
            }
            $("#grid_buscar_cuenta_banco").jqGrid('setGridParam', {
                url: UrlSelf,
                postData: {
                    buscarCuentaBanco: true,
                    Pla_Cod: plaCod,
                    search_banco: search,
                    op_busqueda: op
                },
                datatype: 'json'
            }).trigger('reloadGrid');
            setTimeout(function() {
                var $grid = $("#grid_buscar_cuenta_banco");
                var containerWidth = $grid.closest('.exa-fieldset').width();
                $grid.jqGrid('setGridWidth', containerWidth - 40);
            }, 200);
        }

        function seleccionarCuentaBanco(pldCod) {
            var rowData = $("#grid_buscar_cuenta_banco").jqGrid('getRowData', pldCod);
            $('#PldCod_banco').val(pldCod);
            $('#PldDes_banco').val(rowData.Pld_Des);
            volverPaso2Banco();
        }

        function guardarBanco() {
            var banCod = $('#Ban_Cod').val();
            var plaCod = $('#PlaCod_banco').val();
            var pldCod = $('#PldCod_banco').val();
            var banTip = $('#BanTip').val();
            var bacCue = $('#BacCue').val();
            var bacObs = $('#BacObs').val();
            if (!plaCod) { $.alert('Seleccione un Plan de Cuentas'); return; }
            if (!pldCod) { $.alert('Seleccione una Cuenta Contable'); return; }
            if (!banTip) { $.alert('Seleccione un Tipo'); return; }
            if (!bacCue) { $.alert('Ingrese el número de cuenta bancaria'); return; }
            var tiposPago = [];
            $('input[name="tipos_pago[]"]:checked').each(function() { tiposPago.push($(this).val()); });
            if (tiposPago.length === 0) { $.alert('Seleccione al menos un tipo de pago'); return; }
            var postData = {
                saveBanco: true,
                Pld_Cod: pldCod,
                Ban_Tip: banTip,
                Bac_Cue: bacCue,
                Bac_Obs: bacObs,
                tipos_pago: tiposPago.join(',')
            };
            if (banCod) { postData.Ban_Cod = banCod; }
            $.post(UrlSelf, postData, function(response) {
                var data = typeof response === 'string' ? JSON.parse(response) : response;
                if (data.success) {
                    var mensaje = banCod ? 'Banco modificado exitosamente' : 'Banco guardado exitosamente';
                    $.alert(mensaje, function() {
                        volverPaso1Banco();
                        $('#grid_bancos').trigger('reloadGrid');
                    });
                } else {
                    $.alert('Error: ' + (data.message || 'No se pudo guardar el banco'));
                }
            });
        }

        function deleteBanco(banCod) {
            if (!confirm('¿Está seguro de eliminar este banco?')) { return; }
            $.post(UrlSelf, { deleteBanco: true, Ban_Cod: banCod }, function(response) {
                var data = typeof response === 'string' ? JSON.parse(response) : response;
                if (data.success) {
                    $.alert('Banco eliminado exitosamente', function() {
                        $('#grid_bancos').trigger('reloadGrid');
                        setTimeout(function() {
                            var $grid = $('#grid_bancos');
                            if ($grid.length && $grid.closest('.ui-jqgrid').parent().width() > 0) {
                                try {
                                    $(window).trigger('resize');
                                    $grid.jqGrid('setGridWidth', $grid.closest('.ui-jqgrid').parent().width(), true);
                                } catch(e) {}
                            }
                        }, 150);
                    });
                } else {
                    $.alert('Error: ' + (data.message || 'No se pudo eliminar el banco'));
                }
            });
        }

        function selectSriEmp(id) {          
            $('#grillaSri').hide();
            $('#formularioSri').show();
            var dataFromRow = $("#list_sri").jqGrid('getRowData', id), 
                perio = $('#selectPecCod-sri').find('option:selected').data();
            dataFromRow['Ren_Ret'] = (dataFromRow['Ren_Ret'] === 'RENTA' ? 'R' : 'I');
            $('#codFormSri').setData(dataFromRow);            

            $("#sri_compras").jqGrid('setGridParam', {datatype: 'json', postData: {Ren_Cod: dataFromRow['Ren_Cod'], Pla_Cod: perio['placod'], listTipo: 'C'}}).trigger("reloadGrid", [{ page: 1 }]);
            $("#sri_ventas").jqGrid('setGridParam', {datatype: 'json', postData: {Ren_Cod: dataFromRow['Ren_Cod'], Pla_Cod: perio['placod'], listTipo: 'V'}}).trigger("reloadGrid", [{ page: 1 }]);
            
            // Forzar redimensionamiento de grids después de mostrar
            setTimeout(function() {
                $("#sri_compras").jqGrid('setGridWidth', $("#sri_compras").closest('.ui-jqgrid').parent().width(), true);
                $("#sri_ventas").jqGrid('setGridWidth', $("#sri_ventas").closest('.ui-jqgrid').parent().width(), true);
            }, 150);
        }

        // ============= FUNCIONES BALANCES =============
        var balancePlaSeleccionado = null;
        var balanceEstSeleccionado = null;
        var balancePlanNombre = '';

        var balancePecSeleccionado = null;

        function seleccionarPlan(plaCod) {
            balancePlaSeleccionado = plaCod;
            $('#balance_Pla_Cod').val(plaCod);
            
            // Obtener nombre del plan y Pec_Cod
            var rowData = $("#grid_planes").jqGrid('getRowData', plaCod);
            balancePlanNombre = rowData.Pla_Obs;
            balancePecSeleccionado = rowData.Pec_Cod;
            $('#balance_Pec_Cod').val(balancePecSeleccionado);
            $('#nombre-plan-sel').text(balancePlanNombre);
            $('#info-plan-seleccionado').show();
            $('#btnAgregarTipoBalance').prop('disabled', false);
            
            // Cargar tipos de balance
            $("#grid_tipos_balance").jqGrid('setGridParam', {
                datatype: 'json', 
                postData: {Pla_Cod: plaCod, tiposBalanceAjax: true}
            }).trigger("reloadGrid", [{ page: 1 }]);
        }

        function abrirConfigBalance() {
            if (!balancePlaSeleccionado) {
                $.alert('Primero seleccione un Plan de Cuentas');
                return;
            }
            $('#config-plan-nombre').text(balancePlanNombre);
            $('#balances-lista').hide();
            $('#balances-config').show();
            
            // Ocultar panel de configuración y mostrar mensaje
            $('#panel-tipo-balance').hide();
            $('#msg-seleccione-tipo').show();
            balanceEstSeleccionado = null;
            
            // Cargar estados financieros disponibles
            $("#grid_estados_fin").jqGrid('setGridParam', {
                datatype: 'json', 
                postData: {Pla_Cod: balancePlaSeleccionado, estadosFinAjax: true}
            }).trigger("reloadGrid", [{ page: 1 }]);
            
            // Forzar redimensionamiento de grillas después de mostrar
            setTimeout(function() {
                $("#grid_estados_fin").jqGrid('setGridWidth', $("#grid_estados_fin").closest('.ui-jqgrid').parent().width(), true);
            }, 150);
        }

        function volverListaBalances() {
            $('#balances-config').hide();
            $('#balances-lista').show();
            balanceEstSeleccionado = null;
            
            // Forzar redimensionamiento
            setTimeout(function() {
                $("#grid_planes").jqGrid('setGridWidth', $("#grid_planes").closest('.ui-jqgrid').parent().width(), true);
                $("#grid_tipos_balance").jqGrid('setGridWidth', $("#grid_tipos_balance").closest('.ui-jqgrid').parent().width(), true);
            }, 150);
        }

        function seleccionarTipoBalance(estCod) {
            balanceEstSeleccionado = estCod;
            $('#balance_Est_Cod').val(estCod);
            
            // Obtener nombre del tipo
            var rowData = $("#grid_estados_fin").jqGrid('getRowData', estCod);
            $('#label-tipo-balance').text(rowData.Est_Des);
            
            // Mostrar panel de configuración
            $('#msg-seleccione-tipo').hide();
            $('#panel-tipo-balance').show();
            
            // Cargar cuentas raíz con checkboxes
            cargarCuentasRaiz();
        }

        function cargarCuentasRaiz() {
            // Limpiar completamente y mostrar loading
            $('#container-cuentas-raiz').html('<div class="col-sm-12 text-center" id="loading-cuentas"><i class="fa fa-spinner fa-spin"></i> Cargando cuentas...</div>');
            
            $.post(UrlSelf, {
                cuentasRaizBalance: true,
                Pla_Cod: balancePlaSeleccionado,
                Est_Cod: balanceEstSeleccionado
            }, function(response) {
                var data = typeof response === 'string' ? JSON.parse(response) : response;
                
                // Limpiar contenedor completamente
                $('#container-cuentas-raiz').empty();
                
                if (data.rows && data.rows.length > 0) {
                    var html = '';
                    var itemsPorColumna = Math.ceil(data.rows.length / 3); // Dividir en 3 columnas
                    
                    for (var col = 0; col < 3; col++) {
                        html += '<div class="col-sm-4">';
                        for (var i = col * itemsPorColumna; i < Math.min((col + 1) * itemsPorColumna, data.rows.length); i++) {
                            var cuenta = data.rows['i'];
                            var checked = cuenta.checked == 1 ? 'checked' : '';
                            html += '<div class="checkbox">';
                            html += '<label>';
                            html += '<input type="checkbox" class="cuenta-raiz-chk" value="' + cuenta.Pld_Cod + '" ' + checked + '>';
                            html += ' ' + cuenta.Pld_Des;
                            html += '</label>';
                            html += '</div>';
                        }
                        html += '</div>';
                    }
                    
                    $('#container-cuentas-raiz').html(html);
                    actualizarCheckTodos();
                } else {
                    $('#container-cuentas-raiz').html('<div class="col-sm-12 text-center text-muted">No hay cuentas raíz disponibles</div>');
                }
            });
        }

        function marcarDesmarcarTodos(marcar) {
            $('.cuenta-raiz-chk').prop('checked', marcar);
        }

        function actualizarCheckTodos() {
            var total = $('.cuenta-raiz-chk').length;
            var marcados = $('.cuenta-raiz-chk:checked').length;
            $('#chkMarcarTodos').prop('checked', total > 0 && total === marcados);
        }

        function guardarConfigBalance() {
            if (!balanceEstSeleccionado) {
                $.alert('Primero seleccione un Tipo de Balance');
                return;
            }
            
            var cuentasSeleccionadas = [];
            $('.cuenta-raiz-chk:checked').each(function() {
                cuentasSeleccionadas.push($(this).val());
            });
            
            $.post(UrlSelf, {
                guardarCuentasBalance: true,
                Pla_Cod: balancePlaSeleccionado,
                Est_Cod: balanceEstSeleccionado,
                cuentas: cuentasSeleccionadas
            }, function(response) {
                var data = typeof response === 'string' ? JSON.parse(response) : response;
                if (data.success) {
                    $.alert('Configuración guardada correctamente');
                    // Recargar tipos de balance
                    $("#grid_tipos_balance").jqGrid().trigger("reloadGrid", [{ page: 1 }]);
                } else {
                    $.alert(data.message || 'Error al guardar la configuración');
                }
            });
        }

        // Actualizar checkbox "todos" cuando se cambia un individual
        $(document).on('change', '.cuenta-raiz-chk', function() {
            actualizarCheckTodos();
        });

        // ============= FUNCIONES UTILIDAD =============
        function abrirBusquedaUtilidad() {
            if (!balancePlaSeleccionado) {
                $.alert('Primero seleccione un Plan de Cuentas');
                return;
            }
            $('#searchUtilidad').val('');
            $("#grid_buscar_utilidad").jqGrid('clearGridData');
            $('#infoResultadosUtilidad').html('');
            $('input[name="tipoBusquedaUtilidad"][value="descripcion"]').prop('checked', true);
            actualizarLabelBusquedaUtilidad();
            $('#utilidadDialog').dialog('open');
        }

        function actualizarLabelBusquedaUtilidad() {
            var tipo = $('input[name="tipoBusquedaUtilidad"]:checked').val();
            var label = tipo === 'descripcion' ? 'Descripción:' : (tipo === 'codigo' ? 'Código:' : 'Grupo:');
            $('#lblBusquedaUtilidad').text(label);
            $('#searchUtilidad').attr('placeholder', 'Ingrese ' + label.toLowerCase().replace(':', '') + ' a buscar...');
        }

        function buscarCuentasUtilidad() {
            var search = $('#searchUtilidad').val();
            if (!search || search.length < 1) {
                $.alert('Ingrese texto para buscar');
                return;
            }
            if (!balancePlaSeleccionado) {
                $.alert('Primero seleccione un Plan de Cuentas');
                return;
            }
            var tipoBusqueda = $('input[name="tipoBusquedaUtilidad"]:checked').val();
            
            // Limpiar grid y datos previos
            $("#grid_buscar_utilidad").jqGrid('clearGridData');
            
            // Establecer nuevos parámetros y recargar
            $("#grid_buscar_utilidad").jqGrid('setGridParam', {
                datatype: 'json',
                postData: {
                    search: search,
                    tipoBusqueda: tipoBusqueda,
                    Pla_Cod: balancePlaSeleccionado,
                    Ses_Emp_Cod: '<?php echo $Ses_Emp_Cod; ?>',
                    buscarCuentasUtilidad: true
                },
                url: UrlSelf
            }).trigger("reloadGrid", [{ page: 1 }]);
        }

        function agregarUtilidad(pldCod, tipo) {
            var tipoNombre = tipo === 'G' ? 'Ganancias' : (tipo === 'P' ? 'Pérdidas' : 'Part. Impuestos');
            
            var pecCod = balancePecSeleccionado || $('#balance_Pec_Cod').val() || '0';
            
            $.post(UrlSelf, {
                addUtilidad: true,
                Pld_Cod: pldCod,
                Pla_Cod: balancePlaSeleccionado,
                Pec_Cod: pecCod,
                Uti_Tip: tipo
            }, function(response) {
                var data = typeof response === 'string' ? JSON.parse(response) : response;
                if (data.success) {
                    cargarUtilidades();
                    buscarCuentasUtilidad(); // Refrescar resultados
                    $.alert('Cuenta agregada como ' + tipoNombre);
                } else {
                    $.alert(data.message || 'Error al agregar la cuenta');
                }
            });
        }

        function eliminarUtilidad(pldCod, pecCod, utiTip) {
            if (!confirm('¿Está seguro de eliminar esta cuenta de utilidad?')) {
                return;
            }
            
            $.post(UrlSelf, {
                deleteUtilidad: true,
                Pld_Cod: pldCod,
                Pec_Cod: pecCod,
                Uti_Tip: utiTip
            }, function(response) {
                var data = typeof response === 'string' ? JSON.parse(response) : response;
                if (data.success) {
                    cargarUtilidades();
                } else {
                    $.alert(data.message || 'Error al eliminar la cuenta');
                }
            });
        }

        function cargarUtilidades() {
            if (!balancePlaSeleccionado) return;
            
            $("#grid_utilidades").jqGrid('setGridParam', {
                datatype: 'json',
                postData: {
                    Pla_Cod: balancePlaSeleccionado,
                    utilidadesAjax: true
                }
            }).trigger("reloadGrid", [{ page: 1 }]);
            
            // Forzar redimensionamiento
            setTimeout(function() {
                $("#grid_buscar_utilidad").jqGrid('setGridWidth', $("#grid_buscar_utilidad").closest('.ui-jqgrid').parent().width(), true);
                $("#grid_utilidades").jqGrid('setGridWidth', $("#grid_utilidades").closest('.ui-jqgrid').parent().width(), true);
            }, 200);
        }

        // ============= DOCUMENT READY =============
        $(document).ready(function() {
            
            // ===== BANDERAS PARA CARGA BAJO DEMANDA =====
            var tabsLoaded = {
                'tab-iva': false,
                'tab-ccpp': false,
                'tab-balances': false,
                'tab-sri': false
            };
            
            // ===== INICIALIZAR DIÁLOGOS PRIMERO =====
            // Diálogo de búsqueda de utilidades
            $('#utilidadDialog').dialog({
                autoOpen: false,
                modal: true,
                width: 750,
                height: 420,
                title: 'Búsqueda de Cuentas para Utilidad',
                open: function() {
                    $('#searchUtilidad').val('').focus();
                    $('#infoResultadosUtilidad').html('');
                    // Forzar redimensionamiento del grid de búsqueda al abrir
                    setTimeout(function() {
                        $("#grid_buscar_utilidad").jqGrid('setGridWidth', $("#grid_buscar_utilidad").closest('.ui-jqgrid').parent().width(), true);
                    }, 150);
                },
                close: function() {
                    // Forzar redimensionamiento del grid de utilidades al cerrar
                    setTimeout(function() {
                        $("#grid_utilidades").jqGrid('setGridWidth', $("#grid_utilidades").closest('.ui-jqgrid').parent().width(), true);
                    }, 100);
                }
            });

            // Evento para cambiar label al cambiar tipo de búsqueda
            $('input[name="tipoBusquedaUtilidad"]').on('change', function() {
                actualizarLabelBusquedaUtilidad();
            });
            
            // ===== GRIDS IVA =====
            $("#list_iva_cobrado").createGrid({                
                datatype: 'local', // No cargar al inicio
                postData: $("#formIva").getData("ivaCobrado"), 
                height: 200, rowNum: 10000000, pgbuttons: false, pgtext: null, 
                caption: 'IVA Cobrado',
                colModel: [
                    { label: 'Cód.Int.', name: 'Pld_Cod', key: true, width: 40, align: "center", hidden: false },                                
                    { label: 'Cod. Cuenta', name: 'Pld_Cdc', width: 100 },                      
                    { label: 'Cuenta Contable', name: 'Pld_Des', width: 300 },                               
                    { label: '&nbsp;', name: 'act1', width: 30, align: 'center', viewable: false,
                        formatter: function (cellvalue, options, rowObject) { 
                            return $.getGridButton(deleteIvaCuenta, {Pld_Cod: rowObject.Pld_Cod, tipo: 'cobrado'}, 'Eliminar', 'remove', null, 'danger'); 
                        }
                    }
                ],
                loadComplete: function() { 
                    if ($(this).jqGrid('getDataIDs').length === 0) 
                        $('#BtnIvaCobrado').removeAttr('disabled'); 
                    else 
                        $('#BtnIvaCobrado').attr('disabled', 'disabled'); 
                }
            }, false, "#listPager_iva_cobrado", {refresh: true, view: true});                                    
            
            $("#list_iva_pagado").createGrid({                
                datatype: 'local', // No cargar al inicio
                postData: $("#formIva").getData("ivaPagado"), 
                height: 200, rowNum: 10000000, pgbuttons: false, pgtext: null, 
                caption: 'IVA Pagado',
                colModel: [
                    { label: 'Cód.Int.', name: 'Pld_Cod', key: true, width: 40, align: "center", hidden: false },                                
                    { label: 'Cod. Cuenta', name: 'Pld_Cdc', width: 100 },                      
                    { label: 'Cuenta Contable', name: 'Pld_Des', width: 300 },                               
                    { label: '&nbsp;', name: 'act1', width: 30, align: 'center', viewable: false,
                        formatter: function (cellvalue, options, rowObject) { 
                            return $.getGridButton(deleteIvaCuenta, {Pld_Cod: rowObject.Pld_Cod, tipo: 'pagado'}, 'Eliminar', 'remove', null, 'danger'); 
                        }
                    }
                ]
            }, false, "#listPager_iva_pagado", {refresh: true, view: true});

            // ===== GRIDS CCPP =====
            $("#list_proveedores").createGrid({
                datatype: 'local', // No cargar al inicio
                postData: $("#formCcpp").getData("deudor"),
                height: 200, rowNum: 10000000, pgbuttons: false, pgtext: null,
                caption: 'Proveedores',
                colModel: [
                    { label: 'Cód.Int.', name: 'Pld_Cod', key: true, width: 40, align: "center", hidden: false },
                    { label: 'Cod. Cuenta', name: 'Pld_Cdc', width: 100 },
                    { label: 'Cuenta Contable', name: 'Pld_Des', width: 300 },
                    { label: '&nbsp;', name: 'act1', width: 30, align: 'center', viewable: false,
                        formatter: function (cellvalue, options, rowObject) {
                            return $.getGridButton(deleteCcppCuenta, {Pld_Cod: rowObject.Pld_Cod, tipo: 'Deudor'}, 'Eliminar', 'remove', null, 'danger');
                        }
                    }
                ]
            }, false, "#listPager_proveedores", {refresh: true, view: true});

            $("#list_clientes").createGrid({
                datatype: 'local', // No cargar al inicio
                postData: $("#formCcpp").getData("acreedor"),
                height: 200, rowNum: 10000000, pgbuttons: false, pgtext: null,
                caption: 'Clientes',
                colModel: [
                    { label: 'Cód.Int.', name: 'Pld_Cod', key: true, width: 40, align: "center", hidden: false },
                    { label: 'Cod. Cuenta', name: 'Pld_Cdc', width: 100 },
                    { label: 'Cuenta Contable', name: 'Pld_Des', width: 300 },
                    { label: '&nbsp;', name: 'act1', width: 30, align: 'center', viewable: false,
                        formatter: function (cellvalue, options, rowObject) {
                            return $.getGridButton(deleteCcppCuenta, {Pld_Cod: rowObject.Pld_Cod, tipo: 'Acreedor'}, 'Eliminar', 'remove', null, 'danger');
                        }
                    }
                ]
            }, false, "#listPager_clientes", {refresh: true, view: true});

            // ===== GRIDS SRI =====
            $("#list_sri").createGrid({                
                datatype: 'local', // No cargar al inicio
                postData: $("#sriForm").getData("codAjax"), 
                height: 250, rowNum: 1000000, pgbuttons: false, pgtext: null,
                colModel: [
                    { label: 'Cod. Int.', name: 'Ren_Cod', key: true, width: 20, align: "center", hidden: false },  
                    { label: 'Cod. Int.', name: 'Adq_Cod', width: 20, align: "center", hidden: true },  
                    { label: 'Cod. SRI', name: 'Ren_Sri', width: 35, align: "center" }, 
                    { label: 'Descripción', name: 'Ren_Con', width: 180, align: "left" },
                    { label: 'Porcentaje(%)', name: 'Ren_Por', width: 35, align: "right", formatter: 'number', formatoptions: {suffix: ' %'} },
                    { label: 'Bienes/Servicios', name: 'Ren_Tip', width: 50, align: "center" },
                    { label: 'Renta/IVA', name: 'Ren_Ret', width: 50, align: "center" },
                    { label: '&nbsp;', name: 'act1', width: 30, align: 'center', viewable: false,
                        formatter: function (cellvalue, options, rowObject) { 
                            return $.getGridButton(selectSriEmp, rowObject.Ren_Cod, 'Seleccionar'); 
                        }
                    }
                ]             
            }, false, "#listPager_sri", {refresh: true, view: true});
            
            $("#sri_compras").createGrid({
                height: 80, caption: '<b>COMPRAS»</b> Cuentas Contables',
                colModel: [    
                    { label: 'Ren Cod', name: 'Ren_Cod', width: 30, align: "center", hidden: false },
                    { label: 'Cod. Int.', name: 'Pld_Cod', key: true, width: 30, align: "center", hidden: false }, 
                    { label: 'Cuenta', name: 'Pld_Cdc', width: 50, align: "center"}, 
                    { label: 'Descripción', name: 'Pld_Des', width: 90, align: "left" },
                    { label: '&nbsp;', name: 'act1', width: 30, align: 'center', viewable: false,
                        formatter: function (cellvalue, options, rowObject) { 
                            return $.getGridButton(deleteSriCuenta, {Pld_Cod: rowObject.Pld_Cod, deleteSriCuenta: 'C'}, 'Eliminar', 'remove', null, 'danger'); 
                        }
                    }
                ],
                loadComplete: function() {
                    var ids = $("#sri_compras").jqGrid('getDataIDs'); 
                    if (ids.length === 0) 
                        $('#btnSriCompra').removeAttr('disabled'); 
                    else 
                        $('#btnSriCompra').attr('disabled', 'disabled'); 
                }
            }); 

            $("#sri_ventas").createGrid({
                height: 80, caption: '<b>VENTAS»</b> Cuentas Contables', 
                colModel: [    
                    { label: 'Ren Cod', name: 'Ren_Cod', width: 30, align: "center", hidden: false },
                    { label: 'Cod. Int.', name: 'Pld_Cod', key: true, width: 30, align: "center", hidden: false }, 
                    { label: 'Cuenta', name: 'Pld_Cdc', width: 50, align: "center"}, 
                    { label: 'Descripción', name: 'Pld_Des', width: 90, align: "left" },
                    { label: '&nbsp;', name: 'act1', width: 30, align: 'center', viewable: false,
                        formatter: function (cellvalue, options, rowObject) { 
                            return $.getGridButton(deleteSriCuenta, {Pld_Cod: rowObject.Pld_Cod, deleteSriCuenta: 'V'}, 'Eliminar', 'remove', null, 'danger'); 
                        }
                    }
                ],
                loadComplete: function() {
                    var ids = $("#sri_ventas").jqGrid('getDataIDs'); 
                    if (ids.length === 0) 
                        $('#btnSriVenta').removeAttr('disabled'); 
                    else 
                        $('#btnSriVenta').attr('disabled', 'disabled'); 
                }
            });

            // ===== GRIDS BALANCES =====
            // Grid de planes de cuenta
            $("#grid_planes").createGrid({
                datatype: 'local', // No cargar al inicio
                postData: {balancesAjax: true},
                height: 180, rowNum: 1000000, pgbuttons: false, pgtext: null,
                caption: '<i class="fa fa-folder-open"></i> Planes de Cuenta',
                colModel: [
                    { label: 'Cód.', name: 'Pla_Cod', key: true, width: 40, align: "center" },
                    { label: 'Pec_Cod', name: 'Pec_Cod', hidden: true },
                    { label: 'Descripción', name: 'Pla_Obs', width: 200 },
                    { label: 'Estado', name: 'Pla_Est', width: 60, align: "center",
                        formatter: function(cellvalue, options, rowObject) {
                            if (cellvalue === 'Inactivo') {
                                return '<span class="label label-danger">Inactivo</span>';
                            }
                            return '<span class="label label-success">Activo</span>';
                        }
                    },
                    { label: '', name: 'act1 ', width: 40, align: 'center', viewable: false,
                        formatter: function (cellvalue, options, rowObject) { 
                            if (rowObject.Pla_Est === 'Inactivo') {
                                return '<span class="text-muted"><i class="glyphicon glyphicon-lock"></i></span>';
                            }
                            return $.getGridButton(seleccionarPlan, rowObject.Pla_Cod, 'Seleccionar Plan', 'arrow-right'); 
                        }
                    }
                ],
                onSelectRow: function(id) {
                    var rowData = $(this).jqGrid('getRowData', id);
                    if (rowData.Pla_Est !== 'Inactivo') {
                        seleccionarPlan(id);
                    }
                }
            }, false, "#pager_planes", {refresh: true});

            // Grid de tipos de balance configurados
            $("#grid_tipos_balance").createGrid({
                datatype: 'local', // No cargar datos hasta seleccionar un plan
                height: 150, rowNum: 1000000, pgbuttons: false, pgtext: null,
                caption: '<i class="fa fa-list-alt"></i> Tipos de Balance',
                colModel: [
                    { label: 'Cód.', name: 'Est_Cod', key: true, width: 40, align: "center" },
                    { label: 'Tipo de Balance', name: 'Est_Des', width: 250 }
                ]
            }, false, "#pager_tipos_balance");

            // Grid de estados financieros disponibles (para configuración)
            $("#grid_estados_fin").createGrid({
                height: 80, rowNum: 1000000, pgbuttons: false, pgtext: null,
                caption: '<i class="fa fa-tags"></i> Estados Financieros',
                colModel: [
                    { label: 'Cód.', name: 'Est_Cod', key: true, width: 40, align: "center" },
                    { label: 'Descripción', name: 'Est_Des', width: 200 },
                    { label: '', name: 'act1', width: 40, align: 'center', viewable: false,
                        formatter: function (cellvalue, options, rowObject) { 
                            return $.getGridButton(seleccionarTipoBalance, rowObject.Est_Cod, 'Configurar', 'cog'); 
                        }
                    }
                ],
                onSelectRow: function(id) {
                    seleccionarTipoBalance(id);
                }
            }, false, "#pager_estados_fin");


            // Grid de búsqueda de cuentas para balance
            $("#grid_buscar_cuentas_balance").createGrid({
                height: 180, rowNum: 50, pgbuttons: false, pgtext: null,
                colModel: [
                    { label: 'Cód.', name: 'Pld_Cod', key: true, width: 50, align: "center" },
                    { label: 'Código', name: 'Pld_Cdc', width: 80 },
                    { label: 'Descripción', name: 'Pld_Des', width: 200 },
                    { label: 'Tipo', name: 'Pld_Tip', width: 60, align: "center" },
                    { label: '', name: 'act1', width: 40, align: 'center', viewable: false,
                        formatter: function (cellvalue, options, rowObject) { 
                            return $.getGridButton(agregarCuentaBalance, rowObject.Pld_Cod, 'Agregar', 'plus'); 
                        }
                    }
                ]
            });

            // Inicializar diálogo de búsqueda de cuentas para balance
            $('#balanceDialog').dialog({
                autoOpen: false,
                modal: true,
                width: 700,
                height: 400,
                title: 'Buscar Cuentas (Grupos) para Balance',
                open: function() {
                    $('#searchCuentaBalance').val('').focus();
                    $("#grid_buscar_cuentas_balance").jqGrid('clearGridData');
                }
            });

            // ===== GRIDS UTILIDAD =====
            // Grid de búsqueda de cuentas para utilidad (en diálogo)
            $("#grid_buscar_utilidad").createGrid({
                url: UrlSelf,
                height: 200, rowNum: 50, pgbuttons: false, pgtext: null,
                colModel: [
                    { label: 'Cód.Int.', name: 'Pld_Cod', key: true, width: 40, align: "center" },
                    { label: 'Código', name: 'Pld_Cdc', width: 70 },
                    { label: 'Cuenta', name: 'Pld_Des', width: 150, cellattr: function() { return 'style="white-space: normal;"'; } },
                    { label: 'Grupo', name: 'Pld_Grupo', width: 100, cellattr: function() { return 'style="white-space: normal;"'; } },
                    { label: 'Tipo', name: 'Pld_Tip', width: 40, align: "center" },
                    { label: '', name: 'acciones', width: 50, align: 'center', viewable: false, title: false,
                        formatter: function (cellvalue, options, rowObject) { 
                            // Para cuentas tipo Detalle: mostrar P y G
                            if (rowObject.Pld_Tip === 'D' || rowObject.Pld_Tip === 'Detalle') {
                                return '<button type="button" class="btn btn-warning btn-xs" title="Agregar Cta Pérdidas" onclick="agregarUtilidad(\'' + rowObject.Pld_Cod + '\', \'P\')" style="margin-right:2px;"><b>P</b></button>' +
                                       '<button type="button" class="btn btn-success btn-xs" title="Agregar Cta Ganancias" onclick="agregarUtilidad(\'' + rowObject.Pld_Cod + '\', \'G\')"><b>G</b></button>';
                            }
                            // Para grupos: solo mostrar PI
                            return '<button type="button" class="btn btn-info btn-xs" title="Agregar Part. Impuestos" onclick="agregarUtilidad(\'' + rowObject.Pld_Cod + '\', \'I\')"><b>PI</b></button>';
                        }
                    }
                ],
                loadComplete: function() {
                    var count = $(this).jqGrid('getGridParam', 'records');
                    $('#infoResultadosUtilidad').html('(Se encontraron ' + count + ' registros en la base de datos)');
                    $(this).jqGrid('setGridWidth', $(this).closest('.ui-jqgrid').parent().width(), true);
                }
            });


            // Grid de cuentas de utilidad configuradas
            $("#grid_utilidades").createGrid({
                height: 100, rowNum: 1000000, pgbuttons: false, pgtext: null,
                caption: '<i class="fa fa-list"></i> Utilidades Configuradas',
                colModel: [
                    { label: 'Cód.', name: 'Pld_Cod', key: true, width: 40, align: "center" },
                    { label: 'Pec_Cod', name: 'Pec_Cod', hidden: true },
                    { label: 'Uti_Tip', name: 'Uti_Tip', hidden: true },
                    { label: 'Código', name: 'Pld_Cdc', width: 80 },
                    { label: 'Cuenta', name: 'Pld_Des', width: 180 },
                    { label: 'Tipo', name: 'Tipo_Nombre', width: 80, align: "center",
                        formatter: function(cellvalue, options, rowObject) {
                            var clase = rowObject.Uti_Tip === 'G' ? 'success' : (rowObject.Uti_Tip === 'P' ? 'warning' : 'info');
                            return '<span class="label label-' + clase + '">' + cellvalue + '</span>';
                        }
                    },
                    { label: '', name: 'act1', width: 30, align: 'center', viewable: false,
                        formatter: function (cellvalue, options, rowObject) { 
                            return '<button type="button" class="btn btn-danger btn-xs" title="Eliminar" onclick="eliminarUtilidad(\'' + rowObject.Pld_Cod + '\', \'' + rowObject.Pec_Cod + '\', \'' + rowObject.Uti_Tip + '\')"><i class="glyphicon glyphicon-remove"></i></button>';
                        }
                    }
                ],
                loadComplete: function() {
                    $(this).jqGrid('setGridWidth', $(this).closest('.ui-jqgrid').parent().width(), true);
                }
            }, false, "#pager_utilidades");

            // ===== GRID BANCOS =====
            $("#grid_bancos").createGrid({
                url: UrlSelf,
                postData: {bancosAjax: true},
                datatype: 'local',
                pager: '#pager_bancos',
                height: 250, rowNum: 1000000, pgbuttons: false, pgtext: null,
                caption: '<i class="fa fa-university"></i> Listado de Bancos',
                colModel: [
                    { label: 'Cód.', name: 'Ban_Cod', key: true, width: 30, align: "center" },
                    { label: 'Código Cuenta', name: 'Pld_Cdc', width: 70 },
                    { label: 'Cuenta Contable', name: 'Pld_Des', width: 150, cellattr: function() { return 'style="white-space: normal;"'; } },
                    { label: 'Tipo', name: 'Ban_Tip', width: 50, align: "center",
                        formatter: function(v) {
                            if(v == 'C') return 'Caja';
                            if(v == 'B') return 'Banco';
                            if(v == 'O') return 'Otros';
                            return v;
                        }
                    },
                    { label: '# Cuenta Bancaria', name: 'Bac_Cue', width: 80 },
                    { label: 'Observaciones', name: 'Ban_Obs', width: 100 },
                    { label: ' ', name: 'act1', width: 30, align: 'center', viewable: false,
                        formatter: function (cellvalue, options, rowObject) { 
                            return $.getGridButton(modificarBanco, rowObject.Ban_Cod, 'Modificar', 'edit', null, 'primary');
                        }
                    }
                ]
            });

            $("#grid_buscar_cuenta_banco").createGrid({
                url: UrlSelf,
                datatype: 'local',
                height: 200, rowNum: 50, pgbuttons: false, pgtext: null,
                colModel: [
                    { label: 'Cód.Int.', name: 'Pld_Cod', key: true, width: 40, align: "center" },
                    { label: 'Código', name: 'Pld_Cdc', width: 70 },
                    { label: 'Cuenta', name: 'Pld_Des', width: 150, cellattr: function() { return 'style="white-space: normal;"'; } },
                    { label: 'Tipo', name: 'Pld_Tip', width: 40, align: "center" },
                    { label: '', name: 'act1', width: 50, align: 'center', viewable: false,
                        formatter: function (cellvalue, options, rowObject) { 
                            if (rowObject.existe) {
                                return '<span class="label label-warning">Ya existe</span>';
                            }
                            return $.getGridButton(seleccionarCuentaBanco, rowObject.Pld_Cod, 'Seleccionar'); 
                        }
                    }
                ]
            });

            // Redimensionamiento para tab bancos
            $(document).on('shown.bs.tab', 'a[href="#tab-bancos"]', function (e) {
                setTimeout(function() {
                    var $grid = $('#grid_bancos');
                    if ($grid.length && $grid.closest('.ui-jqgrid').parent().width() > 0) {
                        try {
                            $(window).trigger('resize');
                            $grid.jqGrid('setGridWidth', $grid.closest('.ui-jqgrid').parent().width(), true);
                        } catch(e) {}
                    }
                }, 150);
            });

            // ===== EVENTOS CAMBIO DE PERIODO =====
            $('#selectPecCod-iva').on('change', function() {
                var data = $(this).find('option:selected').data();
                $('#formIva input[name="Pla_Cod"]').val(data['placod']);
                $('#formIva input[name="Pec_Cod"]').val(data['peccod']);
                $('#ivaDialog input[name="periodo"]').val(data['anio']);
                $('#ivaDialog input[name="Pec_Cod"]').val(data['peccod']);
                $('#anio-iva').html(data['anio']);
                $("#list_iva_cobrado").Search("#formIva", "ivaCobrado");
                $("#list_iva_pagado").Search("#formIva", "ivaPagado");
                $.Search('iva');                
            });

            $('#selectPecCod-ccpp').on('change', function() {
                var data = $(this).find('option:selected').data();
                $('#formCcpp input[name="Pla_Cod"]').val(data['placod']);
                $('#formCcpp input[name="Pec_Cod"]').val(data['peccod']);
                $('#ccppDialog input[name="periodo"]').val(data['anio']);
                $('#ccppDialog input[name="Pec_Cod"]').val(data['peccod']);
                $('#anio-ccpp').html(data['anio']);
                $("#list_proveedores").Search("#formCcpp", "deudor");
                $("#list_clientes").Search("#formCcpp", "acreedor");
                $.Search('ccpp');                
            });

            $('#selectPecCod-sri').on('change', function() {
                var data = $(this).find('option:selected').data();
                $('#formSri input[name="Pla_Cod"]').val(data['placod']);
                $('#formSri input[name="Pec_Cod"]').val(data['peccod']);
                $('#sriCuenDialog input[name="periodo"]').val(data['anio']);
                $('#sriCuenDialog input[name="Pec_Cod"]').val(data['peccod']);
                $('#sriCuenDialog input[name="Pla_Cod"]').val(data['placod']);
                $('#anio-sri').html(data['anio']);
                $("#sri_compras").jqGrid('setGridParam', {datatype: 'json', postData: {Ren_Cod: $('#Ren_Cod_Sri').val(), Pla_Cod: data['placod'], listTipo: 'C'}}).trigger("reloadGrid", [{ page: 1 }]);
                $("#sri_ventas").jqGrid('setGridParam', {datatype: 'json', postData: {Ren_Cod: $('#Ren_Cod_Sri').val(), Pla_Cod: data['placod'], listTipo: 'V'}}).trigger("reloadGrid", [{ page: 1 }]);            
                $.Search('sriCuen');                
            });

            // ===== EVENTOS TABS =====
            // Función para redimensionar todos los grids visibles
            function redimensionarGridsVisibles() {
                $('.ui-jqgrid-btable:visible').each(function() {
                    var gridId = $(this).attr('id');
                    if (gridId) {
                        var $grid = $('#' + gridId);
                        if ($grid.length) {
                            var parentWidth = $grid.closest('.ui-jqgrid').parent().width();
                            if (parentWidth > 0) {
                                $grid.jqGrid('setGridWidth', parentWidth, true);
                            }
                        }
                    }
                });
            }

            // Función para forzar redimensionamiento de grids específicos
            function redimensionarGridsEnTab(tabId) {
                var grids = [];
                if (tabId === 'tab-iva') {
                    grids = ['#list_iva_cobrado', '#list_iva_pagado'];
                } else if (tabId === 'tab-ccpp') {
                    grids = ['#list_proveedores', '#list_clientes'];
                } else if (tabId === 'tab-balances') {
                    grids = ['#grid_planes', '#grid_tipos_balance'];
                } else if (tabId === 'tab-sri') {
                    grids = ['#list_sri', '#sri_compras', '#sri_ventas'];
                } else if (tabId === 'tab-bancos') {
                    grids = ['#grid_bancos'];
                }
                
                grids.forEach(function(gridId) {
                    var $grid = $(gridId);
                    if ($grid.length && $grid.closest('.ui-jqgrid').parent().width() > 0) {
                        try {
                            $grid.jqGrid('setGridWidth', $grid.closest('.ui-jqgrid').parent().width(), true);
                        } catch(e) {}
                    }
                });
            }

            // Evento para tabs principales
            $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var targetId = $(e.target).attr('href').replace('#', '');
                
                // Cargar datos solo la primera vez que se abre la pestaña
                if (!tabsLoaded['targetId']) {
                    if (targetId === 'tab-iva') {
                        // Cargar datos de IVA
                        $('#list_iva_cobrado').jqGrid('setGridParam', {datatype: 'json'}).trigger('reloadGrid');
                        $('#list_iva_pagado').jqGrid('setGridParam', {datatype: 'json'}).trigger('reloadGrid');
                    } else if (targetId === 'tab-ccpp') {
                        // Cargar datos de Proveedores/Clientes
                        $('#list_proveedores').jqGrid('setGridParam', {datatype: 'json'}).trigger('reloadGrid');
                        $('#list_clientes').jqGrid('setGridParam', {datatype: 'json'}).trigger('reloadGrid');
                    } else if (targetId === 'tab-balances') {
                        // Cargar datos de Balances
                        $('#grid_planes').jqGrid('setGridParam', {datatype: 'json'}).trigger('reloadGrid');
                    } else if (targetId === 'tab-sri') {
                        // Cargar datos de SRI
                        $('#list_sri').jqGrid('setGridParam', {datatype: 'json'}).trigger('reloadGrid');
                    } else if (targetId === 'tab-bancos') {
                        // Cargar datos de Bancos
                        $('#grid_bancos').jqGrid('setGridParam', {datatype: 'json', postData: {bancosAjax: true}}).trigger('reloadGrid');
                    }
                    tabsLoaded['targetId'] = true;
                }
                
                setTimeout(function() {
                    $(window).trigger('resize');
                    redimensionarGridsEnTab(targetId);
                    redimensionarGridsVisibles();
                }, 100);
                // Segundo intento para asegurar
                setTimeout(function() {
                    redimensionarGridsEnTab(targetId);
                }, 300);
            });

            // Evento para sub-tabs de Balances
            $('#subTabsBalances a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var target = $(e.target).attr('href');
                if (target === '#subtab-utilidad') {
                    cargarUtilidades();
                }
                setTimeout(function() {
                    redimensionarGridsVisibles();
                }, 150);
            });

            // Forzar redimensionamiento inicial después de cargar
            setTimeout(function() {
                $(window).trigger('resize');
                redimensionarGridsVisibles();
            }, 500);
            
            // Cargar la pestaña activa inicial
            var initialTab = $('.nav-tabs li.active a').attr('href');
            if (initialTab) {
                var initialTabId = initialTab.replace('#', '');
                if (initialTabId === 'tab-iva') {
                    $('#list_iva_cobrado').jqGrid('setGridParam', {datatype: 'json'}).trigger('reloadGrid');
                    $('#list_iva_pagado').jqGrid('setGridParam', {datatype: 'json'}).trigger('reloadGrid');
                    tabsLoaded['tab-iva'] = true;
                }
            }
            
            // Redimensionar cuando la ventana cambia de tamaño
            $(window).on('resize', function() {
                redimensionarGridsVisibles();
            });
        });
    </script>
</BODY>
</HTML>
