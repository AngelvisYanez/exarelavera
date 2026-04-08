<?php 
if(isset($_GET['type'])&&isset($_GET['Doc_Cod']))
    header('Location: '."facturacion/COMPONENTES/tesPdfElectronicos.php?type=$_GET[type]&Doc_Cod=$_GET[Doc_Cod]");
if(isset($_GET['clave']))
    header('Location: '."facturacion/COMPONENTES/tesPdfElectronicos.php?clave=$_GET[clave]");

?>
<!DOCTYPE html>
<HTML>
<HEAD>		
    <TITLE>EXA - Facturacion Electrónica</TITLE>
    <meta charset="iso-8859-1" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="shortcut icon" type="image/x-icon" href="./mascaras/model1/img/logo/exa-ico-2.png" />

    <link rel="stylesheet" type="text/css" media="screen" href="./framework/jquery/jquery.ui/jquery-ui-1.11.4/jquery-ui.min.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="./framework/jquery/jqgrid/jqgrid-5.1.1/css/ui.jqgrid.css" />		
    <link rel="stylesheet" type="text/css" media="screen" href="./framework/jquery/bootstrap/bootstrap-3.3.5/css/bootstrap.min.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="./framework/jquery/bootstrap/bootstrap-3.3.5/css/bootstrap-theme.min.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="./framework/jquery/jqgrid/jqgrid-5.1.1/css/ui.fix.custom.css?x=9" />
    
    <!--[if lte IE 8]>
            <script src="./framework/plugins/compatibility/html5shiv/html5shiv-3.7.3.js"></script>
            <script src="./framework/plugins/compatibility/respond/respond-1.4.2.js"></script>
    <![endif]-->
    <script type="text/ecmascript" src="./framework/jquery/jquery.min/jquery-2.1.4.min.js"></script> 
    <script type="text/ecmascript" src="./framework/jquery/bootstrap/bootstrap-3.3.5/js/bootstrap.custom.min.js"></script> 
    <script type="text/ecmascript" src="./framework/jquery/jquery.ui/jquery-ui-1.11.4/jquery-ui.min.js"></script> 
    <script type="text/ecmascript" src="./framework/jquery/jquery.ui/jquery-ui-1.11.4/datepicker-es.js"></script> 
    <script type="text/ecmascript" src="./framework/jquery/jqgrid/jqgrid-5.1.1/js/jquery.jqGrid.min.js?x=53"></script>
    <script type="text/ecmascript" src="./Librerias/scripts/generales/jquery.basics-1.0.js?x=12"></script>
    <link rel="stylesheet" type="text/css" media="screen" href="./framework/plugins/fonts/font-awesome/font-awesome-4.4.0/css/font-awesome.min.css" />
    <!--[if !IE]> -->
            <link type="text/css" rel="stylesheet" href="./framework/plugins/animate/animate-3.4.0.min.css" />			
    <!-- <![endif]-->
    <script type="text/javascript"> 
        var UrlSaveJson="";
            $(document).ready(function () {
                    //$.jgrid.defaults.styleUI='Bootstrap';
                    $.jgrid.defaults.regional="es";
                    $.jgrid.defaults.mtype="POST";
                    $.jgrid.defaults.datatype="json";	
                    $(window).on('load',function(){$('.ui-pg-table table.navtable').find('td.ui-pg-button.ui-corner-all').unbind('mouseenter mouseleave').removeClass('ui-pg-button').addClass('btn btn-xs btn-success').find('.ui-pg-div span').removeClass('ui-icon').addClass('glyphicon');});
             });
    </script> 	
    <!-- INICIO J Q U E R Y    L O A D E R -->
    <link rel="stylesheet" href="./framework/jquery/jquery.plugins/loader/jquery.loader.css" />			
    <script>document.write('<div id="loader"></div>');</script>		
    <script>$(document).ready(function(){jQuery("#loader").fadeOut("slow");});</script>    <style></style>

    <style></style>
</HEAD>
<BODY> 
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Documentos Electronicos</h3></div>
        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
           
            <div class="row">
                <div class="col-sm-6"> 
                    <form action="./facturacion/COMPONENTES/tesPdfElectronicos.php" method="post" class="form-horizontal normal">
                        <fieldset class="exa-fieldset" id="prodLowFormTemp">
                            <legend class="Titulos2">Buscar Clave en <b>EXA[Software Contable]</b></legend>
                            <div class="form-group">
                                <label class="col-sm-2 control-label label-sm rrquired">Clave:</label>
                                <div class="col-sm-10">
                                    <div class="input-group input-group-sm">
                                        <input type="text" name="clave" class="form-control input-xs" placeholder="Ingrese Clave a buscar" required="" />
                                        <span class="input-group-btn">
                                            <button class="btn btn-success btn-xs" type="submit" title="Buscar Clave en EXA"><span class="glyphicon glyphicon-search"></span> Buscar en EXA</button>
                                        </span>
                                    </div>
                                </div>   
                            </div>
                        </fieldset>    
                    </form>
                </div>
                <div class="col-sm-6"> 
                    <form action="./facturacion/COMPONENTES/tesPdfElectronicos.php" method="post" enctype="multipart/form-data" class="form-horizontal normal">
                        <fieldset class="exa-fieldset" id="prodLowFormTemp">
                            <legend class="Titulos2">Cargar <b>XML</b>/Consultar Clave en el <b>SRI</b></legend>
                            <div class="form-group">
                                <label class="col-sm-2 control-label label-sm">Logo:</label>
                                <div class="col-sm-10">
                                    <input type="file" name="imagenLogo" id="imagenLogo" value="" accept="image/*" class="form-control input-sm"  />
                                </div>   
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label label-sm">XML:</label>
                                <div class="col-sm-10">
                                    <div class="input-group input-group-sm">
                                        <input type="file" name="archivoXML" id="archivoXML" value="" accept="text/xml" class="form-control input-sm" />
                                        <span class="input-group-btn">
                                            <button class="btn btn-success btn-xs" type="submit" title="Cargar XML"><span class="glyphicon glyphicon-search"></span> Ver PDF</button>
                                        </span>
                                    </div>
                                </div>   
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label label-sm">Clave:</label>
                                <div class="col-sm-10">
                                    <div class="input-group input-group-sm">
                                        <input type="text" name="claveXML" class="form-control input-xs" placeholder="Ingrese Clave a buscar" />
                                        <span class="input-group-btn">
                                            <button class="btn btn-success btn-xs" type="submit" title="Buscar Clave en SRI"><span class="glyphicon glyphicon-search"></span> Buscar en Sri</button>
                                        </span>
                                    </div>
                                </div>   
                            </div>
                        </fieldset>    
                    </form>
                </div>
            </div>
        </div>
    </div>
</BODY>
</HTML>