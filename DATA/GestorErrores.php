<?php
/* 
 * Copyright (c)2015 - EN Systems Apps
 * Función de gestión de errores
 * http://ensystems.ddns.net
 */

register_shutdown_function( "fatal_handler" );
function fatal_handler() {
  $error = error_get_last();

  if( $error !== NULL) {    
    if (in_array($error['type'],Array(E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR, E_PARSE))){ 
        $errno   = $error["type"];
        $errfile = $error["file"];
        $errline = $error["line"];
        $errstr  = $error["message"];
        echo(format_error( $errno, $errstr, $errfile, $errline));    
    }
  }
}
function format_error( $errno, $errstr, $errfile, $errline ) {
	//error_clear_last();   
   $arc=realpath(str_replace(basename( __FILE__ ),'',__FILE__).'..'.DIRECTORY_SEPARATOR).'/administrador/FRONT/fatalerror.html';
   $content= "";
   if(file_exists($arc)){
       //header('HTTP/1.0 500 Internal Server Error');
       $fp = fopen($arc,'r');
       $content = fread($fp, filesize($arc)); 
   }//else	
   if(isset($_SESSION['Ses_Prs_Cod'])&&$_SESSION['Ses_Prs_Cod']==1)	   
	  $content.= "<style>.r{text-align:right;padding-right:20px;} pre{padding: 3px 9.5px;margin: 4px 0 4px;}</style><div class='col-xs-12'><div class='error-container' style='margin-top:0;'><div class='well' style='margin-top:0;padding-top:10px;'>
	  <table style='width:100%'>
	  <thead><tr style='height:0;'><th width='100'></th><th width='75'></th><th></th></tr><tr><th style='height:40px;' class='r'><span class='blue bigger-125'>Item</span></th><th colspan='2'><span class='blue bigger-125'>Description</span></th></tr></thead>
	  <tbody>
	  <tr><th class='r'>Error:</th><td colspan='2'><pre>$errstr</pre></td></tr>
	  <tr><th class='r'>Error No.:</th><td><pre>$errno</pre></td></tr>
	  <tr><th class='r'>Archivo:</th><td colspan='2'><pre>$errfile</pre></td></tr>
	  <tr><th class='r'>Linea:</th><td><pre>$errline</pre></td></tr>
	  <!--<tr class='r'><th>Trace</th><td colspan='2'><pre></pre></td></tr>-->
	  </tbody>
	  </table></div></div></div>";
    return $content;
}