<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php

if (isset($_POST['save']))
{   
    	include '../../Librerias/PHPMail/PHPMail.php';
		
			
		$obj = new PHPMail();
		
		$dest = array();
		$dest[] = array('Correo'=>trim('josecum_3@hotmail.com'),'Nombre'=>strtoupper('Jose Cumbicos'));
		
		$copias = '';
		$msgHtml = '
		<html xmlns="http://www.w3.org/1999/xhtml">
		
		 <style type="text/css">		 				
			.texto_encabezado{
				font-family:Arial, Helvetica, sans-serif;
				font-size:20px;
				color:#3C753F;
				font-style: normal;			
				font-weight: bold;
			}
			.texto_negrita{
				font-family: Verdana, Geneva, sans-serif;
				font-size:30px;
				color:#3C753F;
				font-weight: bold;
				font-style: normal;
				font-variant:normal;
			}
			.texto_pie{
				font-family:Verdana, Geneva, sans-serif;
				font-size: 10px;								
				font-style: normal;
				font-variant:normal;
				color:#666;
			}
			.texto{
				font-family: tahoma, new york, times, serif;
				font-size: 12px;
				color:#333;
			}
			.texto_titulo{
				font-family: Verdana, Geneva, sans-serif;
				font-size: 14px;
				color:#666;
			}
			.dos a {
				font-family: Tahoma, Geneva, sans-serif;
				font-size: 14px;
				
				background-color: #060;
				text-decoration: none;
				color: #FFF;
				border: 1px solid #0F0;	
			}
			.dos a:hover {
				font-family: Tahoma, Geneva, sans-serif;
				font-size: 14px;
				
				background-color: #090;
				text-decoration: none;
				color: #FFF;
				border: 1px solid #0F0;
			}  			
		 </style>		 
		 <meta http-equiv="accion" content="5;url=http://exa.ofsercont.com/index.php">
		</head>
		
		<body>
		<form name="form1" method="post" target="_new" action="http://exa.ofsercont.com/index.php">	
		<table width="597" border="0" cellpadding="0" cellspacing="0" align="center">
		  <tr>
			<td width="597" height="119" valign="top" background="http://exa.ofsercont.com/mascaras/model1/imagenes/128x128/banner_mail.png"><table width="100%" height="64" border="0" cellpadding="0" cellspacing="0">
			  <tr>
			    <td width="13%" height="45">&nbsp;</td>
			    <td colspan="2" align="left" ><span class="texto_negrita">F</span><span class="texto_encabezado">acturaci&oacute;n</span> <span class="texto_negrita">E</span><span class="texto_encabezado">lectr&oacute;nica</span></td>
			    <td width="4%">&nbsp;</td>
		      </tr>
			  <tr>
			    <td height="19">&nbsp;</td>
			    <td width="37%">&nbsp;</td>
			    <td width="46%">&nbsp;</td>
			    <td>&nbsp;</td>
		      </tr>
		    </table></td>
		  </tr>
		  <tr>
			<td valign="top" bgcolor="#D8E7C3"><table width="100%" border="0" cellspacing="0" cellpadding="0">
			  <tr>
			    <td height="9">&nbsp;</td>
			    <td colspan="2">&nbsp;</td>
			    <td>&nbsp;</td>
		      </tr>
			  <tr>
			    <td width="2%" height="9">&nbsp;</td>
			    <td colspan="2" class="texto_titulo"><strong>AGRONUEVO S.A.</strong></td>
			    <td width="2%">&nbsp;</td>
		      </tr>
			  <tr>
			    <td height="47">&nbsp;</td>
			    <td colspan="2" class="texto">Ha generado el siguente comprobante electr&oacute;nico a, MORAN JUMBO KERLY VERONICA con cedula 0704187673.<br><br>
		        <strong>Tipo:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </strong>Factura <br>
		        <strong>Fecha de Emisi&oacute;n:&nbsp;</strong>16 de Junio 2015<br>
		        <strong>Secuencia:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</strong>001-001-0000012345<br>
		        <strong>Clave de Acceso:&nbsp;&nbsp;</strong> 12365474896521455223256554123658965236541785632145<br>
                </td>
			    <td width="2%">&nbsp;</td>
		      </tr>
			  <tr>
			    <td height="1">&nbsp;</td>
			    <td width="18%" class="texto">&nbsp;</td>
			    <td width="78%" class="texto">&nbsp;</td>
			    <td width="2%">&nbsp;</td>
		      </tr>
		    </table></td>
		  </tr>
		  <tr>
		    <td height="19" valign="top" bgcolor="#D8E7C3">&nbsp;</td>
	      </tr>
		  <tr>
			<td valign="top" bgcolor="#D8E7C3"><table width="100%" border="0" cellspacing="0" cellpadding="0">
			  <tr>
			    <td width="2%" height="63" class="texto">&nbsp;</td>
			    <td width="96%" class="texto">Para descargar su Comprobante Electronico debe seguir los siguentes pasos:<br>
			      <strong>Paso 1:</strong> Click en el boton <strong>Siguiente</strong><br>
			      <strong>Paso 2:</strong> Ingresar el usuario(Cedula/R.u.c) y contrase&ntilde;a(Cedula/R.u.c)<br>
			      <strong>Paso 3:</strong> Click en el boton <strong>Entrar</strong></td>
			    <td width="2%">&nbsp;</td>
		      </tr>
		    </table></td>
		</tr>
		<tr>
			<td valign="top" bgcolor="#D8E7C3"><p>&nbsp;</p></td>
		  </tr>
		<tr>
			<td align="center" bgcolor="#D8E7C3"><div class="dos"><a target="_new" href="http://exa.ofsercont.com/">&nbsp;Siguiente&nbsp;</a></div>			
			<!--<font size="4" face="tahoma, new york, times, serif">
				<button type="submit" id="accion" name="accion" class="button pequeno azul" title="Siguiente"><span>&nbsp;&nbsp;Siguiente&nbsp;&nbsp;</span></button>								
			</font>-->
			</td>
		  </tr>
		  <tr>
			<td height="19" valign="top" bgcolor="#D8E7C3">&nbsp;</td>
		  </tr>
		  <tr>
			<td valign="top" bgcolor="#D8E7C3">&nbsp;</td>
		  </tr>
		  <tr>
			<td height="47" valign="top" bgcolor="#D8E7C3"><p>&nbsp;</p></td>
		  </tr>
		  <tr>
		    <td align="center" valign="top" bgcolor="#D8E7C3" class="texto_pie"><strong>Ofsercont S.A.</strong></td>
	      </tr>
		  <tr>
		    <td align="center" valign="top" bgcolor="#D8E7C3" class="texto_pie">administracion@ofsercont.com</td>
	      </tr>
		  <tr>
		    <td align="center" valign="top" bgcolor="#D8E7C3" class="texto_pie"><strong>Telf:</strong> 2980779 &nbsp;&nbsp; <strong>Cel:</strong>0993814444</td>
	      </tr>
		  <tr>
		    <td align="center" valign="top" bgcolor="#D8E7C3"><span class="texto_pie"><strong>Direcci&oacute;n:</strong> Cdla. La Aurora calle ceibos e./3era y 4ta este</span></td>
	      </tr>
		  <tr>
			<td align="center" valign="top" bgcolor="#D8E7C3"><p class="texto_pie">Machala-El Oro</p></td>
		  </tr>
		   <tr>
			<td height="56" valign="top" bgcolor="#D8E7C3"><table width="100%" border="0" cellspacing="0" cellpadding="0">
			  <tr>
			    <td height="55" colspan="3" background="http://exa.ofsercont.com/mascaras/model1/imagenes/128x128/banner_pie.png">&nbsp;</td>
			    <td width="23%">&nbsp;</td>
		      </tr>
		     </table></td>
		  </tr>
		</table>
		</form>
		</body>
		</html>';
		
		if($obj->enviar($dest, 'Facturaci�n Electr�nica',$msgHtml , array(), '', $dest)){
			echo "<script type='text/javascript'>alert('Correo enviado correctamente');</script>";
		}
}
?>

<HTML xmlns="http://www.w3.org/1999/xhtml">
	<HEAD>
		<TITLE>Correo</TITLE>		
		<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?> 
        <script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
        <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">       
	</HEAD>
<BODY>
<form method="post" name= "form1" action="<?php echo $_SERVER['PHP_SELF'];?>">
  <input type="button" name="hdd_save" id="hdd_save" onclick="this.form.submit();" value="Enviar">
  <input type="hidden" id="save" name="save" value="1"  />
</form>
</BODY>
</HTML>