<?php
require_once("php-barcode.php");
(!$_GET['code'])? $code='000000000000' : $code = $_GET['code'];
(!$_GET['scale']) ? $scale = 1 : $scale = $_GET['scale'];
barcode_print($code,encoding,$scale,mode);
?>