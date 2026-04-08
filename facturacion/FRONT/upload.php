<?php

if ($_POST){ 

$num = md5(time());

//MAIL BODY
$body = "
<html>
<head>
<title>Formulario Trabaja con nosotros</title>
</head>
<body style='background:#EEE; padding:30px;'>
<h2 style='color:#767676;'>Trabaja con nosotros</h2>";

$body .= "
<strong style='color:#0090C6;'>Nombre: </strong>
<span style='color:#767676;'>" . $_POST["nombre"] . "</span>";

$body .= "
<strong style='color:#0090C6;'>Apellidos: </strong>
<span style='color:#767676;'>" . $_POST["apellido"] . "</span>";

$body .= "
<strong style='color:#0090C6;'>Email: </strong>
<span style='color:#767676;'>" . $_POST["email"] . "</span>";

$body .= "
<strong style='color:#0090C6;'>Teléfono: </strong>
<span style='color:#767676;'>" . $_POST["telefono"] . "</span>";

$body .= "</body></html>";

$_name=$_FILES["filead"]["name"];
$_type=$_FILES["filead"]["type"];
$_size=$_FILES["filead"]["size"];
$_temp=$_FILES["filead"]["tmp_name"]; 

if( strcmp($_name, "") ) //FILES EXISTS
{ 
$fp = fopen($_temp, "rb");
$file = fread($fp, $_size);
$file = chunk_split(base64_encode($file)); 

// MULTI-HEADERS Content-Type: multipart/mixed and Boundary is mandatory.
$headers = "From: GME <gme@garciamunte.com>\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: multipart/mixed; "; 
$headers .= "boundary=".$num."\r\n";
$headers .= "--".$num."\n"; 

// HTML HEADERS 
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "Content-Transfer-Encoding: 8bit\r\n";
$headers .= "".$body."\n";
$headers .= "--".$num."\n"; 

// FILES HEADERS 
$headers .= "Content-Type:application/octet-stream "; 
$headers .= "name=\"".$_name."\"r\n";
$headers .= "Content-Transfer-Encoding: base64\r\n";
$headers .= "Content-Disposition: attachment; ";
$headers .= "filename=\"".$_name."\"\r\n\n";
$headers .= "".$file."\r\n";
$headers .= "--".$num."--"; 

}else { //FILES NO EXISTS

// HTML HEADERS
$headers = "From: GME \r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "Content-Transfer-Encoding: 8bit\r\n";
} 

// SEND MAIL
mail("laroca73@gmail.com", "WEB - Trabaja con nosotros" , $body, $headers);

echo "<div class='ok'>
<strong>El formulario se ha enviado correctamente.</strong></div>";

}
?>
<form accept-charset="utf-8" action="upload.php" class="trabaja" enctype="multipart/form-data" id="formulario" method="post">

<input type="text" id="nombre" name="nombre" value="Nombre" />
<input type="text" id="apellido" name="apellido" value="Apellidos" />
<input type="text" id="email" name="email" value="e-mail" />
<input type="text" id="telefono" name="telefono" value="Telefono" />
<input type="file" id="filead" name="filead" />
<input type="checkbox" id="condiciones" name="condiciones" />
<input type="submit" value="Enviar Formulario" />

</form>