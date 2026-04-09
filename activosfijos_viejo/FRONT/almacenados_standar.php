<?Php

/*Agrega etiquetas nuevas a un xml (se usa en el ATS)*/
function simplexml_insert_after(SimpleXMLElement $insert, SimpleXMLElement $target)
{
    $target_dom = dom_import_simplexml($target);
    $insert_dom = $target_dom->ownerDocument->importNode(dom_import_simplexml($insert), true);
    if ($target_dom->nextSibling) {
        return $target_dom->parentNode->insertBefore($insert_dom, $target_dom->nextSibling);
    } else {
        return $target_dom->parentNode->appendChild($insert_dom);
    }	
}

 function convertirMoneda($from,$to,$amount){
	 $url = "http://www.google.com/finance/converter?a=1&from=$from&to=$to"; 
	 $regularExpression     = '#\<span class=bld\>(.+?)\<\/span\>#s'; $request = curl_init(); $timeOut = 0; 
	 curl_setopt ($request, CURLOPT_URL, $url); 
	 curl_setopt ($request, CURLOPT_RETURNTRANSFER, 1); 
	 curl_setopt ($request, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)"); 
	 curl_setopt ($request, CURLOPT_CONNECTTIMEOUT, $timeOut); 
	 $response = curl_exec($request); 
	 curl_close($request); 
     preg_match($regularExpression, $response, $finalData);
	 $change['link']="http://www.google.com/finance?q=$from$to";	 
	 $change['from']=$from;$change['to']=$to;$change['value']=$amount;
	 $change['rate']=('0'.str_replace(' '.$to,'',$finalData[1]))*1;
	 $change['result']=$amount*$change['rate'];
	 return $change;
}
function reporteHtml($params,$templatePath){
	if (!is_file($templatePath))
	{            
		throw new Exception('No se ha encontrado la plantilla!');
	}
	$templateTxt = file_get_contents($templatePath);
	foreach ($params as $key => $value) {
		$templateTxt = str_replace($key, $value, $templateTxt); 
	}
	return $templateTxt;
}
function reporteArray($params,$templateTxt){		
	foreach ($params as $key => $value) {
		$templateTxt = str_replace($key, $value, $templateTxt); 
	}
	return $templateTxt;
}
function utf8_encode_deep(&$input) {
    if (is_string($input)) {
        $input = utf8_encode($input);
    } else if (is_array($input)) {
        foreach ($input as &$value) {
            utf8_encode_deep($value);
        }
        unset($value);
    } else if (is_object($input)) {
        $vars = array_keys(get_object_vars($input));
        foreach ($vars as $var) {
            utf8_encode_deep($input->$var);
        }
    }
}
function pages($count,$page,$limit)
{   
        if( $count > 0 && $limit > 0) {$total_pages = ceil($count/$limit); }
        else {$total_pages = 0; }
        if ($page > $total_pages) { $page = $total_pages; }
        $start = $limit * $page - $limit;
        if($start < 0) {$start = 0;}
        $limits=" LIMIT {$start}, {$limit}";
        
		$responce['data']['rows']=NULL;
        $responce['data']['page'] = $page;
        $responce['data']['total'] = $total_pages;
        $responce['data']['records'] = $count;
        $responce['limits'] = $limits;
		
		return $responce; 
}
function startsWith($haystack, $needle) {
    // search backwards starting from haystack length characters from the end
    return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
}
function endsWith($haystack, $needle) {
    // search forward starting from end minus needle length characters
    return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
}
// url like: http://localhost/admin/FRONT/adm_alt_registro.php
// echo baseUrl();    //  will produce something like: http://localhost/admin/FRONT/
// echo baseUrl('index.php'); //  will produce something like: http://localhost/admin/FRONT/index.php
// echo baseUrl(NULL,TRUE);    //  will produce something like: http://localhost/
// echo basePath('index.php');  //  will produce something like: /admin/FRONT/index.php
function baseUrl($page=NULL,$atRoot=FALSE,$parse=FALSE){    
    $name = filter_input(INPUT_SERVER, 'HTTP_HOST', FILTER_SANITIZE_STRING);   
    if ($name || strlen($name) >0) {  
        $httpsVar = filter_input(INPUT_SERVER, 'HTTPS', FILTER_SANITIZE_STRING);
        $http = ($httpsVar || strlen($httpsVar) >0) && strtolower(filter_input(INPUT_SERVER,'HTTPS')) !== 'off' ? 'https' : 'http';
        $hostname = filter_input(INPUT_SERVER,'HTTP_HOST');
        $dir =  str_replace(basename(filter_input(INPUT_SERVER,'SCRIPT_NAME')), '', filter_input(INPUT_SERVER,'SCRIPT_NAME'));

        $tmplt = $atRoot ? ("%s://%s/") : ("%s://%s%s");
        $end = $atRoot ? ($hostname) : ($dir);
        $base_url = sprintf( $tmplt, $http, $hostname, $end );
    }else $base_url = 'http://localhost/';
    if($page!=NULL){$base_url=$base_url.$page;}
    if ($parse) {$base_url = parse_url($base_url); if (isset($base_url['path'])) if ($base_url['path'] == '/') $base_url['path'] = '';}
    return $base_url;
}
function basePath($page=NULL,$atRoot=FALSE){    
    $base_path=baseUrl($page,$atRoot,TRUE);
    return $base_path['path'];
}
//***********************************S T A N D A R*****************************************************************
//*****************************************************************************************************************
//***********************Funcion que muestra un comentarios de requerido*******************************************
function mensaje_requerido()
{
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>
		  <tr>
       		<td ><span class='Titulos2'>NOTA:</span> <span class='LetraNegra'>Los campos que se encuentran marcados 
					con un asterisco (</span> <span class='Asterisco'>*</span> <span class='LetraNegra'>) son campos obligatorios 
					y  no pueden ser dejados en blanco. </span>
			</td>
		  </tr>
  		</table>
		<hr>";
}
//*********************Devuelve el mes en palabras de acuerdo al numero de mes***************************************
function mes($meses, $tipo)
{
	//1 = Meses con escritura completa
	//2 = Meses en abreviatura	
	switch ($tipo){
		case 1:
			$array_meses1 =array ("Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", 
								"Noviembre", "Diciembre");			
			break;
		 case 2:
			$array_meses1 =array ("Ene", "Feb", "Mar", "Abr", "May", "Jun", "Jul", "Ago", "Sep", "Oct", "Nov", "Dic");
			break;
	}
	return $array_meses1[$meses-1];					
}
//*********************Devuelve el dia en palabras de acuerdo al numero de dia***************************************
function dias($dias, $tipo)
{
	//1 = Dia con escritura completa
	//2 = Dia en abreviatura	
	switch ($tipo){
		case 1:
			$array_dias1 =array ("Domingo", "Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado");			
			break;
		 case 2:
			$array_dias1 =array ("Dom", "Lun", "Mar", "Mié", "Jue", "Vie", "Sáb");			
			break;
	}
	return $array_dias1[$dias];					
}
//-------------------------------------------------------------------------------------------------------------------
//-------------------------------------------------------------------------------------------------------------------
//funcion que permite cortar una cadena de texto desde un inicio hasta un fin determinados
function cortar_cadena($ini, $fin, $cadena)
{
	for ($i=$ini;$i<=$fin;$i++)
	{
		$cortar = $cortar.$cadena[$i];
	}  	
	return $cortar;
}

//funcion que permite cortar una cadena de texto en base a un parametro
function cortar_cadena_param($param, $cadena)
{
	$array = explode($param,$cadena);
	for ($i=0;$i<=count($array)-1;$i++)
	{
		$cortar = $cortar.' '.cortar_cadena(0, 2, $array[$i]);
	}  	
	return $cortar;
}

/* Funcion que devuelve la cantidad de registros encontrados */
function barra_estado($can_registros)
{
	$barra = "<table width='95%' border='0' cellpadding='0' cellspacing='0' class='LetraNegra'>
			  <tr>
    			<td>(Se encontraron <span class='Alertas'>".$can_registros." </span> registros en la base de datos)</td>
			  </tr>
			 </table>";
	return $barra;
}
//Funcion que muestra el detalle del estudiante que se encuentra en un semestre por arrastre
function mensaje_lista()
{
	echo "<table width='100%' border='0'>
		  <tr>
       		<td><div align='justify'>
							<span class='Titulos2'>NOTA:</span> <span class='LetraNegra'>Los estudiantes que se encuentran marcados 
							con un asterisco (</span> <span class='Asterisco'>*</span> <span class='LetraNegra'>) toman la(s) asignatura(s)
							como Arrastre en el semestre. </span>
							</div>
			</td>
		  </tr>
  		</table>";	
}

/* Funcion que cambia el semestre de letras a numeros */
function semestre_num($sem)
{
	switch ($sem){
		case "Pre-Universitario":
			$nivel = "Pre";
			break;
		case "Primero":
			$nivel = "1er";
			break;
		case "Segundo":
			$nivel = "2do";
			break;
		case "Tercero":
			$nivel = "3er";
			break;
		case "Cuarto":
			$nivel = "4to";
			break;
		case "Quinto":
			$nivel = "5to";
			break;
		case "Sexto":
			$nivel = "6to";
			break;
		case "Septimo":
			$nivel = "7mo";
			break;
		case "Octavo":
			$nivel = "8vo";
			break;
		case "Noveno":
			$nivel = "9no";
			break;
		case "Decimo":
			$nivel = "10mo";
			break;			
	}
	return $nivel;					
}

/* Funcion que devuelve un mensaje de error aplicando una imagen */
function error_alerta($mensaje, $tipo)
{
	switch ($tipo){
		case 1: //Informacion
			$imagen = "<img src='../../mascaras/model1/imagenes/32x32/admiracion2.jpg' width='32' height='32'/>";
			$clase = 'alertas2';
			break;
		case 2://Advertencia
			$imagen = "<img src='../../mascaras/model1/imagenes/32x32/advertencia.png' width='32' height='32'/>";
			$clase = 'alertas1';
			break;
		case 3: //Grave
			$imagen = "<img src='../../mascaras/model1/imagenes/32x32/error.png' width='32' height='32'/>";
			$clase = 'alertas3';			
			break;			
	}
	
	$err ="<div align='center'>$imagen$mensaje</div>";

	return $err;
}


//Funcion que devuelve los semestres en base a una carrera y periodo
function semestres_car_per($Car_Int, $Per_Int)
{
	$rs_semestres = cargar ("SELECT niveles.Niv_Des, semestres.Sem_Cod, Sem_Par, IF (Sem_Sec = 'D', 'Diurna', IF (Sem_Sec = 'V',
						'Vespertina', 'Nocturna')) as Sem_Sec, modalidad.Mod_Des FROM semestres, niveles, modalidad, carreras,
						periodos, promocione WHERE promocione.Pro_Cod = semestres.Pro_Cod AND niveles.Niv_Cod = semestres.Niv_Cod
						AND modalidad.Mod_Cod = periodos.Mod_Cod AND promocione.Car_Int = carreras.Car_Int AND promocione.Car_Int =
						$Car_Int AND promocione.Pro_Dis = 'V' AND semestres.Per_Int = $Per_Int AND semestres.Per_Int =
						periodos.Per_Int ORDER BY semestres.Niv_Cod, semestres.Sem_Par, Sem_Sec");
	return $rs_semestres;
	mysqli_free_result ($rs_semestres);
}
//Funcion que devuelve las carreras las cuales esta cursando el estudiante
function estudiante_carreras($Est_Ced)
{
	$rs_existe = cargar ("SELECT carreras.Car_Int, Car_Nom, estudiante.Est_Int, persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape
						FROM estudiante, matriculas, carreras, promocione, semestres, persona WHERE estudiante.Est_Int = matriculas.Est_Int AND 
						matriculas.Sem_Cod = semestres.Sem_Cod AND semestres.Pro_Cod = promocione.Pro_Cod AND carreras.Car_Int = promocione.Car_Int 
						AND persona.Prs_Ced = '$Est_Ced' AND persona.Prs_Cod = estudiante.Prs_Cod AND estudiante.Est_Est = 'A' GROUP BY Car_Int, 
						Car_Nom, Est_Int, persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape");
	return $rs_existe;
	mysqli_free_result($rs_existe);
}

function centimos()
{
global $importe_parcial;

$importe_parcial = number_format($importe_parcial, 2, ".", "") * 100;

if ($importe_parcial > 0)
$num_letra = " con ".decena_centimos($importe_parcial);
else
$num_letra = "";

return $num_letra;
} function unidad_centimos($numero)
{
switch ($numero)
{
case 9:
{
$num_letra = "nueve centavos";
break;
}
case 8:
{
$num_letra = "ocho centavos";
break;
}
case 7:
{
$num_letra = "siete centavos";
break;
}
case 6:
{
$num_letra = "seis centavos";
break;
}
case 5:
{
$num_letra = "cinco centavos";
break;
}
case 4:
{
$num_letra = "cuatro centavos";
break;
}
case 3:
{
$num_letra = "tres centavos";
break;
}
case 2:
{
$num_letra = "dos centavos";
break;
}
case 1:
{
$num_letra = "un centavos";
break;
}
}
return $num_letra;
} function decena_centimos($numero)
{
if ($numero >= 10)
{
if ($numero >= 90 && $numero <= 99)
{
if ($numero == 90)
return "noventa céntimos";
else if ($numero == 91)
return "noventa y un centavos";
else
return "noventa y ".unidad_centimos($numero - 90);
}
if ($numero >= 80 && $numero <= 89)
{
if ($numero == 80)
return "ochenta centavos";
else if ($numero == 81)
return "ochenta y un centavos";
else
return "ochenta y ".unidad_centimos($numero - 80);
}
if ($numero >= 70 && $numero <= 79)
{
if ($numero == 70)
return "setenta céntimos";
else if ($numero == 71)
return "setenta y un centavos";
else
return "setenta y ".unidad_centimos($numero - 70);
}
if ($numero >= 60 && $numero <= 69)
{
if ($numero == 60)
return "sesenta centavos";
else if ($numero == 61)
return "sesenta y un centavos";
else
return "sesenta y ".unidad_centimos($numero - 60);
}
if ($numero >= 50 && $numero <= 59)
{
if ($numero == 50)
return "cincuenta centavos";
else if ($numero == 51)
return "cincuenta y un centavos";
else
return "cincuenta y ".unidad_centimos($numero - 50);
}
if ($numero >= 40 && $numero <= 49)
{
if ($numero == 40)
return "cuarenta centavos";
else if ($numero == 41)
return "cuarenta y un centavos";
else
return "cuarenta y ".unidad_centimos($numero - 40);
}
if ($numero >= 30 && $numero <= 39)
{
if ($numero == 30)
return "treinta centavos";
else if ($numero == 91)
return "treinta y un centavos";
else
return "treinta y ".unidad_centimos($numero - 30);
}
if ($numero >= 20 && $numero <= 29)
{
if ($numero == 20)
return "veinte centavos";
else if ($numero == 21)
return "veintiun centavos";
else
return "veinti".unidad_centimos($numero - 20);
}
if ($numero >= 10 && $numero <= 19)
{
if ($numero == 10)
return "diez centavos";
else if ($numero == 11)
return "once centavos";
else if ($numero == 11)
return "doce centavos";
else if ($numero == 11)
return "trece centavos";
else if ($numero == 11)
return "catorce centavos";
else if ($numero == 11)
return "quince centavos";
else if ($numero == 11)
return "dieciseis centavos";
else if ($numero == 11)
return "diecisiete centavos";
else if ($numero == 11)
return "dieciocho centavos";
else if ($numero == 11)
return "diecinueve centavos";
}
}
else
return unidad_centimos($numero);
} function unidad($numero)
{
switch ($numero)
{
case 9:
{
$num = "nueve";
break;
}
case 8:
{
$num = "ocho";
break;
}
case 7:
{
$num = "siete";
break;
}
case 6:
{
$num = "seis";
break;
}
case 5:
{
$num = "cinco";
break;
}
case 4:
{
$num = "cuatro";
break;
}
case 3:
{
$num = "tres";
break;
}
case 2:
{
$num = "dos";
break;
}
case 1:
{
$num = "uno";
break;
}
}
return $num;
} function decena($numero)
{
if ($numero >= 90 && $numero <= 99)
{
$num_letra = "noventa ";

if ($numero > 90)
$num_letra = $num_letra."y ".unidad($numero - 90);
}
else if ($numero >= 80 && $numero <= 89)
{
$num_letra = "ochenta ";

if ($numero > 80)
$num_letra = $num_letra."y ".unidad($numero - 80);
}
else if ($numero >= 70 && $numero <= 79)
{
$num_letra = "setenta ";

if ($numero > 70)
$num_letra = $num_letra."y ".unidad($numero - 70);
}
else if ($numero >= 60 && $numero <= 69)
{
$num_letra = "sesenta ";

if ($numero > 60)
$num_letra = $num_letra."y ".unidad($numero - 60);
}
else if ($numero >= 50 && $numero <= 59)
{
$num_letra = "cincuenta ";

if ($numero > 50)
$num_letra = $num_letra."y ".unidad($numero - 50);
}
else if ($numero >= 40 && $numero <= 49)
{
$num_letra = "cuarenta ";

if ($numero > 40)
$num_letra = $num_letra."y ".unidad($numero - 40);
}
else if ($numero >= 30 && $numero <= 39)
{
$num_letra = "treinta ";

if ($numero > 30)
$num_letra = $num_letra."y ".unidad($numero - 30);
}
else if ($numero >= 20 && $numero <= 29)
{
if ($numero == 20)
$num_letra = "veinte ";
else
$num_letra = "veinti".unidad($numero - 20);
}
else if ($numero >= 10 && $numero <= 19)
{
switch ($numero)
{
case 10:
{
$num_letra = "diez ";
break;
}
case 11:
{
$num_letra = "once ";
break;
}
case 12:
{
$num_letra = "doce ";
break;
}
case 13:
{
$num_letra = "trece ";
break;
}
case 14:
{
$num_letra = "catorce ";
break;
}
case 15:
{
$num_letra = "quince ";
break;
}
case 16:
{
$num_letra = "dieciseis ";
break;
}
case 17:
{
$num_letra = "diecisiete ";
break;
}
case 18:
{
$num_letra = "dieciocho ";
break;
}
case 19:
{
$num_letra = "diecinueve ";
break;
}
}
}
else
$num_letra = unidad($numero);

return $num_letra;
} function centena($numero)
{
if ($numero >= 100)
{
if ($numero >= 900 & $numero <= 999)
{
$num_letra = "novecientos ";

if ($numero > 900)
$num_letra = $num_letra.decena($numero - 900);
}
else if ($numero >= 800 && $numero <= 899)
{
$num_letra = "ochocientos ";

if ($numero > 800)
$num_letra = $num_letra.decena($numero - 800);
}
else if ($numero >= 700 && $numero <= 799)
{
$num_letra = "setecientos ";

if ($numero > 700)
$num_letra = $num_letra.decena($numero - 700);
}
else if ($numero >= 600 && $numero <= 699)
{
$num_letra = "seiscientos ";

if ($numero > 600)
$num_letra = $num_letra.decena($numero - 600);
}
else if ($numero >= 500 && $numero <= 599)
{
$num_letra = "quinientos ";

if ($numero > 500)
$num_letra = $num_letra.decena($numero - 500);
}
else if ($numero >= 400 && $numero <= 499)
{
$num_letra = "cuatrocientos ";

if ($numero > 400)
$num_letra = $num_letra.decena($numero - 400);
}
else if ($numero >= 300 && $numero <= 399)
{
$num_letra = "trescientos ";

if ($numero > 300)
$num_letra = $num_letra.decena($numero - 300);
}
else if ($numero >= 200 && $numero <= 299)
{
$num_letra = "doscientos ";

if ($numero > 200)
$num_letra = $num_letra.decena($numero - 200);
}
else if ($numero >= 100 && $numero <= 199)
{
if ($numero == 100)
$num_letra = "cien ";
else
$num_letra = "ciento ".decena($numero - 100);
}
}
else
$num_letra = decena($numero);

return $num_letra;
} function cien()
{
global $importe_parcial;

$parcial = 0; $car = 0;

while (substr($importe_parcial, 0, 1) == 0)
$importe_parcial = substr($importe_parcial, 1, strlen($importe_parcial) - 1);

if ($importe_parcial >= 1 && $importe_parcial <= 9.99)
$car = 1;
else if ($importe_parcial >= 10 && $importe_parcial <= 99.99)
$car = 2;
else if ($importe_parcial >= 100 && $importe_parcial <= 999.99)
$car = 3;

$parcial = substr($importe_parcial, 0, $car);
$importe_parcial = substr($importe_parcial, $car);

$num_letra = centena($parcial).centimos();

return $num_letra;
} function cien_mil()
{
global $importe_parcial;

$parcial = 0; $car = 0;

while (substr($importe_parcial, 0, 1) == 0)
$importe_parcial = substr($importe_parcial, 1, strlen($importe_parcial) - 1);

if ($importe_parcial >= 1000 && $importe_parcial <= 9999.99)
$car = 1;
else if ($importe_parcial >= 10000 && $importe_parcial <= 99999.99)
$car = 2;
else if ($importe_parcial >= 100000 && $importe_parcial <= 999999.99)
$car = 3;

$parcial = substr($importe_parcial, 0, $car);
$importe_parcial = substr($importe_parcial, $car);

if ($parcial > 0)
{
if ($parcial == 1)
$num_letra = "mil ";
else
$num_letra = centena($parcial)." mil ";
}

return $num_letra;
} function millon()
{
global $importe_parcial;

$parcial = 0; $car = 0;

while (substr($importe_parcial, 0, 1) == 0)
$importe_parcial = substr($importe_parcial, 1, strlen($importe_parcial) - 1);

if ($importe_parcial >= 1000000 && $importe_parcial <= 9999999.99)
$car = 1;
else if ($importe_parcial >= 10000000 && $importe_parcial <= 99999999.99)
$car = 2;
else if ($importe_parcial >= 100000000 && $importe_parcial <= 999999999.99)
$car = 3;

$parcial = substr($importe_parcial, 0, $car);
$importe_parcial = substr($importe_parcial, $car);

if ($parcial == 1)
$num_letras = "un millón ";
else
$num_letras = centena($parcial)." millones ";

return $num_letras;
} function convertir_a_letras($numero)
{
global $importe_parcial;

$importe_parcial = $numero;

if ($numero < 1000000000)
{
if ($numero >= 1000000 && $numero <= 999999999.99)
$num_letras = millon().cien_mil().cien();
else if ($numero >= 1000 && $numero <= 999999.99)
$num_letras = cien_mil().cien();
else if ($numero >= 1 && $numero <= 999.99)
$num_letras = cien();
else if ($numero >= 0.01 && $numero <= 0.99)
{
if ($numero == 0.01)
$num_letras = "un céntimo";
else
$num_letras = convertir_a_letras(($numero * 100)."/100")." centavos";
}
}
return $num_letras;
} 


/*! 
  @function num2letras () 
  @abstract Dado un n?mero lo devuelve escrito. 
  @param $num number - N?mero a convertir. 
  @param $fem bool - Forma femenina (true) o no (false). 
  @param $dec bool - Con decimales (true) o no (false). 
  @result string - Devuelve el n?mero escrito en letra. 

*/ 
function num2letras($num, $fem = false, $dec = true) { 
//if (strlen($num) > 14) die("El n?mero introducido es demasiado grande"); 
   $matuni[2]  = "dos"; 
   $matuni[3]  = "tres"; 
   $matuni[4]  = "cuatro"; 
   $matuni[5]  = "cinco"; 
   $matuni[6]  = "seis"; 
   $matuni[7]  = "siete"; 
   $matuni[8]  = "ocho"; 
   $matuni[9]  = "nueve"; 
   $matuni[10] = "diez"; 
   $matuni[11] = "once"; 
   $matuni[12] = "doce"; 
   $matuni[13] = "trece"; 
   $matuni[14] = "catorce"; 
   $matuni[15] = "quince"; 
   $matuni[16] = "dieciseis"; 
   $matuni[17] = "diecisiete"; 
   $matuni[18] = "dieciocho"; 
   $matuni[19] = "diecinueve"; 
   $matuni[20] = "veinte"; 
   $matunisub[2] = "dos"; 
   $matunisub[3] = "tres"; 
   $matunisub[4] = "cuatro"; 
   $matunisub[5] = "quin"; 
   $matunisub[6] = "seis"; 
   $matunisub[7] = "sete"; 
   $matunisub[8] = "ocho"; 
   $matunisub[9] = "nove"; 

   $matdec[2] = "veint"; 
   $matdec[3] = "treinta"; 
   $matdec[4] = "cuarenta"; 
   $matdec[5] = "cincuenta"; 
   $matdec[6] = "sesenta"; 
   $matdec[7] = "setenta"; 
   $matdec[8] = "ochenta"; 
   $matdec[9] = "noventa"; 
   $matsub[3]  = 'mill'; 
   $matsub[5]  = 'bill'; 
   $matsub[7]  = 'mill'; 
   $matsub[9]  = 'trill'; 
   $matsub[11] = 'mill'; 
   $matsub[13] = 'bill'; 
   $matsub[15] = 'mill'; 
   $matmil[4]  = 'millones'; 
   $matmil[6]  = 'billones'; 
   $matmil[7]  = 'de billones'; 
   $matmil[8]  = 'millones de billones'; 
   $matmil[10] = 'trillones'; 
   $matmil[11] = 'de trillones'; 
   $matmil[12] = 'millones de trillones'; 
   $matmil[13] = 'de trillones'; 
   $matmil[14] = 'billones de trillones'; 
   $matmil[15] = 'de billones de trillones'; 
   $matmil[16] = 'millones de billones de trillones'; 

   $num = trim((string)@$num); 
   if ($num[0] == '-') { 
      $neg = 'menos '; 
      $num = substr($num, 1); 
   }else 
      $neg = ''; 
   while ($num[0] == '0') $num = substr($num, 1); 
   if ($num[0] < '1' or $num[0] > 9) $num = '0' . $num; 
   $zeros = true; 
   $punt = false; 
   $ent = ''; 
   $fra = ''; 
   for ($c = 0; $c < strlen($num); $c++) { 
      $n = $num[$c]; 
      if (! (strpos(".,'''", $n) === false)) { 
         if ($punt) break; 
         else{ 
            $punt = true; 
            continue; 
         } 

      }elseif (! (strpos('0123456789', $n) === false)) { 
         if ($punt) { 
            if ($n != '0') $zeros = false; 
            $fra .= $n; 
         }else 

            $ent .= $n; 
      }else 

         break; 

   } 
   $ent = '     ' . $ent; 
   if ($dec and $fra and ! $zeros) { 
      $fin = ' coma'; 
      for ($n = 0; $n < strlen($fra); $n++) { 
         if (($s = $fra[$n]) == '0') 
            $fin .= ' cero'; 
         elseif ($s == '1') 
            $fin .= $fem ? ' una' : ' un'; 
         else 
            $fin .= ' ' . $matuni[$s]; 
      } 
   }else 
      $fin = ''; 
   if ((int)$ent === 0) return 'Cero ' . $fin; 
   $tex = ''; 
   $sub = 0; 
   $mils = 0; 
   $neutro = false; 
   while ( ($num = substr($ent, -3)) != '   ') { 
      $ent = substr($ent, 0, -3); 
      if (++$sub < 3 and $fem) { 
         $matuni[1] = 'una'; 
         $subcent = 'as'; 
      }else{ 
         $matuni[1] = $neutro ? 'un' : 'uno'; 
         $subcent = 'os'; 
      } 
      $t = ''; 
      $n2 = substr($num, 1); 
      if ($n2 == '00') { 
      }elseif ($n2 < 21) 
         $t = ' ' . $matuni[(int)$n2]; 
      elseif ($n2 < 30) { 
         $n3 = $num[2]; 
         if ($n3 != 0) $t = 'i' . $matuni[$n3]; 
         $n2 = $num[1]; 
         $t = ' ' . $matdec[$n2] . $t; 
      }else{ 
         $n3 = $num[2]; 
         if ($n3 != 0) $t = ' y ' . $matuni[$n3]; 
         $n2 = $num[1]; 
         $t = ' ' . $matdec[$n2] . $t; 
      } 
      $n = $num[0]; 
      if ($n == 1) { 
         $t = ' ciento' . $t; 
      }elseif ($n == 5){ 
         $t = ' ' . $matunisub[$n] . 'ient' . $subcent . $t; 
      }elseif ($n != 0){ 
         $t = ' ' . $matunisub[$n] . 'cient' . $subcent . $t; 
      } 
      if ($sub == 1) { 
      }elseif (! isset($matsub[$sub])) { 
         if ($num == 1) { 
            $t = ' mil'; 
         }elseif ($num > 1){ 
            $t .= ' mil'; 
         } 
      }elseif ($num == 1) { 
         $t .= ' ' . $matsub[$sub] . '?n'; 
      }elseif ($num > 1){ 
         $t .= ' ' . $matsub[$sub] . 'ones'; 
      }   
      if ($num == '000') $mils ++; 
      elseif ($mils != 0) { 
         if (isset($matmil[$sub])) $t .= ' ' . $matmil[$sub]; 
         $mils = 0; 
      } 
      $neutro = true; 
      $tex = $t . $tex; 
   } 
   $tex = $neg . substr($tex, 1) . $fin; 
   
   /**************************************************************/
   /* Control agregado para controlar el error de CIENTO -> CIEN */
   /**************************************************************/   
   if ($tex == 'ciento')
   {
		$tex='cien';
   }
   /**************************************************************/
   /**************************************************************/
   
   return ucfirst($tex); 
} 

/* Funcion que mueve el apuntador de la consulta al inicio */
function first_last($rs, $row_rs, $pos)
{
	$rows = mysqli_num_rows($rs);
	if($rows > 0) {
		  mysqli_data_seek($rs, $pos);
		  $row_rs = mysqli_fetch_assoc($rs);
	}
	return $row_rs; 
}

//Funcion que devuelve una cadena de parametros para iniciar la busqueda de un sql
function envio_parametros($cant, $Niv_Cod)
{
	for ($i=1;$i<=$cant;$i++)
	{
		if (isset($Niv_Cod[$i]))//Verifica si esta seteada una posicion del arreglo
		{
			$cod = $Niv_Cod[$i]."-"; 
		 	$cod2 = "$cod2$cod";					
		}
	}
	return $cod2;	
}

/* Funcion que crea pestañas diamicas */
function links($tamaño, $nombres, $vinculo)
{
	/* separa los nombres de los links */
	$links = explode('*',$nombres);	
	$pagina = explode('*',$vinculo);	
	echo "<table border='0'>
		   <tr>";
		   for ($i=0; $i<=$tamaño-1; $i++)
			{
               echo "<td class='links'><a href=".$pagina[$i]." class='href'>".$links[$i]."</a></td>";
			 }
    echo   "</tr>
          </table>";
}//background='../../layers/model1/pestanas/pestana1.JPG' style='background-repeat:no-repeat'

/* Funcion que crea pestañas diamicas */
function tabs($tamaño, $nombres, $vinculo, $activo)
{
	/* separa los nombres de los links */
	$links = explode('*',$nombres);	
	$pagina = explode('*',$vinculo);	
	echo "<ul id='tabnav'>";
		   for ($i=0; $i<=$tamaño-1; $i++)
			{
				$url = "javascript:ir('".urlencode($pagina[$i])."'); CambiarEstilo('bt".$i."')";
			   if ($activo == $i+1){ $enfoque = "activo"; }else{ $enfoque = "inactivo";} 
			   echo "<li class='".$enfoque."' id='bt'".$i."><a href=$url>".$links[$i]."</a></li>";
			 }
    echo   "</ul>";
}

/*funcioin para detectar que es celular*/
function detectar_acceso()
{
	// Debido a que este script envía un encabezado de información HTTP el primer carácter en el archivo debe ser el 
	//tag.
	//  $htmlredirect = "/html/my_htmlpage.html";  // URL relativo a su archivo HTML		  
	  if (strpos($_SERVER['HTTP_ACCEPT'],"vnd.wap.wml") > 0) {  // Revisa si el navegador/gateway dice si acepta VML.
		$Br = "WML";
	  }
	  else {
		$browser=substr(trim($HTTP_USER_AGENT),0,4);
	
		if($browser=="Noki" || // Teléfonos Nokia y emuladores
		  $browser=="Eric" || // Ericsson WAP teléfonos y emuladores
		  $browser=="WapI" || // Ericsson WapIDE 2.0
		  $browser=="MC21" || // Ericsson MC218
		  $browser=="AUR " || // Ericsson R320
		  $browser=="R380" || // Ericsson R380
		  $browser=="UP.B" || // UP.Browser
		  $browser=="WinW" || // WinWAP browser
		  $browser=="UPG1" || // UP.SDK 4.0
		  $browser=="upsi" || // another kind of UP.Browser ??
		  $browser=="QWAP" || // unknown QWAPPER browser
		  $browser=="Jigs" || // unknown JigSaw browser
		  $browser=="Java" || // unknown Java based browser
		  $browser=="Alca" || // unknown Alcatel-BE3 browser (UP based?)
		  $browser=="MITS" || // unknown Mitsubishi browser
		  $browser=="MOT-" || // unknown browser (UP based?)
		  $browser=="My S" || // unknown Ericsson devkit browser ?
		  $browser=="WAPJ" || // Virtual WAPJAG www.wapjag.de
		  $browser=="fetc" || // fetchpage.cgi Perl script from www.wapcab.de
		  $browser=="ALAV" || // yet another unknown UP based browser ?
		  $browser=="Wapa")// another unknown browser (Web based "Wapalyzer"?)
		{
			$Br = "WML";
		}
		else 
		{
			$Br = "HTML";
		}
	  }
	 return $Br;
}

/* Funcion que cambia de numeros decimales a romanos */
function decimal_romano($numero) 
{ 
$numero=floor($numero); 
if($numero<0) 
{ 
$var="-"; 
$numero=abs($numero); 
} 
# Definici?n de arrays 
$numerosromanos=array(1000,500,100,50,10,5,1); 
$numeroletrasromanas=array("M"=>1000,"D"=>500,"C"=>100,"L"=> 
50,"X"=>10,"V"=>5,"I"=>1); 
$letrasromanas=array_keys($numeroletrasromanas); 

while($numero) 
{ 
for($pos=0;$pos<=6;$pos++) 
{ 
$dividendo=$numero/$numerosromanos[$pos]; 
if($dividendo>=1) 
{ 
$var.=str_repeat($letrasromanas[$pos],floor($dividendo)); 
$numero-=floor($dividendo)*$numerosromanos[$pos]; 
} 
}
} 
$numcambios=1; 
while($numcambios) 
{ 
$numcambios=0; 
for($inicio=0;$inicio<strlen($var);$inicio++) 
{ 
$parcial=substr($var,$inicio,1); 
if($parcial==$parcialfinal&&$parcial!="M") 
{ 
$apariencia++; 
}else{ 
$parcialfinal=$parcial; 
$apariencia=1; 
} 
# Caso en que encuentre cuatro car?cteres seguidos iguales. 
if($apariencia==4) 
{ 
$primeraletra=substr($var,$inicio-4,1); 
$letra=$parcial; 
$sum=$primernumero+$letternumero*4; 
$pos=busqueda($letra,$letrasromanas); 
if($letrasromanas[$pos-1]==$primeraletra) 
{ 
$cadenaant=$primeraletra.str_repeat($letra,4); 
$cadenanueva=$letra.$letrasromanas[$pos-2]; 
}else{ 
$cadenaant=str_repeat($letra,4); 
$cadenanueva=$letra.$letrasromanas[$pos-1]; 
} 
$numcambios++; 
$var=str_replace($cadenaant,$cadenanueva,$var); 
} 
} } 
return $var; 
} 

function busqueda($cadenanueva,$array) 
{ 
foreach($array as $contenido) 
{ 
if($contenido==$cadenanueva) 
{ 
return $pos; 
} 
$pos++; 
} } 

function paleta($campo)
{
?>
<MAP name=colmap><AREA shape=RECT coords=1,1,7,10 
  href="javascript:showColor('<?Php echo $campo ?>','#00FF00')"><AREA shape=RECT coords=9,1,15,10 
  href="javascript:showColor('<?Php echo $campo ?>','#00FF33')"><AREA shape=RECT coords=17,1,23,10 
  href="javascript:showColor('<?Php echo $campo ?>','#00FF66')"><AREA shape=RECT coords=25,1,31,10 
  href="javascript:showColor('<?Php echo $campo ?>','#00FF99')"><AREA shape=RECT coords=33,1,39,10 
  href="javascript:showColor('<?Php echo $campo ?>','#00FFCC')"><AREA shape=RECT coords=41,1,47,10 
  href="javascript:showColor('<?Php echo $campo ?>','#00FFFF')"><AREA shape=RECT coords=49,1,55,10 
  href="javascript:showColor('<?Php echo $campo ?>','#33FF00')"><AREA shape=RECT coords=57,1,63,10 
  href="javascript:showColor('<?Php echo $campo ?>','#33FF33')"><AREA shape=RECT coords=65,1,71,10 
  href="javascript:showColor('<?Php echo $campo ?>','#33FF66')"><AREA shape=RECT coords=73,1,79,10 
  href="javascript:showColor('<?Php echo $campo ?>','#33FF99')"><AREA shape=RECT coords=81,1,87,10 
  href="javascript:showColor('<?Php echo $campo ?>','#33FFCC')"><AREA shape=RECT coords=89,1,95,10 
  href="javascript:showColor('<?Php echo $campo ?>','#33FFFF')"><AREA shape=RECT coords=97,1,103,10 
  href="javascript:showColor('<?Php echo $campo ?>','#66FF00')"><AREA shape=RECT coords=105,1,111,10 
  href="javascript:showColor('<?Php echo $campo ?>','#66FF33')"><AREA shape=RECT coords=113,1,119,10 
  href="javascript:showColor('<?Php echo $campo ?>','#66FF66')"><AREA shape=RECT coords=121,1,127,10 
  href="javascript:showColor('<?Php echo $campo ?>','#66FF99')"><AREA shape=RECT coords=129,1,135,10 
  href="javascript:showColor('<?Php echo $campo ?>','#66FFCC')"><AREA shape=RECT coords=137,1,143,10 
  href="javascript:showColor('<?Php echo $campo ?>','#66FFFF')"><AREA shape=RECT coords=145,1,151,10 
  href="javascript:showColor('<?Php echo $campo ?>','#99FF00')"><AREA shape=RECT coords=153,1,159,10 
  href="javascript:showColor('<?Php echo $campo ?>','#99FF33')"><AREA shape=RECT coords=161,1,167,10 
  href="javascript:showColor('<?Php echo $campo ?>','#99FF66')"><AREA shape=RECT coords=169,1,175,10 
  href="javascript:showColor('<?Php echo $campo ?>','#99FF99')"><AREA shape=RECT coords=177,1,183,10 
  href="javascript:showColor('<?Php echo $campo ?>','#99FFCC')"><AREA shape=RECT coords=185,1,191,10 
  href="javascript:showColor('<?Php echo $campo ?>','#99FFFF')"><AREA shape=RECT coords=193,1,199,10 
  href="javascript:showColor('<?Php echo $campo ?>','#CCFF00')"><AREA shape=RECT coords=201,1,207,10 
  href="javascript:showColor('<?Php echo $campo ?>','#CCFF33')"><AREA shape=RECT coords=209,1,215,10 
  href="javascript:showColor('<?Php echo $campo ?>','#CCFF66')"><AREA shape=RECT coords=217,1,223,10 
  href="javascript:showColor('<?Php echo $campo ?>','#CCFF99')"><AREA shape=RECT coords=225,1,231,10 
  href="javascript:showColor('<?Php echo $campo ?>','#CCFFCC')"><AREA shape=RECT coords=233,1,239,10 
  href="javascript:showColor('<?Php echo $campo ?>','#CCFFFF')"><AREA shape=RECT coords=241,1,247,10 
  href="javascript:showColor('<?Php echo $campo ?>','#FFFF00')"><AREA shape=RECT coords=249,1,255,10 
  href="javascript:showColor('<?Php echo $campo ?>','#FFFF33')"><AREA shape=RECT coords=257,1,263,10 
  href="javascript:showColor('<?Php echo $campo ?>','#FFFF66')"><AREA shape=RECT coords=265,1,271,10 
  href="javascript:showColor('<?Php echo $campo ?>','#FFFF99')"><AREA shape=RECT coords=273,1,279,10 
  href="javascript:showColor('<?Php echo $campo ?>','#FFFFCC')"><AREA shape=RECT coords=281,1,287,10 
  href="javascript:showColor('<?Php echo $campo ?>','#FFFFFF')"><AREA shape=RECT coords=1,12,7,21 
  href="javascript:showColor('<?Php echo $campo ?>','#00CC00')"><AREA shape=RECT coords=9,12,15,21 
  href="javascript:showColor('<?Php echo $campo ?>','#00CC33')"><AREA shape=RECT coords=17,12,23,21 
  href="javascript:showColor('<?Php echo $campo ?>','#00CC66')"><AREA shape=RECT coords=25,12,31,21 
  href="javascript:showColor('<?Php echo $campo ?>','#00CC99')"><AREA shape=RECT coords=33,12,39,21 
  href="javascript:showColor('<?Php echo $campo ?>','#00CCCC')"><AREA shape=RECT coords=41,12,47,21 
  href="javascript:showColor('<?Php echo $campo ?>','#00CCFF')"><AREA shape=RECT coords=49,12,55,21 
  href="javascript:showColor('<?Php echo $campo ?>','#33CC00')"><AREA shape=RECT coords=57,12,63,21 
  href="javascript:showColor('<?Php echo $campo ?>','#33CC33')"><AREA shape=RECT coords=65,12,71,21 
  href="javascript:showColor('<?Php echo $campo ?>','#33CC66')"><AREA shape=RECT coords=73,12,79,21 
  href="javascript:showColor('<?Php echo $campo ?>','#33CC99')"><AREA shape=RECT coords=81,12,87,21 
  href="javascript:showColor('<?Php echo $campo ?>','#33CCCC')"><AREA shape=RECT coords=89,12,95,21 
  href="javascript:showColor('<?Php echo $campo ?>','#33CCFF')"><AREA shape=RECT coords=97,12,103,21 
  href="javascript:showColor('<?Php echo $campo ?>','#66CC00')"><AREA shape=RECT coords=105,12,111,21 
  href="javascript:showColor('<?Php echo $campo ?>','#66CC33')"><AREA shape=RECT coords=113,12,119,21 
  href="javascript:showColor('<?Php echo $campo ?>','#66CC66')"><AREA shape=RECT coords=121,12,127,21 
  href="javascript:showColor('<?Php echo $campo ?>','#66CC99')"><AREA shape=RECT coords=129,12,135,21 
  href="javascript:showColor('<?Php echo $campo ?>','#66CCCC')"><AREA shape=RECT coords=137,12,143,21 
  href="javascript:showColor('<?Php echo $campo ?>','#66CCFF')"><AREA shape=RECT coords=145,12,151,21 
  href="javascript:showColor('<?Php echo $campo ?>','#99CC00')"><AREA shape=RECT coords=153,12,159,21 
  href="javascript:showColor('<?Php echo $campo ?>','#99CC33')"><AREA shape=RECT coords=161,12,167,21 
  href="javascript:showColor('<?Php echo $campo ?>','#99CC66')"><AREA shape=RECT coords=169,12,175,21 
  href="javascript:showColor('<?Php echo $campo ?>','#99CC99')"><AREA shape=RECT coords=177,12,183,21 
  href="javascript:showColor('<?Php echo $campo ?>','#99CCCC')"><AREA shape=RECT coords=185,12,191,21 
  href="javascript:showColor('<?Php echo $campo ?>','#99CCFF')"><AREA shape=RECT coords=193,12,199,21 
  href="javascript:showColor('<?Php echo $campo ?>','#CCCC00')"><AREA shape=RECT coords=201,12,207,21 
  href="javascript:showColor('<?Php echo $campo ?>','#CCCC33')"><AREA shape=RECT coords=209,12,215,21 
  href="javascript:showColor('<?Php echo $campo ?>','#CCCC66')"><AREA shape=RECT coords=217,12,223,21 
  href="javascript:showColor('<?Php echo $campo ?>','#CCCC99')"><AREA shape=RECT coords=225,12,231,21 
  href="javascript:showColor('<?Php echo $campo ?>','#CCCCCC')"><AREA shape=RECT coords=233,12,239,21 
  href="javascript:showColor('<?Php echo $campo ?>','#CCCCFF')"><AREA shape=RECT coords=241,12,247,21 
  href="javascript:showColor('<?Php echo $campo ?>','#FFCC00')"><AREA shape=RECT coords=249,12,255,21 
  href="javascript:showColor('<?Php echo $campo ?>','#FFCC33')"><AREA shape=RECT coords=257,12,263,21 
  href="javascript:showColor('<?Php echo $campo ?>','#FFCC66')"><AREA shape=RECT coords=265,12,271,21 
  href="javascript:showColor('<?Php echo $campo ?>','#FFCC99')"><AREA shape=RECT coords=273,12,279,21 
  href="javascript:showColor('<?Php echo $campo ?>','#FFCCCC')"><AREA shape=RECT coords=281,12,287,21 
  href="javascript:showColor('<?Php echo $campo ?>','#FFCCFF')"><AREA shape=RECT coords=1,23,7,32 
  href="javascript:showColor('<?Php echo $campo ?>','#009900')"><AREA shape=RECT coords=9,23,15,32 
  href="javascript:showColor('<?Php echo $campo ?>','#009933')"><AREA shape=RECT coords=17,23,23,32 
  href="javascript:showColor('<?Php echo $campo ?>','#009966')"><AREA shape=RECT coords=25,23,31,32 
  href="javascript:showColor('<?Php echo $campo ?>','#009999')"><AREA shape=RECT coords=33,23,39,32 
  href="javascript:showColor('<?Php echo $campo ?>','#0099CC')"><AREA shape=RECT coords=41,23,47,32 
  href="javascript:showColor('<?Php echo $campo ?>','#0099FF')"><AREA shape=RECT coords=49,23,55,32 
  href="javascript:showColor('<?Php echo $campo ?>','#339900')"><AREA shape=RECT coords=57,23,63,32 
  href="javascript:showColor('<?Php echo $campo ?>','#339933')"><AREA shape=RECT coords=65,23,71,32 
  href="javascript:showColor('<?Php echo $campo ?>','#339966')"><AREA shape=RECT coords=73,23,79,32 
  href="javascript:showColor('<?Php echo $campo ?>','#339999')"><AREA shape=RECT coords=81,23,87,32 
  href="javascript:showColor('<?Php echo $campo ?>','#3399CC')"><AREA shape=RECT coords=89,23,95,32 
  href="javascript:showColor('<?Php echo $campo ?>','#3399FF')"><AREA shape=RECT coords=97,23,103,32 
  href="javascript:showColor('<?Php echo $campo ?>','#669900')"><AREA shape=RECT coords=105,23,111,32 
  href="javascript:showColor('<?Php echo $campo ?>','#669933')"><AREA shape=RECT coords=113,23,119,32 
  href="javascript:showColor('<?Php echo $campo ?>','#669966')"><AREA shape=RECT coords=121,23,127,32 
  href="javascript:showColor('<?Php echo $campo ?>','#669999')"><AREA shape=RECT coords=129,23,135,32 
  href="javascript:showColor('<?Php echo $campo ?>','#6699CC')"><AREA shape=RECT coords=137,23,143,32 
  href="javascript:showColor('<?Php echo $campo ?>','#6699FF')"><AREA shape=RECT coords=145,23,151,32 
  href="javascript:showColor('<?Php echo $campo ?>','#999900')"><AREA shape=RECT coords=153,23,159,32 
  href="javascript:showColor('<?Php echo $campo ?>','#999933')"><AREA shape=RECT coords=161,23,167,32 
  href="javascript:showColor('<?Php echo $campo ?>','#999966')"><AREA shape=RECT coords=169,23,175,32 
  href="javascript:showColor('<?Php echo $campo ?>','#999999')"><AREA shape=RECT coords=177,23,183,32 
  href="javascript:showColor('<?Php echo $campo ?>','#9999CC')"><AREA shape=RECT coords=185,23,191,32 
  href="javascript:showColor('<?Php echo $campo ?>','#9999FF')"><AREA shape=RECT coords=193,23,199,32 
  href="javascript:showColor('<?Php echo $campo ?>','#CC9900')"><AREA shape=RECT coords=201,23,207,32 
  href="javascript:showColor('<?Php echo $campo ?>','#CC9933')"><AREA shape=RECT coords=209,23,215,32 
  href="javascript:showColor('<?Php echo $campo ?>','#CC9966')"><AREA shape=RECT coords=217,23,223,32 
  href="javascript:showColor('<?Php echo $campo ?>','#CC9999')"><AREA shape=RECT coords=225,23,231,32 
  href="javascript:showColor('<?Php echo $campo ?>','#CC99CC')"><AREA shape=RECT coords=233,23,239,32 
  href="javascript:showColor('<?Php echo $campo ?>','#CC99FF')"><AREA shape=RECT coords=241,23,247,32 
  href="javascript:showColor('<?Php echo $campo ?>','#FF9900')"><AREA shape=RECT coords=249,23,255,32 
  href="javascript:showColor('<?Php echo $campo ?>','#FF9933')"><AREA shape=RECT coords=257,23,263,32 
  href="javascript:showColor('<?Php echo $campo ?>','#FF9966')"><AREA shape=RECT coords=265,23,271,32 
  href="javascript:showColor('<?Php echo $campo ?>','#FF9999')"><AREA shape=RECT coords=273,23,279,32 
  href="javascript:showColor('<?Php echo $campo ?>','#FF99CC')"><AREA shape=RECT coords=281,23,287,32 
  href="javascript:showColor('<?Php echo $campo ?>','#FF99FF')"><AREA shape=RECT coords=1,34,7,43 
  href="javascript:showColor('<?Php echo $campo ?>','#006600')"><AREA shape=RECT coords=9,34,15,43 
  href="javascript:showColor('<?Php echo $campo ?>','#006633')"><AREA shape=RECT coords=17,34,23,43 
  href="javascript:showColor('<?Php echo $campo ?>','#006666')"><AREA shape=RECT coords=25,34,31,43 
  href="javascript:showColor('<?Php echo $campo ?>','#006699')"><AREA shape=RECT coords=33,34,39,43 
  href="javascript:showColor('<?Php echo $campo ?>','#0066CC')"><AREA shape=RECT coords=41,34,47,43 
  href="javascript:showColor('<?Php echo $campo ?>','#0066FF')"><AREA shape=RECT coords=49,34,55,43 
  href="javascript:showColor('<?Php echo $campo ?>','#336600')"><AREA shape=RECT coords=57,34,63,43 
  href="javascript:showColor('<?Php echo $campo ?>','#336633')"><AREA shape=RECT coords=65,34,71,43 
  href="javascript:showColor('<?Php echo $campo ?>','#336666')"><AREA shape=RECT coords=73,34,79,43 
  href="javascript:showColor('<?Php echo $campo ?>','#336699')"><AREA shape=RECT coords=81,34,87,43 
  href="javascript:showColor('<?Php echo $campo ?>','#3366CC')"><AREA shape=RECT coords=89,34,95,43 
  href="javascript:showColor('<?Php echo $campo ?>','#3366FF')"><AREA shape=RECT coords=97,34,103,43 
  href="javascript:showColor('<?Php echo $campo ?>','#666600')"><AREA shape=RECT coords=105,34,111,43 
  href="javascript:showColor('<?Php echo $campo ?>','#666633')"><AREA shape=RECT coords=113,34,119,43 
  href="javascript:showColor('<?Php echo $campo ?>','#666666')"><AREA shape=RECT coords=121,34,127,43 
  href="javascript:showColor('<?Php echo $campo ?>','#666699')"><AREA shape=RECT coords=129,34,135,43 
  href="javascript:showColor('<?Php echo $campo ?>','#6666CC')"><AREA shape=RECT coords=137,34,143,43 
  href="javascript:showColor('<?Php echo $campo ?>','#6666FF')"><AREA shape=RECT coords=145,34,151,43 
  href="javascript:showColor('<?Php echo $campo ?>','#996600')"><AREA shape=RECT coords=153,34,159,43 
  href="javascript:showColor('<?Php echo $campo ?>','#996633')"><AREA shape=RECT coords=161,34,167,43 
  href="javascript:showColor('<?Php echo $campo ?>','#996666')"><AREA shape=RECT coords=169,34,175,43 
  href="javascript:showColor('<?Php echo $campo ?>','#996699')"><AREA shape=RECT coords=177,34,183,43 
  href="javascript:showColor('<?Php echo $campo ?>','#9966CC')"><AREA shape=RECT coords=185,34,191,43 
  href="javascript:showColor('<?Php echo $campo ?>','#9966FF')"><AREA shape=RECT coords=193,34,199,43 
  href="javascript:showColor('<?Php echo $campo ?>','#CC6600')"><AREA shape=RECT coords=201,34,207,43 
  href="javascript:showColor('<?Php echo $campo ?>','#CC6633')"><AREA shape=RECT coords=209,34,215,43 
  href="javascript:showColor('<?Php echo $campo ?>','#CC6666')"><AREA shape=RECT coords=217,34,223,43 
  href="javascript:showColor('<?Php echo $campo ?>','#CC6699')"><AREA shape=RECT coords=225,34,231,43 
  href="javascript:showColor('<?Php echo $campo ?>','#CC66CC')"><AREA shape=RECT coords=233,34,239,43 
  href="javascript:showColor('<?Php echo $campo ?>','#CC66FF')"><AREA shape=RECT coords=241,34,247,43 
  href="javascript:showColor('<?Php echo $campo ?>','#FF6600')"><AREA shape=RECT coords=249,34,255,43 
  href="javascript:showColor('<?Php echo $campo ?>','#FF6633')"><AREA shape=RECT coords=257,34,263,43 
  href="javascript:showColor('<?Php echo $campo ?>','#FF6666')"><AREA shape=RECT coords=265,34,271,43 
  href="javascript:showColor('<?Php echo $campo ?>','#FF6699')"><AREA shape=RECT coords=273,34,279,43 
  href="javascript:showColor('<?Php echo $campo ?>','#FF66CC')"><AREA shape=RECT coords=281,34,287,43 
  href="javascript:showColor('<?Php echo $campo ?>','#FF66FF')"><AREA shape=RECT coords=1,45,7,54 
  href="javascript:showColor('<?Php echo $campo ?>','#003300')"><AREA shape=RECT coords=9,45,15,54 
  href="javascript:showColor('<?Php echo $campo ?>','#003333')"><AREA shape=RECT coords=17,45,23,54 
  href="javascript:showColor('<?Php echo $campo ?>','#003366')"><AREA shape=RECT coords=25,45,31,54 
  href="javascript:showColor('<?Php echo $campo ?>','#003399')"><AREA shape=RECT coords=33,45,39,54 
  href="javascript:showColor('<?Php echo $campo ?>','#0033CC')"><AREA shape=RECT coords=41,45,47,54 
  href="javascript:showColor('<?Php echo $campo ?>','#0033FF')"><AREA shape=RECT coords=49,45,55,54 
  href="javascript:showColor('<?Php echo $campo ?>','#333300')"><AREA shape=RECT coords=57,45,63,54 
  href="javascript:showColor('<?Php echo $campo ?>','#333333')"><AREA shape=RECT coords=65,45,71,54 
  href="javascript:showColor('<?Php echo $campo ?>','#333366')"><AREA shape=RECT coords=73,45,79,54 
  href="javascript:showColor('<?Php echo $campo ?>','#333399')"><AREA shape=RECT coords=81,45,87,54 
  href="javascript:showColor('<?Php echo $campo ?>','#3333CC')"><AREA shape=RECT coords=89,45,95,54 
  href="javascript:showColor('<?Php echo $campo ?>','#3333FF')"><AREA shape=RECT coords=97,45,103,54 
  href="javascript:showColor('<?Php echo $campo ?>','#663300')"><AREA shape=RECT coords=105,45,111,54 
  href="javascript:showColor('<?Php echo $campo ?>','#663333')"><AREA shape=RECT coords=113,45,119,54 
  href="javascript:showColor('<?Php echo $campo ?>','#663366')"><AREA shape=RECT coords=121,45,127,54 
  href="javascript:showColor('<?Php echo $campo ?>','#663399')"><AREA shape=RECT coords=129,45,135,54 
  href="javascript:showColor('<?Php echo $campo ?>','#6633CC')"><AREA shape=RECT coords=137,45,143,54 
  href="javascript:showColor('<?Php echo $campo ?>','#6633FF')"><AREA shape=RECT coords=145,45,151,54 
  href="javascript:showColor('<?Php echo $campo ?>','#993300')"><AREA shape=RECT coords=153,45,159,54 
  href="javascript:showColor('<?Php echo $campo ?>','#993333')"><AREA shape=RECT coords=161,45,167,54 
  href="javascript:showColor('<?Php echo $campo ?>','#993366')"><AREA shape=RECT coords=169,45,175,54 
  href="javascript:showColor('<?Php echo $campo ?>','#993399')"><AREA shape=RECT coords=177,45,183,54 
  href="javascript:showColor('<?Php echo $campo ?>','#9933CC')"><AREA shape=RECT coords=185,45,191,54 
  href="javascript:showColor('<?Php echo $campo ?>','#9933FF')"><AREA shape=RECT coords=193,45,199,54 
  href="javascript:showColor('<?Php echo $campo ?>','#CC3300')"><AREA shape=RECT coords=201,45,207,54 
  href="javascript:showColor('<?Php echo $campo ?>','#CC3333')"><AREA shape=RECT coords=209,45,215,54 
  href="javascript:showColor('<?Php echo $campo ?>','#CC3366')"><AREA shape=RECT coords=217,45,223,54 
  href="javascript:showColor('<?Php echo $campo ?>','#CC3399')"><AREA shape=RECT coords=225,45,231,54 
  href="javascript:showColor('<?Php echo $campo ?>','#CC33CC')"><AREA shape=RECT coords=233,45,239,54 
  href="javascript:showColor('<?Php echo $campo ?>','#CC33FF')"><AREA shape=RECT coords=241,45,247,54 
  href="javascript:showColor('<?Php echo $campo ?>','#FF3300')"><AREA shape=RECT coords=249,45,255,54 
  href="javascript:showColor('<?Php echo $campo ?>','#FF3333')"><AREA shape=RECT coords=257,45,263,54 
  href="javascript:showColor('<?Php echo $campo ?>','#FF3366')"><AREA shape=RECT coords=265,45,271,54 
  href="javascript:showColor('<?Php echo $campo ?>','#FF3399')"><AREA shape=RECT coords=273,45,279,54 
  href="javascript:showColor('<?Php echo $campo ?>','#FF33CC')"><AREA shape=RECT coords=281,45,287,54 
  href="javascript:showColor('<?Php echo $campo ?>','#FF33FF')"><AREA shape=RECT coords=1,56,7,65 
  href="javascript:showColor('<?Php echo $campo ?>','#000000')"><AREA shape=RECT coords=9,56,15,65 
  href="javascript:showColor('<?Php echo $campo ?>','#000033')"><AREA shape=RECT coords=17,56,23,65 
  href="javascript:showColor('<?Php echo $campo ?>','#000066')"><AREA shape=RECT coords=25,56,31,65 
  href="javascript:showColor('<?Php echo $campo ?>','#000099')"><AREA shape=RECT coords=33,56,39,65 
  href="javascript:showColor('<?Php echo $campo ?>','#0000CC')"><AREA shape=RECT coords=41,56,47,65 
  href="javascript:showColor('<?Php echo $campo ?>','#0000FF')"><AREA shape=RECT coords=49,56,55,65 
  href="javascript:showColor('<?Php echo $campo ?>','#330000')"><AREA shape=RECT coords=57,56,63,65 
  href="javascript:showColor('<?Php echo $campo ?>','#330033')"><AREA shape=RECT coords=65,56,71,65 
  href="javascript:showColor('<?Php echo $campo ?>','#330066')"><AREA shape=RECT coords=73,56,79,65 
  href="javascript:showColor('<?Php echo $campo ?>','#330099')"><AREA shape=RECT coords=81,56,87,65 
  href="javascript:showColor('<?Php echo $campo ?>','#3300CC')"><AREA shape=RECT coords=89,56,95,65 
  href="javascript:showColor('<?Php echo $campo ?>','#3300FF')"><AREA shape=RECT coords=97,56,103,65 
  href="javascript:showColor('<?Php echo $campo ?>','#660000')"><AREA shape=RECT coords=105,56,111,65 
  href="javascript:showColor('<?Php echo $campo ?>','#660033')"><AREA shape=RECT coords=113,56,119,65 
  href="javascript:showColor('<?Php echo $campo ?>','#660066')"><AREA shape=RECT coords=121,56,127,65 
  href="javascript:showColor('<?Php echo $campo ?>','#660099')"><AREA shape=RECT coords=129,56,135,65 
  href="javascript:showColor('<?Php echo $campo ?>','#6600CC')"><AREA shape=RECT coords=137,56,143,65 
  href="javascript:showColor('<?Php echo $campo ?>','#6600FF')"><AREA shape=RECT coords=145,56,151,65 
  href="javascript:showColor('<?Php echo $campo ?>','#990000')"><AREA shape=RECT coords=153,56,159,65 
  href="javascript:showColor('<?Php echo $campo ?>','#990033')"><AREA shape=RECT coords=161,56,167,65 
  href="javascript:showColor('<?Php echo $campo ?>','#990066')"><AREA shape=RECT coords=169,56,175,65 
  href="javascript:showColor('<?Php echo $campo ?>','#990099')"><AREA shape=RECT coords=177,56,183,65 
  href="javascript:showColor('<?Php echo $campo ?>','#9900CC')"><AREA shape=RECT coords=185,56,191,65 
  href="javascript:showColor('<?Php echo $campo ?>','#9900FF')"><AREA shape=RECT coords=193,56,199,65 
  href="javascript:showColor('<?Php echo $campo ?>','#CC0000')"><AREA shape=RECT coords=201,56,207,65 
  href="javascript:showColor('<?Php echo $campo ?>','#CC0033')"><AREA shape=RECT coords=209,56,215,65 
  href="javascript:showColor('<?Php echo $campo ?>','#CC0066')"><AREA shape=RECT coords=217,56,223,65 
  href="javascript:showColor('<?Php echo $campo ?>','#CC0099')"><AREA shape=RECT coords=225,56,231,65 
  href="javascript:showColor('<?Php echo $campo ?>','#CC00CC')"><AREA shape=RECT coords=233,56,239,65 
  href="javascript:showColor('<?Php echo $campo ?>','#CC00FF')"><AREA shape=RECT coords=241,56,247,65 
  href="javascript:showColor('<?Php echo $campo ?>','#FF0000')"><AREA shape=RECT coords=249,56,255,65 
  href="javascript:showColor('<?Php echo $campo ?>','#FF0033')"><AREA shape=RECT coords=257,56,263,65 
  href="javascript:showColor('<?Php echo $campo ?>','#FF0066')"><AREA shape=RECT coords=265,56,271,65 
  href="javascript:showColor('<?Php echo $campo ?>','#FF0099')"><AREA shape=RECT coords=273,56,279,65 
  href="javascript:showColor('<?Php echo $campo ?>','#FF00CC')"><AREA shape=RECT coords=281,56,287,65 
  href="javascript:showColor('<?Php echo $campo ?>','#FF00FF')"></MAP>
<?Php
}

/*Esta función calcula el número de día de la semana de una fecha indicada por parámetro. El número de día 
de la semana que devuelve corresponde con 0 en el caso de que la fecha sea un lunes, 1 en caso de ser martes, y así hasta 
llegar al 6, que corresponde con el domingo. */

function calcula_numero_dia_semana($dia,$mes,$ano){ 
    $numerodiasemana = date('w', mktime(0,0,0,$mes,$dia,$ano)); 
    if ($numerodiasemana == 0) 
       $numerodiasemana = 6; 
    else 
       $numerodiasemana--; 
    return $numerodiasemana; 
} 

/*Sirve para devolver el último día de un mes y año indicados por parámetro. El último día del mes se refiere al número de 
días que tiene un mes. Por ejemplo en enero 31, febrero 28 ó 29, ... diciembre 31. */

function ultimoDia($mes,$anio){ 
    $ultimo_dia=28; 
    while (checkdate($mes,$ultimo_dia + 1,$anio)){ 
       $ultimo_dia++; 
    } 
    return $ultimo_dia; 
} 

/* Funcion que quita la coma decimal del un valor */
function ndecimal($valor)
{
	$mil = 	explode(',', $valor);
	for ($j=0; $j<=count($mil)-1; $j++)
	{
		$base = $base.$mil[$j];					
	}	
	return $base;
}

/* Funcion que formatea los numeros segun el formato */
function formato_numero($numero, $decimales, $tipo)
{
	switch ($tipo){
		case 1: /* Formato Ingles sin separador de miles y separador decimal (.)
				Ejemplo : 5000.45 */
			$f_numero = number_format($numero,$decimales,'.','');
		break;	
		case 2: /* Formato Ingles con separador de miles (,) y separador decimal (.)
				Ejemplo : 5,000.45 */
			$f_numero = number_format($numero,$decimales,'.',',');
		break;			
		case 3: /* Formato ? (Español) con separador decimales (,) y sin separador de miles 
				Ejemplo : 5000,45 */
			$f_numero = number_format($numero,$decimales,',','');
		break;	
		case 4: /* Formato ? (Español) con separador decimales (,) y con separador de miles (.)
				Ejemplo : 5.000,45 */
			$f_numero = number_format($numero,$decimales,',','.');
		break;			
				
		
	}//Fin del switch ($tipo)
	
	return $f_numero;
}

/* Funcion para marcar las palabras buscadas en una cadena de texto */
/* $busqueda = texto buscado
   $cadena = texto encontrado para poder marcarlo de color 
   $color = color aplicado al texto 
   $cambio => 0 = No cambia el texto a mayuscula, lo deja intacto
   			  1 = Cambia el texto a mayuscula */
function marcar_cadena($busqueda, $cadena, $color, $cambio)
{
	/* Evalue si ha llegado texto */
	if (trim($busqueda) != "")
	{
		/* Divide la cadena en arreglos */
		$a_cadena=explode(' ',strtolower(trim($cadena)));
		/* Divide la cadena en arreglos cuando no hay cambios de forma del texto */
		$no_cadena=explode(' ',trim($cadena));
		/* Divide la cadena en arreglos */
		$a_busqueda=explode(' ',strtolower(trim($busqueda)));
		
		$texto_final = "";
		$i=0;
		//foreach($a_cadena as $valor_c)
		foreach($a_cadena as $puntero => $valor_c)
		{	
			$i++;
			if (trim($valor_c) != "")
			{	
				foreach($a_busqueda as $valor_b)
				{
					if (trim($valor_b) != "")
					{
						if (trim(caracteres_especiales($valor_b, 1)) == trim(caracteres_especiales($valor_c, 1)))
						{							
							if ($cambio == 0)
							{
								$valor_c = $no_cadena[$puntero]; //Se usa este arreglo para no alterar el texto
							}
							else
							{
								$valor_c = cambio_cadena($cambio,$valor_c);
							}
						
							$texto_color[$i] = "<font style='background:".$color."'>".$valor_c."</font>";
							break;
						}
						else
						{
							$texto_color[$i] = $no_cadena[$puntero]; //Se usa este arreglo para no alterar el texto
						}				  
					}//Fin del if (trim($valor) != "")
				}//Fin del foreach($busqueda_texto as $valor_c)
			}//Fin del if (trim($valor_c) != "")
		}//Fin del foreach($a_busqueda as $valor_b)
		
		if (isset($texto_color))
		{
			foreach($texto_color as $valor)
			{
				$texto_final = $texto_final.$valor."&nbsp;";
			}
		}
		return $texto_final;
	}//Fin del if (trim($busqueda) != "")
	else
	{
		return $cadena;
	}	
}//Fin del function marcar_cadena($busqueda, $cadena, $color)

/* Funcion que cambia caracteres especiales de MAYUSCULAS -> minusculas y 
viceversa */
function caracteres_especiales($cadena, $tipo)
{
	/* Tipo de cambio
	1 = MAYUSCULA -> minuscula
	2 = minuscula -> MAYUSCULA */
	
	/* Caracteres especiales minusculas*/
	$caracter[]="ñ";
	$caracter[]="á";
	$caracter[]="é";
	$caracter[]="í";				
	$caracter[]="ó";
	$caracter[]="ú";
	/* Caracteres especiales mayusculas*/
	$CARACTER[]="Ñ";
	$CARACTER[]="Á";
	$CARACTER[]="É";
	$CARACTER[]="Í";				
	$CARACTER[]="Ó";
	$CARACTER[]="Ú";
	
	
	for ($i=0; $i<=count($caracter)-1; $i++)
	{
		switch ($tipo){
  		  case 1:
			$cadena = str_replace ($CARACTER[$i], $caracter[$i], $cadena);
		  break;
		  case 2:
			$cadena = str_replace ($caracter[$i], $CARACTER[$i], $cadena);		
		  break;
		 }//Fin del switch ($tipo)			
	}//Fin de for ($i=0; $i<=count($caracter)-1; $i++)

	return $cadena;
}//Fin del function caracteres_especiales($cadena)

/* Pemirte cambiar el aspecto de cadena de texto */
function cambio_cadena($tipo, $cadena)
{
	switch ($tipo)
	{
		case 1:
			/* Cambia a mayusculas */
			$retorno = strtoupper($cadena);
		break;
		case 2:
			/* Cambia a minusculas */
			$retorno = strtolower($cadena);			
		break;		
		case 3:
			/* Pone a mayúsculas el primer caracter de una cadena	 */
			$retorno = ucfirst($cadena);		 		
		break;
		case 4:
			/* Pone en mayúsculas el primer caracter de cada palabra de una cadena */
			$retorno = ucwords($cadena);							
		break;	
	}//Fin del switch ($tipo)
	return $retorno;
}

/* Funcion que determina la edad de una persona */
function edad($anio_nac, $mes_nac, $dia_nac)
{
	/* Cálculo de la edad */  
	$hoy=date('Y-m-d');
	$anio_actual=explode('-',$hoy);
	if($anio_actual[1]>=$mes_nac)
	{
		$mss=$anio_actual[1]-$mes_nac;			
		$aaux=0;
	}
	else
	{
		$mss_sum=$anio_actual[1]+12;
		$mss=$mss_sum-$mes_nac;
		$aaux=-1;
	}
	$an1=$anio_actual[0]+$aaux;
	//$an1=$anio_actual[0]-$anio_nac;
	$an1=$an1-$anio_nac;
	return $an1.' años '.$mss.' mes(es)';
}//Fin del edad($anio_nac, $mes_nac, $dia_nac)

/* Funcion que devuelve una cadena de parametros para iniciar la busqueda de un sql */
function submit_parametros($cant, $arreglo)
{
	for ($i=1;$i<=$cant;$i++)
	{
		/* Verifica si esta seteada una posicion del arreglo */
		if (isset($arreglo[$i]))
		{
			$cod = $arreglo[$i]."-"; 
		 	$cod2 = "$cod2$cod";					
		}//Fin del if (isset($arreglo[$i]))
	}//Fin del for ($i=1;$i<=$cant;$i++)
	return $cod2;	
}//Fin del function submit_parametros($cant, $arreglo)

/*Funcion que devuelve fechas futuras a partir de una fecha y un incremento*/
function fechas_futuras($fecha, $incremento)
{
	list($ann, $mes, $dia) = split('[/.-]', $fecha); //Se descompone la fecha	
	$fecha_fut = date( "Y-m-d", mktime(0,0,0,$mes, $dia + $incremento, $ann));
	return $fecha_fut;
}//Fin del function fechas_futuras($fecha, $incremento)

/* Funcion que corta una cadena y agrega al final 3 puntos suspesivos */
function cadena_mas($cadena, $cant_caracter)
{
	$cadena_total = strlen($cadena);	  
	$cortar_cadena = substr ($cadena, 0, $cant_caracter);
	
	if ($cadena_total > $cant_caracter)
	{
	  	$cortar_cadena = $cortar_cadena."...";
	}//Fin del  if ($cadena_total > $cant_cadena) 
	
	return $cortar_cadena;  
}//FIn del function cadena_mas()

/* Funcion que muestra un mesaje parpadenado */
/*
mensaje = Texto a mostrar
elemento = nombre del objeto que va a parpadear
color_fondo = color del fondo del texto
color_letra = color de la letra del texto
*/
function blink($mensaje, $elemento, $color_fondo, $color_letra)
{
	/* Es necesario que en esta funcion se inicialice el siguiente codigo en el body de la pagina
	onLoad="setInterval('parpadeo(nombre_objeto_html)',500)" */	
	echo "<script>var par=false;
		function parpadeo(objeto) {
		document.getElementById(objeto).style.visibility= (par) ? 'visible' : 'hidden';
		par = !par;
		}
		</script>
		<span id='".$elemento."'>
		<table class='Texto_normal_9'>
		<tr>
		<td bgcolor='".$color_fondo."' style='color:".$color_letra."'><strong>".$mensaje."</strong></td>
		</tr>
	    </table>
		</span>";
}//Fin del blink($mensaje, $elemento, $color_fondo, $color_letra)	

//Efecto de marcado de una fila de la table al pasar el cursor x la superficie
function focus_row($resaltar_text, $resaltar_back, $undo_resaltar_text, $undo_resaltar_back)
{
    /*$Evento="onMouseOver=".'"this.style.background='."'".$color_1."'; "." this.style.color='".$color_2."'".'" '."onMouseOut=".'"this.style.background='."'".$color_3."'; "." this.style.color='".$color_4."'".'"';*/
   $Evento="onMouseOver=".'"this.className='."'".$resaltar_text."'; "." this.className='".$resaltar_back."'".'" '."onMouseOut=".'"this.className='."'".$undo_resaltar_text."'; "." this.className='".$undo_resaltar_back."'".'"';
 return $Evento;
}

/* Funcion que crear una caja de texto y la oculta, con el fin de evitar el submit, cuando en un formulario existe una sola
caja de texto */
function noEnterSubmit()
{
	echo "<input name='evitar_envio' type='text' id='evitar_envio' size='1' maxlength='1' readonly style='display:none'>";
}

/* Función que permite la resta entre dos horas o fechas 
time_date_cox: Hora o fecha de avance del tiempo de conexion al sistema. Ejemplo: 11:55 am
time_date_log: hora o fecha de conexion al sistema. Ejemplo: 10:30 am
$op = 0 representa horas, 1 representa dias
*/
function difenciaTimeDate($time_date_cox, $time_date_log, $op)
{	
	$s = strtotime($time_date_cox)-strtotime($time_date_log);
	$d = intval($s/86400);
	$s -= $d*86400;
	$h = intval($s/3600);
	$s -= $h*3600;
	$m = intval($s/60);
	$s -= $m*60;
	
	/* Evalua cuando son horas */
	if ($op == 0)
	{
		$dif= (($d*24)+$h).hrs." ".($m)."min";
	}
	else ///* Caso contrario cuando son dias */
	{
		$dif= $d.$space.dias." ".abs($h).hrs." ".abs($m)."min";
	}//Fin del if ($op == 0)
	return $dif;
}//Fin del function difenciaTimeDate()

// Funcion para subir imagenes 
function upLoadImg($archivo, $nombreup, $sizeup, $directorioup)
{				
		//Tamaño de la imagen
		$size=$_FILES['archivo']['size'];
		
		//variable de control para sacar la extencion real de la imagen
		$extImg=explode(".",$_FILES['archivo']['name']);
		$extension=strtolower($extImg[1]);
		
		//ruta fisica de la imagen		
		$archivo= $_FILES['archivo']['tmp_name'];
		
		if($extension=="jpg" || $extension=="jpeg" || $extension=="png"

)//valida la extensión del archivo 
		{ 	
			
			if($size < $sizeup) //valida el tamaño del archivo en bytes
			{
				$archivo_name = $nombreup.".".strtolower($extImg[1]);
				$directorioFinal=$directorioup.$archivo_name;
				if(!copy($archivo, $directorioFinal)) 				
				{				
?>					<script LANGUAGE="JavaScript"> alert ("Error al copiar el archivo")</script>
<?Php				return "0";		
				} 
				else 
				{ 			
					return $directorioFinal;														
				}	
			} 
			else 
			{		
?>					
					<script LANGUAGE="JavaScript">alert ("El archivo supera los <?Php echo round($sizeup / 1024); ?> Kb");</script>
<?Php				return "0";	 
			} 
		} 
		else 
		{ 	
?>
			<script LANGUAGE="JavaScript">	
				alert ("El formato de archivo no es valido, solo archivos (.jpg .jpeg .png)");
			</script>
<?Php		return "0";	
		}
}		 

/* cantidad de imagenes a generar, $num= Muestra el numero de proceso actual) */
function wizard($cant,$num)
{
 for($x=1; $x<=$cant;$x++)
 {
  if($num==$x)
  {
   echo "<td align='center' class='Alertas3' background='../../mascaras/model1/imagenes/32x32/img-all.png'>".$x."</td>";
  }else{    
   echo "<td align='center' class='Titulos2' background='../../mascaras/model1/imagenes/32x32/img-null.png'>".$x."</td>";
  }
 }
}

/* Funcion para subir imagenes de cualquier objeto file, y mantiene el nombre original */
function upLoadImg_2($input, $archivo, $nombreup, $sizeup, $directorioup)
{				
		//Tamaño de la imagen
		$size=$_FILES[$input]['size'];
		
		//variable de control para sacar la extencion real de la imagen
		$extImg=explode(".",$_FILES[$input]['name']);

		$extension=strtolower($extImg[1]);
		
		//ruta fisica de la imagen		
		$archivo= $_FILES[$input]['tmp_name'];
		
		if($extension=="jpg" || $extension=="jpeg" || $extension=="png" || $extension=="gif")//valida la extensión del archivo 
		{ 	
			
			if($size < $sizeup) //valida el tamaño del archivo en bytes
			{
				/* Control para asignar el nombre original al archivo */
				if (trim($nombreup) == "")
				{
					$nombreup = $extImg[0];	
				}
				$archivo_name = $nombreup.".".strtolower($extImg[1]);
				$directorioFinal=$directorioup.$archivo_name;		

				if(!copy($archivo, $directorioFinal)) 				
				{ ?>					
					<script LANGUAGE="JavaScript"> alert ("Error al copiar el archivo")</script>
<?Php				return "0";		
				} 
				else 
				{ 			
					return $directorioFinal;														
				}	
			} 
			else 
			{		
?>					
					<script LANGUAGE="JavaScript">alert ("El archivo supera los <?Php echo round($sizeup / 1024); ?> Kb");</script>
<?Php				return "0";	 
			} 
		} 
		else 
		{ 	
?>
			<script LANGUAGE="JavaScript">	
				alert ("El formato de archivo no es valido, solo archivos (.jpg .gif . png)");
			</script>
<?Php		return "0";	
		}
} 


//foreach($_POST as $nombre_campo => $valor){ 
//   $asignacion = "\$" . $nombre_campo . "='" . $valor . "';"; 
//   //echo $asignacion."<br>";
//   eval($asignacion); 
//} 
////extract($_POST);

/* Funcion que marca la fila donde se encuentra el cursor */
function focus_row_select_id($name, $resaltar_text, $resaltar_back, $undo_resaltar_text, $undo_resaltar_back)
{
   $Evento="onfocus= 
".'"'."style.backgroundColor = 'yellow'; ".'document.getElementById('."'".$name."'".').className='."'".$resaltar_text."'; "." document.getElementById("."'".$name."'".").className='".$resaltar_back."'".'" '.
"onblur= 
".'"'." style.backgroundColor = 'transparent' ; ".'document.getElementById('."'".$name."'".').className='."'".$undo_resaltar_text."'; "." document.getElementById("."'".$name."'".").className='".$undo_resaltar_back."'".'"';
	return $Evento;
}

/* Funcion para generar una ventana flotante 
Para usar esta funcion es necesario llamar a la libreria 
- ventana_flot.js
- definir en el BODY onmousemove="SetValues(event)"
- Colocar una hidden TxtPos para el almacenamiento de la posicion
$width = tamaño de la ventana
$height = alto de la ventana
$left = ubicacion del lado izquierdo de la ventana
$top = ubicacion superior de la ventana
$pagina = archivo que se cargara dentro de la ventana
$title = titulo de la ventana
$cerrar = indica el nombre que se ocultara de la ventana Cabecera o cuerpo
*/
function windowsFlotFrame($width, $height, $left, $top, $pagina, $title, $cerrar, $minimizar )
{ 
//" style= 'position:absolute; left:".$Xy[0]."px; top:".$Xy[1]."px; width: 500px;'"
?>
<table id="floatwin" height="<?Php echo $height; ?>"  style="position:absolute; left:<?Php echo $left; ?>; top:<?Php echo $top; ?>;" cellspacing="0">
	<tr class="Cabecera1" height="10">
    	<td width="<?php echo $width; ?>" onmouseover="document.body.style.cursor='move'"  onmouseout="document.body.style.cursor='auto'" onmousedown="startmove(); document.getElementById('floatwin').style.opacity=0.5;" onmouseup="moving=0; document.getElementById('floatwin').style.opacity=1;">
<?php echo $title; ?>
		</td>
   	  <td width="20" align="right" valign="top">
      <img src="../../mascaras/model1/imagenes/32x32/btn_minimizar.png" id="id_min" width="16" height="16" title="Minimizar" style="cursor:pointer" onclick="document.getElementById('floatbody').style.visibility='hidden'; 
 
           document.getElementById('floatwin').style.bottom=0; 
           document.getElementById('floatwin').width=250; 
           document.getElementById('id_min').style.visibility='hidden';
           document.getElementById('id_max').disabled=false;"></td>
   	  <td width="20" align="right" valign="top">
      <img src="../../mascaras/model1/imagenes/32x32/btn_maximizar.png"  id="id_max" width="16" height="16" title="Maximizar" style="cursor:pointer"  onclick="document.getElementById('floatbody').style.visibility='visible'; 
                   document.getElementById('floatwin').style.left=<?Php echo $left; ?>; 
                   document.getElementById('floatwin').style.top=<?Php echo $top; ?>; 
                   document.getElementById('floatwin').width=<?Php echo $width; ?>; 
                   document.getElementById('id_max').disabled = false; 
                   document.getElementById('id_min').style.visibility='visible'">      
      </td>
		<td width="20" align="right" valign="top"><img src="../../mascaras/model1/imagenes/32x32/btn_close.png" width="16" height="16" title="Cerrar" style="cursor:pointer" onclick="document.getElementById('<?Php echo $cerrar; ?>').style.visibility='hidden';">        
        </td>
   </tr>
   <tr class="Fondo" id="floatbody">
   		<td colspan="4"><iframe name="id_chat" id="id_chat" height="100%" width="100%" src="<?Php echo $pagina; ?>"  frameborder="0"></iframe></td>
   </tr>
</table> 
<script language="javascript">
document.getElementById('id_max').disabled=true;

</script>
<?php	
}

/* Funciones que detectan la codificación de un string y de esta manera realizan la conversión de codificación sólo si es necesario. */
//Función que converte un string a ISO-8859-1 (LATIN1)
function latin1($txt) 
{ 
	$encoding = mb_detect_encoding($txt, 'ASCII,UTF-8,ISO-8859-1'); 
	if ($encoding == "UTF-8") {     
		$txt = utf8_decode($txt); } return $txt;
	}
	
//Función que converte un string a UTF-8
function utf8($txt) 
{ 
	$encoding = mb_detect_encoding($txt, 'ASCII,UTF-8,ISO-8859-1'); 
	if ($encoding == "ISO-8859-1") {     
		$txt = utf8_encode($txt); } return $txt;
	}

/* Funcion para marcar las palabras buscadas en una cadena de texto */
/* $busqueda = texto buscado
   $cadena = texto encontrado para poder marcarlo de color 
   $color = color aplicado al fondo
   $colortext = color aplicado al texto
   $cambio => 0 = No cambia el texto a mayuscula, lo deja intacto
   			  1 = Cambia el texto a mayuscula */
function marcarCadenaColor($busqueda, $cadena, $color, $colortext, $cambio)
{
	/* Evalue si ha llegado texto */
	if (trim($busqueda) != "")
	{
		/* Divide la cadena en arreglos */
		$a_cadena=explode(' ',strtolower(trim($cadena)));
		/* Divide la cadena en arreglos cuando no hay cambios de forma del texto */
		$no_cadena=explode(' ',trim($cadena));
		/* Divide la cadena en arreglos */
		$a_busqueda=explode(' ',strtolower(trim($busqueda)));
		
		$texto_final = "";
		$i=0;
		//foreach($a_cadena as $valor_c)
		foreach($a_cadena as $puntero => $valor_c)
		{	
			$i++;
			if (trim($valor_c) != "")
			{	
				foreach($a_busqueda as $valor_b)
				{
					if (trim($valor_b) != "")
					{
						if (trim(caracteres_especiales($valor_b, 1)) == trim(caracteres_especiales($valor_c, 1)))
						{							
							if ($cambio == 0)
							{
								$valor_c = $no_cadena[$puntero]; //Se usa este arreglo para no alterar el texto
							}
							else
							{
								$valor_c = cambio_cadena($cambio,$valor_c);
							}
						
							$texto_color[$i] = "<font style='background:".$color."; color:".$colortext."'>".$valor_c."</font>";
							break;
						}
						else
						{
							$texto_color[$i] = $no_cadena[$puntero]; //Se usa este arreglo para no alterar el texto
						}				  
					}//Fin del if (trim($valor) != "")
				}//Fin del foreach($busqueda_texto as $valor_c)
			}//Fin del if (trim($valor_c) != "")
		}//Fin del foreach($a_busqueda as $valor_b)
		
		if (isset($texto_color))
		{
			foreach($texto_color as $valor)
			{
				$texto_final = $texto_final.$valor."&nbsp;";
			}
		}
		return $texto_final;
	}//Fin del if (trim($busqueda) != "")
	else
	{
		return $cadena;
	}	
}//Fin del function marcar_cadena($busqueda, $cadena, $color)

/*
Funcion que oculta las filas del detalle de una consulta, la cual empieza siempre en el elemento 1 */
function ocultarDetalle($cant)
{
	for ($i=1; $i<=$cant; $i++)
	{ ?>
		<script type="text/javascript">
            ShowHide('detalle[<?Php echo $i; ?>]');
            ShowHide('menos[<?Php echo $i; ?>]');				
        </script>
	<?Php	
	}	
}

/* Funcion para subir cualquier objeto file, y mantiene el nombre original */
function upLoadFile($input, $archivo, $nombreup, $sizeup, $directorioup)
{				
		//Tamaño de la imagen
		$size=$_FILES[$input]['size'];
//print_r ($_FILES[$input]);
		//variable de control para sacar la extencion real de la imagen
		$extImg=explode(".",$_FILES[$input]['name']);

		$extension=strtolower($extImg[count($extImg)-1]);
		
		//ruta fisica de la imagen		
		$archivo= $_FILES[$input]['tmp_name'];
		
		//if($extension=="jpg" || $extension=="jpeg" || $extension=="png" || $extension=="gif")//valida la extensión del archivo 
		//{ 	
			
			if($size <= $sizeup) //valida el tamaño del archivo en bytes
			{
				/* Control para asignar el nombre original al archivo */
				if (trim($nombreup) == "")
				{
					$nombreup = $extImg[0];	
				}
				$archivo_name = $nombreup.".".strtolower($extImg[count($extImg)-1]);
				$directorioFinal=$directorioup.$archivo_name;		

				if(!copy($archivo, $directorioFinal)) 				
				{ ?>					
					<script LANGUAGE="JavaScript"> alert ("Error al copiar el archivo")</script>
<?Php				return "0";		
				} 
				else 
				{ 			
					return $directorioFinal;														
				}	
			} 
			else 
			{		
?>					
					<script LANGUAGE="JavaScript">alert ("El archivo supera los <?Php echo round($sizeup / 1024); ?> Kb");</script>
<?Php				return "0";	 
			} 
		/*} 
		else 
		{ */	
?>
			<!--<script LANGUAGE="JavaScript">	
				alert ("El formato de archivo no es valido, solo archivos (.jpg .gif . png)");
			</script>-->
<?Php		//return "0";	
		//}
}?>