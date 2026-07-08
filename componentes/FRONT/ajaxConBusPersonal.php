<?php
/**
* Ajax que permite buscar una persona para el semestre
* Fecha de actulización:  2014-Abr-19
*/
require_once('../../componentes/LOGICA/logica.php');

if (isset($ajax_perDocente))
{		
	if($opc=='d')
	{
		/**
		* Consultar por Apellido 
		*/
		$rs_buscarPers = $obBD_con1->getArrayConsulta(206,trim($ref), $obBD_conexion);
		$total_rs_buscarPers = count($rs_buscarPers);
	}else{		
		/**
		* Consultar por Cedula 
		*/
		$rs_buscarPers = $obBD_con1->getArrayConsulta(205,trim($ref), $obBD_conexion);
		$total_rs_buscarPers = count($rs_buscarPers);
	}//Fin del if($opc=='d')
	
?>
	&nbsp;
	<FIELDSET>
    <LEGEND>
    <label class="Titulos2">Resultados de la busqueda</label>
    </LEGEND>
    <table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader01">
    <thead>
      <tr>
        <th align="center" width="2%">C&oacute;d. Int.</th>
        <th align="center" width="6%">C&eacute;dula/R.U.C.</th>
        <th align="center" width="41%">Personal</th>                
		<th width="2%">&nbsp;</th>
      </tr>
     <thead> 
     <tbody>
   <?php if($total_rs_buscarPers!=0)
	  {
	  foreach($rs_buscarPers as $row_rs_buscarPers)
	  {?>
      <tr>
        <td height="25" align="center"><?php echo $row_rs_buscarPers['Per_Cod']?></td>        
        <td align="left">&nbsp;<?php echo $row_rs_buscarPers['Prs_Ced']?></td>
        <td align="left">&nbsp;<?php echo $row_rs_buscarPers['Prs_Ape'].' '.$row_rs_buscarPers['Prs_Nom']?></td>		
        <td align="center">
		<img src="../../mascaras/model1/imagenes/32x32/forward.png" name="imgBusca" id="imgBusca" style="cursor:pointer" width="18" height="18" onclick="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_uploadDocente=1&cad=<?php echo $row_rs_buscarPers['Per_Cod'].'*'.$row_rs_buscarPers['Prs_Ape'].' '.$row_rs_buscarPers['Prs_Nom'];?>','div_respSem')" title="Elegir">		
		</td>
      </tr>  
	  <?php }//Fin del $row_rs_buscarPers 
	  }else{?>
	  <tr>
        <td height="25" align="center">&nbsp;</td>
        <td height="25" align="center">&nbsp;</td>
        <td height="25" align="center"><?Php echo error_alerta("¡No hay resultados que mostrar!", 1) ?></td>
        <td height="25" align="center">&nbsp;</td>        
	  </tr>
	  <?php }?>
      </tbody>    
    </table>
</FIELDSET>	
<?php
exit();
}

if (isset($ajax_uploadDocente))
{	
	$inf= explode("*",$cad);
?>	
	<input name="Resp" type="text" id="Resp" size="50" maxlength="50" value="<?php echo $inf[1];?>" readonly="">
	<input type="hidden" id="Per_Doc" name="Per_Doc" value="<?php echo $inf[0];?>">
    <img src="../../mascaras/model1/imagenes/32x32/eliminar.jpg" width="16" height="16" style="cursor:pointer" onClick="document.getElementById('Resp').value=''; document.getElementById('Per_Doc').value='';" title="Quitar docente">
<?php
exit();
}
?>