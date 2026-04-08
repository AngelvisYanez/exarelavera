<?php
/* 
 * Copyright (c)2015 - EN Systems Apps
 * http://ensystems.ddns.net
 */
function pageOpen(){
    return "^XA";
}
function pageClose(){
    return "^XZ";
}
function template1($codigo, $producto){
    $content="";
    $content.="^AF,N,10,5";
    $content.="^FO100,25^BY1";
    $content.="^ADN,11,7^BCN,43,Y,Y,N^FD".$codigo."^FS";
    $content.="^CF0,30,30^FO95,70";
    $content.="^ADN,5,4 ^FB300,10,-3,J";
    $content.="^FD".$producto."^FS";
    return $content;
}
function template2($codigo, $producto){
    $content="";
    $content.="^AF,N,10,5";
    $content.="^FO440,25^BY1";
    $content.="^ADN,11,7^BCN,43,Y,Y,N^FD".$codigo."^FS";
    $content.="^CF0,30,30^FO435,70";
    $content.="^ADN,5,4 ^FB300,10,-3,J";
    $content.="^FD".$producto."^FS";
    return $content;
}
?>