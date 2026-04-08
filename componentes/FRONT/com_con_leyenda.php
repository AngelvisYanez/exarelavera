<?Php
/* Componente que muestra leyendas 
   Desarrollador: Freddy Jumbo
   Fecha: 13 de Octubre del 2009
   
   com_leyenda[0] = Pagos vigentes
   com_leyenda[1] = Registro anulado
   com_leyenda[2] = Registro vencido
   com_leyenda[3] = Registro aprobado
   
*/ 
if (isset($com_leyenda))
{ 
?>

<fieldset>
<legend>
<label class="Titulos2">Leyenda:</label>
</legend>
<table width="420" cellpadding="0" cellspacing="0">  
    <?Php if(isset($com_leyenda)){ ?>
       <?Php if(isset($com_leyenda[0]) && $com_leyenda[0]>0){  /* if($com_leyenda[0]>0){ */ ?>
	   <tr>
             <td width="55" ><div align="center"><img src="../../mascaras/model1/imagenes/32x32/dinero.png" width="22" height="22"></div></td>
             <td width="71" bgcolor="#9CB8CF">&nbsp;</td>
             <td width="292" class="Cuerpo_ajax" align="center"><strong>Mantiene pagos vigentes </strong></td>
		</tr>	
        <?Php } /*fin del if($leyenda[0]>0 */ ?>
        <?Php if(isset($com_leyenda[1]) &&$com_leyenda[1]>0){  /*inicio if($com_leyenda[1]>0) */ ?>
        <tr>
             <td width="55">&nbsp;</td>
             <td width="71" bgcolor="#FF0000">&nbsp;</td>
             <td width="292" class="Cuerpo_ajax" align="center"><strong>Registro anulado</strong></td>
        </tr> <?Php }  /*fin del if($com_leyenda[1]>0) */ ?>
        <?Php if(isset($com_leyenda[2]) && $com_leyenda[2]>0){  /*inicio if($com_leyenda[2]>0) */ ?>
        <tr>
          <td width="55">&nbsp;</td>
          <td width="71" bgcolor="#FF9900">&nbsp;</td>
          <td width="292" class="Cuerpo_ajax" align="center"><strong>Registro vencido </strong></td>
        </tr>		
        <?Php } ?>   
        <?Php if(isset($com_leyenda[3]) && $com_leyenda[3]>0){  /*inicio if($com_leyenda[3]>0) */ ?>
        <tr>
          <td width="55">&nbsp;</td>
          <td width="71" bgcolor="#66FF33">&nbsp;</td>
          <td width="292" class="Cuerpo_ajax" align="center"><strong>Registro aprobado </strong></td>
        </tr>
        <tr>
          <td>&nbsp;</td>
          <td bgcolor="#66FF33">&nbsp;</td>
          <td class="Cuerpo_ajax" align="center">Campo Requerido</td>
        </tr>		
        <?Php } ?>   	
        <?Php if(isset($com_leyenda[4]) && $com_leyenda[4]>0){  /*inicio if($com_leyenda[4]>0) */ ?>
        <tr>
          <td width="55">&nbsp;</td>
          <td width="71" bgcolor="#FF0000">&nbsp;</td>
          <td width="292" class="Cuerpo_ajax" align="center"><strong>Manteniene registro(s) anulado(s)</strong></td>
        </tr>		
        <?Php } ?>  
    <?Php }     ?>
   </table>
</fieldset>
<?Php
}//Fin del if (isset($com_leyenda))
?>