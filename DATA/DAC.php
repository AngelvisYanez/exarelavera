<?php
/***************************************/
/* Inicializa la Sesion en las paginas */
if(session_id()==='') session_start();
/***************************************/
/***************************************/
class base_mysql{
/* P R O P I E D A D E S */
/*************************/
/* variables de conexi�n */
var $BaseDatos;
var $Servidor;
var $Usuario;
var $Clave;

/* identificador de conexi�n y consulta */
var $conexion = 0;
var $rs_cargar = 0;

/* n�mero de error y texto error */
var $Errno = 0;
var $Error = "";
/**************************/
/**************************/

/* M E T O D O S */
/*****************/
/* M�todo Constructor: Cada vez que creemos una variable
de esta clase, se ejecutar� esta funci�n */
function base_mysql($bd = "macros", $host = "localhost", $user = "userMacros", $pass = "lynxsc6"){
	$this->BaseDatos = $bd;
	$this->Servidor = $host;
	$this->Usuario = $user;
	$this->Clave = $pass;
	
	$this->conectar(/*$this->BaseDatos, $this->Servidor, $this->Usuario, $this->Clave*/);
}

/* Conexi�n a la base de datos */
function conectar(){
	// Conectamos al servidor
	$this->conexion = mysqli_connect($this->Servidor, $this->Usuario, $this->Clave,$this->BaseDatos);
	if (!$this->conexion) {
		$this->Error = "Ha fallado la conexi�n.";
		return 0;
	}

	/* Seleccionamos la base de datos */
	if (!@mysqli_select_db($this->conexion,$this->BaseDatos)) {
		$this->Error = "Imposible abrir ".$this->BaseDatos ;
		return 0;
	}

	/* Si hemos tenido �xito conectando devuelve 
	el identificador de la conexi�n, sino devuelve 0 */
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

	/*ejecutamos la consulta*/
	$this->rs_cargar = @mysqli_query($this->conexion,$sql);

	if (!$this->rs_cargar) {
		$this->Errno = @mysqli_errno($this->conexion);
		$this->Error = @mysqli_error($this->conexion);
	}

	/* Cierra la conexion */
	$this->cerrar();
	/* Si hemos tenido �xito en la consulta devuelve 
	el identificador de la conexi�n, sino devuelve 0 */	
	return $this->rs_cargar;
}

/* Crea un arrgleo en base a los parametros concatenados */
function parametros($param){
	$Par_Sql=explode('*',$param);
	return $Par_Sql;
}

/* Devuelve el n�mero de campos de una consulta */
function numcampos() {
	return mysqli_num_fields($this->rs_cargar);
}

/* Devuelve el n�mero de registros de una consulta */
function numregistros(){
	return @mysqli_num_rows($this->rs_cargar);
}

/* Desvuelve una matriz con los datos consultados */
function arregloregistros(){
	return @mysqli_fetch_assoc($this->rs_cargar);
}

/* Devuelve el ultimo codigo generado en una conexion */
function insercionid(){
	return mysqli_insert_id($this->conexion);
}


/* Devuelve el nombre de un campo de una consulta */
/* REVISAR ------------------------------------------------------- */
function nombrecampo($numcampo) {
	return mysqli_field_name($this->rs_cargar, $numcampo);
}

/* Muestra los datos de una consulta */
function verconsulta() {
	echo "<table border=1>\n";
	// mostramos los nombres de los campos
	for ($i = 0; $i < $this->numcampos(); $i++){
		echo "<td><b>".$this->nombrecampo($i)."</b></td>\n";
	}
	echo "</tr>\n";
	// mostrarmos los registros
	
	while ($row = mysqli_fetch_row($this->rs_cargar)) {
		echo "<tr> \n";
		for ($i = 0; $i < $this->numcampos(); $i++){
		echo "<td>".$row[$i]."</td>\n";
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
			<script LANGUAGE="JavaScript">	
				alert ("La transacción se ha realizado con éxito");
			</script>
		<?php
		}
		else
		{
		    mysqli_rollback($this->conexion);
		?>
			<script LANGUAGE="JavaScript">
			alert ("< < < ¡¡¡ A l e r ta !!!: NO se ha podido completar con éxito la transacción > > >");
			</script>
		<?php
		}
		/* Cierra la conexion */
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
		if ($_SESSION['Error']!=1) {//<<---Aqui se debe revisar de la forma objeto
			mysqli_commit($conectar2);
			
?>
			<script LANGUAGE="JavaScript">	
				alert ("La transacción se ha realizado con éxito");
			</script>
<?php
		}
		else
		{
			mysqli_rollback($conectar2);
?>
			<script LANGUAGE="JavaScript">
			alert ("< < < ¡¡¡ A l e r ta !!!: NO se ha podido completar con éxito la transacción > > >");
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
			<script LANGUAGE="JavaScript">	
				alert ("La transacción se ha realizado con éxito");
			</script>
			<?php
		}
		else
		{
			mysqli_rollback($this->conexion);
			?>
			<script LANGUAGE="JavaScript">
			alert ("< < < ¡¡¡ A l e r ta !!!: NO se ha podido completar con éxito la transacción > > >");
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
	 //ejecutamos la consulta
/*	 $rs_cargar = mysqli_query($conectar2,$sql); 
	 return $rs_cargar;*/
	 
	 return $this->rs_cargar = mysqli_query($conectar2,$sql); 	 
	}


} //fin de la Clase bdd_mysql

/*
*
*
*
*
*
*
*
*
*
*
*/
/*****************************************/
/*****************************************/
/*      Clase para conexion con MySql    */
/*****************************************/
/*****************************************/

class Class_Mysql{
/* P R O P I E D A D E S */
/*************************/
/* variables de conexi�n */
var $BaseDatos;
var $Servidor;
var $Usuario;
var $Clave;

/* identificador de conexi�n y consulta */
var $conexion = 0;

/* n�mero de error y texto error */
var $Errno = 0;
var $Error = "";
/**************************/
/**************************/

/* M E T O D O S */
/*****************/
/* M�todo Constructor: Cada vez que creemos una variable
de esta clase, se ejecutar� esta funci�n */
//function Class_Mysql($bd = "macros", $host = "localhost", $user = "usaaEsd2", $pass = "uiereprS"){
function Class_Mysql($bd = "macros", $host = "localhost", $user = "userMacros", $pass = "lynxsc6"){
	$this->BaseDatos = $bd;
	$this->Servidor = $host;
	$this->Usuario = $user;
	$this->Clave = $pass;
	
	$this->conectar();
}

/* Conexi�n a la base de datos */
function conectar(){
	// Conectamos al servidor
	$this->conexion = mysqli_connect($this->Servidor, $this->Usuario, $this->Clave,$this->BaseDatos);
	if (!$this->conexion) {
		$this->Error = "Ha fallado la conexi�n.";
		return 0;
	}

	/* Seleccionamos la base de datos */
	if (!@mysqli_select_db($this->conexion,$this->BaseDatos)) {
		$this->Error = "Imposible abrir ".$this->BaseDatos ;
		return 0;
	}

	/* Si hemos tenido �xito conectando devuelve 
	el identificador de la conexi�n, sino devuelve 0 */
	return $this->conexion;
	}

/* Cierra la conexion */
function cerrar(){
	return @mysqli_close($this->conexion);
	}

}//Fin de clase Class_Conexion


class Class_Datos{
/* P R O P I E D A D E S */
/*************************/
/* identificador de conexi�n y consulta */
var $rs_cargar = 0;

/* M E T O D O S */
/*****************/
/* Ejecuta un consulta */
function consulta($sql = "",$conexion/* Parametro enviado externamente */){
	if ($sql == "") {
		$this->Error = "No ha especificado una consulta SQL";
		return 0;
	}

	/*ejecutamos la consulta*/
	$this->rs_cargar = @mysqli_query($conexion,$sql);

	if (!$this->rs_cargar) {
		$this->Errno = @mysqli_errno($conexion);
		$this->Error = @mysqli_error($conexion);
	}

	/* Si hemos tenido �xito en la consulta devuelve 
	el identificador de la conexi�n, sino devuelve 0 */	
	return $this->rs_cargar;
}

/* Libera la memoria ram de los datos cargados */
function liberar(){
	return @mysqli_free_result($this->rs_cargar);
}

/* Crea un arrgleo en base a los parametros concatenados */
function parametros($param){
	$Par_Sql=explode('*',$param);
	return $Par_Sql;
}

/* Devuelve el n�mero de campos de una consulta */
function numcampos() {
	return mysqli_num_fields($this->rs_cargar);
}

/* Devuelve el n�mero de registros de una consulta */
function numregistros(){
	return @mysqli_num_rows($this->rs_cargar);
}

/* Desvuelve una matriz con los datos consultados */
function registros(){
	return @mysqli_fetch_assoc($this->rs_cargar);
}

/* Devuelve el ultimo codigo generado en una conexion */
function insercionid($conexion){
	return mysqli_insert_id($conexion);
}


/* Devuelve el nombre de un campo de una consulta */
/* REVISAR ------------------------------------------------------- */
function nombrecampo($numcampo) {
	return mysqli_field_name($this->rs_cargar, $numcampo);
}

/* Muestra los datos de una consulta */
function verconsulta() {
	echo "<table border=1>\n";
	// mostramos los nombres de los campos
	for ($i = 0; $i < $this->numcampos(); $i++){
		echo "<td><b>".$this->nombrecampo($i)."</b></td>\n";
	}
	echo "</tr>\n";
	// mostrarmos los registros
	
	while ($row = mysqli_fetch_row($this->rs_cargar)) {
		echo "<tr> \n";
		for ($i = 0; $i < $this->numcampos(); $i++){
		echo "<td>".$row[$i]."</td>\n";
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
			<script LANGUAGE="JavaScript">	
				alert ("La transacción se ha realizado con éxito");
			</script>
		<?php
		}
		else
		{
		    mysqli_rollback($conexion);
		?>
			<script LANGUAGE="JavaScript">
			alert ("< < < ¡¡¡ A l e r ta !!!: NO se ha podido completar con éxito la transacción > > >");
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
			<script LANGUAGE="JavaScript">	
				alert ("La transacción se ha realizado con éxito");
			</script>
			<?php
		}
		else
		{
			mysqli_rollback($conexion);
			?>
			<script LANGUAGE="JavaScript">
			alert ("< < < ¡¡¡ A l e r ta !!!: NO se ha podido completar con éxito la transacción > > >");
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

/* Desvuelve una matriz con los datos consultados en base a un rs */
function fetch_assoc($rs_consulta){
	return @mysqli_fetch_assoc($rs_consulta);
}

/* Desvuelve el total de datos consultados en base a un rs */
function num_rows($rs_consulta){
	return @mysqli_num_rows($rs_consulta);
}

/* libera un rs */
function free_result($rs_consulta){
	return @mysqli_free_result($rs_consulta);
}

/* Mueve el apuntador de la consulta */
function data_seek($rs_consulta, $puntero){
	return @mysqli_data_seek ($rs_consulta, $puntero);
}

/* Devuelve un arreglo de una consulta */
function fetch_array($rs_consulta){
	return @mysqli_fetch_array ($rs_consulta);
}

/******************************************/
/******************************************/
/******************************************/
/******************************************/


///* Ejecuta un consulta de varias transacciones */
//function consultav($sql = "", $conectar2)
//	{
//	 //ejecutamos la consulta
///*	 $rs_cargar = mysqli_query($conectar2,$sql); 
//	 return $rs_cargar;*/
//	 
//	 return $this->rs_cargar = mysqli_query($conectar2,$sql); 	 
//	}
} //fin de la Clase Class_Datos()











/*****************************************/
/*****************************************/
/*   Clase para conexion con PostgreSql  */
/*****************************************/
/*****************************************/

class Class_PostgreSql{
/* P R O P I E D A D E S */
/*************************/
/* variables de conexi�n */
var $BaseDatos;
var $Servidor;
var $Usuario;
var $Clave;

/* identificador de conexi�n y consulta */
var $conexion = 0;

/* n�mero de error y texto error */
var $Errno = 0;
var $Error = "";
/**************************/
/**************************/

/* M E T O D O S */
/*****************/
/* M�todo Constructor: Cada vez que creemos una variable
de esta clase, se ejecutar� esta funci�n */

function Class_PostgreSql($bd ="ginus_bibli", $host="localhost", $user ="onlaCIdh", $pass="conspitw", $port ="5432")
{	$this->BaseDatos = $bd;
	$this->Servidor = $host;
	$this->Puerto = $port;
	$this->Usuario = $user;
	$this->Clave = $pass;	
	$this->conectar();
}

/* Conexi�n a la base de datos */
function conectar(){
	//Conectamos al servidor
	$this->conexion = pg_connect("host=$this->Servidor port=$this->Puerto dbname=$this->BaseDatos user=$this->Usuario password=$this->Clave");

	if (!$this->conexion) {
		$this->Error = "Ha fallado la conexi�n.";
		return 0;
	}//Fin del if (!$this->conexion)

	/* Si hemos tenido �xito conectando devuelve 
	el identificador de la conexi�n, sino devuelve 0 */
	return $this->conexion;
}//Fin del function conectar(){

/* Cierra la conexion */
function cerrar(){
	return @pg_close($this->conexion);
}//Fin del function cerrar()

}//Fin de clase Class_PostgreSql


class Class_Datos_PostgreSql{
/* P R O P I E D A D E S */
/*************************/
/* identificador de consulta y grabado */
var $rs_cargar = 0;
var $row_rs_cargar = 0;
var $rs_save = 0;

/* M E T O D O S */
/*****************/
/* Ejecuta un consulta */
function consulta($sql = "",$conexion/* Parametro enviado externamente */){
	if ($sql == "") {
		$this->Error = "No ha especificado una consulta SQL";
		return 0;
	}//Fin del if ($sql == "")

	/*ejecutamos la consulta*/
	$this->rs_cargar = @pg_query($conexion,$sql);

	if (!$this->rs_cargar) {
		//$this->Errno = @mysqli_errno($conexion);ojojojojojojoj
		$this->Error = @pg_last_error($conexion);
	}//Fin del if (!$this->rs_cargar) 

	/* Si hemos tenido �xito en la consulta devuelve 
	el identificador de la conexi�n, sino devuelve 0 */	
	return $this->rs_cargar;
}//Fin del function consulta

/* Libera la memoria ram de los datos cargados */
function liberar(){
	return @pg_free_result($this->rs_cargar);
}//Fin del function liberar()

/* Crea un arrgleo en base a los parametros concatenados */
function parametros($param){
	$Par_Sql=explode('*',$param);
	return $Par_Sql;
}//Fin del function parametros

/* Devuelve el n�mero de registros de una consulta */
function numregistros(){
	return @pg_num_rows($this->rs_cargar);
}//Fin del function numregistros()

/* Desvuelve una matriz con los datos consultados */
function registros(){
	return @pg_fetch_assoc($this->rs_cargar);
}//Fin del function registros()

/* Devuelve el ultimo codigo generado en una conexion */
function insercionid($conexion, $sequence){ 
	$this->rs_cargar = @pg_query($conexion,"SELECT CURRVAL('".$sequence."_seq')");
	$this->row_rs_cargar = $this->registros();
	return $this->row_rs_cargar['currval'];
}//Fin del function insercionid

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
			<script LANGUAGE="JavaScript">	
				alert ("La transacción se ha realizado con éxito");
			</script>
		<?php
		}
		else
		{
		    pg_rollback($conexion);
		?>
			<script LANGUAGE="JavaScript">
			alert ("< < < ¡¡¡ A l e r ta !!!: NO se ha podido completar con éxito la transacción > > >");
			</script>
		<?php
		}
}//Fin del function grabaru

/* Varias transacciones */
/* Abre la transaccion */
function inicio_transaccion($conexion)
{
		pg_query($conexion,"BEGIN;");
}//Fin del function inicio_transaccion

/* Graba varios registros en una transaccion */
function grabarv_registros($sql = "",$conexion)
{
		//@ oculta el error de la consulta si existiera
		return $this->rs_save = @pg_query($conexion,$sql);
}//Fin del function grabarv_registros

/* Cierra la transaccion */	
function fin_transaccion($conexion)
{   
	if ($this->rs_save)
	{
		pg_query($conexion,'COMMIT;')

		?>
		<script LANGUAGE="JavaScript">	
			alert ("La transacción se ha realizado con éxito");
		</script>
		<?php
	}
	else
	{
		pg_query($conexion,'ROLLBACK;');
		?>
		<script LANGUAGE="JavaScript">
			alert ("< < < ¡¡¡ A l e r ta !!!: NO se ha podido completar con éxito la transacción > > >");
		</script>
		<?php
	}
}//Fin del function fin_transaccion

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
}//Fin del function fin_transaccion_nomsn

/* Desvuelve una matriz con los datos consultados en base a un rs */
function fetch_assoc($rs_consulta){
	return @pg_fetch_assoc($rs_consulta);
}//Fin del function fetch_assoc

/* Desvuelve el total de datos consultados en base a un rs */
function num_rows($rs_consulta){
	return @pg_num_rows($rs_consulta);
}//Fin del function num_rows

/* libera un rs */
function free_result($rs_consulta){
	return @pg_free_result($rs_consulta);
}

} //fin de la Clase PostgreSql()

?>