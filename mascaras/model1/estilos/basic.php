<?php
/*
Alias:	basic
Descripción: CSS base (interfaz, forms, Bootstrap 3.3.5) sin jQuery.
NOTA: jQuery ya es provisto por el mask jqgrid5 (jquery-2.1.4). El antiguo
jquery-1.5.2 (Librerias/fixed-header-table) fue eliminado para evitar
doble-jQuery en pantallas que combinan basic.php + jqgrid.php.
*/
?>
<meta charset="iso-8859-1" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<link rel="shortcut icon" type="image/x-icon" href="../../imagenes/ingresar/favicon.png" />
<link href="../../mascaras/model1/estilos/interfaz.css" rel="stylesheet" type="text/css" charset=""/>
<link href="../../mascaras/model1/estilos/forms.css" rel="stylesheet" type="text/css" />
<!-- FIX: Bootstrap 3.3.5 (was Bootstrap 2.x causing rendering conflicts) -->
<link href="../../framework/jquery/bootstrap/bootstrap-3.3.5/css/bootstrap.min.css" rel="stylesheet">
