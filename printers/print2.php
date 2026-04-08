<?php 
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
include("./printers.php");
if(!isset($GLOBALS['printer'])||!isset($GLOBALS['content'])||!isset($printersTCP[$GLOBALS['printer']]))
    throw new Exception("Las configuraciones no son correctas!");

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if ($socket === false) {
    throw new Exception("socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "\n"); 
} 
$result = socket_connect($socket, $printersTCP[$printer]['host'], $printersTCP[$printer]['port']);
if ($result === false) {
    throw new Exception("socket_connect() failed.\nReason: ($result) " . socket_strerror(socket_last_error($socket)) . "\n");
} 

socket_write($socket, $content, strlen($content));
socket_close($socket)
?>