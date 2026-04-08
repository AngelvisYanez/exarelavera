<?Php

/**
* Permite tomar las variables GET y POST
*/

function getBody(){
    return json_decode(file_get_contents('php://input'), true);
}

// $numero4 = ;
$bodyPostData = getBody();
if($bodyPostData != null && $bodyPostData != ''){
    // print_r( $bodyPostData);
    $tags4 = array_keys($bodyPostData);
    $valores4 = get_object_vars($bodyPostData);

    foreach ($valores4 as $key => $value) {
        $$tags4[$key] = $value;
    }
}

/**
* Variables GET
*/
$numero = count($_GET);
$tags = array_keys($_GET);// obtiene los nombres de las varibles
$valores = array_values($_GET);// obtiene los valores de las varibles

for($i=0;$i<$numero;$i++){ // crea las variables y les asigna el valor
$$tags[$i]=$valores[$i];
}

/**
* Variables POST
*/
$numero2 = count($_POST);
$tags2 = array_keys($_POST); // obtiene los nombres de las varibles
$valores2 = array_values($_POST);// obtiene los valores de las varibles

for($i=0;$i<$numero2;$i++){ // crea las variables y les asigna el valor
$$tags2[$i]=$valores2[$i];
}

/**
* Variables SESSION
*/
if(!isset($_SESSION)){ session_start(); }
$numero3 = count($_SESSION);
$tags3 = array_keys($_SESSION); // obtiene los nombres de las varibles
$valores3 = array_values($_SESSION);// obtiene los valores de las varibles

for($i=0;$i<$numero3;$i++){ // crea las variables y les asigna el valor
$$tags3[$i]=$valores3[$i];
}

$DirSep=DIRECTORY_SEPARATOR;
$APP_REAL_PATH=realpath(str_replace(basename( __FILE__ ),'',__FILE__).'..'.$DirSep  .'..'.$DirSep );

include_once(__DIR__.'/debugbar.php');
include_once(__DIR__.'/monolog.php');
