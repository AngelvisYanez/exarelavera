<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?Php
/**
 * Permite actualizar los datos de + empresa
 *
 * @author Lewis Chimarro
 * @version 1.0
 * Fecha de creación:	2013-02-12
 *
 * @package administrador.FRONT
 */
require_once('../LOGICA/seguridad.php');	  
require_once('../LOGICA/adm_log_ckeditor.php');
require_once('../../Librerias/postclass.php');

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

/**
 * Llamado de la libreria para evitar el reenvio de datos
 * @var Post_Block
 */
$thisPost = new Post_Block;

/**
* Control que obtiene los datos del editor (enrriquecido)
*/
if ($thisPost->postBlock($_POST['postID']))
{
	if ( isset($editor1) )
	{
		if ( isset( $_POST ) )
			$postArray = &$_POST ;			// 4.1.0 or later, use $_POST
		else
			$postArray = &$HTTP_POST_VARS ;	// prior to 4.1.0, use HTTP_POST_VARS
		
		foreach ( $postArray as $sForm => $value )
		{
			if ( get_magic_quotes_gpc() )
				$postedValue = htmlspecialchars( stripslashes( $value ) ) ;
			else
				$postedValue = htmlspecialchars( $value ) ;
		}
	
		/**
		* Actualización de los datos en +empresas
		*/
		$obBD_con1->insertUpdateDelete(2,$Ses_Emp_Cod.'*'.$postedValue,$obBD_conexion);
	}
}//if ($thisPost->postBlock($_POST['postID']))

/**
* Consulta los datos del +empresa
*/
$row_empresa = $obBD_con1->getRowConsulta(1, $Ses_Emp_Cod,$obBD_conexion);
?>
<!--
Copyright (c) 2003-2013, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
-->
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?Php echo $Ses_Sys_Nom; ?></title>
	<meta content="text/html; charset=utf-8" http-equiv="content-type" />
	<script type="text/javascript" src="../../Librerias/ckeditor/ckeditor.js"></script>
	<script src="../../Librerias/ckeditor/_samples/sample.js" type="text/javascript"></script>
	<link href="../../Librerias/ckeditor/_samples/sample.css" rel="stylesheet" type="text/css" />
		<?Php require_once("../../mascaras/model1/estilos/estilos.php")?>        
        <!--Librerias para interfaz -->               
	    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
   
	<script type="text/javascript">
	//<![CDATA[

// The instanceReady event is fired, when an instance of CKEditor has finished
// its initialization.
CKEDITOR.on( 'instanceReady', function( ev )
{
	// Show the editor name and description in the browser status bar.
	document.getElementById( 'eMessage' ).innerHTML = '<p>Instance <code>' + ev.editor.name + '<\/code> loaded.<\/p>';

	// Show this sample buttons.
	 document.getElementById( 'eButtons' ).style.display = 'block';
});

function InsertHTML()
{
	// Get the editor instance that we want to interact with.
	var oEditor = CKEDITOR.instances.editor1;
	var value = document.getElementById( 'htmlArea' ).value;

	// Check the active editing mode.
	if ( oEditor.mode == 'wysiwyg' )
	{
		// Insert HTML code.
		// http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.editor.html#insertHtml
		oEditor.insertHtml( value );
	}
	else
		alert( 'You must be in WYSIWYG mode!' );
}

function InsertText()
{
	// Get the editor instance that we want to interact with.
	var oEditor = CKEDITOR.instances.editor1;
	var value = document.getElementById( 'txtArea' ).value;

	// Check the active editing mode.
	if ( oEditor.mode == 'wysiwyg' )
	{
		// Insert as plain text.
		// http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.editor.html#insertText
		oEditor.insertText( value );
	}
	else
		alert( 'You must be in WYSIWYG mode!' );
}

function SetContents()
{
	// Get the editor instance that we want to interact with.
	var oEditor = CKEDITOR.instances.editor1;
	var value = document.getElementById( 'htmlArea' ).value;

	// Set editor contents (replace current contents).
	// http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.editor.html#setData
	oEditor.setData( value );
}

function GetContents()
{
	// Get the editor instance that you want to interact with.
	var oEditor = CKEDITOR.instances.editor1;

	// Get editor contents
	// http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.editor.html#getData
	alert( oEditor.getData() );
}

function ExecuteCommand( commandName )
{
	// Get the editor instance that we want to interact with.
	var oEditor = CKEDITOR.instances.editor1;

	// Check the active editing mode.
	if ( oEditor.mode == 'wysiwyg' )
	{
		// Execute the command.
		// http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.editor.html#execCommand
		oEditor.execCommand( commandName );
	}
	else
		alert( 'You must be in WYSIWYG mode!' );
}

function CheckDirty()
{
	// Get the editor instance that we want to interact with.
	var oEditor = CKEDITOR.instances.editor1;
	// Checks whether the current editor contents present changes when compared
	// to the contents loaded into the editor at startup
	// http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.editor.html#checkDirty
	alert( oEditor.checkDirty() );
}

function ResetDirty()
{
	// Get the editor instance that we want to interact with.
	var oEditor = CKEDITOR.instances.editor1;
	// Resets the "dirty state" of the editor (see CheckDirty())
	// http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.editor.html#resetDirty
	oEditor.resetDirty();
	alert( 'The "IsDirty" status has been reset' );
}

	//]]>
	</script>

</head>
<body>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
<tr class="BarraTitulo">
  <td height="10">&raquo; Registrar Datos de la Empresa</td>
</tr>
<tr>
	<td>
	<form action="<?Php echo $_SERVER['PHP_SELF']; ?>" method="post">
    <? $thisPost->startPost(); ?>		  
	<br />
		<textarea cols="100" id="editor1" name="editor1" rows="10">		
        <?Php 
		echo $row_empresa['Emp_Who'];
		?>
        </textarea>
			<script type="text/javascript">
			//<![CDATA[

				CKEDITOR.replace( 'editor1',
					{
						skin : 'office2003'
					});

			//]]>
			</script>
	</form>
    </td>
	</tr>
</table>
</body>
</html>
<?Php
/**
* Cerrado de las conexiones 
*/
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>