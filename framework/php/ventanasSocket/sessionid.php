<?php 
if(isset($_GET['pull'])){
    if(session_id()==='') session_start();
    echo json_encode($_SESSION);
}else
    echo json_encode(array('msg'=>'Acceso no Permitido'));
//session_start(); var_dump($_SESSION);