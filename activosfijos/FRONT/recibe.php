<?php 
if(isset($uploadImg))
{
$archivo = $_FILES['foto_activo']['name'];
$nombre=explode('.',$archivo);  
$last=count($nombre)-1;
$responce['Prv_Nom']=$_POST['Prv_Nom'];
$responce['nombre']=$nombre[0];
$responce['extension']=$nombre[$last];
echo json_encode($responce);
}
?>