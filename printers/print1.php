<?php 
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
if(!isset($GLOBALS['printer'])||!isset($GLOBALS['content']))
    throw new Exception("Las configuraciones no son correctas!");

if($ph = printer_open($printer)){
   // Set print mode to RAW and send PDF to printer
   printer_set_option($ph, PRINTER_MODE, "RAW");
   printer_write($ph, $content);
   printer_close($ph);
}
else throw new Exception("No se pudo conectar a la Impresora \"$printer\"!");
?>