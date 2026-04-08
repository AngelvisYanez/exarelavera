<?php

require_once("php-barcode-configure.php");
require_once("encode_bars.php");

$font_loc="fonts/arialbd.ttf";
if (isset($_ENV['windir']) && file_exists($_ENV['windir'])){
 $font_loc=$_ENV['windir']."\Fonts\arialbd.ttf";
}

function barcode_outimage($text, $bars, $scale=1, $mode , $total_y=0, $space = ''){
 global $bar_color, $bg_color, $text_color;
 global $font_loc;
 ($scale < 1) ? $scale = 1 : null;
 $total_y=(int)($total_y);
 if ($total_y < 1)
  $total_y = (int)$scale * barcodeHeight;
 if(!$space)
  $space=array('top'   => 2 * $scale,
               'bottom'=> 2 * $scale,
               'left'  => 2 * $scale,
               'right' => 2 * $scale);

 $xpos=0;
 $width=true;
 for ($i=0; $i<strlen($bars); $i++){
  $val=strtolower($bars[$i]);
  if($width){
   $xpos += $val * $scale;
   $width = false;
   continue;
  }
  if(ereg("[a-z]", $val)){
   $val=ord($val)-ord('a')+1;
  } 
  $xpos+=$val*$scale;
  $width=true;
 }
 $total_x = ($xpos) + $space['right'] + $space['right'];
 $xpos = $space['left'];
 if(!function_exists("imagecreate")){
  print "Es necesaria tener la extension gd2 activada en PHP<BR>\n";
  return "";
 }
 $im = imagecreate($total_x, $total_y);
 if (barcodeTransparent == "false"){
  $col_bg = imageColorAllocate($im,$bg_color[0],$bg_color[1],$bg_color[2]);
 }
 else{
  $col_bg = imagecolorallocatealpha($im,$bg_color[0],$bg_color[1],$bg_color[2],127);
 }
 $col_bar = imageColorAllocate($im,$bar_color[0],$bar_color[1],$bar_color[2]);
 $col_text = imageColorAllocate($im,$text_color[0],$text_color[1],$text_color[2]);
 $height = round($total_y - ($scale * alignedBarsBottom));
 $height2=round($total_y-$space['bottom']);

 $width=true;
 for($i=0;$i<strlen($bars);$i++){
  $val=strtolower($bars[$i]);
  if($width){
   $xpos+=$val*$scale;
   $width=false;
   continue;
  }
  if(ereg("[a-z]", $val)){
   $val=ord($val)-ord('a')+1;
   $h=$height2;
  }
  else
   $h=$height;
  imagefilledrectangle($im, $xpos, $space['top'], $xpos+($val*$scale)-1, $h, $col_bar);
  $xpos+=$val*$scale;
  $width=true;
 }

 global $_SERVER;
 $chars=explode(" ", $text);
 reset($chars);
 while (list($n, $v)=each($chars)){
  if(trim($v)){
   $inf=explode(":", $v);
   $fontsize=$scale*($inf[1]/1.8);
   $fontheight=$total_y-($fontsize/2.7)+1;
   @imagettftext($im, $fontsize, 0, $space['left']+($scale*$inf[0])+2,$fontheight, $col_text, $font_loc, $inf[2]);
  }
 }

 $mode=strtolower($mode);
 if($mode=='jpg' || $mode=='jpeg'){
  header("Content-Type: image/jpeg; name=\"".$code.".jpg\"");
  imagejpeg($im);
 }
 else
  if ($mode=='gif'){
   header("Content-Type: image/gif; name=\"".$code.".gif\"");
   imagegif($im);
  }
  else {
   header("Content-Type: image/png; name=\"".$code.".png\"");
   imagepng($im);
  }
}

function barcode_outtext($code,$bars){
 $width=true;
 $xpos=$heigh2=0;
 $bar_line="";
 for ($i=0;$i<strlen($bars);$i++){
  $val=strtolower($bars[$i]);
  if ($width){
   $xpos+=$val;
   $width=false;
   for ($a=0;$a<$val;$a++) $bar_line.="-";
   continue;
  }
  if (ereg("[a-z]", $val)){
   $val=ord($val)-ord('a')+1;
   $h=$heigh2;
   for ($a=0;$a<$val;$a++) $bar_line.="I";
  }
  else
   for ($a=0;$a<$val;$a++) $bar_line.="#";
  $xpos+=$val;
  $width=true;
 }
 return $bar_line;
}

function barcode_encode_genbarcode($code,$encoding){
 global $genbarcode_loc;
 if (eregi("^ean$", $encoding) && strlen($code)==13) $code=substr($code,0,12);
 if (!$encoding) $encoding="EAN13";
 $encoding=ereg_replace("[|\\]", "_", $encoding);
 $code=ereg_replace("[|\\]", "_", $code);
 $cmd=$genbarcode_loc." \""
 .str_replace("\"", "\\\"",$code)."\" \""
 .str_replace("\"", "\\\"",strtoupper($encoding))."\"";
 $fp=popen($cmd, "r");
 if ($fp){
  $bars=fgets($fp, 1024);
  $text=fgets($fp, 1024);
  $encoding=fgets($fp, 1024);
  pclose($fp);
 }
 else return false;
 $ret=array(
  "encoding" => trim($encoding),
  "bars" => trim($bars),
  "text" => trim($text)
 );
 if (!$ret['encoding']) return false;
 if (!$ret['bars']) return false;
 if (!$ret['text']) return false;
 return $ret;
}

function barcode_encode($code, $encoding){
 global $genbarcode_loc;
 if(
  ((eregi("^ean$", $encoding) &&
  ( strlen($code)==12 || strlen($code)==13)))
  || (($encoding) && (eregi("^isbn$", $encoding))
  && (( strlen($code)==9 || strlen($code)==10) ||
  (((ereg("^978", $code) && strlen($code)==12) ||
  (strlen($code)==13)))))
  || (( !isset($encoding) || !$encoding || (eregi("^ANY$", $encoding) ))
  && (ereg("^[0-9]{12,13}$", $code)))
  ){
  $bars=barcode_encode_ean($code, $encoding);
 }
 return $bars;
}

function barcode_print($code, $encoding, $scale, $mode){
 $bars=barcode_encode($code, $encoding);
 if (!$bars) return;
 if (!$mode) $mode="png";
 barcode_outimage($bars['text'],$bars['bars'],$scale, $mode);
 return $bars;
}

?>