<?php 
/**
* Descripción: Páina de publicidad introductoria de macros
* Fecha de creación:	2013-05-22
* Desarrollador:	Lewis Chimarro 
*/
require_once('../../Librerias/procedimientos/almacenados_standar.php'); 
require_once('../LOGICA/adm_log_intro.php'); 

/**
 * objeto para la conexion
 * @var Class_Log_Conexion_Adm
 */
$obBD_conexion = new Class_Log_Conexion_Log;

/**
 * objeto para consultas
 * @var Class_Log_Datos_Adm
 */
$obBD_con1 =  new Class_Log_Datos_Log;

$hoy = date("Y-m-d");
/**
* Consultas las imágenes no caducadas
*/
$row_galeria = $obBD_con1->getArrayConsulta(1, $hoy.'*'.$Ses_Emp_Cod, $obBD_conexion);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

	<!--
		Supersized - Fullscreen Slideshow jQuery Plugin
		Version : 3.2.7
		Site	: www.buildinternet.com/project/supersized
		
		Author	: Sam Dunn
		Company : One Mighty Roar (www.onemightyroar.com)
		License : MIT License / GPL License
	-->

	<head>

		<title>Intro</title>
		<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
		<link rel="stylesheet" href="../../Librerias/slideshow/css/supersized.css" type="text/css" media="screen" />
		<link rel="stylesheet" href="../../Librerias/slideshow/theme/supersized.shutter.css" type="text/css" media="screen" />
		
		<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js"></script>
		<script type="text/javascript" src="../../Librerias/slideshow/js/jquery.easing.min.js"></script>
		
		<script type="text/javascript" src="../../Librerias/slideshow/js/supersized.3.2.7.min.js"></script>
		<script type="text/javascript" src="../../Librerias/slideshow/theme/supersized.shutter.min.js"></script>
		
		<script type="text/javascript">
			
			jQuery(function($){
				
				$.supersized({
				
					// Functionality
					slideshow               :   1,			// Slideshow on/off
					autoplay				:	1,			// Slideshow starts playing automatically
					start_slide             :   1,			// Start slide (0 is random)
					stop_loop				:	0,			// Pauses slideshow on last slide
					random					: 	0,			// Randomize slide order (Ignores start slide)
					slide_interval          :   3000,		// Length between transitions
					transition              :   6, 			// 0-None, 1-Fade, 2-Slide Top, 3-Slide Right, 4-Slide Bottom, 5-Slide Left, 6-Carousel Right, 7-Carousel Left
					transition_speed		:	1000,		// Speed of transition
					new_window				:	1,			// Image links open in new window/tab
					pause_hover             :   0,			// Pause slideshow on hover
					keyboard_nav            :   1,			// Keyboard navigation on/off
					performance				:	1,			// 0-Normal, 1-Hybrid speed/quality, 2-Optimizes image quality, 3-Optimizes transition speed // (Only works for Firefox/IE, not Webkit)
					image_protect			:	1,			// Disables image dragging and right click with Javascript
															   
					// Size & Position						   
					min_width		        :   0,			// Min width allowed (in pixels)
					min_height		        :   0,			// Min height allowed (in pixels)
					vertical_center         :   1,			// Vertically center background
					horizontal_center       :   1,			// Horizontally center background
					fit_always				:	0,			// Image will never exceed browser width or height (Ignores min. dimensions)
					fit_portrait         	:   1,			// Portrait images will not exceed browser height
					fit_landscape			:   0,			// Landscape images will not exceed browser width
															   
					// Components							
					slide_links				:	'blank',	// Individual links for each slide (Options: false, 'num', 'name', 'blank')
					thumb_links				:	1,			// Individual thumb links for each slide
					thumbnail_navigation    :   0,			// Thumbnail navigation
					slides 					:  	[			// Slideshow Images
					<?Php 
					/** 
					* Contador para no poner la coma al final
					*/
					$cont = 0;
					foreach($row_galeria as $row) 
					{ 
						$cont++; ?>
						{image : '../../storage/FRONT/<?Php echo $row['Upl_Rut']; ?>', title : 'T&iacute;tulo: <?Php echo $row['Upl_Des']; ?>', thumb : '../../storage/FRONT/<?Php echo $row['Upl_Rut']; ?>', url : '../../storage/FRONT/<?Php echo $row['Upl_Rut']; ?>'}
					<?Php 
						if ($cont < count($row_galeria)){ echo ", "; } 
					} ?>														 			
								],
												
					// Theme Options			   
					progress_bar			:	1,			// Timer for each slide							
					mouse_scrub				:	0
					
				});
		    });
		    
		</script>
		
	</head>
	
	<style type="text/css">
		ul#demo-block{ margin:0 15px 15px 15px; }
			ul#demo-block li{ margin:0 0 10px 0; padding:10px; display:inline; float:left; clear:both; color:#aaa; background:url('../../Librerias/slideshow/img/bg-black.png'); font:11px Helvetica, Arial, sans-serif; }
			ul#demo-block li a{ color:#eee; font-weight:bold; }
	</style>

<body>

	<!--Demo styles (you can delete this block)-->
	
	<ul id="demo-block">
		<li>Fotograf&iacute;as: <a href="#"></a></li>
	</ul>
	
	<!--End of styles-->

	<!--Thumbnail Navigation-->
	<div id="prevthumb"></div>
	<div id="nextthumb"></div>
	
	<!--Arrow Navigation-->
	<a id="prevslide" class="load-item"></a>
	<a id="nextslide" class="load-item"></a>
	
	<div id="thumb-tray" class="load-item">
		<div id="thumb-back"></div>
		<div id="thumb-forward"></div>
	</div>
	
	<!--Time Bar-->
	<div id="progress-back" class="load-item">
		<div id="progress-bar"></div>
	</div>
	
	<!--Control Bar-->
	<div id="controls-wrapper" class="load-item">
		<div id="controls">
			
			<a id="play-button"><img id="pauseplay" src="../../Librerias/slideshow/img/pause.png"/></a>
		
			<!--Slide counter-->
			<div id="slidecounter">
				<span class="slidenumber"></span> / <span class="totalslides"></span>
			</div>
			
			<!--Slide captions displayed here-->
			<div id="slidecaption"></div>
			
			<!--Thumb Tray button-->
			<a id="tray-button"><img id="tray-arrow" src="../../Librerias/slideshow/img/button-tray-up.png"/></a>
			
			<!--Navigation-->
			<ul id="slide-list"></ul>
			
		</div>
	</div>

</body>
</html>