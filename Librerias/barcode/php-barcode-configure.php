<?php
// Archivo de configuracion para encode_bars.php & php-barcode.php
// Created by vaLar (07-Jun-2008)

// Tipo de archivo a generar (jpg | jpeg | png | gif)?
define(mode,"jpg");

// Tipo de codificacion (EAN | ISBN)?
define(encoding,"EAN");

// Se requiere una imagen transparente?
define(barcodeTransparent,"false");

// Altura de la imagen en pixeles
define(barcodeHeight,80);

// Se debe mostrar la cadena numerica en el codigo?
define(showNumbersInBarCode,"true");

// Auto-alineacion de las barras con los numeros (No es necesario cambiar aqui)
(showNumbersInBarCode == "true") ? define(alignedBarsBottom,11) : define(alignedBarsBottom,2);

// Color de las barras (R,G,B)
$bar_color = Array(0,0,0);

// Color de fondo del codigo de barras (R,G,B)
$bg_color  = Array(255,255,255);

//Color de la cadena numerica (R,G,B)
$text_color= Array(0,0,0);
?>