<?php
if (!is_object($obBD_con1) || !function_exists('latin1')) return;
///Inicialización del Menú - procesos
/* Conversion de caracteres php -> javascript */
$caracter = array("+", "%");
$trans=latin1($v2["Pcs_Det"]);
$band=$v2["Pcs_Lin"]." --- [".str_replace($caracter, " ", urlencode($trans))."]";
/*********************************************/
$url=$v2["Rut_Des"].$v2["Pcs_Nom"];
$icon2=isset($v2["Pcs_Img"])?$v2["Pcs_Img"]:'';
$expandedIcon='';
if(!isset($rc))$rc=0; $rc=$rc+1;
$valchk=$v2['Pcs_Cod'];
$total_rs_check=0;
if ($Com_Tipo == 'M')
{
	/* Consulta los procesos del usuario */
	$rs_check = $obBD_con1->consulta(sentencias_adm(26, $obBD_con1->parametros($codigo.'*'.$v2["Pcs_Cod"])), $obBD_conexion->conexion);
	$total_rs_check = $obBD_con1->numregistros();
}//Fin del if ($Com_Tipo == 'M')

if ($total_rs_check > 0)
{
	$estado="checked";
}
if ($v2["Pcs_Tip"] == 'P')
{
	$img_pag = '<img src="../LOGICA/images/proceso.png" width="20" height="18" title="Página de tipo Proceso">';
}
else
{
	$img_pag = '<img src="../LOGICA/images/reporte.png" width="20" height="18" title="Página de tipo Reporte">';						
}

/* Input para el check */	
$check="<font style='cursor:pointer'><input name=nomchk[".$rc."] type='checkbox' style='cursor:pointer' value='".$valchk."' ".(isset($estado)?$estado:'')."></font>";
//$check=$check.$v2["Tipo"].": ".mb_convert_encoding($v2["Pcs_Det"], 'ISO-8859-1', 'UTF-8');
?>