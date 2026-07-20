<?php
// require_once('../../../administrador/LOGICA/seguridad.php');
require_once(__DIR__.'/../../../Librerias/config.php/register_globals.php'); 
require_once($APP_REAL_PATH.'/compras/LOGICA/requisiciones/index.php');
require_once('../../../Librerias/procedimientos/almacenados_standar.php');


/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);

/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_Datos_Requisiciones;
$numeroRequisicionActual;
$tusItems ;
$busquedaProf;
//
if (isset($clieAjax)) {
    $responce = $obBD_con1->getPageGridJson('requisiciones.selectWhere', $_GET, $obBD_conexion);
}
//busqueda de requisiciones
if (isset($reqAjax)) {
    $responce = $obBD_con1->getPageGridJson('requisiciones.selectWhere', $_GET, $obBD_conexion);
    //$resp = $obBD_con1->getArrayConsultaSql("select * from requisiciones_det inner join requisiciones on requisiciones.Req_Cod=requisiciones_det.Req_Cod;", $obBD_conexion, true);
    $obBD_con1->echoLog($responce);
}

if(isset($detalleRequisiciones)){
    $resuladoDetalle=array(
        'success' => true,
        'detReqms'=>$obBD_con1->getArrayConsultaSql("SELECT requisiciones.Req_Cod,Pfd_Int,requisiciones_det.Pro_Cod,Req_Imp,Req_Cant,unidad.Uni_Cod,Uni_Des,Req_Pru,Req_Des,
        Req_Num,Req_Obs,item.Ite_Cod,adquisicio.Adq_Cod,Ite_Lar,Adq_Des,Adq_Cor,
        iva.Iva_Cod,Iva_Por, Req_Adi
      FROM
      requisiciones
        INNER JOIN requisiciones_det ON (requisiciones.Req_Cod = requisiciones_det.Req_Cod)
        INNER JOIN producto ON (requisiciones_det.Pro_Cod = producto.Pro_Cod)
        INNER JOIN iva ON (requisiciones_det.Iva_Cod = iva.Iva_Cod)
        INNER JOIN unidad ON (producto.Uni_Cod = unidad.Uni_Cod)
        INNER JOIN adquisicio ON (producto.Adq_Cod = adquisicio.Adq_Cod)
        INNER JOIN item ON (producto.Ite_Cod = item.Ite_Cod)
      WHERE requisiciones.Req_Cod='$Req_Cod';", $obBD_conexion),
      'numVentasAct'=>$obBD_con1->getArrayConsultaSql("SELECT  COUNT(*) AS total from ventas inner join cliente on cliente.Cli_Cod=ventas.Cli_Cod inner join persona on persona.Prs_Cod=cliente.Prs_Cod inner join caja_aper on ventas.Caj_Cod=caja_aper.Caj_Cod  where Req_Cod= '$Req_Cod';",$obBD_conexion),
    );
    $obBD_con1->echoJson($resuladoDetalle);
}

if (isset($cliSearchAjax)) {
    //ChromePhp::log("CLISEARCHAJAX");
    //$respuesta = $obBD_con1->getRowConsulta(1,  $Prs_Ced.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*', $obBD_conexion,true);
    //array('Cliente'=>"CONCAT(Prs_Nom,' ',Prs_Ape)",'Prs_Nom','Prs_Ape','Prs_Ced','Prs_Dir')
    $data = array_merge($_GET, array('setWhere' => array('byPerCod')));
    $respuesta = $obBD_con1->getPageGridJson('cliente.selectWhere', $_GET, $obBD_conexion);
    //$obBD_con1->echoLog($respuesta);


}

if (isset($proAjax)) {
    $productos = $obBD_con1->getPageGridJson('producto.selectWhere', $_GET, $obBD_conexion);
    //$obBD_con1->echoLog($productos);
}



if(isset($numRequisicionCliente)){
    $busquedaNum=array(
        'success'=>true,
        'numeroReq'=>$obBD_con1->getRowConsultaSql("SELECT IFNULL(MAX(Req_Num),0) AS total FROM requisiciones INNER JOIN cliente ON cliente.Cli_Cod=requisiciones.Cli_Cod INNER JOIN persona ON persona.Prs_Cod=cliente.Prs_Cod INNER JOIN sucursal ON cliente.Emp_Cod=sucursal.Emp_Cod WHERE (sucursal.Emp_Cod='$Ses_Emp_Cod');", $obBD_conexion),
    );
    $obBD_con1->echoJson($busquedaNum);

}
?>
