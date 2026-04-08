<?php
/**
* @abstract Permite consultar los coboros por vencimiento
* @author Alejandro CAmacho
* @version 1.0
* Fecha de creacion  2021-03-03
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_cccc_lotes_2.0.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');


/* Creacion del Objeto de conexion */
$obBD_conexion_set = new Class_Log_Conexion_Cccc($Ses_Dat_Dis);
$obBD_con_set =  new Class_Log_Datos_Cccc;

// $obBD_con_set->getPageGrid("asientos.selectWhere",array('rows'=>1000,'page'=>1,'where'=>array('Com_Cod'=>10933),'order'=>'asientos.Asi_Deh asc'), $obBD_conexion_set, true);
// var_dump($obBD_con_set->select("asientos.selectWhere",array('Com_Cod'=>10933,'order'=>'asientos.Asi_Deh asc'), $obBD_conexion_set));

$obBD_conexion_get = new Class_Log_Conexion_Cccc($Ses_Dat_Dis);
$obBD_con_get =  new Class_Log_Datos_Cccc;

//fecha y mes actuales
$hoy = date("Y-m-d");
$mes = date("m");


// obtenemos las facturas por cobrar de el cliente seleccionado
if (isset($ajaxComprobante)) {
    $responce = array();
    // Procesar rangos dinámicos
    $numRangos = 0;
    $rangos = array();
    foreach ($_GET as $key => $value) {
        if (preg_match('/^rango(\d+)Ini$/', $key, $matches)) {
            $num = $matches[1];
            $rangos[$num]['Ini'] = $value;
        } elseif (preg_match('/^rango(\d+)Fin$/', $key, $matches)) {
            $num = $matches[1];
            $rangos[$num]['Fin'] = $value;
        }
    }
    ksort($rangos); // Asegurar que los rangos estén ordenados
    $numRangos = count($rangos);
    
    // Si no hay rangos, usar valores por defecto
    if ($numRangos <= 0) {
        $numRangos = 4;
        $rangos[1] = array('Ini' => 1, 'Fin' => 30);
        $rangos[2] = array('Ini' => 31, 'Fin' => 60);
        $rangos[3] = array('Ini' => 61, 'Fin' => 90);
        $rangos[4] = array('Ini' => 91, 'Fin' => 120);
    }

    $_GET['numRangos'] = $numRangos;
    foreach ($rangos as $num => $rango) {
        $_GET['rango' . $num . 'Ini'] = $rango['Ini'];
        $_GET['rango' . $num . 'Fin'] = $rango['Fin'];
    }

    $responce['rows'] = $obBD_con_get->getArrayConsulta(558, $_GET, $obBD_conexion_get);
    if (!is_array($responce['rows'])) {
        $responce['rows'] = array();
    }
  //AGRUPO POR CLIENTE LA CONSULTA (CON SQL SE DA�ABAN LAS SUMAS AGRUPANDO POR CLIENTE)
  $porCliente = array();
  $contador = 0;
  $inicio = true;

      if (!empty($responce['rows'])) {
          foreach ($responce['rows'] as $key => $item){ 
              if (isset($item['Abono']) && isset($item['Total']) && $item['Abono'] == $item['Total']){
                unset($responce['rows'][$key]);
              } 
          }

          foreach ($responce['rows'] as $key => $item){
              // Calcular saldo dinámicamente
              $saldo = floatval($item['PorVencer']);
              for ($i = 1; $i <= $numRangos; $i++) {
                  $rangoKey = 'Rango' . $i;
                  if (isset($item[$rangoKey])) {
                      $saldo += floatval($item[$rangoKey]);
                  }
              }
              if (isset($item['RangoUltimo'])) {
                  $saldo += floatval($item['RangoUltimo']);
              }
              $item['Saldo'] = $saldo;
              
              if($inicio){
                 array_push($porCliente, $item);
                 $inicio = false;
              }
              else{
                  if($item['Cli_Cod'] == $porCliente[$contador]['Cli_Cod']){
                      $porCliente[$contador]['PorVencer'] += floatval($item['PorVencer']);
                      
                      // Sumar rangos dinámicos
                      for ($i = 1; $i <= $numRangos; $i++) {
                          $rangoKey = 'Rango' . $i;
                          if (!isset($porCliente[$contador][$rangoKey])) {
                              $porCliente[$contador][$rangoKey] = 0;
                          }
                          if (isset($item[$rangoKey])) {
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
      }
    $responce['rows']=$porCliente;
    $responce['records']=count($responce['rows']);
    $responce['numRangos'] = $numRangos; // Enviar número de rangos al frontend
    $obBD_con_get->echoJson($responce);
    exit();
}

if(isset($dataReport)){   
        $responce = array();
        $responce['success']=false;
        $table = array();
        $table['{body}']='';
        $table['{empresa}']=$Ses_Emp_Nom;
        $fecha=explode('-',$hoy);
        $table['{fecha}']=$fecha[2].' de '.mes($fecha[1],1).' de '.$fecha[0];

        // Procesar rangos dinámicos
        $numRangos = 0;
        $rangos = array();
        foreach ($_POST as $key => $value) {
            if (preg_match('/^rango(\d+)Ini$/', $key, $matches)) {
                $num = $matches[1];
                $rangos[$num]['Ini'] = $value;
                $table['{Rango' . $num . '}'] = $value . ' - ' . (isset($_POST['rango' . $num . 'Fin']) ? $_POST['rango' . $num . 'Fin'] : '');
            } elseif (preg_match('/^rango(\d+)Fin$/', $key, $matches)) {
                $num = $matches[1];
                $rangos[$num]['Fin'] = $value;
                if (isset($rangos[$num]['Ini'])) {
                    $table['{Rango' . $num . '}'] = $rangos[$num]['Ini'] . ' - ' . $value;
                }
            }
        }
        ksort($rangos);
        $numRangos = count($rangos);
        
        // Si no hay rangos, usar valores por defecto
        if ($numRangos <= 0) {
            $numRangos = 4;
            $rangos[1] = array('Ini' => 1, 'Fin' => 30);
            $rangos[2] = array('Ini' => 31, 'Fin' => 60);
            $rangos[3] = array('Ini' => 61, 'Fin' => 90);
            $rangos[4] = array('Ini' => 91, 'Fin' => 120);
            $table['{Rango1}'] = '1 - 30';
            $table['{Rango2}'] = '31 - 60';
            $table['{Rango3}'] = '61 - 90';
            $table['{Rango4}'] = '91 - 120';
        }

        $_POST['numRangos'] = $numRangos;
        foreach ($rangos as $num => $rango) {
            $_POST['rango' . $num . 'Ini'] = $rango['Ini'];
            $_POST['rango' . $num . 'Fin'] = $rango['Fin'];
        }

        $responce['rows'] = $obBD_con_get->getArrayConsulta(558, $_POST, $obBD_conexion_get);
        if (!is_array($responce['rows'])) {
            $responce['rows'] = array();
        }
        //AGRUPO POR CLIENTE LA CONSULTA (CON SQL SE DA�ABAN LAS SUMAS AGRUPANDO POR CLIENTE)
        $clientes = $responce['rows'];
        $porCliente = array();
        $contador = 0;
        $inicio = true;

          if (!empty($responce['rows'])) {
              foreach ($responce['rows'] as $key => $item){ 
                  if (isset($item['Abono']) && isset($item['Total']) && $item['Abono'] == $item['Total']){
                    unset($responce['rows'][$key]);
                  } 
              }

              foreach ($responce['rows'] as $key => $item){
                  // Calcular saldo dinámicamente
                  $saldo = isset($item['PorVencer']) ? floatval($item['PorVencer']) : 0;
                  for ($i = 1; $i <= $numRangos; $i++) {
                    $rangoKey = 'Rango' . $i;
                    if (isset($item[$rangoKey])) {
                      $saldo += floatval($item[$rangoKey]);
                    }
                  }
                  if (isset($item['RangoUltimo'])) {
                    $saldo += floatval($item['RangoUltimo']);
                  }
                  $item['Saldo'] = $saldo;

                  if($inicio){
                     array_push($porCliente, $item);
                     $inicio = false;
                  }
                  else{
                    if(isset($item['Cli_Cod']) && isset($porCliente[$contador]['Cli_Cod']) && $item['Cli_Cod'] == $porCliente[$contador]['Cli_Cod']){
                        $porCliente[$contador]['PorVencer'] += isset($item['PorVencer']) ? floatval($item['PorVencer']) : 0;
                        
                        // Sumar rangos dinámicos
                        for ($i = 1; $i <= $numRangos; $i++) {
                          $rangoKey = 'Rango' . $i;
                          if (!isset($porCliente[$contador][$rangoKey])) {
                            $porCliente[$contador][$rangoKey] = 0;
                          }
                          if (isset($item[$rangoKey])) {
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
          }

        $totales = array();
        $totales['PorVencer'] = 0;
        $totales['Saldo'] = 0;
        for ($i = 1; $i <= $numRangos; $i++) {
          $totales['Rango' . $i] = 0;
        }
        $totales['RangoUltimo'] = 0;

        foreach ($porCliente as $key) {
          $rowHtml = '<tr><td style="border-left: none; border-right: none; border-top: 1px solid #808080; border-bottom: 1px solid #808080; width: 35% !important; min-width: 280px !important; text-align: left;">' . $key['Cliente'] . '</td>';
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

        // Generar HTML dinámicamente según número de rangos
        $numColumnas = 2 + $numRangos + 2; // Cliente + Por Vencer + Rangos + Rango Ultimo + Total
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
        $html .= '<tr><th colspan="' . $numColumnas . '" style="border:0; text-align: center !important; width:100%; font-size: 12px; font-weight: bold;">ESTADO DE CUENTA POR COBRAR POR ANTIGUEDAD</th></tr>';
        $html .= '<tr><th colspan="' . $numColumnas . '" style="border:0; text-align: center !important; width:100%; font-size: 12px; font-weight: bold;">' . $table['{empresa}'] . '</th></tr>';
        $html .= '<tr><th colspan="' . $numColumnas . '" style="border:0; text-align: center !important; width:100%; font-size: 12px;">' . $table['{fecha}'] . '</th></tr>';
        
        // Calcular ancho de columnas numéricas (distribuir el 65% restante)
        $numColumnasNumericas = 2 + $numRangos + 2; // Por Vencer + Rangos + Rango Ultimo + Total
        $anchoColumnaNumerica = (65 / $numColumnasNumericas);
        
        // Encabezados de columnas
        $html .= '<tr>';
        $html .= '<th style="width:35% !important; min-width: 280px !important; max-width: none !important; font-size: 10px; border-top: 1px solid #000; border-bottom: 1px solid #000; text-align: left;">CLIENTE</th>';
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
         
        $html = str_replace('</tbody>', $totalesHtml . '</tbody>', $html);

  $responce['html'] = $html;
  $responce['success']=true;
        
  utf8_encode_deep($responce);        
  echo json_encode($responce);
  exit();
}

//obtenemos todos los pagos de una factura de un cliente
if(isset($abonosDetAjax)){
  $responce = array();
  $responce['rows'] = $obBD_con_get->getArrayConsulta(333, array($abonosDetAjax), $obBD_conexion_get);
  if (!is_array($responce['rows'])) {
      $responce['rows'] = array();
  }

  if (!empty($responce['rows'])) {
      foreach ($responce['rows'] as $key => $item){ 
              if (isset($item['vencimiento']) && $item['vencimiento'] == 'Pagado'){
                unset($responce['rows'][$key]);
              } 
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
  <!--TITLE><?php echo $Ses_Sys_Nom; ?></TITLE-->
  <title><?php echo "Ccxcc Consultar Antiguos [EXA]"; ?></title>
	<meta charset="UTF-8">
  <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
  <?php
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
  #searchGrid_mod input[type="text"]:read-only{
    background-color:#a2a2a2;
    border: none;
  }
  </style>
</head>
<body>
  <div class="panel panel-main">
    <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Cobros Por Vencimiento</h3></div>
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
                  <button onclick="exportar(true)" title="Imprimir Reporte" type="button" class="btn btn-primary start"> <i class="icon-print icon-white"></i> <span>Imprimir</span></button>   
                  <button onclick="exportar(false)" class="btn btn-primary start" title="Descargar archivo de Excel"> <i class="icon-share icon-white"></i> <span>Excel</span></button>          
          </div>

        </div>


      </div>
    </div>
  </div>


  <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
  <script src="../VALIDACIONES/tes_val_cccc_ant_lotes.js?a=60"></script>
  <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
  <script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
  <script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>

</body>
</html>
