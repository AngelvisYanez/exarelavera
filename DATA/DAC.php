<?php
/***************************************/
/* Inicializa la Sesion en las paginas */
if(session_id() === '') session_start();
/***************************************/
/***************************************/
#[AllowDynamicProperties]
class base_mysql{
/* P R O P I E D A D E S */
/*************************/
/* variables de conexion */
public $BaseDatos;
public $Servidor;
public $Usuario;
public $Clave;
/* identificador de conexion y consulta */
public $conexion = 0;
public $rs_cargar = 0;
/* numero de error y texto error */
public $Errno = 0;
public $Error = "";
/**************************/
/**************************/
/* M E T O D O S */
/*****************/
/* Metodo Constructor */
function base_mysql($bd = "macros", $host = "localhost", $user = "userMacros", $pass = "lynxsc6"){
	$this->BaseDatos = $bd;
	$this->Servidor = $host;
	$this->Usuario = $user;
	$this->Clave = $pass;
	$this->conectar();
}
/* Conexion a la base de datos */
function conectar(){
	$this->conexion = mysqli_connect($this->Servidor, $this->Usuario, $this->Clave,$this->BaseDatos);
	if (!$this->conexion) {
		$this->Error = "Ha fallado la conexion.";
		return 0;
	}
	if (!@mysqli_select_db($this->conexion,$this->BaseDatos)) {
		$this->Error = "Imposible abrir " . $this->BaseDatos;
		return 0;
	}
	return $this->conexion;
}
/* Cierra la conexion */
function cerrar(){
	return @mysqli_close($this->conexion);
}
/* Libera la memoria ram de los datos cargados */
function liberar(){
	return @mysqli_free_result($this->rs_cargar);
}
/* Ejecuta un consulta */
function consulta($sql = ""){
	if ($sql == "") {
		$this->Error = "No ha especificado una consulta SQL";
		return 0;
	}
	$this->rs_cargar = @mysqli_query($this->conexion,$sql);
	if (!$this->rs_cargar) {
		$this->Errno = @mysqli_errno($this->conexion);
		$this->Error = @mysqli_error($this->conexion);
	}
	$this->cerrar();
	return $this->rs_cargar;
}
/* Crea un arreglo en base a los parametros concatenados */
function parametros($param){
	$Par_Sql=explode('*',$param);
	return $Par_Sql;
}
/* Devuelve el numero de campos de una consulta */
function numcampos() {
	if (!($this->rs_cargar instanceof mysqli_result)) return 0;
	return mysqli_num_fields($this->rs_cargar);
}
/* Devuelve el numero de registros de una consulta */
function numregistros(){
	if (!($this->rs_cargar instanceof mysqli_result)) return 0;
	return @mysqli_num_rows($this->rs_cargar);
}
/* Devuelve una matriz con los datos consultados */
function arregloregistros(){
	if (!($this->rs_cargar instanceof mysqli_result)) return false;
	return @mysqli_fetch_assoc($this->rs_cargar);
}
/* Devuelve el ultimo codigo generado en una conexion */
function insercionid(){
	return mysqli_insert_id($this->conexion);
}
/* Devuelve el nombre de un campo de una consulta */
function nombrecampo($numcampo) {
	if (!($this->rs_cargar instanceof mysqli_result)) return null;
	$finfo = mysqli_fetch_field_direct($this->rs_cargar, $numcampo);
	return $finfo ? $finfo->name : null;
}
/* Muestra los datos de una consulta */
function verconsulta() {
	echo "<table border=1>\n";
	for ($i = 0; $i < $this->numcampos(); $i++){
		echo "<td><b>" . $this->nombrecampo($i) . "</b></td>\n";
	}
	echo "</tr>\n";
	while ($row = mysqli_fetch_row($this->rs_cargar)) {
		echo "<tr> \n";
		for ($i = 0; $i < $this->numcampos(); $i++){
		echo "<td>" . $row[$i] . "</td>\n";
		}
		echo "</tr>\n";
	}
}
/* Graba un registro utilizando transacciones */
function grabaru($sql = "")
	{
		if ($sql == "")
		{
			$this->Error = "No ha especificado una consulta SQL";
			return 0;
		}
		mysqli_query($this->conexion,"BEGIN");
		$this->rs_save = @mysqli_query($this->conexion,$sql);
		if (mysqli_errno($this->conexion) == 0) {
			mysqli_commit($this->conexion);
		?>
			<script type="text/javascript">
				alert ("La transaccion se ha realizado con exito");
			</script>
		<?php
		}
		else
		{
		    mysqli_rollback($this->conexion);
		?>
			<script type="text/javascript">
			alert ("< < < A l e r ta !!!: NO se ha podido completar con exito la transaccion > > >");
			</script>
		<?php
		}
		$this->cerrar();
	}
/* Abre la transaccion */
function inicio_trans()
	{
		mysqli_autocommit($this->conexion, FALSE);
		mysqli_query($this->conexion,"BEGIN");
		return $this->conexion;
	}
/* Graba varios registros */
function grabarv($sql = "",$conectar2)
	{
		return mysqli_query($conectar2,$sql);
	}
/* Cierra la transaccion */
function fin_trans($conectar2)
	{
		if ($_SESSION['Error']!=1) {
			mysqli_commit($conectar2);
?>
			<script type="text/javascript">
				alert ("La transaccion se ha realizado con exito");
			</script>
<?php
		}
		else
		{
			mysqli_rollback($conectar2);
?>
			<script type="text/javascript">
			alert ("< < < A l e r ta !!!: NO se ha podido completar con exito la transaccion > > >");
			</script>
<?php
		}
	$this->cerrar();
	unset($_SESSION['Error']);
	}
/******************************************/
/******************************************/
/******************************************/
/******************************************/
/* Mejoras en la transaccion */
/* Abre la transaccion */
function inicio_transaccion()
	{
		mysqli_autocommit($this->conexion, FALSE);
		mysqli_query($this->conexion,"BEGIN");
	}
/* Graba varios registros en una transaccion */
function grabarv_registros($sql = "")
	{
		return mysqli_query($this->conexion,$sql);
	}
/* Cierra la transaccion */
function fin_transaccion()
	{
		if (mysqli_errno($this->conexion) == 0)
		{
			mysqli_commit($this->conexion);
			?>
			<script type="text/javascript">
				alert ("La transaccion se ha realizado con exito");
			</script>
			<?php
		}
		else
		{
			mysqli_rollback($this->conexion);
			?>
			<script type="text/javascript">
			alert ("< < < A l e r ta !!!: NO se ha podido completar con exito la transaccion > > >");
			</script>
			<?php
		}
		$this->cerrar();
	}
/******************************************/
/******************************************/
/******************************************/
/******************************************/
/* Ejecuta un consulta de varias transacciones */
function consultav($sql = "", $conectar2)
	{
	 return $this->rs_cargar = mysqli_query($conectar2,$sql);
	}
} //fin de la Clase bdd_mysql
/*
*/
/*****************************************/
/*****************************************/
/*      Clase para conexion con MySql    */
/*****************************************/
/*****************************************/
#[\AllowDynamicProperties]
class Class_Mysql{
/* P R O P I E D A D E S */
/*************************/
/* variables de conexion */
public $BaseDatos;
public $Servidor;
public $Usuario;
public $Clave;
/* identificador de conexion y consulta */
public $conexion = 0;
/* numero de error y texto error */
public $Errno = 0;
public $Error = "";
/**************************/
/**************************/
/* M E T O D O S */
/*****************/
/* Metodo Constructor */
function Class_Mysql($bd = "macros", $host = "localhost", $user = "userMacros", $pass = "lynxsc6"){
	$this->BaseDatos = $bd;
	$this->Servidor = $host;
	$this->Usuario = $user;
	$this->Clave = $pass;
	$this->conectar();
}
/* Conexion a la base de datos */
function conectar(){
	$this->conexion = mysqli_connect($this->Servidor, $this->Usuario, $this->Clave,$this->BaseDatos);
	if (!$this->conexion) {
		$this->Error = "Ha fallado la conexion.";
		return 0;
	}
	if (!@mysqli_select_db($this->conexion,$this->BaseDatos)) {
		$this->Error = "Imposible abrir " . $this->BaseDatos;
		return 0;
	}
	return $this->conexion;
	}
/* Cierra la conexion */
function cerrar(){
	if ($this->conexion instanceof mysqli) {
		return @mysqli_close($this->conexion);
	}
	return false;
	}
}//Fin de clase Class_Conexion
#[\AllowDynamicProperties]
class Class_Datos{
/* P R O P I E D A D E S */
/*************************/
/* identificador de conexion y consulta */
public $rs_cargar = 0;
/* M E T O D O S */
/*****************/
/* Ejecuta un consulta */
function consulta($sql = "",$conexion){
	if ($sql == "") {
		$this->Error = "No ha especificado una consulta SQL";
		return 0;
	}
	if (!($conexion instanceof mysqli)) {
		$this->Error = "No hay conexion";
		return 0;
	}
	$this->rs_cargar = @mysqli_query($conexion,$sql);
	if (!$this->rs_cargar) {
		$this->Errno = @mysqli_errno($conexion);
		$this->Error = @mysqli_error($conexion);
	}
	return $this->rs_cargar;
}
/* Libera la memoria ram de los datos cargados */
function liberar(){
	return true;
}
/* Crea un arreglo en base a los parametros concatenados */
function parametros($param){
	$Par_Sql=explode('*',$param);
	return $Par_Sql;
}
/* Devuelve el numero de campos de una consulta */
function numcampos() {
	if (!($this->rs_cargar instanceof mysqli_result)) return 0;
	return mysqli_num_fields($this->rs_cargar);
}
/* Devuelve el numero de registros de una consulta */
function numregistros(){
	if (!($this->rs_cargar instanceof mysqli_result)) return 0;
	return @mysqli_num_rows($this->rs_cargar);
}
/* Devuelve una matriz con los datos consultados */
function registros(){
	if (!($this->rs_cargar instanceof mysqli_result)) return false;
	return @mysqli_fetch_assoc($this->rs_cargar);
}
/* Devuelve el ultimo codigo generado en una conexion */
function insercionid($conexion){
	return mysqli_insert_id($conexion);
}
/* Devuelve el nombre de un campo de una consulta */
function nombrecampo($numcampo) {
	if (!($this->rs_cargar instanceof mysqli_result)) return null;
	$finfo = mysqli_fetch_field_direct($this->rs_cargar, $numcampo);
	return $finfo ? $finfo->name : null;
}
/* Muestra los datos de una consulta */
function verconsulta() {
	echo "<table border=1>\n";
	for ($i = 0; $i < $this->numcampos(); $i++){
		echo "<td><b>" . $this->nombrecampo($i) . "</b></td>\n";
	}
	echo "</tr>\n";
	while ($row = mysqli_fetch_row($this->rs_cargar)) {
		echo "<tr> \n";
		for ($i = 0; $i < $this->numcampos(); $i++){
		echo "<td>" . $row[$i] . "</td>\n";
		}
		echo "</tr>\n";
	}
}
/* Graba un registro utilizando transacciones */
function grabaru($sql = "")
	{
		if ($sql == "")
		{
			$this->Error = "No ha especificado una consulta SQL";
			return 0;
		}
		mysqli_query($conexion,"BEGIN");
		$this->rs_save = @mysqli_query($conexion,$sql);
		if (mysqli_errno($conexion) == 0) {
			mysqli_commit($conexion);
		?>
			<script type="text/javascript">
				alert ("La transaccion se ha realizado con exito");
			</script>
		<?php
		}
		else
		{
		    mysqli_rollback($conexion);
		?>
			<script type="text/javascript">
			alert ("< < < A l e r ta !!!: NO se ha podido completar con exito la transaccion > > >");
			</script>
		<?php
		}
	}
/******************************************/
/******************************************/
/******************************************/
/******************************************/
/* Mejoras en la transaccion */
/* Abre la transaccion */
function inicio_transaccion($conexion)
	{
		mysqli_autocommit($conexion, FALSE);
		mysqli_query($conexion,"BEGIN");
	}
/* Graba varios registros en una transaccion */
function grabarv_registros($sql = "",$conexion)
	{
		return mysqli_query($conexion,$sql);
	}
/* Cierra la transaccion */
function fin_transaccion($conexion)
	{
		if (mysqli_errno($conexion) == 0)
		{
			mysqli_commit($conexion);
			?>
			<script type="text/javascript">
				alert ("La transaccion se ha realizado con exito");
			</script>
			<?php
		}
		else
		{
			mysqli_rollback($conexion);
			?>
			<script type="text/javascript">
			alert ("< < < A l e r ta !!!: NO se ha podido completar con exito la transaccion > > >");
			</script>
			<?php
		}
	}
/* Cierra la transaccion sin mensaje de alerta */
function fin_transaccion_nomsn($conexion)
	{
		if (mysqli_errno($conexion) == 0)
		{
			mysqli_commit($conexion);
		}
		else
		{
			mysqli_rollback($conexion);
		}
	}
/* Devuelve una matriz con los datos consultados en base a un rs */
function fetch_assoc($rs_consulta){
	if (!($rs_consulta instanceof mysqli_result)) return false;
	return @mysqli_fetch_assoc($rs_consulta);
}
/* Devuelve el total de datos consultados en base a un rs */
function num_rows($rs_consulta){
	if (!($rs_consulta instanceof mysqli_result)) return 0;
	return @mysqli_num_rows($rs_consulta);
}
/* libera un rs */
function free_result($rs_consulta){
	if (!($rs_consulta instanceof mysqli_result)) return false;
	return @mysqli_free_result($rs_consulta);
}
/* Mueve el apuntador de la consulta */
function data_seek($rs_consulta, $puntero){
	return @mysqli_data_seek ($rs_consulta, $puntero);
}
/* Devuelve un arreglo de una consulta */
function fetch_array($rs_consulta){
	if (!($rs_consulta instanceof mysqli_result)) return false;
	return @mysqli_fetch_array ($rs_consulta);
}
/******************************************/
/******************************************/
/******************************************/
/******************************************/
} //fin de la Clase Class_Datos()
/*****************************************/
/*****************************************/
/*   Clase para conexion con PostgreSql  */
/*****************************************/
/*****************************************/
#[\AllowDynamicProperties]
class Class_PostgreSql{
/* P R O P I E D A D E S */
/*************************/
/* variables de conexion */
public $BaseDatos;
public $Servidor;
public $Usuario;
public $Clave;
/* identificador de conexion y consulta */
public $conexion = 0;
/* numero de error y texto error */
public $Errno = 0;
public $Error = "";
/**************************/
/**************************/
/* M E T O D O S */
/*****************/
/* Metodo Constructor */
function Class_PostgreSql($bd ="ginus_bibli", $host="localhost", $user ="onlaCIdh", $pass="conspitw", $port ="5432")
{	$this->BaseDatos = $bd;
	$this->Servidor = $host;
	$this->Puerto = $port;
	$this->Usuario = $user;
	$this->Clave = $pass;
	$this->conectar();
}
/* Conexion a la base de datos */
function conectar(){
	$this->conexion = pg_connect("host=$this->Servidor port=$this->Puerto dbname=$this->BaseDatos user=$this->Usuario password=$this->Clave");
	if (!$this->conexion) {
		$this->Error = "Ha fallado la conexion.";
		return 0;
	}
	return $this->conexion;
}
/* Cierra la conexion */
function cerrar(){
	return @pg_close($this->conexion);
}
}//Fin de clase Class_PostgreSql
#[\AllowDynamicProperties]
class Class_Datos_PostgreSql{
/* P R O P I E D A D E S */
/*************************/
/* identificador de consulta y grabado */
public $rs_cargar = 0;
public $row_rs_cargar = 0;
public $rs_save = 0;
/* M E T O D O S */
/*****************/
/* Ejecuta un consulta */
function consulta($sql = "",$conexion){
	if ($sql == "") {
		$this->Error = "No ha especificado una consulta SQL";
		return 0;
	}
	$this->rs_cargar = @pg_query($conexion,$sql);
	if (!$this->rs_cargar) {
		$this->Error = @pg_last_error($conexion);
	}
	return $this->rs_cargar;
}
/* Libera la memoria ram de los datos cargados */
function liberar(){
	return @pg_free_result($this->rs_cargar);
}
/* Crea un arreglo en base a los parametros concatenados */
function parametros($param){
	$Par_Sql=explode('*',$param);
	return $Par_Sql;
}
/* Devuelve el numero de registros de una consulta */
function numregistros(){
	return @pg_num_rows($this->rs_cargar);
}
/* Devuelve una matriz con los datos consultados */
function registros(){
	return @pg_fetch_assoc($this->rs_cargar);
}
/* Devuelve el ultimo codigo generado en una conexion */
function insercionid($conexion, $sequence){
	$this->rs_cargar = @pg_query($conexion,"SELECT CURRVAL('" . $sequence . "_seq')");
	$this->row_rs_cargar = $this->registros();
	return $this->row_rs_cargar['currval'];
}
/* Graba un registro utilizando transacciones */
function grabaru($sql = "")
{
		if ($sql == "")
		{
			$this->Error = "No ha especificado una consulta SQL";
			return 0;
		}
		pg_query($conexion,"BEGIN;");
		$this->rs_save = @pg_query($conexion,$sql);
		if ($this->rs_save) {
			pg_commit($conexion);
		?>
			<script type="text/javascript">
				alert ("La transaccion se ha realizado con exito");
			</script>
		<?php
		}
		else
		{
		    pg_rollback($conexion);
		?>
			<script type="text/javascript">
			alert ("< < < A l e r ta !!!: NO se ha podido completar con exito la transaccion > > >");
			</script>
		<?php
		}
}
/* Abre la transaccion */
function inicio_transaccion($conexion)
{
		pg_query($conexion,"BEGIN;");
}
/* Graba varios registros en una transaccion */
function grabarv_registros($sql = "",$conexion)
{
		return $this->rs_save = @pg_query($conexion,$sql);
}
/* Cierra la transaccion */
function fin_transaccion($conexion)
{
	if ($this->rs_save)
	{
		pg_query($conexion,'COMMIT;');
		?>
		<script type="text/javascript">
			alert ("La transaccion se ha realizado con exito");
		</script>
		<?php
	}
	else
	{
		pg_query($conexion,'ROLLBACK;');
		?>
		<script type="text/javascript">
			alert ("< < < A l e r ta !!!: NO se ha podido completar con exito la transaccion > > >");
		</script>
		<?php
	}
}
/* Cierra la transaccion sin mensaje de alerta */
function fin_transaccion_nomsn($conexion)
{
	if ($this->rs_save)
	{
		pg_query($conexion,'COMMIT;');
	}
	else
	{
		pg_query($conexion,'ROLLBACK;');
	}
}
/* Devuelve una matriz con los datos consultados en base a un rs */
function fetch_assoc($rs_consulta){
	return @pg_fetch_assoc($rs_consulta);
}
/* Devuelve el total de datos consultados en base a un rs */
function num_rows($rs_consulta){
	return @pg_num_rows($rs_consulta);
}
/* libera un rs */
function free_result($rs_consulta){
	return @pg_free_result($rs_consulta);
}
} //fin de la Clase PostgreSql()
?>
