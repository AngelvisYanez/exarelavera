<?Php
require_once('../LOGICA/logica.php');    
/* componente ajax que despliega en pantalla un mensaje de alerta, 
si el documento de compra ya se encuentra registrado en la base de datos */
if($ajax_con_numcom==1)
{	
  $Ins_Mod=$Ins_Mod."'".$Cop_Bus."'";
  /* consulto si para el provedor ya se registro la factura de compra */
  if(trim(Cop_Num)!="" && trim($Prv_Cod)!="" && trim($Tic_Cod)!="")
  {	 $rs_existe_factura = $obBD_con1->consulta(sentencias_tes(334, $obBD_con1->parametros($Cop_Num.'*'.$Prv_Cod.'*'.$Tic_Cod.'*'.$Ins_Mod)), $obBD_conexion->conexion);
	 $row_rs_existe_factura = $obBD_con1->registros();
	 $total_rs_existe_factura = $obBD_con1->numregistros();
	   if($total_rs_existe_factura>0)
	   { /* muestra el mesaje de error */
	     ?><span class="Alertas3"><img src="../../mascaras/model1/imagenes/32x32/gtk-no.gif" width="20" height="20" type="image"  />
	  	 <?Php echo "El documento ya se encuentra registrado."; ?></span><?Php
	   }
   }
   @$obBD_con1->free_result($rs_existe_factura);
   exit();
}

?>