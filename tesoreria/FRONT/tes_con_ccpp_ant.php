<?php
/**
* @abstract Permite consultar los pagos por vencimiento
* @author Alejandro CAmacho
* @version 1.0
* Fecha de creacion  2021-05-04
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_ccpp_lotes_2.0.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');


/**
* Creacion del Objeto de conexion
*/
$obBD_conexion_set = new Class_Log_Conexion_Ccpp($Ses_Dat_Dis);
$obBD_con_set =  new Class_Log_Datos_Ccpp;

// $obBD_con_set->getPageGrid("asientos.selectWhere",array('rows'=>1000,'page'=>1,'where'=>array('Com_Cod'=>10933),'order'=>'asientos.Asi_Deh asc'), $obBD_conexion_set, true);
// var_dump($obBD_con_set->select("asientos.selectWhere",array('Com_Cod'=>10933,'order'=>'asientos.Asi_Deh asc'), $obBD_conexion_set));

$obBD_conexion_get = new Class_Log_Conexion_Ccpp($Ses_Dat_Dis);
$obBD_con_get =  new Class_Log_Datos_Ccpp;

//fecha y mes actuales
$hoy = date("Y-m-d");
$mes = date("m");


// obtenemos las facturas por pagar
if (isset($ajaxComprobante)) {
  // Contar número de rangos configurados
  $numRangos = 4;
  for ($i = 1; $i <= 20; $i++) {
    if (!isset($_GET['rango' . $i . 'Ini']) || !isset($_GET['rango' . $i . 'Fin']) || empty($_GET['rango' . $i . 'Ini']) || empty($_GET['rango' . $i . 'Fin'])) {
      $numRangos = $i - 1;
      break;
    }
  }
  $_GET['numRangos'] = $numRangos;
  
  $responce['rows'] = $obBD_con_get->getArrayConsulta(557, $_GET, $obBD_conexion_get);
  //AGRUPO POR CLIENTE LA CONSULTA (CON SQL SE DA�ABAN LAS SUMAS AGRUPANDO POR CLIENTE)
  $porCliente = array();
  $contador = 0;
  $inicio = true;

      foreach ($responce['rows'] as $key => $item){ 
          if ($item['Abono'] == $item['Total']){
            unset($responce['rows'][$key]);
          } 
      }

     foreach ($responce['rows'] as $key => $item){
        if($inicio){
           array_push($porCliente, $item);
           $inicio = false;
        }
        else{
          if($item['Prv_Cod'] == $porCliente[$contador]['Prv_Cod']){

              $porCliente[$contador]['PorVencer'] += $item['PorVencer'];
              // Sumar todos los rangos dinámicamente
              for ($i = 1; $i <= $numRangos; $i++) {
                $rangoKey = 'Rango' . $i;
                if (isset($item[$rangoKey])) {
                  if (!isset($porCliente[$contador][$rangoKey])) {
                    $porCliente[$contador][$rangoKey] = 0;
                  }
                  $porCliente[$contador][$rangoKey] += floatval($item[$rangoKey]);
                }
              }
              // Sumar rango último si existe
              if (isset($item['RangoUltimo'])) {
                if (!isset($porCliente[$contador]['RangoUltimo'])) {
                  $porCliente[$contador]['RangoUltimo'] = 0;
                }
                $porCliente[$contador]['RangoUltimo'] += floatval($item['RangoUltimo']);
              }
              $porCliente[$contador]['Saldo'] += $item['Saldo'];
           }
           else{
                array_push($porCliente, $item);
                $contador++;
           }
        } 
     }

    $responce['rows']=$porCliente;
    $responce['records']=count($responce['rows']);
    $responce['numRangos'] = $numRangos; // Enviar número de rangos al frontend
    $obBD_con_get->echoJson($responce);
    exit();
}

if(isset($dataReport)){   
        $responce['success']=false;
        $table['{body}']='';
        $table['{empresa}']=$Ses_Emp_Nom;
        $fecha=explode('-',$hoy);
        $table['{fecha}']=$fecha[2].' de '.mes($fecha[1],1).' de '.$fecha[0];

        // Contar número de rangos configurados
        $numRangos = isset($_POST['numRangos']) ? intval($_POST['numRangos']) : 4;
        $_POST['numRangos'] = $numRangos;
        
        // Preparar datos de rangos para la plantilla
        for ($i = 1; $i <= $numRangos; $i++) {
          $rangoIni = isset($_POST['rango' . $i . 'Ini']) ? $_POST['rango' . $i . 'Ini'] : '';
          $rangoFin = isset($_POST['rango' . $i . 'Fin']) ? $_POST['rango' . $i . 'Fin'] : '';
          $table['{Rango' . $i . '}'] = $rangoIni . ' - ' . $rangoFin;
        }

        $responce['rows'] = $obBD_con_get->getArrayConsulta(557, $_POST, $obBD_conexion_get);
        //AGRUPO POR CLIENTE LA CONSULTA (CON SQL SE DA�ABAN LAS SUMAS AGRUPANDO POR CLIENTE)
        $clientes = $responce['rows'];
        $porCliente = array();
        $contador = 0;
        $inicio = true;

          foreach ($responce['rows'] as $key => $item){ 
              if ($item['Abono'] == $item['Total']){
                unset($responce['rows'][$key]);
              } 
          }

           foreach ($responce['rows'] as $key => $item){
              if($inicio){
                 array_push($porCliente, $item);
                 $inicio = false;
              }
              else{
                if($item['Prv_Cod'] == $porCliente[$contador]['Prv_Cod']){

                    $porCliente[$contador]['PorVencer'] += $item['PorVencer'];
                    // Sumar todos los rangos dinámicamente
                    for ($i = 1; $i <= $numRangos; $i++) {
                      $rangoKey = 'Rango' . $i;
                      if (isset($item[$rangoKey])) {
                        if (!isset($porCliente[$contador][$rangoKey])) {
                          $porCliente[$contador][$rangoKey] = 0;
                        }
                        $porCliente[$contador][$rangoKey] += floatval($item[$rangoKey]);
                      }
                    }
                    // Sumar rango último si existe
                    if (isset($item['RangoUltimo'])) {
                      if (!isset($porCliente[$contador]['RangoUltimo'])) {
                        $porCliente[$contador]['RangoUltimo'] = 0;
                      }
                      $porCliente[$contador]['RangoUltimo'] += floatval($item['RangoUltimo']);
                    }
                    $porCliente[$contador]['Saldo'] += $item['Saldo'];
                 }
                 else{
                      array_push($porCliente, $item);
                      $contador++;
                 }
              } 
           }

        $totales = array();
        $totales['PorVencer'] = 0;
        $totales['Saldo'] = 0;
        for ($i = 1; $i <= $numRangos; $i++) {
          $totales['Rango' . $i] = 0;
        }
        $totales['RangoUltimo'] = 0;

        foreach ($porCliente as $key) {
          $rowHtml = '<tr><td style="border-left: none; border-right: none; border-top: 1px solid #808080; border-bottom: 1px solid #808080; width: 35% !important; min-width: 280px !important; text-align: left;">' . $key['Proveedor'] . '</td>';
          $porVencerFormato = number_format(round($key['PorVencer'],2), 2, '.', ',');
          $rowHtml .= '<td style="border-left: none; border-right: none; border-top: 1px solid #808080; border-bottom: 1px solid #808080; text-align: right;">' . $porVencerFormato . '</td>';
          
          // Agregar columnas de rangos
          for ($i = 1; $i <= $numRangos; $i++) {
            $rangoKey = 'Rango' . $i;
            $valor = isset($key[$rangoKey]) ? round($key[$rangoKey],2) : 0;
            $valorFormato = number_format($valor, 2, '.', ',');
            $rowHtml .= '<td style="border-left: none; border-right: none; border-top: 1px solid #808080; border-bottom: 1px solid #808080; text-align: right;">' . $valorFormato . '</td>';
            $totales[$rangoKey] += $valor;
          }
          
          // Agregar rango último
          $valorUltimo = isset($key['RangoUltimo']) ? round($key['RangoUltimo'],2) : 0;
          $valorUltimoFormato = number_format($valorUltimo, 2, '.', ',');
          $rowHtml .= '<td style="border-left: none; border-right: none; border-top: 1px solid #808080; border-bottom: 1px solid #808080; text-align: right;">' . $valorUltimoFormato . '</td>';
          $totales['RangoUltimo'] += $valorUltimo;
          
          $saldoFormato = number_format(round($key['Saldo'],2), 2, '.', ',');
          $rowHtml .= '<td style="border-left: none; border-right: none; border-top: 1px solid #808080; border-bottom: 1px solid #808080; text-align: right;">' . $saldoFormato . '</td></tr>';
          $table['{body}'] .= $rowHtml;

          $totales['PorVencer'] += $key['PorVencer'];
          $totales['Saldo'] += $key['Saldo'];
        }

         $totalesHtml = '<tr><th style="border-left: none; border-right: none; border-top: 2px solid #000; border-bottom: 1px solid #000; width: 35% !important; min-width: 280px !important; text-align: left;"> TOTALES </th>';
         $totalesPorVencerFormato = number_format(round($totales['PorVencer'],2), 2, '.', ',');
         $totalesHtml .= '<th style="border-left: none; border-right: none; border-top: 2px solid #000; border-bottom: 1px solid #000; text-align: right;">' . $totalesPorVencerFormato . '</th>';
         for ($i = 1; $i <= $numRangos; $i++) {
           $totalRangoFormato = number_format(round($totales['Rango' . $i],2), 2, '.', ',');
           $totalesHtml .= '<th style="border-left: none; border-right: none; border-top: 2px solid #000; border-bottom: 1px solid #000; text-align: right;">' . $totalRangoFormato . '</th>';
         }
         $totalesUltimoFormato = number_format(round($totales['RangoUltimo'],2), 2, '.', ',');
         $totalesHtml .= '<th style="border-left: none; border-right: none; border-top: 2px solid #000; border-bottom: 1px solid #000; text-align: right;">' . $totalesUltimoFormato . '</th>';
         $totalesSaldoFormato = number_format(round($totales['Saldo'],2), 2, '.', ',');
         $totalesHtml .= '<th style="border-left: none; border-right: none; border-top: 2px solid #000; border-bottom: 1px solid #000; text-align: right;">' . $totalesSaldoFormato . '</th></tr>';
         $table['{body}'] .= $totalesHtml;

  // Generar HTML dinámicamente según número de rangos
  $numColumnas = 2 + $numRangos + 2; // Proveedor + Por Vencer + Rangos + Rango Ultimo + Total
  $html = '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
  $html .= '<style>
    #datosTabla {
      border-left: none !important;
      border-right: none !important;
      width: 100% !important;
      table-layout: auto !important;
    }
    #datosTabla thead th[colspan] {
      text-align: center !important;
    }
    #datosTabla tbody td, #datosTabla tbody th {
      border-left: none !important;
      border-right: none !important;
    }
    #datosTabla th:first-child, #datosTabla td:first-child {
      width: 35% !important;
      min-width: 280px !important;
      max-width: none !important;
      text-align: left !important;
    }
    #datosTabla th:not(:first-child):not([colspan]), #datosTabla td:not(:first-child) {
      width: 7% !important;
      max-width: 75px !important;
      text-align: right !important;
    }
  </style>';
  $html .= '<table id="datosTabla" style="width: 100%; word-wrap: break-word; border-collapse: collapse; font-family:Verdana, Geneva, sans-serif; font-size:10px" align="center" cellpadding="3" class="noBorder">';
  $html .= '<thead>';
  $html .= '<tr><th colspan="' . $numColumnas . '" style="border:0; text-align: center !important; width:100%; font-size: 12px; font-weight: bold;">ESTADO DE CUENTA POR PAGAR POR ANTIGUEDAD</th></tr>';
  $html .= '<tr><th colspan="' . $numColumnas . '" style="border:0; text-align: center !important; width:100%; font-size: 12px; font-weight: bold;">' . $table['{empresa}'] . '</th></tr>';
  $html .= '<tr><th colspan="' . $numColumnas . '" style="border:0; text-align: center !important; width:100%; font-size: 12px;">' . $table['{fecha}'] . '</th></tr>';
  
  // Calcular ancho de columnas numéricas (distribuir el 65% restante)
  $numColumnasNumericas = 2 + $numRangos + 2; // Por Vencer + Rangos + Rango Ultimo + Total
  $anchoColumnaNumerica = (65 / $numColumnasNumericas);
  
  // Encabezados de columnas
  $html .= '<tr>';
  $html .= '<th style="width:35% !important; min-width: 280px !important; max-width: none !important; font-size: 10px; border-top: 1px solid #000; border-bottom: 1px solid #000; text-align: left;">PROVEEDOR</th>';
  $html .= '<th style="width:' . round($anchoColumnaNumerica, 2) . '%;font-size: 10px; border-top: 1px solid #000; border-bottom: 1px solid #000; text-align: right;">POR<br/>VENCER</th>';
  for ($i = 1; $i <= $numRangos; $i++) {
    $rangoTexto = $table['{Rango' . $i . '}'];
    $html .= '<th style="width:' . round($anchoColumnaNumerica, 2) . '%;font-size: 10px; border-top: 1px solid #000; border-bottom: 1px solid #000; text-align: right;">' . $rangoTexto . '<br/>d&iacute;as</th>';
  }
  $ultimoFin = isset($_POST['rango' . $numRangos . 'Fin']) ? $_POST['rango' . $numRangos . 'Fin'] : '';
  $html .= '<th style="width:' . round($anchoColumnaNumerica, 2) . '%;font-size: 10px; border-top: 1px solid #000; border-bottom: 1px solid #000; text-align: right;">&gt; ' . $ultimoFin . '<br/>d&iacute;as</th>';
  $html .= '<th style="width:' . round($anchoColumnaNumerica, 2) . '%;font-size: 10px; border-top: 1px solid #000; border-bottom: 1px solid #000; text-align: right;">TOTAL</th>';
  $html .= '</tr>';
  $html .= '</thead>';
  $html .= '<tbody>' . $table['{body}'] . '</tbody>';
  $html .= '</table>';
  
  $responce['html'] = $html;
  $responce['success']=true;
        
  utf8_encode_deep($responce);        
  echo json_encode($responce);
  exit();
}

//obtenemos todos los pagos de una factura de un cliente
if(isset($abonosDetAjax)){
  $responce['rows'] = $obBD_con_get->getArrayConsulta(555, array($abonosDetAjax), $obBD_conexion_get);

  foreach ($responce['rows'] as $key => $item){ 
          if ($item['vencimiento'] == 'Pagado'){
            unset($responce['rows'][$key]);
          } 
      }

  $responce['records']=count($responce['rows']);
  $obBD_con_get->echoJson($responce);
  exit();
}


?>
<!DOCTYPE html>
<html>
<head>
  <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
  <TITLE><?Php echo "Ccxpp Consultar Antiguos [EXA]"; ?></TITLE>
  <meta charset="UTF-8">
  <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
  <?Php
   require_once("../../mascaras/model1/estilos/jqgrid5.php");?>
  <style>
  .txt-green{
    color:#29a827;
  }
  .txt-red{
    color:#ff0000;
  }
  .txt-blue{
    color:#467de8;
  }
  .obs-mayus{
    text-transform:uppercase;
  }
  .btn-sg-pg{
    padding-right: 2;
  }
  #searchGrid_mod .no_padding{padding: 0 !important;}
  #searchGrid_mod .no_padding input[type="text"]{height: 23px;font-size: 14px;font-weight: bold; -moz-appearance:textfield !important;}
  #searchGrid_mod .no_padding input[type="text"]::-webkit-outer-spin-button,
  #searchGrid_mod .no_padding input[type="text"]::-webkit-inner-spin-button {
    -webkit-appearance: none !important;
    margin: 0 !important;
  }
  #searchGrid_mod input[type="text"]:read-only,
  input[type="number"]:read-only{
    background-color:#e8e8e8;
    cursor: not-allowed;
  }
  </style>
</head>
<body>
  <div class="panel panel-main">
    <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Pagos Por Vencimiento</h3></div>
    <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
      
      <div id="listar_cccc">
        <div class="row">
          <form name="searchCccc" id="searchCccc" class="form-horizontal normal" action="javascript:$('#searchGrid_mod').Search('#searchCccc','ajaxComprobante');">

            <div class="col-sm-6">
              <fieldset class="exa-fieldset">
                <legend class="Titulos2">Rangos de vencimiento en d&iacute;as (Historial Acumulado)</legend>
                
                <div id="rangosContainer">
                  <!-- Los rangos se generarán dinámicamente con JavaScript -->
                </div>
                
                <div class="form-group">
                  <div class="col-sm-12">
                    <button type="button" class="btn btn-primary btn-xs" onclick="agregarRango()" title="Agregar Rango">
                      <span class="glyphicon glyphicon-plus"></span> Agregar Rango
                    </button>
                    <button type="button" class="btn btn-danger btn-xs" onclick="eliminarUltimoRango()" title="Eliminar Último Rango">
                      <span class="glyphicon glyphicon-minus"></span> Eliminar Rango
                    </button>
                    <button type="submit" class="btn btn-success btn-xs" title="Buscar Cobros" tabindex="-1" style="min-width: 100px;">
                      <span class="glyphicon glyphicon-search"></span> <span>Buscar</span>
                    </button>
                  </div>
                </div>
              </fieldset>

            </div>
          </form>
        </div>
        <div class="row">
          <div class="col-sm-12">
            <table id="searchGrid_mod" name="searchGrid_mod"></table>
            <div id="sgPager"></div>
            <br>
          </div>

          <div style="padding:15px;">
                  <button onclick="return exportar(true)" title="Imprimir Reporte" type="button" class="btn btn-primary start"> <i class="icon-print icon-white"></i> <span>Imprimir</span></button>   
                  <button onclick="return exportar(false)" class="btn btn-primary start" title="Descargar archivo de Excel"> <i class="icon-share icon-white"></i> <span>Excel</span></button>          
          </div>

        </div>


      </div>
    </div>
  </div>


  <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
  <script src="../VALIDACIONES/tes_val_ccpp_ant_lotes.js?a=60"></script>
  <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
  <script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
  <script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>

</body>
</html>
