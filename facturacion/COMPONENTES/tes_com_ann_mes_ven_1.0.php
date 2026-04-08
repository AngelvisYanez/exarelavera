<?Php 
/**
* Componente que realiza los siguiente
* Año	= 	Selecciona y agrupa los años de las facturas de ventas del punto de impresión, segun el usuario
* Mes	=	Los 12 meses 
*/

/**
* Carga los años 
*/
$row_rs_anios = $obBD_con1->getArrayConsulta(245, $Pun_Cod, $obBD_conexion);	
$total_row_rs_anios=(isset($row_rs_anios['Anio']) && $row_rs_anios['Anio'] > 0)? 1 : 0;
if($total_row_rs_anios==0)
{
	$row_rs_anios = $obBD_con1->getArrayConsulta(1236, '', $obBD_conexion);	
}

/** 
* Control para mantener seteada la información seleccionada 
*/
if (isset($cmb_mes))
{
	$exp_mes = explode('=',$cmb_mes);	
	$mes = $exp_mes[1];
}//FIn del if (isset($cmb_mes))

if (isset($cmb_anio))
{
	$anio = $cmb_anio;
}//FIn del if (isset($cmb_mes))
?>
<table width="50%" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td width="5%" class="Etiqueta1">A&ntilde;o:&nbsp;</td>
    <td width="10%" class="LetraNegra">
      <select name="cmb_anio" id="cmb_anio">
        <?php 
		foreach ($row_rs_anios as $row)
		{
		?>
        <option <?php if (isset($anio) && $anio == $row['Anio']){ echo "selected"; } ?> value="<?php echo $row['Anio']; ?>"><?php echo $row['Anio']; ?></option>
        <?Php
		}//FIn del foreach -> $row_rs_anios
		?>
      </select>    </td>
    <td width="5%" class="Etiqueta1">&nbsp;&nbsp;Mes:&nbsp;</td>
    <td width="10%" class="LetraNegra">
      <select name="cmb_mes" id="cmb_mes">
        <option value=""><< TODOS >></option>
        <?Php
				  for ($i=1;$i<=12;$i++)
				  { ?>
        <option <?php if ($i == $mes){ echo "selected"; } ?> value="<?Php echo "AND MONTH(Caj_Fec)=$i"; ?>"><?php echo mes($i, 1) ?></option>
        <?Php
				  } ?>
      </select>
    </td>
  </tr>
</table>