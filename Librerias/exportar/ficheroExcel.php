<?php
ini_set('post_max_size','1024M');
ini_set('memory_limit','1024M');
ini_set('upload_max_filesize','1024M');
ini_set('max_execution_time', 0);
if(empty($_POST)){
    $data = file_get_contents('php://input');
    parse_str($data,$_POST);   
}

if(!isset($filename))$filename=(isset($_POST)&&isset($_POST['nombre'])&&trim($_POST['nombre'])!=''?trim($_POST['nombre']):NULL);
if(!isset($worksheet))$worksheet=(isset($_POST)&&isset($_POST['hoja'])?trim($_POST['hoja']):'Hoja 1');

header("Content-Type: application/vnd.ms-excel; charset=UTF-8"); 
header("Content-Transfer-Encoding: binary"); 
header("Content-disposition: attachment; filename=".(empty($filename)?'ficheroExcel.xls':$filename.'.xls')); 
header("Pragma: no-cache"); 
header("Cache-Control: must-revalidate, post-check=0, pre-check=0"); 
header("Expires: 0");
if(isset($_POST['fileDownloadToken'])) setcookie("fileDownloadToken",$_POST['fileDownloadToken'], time()+180000, "/");

if( isset($_POST) && isset($_POST['datos_a_enviar']) ){
    $excel_content=((!!mb_detect_encoding($_POST['datos_a_enviar'], 'UTF-8', true))?$_POST['datos_a_enviar']:utf8_encode($_POST['datos_a_enviar']));
    if(isset($_POST['new'])) $excel_content = stripslashes($excel_content);
    // Extraer bloques <style> para colocarlos en <head> (Excel en producción interpreta mejor los estilos ahí)
    $styles_in_head = '';
    if(preg_match_all('/<style[^>]*>[\s\S]*?<\/style>/i', $excel_content, $matches)){
        $styles_in_head = implode("\n", $matches[0]);
        $excel_content = preg_replace('/<style[^>]*>[\s\S]*?<\/style>\s*/i', '', $excel_content);
    }
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><meta http-equiv="content-type" content="application/vnd.ms-excel; charset=UTF-8"><head>'.$styles_in_head.'<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>'.$worksheet.'</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head><body>';    
    echo $excel_content;    
    echo '</body></html>';
    exit();
}else if(isset($excel_content)){ echo $excel_content; exit(); }