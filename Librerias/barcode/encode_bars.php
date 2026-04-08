<?php
require_once("php-barcode-configure.php");
function barcode_gen_ean_sum($ean){
 $arrayBarCode = str_split($ean);
 $arrayBarCodeReverse = array_reverse($arrayBarCode);
 for ($i=0; $i<(count($arrayBarCodeReverse) / 2); $i++){
  $addOdd += $arrayBarCodeReverse[$i*2];
  $addEven += $arrayBarCodeReverse[($i*2)+1];
 }
 $addTotal = ($addOdd * 3) + $addEven;
 return(substr(10 - substr($addTotal,-1,1),-1,1));
}

function barcode_encode_ean($ean, $encoding = "EAN-13"){
 $digits=array(3211,2221,2122,1411,1132,1231,1114,1312,1213,3112);
 $mirror=array("000000","001011","001101","001110","010011","011001","011100","010101","010110","011010");
 $guards=array("9a1a","1a1a1","a1a");

 $ean=trim($ean);
 if (eregi("[^0-9]",$ean)){
  return array("text"=>"Codigo EAN incorrecto");
 }
 $encoding=strtoupper($encoding);
 if ($encoding=="ISBN"){
  (!ereg("^978", $ean)) ? $ean="978".$ean : null;
 }
 (ereg("^978", $ean)) ? $encoding="ISBN" : null;
 if (strlen($ean)<12 || strlen($ean)>13){
  return array("text"=>"Codigo $encoding no valido (debe ser de 12 o 13 digitos)");
 }

 $ean = substr($ean,0,12);
 $eansum = barcode_gen_ean_sum($ean);
 $ean = $ean . $eansum;
 $line=$guards[0];
 for ($i=1; $i<13; $i++){
  $str = $digits[$ean[$i]];
  ($i<7 && $mirror[$ean[0]][$i-1]==1) ?  $line .= strrev($str) : $line.=$str;
  ($i==6) ? $line.=$guards[1] : null;
 }
 $line .= $guards[2];

 $pos=0;
 $text="";
 for ($a=0; $a<13; $a++){
  if ($a>0) $text.=" ";
  $text.="$pos:12:{$ean[$a]}";
  if ($a==0) $pos+=12;
  else
   if ($a==6) $pos+=12;
    else $pos+=7;
 }
 if (showNumbersInBarCode == "true"){
  return array("encoding" => $encoding, "bars" => $line, "text" => $text);
 }
 else{
  return array( "encoding" => $encoding, "bars" => $line, "text" => "");
 }
}
?>