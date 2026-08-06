<?Php
/* 
Alias:	-
Descripción: Cargar el menu-drop down del sistema informático
Fecha de actualización:	2010-10-19
Desarrollador:	Lewis Chimarro 
*/
require_once('../LOGICA/logica.php');


/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Adm($_SESSION['Ses_Dat_Dis']);
/* Cracion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Adm; 	  


///Recorrido de la variable de sesion de los perfiles de usuario
foreach($_SESSION['Ses_Lis_Per'] as $item)
{
	$mperf=$mperf." "."perfiorgan.Per_Cod=".$item." OR";
}			

$sql_0 ="SELECT DISTINCT 
  organizado.Org_Det,
  organizado.Org_Des,
  organizado.Org_Niv,
  organizado.Org_Cod,
  organizado.Org_Img,
  organizado.Org_Ime
FROM
  organizado
WHERE
  organizado.Org_Cod IN (
	SELECT DISTINCT organizado.Org_Niv
	FROM organizado WHERE
  	organizado.Org_Cod IN 
  	(SELECT organizado.Org_Cod FROM organizado INNER JOIN procesos ON (organizado.Org_Cod = 
  	procesos.Org_Cod) INNER JOIN perfiorgan ON (procesos.Pcs_Cod = perfiorgan.Pcs_Cod) 
  	WHERE perfiorgan.Per_Cod = 11)
ORDER BY
  organizado.Org_Ord)";	
///Cargado de Menús Principales. Ej: Gestión Administrativa, Gestión de Periodos los que tiene Org_Niv=0
$rs_organizado_cero = $obBD_con1->consulta($sql_0, $obBD_conexion->conexion);
$total_rs_organizado_cero = $obBD_con1->numregistros();
?>
<!-- Beginning of compulsory code below -->
<ul id="nav" class="dropdown dropdown-linear dropdown-columnar">
<li><a href="./">Home</a></li>
<?Php	
///Recorrido de los nodos de nivel cero
while($v0 = $obBD_con1->fetch_array($rs_organizado_cero))
{ ?>
	<li class="dir" style="cursor:pointer"><?Php echo ($v0['Org_Des']); ?>
	<ul>
<?Php
	$nodo = $v0['Org_Cod'];
	$sql_1="SELECT DISTINCT  organizado.Org_Det,
  organizado.Org_Des,
  organizado.Org_Niv,
  organizado.Org_Cod,
  organizado.Org_Img,
  organizado.Org_Ime FROM organizado WHERE organizado.Org_Niv =  $nodo AND
organizado.Org_Cod IN (SELECT organizado.Org_Cod FROM organizado INNER JOIN procesos 
ON (organizado.Org_Cod = procesos.Org_Cod) INNER JOIN perfiorgan ON (procesos.Pcs_Cod 
= perfiorgan.Pcs_Cod) WHERE perfiorgan.Per_Cod = 11) ORDER BY organizado.Org_Ord";

	/* Consulta los organizados de nivel 1 */
	$rs_organizado_uno = $obBD_con1->consulta($sql_1, $obBD_conexion->conexion);
	$total_rs_organizado_uno = $obBD_con1->numregistros();
	while($v1 = $obBD_con1->fetch_array($rs_organizado_uno))
	{ 
	?>
			<li class="dir"><?Php echo $v1['Org_Des']; ?>
				<ul>
                <?Php
				$nodo = $v1['Org_Cod'];
				$sql_p = "SELECT DISTINCT procesos.Pcs_Lin, rutas.Rut_Des,
procesos.Pcs_Nom, procesos.Pcs_Img, procesos.Pcs_Det
FROM
  rutas
  INNER JOIN procesos ON (rutas.Rut_Cod = procesos.Rut_Cod)
  INNER JOIN perfiorgan ON (procesos.Pcs_Cod = perfiorgan.Pcs_Cod)
WHERE
procesos.Pcs_Est='A' AND procesos.Pcs_Tip = 'P'
AND procesos.Org_Cod=$nodo AND perfiorgan.Per_Cod = 11
ORDER BY procesos.Pcs_Ord";
				/* Consulta los procesos del usuario */
				$rs_procesos = $obBD_con1->consulta($sql_p,  $obBD_conexion->conexion);
				$total_rs_procesos = $obBD_con1->numregistros();
		
				while($vp = $obBD_con1->fetch_array($rs_procesos))
				{  							
					$url=$vp["Rut_Des"].$vp["Pcs_Nom"];
				?>
					<li><a href="<?Php echo $url; ?>" target="contenido"><?Php echo $vp['Pcs_Lin']?></a></li>
                <?Php
				}
				?>
				</ul>
			</li>
		
  <?Php   			
	} ?>
	</ul></li>
   <?Php
}//Fin del while($v0= $obBD_con1->fetch_array($rs_organizado_cero))
?>
</ul>
<!-- / END -->

<?Php	

/* Liberacion de los cursores de la base de datos */
@$obBD_con1->free_result($rs_organizado_cero);
@$obBD_con1->free_result($rs_organizado_uno);
@$obBD_con1->free_result($rs_procesos);
@$obBD_con1->liberar();
?>